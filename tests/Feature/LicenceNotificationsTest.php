<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\EmitLicence;
use App\Actions\LicenceRequests\FlagLicenceRequestSection;
use App\Actions\LicenceRequests\RejectLicenceRequest;
use App\Actions\LicenceRequests\RequestLicenceRequestChanges;
use App\Actions\LicenceRequests\ResubmitLicenceRequest;
use App\Actions\LicenceRequests\SubmitLicenceRequest;
use App\Actions\LicenceRequests\VerifyLicenceRequest;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\SetOrderPrice;
use App\Data\HolderData;
use App\Enums\LicenceRequestDocumentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestSection;
use App\Enums\NotificationType;
use App\Models\LicenceRequest;
use App\Models\Price;
use App\Models\User;
use App\Notifications\LicenceEmitted;
use App\Notifications\LicenceRequestChangesRequested;
use App\Notifications\LicenceRequestReceived;
use App\Notifications\LicenceRequestRejected;
use App\Notifications\LicenceRequestSubmitted;
use App\Notifications\LicenceRequestVerified;
use App\Notifications\OrderPriceSet;
use Database\Factories\LicenceFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class LicenceNotificationsTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    private const FISCAL_CODE = 'RSSMRA08E14A794X';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('licence-documents');
        Storage::fake('payments');
        Notification::fake();
    }

    public function test_submitting_notifies_the_applicant_and_the_organizers(): void
    {
        $organizer = User::factory()->organizer()->create();
        $admin = User::factory()->admin()->create();
        $adminWhoOptedIn = User::factory()->admin()->create(['notification_preferences' => [NotificationType::LicenceRequestSubmitted->value => true]]);
        $driver = User::factory()->create();
        $licenceRequest = $this->draft();

        app(SubmitLicenceRequest::class)($licenceRequest);

        Notification::assertSentToTimes($licenceRequest->user, LicenceRequestReceived::class, 1);
        Notification::assertSentToTimes($organizer, LicenceRequestSubmitted::class, 1);
        Notification::assertSentToTimes($adminWhoOptedIn, LicenceRequestSubmitted::class, 1);
        Notification::assertNotSentTo($admin, LicenceRequestSubmitted::class);
        Notification::assertNotSentTo($driver, LicenceRequestSubmitted::class);
        Notification::assertNotSentTo($licenceRequest->user, LicenceRequestSubmitted::class);

        $this->assertSafeMail(new LicenceRequestReceived($licenceRequest->fresh()), $licenceRequest->user, ['The organizer checks it and tells you if anything needs to be fixed.']);
        $this->assertSafeMail(new LicenceRequestSubmitted($licenceRequest->fresh()), $organizer, ['Giulia Bianchi']);
    }

    public function test_sending_again_after_changes_notifies_the_organizers_only(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->changesRequested()->withAllDocuments()->create();

        app(ResubmitLicenceRequest::class)($licenceRequest);

        Notification::assertSentToTimes($organizer, LicenceRequestSubmitted::class, 1);
        Notification::assertSentTo($organizer, LicenceRequestSubmitted::class, fn (LicenceRequestSubmitted $notification) => $notification->isResubmission);
        Notification::assertNotSentTo($licenceRequest->user, LicenceRequestReceived::class);
    }

    public function test_requesting_changes_sends_the_applicant_to_the_comments(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->withAllDocuments()->create();

        app(FlagLicenceRequestSection::class)($licenceRequest, $organizer, LicenceRequestSection::Holder, 'The birth place is wrong.');
        app(RequestLicenceRequestChanges::class)($licenceRequest->fresh(), $organizer);

        Notification::assertSentToTimes($licenceRequest->user, LicenceRequestChangesRequested::class, 1);

        $this->assertSafeMail(new LicenceRequestChangesRequested($licenceRequest->fresh()), $licenceRequest->user, [
            'asks you to make changes',
        ]);
    }

    public function test_verifying_asks_the_applicant_to_pay(): void
    {
        config(['races.organizer.bank_account' => 'IT60X0542811101000000123456']);

        $organizer = User::factory()->organizer()->create();
        $licenceRequest = $this->underReview();

        app(VerifyLicenceRequest::class)($licenceRequest, $organizer, 4500);

        Notification::assertSentToTimes($licenceRequest->user, LicenceRequestVerified::class, 1);

        $licenceRequest->refresh();

        $this->assertSafeMail(new LicenceRequestVerified($licenceRequest), $licenceRequest->user, [
            $licenceRequest->amountLabel(),
            'IT60X0542811101000000123456',
            $licenceRequest->ulid,
            'You can also pay by cash or credit card at the organizer\'s office.',
        ]);
    }

    public function test_a_request_covered_by_a_package_has_nothing_to_pay(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = $this->underReview(['payment_status' => LicenceRequestPaymentStatus::Covered]);

        app(VerifyLicenceRequest::class)($licenceRequest, $organizer, null);

        $this->assertSafeMail(new LicenceRequestVerified($licenceRequest->fresh()), $licenceRequest->user, ['covered by your order']);
    }

    public function test_rejecting_tells_the_applicant_why(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->submitted()->create();

        app(RejectLicenceRequest::class)($licenceRequest, $organizer, 'The holder already has a licence for this year.');

        Notification::assertSentToTimes($licenceRequest->user, LicenceRequestRejected::class, 1);

        $this->assertSafeMail(new LicenceRequestRejected($licenceRequest->fresh()), $licenceRequest->user, ['The holder already has a licence for this year.']);
    }

    public function test_emitting_links_the_applicant_to_the_licence(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->verified()->create(['payment_status' => LicenceRequestPaymentStatus::Covered]);

        $licence = app(EmitLicence::class)($licenceRequest, $organizer, 'A123456', 2027, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));

        Notification::assertSentToTimes($licenceRequest->user, LicenceEmitted::class, 1);

        $this->assertSafeMail(new LicenceEmitted($licence), $licenceRequest->user, [(string) $licence->validity_year]);
    }

    public function test_pricing_an_order_asks_the_buyer_to_pay(): void
    {
        $organizer = User::factory()->organizer()->create();
        $order = (new PlaceOrder)(User::factory()->create(), Price::factory()->range(23000, 31000)->create()->product);

        $order = (new SetOrderPrice)($order, $organizer, 25000);

        Notification::assertSentToTimes($order->user, OrderPriceSet::class, 1);

        $this->assertSafeMail(new OrderPriceSet($order), $order->user, [$order->amountLabel(), $order->ulid]);
    }

    public function test_turning_off_the_email_keeps_the_notification_in_the_app(): void
    {
        $organizer = User::factory()->organizer()->create(['notification_preferences' => [NotificationType::LicenceRequestSubmitted->value => false]]);
        $adminWhoOptedIn = User::factory()->admin()->create(['notification_preferences' => [NotificationType::LicenceRequestSubmitted->value => true]]);
        $licenceRequest = $this->draft();
        $licenceRequest->user->update(['notification_preferences' => [NotificationType::LicenceRequestReceived->value => false]]);

        app(SubmitLicenceRequest::class)($licenceRequest);

        Notification::assertSentTo($licenceRequest->user, LicenceRequestReceived::class, fn ($notification, array $channels) => array_values($channels) === ['database']);
        Notification::assertSentTo($organizer, LicenceRequestSubmitted::class, fn ($notification, array $channels) => array_values($channels) === ['database']);
        Notification::assertSentTo($adminWhoOptedIn, LicenceRequestSubmitted::class, fn ($notification, array $channels) => array_values($channels) === ['mail', 'database']);
    }

    public function test_notifications_are_in_the_language_of_the_user(): void
    {
        $licenceRequest = $this->draft();
        $licenceRequest->user->update(['locale' => 'it']);

        app(SubmitLicenceRequest::class)($licenceRequest);

        Notification::assertSentTo($licenceRequest->user, LicenceRequestReceived::class, fn ($notification, $channels, $notifiable, ?string $locale) => $locale === 'it');
    }

    public function test_the_language_in_use_is_remembered_for_the_notifications(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('language.change', ['lang' => 'it']));
        $this->actingAs($user)->get(route('dashboard'));

        $this->assertEquals('it', $user->fresh()->locale);
    }

    public function test_the_app_copy_links_to_the_request(): void
    {
        $licenceRequest = $this->draft();

        app(SubmitLicenceRequest::class)($licenceRequest);

        Notification::assertSentTo(
            $licenceRequest->user,
            LicenceRequestReceived::class,
            fn (LicenceRequestReceived $notification, array $channels, User $notifiable) => $notification->toArray($notifiable) === ['licence_request' => $licenceRequest->ulid],
        );
    }

    /**
     * Render the mail and check it shows the given texts, links through the notification kept in the app and shows
     * no personal document or fiscal code.
     *
     * @param  array<int, string>  $expected
     */
    private function assertSafeMail(BaseNotification $notification, User $notifiable, array $expected): void
    {
        $notification->id = (string) Str::uuid();

        /** @var MailMessage $mail */
        $mail = $notification->toMail($notifiable);
        $html = html_entity_decode((string) $mail->render(), ENT_QUOTES);

        $this->assertStringStartsWith(route('notifications.open', $notification->id), (string) $mail->actionUrl);
        $this->assertEmpty($mail->attachments);
        $this->assertEmpty($mail->rawAttachments);
        $this->assertStringNotContainsString(self::FISCAL_CODE, $html);
        $this->assertStringContainsString('Manage your email notifications', $html);
        $this->assertStringContainsString(route('notifications.edit'), $html);

        foreach ($expected as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    private function draft(): LicenceRequest
    {
        return LicenceRequest::factory()->withLicenceAndHolder($this->holder())->withAllDocuments()->create();
    }

    /**
     * A submitted request with every document viewed, ready to be verified.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function underReview(array $attributes = []): LicenceRequest
    {
        $licenceRequest = LicenceRequest::factory()->submitted()->withLicenceAndHolder($this->holder())->withAllDocuments()->create($attributes);

        $licenceRequest->documents()->update(['status' => LicenceRequestDocumentStatus::Accepted]);

        return $licenceRequest->fresh();
    }

    private function holder(): HolderData
    {
        $holder = LicenceFactory::holder('Giulia', 'Bianchi');
        $holder->fiscal_code = self::FISCAL_CODE;

        return $holder;
    }
}

<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\NotificationType;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * A notification kept in the app, as shown in the inbox. The text is written when shown, in the language in use,
 * from what it is about: the row stores only its identifier.
 */
final readonly class NotificationItem
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $detail,
        public ?string $url,
        public bool $isRead,
        public CarbonInterface $createdAt,
    ) {}

    /**
     * The inbox items of the given notifications, loading what they are about at once.
     *
     * @param  iterable<int, DatabaseNotification>  $notifications
     * @return Collection<int, self>
     */
    public static function forNotifications(iterable $notifications): Collection
    {
        $notifications = collect($notifications);

        $ulidsOf = fn (string $key) => $notifications->pluck("data.{$key}")->filter()->unique()->values()->all();

        $licenceRequests = LicenceRequest::query()->whereIn('ulid', $ulidsOf('licence_request'))->get()->keyBy('ulid');
        $licences = Licence::query()->whereIn('ulid', $ulidsOf('licence'))->get()->keyBy('ulid');
        $orders = Order::query()->whereIn('ulid', $ulidsOf('order'))->get()->keyBy('ulid');
        $enrollments = Enrollment::query()->whereIn('ulid', $ulidsOf('enrollment'))->with(['championship', 'licence', 'licenceRequest'])->get()->keyBy('ulid');

        return $notifications->map(function (DatabaseNotification $notification) use ($licenceRequests, $licences, $orders, $enrollments) {
            $type = NotificationType::fromNotificationClass($notification->type);

            /** @var array{licence_request?: string, licence?: string, order?: string, enrollment?: string} $data */
            $data = $notification->data;

            [$detail, $url] = match (true) {
                isset($data['licence_request']) => self::describeLicenceRequest($licenceRequests->get($data['licence_request']), $type),
                isset($data['licence']) => self::describeLicence($licences->get($data['licence'])),
                isset($data['order']) => self::describeOrder($orders->get($data['order']), $type),
                isset($data['enrollment']) => self::describeEnrollment($enrollments->get($data['enrollment'])),
                default => [null, null],
            };

            return new self(
                id: $notification->getKey(),
                title: $type?->label() ?? __('Notification'),
                detail: $detail,
                url: $url,
                isRead: $notification->read_at !== null,
                createdAt: $notification->created_at,
            );
        });
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private static function describeLicenceRequest(?LicenceRequest $licenceRequest, ?NotificationType $type): array
    {
        if ($licenceRequest === null) {
            return [null, null];
        }

        $url = match ($type) {
            NotificationType::LicenceRequestSubmitted => route('licence-request-reviews.show', $licenceRequest),
            NotificationType::LicenceRequestChangesRequested => route('licence-requests.edit', $licenceRequest),
            default => route('licence-requests.show', $licenceRequest),
        };

        return [collect([$licenceRequest->licenceTypeLabel(), $licenceRequest->holder?->fullName()])->filter()->join(' · '), $url];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private static function describeLicence(?Licence $licence): array
    {
        if ($licence === null) {
            return [null, null];
        }

        return [
            collect([$licence->licenceTypeLabel(), $licence->holder->fullName(), (string) $licence->validity_year])->filter()->implode(' · '),
            route('my-licences.show', $licence),
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private static function describeEnrollment(?Enrollment $enrollment): array
    {
        if ($enrollment === null) {
            return [null, null];
        }

        return [collect([$enrollment->championship->title, $enrollment->holder()?->fullName()])->filter()->join(' · '), route('my-enrollments.show', $enrollment)];
    }

    /**
     * The Order's page, or the Enrollment's page when the Order pays an Enrollment: drivers see it as its payment. A paid
     * renewal still to start leads to My licences.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function describeOrder(?Order $order, ?NotificationType $type): array
    {
        if ($order === null) {
            return [null, null];
        }

        if ($type === NotificationType::OrderPaymentConfirmed && $order->canStartLicenceRenewal()) {
            return [__('Order :order', ['order' => $order->ulid]), route('my-licences.index')];
        }

        $enrollment = Enrollment::query()->where('order_id', $order->getKey())->with('championship')->latest('id')->first();

        if ($enrollment !== null) {
            return [__('Enrollment in :championship', ['championship' => $enrollment->championship->title]), route('my-enrollments.show', $enrollment)];
        }

        return [__('Order :order', ['order' => $order->ulid]), route('my-orders.show', $order)];
    }
}

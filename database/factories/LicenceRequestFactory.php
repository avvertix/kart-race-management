<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\HolderData;
use App\Data\RequiredDocument;
use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\LaravelData\DataCollection;

/**
 * A draft renewal with nothing filled in.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenceRequest>
 */
class LicenceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => LicenceRequestStatus::Draft,
            'payment_status' => LicenceRequestPaymentStatus::AwaitingPrice,
            'parents' => HolderData::collect([], DataCollection::class),
        ];
    }

    /**
     * The licence and holder sections filled in: an adult renewing an ACI NAZ E licence.
     */
    public function withLicenceAndHolder(?HolderData $holder = null): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => LicenceProvider::Aci,
            'licence_type' => 'naz-e',
            'previous_licence_number' => (string) fake()->numberBetween(100000, 999999),
            'holder' => $holder ?? LicenceFactory::holder(),
        ]);
    }

    /**
     * An underage holder with one parent.
     */
    public function underage(): static
    {
        $holder = LicenceFactory::holder('Luca', 'Rossi');
        $holder->birth_date = now()->subYears(12)->toDateString();

        return $this->withLicenceAndHolder($holder)->state(fn (array $attributes) => [
            'parents' => HolderData::collect([LicenceFactory::holder('Mario', 'Rossi')], DataCollection::class),
        ]);
    }

    public function submitted(): static
    {
        return $this->withLicenceAndHolder()->state(fn (array $attributes) => [
            'status' => LicenceRequestStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function changesRequested(): static
    {
        return $this->submitted()->state(fn (array $attributes) => [
            'status' => LicenceRequestStatus::ChangesRequested,
        ]);
    }

    /**
     * Every document and form the request asks for uploaded, waiting for review.
     */
    public function withAllDocuments(): static
    {
        return $this->afterCreating(function (LicenceRequest $licenceRequest) {
            $licenceRequest->requiredDocuments(LicenceDocumentKind::Upload)
                ->merge($licenceRequest->requiredDocuments(LicenceDocumentKind::SignedForm))
                ->each(fn (RequiredDocument $required) => LicenceRequestDocument::factory()->for($licenceRequest)->create([
                    'document' => $required->document,
                    'parent_index' => $required->parentIndex,
                ]));

            $licenceRequest->unsetRelation('documents');
        });
    }

    /**
     * The organizer set the amount, in cents, and waits for the payment.
     */
    public function awaitingPayment(int $amount = 5000): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => LicenceRequestPaymentStatus::AwaitingPayment,
            'amount' => $amount,
            'priced_at' => now(),
        ]);
    }
}

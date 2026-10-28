<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceDocumentScope;
use App\Enums\LicenceFormTemplate;
use App\Models\LicenceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenceTypeDocument>
 */
class LicenceTypeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'licence_type_id' => LicenceType::factory(),
            'name' => 'Identity card',
            'description' => null,
            'kind' => LicenceDocumentKind::Upload,
            'template' => null,
            'scope' => LicenceDocumentScope::Holder,
            'underage_only' => false,
            'required' => true,
            'sort' => 0,
        ];
    }

    public function signedForm(LicenceFormTemplate $template = LicenceFormTemplate::SelfDeclaration): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $template->label(),
            'kind' => LicenceDocumentKind::SignedForm,
            'template' => $template,
        ]);
    }
}

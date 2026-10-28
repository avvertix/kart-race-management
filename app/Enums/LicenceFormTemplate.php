<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The forms that can be given to an applicant pre-filled with their details.
 */
enum LicenceFormTemplate: string
{
    case Delegation = 'delegation';
    case Privacy = 'privacy';
    case ParentalAuthorisation = 'parental_authorisation';
    case SelfDeclaration = 'self_declaration';

    /**
     * The templates as a value => label map.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $template) => [$template->value => $template->label()])
            ->all();
    }

    /**
     * The human readable name of the template.
     */
    public function label(): string
    {
        return trans("licences.form_template.{$this->value}");
    }

    /**
     * The Blade view that renders the form. All forms use a placeholder until the real paperwork is available.
     */
    public function view(): string
    {
        return 'licence-forms.placeholder';
    }
}

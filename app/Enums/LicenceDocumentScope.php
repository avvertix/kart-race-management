<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Whose document is asked for in a Licence Request.
 */
enum LicenceDocumentScope: string
{
    /**
     * Asked once, about the licence holder.
     */
    case Holder = 'holder';

    /**
     * Asked for each parent or guardian entered.
     */
    case EachParent = 'each_parent';

    /**
     * Asked once per request, e.g. a form signed by both parents.
     */
    case Once = 'once';

    /**
     * The scopes as a value => label map.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $scope) => [$scope->value => $scope->label()])
            ->all();
    }

    /**
     * The human readable name of the scope.
     */
    public function label(): string
    {
        return trans("licences.document_scope.{$this->value}");
    }
}

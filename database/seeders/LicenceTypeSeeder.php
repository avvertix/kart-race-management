<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceDocumentScope;
use App\Enums\LicenceFormTemplate;
use App\Enums\LicenceLevel;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Models\LicenceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The ACI driver, competitor and mechanic licences with their usual grades and documents.
 * Types that already exist, matched by provider, role and name, are left untouched.
 */
class LicenceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seed(LicenceRole::Driver, __('Driver licence'), [
                [LicenceLevel::National, 'C Junior'],
                [LicenceLevel::National, 'C Senior'],
                [LicenceLevel::National, 'D'],
                [LicenceLevel::International, 'International C Junior'],
                [LicenceLevel::International, 'International C Senior'],
                [LicenceLevel::International, 'International B'],
            ], [
                $this->identityDocuments(),
                $this->parentIdentityDocuments(),
                $this->upload(__('Karting competitive medical certificate'), __('Valid for the whole licence year.')),
                $this->form(LicenceFormTemplate::ParentalAuthorisation, LicenceDocumentScope::Once, underageOnly: true),
                $this->form(LicenceFormTemplate::Delegation),
                $this->form(LicenceFormTemplate::Privacy, LicenceDocumentScope::Once, __('Signed by both parents when the holder is underage.')),
                $this->form(LicenceFormTemplate::SelfDeclaration),
            ]);

            $this->seed(LicenceRole::Competitor, __('Competitor licence'), [], [
                $this->identityDocuments(),
                $this->parentIdentityDocuments(),
                $this->form(LicenceFormTemplate::ParentalAuthorisation, LicenceDocumentScope::Once, underageOnly: true),
                $this->form(LicenceFormTemplate::Delegation),
                $this->form(LicenceFormTemplate::Privacy, LicenceDocumentScope::Once, __('Signed by both parents when the holder is underage.')),
                $this->form(LicenceFormTemplate::SelfDeclaration),
            ]);

            $this->seed(LicenceRole::Mechanic, __('Mechanic licence'), [], [
                $this->identityDocuments(),
                $this->form(LicenceFormTemplate::Delegation),
                $this->form(LicenceFormTemplate::Privacy),
                $this->form(LicenceFormTemplate::SelfDeclaration),
            ]);
        });
    }

    /**
     * @param  list<array{0: LicenceLevel, 1: string}>  $grades
     * @param  list<array<string, mixed>>  $documents
     */
    private function seed(LicenceRole $role, string $name, array $grades, array $documents): void
    {
        $licenceType = LicenceType::firstOrNew([
            'provider' => LicenceProvider::Aci,
            'role' => $role,
            'name' => $name,
        ]);

        if ($licenceType->exists) {
            return;
        }

        $licenceType->active = true;
        $licenceType->save();

        foreach ($grades as $sort => [$level, $grade]) {
            $licenceType->grades()->create(['level' => $level, 'name' => $grade, 'sort' => $sort]);
        }

        foreach ($documents as $sort => $document) {
            $licenceType->documents()->create([...$document, 'sort' => $sort]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function identityDocuments(): array
    {
        return $this->upload(__('Identity card and fiscal code'));
    }

    /**
     * @return array<string, mixed>
     */
    private function parentIdentityDocuments(): array
    {
        return [
            ...$this->upload(__('Identity card and fiscal code of the parent or guardian')),
            'scope' => LicenceDocumentScope::EachParent,
            'underage_only' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function upload(string $name, ?string $description = null): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'kind' => LicenceDocumentKind::Upload,
            'template' => null,
            'scope' => LicenceDocumentScope::Holder,
            'underage_only' => false,
            'required' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function form(LicenceFormTemplate $template, LicenceDocumentScope $scope = LicenceDocumentScope::Holder, ?string $description = null, bool $underageOnly = false): array
    {
        return [
            'name' => $template->label(),
            'description' => $description,
            'kind' => LicenceDocumentKind::SignedForm,
            'template' => $template,
            'scope' => $scope,
            'underage_only' => $underageOnly,
            'required' => true,
        ];
    }
}

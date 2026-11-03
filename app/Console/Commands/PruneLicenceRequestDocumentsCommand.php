<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\LicenceRequests\PruneLicenceRequestDocuments;
use App\Models\LicenceRequest;
use Illuminate\Console\Command;

class PruneLicenceRequestDocumentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licence-requests:prune-documents
                            {--dry-run : List the requests without deleting anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the documents and payment proofs of licence requests past the retention period';

    /**
     * Execute the console command.
     */
    public function handle(PruneLicenceRequestDocuments $pruneDocuments): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $count = 0;

        LicenceRequest::query()
            ->pastDocumentRetention()
            ->withCount(['documents', 'payments'])
            ->lazyById()
            ->each(function (LicenceRequest $licenceRequest) use ($isDryRun, $pruneDocuments, &$count) {
                $this->line(sprintf(
                    '%s: %d documents, %d payment proofs',
                    $licenceRequest->ulid,
                    $licenceRequest->documents_count,
                    $licenceRequest->payments_count,
                ));

                if (! $isDryRun) {
                    $pruneDocuments($licenceRequest);
                }

                $count++;
            });

        $this->info($isDryRun
            ? "{$count} licence requests would have their documents deleted."
            : "Deleted the documents of {$count} licence requests.");

        return self::SUCCESS;
    }
}

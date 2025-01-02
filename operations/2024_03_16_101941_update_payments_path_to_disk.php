<?php

declare(strict_types=1);

use App\Models\Payment;
use Illuminate\Support\Str;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class extends OneTimeOperation
{
    /**
     * Determine if the operation is being processed asynchronously.
     */
    protected bool $async = true;

    /**
     * The queue that the job will be dispatched to.
     */
    protected string $queue = 'default';

    /**
     * A tag name, that this operation can be filtered by.
     */
    protected ?string $tag = null;

    /**
     * Process the operation.
     */
    public function process(): void
    {
        Payment::query()
            ->lazy()
            ->each(function ($payment) {

                if (Str::startsWith($payment->path, 'payments/')) {
                    $payment->path = Str::after($payment->path, 'payments/');

                    $payment->save();
                }

            });
    }
};

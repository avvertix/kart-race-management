<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (InvalidSignatureException $e) {
            return response()->view('errors.link-expired', [], 403);
        });

        $this->renderable(function (InvalidParticipantSignatureException $e) {
            return response()->view('errors.participant-link-invalid', [], 403);
        });

        $this->renderable(function (InvalidParticipantTiresSignatureException $e) {
            return response()->view('errors.participant-tires-link-invalid', [], 401);
        });
    }
}

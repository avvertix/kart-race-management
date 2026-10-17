<?php

declare(strict_types=1);

use App\Exceptions\InvalidParticipantSignatureException;
use App\Exceptions\InvalidParticipantTiresSignatureException;
use App\Http\Middleware\Locale;
use Avvertix\AgentRequest\LaravelAgentRequest\Http\Middleware\TraceAgentMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->web(append: [
            TraceAgentMiddleware::class,
            Locale::class,
        ]);

        $middleware->throttleApi();
    })
    ->withEvents(discover: false)
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (InvalidSignatureException $e) => response()->view('errors.link-expired', [], 403));

        $exceptions->render(fn (InvalidParticipantSignatureException $e) => response()->view('errors.participant-link-invalid', [], 403));

        $exceptions->render(fn (InvalidParticipantTiresSignatureException $e) => response()->view('errors.participant-tires-link-invalid', [], 401));
    })->create();

<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureLeader;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SetLocale::class, EnsureActiveAccount::class, SecurityHeaders::class]);
        $middleware->alias(['leader' => EnsureLeader::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $r) => $r->user()?->homeRoute() ?? '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Domain errors are user-safe: show them as friendly messages, never as stack traces.
        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'reason' => $e->reason], $e->status);
            }

            return back()->withInput()->with('error', $e->getMessage())->with('error_reason', $e->reason);
        });
    })->create();

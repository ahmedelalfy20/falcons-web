<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /** Respond to both fetch() calls and classic form posts. */
    protected function done(string $message, array $data = [])
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => $message] + $data);
        }

        return back()->with('success', $message);
    }
}

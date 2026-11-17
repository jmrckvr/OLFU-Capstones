<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

abstract class Controller
{
    /** Redirect back and show a toast that hides itself. */
    protected function backWithToast(string $message, string $type = 'success'): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return back();
    }

    /** Redirect to a route and show a toast. */
    protected function toWithToast(string $route, mixed $params, string $message, string $type = 'success'): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return redirect()->route($route, $params);
    }

    /** The fixed dropdown lists (programs, categories, years, statuses, roles). */
    protected function options(): array
    {
        return config('repository');
    }
}

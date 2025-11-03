<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

abstract class BaseController extends Controller
{
    /**
     * Get per-page value from request with a default.
     */
    protected function perPage(string $key = 'per_page', int $default = 10): int
    {
        return (int) request($key, $default);
    }

    /**
     * Apply a simple LIKE search across the provided columns if a term exists.
     */
    protected function applySearch(Builder $query, array $columns, ?string $term = null): Builder
    {
        $term = $term ?? (string) request('search', '');
        if ($term === '') {
            return $query;
        }

        $query->where(function (Builder $q) use ($columns, $term) {
            foreach ($columns as $i => $col) {
                if ($i === 0) {
                    $q->where($col, 'like', "%{$term}%");
                } else {
                    $q->orWhere($col, 'like', "%{$term}%");
                }
            }
        });

        return $query;
    }

    /**
     * Redirect to a named route with a success flash.
     */
    protected function successRedirect(string $route, string $message, array $parameters = []): RedirectResponse
    {
        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * Redirect back with an error flash.
     */
    protected function errorBack(string $message): RedirectResponse
    {
        return redirect()->back()->with('error', $message);
    }

    /**
     * Centralized exception logging with useful context.
     */
    protected function logException(\Throwable $th, string $action, array $context = []): void
    {
        try {
            $userId = optional(request()->user())->getKey();
        } catch (\Throwable $e) {
            $userId = null;
        }

        Log::error("Failed to {$action}: ".$th->getMessage(), array_merge([
            'user_id' => $userId,
            'message' => $th->getMessage(),
            'trace' => $th->getTraceAsString(),
        ], $context));
    }
}

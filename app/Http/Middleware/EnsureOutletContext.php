<?php

namespace App\Http\Middleware;

use App\Services\CurrentOutletService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOutletContext
{
    public function __construct(private CurrentOutletService $currentOutlet) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            try {
                $this->currentOutlet->current();
            } catch (\RuntimeException $e) {
                return redirect()->route('login')
                    ->with('error', $e->getMessage());
            }
        }

        return $next($request);
    }
}

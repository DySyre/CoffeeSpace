<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLoginForCoffeeMap
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'))
                ->with('status', 'You must log in before opening the Coffee Map.');
        }

        return $next($request);
    }
}

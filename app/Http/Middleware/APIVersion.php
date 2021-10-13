<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class APIVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $guard Revisar el valor en donde se aplica el middleware
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard)
    {
        config(['app.api.version' => "V{$guard}"]);

        return $next($request);
    }
}

<?php

namespace Micaomao\NmsAdmin\Middleware;

use Closure;
use Micaomao\NmsAdmin\Admin;
use Illuminate\Http\Request;

class Bootstrap
{
    public function handle(Request $request, Closure $next)
    {
        Admin::bootstrap();

        return $next($request);
    }
}

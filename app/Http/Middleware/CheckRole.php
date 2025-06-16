<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Администраторы имеют доступ ко всему
        if (auth()->check() && auth()->user()->role === 'admin') {
            return $next($request);
        }
        
        // Для роли 'user' разрешаем доступ всем неаутентифицированным пользователям
        if (!auth()->check() && in_array('user', $roles)) {
            return $next($request);
        }

        // Для других ролей проверяем авторизацию и соответствие роли
        if (!auth()->check() || !in_array(auth()->user()->role, $roles)) {
            abort(403, 'Доступ запрещен');
        }

        return $next($request);
    }
}

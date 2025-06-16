<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Если есть проблемы с CSRF, можно временно добавить путь сюда для отладки
        // '/sup/calculate',
        // '/sup/payment'
    ];
    
    /**
     * Determine if the request has a URI that should be CSRF protected.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough($request)
    {
        // Проверяем заголовок Accept для определения API запросов
        if ($request->is('api/*') || 
            $request->expectsJson() || 
            $request->header('Accept') === 'application/json') {
            
            // Пропускаем CSRF-защиту для API запросов с авторизацией
            if ($request->user()) {
                return true;
            }
        }
        
        return parent::shouldPassThrough($request);
    }
}

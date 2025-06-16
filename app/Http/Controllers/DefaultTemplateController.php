<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class DefaultTemplateController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:client,admin');
    }

    /**
     * Показать редактор медиа с базовым шаблоном.
     *
     * @return \Illuminate\Http\Response
     */
    public function showEditor()
    {
        try {
            // Получаем базовый шаблон (здесь можно настроить логику выбора базового шаблона)
            $defaultTemplate = Template::where('is_default', true)
                                ->where('is_active', true)
                                ->first();
            
            // Если базового шаблона нет, берем первый активный шаблон
            if (!$defaultTemplate) {
                $defaultTemplate = Template::where('is_active', true)->first();
            }
            
            // Если шаблон найден, перенаправляем на редактор медиа с этим шаблоном
            if ($defaultTemplate) {
                return redirect()->route('media.editor.template', $defaultTemplate->id);
            }
            
            // Если шаблонов нет, просто перенаправляем на обычный редактор медиа
            return redirect()->route('media.editor')->with('message', 'Базовый шаблон не найден, выберите файл для загрузки');
        } catch (\Exception $e) {
            Log::error('Error in DefaultTemplateController::showEditor: ' . $e->getMessage());
            // В случае ошибки перенаправляем пользователя на главную страницу
            return redirect()->route('home')->with('error', 'Произошла ошибка при загрузке редактора. Пожалуйста, попробуйте позже.');
        }
    }

    /**
     * Безопасное создание URL для маршрута
     *
     * @param string $name Имя маршрута
     * @param array $parameters Параметры маршрута
     * @param string $fallbackRoute Запасной маршрут
     * @return string URL
     */
    protected function safeRoute($name, $parameters = [], $fallbackRoute = 'home')
    {
        try {
            if (Route::has($name)) {
                return route($name, $parameters);
            }
            
            Log::warning("Маршрут {$name} не найден, используем запасной маршрут {$fallbackRoute}");
            return route($fallbackRoute);
        } catch (\Exception $e) {
            Log::error("Ошибка при создании URL для маршрута {$name}: " . $e->getMessage());
            return route('home');
        }
    }
}

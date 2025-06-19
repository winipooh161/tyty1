<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserTemplate;
use App\Models\AcquiredTemplate;

class PublicTemplateController extends Controller
{
    /**
     * Отображение опубликованного шаблона пользователя
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Загружаем шаблон с нужными связями
        $userTemplate = UserTemplate::with(['template.category', 'user'])
            ->where('id', $id)
            ->where('is_active', 1) // Убедимся, что шаблон активен
            ->first();

        // Если шаблон не найден, возвращаем 404
        if (!$userTemplate) {
            // Добавляем логирование для отладки
            \Log::error('Шаблон не найден или не активен', ['id' => $id]);
            abort(404, 'Шаблон не найден');
        }

        // Проверяем, получил ли текущий пользователь этот шаблон
        $acquiredTemplate = null;
        if (Auth::check()) {
            $acquiredTemplate = AcquiredTemplate::where('user_id', Auth::id())
                ->where('user_template_id', $userTemplate->id)
                ->first();
        }

        // Собираем данные о серии, если шаблон является серийным
        $seriesData = null;
        
        // Исправляем условие с правильными скобками
        if (Auth::check() && ($userTemplate->custom_data && (is_array($userTemplate->custom_data) || is_object($userTemplate->custom_data)))) {
            $customData = is_object($userTemplate->custom_data) ? 
                (array)$userTemplate->custom_data : 
                $userTemplate->custom_data;

            // Проверяем, является ли шаблон серийным, либо по явному флагу, либо по количеству
            $isSeries = (isset($customData['is_series']) && $customData['is_series']) || 
                       (isset($customData['series_quantity']) && $customData['series_quantity'] > 1);
                       
            if ($isSeries) {
                // Подсчитываем количество уже полученных шаблонов
                $acquiredCount = AcquiredTemplate::where('user_template_id', $userTemplate->id)->count();
                
                // Подсчитываем количество использованных (отсканированных) шаблонов
                $scanCount = AcquiredTemplate::where('user_template_id', $userTemplate->id)
                    ->where('status', 'used')
                    ->count();
                
                $seriesData = [
                    'acquired_count' => $acquiredCount,
                    'scan_count' => $scanCount,
                    'series_quantity' => $customData['series_quantity'] ?? 1,
                    'required_scans' => $customData['required_scans'] ?? 1,
                    'is_series' => true
                ];
                
                \Log::info('Template series data prepared', $seriesData);
            }
        }
        
        // Добавляем логирование для диагностики
        \Log::info('Template view data', [
            'template_id' => $id,
            'has_custom_data' => !empty($userTemplate->custom_data),
            'custom_data_type' => gettype($userTemplate->custom_data),
            'series_data' => $seriesData
        ]);

        // Добавляем диагностические данные
        $debug = [
            'template_id' => $id,
            'template_found' => $userTemplate ? true : false,
            'template_name' => $userTemplate ? $userTemplate->name : 'Не найдено',
            'has_html_content' => $userTemplate && !empty($userTemplate->html_content) ? true : false,
            'html_content_length' => $userTemplate ? mb_strlen($userTemplate->html_content) : 0
        ];

        return view('public.template', compact('userTemplate', 'acquiredTemplate', 'seriesData', 'debug'));
    }
}

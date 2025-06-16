<?php

namespace App\Http\Controllers;

use App\Models\UserTemplate;
use App\Models\AcquiredTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // Находим только опубликованный шаблон
        $userTemplate = UserTemplate::where('id', $id)
                       ->where('status', 'published')
                       ->firstOrFail();
        
        // Преобразуем custom_data в массив, если это не массив
        if (!is_array($userTemplate->custom_data)) {
            try {
                $userTemplate->custom_data = json_decode(json_encode($userTemplate->custom_data), true);
            } catch (\Exception $e) {
                Log::error('Ошибка при обработке custom_data: ' . $e->getMessage());
                $userTemplate->custom_data = [];
            }
        }
        
        // Получаем информацию о серии для передачи в представление
        $acquiredCount = AcquiredTemplate::where('user_template_id', $userTemplate->id)->count();
        
        // Получаем общее количество сканирований шаблона
        $scanCount = AcquiredTemplate::where('user_template_id', $userTemplate->id)
                    ->where('status', 'used')
                    ->count();
        
        // Извлекаем данные серии из custom_data
        $customData = $userTemplate->custom_data ?? [];
        $seriesQuantity = $customData['series_quantity'] ?? 1;
        $requiredScans = $customData['required_scans'] ?? 1;
        
        // Добавляем данные для передачи в шаблон
        $seriesData = [
            'acquired_count' => $acquiredCount,
            'scan_count' => $scanCount,
            'series_quantity' => $seriesQuantity,
            'required_scans' => $requiredScans
        ];
        
        // Пре-обработка HTML для замены шаблонных выражений на фактические значения
        $processedHtml = $this->processTemplateHtml($userTemplate->html_content, $userTemplate->custom_data, $seriesData);
        $userTemplate->html_content = $processedHtml;
            
        return view('public.template', compact('userTemplate', 'seriesData'));
    }
    
    /**
     * Обработка HTML шаблона для замены шаблонных выражений на фактические значения
     *
     * @param string $html HTML содержимое
     * @param array $customData Пользовательские данные
     * @param array $seriesData Данные серии
     * @return string Обработанный HTML
     */
    private function processTemplateHtml($html, $customData, $seriesData)
    {
        // Подготовка данных для подстановки
        $seriesQuantity = $seriesData['series_quantity'] ?? '1';
        $requiredScans = $seriesData['required_scans'] ?? '1';
        $acquiredCount = $seriesData['acquired_count'] ?? '0';
        $scanCount = $seriesData['scan_count'] ?? '0';
        
        // Функция для замены значений в input элементах
        $replaceInputValue = function($matches) use ($seriesQuantity, $acquiredCount, $scanCount, $requiredScans) {
            $fullMatch = $matches[0];
            $editable = $matches[1] ?? '';
            
            $newValue = '';
            switch ($editable) {
                case 'series_quantity':
                    $newValue = $seriesQuantity;
                    break;
                case 'series_received':
                    $newValue = $acquiredCount;
                    break;
                case 'scan_count':
                    $newValue = $scanCount;
                    break;
                case 'required_scans':
                    $newValue = $requiredScans;
                    break;
                default:
                    return $fullMatch;
            }
            
            // Заменяем value в input элементе
            $result = preg_replace('/value="[^"]*"/', 'value="' . $newValue . '"', $fullMatch);
            // Также заменяем placeholder для согласованности
            $result = preg_replace('/placeholder="[^"]*"/', 'placeholder="' . $newValue . '"', $result);
            
            return $result;
        };
        
        // Заменяем значения в input элементах с data-editable атрибутами
        $html = preg_replace_callback(
            '/<input[^>]*data-editable="(series_quantity|series_received|scan_count|required_scans)"[^>]*>/',
            $replaceInputValue,
            $html
        );
        
        // Дополнительная обработка для span и других элементов
        $replaceTextValue = function($matches) use ($seriesQuantity, $acquiredCount, $scanCount, $requiredScans) {
            $fullMatch = $matches[0];
            $editable = $matches[1] ?? '';
            
            $newValue = '';
            switch ($editable) {
                case 'series_quantity':
                    $newValue = $seriesQuantity;
                    break;
                case 'series_received':
                    $newValue = $acquiredCount;
                    break;
                case 'scan_count':
                    $newValue = $scanCount;
                    break;
                case 'required_scans':
                    $newValue = $requiredScans;
                    break;
                default:
                    return $fullMatch;
            }
            
            // Заменяем содержимое между тегами
            return preg_replace('/>[^<]*</', '>' . $newValue . '<', $fullMatch);
        };
        
        // Заменяем значения в span и других элементах
        $html = preg_replace_callback(
            '/<(span|div|p)[^>]*data-editable="(series_quantity|series_received|scan_count|required_scans)"[^>]*>[^<]*<\/\1>/',
            $replaceTextValue,
            $html
        );
        
        // Дополнительно заменяем любые оставшиеся шаблонные выражения
        $replacements = [
            '{{ isset($userTemplate) && isset($userTemplate->custom_data[\'series_quantity\']) ? $userTemplate->custom_data[\'series_quantity\'] : \'1\' }}' => $seriesQuantity,
            '{{ isset($seriesData) ? $seriesData[\'acquired_count\'] : \'0\' }}' => $acquiredCount,
            '{{ isset($seriesData) ? $seriesData[\'scan_count\'] : \'0\' }}' => $scanCount,
            '{{ isset($userTemplate) && isset($userTemplate->custom_data[\'required_scans\']) ? $userTemplate->custom_data[\'required_scans\'] : \'1\' }}' => $requiredScans
        ];
        
        $processedHtml = str_replace(array_keys($replacements), array_values($replacements), $html);
        
        Log::info('HTML processing completed', [
            'original_length' => strlen($html),
            'processed_length' => strlen($processedHtml),
            'series_data' => $seriesData
        ]);
        
        return $processedHtml;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserTemplate;
use App\Models\AcquiredTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TemplateStatusController extends Controller
{
    /**
     * Изменить статус шаблона по URL
     * 
     * @param int $templateId
     * @param int $userId
     * @param int $acquiredId
     * @param int|null $timestamp
     * @return \Illuminate\Http\Response
     */
    public function changeStatusByUrl($templateId, $userId, $acquiredId, $timestamp = null)
    {
        return $this->changeStatus($templateId, $userId, $acquiredId, $timestamp);
    }

    /**
     * Изменить статус приобретенного шаблона на "использованный"
     *
     * @param  int  $templateId
     * @param  int  $userId
     * @param  int  $acquiredId
     * @param  int|null  $timestamp
     * @return \Illuminate\Http\Response
     */
    public function changeStatus($templateId, $userId, $acquiredId, $timestamp = null)
    {
        $success = false;
        $message = '';
        $template = null;
        $acquired = null;
        $errorDetails = [];
        
        try {
            // Проверяем, что пользователь авторизован
            if (!Auth::check()) {
                $errorDetails[] = 'Необходимо авторизоваться для изменения статуса шаблона.';
                return view('public.status-change', compact('success', 'message', 'errorDetails'))
                    ->with('message', 'Для изменения статуса шаблона необходимо войти в систему.');
            }
            
            // Проверяем, что ID пользователя в URL соответствует авторизованному
            if (Auth::id() != $userId && Auth::user()->role !== 'admin') {
                $errorDetails[] = 'Неверный ID пользователя в запросе.';
                return view('public.status-change', compact('success', 'message', 'errorDetails'))
                    ->with('message', 'У вас нет прав для изменения этого шаблона.');
            }
            
            // Проверка времени формирования ссылки (не старше 1 часа)
            if ($timestamp && (time() - (int)$timestamp) > 3600) {
                $errorDetails[] = 'Срок действия QR-кода истек.';
                return view('public.status-change', compact('success', 'message', 'errorDetails'))
                    ->with('message', 'QR-код устарел. Пожалуйста, сгенерируйте новый QR-код.');
            }
            
            // Находим шаблон
            $template = UserTemplate::findOrFail($templateId);
            
            // Для создателя шаблона или админа проверяем дополнительные условия
            if ($template->user_id == Auth::id() || Auth::user()->role === 'admin') {
                // Если $acquiredId = 0, то, вероятно, это создатель шаблона проверяет работу
                if ($acquiredId == 0) {
                    $success = true;
                    return view('public.status-change', compact('success', 'message', 'template'))
                        ->with('message', 'Проверка QR-кода выполнена успешно. Это тестовый режим для создателя шаблона.');
                }
            }
            
            // Находим приобретенный шаблон
            $acquired = AcquiredTemplate::where('id', $acquiredId)
                ->where('user_template_id', $templateId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$acquired) {
                $errorDetails[] = 'Шаблон не найден в вашей коллекции.';
                return view('public.status-change', compact('success', 'message', 'errorDetails'))
                    ->with('message', 'Шаблон не найден или у вас нет прав для его использования.');
            }
            
            // Проверяем, что шаблон активен
            if ($acquired->status !== 'active') {
                $errorDetails[] = 'Шаблон уже был использован ранее.';
                return view('public.status-change', compact('success', 'message', 'errorDetails'))
                    ->with('message', 'Этот шаблон уже был отмечен как использованный.');
            }
            
            // Меняем статус на "использованный"
            $acquired->status = 'used';
            $acquired->status_changed_at = now();
            $acquired->save();
            
            // Извлекаем и проверяем данные о серии корректно
            $customData = is_array($template->custom_data) 
                ? $template->custom_data 
                : (json_decode($template->custom_data, true) ?: []);
            
            if (!empty($customData['is_series'])) {
                // Получаем количество требуемых сканирований
                $requiredScans = isset($customData['required_scans']) && is_numeric($customData['required_scans']) 
                    ? (int)$customData['required_scans'] : 1;
                
                // Получаем текущее количество сканирований
                $currentScans = AcquiredTemplate::where('user_template_id', $template->id)
                    ->where('status', 'used')
                    ->count();
                
                // Проверяем, достигнуто ли требуемое количество сканирований
                $isCompleted = $currentScans >= $requiredScans;
                
                // Обновляем статистику сканирований для шаблона
                Log::info('Шаблон из серии отмечен как использованный', [
                    'acquiredId' => $acquiredId,
                    'templateId' => $templateId,
                    'currentScans' => $currentScans,
                    'requiredScans' => $requiredScans,
                    'isCompleted' => $isCompleted
                ]);
                
                // Здесь можно было бы добавить логику для выполнения действия 
                // когда достигнуто требуемое количество сканирований
                if ($isCompleted) {
                    Log::info('Достигнуто требуемое количество сканирований', [
                        'templateId' => $templateId,
                        'scans' => "$currentScans/$requiredScans"
                    ]);
                }
            }
            
            $success = true;
            $message = 'Статус шаблона успешно изменен на "Использованный".';
            
            // Записываем информацию в лог
            Log::info('Изменен статус шаблона', [
                'template_id' => $templateId, 
                'user_id' => $userId, 
                'acquired_id' => $acquiredId
            ]);
            
            return view('public.status-change', compact('success', 'message', 'template', 'acquired'));
            
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса шаблона', [
                'error' => $e->getMessage(),
                'template_id' => $templateId,
                'user_id' => $userId,
                'acquired_id' => $acquiredId
            ]);
            
            $errorDetails[] = 'Внутренняя ошибка сервера: ' . $e->getMessage();
            return view('public.status-change', compact('success', 'message', 'errorDetails'))
                ->with('message', 'Произошла ошибка при изменении статуса шаблона.');
        }
    }
}

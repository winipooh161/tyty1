<?php

namespace App\Http\Controllers;

use App\Models\UserTemplate;
use App\Models\AcquiredTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SeriesTemplateController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Получить шаблон из серии.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function acquire(Request $request, $id)
    {
        // Улучшенное логирование начала запроса
        Log::info('Template acquisition started', [
            'template_id' => $id, 
            'user_id' => Auth::id(),
            'method' => $request->method(),
            'url' => $request->url(),
            'referer' => $request->header('referer'),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'all_input' => $request->all(),
            'has_debug_fields' => $request->has('debug_user_id')
        ]);
        
        try {
            // Проверяем существование шаблона перед транзакцией
            $templateExists = UserTemplate::where('id', $id)->exists();
            if (!$templateExists) {
                Log::error('Template not found before transaction', ['template_id' => $id, 'user_id' => Auth::id()]);
                return redirect()->back()->with('error', 'Шаблон не найден');
            }
            
            // Транзакция для обеспечения целостности данных
            return DB::transaction(function () use ($id, $request) {
                // Получаем шаблон для блокировки строки (for update)
                $template = UserTemplate::where('id', $id)
                    ->lockForUpdate()
                    ->first();
                
                if (!$template) {
                    Log::error('Template not found within transaction', ['template_id' => $id, 'user_id' => Auth::id()]);
                    return redirect()->back()->with('error', 'Шаблон не найден');
                }
                
                // Более подробное логирование деталей шаблона
                Log::info('Template details', [
                    'template_id' => $template->id,
                    'name' => $template->name,
                    'user_id' => $template->user_id,
                    'template_id_foreign' => $template->template_id,
                    'status' => $template->status ?? 'not_set',
                    'is_active' => $template->is_active ?? 'not_set',
                    'is_published' => $template->is_published ?? 'not_set',
                    'custom_data' => json_encode($template->custom_data)
                ]);
                
                // Проверяем статус шаблона - ИЗМЕНЕННАЯ ЛОГИКА
                // Шаблон доступен если:
                // 1. Он активен (is_active = true) И
                // 2. Либо статус не задан, либо статус не равен 'archived'/'deleted'
                $isAvailable = true;
                $availabilityReason = '';
                
                // Проверяем сначала is_active - это главное условие
                if (isset($template->is_active) && $template->is_active == 0) {
                    $isAvailable = false;
                    $availabilityReason = 'not_active';
                    Log::warning('Template not active', ['template_id' => $id, 'is_active' => $template->is_active]);
                }
                
                // Если is_active = true, проверяем статус только на архивацию/удаление
                if (isset($template->status) && in_array($template->status, ['archived', 'deleted'])) {
                    $isAvailable = false;
                    $availabilityReason = 'archived_or_deleted';
                    Log::warning('Template not available (archived or deleted)', ['template_id' => $id, 'status' => $template->status]);
                }
                
                // Дополнительно проверяем is_published, если поле существует
                if ($isAvailable && isset($template->is_published) && $template->is_published == 0) {
                    // Если шаблон черновик (draft), но активен (is_active = true), оставляем его доступным
                    if ($template->status !== 'draft') {
                        $isAvailable = false;
                        $availabilityReason = 'not_published';
                        Log::warning('Template not published', ['template_id' => $id, 'is_published' => $template->is_published]);
                    } else {
                        Log::info('Template is draft but active, allowing acquisition', [
                            'template_id' => $id, 
                            'status' => $template->status,
                            'is_active' => $template->is_active
                        ]);
                    }
                }
                
                if (!$isAvailable) {
                    Log::warning('Template unavailable for acquisition', [
                        'reason' => $availabilityReason,
                        'template_id' => $id
                    ]);
                    return redirect()->back()->with('error', "Шаблон недоступен для получения ({$availabilityReason})");
                }
                
                // Проверяем, принадлежит ли шаблон текущему пользователю
                if ($template->user_id === Auth::id()) {
                    Log::info('User trying to acquire their own template', ['template_id' => $id, 'user_id' => Auth::id()]);
                    return redirect()->back()->with('info', 'Вы не можете получить свой собственный шаблон');
                }
                
                // Проверяем, получал ли пользователь этот шаблон ранее
                $existingAcquired = AcquiredTemplate::where('user_template_id', $id)
                    ->where('user_id', Auth::id())
                    ->first();
                
                if ($existingAcquired) {
                    Log::info('User already acquired this template', [
                        'template_id' => $id, 
                        'user_id' => Auth::id(),
                        'acquired_id' => $existingAcquired->id,
                        'acquired_at' => $existingAcquired->acquired_at
                    ]);
                    return redirect()->back()->with('info', 'Вы уже получили этот шаблон ранее');
                }
                
                // Проверяем, является ли шаблон серийным
                $customData = null;
                
                try {
                    if (is_string($template->custom_data)) {
                        $customData = json_decode($template->custom_data, true);
                        Log::info('Custom data decoded from string', ['custom_data' => $customData]);
                    } elseif (is_array($template->custom_data)) {
                        $customData = $template->custom_data;
                        Log::info('Custom data is already an array');
                    } elseif (is_object($template->custom_data)) {
                        $customData = (array)$template->custom_data;
                        Log::info('Custom data converted from object to array');
                    } else {
                        $customData = [];
                        Log::warning('Custom data is empty or in unknown format', ['type' => gettype($template->custom_data)]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error parsing custom_data', [
                        'error' => $e->getMessage(), 
                        'custom_data' => $template->custom_data
                    ]);
                    $customData = [];
                }
                
                $isSeriesTemplate = isset($customData['is_series']) && $customData['is_series'];
                $seriesQuantity = isset($customData['series_quantity']) ? (int)$customData['series_quantity'] : 1;
                
                Log::info('Series data', [
                    'is_series' => $isSeriesTemplate,
                    'series_quantity' => $seriesQuantity
                ]);
                
                // Для серийного шаблона проверяем количество уже полученных экземпляров
                if ($isSeriesTemplate) {
                    $acquiredCount = AcquiredTemplate::where('user_template_id', $id)->count();
                    
                    Log::info('Series template acquisition check', [
                        'acquired_count' => $acquiredCount,
                        'series_quantity' => $seriesQuantity,
                        'is_available' => ($acquiredCount < $seriesQuantity)
                    ]);
                    
                    if ($acquiredCount >= $seriesQuantity) {
                        Log::warning('Series template limit exceeded', [
                            'template_id' => $id,
                            'acquired_count' => $acquiredCount,
                            'series_quantity' => $seriesQuantity
                        ]);
                        return redirect()->back()->with('error', 'Все доступные экземпляры этой серии уже разобраны');
                    }
                }
                
                // Создаем запись о полученном шаблоне
                try {
                    $acquiredTemplate = new AcquiredTemplate([
                        'user_id' => Auth::id(),
                        'user_template_id' => $id,
                        'status' => 'active',
                        'acquired_at' => now(),
                        'is_series' => $isSeriesTemplate
                    ]);
                    
                    $acquiredTemplate->save();
                    
                    Log::info('Template successfully acquired', [
                        'template_id' => $id,
                        'user_id' => Auth::id(),
                        'acquired_id' => $acquiredTemplate->id,
                        'is_series' => $isSeriesTemplate
                    ]);
                    
                    return redirect()->back()->with('success', 'Шаблон успешно получен');
                } catch (\Exception $e) {
                    Log::error('Error saving acquired template', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e; // Пробрасываем исключение для отката транзакции
                }
            });
        } catch (\Exception $e) {
            Log::error('Error acquiring template', [
                'template_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Произошла ошибка при получении шаблона: ' . $e->getMessage());
        }
    }
}


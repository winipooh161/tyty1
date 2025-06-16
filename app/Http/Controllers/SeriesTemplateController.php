<?php

namespace App\Http\Controllers;

use App\Models\UserTemplate;
use App\Models\AcquiredTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * Получить шаблон из серии (или несерийный шаблон)
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function acquire($id, Request $request)
    {
        // Логируем начало процесса с дополнительной информацией
        Log::info('Template acquisition started', [
            'template_id' => $id,
            'user_id' => Auth::id(),
            'method' => $request->method(),
            'url' => $request->url(),
            'referer' => $request->header('referer'),
            'user_agent' => $request->header('user-agent'),
            'ip' => $request->ip(),
            'all_input' => $request->all()
        ]);
        
        // Проверяем метод запроса
        if (!$request->isMethod('post')) {
            Log::warning('Invalid method for template acquisition', [
                'method' => $request->method(),
                'template_id' => $id
            ]);
            return redirect()
                ->route('public.template', $id)
                ->with('error', 'Для получения шаблона используйте кнопку "Получить шаблон"');
        }
        
        // Проверяем CSRF токен
        if (!$request->hasValidSignature(false) && !hash_equals($request->session()->token(), $request->input('_token'))) {
            Log::warning('CSRF token mismatch', [
                'template_id' => $id,
                'user_id' => Auth::id(),
                'session_token' => $request->session()->token(),
                'request_token' => $request->input('_token')
            ]);
        }
        
        try {
            // Получаем шаблон
            $userTemplate = UserTemplate::where('id', $id)
                ->where('status', 'published')
                ->firstOrFail();
                
            Log::info('Template found', [
                'template_id' => $userTemplate->id,
                'template_name' => $userTemplate->name,
                'owner_id' => $userTemplate->user_id,
                'current_user' => Auth::id()
            ]);
            
            // Проверяем, что пользователь не является владельцем шаблона
            if ($userTemplate->user_id == Auth::id()) {
                Log::warning('User tried to acquire own template', [
                    'user_id' => Auth::id(),
                    'template_id' => $userTemplate->id
                ]);
                return redirect()->route('public.template', $id)->with('error', 'Вы не можете получить свой собственный шаблон.');
            }
            
            // Проверяем, не получил ли пользователь уже этот шаблон
            $alreadyAcquired = AcquiredTemplate::where('user_id', Auth::id())
                ->where('user_template_id', $userTemplate->id)
                ->exists();
                
            if ($alreadyAcquired) {
                Log::warning('User already acquired this template', [
                    'user_id' => Auth::id(),
                    'template_id' => $userTemplate->id
                ]);
                return redirect()->route('public.template', $id)->with('error', 'Вы уже получили этот шаблон.');
            }
            
            // Проверяем, является ли шаблон серией, используя custom_data
            $customData = is_array($userTemplate->custom_data) 
                ? $userTemplate->custom_data 
                : (json_decode($userTemplate->custom_data, true) ?: []);
            
            $isSeries = isset($customData['is_series']) && $customData['is_series'];
            $totalCount = 1; // По умолчанию для нессерийных шаблонов
            $requiredScans = 1; // По умолчанию 1 скан
            
            if ($isSeries) {
                $totalCount = isset($customData['series_quantity']) && is_numeric($customData['series_quantity']) 
                            ? (int)$customData['series_quantity'] : 1;
                $requiredScans = isset($customData['required_scans']) && is_numeric($customData['required_scans'])
                            ? (int)$customData['required_scans'] : 1;
            }
            
            Log::info('Template series info', [
                'is_series' => $isSeries,
                'total_count' => $totalCount,
                'required_scans' => $requiredScans,
                'custom_data' => $customData
            ]);
            
            // Для серий проверяем доступное количество
            $acquiredCount = AcquiredTemplate::where('user_template_id', $userTemplate->id)->count();
            
            // Проверка доступности шаблона
            if ($acquiredCount >= $totalCount) {
                Log::warning('Template no longer available', [
                    'acquired_count' => $acquiredCount,
                    'total_count' => $totalCount,
                    'template_id' => $userTemplate->id
                ]);
                return redirect()->route('public.template', $id)->with('error', 'Этот шаблон больше не доступен для получения.');
            }
            
            // Используем транзакцию для обеспечения целостности данных
            DB::beginTransaction();
            
            try {
                // Создаем запись о получении шаблона (убираем acquired_at из параметров)
                $acquiredTemplate = AcquiredTemplate::create([
                    'user_id' => Auth::id(),
                    'user_template_id' => $userTemplate->id,
                    'status' => 'active'
                    // Убираем 'acquired_at' - оно будет заполнено автоматически через модель
                ]);
                
                // Устанавливаем acquired_at вручную после создания
                $acquiredTemplate->acquired_at = now();
                $acquiredTemplate->save();
                
                // Логируем событие
                Log::info('Template acquired successfully', [
                    'user_id' => Auth::id(),
                    'template_id' => $userTemplate->id,
                    'acquired_id' => $acquiredTemplate->id,
                    'is_series' => $isSeries,
                    'acquired_count' => $acquiredCount + 1,
                    'total_count' => $totalCount,
                    'required_scans' => $requiredScans
                ]);
            
                DB::commit();
                
                // Возвращаемся с сообщением об успехе на ту же страницу
                return redirect()->route('public.template', $id)->with('success', 'Шаблон успешно получен и доступен в разделе "Полученные шаблоны".');
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database error during template acquisition', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => Auth::id(),
                    'template_id' => $id
                ]);
                throw $e;
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Template not found', [
                'template_id' => $id,
                'user_id' => Auth::id()
            ]);
            
            return redirect()->route('public.template', $id)->with('error', 'Шаблон не найден или больше не доступен.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error acquiring template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'template_id' => $id,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return redirect()->route('public.template', $id)->with('error', 'Произошла ошибка при получении шаблона. Пожалуйста, попробуйте позже.');
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcquiredTemplate;
use App\Models\UserTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class TemplateStatusController extends Controller
{
    /**
     * Получить подписанный URL для изменения статуса шаблона
     *
     * @param  int  $template_id
     * @param  int  $user_id
     * @param  int  $acquired_id
     * @param  int  $timestamp
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSignedStatusUrl($template_id, $user_id, $acquired_id, $timestamp)
    {
        // Проверяем, что текущий пользователь запрашивает URL для своего шаблона
        if (Auth::id() != $user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }

        try {
            // Проверяем существование шаблона и записи о его получении
            $userTemplate = UserTemplate::findOrFail($template_id);
            $acquiredTemplate = AcquiredTemplate::findOrFail($acquired_id);
            
            // Проверяем, что запись принадлежит указанному пользователю
            if ($acquiredTemplate->user_id != $user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректные данные запроса'
                ], 400);
            }

            // Генерируем подписанный URL, действительный 24 часа (временная метка передается как параметр)
            $signedUrl = URL::temporarySignedRoute(
                'template.status.change',
                now()->addDay(),
                [
                    'template' => $template_id,
                    'user' => $user_id,
                    'acquired' => $acquired_id,
                    'timestamp' => $timestamp
                ]
            );

            return response()->json([
                'success' => true,
                'url' => $signedUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при генерации URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Изменение статуса шаблона после сканирования QR-кода
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(Request $request)
    {
        try {
            // Проверяем, что пользователь авторизован
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Требуется авторизация'
                ], 401);
            }
            
            // Валидация данных
            $validated = $request->validate([
                'template_id' => 'required|integer|exists:user_templates,id',
                'user_id' => 'required|integer|exists:users,id',
                'acquired_id' => 'required|integer|exists:acquired_templates,id',
            ]);
            
            // Находим шаблон
            $userTemplate = UserTemplate::findOrFail($validated['template_id']);
            
            // Проверяем, что текущий пользователь является владельцем шаблона
            if ($userTemplate->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Только владелец шаблона может изменить его статус'
                ], 403);
            }
            
            // Находим запись о полученном шаблоне
            $acquiredTemplate = AcquiredTemplate::findOrFail($validated['acquired_id']);
            
            // Проверяем, что запись принадлежит указанному пользователю
            if ($acquiredTemplate->user_id != $validated['user_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректные данные запроса'
                ], 400);
            }
            
            // Проверяем, что шаблон активен
            if ($acquiredTemplate->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Шаблон уже не активен'
                ]);
            }
            
            // Меняем статус на "used" (использованный)
            $acquiredTemplate->status = 'used';
            $acquiredTemplate->used_at = now();
            $acquiredTemplate->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Статус шаблона успешно изменен на "Использованный"'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса шаблона: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обработке запроса'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserTemplate;
use App\Models\AcquiredTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class QrVerificationController extends Controller
{
    /**
     * Проверка прав доступа к изменению статуса шаблона через QR-код
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyAccess(Request $request)
    {
        // Проверяем, что пользователь аутентифицирован
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы должны войти в систему для проверки QR-кода'
            ]);
        }

        $user = Auth::user();
        
        // Проверяем, что пользователь является предпринимателем
        if ($user->role !== 'client' && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Только предприниматели имеют доступ к сканированию шаблонов'
            ]);
        }
        
        // Получаем ID шаблона из запроса
        $templateId = $request->input('templateId');
        $acquiredId = $request->input('acquiredId');
        
        try {
            // Находим шаблон и его приобретение
            $acquiredTemplate = AcquiredTemplate::findOrFail($acquiredId);
            $userTemplate = UserTemplate::findOrFail($acquiredTemplate->user_template_id);
            
            // Проверяем, что пользователь является создателем шаблона
            if ($userTemplate->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Только создатель шаблона может сканировать этот QR-код'
                ]);
            }
            
            // Проверяем, является ли шаблон серией (дополнительная проверка)
            $customData = json_decode($userTemplate->custom_data, true) ?: [];
            if (empty($customData['is_series'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR-код может быть отсканирован только для шаблонов серии'
                ]);
            }
            
            // Если все проверки пройдены, разрешаем доступ
            return response()->json([
                'success' => true,
                'message' => 'Доступ подтвержден. Изменение статуса...'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при проверке шаблона: ' . $e->getMessage()
            ]);
        }
    }
}

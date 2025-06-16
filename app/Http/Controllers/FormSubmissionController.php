<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\UserTemplate;
use Illuminate\Support\Facades\Log;

class FormSubmissionController extends Controller
{
    /**
     * Обработка отправки формы из шаблона
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $templateId
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request, $templateId)
    {
        // Найдем шаблон пользователя
        $userTemplate = UserTemplate::findOrFail($templateId);
        
        // Получим email из настроек шаблона
        $recipient = $userTemplate->custom_data['form_recipient'] ?? null;
        $sendCopy = $userTemplate->custom_data['form_copy_to_customer'] ?? false;
        $successMessage = $userTemplate->custom_data['form_success_message'] ?? 'Спасибо! Ваше сообщение отправлено.';
        
        // Проверим наличие email получателя
        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка конфигурации: не указан адрес получателя.'
            ], 400);
        }
        
        // Собираем данные формы
        $formData = $request->except('_token');
        
        // Найдем email отправителя, если есть
        $customerEmail = null;
        foreach ($formData as $key => $value) {
            if (stripos($key, 'email') !== false && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $customerEmail = $value;
                break;
            }
        }
        
        // Формируем HTML содержимое письма
        $content = '<h2>Новое сообщение из формы ' . $userTemplate->name . '</h2>';
        $content .= '<table border="1" cellpadding="10" cellspacing="0" width="100%">';
        
        foreach ($formData as $key => $value) {
            $content .= '<tr>';
            $content .= '<td><strong>' . htmlspecialchars($key) . '</strong></td>';
            $content .= '<td>' . htmlspecialchars($value) . '</td>';
            $content .= '</tr>';
        }
        
        $content .= '</table>';
        
        try {
            // Отправка письма администратору/владельцу шаблона
            Mail::raw($content, function ($message) use ($recipient, $userTemplate) {
                $message->to($recipient)
                        ->subject('Новое сообщение из формы ' . $userTemplate->name)
                        ->setBody($content, 'text/html');
            });
            
            // Отправка копии пользователю, если включено и есть email
            if ($sendCopy && $customerEmail) {
                $copyContent = '<h2>Копия вашего сообщения из формы ' . $userTemplate->name . '</h2>';
                $copyContent .= '<p>Спасибо за отправку сообщения! Ниже представлена копия заполненных вами данных:</p>';
                $copyContent .= $content;
                
                Mail::raw($copyContent, function ($message) use ($customerEmail, $userTemplate) {
                    $message->to($customerEmail)
                            ->subject('Копия вашего сообщения из формы ' . $userTemplate->name)
                            ->setBody($copyContent, 'text/html');
                });
            }
            
            return response()->json([
                'success' => true,
                'message' => $successMessage
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке формы: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отправке формы. Пожалуйста, попробуйте позже.'
            ], 500);
        }
    }
}

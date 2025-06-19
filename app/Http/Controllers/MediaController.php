<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\Template;
use Exception;

class MediaController extends Controller
{
    /**
     * Отображение страницы редактора медиа
     *
     * @param int|null $template
     * @return \Illuminate\View\View
     */
    public function editor($template = null)
    {
        return view('media.editor', compact('template'));
    }

    /**
     * Отображение страницы редактора медиа для шаблона
     *
     * @param Template $template
     * @return \Illuminate\View\View
     */
    public function editorForTemplate(Template $template)
    {
        return view('media.editor', compact('template'));
    }

    /**
     * Обработка загруженного медиафайла
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request)
    {
        try {
            // Проверяем наличие файла
            if (!$request->hasFile('media_file')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Файл не был загружен'
                ], 400);
            }

            $file = $request->file('media_file');
            $fileType = $file->getMimeType();
            
            // Определяем тип файла
            $isImage = strpos($fileType, 'image/') === 0;
            $isVideo = strpos($fileType, 'video/') === 0;
            
            if (!$isImage && !$isVideo) {
                return response()->json([
                    'success' => false,
                    'error' => 'Неподдерживаемый тип файла'
                ], 400);
            }
            
            // Создаем директорию для хранения файлов
            $path = 'media/' . date('Y/m/d');
            if (!Storage::disk('public')->exists($path)) {
                Storage::disk('public')->makeDirectory($path);
            }
            
            // Генерируем уникальное имя файла
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $fullPath = $path . '/' . $fileName;
            
            // Сохраняем файл
            $file->storeAs($path, $fileName, 'public');
            
            // Сохраняем данные о трансформации/обрезке
            if ($isImage && $request->has('crop_data')) {
                $cropData = json_decode($request->input('crop_data'), true);
                Session::put('image_crop_data', $cropData);
            } elseif ($isVideo) {
                $videoStart = $request->input('video_start', 0);
                $videoEnd = $request->input('video_end', 15);
                
                Session::put('video_trim_data', [
                    'start' => $videoStart,
                    'end' => $videoEnd
                ]);
            }
            
            // Сохраняем информацию о файле в сессии
            Session::put('media_editor_file', $fullPath);
            Session::put('media_editor_type', $isImage ? 'image' : 'video');
            
            // Определяем URL для перенаправления
            $redirectUrl = $this->getRedirectUrl($request);
            
            return response()->json([
                'success' => true,
                'file_path' => Storage::url($fullPath),
                'file_type' => $isImage ? 'image' : 'video',
                'redirect_url' => $redirectUrl
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка при обработке файла: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Определить URL для перенаправления после обработки медиа
     *
     * @param Request $request
     * @return string
     */
    protected function getRedirectUrl(Request $request)
    {
        // Если был указан шаблон, вернемся к нему
        if ($request->has('template_id')) {
            $templateId = $request->input('template_id');
            return route('templates.editor', $templateId);
        }
        
        // Иначе перенаправляем на дефолтный шаблон
        $defaultTemplate = Template::where('is_default', true)->first();
        
        if ($defaultTemplate) {
            return route('templates.editor', $defaultTemplate->id);
        }
        
        // Если дефолтный шаблон не найден, перенаправляем на список категорий шаблонов
        return route('client.templates.categories');
    }
}

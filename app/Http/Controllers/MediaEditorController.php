<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaEditorController extends Controller
{
    /**
     * Создание нового экземпляра контроллера.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Отображение редактора медиафайлов.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Очищаем старые данные сессии при открытии редактора
        Session::forget(['media_editor_file', 'media_editor_type']);
        
        return view('media.editor');
    }

    /**
     * Отображение редактора медиафайлов для конкретного шаблона.
     *
     * @param int $template_id
     * @return \Illuminate\View\View
     */
    public function editForTemplate($template_id)
    {
        // Найти шаблон
        $template = Template::findOrFail($template_id);
        
        return view('media.editor', compact('template'));
    }

    /**
     * Обработка загруженных медиафайлов.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processMedia(Request $request)
    {
        $request->validate([
            'media_file' => 'required|file|max:50000|mimes:jpeg,png,jpg,gif,mp4,mov,avi,wmv',
            'template_id' => 'nullable|exists:templates,id',
            'crop_data' => 'nullable|json',
            'video_start' => 'nullable|numeric',
            'video_end' => 'nullable|numeric'
        ]);

        $file = $request->file('media_file');
        $extension = $file->getClientOriginalExtension();
        $mediaType = $file->getClientMimeType();
        
        // Определяем тип медиа (видео или изображение)
        $isVideo = Str::startsWith($mediaType, 'video/');
        $finalType = $isVideo ? 'video' : 'image';
        
        // Создаем имя файла и путь для сохранения
        $fileName = 'cover_' . time() . '_' . Str::random(10) . '.' . $extension;
        $filePath = 'covers/' . auth()->id();
        
        // Сохраняем файл
        $savedPath = $file->storeAs($filePath, $fileName, 'public');
        
        // Записываем в лог для отладки
        Log::info('MediaEditor: Сохранен файл', [
            'path' => $savedPath,
            'type' => $finalType,
            'size' => $file->getSize()
        ]);
        
        // Сохраняем в сессии информацию о файле
        Session::put('media_editor_file', $savedPath);
        Session::put('media_editor_type', $finalType);
        
        // Проверяем, что данные записались в сессию
        Log::info('MediaEditor: Проверка сессии после сохранения', [
            'saved_file' => Session::get('media_editor_file'),
            'saved_type' => Session::get('media_editor_type'),
            'session_id' => Session::getId()
        ]);
        
        // Получаем ID шаблона из запроса или используем стандартный шаблон с id=1
        $templateId = $request->input('template_id', 1); // Если template_id не указан, используем 1
        
        // Всегда перенаправляем на страницу создания нового шаблона с ID=1, 
        // или с тем ID, который был передан в запросе
        $redirectUrl = route('templates.create-new', $templateId);
        
        // Применяем flash-сессию для передачи дополнительных данных
        Session::flash('media_processed', true);
        Session::flash('media_file_path', Storage::url($savedPath));

        return response()->json([
            'success' => true,
            'redirect_url' => $redirectUrl,
            'file_path' => Storage::url($savedPath),
            'file_name' => $savedPath,
            'file_type' => $finalType,
            'session_id' => Session::getId()
        ]);
    }
}


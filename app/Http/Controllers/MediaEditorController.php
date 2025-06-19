<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\MediaOptimizationService;

class MediaEditorController extends Controller
{
    /**
     * Сервис оптимизации медиафайлов
     * 
     * @var \App\Services\MediaOptimizationService
     */
    protected $mediaOptimizationService;
    
    /**
     * Создание нового экземпляра контроллера.
     *
     * @param \App\Services\MediaOptimizationService $mediaOptimizationService
     * @return void
     */
    public function __construct(MediaOptimizationService $mediaOptimizationService)
    {
        $this->middleware('auth');
        $this->mediaOptimizationService = $mediaOptimizationService;
    }

    /**
     * Отображение редактора медиафайлов.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Очищаем старые данные сессии при открытии редактора
        Session::forget(['media_editor_file', 'media_editor_type', 'media_editor_thumbnail']);
        
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
        $mediaTypeStr = $isVideo ? 'video' : 'image';
        
        // Создаем имя файла и путь для сохранения
        $fileName = 'cover_' . time() . '_' . Str::random(10) . '.' . $extension;
        $filePath = 'covers/' . auth()->id();
        
        // Сохраняем исходный файл
        $savedPath = $file->storeAs($filePath, $fileName, 'public');
        
        try {
            // Обрабатываем файл в зависимости от типа
            if ($isVideo) {
                $options = [
                    'start_time' => (float)$request->input('video_start', 0),
                    'end_time' => (float)$request->input('video_end', 15),
                    'width' => 1280, // Оптимальная ширина для мобильных устройств
                    'height' => 0    // Автоматический расчет
                ];
                
                // Обрабатываем видео с оптимизацией размера
                $result = $this->mediaOptimizationService->processVideo($savedPath, $options);
                
                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => $result['error'] ?? 'Ошибка при обработке видео'
                    ], 500);
                }
                
                // Создаем миниатюру для видео в формате WebP
                $thumbnailPath = $this->mediaOptimizationService->createVideoThumbnail($savedPath, $options['start_time'] + 1);
                
                // Используем обработанный файл
                $processedPath = $result['file_path'];
                Session::put('media_editor_thumbnail', $thumbnailPath);
                
                // Добавляем информацию о сжатии
                $compressionData = [
                    'original_size' => $result['original_size'] ?? 0,
                    'new_size' => $result['new_size'] ?? 0,
                    'compression_ratio' => $result['compression_ratio'] ?? 0,
                    'resolution' => ($result['width'] ?? 0) . 'x' . ($result['height'] ?? 'auto')
                ];
                
                Session::put('media_compression_data', $compressionData);
            } else {
                $options = [
                    'maxWidth' => 1200,
                    'maxHeight' => 1200,
                    'webpQuality' => 80,
                    'convertToWebP' => true
                ];
                
                // Получаем данные о кадрировании из запроса
                if ($request->has('crop_data')) {
                    $cropData = json_decode($request->input('crop_data'), true);
                    $options = array_merge($options, $cropData);
                }
                
                // Обрабатываем изображение с конвертацией в WebP
                $result = $this->mediaOptimizationService->processImage($savedPath, $options);
                
                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => $result['error'] ?? 'Ошибка при обработке изображения'
                    ], 500);
                }
                
                // Используем обработанный файл
                $processedPath = $result['file_path'];
                
                // Добавляем информацию о сжатии
                $compressionData = [
                    'original_size' => $result['original_size'] ?? 0,
                    'new_size' => $result['new_size'] ?? 0,
                    'compression_ratio' => $result['compression_ratio'] ?? 0,
                    'dimensions' => ($result['width'] ?? 0) . 'x' . ($result['height'] ?? 0)
                ];
                
                Session::put('media_compression_data', $compressionData);
            }
            
            // Записываем в лог для отладки
            Log::info('MediaEditor: Файл обработан и сжат', [
                'original_path' => $savedPath,
                'processed_path' => $processedPath,
                'type' => $mediaTypeStr,
                'compression_data' => $compressionData ?? null
            ]);
            
            // Сохраняем в сессии информацию о файле
            Session::put('media_editor_file', $processedPath);
            Session::put('media_editor_type', $mediaTypeStr);
            
            // Получаем ID шаблона из запроса или используем стандартный шаблон с id=1
            $templateId = $request->input('template_id', 1);
            
            // Перенаправляем на страницу создания нового шаблона
            $redirectUrl = route('templates.create-new', $templateId);
            Log::info('MediaEditor: Редирект URL', ['url' => $redirectUrl]);
            
            // Применяем flash-сессию для передачи дополнительных данных
            Session::flash('media_processed', true);
            Session::flash('media_file_path', Storage::url($processedPath));

            return response()->json([
                'success' => true,
                'redirect_url' => $redirectUrl,
                'file_path' => Storage::url($processedPath),
                'file_name' => $processedPath,
                'file_type' => $mediaTypeStr,
                'session_id' => Session::getId(),
                'compression_data' => $compressionData ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('MediaEditor: Ошибка при обработке файла', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_path' => $savedPath
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка при обработке файла: ' . $e->getMessage()
            ], 500);
        }
    }
}


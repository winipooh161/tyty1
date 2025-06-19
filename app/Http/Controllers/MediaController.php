<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\Template;
use App\Services\MediaOptimizationService;
use Exception;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    /**
     * Сервис оптимизации медиафайлов
     * 
     * @var \App\Services\MediaOptimizationService
     */
    protected $mediaOptimizationService;
    
    /**
     * Создание нового экземпляра контроллера
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
            // Добавляем подробное логирование для отладки
            Log::info('Начало обработки медиа файла', [
                'user_id' => auth()->id(),
                'has_file' => $request->hasFile('media_file'),
                'content_type' => $request->header('Content-Type')
            ]);
            
            // Проверяем наличие файла
            if (!$request->hasFile('media_file')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Файл не был загружен'
                ], 400);
            }

            $file = $request->file('media_file');
            $fileType = $file->getMimeType();
            
            // Логируем информацию о файле
            Log::info('Информация о загруженном файле', [
                'name' => $file->getClientOriginalName(),
                'type' => $fileType,
                'size' => $file->getSize(),
                'extension' => $file->getClientOriginalExtension()
            ]);
            
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
            
            Log::info('Файл успешно сохранен', [
                'fullPath' => $fullPath
            ]);
            
            // Проверяем наличие сервиса оптимизации
            if (!$this->mediaOptimizationService) {
                Log::error('Сервис оптимизации медиа не инициализирован');
                throw new Exception('Сервис оптимизации медиа недоступен');
            }
            
            // Обрабатываем файл в зависимости от типа
            if ($isImage) {
                $options = [
                    'maxWidth' => 1200,
                    'maxHeight' => 1200,
                    'webpQuality' => 80,
                    'convertToWebP' => true
                ];
                
                // Получаем данные о кадрировании из запроса
                if ($request->has('crop_data')) {
                    $cropData = json_decode($request->input('crop_data'), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('Ошибка декодирования JSON crop_data', [
                            'error' => json_last_error_msg(),
                            'input' => $request->input('crop_data')
                        ]);
                    } else {
                        $options = array_merge($options, $cropData);
                    }
                }
                
                Log::info('Начало обработки изображения', [
                    'options' => $options
                ]);
                
                // Обрабатываем изображение
                $result = $this->mediaOptimizationService->processImage($fullPath, $options);
                
                if (!$result['success']) {
                    Log::error('Ошибка при обработке изображения', [
                        'error' => $result['error'] ?? 'Неизвестная ошибка',
                        'file_path' => $fullPath
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => $result['error'] ?? 'Ошибка при обработке изображения'
                    ], 500);
                }
                
                // Сохраняем путь к обработанному файлу
                $processedPath = $result['file_path'];
                Session::put('media_editor_file', $processedPath);
                
                // Добавляем информацию о сжатии
                $compressionData = [
                    'original_size' => $result['original_size'] ?? 0,
                    'new_size' => $result['new_size'] ?? 0,
                    'compression_ratio' => $result['compression_ratio'] ?? 0
                ];
                
                Session::put('media_compression_data', $compressionData);
                
                Log::info('Изображение успешно обработано', [
                    'processed_path' => $processedPath,
                    'compression_data' => $compressionData
                ]);
                
            } elseif ($isVideo) {
                // Предварительная проверка FFmpeg
                if (!$this->mediaOptimizationService->checkFFmpeg()) {
                    Log::error('FFmpeg не установлен. Видео не может быть обработано.');
                    return response()->json([
                        'success' => false,
                        'error' => 'FFmpeg не установлен. Для обработки видео необходимо установить FFmpeg.',
                        'install_required' => true
                    ], 500);
                }
                
                $options = [
                    'start_time' => (float)$request->input('video_start', 0),
                    'end_time' => (float)$request->input('video_end', 15),
                    'width' => 1280, // Максимальная ширина для хорошего качества
                    'height' => 0, // Автоматическая высота
                ];
                
                Log::info('Начало обработки видео', [
                    'options' => $options
                ]);
                
                // Обрабатываем видео
                $result = $this->mediaOptimizationService->processVideo($fullPath, $options);
                
                if (!$result['success']) {
                    Log::error('Ошибка при обработке видео', [
                        'error' => $result['error'] ?? 'Неизвестная ошибка',
                        'file_path' => $fullPath
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => $result['error'] ?? 'Ошибка при обработке видео'
                    ], 500);
                }
                
                // Создаем миниатюру для видео
                $thumbnailPath = $this->mediaOptimizationService->createVideoThumbnail($fullPath, $options['start_time'] + 1);
                
                // Сохраняем пути к обработанному видео и миниатюре
                $processedPath = $result['file_path'];
                Session::put('media_editor_file', $processedPath);
                Session::put('media_editor_thumbnail', $thumbnailPath);
                
                // Добавляем информацию о сжатии
                $compressionData = [
                    'original_size' => $result['original_size'] ?? 0,
                    'new_size' => $result['new_size'] ?? 0,
                    'compression_ratio' => $result['compression_ratio'] ?? 0,
                    'width' => $result['width'] ?? 0,
                    'height' => $result['height'] ?? 0
                ];
                
                Session::put('media_compression_data', $compressionData);
                
                Log::info('Видео успешно обработано', [
                    'processed_path' => $processedPath,
                    'thumbnail_path' => $thumbnailPath,
                    'compression_data' => $compressionData
                ]);
            }
            
            // Сохраняем информацию о типе медиа в сессии
            Session::put('media_editor_type', $isImage ? 'image' : 'video');
            
            // Определяем URL для перенаправления
            $redirectUrl = $this->getRedirectUrl($request);
            
            Log::info('Медиа файл успешно обработан', [
                'redirect_url' => $redirectUrl
            ]);
            
            return response()->json([
                'success' => true,
                'file_path' => Storage::url($processedPath ?? $fullPath),
                'file_type' => $isImage ? 'image' : 'video',
                'redirect_url' => $redirectUrl,
                'compression_data' => $compressionData ?? null
            ]);
            
        } catch (Exception $e) {
            // Подробное логирование ошибки
            Log::error('Исключение при обработке медиа файла', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // В продакшене не стоит возвращать подробности исключения клиенту
            $errorMessage = app()->environment('production') 
                ? 'Произошла ошибка при обработке файла. Попробуйте позже.' 
                : 'Произошла ошибка при обработке файла: ' . $e->getMessage();
            
            return response()->json([
                'success' => false,
                'error' => $errorMessage,
                'debug_info' => app()->environment('production') ? null : [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine()
                ]
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
            
            try {
                // Проверяем наличие маршрута templates.create-new
                if (route('templates.create-new', $templateId, false)) {
                    return route('templates.create-new', $templateId);
                }
            } catch (\Exception $e) {
                Log::warning('Маршрут templates.create-new недоступен: ' . $e->getMessage());
            }
            
            // Резервный вариант
            try {
                if (route('templates.editor', $templateId, false)) {
                    return route('templates.editor', $templateId);
                }
            } catch (\Exception $e) {
                Log::warning('Маршрут templates.editor недоступен: ' . $e->getMessage());
            }
        }
        
        // Иначе перенаправляем на дефолтный шаблон
        $defaultTemplate = Template::where('is_default', true)->first();
        
        if ($defaultTemplate) {
            try {
                return route('templates.create-new', $defaultTemplate->id);
            } catch (\Exception $e) {
                Log::warning('Не удалось сформировать URL для создания шаблона: ' . $e->getMessage());
            }
        }
        
        // Резервный вариант перенаправления
        try {
            return route('client.templates.categories');
        } catch (\Exception $e) {
            Log::warning('Не удалось сформировать URL для категорий шаблонов: ' . $e->getMessage());
            return '/client/my-templates'; // Самый базовый вариант
        }
    }
}

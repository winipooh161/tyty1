<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Services\MediaOptimizationService;

class MediaEditorController extends Controller
{
    /**
     * Сервис оптимизации медиа
     * 
     * @var \App\Services\MediaOptimizationService
     */
    protected $mediaOptimizationService;
    
    /**
     * Список поддерживаемых форматов медиа
     */
    protected $supportedImageFormats = 'jpeg,png,gif,webp,jpg,bmp,tiff,tif,avif,heic,heif,svg';
    protected $supportedVideoFormats = 'mp4,webm,mov,avi,wmv,flv,mkv,mpeg,mpg,m4v,3gp';
    
    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\MediaOptimizationService  $mediaOptimizationService
     * @return void
     */
    public function __construct(MediaOptimizationService $mediaOptimizationService)
    {
        $this->middleware('auth');
        $this->middleware('role:client,admin');
        $this->mediaOptimizationService = $mediaOptimizationService;
    }

    /**
     * Показать страницу редактора медиа.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('media.editor');
    }
    
    /**
     * Редактор медиа для конкретного шаблона.
     *
     * @param int $template_id ID шаблона
     * @return \Illuminate\View\View
     */
    public function editForTemplate($template_id)
    {
        $template = Template::findOrFail($template_id);
        return view('media.editor', compact('template'));
    }

    /**
     * Обработка загруженного медиа-файла.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processMedia(Request $request)
    {
        try {
            Log::info('Starting media processing', [
                'has_file' => $request->hasFile('media_file'),
                'params' => $request->except(['media_file', '_token']),
                'user_agent' => $request->header('User-Agent'),
                'request_ip' => $request->ip()
            ]);
            
            // Проверка CSRF токена
            if (!$this->hasValidCsrf($request)) {
                Log::error('CSRF token mismatch', [
                    'token' => $request->header('X-CSRF-TOKEN'),
                    'session_token' => $request->session()->token()
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Неверный CSRF токен. Пожалуйста, обновите страницу.'
                ], 419);
            }
            
            // Проверяем, не осталось ли старых данных сессии от предыдущего сохранения
            if (session()->has('media_editor_processed') && session('media_editor_processed') === true) {
                $oldFile = session('media_editor_file');
                if ($oldFile) {
                    $oldFilePath = storage_path('app/public/template_covers/' . $oldFile);
                    Log::info('Found previous media session data, cleaning up', ['old_file' => $oldFile]);
                    
                    // Очищаем старые данные сессии перед обработкой нового файла
                    session()->forget(['media_editor_file', 'media_editor_type', 'media_editor_processed']);
                }
            }
            
            // Расширяем поддерживаемые форматы
            $supportedFormats = $this->supportedImageFormats . ',' . $this->supportedVideoFormats;
            
            $validator = Validator::make($request->all(), [
                'media_file' => 'required|file|mimes:' . $supportedFormats . '|max:50000', // 50MB максимум
                'template_id' => 'nullable|exists:templates,id',
                'crop_data' => 'nullable|json',
                'video_start' => 'nullable|numeric',
                'video_end' => 'nullable|numeric',
                'quality' => 'nullable|in:small,medium,large',
                'convert_to_webp' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                Log::error('Validation failed: ' . json_encode($validator->errors()));
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!$request->hasFile('media_file')) {
                Log::error('No file found in request');
                return response()->json([
                    'success' => false,
                    'error' => 'Файл не найден в запросе'
                ], 400);
            }

            $file = $request->file('media_file');
            if (!$file->isValid()) {
                Log::error('Uploaded file is not valid');
                return response()->json([
                    'success' => false,
                    'error' => 'Загруженный файл повреждён'
                ], 400);
            }

            $fileExtension = strtolower($file->getClientOriginalExtension());
            $fileName = time() . '_' . Str::random(10) . '.' . $fileExtension;
            
            Log::info('File preparation completed. Extension: ' . $fileExtension);
            
            // Проверяем тип файла
            $isVideo = in_array($fileExtension, explode(',', $this->supportedVideoFormats));
            
            // Создаём директории для сохранения
            $baseStoragePath = storage_path('app/public');
            $coversDirPath = $baseStoragePath . '/template_covers';
            
            // Проверка существования базовой директории storage/app/public
            if (!File::isDirectory($baseStoragePath)) {
                Log::info('Creating base storage directory: ' . $baseStoragePath);
                File::makeDirectory($baseStoragePath, 0755, true);
            }
            
            // Проверка существования директории для обложек
            if (!File::isDirectory($coversDirPath)) {
                Log::info('Creating covers directory: ' . $coversDirPath);
                File::makeDirectory($coversDirPath, 0755, true);
            }
            
            // Проверяем права доступа
            if (!is_writable($coversDirPath)) {
                Log::error('Directory is not writable: ' . $coversDirPath);
                chmod($coversDirPath, 0755); // Пробуем установить права
                
                if (!is_writable($coversDirPath)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Нет прав для записи в директорию: ' . $coversDirPath
                    ], 500);
                }
            }
            
            Log::info('Directories checked and ready');
            
            if ($isVideo) {
                // Обработка видео с оптимизацией
                $fileSize = $file->getSize();
                $fileSizeMB = $fileSize / (1024 * 1024);
                
                Log::info('Processing video file', [
                    'size' => round($fileSizeMB, 2) . 'MB', 
                    'extension' => $fileExtension,
                    'original_filename' => $file->getClientOriginalName()
                ]);
                
                // Более тщательная обработка параметров с логированием и отладкой
                $videoStartTime = null;
                $videoEndTime = null;
                
                if ($request->has('video_start')) {
                    $rawStartTime = $request->input('video_start');
                    
                    if (is_numeric($rawStartTime)) {
                        $videoStartTime = floatval($rawStartTime);
                        Log::info('Параметр video_start успешно обработан:', [
                            'raw_value' => $rawStartTime,
                            'parsed_value' => $videoStartTime
                        ]);
                    } else {
                        Log::warning('Некорректный параметр video_start:', [
                            'raw_value' => $rawStartTime,
                            'type' => gettype($rawStartTime)
                        ]);
                    }
                }
                
                if ($request->has('video_end')) {
                    $rawEndTime = $request->input('video_end');
                    
                    if (is_numeric($rawEndTime)) {
                        $videoEndTime = floatval($rawEndTime);
                        Log::info('Параметр video_end успешно обработан:', [
                            'raw_value' => $rawEndTime,
                            'parsed_value' => $videoEndTime
                        ]);
                    } else {
                        Log::warning('Некорректный параметр video_end:', [
                            'raw_value' => $rawEndTime,
                            'type' => gettype($rawEndTime)
                        ]);
                    }
                }
                
                // Добавляем логирование отладочного параметра длительности
                if ($request->has('video_clip_duration')) {
                    $clipDuration = floatval($request->input('video_clip_duration'));
                    Log::info('Запрошенная длительность обрезки:', [
                        'clip_duration' => $clipDuration,
                        'calculated_duration' => ($videoEndTime && $videoStartTime) ? ($videoEndTime - $videoStartTime) : null
                    ]);
                }
                
                // Выбор качества в зависимости от размера файла
                $quality = $request->input('quality', 'medium');
                
                // Автоматически снижаем качество для больших файлов
                if ($fileSizeMB > 20) {
                    $quality = 'small';
                    Log::info('Принудительно установлено низкое качество для большого видео: ' . round($fileSizeMB, 2) . 'MB');
                } else if ($fileSizeMB > 10) {
                    // Для файлов средней величины снижаем качество только если не запрошен high
                    if ($quality !== 'large') $quality = 'small';
                    Log::info('Скорректировано качество для среднего файла: ' . $quality);
                }
                
                Log::info('Параметры обрезки видео перед вызовом optimizeVideo:', [
                    'start' => $videoStartTime,
                    'end' => $videoEndTime,
                    'quality' => $quality
                ]);
                
                $fileName = $this->mediaOptimizationService->optimizeVideo(
                    $file, 
                    $fileName,
                    $videoStartTime,
                    $videoEndTime,
                    $quality
                );
                
                $mediaType = 'video';
                $fileUrl = asset('storage/template_covers/' . $fileName);
                
                // Проверяем результат оптимизации
                $processedPath = $coversDirPath . '/' . $fileName;
                if (file_exists($processedPath)) {
                    $processedSize = filesize($processedPath);
                    $processedSizeMB = $processedSize / (1024 * 1024);
                    
                    $compressionRatio = $fileSize > 0 ? ($processedSize / $fileSize) * 100 : 0;
                    
                    Log::info('Video processing completed', [
                        'file' => $fileName,
                        'original_size' => round($fileSizeMB, 2) . 'MB',
                        'processed_size' => round($processedSizeMB, 2) . 'MB',
                        'compression' => round(100 - $compressionRatio, 1) . '%'
                    ]);
                } else {
                    Log::warning('Processed video file not found at: ' . $processedPath);
                }
            } else {
                // Обработка изображения с оптимизацией
                Log::info('Processing image file with optimization');
                
                // Определяем, нужно ли конвертировать в WebP
                $convertToWebp = $request->input('convert_to_webp', true);
                
                // Оптимизируем и сохраняем изображение
                $fileName = $this->mediaOptimizationService->optimizeImage($file, $fileName, $convertToWebp);
                
                $mediaType = 'image';
                $fileUrl = asset('storage/template_covers/' . $fileName);
                Log::info('Image processing completed: ' . $fileName);
            }
            
            // Сохраняем информацию о файле в сессии
            session()->put('media_editor_file', $fileName);
            session()->put('media_editor_type', $mediaType);
            session()->put('media_editor_processed', true);
            session()->put('media_editor_timestamp', time());
            
            Log::info('Media info saved in session: ' . $fileName . ', type: ' . $mediaType);
            
            // Проверяем размер обработанного файла
            $processedFilePath = $coversDirPath . '/' . $fileName;
            if (file_exists($processedFilePath)) {
                $processedSize = filesize($processedFilePath);
                Log::info('Processed file size: ' . round($processedSize / 1024, 2) . ' KB');
            }
            
            // Формируем URL для редиректа
            $redirectUrl = null;
            if ($request->template_id) {
                $redirectUrl = route('client.templates.create-new', $request->template_id);
                Log::info('Redirecting to template creation: ' . $redirectUrl);
                
                return response()->json([
                    'success' => true,
                    'redirect_url' => $redirectUrl,
                    'file_name' => $fileName,
                    'file_type' => $mediaType,
                    'message' => 'Файл успешно обработан'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'file_url' => $fileUrl,
                'media_type' => $mediaType,
                'file_name' => $fileName,
                'template_id' => $request->template_id,
                'redirect_url' => $redirectUrl
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in processMedia: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка при обработке файла: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Проверка валидности CSRF токена.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    private function hasValidCsrf(Request $request)
    {
        // Проверяем токен из заголовка X-CSRF-TOKEN
        $token = $request->header('X-CSRF-TOKEN');
        
        if (!$token) {
            // Проверяем токен из поля _token
            $token = $request->input('_token');
        }
        
        // Сравниваем с токеном сессии
        return $token && hash_equals($request->session()->token(), $token);
    }
    
    /**
     * Проверка наличия FFmpeg.
     *
     * @return bool
     */
    private function checkFFmpegInstalled()
    {
        $output = null;
        $returnVar = null;
        
        @exec('ffmpeg -version', $output, $returnVar);
        
        return $returnVar === 0;
    }
    
    /**
     * Создает директорию, если она не существует.
     *
     * @param  string  $path
     * @return void
     */
    private function ensureDirectoryExists($path)
    {
        $fullPath = public_path($path);
        
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }
}

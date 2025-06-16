<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\UserTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class TemplateEditorController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:client,admin');
    }

    /**
     * Показать редактор шаблона.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $template = Template::findOrFail($id);
        
        // Проверяем, есть ли у текущего пользователя сохраненный шаблон
        $userTemplate = UserTemplate::where('user_id', Auth::id())
                        ->where('template_id', $template->id)
                        ->latest()
                        ->first();
        
        // Сбрасываем данные об обложке, если они сохранены в сессии
        if (session()->has('cover_preview')) {
            session()->forget('cover_preview');
        }
        
        // Загружаем список VIP-пользователей
        $vipUsers = User::where('status', 'vip')->orderBy('name')->get();
        
        // Передаем параметр is_new_template = false
        $is_new_template = false;
        
        return view('templates.editor', compact('template', 'userTemplate', 'is_new_template', 'vipUsers'));
    }
    
    /**
     * Создать новый шаблон на основе существующего.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function createNew($id)
    {
        $template = Template::findOrFail($id);
        
        // Принудительно создаем новый шаблон
        $userTemplate = null;
        
        // Логируем информацию о сессии для диагностики
        Log::info('Template creation started with id: ' . $id);
        Log::info('Media editor session data: ' . json_encode([
            'file' => session('media_editor_file'),
            'type' => session('media_editor_type'),
            'processed' => session('media_editor_processed')
        ]));
        
        // Получаем данные из сессии, если они есть
        $media_editor_file = session('media_editor_file');
        $media_editor_type = session('media_editor_type');
        $media_editor_processed = session('media_editor_processed', false);
        
        // Проверяем, существует ли файл физически
        if ($media_editor_file) {
            $filePath = storage_path('app/public/template_covers/' . $media_editor_file);
            if (!File::exists($filePath)) {
                Log::warning('Media file not found physically: ' . $filePath);
                session()->forget(['media_editor_file', 'media_editor_type', 'media_editor_processed']);
                $media_editor_file = null;
                $media_editor_type = null;
            } else {
                Log::info('Media file confirmed to exist: ' . $filePath);
            }
        }
        
        // Загружаем список VIP-пользователей
        $vipUsers = User::where('status', 'vip')->orderBy('name')->get();
        
        // Передаем параметр is_new_template = true
        $is_new_template = true;
        
        return view('templates.editor', compact('template', 'userTemplate', 'is_new_template', 
            'vipUsers', 'media_editor_file', 'media_editor_type', 'media_editor_processed'));
    }

    /**
     * Сохранить отредактированный пользователем шаблон.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request, $id)
    {
        $template = Template::findOrFail($id);
        
        // Логируем входящие данные для отладки
        Log::info('Template save request:', [
            'template_id' => $id,
            'user_id' => Auth::id(),
            'request_data' => $request->except(['_token', 'cover_file']),
            'has_cover_file' => $request->hasFile('cover_file'),
            'custom_data_raw' => $request->input('custom_data')
        ]);
        
        // Улучшенная валидация с более гибким требованием к файлу
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'required|string',
            'custom_data' => 'nullable|string',
            'cover_file' => 'nullable|file|mimes:jpeg,png,gif,webp,mp4,webm|max:20480',
            'media_editor_file' => 'nullable|string',
            'media_editor_type' => 'nullable|string|in:image,video',
            'has_existing_cover' => 'nullable|string',
            'target_user_id' => 'nullable|exists:users,id',
        ]);
        
        // Правильно обрабатываем custom_data
        $customData = [];
        if ($request->filled('custom_data')) {
            try {
                $decodedData = json_decode($request->input('custom_data'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
                    $customData = $decodedData;
                    Log::info('Successfully parsed custom_data:', $customData);
                } else {
                    Log::warning('Failed to parse custom_data as JSON:', [
                        'error' => json_last_error_msg(),
                        'raw_data' => $request->input('custom_data')
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Exception when decoding custom_data:', [
                    'error' => $e->getMessage(),
                    'raw_data' => $request->input('custom_data')
                ]);
            }
        }
        
        // Дополнительная обработка данных серии из HTML, если они не присутствуют в custom_data
        $htmlContent = $validatedData['html_content'];
        
        // Проверяем наличие полей серии в HTML и извлекаем их значения
        if (preg_match('/data-editable="series_quantity"/', $htmlContent)) {
            if (!isset($customData['series_quantity'])) {
                // Пытаемся найти значение в HTML
                if (preg_match('/data-editable="series_quantity"[^>]*value="(\d+)"/', $htmlContent, $matches)) {
                    $customData['series_quantity'] = (int)$matches[1];
                } elseif (preg_match('/<[^>]*data-editable="series_quantity"[^>]*>(\d+)<\/[^>]*>/', $htmlContent, $matches)) {
                    $customData['series_quantity'] = (int)$matches[1];
                } else {
                    $customData['series_quantity'] = 1;
                }
            }
            $customData['is_series'] = ($customData['series_quantity'] ?? 1) > 1;
        }
        
        if (preg_match('/data-editable="required_scans"/', $htmlContent)) {
            if (!isset($customData['required_scans'])) {
                if (preg_match('/data-editable="required_scans"[^>]*value="(\d+)"/', $htmlContent, $matches)) {
                    $customData['required_scans'] = (int)$matches[1];
                } elseif (preg_match('/<[^>]*data-editable="required_scans"[^>]*>(\d+)<\/[^>]*>/', $htmlContent, $matches)) {
                    $customData['required_scans'] = (int)$matches[1];
                } else {
                    $customData['required_scans'] = 1;
                }
            }
        }
        
        // Обеспечиваем корректность данных серии
        if (isset($customData['is_series']) && $customData['is_series'] && !isset($customData['series_quantity'])) {
            $customData['series_quantity'] = 1;
        }
        if (!isset($customData['required_scans'])) {
            $customData['required_scans'] = 1;
        }
        
        Log::info('Final custom_data after processing:', $customData);
        
        // Данные для создания/обновления шаблона
        $templateData = [
            'name' => $validatedData['name'],
            'html_content' => $validatedData['html_content'],
            'custom_data' => $customData,
            'status' => 'published',
            'target_user_id' => $validatedData['target_user_id'] ?? null,
        ];
        
        // Проверяем, нужно ли принудительно создать новый шаблон
        if ($request->input('is_new_template') == '1') {
            // Принудительно создаем новый шаблон
            $userTemplate = null;
        } else {
            // Находим существующий шаблон
            $userTemplate = UserTemplate::where('user_id', Auth::id())
                         ->where('template_id', $template->id)
                         ->latest()
                         ->first();
        }
        
        // Флаг для отслеживания успешной обработки файла
        $fileProcessed = false;
        
        // Первый приоритет: проверяем явно указанный файл из медиа-редактора в форме
        if ($request->filled('media_editor_file')) {
            $fileName = $request->input('media_editor_file');
            $mediaType = $request->input('media_editor_type');
            $filePath = storage_path('app/public/template_covers/' . $fileName);
            
            if (File::exists($filePath)) {
                Log::info('Using media editor file from form: ' . $fileName);
                $templateData['cover_path'] = $fileName;
                $templateData['cover_type'] = $mediaType;
                $fileProcessed = true;
            } else {
                Log::warning('Media editor file from form not found: ' . $filePath);
            }
        }
        // Второй приоритет: проверяем загруженный файл
        elseif ($request->hasFile('cover_file')) {
            Log::info('Processing uploaded file');
            $coverFile = $request->file('cover_file');
            $fileExtension = strtolower($coverFile->getClientOriginalExtension());
            $fileName = time() . '_' . Str::random(10) . '.' . $fileExtension;
            
            // Определяем тип файла
            $isVideo = in_array($fileExtension, ['mp4', 'webm']);
            $coverType = $isVideo ? 'video' : 'image';
            
            // Создаем полный путь к директории
            $publicStorage = storage_path('app/public');
            $coversPath = $publicStorage . '/template_covers';
            
            // Создаем директории, если их нет
            if (!File::isDirectory($publicStorage)) {
                File::makeDirectory($publicStorage, 0755, true);
            }
            
            if (!File::isDirectory($coversPath)) {
                File::makeDirectory($coversPath, 0755, true);
            }
            
            if ($isVideo) {
                try {
                    // Путь для сохранения
                    $outputPath = $coversPath . '/' . $fileName;
                    
                    // Проверяем наличие FFmpeg
                    $hasFFmpeg = $this->checkFFmpegInstalled();
                    
                    if ($hasFFmpeg) {
                        // Временный путь
                        $tempPath = $coverFile->getRealPath();
                        
                        // Экранируем пути для безопасного использования в командах
                        $escapedTempPath = escapeshellarg($tempPath);
                        $escapedOutputPath = escapeshellarg($outputPath);
                        
                        // Получаем длительность видео, если возможно
                        try {
                            $ffprobeCmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$escapedTempPath}";
                            $duration = (float)trim(shell_exec($ffprobeCmd));
                            
                            if ($duration > 15) {
                                return redirect()->back()->withErrors(['cover_file' => 'Видео должно быть не длиннее 15 секунд']);
                            }
                        } catch (\Exception $e) {
                            // Если не удалось определить длину, продолжаем без проверки
                            Log::warning('Не удалось определить длительность видео: ' . $e->getMessage());
                        }
                        
                        // Сжимаем видео с использованием FFmpeg
                        $ffmpegCmd = "ffmpeg -i {$escapedTempPath} -vf scale=640:-2 -c:v libx264 -preset medium -crf 28 -c:a aac -b:a 96k {$escapedOutputPath}";
                        $output = null;
                        $returnVar = null;
                        exec($ffmpegCmd, $output, $returnVar);
                        
                        if ($returnVar !== 0) {
                            // Ошибка при выполнении ffmpeg, логируем и используем стандартное сохранение
                            Log::error('FFmpeg ошибка: ' . implode("\n", $output));
                            $this->saveFileDirectly($coverFile, $outputPath);
                        }
                    } else {
                        // FFmpeg не установлен, сохраняем файл без обработки
                        $this->saveFileDirectly($coverFile, $outputPath);
                    }
                    
                    $templateData['cover_path'] = $fileName;
                    $templateData['cover_type'] = 'video';
                } catch (\Exception $e) {
                    Log::error('Ошибка при сохранении видео: ' . $e->getMessage());
                    return redirect()->back()->withErrors(['cover_file' => 'Ошибка при сохранении видео: ' . $e->getMessage()]);
                }
            } else {
                // Обработка изображения с Intervention Image
                try {
                    $img = Image::make($coverFile->getRealPath());
                    $img->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    
                    // Сохраняем с более высоким сжатием для JPG
                    if (in_array($fileExtension, ['jpg', 'jpeg'])) {
                        $img->save($coversPath . '/' . $fileName, 75);
                    } else {
                        $img->save($coversPath . '/' . $fileName);
                    }
                    
                    $templateData['cover_path'] = $fileName;
                    $templateData['cover_type'] = 'image';
                } catch (\Exception $e) {
                    Log::error('Ошибка при сохранении изображения: ' . $e->getMessage());
                    return redirect()->back()->withErrors(['cover_file' => 'Ошибка при сохранении изображения: ' . $e->getMessage()]);
                }
            }
            
            // Если был старый файл обложки, удаляем его
            if ($userTemplate && $userTemplate->cover_path) {
                $oldFilePath = $coversPath . '/' . $userTemplate->cover_path;
                if (File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }
            }
        } 
        // Третий приоритет: проверяем файл из сессии (если он не был передан через форму)
        elseif (!$fileProcessed && session()->has('media_editor_file')) {
            $fileName = session('media_editor_file');
            $mediaType = session('media_editor_type');
            $filePath = storage_path('app/public/template_covers/' . $fileName);
            
            if (File::exists($filePath)) {
                Log::info('Using media editor file from session: ' . $fileName);
                $templateData['cover_path'] = $fileName;
                $templateData['cover_type'] = $mediaType;
                $fileProcessed = true;
            } else {
                Log::warning('Media editor file from session not found: ' . $filePath);
            }
        }
        // Четвертый приоритет: проверяем существующую обложку
        elseif ($request->has('has_existing_cover') && $userTemplate && $userTemplate->cover_path) {
            Log::info('Using existing cover: ' . $userTemplate->cover_path);
            $templateData['cover_path'] = $userTemplate->cover_path;
            $templateData['cover_type'] = $userTemplate->cover_type;
            $fileProcessed = true;
        }
        
        // Если файл не был обработан, возвращаем ошибку
        if (!$fileProcessed) {
            Log::warning('No cover file processed! Returning error.');
            return redirect()->back()
                ->withInput()
                ->withErrors(['cover_file' => 'Обложка обязательна для шаблона']);
        }
        
        // Создаем или обновляем пользовательский шаблон
        if ($userTemplate && $request->input('is_new_template') != '1') {
            Log::info('Updating existing template:', [
                'user_template_id' => $userTemplate->id,
                'template_data' => $templateData
            ]);
            $userTemplate->update($templateData);
        } else {
            $templateData['user_id'] = Auth::id();
            $templateData['template_id'] = $template->id;
            Log::info('Creating new template:', $templateData);
            $userTemplate = UserTemplate::create($templateData);
        }
        
        Log::info('Template saved successfully:', [
            'user_template_id' => $userTemplate->id,
            'final_custom_data' => $userTemplate->custom_data
        ]);
        
        // Очищаем данные сессии после сохранения - исправляем проблему с дубликатами
        session()->forget(['media_editor_file', 'media_editor_type', 'media_editor_processed', 'cover_preview']);
        
        return redirect()->route('user.templates')->with('status', 'Шаблон успешно сохранен и опубликован!');
    }
    
    /**
     * Проверяет, установлен ли FFmpeg на сервере
     *
     * @return bool
     */
    private function checkFFmpegInstalled()
    {
        $output = null;
        $returnVar = null;
        
        // Выполняем простую проверку наличия ffmpeg
        @exec('ffmpeg -version', $output, $returnVar);
        
        return $returnVar === 0;
    }
    
    /**
     * Сохраняет файл напрямую без использования FFmpeg
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $outputPath
     * @return bool
     */
    private function saveFileDirectly($file, $outputPath)
    {
        try {
            return $file->move(dirname($outputPath), basename($outputPath));
        } catch (\Exception $e) {
            Log::error('Ошибка при прямом сохранении файла: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Сохранить черновик шаблона с помощью AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAjax(Request $request, $id)
    {
        $template = Template::findOrFail($id);
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'required|string',
            'custom_data' => 'nullable',
        ]);
        
        // Обработка данных custom_data
        $customData = json_decode($validatedData['custom_data'], true) ?? [];
        
        // Создаем или обновляем пользовательский шаблон
        $userTemplate = UserTemplate::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'template_id' => $template->id
            ],
            [
                'name' => $validatedData['name'],
                'html_content' => $validatedData['html_content'],
                'custom_data' => $customData,
                'status' => 'published', // Автоматически публикуем при сохранении
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Шаблон сохранен и опубликован',
            'template_id' => $userTemplate->id
        ]);
    }
}

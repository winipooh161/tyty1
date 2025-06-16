<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class MediaOptimizationService
{
    /**
     * Максимальные размеры для изображений
     */
    protected $maxWidth = 1200;
    protected $maxHeight = 1200;
    
    /**
     * Настройки качества сжатия изображений
     */
    protected $jpgQuality = 75;
    protected $pngCompressionLevel = 6;
    protected $webpQuality = 80;
    protected $avifQuality = 65;
    
    /**
     * Настройки качества сжатия видео
     */
    protected $videoPresets = [
        'small' => [
            'resolution' => '480:-2',      // 480p
            'bitrate' => '500k',           // 500 кбит/с
            'audio_bitrate' => '64k',      // 64 кбит/с аудио
            'crf' => 30,                  // Более высокое значение = больше сжатие
        ],
        'medium' => [
            'resolution' => '640:-2',      // 640p
            'bitrate' => '1000k',          // 1 Мбит/с
            'audio_bitrate' => '96k',      // 96 кбит/с аудио
            'crf' => 28,                  // Стандартное сжатие
        ],
        'large' => [
            'resolution' => '1280:-2',     // 720p
            'bitrate' => '2500k',          // 2.5 Мбит/с
            'audio_bitrate' => '128k',     // 128 кбит/с аудио
            'crf' => 23,                  // Более низкое сжатие
        ]
    ];
    
    /**
     * Поддерживаемые форматы изображений
     */
    protected $supportedImageFormats = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif', 'avif', 'heic', 'heif', 'svg'
    ];
    
    /**
     * Поддерживаемые форматы видео
     */
    protected $supportedVideoFormats = [
        'mp4', 'webm', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'mpeg', 'mpg', 'm4v', '3gp'
    ];
    
    /**
     * Оптимизировать изображение
     *
     * @param \Illuminate\Http\UploadedFile|resource|string $image Загруженный файл или путь к файлу
     * @param string $fileName Имя файла для сохранения
     * @param bool $convertToWebp Конвертировать в WebP, если возможно
     * @return string Имя сохраненного файла
     */
    public function optimizeImage($image, $fileName, $convertToWebp = true)
    {
        try {
            // Создаём директорию для сохранения
            $directory = storage_path('app/public/template_covers');
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            $originalExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $outputPath = "{$directory}/{$fileName}";
            
            // Создаем изображение с помощью Intervention
            if ($image instanceof UploadedFile) {
                $img = Image::make($image->getRealPath());
            } elseif (is_string($image) && file_exists($image)) {
                $img = Image::make($image);
            } else {
                $img = Image::make($image);
            }
            
            // Особая обработка для SVG файлов
            if ($originalExtension === 'svg') {
                // SVG - просто сохраняем как есть, без изменений
                if ($image instanceof UploadedFile) {
                    $image->move($directory, $fileName);
                } elseif (is_string($image) && file_exists($image)) {
                    File::copy($image, $outputPath);
                } else {
                    file_put_contents($outputPath, $image);
                }
                
                Log::info('SVG файл сохранен без изменений', ['file' => $fileName]);
                return $fileName;
            }
            
            // Получаем оригинальные размеры
            $originalWidth = $img->width();
            $originalHeight = $img->height();
            
            // Изменяем размер изображения, не увеличивая его если оно меньше
            if ($originalWidth > $this->maxWidth || $originalHeight > $this->maxHeight) {
                $img->resize($this->maxWidth, $this->maxHeight, function($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                
                Log::info('Изображение изменено в размерах', [
                    'file' => $fileName,
                    'original' => "{$originalWidth}x{$originalHeight}",
                    'new' => "{$img->width()}x{$img->height()}"
                ]);
            }
            
            // Если нужно конвертировать в WebP, и исходный формат не WebP/AVIF
            if ($convertToWebp && !in_array($originalExtension, ['webp', 'avif']) && $originalExtension !== 'svg') {
                $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.webp';
                $outputPath = "{$directory}/{$newFileName}";
                
                // Сохраняем в WebP с заданным качеством
                $img->encode('webp', $this->webpQuality);
                $img->save($outputPath);
                
                Log::info('Изображение конвертировано в WebP', [
                    'original_file' => $fileName,
                    'new_file' => $newFileName,
                    'quality' => $this->webpQuality
                ]);
                
                return $newFileName;
            }
            
            // Оптимизируем в зависимости от формата
            switch ($originalExtension) {
                case 'jpg':
                case 'jpeg':
                    $img->encode('jpg', $this->jpgQuality);
                    break;
                    
                case 'png':
                    // Проверяем, содержит ли PNG прозрачность
                    $hasTransparency = $this->checkImageTransparency($img);
                    if (!$hasTransparency) {
                        // Если нет прозрачности, конвертируем в JPG с белым фоном для лучшего сжатия
                        $img->encode('jpg', $this->jpgQuality);
                        $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
                        $outputPath = $directory . '/' . $newFileName;
                        Log::info('PNG без прозрачности конвертирован в JPG', ['file' => $newFileName]);
                        $fileName = $newFileName;
                    } else {
                        // PNG с прозрачностью, оптимизируем как PNG
                        $img->encode('png', $this->pngCompressionLevel * 10);
                    }
                    break;
                    
                case 'gif':
                    // Проверяем, является ли GIF анимированным
                    if ($this->isAnimatedGif($image instanceof UploadedFile ? $image->getRealPath() : $image)) {
                        // Для анимированных GIF просто сохраняем как есть
                        if ($image instanceof UploadedFile) {
                            $image->move($directory, $fileName);
                        } else {
                            file_put_contents($outputPath, $image instanceof UploadedFile ? file_get_contents($image->getRealPath()) : $image);
                        }
                        Log::info('Анимированный GIF сохранен без изменений', ['file' => $fileName]);
                        return $fileName;
                    }
                    // Для статичных GIF конвертируем в PNG
                    $img->encode('png', $this->pngCompressionLevel * 10);
                    break;
                    
                case 'webp':
                    $img->encode('webp', $this->webpQuality);
                    break;
                    
                case 'avif':
                    // Если библиотека поддерживает AVIF
                    if (method_exists($img, 'avif') || method_exists($img, 'encode') && in_array('avif', $img->getDrivers())) {
                        $img->encode('avif', $this->avifQuality);
                    } else {
                        // Если AVIF не поддерживается, конвертируем в WebP
                        $img->encode('webp', $this->webpQuality);
                        $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.webp';
                        $outputPath = $directory . '/' . $newFileName;
                        Log::info('AVIF конвертирован в WebP из-за отсутствия поддержки', ['file' => $newFileName]);
                        $fileName = $newFileName;
                    }
                    break;
                
                case 'bmp':
                case 'tiff':
                case 'tif': 
                    // Конвертируем эти форматы в JPG для лучшего сжатия
                    $img->encode('jpg', $this->jpgQuality);
                    $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
                    $outputPath = $directory . '/' . $newFileName;
                    Log::info('Формат конвертирован в JPG для сжатия', [
                        'original' => $originalExtension, 
                        'file' => $newFileName
                    ]);
                    $fileName = $newFileName;
                    break;
                
                case 'heic':
                case 'heif':
                    // Конвертируем в WebP, так как поддержка HEIC/HEIF ограничена
                    $img->encode('webp', $this->webpQuality);
                    $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.webp';
                    $outputPath = $directory . '/' . $newFileName;
                    Log::info('HEIC/HEIF конвертирован в WebP', ['file' => $newFileName]);
                    $fileName = $newFileName;
                    break;
                
                default:
                    // Для неизвестных форматов конвертируем в JPG
                    $img->encode('jpg', $this->jpgQuality);
                    $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
                    $outputPath = $directory . '/' . $newFileName;
                    Log::info('Неизвестный формат конвертирован в JPG', ['file' => $newFileName]);
                    $fileName = $newFileName;
            }
            
            // Сохраняем оптимизированное изображение
            $img->save($outputPath);
            
            // Дополнительная оптимизация крупных файлов
            $fileSize = filesize($outputPath);
            if ($fileSize > 500 * 1024) { // Если больше 500KB
                $currentExtension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
                
                if ($currentExtension === 'jpg' || $currentExtension === 'jpeg') {
                    // Повторно сохраняем с более низким качеством
                    $img = Image::make($outputPath);
                    $img->save($outputPath, $this->jpgQuality - 10);
                    Log::info('Крупное JPG повторно сжато с пониженным качеством', [
                        'file' => $fileName,
                        'original_size' => round($fileSize / 1024) . 'KB',
                        'new_size' => round(filesize($outputPath) / 1024) . 'KB'
                    ]);
                } elseif ($currentExtension === 'png') {
                    // Используем pngquant для дополнительной оптимизации PNG, если он доступен
                    if ($this->checkToolInstalled('pngquant')) {
                        $this->optimizePngWithPngquant($outputPath);
                    }
                }
            }
            
            Log::info('Изображение оптимизировано', [
                'file' => $fileName,
                'size' => round(filesize($outputPath) / 1024) . 'KB',
                'format' => pathinfo($outputPath, PATHINFO_EXTENSION)
            ]);
            
            return pathinfo($outputPath, PATHINFO_BASENAME);
        } catch (\Exception $e) {
            Log::error('Ошибка при оптимизации изображения: ' . $e->getMessage(), [
                'file' => $fileName,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Оптимизировать видео
     *
     * @param \Illuminate\Http\UploadedFile $file Загруженный файл
     * @param string $fileName Имя файла для сохранения
     * @param float|null $startTime Время начала видео в секундах
     * @param float|null $endTime Время конца видео в секундах
     * @param string $quality Качество видео (small, medium, large)
     * @return string Имя сохраненного файла
     */
    public function optimizeVideo($file, $fileName, $startTime = null, $endTime = null, $quality = 'medium')
    {
        try {
            // Создаём директорию для сохранения
            $directory = storage_path('app/public/template_covers');
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            $outputPath = $directory . '/' . $fileName;
            $originalExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Проверяем наличие FFmpeg
            if (!$this->checkFFmpegInstalled()) {
                // Если FFmpeg не установлен, просто копируем файл
                if ($file instanceof UploadedFile) {
                    $file->move($directory, $fileName);
                } else {
                    copy($file, $outputPath);
                }
                
                Log::warning('FFmpeg не установлен. Файл сохранен без оптимизации', [
                    'file' => $fileName
                ]);
                
                return $fileName;
            }
            
            // Временный файл для исходного видео
            $tempPath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
            $tempDir = sys_get_temp_dir();
            $tempPrefix = 'video_processing_' . time() . '_';
            
            // Создаём уникальные имена для временных файлов
            $tempTrimmedPath = $tempDir . '/' . $tempPrefix . 'trimmed.mp4';
            
            // Получаем информацию о файле
            $fileSize = $file instanceof UploadedFile ? $file->getSize() : filesize($tempPath);
            $fileSizeMB = $fileSize / (1024 * 1024);
            
            Log::info('Исходное видео:', [
                'размер' => round($fileSizeMB, 2) . ' MB',
                'путь' => $tempPath,
                'расширение' => $originalExtension
            ]);
            
            // Экранируем пути для безопасного использования в командах
            $escapedTempPath = escapeshellarg($tempPath);
            $escapedOutputPath = escapeshellarg($outputPath);
            $escapedTrimmedPath = escapeshellarg($tempTrimmedPath);
            
            // Получаем информацию о видео
            $videoInfo = $this->getVideoInfoDetailed($tempPath);
            $videoDuration = $videoInfo['duration'] ?? 0;
            
            Log::info('Информация о видео:', [
                'длительность' => $videoDuration,
                'ширина' => $videoInfo['width'],
                'высота' => $videoInfo['height'],
                'начальное_время' => $startTime,
                'конечное_время' => $endTime
            ]);
            
            // Определяем параметры обрезки
            $shouldTrim = false;
            $startTimeFloat = is_numeric($startTime) ? (float)$startTime : 0;
            $endTimeFloat = is_numeric($endTime) ? (float)$endTime : min($videoDuration, 15);
            
            // Проверяем валидность параметров для обрезки
            if ($startTimeFloat >= 0 && $startTimeFloat < $endTimeFloat && $endTimeFloat <= $videoDuration) {
                $shouldTrim = true;
                // Ограничиваем длину видео 15 секундами если не указано конкретное значение
                if (!is_numeric($endTime) && $videoDuration > 15) {
                    $endTimeFloat = $startTimeFloat + 15;
                }
            } else if ($videoDuration > 15) {
                // Если видео длиннее 15 секунд, обрезаем его автоматически
                $startTimeFloat = 0;
                $endTimeFloat = 15;
                $shouldTrim = true;
            }
            
            if ($shouldTrim) {
                Log::info('Применяется обрезка видео', [
                    'начало' => $startTimeFloat,
                    'конец' => $endTimeFloat,
                    'длительность' => $endTimeFloat - $startTimeFloat
                ]);
            }
            
            // Выбираем пресет качества
            if (!isset($this->videoPresets[$quality])) {
                $quality = 'medium'; // Безопасное значение по умолчанию
            }
            $preset = $this->videoPresets[$quality];
            
            // **ПЕРВЫЙ ЭТАП: ОБРЕЗКА** - сначала делаем обрезку отдельной командой
            if ($shouldTrim) {
                // ВАЖНО: правильный порядок параметров для точной обрезки
                // -ss перед -i для быстрого поиска и точной обрезки начала
                $trimCmd = "ffmpeg -y -ss " . number_format($startTimeFloat, 3, '.', '') .
                         " -i {$escapedTempPath}" .
                         " -t " . number_format($endTimeFloat - $startTimeFloat, 3, '.', '') .
                         " -c copy {$escapedTrimmedPath}";
                
                Log::info('Выполняется команда обрезки:', ['command' => $trimCmd]);
                
                $output = [];
                $returnVar = null;
                exec($trimCmd . " 2>&1", $output, $returnVar);
                
                if ($returnVar === 0 && file_exists($tempTrimmedPath)) {
                    // Успешная обрезка - используем обрезанное видео для следующих шагов
                    Log::info('Видео успешно обрезано');
                    
                    // Проверим длительность полученного файла
                    $trimmedInfo = $this->getVideoInfoDetailed($tempTrimmedPath);
                    Log::info('Длительность обрезанного видео: ' . ($trimmedInfo['duration'] ?? 'неизвестно'));
                    
                    // Теперь используем обрезанное видео как источник для кодирования
                    $inputPath = $tempTrimmedPath;
                    $escapedInputPath = $escapedTrimmedPath;
                } else {
                    // Ошибка обрезки - используем оригинальное видео
                    Log::warning('Не удалось обрезать видео отдельной командой. Используем исходное видео.');
                    $inputPath = $tempPath;
                    $escapedInputPath = $escapedTempPath;
                    
                    // Логируем ошибку для отладки
                    Log::error('Ошибка обрезки:', [
                        'код_возврата' => $returnVar, 
                        'вывод' => implode("\n", $output)
                    ]);
                }
            } else {
                // Обрезка не требуется
                $inputPath = $tempPath;
                $escapedInputPath = $escapedTempPath;
            }
            
            // **ВТОРОЙ ЭТАП: КОДИРОВАНИЕ И СЖАТИЕ**
            // Формируем команду кодирования без дополнительных параметров обрезки
            $encodingCmd = "ffmpeg -y -i {$escapedInputPath}" .
                         " -c:v libx264" .
                         " -preset medium" .  // Используем 'medium' как надежный вариант
                         " -vf scale={$preset['resolution']}" .
                         " -crf {$preset['crf']}" .
                         " -maxrate {$preset['bitrate']}" .
                         " -bufsize " . (intval(str_replace('k', '', $preset['bitrate'])) * 2) . "k" .
                         " -profile:v baseline" .
                         " -level 3.0" .
                         " -pix_fmt yuv420p" .
                         " -movflags +faststart" .
                         " -c:a aac" .
                         " -b:a {$preset['audio_bitrate']}" .
                         " -ac 2" .
                         " {$escapedOutputPath}";
            
            Log::info('Выполняется кодирование видео:', ['command' => $encodingCmd]);
            
            $output = [];
            $returnVar = null;
            exec($encodingCmd . " 2>&1", $output, $returnVar);
            
            if ($returnVar !== 0) {
                Log::error('Ошибка кодирования:', ['вывод' => implode("\n", $output)]);
                
                // Пробуем запасной вариант с более простыми параметрами
                $fallbackCmd = "ffmpeg -y -i {$escapedInputPath}" .
                             " -c:v libx264" .
                             " -vf scale={$preset['resolution']}" .
                             " -crf " . ($preset['crf'] + 4) .
                             " -preset ultrafast" .
                             " -c:a aac" .
                             " -b:a 64k" .
                             " {$escapedOutputPath}";
                
                Log::info('Пробуем запасной вариант кодирования:', ['command' => $fallbackCmd]);
                
                $output = [];
                $returnVar = null;
                exec($fallbackCmd . " 2>&1", $output, $returnVar);
                
                if ($returnVar !== 0) {
                    // Если и запасной вариант не сработал, возвращаемся к обрезанному или исходному видео
                    Log::error('Запасной вариант кодирования не сработал:', ['вывод' => implode("\n", $output)]);
                    
                    // Последняя попытка - простое копирование
                    if ($shouldTrim && file_exists($tempTrimmedPath)) {
                        // Используем обрезанное видео без перекодирования
                        copy($tempTrimmedPath, $outputPath);
                        Log::info('Скопировано обрезанное видео без перекодирования');
                    } else {
                        // Последняя надежда - просто скопировать исходной файл
                        if ($file instanceof UploadedFile) {
                            $file->move($directory, $fileName);
                        } else {
                            copy($file, $outputPath);
                        }
                        Log::warning('Скопирован исходный файл из-за ошибок кодирования');
                    }
                }
            }
            
            // Очистка временных файлов
            if (file_exists($tempTrimmedPath)) {
                unlink($tempTrimmedPath);
                Log::info('Удален временный файл: ' . $tempTrimmedPath);
            }
            
            // Проверяем результат
            if (file_exists($outputPath)) {
                $outputSize = filesize($outputPath);
                $outputSizeMB = $outputSize / (1024 * 1024);
                
                Log::info('Результат обработки видео:', [
                    'файл' => $fileName,
                    'исходный_размер' => round($fileSizeMB, 2) . ' MB',
                    'итоговый_размер' => round($outputSizeMB, 2) . ' MB',
                    'процент_сжатия' => round(($outputSize / $fileSize) * 100, 1) . '%'
                ]);
                
                // Проверяем длительность итогового видео
                $finalInfo = $this->getVideoInfoDetailed($outputPath);
                $finalDuration = $finalInfo['duration'] ?? 0;
                
                Log::info('Итоговая длительность видео: ' . round($finalDuration, 2) . 'с');
                
                // Если файл слишком большой, применяем экстремальное сжатие
                if ($outputSizeMB > 5) {
                    return $this->applyExtremeCompression($outputPath, $fileName, $directory);
                }
            } else {
                Log::error('Выходной файл не найден после обработки: ' . $outputPath);
                
                // Копируем обрезанное/оригинальное видео как запасной вариант
                if ($shouldTrim && file_exists($tempTrimmedPath)) {
                    copy($tempTrimmedPath, $outputPath);
                } else if ($file instanceof UploadedFile) {
                    $file->move($directory, $fileName);
                } else {
                    copy($file, $outputPath);
                }
            }
            
            return $fileName;
            
        } catch (\Exception $e) {
            Log::error('Ошибка при оптимизации видео: ' . $e->getMessage(), [
                'файл' => $fileName,
                'трассировка' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Применяет экстремальное сжатие для больших видеофайлов
     *
     * @param string $inputFilePath Путь к файлу для сжатия
     * @param string $fileName Имя файла
     * @param string $directory Директория для сохранения
     * @return string Имя сохраненного файла
     */
    private function applyExtremeCompression($inputFilePath, $fileName, $directory)
    {
        try {
            Log::info('Применяем экстремальное сжатие видео', ['file' => $fileName]);
            
            $tempFileName = 'extreme_' . $fileName;
            $tempPath = $directory . '/' . $tempFileName;
            $escapedInputPath = escapeshellarg($inputFilePath);
            $escapedTempPath = escapeshellarg($tempPath);
            
            // Экстремальное сжатие: низкое разрешение, высокий CRF, сильное сжатие аудио
            $extremeCmd = "ffmpeg -i {$escapedInputPath}" .
                         " -vf scale=480:-2" .  // 480p или меньше
                         " -c:v libx264" .
                         " -preset slow" .      // Медленное кодирование для лучшего сжатия
                         " -crf 34" .           // Очень высокое сжатие
                         " -maxrate 300k" .     // Очень низкий битрейт
                         " -bufsize 600k" .
                         " -profile:v baseline" .
                         " -level 3.0" .
                         " -pix_fmt yuv420p" .
                         " -movflags +faststart" .
                         " -c:a aac" .
                         " -b:a 32k" .          // Минимальный битрейт аудио
                         " -ac 1" .             // Моно звук
                         " -y {$escapedTempPath}";
            
            Log::info('Команда экстремального сжатия:', ['command' => $extremeCmd]);
            exec($extremeCmd, $output, $returnVar);
            
            if ($returnVar === 0 && file_exists($tempPath)) {
                $originalSize = filesize($inputFilePath);
                $newSize = filesize($tempPath);
                $compressionRatio = $originalSize > 0 ? ($newSize / $originalSize) * 100 : 100;
                
                Log::info('Результат экстремального сжатия:', [
                    'original_file' => $fileName,
                    'original_size' => round($originalSize / (1024 * 1024), 2) . 'MB',
                    'new_size' => round($newSize / (1024 * 1024), 2) . 'MB',
                    'compression_ratio' => round($compressionRatio, 1) . '%'
                ]);
                
                // Если экстремальное сжатие дало результат, заменяем исходный файл
                if ($newSize < $originalSize * 0.9) { // Как минимум 10% экономии
                    unlink($inputFilePath);
                    rename($tempPath, $inputFilePath);
                    return $fileName;
                } else {
                    // Если сжатие не дало значительного выигрыша, оставляем исходный файл
                    unlink($tempPath);
                    return $fileName;
                }
            } else {
                Log::warning('Экстремальное сжатие не удалось:', [
                    'returnVar' => $returnVar,
                    'output' => implode("\n", $output)
                ]);
                
                // Если экстремальное сжатие не удалось, возвращаем исходный файл
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
                return $fileName;
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при экстремальном сжатии видео: ' . $e->getMessage());
            return $fileName;
        }
    }

    /**
     * Определяет уровень сжатия для видео на основе его размера
     *
     * @param float $fileSizeMB Размер файла в мегабайтах
     * @param string $requestedQuality Запрошенное качество (small, medium, large)
     * @return string Уровень сжатия (ultra_compressed, small, medium, large)
     */
    private function getCompressionLevelForVideo($fileSizeMB, $requestedQuality)
    {
        // Если файл очень большой (>30MB), используем максимальное сжатие
        if ($fileSizeMB > 30) {
            return 'ultra_compressed';
        }
        
        // Если файл большой (>15MB), используем низкое качество
        if ($fileSizeMB > 15) {
            return 'small';
        }
        
        // Для файлов от 5 до 15MB используем качество на основе запроса
        if ($fileSizeMB > 5) {
            // Для large используем medium, для остальных - запрошенное
            if ($requestedQuality === 'large') {
                return 'medium';
            }
            return $requestedQuality;
        }
        
        // Для небольших файлов (<5MB) используем запрошенное качество
        return $requestedQuality;
    }

    /**
     * Получает параметры обрезки видео
     *
     * @param float|null $startTime Время начала
     * @param float|null $endTime Время конца
     * @param float $duration Длительность видео
     * @return string Параметры для FFmpeg
     */
    private function getTrimParameters($startTime, $endTime, $duration)
    {
        $trimParams = '';
        
        // Если указано начальное время
        if (is_numeric($startTime) && $startTime > 0) {
            $trimParams .= " -ss " . floatval($startTime);
        }
        
        // Если указано конечное время
        if (is_numeric($endTime) && $endTime > 0 && $endTime > $startTime) {
            if (is_numeric($startTime) && $startTime > 0) {
                // Если указано время начала, то -t указывает длительность
                $videoDuration = $endTime - $startTime;
                $trimParams .= " -t " . floatval($videoDuration);
            } else {
                // Если время начала не указано, то -to указывает время конца
                $trimParams .= " -to " . floatval($endTime);
            }
        }
        
        // Ограничиваем максимальную длительность видео до 15 секунд
        $maxDuration = 15.0;
        if (empty($trimParams) && $duration > $maxDuration) {
            $trimParams .= " -t " . $maxDuration;
            Log::info('Видео обрезано до максимальной длительности', [
                'max_duration' => $maxDuration,
                'original_duration' => $duration
            ]);
        }
        
        return $trimParams;
    }

    /**
     * Получает детальную информацию о видеофайле
     *
     * @param string $videoPath Путь к видеофайлу
     * @return array Информация о видео
     */
    private function getVideoInfoDetailed($videoPath)
    {
        $info = [
            'duration' => 0,
            'width' => 0,
            'height' => 0,
            'bitrate' => 0,
            'codec' => '',
            'fps' => 0
        ];
        
        try {
            $escapedPath = escapeshellarg($videoPath);
            
            // Детальная информация о файле
            $ffprobeCmd = "ffprobe -v quiet -print_format json -show_format -show_streams {$escapedPath}";
            $jsonOutput = shell_exec($ffprobeCmd);
            
            if ($jsonOutput) {
                $data = json_decode($jsonOutput, true);
                
                if (isset($data['format']['duration'])) {
                    $info['duration'] = (float)$data['format']['duration'];
                }
                
                if (isset($data['format']['bit_rate'])) {
                    $info['bitrate'] = (int)$data['format']['bit_rate'];
                }
                
                // Ищем видеопоток
                if (isset($data['streams']) && is_array($data['streams'])) {
                    foreach ($data['streams'] as $stream) {
                        if (isset($stream['codec_type']) && $stream['codec_type'] === 'video') {
                            $info['width'] = (int)($stream['width'] ?? 0);
                            $info['height'] = (int)($stream['height'] ?? 0);
                            $info['codec'] = $stream['codec_name'] ?? '';
                            
                            // Извлекаем FPS
                            if (isset($stream['r_frame_rate'])) {
                                $fpsStr = $stream['r_frame_rate'];
                                if (preg_match('/(\d+)\/(\d+)/', $fpsStr, $matches)) {
                                    $num = (int)$matches[1];
                                    $den = (int)$matches[2];
                                    if ($den > 0) {
                                        $info['fps'] = $num / $den;
                                    }
                                }
                            }
                            
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при получении информации о видео: ' . $e->getMessage());
        }
        
        return $info;
    }
    
    /**
     * Получает информацию о видео с использованием FFprobe
     *
     * @param string $videoPath Путь к видео
     * @return array Информация о видео (длительность, разрешение и т.д.)
     */
    protected function getVideoInfo($videoPath)
    {
        $info = [
            'duration' => 0,
            'width' => 0,
            'height' => 0,
            'bitrate' => 0
        ];
        
        if (!$this->checkFFmpegInstalled()) {
            return $info;
        }
        
        try {
            $escapedPath = escapeshellarg($videoPath);
            
            // Получаем длительность
            $durationCmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$escapedPath}";
            $output = [];
            exec($durationCmd, $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output[0])) {
                $info['duration'] = (float)$output[0];
            }
            
            // Получаем разрешение
            $resolutionCmd = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of default=noprint_wrappers=1:nokey=0 {$escapedPath}";
            $output = [];
            exec($resolutionCmd, $output, $returnVar);
            
            if ($returnVar === 0) {
                foreach ($output as $line) {
                    if (preg_match('/width=(\d+)/', $line, $matches)) {
                        $info['width'] = (int)$matches[1];
                    } elseif (preg_match('/height=(\d+)/', $line, $matches)) {
                        $info['height'] = (int)$matches[1];
                    }
                }
            }
            
            // Получаем битрейт
            $bitrateCmd = "ffprobe -v error -select_streams v:0 -show_entries stream=bit_rate -of default=noprint_wrappers=1:nokey=1 {$escapedPath}";
            $output = [];
            exec($bitrateCmd, $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output[0])) {
                $info['bitrate'] = (int)$output[0];
            }
            
        } catch (\Exception $e) {
            Log::error('Ошибка при получении информации о видео: ' . $e->getMessage());
        }
        
        return $info;
    }

    /**
     * Проверка наличия FFmpeg
     *
     * @return bool
     */
    protected function checkFFmpegInstalled()
    {
        $output = null;
        $returnVar = null;
        
        @exec('ffmpeg -version', $output, $returnVar);
        
        return $returnVar === 0;
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Exception;
use Symfony\Component\Process\Process;

class MediaOptimizationService
{
    /**
     * Максимальные размеры для оптимизированных изображений
     */
    const DEFAULT_MAX_WIDTH = 1200;
    const DEFAULT_MAX_HEIGHT = 1200;
    
    /**
     * Качество сжатия по умолчанию
     */
    const DEFAULT_WEBP_QUALITY = 80;
    const DEFAULT_JPEG_QUALITY = 85;
    
    /**
     * Обработать медиафайл в зависимости от типа
     * 
     * @param string $filePath Путь к файлу
     * @param string $fileType Тип файла ('image' или 'video')
     * @param array $options Дополнительные опции для обработки
     * @return array Результат обработки (успех, путь к обработанному файлу и т.д.)
     */
    public function processMedia($filePath, $fileType, $options = [])
    {
        try {
            if ($fileType === 'image') {
                return $this->processImage($filePath, $options);
            } elseif ($fileType === 'video') {
                return $this->processVideo($filePath, $options);
            } else {
                throw new Exception('Неподдерживаемый тип файла');
            }
        } catch (Exception $e) {
            Log::error('Ошибка при обработке медиафайла', [
                'error' => $e->getMessage(),
                'filePath' => $filePath,
                'fileType' => $fileType,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file_path' => $filePath
            ];
        }
    }

    /**
     * Обработать изображение (обрезка, масштабирование, поворот, преобразование в WebP)
     * 
     * @param string $filePath Путь к файлу
     * @param array $options Опции обработки
     * @return array Результат обработки
     */
    public function processImage($filePath, $options = [])
    {
        try {
            // Проверяем, существует ли файл
            if (!Storage::disk('public')->exists($filePath)) {
                throw new Exception('Файл изображения не найден: ' . $filePath);
            }

            // Полный путь к файлу
            $fullPath = Storage::disk('public')->path($filePath);
            
            // Логируем параметры
            Log::info('Обработка изображения', [
                'filePath' => $filePath,
                'fullPath' => $fullPath,
                'exists' => file_exists($fullPath),
                'filesize' => file_exists($fullPath) ? filesize($fullPath) : 'файл не существует',
                'options' => $options
            ]);
            
            // Получаем изображение
            $img = Image::make($fullPath);
            
            // Получаем оригинальные размеры
            $originalWidth = $img->width();
            $originalHeight = $img->height();
            
            // Получаем данные о трансформации
            $scale = $options['scale'] ?? 1;
            $translateX = $options['x'] ?? 0;
            $translateY = $options['y'] ?? 0;
            $rotation = $options['rotation'] ?? 0;
            
            // Оптимальный размер для вывода
            $maxWidth = $options['maxWidth'] ?? self::DEFAULT_MAX_WIDTH;
            $maxHeight = $options['maxHeight'] ?? self::DEFAULT_MAX_HEIGHT;
            
            // Качество для WebP (по умолчанию - 80%)
            $webpQuality = $options['webpQuality'] ?? self::DEFAULT_WEBP_QUALITY;
            
            // Принудительно конвертировать в WebP
            $convertToWebP = $options['convertToWebP'] ?? true;
            
            // Создаем директорию для оптимизированных изображений
            $optimizedDir = 'optimized/' . date('Y/m/d');
            
            // Проверяем существование директории и создаем её рекурсивно
            if (!Storage::disk('public')->exists($optimizedDir)) {
                Log::info('Создание директории для оптимизированных изображений', [
                    'dir' => $optimizedDir
                ]);
                Storage::disk('public')->makeDirectory($optimizedDir);
            }
            
            // Проверяем успешность создания директории
            if (!Storage::disk('public')->exists($optimizedDir)) {
                Log::error('Не удалось создать директорию для оптимизированных изображений', [
                    'dir' => $optimizedDir,
                    'storage_path' => Storage::disk('public')->path($optimizedDir)
                ]);
                throw new Exception('Не удалось создать директорию для сохранения изображения: ' . $optimizedDir);
            }
            
            // Применяем трансформации
            // Сначала масштабирование
            if ($scale != 1) {
                $newWidth = round($img->width() * $scale);
                $newHeight = round($img->height() * $scale);
                
                // Применяем масштабирование из центра
                $img->resize($newWidth, $newHeight, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            
            // Поворот
            if ($rotation != 0) {
                $img->rotate($rotation);
            }
            
            // Преобразуем смещение из пикселей веб-интерфейса в пиксели изображения
            $transformedWidth = $img->width();
            $transformedHeight = $img->height();
            
            // Смещение с учетом центра изображения
            $offsetX = ($translateX / $scale);
            $offsetY = ($translateY / $scale);
            
            // Обрезаем изображение под нужный размер с учетом смещения
            $canvas = Image::canvas($maxWidth, $maxHeight);
            
            // Центрирование с учетом смещения
            $x = ($maxWidth - $transformedWidth) / 2 + $offsetX;
            $y = ($maxHeight - $transformedHeight) / 2 + $offsetY;
            
            $canvas->insert($img, 'top-left', intval($x), intval($y));
            
            // Дополнительная оптимизация - если изображение слишком большое, уменьшаем его
            // с сохранением пропорций до максимально допустимых размеров
            if ($canvas->width() > $maxWidth || $canvas->height() > $maxHeight) {
                $canvas->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize(); // Не увеличиваем маленькие изображения
                });
                
                Log::info('Изображение уменьшено до оптимального размера', [
                    'newWidth' => $canvas->width(),
                    'newHeight' => $canvas->height(),
                    'originalWidth' => $originalWidth,
                    'originalHeight' => $originalHeight
                ]);
            }
            
            // Создаем новое имя файла
            $optimizedFileName = Str::random(8) . '_optimized';
            
            // Логируем информацию о сжатии
            $originalSize = filesize($fullPath);
            
            // Если конвертировать в WebP и это поддерживается в PHP
            if ($convertToWebP && function_exists('imagewebp')) {
                $optimizedPath = $optimizedDir . '/' . $optimizedFileName . '.webp';
                
                // Создаем директорию, если её нет (с учетом полного пути)
                $outputDir = dirname(Storage::disk('public')->path($optimizedPath));
                if (!is_dir($outputDir)) {
                    Log::info('Создание директории для оптимизированных WebP изображений', [
                        'dir' => $outputDir
                    ]);
                    
                    // Создаем директорию с указанием прав доступа 0755
                    if (!mkdir($outputDir, 0755, true)) {
                        Log::error('Не удалось создать директорию', [
                            'dir' => $outputDir,
                            'error' => error_get_last()
                        ]);
                        throw new Exception('Не удалось создать директорию для сохранения изображения: ' . $outputDir);
                    }
                }
                
                // Проверяем, что директория существует и доступна для записи
                if (!is_dir($outputDir) || !is_writable($outputDir)) {
                    Log::error('Директория недоступна для записи', [
                        'dir' => $outputDir,
                        'is_dir' => is_dir($outputDir),
                        'is_writable' => is_writable($outputDir)
                    ]);
                    throw new Exception('Директория недоступна для записи: ' . $outputDir);
                }
                
                $outputFilePath = Storage::disk('public')->path($optimizedPath);
                
                // Сохраняем изображение в WebP
                Log::info('Сохранение изображения в WebP', [
                    'filePath' => $optimizedPath,
                    'fullPath' => $outputFilePath
                ]);
                
                $canvas->encode('webp', $webpQuality)->save($outputFilePath);
                
                // Проверяем, что файл успешно сохранен
                if (!file_exists($outputFilePath)) {
                    Log::error('Файл WebP не был создан после сохранения', [
                        'path' => $outputFilePath
                    ]);
                    throw new Exception('Не удалось сохранить изображение в формате WebP');
                }
                
                $newSize = filesize($outputFilePath);
                $compressionRatio = $originalSize > 0 ? round((1 - $newSize / $originalSize) * 100, 2) : 0;
                
                Log::info('Изображение сконвертировано в WebP и сжато', [
                    'originalFormat' => pathinfo($filePath, PATHINFO_EXTENSION),
                    'newFormat' => 'webp',
                    'originalSize' => $originalSize,
                    'newSize' => $newSize,
                    'compressionRatio' => $compressionRatio . '%',
                    'quality' => $webpQuality
                ]);
            } else {
                // Если WebP не поддерживается, используем JPEG
                $optimizedPath = $optimizedDir . '/' . $optimizedFileName . '.jpg';
                
                // Создаем директорию, если её нет (с учетом полного пути)
                $outputDir = dirname(Storage::disk('public')->path($optimizedPath));
                if (!is_dir($outputDir)) {
                    Log::info('Создание директории для оптимизированных JPEG изображений', [
                        'dir' => $outputDir
                    ]);
                    
                    // Создаем директорию с указанием прав доступа 0755
                    if (!mkdir($outputDir, 0755, true)) {
                        Log::error('Не удалось создать директорию', [
                            'dir' => $outputDir,
                            'error' => error_get_last()
                        ]);
                        throw new Exception('Не удалось создать директорию для сохранения изображения: ' . $outputDir);
                    }
                }
                
                // Проверяем, что директория существует и доступна для записи
                if (!is_dir($outputDir) || !is_writable($outputDir)) {
                    Log::error('Директория недоступна для записи', [
                        'dir' => $outputDir,
                        'is_dir' => is_dir($outputDir),
                        'is_writable' => is_writable($outputDir)
                    ]);
                    throw new Exception('Директория недоступна для записи: ' . $outputDir);
                }
                
                $outputFilePath = Storage::disk('public')->path($optimizedPath);
                
                // Сохраняем изображение в JPEG
                Log::info('Сохранение изображения в JPEG', [
                    'filePath' => $optimizedPath,
                    'fullPath' => $outputFilePath
                ]);
                
                $canvas->encode('jpg', self::DEFAULT_JPEG_QUALITY)->save($outputFilePath);
                
                // Проверяем, что файл успешно сохранен
                if (!file_exists($outputFilePath)) {
                    Log::error('Файл JPEG не был создан после сохранения', [
                        'path' => $outputFilePath
                    ]);
                    throw new Exception('Не удалось сохранить изображение в формате JPEG');
                }
                
                $newSize = filesize($outputFilePath);
                $compressionRatio = $originalSize > 0 ? round((1 - $newSize / $originalSize) * 100, 2) : 0;
                
                Log::info('Изображение сохранено в JPEG и сжато', [
                    'originalFormat' => pathinfo($filePath, PATHINFO_EXTENSION),
                    'newFormat' => 'jpg',
                    'originalSize' => $originalSize,
                    'newSize' => $newSize,
                    'compressionRatio' => $compressionRatio . '%',
                    'quality' => self::DEFAULT_JPEG_QUALITY
                ]);
            }
            
            return [
                'success' => true,
                'file_path' => $optimizedPath,
                'full_path' => Storage::disk('public')->url($optimizedPath),
                'width' => $canvas->width(),
                'height' => $canvas->height(),
                'original_size' => $originalSize ?? 0,
                'new_size' => $newSize ?? 0,
                'compression_ratio' => $compressionRatio ?? 0
            ];
        } catch (Exception $e) {
            Log::error('Ошибка при обработке изображения', [
                'error' => $e->getMessage(),
                'file' => $filePath,
                'trace' => $e->getTraceAsString(),
                'file_line' => $e->getFile() . ':' . $e->getLine()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file_path' => $filePath
            ];
        }
    }

    /**
     * Проверить наличие FFmpeg в системе и вернуть путь к исполняемому файлу
     * 
     * @return string|bool Путь к FFmpeg или false, если не установлен
     */
    public function getFFmpegPath()
    {
        // Сначала проверяем локальную установку в папке storage
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $localPath = $isWindows 
            ? storage_path('ffmpeg/bin/ffmpeg.exe')
            : storage_path('ffmpeg/bin/ffmpeg');
        
        if (file_exists($localPath)) {
            return $localPath;
        }
        
        // Если локальной установки нет, проверяем глобальную
        try {
            $command = $isWindows ? 'where ffmpeg' : 'which ffmpeg';
            $process = Process::fromShellCommandline($command);
            $process->run();
            
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                if (file_exists($output)) {
                    return $output;
                }
            }
        } catch (Exception $e) {
            Log::warning('Ошибка при поиске FFmpeg: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Проверить наличие FFprobe в системе и вернуть путь к исполняемому файлу
     * 
     * @return string|bool Путь к FFprobe или false, если не установлен
     */
    public function getFFprobePath()
    {
        // Сначала проверяем локальную установку в папке storage
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $localPath = $isWindows 
            ? storage_path('ffmpeg/bin/ffprobe.exe')
            : storage_path('ffmpeg/bin/ffprobe');
        
        if (file_exists($localPath)) {
            return $localPath;
        }
        
        // Если локальной установки нет, проверяем глобальную
        try {
            $command = $isWindows ? 'where ffprobe' : 'which ffprobe';
            $process = Process::fromShellCommandline($command);
            $process->run();
            
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                if (file_exists($output)) {
                    return $output;
                }
            }
        } catch (Exception $e) {
            Log::warning('Ошибка при поиске FFprobe: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Проверить наличие FFmpeg в системе
     * 
     * @return bool
     */
    public function checkFFmpeg()
    {
        return (bool)$this->getFFmpegPath();
    }

    /**
     * Обработать видео (обрезка по длительности и сжатие)
     * 
     * @param string $filePath Путь к файлу
     * @param array $options Опции обработки
     * @return array Результат обработки
     */
    public function processVideo($filePath, $options = [])
    {
        try {
            // Проверяем, существует ли файл
            if (!Storage::disk('public')->exists($filePath)) {
                throw new Exception('Файл видео не найден: ' . $filePath);
            }

            // Получаем путь к FFmpeg
            $ffmpegPath = $this->getFFmpegPath();
            
            // Проверяем наличие FFmpeg в системе
            if (!$ffmpegPath) {
                throw new Exception('FFmpeg не установлен в системе. Установите его вручную или выполните команду: php artisan install:ffmpeg');
            }

            // Полный путь к исходному файлу
            $inputPath = Storage::disk('public')->path($filePath);
            
            // Определяем временные отрезки для обрезки
            $startTime = isset($options['start_time']) ? floatval($options['start_time']) : 0;
            $endTime = isset($options['end_time']) ? floatval($options['end_time']) : 15;
            $duration = $endTime - $startTime;
            
            // Максимальная длительность 15 секунд
            $duration = min($duration, 15);
            
            // Создаем новое имя файла для обработанного видео
            $processedDir = 'processed_videos/' . date('Y/m/d');
            $processedPath = $processedDir . '/' . Str::random(8) . '_processed.mp4';
            
            // Создаем директорию, если её нет
            $outputDir = dirname(Storage::disk('public')->path($processedPath));
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            // Полный путь для выходного файла
            $outputPath = Storage::disk('public')->path($processedPath);
            
            // Логируем оригинальный размер файла
            $originalSize = filesize($inputPath);
            
            // Получаем информацию о видео для оптимального сжатия
            $videoInfo = $this->getVideoInfo($filePath);
            
            // Настройки качества видео
            $videoWidth = $options['width'] ?? ($videoInfo['width'] ?? 720);
            
            // Если видео слишком большое, уменьшаем разрешение
            if ($videoWidth > 1280) {
                $videoWidth = 1280; // Максимальная ширина для мобильного оптимизированного видео
            }
            
            $videoHeight = $options['height'] ?? 0; // 0 - автоматический расчет
            
            // Настраиваем битрейт в зависимости от размера видео
            $defaultBitrate = $this->calculateOptimalBitrate($videoWidth, $videoHeight ?: ($videoInfo['height'] ?? 0));
            $videoBitrate = $options['bitrate'] ?? $defaultBitrate;
            
            // Формируем команду для FFmpeg с оптимизацией
            $ffmpegCmd = escapeshellcmd($ffmpegPath);
            $inputPathEscaped = escapeshellarg($inputPath);
            $outputPathEscaped = escapeshellarg($outputPath);
            
            $command = "{$ffmpegCmd} -y -i {$inputPathEscaped} -ss {$startTime} -t {$duration} " .
                      "-c:v libx264 -preset medium -b:v {$videoBitrate} -pix_fmt yuv420p ";
            
            // Добавляем параметры размера, если указаны
            if ($videoWidth > 0) {
                if ($videoHeight > 0) {
                    $command .= "-vf \"scale={$videoWidth}:{$videoHeight}\" ";
                } else {
                    $command .= "-vf \"scale={$videoWidth}:-2\" ";
                }
            }
            
            // Добавляем параметры для аудио - сжимаем и оптимизируем
            $command .= "-c:a aac -b:a 96k -ar 44100 -strict experimental {$outputPathEscaped} 2>&1";
            
            // Логируем команду для отладки
            Log::info('FFmpeg команда: ' . $command);
            
            // Выполняем команду
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);
            
            // Проверяем результат выполнения
            if ($returnVar !== 0) {
                throw new Exception('Ошибка при выполнении FFmpeg: ' . implode("\n", $output));
            }
            
            // Проверяем, создался ли файл
            if (!file_exists($outputPath)) {
                throw new Exception('Файл не был создан после обработки');
            }
            
            // Логируем результаты сжатия
            $newSize = filesize($outputPath);
            $compressionRatio = $originalSize > 0 ? round((1 - $newSize / $originalSize) * 100, 2) : 0;
            
            Log::info('Видео успешно сжато', [
                'originalSize' => $originalSize,
                'newSize' => $newSize,
                'compressionRatio' => $compressionRatio . '%',
                'bitrate' => $videoBitrate,
                'resolution' => $videoWidth . 'x' . ($videoHeight ?: 'auto')
            ]);
            
            return [
                'success' => true,
                'file_path' => $processedPath,
                'full_path' => Storage::disk('public')->url($processedPath),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $duration,
                'original_size' => $originalSize,
                'new_size' => $newSize,
                'compression_ratio' => $compressionRatio,
                'width' => $videoWidth,
                'height' => $videoHeight ?: ($videoInfo['height'] ?? 'auto')
            ];
        } catch (Exception $e) {
            Log::error('Ошибка при обработке видео', [
                'error' => $e->getMessage(),
                'file' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file_path' => $filePath
            ];
        }
    }

    /**
     * Рассчитать оптимальный битрейт для видео в зависимости от разрешения
     * 
     * @param int $width Ширина видео
     * @param int $height Высота видео
     * @return string Битрейт в формате '1000k'
     */
    private function calculateOptimalBitrate($width, $height)
    {
        // Расчет битрейта по формуле, оптимизированной для мобильных устройств
        $pixelCount = $width * ($height ?: $width / 1.78); // Если высота не указана, предполагаем пропорцию 16:9
        
        if ($pixelCount <= 0) {
            return '800k'; // Значение по умолчанию
        }
        
        // Формула для расчета битрейта (кбит/с) в зависимости от количества пикселей
        // Для мобильных устройств используем более агрессивное сжатие
        if ($pixelCount <= 409600) { // 640x640 и меньше
            $bitrate = round($pixelCount / 900);
        } elseif ($pixelCount <= 921600) { // 1280x720 и меньше
            $bitrate = round($pixelCount / 1200);
        } else { // Выше 720p
            $bitrate = round($pixelCount / 1500);
        }
        
        // Ограничения битрейта
        $bitrate = max(500, min(2500, $bitrate));
        
        return $bitrate . 'k';
    }

    /**
     * Получить информацию о видео для оптимального сжатия
     * 
     * @param string $filePath Путь к файлу
     * @return array Информация о видео
     */
    private function getVideoInfo($filePath)
    {
        try {
            $inputPath = Storage::disk('public')->path($filePath);
            $ffprobePath = $this->getFFprobePath();
            
            if (!$ffprobePath) {
                return ['width' => 720, 'height' => 0]; // Значения по умолчанию
            }
            
            // Получаем информацию о ширине и высоте видео
            $ffprobeCmd = escapeshellcmd($ffprobePath);
            $inputPathEscaped = escapeshellarg($inputPath);
            
            $command = "{$ffprobeCmd} -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 {$inputPathEscaped} 2>&1";
            
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0 || empty($output)) {
                Log::warning('Не удалось получить информацию о видео: ' . implode("\n", $output));
                return ['width' => 720, 'height' => 0]; // Значения по умолчанию
            }
            
            // Парсим вывод (например, "1920x1080")
            $dimensions = explode('x', $output[0]);
            
            return [
                'width' => (int)($dimensions[0] ?? 720),
                'height' => (int)($dimensions[1] ?? 0)
            ];
        } catch (Exception $e) {
            Log::error('Ошибка при получении информации о видео', [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);
            
            return ['width' => 720, 'height' => 0]; // Значения по умолчанию
        }
    }

    /**
     * Создать миниатюру из видео
     * 
     * @param string $videoPath Путь к видео
     * @param float $timeOffset Время в секундах для создания миниатюры
     * @return string|null Путь к миниатюре или null в случае ошибки
     */
    public function createVideoThumbnail($videoPath, $timeOffset = 0)
    {
        try {
            // Проверяем, существует ли файл
            if (!Storage::disk('public')->exists($videoPath)) {
                throw new Exception('Файл видео не найден: ' . $videoPath);
            }
            
            // Получаем путь к FFmpeg
            $ffmpegPath = $this->getFFmpegPath();
            
            // Проверяем наличие FFmpeg
            if (!$ffmpegPath) {
                throw new Exception('FFmpeg не установлен в системе');
            }

            $inputPath = Storage::disk('public')->path($videoPath);
            
            // Создаем имя для миниатюры, используем WebP для лучшего сжатия
            $thumbnailDir = 'thumbnails/' . date('Y/m/d');
            $thumbnailPath = $thumbnailDir . '/' . Str::random(8) . '_thumb.webp';
            
            // Создаем директорию, если её нет
            $outputDir = dirname(Storage::disk('public')->path($thumbnailPath));
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            $outputPath = Storage::disk('public')->path($thumbnailPath);
            
            // Формируем команду для создания миниатюры в WebP формате
            $ffmpegCmd = escapeshellcmd($ffmpegPath);
            $inputPathEscaped = escapeshellarg($inputPath);
            $outputPathEscaped = escapeshellarg($outputPath);
            
            $command = "{$ffmpegCmd} -y -i {$inputPathEscaped} -ss {$timeOffset} -vframes 1 " .
                      "-vf \"scale=640:-2\" -c:v libwebp -lossless 0 -compression_level 6 -q:v 75 {$outputPathEscaped} 2>&1";
            
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);
            
            // Если не удалось создать WebP, пробуем JPEG
            if ($returnVar !== 0 || !file_exists($outputPath)) {
                Log::warning('Не удалось создать WebP миниатюру, пробуем JPEG', [
                    'output' => implode("\n", $output)
                ]);
                
                $thumbnailPath = $thumbnailDir . '/' . Str::random(8) . '_thumb.jpg';
                $outputPath = Storage::disk('public')->path($thumbnailPath);
                
                $command = "{$ffmpegCmd} -y -i {$inputPathEscaped} -ss {$timeOffset} -vframes 1 " .
                          "-vf \"scale=640:-2\" -q:v 2 {$outputPathEscaped} 2>&1";
                
                $output = [];
                $returnVar = 0;
                exec($command, $output, $returnVar);
                
                if ($returnVar !== 0 || !file_exists($outputPath)) {
                    throw new Exception('Ошибка при создании миниатюры: ' . implode("\n", $output));
                }
            }
            
            return $thumbnailPath;
        } catch (Exception $e) {
            Log::error('Ошибка при создании миниатюры', [
                'error' => $e->getMessage(),
                'video' => $videoPath,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Получить длительность видео в секундах
     * 
     * @param string $videoPath Путь к видео
     * @return float|null Длительность видео в секундах или null в случае ошибки
     */
    public function getVideoDuration($videoPath)
    {
        try {
            // Проверяем, существует ли файл
            if (!Storage::disk('public')->exists($videoPath)) {
                throw new Exception('Файл видео не найден: ' . $videoPath);
            }
            
            // Получаем путь к FFprobe
            $ffprobePath = $this->getFFprobePath();
            
            // Проверяем наличие FFprobe
            if (!$ffprobePath) {
                throw new Exception('FFprobe не установлен в системе');
            }

            $inputPath = Storage::disk('public')->path($videoPath);
            
            // Формируем команду для получения длительности
            $ffprobeCmd = escapeshellcmd($ffprobePath);
            $inputPathEscaped = escapeshellarg($inputPath);
            
            $command = "{$ffprobeCmd} -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$inputPathEscaped} 2>&1";
            
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0 || empty($output)) {
                throw new Exception('Ошибка при получении длительности видео');
            }
            
            return floatval($output[0]);
        } catch (Exception $e) {
            Log::error('Ошибка при получении длительности видео', [
                'error' => $e->getMessage(),
                'video' => $videoPath,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
}
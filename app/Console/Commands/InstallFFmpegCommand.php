<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;
use Exception;
use ZipArchive;
use Illuminate\Support\Facades\Storage;

class InstallFFmpegCommand extends Command
{
    /**
     * Название команды консоли
     *
     * @var string
     */
    protected $signature = 'install:ffmpeg {--force : Принудительная переустановка FFmpeg}';

    /**
     * Описание команды консоли
     *
     * @var string
     */
    protected $description = 'Установка FFmpeg для обработки видео';

    /**
     * Флаг, указывающий на то, работает ли команда в Windows
     * 
     * @var bool
     */
    protected $isWindows;

    /**
     * Выполнение консольной команды
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Проверка установки FFmpeg...');
        
        // Определяем ОС
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        try {
            // Проверяем, установлен ли уже FFmpeg
            if ($this->isFFmpegInstalled() && !$this->option('force')) {
                $this->info('FFmpeg уже установлен. Используйте --force для переустановки.');
                return 0;
            }
            
            // Устанавливаем FFmpeg
            if ($this->isWindows) {
                $this->installFFmpegOnWindows();
            } else {
                $this->installFFmpegOnLinux();
            }
            
            // Проверяем успешность установки
            if ($this->isFFmpegInstalled()) {
                $this->info('FFmpeg успешно установлен!');
                $this->checkFFmpegVersion();
                return 0;
            } else {
                $this->error('Не удалось установить FFmpeg. Попробуйте установить его вручную.');
                return 1;
            }
            
        } catch (Exception $e) {
            $this->error('Произошла ошибка: ' . $e->getMessage());
            Log::error('Ошибка при установке FFmpeg: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Проверяет, установлен ли FFmpeg
     * 
     * @return bool
     */
    protected function isFFmpegInstalled()
    {
        try {
            // На Windows нужно проверить наличие файлов в папке bin
            if ($this->isWindows) {
                $binPath = storage_path('ffmpeg/bin/ffmpeg.exe');
                if (file_exists($binPath)) {
                    $process = new Process([$binPath, '-version']);
                    $process->run();
                    if ($process->isSuccessful()) {
                        return true;
                    }
                }

                // Также проверим глобальную установку
                $process = Process::fromShellCommandline('where ffmpeg');
                $process->run();
                return $process->isSuccessful();
            } else {
                // Для Linux проверяем через which
                $process = Process::fromShellCommandline('which ffmpeg');
                $process->run();
                return $process->isSuccessful();
            }
        } catch (Exception $e) {
            Log::warning('Ошибка при проверке наличия FFmpeg: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Установка FFmpeg в Windows
     * 
     * @throws Exception
     */
    protected function installFFmpegOnWindows()
    {
        $this->info('Установка FFmpeg для Windows...');
        
        // Создаем директорию для FFmpeg в storage
        $ffmpegDir = storage_path('ffmpeg');
        if (!is_dir($ffmpegDir)) {
            mkdir($ffmpegDir, 0755, true);
        }
        
        // Устанавливаем путь для временного ZIP файла
        $zipFile = storage_path('ffmpeg/ffmpeg.zip');
        
        // URL для скачивания (essentials_build - меньший размер, содержит только необходимые файлы)
        $ffmpegUrl = 'https://github.com/GyanD/codexffmpeg/releases/download/6.0/ffmpeg-6.0-essentials_build.zip';
        
        // Скачиваем архив FFmpeg
        $this->info('Скачивание архива FFmpeg...');
        try {
            if (!file_exists($zipFile) || $this->option('force')) {
                // Используем curl для загрузки
                $this->info('Загрузка из: ' . $ffmpegUrl);
                $ch = curl_init($ffmpegUrl);
                $fp = fopen($zipFile, 'w');
                
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $success = curl_exec($ch);
                if (!$success) {
                    throw new Exception('Ошибка при скачивании FFmpeg: ' . curl_error($ch));
                }
                
                curl_close($ch);
                fclose($fp);
                
                $this->info('Архив успешно скачан: ' . number_format(filesize($zipFile) / 1024 / 1024, 2) . ' МБ');
            } else {
                $this->info('Архив уже скачан. Используем его.');
            }
        } catch (Exception $e) {
            $this->error('Ошибка при загрузке FFmpeg: ' . $e->getMessage());
            throw new Exception('Не удалось скачать архив FFmpeg. Возможно, проблемы с сетью.');
        }
        
        // Распаковываем архив
        $this->info('Распаковка архива...');
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === true) {
            $extractDir = storage_path('ffmpeg-extract');
            
            // Удаляем старую распакованную папку, если она существует
            if (is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }
            
            mkdir($extractDir, 0755, true);
            $zip->extractTo($extractDir);
            $zip->close();
            
            // Найти папку bin в распакованном архиве
            $binFolder = null;
            $ffmpegExe = null;
            
            // Ищем ffmpeg.exe рекурсивно во всех подпапках
            $this->info('Поиск исполняемых файлов FFmpeg...');
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getFilename()) === 'ffmpeg.exe') {
                    $ffmpegExe = $file->getPathname();
                    $binFolder = $file->getPath();
                    $this->info('Найден ffmpeg.exe: ' . $ffmpegExe);
                    break;
                }
            }
            
            if (!$binFolder) {
                throw new Exception('Не удалось найти папку с исполняемыми файлами FFmpeg в архиве');
            }
            
            // Создаем папку bin в папке ffmpeg, если её нет
            $targetBinFolder = $ffmpegDir . '/bin';
            if (!is_dir($targetBinFolder)) {
                mkdir($targetBinFolder, 0755, true);
            }
            
            // Копируем все файлы из найденной папки bin в папку ffmpeg/bin
            $this->info('Копирование файлов FFmpeg...');
            foreach (glob($binFolder . '/*.exe') as $file) {
                $filename = basename($file);
                copy($file, $targetBinFolder . '/' . $filename);
                $this->info('Скопирован файл: ' . $filename);
            }
            
            foreach (glob($binFolder . '/*.dll') as $file) {
                $filename = basename($file);
                copy($file, $targetBinFolder . '/' . $filename);
                $this->info('Скопирован файл: ' . $filename);
            }
            
            // Удаляем временную папку с распакованными файлами
            $this->info('Очистка временных файлов...');
            $this->deleteDirectory($extractDir);
            
            // Создаем batch-файл для добавления в PATH
            $this->createSetupBatch($targetBinFolder);
            
            $this->info('FFmpeg установлен в: ' . $targetBinFolder);
            $this->info('Пожалуйста, запустите setup-ffmpeg.bat от имени администратора, чтобы добавить FFmpeg в PATH.');
        } else {
            throw new Exception('Не удалось распаковать архив FFmpeg');
        }
    }
    
    /**
     * Создает batch-файл для добавления FFmpeg в PATH
     * 
     * @param string $binPath путь к папке bin с исполняемыми файлами FFmpeg
     */
    protected function createSetupBatch($binPath)
    {
        $batchFile = storage_path('ffmpeg/setup-ffmpeg.bat');
        $binPath = str_replace('/', '\\', $binPath);
        
        $batchContent = '@echo off' . PHP_EOL;
        $batchContent .= 'echo Добавление FFmpeg в системную переменную PATH' . PHP_EOL;
        $batchContent .= 'echo =============================================' . PHP_EOL;
        $batchContent .= 'echo.' . PHP_EOL;
        $batchContent .= 'echo Проверка привилегий администратора...' . PHP_EOL;
        $batchContent .= 'net session >nul 2>&1' . PHP_EOL;
        $batchContent .= 'if %errorLevel% neq 0 (' . PHP_EOL;
        $batchContent .= '    echo Ошибка: Запустите этот скрипт от имени администратора!' . PHP_EOL;
        $batchContent .= '    echo.' . PHP_EOL;
        $batchContent .= '    pause' . PHP_EOL;
        $batchContent .= '    exit /b 1' . PHP_EOL;
        $batchContent .= ')' . PHP_EOL;
        $batchContent .= 'echo.' . PHP_EOL;
        $batchContent .= 'echo Добавление "%binPath%" в PATH...' . PHP_EOL;
        $batchContent .= 'setx PATH "%PATH%;' . $binPath . '" /M' . PHP_EOL;
        $batchContent .= 'if %errorLevel% neq 0 (' . PHP_EOL;
        $batchContent .= '    echo Ошибка при добавлении пути в переменную PATH!' . PHP_EOL;
        $batchContent .= ') else (' . PHP_EOL;
        $batchContent .= '    echo FFmpeg успешно добавлен в PATH.' . PHP_EOL;
        $batchContent .= '    echo Перезапустите командную строку или приложение, чтобы изменения вступили в силу.' . PHP_EOL;
        $batchContent .= ')' . PHP_EOL;
        $batchContent .= 'echo.' . PHP_EOL;
        $batchContent .= 'pause' . PHP_EOL;
        
        file_put_contents($batchFile, $batchContent);
        $this->info('Создан файл для установки FFmpeg в PATH: ' . $batchFile);
    }
    
    /**
     * Установка FFmpeg в Linux
     * 
     * @throws Exception
     */
    protected function installFFmpegOnLinux()
    {
        $this->info('Установка FFmpeg для Linux...');
        
        // Определяем пакетный менеджер
        if ($this->checkCommand('apt')) {
            $this->info('Используем apt для установки...');
            $process = Process::fromShellCommandline('sudo apt update && sudo apt install -y ffmpeg');
        } elseif ($this->checkCommand('yum')) {
            $this->info('Используем yum для установки...');
            $process = Process::fromShellCommandline('sudo yum install -y epel-release && sudo yum install -y ffmpeg');
        } elseif ($this->checkCommand('dnf')) {
            $this->info('Используем dnf для установки...');
            $process = Process::fromShellCommandline('sudo dnf install -y ffmpeg');
        } else {
            throw new Exception('Не удалось определить пакетный менеджер для установки FFmpeg');
        }
        
        $process->setTimeout(300); // 5 минут на установку
        $process->run(function ($type, $buffer) {
            if ($type === Process::ERR) {
                $this->warn($buffer);
            } else {
                $this->line($buffer);
            }
        });
        
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
    
    /**
     * Проверяет, доступна ли команда в системе
     * 
     * @param string $command
     * @return bool
     */
    protected function checkCommand($command)
    {
        try {
            if ($this->isWindows) {
                $process = Process::fromShellCommandline("where {$command}");
            } else {
                $process = Process::fromShellCommandline("which {$command}");
            }
            $process->run();
            return $process->isSuccessful();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Рекурсивно удаляет директорию и все её содержимое
     * 
     * @param string $dir
     * @return bool
     */
    protected function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Проверка и вывод версии FFmpeg
     */
    protected function checkFFmpegVersion()
    {
        if ($this->isWindows) {
            // На Windows проверяем локальную установку
            $ffmpegPath = storage_path('ffmpeg/bin/ffmpeg.exe');
            if (file_exists($ffmpegPath)) {
                $process = new Process([$ffmpegPath, '-version']);
            } else {
                $process = Process::fromShellCommandline('ffmpeg -version');
            }
        } else {
            $process = Process::fromShellCommandline('ffmpeg -version');
        }
        
        $process->run();
        
        if ($process->isSuccessful()) {
            $output = $process->getOutput();
            $version = explode("\n", $output)[0];
            $this->info('Установленная версия FFmpeg: ' . $version);
        } else {
            $this->warn('Не удалось получить версию FFmpeg');
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;
use Exception;

class InstallMediaOptimizationDependencies extends Command
{
    /**
     * Название команды консоли
     *
     * @var string
     */
    protected $signature = 'install:media-optimization {--skip-ffmpeg : Пропустить установку FFmpeg} {--force : Принудительная установка}';

    /**
     * Описание команды консоли
     *
     * @var string
     */
    protected $description = 'Установка зависимостей для оптимизации медиафайлов (FFmpeg, Intervention Image, и др.)';

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
        $this->info('Установка зависимостей для оптимизации медиафайлов...');
        
        // Определяем ОС
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        try {
            // Устанавливаем PHP расширения
            $this->installPhpExtensions();
            
            // Устанавливаем Composer пакеты
            $this->installComposerPackages();
            
            // Устанавливаем FFmpeg, если не указано --skip-ffmpeg
            if (!$this->option('skip-ffmpeg')) {
                $this->installFFmpeg();
            } else {
                $this->info('Установка FFmpeg пропущена по запросу');
            }
            
            // Создаем необходимые директории
            $this->createDirectories();
            
            $this->info('Установка зависимостей завершена успешно!');
            return 0;
            
        } catch (Exception $e) {
            $this->error('Произошла ошибка: ' . $e->getMessage());
            Log::error('Ошибка при установке зависимостей: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Установка необходимых PHP расширений
     */
    protected function installPhpExtensions()
    {
        $this->info('Проверка и установка PHP расширений...');
        
        $requiredExtensions = ['gd', 'exif', 'fileinfo'];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            }
        }
        
        if (empty($missingExtensions)) {
            $this->info('Все необходимые PHP расширения уже установлены.');
            return;
        }
        
        $this->warn('Следующие PHP расширения не установлены: ' . implode(', ', $missingExtensions));
        
        if ($this->isWindows) {
            $this->warn('Для Windows: включите эти расширения в php.ini вручную и перезапустите сервер.');
        } else {
            $this->info('Установка отсутствующих PHP расширений...');
            
            foreach ($missingExtensions as $extension) {
                $this->info("Установка расширения: $extension");
                
                if ($this->checkCommand('apt')) {
                    $process = Process::fromShellCommandline("sudo apt update && sudo apt install -y php-$extension");
                } elseif ($this->checkCommand('yum')) {
                    $process = Process::fromShellCommandline("sudo yum install -y php-$extension");
                } elseif ($this->checkCommand('dnf')) {
                    $process = Process::fromShellCommandline("sudo dnf install -y php-$extension");
                } else {
                    $this->warn("Не удалось автоматически установить расширение $extension. Установите его вручную.");
                    continue;
                }
                
                $process->setTimeout(180);
                $process->run(function ($type, $buffer) {
                    if ($type === Process::ERR) {
                        $this->warn($buffer);
                    } else {
                        $this->line($buffer);
                    }
                });
                
                if ($process->isSuccessful()) {
                    $this->info("Расширение $extension успешно установлено!");
                } else {
                    $this->error("Не удалось установить расширение $extension. Установите его вручную.");
                }
            }
            
            $this->warn('Для корректной работы перезапустите PHP сервер после установки расширений.');
        }
    }
    
    /**
     * Установка Composer пакетов
     */
    protected function installComposerPackages()
    {
        $this->info('Установка Composer пакетов...');
        
        $packages = [
            'intervention/image',
            'guzzlehttp/guzzle'
        ];
        
        $command = 'composer require ' . implode(' ', $packages);
        
        $process = Process::fromShellCommandline($command, base_path());
        $process->setTimeout(300); // 5 минут на установку
        $process->run(function ($type, $buffer) {
            if ($type === Process::ERR) {
                $this->warn($buffer);
            } else {
                $this->line($buffer);
            }
        });
        
        if ($process->isSuccessful()) {
            $this->info('Composer пакеты успешно установлены!');
        } else {
            throw new ProcessFailedException($process);
        }
    }
    
    /**
     * Установка FFmpeg
     */
    protected function installFFmpeg()
    {
        $this->info('Установка FFmpeg...');
        
        $command = $this->findArtisan() . ' install:ffmpeg';
        if ($this->option('force')) {
            $command .= ' --force';
        }
        
        $process = Process::fromShellCommandline($command, base_path());
        $process->setTimeout(500); // Увеличиваем таймаут до 8+ минут
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
     * Создание необходимых директорий
     */
    protected function createDirectories()
    {
        $this->info('Создание директорий для медиафайлов...');
        
        $directories = [
            'storage/app/public/optimized',
            'storage/app/public/processed_videos',
            'storage/app/public/thumbnails',
            'storage/app/public/temp'
        ];
        
        foreach ($directories as $dir) {
            $path = base_path($dir);
            if (!is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    $this->info("Создана директория: $dir");
                } else {
                    $this->warn("Не удалось создать директорию: $dir");
                }
            } else {
                $this->info("Директория уже существует: $dir");
            }
        }
        
        // Создаем символьную ссылку, если её нет
        if (!file_exists(public_path('storage'))) {
            $this->info('Создание символьной ссылки для storage...');
            
            $linkCommand = $this->findArtisan() . ' storage:link';
            $process = Process::fromShellCommandline($linkCommand, base_path());
            $process->run();
            
            if ($process->isSuccessful()) {
                $this->info('Символьная ссылка создана успешно!');
            } else {
                $this->warn('Не удалось создать символьную ссылку. Выполните команду "php artisan storage:link" вручную.');
            }
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
            $process = Process::fromShellCommandline($this->isWindows ? "where $command" : "which $command");
            $process->run();
            return $process->isSuccessful();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Находит путь к исполняемому файлу Artisan
     * 
     * @return string
     */
    protected function findArtisan()
    {
        return $this->isWindows ? 'php artisan' : './artisan';
    }
}
      
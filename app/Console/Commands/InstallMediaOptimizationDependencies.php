<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallMediaOptimizationDependencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:install-dependencies {--extended : Установить расширенные зависимости для дополнительной оптимизации}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Установка зависимостей для оптимизации медиафайлов (FFmpeg и другие инструменты)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Проверка наличия FFmpeg...');
        
        $hasFFmpeg = $this->checkFFmpegInstalled();
        
        if ($hasFFmpeg) {
            $this->info('FFmpeg уже установлен.');
        } else {
            $this->warn('FFmpeg не найден. Необходимо установить FFmpeg для оптимизации видео.');
            
            if ($this->confirm('Хотите получить инструкции по установке FFmpeg?')) {
                $this->showFFmpegInstallInstructions();
            }
        }
        
        // Проверяем дополнительные инструменты оптимизации
        if ($this->option('extended')) {
            $this->info('Проверка наличия дополнительных инструментов для оптимизации изображений...');
            
            // Проверяем pngquant
            $hasPngquant = $this->checkToolInstalled('pngquant');
            if ($hasPngquant) {
                $this->info('pngquant уже установлен - отлично для оптимизации PNG.');
            } else {
                $this->warn('pngquant не найден. Этот инструмент помогает оптимизировать PNG изображения.');
                if ($this->confirm('Хотите получить инструкции по установке pngquant?')) {
                    $this->showPngquantInstallInstructions();
                }
            }
            
            // Проверяем jpegoptim
            $hasJpegoptim = $this->checkToolInstalled('jpegoptim');
            if ($hasJpegoptim) {
                $this->info('jpegoptim уже установлен - отлично для оптимизации JPEG.');
            } else {
                $this->warn('jpegoptim не найден. Этот инструмент помогает оптимизировать JPEG изображения.');
                if ($this->confirm('Хотите получить инструкции по установке jpegoptim?')) {
                    $this->showJpegoptimInstallInstructions();
                }
            }
        }
        
        $this->info('Проверка установки зависимостей PHP...');
        
        // Проверяем, установлен ли пакет Intervention/Image
        $hasIntervention = $this->checkComposerPackage('intervention/image');
        
        if ($hasIntervention) {
            $this->info('Пакет intervention/image уже установлен.');
        } else {
            $this->warn('Пакет intervention/image не найден.');
            
            if ($this->confirm('Установить intervention/image сейчас?')) {
                $this->info('Выполняем composer require intervention/image');
                shell_exec('composer require intervention/image');
            }
        }
        
        // Проверяем расширения PHP
        $this->checkPhpExtension('gd', 'GD Library необходимо для обработки изображений.');
        $this->checkPhpExtension('exif', 'EXIF расширение рекомендуется для корректной работы с изображениями.');
        $this->checkPhpExtension('fileinfo', 'Fileinfo расширение необходимо для определения MIME-типов файлов.');
        
        $this->info('Создание символической ссылки на публичное хранилище...');
        $this->call('storage:link');
        
        $this->info('Проверка директорий для хранения медиафайлов...');
        $this->ensureDirectoriesExist();
        
        $this->info('Настройка зависимостей для оптимизации медиафайлов завершена.');
        
        $this->info('');
        $this->info('Поддерживаемые форматы изображений:');
        $this->line('JPG/JPEG, PNG, GIF, WebP, BMP, TIFF, AVIF, HEIC/HEIF, SVG');
        $this->info('');
        $this->info('Поддерживаемые форматы видео:');
        $this->line('MP4, WebM, MOV, AVI, WMV, FLV, MKV, MPEG/MPG, M4V, 3GP');
        $this->info('');
        $this->info('Все форматы видео будут конвертированы в MP4 или WebM для лучшей совместимости и сжатия.');
    }
    
    /**
     * Проверка наличия FFmpeg
     */
    protected function checkFFmpegInstalled()
    {
        $output = null;
        $returnVar = null;
        
        @exec('ffmpeg -version', $output, $returnVar);
        
        return $returnVar === 0;
    }
    
    /**
     * Проверка наличия указанного инструмента
     */
    protected function checkToolInstalled($tool)
    {
        $output = null;
        $returnVar = null;
        
        @exec($tool . ' --version', $output, $returnVar);
        
        return $returnVar === 0;
    }
    
    /**
     * Проверка установленного пакета Composer
     */
    protected function checkComposerPackage($package)
    {
        $composerJson = file_get_contents(base_path('composer.json'));
        $composerData = json_decode($composerJson, true);
        
        return isset($composerData['require'][$package]) || isset($composerData['require-dev'][$package]);
    }
    
    /**
     * Проверка расширения PHP
     */
    protected function checkPhpExtension($extension, $message)
    {
        if (extension_loaded($extension)) {
            $this->info("Расширение PHP {$extension} уже установлено.");
        } else {
            $this->warn("Расширение PHP {$extension} не найдено. {$message}");
        }
    }
    
    /**
     * Показать инструкции по установке FFmpeg
     */
    protected function showFFmpegInstallInstructions()
    {
        $this->info('=== Инструкции по установке FFmpeg ===');
        $this->line('');
        
        $this->info('Для Ubuntu/Debian:');
        $this->line('sudo apt-get update');
        $this->line('sudo apt-get install ffmpeg -y');
        $this->line('');
        
        $this->info('Для CentOS/RHEL:');
        $this->line('sudo yum install epel-release');
        $this->line('sudo yum update');
        $this->line('sudo yum install ffmpeg ffmpeg-devel');
        $this->line('');
        
        $this->info('Для macOS (требуется Homebrew):');
        $this->line('brew install ffmpeg');
        $this->line('');
        
        $this->info('Для Windows:');
        $this->line('1. Скачайте статическую сборку FFmpeg отсюда: https://www.gyan.dev/ffmpeg/builds/');
        $this->line('2. Распакуйте архив');
        $this->line('3. Добавьте папку bin в переменную PATH');
        $this->line('');
        
        $this->info('После установки FFmpeg, перезапустите веб-сервер.');
    }
    
    /**
     * Показать инструкции по установке pngquant
     */
    protected function showPngquantInstallInstructions()
    {
        $this->info('=== Инструкции по установке pngquant ===');
        $this->line('');
        
        $this->info('Для Ubuntu/Debian:');
        $this->line('sudo apt-get update');
        $this->line('sudo apt-get install pngquant -y');
        $this->line('');
        
        $this->info('Для CentOS/RHEL:');
        $this->line('sudo yum install epel-release');
        $this->line('sudo yum install pngquant');
        $this->line('');
        
        $this->info('Для macOS (требуется Homebrew):');
        $this->line('brew install pngquant');
        $this->line('');
        
        $this->info('Для Windows:');
        $this->line('1. Скачайте pngquant с официального сайта: https://pngquant.org/');
        $this->line('2. Распакуйте архив');
        $this->line('3. Добавьте путь в переменную PATH');
        $this->line('');
    }
    
    /**
     * Показать инструкции по установке jpegoptim
     */
    protected function showJpegoptimInstallInstructions()
    {
        $this->info('=== Инструкции по установке jpegoptim ===');
        $this->line('');
        
        $this->info('Для Ubuntu/Debian:');
        $this->line('sudo apt-get update');
        $this->line('sudo apt-get install jpegoptim -y');
        $this->line('');
        
        $this->info('Для CentOS/RHEL:');
        $this->line('sudo yum install epel-release');
        $this->line('sudo yum install jpegoptim');
        $this->line('');
        
        $this->info('Для macOS (требуется Homebrew):');
        $this->line('brew install jpegoptim');
        $this->line('');
        
        $this->info('Для Windows:');
        $this->line('1. Скачайте jpegoptim с сайта: https://github.com/tjko/jpegoptim/releases');
        $this->line('2. Распакуйте архив');
        $this->line('3. Добавьте путь в переменную PATH');
        $this->line('');
    }
    
    /**
     * Проверка и создание необходимых директорий
     */
    protected function ensureDirectoriesExist()
    {
        $directories = [
            storage_path('app/public/template_covers'),
        ];
        
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                $this->info("Создание директории: {$directory}");
                mkdir($directory, 0755, true);
            } else {
                $this->line("Директория {$directory} уже существует.");
            }
        }
        
        // Установка прав доступа для директорий
        $this->info('Установка прав доступа для директорий хранения...');
        chmod(storage_path('app/public'), 0755);
        chmod(storage_path('app/public/template_covers'), 0755);
    }
}

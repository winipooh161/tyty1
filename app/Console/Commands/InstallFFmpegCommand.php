<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InstallFfmpegCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'ffmpeg:install {--force : Принудительная переустановка FFmpeg}';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Установка FFmpeg или вывод инструкций по установке';

    /**
     * Выполнение консольной команды.
     */
    public function handle()
    {
        $this->info('Проверка наличия FFmpeg...');

        // Проверяем, установлен ли уже FFmpeg
        if ($this->checkFfmpegInstalled() && !$this->option('force')) {
            $this->info('FFmpeg уже установлен.');
            $this->info('Версия FFmpeg: ' . $this->getFfmpegVersion());
            
            if ($this->confirm('Хотите выполнить переустановку?', false)) {
                $this->installFfmpeg();
            }
            
            return;
        }

        // Определяем ОС
        $os = $this->getOperatingSystem();
        $this->info("Обнаружена операционная система: {$os}");

        // Выполняем установку
        $this->installFfmpeg($os);
    }

    /**
     * Проверяет, установлен ли FFmpeg.
     *
     * @return bool
     */
    protected function checkFfmpegInstalled()
    {
        try {
            $process = new Process(['ffmpeg', '-version']);
            $process->run();
            return $process->isSuccessful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получает версию FFmpeg.
     *
     * @return string
     */
    protected function getFfmpegVersion()
    {
        try {
            $process = new Process(['ffmpeg', '-version']);
            $process->run();
            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                if (preg_match('/ffmpeg version ([^\s]+)/', $output, $matches)) {
                    return $matches[1];
                }
                return 'Версия не определена';
            }
            return 'Не удалось определить версию';
        } catch (\Exception $e) {
            return 'Ошибка при определении версии';
        }
    }

    /**
     * Определяет текущую операционную систему.
     *
     * @return string
     */
    protected function getOperatingSystem()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'windows';
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            return 'macos';
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Определение конкретного дистрибутива Linux
            if (file_exists('/etc/debian_version')) {
                return 'debian';
            } elseif (file_exists('/etc/redhat-release')) {
                return 'redhat';
            } else {
                return 'linux';
            }
        } else {
            return 'unknown';
        }
    }

    /**
     * Устанавливает FFmpeg в зависимости от ОС.
     *
     * @param string $os
     * @return void
     */
    protected function installFfmpeg($os = null)
    {
        if (!$os) {
            $os = $this->getOperatingSystem();
        }

        switch ($os) {
            case 'windows':
                $this->installFfmpegOnWindows();
                break;
            case 'debian':
                $this->installFfmpegOnDebian();
                break;
            case 'redhat':
                $this->installFfmpegOnRedHat();
                break;
            case 'macos':
                $this->installFfmpegOnMacOS();
                break;
            default:
                $this->showManualInstructions();
                break;
        }
    }

    /**
     * Отображает инструкции по установке FFmpeg вручную.
     *
     * @return void
     */
    protected function showManualInstructions()
    {
        $this->info('=== Инструкции по установке FFmpeg ===');
        $this->newLine();
        
        $this->info('Для Ubuntu/Debian:');
        $this->line('sudo apt-get update');
        $this->line('sudo apt-get install ffmpeg -y');
        $this->newLine();
        
        $this->info('Для CentOS/RHEL:');
        $this->line('sudo yum install epel-release -y');
        $this->line('sudo yum update -y');
        $this->line('sudo yum install ffmpeg ffmpeg-devel -y');
        $this->newLine();
        
        $this->info('Для macOS (требуется Homebrew):');
        $this->line('/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"');
        $this->line('brew install ffmpeg');
        $this->newLine();
        
        $this->info('Для Windows:');
        $this->line('1. Скачайте статическую сборку FFmpeg отсюда: https://www.gyan.dev/ffmpeg/builds/');
        $this->line('2. Распакуйте архив в удобное место (например, C:\\ffmpeg)');
        $this->line('3. Добавьте путь к папке bin в переменную PATH:');
        $this->line('   - Нажмите Win + X, выберите "Система"');
        $this->line('   - Нажмите "Дополнительные параметры системы"');
        $this->line('   - Нажмите "Переменные среды"');
        $this->line('   - В разделе "Системные переменные" найдите Path и нажмите "Изменить"');
        $this->line('   - Нажмите "Создать" и добавьте путь к папке bin (например, C:\\ffmpeg\\bin)');
        $this->line('   - Нажмите "OK" на всех диалогах');
        $this->line('4. Перезапустите командную строку и проверьте установку командой:');
        $this->line('   ffmpeg -version');
        $this->newLine();
        
        $this->info('После установки FFmpeg, проверьте командой: ffmpeg -version');
    }

    /**
     * Устанавливает FFmpeg на Windows.
     *
     * @return void
     */
    protected function installFfmpegOnWindows()
    {
        $this->info('Для Windows FFmpeg устанавливается вручную:');
        $this->newLine();
        
        // Проверяем наличие Chocolatey
        $hasChocolatey = false;
        try {
            $process = new Process(['choco', '--version']);
            $process->run();
            $hasChocolatey = $process->isSuccessful();
        } catch (\Exception $e) {
            $hasChocolatey = false;
        }
        
        if ($hasChocolatey) {
            $this->info('Обнаружен менеджер пакетов Chocolatey.');
            if ($this->confirm('Установить FFmpeg с помощью Chocolatey?', true)) {
                $this->info('Установка FFmpeg через Chocolatey...');
                
                try {
                    $process = Process::fromShellCommandline('choco install ffmpeg -y');
                    $process->setTty(true);
                    $process->run();
                    
                    if ($process->isSuccessful()) {
                        $this->info('FFmpeg успешно установлен через Chocolatey!');
                        $this->info('Перезапустите терминал, чтобы изменения в PATH вступили в силу.');
                        return;
                    } else {
                        $this->error('Не удалось установить FFmpeg через Chocolatey.');
                        $this->line($process->getErrorOutput());
                    }
                } catch (\Exception $e) {
                    $this->error('Произошла ошибка: ' . $e->getMessage());
                }
            }
        } else {
            $this->info('Вы можете установить менеджер пакетов Chocolatey и использовать его для установки FFmpeg:');
            $this->line('Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString(\'https://community.chocolatey.org/install.ps1\'))');
            $this->line('choco install ffmpeg -y');
            $this->newLine();
        }
        
        $this->info('Руководство по ручной установке FFmpeg:');
        $this->line('1. Скачайте статическую сборку FFmpeg отсюда: https://www.gyan.dev/ffmpeg/builds/');
        $this->line('2. Распакуйте архив в удобное место (например, C:\\ffmpeg)');
        $this->line('3. Добавьте путь к папке bin в переменную PATH:');
        $this->line('   - Нажмите Win + X, выберите "Система"');
        $this->line('   - Нажмите "Дополнительные параметры системы"');
        $this->line('   - Нажмите "Переменные среды"');
        $this->line('   - В разделе "Системные переменные" найдите Path и нажмите "Изменить"');
        $this->line('   - Нажмите "Создать" и добавьте путь к папке bin (например, C:\\ffmpeg\\bin)');
        $this->line('   - Нажмите "OK" на всех диалогах');
        $this->line('4. Перезапустите командную строку и проверьте установку командой:');
        $this->line('   ffmpeg -version');
        
        // Предлагаем автоматическую загрузку
        if ($this->confirm('Хотите автоматически скачать FFmpeg? (архив будет загружен в текущую директорию)', true)) {
            $this->downloadFfmpegForWindows();
        }
    }

    /**
     * Загружает FFmpeg для Windows.
     *
     * @return void
     */
    protected function downloadFfmpegForWindows()
    {
        $this->info('Загрузка FFmpeg для Windows...');
        
        // URL для скачивания сборки FFmpeg
        $url = 'https://github.com/BtbN/FFmpeg-Builds/releases/download/latest/ffmpeg-master-latest-win64-gpl.zip';
        $zipFile = storage_path('ffmpeg-master-latest-win64-gpl.zip');
        
        $this->info("Загрузка FFmpeg с $url");
        $this->info("Файл будет сохранен как $zipFile");
        
        if (file_exists($zipFile)) {
            if (!$this->confirm("Файл $zipFile уже существует. Перезаписать?", true)) {
                return;
            }
            unlink($zipFile);
        }
        
        // Создаем директорию, если она не существует
        if (!file_exists(dirname($zipFile))) {
            mkdir(dirname($zipFile), 0755, true);
        }
        
        // Загружаем файл
        try {
            $this->info('Загрузка FFmpeg, пожалуйста, подождите...');
            $result = file_put_contents($zipFile, fopen($url, 'r'));
            
            if ($result === false) {
                $this->error('Не удалось загрузить файл.');
                return;
            }
            
            $this->info("FFmpeg успешно загружен в $zipFile");
            $this->info('Теперь вы можете вручную распаковать архив и добавить путь к FFmpeg в переменную PATH.');
            
            // Предлагаем распаковать архив
            if ($this->confirm('Распаковать архив сейчас?', true)) {
                $extractPath = storage_path('ffmpeg-extract');
                
                if (!file_exists($extractPath)) {
                    mkdir($extractPath, 0755, true);
                }
                
                $this->info("Распаковка архива в $extractPath");
                
                // Распаковываем архив с помощью ZipArchive
                $zip = new \ZipArchive;
                if ($zip->open($zipFile) === true) {
                    $zip->extractTo($extractPath);
                    $zip->close();
                    $this->info('Архив успешно распакован!');
                    
                    // Находим директорию bin внутри распакованного архива
                    $binDir = $this->findBinDirInExtract($extractPath);
                    
                    if ($binDir) {
                        $this->info("Найдена директория с FFmpeg: $binDir");
                        $this->info('Теперь добавьте эту директорию в переменную PATH вашей системы.');
                        
                        // Предлагаем добавить путь в PATH
                        if ($this->confirm('Попробовать автоматически добавить путь в PATH?', true)) {
                            $this->addToWindowsPath($binDir);
                        }
                    } else {
                        $this->warn('Не удалось найти директорию bin в распакованном архиве.');
                        $this->info('Проверьте содержимое распакованного архива вручную и добавьте путь к FFmpeg в переменную PATH.');
                    }
                } else {
                    $this->error('Не удалось распаковать архив.');
                }
            }
        } catch (\Exception $e) {
            $this->error('Произошла ошибка при загрузке FFmpeg: ' . $e->getMessage());
        }
    }

    /**
     * Находит директорию bin в распакованном архиве.
     *
     * @param string $extractPath
     * @return string|null
     */
    protected function findBinDirInExtract($extractPath)
    {
        // Рекурсивно ищем файл ffmpeg.exe
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'ffmpeg.exe') {
                return $file->getPath();
            }
        }
        
        return null;
    }

    /**
     * Добавляет путь в переменную PATH Windows.
     *
     * @param string $path
     * @return void
     */
    protected function addToWindowsPath($path)
    {
        $this->info('Попытка добавления пути в переменную PATH Windows...');
        
        try {
            // Создаем PowerShell скрипт для добавления пути в PATH
            $scriptPath = storage_path('add_to_path.ps1');
            $script = <<<EOT
\$path = [Environment]::GetEnvironmentVariable('Path', [EnvironmentVariableTarget]::User)
\$newPath = "\$path;$path"
[Environment]::SetEnvironmentVariable('Path', \$newPath, [EnvironmentVariableTarget]::User)
Write-Host "Путь успешно добавлен в PATH."
EOT;
            
            file_put_contents($scriptPath, $script);
            
            // Запускаем PowerShell скрипт с повышенными привилегиями
            $this->info('Запуск PowerShell для изменения переменной PATH...');
            $this->warn('Может потребоваться подтверждение UAC и PowerShell может открыться в отдельном окне.');
            
            $process = Process::fromShellCommandline("powershell -ExecutionPolicy Bypass -File \"$scriptPath\"");
            $process->run();
            
            if ($process->isSuccessful()) {
                $this->info('Путь успешно добавлен в PATH.');
                $this->info('Вам может потребоваться перезапустить командную строку или компьютер, чтобы изменения вступили в силу.');
            } else {
                $this->error('Не удалось добавить путь в PATH автоматически.');
                $this->line($process->getErrorOutput());
                $this->info('Добавьте путь вручную, следуя инструкциям выше.');
            }
            
            // Удаляем временный скрипт
            unlink($scriptPath);
        } catch (\Exception $e) {
            $this->error('Произошла ошибка при добавлении пути в PATH: ' . $e->getMessage());
            $this->info('Добавьте путь вручную, следуя инструкциям выше.');
        }
    }

    /**
     * Устанавливает FFmpeg на Debian/Ubuntu.
     *
     * @return void
     */
    protected function installFfmpegOnDebian()
    {
        $this->info('Установка FFmpeg на Debian/Ubuntu...');
        
        if ($this->confirm('Выполнить установку FFmpeg с помощью apt-get?', true)) {
            $this->info('Обновление репозиториев...');
            $this->executeCommand('sudo apt-get update');
            
            $this->info('Установка FFmpeg...');
            $this->executeCommand('sudo apt-get install ffmpeg -y');
            
            if ($this->checkFfmpegInstalled()) {
                $this->info('FFmpeg успешно установлен!');
                $this->info('Версия FFmpeg: ' . $this->getFfmpegVersion());
            } else {
                $this->error('Не удалось установить FFmpeg.');
                $this->showManualInstructions();
            }
        } else {
            $this->showManualInstructions();
        }
    }

    /**
     * Устанавливает FFmpeg на RedHat/CentOS.
     *
     * @return void
     */
    protected function installFfmpegOnRedHat()
    {
        $this->info('Установка FFmpeg на RedHat/CentOS...');
        
        if ($this->confirm('Выполнить установку FFmpeg с помощью yum?', true)) {
            $this->info('Установка EPEL репозитория...');
            $this->executeCommand('sudo yum install epel-release -y');
            
            $this->info('Обновление репозиториев...');
            $this->executeCommand('sudo yum update -y');
            
            $this->info('Установка FFmpeg...');
            $this->executeCommand('sudo yum install ffmpeg ffmpeg-devel -y');
            
            if ($this->checkFfmpegInstalled()) {
                $this->info('FFmpeg успешно установлен!');
                $this->info('Версия FFmpeg: ' . $this->getFfmpegVersion());
            } else {
                $this->error('Не удалось установить FFmpeg.');
                $this->showManualInstructions();
            }
        } else {
            $this->showManualInstructions();
        }
    }

    /**
     * Устанавливает FFmpeg на macOS.
     *
     * @return void
     */
    protected function installFfmpegOnMacOS()
    {
        $this->info('Установка FFmpeg на macOS...');
        
        // Проверка наличия Homebrew
        $hasHomebrew = false;
        try {
            $process = new Process(['brew', '--version']);
            $process->run();
            $hasHomebrew = $process->isSuccessful();
        } catch (\Exception $e) {
            $hasHomebrew = false;
        }
        
        if ($hasHomebrew) {
            $this->info('Обнаружен менеджер пакетов Homebrew.');
            if ($this->confirm('Установить FFmpeg с помощью Homebrew?', true)) {
                $this->info('Установка FFmpeg через Homebrew...');
                $this->executeCommand('brew install ffmpeg');
                
                if ($this->checkFfmpegInstalled()) {
                    $this->info('FFmpeg успешно установлен!');
                    $this->info('Версия FFmpeg: ' . $this->getFfmpegVersion());
                } else {
                    $this->error('Не удалось установить FFmpeg.');
                    $this->showManualInstructions();
                }
            } else {
                $this->showManualInstructions();
            }
        } else {
            $this->info('Для установки FFmpeg на macOS рекомендуется использовать Homebrew.');
            if ($this->confirm('Установить Homebrew?', true)) {
                $this->info('Установка Homebrew...');
                $this->executeCommand('/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"');
                
                $this->info('Установка FFmpeg через Homebrew...');
                $this->executeCommand('brew install ffmpeg');
                
                if ($this->checkFfmpegInstalled()) {
                    $this->info('FFmpeg успешно установлен!');
                    $this->info('Версия FFmpeg: ' . $this->getFfmpegVersion());
                } else {
                    $this->error('Не удалось установить FFmpeg.');
                    $this->showManualInstructions();
                }
            } else {
                $this->showManualInstructions();
            }
        }
    }

    /**
     * Выполняет команду и выводит результат.
     *
     * @param string $command
     * @return bool
     */
    protected function executeCommand($command)
    {
        try {
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300); // 5 минут тайм-аут
            
            // Пытаемся использовать TTY для интерактивного ввода, если поддерживается
            try {
                $process->setTty(true);
            } catch (\RuntimeException $e) {
                // TTY не поддерживается, продолжаем без него
                $this->warn('TTY не поддерживается, команда может выполниться без интерактивности.');
                
                // Для некоторых команд может потребоваться подтверждение
                $command = str_replace(['sudo apt-get', 'sudo yum'], ['sudo apt-get -y', 'sudo yum -y'], $command);
            }
            
            $process->run(function ($type, $buffer) {
                if ($process = Process::ERR) {
                    $this->warn($buffer);
                } else {
                    $this->line($buffer);
                }
            });
            
            return $process->isSuccessful();
        } catch (\Exception $e) {
            $this->error('Ошибка выполнения команды: ' . $e->getMessage());
            return false;
        }
    }
}

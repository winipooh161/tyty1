<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ClearAllCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-all {--force : Force clearing without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистить все виды кэша и буферизации в приложении';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Вы уверены, что хотите очистить ВСЕ кэши и буферизации?')) {
                $this->info('Операция отменена.');
                return;
            }
        }

        $this->info('🧹 Начинаем полную очистку всех кэшей и буферизаций...');
        $this->newLine();

        // Laravel кэши
        $this->clearLaravelCaches();
        
        // Composer кэш
        $this->clearComposerCache();
        
        // NPM/Node кэши
        $this->clearNodeCaches();
        
        // Vite кэши
        $this->clearViteCaches();
        
        // Браузерные кэши (manifest файлы)
        $this->clearBrowserCaches();
        
        // Логи
        $this->clearLogs();
        
        // Временные файлы
        $this->clearTempFiles();

        $this->newLine();
        $this->info('✅ Все кэши и буферизации успешно очищены!');
        $this->info('💡 Рекомендуется перезапустить веб-сервер и браузер.');
    }

    private function clearLaravelCaches()
    {
        $this->info('🔄 Очищаем Laravel кэши...');
        
        try {
            Artisan::call('cache:clear');
            $this->line('   ✓ Application cache cleared');
            
            Artisan::call('config:clear');
            $this->line('   ✓ Configuration cache cleared');
            
            Artisan::call('route:clear');
            $this->line('   ✓ Route cache cleared');
            
            Artisan::call('view:clear');
            $this->line('   ✓ View cache cleared');
            
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $this->line('   ✓ OPCache cleared');
            }
            
            // Очистка session files
            if (config('session.driver') === 'file') {
                $sessionPath = storage_path('framework/sessions');
                if (File::exists($sessionPath)) {
                    File::cleanDirectory($sessionPath);
                    $this->line('   ✓ Session files cleared');
                }
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error clearing Laravel caches: ' . $e->getMessage());
        }
    }

    private function clearComposerCache()
    {
        $this->info('🔄 Очищаем Composer кэш...');
        
        try {
            exec('composer clear-cache 2>&1', $output, $returnCode);
            if ($returnCode === 0) {
                $this->line('   ✓ Composer cache cleared');
            } else {
                $this->line('   ⚠️  Composer не найден или ошибка очистки');
            }
        } catch (\Exception $e) {
            $this->line('   ⚠️  Ошибка очистки Composer кэша: ' . $e->getMessage());
        }
    }

    private function clearNodeCaches()
    {
        $this->info('🔄 Очищаем Node.js кэши...');
        
        try {
            // NPM cache
            exec('npm cache clean --force 2>&1', $output, $returnCode);
            if ($returnCode === 0) {
                $this->line('   ✓ NPM cache cleared');
            }
            
            // Node modules cache
            $nodeModulesPath = base_path('node_modules/.cache');
            if (File::exists($nodeModulesPath)) {
                File::deleteDirectory($nodeModulesPath);
                $this->line('   ✓ Node modules cache cleared');
            }
            
        } catch (\Exception $e) {
            $this->line('   ⚠️  Ошибка очистки Node кэшей: ' . $e->getMessage());
        }
    }

    private function clearViteCaches()
    {
        $this->info('🔄 Очищаем Vite кэши...');
        
        try {
            // Vite cache directory
            $viteCachePath = base_path('node_modules/.vite');
            if (File::exists($viteCachePath)) {
                File::deleteDirectory($viteCachePath);
                $this->line('   ✓ Vite cache directory cleared');
            }
            
            // Public build files
            $publicBuildPath = public_path('build');
            if (File::exists($publicBuildPath)) {
                File::deleteDirectory($publicBuildPath);
                $this->line('   ✓ Public build files cleared');
            }
            
            // Hot file
            $hotFile = public_path('hot');
            if (File::exists($hotFile)) {
                File::delete($hotFile);
                $this->line('   ✓ Vite hot file cleared');
            }
            
        } catch (\Exception $e) {
            $this->line('   ⚠️  Ошибка очистки Vite кэшей: ' . $e->getMessage());
        }
    }

    private function clearBrowserCaches()
    {
        $this->info('🔄 Очищаем браузерные кэши...');
        
        try {
            // Service worker files
            $swFiles = ['sw.js', 'service-worker.js', 'workbox-sw.js'];
            foreach ($swFiles as $swFile) {
                $swPath = public_path($swFile);
                if (File::exists($swPath)) {
                    File::delete($swPath);
                    $this->line("   ✓ Service worker file {$swFile} cleared");
                }
            }
            
            // Manifest files
            $manifestFiles = ['manifest.json', 'mix-manifest.json', 'vite-manifest.json'];
            foreach ($manifestFiles as $manifestFile) {
                $manifestPath = public_path($manifestFile);
                if (File::exists($manifestPath)) {
                    // Создаем новый пустой манифест с новым timestamp
                    File::put($manifestPath, json_encode(['timestamp' => time()]));
                    $this->line("   ✓ Manifest file {$manifestFile} updated");
                }
            }
            
        } catch (\Exception $e) {
            $this->line('   ⚠️  Ошибка очистки браузерных кэшей: ' . $e->getMessage());
        }
    }

    private function clearLogs()
    {
        $this->info('🔄 Очищаем логи...');
        
        try {
            $logsPath = storage_path('logs');
            if (File::exists($logsPath)) {
                $logFiles = File::glob($logsPath . '/*.log');
                foreach ($logFiles as $logFile) {
                    File::put($logFile, '');
                }
                $this->line('   ✓ Log files cleared');
            }
        } catch (\Exception $e) {
            $this->line('   ⚠️  Ошибка очистки логов: ' . $e->getMessage());
        }
    }

    private function clearTempFiles()
    {
        $this->info('🔄 Очищаем временные файлы...');
        
        try {
            // Framework cache
            $frameworkPath = storage_path('framework/cache');
            if (File::exists($frameworkPath)) {
                File::cleanDirectory($frameworkPath);
                $this->line('   ✓ Framework cache cleared');
            }
            
            // Debugbar files
            $debugbarPath = storage_path('debugbar');
            if (File::exists($debugbarPath)) {
                File::cleanDirectory($debugbarPath);
                $this->line('   ✓ Debugbar files cleared');
            }
            
            // Temporary uploads
            $tempUploadsPath = storage_path('app/temp');
            if (File::exists($tempUploadsPath)) {
                File::cleanDirectory($tempUploadsPath);
                $this->line('   ✓ Temporary uploads cleared');
            }
            
        } catch (\Exception $e) {
            $this->line('   ⚠️  Ошибка очистки временных файлов: ' . $e->getMessage());
        }
    }
}

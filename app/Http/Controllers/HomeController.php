<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AcquiredTemplate;
use App\Models\AcquiredTemplateFolder;
use App\Services\MediaOptimizationService;

class HomeController extends Controller
{
    /**
     * Сервис оптимизации медиа
     *
     * @var MediaOptimizationService
     */
    protected $mediaOptimizationService;

    /**
     * Create a new controller instance.
     *
     * @param MediaOptimizationService $mediaOptimizationService
     * @return void
     */
    public function __construct(MediaOptimizationService $mediaOptimizationService)
    {
        $this->middleware('auth');
        $this->mediaOptimizationService = $mediaOptimizationService;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Загружаем полученные пользователем шаблоны
        $acquiredTemplates = Auth::user()->acquiredTemplates()
            ->with(['userTemplate.user', 'userTemplate.template.category', 'folder'])
            ->latest('acquired_at') // Возвращаем сортировку по acquired_at после успешной миграции
            ->get();

        // Получаем папки для приобретенных шаблонов
        $acquiredFolders = AcquiredTemplateFolder::where('user_id', auth()->id())->get();
        
        // Добавляем диагностическую информацию для отладки
        $acquiredTemplatesCount = auth()->user()->acquiredTemplates()->count();
        $userTemplatesCount = auth()->user()->templates()->count();
        
        // Проверяем, установлен ли FFmpeg (только для администраторов)
        $ffmpegInstalled = null;
        if (auth()->user()->role === 'admin') {
            $ffmpegInstalled = $this->mediaOptimizationService->checkFFmpeg();
        }
        
        return view('home', compact('acquiredTemplates', 'acquiredFolders', 'acquiredTemplatesCount', 'userTemplatesCount', 'ffmpegInstalled'));
    }
    
    /**
     * Отобразить окно проверки статуса медиа-зависимостей
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkMediaDependencies()
    {
        // Проверка прав администратора
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'Доступ запрещен'
            ], 403);
        }
        
        $ffmpegInstalled = $this->mediaOptimizationService->checkFFmpeg();
        
        // Проверка наличия расширений PHP
        $extensions = [
            'gd' => extension_loaded('gd'),
            'exif' => extension_loaded('exif'),
            'fileinfo' => extension_loaded('fileinfo')
        ];
        
        return response()->json([
            'success' => true,
            'ffmpeg_installed' => $ffmpegInstalled,
            'extensions' => $extensions
        ]);
    }
}

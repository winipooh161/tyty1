<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\UserTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class DefaultTemplateController extends Controller
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
     * Показать редактор медиа с базовым шаблоном.
     *
     * @return \Illuminate\Http\Response
     */
 

    /**
     * Безопасное создание URL для маршрута
     *
     * @param string $name Имя маршрута
     * @param array $parameters Параметры маршрута
     * @param string $fallbackRoute Запасной маршрут
     * @return string URL
     */
    protected function safeRoute($name, $parameters = [], $fallbackRoute = 'home')
    {
        try {
            if (Route::has($name)) {
                return route($name, $parameters);
            }
            
            Log::warning("Маршрут {$name} не найден, используем запасной маршрут {$fallbackRoute}");
            return route($fallbackRoute);
        } catch (\Exception $e) {
            Log::error("Ошибка при создании URL для маршрута {$name}: " . $e->getMessage());
            return route('home');
        }
    }

    /**
     * Показать список дефолтных шаблонов
     */
    public function index(Request $request)
    {
        // Проверяем наличие загруженного медиафайла в сессии
        $mediaFile = session('media_editor_file');
        $mediaType = session('media_editor_type');
        
        if (!$mediaFile) {
            return redirect()->route('media.editor')
                ->with('error', 'Необходимо сначала загрузить медиафайл');
        }
        
        // Получаем шаблоны, подходящие для данного типа медиа
        $templates = Template::where('is_active', 1)
            ->where(function ($query) use ($mediaType) {
                if ($mediaType === 'image') {
                    $query->where('cover_type', 'image')
                          ->orWhere('cover_type', 'any');
                } elseif ($mediaType === 'video') {
                    $query->where('cover_type', 'video')
                          ->orWhere('cover_type', 'any');
                }
            })
            ->orderBy('id', 'desc')
            ->get();
        
        return view('templates.default', compact('templates', 'mediaFile', 'mediaType'));
    }
    
    /**
     * Выбрать дефолтный шаблон и открыть его в редакторе
     */
    public function select(Request $request, $id)
    {
        // Проверяем наличие загруженного медиафайла в сессии
        $mediaFile = session('media_editor_file');
        $mediaType = session('media_editor_type');
        
        if (!$mediaFile) {
            return redirect()->route('media.editor')
                ->with('error', 'Необходимо сначала загрузить медиафайл');
        }
        
        // Находим шаблон
        $template = Template::findOrFail($id);
        
        // Создаем новый пользовательский шаблон
        $userTemplate = new UserTemplate();
        $userTemplate->user_id = Auth::id();
        $userTemplate->template_id = $template->id;
        $userTemplate->name = $template->name;
        $userTemplate->cover_path = $mediaFile;
        $userTemplate->cover_type = $mediaType;
        $userTemplate->html_content = $template->html_content;
        $userTemplate->is_active = 1;
        $userTemplate->save();
        
        // Очищаем сессию
        session()->forget(['media_editor_file', 'media_editor_type']);
        
        // Перенаправляем на страницу редактора
        return redirect()->route('client.templates.editor', $userTemplate->id)
            ->with('success', 'Шаблон создан с вашей медиа-обложкой');
    }
    
    /**
     * Показать дефолтный шаблон в редакторе
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showEditor()
    {
        // Находим дефолтный шаблон
        $defaultTemplate = Template::where('is_default', true)->first();
        
        // Если дефолтный шаблон не найден, перенаправляем на список категорий
        if (!$defaultTemplate) {
            return redirect()->route('templates.categories')
                ->with('error', 'Дефолтный шаблон не найден');
        }
        
        // Перенаправляем на редактор шаблона
        return redirect()->route('templates.editor', $defaultTemplate->id);
    }

    /**
     * Показать шаблон с загруженным медиа
     * 
     * @param int $templateId
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function showTemplateWithMedia($templateId)
    {
        // Проверяем существование шаблона
        $template = Template::find($templateId);
        
        if (!$template) {
            // Если шаблон не найден, используем дефолтный
            $template = Template::where('is_default', true)->first();
            
            if (!$template) {
                return redirect()->route('templates.categories')
                    ->with('error', 'Шаблон не найден');
            }
        }
        
        // Проверяем наличие загруженного медиа в сессии
        $mediaFile = Session::get('media_editor_file');
        $mediaType = Session::get('media_editor_type');
        
        if ($mediaFile) {
            // Создаем переменные для передачи в шаблон
            $coverPath = Storage::url($mediaFile);
            
            // Показываем редактор шаблона с данными о медиа
            return view('templates.editor', compact('template', 'coverPath', 'mediaType'));
        }
        
        // Перенаправляем на редактор шаблона без медиа
        return redirect()->route('templates.editor', $template->id);
    }
}

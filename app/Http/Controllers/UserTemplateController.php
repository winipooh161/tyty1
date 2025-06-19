<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserTemplate;
use App\Models\TemplateFolder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserTemplateController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Показать список шаблонов пользователя.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Получаем текущего пользователя
        $user = Auth::user();
        
        // Логируем для отладки
        Log::info('UserTemplateController@index: Загрузка шаблонов для пользователя ' . $user->id);
        
        // Получаем все папки пользователя
        $folders = TemplateFolder::where('user_id', $user->id)->get();
        
        // Получаем шаблоны, созданные пользователем
        $templates = UserTemplate::where('user_id', $user->id)
            ->with(['template.category', 'folder'])
            ->latest()
            ->get();
        
        // Проверка наличия обложек
        foreach ($templates as $template) {
            if ($template->cover_path) {
                $exists = \Storage::disk('public')->exists($template->cover_path);
                \Log::info("Шаблон ID={$template->id}, cover_path={$template->cover_path}, exists={$exists}");
            }
        }
        
        // Логируем количество найденных шаблонов для отладки
        Log::info('UserTemplateController@index: Найдено шаблонов: ' . $templates->count());
        
        return view('user.templates.index', compact('templates', 'folders'));
    }
    
    /**
     * Показать конкретный шаблон пользователя.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $userTemplate = UserTemplate::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->where('status', 'published')
                        ->firstOrFail();
        
        return view('user.templates.show', compact('userTemplate'));
    }
    
    /**
     * Редактирование шаблона пользователя.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $userTemplate = UserTemplate::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->firstOrFail();
        
        $template = $userTemplate->template;
        
        return view('templates.editor', compact('template', 'userTemplate'));
    }
    
    /**
     * Удалить шаблон пользователя.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $userTemplate = UserTemplate::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->firstOrFail();
        
        // Удаляем файл обложки, если он существует
        if ($userTemplate->cover_path) {
            $coverPath = public_path('storage/template_covers/' . $userTemplate->cover_path);
            if (file_exists($coverPath)) {
                unlink($coverPath);
            }
        }
        
        $userTemplate->delete();
        
        return redirect()->route('user.templates')->with('status', 'Шаблон успешно удален');
    }
}

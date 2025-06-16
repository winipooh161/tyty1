<?php

namespace App\Http\Controllers;

use App\Models\UserTemplate;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $this->middleware('role:client,admin')->except(['showUserTemplates']);
    }

    /**
     * Показать список шаблонов текущего пользователя.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Получаем шаблоны, созданные текущим пользователем (где user_id = текущий пользователь)
        $userCreatedTemplates = UserTemplate::where('user_id', Auth::id())->get();
        
        // Получаем шаблоны, предназначенные для текущего пользователя (где target_user_id = текущий пользователь)
        $userTargetedTemplates = UserTemplate::where('target_user_id', Auth::id())->get();
        
        // Объединяем коллекции
        $userTemplates = $userCreatedTemplates->merge($userTargetedTemplates);
        
        $folders = TemplateFolder::where('user_id', Auth::id())
            ->orderBy('display_order')
            ->get();
            
        return view('user.templates.index', compact('userTemplates', 'folders'));
    }

    /**
     * Показать список шаблонов указанного пользователя для других пользователей.
     *
     * @param  int  $userId
     * @return \Illuminate\View\View
     */
    public function showUserTemplates($userId)
    {
        // Проверяем существование пользователя
        $user = User::findOrFail($userId);

        // Если пользователь просматривает свои шаблоны, перенаправляем его на стандартный маршрут
        if (Auth::id() == $userId) {
            return redirect()->route('user.templates');
        }

        // Получаем только опубликованные шаблоны, созданные указанным пользователем
        $userTemplates = UserTemplate::where('user_id', $userId)
            ->where('status', 'published')  // Только опубликованные шаблоны
            ->get();
        
        $folders = TemplateFolder::where('user_id', $userId)
            ->orderBy('display_order')
            ->get();
            
        // Передаем флаг для шаблона, что просмотр идет от другого пользователя
        $isOwner = false;
        $profileUser = $user;
            
        return view('user.templates.index', compact('userTemplates', 'folders', 'isOwner', 'profileUser'));
    }

    /**
     * Показать отдельный пользовательский шаблон.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $userTemplate = UserTemplate::where('id', $id)
            ->where('user_id', Auth::id())
            ->with('template.category')
            ->firstOrFail();
            
        return view('user.templates.show', compact('userTemplate'));
    }

    /**
     * Редактировать существующий пользовательский шаблон.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $userTemplate = UserTemplate::where('id', $id)
            ->where('user_id', Auth::id())
            ->with('template')
            ->firstOrFail();
            
        return view('templates.editor', [
            'template' => $userTemplate->template,
            'userTemplate' => $userTemplate
        ]);
    }

    /**
     * Удалить пользовательский шаблон.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $userTemplate = UserTemplate::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $userTemplate->delete();
        
        return redirect()->route('user.templates')->with('status', 'Шаблон успешно удален!');
    }

    /**
     * Опубликовать шаблон пользователя.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function publish($id)
    {
        $userTemplate = UserTemplate::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $userTemplate->update(['status' => 'published']);
        
        return redirect()->back()->with('status', 'Шаблон успешно опубликован!');
    }
    
    /**
     * Отменить публикацию шаблона пользователя.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unpublish($id)
    {
        $userTemplate = UserTemplate::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $userTemplate->update(['status' => 'draft']);
        
        return redirect()->back()->with('status', 'Публикация шаблона отменена!');
    }
    
    /**
     * Переместить шаблон в папку.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function moveToFolder(Request $request, $id)
    {
        $userTemplate = UserTemplate::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'folder_id' => 'nullable|exists:template_folders,id',
        ]);

        // Проверяем, что папка принадлежит текущему пользователю, если указана
        if ($request->folder_id) {
            $folder = TemplateFolder::where('id', $request->folder_id)
                ->where('user_id', Auth::id())
                ->firstOrFail();
        }

        try {
            $userTemplate->update(['folder_id' => $request->folder_id]);
        } catch (\Exception $e) {
            // В случае ошибки выводим детальное сообщение в логи
            \Log::error('Ошибка при перемещении шаблона: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ошибка при перемещении шаблона. Пожалуйста, свяжитесь с администратором.');
        }

        return redirect()->back()->with('status', 'Шаблон успешно перемещен!');
    }
}

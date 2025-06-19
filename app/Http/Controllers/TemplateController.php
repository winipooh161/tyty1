<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
class TemplateController extends Controller
{
    /**
     * Показать список категорий шаблонов.
     *
     * @return \Illuminate\View\View
     */
    public function categories()
    {
        // Получаем все активные категории шаблонов
     

  

        return view('templates.categories');
    }

    /**
     * Показать шаблоны определенной категории.
     *
     * @param  string  $slug
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index($slug)
    {
        $category = TemplateCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
            
        // Проверяем статус пользователя
        $user = Auth::user();
        
        // Если пользователь не VIP, перенаправляем на редактирование стандартного шаблона
        if (!$user->isVip()) {
            // Ищем стандартный шаблон категории
            $defaultTemplate = Template::where('template_category_id', $category->id)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
                
            // Если найден, перенаправляем на страницу редактирования
            if ($defaultTemplate) {
                return redirect()->route('client.templates.editor', $defaultTemplate->id);
            }
        }
        
        // Для VIP-пользователей показываем все шаблоны категории и личные шаблоны
        $templatesQuery = Template::where('template_category_id', $category->id)
            ->where('is_active', true)
            ->where(function($query) use ($user) {
                $query->whereNull('target_user_id')
                      ->orWhere('target_user_id', $user->id);
            });
        
        $templates = $templatesQuery->get();
        
        return view('templates.index', compact('category', 'templates'));
    }

    /**
     * Показать страницу просмотра шаблона.
     *
     * @param  string  $categorySlug
     * @param  string  $templateSlug
     * @return \Illuminate\View\View
     */
    public function show($categorySlug, $templateSlug)
    {
        $category = TemplateCategory::where('slug', $categorySlug)
            ->where('is_active', true)
            ->firstOrFail();
            
        // Проверяем доступ пользователя к шаблону
        $user = Auth::user();
        
        $templateQuery = Template::where('slug', $templateSlug)
            ->where('template_category_id', $category->id)
            ->where('is_active', true)
            ->where(function($query) use ($user) {
                $query->whereNull('target_user_id')
                      ->orWhere('target_user_id', $user->id);
            });
            
        $template = $templateQuery->firstOrFail();
            
        return view('templates.show', compact('category', 'template'));
    }

    /**
     * Создать новый шаблон на основе существующего.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function createNew($id)
    {
        $template = Template::findOrFail($id);
        
        // Принудительно создаем новый шаблон
        $userTemplate = null;
        
        // Получаем данные из сессии, если они есть
        $media_editor_file = session('media_editor_file');
        $media_editor_type = session('media_editor_type');
        
        // Сбрасываем данные об обложке, если они сохранены в сессии
        session()->forget('cover_preview');
        
        // Загружаем список VIP-пользователей
        $vipUsers = User::where('status', 'vip')->orderBy('name')->get();
        
        // Передаем параметр is_new_template = true
        $is_new_template = true;
        
        return view('templates.editor', compact('template', 'userTemplate', 'is_new_template', 'vipUsers', 'media_editor_file', 'media_editor_type'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $templates = Template::with(['category', 'targetUser'])->orderBy('display_order')->get();
        
        return view('admin.templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = TemplateCategory::where('is_active', true)->orderBy('name')->get();
        $vipUsers = User::where('status', 'vip')->orderBy('name')->get();
        
        return view('admin.templates.create', compact('categories', 'vipUsers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_category_id' => 'required|exists:template_categories,id',
            'html_content' => 'required|string',
            'editable_fields' => 'nullable|json',
            'preview_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'target_user_id' => 'nullable|exists:users,id',
        ]);
        
        // Оптимизируем HTML контент перед сохранением
        $validatedData['html_content'] = $this->optimizeHtmlContent($validatedData['html_content']);
        
        // Генерируем slug
        $validatedData['slug'] = Str::slug($validatedData['name']);
        
        // Преобразуем JSON строку в массив
        if (isset($validatedData['editable_fields'])) {
            $validatedData['editable_fields'] = json_decode($validatedData['editable_fields'], true);
        }
        
        // Обработка чекбокса is_active (если не отмечен, то в запросе его не будет)
        $validatedData['is_active'] = $request->has('is_active');
        
        // Обработка чекбокса is_default (если не отмечен, то в запросе его не будет)
        $validatedData['is_default'] = $request->has('is_default');
        
        // Обработка загрузки превью
        if ($request->hasFile('preview_image')) {
            $previewImage = $request->file('preview_image');
            $filename = time() . '_' . Str::slug($validatedData['name']) . '.' . $previewImage->getClientOriginalExtension();
            
            $previewImage->storeAs('template_previews', $filename, 'public');
            $validatedData['preview_image'] = $filename;
        }
        
        // Создаем шаблон
        $template = Template::create($validatedData);
        
        // Если шаблон установлен как стандартный, снимаем этот статус с других шаблонов категории
        if ($validatedData['is_default']) {
            Template::where('template_category_id', $validatedData['template_category_id'])
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }
        
        return redirect()->route('admin.templates.index')->with('success', 'Шаблон успешно создан.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $template = Template::findOrFail($id);
        $categories = TemplateCategory::where('is_active', true)->orderBy('name')->get();
        $vipUsers = User::where('status', 'vip')->orderBy('name')->get();
        
        return view('admin.templates.edit', compact('template', 'categories', 'vipUsers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Template $template)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_category_id' => 'required|exists:template_categories,id',
            'html_content' => 'required|string',
            'editable_fields' => 'nullable|json',
            'preview_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'target_user_id' => 'nullable|exists:users,id',
        ]);

        // Оптимизируем HTML контент перед сохранением
        $validatedData['html_content'] = $this->optimizeHtmlContent($validatedData['html_content']);

        // Преобразуем JSON строку в массив
        if (isset($validatedData['editable_fields'])) {
            $validatedData['editable_fields'] = json_decode($validatedData['editable_fields'], true);
        }

        // Обработка чекбокса is_active (если не отмечен, то в запросе его не будет)
        $validatedData['is_active'] = $request->has('is_active');
        
        // Обработка чекбокса is_default (если не отмечен, то в запросе его не будет)
        $validatedData['is_default'] = $request->has('is_default');

        // Обработка изменения категории шаблона
        $categoryChanged = $template->template_category_id != $validatedData['template_category_id'];
        
        // Если категория изменилась и шаблон был стандартным, сбрасываем флаг
        if ($categoryChanged && $template->is_default) {
            $validatedData['is_default'] = false;
        }

        // Обработка загрузки превью
        if ($request->hasFile('preview_image')) {
            // Удаляем старое превью, если есть
            if ($template->preview_image) {
                Storage::disk('public')->delete('template_previews/' . $template->preview_image);
            }
            
            // Сохраняем новое превью
            $previewImage = $request->file('preview_image');
            $filename = time() . '_' . Str::slug($validatedData['name']) . '.' . $previewImage->getClientOriginalExtension();
            
            $previewImage->storeAs('template_previews', $filename, 'public');
            $validatedData['preview_image'] = $filename;
        }

        // Обновляем slug если изменилось название
        if ($template->name !== $validatedData['name']) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        // Обновляем шаблон
        $template->update($validatedData);
        
        // Если шаблон установлен как стандартный, снимаем этот статус с других шаблонов категории
        if ($validatedData['is_default']) {
            Template::where('template_category_id', $validatedData['template_category_id'])
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }
        
        return redirect()->route('admin.templates.index')->with('success', 'Шаблон успешно обновлен.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        
        // Удаление изображения превью, если оно есть
        if ($template->preview_image) {
            Storage::disk('public')->delete('template_previews/' . $template->preview_image);
        }
        
        $template->delete();
        
        return redirect()->route('admin.templates.index')
            ->with('success', 'Шаблон успешно удален!');
    }

    /**
     * Оптимизирует HTML-контент шаблона
     * 
     * @param string $htmlContent
     * @return string
     */
    protected function optimizeHtmlContent($htmlContent)
    {
        // Больше не выполняем минификацию HTML, просто возвращаем исходный код
        return $htmlContent;
    }
}
      
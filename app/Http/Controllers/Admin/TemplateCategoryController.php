<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateCategoryController extends Controller
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
        $categories = TemplateCategory::orderBy('display_order')->get();
        
        return view('admin.template-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.template-categories.create');
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);
        
        $slug = Str::slug($validatedData['name']);
        
        // Проверка уникальности slug
        $count = 0;
        $originalSlug = $slug;
        while (TemplateCategory::where('slug', $slug)->exists()) {
            $count++;
            $slug = "{$originalSlug}-{$count}";
        }
        
        // Обработка изображения
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            
            if (!Storage::disk('public')->exists('category_images')) {
                Storage::disk('public')->makeDirectory('category_images');
            }
            
            $image->storeAs('category_images', $filename, 'public');
            $validatedData['image'] = $filename;
        }
        
        // Создание категории
        TemplateCategory::create([
            'name' => $validatedData['name'],
            'slug' => $slug,
            'description' => $validatedData['description'] ?? null,
            'image' => $validatedData['image'] ?? null,
            'is_active' => $request->has('is_active'),
            'display_order' => $validatedData['display_order'] ?? 0,
        ]);
        
        return redirect()->route('admin.template-categories.index')
            ->with('success', 'Категория успешно создана!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $category = TemplateCategory::findOrFail($id);
        return view('admin.template-categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $category = TemplateCategory::findOrFail($id);
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);
        
        // Обработка изображения
        if ($request->hasFile('image')) {
            // Удаление старого изображения, если оно есть
            if ($category->image) {
                Storage::disk('public')->delete('category_images/' . $category->image);
            }
            
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            
            if (!Storage::disk('public')->exists('category_images')) {
                Storage::disk('public')->makeDirectory('category_images');
            }
            
            $image->storeAs('category_images', $filename, 'public');
            $validatedData['image'] = $filename;
        }
        
        // Обновление категории
        $category->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'image' => $validatedData['image'] ?? $category->image,
            'is_active' => $request->has('is_active'),
            'display_order' => $validatedData['display_order'] ?? 0,
        ]);
        
        return redirect()->route('admin.template-categories.index')
            ->with('success', 'Категория успешно обновлена!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $category = TemplateCategory::findOrFail($id);
        
        // Удаление изображения категории
        if ($category->image) {
            Storage::disk('public')->delete('category_images/' . $category->image);
        }
        
        $category->delete();
        
        return redirect()->route('admin.template-categories.index')
            ->with('success', 'Категория успешно удалена!');
    }
}

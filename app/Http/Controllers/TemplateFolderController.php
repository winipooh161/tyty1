<?php

namespace App\Http\Controllers;

use App\Models\TemplateFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateFolderController extends Controller
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
     * Создать новую папку.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:50',
        ]);

        TemplateFolder::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'color' => $request->color ?? '#6c757d',
            'display_order' => TemplateFolder::where('user_id', Auth::id())->count(),
        ]);

        return redirect()->route('user.templates')->with('status', 'Папка успешно создана!');
    }

    /**
     * Обновить существующую папку.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $folder = TemplateFolder::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:50',
        ]);

        $folder->update([
            'name' => $request->name,
            'color' => $request->color ?? '#6c757d',
        ]);

        return redirect()->route('user.templates')->with('status', 'Папка успешно обновлена!');
    }

    /**
     * Удалить папку.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $folder = TemplateFolder::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Освобождаем шаблоны от удаляемой папки (устанавливаем folder_id в null)
        $folder->templates()->update(['folder_id' => null]);

        $folder->delete();

        return redirect()->route('user.templates')->with('status', 'Папка успешно удалена!');
    }
}

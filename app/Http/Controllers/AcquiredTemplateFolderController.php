<?php

namespace App\Http\Controllers;

use App\Models\AcquiredTemplateFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcquiredTemplateFolderController extends Controller
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
     * Создать новую папку для полученных шаблонов.
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

        AcquiredTemplateFolder::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'color' => $request->color ?? '#6c757d',
            'display_order' => AcquiredTemplateFolder::where('user_id', Auth::id())->count(),
        ]);

        return redirect()->route('user.acquired-templates')->with('status', 'Папка успешно создана!');
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
        $folder = AcquiredTemplateFolder::where('id', $id)
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

        return redirect()->route('home')->with('status', 'Папка успешно обновлена!');
    }

    /**
     * Удалить папку.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $folder = AcquiredTemplateFolder::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Освобождаем шаблоны от удаляемой папки
        $folder->acquiredTemplates()->update(['folder_id' => null]);

        $folder->delete();

        return redirect()->route('home')->with('status', 'Папка успешно удалена!');
    }
}

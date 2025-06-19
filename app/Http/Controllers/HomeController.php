<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AcquiredTemplate;
use App\Models\AcquiredTemplateFolder;

class HomeController extends Controller
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
        
        return view('home', compact('acquiredTemplates', 'acquiredFolders', 'acquiredTemplatesCount', 'userTemplatesCount'));
    }
}

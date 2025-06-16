<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AcquiredTemplate;

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

        // Загружаем папки для полученных шаблонов
        $acquiredFolders = Auth::user()->acquiredTemplateFolders()
            ->orderBy('display_order')
            ->get();

        return view('home', compact('acquiredTemplates', 'acquiredFolders'));
    }
}

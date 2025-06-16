<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Применяем middleware ко всем методам, разрешая доступ и администраторам, и клиентам
        $this->middleware('role:client,admin');
    }

    /**
     * Show the client dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Загружаем полученные пользователем шаблоны
        $acquiredTemplates = Auth::user()->acquiredTemplates()
            ->with(['userTemplate.user', 'userTemplate.template.category'])
            ->latest('acquired_at') // Возвращаем сортировку по acquired_at после успешной миграции
            ->get();

        return view('home', compact('acquiredTemplates'));
    }
}

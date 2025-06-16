<div class="sidebar d-none d-md-flex flex-column flex-shrink-0 bg-white border-end">
    <div class="sidebar-header p-3 border-bottom">
        <a href="{{ url('/') }}" class="d-flex align-items-center justify-content-center text-decoration-none">
            <span class="fs-4 fw-semibold text-primary">{{ config('app.name', 'Laravel') }}</span>
        </a>
    </div>
    
    <div class="user-profile text-center my-3 px-3">
        <div class="avatar-container mb-2">
            <img src="{{ Auth::user()->avatar ? asset('storage/avatars/'.Auth::user()->avatar) : asset('images/default-avatar.jpg') }}" 
                 class="rounded-circle avatar-img shadow" alt="Аватар">
        </div>
        <div class="fw-medium">{{ Auth::user()->name }}</div>
        <small class="text-muted">{{ Auth::user()->email }}</small>
    </div>
    
    <div class="px-3 mb-auto">
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item">
                <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-house me-2"></i>
                    Главная
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('profile.show') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-person me-2"></i>
                    Профиль
                </a>
            </li>
            
            @if(Auth::user()->role === 'admin')
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Админ-панель
                </a>
            </li>
            <li class="nav-item">
                <hr class="my-2">
                <div class="sidebar-heading px-1 text-muted small">
                    <span>УПРАВЛЕНИЕ</span>
                </div>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.template-categories.index') }}" class="nav-link {{ request()->routeIs('admin.template-categories.*') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-tags me-2"></i>
                    Категории
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.templates.index') }}" class="nav-link {{ request()->routeIs('admin.templates.*') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Шаблоны
                </a>
            </li>
            <li class="nav-item">
                <hr class="my-2">
                <div class="sidebar-heading px-1 text-muted small">
                    <span>ФУНКЦИИ КЛИЕНТА</span>
                </div>
            </li>
            @endif
            
            @if(Auth::user()->role === 'client' || Auth::user()->role === 'admin')
            <li class="nav-item">
                <a href="{{ route('create.template') }}" class="nav-link {{ request()->routeIs('create.template') || request()->routeIs('media.editor') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-grid-3x3-gap me-2"></i>
                    Создать шаблон
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('user.templates') }}" class="nav-link {{ request()->routeIs('user.templates*') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-collection me-2"></i>
                    Мои шаблоны
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('client.dashboard') }}" class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-briefcase me-2"></i>
                    Кабинет клиента
                </a>
            </li>
            @endif
        </ul>
    </div>
    
    <div class="p-3 border-top">
        <a href="{{ route('logout') }}" class="btn btn-outline-danger w-100"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="bi bi-box-arrow-right me-2"></i>
            Выйти
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</div>

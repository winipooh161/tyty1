@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12">
            <h2 class="mb-4">Панель администратора</h2>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill me-2"></i> Добро пожаловать в панель администратора! Здесь вы можете управлять всеми аспектами сайта.
            </div>
            
            <h4 class="mb-3">Административные функции</h4>
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-tags me-2"></i> Категории шаблонов</h5>
                            <p class="card-text">Управление категориями шаблонов для пользователей.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="{{ route('admin.template-categories.index') }}" class="btn btn-primary">Управление категориями</a>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-earmark-text me-2"></i> Шаблоны</h5>
                            <p class="card-text">Управление шаблонами, доступными для клиентов.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="{{ route('admin.templates.index') }}" class="btn btn-primary">Управление шаблонами</a>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-people me-2"></i> Пользователи</h5>
                            <p class="card-text">Управление учетными записями пользователей.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-primary">Управление пользователями</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <h4 class="mb-3">Функции клиента <span class="badge bg-secondary">Доступно администраторам</span></h4>
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-grid-3x3-gap me-2"></i> Категории шаблонов</h5>
                            <p class="card-text">Просмотр доступных категорий шаблонов для редактирования.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="{{ route('client.templates.categories') }}" class="btn btn-outline-primary">Просмотр категорий</a>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-collection me-2"></i> Мои шаблоны</h5>
                            <p class="card-text">Управление сохраненными шаблонами.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="{{ route('user.templates') }}" class="btn btn-outline-primary">Мои шаблоны</a>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-briefcase me-2"></i> Кабинет клиента</h5>
                            <p class="card-text">Доступ к кабинету клиента для проверки функциональности.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="{{ route('client.dashboard') }}" class="btn btn-outline-primary">Перейти в кабинет клиента</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Последние действия</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Функционал журнала действий будет доступен в следующих обновлениях.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Статистика</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Функционал статистики будет доступен в следующих обновлениях.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Управление пользователями</h2>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Назад к панели
                </a>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Список пользователей ({{ $users->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Аватар</th>
                                        <th>Имя</th>
                                        <th>Email</th>
                                        <th>Роль</th>
                                        <th>Статус</th>
                                        <th>Дата регистрации</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>
                                            @if($user->avatar)
                                                <img src="{{ asset($user->avatar) }}" alt="Аватар" class="rounded-circle" width="40" height="40">
                                            @else
                                                <img src="{{ asset('images/default-avatar.png') }}" alt="Аватар" class="rounded-circle" width="40" height="40">
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $user->name }}</strong>
                                            @if($user->phone)
                                                <br><small class="text-muted">{{ $user->phone }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $user->email }}
                                            @if($user->email_verified_at)
                                                <i class="bi bi-check-circle-fill text-success" title="Email подтвержден"></i>
                                            @else
                                                <i class="bi bi-exclamation-circle-fill text-warning" title="Email не подтвержден"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge 
                                                @if($user->role === 'admin') bg-danger 
                                                @elseif($user->role === 'client') bg-primary 
                                                @else bg-secondary 
                                                @endif">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                @if($user->status === 'vip') bg-warning 
                                                @elseif($user->status === 'active') bg-success 
                                                @else bg-secondary 
                                                @endif">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $user->created_at->format('d.m.Y H:i') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="Редактировать">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                @if($user->id !== auth()->id())
                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Удалить" onclick="confirmDelete({{ $user->id }})">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-people display-1 text-muted"></i>
                            <h5 class="mt-3">Пользователи не найдены</h5>
                            <p class="text-muted">В системе пока нет зарегистрированных пользователей.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этого пользователя?</p>
                <p class="text-danger"><small>Это действие необратимо!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function confirmDelete(userId) {
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/admin/users/${userId}`;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>
@endsection

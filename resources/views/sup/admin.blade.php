@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-coins text-warning me-2"></i>
                        Управление SUP - Администратор
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Пользователь</th>
                                        <th>Email</th>
                                        <th class="text-end">Баланс</th>
                                        <th class="text-end">Заработано</th>
                                        <th class="text-end">Потрачено</th>
                                        <th class="text-center">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($user->avatar)
                                                        <img src="{{ asset($user->avatar) }}" 
                                                             class="rounded-circle me-2" 
                                                             width="32" height="32">
                                                    @endif
                                                    <strong>{{ $user->name }}</strong>
                                                </div>
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td class="text-end">
                                                <strong>{{ number_format(optional($user->supBalance)->balance ?? 0, 0) }} SUP</strong>
                                            </td>
                                            <td class="text-end text-success">
                                                {{ number_format(optional($user->supBalance)->total_earned ?? 0, 0) }}
                                            </td>
                                            <td class="text-end text-danger">
                                                {{ number_format(optional($user->supBalance)->total_spent ?? 0, 0) }}
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" 
                                                            class="btn btn-success btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#addSupModal" 
                                                            data-user-id="{{ $user->id }}" 
                                                            data-user-name="{{ $user->name }}">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#subtractSupModal" 
                                                            data-user-id="{{ $user->id }}" 
                                                            data-user-name="{{ $user->name }}">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{ $users->links() }}
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Пользователи не найдены</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для начисления SUP -->
<div class="modal fade" id="addSupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('sup.admin.add') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Начислить SUP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="addUserId">
                    <p>Начислить SUP пользователю: <strong id="addUserName"></strong></p>
                    
                    <div class="mb-3">
                        <label for="addAmount" class="form-label">Сумма (SUP)</label>
                        <input type="number" class="form-control" id="addAmount" name="amount" min="0.01" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="addDescription" class="form-label">Описание</label>
                        <input type="text" class="form-control" id="addDescription" name="description" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Начислить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для списания SUP -->
<div class="modal fade" id="subtractSupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('sup.admin.subtract') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Списать SUP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="subtractUserId">
                    <p>Списать SUP у пользователя: <strong id="subtractUserName"></strong></p>
                    
                    <div class="mb-3">
                        <label for="subtractAmount" class="form-label">Сумма (SUP)</label>
                        <input type="number" class="form-control" id="subtractAmount" name="amount" min="0.01" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subtractDescription" class="form-label">Описание</label>
                        <input type="text" class="form-control" id="subtractDescription" name="description" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Списать</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Обработка модальных окон
document.addEventListener('DOMContentLoaded', function() {
    // Модальное окно начисления
    const addSupModal = document.getElementById('addSupModal');
    addSupModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        const userName = button.getAttribute('data-user-name');
        
        document.getElementById('addUserId').value = userId;
        document.getElementById('addUserName').textContent = userName;
    });
    
    // Модальное окно списания
    const subtractSupModal = document.getElementById('subtractSupModal');
    subtractSupModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        const userName = button.getAttribute('data-user-name');
        
        document.getElementById('subtractUserId').value = userId;
        document.getElementById('subtractUserName').textContent = userName;
    });
});
</script>
@endsection

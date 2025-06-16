@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h2>Управление шаблонами</h2>
        <a href="{{ route('admin.templates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Добавить шаблон
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="80">ID</th>
                            <th width="100">Превью</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Описание</th>
                            <th>VIP-пользователь</th>
                            <th>Поля для ред.</th>
                            <th>Порядок</th>
                            <th>Статус</th>
                            <th width="200">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->id }}</td>
                            <td>
                                @if($template->preview_image)
                                <img src="{{ asset('storage/template_previews/'.$template->preview_image) }}" 
                                     alt="{{ $template->name }}" class="img-thumbnail" style="max-height: 50px;">
                                @else
                                <span class="text-muted">Нет</span>
                                @endif
                            </td>
                            <td>{{ $template->name }}</td>
                            <td>{{ $template->category->name }}</td>
                            <td>{{ Str::limit($template->description, 30) }}</td>
                            <td>
                                @if($template->target_user_id && $template->targetUser)
                                <span class="badge bg-warning text-dark">
                                    {{ $template->targetUser->name }}
                                </span>
                                @else
                                <span class="text-muted">Для всех</span>
                                @endif
                            </td>
                            <td>
                                @if($template->editable_fields && count($template->editable_fields) > 0)
                                <span class="badge bg-info">{{ count($template->editable_fields) }}</span>
                                @else
                                <span class="badge bg-secondary">0</span>
                                @endif
                            </td>
                            <td>{{ $template->display_order }}</td>
                            <td>
                                @if($template->is_active)
                                <span class="badge bg-success">Активен</span>
                                @else
                                <span class="badge bg-danger">Неактивен</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('client.templates.show', [$template->category->slug, $template->slug]) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Просмотр" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <a href="{{ route('admin.templates.edit', $template->id) }}" 
                                       class="btn btn-sm btn-outline-secondary" title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" data-bs-target="#deleteTemplateModal"
                                            data-id="{{ $template->id }}"
                                            data-name="{{ $template->name }}"
                                            title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">Шаблоны не найдены</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для удаления -->
<div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-labelledby="deleteTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTemplateModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы действительно хотите удалить шаблон <strong id="template-name"></strong>?</p>
                <p class="text-danger">Это также удалит все пользовательские версии этого шаблона!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form id="delete-template-form" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('[data-bs-target="#deleteTemplateModal"]');
    const templateNameEl = document.getElementById('template-name');
    const deleteForm = document.getElementById('delete-template-form');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-id');
            const templateName = this.getAttribute('data-name');
            
            templateNameEl.textContent = templateName;
            deleteForm.action = `{{ url('admin/templates') }}/${templateId}`;
        });
    });
});
</script>
@endsection

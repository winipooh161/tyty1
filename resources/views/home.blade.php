@extends('layouts.app')

@section('content')


    <!-- Отображение полученных шаблонов в стиле страницы my-templates -->
    @php
        $acquiredTemplates = Auth::user()->acquiredTemplates()
            ->with('userTemplate.template.category', 'userTemplate.user', 'folder')
            ->latest('created_at')
            ->get();
    @endphp
    
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if($acquiredTemplates->count() > 0)
   
    
    <!-- Навигация по вкладкам -->
    <ul class="nav nav-tabs card-header-tabs mb-3" id="templateTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button"
                role="tab" aria-controls="all" aria-selected="true">
                <i class="bi bi-grid me-1"></i>
                <span class="tab-text">Все</span>
            </button>
        </li>
        
        
        <!-- Вкладки для папок -->
        @foreach($acquiredFolders as $folder)
            <li class="nav-item" role="presentation">
                <button class="nav-link folder-tab d-flex align-items-center" 
                    id="folder-{{ $folder->id }}-tab"
                    data-bs-toggle="tab" 
                    data-bs-target="#folder-{{ $folder->id }}" 
                    type="button" role="tab"
                    aria-controls="folder-{{ $folder->id }}" 
                    aria-selected="false">
                   
                    <span class="ms-1">{{ $folder->name }}</span>
                    
                   
                </button>
            </li>
        @endforeach
        
        <!-- Кнопка создания новой папки -->
        <li class="nav-item">
            <button type="button" class="btn btn-sm text-primary border-0" 
                data-bs-toggle="modal" data-bs-target="#newFolderModal">
                <i class="bi bi-folder-plus"></i>
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="templateTabsContent">
        <!-- Все шаблоны -->
        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
            <div class="row g-2">
                @foreach($acquiredTemplates as $acquisition)
                    @if($acquisition->userTemplate) <!-- Проверяем, что шаблон существует -->
                    <div class="col-4">
                        <a href="{{ route('public.template', $acquisition->userTemplate->id) }}" class="text-decoration-none template-card-link">
                            <div class="card h-100 template-card">
                                <!-- Превью карточки -->
                                <div class="card-img-top template-preview">
                                    @if($acquisition->userTemplate->cover_path)
                                        @if($acquisition->userTemplate->cover_type === 'video')
                                            <video src="{{ asset('storage/'.$acquisition->userTemplate->cover_path) }}" 
                                                class="img-fluid" autoplay loop muted></video>
                                        @else
                                            <img src="{{ asset('storage/'.$acquisition->userTemplate->cover_path) }}" 
                                                alt="{{ $acquisition->userTemplate->name }}" class="img-fluid">
                                        @endif
                                    @else
                                        <div class="default-preview d-flex align-items-center justify-content-center">
                                            <i class="bi bi-file-earmark-text template-icon"></i>
                                        </div>
                                    @endif
                                    
                                    <!-- Статус шаблона -->
                                    <div class="template-status">
                                        @if($acquisition->status === 'active')
                                            <span class="badge bg-success status-badge" title="Активный">✓</span>
                                        @elseif($acquisition->status === 'used')
                                            <span class="badge bg-secondary status-badge" title="Использованный">✓</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Индикатор папки, если шаблон в папке -->
                                    @if($acquisition->folder_id)
                                    <div class="template-folder-indicator">
                                        <span class="badge rounded-pill" style="background-color: {{ $acquisition->folder->color }};">
                                            <i class="bi bi-folder-fill"></i>
                                        </span>
                                    </div>
                                    @endif
                                    
                                    <!-- Отображение автора шаблона -->
                                    <div class="template-owner">
                                        <span class="badge bg-dark">
                                            Автор: {{ $acquisition->userTemplate->user->name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        
        <!-- Активные шаблоны -->
        <div class="tab-pane fade" id="active" role="tabpanel" aria-labelledby="active-tab">
            <div class="row g-2">
                @foreach($acquiredTemplates->where('status', 'active') as $acquisition)
                    @if($acquisition->userTemplate)
                    <div class="col-4">
                        <a href="{{ route('public.template', $acquisition->userTemplate->id) }}" class="text-decoration-none template-card-link">
                            <div class="card h-100 template-card">
                                <div class="card-img-top template-preview">
                                    @if($acquisition->userTemplate->cover_path)
                                        @if($acquisition->userTemplate->cover_type === 'video')
                                            <video src="{{ asset('storage/'.$acquisition->userTemplate->cover_path) }}" 
                                                class="img-fluid" autoplay loop muted></video>
                                        @else
                                            <img src="{{ asset('storage/'.$acquisition->userTemplate->cover_path) }}" 
                                                alt="{{ $acquisition->userTemplate->name }}" class="img-fluid">
                                        @endif
                                    @else
                                        <div class="default-preview d-flex align-items-center justify-content-center">
                                            <i class="bi bi-file-earmark-text template-icon"></i>
                                        </div>
                                    @endif
                                    
                                    <div class="template-status">
                                        <span class="badge bg-success status-badge" title="Активный">✓</span>
                                    </div>
                                    
                                    <!-- Индикатор папки, если шаблон в папке -->
                                    @if($acquisition->folder_id)
                                    <div class="template-folder-indicator">
                                        <span class="badge rounded-pill" style="background-color: {{ $acquisition->folder->color }};">
                                            <i class="bi bi-folder-fill"></i>
                                        </span>
                                    </div>
                                    @endif
                                    
                                    <!-- Отображение автора шаблона -->
                                    <div class="template-owner">
                                        <span class="badge bg-dark">
                                            Автор: {{ $acquisition->userTemplate->user->name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        
        <!-- Использованные шаблоны -->
        <div class="tab-pane fade" id="used" role="tabpanel" aria-labelledby="used-tab">
            <div class="row g-2">
                @foreach($acquiredTemplates->where('status', 'used') as $acquisition)
                    @if($acquisition->userTemplate)
                    <div class="col-4">
                        <a href="{{ route('public.template', $acquisition->userTemplate->id) }}" class="text-decoration-none template-card-link">
                            <div class="card h-100 template-card">
                                <div class="card-img-top template-preview">
                                    @if($acquisition->userTemplate->cover_path)
                                        @if($acquisition->userTemplate->cover_type === 'video')
                                            <video src="{{ asset('storage/'.$acquisition->userTemplate->cover_path) }}" 
                                                class="img-fluid" autoplay loop muted></video>
                                        @else
                                            <img src="{{ asset('storage/'.$acquisition->userTemplate->cover_path) }}" 
                                                alt="{{ $acquisition->userTemplate->name }}" class="img-fluid">
                                        @endif
                                    @else
                                        <div class="default-preview d-flex align-items-center justify-content-center">
                                            <i class="bi bi-file-earmark-text template-icon"></i>
                                        </div>
                                    @endif
                                    
                                    <div class="template-status">
                                        <span class="badge bg-secondary status-badge" title="Использованный">✓</span>
                                    </div>
                                    
                                    <!-- Индикатор папки, если шаблон в папке -->
                                    @if($acquisition->folder_id)
                                    <div class="template-folder-indicator">
                                        <span class="badge rounded-pill" style="background-color: {{ $acquisition->folder->color }};">
                                            <i class="bi bi-folder-fill"></i>
                                        </span>
                                    </div>
                                    @endif
                                    
                                    <!-- Отображение автора шаблона -->
                                    <div class="template-owner">
                                        <span class="badge bg-dark">
                                            Автор: {{ $acquisition->userTemplate->user->name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        
        <!-- Шаблоны по папкам -->
        @foreach($acquiredFolders as $folder)
            <div class="tab-pane fade" id="folder-{{ $folder->id }}" role="tabpanel" aria-labelledby="folder-{{ $folder->id }}-tab">
                <div class="row g-2">
                    @php $folderTemplates = $acquiredTemplates->where('folder_id', $folder->id); @endphp
                    
                    @if($folderTemplates->count() > 0)
                        @foreach($folderTemplates as $acquisition)
                            @if($acquisition->userTemplate)
                            <div class="col-4">
                                <a href="{{ route('public.template', $acquisition->userTemplate->id) }}" class="text-decoration-none template-card-link">
                                    <div class="card h-100 template-card">
                                        <!-- Превью карточки -->
                                        <div class="card-img-top template-preview">
                                            @if($acquisition->userTemplate->cover_path)
                                                @if($acquisition->userTemplate->cover_type === 'video')
                                                    <video src="{{ asset('storage/'.$acquisition->userTemplate->cover_path) }}" 
                                                        class="img-fluid" autoplay loop muted></video>
                                                @else
                                                    <img src="{{ asset('storage/'.$acquisition->userTemplate->cover_path) }}" 
                                                        alt="{{ $acquisition->userTemplate->name }}" class="img-fluid">
                                                @endif
                                            @else
                                                <div class="default-preview d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-file-earmark-text template-icon"></i>
                                                </div>
                                            @endif
                                            
                                            <!-- Статус шаблона -->
                                            <div class="template-status">
                                                @if($acquisition->status === 'active')
                                                    <span class="badge bg-success status-badge" title="Активный">✓</span>
                                                @elseif($acquisition->status === 'used')
                                                    <span class="badge bg-secondary status-badge" title="Использованный">✓</span>
                                                @endif
                                            </div>
                                            
                                            <div class="template-folder-indicator">
                                                <span class="badge rounded-pill" style="background-color: {{ $folder->color }};">
                                                    <i class="bi bi-folder-fill"></i>
                                                </span>
                                            </div>
                                            
                                            <!-- Отображение автора шаблона -->
                                            <div class="template-owner">
                                                <span class="badge bg-dark">
                                                    Автор: {{ $acquisition->userTemplate->user->name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            @endif
                        @endforeach
                    @else
                        <!-- Пустые карточки вместо сообщения о пустой папке -->
                        @for($i = 0; $i < 12; $i++)
                        <div class="col-4">
                            <div class="card h-100 template-card empty-template-card">
                                <div class="card-img-top template-preview empty-preview">
                                    <div class="empty-card-content">
                                        <!-- Пустая карточка -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endfor
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Модальное окно для создания новой папки -->
    <div class="modal fade" id="newFolderModal" tabindex="-1" aria-labelledby="newFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newFolderModalLabel">Создать новую папку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('acquired.folders.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="folder-name" class="form-label">Название папки</label>
                            <input type="text" class="form-control" id="folder-name" name="name" maxlength="55" required>
                        </div>
                        {{-- <div class="mb-3">
                            <label for="folder-color" class="form-label">Цвет папки</label>
                            <div class="d-flex">
                                <input type="color" class="form-control form-control-color" id="folder-color"
                                    name="color" value="#6c757d">
                                <span class="ms-2 d-flex align-items-center">
                                    <i class="bi bi-folder-fill" style="font-size: 1.5rem; color: #6c757d;"
                                        id="folder-color-preview"></i>
                                </span>
                            </div>
                        </div> --}}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Создать папку</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для редактирования папки -->
    <div class="modal fade" id="editFolderModal" tabindex="-1" aria-labelledby="editFolderModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFolderModalLabel">Изменить папку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-folder-form" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit-folder-name" class="form-label">Название папки</label>
                            <input type="text" class="form-control" id="edit-folder-name" name="name"  maxlength="55" required>
                        </div>
                        {{-- <div class="mb-3">
                            <label for="edit-folder-color" class="form-label">Цвет папки</label>
                            <div class="d-flex">
                                <input type="color" class="form-control form-control-color" id="edit-folder-color"
                                    name="color" value="#6c757d">
                                <span class="ms-2 d-flex align-items-center">
                                    <i class="bi bi-folder-fill" style="font-size: 1.5rem;"
                                        id="edit-folder-color-preview"></i>
                                </span>
                            </div>
                        </div> --}}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для удаления папки -->
    <div class="modal fade" id="deleteFolderModal" tabindex="-1" aria-labelledby="deleteFolderModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteFolderModalLabel">Удалить папку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить папку <strong id="delete-folder-name"></strong>?</p>
                    <p class="text-muted">Шаблоны из этой папки не будут удалены и станут доступны в общем списке.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <form id="delete-folder-form" action="" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для перемещения шаблона в папку -->
    <div class="modal fade" id="moveTemplateModal" tabindex="-1" aria-labelledby="moveTemplateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveTemplateModalLabel">Переместить шаблон</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="move-template-form" action="{{ route('acquired.templates.move') }}" method="POST">
                    @csrf
                    <input type="hidden" name="template_id" id="move-template-id" value="">
                    <div class="modal-body">
                        <p>Выберите папку для шаблона <strong id="move-template-name"></strong>:</p>

                        <div class="list-group">
                            <label class="list-group-item">
                                <input class="form-check-input me-2" type="radio" name="folder_id" value=""
                                    checked>
                                <i class="bi bi-folder text-muted me-2"></i> Без папки
                            </label>

                            @foreach($acquiredFolders as $folder)
                                <label class="list-group-item">
                                    <input class="form-check-input me-2" type="radio" name="folder_id"
                                        value="{{ $folder->id }}">
                                    <i class="bi bi-folder-fill me-2" style="color: {{ $folder->color }};"></i>
                                    {{ $folder->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Переместить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
    /* Стилизация видео в карточках */
    .template-preview video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }
    
    /* Стилизация пустого списка */
    .empty-folder {
        text-align: center;
        padding: 40px 0;
    }
    
    .empty-folder-icon {
        font-size: 4rem;
        color: #d1d9e6;
        margin-bottom: 15px;
    }
    
    /* Стили для владельца шаблона */
    .template-owner {
        position: absolute;
        bottom: 5px;
        left: 5px;
        z-index: 2;
    }
    
    .template-owner .badge {
        font-size: 10px;
        font-weight: normal;
        padding: 4px 8px;
        border-radius: 10px;
        opacity: 0.9;
        background-color: rgba(33, 37, 41, 0.8);
        color: #fff;
    }
    
    /* Стили для индикатора папки */
    .template-folder-indicator {
        position: absolute;
        top: 5px;
        left: 5px;
        z-index: 2;
    }
    
    .template-folder-indicator .badge {
        font-size: 10px;
        padding: 4px 8px;
        border-radius: 10px;
        opacity: 0.9;
    }
    
    /* Стили для пустых карточек */
    .empty-template-card {
        border: 2px dashed #e9ecef;
        background-color: #f8f9fa;
        opacity: 0.6;
        cursor: default;
        pointer-events: none;
    }
    
    .empty-preview {
        background-color: #ffffff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 150px;
    }
    
    .empty-card-content {
        width: 100%;
        height: 100%;
        background-color: transparent;
    }
    
    /* Улучшенные стили для кликабельных карточек */
    .template-card-link {
        display: block;
        color: inherit;
        text-decoration: none;
    }
    
    .template-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .template-card-link:hover .template-card {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .template-preview {
        aspect-ratio: 16/9;
        background-color: #f8f9fa;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Стили для статусных индикаторов */
    .status-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        padding: 4px 6px;
        border-radius: 50%;
        font-size: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        z-index: 2;
    }
    
    /* Убираем эффект hover для пустых карточек */
    .empty-template-card:hover {
        transform: none;
        box-shadow: none;
    }

    /* Адаптивные стили для вкладок */
    .nav-tabs {
        border-bottom: 1px solid #e9ecef;
    }
    
    .nav-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        color: #6c757d;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .nav-tabs .nav-link.active {
        border-bottom: 2px solid #6c8aec;
        color: #6c8aec;
        background-color: transparent;
    }
    
    .nav-tabs .nav-link:hover:not(.active) {
        border-color: rgba(108, 138, 236, 0.2);
    }
    
    @media (max-width: 767px) {
        .tab-text {
            display: none;
        }
        
        .nav-link .bi {
            margin-right: 0 !important;
            font-size: 1.2rem;
        }
        
        .nav-item {
            margin: 0 2px;
        }
        
        .nav-tabs .nav-link {
            padding: 0.4rem 0.6rem;
        }
        
        .folder-tab .dropdown {
            margin-left: 5px !important;
        }
        
        .template-card {
          
        }
        
        /* Улучшаем стили модальных окон на мобильных устройствах */
        .modal-dialog {
            margin: 0;
            max-width: 100%;
            height: 100%;
        }
        
        .modal-content {
            height: 100%;
            border-radius: 0;
            border: none;
        }
    }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            / Управление предпросмотром цвета папки при создании
            const folderColor = document.getElementById('folder-color');
            const folderColorPreview = document.getElementById('folder-color-preview');
            
            if (folderColor && folderColorPreview) {
                folderColor.addEventListener('input', function() {
                    folderColorPreview.style.color = this.value;
                });
            }
            
            / Управление предпросмотром цвета папки при редактировании
            const editFolderColor = document.getElementById('edit-folder-color');
            const editFolderColorPreview = document.getElementById('edit-folder-color-preview');
            
            if (editFolderColor && editFolderColorPreview) {
                editFolderColor.addEventListener('input', function() {
                    editFolderColorPreview.style.color = this.value;
                });
            }
            
            / Заполнение данных для редактирования папки
            document.querySelectorAll('[data-bs-target="#editFolderModal"]').forEach(element => {
                element.addEventListener('click', function() {
                    const folderId = this.getAttribute('data-folder-id');
                    const folderName = this.getAttribute('data-folder-name');
                    const folderColor = this.getAttribute('data-folder-color');
                    
                    document.getElementById('edit-folder-name').value = folderName;
                    document.getElementById('edit-folder-color').value = folderColor;
                    document.getElementById('edit-folder-color-preview').style.color = folderColor;
                    document.getElementById('edit-folder-form').action = `/client/acquired-folders/${folderId}`;
                });
            });
            
            / Заполнение данных для удаления папки
            document.querySelectorAll('[data-bs-target="#deleteFolderModal"]').forEach(element => {
                element.addEventListener('click', function() {
                    const folderId = this.getAttribute('data-folder-id');
                    const folderName = this.getAttribute('data-folder-name');
                    
                    document.getElementById('delete-folder-name').textContent = folderName;
                    document.getElementById('delete-folder-form').action = `/client/acquired-folders/${folderId}`;
                });
            });
            
            / Заполнение данных для перемещения шаблона
            document.querySelectorAll('[data-bs-target="#moveTemplateModal"]').forEach(element => {
                element.addEventListener('click', function() {
                    const templateId = this.getAttribute('data-template-id');
                    const templateName = this.getAttribute('data-template-name');
                    const currentFolder = this.getAttribute('data-current-folder');
                    
                    document.getElementById('move-template-id').value = templateId;
                    document.getElementById('move-template-name').textContent = templateName;
                    
                    / Устанавливаем текущую папку в форме
                    if (currentFolder) {
                        const radioButton = document.querySelector(
                            `input[name="folder_id"][value="${currentFolder}"]`);
                        if (radioButton) radioButton.checked = true;
                    } else {
                        document.querySelector('input[name="folder_id"][value=""]').checked = true;
                    }
                });
            });
        });
    </script>
    @else
        <!-- Пустые карточки вместо сообщения о пустом списке -->
        <div class="row g-2">
            @for($i = 0; $i < 12; $i++)
            <div class="col-4">
                <div class="card h-100 template-card empty-template-card">
                    <div class="card-img-top template-preview empty-preview">
                        <div class="empty-card-content">
                            <!-- Пустая карточка -->
                        </div>
                    </div>
                </div>
            </div>
            @endfor
        </div>
    @endif

@endsection
          
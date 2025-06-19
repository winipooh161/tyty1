@extends('layouts.app')

@section('content')
<div class="container py-4">
 
    <!-- Отладочная информация -->
    @if(config('app.debug'))
    <div class="alert alert-info mb-4">
        <h5>Диагностическая информация:</h5>
        <p>Авторизованный пользователь: {{ Auth::user()->name }} (ID: {{ Auth::user()->id }})</p>
        <p>Всего найденных шаблонов: {{ $templates->count() }}</p>
        <p>SQL запрос: <code>SELECT * FROM user_templates WHERE user_id = {{ Auth::user()->id }}</code></p>
    </div>
    @endif
<div class="">
       <div class="text-center overflow-hidden position-relative" style="padding: 15px">
        <div class="blur-gradient-effect">
            <img src="{{ isset($profileUser) ? ($profileUser->avatar ? asset('storage/avatars/'.$profileUser->avatar) : asset('images/default-avatar.jpg')) : (Auth::user()->avatar ? asset('storage/avatars/'.Auth::user()->avatar) : asset('images/default-avatar.jpg')) }}"
                class="profile-avatar" alt="Аватар">
                
        </div>
        <div class="abs_title_img">
         <h4 class="mt-3 user-name-display">{{ isset($profileUser) ? $profileUser->name : Auth::user()->name }}</h4>
         <p class="text-muted">{{ isset($profileUser) ? $profileUser->email : Auth::user()->email }}</p>
        </div>
       </div>
        @if (session('status'))
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        @endif
       
    </div>

    <!-- Навигация по вкладкам -->
    <ul class="nav nav-tabs mb-3" id="templateTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button"
                role="tab" aria-controls="all" aria-selected="true">
                <i class="bi bi-grid me-1"></i> Все
            </button>
        </li>
        
        @foreach($folders as $folder)
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="folder-{{ $folder->id }}-tab" data-bs-toggle="tab" 
                    data-bs-target="#folder-{{ $folder->id }}" type="button" role="tab"
                    aria-controls="folder-{{ $folder->id }}" aria-selected="false">
                    <i class="bi bi-folder-fill me-1" style="color: {{ $folder->color }};"></i> 
                    {{ $folder->name }}
                </button>
            </li>
        @endforeach
        
        <li class="nav-item ms-auto">
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newFolderModal">
                <i class="bi bi-folder-plus me-1"></i> Новая папка
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
            @if($templates->count() > 0)
                @include('user.templates.partials.template-list', ['templates' => $templates])
            @else
              
            @endif
        </div>
        
        @foreach($folders as $folder)
            <div class="tab-pane fade" id="folder-{{ $folder->id }}" role="tabpanel" aria-labelledby="folder-{{ $folder->id }}-tab">
                @php $folderTemplates = $templates->where('folder_id', $folder->id); @endphp
                
                @if($folderTemplates->count() > 0)
                    @include('user.templates.partials.template-list', ['templates' => $folderTemplates])
                @else
                    <div class="empty-folder text-center py-5">
                        <div class="empty-folder-icon">
                            <i class="bi bi-folder"></i>
                        </div>
                        <h4>Папка пуста</h4>
                        <p class="text-muted">В этой папке пока нет шаблонов</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>


@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-template');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const templateId = this.getAttribute('data-id');
                    const templateName = this.getAttribute('data-name');
                    
                    document.getElementById('template-name-to-delete').textContent = templateName;
                    document.getElementById('delete-template-form').action = `/client/my-templates/${templateId}`;
                });
            });

            // Добавляем обработчики для свайпа между папками
            const tabsContainer = document.querySelector('#myTabContent');
            let touchStartX = 0;
            let touchEndX = 0;
            let currentTabIndex = 0;
            
            // Получаем все вкладки
            const tabButtons = Array.from(document.querySelectorAll('#myTab button'));
            
            // Находим активную вкладку
            const activeTabButton = document.querySelector('#myTab button.active');
            if (activeTabButton) {
                currentTabIndex = tabButtons.indexOf(activeTabButton);
            }

            if (tabsContainer) {
                // Обработчик начала касания
                tabsContainer.addEventListener('touchstart', function(e) {
                    touchStartX = e.changedTouches[0].screenX;
                }, { passive: true });
                
                // Обработчик окончания касания
                tabsContainer.addEventListener('touchend', function(e) {
                    touchEndX = e.changedTouches[0].screenX;
                    handleGesture();
                }, { passive: true });
            }
            
            // Обработка жеста свайпа
            function handleGesture() {
                // Вычисляем минимальное расстояние для регистрации свайпа (20% от ширины экрана)
                const minSwipeDistance = window.innerWidth * 0.2;
                
                if (touchEndX - touchStartX > minSwipeDistance) {
                    // Свайп вправо - переключаемся на предыдущую вкладку
                    if (currentTabIndex > 0) {
                        currentTabIndex--;
                        activateTab(currentTabIndex);
                    }
                } else if (touchStartX - touchEndX > minSwipeDistance) {
                    // Свайп влево - переключаемся на следующую вкладку
                    if (currentTabIndex < tabButtons.length - 1) {
                        currentTabIndex++;
                        activateTab(currentTabIndex);
                    }
                }
            }
            
            // Активация вкладки с определенным индексом
            function activateTab(index) {
                if (tabButtons[index]) {
                    // Создаем новый экземпляр bootstrap.Tab и активируем его
                    const tab = new bootstrap.Tab(tabButtons[index]);
                    tab.show();
                    
                    // Прокручиваем вкладку в зону видимости
                    tabButtons[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    
                    // Обновляем текущий индекс
                    currentTabIndex = index;
                    
                    // Показываем визуальное подтверждение свайпа (небольшая анимация)
                    showSwipeAnimation(index);
                }
            }
            
            // Показываем визуальную индикацию свайпа
            function showSwipeAnimation(index) {
                const content = document.querySelector(tabButtons[index].dataset.bsTarget);
                if (content) {
                    // Добавляем класс для анимации и через 500мс удаляем его
                    content.classList.add('tab-swiped');
                    setTimeout(() => {
                        content.classList.remove('tab-swiped');
                    }, 500);
                }
            }
            
            // Добавляем стили для анимации свайпа
            const style = document.createElement('style');
            style.textContent = `
                .tab-pane.tab-swiped {
                    animation: tab-swipe-in 0.5s ease;
                }
                @keyframes tab-swipe-in {
                    0% { opacity: 0.7; transform: translateX(5%); }
                    100% { opacity: 1; transform: translateX(0); }
                }
                
                /* Улучшаем прокрутку для вкладок */
                .nav-tabs {
                  flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    overflow-y: hidden;
    scrollbar-width: thin;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
                }
                .nav-tabs::-webkit-scrollbar {
                    height: 4px;
                }
                .nav-tabs::-webkit-scrollbar-thumb {
                    background-color: rgba(0, 0, 0, 0.2);
                }
            `;
            document.head.append(style);
            
            // Обрабатываем изменение вкладки по клику для обновления индекса
            tabButtons.forEach((button, index) => {
                button.addEventListener('shown.bs.tab', function() {
                    currentTabIndex = index;
                });
            });
        });
    </script>

    <!-- Добавляем стили для адаптивных вкладок -->
    <style>
       
    </style>
@endsection
                          
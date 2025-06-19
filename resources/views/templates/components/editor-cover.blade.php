<div id="coverPreviewContainer" class="cover-container mb-3">
    @php
        $hasCover = false;
        $coverExists = false;
        $isVideo = false;
    @endphp

    {{-- Проверяем наличие обложки из сессии после загрузки из медиа-редактора --}}
    @if(session('media_editor_file'))
        @php
            $mediaFile = session('media_editor_file');
            $mediaType = session('media_editor_type');
            $coverPath = Storage::url($mediaFile);
            // Исправляем проверку существования файла
            $coverExists = Storage::disk('public')->exists($mediaFile);
            $hasCover = true;
            $isVideo = $mediaType === 'video';
        @endphp

        @if($isVideo && $coverExists)
            <video id="coverVideo" class="cover-video" autoplay loop muted playsinline>
                <source src="{{ $coverPath }}" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
        @elseif(!$isVideo && $coverExists)
            <img src="{{ $coverPath }}" class="cover-image" alt="Обложка шаблона">
        @endif

    {{-- Проверяем наличие обложки из шаблона пользователя --}}
    @elseif(isset($userTemplate) && $userTemplate && $userTemplate->cover_path)
        @php
            $coverPath = $userTemplate->cover_path;
            $coverExists = file_exists(public_path($coverPath));
            $isVideo = $userTemplate->cover_type === 'video';
            $hasCover = true;
        @endphp

        @if($isVideo && $coverExists)
            <video id="coverVideo" class="cover-video" autoplay loop muted playsinline>
                <source src="{{ asset($coverPath) }}" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
        @elseif(!$isVideo && $coverExists)
            <img src="{{ asset($coverPath) }}" class="cover-image" alt="{{ $userTemplate->name ?? 'Обложка шаблона' }}">
        @endif
    @endif

    {{-- Если нет обложки, показываем заглушку --}}
    @if(!$hasCover || !$coverExists)
        <div class="cover-fallback">
            <div class="fallback-content">
                <i class="bi bi-image-fill display-1 mb-3"></i>
                <p class="mb-2">Обложка не загружена</p>
            </div>
        </div>
    @endif
    
    <!-- Кнопка для смены обложки -->
    <a href="{{ isset($template) ? route('media.editor.template', $template->id) : route('media.editor') }}" 
       class="change-cover-btn">
        <i class="bi bi-camera"></i>
        <span>Изменить обложку</span>
    </a>
    
    <!-- Кнопка для просмотра/скрытия обложки -->
    <div class="skip-btn" id="toggleCoverBtn">
        <span id="skipBtnText"></span>
        <i class="bi bi-chevron-down"></i>
    </div>
    
    <!-- Индикатор прогресса свайпа -->
    <div class="swipe-progress-container">
        <div id="swipeProgress" class="swipe-progress"></div>
    </div>
</div>

<!-- Индикатор возврата к обложке -->
<div id="returnToCover" class="return-to-cover">
    <div class="return-indicator">
        <i class="bi bi-chevron-up"></i>
        <span>Вернуться к обложке</span>
    </div>
</div>

<style>
    .cover-container {
        position: relative;
        width: 100%;
        height: 70vh;
        overflow: hidden;
        border-radius: 12px;
        margin-bottom: 20px;
        background-color: #f0f0f0;
    }
    
    .cover-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .cover-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .cover-fallback {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        background-color: #e9ecef;
        color: #6c757d;
    }
    
    .cover-fallback i {
        font-size: 48px;
        margin-bottom: 10px;
    }
    
    .cover-fallback p {
        margin: 0;
        font-size: 16px;
    }
    
    .change-cover-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: rgba(255, 255, 255, 0.8);
        color: #212529;
        padding: 8px 16px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }
    
    .change-cover-btn:hover {
        background-color: rgba(255, 255, 255, 0.95);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .change-cover-btn i {
        margin-right: 5px;
    }
    
    .skip-btn {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        background-color: rgba(255, 255, 255, 0.8);
        color: #212529;
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }
    
    .skip-btn:hover {
        background-color: rgba(255, 255, 255, 0.95);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .skip-btn i {
        margin-left: 5px;
    }
    
    .swipe-progress-container {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background-color: rgba(255, 255, 255, 0.3);
    }
    
    .swipe-progress {
        height: 100%;
        background-color: #0d6efd;
        width: 0;
        transition: width 0.2s;
    }
    
    .return-to-cover {
        text-align: center;
        margin-top: -10px;
        margin-bottom: 20px;
        opacity: 0;
        transition: opacity 0.3s;
        display: none;
    }
    
    .return-to-cover.visible {
        opacity: 1;
        display: block;
    }
    
    .return-indicator {
        display: inline-flex;
        align-items: center;
        background-color: rgba(0, 0, 0, 0.1);
        padding: 5px 15px;
        border-radius: 20px;
        cursor: pointer;
    }
    
    .return-indicator:hover {
        background-color: rgba(0, 0, 0, 0.2);
    }
    
    .return-indicator i {
        margin-right: 5px;
    }
</style>

@extends('layouts.app')

@section('content')
<div class="editor-container">
    
    <!-- Подключаем компонент обложки -->
    @include('templates.components.editor-cover')

    <!-- Подключаем компонент предпросмотра шаблона -->
    @include('templates.components.editor-preview')

    <!-- Подключаем компонент формы редактирования -->
    @include('templates.components.editor-form', [
        'template' => $template,
        'userTemplate' => $userTemplate ?? null,
        'is_new_template' => $is_new_template ?? false
    ])
</div>

<!-- Улучшенные стили для обеспечения скролла -->
<style>
    @media (max-width: 767.98px) {
        .mobile-only-mode #app {
            height: auto !important;
            min-height: 100% !important;
        }
        
        .mobile-only-mode .content-wrapper {
            overflow: visible !important;
        }
        
        /* Принудительно включаем скролл для body */
        body {
            overflow-y: auto !important;
            height: auto !important;
            position: static !important;
        }
    }
</style>

@endsection

@section('scripts')
<!-- Инициализация редактора шаблона -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем модуль редактора шаблона
    TemplateEditor.init({
        template: @json($template ?? null),
        userTemplate: @json($userTemplate ?? null),
        mediaFile: "{{ session('media_editor_file') ?? '' }}",
        mediaType: "{{ session('media_editor_type') ?? '' }}",
        isNewTemplate: {{ isset($is_new_template) && $is_new_template ? 'true' : 'false' }},
        saveUrl: "{{ route('templates.save', $template->id) }}"
    });
    
    // Принудительно разблокируем скролл страницы
    function unblockPageScroll() {
        document.body.style.overflow = 'auto';
        document.body.style.position = 'static';
        document.body.style.height = 'auto';
        document.body.style.top = '';
        document.body.style.width = '';
        
        // Удаляем все классы, которые могут блокировать скролл
        document.body.classList.remove('modal-scroll-blocked');
        document.body.classList.remove('popup-scroll-blocked');
        
        // Сбрасываем inline стили для html
        document.documentElement.style.overflow = '';
        
        // Проверяем content-wrapper
        const contentWrapper = document.querySelector('.content-wrapper');
        if (contentWrapper) {
            contentWrapper.style.overflow = 'visible';
        }
    }
    
    // Вызываем разблокировку сразу и с небольшой задержкой
    unblockPageScroll();
    setTimeout(unblockPageScroll, 500);
    setTimeout(unblockPageScroll, 1500);
    
    // Наблюдаем за изменениями стилей body
    const observeBodyStyles = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'style' || mutation.attributeName === 'class') {
                // Если стиль body изменился, и скролл блокируется, разблокируем его
                if (document.body.style.overflow === 'hidden' || 
                    document.body.classList.contains('modal-scroll-blocked') ||
                    document.body.classList.contains('popup-scroll-blocked')) {
                    setTimeout(unblockPageScroll, 100);
                }
            }
        });
    });
    
    // Запускаем наблюдение за изменениями стилей body
    observeBodyStyles.observe(document.body, {
        attributes: true
    });
});
</script>
@endsection

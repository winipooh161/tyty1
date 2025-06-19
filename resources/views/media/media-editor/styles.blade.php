<style>
    /* Основные стили редактора */
    .fullscreen-editor {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1050;
        background: #fff;
        overflow: hidden;
    }
    
    /* Контейнеры медиа */
    .max-height-full {
        max-height: 80vh;
        object-fit: contain;
    }
    
    /* Стили для изображений */
    .image-container {
        position: relative;
        overflow: hidden;
        height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #000;
    }
    
    #imageViewport {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    #imagePreview {
        touch-action: none;
        transform-origin: center;
        max-width: 100%;
        max-height: 100%;
    }
    
    /* Стили для видео-контейнера */
    .video-container {
        background-color: #000;
        height: 80vh;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    #videoPreview {
        max-height: 80%;
        max-width: 100%;
        object-fit: contain;
    }

    /* Стиль для таймлайна видео */
    .video-timeline-container {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16px 12px;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(8px);
        z-index: 5;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
    
    /* Обертка для миниатюр видео */
.video-thumbnails-wrapper {
    position: relative;
    height: 50px;
    background-color: rgba(0, 0, 0, 0.3);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 15px;
}

.video-thumbnails-container {
    display: flex;
    height: 100%;
    width: 100%;
}

.video-thumbnail {
    height: 100%;
    flex: 1;
    background-size: cover;
    background-position: center;
    min-width: 40px;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.video-thumbnail:hover {
    opacity: 1;
}

/* Маркеры времени поверх эскизов */
.time-markers {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.time-marker {
    position: absolute;
    top: 0;
    height: 100%;
    width: 1px;
    background-color: rgba(255, 255, 255, 0.5);
}

.time-marker-label {
    position: absolute;
    bottom: 2px;
    transform: translateX(-50%);
    color: rgba(255, 255, 255, 0.8);
    font-size: 10px;
    background-color: rgba(0, 0, 0, 0.5);
    padding: 0 3px;
    border-radius: 2px;
}

/* Индикатор текущего времени */
.current-time-indicator {
    position: absolute;
    top: 0;
    width: 2px;
    height: 100%;
    background-color: #ff3e3e;
    box-shadow: 0 0 4px rgba(255, 0, 0, 0.7);
    pointer-events: none;
    z-index: 5;
}

/* Слайдер диапазона видео */
.range-slider-container {
    position: relative;
    padding: 10px 0;
}

.video-range-slider {
    height: 20px;
    position: relative;
}

/* Стили для слайдера noUiSlider (будет подключен в JS) */
.noUi-connect {
    background: #007bff;
}

.noUi-handle {
    border-radius: 50%;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    cursor: grab;
    width: 28px !important;
    height: 28px !important;
    right: -14px !important;
    top: -5px !important;
}

.noUi-handle:after,
.noUi-handle:before {
    display: none;
}

.noUi-handle:active {
    cursor: grabbing;
}

.noUi-horizontal {
    height: 10px;
}

.noUi-horizontal .noUi-tooltip {
    bottom: -30px;
    padding: 2px 5px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 11px;
    border: none;
}

/* Элементы для отображения времени */
.time-display-container {
    margin-top: 10px;
}

.time-display {
    font-size: 14px;
    color: white;
    padding: 2px 8px;
    background-color: rgba(0, 0, 0, 0.5);
    border-radius: 4px;
    font-weight: 500;
}

.time-display.duration {
    background-color: rgba(0, 123, 255, 0.7);
}

/* Кнопки управления */
.video-controls {
    background-color: #f8f9fa;
}

#previewVideoBtn.playing {
    background-color: #dc3545;
}

 
    /* Фиксы для портретного режима */
    @media (orientation: portrait) and (max-width: 576px) {
        .video-container, .image-container {
            height: 100vh; /* Меньше в портретном режиме */
        }
    }
    
    /* Улучшенные стили для кнопок действий внизу экрана */
    .action-buttons {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16px;
        background: white;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 20;
        border-top: 1px solid #eaeaea;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 5px;
    }
    
    @media (max-width: 576px) {
        .action-buttons .btn {
            padding: 12px;
            font-size: 1rem;
        }
    }
    
    /* Улучшенные мобильно-адаптивные стили для видео-редактора */
    .video-container {
        background-color: #000;
        height: 7100vh0vh; /* Уменьшаем высоту на мобильных устройствах */
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    #videoPreview {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }
    
    /* Улучшенный контейнер таймлайна для мобильных */
    .video-timeline-container {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16px 12px;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(8px);
        z-index: 5;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
    
    /* Индикаторы времени */
    .mobile-time-display {
        margin-bottom: 8px;
    }
    
    .mobile-time-counter {
        background-color: rgba(255, 255, 255, 0.15);
        color: white;
        padding: 8px 10px;
        border-radius: 8px;
        font-size: 0.95rem;
        font-family: monospace;
        min-width: 52px;
        text-align: center;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) inset;
    }
    
    .mobile-duration-badge {
        background-color: rgba(13, 110, 253, 0.9);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
    
    /* Улучшенный мобильный слайдер для легкого касания */
    .mobile-trim-slider {
        position: relative;
        height: 52px;
        margin: 12px 0;
    }
    
    .mobile-range-track {
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 12px;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) inset;
    }
    
    .mobile-range-progress {
        position: absolute;
        height: 100%;
        background: rgba(13, 110, 253, 0.9);
        border-radius: 6px;
        top: 0;
        box-shadow: 0 0 8px rgba(13, 110, 253, 0.5);
        will-change: left, width; /* Оптимизация производительности анимации */
    }
    
    /* Оптимизированные ручки для мобильного устройства */
    .mobile-handle {
        position: absolute;
        width: 40px; /* Увеличенный размер для удобного касания */
        height: 40px;
        border-radius: 50%;
        background: white;
        top: 50%;
        transform: translate(-50%, -50%);
        box-shadow: 0 0 12px rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        touch-action: none; /* Отключаем стандартные сенсорные жесты браузера */
        border: 2px solid #0d6efd;
        -webkit-tap-highlight-color: transparent; /* Убираем подсветку при тапе */
    }
    
    .handle-grip {
        width: 4px;
        height: 22px;
        background-color: #0d6efd;
        border-radius: 2px;
        position: relative;
    }
    
    /* Визуальные элементы для более легкого захвата */
    .handle-grip::before,
    .handle-grip::after {
        content: "";
        position: absolute;
        width: 4px;
        height: 14px;
        background-color: #0d6efd;
        border-radius: 2px;
    }
    
    .handle-grip::before {
        left: -7px;
    }
    
    .handle-grip::after {
        right: -7px;
    }
    
    /* Убираем лишние элементы для более чистого интерфейса */
    .mobile-handle.start-handle .handle-grip::before,
    .mobile-handle.end-handle .handle-grip::after {
        display: none;
    }
    
    /* Активное состояние для мобильного */
    .mobile-handle:active {
        transform: translate(-50%, -50%) scale(1.15);
        background-color: #e6f2ff;
        box-shadow: 0 0 16px rgba(13, 110, 253, 0.7);
    }
    
    /* Усиленное состояние затемнения при перетаскивании */
    body.mobile-dragging {
        overflow: hidden !important;
        touch-action: none !important;
    }
    
    body.mobile-dragging * {
        user-select: none !important;
    }
    
    /* Кнопки управления для мобильной версии */
    .mobile-controls {
        padding: 10px 0;
    }
    
    .btn-preview {
        background-color: #0d6efd;
        color: white;
        border-radius: 25px;
        padding: 10px 18px;
        font-weight: 600;
        font-size: 1rem;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.4);
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 120px;
        justify-content: center;
    }
    
    .btn-preview i {
        font-size: 1.25rem;
    }
    
    .btn-preview:hover {
        background-color: #0b5ed7;
        box-shadow: 0 6px 12px rgba(13, 110, 253, 0.5);
    }
    
    .btn-preview:active {
        transform: translateY(1px);
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.4);
    }
    
    .btn-preview.playing {
        background-color: #dc3545;
        box-shadow: 0 4px 10px rgba(220, 53, 69, 0.4);
    }
    
    #resetTrimmingBtn {
        background-color: #6c757d;
        color: white;
        border-radius: 25px;
        padding: 10px 18px;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 120px;
        justify-content: center;
    }
    
    #resetTrimmingBtn i {
        font-size: 1.25rem;
    }
    
    /* Адаптивная высота плеера для маленьких экранов */
    @media (max-height: 700px) {
        .video-container, .image-container {
            height: 60vh;
        }
    }
    
    @media (max-height: 600px) {
        .video-container, .image-container {
            height: 55vh;
        }
    }
    
    /* Адаптивные медиа-запросы для разных размеров экранов */
    @media (max-width: 576px) {
        .mobile-handle {
            width: 36px;
            height: 36px;
        }
        
        .handle-grip {
            height: 18px;
        }
        
        .action-buttons .btn {
            padding: 12px;
            font-size: 1rem;
        }
    }
    
    @media (max-width: 400px) {
        .video-timeline-container {
            padding: 12px 8px;
        }
        
        .mobile-trim-slider {
            height: 40px;
            margin: 8px 0;
        }
        
        .mobile-handle {
            width: 32px;
            height: 32px;
        }
        
        .handle-grip {
            height: 16px;
            width: 3px;
        }
    }
    
    @media (orientation: portrait) and (max-width: 576px) {
        .video-container, .image-container {
            height: 70vh;
        }
    }
</style>
    
<style>
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
    
    .max-height-full {
        max-height: 80vh;
        object-fit: contain;
    }
    
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
        touch-action: none; /* Предотвращаем стандартные действия браузера при касании */
        transform-origin: center;
        max-width: 100%;
        max-height: 100%;
    }
    
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
        max-height: 100%;
        max-width: 100%;
    }
    
    /* Стили для таймлайна видео в стиле рилсов */
    .video-timeline-container {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 15px;
        backdrop-filter: blur(5px);
    }
    
    .video-timeline {
        position: relative;
        width: 100%;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        overflow: hidden;
        display: flex;
    }
    
    .video-thumbnail {
        height: 100%;
        flex: 1;
        background-size: cover;
        background-position: center;
    }
    
    .video-trim-window {
        position: absolute;
        top: 0;
        left: 5%;
        right: 5%;
        height: 40px;
        border: 2px solid #fff;
        border-radius: 4px;
        pointer-events: none;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .trim-handle {
        width: 8px;
        height: 100%;
        background-color: #fff;
        position: absolute;
        top: 0;
        cursor: ew-resize;
        pointer-events: auto;
    }
    
    .left-handle {
        left: 0;
        border-top-left-radius: 4px;
        border-bottom-left-radius: 4px;
    }
    
    .right-handle {
        right: 0;
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
    }
    
    .trim-duration {
        color: #fff;
        font-weight: bold;
        font-size: 0.8rem;
        text-shadow: 0 0 3px rgba(0, 0, 0, 0.7);
        pointer-events: none;
    }
    
    .timeline-cursor {
        position: absolute;
        top: 0;
        left: 0;
        width: 2px;
        height: 100%;
        background-color: red;
        pointer-events: none;
    }
    
    .duration-badge {
        background-color: rgba(0, 123, 255, 0.7);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
    }
    
    .gesture-hint {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    /* Стили для слайдера временной шкалы */
    .reels-style-controls {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 10px;
    }
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/components/media-editor/styles.blade.php ENDPATH**/ ?>
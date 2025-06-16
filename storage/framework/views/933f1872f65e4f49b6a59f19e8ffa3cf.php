<!-- Секция для редактирования видео -->
<div class="video-editor-section p-0" id="videoEditorSection" style="display: none;">
    <div class="video-container text-center position-relative">
        <video id="videoPreview" controls class="img-fluid max-height-full"></video>
        
        <!-- Слайдер для обрезки видео в стиле рилсов -->
        <div class="video-timeline-container">
            <div class="video-timeline" id="videoTimeline">
                <!-- Здесь будут отображаться кадры видео -->
            </div>
            <div class="video-trim-window">
                <div class="trim-handle left-handle"></div>
                <div class="trim-duration" id="trimDuration">0 сек</div>
                <div class="trim-handle right-handle"></div>
            </div>
            <div class="timeline-cursor"></div>
        </div>
    </div>
    
    <div class="video-controls p-3">
        <p class="text-muted mb-2">Выберите отрезок видео до 15 секунд</p>
        
        <div class="reels-style-controls">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span id="startTimeDisplay">0:00</span>
                <span class="duration-badge" id="durationBadge">15 сек макс.</span>
                <span id="endTimeDisplay">0:15</span>
            </div>
            
            <div class="text-center mt-2">
                <button type="button" class="btn btn-primary rounded-circle" id="previewVideoBtn">
                    <i class="bi bi-play-fill"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/components/media-editor/video-editor.blade.php ENDPATH**/ ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const coverContainer = document.getElementById('coverPreviewContainer');
    const returnToCover = document.getElementById('returnToCover');
    const toggleCoverBtn = document.getElementById('toggleCoverBtn');
    const skipBtnText = document.getElementById('skipBtnText');
    const swipeProgress = document.getElementById('swipeProgress');
    
    let isCoverHidden = false;
    let startY, currentY;
    let isSwipeInProgress = false;
    
    // Функция для переключения видимости обложки
    function toggleCover() {
        if (isCoverHidden) {
            // Показываем обложку
            coverContainer.classList.remove('cover-hidden');
            skipBtnText.textContent = 'Перейти к редактированию';
            toggleCoverBtn.querySelector('i').classList.remove('bi-chevron-up');
            toggleCoverBtn.querySelector('i').classList.add('bi-chevron-down');
        } else {
            // Скрываем обложку
            coverContainer.classList.add('cover-hidden');
            skipBtnText.textContent = 'Показать обложку';
            toggleCoverBtn.querySelector('i').classList.remove('bi-chevron-down');
            toggleCoverBtn.querySelector('i').classList.add('bi-chevron-up');
        }
        isCoverHidden = !isCoverHidden;
    }
    
    // Обработчик клика по кнопке
    if (toggleCoverBtn) {
        toggleCoverBtn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleCover();
        });
    }
    
    // Обработчик клика по индикатору возврата
    if (returnToCover) {
        returnToCover.addEventListener('click', function() {
            if (isCoverHidden) {
                toggleCover();
            }
        });
    }
    
    // Поддержка жестов свайпа для мобильных устройств
    if (coverContainer) {
        // Свайп вниз для скрытия обложки
        coverContainer.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
            isSwipeInProgress = true;
            document.body.classList.add('swipe-active');
        }, { passive: true });
        
        coverContainer.addEventListener('touchmove', function(e) {
            if (!isSwipeInProgress) return;
            
            currentY = e.touches[0].clientY;
            const diffY = currentY - startY;
            
            // Если свайп вниз - показываем прогресс
            if (diffY > 0) {
                const percentage = Math.min((diffY / 200) * 100, 100);
                if (swipeProgress) {
                    swipeProgress.style.width = percentage + '%';
                }
                
                // Если прогресс достиг 100% - скрываем обложку
                if (percentage >= 90 && !isCoverHidden) {
                    toggleCover();
                    isSwipeInProgress = false;
                    resetSwipeProgress();
                }
            }
        }, { passive: true });
        
        coverContainer.addEventListener('touchend', function() {
            isSwipeInProgress = false;
            document.body.classList.remove('swipe-active');
            resetSwipeProgress();
        }, { passive: true });
    }
    
    // Свайп вверх для возврата к обложке
    if (returnToCover) {
        returnToCover.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
            isSwipeInProgress = true;
            document.body.classList.add('return-swipe-active');
        }, { passive: true });
        
        returnToCover.addEventListener('touchmove', function(e) {
            if (!isSwipeInProgress) return;
            
            currentY = e.touches[0].clientY;
            const diffY = startY - currentY; // Инвертируем для свайпа вверх
            
            if (diffY > 0) {
                const percentage = Math.min((diffY / 100) * 100, 100);
                
                // Если прогресс достиг 90% - возвращаемся к обложке
                if (percentage >= 90 && isCoverHidden) {
                    toggleCover();
                    isSwipeInProgress = false;
                }
            }
        }, { passive: true });
        
        returnToCover.addEventListener('touchend', function() {
            isSwipeInProgress = false;
            document.body.classList.remove('return-swipe-active');
        }, { passive: true });
    }
    
    // Функция для сброса индикатора прогресса свайпа
    function resetSwipeProgress() {
        if (swipeProgress) {
            swipeProgress.style.width = '0%';
        }
    }
    
    // Автоматическое воспроизведение видео при загрузке
    const coverVideo = document.getElementById('coverVideo');
    if (coverVideo) {
        coverVideo.play().catch(error => {
            console.log("Автовоспроизведение видео не поддерживается");
        });
    }
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/components/cover-management.blade.php ENDPATH**/ ?>
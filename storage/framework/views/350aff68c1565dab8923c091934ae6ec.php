<script src="<?php echo e(asset('js/public-template.js')); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const coverContainer = document.getElementById('coverContainer');
    const returnToCover = document.getElementById('returnToCover');
    const toggleCoverBtn = document.getElementById('toggleCoverBtn');
    const skipBtnText = document.getElementById('skipBtnText');
    const swipeProgress = document.getElementById('swipeProgress');
    const coverVideo = document.getElementById('coverVideo');
    
    let isCoverHidden = false;
    let touchStartY = 0;
    let touchDeltaY = 0;
    
    // Функция для переключения видимости обложки
    function toggleCover() {
        if (isCoverHidden) {
            // Показываем обложку
            coverContainer.classList.remove('cover-hidden');
            skipBtnText.textContent = 'Пропустить';
            toggleCoverBtn.querySelector('i').classList.remove('bi-chevron-up');
            toggleCoverBtn.querySelector('i').classList.add('bi-chevron-down');
            document.body.classList.remove('return-swipe-active');
        } else {
            // Скрываем обложку
            coverContainer.classList.add('cover-hidden');
            skipBtnText.textContent = 'Показать обложку';
            toggleCoverBtn.querySelector('i').classList.remove('bi-chevron-down');
            toggleCoverBtn.querySelector('i').classList.add('bi-chevron-up');
            document.body.classList.add('return-swipe-active');
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
    
    // Добавляем обработчики для свайпа
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
    }, { passive: true });
    
    document.addEventListener('touchmove', function(e) {
        if (!isCoverHidden && e.touches[0].clientY < touchStartY) {
            touchDeltaY = touchStartY - e.touches[0].clientY;
            const progress = Math.min(100, (touchDeltaY / 150) * 100);
            swipeProgress.style.width = progress + '%';
            
            if (progress >= 100) {
                toggleCover();
                swipeProgress.style.width = '0%';
            }
        } else if (isCoverHidden && e.touches[0].clientY > touchStartY) {
            // Свайп вниз для возврата к обложке
            if ((e.touches[0].clientY - touchStartY) > 100) {
                toggleCover();
            }
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function() {
        swipeProgress.style.width = '0%';
    }, { passive: true });
    
    // Автоматическое воспроизведение видео при загрузке
    if (coverVideo) {
        coverVideo.play().catch(error => {
            console.log("Автовоспроизведение видео не поддерживается");
        });
    }
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/scripts.blade.php ENDPATH**/ ?>
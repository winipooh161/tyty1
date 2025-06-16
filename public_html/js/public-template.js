document.addEventListener('DOMContentLoaded', function() {
    // DOM элементы
    const coverContainer = document.getElementById('coverContainer');
    const coverVideo = document.getElementById('coverVideo');
    const skipBtn = document.getElementById('skipBtn');
    const returnToCover = document.getElementById('returnToCover');
    const swipeProgress = document.getElementById('swipeProgress');
    
    // Переменные для отслеживания состояния свайпа
    let startY = 0;
    let isDragging = false;
    let initialScrollPosition = 0;
    
    function hideCover() {
        // Приостанавливаем видео для экономии ресурсов
        if (coverVideo) {
            coverVideo.pause();
        }
        
        coverContainer.classList.add('cover-hidden');
        document.body.classList.add('return-swipe-active');
        returnToCover.style.display = 'block';
    }
    
    // Функция для показа обложки
    function showCover() {
        coverContainer.classList.remove('cover-hidden');
        returnToCover.style.display = 'none'; // Скрываем индикатор возврата
        document.body.classList.remove('return-swipe-active');
        
        // Возобновляем видео при возврате к обложке
        if (coverVideo) {
            coverVideo.play();
        }
    }
    
    // Обработчик свайпа вниз для скрытия обложки
    if (coverContainer) {
        coverContainer.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
            isDragging = true;
            initialScrollPosition = 0;
        }, { passive: true });

        coverContainer.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            
            const currentY = e.touches[0].clientY;
            const deltaY = currentY - startY;
            
            // Свайп вниз
            if (deltaY > 0) {
                const progress = Math.min(deltaY / 150, 1); // 150px для полного свайпа
                swipeProgress.style.width = `${progress * 100}%`;
                
                if (progress >= 1) {
                    hideCover();
                    isDragging = false;
                }
            }
        }, { passive: true });

        coverContainer.addEventListener('touchend', function() {
            isDragging = false;
            swipeProgress.style.width = '0%';
        }, { passive: true });
    }
    
    // Обработчик клика на кнопке Skip
    if (skipBtn) {
        skipBtn.addEventListener('click', hideCover);
    }
    
    // Обработчик свайпа вверх для возврата к обложке
    if (returnToCover) {
        returnToCover.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
            isDragging = true;
        }, { passive: true });

        returnToCover.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            
            const currentY = e.touches[0].clientY;
            const deltaY = startY - currentY;
            
            // Свайп вверх
            if (deltaY > 50) {
                showCover();
                isDragging = false;
            }
        }, { passive: true });

        returnToCover.addEventListener('touchend', function() {
            isDragging = false;
        }, { passive: true });
        
        // Добавляем обработчик клика для возврата к обложке
        returnToCover.addEventListener('click', showCover);
    }
    
    // Функция для скрытия/показа информационной панели
    window.togglePanel = function() {
        const panel = document.getElementById('infoPanel');
        const toggleBtn = document.getElementById('togglePanel');
        
        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
            toggleBtn.innerHTML = '<i class="bi bi-info"></i>';
        } else {
            panel.classList.add('hidden');
            toggleBtn.innerHTML = '<i class="bi bi-info"></i>';
        }
        
        // Сохраняем состояние в localStorage
        localStorage.setItem('infoPanelHidden', panel.classList.contains('hidden'));
    };
    
    // Восстанавливаем состояние панели из localStorage
    const panelState = localStorage.getItem('infoPanelHidden');
    if (panelState === 'true') {
        document.getElementById('infoPanel').classList.add('hidden');
    }
    
    // Обработка форм в шаблоне
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Если форма имеет атрибут action, то не блокируем её отправку
            if (!this.getAttribute('action')) {
                e.preventDefault();
                alert('Эта форма доступна только в режиме предпросмотра.');
            }
        });
    });
});

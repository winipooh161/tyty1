<!-- Конфигурации для всплывающих меню мобильной навигации -->
<div id="mobile-nav-popup-configs" class="d-none">
    <!-- Конфигурация для Home -->
    

    <!-- Конфигурация для Profile -->
    <div data-popup-config="profile" class="popup-config">
       
        <div class="popup-items">
            <div class="popup-item" data-icon="speedometer.svg" data-href="#" data-modal="true" data-modal-target="user-profile-modal" data-title="Настройки профиля"></div>
            <div class="popup-item" data-icon="sup.svg" data-href="#" data-modal="true" data-modal-target="sub-profile-modal" data-title="Донат"></div>
            <div class="popup-item" data-icon="share.svg" data-href="#" data-modal="true" data-modal-target="share-profile-modal" data-title="Поделиться"></div>
        </div>
    </div>

    <!-- Конфигурация для Create -->
    <div data-popup-config="create" class="popup-config">
       
        <div class="popup-items">
            <?php if(request()->is('client/templates/create-new/*')): ?>
                <div class="popup-item" 
                     data-icon="speedometer.svg" 
                     data-modal="true"
                     data-modal-target="settings-offcanvas-modal"
                     data-title="Вернуться">
                </div>
            <?php else: ?>
                <div class="popup-item" data-icon="back.svg" data-href="javascript:history.back();" data-title=""></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Конфигурация для QR-Scanner -->
    

    <!-- Конфигурация для Games -->
    <div data-popup-config="games" class="popup-config">
    
        <div class="popup-items">
            <div class="popup-item" data-icon="puzzle.svg" data-href="/games/puzzle" data-title=""></div>
            <div class="popup-item" data-icon="controller.svg" data-href="/games/arcade" data-title=""></div>
            <div class="popup-item" data-icon="trophy.svg" data-href="/games/tournaments" data-title=""></div>
        </div>
    </div>

    <!-- Конфигурация для Admin -->
    

    <!-- Вы можете добавить любые дополнительные конфигурации здесь -->
</div>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/mobile-nav-popup-configs.blade.php ENDPATH**/ ?>
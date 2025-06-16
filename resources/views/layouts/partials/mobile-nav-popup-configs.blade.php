<!-- Конфигурации для всплывающих меню мобильной навигации -->
<div id="mobile-nav-popup-configs" class="d-none">
    <!-- Конфигурация для Home -->
    {{-- <div data-popup-config="center" class="popup-config">
       
        <div class="popup-items">
            <div class="popup-item" data-icon="newspaper.svg" data-href="/news" data-title=""></div>
            <div class="popup-item" data-icon="calendar.svg" data-href="/events" data-title=""></div>
            <div class="popup-item" data-icon="info-circle.svg" data-href="/about" data-title=""></div>
        </div>
    </div> --}}

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
            @if(request()->is('client/templates/create-new/*'))
                <div class="popup-item" 
                     data-icon="speedometer.svg" 
                     data-modal="true"
                     data-modal-target="settings-offcanvas-modal"
                     data-title="Вернуться">
                </div>
            @else
                <div class="popup-item" data-icon="back.svg" data-href="javascript:history.back();" data-title=""></div>
            @endif
        </div>
    </div>

    <!-- Конфигурация для QR-Scanner -->
    {{-- <div data-popup-config="qr-scanner" class="popup-config">
    
        <div class="popup-items">
            <div class="popup-item" data-icon="qr-code.svg" data-href="#" data-modal="true" data-modal-target="qr-scanner-modal" data-title=""></div>
            <div class="popup-item" data-icon="camera.svg" data-href="#" data-modal="true" data-modal-target="camera-modal" data-title=""></div>
            <div class="popup-item" data-icon="image.svg" data-href="/qr/history" data-title=""></div>
        </div>
    </div> --}}

    <!-- Конфигурация для Games -->
    <div data-popup-config="games" class="popup-config">
    
        <div class="popup-items">
            <div class="popup-item" data-icon="puzzle.svg" data-href="/games/puzzle" data-title=""></div>
            <div class="popup-item" data-icon="controller.svg" data-href="/games/arcade" data-title=""></div>
            <div class="popup-item" data-icon="trophy.svg" data-href="/games/tournaments" data-title=""></div>
        </div>
    </div>

    <!-- Конфигурация для Admin -->
    {{-- <div data-popup-config="admin" class="popup-config">
      
        <div class="popup-items">
            <div class="popup-item" data-icon="people.svg" data-href="/admin/users" data-title=""></div>
            <div class="popup-item" data-icon="bar-chart.svg" data-href="/admin/statistics" data-title=""></div>
            <div class="popup-item" data-icon="gear.svg" data-href="/admin/settings" data-title=""></div>
        </div>
    </div> --}}

    <!-- Вы можете добавить любые дополнительные конфигурации здесь -->
</div>

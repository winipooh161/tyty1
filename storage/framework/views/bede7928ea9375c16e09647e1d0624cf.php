<nav class="mb-navigation mb-dock hide-desktop">
    <div class="mb-fixed-container">
        <div class="mb-scroller" id="nav-scroll-container">
            <div class="mb-icons-container" id="nav-icons-container">
                
                
                   
              
                    <!-- Добавляем новую иконку сканера QR-кода с атрибутом data-modal-target -->
                    <div class="mb-icon-wrapper" data-icon-id="qr-scanner" data-modal="true"
                        data-modal-target="qr-scanner-modal" data-original="true">
                        <a class="mb-nav-link no-spinner" href="javascript:void(0);"
                            onclick="openQrScannerModal(this.parentElement)">
                            <div class="mb-nav-icon-wrap">
                                <img class="mb-nav-icon" src="<?php echo e(asset('images/icons/scan.svg')); ?>" alt="QR сканер">
                            </div>
                        </a>
                    </div>
                  

                    <div class="mb-icon-wrapper mb-center-wrapper" data-icon-id="center" data-original="true">
                        <a class="mb-nav-link mb-center-link <?php echo e(request()->routeIs('home') ? 'mb-active' : ''); ?>"
                            href="<?php echo e(route('home')); ?>" onclick="centerNavItem(this.parentElement)">
                            <div class="mb-nav-icon-wrap mb-center-icon-wrap">
                                <img class="mb-nav-icon mb-center-nav-icon" src="<?php echo e(asset('images/center-icon.svg')); ?>"
                                    alt="Центр">
                            </div>
                        </a>
                    </div>


                    <?php if(Auth::user()->role === 'client' || Auth::user()->role === 'admin'): ?>
                        <div class="mb-icon-wrapper" data-icon-id="profile" data-original="true">
                            <a class="mb-nav-link <?php echo e(request()->routeIs('user.templates*') ? 'mb-active' : ''); ?>"
                                href="<?php echo e(route('user.templates')); ?>" onclick="centerNavItem(this.parentElement)">
                                <div class="mb-nav-icon-wrap">
                                    <img class="mb-nav-icon" src="<?php echo e(asset('images/icons/person.svg')); ?>"
                                        alt="Профиль">
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
 <div class="mb-icon-wrapper" data-icon-id="create" data-original="true">
                    <a class="mb-nav-link <?php echo e(request()->routeIs('media.editor') || request()->routeIs('create.template') ? 'mb-active' : ''); ?>"
                        href="<?php echo e(route('create.template')); ?>" onclick="centerNavItem(this.parentElement)">
                        <div class="mb-nav-icon-wrap">
                            <img class="mb-nav-icon" src="<?php echo e(asset('images/icons/plus-1.svg')); ?>" alt="Создать">
                        </div>
                    </a>
                </div>
                    <?php if(Auth::user()->role === 'admin'): ?>
                        <div class="mb-icon-wrapper" data-icon-id="admin" data-original="true">
                            <a class="mb-nav-link <?php echo e(request()->routeIs('admin.dashboard') ? 'mb-active' : ''); ?>"
                                href="<?php echo e(route('admin.dashboard')); ?>" onclick="centerNavItem(this.parentElement)">
                                <div class="mb-nav-icon-wrap">
                                    <img class="mb-nav-icon" src="<?php echo e(asset('images/icons/speedometer.svg')); ?>"
                                        alt="Админ панель">
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>

            </div>
        </div>
    </div>
</nav>

<style>
    /* Стили для ограничения мобильной навигации до 4 иконок */
    .mb-fixed-container {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        position: relative;
    }

    .mb-scroller {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE and Edge */
    }

    .mb-scroller::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari and Opera */
    }

    .mb-icons-container {
        display: flex;
        align-items: center;
        justify-content: flex-start;
    }

    /* Фиксированная ширина для иконок, чтобы их было ровно 4 */
    .mb-icon-wrapper {
        flex: 0 0 25%;
        /* Ровно 4 элемента в ряд */
        max-width: 25%;
        box-sizing: border-box;
        text-align: center;
    }

    /* Состояние после завершения инициализации */
    .mb-centering-complete .mb-icons-container {
        width: auto;
        min-width: 100%;
    }

    /* Улучшенный стиль для центрального элемента */
    .mb-icon-wrapper.mb-centered {
        position: relative;
    }

    .mb-icon-wrapper.mb-centered::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 2px;
        background-color: #007bff;
        border-radius: 2px;
    }
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/mobile-nav.blade.php ENDPATH**/ ?>
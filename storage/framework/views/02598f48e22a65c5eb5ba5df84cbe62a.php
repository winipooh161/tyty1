<!-- Стили для редактора шаблонов -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
    /*
    * Prefixed by https://autoprefixer.github.io
    * PostCSS: v8.4.14,
    * Autoprefixer: v10.4.7
    * Browsers: last 4 version
    */

   /* Стили для контейнера обложки в режиме редактирования */
    .cover-container {
        position: relative;
        width: 100%;
        height: 70vh;
        z-index: 100;
        background-color: #000;
        -webkit-transition: height 0.4s ease;
        -o-transition: height 0.4s ease;
        transition: height 0.4s ease;
        overflow: hidden;
        border-radius: 8px;
        -webkit-box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
   
    @media (max-width: 767.98px) {
        .content-wrapper {
            padding-top: 60px;
            padding: 0px 0 0 0 !important;
        }
    }
    
    .cover-container.cover-hidden {
        height: 30vh;
    }
    
    .cover-video, .cover-image {
        width: 100%;
        height: 100%;
        -o-object-fit: cover;
           object-fit: cover;
    }
    
    /* Запасная обложка */
    .cover-fallback {
        width: 100%;
        height: 100%;
        background: -o-linear-gradient(315deg, #6a11cb 0%, #2575fc 100%);
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
            -ms-flex-align: center;
                align-items: center;
        -webkit-box-pack: center;
            -ms-flex-pack: center;
                justify-content: center;
        color: white;
        text-align: center;
    }
    
    .fallback-content {
        max-width: 80%;
        padding: 20px;
    }
    
    /* Кнопка смены обложки */
    .change-cover-btn {
        position: absolute;
        top: 50%;
        left: 50%;
        -webkit-transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
                transform: translate(-50%, -50%);
        background-color: rgba(0, 0, 0, 0.6);
        color: white;
        padding: 10px 20px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 500;
        font-size: 16px;
        -webkit-transition: all 0.3s ease;
        -o-transition: all 0.3s ease;
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.4);
        -webkit-backdrop-filter: blur(3px);
                backdrop-filter: blur(3px);
        z-index: 110;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
            -ms-flex-align: center;
                align-items: center;
        opacity: 0.7;
    }
    
    .change-cover-btn:hover {
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        opacity: 1;
        -webkit-transform: translate(-50%, -50%) scale(1.05);
            -ms-transform: translate(-50%, -50%) scale(1.05);
                transform: translate(-50%, -50%) scale(1.05);
        -webkit-box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
                box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
    }
    
    /* Кнопка переключения режимов */
    .skip-btn {
        position: absolute;
        bottom: 15px;
        left: 50%;
        -webkit-transform: translateX(-50%);
            -ms-transform: translateX(-50%);
                transform: translateX(-50%);
        color: white;
        background-color: rgba(0, 0, 0, 0.5);
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
            -ms-flex-align: center;
                align-items: center;
        gap: 5px;
        font-size: 14px;
        z-index: 101;
    }
    
    .swipe-progress-container {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    .swipe-progress {
        height: 100%;
        background-color: white;
        width: 0%;
        -webkit-transition: width 0.1s linear;
        -o-transition: width 0.1s linear;
        transition: width 0.1s linear;
    }
    
    /* Индикатор возврата к обложке */
    .return-to-cover {
        position: relative;
        width: 100%;
        padding: 10px 0;
        background: -webkit-gradient(linear, left top, left bottom, from(rgba(0,0,0,0.5)), to(rgba(0,0,0,0)));
        background: -o-linear-gradient(top, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0) 100%);
        background: linear-gradient(180deg, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0) 100%);
        color: white;
        text-align: center;
        z-index: 90;
        -webkit-transform: translateY(-100%);
            -ms-transform: translateY(-100%);
                transform: translateY(-100%);
        -webkit-transition: -webkit-transform 0.3s ease;
        transition: -webkit-transform 0.3s ease;
        -o-transition: transform 0.3s ease;
        transition: transform 0.3s ease;
        transition: transform 0.3s ease, -webkit-transform 0.3s ease;
        cursor: pointer;
        display: none;
    }
    
    .return-indicator {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-orient: vertical;
        -webkit-box-direction: normal;
            -ms-flex-direction: column;
                flex-direction: column;
        -webkit-box-align: center;
            -ms-flex-align: center;
                align-items: center;
        font-size: 14px;
    }
    
    body.return-swipe-active .return-to-cover {
        -webkit-transform: translateY(0);
            -ms-transform: translateY(0);
                transform: translateY(0);
        display: block;
    }
    
    /* Кнопка сохранения */
    .save-btn-container {
        text-align: center;
        padding: 20px 0 80px 0;
        background-color: #f8f9fa;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .save-btn-container .btn {
        -webkit-box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 30px;
        padding: 12px 30px;
        font-weight: 600;
        min-width: 200px;
    }
    
    .save-btn-container .btn:hover {
        -webkit-transform: translateY(-2px);
            -ms-transform: translateY(-2px);
                transform: translateY(-2px);
        -webkit-box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }
    
    /* Индикатор загрузки */
    .template-loading {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-orient: vertical;
        -webkit-box-direction: normal;
            -ms-flex-direction: column;
                flex-direction: column;
        -webkit-box-align: center;
            -ms-flex-align: center;
                align-items: center;
        -webkit-box-pack: center;
            -ms-flex-pack: center;
                justify-content: center;
        height: 100%;
        min-height: 300px;
        background-color: rgba(248, 249, 250, 0.7);
    }
    
    .template-loading .spinner-border {
        width: 3rem;
        height: 3rem;
    }

    /* Стили для модального окна настроек */
    .template-settings-modal .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .template-settings-modal .modal-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        border-radius: 14px 14px 0 0;
        border-bottom: none;
    }

    .template-settings-modal .modal-footer {
        border-top: 1px solid #eee;
        padding: 15px 20px;
    }

    .template-settings-modal .tab-content {
        padding: 20px 0;
    }

    .template-settings-modal .nav-tabs {
        border-bottom: 1px solid #eee;
        margin-bottom: 15px;
    }

    .template-settings-modal .nav-link {
        color: #6c757d;
        border: none;
        padding: 10px 15px;
        font-weight: 500;
    }

    .template-settings-modal .nav-link.active {
        color: #4e73df;
        border-bottom: 2px solid #4e73df;
        background: transparent;
    }

    /* Редактируемые элементы */
    [data-editable] {
        transition: background-color 0.2s;
        padding: 2px;
        outline: none;
    }

    [data-editable]:hover {
        background-color: rgba(0, 123, 255, 0.1);
    }

    [data-editable].editing {
        background-color: rgba(0, 123, 255, 0.15);
        outline: 2px solid #007bff;
    }
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/components/editor-head.blade.php ENDPATH**/ ?>
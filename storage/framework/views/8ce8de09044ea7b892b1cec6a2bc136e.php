<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="index, follow">

<!-- CSRF Token для JavaScript -->
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

<!-- SEO метаданные -->
<title><?php echo e($userTemplate->name); ?> | <?php echo e(config('app.name')); ?></title>
<meta name="description" content="<?php echo e($userTemplate->description ?? 'Просмотр шаблона ' . $userTemplate->name); ?>">

<!-- Стили -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
 /*
* Prefixed by https://autoprefixer.github.io
* PostCSS: v8.4.14,
* Autoprefixer: v10.4.7
* Browsers: last 4 version
*/

   body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        font-family: 'Nunito', sans-serif;
        overflow-x: hidden;
    }
    
    /* Информационная панель */
    .info-panel {
        position: fixed;
        top: 0;
        right: 0;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 15px;
        border-radius: 0 0 0 10px;
        font-size: 14px;
        z-index: 1050;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
            -ms-flex-align: center;
                align-items: center;
        gap: 10px;
        -webkit-backdrop-filter: blur(5px);
                backdrop-filter: blur(5px);
        -webkit-transition: -webkit-transform 0.3s ease;
        transition: -webkit-transform 0.3s ease;
        -o-transition: transform 0.3s ease;
        transition: transform 0.3s ease;
        transition: transform 0.3s ease, -webkit-transform 0.3s ease;
    }
    
    .info-panel.hidden {
        -webkit-transform: translateY(-100%);
            -ms-transform: translateY(-100%);
                transform: translateY(-100%);
    }
    
    .info-panel a {
        color: white;
        text-decoration: none;
    }
    
    .info-panel .btn-use {
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        -webkit-transition: all 0.2s;
        -o-transition: all 0.2s;
        transition: all 0.2s;
    }
    
    .info-panel .btn-use:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .close-panel {
        cursor: pointer;
        font-size: 18px;
        line-height: 1;
    }
    
    .toggle-panel {
        position: fixed;
        top: 10px;
        left: 10px;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
            -ms-flex-align: center;
                align-items: center;
        -webkit-box-pack: center;
            -ms-flex-pack: center;
                justify-content: center;
        cursor: pointer;
        z-index: 1000;
        -webkit-backdrop-filter: blur(5px);
                backdrop-filter: blur(5px);
        -webkit-transition: opacity 0.3s ease;
        -o-transition: opacity 0.3s ease;
        transition: opacity 0.3s ease;
        opacity: 0;
    }
    
    .toggle-panel:hover {
        opacity: 1;
    }
    
    body:hover .toggle-panel {
        opacity: 0.5;
    }
    
    /* Стили для обложки */
    .cover-container {
        position: relative;
        top: 0;
        left: 0;
        width: 100%;
        height: 70vh;
        z-index: 100;
        background-color: #000;
        overflow: hidden;
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
    
    /* Основное содержимое */
    .template-content {
        position: relative;
        margin: 0;
        z-index: 50;
        background-color: #f8f9fa;
        padding-top: 20px;
    }
    
    /* Остальные стили */
    .series-badge {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 999;
        background-color: rgba(255, 193, 7, 0.8);
        color: #212529;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 14px;
        -webkit-backdrop-filter: blur(5px);
                backdrop-filter: blur(5px);
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        border: 1px solid rgba(255,255,255,0.2);
        animation: fadeInBadge 0.5s ease-out;
    }
    
    @keyframes fadeInBadge {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .series-active {
        border: 2px solid rgba(255, 193, 7, 0.8) !important;
        box-shadow: 0 0 10px rgba(255, 193, 7, 0.4) !important;
    }
    
    .acquire-template-btn {
        position: relative;
        bottom: 0px;
        right: 0px;
        z-index: 1000;
        padding: 10px 20px;
        border-radius: 9px;
        background-color: #0d6efd;
        color: white;
        text-decoration: none;
        -webkit-box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        -webkit-transition: all 0.3s ease;
        -o-transition: all 0.3s ease;
        transition: all 0.3s ease;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
            -ms-flex-align: center;
                align-items: center;
        gap: 10px;
    }
    
    .acquire-template-btn:hover {
        background-color: #0b5ed7;
        -webkit-transform: translateY(-2px);
            -ms-transform: translateY(-2px);
                transform: translateY(-2px);
        color: white;
    }

    @media (max-width: 767.98px) {
        .template-content {
            padding: 0;
            margin: 0;
        }
    }

    /* Стили для QR-кода */
    #qrcode img {
        margin: 0 auto;
        border-radius: 8px;
        -webkit-box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        -webkit-transition: -webkit-transform 0.3s ease;
        transition: -webkit-transform 0.3s ease;
        -o-transition: transform 0.3s ease;
        transition: transform 0.3s ease;
        transition: transform 0.3s ease, -webkit-transform 0.3s ease;
    }
    
    .qr-loading {
        text-align: center;
        padding: 20px;
    }
    
    .qr-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid rgba(0,0,0,0.1);
        border-radius: 50%;
        border-top: 4px solid #0d6efd;
        -webkit-animation: spin 1s linear infinite;
                animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    
    @-webkit-keyframes spin {
        0% { -webkit-transform: rotate(0deg); transform: rotate(0deg); }
        100% { -webkit-transform: rotate(360deg); transform: rotate(360deg); }
    }
    
    @keyframes spin {
        0% { -webkit-transform: rotate(0deg); transform: rotate(0deg); }
        100% { -webkit-transform: rotate(360deg); transform: rotate(360deg); }
    }
    
    .content-cover_content {
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
        -ms-flex-line-pack: center;
            align-content: center;
    }

    /* Стили для кнопок действий шаблона */
    .template-actions-container {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 2px solid #e9ecef;
    }
    
    .certificate-buttons {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-pack: center;
            -ms-flex-pack: center;
                justify-content: center;
        gap: 15px;
        margin: 20px 0;
        -ms-flex-wrap: wrap;
            flex-wrap: wrap;
    }
    
    .certificate-buttons button,
    .certificate-buttons .acquire-template-btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        font-size: 16px;
        -webkit-transition: all 0.3s ease;
        -o-transition: all 0.3s ease;
        transition: all 0.3s ease;
        text-decoration: none;
        display: -webkit-inline-box;
        display: -ms-inline-flexbox;
        display: inline-flex;
        -webkit-box-align: center;
            -ms-flex-align: center;
                align-items: center;
        gap: 8px;
        min-width: 140px;
        -webkit-box-pack: center;
            -ms-flex-pack: center;
                justify-content: center;
    }
    
    .certificate-buttons .red {
        background-color: #dc3545;
        color: white;
    }
    
    .certificate-buttons .green {
        background-color: #28a745;
        color: white;
    }
    
    .certificate-buttons .red:hover {
        background-color: #c82333;
        color: white;
        -webkit-transform: translateY(-2px);
            -ms-transform: translateY(-2px);
                transform: translateY(-2px);
        -webkit-box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
                box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }
    
    .certificate-buttons .green:hover {
        background-color: #218838;
        color: white;
        -webkit-transform: translateY(-2px);
            -ms-transform: translateY(-2px);
                transform: translateY(-2px);
        -webkit-box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
                box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    
    .certificate-buttons button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        -webkit-transform: none;
            -ms-transform: none;
                transform: none;
    }
    
    @media (max-width: 600px) {
        .certificate-buttons {
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
                -ms-flex-direction: column;
                    flex-direction: column;
            gap: 10px;
        }
        
        .certificate-buttons button,
        .certificate-buttons .acquire-template-btn {
            width: 100%;
            min-width: auto;
        }
    }

    /* Стили для прогресса сканирований */
    .scan-progress-display {
        width: 100%;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 14px;
        background-color: #fff;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        font-weight: 600;
        color: #555;
        cursor: default;
    }
    
    /* Статусы сканирования */
    .scans-complete {
        background-color: rgba(40, 167, 69, 0.1) !important;
        border-color: rgba(40, 167, 69, 0.5) !important;
        color: #28a745 !important;
    }
    
    .scans-in-progress {
        background-color: rgba(255, 193, 7, 0.1) !important;
        border-color: rgba(255, 193, 7, 0.5) !important;
        color: #d39e00 !important;
    }
    
    .certificate-series-info {
        animation: fadeIn 0.5s ease-out;
        transition: all 0.3s ease;
    }
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/template-head.blade.php ENDPATH**/ ?>
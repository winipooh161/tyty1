<style>
    body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        font-family: 'Nunito', sans-serif;
    }
    
    /* Стили для информационной панели */
    .info-panel {
        position: fixed;
        top: 0;
        right: 0;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 15px;
        border-radius: 0 0 0 10px;
        font-size: 14px;
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 10px;
        backdrop-filter: blur(5px);
        transition: transform 0.3s ease;
    }
    
    .info-panel.hidden {
        transform: translateY(-100%);
    }
    
    .info-panel a {
        color: white;
        text-decoration: none;
    }
    
    .info-panel a:hover {
        text-decoration: underline;
    }
    
    .toggle-panel {
        position: fixed;
        top: 10px;
        left:  10px;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 999;
        backdrop-filter: blur(5px);
        transition: opacity 0.3s ease;
        opacity: 0;
    }
    
    .toggle-panel:hover {
        opacity: 1;
    }
    
    body:hover .toggle-panel {
        opacity: 0.5;
    }
    
    /* Стили для обложки - аналогичные редактору шаблона */
    .cover-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 100;
        background-color: #000;
        transition: transform 0.4s ease, height 0.4s ease;
    }
    
    .cover-container.cover-hidden {
        transform: translateY(-100%);
        height: 30vh;
    }
    
    .cover-video, .cover-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Добавление стилей для запасной обложки */
    .cover-fallback {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-align: center;
    }
    
    .fallback-content {
        max-width: 80%;
        padding: 20px;
    }
    
    .skip-btn {
        position: absolute;
        bottom: 80px;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        background-color: rgba(0, 0, 0, 0.5);
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
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
        transition: width 0.1s linear;
    }
    
    /* Стили для индикатора возврата к обложке */
    .return-to-cover {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        padding: 10px 0;
        background: linear-gradient(180deg, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0) 100%);
        color: white;
        text-align: center;
        z-index: 90;
        transform: translateY(-100%);
        transition: transform 0.3s ease;
        cursor: pointer;
        display: none;
    }
    
    .return-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 14px;
    }
    
    body.return-swipe-active .return-to-cover {
        transform: translateY(0);
        display: block;
    }
    
    .acquire-template-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        padding: 10px 20px;
        border-radius: 30px;
        background-color: #0d6efd;
        color: white;
        text-decoration: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .acquire-template-btn:hover {
        background-color: #0b5ed7;
        transform: translateY(-2px);
        color: white;
    }
    
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
        backdrop-filter: blur(5px);
    }
    
    .template-author {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 999;
        background-color: rgba(0, 0, 0, 0.6);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .template-author img {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid rgba(255,255,255,0.5);
    }
    
    .qr-code-btn {
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 1000;
        padding: 10px 20px;
        border-radius: 30px;
        background-color: #6c757d;
        color: white;
        text-decoration: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        border: none;
        cursor: pointer;
    }
    
    .qr-code-btn:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
        color: white;
    }
    
    /* Самописное модальное окно для QR-кода */
    .custom-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 2000;
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(3px);
    }
    
    .custom-modal-overlay.show {
        display: flex;
        animation: fadeIn 0.4s forwards;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    .custom-modal-container {
        margin: auto;
        width: 90%;
        max-width: 500px;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        transform: scale(0.8) translateY(30px);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .custom-modal-overlay.show .custom-modal-container {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
    
    .custom-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .custom-modal-title {
        font-size: 1.25rem;
        font-weight: 500;
        margin: 0;
        color: #212529;
    }
    
    .custom-modal-close {
        border: none;
        background: none;
        font-size: 24px;
        line-height: 0.7;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        color: #6c757d;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .custom-modal-close:hover {
        background-color: #e9ecef;
        color: #212529;
    }
    
    .custom-modal-body {
        padding: 24px;
    }
    
    .custom-modal-footer {
        display: flex;
        justify-content: flex-end;
        padding: 16px 20px;
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }
    
    .custom-btn {
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    
    .custom-btn-secondary {
        background-color: #6c757d;
        color: white;
    }
    
    .custom-btn-secondary:hover {
        background-color: #5a6268;
    }
    
    /* QR-код и его контейнер */
    #qrcode-container {
        text-align: center;
        margin: 0 auto 20px;
        padding: 20px;
        background-color: white;
        border-radius: 8px;
        transition: all 0.3s ease;
        min-height: 240px;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }
    
    #qrcode img {
        margin: 0 auto;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    #qrcode img:hover {
        transform: scale(1.02);
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
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .modal-qr-instructions {
        margin-top: 20px;
        font-size: 14px;
        text-align: center;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #0d6efd;
    }
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/styles.blade.php ENDPATH**/ ?>
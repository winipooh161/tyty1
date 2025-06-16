<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    
    <!-- SEO метаданные -->
    <title><?php echo e($template->name); ?> | <?php echo e(config('app.name')); ?></title>
    <meta name="description" content="<?php echo e($template->description ?? 'Просмотр шаблона ' . $template->name); ?>">
    
    <!-- Подключение стилей из шаблона, если они есть -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
        }
        
        /* Добавляем небольшую информационную панель, которую можно скрыть */
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
            right: 10px;
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
    </style>

    <!-- Внешние стили, используемые в шаблоне, если они есть -->
    <?php if(isset($template->custom_data['external_styles'])): ?>
        <?php $__currentLoopData = $template->custom_data['external_styles']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $style): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <link rel="stylesheet" href="<?php echo e($style); ?>">
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</head>
<body>
    <!-- Маленькая информационная панель, которую можно скрыть -->
    <div class="info-panel" id="infoPanel">
        <span><?php echo e($template->name); ?></span>
        <?php if(auth()->guard()->check()): ?>
            <a href="<?php echo e(route('client.templates.editor', $template->id)); ?>" class="btn-use">Использовать шаблон</a>
        <?php else: ?>
            <a href="<?php echo e(route('login')); ?>" class="btn-use">Войти для использования</a>
        <?php endif; ?>
        <span class="close-panel" onclick="togglePanel()">&times;</span>
    </div>

    <!-- Кнопка для отображения панели -->
    <div class="toggle-panel" id="togglePanel" onclick="togglePanel()">
        <i class="bi bi-info"></i>
    </div>
    
    <!-- Непосредственное содержимое шаблона без оболочки -->
    <?php echo $template->html_content; ?>


    <div class="card-footer">
        <a href="<?php echo e(route('client.templates.create-new', $template->id)); ?>" class="btn btn-primary">
            <i class="bi bi-pencil-square me-1"></i> Создать мой шаблон
        </a>
    </div>

    <script>
        // Простой скрипт для скрытия/показа информационной панели
        function togglePanel() {
            const panel = document.getElementById('infoPanel');
            const toggleBtn = document.getElementById('togglePanel');
            
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                toggleBtn.innerHTML = '<i class="bi bi-info"></i>';
            } else {
                panel.classList.add('hidden');
                toggleBtn.innerHTML = '<i class="bi bi-info"></i>';
            }
        }
        
        // Запоминаем состояние панели в localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const panelState = localStorage.getItem('infoPanelHidden');
            if (panelState === 'true') {
                document.getElementById('infoPanel').classList.add('hidden');
            }
            
            document.getElementById('infoPanel').querySelector('.close-panel').addEventListener('click', function() {
                localStorage.setItem('infoPanelHidden', 'true');
            });
            
            document.getElementById('togglePanel').addEventListener('click', function() {
                const isHidden = document.getElementById('infoPanel').classList.contains('hidden');
                localStorage.setItem('infoPanelHidden', !isHidden);
            });
        });
        
        // Обработка форм в шаблоне
        document.addEventListener('DOMContentLoaded', function() {
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
    </script>
</body>
</html>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/show.blade.php ENDPATH**/ ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/davidshimjs-qrcodejs@0.0.2/qrcode.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const coverVideo = document.getElementById('coverVideo');
        
        // Автопроигрывание видео
        if (coverVideo) {
            coverVideo.play().catch(error => {
                console.log("Автовоспроизведение видео не поддерживается");
            });
        }
        
        // Обработчик для инфо-панели
        window.togglePanel = function() {
            const infoPanel = document.getElementById('infoPanel');
            infoPanel.classList.toggle('hidden');
        };
        
        // Генерация QR-кода
        const qrCodeContainer = document.getElementById('qrcode');
        const qrLoadingContainer = document.getElementById('qr-loading');
        
        if (qrCodeContainer) {
            if (qrLoadingContainer) {
                qrLoadingContainer.style.display = 'block';
            }
            
            setTimeout(function() {
                try {
                    @auth
                    const templateId = {{ $userTemplate->id }};
                    const userId = {{ Auth::id() }};
                    
                    @php
                    $acquiredTemplate = \App\Models\AcquiredTemplate::where('user_template_id', $userTemplate->id)
                        ->where('user_id', Auth::id())
                        ->first();
                    @endphp
                    
                    @if($acquiredTemplate)
                    const acquiredId = {{ $acquiredTemplate->id }};
                    @else
                    const acquiredId = 0;
                    @endif
                    
                    const timestamp = Math.floor(Date.now() / 1000);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                    const baseUrl = '{{ url("/") }}';
                    const changeStatusUrl = `${baseUrl}/template-status/change/${templateId}/${userId}/${acquiredId}/${timestamp}?_token=${csrfToken}&nonce={{ Str::random(10) }}`;
                    
                    if (qrCodeContainer) {
                        qrCodeContainer.innerHTML = '';
                        
                        new QRCode(qrCodeContainer, {
                            text: changeStatusUrl,
                            width: 160,
                            height: 160,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    }
                    @else
                    if (qrCodeContainer) {
                        qrCodeContainer.innerHTML = `
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                Войдите в систему, чтобы увидеть QR-код
                            </div>
                        `;
                    }
                    @endauth
                    
                    if (qrLoadingContainer) {
                        setTimeout(() => {
                            qrLoadingContainer.style.display = 'none';
                        }, 300);
                    }
                } catch (error) {
                    console.error('Ошибка при генерации QR-кода', error);
                    if (qrLoadingContainer) {
                        qrLoadingContainer.style.display = 'none';
                    }
                }
            }, 400);
        }

        // Автоподстановка имени пользователя
        @auth
        const recipientNameFields = document.querySelectorAll('[data-editable="recipient_name"]');
        
        if (recipientNameFields.length > 0) {
            const userName = @json(Auth::user()->name);
            
            recipientNameFields.forEach(field => {
                field.innerHTML = userName;
            });
        }
        @endauth
        
        // Обработка полей серии с данными из контроллера
        const seriesData = @json($seriesData ?? null) || {
            'acquired_count': 0, 
            'scan_count': 0, 
            'series_quantity': 1, 
            'required_scans': 1
        };
        
        console.log('Series data received:', seriesData);
        
        // Функция для обновления значений полей серии
        function updateSeriesField(fieldName, value) {
            // Ищем элементы по data-editable атрибуту
            const fields = document.querySelectorAll(`[data-editable="${fieldName}"]`);
            
            fields.forEach(field => {
                console.log(`Updating field ${fieldName} with value ${value}`, field);
                
                if (field.tagName === 'INPUT') {
                    field.value = value;
                    field.placeholder = value;
                    field.setAttribute('value', value);
                } else {
                    field.textContent = value;
                    field.innerHTML = value;
                }
            });
            
            if (fields.length === 0) {
                console.log(`Field ${fieldName} not found`);
            }
        }
        
        // Заполняем данные с задержкой для гарантии загрузки DOM
        setTimeout(function() {
            // Заполняем данные о количестве выпущенных шаблонов
            updateSeriesField('series_quantity', seriesData.series_quantity || 1);
            
            // Заполняем данные о количестве полученных шаблонов
            updateSeriesField('series_received', seriesData.acquired_count || 0);
            
            // Заполняем данные о количестве сканирований
            updateSeriesField('scan_count', seriesData.scan_count || 0);
            
            // Заполняем данные о требуемых сканированиях
            updateSeriesField('required_scans', seriesData.required_scans || 1);
        }, 500);
        
        // Обработка элементов с шаблонными тегами Blade (как резервный вариант)
        document.querySelectorAll('[data-editable]').forEach(function(element) {
            const fieldName = element.getAttribute('data-editable');
            
            // Проверяем, есть ли в тексте шаблонные теги Blade
            if (element.innerText && element.innerText.includes('{{')) {
                // Используем данные из seriesData если они доступны
                if (seriesData[fieldName] !== undefined) {
                    if (element.tagName === 'INPUT') {
                        element.value = seriesData[fieldName];
                    } else {
                        element.innerText = seriesData[fieldName];
                    }
                }
            }
            
            // Аналогично для value у input элементов
            if (element.tagName === 'INPUT' && element.value && element.value.includes('{{')) {
                if (seriesData[fieldName] !== undefined) {
                    element.value = seriesData[fieldName];
                }
            }
            
            // Специальная обработка для полей серии
            if (['series_quantity', 'series_received', 'scan_count', 'required_scans'].includes(fieldName)) {
                let value = 0;
                switch (fieldName) {
                    case 'series_quantity':
                        value = seriesData.series_quantity || 1;
                        break;
                    case 'series_received':
                        value = seriesData.acquired_count || 0;
                        break;
                    case 'scan_count':
                        value = seriesData.scan_count || 0;
                        break;
                    case 'required_scans':
                        value = seriesData.required_scans || 1;
                        break;
                }
                
                if (element.tagName === 'INPUT') {
                    element.value = value;
                    element.placeholder = value;
                } else {
                    element.textContent = value;
                }
            }
        });
    });
</script>

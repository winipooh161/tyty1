<div id="templateContent" class="template-content">
    <!-- Подключаем информацию о серии -->
    @include('public.partials.template-series-badge')
    
    <!-- HTML содержимое шаблона -->
    {!! $userTemplate->html_content !!}
    
    <!-- Кнопки действий (получить/отказ) встроены в контент -->
    <div class="template-actions-container">
        @auth
            @php
                $alreadyAcquired = \App\Models\AcquiredTemplate::where('user_id', Auth::id())
                    ->where('user_template_id', $userTemplate->id)
                    ->exists();
                    
                $isOwner = $userTemplate->user_id == Auth::id();
                
                // Проверяем, является ли шаблон серией
                $customData = is_array($userTemplate->custom_data) 
                    ? $userTemplate->custom_data 
                    : (json_decode($userTemplate->custom_data, true) ?: []);
                    
                $isSeries = isset($customData['is_series']) && $customData['is_series'];
                
                $acquiredCount = \App\Models\AcquiredTemplate::where('user_template_id', $userTemplate->id)->count();
                
                // Для серий используем указанное количество, для обычных - максимум 1
                $totalCount = $isSeries ? ($customData['series_quantity'] ?? 1) : 1;
                $isAvailable = $acquiredCount < $totalCount;
                
                // Логируем состояние для отладки
                \Log::info('Template actions state (in content)', [
                    'template_id' => $userTemplate->id,
                    'user_id' => Auth::id(),
                    'already_acquired' => $alreadyAcquired,
                    'is_owner' => $isOwner,
                    'is_available' => $isAvailable,
                    'acquired_count' => $acquiredCount,
                    'total_count' => $totalCount,
                    'is_series' => $isSeries
                ]);
            @endphp
            
            @if(!$alreadyAcquired && !$isOwner && $isAvailable)
                <div class="certificate-buttons" id="certificate-action-buttons">
                    <form action="{{ route('series.acquire', $userTemplate->id) }}" method="POST" 
                          style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; background: #fff; padding: 10px;"
                          onsubmit="handleFormSubmit(this, event)">
                        @csrf
                        
                        <!-- Добавляем скрытые поля для отладки -->
                        <input type="hidden" name="debug_user_id" value="{{ Auth::id() }}">
                        <input type="hidden" name="debug_template_id" value="{{ $userTemplate->id }}">
                        <input type="hidden" name="debug_timestamp" value="{{ time() }}">
                        
                        <a href="{{ route('home') }}" class="acquire-template-btn red">
                            <i class="bi bi-box-arrow-in-right"></i> Отказ
                        </a>
                        <button type="submit" class="acquire-template-btn green" 
                                style="border: none; cursor: pointer;">
                            <i class="bi bi-download"></i> Получить 
                        </button>
                    </form>
                    
                    <!-- Отладочная информация -->
                    <div class="mt-2 small text-muted">
                        <p>Отладка: Маршрут: {{ route('series.acquire', $userTemplate->id) }}</p>
                        <p>Пользователь: {{ Auth::id() }}, Шаблон: {{ $userTemplate->id }}</p>
                        <p>Уже получен: {{ $alreadyAcquired ? 'Да' : 'Нет' }}, Владелец: {{ $isOwner ? 'Да' : 'Нет' }}, Доступен: {{ $isAvailable ? 'Да' : 'Нет' }}</p>
                    </div>
                </div>
            @elseif($alreadyAcquired)
                <div class="certificate-buttons" id="certificate-action-buttons">
                    <div style="width: 100%; text-align: center; padding: 20px; background: #f8f9fa;">
                        <p class="text-success">
                            <i class="bi bi-check-circle"></i> Вы уже получили этот шаблон
                        </p>
                        <a href="{{ route('home') }}" class="btn btn-primary mt-2">
                            Перейти в полученные шаблоны
                        </a>
                    </div>
                </div>
            @elseif($isOwner)
                <div class="certificate-buttons" id="certificate-action-buttons">
                    <div style="width: 100%; text-align: center; padding: 20px; background: #f8f9fa;">
                        <p class="text-info">
                            <i class="bi bi-person-circle"></i> Это ваш шаблон
                        </p>
                        <a href="{{ route('user.templates') }}" class="btn btn-primary mt-2">
                            Управление шаблонами
                        </a>
                    </div>
                </div>
            @elseif(!$isAvailable)
                <div class="certificate-buttons" id="certificate-action-buttons">
                    <div style="width: 100%; text-align: center; padding: 20px; background: #f8f9fa;">
                        <p class="text-warning">
                            <i class="bi bi-exclamation-triangle"></i> Шаблон больше не доступен
                        </p>
                        <small class="text-muted">Получено: {{ $acquiredCount }} из {{ $totalCount }}</small>
                    </div>
                </div>
            @endif
        @else
            <div class="certificate-buttons" id="certificate-action-buttons">
                <div style="width: 100%; text-align: center; padding: 20px; background: #f8f9fa;">
                    <a href="{{ route('login') }}" class="acquire-template-btn">
                        <i class="bi bi-box-arrow-in-right"></i> Войти для получения
                    </a>
                </div>
            </div>
        @endauth
    </div>
</div>

<script>
function handleFormSubmit(form, event) {
    console.log('Form submit started', {
        action: form.action,
        method: form.method,
        formData: new FormData(form)
    });
    
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Отключаем кнопку и показываем индикатор загрузки
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Получение...';
    
    // Добавляем обработчик для отслеживания отправки формы
    form.addEventListener('submit', function() {
        console.log('Form actually submitted');
    });
    
    // Если есть ошибка, возвращаем кнопку в исходное состояние через 10 секунд
    setTimeout(() => {
        if (submitButton.disabled) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-download"></i> Получить';
            console.log('Button reset due to timeout');
        }
    }, 10000);
}
</script>

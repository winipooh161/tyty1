<!-- Модальное окно пополнения баланса SUP -->
<div class="modal-panel fade" id="sub-profile-modal">
    <div class="modal-backdrop"></div>
    <div class="modal-panel-dialog">
        <div class="modal-panel-content">
          
           
            <div class="modal-panel-body">
                <div class="mb-4">
                    <p>Донатны процедуры. Чем больше сумма - тем выгоднее.</p>
                </div>
                
                <form id="sub-payment-form">
                    
                    <div class="form-group mb-3">
                        <label for="payment-amount" class="form-label">Сумма в рублях</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="payment-amount" min="100" value="100" step="100">
                            <span class="input-group-text">₽</span>
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="sup-amount" class="form-label">Вы получите SUP</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="sup-amount" readonly value="20">
                            <span class="input-group-text">SUP</span>
                        </div>
                        <small class="text-muted mt-1" id="exchange-rate">Текущий курс: 5.00 ₽ за 1 SUP</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Отправить</button>
                </form>
                
                <!-- Добавляем индикатор ошибки -->
                <div id="payment-error" class="alert alert-danger mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentInput = document.getElementById('payment-amount');
    const supOutput = document.getElementById('sup-amount');
    const exchangeRate = document.getElementById('exchange-rate');
    const form = document.getElementById('sub-payment-form');
    const errorDiv = document.getElementById('payment-error');
    
    // Функция для расчета SUP с использованием API
    function calculateSup(amount) {
        // Показать индикатор загрузки
        supOutput.value = '...';
        exchangeRate.textContent = 'Расчет...';
        
        // Получаем CSRF-токен из мета-тега
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        
      
        
        // Запрос к серверу с улучшенной обработкой ошибок
        fetch('/sup/calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ amount: Number(amount) })
        })
        .then(response => {
            // Сначала проверяем, успешен ли запрос
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || `Ошибка сервера: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            // Проверяем, есть ли поле sup в ответе
            if (!data.success || data.sup === undefined) {
                throw new Error('Некорректный ответ сервера');
            }
            
            // Обновляем значения в форме
            supOutput.value = data.sup;
            exchangeRate.textContent = `Текущий курс: ${data.rate.toFixed(2)} ₽ за 1 SUP`;
            
            // Скрываем сообщение об ошибке
            errorDiv.style.display = 'none';
        })
        .catch(error => {
            console.error('Ошибка при расчете SUP:', error);
            supOutput.value = '?';
            exchangeRate.textContent = 'Ошибка расчета';
            errorDiv.textContent = `Не удалось рассчитать количество SUP: ${error.message}`;
            errorDiv.style.display = 'block';
            
            // Через 10 секунд снова попробуем рассчитать
            setTimeout(() => {
                if (supOutput.value === '?') {
                    calculateSup(amount);
                }
            }, 10000);
        });
    }
    
    // Обработчик изменения суммы
    paymentInput.addEventListener('input', function() {
        let amount = parseInt(this.value) || 0;
        
        // Ограничение минимального значения
        if (amount < 100) {
            amount = 100;
            this.value = 100;
        }
        
        // Вызываем API для расчета SUP
        calculateSup(amount);
    });
    
    // Инициализация первоначального значения
    calculateSup(100);
    
    // Обработчик отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const amount = parseInt(paymentInput.value);
        const sup = parseInt(supOutput.value);
        
        // Проверяем минимальную сумму
        if (amount < 100) {
            errorDiv.textContent = 'Минимальная сумма пополнения - 100 ₽';
            errorDiv.style.display = 'block';
            return;
        }
        
        // Проверка валидности sup
        if (isNaN(sup) || sup <= 0) {
            errorDiv.textContent = 'Некорректное значение SUP';
            errorDiv.style.display = 'block';
            return;
        }
        
        // Проверяем наличие CSRF токена
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken || !csrfToken.content) {
            errorDiv.textContent = 'Ошибка: CSRF токен не найден';
            errorDiv.style.display = 'block';
            console.error('CSRF token not found');
            return;
        }
        
        // Показываем индикатор загрузки
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Обработка...';
        errorDiv.style.display = 'none';
        
        // Отправляем запрос на сервер
        fetch('/sup/payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content
            },
            body: JSON.stringify({
                amount: amount,
                sup_amount: sup
            })
        })
        .then(response => {
            // Сначала проверяем ответ - если статус не 2xx, создаем ошибку
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || `Ошибка сервера: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Закрыть модальное окно
                closeModalPanel('sub-profile-modal');
                
                // Показать сообщение об успехе
                alert(`Успешно пополнено на ${sup} SUP`);
                
                // Обновить отображаемый баланс, если есть
                const balanceElement = document.getElementById('user-sup-balance');
                if (balanceElement) {
                    balanceElement.textContent = `Баланс SUP: ${data.new_balance}`;
                }
            } else {
                // Отображаем сообщение об ошибке
                errorDiv.textContent = data.message || 'Произошла ошибка при пополнении баланса';
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            errorDiv.textContent = `Произошла ошибка при обработке платежа: ${error.message}`;
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            // Восстанавливаем кнопку
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        });
    });
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-sub.blade.php ENDPATH**/ ?>
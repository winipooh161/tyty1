<!-- Модальное окно пополнения баланса SUP -->
<div class="modal-panel fade" id="sub-profile-modal">
    <div class="modal-backdrop"></div>
    <div class="modal-panel-dialog">
        <div class="modal-panel-content">
          
           
            <div class="modal-panel-body modal-panel-body-sub">
                <div class="mb-4">
                    <h4 style="text-align: center">Присоединяйся к команде</h4>
                </div>
                
                <form id="sub-payment-form">
                     <div class="mb-4">
                    <p style="text-align: center">Чем больше сабов выбираете, тем выгоднее курс</p>
                </div>
                    <div class="form-group mb-3">
                       
                        <div class="input-group"  style="display: flex; align-items: center; gap: 10px;">
                            <div class="input-group-svg">
                                <img src="{{ asset('images/icons/ru.svg') }}" width="55" height="55" alt="SUP Icon" class="icon">
                            </div>
                            <input type="number" class="form-control" id="payment-amount" min="100" value="100" max="2500" step="100">
                            <button type="button" class="btn btn-outline-secondary value-control green" id="increase-amount">+</button>
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">

                        <div class="input-group" style="display: flex; align-items: center; gap: 10px;">
                            
                            <button type="button" class="btn btn-outline-secondary value-control red" id="decrease-sup">-</button>
                            <input type="text" class="form-control" id="sup-amount" readonly value="20">
                            <div class="input-group-svg">
                                <img src="{{ asset('images/icons/house.svg') }}" width="55" height="55" alt="SUP Icon" class="icon">
                            </div>
                        </div>
                        <small class="text-muted mt-1" id="exchange-rate" style="text-align: center">Текущий курс: 5.00 ₽ за 1 SUP</small>
                    </div>
                    
                  
                </form>
                  <button type="submit" class="btn btn-primary w-100">Донат</button>
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
    
    // Получаем кнопку отправки формы
    const submitButton = document.querySelector('.btn.btn-primary.w-100');
    
    // Добавляем обработчики для кнопок + и -
    const increaseAmountBtn = document.getElementById('increase-amount');
    const decreaseSupBtn = document.getElementById('decrease-sup');
    
    // Функция для обновления значения с шагом 10
    function updateValue(input, increment) {
        let currentValue = parseInt(input.value) || 0;
        let newValue = currentValue + increment;
        
        // Проверяем ограничения min и max
        const minValue = parseInt(input.min) || 0;
        const maxValue = parseInt(input.max) || Infinity;
        
        // Применяем ограничения
        if (newValue < minValue) newValue = minValue;
        if (newValue > maxValue) newValue = maxValue;
        
        // Обновляем значение
        input.value = newValue;
        
        // Запускаем событие input для активации расчета SUP
        const event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
        
        // Анимация кнопки
        animateButtonClick(increment > 0 ? increaseAmountBtn : decreaseSupBtn);
    }
    
    // Получаем текущий курс из надписи
    function getCurrentRate() {
        const rateText = exchangeRate.textContent;
        const rateMatch = rateText.match(/(\d+(\.\d+)?)/);
        return rateMatch ? parseFloat(rateMatch[1]) : 5.0; // По умолчанию 5.0
    }
    
    // Функция анимации клика по кнопке
    function animateButtonClick(button) {
        button.classList.add('btn-clicked');
        setTimeout(() => {
            button.classList.remove('btn-clicked');
        }, 200);
    }
    
    // Обработчики для кнопок увеличения и уменьшения суммы
    increaseAmountBtn.addEventListener('click', function() {
        updateValue(paymentInput, 10);
    });
    
    // Обработчик для кнопки уменьшения sup
    decreaseSupBtn.addEventListener('click', function() {
        // Получаем текущее значение SUP
        let currentSup = parseInt(supOutput.value) || 0;
        
        // Уменьшаем на 1 единицу
        if (currentSup > 1) { // Проверка минимального значения
            // Получаем текущий курс обмена
            const rate = getCurrentRate();
            
            // Рассчитываем, на сколько нужно уменьшить сумму платежа
            // чтобы получить уменьшение SUP на 1
            const decreaseAmount = Math.ceil(rate);
            
            // Получаем текущую сумму платежа
            let currentPayment = parseInt(paymentInput.value) || 0;
            
            // Уменьшаем сумму платежа
            let newPayment = currentPayment - decreaseAmount;
            
            // Проверяем минимальное значение для платежа
            if (newPayment >= parseInt(paymentInput.min)) {
                paymentInput.value = newPayment;
                
                // Запускаем расчет SUP
                calculateSup(newPayment);
                
                // Анимируем кнопку
                animateButtonClick(decreaseSupBtn);
            }
        }
    });
    
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
    
    // Обработчик клика на кнопке "Донат"
    submitButton.addEventListener('click', function(e) {
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
    
    // Остальной существующий код...
    // ...existing code...
});
</script>

<style>
/* Стили для кнопок +/- */
.value-control {
    min-width: 40px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.value-control:hover:not([disabled]) {
    background-color: #6c757d;
    color: white;
    border-color: #6c757d;
}

/* Анимация при клике */
.btn-clicked {
    transform: scale(0.95);
    background-color: #6c757d !important;
    color: white !important;
}

/* Стилизуем input */
.input-group .form-control {
    text-align: center;
}

/* Стилизуем дополнительные элементы */
#exchange-rate {
    display: block;
    text-align: right;
    margin-top: 5px;
    font-size: 0.85rem;
}
</style>
</style>

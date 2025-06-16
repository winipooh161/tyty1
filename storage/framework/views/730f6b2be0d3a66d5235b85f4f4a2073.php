<!-- Модальное окно для управления SUP и статуса VIP -->
<div class="modal-panel" id="sup-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-panel-dialog">
        <div class="modal-panel-content">
            <div class="modal-panel-header">
                <h5 class="modal-panel-title">Управление балансом</h5>
                <button type="button" class="modal-panel-close" onclick="window.modalPanel.closeModal()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-panel-body">
                <!-- Секция текущего баланса -->
                <div class="sup-balance-section mb-4">
                    <div class="sup-balance-card p-3 rounded">
                        <h6>Текущий баланс</h6>
                        <div class="d-flex align-items-center">
                            <span class="display-6" id="modal-sup-balance">0</span>
                            <span class="ms-2 sup-currency">SUP</span>
                        </div>
                        <div class="text-muted small mt-1">Последнее обновление: <span id="sup-last-update">сейчас</span></div>
                    </div>
                </div>
                
                <!-- Секция пополнения баланса -->
                <div class="sup-recharge-section mb-4">
                    <h6>Пополнить баланс</h6>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="supAmount" class="form-label">Выберите сумму пополнения:</label>
                                <div class="sup-amount-options">
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="supAmount" id="sup100" value="100" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="sup100">100 SUP</label>
                                        
                                        <input type="radio" class="btn-check" name="supAmount" id="sup500" value="500" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary" for="sup500">500 SUP</label>
                                        
                                        <input type="radio" class="btn-check" name="supAmount" id="sup1000" value="1000" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="sup1000">1000 SUP</label>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="supCustomAmount" placeholder="Другая сумма">
                                        <span class="input-group-text">SUP</span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="rechargeButton" class="btn btn-primary w-100">
                                <i class="bi bi-cash-coin me-2"></i> Пополнить баланс
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Секция VIP статуса -->
                <div class="vip-status-section mb-3">
                    <h6>VIP статус</h6>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1">VIP доступ</h6>
                                    <p class="text-muted mb-0 small">Расширенные возможности и привилегии</p>
                                </div>
                                <div class="vip-price">
                                    <span class="badge bg-warning text-dark">1000 SUP / месяц</span>
                                </div>
                            </div>
                            <div class="vip-features mb-3">
                                <div class="vip-feature d-flex align-items-center mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Доступ ко всем шаблонам</span>
                                </div>
                                <div class="vip-feature d-flex align-items-center mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Отсутствие рекламы</span>
                                </div>
                                <div class="vip-feature d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Приоритетная поддержка</span>
                                </div>
                            </div>
                            <button type="button" id="activateVipButton" class="btn btn-warning w-100">
                                <i class="bi bi-star-fill me-2"></i> Активировать VIP статус
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- История транзакций -->
                <div class="sup-history-section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">История транзакций</h6>
                        <a href="<?php echo e(route('sup.index')); ?>" class="btn btn-sm btn-outline-secondary">
                            Полная история
                        </a>
                    </div>
                    <div class="card bg-light">
                        <div class="list-group list-group-flush" id="transactions-list">
                            <div class="list-group-item px-3 py-2 text-center" id="loading-transactions">
                                <div class="spinner-border spinner-border-sm text-secondary" role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                                <span class="ms-2">Загрузка транзакций...</span>
                            </div>
                            <!-- Здесь будут элементы истории транзакций -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик открытия модального окна SUP
    document.addEventListener('modal.opened', function(event) {
        if (event.detail?.modalId === 'sup-modal') {
            loadSupBalanceAndTransactions();
        }
    });
    
    // Загрузка баланса SUP и транзакций
    function loadSupBalanceAndTransactions() {
        // Загружаем баланс
        fetch('/sup/balance')
            .then(response => response.json())
            .then(data => {
                document.getElementById('modal-sup-balance').textContent = data.formatted_balance;
                document.getElementById('sup-last-update').textContent = new Date().toLocaleTimeString();
            })
            .catch(error => {
                console.error('Ошибка при загрузке баланса:', error);
                document.getElementById('modal-sup-balance').textContent = 'Ошибка';
            });
            
        // Загружаем историю транзакций (последние 5)
        fetch('/sup?limit=5')
            .then(response => response.text())
            .then(html => {
                // Создаем временный элемент для парсинга HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Находим таблицу транзакций в полученном HTML
                const transactionRows = doc.querySelectorAll('.transaction-item');
                
                const transactionsList = document.getElementById('transactions-list');
                document.getElementById('loading-transactions').style.display = 'none';
                
                if (transactionRows.length === 0) {
                    // Если транзакций нет
                    transactionsList.innerHTML = `
                        <div class="list-group-item px-3 py-3 text-center text-muted">
                            <i class="bi bi-info-circle me-2"></i>
                            История транзакций пуста
                        </div>
                    `;
                } else {
                    // Выводим последние 5 транзакций
                    let transactionsHTML = '';
                    let counter = 0;
                    
                    transactionRows.forEach(row => {
                        if (counter < 5) {
                            const amount = row.querySelector('.transaction-amount').textContent;
                            const desc = row.querySelector('.transaction-desc').textContent;
                            const date = row.querySelector('.transaction-date').textContent;
                            
                            const isPositive = !amount.includes('-');
                            const amountClass = isPositive ? 'text-success' : 'text-danger';
                            
                            transactionsHTML += `
                                <div class="list-group-item px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div>${desc}</div>
                                            <small class="text-muted">${date}</small>
                                        </div>
                                        <span class="${amountClass} fw-bold">${amount}</span>
                                    </div>
                                </div>
                            `;
                            counter++;
                        }
                    });
                    
                    transactionsList.innerHTML = transactionsHTML;
                }
            })
            .catch(error => {
                console.error('Ошибка при загрузке транзакций:', error);
                document.getElementById('loading-transactions').innerHTML = `
                    <div class="text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Ошибка загрузки транзакций
                    </div>
                `;
            });
    }
    
    // Обработчик кнопки пополнения баланса
    document.getElementById('rechargeButton').addEventListener('click', function() {
        // Получаем выбранную сумму
        let amount = 0;
        const checkedRadio = document.querySelector('input[name="supAmount"]:checked');
        const customAmount = document.getElementById('supCustomAmount').value;
        
        if (customAmount && !isNaN(customAmount) && customAmount > 0) {
            amount = parseInt(customAmount);
        } else if (checkedRadio) {
            amount = parseInt(checkedRadio.value);
        }
        
        if (amount <= 0) {
            showToast('Укажите корректную сумму пополнения', 'error');
            return;
        }
        
        // Демонстрационное пополнение (в реальном приложении здесь была бы интеграция с платежной системой)
        showToast('Переход к пополнению баланса на ' + amount + ' SUP', 'info');
        
        // Здесь можно открыть модальное окно с формой оплаты или перенаправить на страницу оплаты
        setTimeout(() => {
            alert('Это демонстрационная версия. В реальном приложении здесь открылась бы форма оплаты.');
        }, 1000);
    });
    
    // Обработчик кнопки активации VIP статуса
    document.getElementById('activateVipButton').addEventListener('click', function() {
        // Проверка наличия достаточного количества SUP (демо)
        const currentBalance = parseInt(document.getElementById('modal-sup-balance').textContent.replace(/[^\d]/g, ''));
        
        if (currentBalance < 1000) {
            showToast('Недостаточно средств для активации VIP статуса', 'error');
            return;
        }
        
        // Отправляем запрос на активацию VIP (в реальном приложении)
        showToast('Запрос на активацию VIP статуса отправлен', 'info');
        
        setTimeout(() => {
            alert('Это демонстрационная версия. В реальном приложении здесь произошла бы активация VIP статуса.');
        }, 1000);
    });
    
    // Обработка ввода пользовательской суммы
    document.getElementById('supCustomAmount').addEventListener('input', function() {
        // При вводе пользовательской суммы снимаем выделение с радио-кнопок
        const radios = document.querySelectorAll('input[name="supAmount"]');
        radios.forEach(radio => radio.checked = false);
    });
    
    // Функция для отображения уведомлений
    function showToast(message, type = 'success') {
        // Проверяем наличие функции в глобальной области видимости
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            // Реализация функции, если она отсутствует в глобальной области
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }
});
</script>

<style>
/* Дополнительные стили для модального окна SUP */
.sup-currency {
    font-size: 1.2rem;
    color: #6c757d;
    align-self: flex-end;
    margin-bottom: 0.75rem;
}

.sup-amount-options .btn-group {
    flex-wrap: nowrap;
}

.sup-amount-options .btn {
    flex: 1;
}

.vip-feature i {
    font-size: 1.2rem;
}

.vip-price .badge {
    font-size: 0.85rem;
    padding: 0.5em 0.8em;
}

/* Анимация для транзакций */
#transactions-list .list-group-item {
    transition: background-color 0.3s;
}

#transactions-list .list-group-item:hover {
    background-color: rgba(0, 0, 0, 0.03);
}
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-sup.blade.php ENDPATH**/ ?>
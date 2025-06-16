class CacheManager {
    constructor() {
        this.storageKeys = [
            'mobileNavPicker',
            'userPreferences',
            'tempData',
            'bootstrap',
            'vite'
        ];
    }

    /**
     * Полная очистка всех браузерных кэшей и хранилищ
     */
    async clearAllBrowserCaches() {
        console.log('🧹 Начинаем очистку браузерных кэшей...');
        
        try {
            // Очистка localStorage
            this.clearLocalStorage();
            
            // Очистка sessionStorage
            this.clearSessionStorage();
            
            // Очистка IndexedDB
            await this.clearIndexedDB();
            
            // Очистка Service Workers
            await this.clearServiceWorkers();
            
            // Очистка Cache API
            await this.clearCacheAPI();
            
            // Сброс модулей
            this.resetModules();
            
            console.log('✅ Все браузерные кэши очищены!');
            return true;
            
        } catch (error) {
            console.error('❌ Ошибка при очистке кэшей:', error);
            return false;
        }
    }

    /**
     * Очистка localStorage
     */
    clearLocalStorage() {
        try {
            // Сохраняем важные данные
            const importantData = {};
            const preserveKeys = ['theme', 'language', 'auth_token'];
            
            preserveKeys.forEach(key => {
                if (localStorage.getItem(key)) {
                    importantData[key] = localStorage.getItem(key);
                }
            });
            
            // Очищаем всё
            localStorage.clear();
            
            // Восстанавливаем важные данные
            Object.keys(importantData).forEach(key => {
                localStorage.setItem(key, importantData[key]);
            });
            
            console.log('   ✓ localStorage cleared');
        } catch (error) {
            console.warn('   ⚠️ Ошибка очистки localStorage:', error);
        }
    }

    /**
     * Очистка sessionStorage
     */
    clearSessionStorage() {
        try {
            sessionStorage.clear();
            console.log('   ✓ sessionStorage cleared');
        } catch (error) {
            console.warn('   ⚠️ Ошибка очистки sessionStorage:', error);
        }
    }

    /**
     * Очистка IndexedDB
     */
    async clearIndexedDB() {
        try {
            if ('indexedDB' in window) {
                const databases = await indexedDB.databases();
                await Promise.all(
                    databases.map(db => {
                        return new Promise((resolve, reject) => {
                            const deleteRequest = indexedDB.deleteDatabase(db.name);
                            deleteRequest.onsuccess = () => resolve();
                            deleteRequest.onerror = () => reject(deleteRequest.error);
                        });
                    })
                );
                console.log('   ✓ IndexedDB cleared');
            }
        } catch (error) {
            console.warn('   ⚠️ Ошибка очистки IndexedDB:', error);
        }
    }

    /**
     * Очистка Service Workers
     */
    async clearServiceWorkers() {
        try {
            if ('serviceWorker' in navigator) {
                const registrations = await navigator.serviceWorker.getRegistrations();
                await Promise.all(
                    registrations.map(registration => registration.unregister())
                );
                console.log('   ✓ Service Workers cleared');
            }
        } catch (error) {
            console.warn('   ⚠️ Ошибка очистки Service Workers:', error);
        }
    }

    /**
     * Очистка Cache API
     */
    async clearCacheAPI() {
        try {
            if ('caches' in window) {
                const cacheNames = await caches.keys();
                await Promise.all(
                    cacheNames.map(cacheName => caches.delete(cacheName))
                );
                console.log('   ✓ Cache API cleared');
            }
        } catch (error) {
            console.warn('   ⚠️ Ошибка очистки Cache API:', error);
        }
    }

    /**
     * Сброс модулей JavaScript
     */
    resetModules() {
        try {
            // Сброс глобальных переменных приложения
            if (window.mobileNavPicker) {
                window.mobileNavPicker = null;
            }
            
            if (window.bootstrap) {
                // Не сбрасываем bootstrap полностью, но сбрасываем состояния
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    const bsModal = window.bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.dispose();
                    }
                });
            }
            
            // Сброс кэшированных CSS
            const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
            stylesheets.forEach(sheet => {
                if (sheet.href && sheet.href.includes('build/')) {
                    const newHref = sheet.href.split('?')[0] + '?v=' + Date.now();
                    sheet.href = newHref;
                }
            });
            
            console.log('   ✓ Modules reset');
        } catch (error) {
            console.warn('   ⚠️ Ошибка сброса модулей:', error);
        }
    }

    /**
     * Создание кнопки для ручной очистки кэшей
     */
    createClearCacheButton() {
        const button = document.createElement('button');
        button.textContent = '🧹 Очистить кэши';
        button.className = 'btn btn-warning btn-sm';
        button.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
            opacity: 0.7;
        `;
        
        button.addEventListener('click', async () => {
            button.disabled = true;
            button.textContent = 'Очищаем...';
            
            const success = await this.clearAllBrowserCaches();
            
            if (success) {
                button.textContent = '✅ Готово!';
                setTimeout(() => {
                    if (confirm('Кэши очищены! Перезагрузить страницу?')) {
                        window.location.reload(true);
                    }
                }, 1000);
            } else {
                button.textContent = '❌ Ошибка';
                button.disabled = false;
            }
        });
        
        document.body.appendChild(button);
        return button;
    }

    /**
     * Автоматическая очистка при обновлении версии
     */
    checkVersionAndClear() {
        const currentVersion = document.querySelector('meta[name="app-version"]')?.content || '1.0.0';
        const savedVersion = localStorage.getItem('app_version');
        
        if (savedVersion && savedVersion !== currentVersion) {
            console.log('🔄 Обнаружено обновление версии, очищаем кэши...');
            this.clearAllBrowserCaches().then(() => {
                localStorage.setItem('app_version', currentVersion);
            });
        } else if (!savedVersion) {
            localStorage.setItem('app_version', currentVersion);
        }
    }
}

// Глобальный экземпляр
window.cacheManager = new CacheManager();

// Автоматическая проверка версии при загрузке
document.addEventListener('DOMContentLoaded', () => {
    window.cacheManager.checkVersionAndClear();
    
    // Добавляем кнопку в development режиме
    if (document.querySelector('meta[name="app-env"]')?.content === 'local') {
        window.cacheManager.createClearCacheButton();
    }
});

// Глобальная функция для ручного вызова
window.clearAllCaches = () => window.cacheManager.clearAllBrowserCaches();

export default CacheManager;

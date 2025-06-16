export class MobileNavStorage {
    constructor() {
        this.storageKey = 'mobileNavState';
        this.routeToIconMap = this.createRouteMapping();
        this.storageAvailable = this.checkStorageAvailability();
    }

    // Проверка доступности localStorage
    checkStorageAvailability() {
        try {
            const testKey = '__storage_test__';
            localStorage.setItem(testKey, testKey);
            localStorage.removeItem(testKey);
            return true;
        } catch (e) {
            return false;
        }
    }

    createRouteMapping() {
        return {
            '/': 'home',
            '/home': 'home',
            '/user/templates': 'profile',
            '/profile': 'profile',
            '/client/templates/categories': 'create',
            '/client/templates': 'create',
            '/client/projects': 'create',
            '/client/images': 'create',
            '/admin/dashboard': 'admin',
            '/admin': 'admin',
            '/games': 'games',
            '/email': 'email'
        };
    }

    // Определение иконки для текущей страницы
    getIconForCurrentPage() {
        const currentPath = window.location.pathname;
        
        // Точное совпадение
        if (this.routeToIconMap[currentPath]) {
            return this.routeToIconMap[currentPath];
        }
        
        // Частичное совпадение (для вложенных маршрутов)
        for (const [route, iconId] of Object.entries(this.routeToIconMap)) {
            if (route !== '/' && currentPath.startsWith(route)) {
                return iconId;
            }
        }
        
        return null;
    }

    // Определение приоритетной иконки
    getPriorityIcon() {
        // 1. Иконка для текущей страницы
        const currentPageIcon = this.getIconForCurrentPage();
        if (currentPageIcon) {
            return currentPageIcon;
        }
        
        // По умолчанию - home
        return 'home';
    }

    // Безопасная очистка устаревших данных
    clearExpiredData() {
        if (!this.storageAvailable) return;
        
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (stored) {
                const state = JSON.parse(stored);
                if (!state || !state.timestamp || Date.now() - state.timestamp > 24 * 60 * 60 * 1000) {
                    localStorage.removeItem(this.storageKey);
                }
            }
        } catch (e) {
            console.warn('Ошибка при очистке устаревших данных навигации:', e);
            
            // При ошибке пытаемся полностью удалить ключ
            try {
                localStorage.removeItem(this.storageKey);
            } catch (clearError) {
                // Игнорируем, если удаление тоже не удалось
            }
        }
    }
}
   
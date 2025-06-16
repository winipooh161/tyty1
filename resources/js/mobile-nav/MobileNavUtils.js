export class MobileNavUtils {
    constructor(core, scroll) {
        this.core = core;
        this.scroll = scroll;
    }

    // Удалены методы centerActiveItem() и centerItemByIndex()

    // Метод для получения информации о границах скролла
    getScrollBounds() {
        const containerWidth = this.core.container.offsetWidth;
        const scrollWidth = this.core.iconsContainer.scrollWidth;
        const maxScrollLeft = scrollWidth - containerWidth;
        
        return {
            containerWidth,
            scrollWidth,
            maxScrollLeft,
            currentScrollLeft: this.core.container.scrollLeft,
            sidePadding: this.core.sidePadding
        };
    }

    // Метод для получения максимального scrollLeft для последнего элемента
    getMaxScrollForLastItem() {
        if (this.core.items.length === 0) return 0;
        
        const lastItem = this.core.items[this.core.items.length - 1];
        const containerWidth = this.core.container.offsetWidth;
        const maxScroll = lastItem.offsetLeft - containerWidth / 2 + lastItem.offsetWidth / 2;
        
        return Math.max(0, maxScroll);
    }

    setupIntersectionObserver() {
        if (!('IntersectionObserver' in window)) return;

        const observerOptions = {
            root: this.core.container,
            rootMargin: '0px',
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('mb-visible');
                } else {
                    entry.target.classList.remove('mb-visible');
                }
            });
        }, observerOptions);

        document.addEventListener('DOMContentLoaded', () => {
            const items = document.querySelectorAll('.mb-icon-wrapper');
            items.forEach(item => observer.observe(item));
        });
    }
}
    
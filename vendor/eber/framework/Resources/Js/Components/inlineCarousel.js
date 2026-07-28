/**
 * InlineCarousel
 * Módulo para generar carruseles en línea (múltiples elementos visibles, estilo lista de iconos).
 * Permite modo con límite (scroll nativo) y modo sin fin (infinito).
 */
class InlineCarousel {
    constructor(element) {
        this.container = element;
        if (!this.container) return;

        this.track = this.container.querySelector('.ic-track');
        this.prevBtn = this.container.querySelector('.ic-prev');
        this.nextBtn = this.container.querySelector('.ic-next');
        
        if (!this.track) return;

        // true = infinito (sin fin), false = con límite
        this.isLoop = this.container.dataset.loop === 'true'; 
        this.isAnimating = false;
        
        this.init();
    }

    init() {
        // Estilos base requeridos
        this.container.style.position = 'relative';
        this.container.style.overflow = 'hidden';
        
        this.track.style.display = 'flex';
        this.track.style.alignItems = 'center'; 
        this.track.style.flex = '1';
        this.track.style.minWidth = '0';
        
        // Evitar que el drag nativo de las imágenes rompa el evento mousedown
        this.track.querySelectorAll('img').forEach(img => {
            img.style.pointerEvents = 'none';
        });
        this.track.querySelectorAll('a').forEach(a => {
            a.addEventListener('dragstart', (e) => e.preventDefault());
        });
        
        const gap = this.container.dataset.gap || '16px';
        this.track.style.gap = gap;

        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.slide('next'));
            this.nextBtn.style.transition = 'opacity 0.3s ease';
        }
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.slide('prev'));
            this.prevBtn.style.transition = 'opacity 0.3s ease';
        }

        // Hover logic for buttons visibility
        this.isMouse = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        this.isHovered = !this.isMouse; // Always true on touch devices
        
        if (this.isMouse) {
            this.container.addEventListener('mouseenter', () => {
                this.isHovered = true;
                this.updateButtons();
            });
            this.container.addEventListener('mouseleave', () => {
                this.isHovered = false;
                this.updateButtons();
            });
        }
        
        // Set initial visibility
        this.updateButtons();

        if (!this.isLoop) {
            this.track.style.overflowX = 'hidden'; // Oculto nativo, lo movemos con JS y touch
            this.track.style.scrollBehavior = 'smooth';
            this.track.style.scrollbarWidth = 'none'; 
            this.track.style.msOverflowStyle = 'none'; 
            
            this.track.addEventListener('scroll', () => this.updateButtons());
            setTimeout(() => this.updateButtons(), 150);
            window.addEventListener('resize', () => this.updateButtons());
        } else {
            this.track.style.overflowX = 'visible';
        }

        this.initDrag();
    }

    initDrag() {
        let isDown = false;
        let startX = 0;
        let diffX = 0;
        let isDragging = false;
        
        const start = (e) => {
            isDown = true;
            isDragging = false;
            startX = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
            diffX = 0;
            if (!this.isLoop) {
                this.track.style.scrollBehavior = 'auto'; // Quitar smooth para que el drag sea inmediato
            }
        };
        
        const move = (e) => {
            if (!isDown) return;
            const currentX = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
            diffX = currentX - startX;
            
            // Si nos movemos más de 3px, consideramos que es un drag (y no un click)
            if (Math.abs(diffX) > 3) {
                isDragging = true;
            }

            if (!this.isLoop && isDragging) {
                e.preventDefault(); 
                this.track.scrollLeft -= diffX;
                startX = currentX; 
            }
        };
        
        const end = (e) => {
            if (!isDown) return;
            isDown = false;
            
            if (!this.isLoop) {
                this.track.style.scrollBehavior = 'smooth'; // Restaurar smooth
            } else {
                // Modo loop: Si arrastró suficiente, cambia al siguiente/anterior
                if (Math.abs(diffX) > 30) {
                    if (diffX > 0) this.slide('prev');
                    else this.slide('next');
                }
            }
        };

        // Eventos Mouse
        this.track.addEventListener('mousedown', start);
        this.track.addEventListener('mousemove', move);
        this.track.addEventListener('mouseup', end);
        this.track.addEventListener('mouseleave', end);

        // Eventos Touch
        this.track.addEventListener('touchstart', start, { passive: true });
        this.track.addEventListener('touchmove', move, { passive: false });
        this.track.addEventListener('touchend', end);

        // Prevenir click en enlaces si hubo arrastre
        this.track.addEventListener('click', (e) => {
            if (isDragging) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }

    getItemWidth() {
        const item = this.track.children[0];
        if (!item) return 0;
        const gap = parseFloat(window.getComputedStyle(this.track).gap) || 0;
        return item.offsetWidth + gap;
    }

    slide(direction) {
        if (this.isAnimating) return;
        
        if (!this.isLoop) {
            // Carrusel con límite
            // Usa el ancho visible del track para desplazar más efectivamente que el ancho de un ítem
            const scrollAmount = this.track.clientWidth * 0.8; 
            
            if (direction === 'next') {
                this.track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            } else {
                this.track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            }
            return;
        }

        // Carrusel infinito (manipulación DOM)
        this.isAnimating = true;
        const moveDistance = this.getItemWidth();

        if (direction === 'next') {
            this.track.style.transition = 'transform 0.3s ease-in-out';
            this.track.style.transform = `translateX(-${moveDistance}px)`;
            
            setTimeout(() => {
                this.track.style.transition = 'none';
                this.track.appendChild(this.track.children[0]);
                this.track.style.transform = 'translateX(0)';
                this.isAnimating = false;
            }, 300);
        } else {
            this.track.style.transition = 'none';
            this.track.prepend(this.track.children[this.track.children.length - 1]);
            this.track.style.transform = `translateX(-${moveDistance}px)`;
            
            this.track.offsetHeight; // Forzar reflow
            
            this.track.style.transition = 'transform 0.3s ease-in-out';
            this.track.style.transform = 'translateX(0)';
            
            setTimeout(() => {
                this.isAnimating = false;
            }, 300);
        }
    }

    updateButtons() {
        if (!this.prevBtn || !this.nextBtn) return;
        
        if (this.isLoop) {
            this.prevBtn.style.opacity = this.isHovered ? '1' : '0';
            this.prevBtn.style.pointerEvents = this.isHovered ? 'auto' : 'none';
            this.nextBtn.style.opacity = this.isHovered ? '1' : '0';
            this.nextBtn.style.pointerEvents = this.isHovered ? 'auto' : 'none';
            return;
        }
        
        const atStart = this.track.scrollLeft <= 0;
        const atEnd = Math.ceil(this.track.scrollLeft + this.track.clientWidth) >= this.track.scrollWidth - 2;

        this.prevBtn.style.opacity = (atStart || !this.isHovered) ? '0' : '1';
        this.prevBtn.style.pointerEvents = (atStart || !this.isHovered) ? 'none' : 'auto';

        this.nextBtn.style.opacity = (atEnd || !this.isHovered) ? '0' : '1';
        this.nextBtn.style.pointerEvents = (atEnd || !this.isHovered) ? 'none' : 'auto';
    }
}

export function inlineCarousel() {
    const initAll = () => {
        document.querySelectorAll('.inline-carousel').forEach(el => {
            // Si no tiene la instancia JS, significa que no ha sido inicializado
            // o es un clon (ej. creado por modal.js usando innerHTML)
            if (!el.__ic_instance) {
                el.__ic_instance = new InlineCarousel(el);
                el.classList.add('ic-initialized');
            }
        });
    };
    
    initAll();

    const observer = new MutationObserver((mutations) => {
        let shouldInit = false;
        for (let m of mutations) {
            if (m.addedNodes.length > 0) {
                shouldInit = true;
                break;
            }
        }
        if (shouldInit) initAll();
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
}

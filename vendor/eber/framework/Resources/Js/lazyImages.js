/**
 * Lazy Loading Progressive de Imágenes
 * 
 * Implementa lazy loading con blur-up placeholder para mejorar el perceived performance.
 * Compatible con navegadores modernos y con fallback para navegadores antiguos.
 */

export function lazyImage() {

    (function() {
        'use strict';
    
        class LazyImageLoader {
            constructor() {
                this.images = document.querySelectorAll('img[loading="lazy"]');
                this.observer = null;
                this.supportsNativeLazyLoading = 'loading' in HTMLImageElement.prototype;
                
                this.init();
            }
    
            init() {
                if (this.supportsNativeLazyLoading) {
                    this.initNativeLazyLoading();
                } else {
                    this.initIntersectionObserver();
                }
                
                this.initBlurUp();
            }
    
            /**
             * Inicializa lazy loading nativo del navegador
             */
            initNativeLazyLoading() {
                this.images.forEach(img => {
                    this.setupImage(img);
                });
            }
    
            /**
             * Inicializa Intersection Observer como fallback
             */
            initIntersectionObserver() {
                const options = {
                    rootMargin: '200px 0px', // Cargar 200px antes de visible
                    threshold: 0.01
                };
    
                this.observer = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            this.setupImage(img);
                            observer.unobserve(img);
                        }
                    });
                }, options);
    
                this.images.forEach(img => {
                    this.observer.observe(img);
                });
            }
    
            /**
             * Configura imagen para carga con blur-up
             */
            setupImage(img) {
                img.classList.add('loading');
    
                img.addEventListener('load', () => {
                    this.onImageLoad(img);
                });
    
                img.addEventListener('error', () => {
                    this.onImageError(img);
                });
            }
    
            /**
             * Maneja evento de carga exitosa de imagen
             */
            onImageLoad(img) {
                img.classList.remove('loading');
                img.classList.add('loaded');
                
                // Eliminar placeholder background después de fade
                setTimeout(() => {
                    img.style.backgroundImage = 'none';
                }, 300);
            }
    
            /**
             * Maneja error de carga de imagen
             */
            onImageError(img) {
                img.classList.remove('loading');
                img.classList.add('error');
                
                // Mostrar placeholder de error
                const errorPlaceholder = this.createErrorPlaceholder();
                if (errorPlaceholder && img.parentNode) {
                    img.parentNode.insertBefore(errorPlaceholder, img);
                    img.style.display = 'none';
                }
            }
    
            /**
             * Inicializa efecto blur-up
             */
            initBlurUp() {
                const style = document.createElement('style');
                style.textContent = `
                    .lazy-image {
                        transition: filter 0.3s ease, transform 0.3s ease;
                        will-change: filter;
                    }
                    
                    .lazy-image.loading {
                        filter: blur(10px);
                        transform: scale(1.05);
                    }
                    
                    .lazy-image.loaded {
                        filter: blur(0);
                        transform: scale(1);
                    }
                    
                    .lazy-image.error {
                        background-color: #f0f0f0;
                        min-height: 200px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .error-placeholder {
                        background-color: #f0f0f0;
                        color: #666;
                        padding: 2rem;
                        text-align: center;
                        border-radius: 8px;
                    }
                    
                    .error-placeholder svg {
                        width: 48px;
                        height: 48px;
                        margin-bottom: 1rem;
                        opacity: 0.5;
                    }
                `;
                document.head.appendChild(style);
            }
    
            /**
             * Crea placeholder de error
             */
            createErrorPlaceholder() {
                const placeholder = document.createElement('div');
                placeholder.className = 'error-placeholder';
                placeholder.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p>No se pudo cargar la imagen</p>
                `;
                return placeholder;
            }
    
            /**
             * Carga imagen manualmente (para uso programático)
             */
            static loadImage(img) {
                const loader = new LazyImageLoader();
                loader.setupImage(img);
            }
    
            /**
             * Recarga imagen (útil para retry de errores)
             */
            static retryLoad(img) {
                const src = img.src;
                img.classList.remove('error');
                img.style.display = 'block';
                img.src = '';
                setTimeout(() => {
                    img.src = src;
                }, 100);
            }
        }
    
        // Exponer al global scope
        window.LazyImageLoader = LazyImageLoader;
    
        // Inicializar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                new LazyImageLoader();
            });
        } else {
            new LazyImageLoader();
        }
    
        // Exponer función global para uso manual
        window.lazyLoadImage = function(img) {
            LazyImageLoader.loadImage(img);
        };
    
        window.retryImageLoad = function(img) {
            LazyImageLoader.retryLoad(img);
        };
    
    })();
    
}


/**
 * Lazy Loading de Imagens com Skeleton Loader
 * Suporte ao loading="lazy" nativo + efeito shimmer
 */
(function() {
    'use strict';

    const supportsNativeLazyLoading = 'loading' in HTMLImageElement.prototype;

    // Adicionar transição suave quando imagem carregar
    function handleImageLoad(img) {
        // Aguardar um frame para garantir que a imagem foi renderizada
        requestAnimationFrame(() => {
            img.classList.remove('lazy-load', 'lazy-card', 'lazy-thumb', 'lazy-hero');
            img.classList.add('loaded');
        });
    }

    function handleImageError(img) {
        img.classList.remove('lazy-load', 'lazy-card', 'lazy-thumb', 'lazy-hero');
        img.classList.add('error');
        
        // Manter o background cinza se erro
        img.style.backgroundColor = '#f3f4f6';
    }

    if (supportsNativeLazyLoading) {
        // Browser moderno - apenas gerenciar classes
        document.addEventListener('DOMContentLoaded', function() {
            const lazyImages = document.querySelectorAll('img.lazy-load, img[loading="lazy"]');
            
            lazyImages.forEach(img => {
                // Se já carregou (cache)
                if (img.complete && img.naturalHeight !== 0) {
                    handleImageLoad(img);
                } else {
                    img.addEventListener('load', () => handleImageLoad(img), { once: true });
                    img.addEventListener('error', () => handleImageError(img), { once: true });
                }
            });
        });
        return;
    }

    // Fallback para browsers antigos
    const config = {
        rootMargin: '50px 0px',
        threshold: 0.01
    };

    function onIntersection(entries, observer) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                
                if (img.dataset.src && img.src !== img.dataset.src) {
                    img.src = img.dataset.src;
                }
                
                if (img.complete && img.naturalHeight !== 0) {
                    handleImageLoad(img);
                } else {
                    img.addEventListener('load', () => handleImageLoad(img), { once: true });
                    img.addEventListener('error', () => handleImageError(img), { once: true });
                }
                
                observer.unobserve(img);
            }
        });
    }

    const observer = new IntersectionObserver(onIntersection, config);

    function observeImages() {
        const lazyImages = document.querySelectorAll('img.lazy-load, img[loading="lazy"]');
        lazyImages.forEach(img => {
            // Se já carregou (cache)
            if (img.complete && img.naturalHeight !== 0) {
                handleImageLoad(img);
            } else {
                observer.observe(img);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', observeImages);
    } else {
        observeImages();
    }

    window.lazyLoadObserver = {
        observe: function(img) {
            if (img && (img.classList.contains('lazy-load') || img.getAttribute('loading') === 'lazy')) {
                if (img.complete && img.naturalHeight !== 0) {
                    handleImageLoad(img);
                } else {
                    observer.observe(img);
                }
            }
        },
        refresh: observeImages
    };
})();

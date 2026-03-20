/**
 * Agregador de Contenido - JavaScript Frontend
 *
 * @package Flavor_Chat_IA
 * @since 3.5.0
 */

(function($) {
    'use strict';

    /**
     * Video Modal - Reproduce videos de YouTube en modal
     */
    const VideoModal = {
        modal: null,

        init: function() {
            this.createModal();
            this.bindEvents();
        },

        createModal: function() {
            if ($('.video-modal-overlay').length) return;

            const html = `
                <div class="video-modal-overlay">
                    <div class="video-modal-content">
                        <button class="video-modal-close" aria-label="Cerrar">&times;</button>
                        <iframe src="" allowfullscreen></iframe>
                    </div>
                </div>
            `;
            $('body').append(html);
            this.modal = $('.video-modal-overlay');
        },

        bindEvents: function() {
            const self = this;

            // Click en thumbnail de video
            $(document).on('click', '.video-thumbnail[data-video-id]', function(e) {
                e.preventDefault();
                const videoId = $(this).data('video-id');
                if (videoId) {
                    self.open(videoId);
                }
            });

            // Cerrar modal
            $(document).on('click', '.video-modal-close, .video-modal-overlay', function(e) {
                if (e.target === this) {
                    self.close();
                }
            });

            // Cerrar con ESC
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.modal.hasClass('active')) {
                    self.close();
                }
            });
        },

        open: function(videoId) {
            const iframe = this.modal.find('iframe');
            iframe.attr('src', `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`);
            this.modal.addClass('active');
            $('body').css('overflow', 'hidden');
        },

        close: function() {
            const iframe = this.modal.find('iframe');
            iframe.attr('src', '');
            this.modal.removeClass('active');
            $('body').css('overflow', '');
        }
    };

    /**
     * Carrusel de Videos
     */
    const VideoCarousel = {
        init: function() {
            $('.flavor-agregador-carrusel').each(function() {
                const $carousel = $(this);
                const $track = $carousel.find('.carrusel-track');
                const $slides = $carousel.find('.carrusel-slide');
                const $prevBtn = $carousel.find('.carrusel-prev');
                const $nextBtn = $carousel.find('.carrusel-next');

                let currentIndex = 0;
                let slidesPerView = VideoCarousel.getSlidesPerView();
                let maxIndex = Math.max(0, $slides.length - slidesPerView);

                // Calcular ancho de slides
                const updateSlideWidth = function() {
                    slidesPerView = VideoCarousel.getSlidesPerView();
                    maxIndex = Math.max(0, $slides.length - slidesPerView);
                    currentIndex = Math.min(currentIndex, maxIndex);
                    goToSlide(currentIndex);
                };

                // Ir a slide
                const goToSlide = function(index) {
                    currentIndex = Math.max(0, Math.min(index, maxIndex));
                    const slideWidth = $slides.first().outerWidth(true);
                    $track.css('transform', `translateX(-${currentIndex * slideWidth}px)`);
                    updateButtons();
                };

                // Actualizar botones
                const updateButtons = function() {
                    $prevBtn.prop('disabled', currentIndex === 0).css('opacity', currentIndex === 0 ? 0.5 : 1);
                    $nextBtn.prop('disabled', currentIndex >= maxIndex).css('opacity', currentIndex >= maxIndex ? 0.5 : 1);
                };

                // Eventos
                $prevBtn.on('click', function() {
                    goToSlide(currentIndex - 1);
                });

                $nextBtn.on('click', function() {
                    goToSlide(currentIndex + 1);
                });

                // Resize
                $(window).on('resize', debounce(updateSlideWidth, 200));

                // Autoplay
                if ($carousel.data('autoplay') === 'true') {
                    setInterval(function() {
                        if (currentIndex >= maxIndex) {
                            goToSlide(0);
                        } else {
                            goToSlide(currentIndex + 1);
                        }
                    }, 5000);
                }

                // Inicializar
                updateButtons();
            });
        },

        getSlidesPerView: function() {
            const width = window.innerWidth;
            if (width <= 480) return 1;
            if (width <= 768) return 2;
            if (width <= 1024) return 3;
            return 4;
        }
    };

    /**
     * Lazy Loading de imágenes
     */
    const LazyLoad = {
        init: function() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                            }
                            observer.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '100px'
                });

                document.querySelectorAll('.agregador-grid img[data-src]').forEach(function(img) {
                    observer.observe(img);
                });
            }
        }
    };

    /**
     * Infinite Scroll (opcional)
     */
    const InfiniteScroll = {
        page: 1,
        loading: false,
        hasMore: true,

        init: function($container, endpoint) {
            if (!$container.data('infinite-scroll')) return;

            const self = this;
            const $grid = $container.find('.agregador-grid');

            $(window).on('scroll', debounce(function() {
                if (self.loading || !self.hasMore) return;

                const containerBottom = $container.offset().top + $container.outerHeight();
                const scrollBottom = $(window).scrollTop() + $(window).height();

                if (scrollBottom >= containerBottom - 200) {
                    self.loadMore($grid, endpoint);
                }
            }, 200));
        },

        loadMore: function($grid, endpoint) {
            const self = this;
            self.loading = true;
            self.page++;

            // Mostrar loading
            $grid.append('<div class="loading-more">Cargando...</div>');

            $.get(endpoint, { page: self.page }, function(response) {
                $grid.find('.loading-more').remove();

                if (response.items && response.items.length > 0) {
                    // Añadir items
                    response.items.forEach(function(item) {
                        $grid.append(self.renderItem(item));
                    });

                    if (self.page >= response.total_pages) {
                        self.hasMore = false;
                    }
                } else {
                    self.hasMore = false;
                }

                self.loading = false;
            });
        },

        renderItem: function(item) {
            // Template genérico - se puede personalizar
            return `
                <article class="contenido-card">
                    <div class="contenido-media">
                        <img src="${item.thumbnail}" alt="${item.title}" loading="lazy">
                    </div>
                    <div class="contenido-body">
                        <h3 class="contenido-titulo">
                            <a href="${item.link}">${item.title}</a>
                        </h3>
                        <span class="contenido-fecha">${item.date}</span>
                    </div>
                </article>
            `;
        }
    };

    /**
     * Helpers
     */
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    /**
     * Inicialización
     */
    $(document).ready(function() {
        VideoModal.init();
        VideoCarousel.init();
        LazyLoad.init();
    });

})(jQuery);

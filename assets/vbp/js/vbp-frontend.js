/**
 * Visual Builder Pro - Frontend Interactivity
 * JavaScript para componentes interactivos en el frontend
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

(function() {
    'use strict';

    /**
     * Inicializar todos los componentes
     */
    function initVBPComponents() {
        initCountdowns();
        initAccordions();
        initTabs();
        initCarousels();
        initTimelines();
        initBeforeAfter();
        initProgressBars();
        initAlerts();
        initGalleryLightbox();
    }

    /**
     * Countdown Timer
     */
    function initCountdowns() {
        var countdowns = document.querySelectorAll('.vbp-countdown[data-target-date]');

        countdowns.forEach(function(countdown) {
            var targetDate = new Date(countdown.dataset.targetDate);
            var endMessage = countdown.dataset.endMessage || 'Tiempo agotado';

            function updateCountdown() {
                var now = new Date();
                var diff = Math.max(0, targetDate - now);

                if (diff === 0) {
                    var timer = countdown.querySelector('.vbp-countdown__timer');
                    if (timer) {
                        timer.innerHTML = '<div class="vbp-countdown__ended">' + endMessage + '</div>';
                    }
                    return;
                }

                var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((diff % (1000 * 60)) / 1000);

                var daysEl = countdown.querySelector('[data-unit="days"] .vbp-countdown__value');
                var hoursEl = countdown.querySelector('[data-unit="hours"] .vbp-countdown__value');
                var minsEl = countdown.querySelector('[data-unit="minutes"] .vbp-countdown__value');
                var secsEl = countdown.querySelector('[data-unit="seconds"] .vbp-countdown__value');

                if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
                if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
                if (minsEl) minsEl.textContent = String(minutes).padStart(2, '0');
                if (secsEl) secsEl.textContent = String(seconds).padStart(2, '0');
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        });
    }

    /**
     * Accordion
     */
    function initAccordions() {
        var accordions = document.querySelectorAll('.vbp-accordion');

        accordions.forEach(function(accordion) {
            var allowMultiple = accordion.dataset.allowMultiple === 'true';
            var items = accordion.querySelectorAll('.vbp-accordion__item');

            items.forEach(function(item) {
                var header = item.querySelector('.vbp-accordion__header');

                if (header) {
                    header.addEventListener('click', function() {
                        var isOpen = item.classList.contains('vbp-accordion__item--open');

                        if (!allowMultiple) {
                            items.forEach(function(otherItem) {
                                otherItem.classList.remove('vbp-accordion__item--open');
                            });
                        }

                        if (isOpen) {
                            item.classList.remove('vbp-accordion__item--open');
                        } else {
                            item.classList.add('vbp-accordion__item--open');
                        }
                    });
                }
            });
        });
    }

    /**
     * Tabs
     */
    function initTabs() {
        var tabContainers = document.querySelectorAll('.vbp-tabs');

        tabContainers.forEach(function(container) {
            var buttons = container.querySelectorAll('.vbp-tabs__button');
            var panels = container.querySelectorAll('.vbp-tabs__panel');

            buttons.forEach(function(button, index) {
                button.addEventListener('click', function() {
                    buttons.forEach(function(btn) {
                        btn.classList.remove('vbp-tabs__button--active');
                    });
                    panels.forEach(function(panel) {
                        panel.classList.remove('vbp-tabs__panel--active');
                    });

                    button.classList.add('vbp-tabs__button--active');
                    if (panels[index]) {
                        panels[index].classList.add('vbp-tabs__panel--active');
                    }
                });
            });
        });
    }

    /**
     * Carrusel Avanzado
     */
    function initCarousels() {
        var carousels = document.querySelectorAll('.vbp-carousel');

        carousels.forEach(function(carousel) {
            var track = carousel.querySelector('.vbp-carousel__track');
            var slides = carousel.querySelectorAll('.vbp-carousel__slide');
            var prevBtn = carousel.querySelector('.vbp-carousel__arrow--prev');
            var nextBtn = carousel.querySelector('.vbp-carousel__arrow--next');
            var dots = carousel.querySelectorAll('.vbp-carousel__dot');

            if (!track || slides.length === 0) return;

            var currentIndex = 0;
            var autoplay = carousel.dataset.autoplay === 'true';
            var interval = parseInt(carousel.dataset.interval) || 5000;
            var loop = carousel.dataset.loop === 'true';
            var slidesVisible = parseInt(carousel.dataset.slidesVisible) || 1;
            var efecto = carousel.dataset.effect || 'slide';
            var autoplayTimer = null;
            var touchStartX = 0;
            var touchEndX = 0;

            var totalSlides = slides.length;
            var maxIndex = Math.max(0, totalSlides - slidesVisible);

            // Configurar ancho de slides
            function configurarSlides() {
                var slideWidth = 100 / slidesVisible;
                slides.forEach(function(slide) {
                    slide.style.width = slideWidth + '%';
                });
            }

            // Ir a slide específico
            function goToSlide(index) {
                if (!loop) {
                    index = Math.max(0, Math.min(index, maxIndex));
                } else {
                    if (index < 0) index = maxIndex;
                    if (index > maxIndex) index = 0;
                }

                currentIndex = index;

                if (efecto === 'fade') {
                    slides.forEach(function(slide, i) {
                        slide.style.opacity = i === currentIndex ? '1' : '0';
                        slide.style.position = i === currentIndex ? 'relative' : 'absolute';
                    });
                } else {
                    var translateX = -(currentIndex * (100 / slidesVisible));
                    track.style.transform = 'translateX(' + translateX + '%)';
                }

                // Actualizar dots
                dots.forEach(function(dot, i) {
                    dot.classList.toggle('vbp-carousel__dot--active', i === currentIndex);
                });
            }

            // Navegación
            function nextSlide() {
                goToSlide(currentIndex + 1);
            }

            function prevSlide() {
                goToSlide(currentIndex - 1);
            }

            // Event listeners para botones
            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    prevSlide();
                    resetAutoplay();
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    nextSlide();
                    resetAutoplay();
                });
            }

            // Event listeners para dots
            dots.forEach(function(dot, i) {
                dot.addEventListener('click', function() {
                    goToSlide(i);
                    resetAutoplay();
                });
            });

            // Touch/Swipe support
            carousel.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            carousel.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            }, { passive: true });

            function handleSwipe() {
                var diffX = touchStartX - touchEndX;
                var threshold = 50;

                if (Math.abs(diffX) > threshold) {
                    if (diffX > 0) {
                        nextSlide();
                    } else {
                        prevSlide();
                    }
                    resetAutoplay();
                }
            }

            // Autoplay
            function startAutoplay() {
                if (autoplay && totalSlides > 1) {
                    autoplayTimer = setInterval(nextSlide, interval);
                }
            }

            function stopAutoplay() {
                if (autoplayTimer) {
                    clearInterval(autoplayTimer);
                    autoplayTimer = null;
                }
            }

            function resetAutoplay() {
                stopAutoplay();
                startAutoplay();
            }

            // Pausar autoplay al hacer hover
            carousel.addEventListener('mouseenter', stopAutoplay);
            carousel.addEventListener('mouseleave', startAutoplay);

            // Keyboard navigation
            carousel.setAttribute('tabindex', '0');
            carousel.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    prevSlide();
                    resetAutoplay();
                } else if (e.key === 'ArrowRight') {
                    nextSlide();
                    resetAutoplay();
                }
            });

            // Inicializar
            configurarSlides();
            goToSlide(0);
            startAutoplay();
        });
    }

    /**
     * Timeline con animación al scroll
     */
    function initTimelines() {
        var timelines = document.querySelectorAll('.vbp-timeline--animated');

        if (!timelines.length) return;

        var observerTimeline = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('vbp-visible');
                }
            });
        }, {
            threshold: 0.2,
            rootMargin: '0px 0px -50px 0px'
        });

        timelines.forEach(function(timeline) {
            var items = timeline.querySelectorAll('.vbp-timeline__item');
            items.forEach(function(item, index) {
                // Añadir delay escalonado
                item.style.transitionDelay = (index * 0.15) + 's';
                observerTimeline.observe(item);
            });
        });
    }

    /**
     * Before/After Slider
     */
    function initBeforeAfter() {
        var sliders = document.querySelectorAll('.vbp-before-after');

        sliders.forEach(function(slider) {
            var container = slider.querySelector('.vbp-before-after__container');
            var handle = slider.querySelector('.vbp-before-after__slider');
            var beforeImg = slider.querySelector('.vbp-before-after__before');
            var isVertical = slider.classList.contains('vbp-before-after--vertical');
            var isDragging = false;

            if (!handle || !beforeImg) return;

            function updatePosition(e) {
                var rect = container.getBoundingClientRect();
                var position;

                if (isVertical) {
                    position = ((e.clientY - rect.top) / rect.height) * 100;
                } else {
                    position = ((e.clientX - rect.left) / rect.width) * 100;
                }

                position = Math.max(0, Math.min(100, position));

                if (isVertical) {
                    beforeImg.style.clipPath = 'inset(0 0 ' + (100 - position) + '% 0)';
                    handle.style.top = position + '%';
                } else {
                    beforeImg.style.clipPath = 'inset(0 ' + (100 - position) + '% 0 0)';
                    handle.style.left = position + '%';
                }
            }

            handle.addEventListener('mousedown', function(e) {
                e.preventDefault();
                isDragging = true;
                document.body.style.cursor = isVertical ? 'ns-resize' : 'ew-resize';
            });

            document.addEventListener('mousemove', function(e) {
                if (isDragging) {
                    updatePosition(e);
                }
            });

            document.addEventListener('mouseup', function() {
                isDragging = false;
                document.body.style.cursor = '';
            });

            // Touch support
            handle.addEventListener('touchstart', function(e) {
                isDragging = true;
            }, { passive: true });

            document.addEventListener('touchmove', function(e) {
                if (isDragging && e.touches[0]) {
                    updatePosition(e.touches[0]);
                }
            }, { passive: true });

            document.addEventListener('touchend', function() {
                isDragging = false;
            });
        });
    }

    /**
     * Progress Bars - Animate on scroll
     */
    function initProgressBars() {
        var progressBars = document.querySelectorAll('.vbp-progress-bar--animated');

        if (!progressBars.length) return;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var fill = entry.target.querySelector('.vbp-progress-bar__fill');
                    if (fill) {
                        var targetWidth = fill.dataset.value || fill.style.width;
                        fill.style.width = '0';
                        setTimeout(function() {
                            fill.style.width = targetWidth;
                        }, 100);
                    }
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        progressBars.forEach(function(bar) {
            observer.observe(bar);
        });
    }

    /**
     * Alerts - Dismiss functionality
     */
    function initAlerts() {
        var dismissButtons = document.querySelectorAll('.vbp-alert__dismiss');

        dismissButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var alert = button.closest('.vbp-alert');
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(20px)';
                    alert.style.transition = 'opacity 0.3s, transform 0.3s';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }
            });
        });
    }

    /**
     * Gallery Lightbox - Full featured image viewer
     */
    function initGalleryLightbox() {
        var galleries = document.querySelectorAll('.vbp-gallery[data-lightbox="true"], .vbp-gallery--lightbox');

        if (!galleries.length) return;

        // Create lightbox container (singleton)
        var lightbox = document.getElementById('vbp-lightbox');
        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.id = 'vbp-lightbox';
            lightbox.className = 'vbp-lightbox';
            lightbox.innerHTML = [
                '<div class="vbp-lightbox__overlay"></div>',
                '<div class="vbp-lightbox__container">',
                '  <button type="button" class="vbp-lightbox__close" aria-label="Cerrar">&times;</button>',
                '  <button type="button" class="vbp-lightbox__nav vbp-lightbox__nav--prev" aria-label="Anterior">',
                '    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">',
                '      <polyline points="15 18 9 12 15 6"/>',
                '    </svg>',
                '  </button>',
                '  <div class="vbp-lightbox__content">',
                '    <img class="vbp-lightbox__image" src="" alt="">',
                '    <div class="vbp-lightbox__caption"></div>',
                '    <div class="vbp-lightbox__loader"></div>',
                '  </div>',
                '  <button type="button" class="vbp-lightbox__nav vbp-lightbox__nav--next" aria-label="Siguiente">',
                '    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">',
                '      <polyline points="9 18 15 12 9 6"/>',
                '    </svg>',
                '  </button>',
                '  <div class="vbp-lightbox__counter"><span class="current">1</span> / <span class="total">1</span></div>',
                '  <div class="vbp-lightbox__zoom-controls">',
                '    <button type="button" class="vbp-lightbox__zoom-in" aria-label="Acercar">+</button>',
                '    <button type="button" class="vbp-lightbox__zoom-out" aria-label="Alejar">−</button>',
                '    <button type="button" class="vbp-lightbox__zoom-reset" aria-label="Restablecer">1:1</button>',
                '  </div>',
                '</div>'
            ].join('');
            document.body.appendChild(lightbox);
        }

        var currentImages = [];
        var currentIndex = 0;
        var currentZoom = 1;
        var touchStartX = 0;
        var touchStartY = 0;
        var isDragging = false;

        var lightboxImage = lightbox.querySelector('.vbp-lightbox__image');
        var lightboxCaption = lightbox.querySelector('.vbp-lightbox__caption');
        var lightboxLoader = lightbox.querySelector('.vbp-lightbox__loader');
        var counterCurrent = lightbox.querySelector('.vbp-lightbox__counter .current');
        var counterTotal = lightbox.querySelector('.vbp-lightbox__counter .total');
        var prevBtn = lightbox.querySelector('.vbp-lightbox__nav--prev');
        var nextBtn = lightbox.querySelector('.vbp-lightbox__nav--next');
        var closeBtn = lightbox.querySelector('.vbp-lightbox__close');
        var overlay = lightbox.querySelector('.vbp-lightbox__overlay');
        var zoomInBtn = lightbox.querySelector('.vbp-lightbox__zoom-in');
        var zoomOutBtn = lightbox.querySelector('.vbp-lightbox__zoom-out');
        var zoomResetBtn = lightbox.querySelector('.vbp-lightbox__zoom-reset');

        function openLightbox(images, index) {
            currentImages = images;
            currentIndex = index || 0;
            currentZoom = 1;

            lightbox.classList.add('vbp-lightbox--open');
            document.body.style.overflow = 'hidden';

            updateLightboxImage();
            updateNavigation();
        }

        function closeLightbox() {
            lightbox.classList.remove('vbp-lightbox--open');
            document.body.style.overflow = '';
            currentZoom = 1;
            lightboxImage.style.transform = '';
        }

        function updateLightboxImage() {
            if (!currentImages[currentIndex]) return;

            var item = currentImages[currentIndex];
            lightboxLoader.style.display = 'block';
            lightboxImage.style.opacity = '0';

            var tempImg = new Image();
            tempImg.onload = function() {
                lightboxImage.src = item.src;
                lightboxImage.alt = item.alt || '';
                lightboxCaption.textContent = item.caption || item.alt || '';
                lightboxCaption.style.display = item.caption || item.alt ? 'block' : 'none';
                lightboxLoader.style.display = 'none';
                lightboxImage.style.opacity = '1';
                lightboxImage.style.transform = 'scale(' + currentZoom + ')';
            };
            tempImg.src = item.src;

            counterCurrent.textContent = currentIndex + 1;
            counterTotal.textContent = currentImages.length;
        }

        function updateNavigation() {
            var hideNav = currentImages.length <= 1;
            prevBtn.style.display = hideNav ? 'none' : '';
            nextBtn.style.display = hideNav ? 'none' : '';
            lightbox.querySelector('.vbp-lightbox__counter').style.display = hideNav ? 'none' : '';
        }

        function goToPrev() {
            currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
            currentZoom = 1;
            updateLightboxImage();
        }

        function goToNext() {
            currentIndex = (currentIndex + 1) % currentImages.length;
            currentZoom = 1;
            updateLightboxImage();
        }

        function zoomIn() {
            currentZoom = Math.min(currentZoom + 0.25, 3);
            lightboxImage.style.transform = 'scale(' + currentZoom + ')';
        }

        function zoomOut() {
            currentZoom = Math.max(currentZoom - 0.25, 0.5);
            lightboxImage.style.transform = 'scale(' + currentZoom + ')';
        }

        function zoomReset() {
            currentZoom = 1;
            lightboxImage.style.transform = 'scale(1)';
        }

        // Event listeners
        closeBtn.addEventListener('click', closeLightbox);
        overlay.addEventListener('click', closeLightbox);
        prevBtn.addEventListener('click', goToPrev);
        nextBtn.addEventListener('click', goToNext);
        zoomInBtn.addEventListener('click', zoomIn);
        zoomOutBtn.addEventListener('click', zoomOut);
        zoomResetBtn.addEventListener('click', zoomReset);

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (!lightbox.classList.contains('vbp-lightbox--open')) return;

            switch(e.key) {
                case 'Escape':
                    closeLightbox();
                    break;
                case 'ArrowLeft':
                    goToPrev();
                    break;
                case 'ArrowRight':
                    goToNext();
                    break;
                case '+':
                case '=':
                    zoomIn();
                    break;
                case '-':
                    zoomOut();
                    break;
                case '0':
                    zoomReset();
                    break;
            }
        });

        // Touch support (swipe)
        lightbox.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            isDragging = true;
        }, { passive: true });

        lightbox.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
        }, { passive: true });

        lightbox.addEventListener('touchend', function(e) {
            if (!isDragging) return;
            isDragging = false;

            var touchEndX = e.changedTouches[0].clientX;
            var touchEndY = e.changedTouches[0].clientY;
            var diffX = touchStartX - touchEndX;
            var diffY = touchStartY - touchEndY;

            // Only process horizontal swipes (and ignore if mostly vertical)
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    goToNext();
                } else {
                    goToPrev();
                }
            }
        }, { passive: true });

        // Mouse wheel zoom
        lightbox.querySelector('.vbp-lightbox__content').addEventListener('wheel', function(e) {
            e.preventDefault();
            if (e.deltaY < 0) {
                zoomIn();
            } else {
                zoomOut();
            }
        }, { passive: false });

        // Double-click to zoom
        lightboxImage.addEventListener('dblclick', function() {
            if (currentZoom === 1) {
                currentZoom = 2;
            } else {
                currentZoom = 1;
            }
            lightboxImage.style.transform = 'scale(' + currentZoom + ')';
        });

        // Initialize galleries
        galleries.forEach(function(gallery) {
            var images = gallery.querySelectorAll('.vbp-gallery__item img, .vbp-gallery__image');

            images.forEach(function(img, index) {
                img.style.cursor = 'pointer';
                img.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Collect all images from this gallery
                    var galleryImages = [];
                    images.forEach(function(galleryImg) {
                        galleryImages.push({
                            src: galleryImg.dataset.fullSrc || galleryImg.src,
                            alt: galleryImg.alt || '',
                            caption: galleryImg.dataset.caption || galleryImg.alt || ''
                        });
                    });

                    openLightbox(galleryImages, index);
                });
            });
        });

        // Expose lightbox API globally
        window.VBPLightbox = {
            open: openLightbox,
            close: closeLightbox,
            next: goToNext,
            prev: goToPrev,
            zoomIn: zoomIn,
            zoomOut: zoomOut,
            zoomReset: zoomReset
        };
    }

    /**
     * Smooth Scroll for anchor links
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var targetId = this.getAttribute('href');
                if (targetId === '#') return;

                var target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    /**
     * Scroll Animations
     */
    function initScrollAnimations() {
        var animatedElements = document.querySelectorAll('[data-animation]');

        if (!animatedElements.length) return;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var animation = entry.target.dataset.animation;
                    entry.target.style.animationPlayState = 'running';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        animatedElements.forEach(function(el) {
            el.style.animationPlayState = 'paused';
            observer.observe(el);
        });
    }

    /**
     * Initialize AJAX Forms (Contact, Newsletter, etc.)
     */
    function initAjaxForms() {
        var forms = document.querySelectorAll('.vbp-ajax-form');

        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(form);
            });
        });
    }

    /**
     * Handle form submission via AJAX
     * @param {HTMLFormElement} form - The form element
     */
    function handleFormSubmit(form) {
        var submitBtn = form.querySelector('button[type="submit"]');
        var btnText = submitBtn.querySelector('.vbp-btn-text');
        var btnLoading = submitBtn.querySelector('.vbp-btn-loading');
        var statusDiv = form.querySelector('.vbp-form-status');
        var successDiv = form.closest('.vbp-contact').querySelector('.vbp-contact__success');

        // Limpiar errores previos
        form.querySelectorAll('.vbp-field-error').forEach(function(el) {
            el.textContent = '';
            el.style.display = 'none';
        });
        form.querySelectorAll('.vbp-contact__field.has-error').forEach(function(el) {
            el.classList.remove('has-error');
        });

        if (statusDiv) {
            statusDiv.textContent = '';
            statusDiv.className = 'vbp-form-status';
        }

        // Estado de carga
        submitBtn.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnLoading) btnLoading.style.display = 'inline-flex';

        // Obtener datos del formulario
        var formData = new FormData(form);

        // Determinar URL de AJAX
        var ajaxUrl = window.vbp_ajax_url || (window.ajaxurl) || '/wp-admin/admin-ajax.php';

        // Enviar petición
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Éxito
                if (successDiv) {
                    form.style.display = 'none';
                    successDiv.style.display = 'block';
                } else if (statusDiv) {
                    statusDiv.className = 'vbp-form-status vbp-form-status--success';
                    statusDiv.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> ' + (data.data.message || form.dataset.successMessage || '¡Enviado!');
                }

                // Resetear formulario
                form.reset();

                // Disparar evento personalizado
                form.dispatchEvent(new CustomEvent('vbp:form:success', {
                    detail: data.data,
                    bubbles: true
                }));

            } else {
                // Error
                var errorMessage = data.data && data.data.message ? data.data.message : 'Error al enviar el formulario';

                if (statusDiv) {
                    statusDiv.className = 'vbp-form-status vbp-form-status--error';
                    statusDiv.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ' + errorMessage;
                }

                // Mostrar errores de campo específicos
                if (data.data && data.data.errors) {
                    Object.keys(data.data.errors).forEach(function(field) {
                        var input = form.querySelector('[name="' + field + '"]');
                        if (input) {
                            var fieldContainer = input.closest('.vbp-contact__field');
                            var errorSpan = fieldContainer ? fieldContainer.querySelector('.vbp-field-error') : null;

                            if (fieldContainer) {
                                fieldContainer.classList.add('has-error');
                            }
                            if (errorSpan) {
                                errorSpan.textContent = data.data.errors[field];
                                errorSpan.style.display = 'block';
                            }
                        }
                    });
                }

                // Disparar evento de error
                form.dispatchEvent(new CustomEvent('vbp:form:error', {
                    detail: data.data,
                    bubbles: true
                }));
            }
        })
        .catch(function(error) {
            console.error('[VBP Form] Error:', error);

            if (statusDiv) {
                statusDiv.className = 'vbp-form-status vbp-form-status--error';
                statusDiv.textContent = 'Error de conexión. Por favor, inténtalo de nuevo.';
            }
        })
        .finally(function() {
            // Restaurar botón
            submitBtn.disabled = false;
            if (btnText) btnText.style.display = 'inline';
            if (btnLoading) btnLoading.style.display = 'none';
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initVBPComponents();
            initSmoothScroll();
            initScrollAnimations();
            initAjaxForms();
        });
    } else {
        initVBPComponents();
        initSmoothScroll();
        initScrollAnimations();
        initAjaxForms();
    }

    // Expose for external use
    window.VBPFrontend = {
        init: initVBPComponents,
        initCountdowns: initCountdowns,
        initAccordions: initAccordions,
        initTabs: initTabs,
        initCarousels: initCarousels,
        initTimelines: initTimelines,
        initBeforeAfter: initBeforeAfter,
        initProgressBars: initProgressBars,
        initAlerts: initAlerts,
        initGalleryLightbox: initGalleryLightbox,
        initAjaxForms: initAjaxForms
    };

})();

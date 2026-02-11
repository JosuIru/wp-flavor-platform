/**
 * Sistema de Onboarding Interactivo para Flavor Platform
 *
 * Implementación completa de tours guiados sin dependencias externas
 * siguiendo el patrón de shepherd.js/driver.js
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

(function($) {
    'use strict';

    /**
     * Sistema principal de Tours
     */
    window.FlavorTour = {
        // Estado del tour actual
        currentTour: null,
        currentStepIndex: 0,
        isRunning: false,

        // Elementos DOM
        overlay: null,
        popover: null,
        highlightBox: null,

        /**
         * Inicializa un tour
         *
         * @param {Object} tourConfig Configuración del tour
         */
        init: function(tourConfig) {
            this.currentTour = tourConfig;
            this.currentStepIndex = 0;
            this.createOverlay();
            this.createPopover();
            this.createHighlightBox();
            this.bindEvents();
        },

        /**
         * Crea el overlay oscuro
         */
        createOverlay: function() {
            if (this.overlay) return;

            this.overlay = $('<div class="flavor-tour-overlay"></div>');
            $('body').append(this.overlay);
        },

        /**
         * Crea el popover del tour
         */
        createPopover: function() {
            if (this.popover) return;

            this.popover = $(`
                <div class="flavor-tour-popover" role="dialog" aria-modal="true">
                    <div class="flavor-tour-popover-arrow"></div>
                    <button class="flavor-tour-popover-close" aria-label="${FlavorOnboardingData.strings.close}">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <div class="flavor-tour-popover-header">
                        <h3 class="flavor-tour-popover-title"></h3>
                        <span class="flavor-tour-popover-progress"></span>
                    </div>
                    <div class="flavor-tour-popover-content"></div>
                    <div class="flavor-tour-popover-footer">
                        <button class="flavor-tour-btn flavor-tour-btn-skip">
                            ${FlavorOnboardingData.strings.skip}
                        </button>
                        <div class="flavor-tour-popover-nav">
                            <button class="flavor-tour-btn flavor-tour-btn-prev">
                                ${FlavorOnboardingData.strings.prev}
                            </button>
                            <button class="flavor-tour-btn flavor-tour-btn-next flavor-tour-btn-primary">
                                ${FlavorOnboardingData.strings.next}
                            </button>
                        </div>
                    </div>
                </div>
            `);
            $('body').append(this.popover);
        },

        /**
         * Crea la caja de highlight
         */
        createHighlightBox: function() {
            if (this.highlightBox) return;

            this.highlightBox = $('<div class="flavor-tour-highlight"></div>');
            $('body').append(this.highlightBox);
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            var self = this;

            // Botón siguiente
            this.popover.find('.flavor-tour-btn-next').on('click', function() {
                self.nextStep();
            });

            // Botón anterior
            this.popover.find('.flavor-tour-btn-prev').on('click', function() {
                self.prevStep();
            });

            // Botón saltar
            this.popover.find('.flavor-tour-btn-skip').on('click', function() {
                self.skip();
            });

            // Botón cerrar
            this.popover.find('.flavor-tour-popover-close').on('click', function() {
                self.stop();
            });

            // Teclas de navegación
            $(document).on('keydown.flavorTour', function(e) {
                if (!self.isRunning) return;

                switch(e.keyCode) {
                    case 27: // Escape
                        self.stop();
                        break;
                    case 37: // Flecha izquierda
                        self.prevStep();
                        break;
                    case 39: // Flecha derecha
                        self.nextStep();
                        break;
                }
            });

            // Redimensionar ventana
            $(window).on('resize.flavorTour', function() {
                if (self.isRunning) {
                    self.positionElements();
                }
            });
        },

        /**
         * Inicia el tour
         */
        start: function() {
            if (!this.currentTour || !this.currentTour.pasos || this.currentTour.pasos.length === 0) {
                console.warn('FlavorTour: No hay pasos definidos para el tour');
                return;
            }

            this.isRunning = true;
            this.currentStepIndex = 0;

            $('body').addClass('flavor-tour-active');
            this.overlay.addClass('active');
            this.popover.addClass('active');

            this.showStep(0);
        },

        /**
         * Muestra un paso específico
         *
         * @param {number} stepIndex Índice del paso
         */
        showStep: function(stepIndex) {
            if (stepIndex < 0 || stepIndex >= this.currentTour.pasos.length) {
                return;
            }

            var step = this.currentTour.pasos[stepIndex];
            var totalSteps = this.currentTour.pasos.length;

            // Actualizar contenido del popover
            this.popover.find('.flavor-tour-popover-title').text(step.titulo);
            this.popover.find('.flavor-tour-popover-content').html(step.contenido);
            this.popover.find('.flavor-tour-popover-progress').text(
                FlavorOnboardingData.strings.stepOf
                    .replace('%1$d', stepIndex + 1)
                    .replace('%2$d', totalSteps)
            );

            // Actualizar botones de navegación
            this.popover.find('.flavor-tour-btn-prev').toggle(stepIndex > 0);

            if (stepIndex === totalSteps - 1) {
                this.popover.find('.flavor-tour-btn-next').text(FlavorOnboardingData.strings.finish);
            } else {
                this.popover.find('.flavor-tour-btn-next').text(FlavorOnboardingData.strings.next);
            }

            // Posicionar elementos
            this.currentStepIndex = stepIndex;
            this.positionElements();

            // Guardar progreso
            this.saveProgress(stepIndex);

            // Añadir video si existe
            if (step.video_url) {
                this.addVideoButton(step.video_url);
            }
        },

        /**
         * Posiciona el highlight y popover
         */
        positionElements: function() {
            var step = this.currentTour.pasos[this.currentStepIndex];
            var selectors = step.elemento.split(',').map(function(s) { return s.trim(); });
            var targetElement = null;

            // Buscar el primer selector que exista y sea visible
            for (var i = 0; i < selectors.length; i++) {
                var $el = $(selectors[i]).filter(':visible').first();
                if ($el.length > 0) {
                    targetElement = $el;
                    break;
                }
            }

            if (!targetElement || targetElement.length === 0) {
                // Si no se encuentra el elemento, mostrar centrado
                this.showCentered(step);
                return;
            }

            // Hacer scroll al elemento
            this.scrollToElement(targetElement);

            // Posicionar highlight
            var rect = targetElement[0].getBoundingClientRect();
            var padding = step.destacar ? 12 : 8;

            this.highlightBox.css({
                top: rect.top + window.scrollY - padding,
                left: rect.left + window.scrollX - padding,
                width: rect.width + (padding * 2),
                height: rect.height + (padding * 2)
            }).addClass('active');

            // Posicionar popover
            this.positionPopover(targetElement, step.posicion || 'bottom');
        },

        /**
         * Hace scroll hasta el elemento
         *
         * @param {jQuery} element Elemento objetivo
         */
        scrollToElement: function(element) {
            var rect = element[0].getBoundingClientRect();
            var isInViewport = (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= window.innerHeight &&
                rect.right <= window.innerWidth
            );

            if (!isInViewport) {
                element[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        },

        /**
         * Posiciona el popover relativo al elemento
         *
         * @param {jQuery} element Elemento objetivo
         * @param {string} position Posición: top, bottom, left, right
         */
        positionPopover: function(element, position) {
            var rect = element[0].getBoundingClientRect();
            var popoverWidth = this.popover.outerWidth();
            var popoverHeight = this.popover.outerHeight();
            var gap = 15;
            var arrowSize = 10;

            var top, left;
            var arrowClass = 'arrow-' + position;

            switch(position) {
                case 'top':
                    top = rect.top + window.scrollY - popoverHeight - gap - arrowSize;
                    left = rect.left + window.scrollX + (rect.width / 2) - (popoverWidth / 2);
                    break;
                case 'bottom':
                    top = rect.bottom + window.scrollY + gap + arrowSize;
                    left = rect.left + window.scrollX + (rect.width / 2) - (popoverWidth / 2);
                    break;
                case 'left':
                    top = rect.top + window.scrollY + (rect.height / 2) - (popoverHeight / 2);
                    left = rect.left + window.scrollX - popoverWidth - gap - arrowSize;
                    break;
                case 'right':
                    top = rect.top + window.scrollY + (rect.height / 2) - (popoverHeight / 2);
                    left = rect.right + window.scrollX + gap + arrowSize;
                    break;
            }

            // Ajustar si se sale de la pantalla
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();

            if (left < 10) left = 10;
            if (left + popoverWidth > windowWidth - 10) left = windowWidth - popoverWidth - 10;
            if (top < 10) top = window.scrollY + 10;
            if (top + popoverHeight > windowHeight + window.scrollY - 10) {
                top = windowHeight + window.scrollY - popoverHeight - 10;
            }

            this.popover.css({ top: top, left: left });
            this.popover.removeClass('arrow-top arrow-bottom arrow-left arrow-right').addClass(arrowClass);

            // Posicionar flecha
            this.positionArrow(element, position);
        },

        /**
         * Posiciona la flecha del popover
         *
         * @param {jQuery} element Elemento objetivo
         * @param {string} position Posición
         */
        positionArrow: function(element, position) {
            var rect = element[0].getBoundingClientRect();
            var popoverRect = this.popover[0].getBoundingClientRect();
            var arrow = this.popover.find('.flavor-tour-popover-arrow');

            if (position === 'top' || position === 'bottom') {
                var elementCenter = rect.left + (rect.width / 2);
                var arrowLeft = elementCenter - popoverRect.left;
                arrowLeft = Math.max(20, Math.min(arrowLeft, popoverRect.width - 20));
                arrow.css({ left: arrowLeft + 'px', top: '', right: '' });
            } else {
                var elementMiddle = rect.top + (rect.height / 2);
                var arrowTop = elementMiddle - popoverRect.top + window.scrollY;
                arrowTop = Math.max(20, Math.min(arrowTop, popoverRect.height - 20));
                arrow.css({ top: arrowTop + 'px', left: '', right: '' });
            }
        },

        /**
         * Muestra el popover centrado cuando no hay elemento
         *
         * @param {Object} step Paso del tour
         */
        showCentered: function(step) {
            this.highlightBox.removeClass('active');

            var windowWidth = $(window).width();
            var windowHeight = $(window).height();
            var popoverWidth = this.popover.outerWidth();
            var popoverHeight = this.popover.outerHeight();

            this.popover.css({
                top: (windowHeight / 2) - (popoverHeight / 2) + window.scrollY,
                left: (windowWidth / 2) - (popoverWidth / 2)
            }).removeClass('arrow-top arrow-bottom arrow-left arrow-right');
        },

        /**
         * Avanza al siguiente paso
         */
        nextStep: function() {
            if (this.currentStepIndex < this.currentTour.pasos.length - 1) {
                this.showStep(this.currentStepIndex + 1);
            } else {
                this.complete();
            }
        },

        /**
         * Retrocede al paso anterior
         */
        prevStep: function() {
            if (this.currentStepIndex > 0) {
                this.showStep(this.currentStepIndex - 1);
            }
        },

        /**
         * Salta el tour
         */
        skip: function() {
            this.stop();
        },

        /**
         * Detiene el tour
         */
        stop: function() {
            this.isRunning = false;
            $('body').removeClass('flavor-tour-active');

            // Verificar que los elementos existen antes de manipularlos
            if (this.overlay) {
                this.overlay.removeClass('active');
            }
            if (this.popover) {
                this.popover.removeClass('active');
            }
            if (this.highlightBox) {
                this.highlightBox.removeClass('active');
            }

            $(document).off('keydown.flavorTour');
            $(window).off('resize.flavorTour');
        },

        /**
         * Completa el tour
         */
        complete: function() {
            var self = this;
            var tourId = this.currentTour.id;

            // Animación de completado
            this.popover.addClass('completed');

            setTimeout(function() {
                self.stop();
                self.markComplete(tourId);
            }, 500);
        },

        /**
         * Marca el tour como completado en el servidor
         *
         * @param {string} tourId ID del tour
         */
        markComplete: function(tourId) {
            $.ajax({
                url: FlavorOnboardingData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_complete_tour',
                    nonce: FlavorOnboardingData.nonce,
                    tour_id: tourId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorOnboardingData.completedTours.push(tourId);
                        FlavorOnboarding.updateUI();

                        // Mostrar mensaje de éxito
                        FlavorOnboarding.showNotification(
                            FlavorOnboardingData.strings.tourCompleted,
                            'success'
                        );
                    }
                }
            });
        },

        /**
         * Guarda el progreso del tour
         *
         * @param {number} stepIndex Índice del paso actual
         */
        saveProgress: function(stepIndex) {
            if (!this.currentTour) return;

            $.ajax({
                url: FlavorOnboardingData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_save_tour_progress',
                    nonce: FlavorOnboardingData.nonce,
                    tour_id: this.currentTour.id,
                    step: stepIndex
                }
            });
        },

        /**
         * Añade botón de video al popover
         *
         * @param {string} videoUrl URL del video
         */
        addVideoButton: function(videoUrl) {
            var videoBtn = $(`
                <button class="flavor-tour-video-btn">
                    <span class="dashicons dashicons-video-alt3"></span>
                    ${FlavorOnboardingData.strings.watchVideo}
                </button>
            `);

            this.popover.find('.flavor-tour-popover-content').append(videoBtn);

            videoBtn.on('click', function() {
                FlavorOnboarding.openVideoModal(videoUrl);
            });
        },

        /**
         * Destruye el tour y limpia recursos
         */
        destroy: function() {
            this.stop();
            if (this.overlay) this.overlay.remove();
            if (this.popover) this.popover.remove();
            if (this.highlightBox) this.highlightBox.remove();
            this.overlay = null;
            this.popover = null;
            this.highlightBox = null;
            this.currentTour = null;
        }
    };

    /**
     * Sistema de Ayuda Contextual (Tooltips)
     */
    window.FlavorContextualHelp = {
        tooltips: [],
        activeTooltip: null,

        /**
         * Registra un tooltip
         *
         * @param {Object} config Configuración del tooltip
         */
        registerTooltip: function(config) {
            this.tooltips.push(config);
            this.bindTooltip(config);
        },

        /**
         * Vincula eventos a un tooltip
         *
         * @param {Object} config Configuración del tooltip
         */
        bindTooltip: function(config) {
            var self = this;
            var $element = $(config.selector);

            if ($element.length === 0) return;

            // Añadir icono de ayuda si está configurado
            if (config.show_icon) {
                var helpIcon = $(`
                    <span class="flavor-help-icon">
                        <span class="dashicons ${config.icon_class || 'dashicons-info-outline'}"></span>
                    </span>
                `);
                $element.after(helpIcon);
                $element = helpIcon;
            }

            // Evento según trigger
            switch(config.trigger) {
                case 'hover':
                    $element.on('mouseenter', function() {
                        self.show(config, this);
                    }).on('mouseleave', function() {
                        if (!config.persistent) {
                            self.hide();
                        }
                    });
                    break;

                case 'click':
                    $element.on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        self.toggle(config, this);
                    });
                    break;

                case 'focus':
                    $element.on('focus', function() {
                        self.show(config, this);
                    }).on('blur', function() {
                        setTimeout(function() {
                            self.hide();
                        }, 200);
                    });
                    break;
            }
        },

        /**
         * Muestra un tooltip
         *
         * @param {Object} config Configuración
         * @param {Element} targetElement Elemento objetivo
         */
        show: function(config, targetElement) {
            this.hide();

            var tooltip = this.createTooltip(config);
            $('body').append(tooltip);

            this.positionTooltip(tooltip, targetElement, config.posicion);
            this.activeTooltip = tooltip;

            // Animación de entrada
            setTimeout(function() {
                tooltip.addClass('active');
            }, 10);
        },

        /**
         * Crea el elemento tooltip
         *
         * @param {Object} config Configuración
         * @return {jQuery} Elemento tooltip
         */
        createTooltip: function(config) {
            var themeClass = config.theme === 'dark' ? 'flavor-tooltip--dark' : '';
            var closableHtml = config.closable ?
                '<button class="flavor-tooltip-close"><span class="dashicons dashicons-no-alt"></span></button>' : '';

            var tooltip = $(`
                <div class="flavor-tooltip ${themeClass}" style="max-width: ${config.max_width}px">
                    <div class="flavor-tooltip-arrow"></div>
                    ${closableHtml}
                    <div class="flavor-tooltip-content">${config.contenido}</div>
                </div>
            `);

            if (config.closable) {
                var self = this;
                tooltip.find('.flavor-tooltip-close').on('click', function() {
                    self.hide();
                });
            }

            // Si es video
            if (config.is_video && config.video_url) {
                var videoBtn = $(`
                    <button class="flavor-tooltip-video-btn">
                        <span class="dashicons dashicons-video-alt3"></span>
                        ${config.video_titulo}
                    </button>
                `);
                tooltip.find('.flavor-tooltip-content').html(videoBtn);
                videoBtn.on('click', function() {
                    FlavorOnboarding.openVideoModal(config.video_url);
                });
            }

            return tooltip;
        },

        /**
         * Posiciona el tooltip
         *
         * @param {jQuery} tooltip Elemento tooltip
         * @param {Element} target Elemento objetivo
         * @param {string} position Posición
         */
        positionTooltip: function(tooltip, target, position) {
            var rect = target.getBoundingClientRect();
            var tooltipWidth = tooltip.outerWidth();
            var tooltipHeight = tooltip.outerHeight();
            var gap = 10;

            var top, left;

            switch(position) {
                case 'top':
                    top = rect.top + window.scrollY - tooltipHeight - gap;
                    left = rect.left + window.scrollX + (rect.width / 2) - (tooltipWidth / 2);
                    break;
                case 'bottom':
                    top = rect.bottom + window.scrollY + gap;
                    left = rect.left + window.scrollX + (rect.width / 2) - (tooltipWidth / 2);
                    break;
                case 'left':
                    top = rect.top + window.scrollY + (rect.height / 2) - (tooltipHeight / 2);
                    left = rect.left + window.scrollX - tooltipWidth - gap;
                    break;
                case 'right':
                    top = rect.top + window.scrollY + (rect.height / 2) - (tooltipHeight / 2);
                    left = rect.right + window.scrollX + gap;
                    break;
            }

            // Ajustes de viewport
            if (left < 10) left = 10;
            if (left + tooltipWidth > $(window).width() - 10) {
                left = $(window).width() - tooltipWidth - 10;
            }
            if (top < window.scrollY + 10) top = window.scrollY + 10;

            tooltip.css({ top: top, left: left }).addClass('arrow-' + position);
        },

        /**
         * Oculta el tooltip activo
         */
        hide: function() {
            if (this.activeTooltip) {
                this.activeTooltip.removeClass('active');
                var tooltip = this.activeTooltip;
                setTimeout(function() {
                    tooltip.remove();
                }, 200);
                this.activeTooltip = null;
            }
        },

        /**
         * Alterna visibilidad del tooltip
         *
         * @param {Object} config Configuración
         * @param {Element} targetElement Elemento objetivo
         */
        toggle: function(config, targetElement) {
            if (this.activeTooltip) {
                this.hide();
            } else {
                this.show(config, targetElement);
            }
        },

        /**
         * Añade un tooltip dinámicamente
         *
         * @param {string|Element} selector Selector o elemento
         * @param {string} content Contenido
         * @param {string} position Posición
         */
        addTooltip: function(selector, content, position) {
            this.registerTooltip({
                id: 'dynamic_' + Date.now(),
                selector: selector,
                contenido: content,
                posicion: position || 'bottom',
                trigger: 'hover',
                max_width: 300
            });
        }
    };

    /**
     * Controlador principal de Onboarding
     */
    window.FlavorOnboarding = {
        /**
         * Inicializa el sistema de onboarding
         */
        init: function() {
            this.bindEvents();
            this.initNotificationHandlers();
            this.initHelpLauncher();
        },

        /**
         * Vincula eventos principales
         */
        bindEvents: function() {
            var self = this;

            // Iniciar tour desde menú de ayuda
            $(document).on('click', '.flavor-help-item[data-tour-id]', function() {
                var tourId = $(this).data('tour-id');
                self.startTour(tourId);
                $('.flavor-help-menu').removeClass('active');
            });

            // Cerrar tooltip al hacer clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.flavor-tooltip, .flavor-help-icon').length) {
                    FlavorContextualHelp.hide();
                }
            });
        },

        /**
         * Inicializa handlers de notificación de tour
         */
        initNotificationHandlers: function() {
            var self = this;
            var $notification = $('#flavor-tour-notification');

            if ($notification.length === 0) return;

            // Botón iniciar tour
            $notification.find('.flavor-start-tour-btn').on('click', function() {
                var tourId = $notification.data('tour-id');
                $notification.fadeOut(300);
                self.startTour(tourId);
            });

            // Botón descartar
            $notification.find('.flavor-dismiss-tour-btn, .flavor-tour-notification-close').on('click', function() {
                var tourId = $notification.data('tour-id');
                $notification.fadeOut(300);
                self.dismissTour(tourId);
            });

            // Mostrar con animación
            setTimeout(function() {
                $notification.addClass('active');
            }, 1000);
        },

        /**
         * Inicializa el lanzador de ayuda flotante
         */
        initHelpLauncher: function() {
            var $launcher = $('#flavor-help-launcher');

            if ($launcher.length === 0) return;

            $launcher.find('.flavor-help-btn').on('click', function(e) {
                e.stopPropagation();
                $launcher.find('.flavor-help-menu').toggleClass('active');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#flavor-help-launcher').length) {
                    $launcher.find('.flavor-help-menu').removeClass('active');
                }
            });
        },

        /**
         * Inicia un tour específico
         *
         * @param {string} tourId ID del tour
         */
        startTour: function(tourId) {
            var tourConfig = FlavorOnboardingData.tours[tourId] ||
                             FlavorOnboardingData.allTours[tourId];

            if (!tourConfig) {
                console.error('Tour no encontrado:', tourId);
                return;
            }

            // Filtrar pasos cuyos elementos existen
            var validSteps = [];
            tourConfig.pasos.forEach(function(paso) {
                var selectors = paso.elemento.split(',').map(function(s) { return s.trim(); });
                var hasValidElement = selectors.some(function(selector) {
                    return $(selector).filter(':visible').length > 0;
                });

                if (hasValidElement) {
                    validSteps.push(paso);
                }
            });

            if (validSteps.length === 0) {
                this.showNotification('No hay elementos visibles para este tour en esta página.', 'warning');
                return;
            }

            var tourWithValidSteps = $.extend({}, tourConfig, { pasos: validSteps });

            FlavorTour.destroy();
            FlavorTour.init(tourWithValidSteps);
            FlavorTour.start();
        },

        /**
         * Descarta un tour
         *
         * @param {string} tourId ID del tour
         */
        dismissTour: function(tourId) {
            $.ajax({
                url: FlavorOnboardingData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_dismiss_tour',
                    nonce: FlavorOnboardingData.nonce,
                    tour_id: tourId
                }
            });
        },

        /**
         * Reinicia un tour
         *
         * @param {string} tourId ID del tour
         */
        resetTour: function(tourId) {
            $.ajax({
                url: FlavorOnboardingData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_reset_tour',
                    nonce: FlavorOnboardingData.nonce,
                    tour_id: tourId
                },
                success: function() {
                    location.reload();
                }
            });
        },

        /**
         * Reinicia todos los tours
         */
        resetAllTours: function() {
            $.ajax({
                url: FlavorOnboardingData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_reset_all_tours',
                    nonce: FlavorOnboardingData.nonce
                },
                success: function() {
                    location.reload();
                }
            });
        },

        /**
         * Actualiza la UI después de cambios
         */
        updateUI: function() {
            // Actualizar indicadores de tours completados
            $('.flavor-help-item[data-tour-id]').each(function() {
                var tourId = $(this).data('tour-id');
                if (FlavorOnboardingData.completedTours.indexOf(tourId) !== -1) {
                    $(this).addClass('completed');
                    if (!$(this).find('.dashicons-yes-alt').length) {
                        $(this).append('<span class="dashicons dashicons-yes-alt flavor-check"></span>');
                    }
                }
            });
        },

        /**
         * Muestra una notificación
         *
         * @param {string} message Mensaje
         * @param {string} type Tipo: success, error, warning, info
         */
        showNotification: function(message, type) {
            type = type || 'info';
            var iconMap = {
                success: 'dashicons-yes-alt',
                error: 'dashicons-dismiss',
                warning: 'dashicons-warning',
                info: 'dashicons-info'
            };

            var notification = $(`
                <div class="flavor-notification flavor-notification--${type}">
                    <span class="dashicons ${iconMap[type]}"></span>
                    <span class="flavor-notification-message">${message}</span>
                    <button class="flavor-notification-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            `);

            $('body').append(notification);

            setTimeout(function() {
                notification.addClass('active');
            }, 10);

            notification.find('.flavor-notification-close').on('click', function() {
                notification.removeClass('active');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            });

            // Auto-ocultar después de 5 segundos
            setTimeout(function() {
                notification.removeClass('active');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 5000);
        },

        /**
         * Abre el modal de video
         *
         * @param {string} videoUrl URL del video
         */
        openVideoModal: function(videoUrl) {
            var $modal = $('#flavor-video-modal');
            var $iframe = $('#flavor-video-iframe');

            // Convertir URL de YouTube/Vimeo a embed
            var embedUrl = this.getEmbedUrl(videoUrl);
            $iframe.attr('src', embedUrl);

            $modal.addClass('active');

            // Cerrar modal
            $modal.find('.flavor-video-modal-close').one('click', function() {
                $modal.removeClass('active');
                $iframe.attr('src', '');
            });

            // Cerrar con click fuera
            $modal.one('click', function(e) {
                if ($(e.target).hasClass('flavor-video-modal')) {
                    $modal.removeClass('active');
                    $iframe.attr('src', '');
                }
            });

            // Cerrar con Escape
            $(document).one('keydown', function(e) {
                if (e.keyCode === 27) {
                    $modal.removeClass('active');
                    $iframe.attr('src', '');
                }
            });
        },

        /**
         * Convierte URL de video a formato embed
         *
         * @param {string} url URL del video
         * @return {string} URL embed
         */
        getEmbedUrl: function(url) {
            // YouTube
            var youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/);
            if (youtubeMatch) {
                return 'https://www.youtube.com/embed/' + youtubeMatch[1] + '?autoplay=1';
            }

            // Vimeo
            var vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
            if (vimeoMatch) {
                return 'https://player.vimeo.com/video/' + vimeoMatch[1] + '?autoplay=1';
            }

            return url;
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        if (typeof FlavorOnboardingData !== 'undefined') {
            FlavorOnboarding.init();
        }
    });

})(jQuery);

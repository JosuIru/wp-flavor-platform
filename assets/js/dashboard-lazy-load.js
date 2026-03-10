/**
 * Dashboard Widget Lazy Loading
 *
 * Carga widgets de forma diferida cuando entran en el viewport
 * usando IntersectionObserver para mejor rendimiento.
 *
 * @package FlavorChatIA
 * @since 4.2.0
 */

(function() {
    'use strict';

    // Configuración
    const CONFIG = {
        // Margen para precargar antes de que el widget sea visible
        rootMargin: '100px 0px',
        // Umbral de visibilidad para activar la carga
        threshold: 0.1,
        // Clase que identifica widgets lazy
        lazyClass: 'fud-widget--lazy',
        // Clase cuando está cargando
        loadingClass: 'fud-widget--loading',
        // Clase cuando ha cargado
        loadedClass: 'fud-widget--loaded',
        // Clase para errores
        errorClass: 'fud-widget--error',
        // Selector del body del widget
        bodySelector: '.fud-widget__body, .fl-widget__body',
        // Selector del loading spinner
        loadingSelector: '.fud-widget__loading, .fl-widget__loading',
    };

    // Cache de widgets ya cargados
    const loadedWidgets = new Set();

    // Observer instance
    let observer = null;

    /**
     * Inicializa el lazy loading
     */
    function init() {
        // Verificar soporte de IntersectionObserver
        if (!('IntersectionObserver' in window)) {
            // Fallback: cargar todos los widgets lazy inmediatamente
            loadAllLazyWidgets();
            return;
        }

        // Crear observer
        observer = new IntersectionObserver(handleIntersection, {
            root: null,
            rootMargin: CONFIG.rootMargin,
            threshold: CONFIG.threshold,
        });

        // Observar widgets lazy
        observeLazyWidgets();

        // Re-observar cuando se añadan nuevos widgets (para SPA)
        observeDOMChanges();
    }

    /**
     * Maneja las intersecciones detectadas
     *
     * @param {IntersectionObserverEntry[]} entries
     */
    function handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const widget = entry.target;
                loadWidget(widget);
                observer.unobserve(widget);
            }
        });
    }

    /**
     * Observa todos los widgets lazy actuales
     */
    function observeLazyWidgets() {
        const lazyWidgets = document.querySelectorAll('.' + CONFIG.lazyClass);
        lazyWidgets.forEach(widget => {
            const widgetId = widget.dataset.widgetId;
            if (widgetId && !loadedWidgets.has(widgetId)) {
                observer.observe(widget);
            }
        });
    }

    /**
     * Observa cambios en el DOM para nuevos widgets
     */
    function observeDOMChanges() {
        const dashboardContainer = document.querySelector('.fud-dashboard, .fl-dashboard, #flavor-dashboard-grid');
        if (!dashboardContainer) return;

        const mutationObserver = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        if (node.classList && node.classList.contains(CONFIG.lazyClass)) {
                            const widgetId = node.dataset.widgetId;
                            if (widgetId && !loadedWidgets.has(widgetId)) {
                                observer.observe(node);
                            }
                        }
                        // También buscar widgets lazy dentro del nodo añadido
                        const innerLazy = node.querySelectorAll ? node.querySelectorAll('.' + CONFIG.lazyClass) : [];
                        innerLazy.forEach(widget => {
                            const widgetId = widget.dataset.widgetId;
                            if (widgetId && !loadedWidgets.has(widgetId)) {
                                observer.observe(widget);
                            }
                        });
                    }
                });
            });
        });

        mutationObserver.observe(dashboardContainer, {
            childList: true,
            subtree: true,
        });
    }

    /**
     * Carga un widget via AJAX
     *
     * @param {HTMLElement} widget
     */
    function loadWidget(widget) {
        const widgetId = widget.dataset.widgetId;

        if (!widgetId || loadedWidgets.has(widgetId)) {
            return;
        }

        // Marcar como cargando
        widget.classList.add(CONFIG.loadingClass);
        widget.setAttribute('aria-busy', 'true');

        // Obtener configuración AJAX
        const ajaxUrl = window.fudDashboard?.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php';
        const nonce = window.fudDashboard?.nonce || '';

        // Hacer petición AJAX
        const formData = new FormData();
        formData.append('action', 'fud_load_widget');
        formData.append('widget_id', widgetId);
        formData.append('nonce', nonce);

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data.html) {
                // Insertar contenido
                const body = widget.querySelector(CONFIG.bodySelector);
                if (body) {
                    body.innerHTML = result.data.html;
                }

                // Actualizar estado
                widget.classList.remove(CONFIG.lazyClass, CONFIG.loadingClass);
                widget.classList.add(CONFIG.loadedClass);
                widget.setAttribute('aria-busy', 'false');
                loadedWidgets.add(widgetId);

                // Disparar evento para que otros scripts puedan reaccionar
                widget.dispatchEvent(new CustomEvent('fud:widget:loaded', {
                    detail: { widgetId, data: result.data },
                    bubbles: true,
                }));

                // Inicializar charts si hay
                initWidgetCharts(widget);
            } else {
                handleLoadError(widget, result.data?.message || 'Error desconocido');
            }
        })
        .catch(error => {
            handleLoadError(widget, error.message);
        });
    }

    /**
     * Maneja errores de carga
     *
     * @param {HTMLElement} widget
     * @param {string} message
     */
    function handleLoadError(widget, message) {
        const body = widget.querySelector(CONFIG.bodySelector);
        if (body) {
            body.innerHTML = `
                <div class="fud-widget__error fl-widget__error" role="alert">
                    <span class="dashicons dashicons-warning" aria-hidden="true"></span>
                    <p>${escapeHtml(message)}</p>
                    <button type="button" class="fud-widget__retry fl-widget__retry" onclick="window.fudLazyLoad.retry(this)">
                        Reintentar
                    </button>
                </div>
            `;
        }

        widget.classList.remove(CONFIG.loadingClass);
        widget.classList.add(CONFIG.errorClass);
        widget.setAttribute('aria-busy', 'false');
    }

    /**
     * Reintenta cargar un widget
     *
     * @param {HTMLElement} button
     */
    function retryLoad(button) {
        const widget = button.closest('.fud-widget, .fl-widget');
        if (widget) {
            widget.classList.remove(CONFIG.errorClass);
            widget.classList.add(CONFIG.lazyClass);
            loadWidget(widget);
        }
    }

    /**
     * Carga todos los widgets lazy (fallback sin IntersectionObserver)
     */
    function loadAllLazyWidgets() {
        const lazyWidgets = document.querySelectorAll('.' + CONFIG.lazyClass);
        lazyWidgets.forEach(loadWidget);
    }

    /**
     * Inicializa charts dentro de un widget si los hay
     *
     * @param {HTMLElement} widget
     */
    function initWidgetCharts(widget) {
        // Si hay Chart.js disponible y hay canvas
        if (typeof Chart !== 'undefined') {
            const canvases = widget.querySelectorAll('canvas[data-chart]');
            canvases.forEach(canvas => {
                try {
                    const chartConfig = JSON.parse(canvas.dataset.chart);
                    new Chart(canvas, chartConfig);
                } catch (e) {
                    console.warn('Error initializing chart:', e);
                }
            });
        }
    }

    /**
     * Escapa HTML para prevenir XSS
     *
     * @param {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Fuerza la carga de un widget específico
     *
     * @param {string} widgetId
     */
    function forceLoad(widgetId) {
        const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
        if (widget) {
            loadWidget(widget);
        }
    }

    // API pública
    window.fudLazyLoad = {
        init: init,
        retry: retryLoad,
        forceLoad: forceLoad,
        loadAll: loadAllLazyWidgets,
    };

    // Auto-inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

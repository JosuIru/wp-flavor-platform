/**
 * Flavor Admin Shell - AJAX Navigation
 *
 * Sistema de navegación sin full page reload para el admin shell.
 * Intercepta clicks en el sidebar y carga contenido via AJAX.
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

(function() {
    'use strict';

    /**
     * Configuración
     */
    const CONFIG = {
        // Selector del contenedor de contenido principal
        contentSelector: '#wpbody-content',
        // Selector de links navegables
        navLinkSelector: '.fls-shell__menu-link, .fls-shell__submenu-link, .fls-shell__quick-link',
        // Header para indicar petición AJAX
        ajaxHeader: 'X-Flavor-Ajax-Navigation',
        // Tiempo máximo de espera (ms)
        timeout: 15000,
        // Clase para indicar carga
        loadingClass: 'fls-ajax-loading',
        // Prefijos de URL que deben usar navegación AJAX
        allowedPrefixes: [
            'admin.php?page=flavor-',
            'admin.php?page=gc-',
            'admin.php?page=socios',
            'admin.php?page=eventos',
            'admin.php?page=marketplace',
            'admin.php?page=comunidades',
            'admin.php?page=tramites',
            'admin.php?page=incidencias',
        ],
        // URLs que NO deben usar AJAX (editors, forms complejos)
        excludedPatterns: [
            'post.php',
            'post-new.php',
            'edit.php',
            'upload.php',
            'edit-tags.php',
            'vbp-editor',
            'action=edit',
        ],
    };

    /**
     * Estado de la navegación
     */
    let isNavigating = false;
    let currentController = null;

    /**
     * Inicializar sistema de navegación AJAX
     */
    function init() {
        // Solo activar si el shell está presente
        if (!document.body.classList.contains('fls-shell-active')) {
            return;
        }

        // Interceptar clicks en links del menú
        document.addEventListener('click', handleNavClick);

        // Manejar navegación del historial (back/forward)
        window.addEventListener('popstate', handlePopState);

        // Exponer API para uso externo
        window.flavorAjaxNav = {
            navigate: navigateTo,
            isNavigating: () => isNavigating,
            cancel: cancelNavigation,
        };
    }

    /**
     * Manejar click en link de navegación
     *
     * @param {Event} event Click event
     */
    function handleNavClick(event) {
        const link = event.target.closest(CONFIG.navLinkSelector);

        if (!link) {
            return;
        }

        const href = link.getAttribute('href');

        // Verificar si es una URL válida para AJAX
        if (!shouldUseAjax(href)) {
            return; // Dejar que el navegador maneje la navegación normal
        }

        // Prevenir navegación normal
        event.preventDefault();

        // Navegar via AJAX
        navigateTo(href, true);
    }

    /**
     * Verificar si una URL debe usar navegación AJAX
     *
     * @param {string} url URL a verificar
     * @return {boolean}
     */
    function shouldUseAjax(url) {
        if (!url || url === '#') {
            return false;
        }

        // Verificar patrones excluidos
        for (const pattern of CONFIG.excludedPatterns) {
            if (url.includes(pattern)) {
                return false;
            }
        }

        // Verificar si es una URL del admin de WordPress
        if (!url.includes('/wp-admin/') && !url.startsWith('admin.php')) {
            return false;
        }

        // Verificar prefijos permitidos
        for (const prefix of CONFIG.allowedPrefixes) {
            if (url.includes(prefix)) {
                return true;
            }
        }

        // Por defecto, no usar AJAX para URLs desconocidas
        return false;
    }

    /**
     * Navegar a una URL via AJAX
     *
     * @param {string} url URL destino
     * @param {boolean} pushState Si debe actualizar el historial
     */
    async function navigateTo(url, pushState = true) {
        if (isNavigating) {
            cancelNavigation();
        }

        isNavigating = true;
        const contentContainer = document.querySelector(CONFIG.contentSelector);

        if (!contentContainer) {
            console.warn('[FlavorAjaxNav] Content container not found');
            window.location.href = url;
            return;
        }

        // Mostrar skeleton loader
        showSkeleton(contentContainer);

        // Actualizar estado activo en el menú
        updateActiveMenuState(url);

        try {
            // Crear AbortController para cancelación
            currentController = new AbortController();

            // Fetch con header especial
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    [CONFIG.ajaxHeader]: '1',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: currentController.signal,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            // Verificar si el servidor soporta navegación AJAX
            const isAjaxResponse = response.headers.get('X-Flavor-Ajax-Response') === '1';

            if (!isAjaxResponse) {
                // Fallback a navegación normal si el servidor no soporta AJAX
                window.location.href = url;
                return;
            }

            // Obtener contenido HTML
            const html = await response.text();

            // Reemplazar contenido
            replaceContent(contentContainer, html);

            // Actualizar historial
            if (pushState) {
                history.pushState({ flavorAjax: true, url: url }, '', url);
            }

            // Ejecutar scripts del nuevo contenido
            executeInlineScripts(contentContainer);

            // Disparar evento de navegación completada
            document.dispatchEvent(new CustomEvent('flavor:ajax-navigation', {
                detail: { url: url }
            }));

        } catch (error) {
            if (error.name === 'AbortError') {
                // Navegación cancelada, ignorar
                return;
            }

            console.error('[FlavorAjaxNav] Navigation failed:', error);

            // Fallback a navegación normal
            window.location.href = url;
        } finally {
            isNavigating = false;
            currentController = null;
        }
    }

    /**
     * Cancelar navegación en curso
     */
    function cancelNavigation() {
        if (currentController) {
            currentController.abort();
            currentController = null;
        }
        isNavigating = false;
    }

    /**
     * Manejar navegación con botones back/forward
     *
     * @param {PopStateEvent} event
     */
    function handlePopState(event) {
        if (event.state && event.state.flavorAjax) {
            navigateTo(event.state.url, false);
        }
    }

    /**
     * Mostrar skeleton loader
     *
     * @param {HTMLElement} container Contenedor donde mostrar
     */
    function showSkeleton(container) {
        container.classList.add(CONFIG.loadingClass);

        const skeleton = document.createElement('div');
        skeleton.className = 'fls-skeleton';
        skeleton.innerHTML = `
            <div class="fls-skeleton__header">
                <div class="fls-skeleton__bar fls-skeleton__bar--title"></div>
                <div class="fls-skeleton__bar fls-skeleton__bar--subtitle"></div>
            </div>
            <div class="fls-skeleton__content">
                <div class="fls-skeleton__row">
                    <div class="fls-skeleton__card"></div>
                    <div class="fls-skeleton__card"></div>
                    <div class="fls-skeleton__card"></div>
                    <div class="fls-skeleton__card"></div>
                </div>
                <div class="fls-skeleton__bar fls-skeleton__bar--full"></div>
                <div class="fls-skeleton__bar fls-skeleton__bar--full"></div>
                <div class="fls-skeleton__bar fls-skeleton__bar--medium"></div>
            </div>
        `;

        // Guardar contenido original y mostrar skeleton
        container.dataset.originalContent = container.innerHTML;
        container.innerHTML = '';
        container.appendChild(skeleton);
    }

    /**
     * Reemplazar contenido del contenedor
     *
     * @param {HTMLElement} container Contenedor
     * @param {string} html Nuevo HTML
     */
    function replaceContent(container, html) {
        container.classList.remove(CONFIG.loadingClass);
        container.innerHTML = html;

        // Scroll al inicio
        window.scrollTo(0, 0);

        // Re-inicializar componentes Alpine.js si existen
        if (window.Alpine) {
            window.Alpine.initTree(container);
        }
    }

    /**
     * Ejecutar scripts inline del nuevo contenido
     *
     * @param {HTMLElement} container Contenedor con scripts
     */
    function executeInlineScripts(container) {
        const scripts = container.querySelectorAll('script');

        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');

            // Copiar atributos
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });

            // Copiar contenido inline
            newScript.textContent = oldScript.textContent;

            // Reemplazar script
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    /**
     * Actualizar estado activo en el menú
     *
     * @param {string} url Nueva URL activa
     */
    function updateActiveMenuState(url) {
        // Extraer page parameter
        const urlParams = new URLSearchParams(url.split('?')[1] || '');
        const page = urlParams.get('page');

        if (!page) {
            return;
        }

        // Remover clase activa de todos los links
        document.querySelectorAll('.fls-shell__menu-link--active, .fls-shell__submenu-link--active').forEach(el => {
            el.classList.remove('fls-shell__menu-link--active', 'fls-shell__submenu-link--active');
        });

        // Añadir clase activa al nuevo link
        const activeLink = document.querySelector(
            `.fls-shell__menu-link[href*="page=${page}"], .fls-shell__submenu-link[href*="page=${page}"]`
        );

        if (activeLink) {
            if (activeLink.classList.contains('fls-shell__menu-link')) {
                activeLink.classList.add('fls-shell__menu-link--active');
            } else {
                activeLink.classList.add('fls-shell__submenu-link--active');
            }
        }

        // Actualizar variable global si existe
        if (typeof flavorAdminShell !== 'undefined') {
            flavorAdminShell.currentPage = page;
        }
    }

    /**
     * Inicializar cuando el DOM esté listo
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

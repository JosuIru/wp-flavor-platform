/**
 * JavaScript Frontend para Formularios de Módulos
 *
 * Usa Alpine.js para manejar formularios reactivos
 *
 * @package FlavorChatIA
 */

/**
 * Alpine.js component para formularios de módulos
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('flavorForm', (moduleId, actionName) => ({
        loading: false,
        success: false,
        message: '',
        formData: {},

        /**
         * Envía el formulario via REST API
         */
        async submitForm(event) {
            this.loading = true;
            this.message = '';
            this.success = false;

            // Recoger datos del formulario
            const formData = new FormData(event.target);
            const data = {};

            // Convertir FormData a objeto
            for (const [key, value] of formData.entries()) {
                if (key === 'flavor_nonce') continue;

                // Manejar checkboxes
                if (event.target.elements[key]?.type === 'checkbox') {
                    data[key] = event.target.elements[key].checked ? 1 : 0;
                } else {
                    data[key] = value;
                }
            }

            try {
                const response = await fetch(
                    `${flavorModulesData.apiUrl}modules/${moduleId}/actions/${actionName}`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': flavorModulesData.nonce
                        },
                        body: JSON.stringify(data)
                    }
                );

                const result = await response.json();

                if (response.ok && result.success) {
                    this.success = true;
                    this.message = result.message || '¡Éxito!';

                    // Resetear formulario si fue exitoso
                    event.target.reset();

                    // Redirigir si hay URL de redirección
                    if (result.redirect_url) {
                        setTimeout(() => {
                            window.location.href = result.redirect_url;
                        }, 1500);
                    }

                    // Emitir evento personalizado para que otros scripts puedan escuchar
                    window.dispatchEvent(new CustomEvent('flavorFormSuccess', {
                        detail: { moduleId, actionName, result }
                    }));
                } else {
                    this.success = false;
                    this.message = result.message || result.error || 'Error al procesar el formulario';

                    // Emitir evento de error
                    window.dispatchEvent(new CustomEvent('flavorFormError', {
                        detail: { moduleId, actionName, result }
                    }));
                }
            } catch (error) {
                this.success = false;
                this.message = 'Error de conexión. Por favor, inténtalo de nuevo.';
                console.error('Error en formulario Flavor:', error);

                // Emitir evento de error de red
                window.dispatchEvent(new CustomEvent('flavorFormNetworkError', {
                    detail: { moduleId, actionName, error }
                }));
            } finally {
                this.loading = false;

                // Auto-ocultar mensaje después de 10 segundos
                setTimeout(() => {
                    this.message = '';
                }, 10000);
            }
        },

        /**
         * Limpia el mensaje
         */
        clearMessage() {
            this.message = '';
        }
    }));
});

/**
 * Helpers y utilidades adicionales
 */
(function() {
    'use strict';

    // Función para cargar Alpine.js si no está cargado
    function loadAlpine() {
        if (typeof Alpine === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
            script.defer = true;
            document.head.appendChild(script);
        }
    }

    // Cargar Alpine cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAlpine);
    } else {
        loadAlpine();
    }

    /**
     * Event listeners globales para formularios Flavor
     */

    // Log de éxitos (solo en modo debug)
    if (flavorModulesData.debug) {
        window.addEventListener('flavorFormSuccess', (e) => {
            console.log('✅ Formulario enviado exitosamente:', e.detail);
        });

        window.addEventListener('flavorFormError', (e) => {
            console.error('❌ Error en formulario:', e.detail);
        });

        window.addEventListener('flavorFormNetworkError', (e) => {
            console.error('🌐 Error de red en formulario:', e.detail);
        });
    }

    /**
     * Mejoras de accesibilidad
     */

    // Foco automático en primer campo con error
    window.addEventListener('flavorFormError', (e) => {
        setTimeout(() => {
            const primerCampoError = document.querySelector('.flavor-field-error input, .flavor-field-error select, .flavor-field-error textarea');
            if (primerCampoError) {
                primerCampoError.focus();
            }
        }, 100);
    });

    // Anunciar mensajes a lectores de pantalla
    window.addEventListener('flavorFormSuccess', (e) => {
        anunciarALectorPantalla(e.detail.result.message || 'Formulario enviado correctamente');
    });

    window.addEventListener('flavorFormError', (e) => {
        anunciarALectorPantalla(e.detail.result.message || 'Error al enviar formulario', 'assertive');
    });

    function anunciarALectorPantalla(mensaje, prioridad = 'polite') {
        const anuncio = document.createElement('div');
        anuncio.setAttribute('role', 'status');
        anuncio.setAttribute('aria-live', prioridad);
        anuncio.setAttribute('aria-atomic', 'true');
        anuncio.className = 'sr-only';
        anuncio.textContent = mensaje;
        document.body.appendChild(anuncio);

        setTimeout(() => {
            document.body.removeChild(anuncio);
        }, 1000);
    }

    /**
     * Prevenir doble-submit
     */
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('flavor-form')) {
            const botonSubmit = e.target.querySelector('button[type="submit"]');
            if (botonSubmit && botonSubmit.disabled) {
                e.preventDefault();
                return false;
            }
        }
    });

    /**
     * Auto-scroll a mensajes de error/éxito
     */
    window.addEventListener('flavorFormSuccess', scrollToMessage);
    window.addEventListener('flavorFormError', scrollToMessage);

    function scrollToMessage(e) {
        setTimeout(() => {
            const mensaje = document.querySelector('.flavor-message');
            if (mensaje) {
                mensaje.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }, 100);
    }

})();

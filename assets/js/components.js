/**
 * Flavor Components - Frontend JavaScript
 */

(function($) {
    'use strict';

    const FlavorComponents = {
        init: function() {
            this.initSearchForm();
            this.initAnimations();
            this.initSmoothScroll();
            this.initFormValidation();
        },

        /**
         * Inicializar formulario de búsqueda de carpooling
         */
        initSearchForm: function() {
            $('#carpooling-search-form').on('submit', function(e) {
                e.preventDefault();

                const formData = {
                    origen: $(this).find('[name="origen"]').val(),
                    destino: $(this).find('[name="destino"]').val(),
                    fecha: $(this).find('[name="fecha"]').val(),
                    plazas: $(this).find('[name="plazas"]').val()
                };

                console.log('Buscando viajes:', formData);

                // Aquí se haría la llamada AJAX real al backend
                // Por ahora solo mostramos en consola

                // Ejemplo de cómo sería:
                /*
                $.ajax({
                    url: flavorComponents.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'flavor_search_trips',
                        nonce: flavorComponents.nonce,
                        ...formData
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizar resultados
                            console.log('Viajes encontrados:', response.data);
                        }
                    }
                });
                */
            });
        },

        /**
         * Inicializar animaciones al hacer scroll
         */
        initAnimations: function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in');
                    }
                });
            }, {
                threshold: 0.1
            });

            // Observar todos los componentes
            document.querySelectorAll('.flavor-component').forEach(component => {
                observer.observe(component);
            });
        },

        /**
         * Scroll suave a anclas
         */
        initSmoothScroll: function() {
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this.getAttribute('href'));

                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 80
                    }, 600);
                }
            });
        },

        /**
         * Validación básica de formularios
         */
        initFormValidation: function() {
            $('form').on('submit', function(e) {
                let isValid = true;

                $(this).find('[required]').each(function() {
                    const $field = $(this);
                    const value = $field.val().trim();

                    if (!value) {
                        isValid = false;
                        $field.addClass('error');
                        $field.on('input', function() {
                            $(this).removeClass('error');
                        });
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    this.showAlert('error', 'Por favor, completa todos los campos requeridos.');
                }
            });
        },

        /**
         * Mostrar alerta
         */
        showAlert: function(type, message) {
            const alertClass = `flavor-alert flavor-alert-${type}`;
            const $alert = $(`
                <div class="${alertClass}">
                    ${message}
                </div>
            `);

            $('form').first().before($alert);

            setTimeout(() => {
                $alert.fadeOut(() => {
                    $alert.remove();
                });
            }, 5000);
        }
    };

    /**
     * Utilidades globales
     */
    window.FlavorComponents = {
        /**
         * Formatear precio
         */
        formatPrice: function(price) {
            return new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        },

        /**
         * Formatear fecha
         */
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('es-ES', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        },

        /**
         * Truncar texto
         */
        truncate: function(text, length) {
            if (text.length <= length) return text;
            return text.substr(0, length) + '...';
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Lazy load images
         */
        lazyLoadImages: function() {
            const images = document.querySelectorAll('img[data-src]');

            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        },

        /**
         * Copy to clipboard
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast('Copiado al portapapeles');
                });
            } else {
                // Fallback para navegadores antiguos
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                this.showToast('Copiado al portapapeles');
            }
        },

        /**
         * Mostrar toast notification
         */
        showToast: function(message, duration = 3000) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, duration);
        },

        /**
         * Get query parameter
         */
        getQueryParam: function(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        },

        /**
         * Set query parameter
         */
        setQueryParam: function(param, value) {
            const url = new URL(window.location);
            url.searchParams.set(param, value);
            window.history.pushState({}, '', url);
        },

        /**
         * Validar email
         */
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Validar teléfono español
         */
        isValidPhone: function(phone) {
            const re = /^(\+34|0034|34)?[6789]\d{8}$/;
            return re.test(phone.replace(/\s/g, ''));
        },

        /**
         * Animar contador
         */
        animateCounter: function(element, target, duration = 2000) {
            let current = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = Math.round(target);
                    clearInterval(timer);
                } else {
                    element.textContent = Math.round(current);
                }
            }, 16);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorComponents.init();
        window.FlavorComponents.lazyLoadImages();
    });

})(jQuery);

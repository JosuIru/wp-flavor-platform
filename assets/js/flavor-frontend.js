/**
 * Flavor Frontend - JavaScript compartido para vistas públicas
 *
 * Funcionalidades:
 * - Filtrado AJAX sin recarga de página
 * - Lazy loading para imágenes
 * - Validación de formularios inline
 * - Toast notifications
 *
 * @package FlavorChatIA
 */

(function() {
    'use strict';

    var FlavorFrontend = {

        /**
         * Inicialización
         */
        init: function() {
            this.initFiltradoAjax();
            this.initLazyLoading();
            this.initValidacionFormularios();
            this.initVistaToggle();
            this.initOrdenacion();
        },

        // =============================================
        // FILTRADO AJAX
        // =============================================

        initFiltradoAjax: function() {
            var formulariosFiltro = document.querySelectorAll('.flavor-filters form');

            formulariosFiltro.forEach(function(formulario) {
                formulario.addEventListener('submit', function(evento) {
                    if (formulario.dataset.ajax !== 'false') {
                        evento.preventDefault();
                        FlavorFrontend.ejecutarFiltroAjax(formulario);
                    }
                });
            });

            // Filtros de categoría (botones)
            var botonesFiltro = document.querySelectorAll('[data-categoria]');
            botonesFiltro.forEach(function(boton) {
                boton.addEventListener('click', function() {
                    var categoriaSeleccionada = this.dataset.categoria;

                    // Actualizar estado visual
                    botonesFiltro.forEach(function(otroBoton) {
                        otroBoton.classList.remove('filter-active');
                        otroBoton.classList.remove('bg-violet-100', 'text-violet-700');
                        otroBoton.classList.add('bg-gray-100', 'text-gray-600');
                    });
                    this.classList.add('filter-active');
                    this.classList.remove('bg-gray-100', 'text-gray-600');
                    this.classList.add('bg-violet-100', 'text-violet-700');

                    // Filtrar elementos visualmente
                    FlavorFrontend.filtrarPorCategoria(categoriaSeleccionada);
                });
            });
        },

        ejecutarFiltroAjax: function(formulario) {
            var datosFormulario = new FormData(formulario);
            var parametrosUrl = new URLSearchParams(datosFormulario).toString();
            var contenedorResultados = document.querySelector('.flavor-frontend .grid');

            if (!contenedorResultados) return;

            // Mostrar estado de carga
            contenedorResultados.style.opacity = '0.5';
            contenedorResultados.style.pointerEvents = 'none';

            var urlFiltro = window.location.pathname + '?' + parametrosUrl;

            fetch(urlFiltro, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(respuesta) { return respuesta.text(); })
            .then(function(html) {
                var documentoTemporal = new DOMParser().parseFromString(html, 'text/html');
                var nuevosResultados = documentoTemporal.querySelector('.flavor-frontend .grid');

                if (nuevosResultados) {
                    contenedorResultados.innerHTML = nuevosResultados.innerHTML;
                }

                // Actualizar URL sin recargar
                window.history.pushState({}, '', urlFiltro);

                contenedorResultados.style.opacity = '1';
                contenedorResultados.style.pointerEvents = '';

                // Re-inicializar lazy loading en nuevos elementos
                FlavorFrontend.initLazyLoading();
            })
            .catch(function() {
                contenedorResultados.style.opacity = '1';
                contenedorResultados.style.pointerEvents = '';
                FlavorFrontend.mostrarToast('Error al filtrar. Inténtalo de nuevo.', 'error');
            });
        },

        filtrarPorCategoria: function(categoriaSeleccionada) {
            var elementosFiltrables = document.querySelectorAll('[data-filter-categoria]');

            elementosFiltrables.forEach(function(elemento) {
                if (categoriaSeleccionada === 'todos' || elemento.dataset.filterCategoria === categoriaSeleccionada) {
                    elemento.style.display = '';
                    elemento.style.animation = 'fadeIn 0.3s ease';
                } else {
                    elemento.style.display = 'none';
                }
            });
        },

        // =============================================
        // LAZY LOADING
        // =============================================

        initLazyLoading: function() {
            if (!('IntersectionObserver' in window)) return;

            var imagenesLazy = document.querySelectorAll('img[data-src]');

            var observadorImagenes = new IntersectionObserver(function(entradas) {
                entradas.forEach(function(entrada) {
                    if (entrada.isIntersecting) {
                        var imagen = entrada.target;
                        imagen.src = imagen.dataset.src;

                        if (imagen.dataset.srcset) {
                            imagen.srcset = imagen.dataset.srcset;
                        }

                        imagen.classList.add('flavor-loaded');
                        imagen.removeAttribute('data-src');
                        observadorImagenes.unobserve(imagen);
                    }
                });
            }, {
                rootMargin: '200px 0px'
            });

            imagenesLazy.forEach(function(imagen) {
                observadorImagenes.observe(imagen);
            });
        },

        // =============================================
        // VALIDACIÓN DE FORMULARIOS
        // =============================================

        initValidacionFormularios: function() {
            var formularios = document.querySelectorAll('.flavor-frontend form[data-validate]');

            formularios.forEach(function(formulario) {
                var camposRequeridos = formulario.querySelectorAll('[required]');

                camposRequeridos.forEach(function(campo) {
                    campo.addEventListener('blur', function() {
                        FlavorFrontend.validarCampo(campo);
                    });

                    campo.addEventListener('input', function() {
                        if (campo.classList.contains('flavor-field-error')) {
                            FlavorFrontend.validarCampo(campo);
                        }
                    });
                });

                formulario.addEventListener('submit', function(evento) {
                    var formularioValido = true;

                    camposRequeridos.forEach(function(campo) {
                        if (!FlavorFrontend.validarCampo(campo)) {
                            formularioValido = false;
                        }
                    });

                    if (!formularioValido) {
                        evento.preventDefault();
                        FlavorFrontend.mostrarToast('Por favor, completa todos los campos obligatorios.', 'warning');
                    }
                });
            });
        },

        validarCampo: function(campo) {
            var mensajeError = campo.parentElement.querySelector('.flavor-field-message');

            // Limpiar estado anterior
            campo.classList.remove('flavor-field-error', 'flavor-field-success');
            if (mensajeError) mensajeError.remove();

            var valorCampo = campo.value.trim();
            var tipoCampo = campo.type;
            var campoValido = true;
            var textoError = '';

            // Validar requerido
            if (campo.required && !valorCampo) {
                campoValido = false;
                textoError = 'Este campo es obligatorio';
            }

            // Validar email
            if (campoValido && tipoCampo === 'email' && valorCampo) {
                var patronEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!patronEmail.test(valorCampo)) {
                    campoValido = false;
                    textoError = 'Introduce un email válido';
                }
            }

            // Validar teléfono
            if (campoValido && tipoCampo === 'tel' && valorCampo) {
                var patronTelefono = /^[+]?[\d\s()-]{9,15}$/;
                if (!patronTelefono.test(valorCampo)) {
                    campoValido = false;
                    textoError = 'Introduce un teléfono válido';
                }
            }

            // Validar longitud mínima
            if (campoValido && campo.minLength > 0 && valorCampo.length < campo.minLength) {
                campoValido = false;
                textoError = 'Mínimo ' + campo.minLength + ' caracteres';
            }

            // Aplicar estado visual
            if (!campoValido) {
                campo.classList.add('flavor-field-error');
                campo.style.borderColor = '#ef4444';

                var elementoError = document.createElement('p');
                elementoError.className = 'flavor-field-message text-red-500 text-xs mt-1';
                elementoError.textContent = textoError;
                campo.parentElement.appendChild(elementoError);
            } else if (valorCampo) {
                campo.classList.add('flavor-field-success');
                campo.style.borderColor = '#10b981';
            } else {
                campo.style.borderColor = '';
            }

            return campoValido;
        },

        // =============================================
        // TOGGLE VISTA GRID/LISTA
        // =============================================

        initVistaToggle: function() {
            var botonesVista = document.querySelectorAll('[data-vista]');

            botonesVista.forEach(function(botonVista) {
                botonVista.addEventListener('click', function() {
                    var tipoVista = this.dataset.vista;
                    var contenedorGrid = document.querySelector('.flavor-frontend .grid');

                    if (!contenedorGrid) return;

                    // Actualizar botones
                    botonesVista.forEach(function(otroBoton) {
                        otroBoton.classList.remove('bg-gray-100', 'text-gray-800');
                        otroBoton.classList.add('text-gray-400');
                    });
                    this.classList.add('bg-gray-100', 'text-gray-800');
                    this.classList.remove('text-gray-400');

                    // Cambiar layout
                    if (tipoVista === 'list') {
                        contenedorGrid.classList.remove('grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-3');
                        contenedorGrid.classList.add('grid-cols-1');

                        contenedorGrid.querySelectorAll('article').forEach(function(tarjeta) {
                            tarjeta.classList.add('flavor-list-view');
                        });
                    } else {
                        contenedorGrid.classList.remove('grid-cols-1');
                        contenedorGrid.classList.add('grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-3');

                        contenedorGrid.querySelectorAll('article').forEach(function(tarjeta) {
                            tarjeta.classList.remove('flavor-list-view');
                        });
                    }
                });
            });
        },

        // =============================================
        // ORDENACIÓN
        // =============================================

        initOrdenacion: function() {
            var selectorOrden = document.getElementById('flavor-sort');
            if (!selectorOrden) return;

            selectorOrden.addEventListener('change', function() {
                var parametrosActuales = new URLSearchParams(window.location.search);
                parametrosActuales.set('orden', this.value);
                window.location.search = parametrosActuales.toString();
            });
        },

        // =============================================
        // TOAST NOTIFICATIONS
        // =============================================

        mostrarToast: function(mensaje, tipo) {
            tipo = tipo || 'info';

            var coloresFondo = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-amber-500',
                info: 'bg-blue-500'
            };

            var iconosToast = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };

            // Crear contenedor si no existe
            var contenedorToasts = document.getElementById('flavor-toast-container');
            if (!contenedorToasts) {
                contenedorToasts = document.createElement('div');
                contenedorToasts.id = 'flavor-toast-container';
                contenedorToasts.className = 'fixed bottom-4 right-4 z-50 flex flex-col gap-2';
                contenedorToasts.setAttribute('aria-live', 'polite');
                document.body.appendChild(contenedorToasts);
            }

            var elementoToast = document.createElement('div');
            elementoToast.className = coloresFondo[tipo] + ' text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 transform translate-x-full transition-transform duration-300';
            elementoToast.setAttribute('role', 'alert');
            elementoToast.innerHTML =
                '<span class="font-bold text-lg">' + iconosToast[tipo] + '</span>' +
                '<span>' + this.escaparHtml(mensaje) + '</span>' +
                '<button class="ml-2 hover:opacity-70" aria-label="Cerrar">&times;</button>';

            contenedorToasts.appendChild(elementoToast);

            // Animar entrada
            requestAnimationFrame(function() {
                elementoToast.classList.remove('translate-x-full');
                elementoToast.classList.add('translate-x-0');
            });

            // Cerrar al hacer clic
            elementoToast.querySelector('button').addEventListener('click', function() {
                FlavorFrontend.cerrarToast(elementoToast);
            });

            // Auto-cerrar después de 5 segundos
            setTimeout(function() {
                FlavorFrontend.cerrarToast(elementoToast);
            }, 5000);
        },

        cerrarToast: function(elementoToast) {
            elementoToast.classList.remove('translate-x-0');
            elementoToast.classList.add('translate-x-full');

            setTimeout(function() {
                if (elementoToast.parentElement) {
                    elementoToast.parentElement.removeChild(elementoToast);
                }
            }, 300);
        },

        escaparHtml: function(texto) {
            var elementoTemporal = document.createElement('div');
            elementoTemporal.appendChild(document.createTextNode(texto));
            return elementoTemporal.innerHTML;
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            FlavorFrontend.init();
        });
    } else {
        FlavorFrontend.init();
    }

    // Exponer globalmente
    window.FlavorFrontend = FlavorFrontend;

})();

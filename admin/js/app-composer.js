/**
 * Flavor Platform - App Composer
 *
 * Componente interactivo para gestionar plantillas y módulos.
 * Usa Alpine.js para reactividad.
 *
 * @package FlavorPlatform
 * @since 3.1.0
 */
const flavorComposerFactory = () => ({
        // Estado
        pasoActual: 'plantillas',
        perfilSeleccionado: flavorComposerData.perfilActivo || 'personalizado',
        modulosActivos: flavorComposerData.modulosActivos || [],
        categoriaFiltrada: 'todos',
        filtroPerfilTexto: '',
        filtroPerfilCategoria: 'todos',
        filtroPerfilTipo: 'todos',
        filtroPerfilImpacto: 'todos',
        filtroPerfilCapacidad: 'todos',
        filtroPerfilContexto: 'todos',
        filtroModuloTexto: '',
        moduleCategoryMap: {},
        landingTags: {},

        // Multi-perfil
        modoMultiSeleccion: false,
        perfilesSeleccionados: flavorComposerData.perfilesActivos || [flavorComposerData.perfilActivo || 'personalizado'],
        perfilesActivosActuales: flavorComposerData.perfilesActivos || [flavorComposerData.perfilActivo || 'personalizado'],

        // Datos inyectados desde PHP
        perfiles: flavorComposerData.perfiles || {},
        perfilesEcosistema: flavorComposerData.perfilesEcosistema || {},
        categorias: flavorComposerData.categorias || {},
        capacidadesPerfiles: flavorComposerData.capacidadesPerfiles || [],
        contextosPerfiles: flavorComposerData.contextosPerfiles || [],
        modulosRegistrados: flavorComposerData.modulosRegistrados || {},
        nonces: flavorComposerData.nonces || {},
        adminPostUrl: flavorComposerData.adminPostUrl || '',

        // Estado de carga
        cargando: false,

        /**
         * Inicialización
         */
        init() {
            console.log('[App Composer] Perfil activo desde PHP:', flavorComposerData.perfilActivo);
            console.log('[App Composer] perfilSeleccionado inicializado:', this.perfilSeleccionado);
            this.moduleCategoryMap = this.buildModuleCategoryMap();
            this.landingTags = flavorComposerData.landingTags || {};

            // Restaurar paso activo desde localStorage
            const pasoGuardado = localStorage.getItem('flavor_composer_paso');
            if (pasoGuardado && ['plantillas', 'modulos'].includes(pasoGuardado)) {
                this.pasoActual = pasoGuardado;
            }
        },

        /**
         * Navega a un paso
         */
        irAPaso(paso) {
            this.pasoActual = paso;
            // Guardar en localStorage para persistir al recargar
            localStorage.setItem('flavor_composer_paso', paso);
        },

        /**
         * Comprueba si una plantilla está seleccionada
         */
        esPerfilActivo(idPerfil) {
            if (this.modoMultiSeleccion) {
                return this.perfilesSeleccionados.includes(idPerfil);
            }
            return this.perfilSeleccionado === idPerfil;
        },

        perfilEstaActivoEnSistema(idPerfil) {
            return this.perfilesActivosActuales.includes(idPerfil);
        },

        /**
         * Cambia el perfil activo vía submit
         */
        cambiarPerfil(idPerfil) {
            if (this.cargando) return;
            if (this.perfilSeleccionado === idPerfil) return;

            if (!confirm(flavorComposerData.i18n.confirmarCambioPerfil || '¿Deseas cambiar de plantilla?')) {
                return;
            }

            const regenerarPaginas = confirm('¿Regenerar páginas y menú? Aceptar = regenerar. Cancelar = mantener.');
            this.cargando = true;

            const formulario = document.createElement('form');
            formulario.method = 'POST';
            formulario.action = this.adminPostUrl;

            const campos = {
                'action': 'flavor_chat_ia_cambiar_perfil',
                'perfil_id': idPerfil,
                '_wpnonce': this.nonces.cambiarPerfil,
                'regenerar_paginas': regenerarPaginas ? '1' : '0',
                'menu_sync': 'replace'
            };

            Object.entries(campos).forEach(([nombre, valor]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = nombre;
                input.value = valor;
                formulario.appendChild(input);
            });

            document.body.appendChild(formulario);
            formulario.submit();
        },

        /**
         * Comprueba si un módulo está activo
         */
        esModuloActivo(idModulo) {
            return this.modulosActivos.includes(idModulo);
        },

        /**
         * Comprueba si un módulo es requerido por el perfil actual
         */
        esModuloRequerido(idModulo) {
            const perfil = this.perfiles[this.perfilSeleccionado];
            if (!perfil) return false;
            return (perfil.modulos_requeridos || []).includes(idModulo);
        },

        /**
         * Toggle de módulo vía AJAX (sin recargar página)
         */
        toggleModulo(idModulo) {
            if (this.cargando) return;
            if (this.esModuloRequerido(idModulo)) return;

            this.cargando = true;
            const activar = !this.esModuloActivo(idModulo);

            console.log('[Toggle] Módulo:', idModulo, 'Activar:', activar);
            console.log('[Toggle] Módulos activos antes:', this.modulosActivos);

            // Crear FormData para AJAX
            const formData = new FormData();
            formData.append('action', 'flavor_toggle_modulo');
            formData.append('modulo_id', idModulo);
            formData.append('activar', activar ? '1' : '0');
            formData.append('_ajax_nonce', this.nonces.toggleModulo);

            // Petición AJAX
            fetch(ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('[Toggle] Respuesta del servidor:', data);

                if (data.success) {
                    console.log('[Toggle] ✅ Operación exitosa');
                    console.log('[Toggle] Módulos activos desde servidor:', data.data.modulos_activos);

                    // Actualizar estado local con los datos del servidor (fuente única de verdad)
                    this.modulosActivos = data.data.modulos_activos || [];

                    // Actualizar también en flavorComposerData para que persista
                    if (window.flavorComposerData) {
                        window.flavorComposerData.modulosActivos = this.modulosActivos;
                    }

                    console.log('[Toggle] Módulos activos después:', this.modulosActivos);

                    // Mostrar notificación de éxito
                    this.mostrarNotificacion(data.data.message, 'success');
                } else {
                    console.error('[Toggle] ❌ Error del servidor:', data.data);

                    // Si es un módulo requerido, mostrar advertencia especial
                    if (data.data && data.data.modulo_requerido) {
                        this.mostrarNotificacion(data.data.message, 'warning');
                    } else {
                        this.mostrarNotificacion(data.data.message || 'Error al actualizar el módulo', 'error');
                    }

                    // NO actualizar el estado local - mantener el estado anterior
                    console.warn('[Toggle] Estado local NO modificado por error');
                }
            })
            .catch(error => {
                console.error('[Toggle] Error en fetch:', error);
                this.mostrarNotificacion('Error de conexión al actualizar el módulo', 'error');
            })
            .finally(() => {
                this.cargando = false;
            });
        },

        /**
         * Muestra notificación temporal
         */
        mostrarNotificacion(mensaje, tipo = 'success') {
            const notificacion = document.createElement('div');
            notificacion.className = `notice notice-${tipo} is-dismissible`;
            notificacion.innerHTML = `<p>${mensaje}</p>`;

            // Buscar contenedor de notices
            const wrap = document.querySelector('.wrap');
            if (wrap) {
                const h1 = wrap.querySelector('h1');
                if (h1) {
                    h1.insertAdjacentElement('afterend', notificacion);
                } else {
                    wrap.insertAdjacentElement('afterbegin', notificacion);
                }

                // Auto-cerrar después de 3 segundos
                setTimeout(() => {
                    notificacion.remove();
                }, 3000);
            }
        },

        /**
         * Filtra módulos por categoría
         */
        filtrarCategoria(idCategoria) {
            this.categoriaFiltrada = idCategoria;
        },

        /**
         * Construye un mapa de modulo -> categorias
         */
        buildModuleCategoryMap() {
            const map = {};
            Object.entries(this.categorias || {}).forEach(([idCategoria, datos]) => {
                (datos.modulos || []).forEach((idModulo) => {
                    if (!map[idModulo]) {
                        map[idModulo] = [];
                    }
                    map[idModulo].push(idCategoria);
                });
            });
            return map;
        },

        /**
         * Categorias asociadas a un perfil
         */
        obtenerCategoriasPerfil(idPerfil) {
            const perfil = this.perfiles[idPerfil];
            if (!perfil) return [];
            const modulos = []
                .concat(perfil.modulos_requeridos || [])
                .concat(perfil.modulos_opcionales || []);
            const categorias = new Set();
            modulos.forEach((idModulo) => {
                (this.moduleCategoryMap[idModulo] || []).forEach((idCategoria) => categorias.add(idCategoria));
            });
            return Array.from(categorias);
        },

        /**
         * Normaliza texto para filtros
         */
        normalizarTexto(valor) {
            return String(valor || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        },

        /**
         * Comprueba si un perfil pasa los filtros
         */
        perfilCoincideFiltro(idPerfil) {
            const perfil = this.perfiles[idPerfil];
            if (!perfil) return false;
            const perfilEcosistema = this.perfilesEcosistema[idPerfil] || {};

            if (this.filtroPerfilCategoria !== 'todos') {
                const categoriasPerfil = this.obtenerCategoriasPerfil(idPerfil);
                if (!categoriasPerfil.includes(this.filtroPerfilCategoria)) {
                    return false;
                }
            }

            if (this.filtroPerfilTipo !== 'todos') {
                const tipos = perfil.tipo_organizacion || [];
                if (!tipos.includes(this.filtroPerfilTipo)) {
                    return false;
                }
            }

            if (this.filtroPerfilImpacto !== 'todos') {
                const impactos = perfil.impacto_social || [];
                if (!impactos.includes(this.filtroPerfilImpacto)) {
                    return false;
                }
            }

            if (this.filtroPerfilCapacidad !== 'todos') {
                const capacidades = perfilEcosistema.capacidades || [];
                if (!capacidades.includes(this.filtroPerfilCapacidad)) {
                    return false;
                }
            }

            if (this.filtroPerfilContexto !== 'todos') {
                const contextos = perfilEcosistema.contextos || [];
                if (!contextos.includes(this.filtroPerfilContexto)) {
                    return false;
                }
            }

            const texto = this.normalizarTexto(this.filtroPerfilTexto);
            if (!texto) return true;

            const nombre = this.normalizarTexto(perfil.nombre || '');
            const descripcion = this.normalizarTexto(perfil.descripcion || '');
            const capacidadesTexto = this.normalizarTexto((perfilEcosistema.capacidades || []).join(' '));
            const contextosTexto = this.normalizarTexto((perfilEcosistema.contextos || []).join(' '));
            const recomendadosTexto = this.normalizarTexto((perfilEcosistema.recomendados || []).join(' '));

            if (nombre.includes(texto) || descripcion.includes(texto) || capacidadesTexto.includes(texto) || contextosTexto.includes(texto) || recomendadosTexto.includes(texto)) {
                return true;
            }

            const modulos = []
                .concat(perfil.modulos_requeridos || [])
                .concat(perfil.modulos_opcionales || []);
            return modulos.some((idModulo) => {
                const info = this.obtenerInfoModulo(idModulo);
                return this.normalizarTexto(info.name || '').includes(texto);
            });
        },

        /**
         * Comprueba si un modulo pasa el filtro de texto
         */
        moduloCoincideFiltro(idModulo) {
            const texto = this.normalizarTexto(this.filtroModuloTexto);
            if (!texto) return true;
            const info = this.obtenerInfoModulo(idModulo);
            const nombre = this.normalizarTexto(info.name || '');
            const descripcion = this.normalizarTexto(info.description || '');
            return nombre.includes(texto) || descripcion.includes(texto);
        },

        /**
         * Comprueba si una landing/pagina demo pasa los filtros generales
         */
        landingCoincideFiltro(nombre, descripcion, categorias = [], tipos = [], impactos = []) {
            if (this.filtroPerfilCategoria !== 'todos') {
                if (!categorias.includes(this.filtroPerfilCategoria)) {
                    return false;
                }
            }

            if (this.filtroPerfilTipo !== 'todos') {
                if (!tipos.includes(this.filtroPerfilTipo)) {
                    return false;
                }
            }

            if (this.filtroPerfilImpacto !== 'todos') {
                if (!impactos.includes(this.filtroPerfilImpacto)) {
                    return false;
                }
            }

            const texto = this.normalizarTexto(this.filtroPerfilTexto);
            if (!texto) return true;

            const nombreNorm = this.normalizarTexto(nombre || '');
            const descripcionNorm = this.normalizarTexto(descripcion || '');
            return nombreNorm.includes(texto) || descripcionNorm.includes(texto);
        },

        /**
         * Obtiene módulos filtrados por categoría
         */
        obtenerModulosFiltrados() {
            if (this.categoriaFiltrada === 'todos') {
                return Object.keys(this.categorias).reduce((acumulados, idCategoria) => {
                    return acumulados.concat(this.categorias[idCategoria].modulos || []);
                }, []);
            }
            const categoria = this.categorias[this.categoriaFiltrada];
            return categoria ? (categoria.modulos || []) : [];
        },

        /**
         * Obtiene información de un módulo registrado
         */
        obtenerInfoModulo(idModulo) {
            return this.modulosRegistrados[idModulo] || {
                name: idModulo.charAt(0).toUpperCase() + idModulo.slice(1).replace(/_/g, ' '),
                description: ''
            };
        },

        /**
         * Cuenta módulos de una plantilla
         */
        contarModulosPerfil(idPerfil) {
            const perfil = this.perfiles[idPerfil];
            if (!perfil) return 0;
            return (perfil.modulos_requeridos || []).length + (perfil.modulos_opcionales || []).length;
        },

        /**
         * Cuenta módulos activos en una categoría
         */
        contarActivosEnCategoria(idCategoria) {
            const modulos = this.categorias[idCategoria]?.modulos || [];
            return modulos.filter(idModulo => this.esModuloActivo(idModulo)).length;
        },

        obtenerContextosActivos() {
            const contextos = new Set();

            this.modulosActivos.forEach((idModulo) => {
                const modulo = this.modulosRegistrados[idModulo] || {};
                const dashboard = modulo.dashboard || {};
                (dashboard.client_contexts || []).forEach((contexto) => {
                    if (contexto) {
                        contextos.add(contexto);
                    }
                });
            });

            this.perfilesActivosActuales.forEach((idPerfil) => {
                const perfilEcosistema = this.perfilesEcosistema[idPerfil] || {};
                (perfilEcosistema.contextos || []).forEach((contexto) => {
                    if (contexto) {
                        contextos.add(contexto);
                    }
                });
            });

            return Array.from(contextos);
        },

        obtenerCapacidadesActivas() {
            const capacidades = new Set();

            this.perfilesActivosActuales.forEach((idPerfil) => {
                const perfilEcosistema = this.perfilesEcosistema[idPerfil] || {};
                (perfilEcosistema.capacidades || []).forEach((capacidad) => {
                    if (capacidad) {
                        capacidades.add(capacidad);
                    }
                });
            });

            return Array.from(capacidades);
        },

        obtenerPerfilesSugeridos() {
            const contextosActivos = this.obtenerContextosActivos();
            const capacidadesActivas = this.obtenerCapacidadesActivas();

            return Object.entries(this.perfiles)
                .filter(([idPerfil]) => idPerfil !== 'personalizado' && !this.perfilEstaActivoEnSistema(idPerfil))
                .map(([idPerfil, perfil]) => {
                    const ecosistema = this.perfilesEcosistema[idPerfil] || {};
                    const contextos = ecosistema.contextos || [];
                    const capacidades = ecosistema.capacidades || [];
                    const modulosPerfil = []
                        .concat(perfil.modulos_requeridos || [])
                        .concat(perfil.modulos_opcionales || []);

                    const contextosCompartidos = contextos.filter((contexto) => contextosActivos.includes(contexto));
                    const capacidadesCompartidas = capacidades.filter((capacidad) => capacidadesActivas.includes(capacidad));
                    const modulosCompartidos = modulosPerfil.filter((idModulo) => this.esModuloActivo(idModulo));
                    const modulosFaltantes = modulosPerfil.filter((idModulo) => !this.esModuloActivo(idModulo));

                    const score = (contextosCompartidos.length * 4) + (capacidadesCompartidas.length * 3) + (modulosCompartidos.length * 2);

                    return {
                        id: idPerfil,
                        nombre: perfil.nombre || idPerfil,
                        descripcion: perfil.descripcion || '',
                        score,
                        contextosCompartidos: contextosCompartidos.slice(0, 3),
                        capacidadesCompartidas: capacidadesCompartidas.slice(0, 2),
                        modulosFaltantes: modulosFaltantes.length,
                    };
                })
                .filter((perfil) => perfil.score > 0)
                .sort((a, b) => {
                    if (a.score !== b.score) {
                        return b.score - a.score;
                    }

                    return a.modulosFaltantes - b.modulosFaltantes;
                })
                .slice(0, 4);
        },

        /**
         * Toggle perfil en modo multi-selección
         */
        togglePerfilSeleccion(perfilId) {
            if (!this.modoMultiSeleccion) return;

            const index = this.perfilesSeleccionados.indexOf(perfilId);
            if (index > -1) {
                // Si ya está seleccionado, quitar (pero mantener al menos uno)
                if (this.perfilesSeleccionados.length > 1) {
                    this.perfilesSeleccionados.splice(index, 1);
                } else {
                    alert('Debe haber al menos un perfil activo');
                }
            } else {
                // Si no está seleccionado, añadir
                this.perfilesSeleccionados.push(perfilId);
            }
        },

        /**
         * Actualizar modo multi-selección
         */
        actualizarModoMulti() {
            if (!this.modoMultiSeleccion) {
                // Si se desactiva, restaurar perfiles activos originales
                this.perfilesSeleccionados = flavorComposerData.perfilesActivos || [this.perfilSeleccionado];
            }
        },

        /**
         * Aplicar selección de múltiples perfiles
         */
        aplicarPerfilesMultiples() {
            if (this.cargando) return;
            if (this.perfilesSeleccionados.length === 0) {
                alert('Debes seleccionar al menos un perfil');
                return;
            }

            const mensaje = flavorComposerData.i18n.confirmarPerfilesMultiples ||
                           '¿Se activarán los módulos de todos los perfiles seleccionados. ¿Continuar?';
            if (!confirm(mensaje)) return;

            const regenerarPaginas = confirm('¿Regenerar páginas y menú? Aceptar = regenerar. Cancelar = mantener.');
            this.cargando = true;

            // Crear formulario para enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.adminPostUrl;

            // Añadir cada perfil seleccionado como array
            this.perfilesSeleccionados.forEach(perfilId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'perfiles[]';
                input.value = perfilId;
                form.appendChild(input);
            });

            // Añadir action y nonce
            const camposAdicionales = {
                action: 'flavor_chat_ia_cambiar_perfil',
                _wpnonce: this.nonces.cambiarPerfil,
                regenerar_paginas: regenerarPaginas ? '1' : '0',
                menu_sync: 'replace'
            };

            Object.entries(camposAdicionales).forEach(([nombre, valor]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = nombre;
                input.value = valor;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    });

document.addEventListener('alpine:init', () => {
    Alpine.data('flavorComposer', flavorComposerFactory);
});

window.flavorComposer = flavorComposerFactory;

/**
 * Combina flavorComposer y flavorTemplateOrchestrator en un solo estado Alpine.
 * Usa composición directa para evitar problemas con getters y métodos duplicados.
 */
window.flavorComposerState = () => {
    // Obtener instancias base
    const composerBase = window.flavorComposer ? window.flavorComposer() : {};
    const orchestratorBase = window.flavorTemplateOrchestrator ? window.flavorTemplateOrchestrator() : {};

    // Crear objeto combinado con todas las propiedades
    const combined = {};

    // Copiar propiedades del composer (primero)
    Object.keys(composerBase).forEach(key => {
        if (typeof composerBase[key] !== 'function') {
            combined[key] = composerBase[key];
        }
    });

    // Copiar propiedades del orchestrator (pueden sobrescribir)
    Object.keys(orchestratorBase).forEach(key => {
        if (typeof orchestratorBase[key] !== 'function') {
            combined[key] = orchestratorBase[key];
        }
    });

    // Copiar métodos del composer
    Object.keys(composerBase).forEach(key => {
        if (typeof composerBase[key] === 'function') {
            // Renombrar init del composer para evitar conflicto
            if (key === 'init') {
                combined._composerInit = composerBase[key];
            } else if (key === 'mostrarNotificacion') {
                combined._composerNotificacion = composerBase[key];
            } else {
                combined[key] = composerBase[key];
            }
        }
    });

    // Copiar métodos del orchestrator
    Object.keys(orchestratorBase).forEach(key => {
        if (typeof orchestratorBase[key] === 'function') {
            // Renombrar init del orchestrator para evitar conflicto
            if (key === 'init') {
                combined._orchestratorInit = orchestratorBase[key];
            } else if (key === 'mostrarNotificacion') {
                // Usar mostrarNotificacion del orchestrator como principal
                combined.mostrarNotificacion = orchestratorBase[key];
            } else {
                combined[key] = orchestratorBase[key];
            }
        }
    });

    // Crear init combinado que llame a ambos
    combined.init = function() {
        if (this._composerInit) {
            this._composerInit.call(this);
        }
        if (this._orchestratorInit) {
            this._orchestratorInit.call(this);
        }
    };

    // Definir getters computados manualmente para el orchestrator
    Object.defineProperty(combined, 'porcentajeProgreso', {
        get: function() {
            const totalPasos = this.pasosInstalacion ? this.pasosInstalacion.length : 0;
            if (!totalPasos) return 0;

            if (this.hayError) {
                const completados = this.pasosInstalacion.filter(paso => paso.estado === 'completado').length;
                return Math.round((completados / totalPasos) * 100);
            }

            const completados = this.pasosInstalacion.filter(paso =>
                paso.estado === 'completado' || paso.estado === 'omitido'
            ).length;

            return Math.round((completados / totalPasos) * 100);
        },
        enumerable: true,
        configurable: true
    });

    Object.defineProperty(combined, 'tituloProgreso', {
        get: function() {
            if (this.hayError) {
                return this.datosOrquestador?.i18n?.tituloError || 'Error en la instalación';
            }
            if (this.instalacionCompletada) {
                return this.datosOrquestador?.i18n?.tituloCompletado || 'Instalación completada';
            }
            return this.datosOrquestador?.i18n?.tituloInstalando || 'Instalando plantilla...';
        },
        enumerable: true,
        configurable: true
    });

    return combined;
};

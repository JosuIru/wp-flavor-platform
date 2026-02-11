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
        filtroModuloTexto: '',
        moduleCategoryMap: {},
        landingTags: {},

        // Multi-perfil
        modoMultiSeleccion: false,
        perfilesSeleccionados: flavorComposerData.perfilesActivos || [flavorComposerData.perfilActivo || 'personalizado'],

        // Datos inyectados desde PHP
        perfiles: flavorComposerData.perfiles || {},
        categorias: flavorComposerData.categorias || {},
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
        },

        /**
         * Navega a un paso
         */
        irAPaso(paso) {
            this.pasoActual = paso;
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
         * Toggle de módulo vía submit
         */
        toggleModulo(idModulo) {
            if (this.cargando) return;
            if (this.esModuloRequerido(idModulo)) return;

            this.cargando = true;
            const activar = !this.esModuloActivo(idModulo);

            const formulario = document.createElement('form');
            formulario.method = 'POST';
            formulario.action = this.adminPostUrl;

            const campos = {
                'action': 'flavor_chat_ia_toggle_modulo',
                'modulo_id': idModulo,
                'activar': activar ? '1' : '0',
                '_wpnonce': this.nonces.toggleModulo
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

            const texto = this.normalizarTexto(this.filtroPerfilTexto);
            if (!texto) return true;

            const nombre = this.normalizarTexto(perfil.nombre || '');
            const descripcion = this.normalizarTexto(perfil.descripcion || '');

            if (nombre.includes(texto) || descripcion.includes(texto)) {
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
window.flavorComposerState = () => {
    const composer = window.flavorComposer ? flavorComposer() : {};
    const orchestrator = window.flavorTemplateOrchestrator ? flavorTemplateOrchestrator() : {};
    return Object.defineProperties(
        {},
        Object.assign(
            {},
            Object.getOwnPropertyDescriptors(composer),
            Object.getOwnPropertyDescriptors(orchestrator)
        )
    );
};

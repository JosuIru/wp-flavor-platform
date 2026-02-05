/**
 * Flavor Platform - App Composer
 *
 * Componente interactivo para gestionar plantillas y módulos.
 * Usa Alpine.js para reactividad.
 *
 * @package FlavorPlatform
 * @since 3.1.0
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('flavorComposer', () => ({
        // Estado
        pasoActual: 'plantillas',
        perfilSeleccionado: flavorComposerData.perfilActivo || 'personalizado',
        modulosActivos: flavorComposerData.modulosActivos || [],
        categoriaFiltrada: 'todos',

        // Datos inyectados desde PHP
        perfiles: flavorComposerData.perfiles || {},
        categorias: flavorComposerData.categorias || {},
        modulosRegistrados: flavorComposerData.modulosRegistrados || {},
        nonces: flavorComposerData.nonces || {},
        adminPostUrl: flavorComposerData.adminPostUrl || '',

        // Estado de carga
        cargando: false,

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

            this.cargando = true;

            const formulario = document.createElement('form');
            formulario.method = 'POST';
            formulario.action = this.adminPostUrl;

            const campos = {
                'action': 'flavor_chat_ia_cambiar_perfil',
                'perfil_id': idPerfil,
                '_wpnonce': this.nonces.cambiarPerfil
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
        }
    }));
});

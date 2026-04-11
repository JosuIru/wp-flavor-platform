/**
 * Visual Builder Pro - Comandos de Simbolos
 *
 * Integra el sistema de simbolos con el Command Palette y atajos de teclado.
 * Este archivo registra comandos para crear, insertar, desvincular y gestionar
 * simbolos e instancias desde el command palette (Ctrl+K) y atajos de teclado.
 *
 * ATAJOS REGISTRADOS:
 * - Ctrl+Shift+Y: Crear simbolo desde seleccion
 * - Ctrl+Alt+O: Insertar simbolo (abre selector)
 * - Ctrl+Alt+U: Desvincular instancia (detach)
 * - Ctrl+Shift+G: Ir al simbolo maestro (no confundir con Ctrl+G que es group)
 * - F8: Abrir panel de simbolos
 *
 * NOTA: Los atajos evitan conflictos con los ya existentes en vbp-keyboard-shortcuts.json
 *
 * @package Flavor_Chat_IA
 * @since 2.0.22
 */

(function() {
    'use strict';

    /**
     * Sistema de comandos de simbolos
     */
    window.VBPSymbolsCommands = {

        /** Flag de inicializacion */
        inicializado: false,

        /** Categorias registradas en command palette */
        categoriasRegistradas: false,

        /**
         * Inicializar el sistema de comandos
         */
        init: function() {
            if (this.inicializado) {
                return;
            }

            var self = this;

            // Esperar a que el command palette este disponible
            if (typeof Alpine === 'undefined' || !Alpine.store) {
                document.addEventListener('alpine:init', function() {
                    setTimeout(function() {
                        self.registrarComandos();
                        self.registrarAtajos();
                    }, 200);
                });
            } else {
                this.registrarComandos();
                this.registrarAtajos();
            }

            this.inicializado = true;

            if (window.vbpLog) {
                vbpLog.log('[VBPSymbolsCommands] Inicializado');
            }
        },

        /**
         * Registrar comandos en el Command Palette
         * Se integra con el store vbpCommandPalette de Alpine.js
         */
        registrarComandos: function() {
            var self = this;
            var intentos = 0;
            var maxIntentos = 20;

            function intentarRegistrar() {
                var palette = Alpine.store('vbpCommandPalette');

                if (!palette || !palette.commands) {
                    intentos++;
                    if (intentos < maxIntentos) {
                        setTimeout(intentarRegistrar, 100);
                    }
                    return;
                }

                // Verificar que no estan ya registrados
                var yaRegistrado = palette.commands.some(function(cmd) {
                    return cmd.id === 'symbols-create';
                });

                if (yaRegistrado) {
                    return;
                }

                // Array de comandos de simbolos
                var comandosSimbolos = [
                    // Creacion
                    {
                        id: 'symbols-create',
                        label: 'Crear simbolo desde seleccion',
                        category: 'simbolos',
                        icon: '◇',
                        action: 'createSymbol',
                        shortcut: 'Ctrl+Shift+Y',
                        isAvailable: function() {
                            var store = Alpine.store('vbp');
                            return store && store.selection && store.selection.elementIds && store.selection.elementIds.length > 0;
                        }
                    },
                    // Insertar
                    {
                        id: 'symbols-insert',
                        label: 'Insertar simbolo...',
                        category: 'simbolos',
                        icon: '◇+',
                        action: 'insertSymbol',
                        shortcut: 'Ctrl+Alt+O'
                    },
                    // Desvincular
                    {
                        id: 'symbols-detach',
                        label: 'Desvincular instancia',
                        category: 'simbolos',
                        icon: '⊘',
                        action: 'detachInstance',
                        shortcut: 'Ctrl+Alt+U',
                        isAvailable: function() {
                            return self.seleccionEsInstancia();
                        }
                    },
                    // Ir al maestro
                    {
                        id: 'symbols-goto-master',
                        label: 'Ir al simbolo maestro',
                        category: 'simbolos',
                        icon: '→◇',
                        action: 'goToMaster',
                        shortcut: 'Ctrl+Shift+G',
                        isAvailable: function() {
                            return self.seleccionEsInstancia();
                        }
                    },
                    // Resetear overrides
                    {
                        id: 'symbols-reset-overrides',
                        label: 'Resetear todas las modificaciones',
                        category: 'simbolos',
                        icon: '↺',
                        action: 'resetOverrides',
                        isAvailable: function() {
                            if (!self.seleccionEsInstancia()) return false;
                            var store = Alpine.store('vbp');
                            var elementId = store.selection.elementIds[0];
                            return store.instanceHasOverrides && store.instanceHasOverrides(elementId);
                        }
                    },
                    // Sincronizar instancia
                    {
                        id: 'symbols-sync',
                        label: 'Sincronizar instancia con maestro',
                        category: 'simbolos',
                        icon: '↻',
                        action: 'syncInstance',
                        isAvailable: function() {
                            return self.seleccionEsInstancia();
                        }
                    },
                    // Panel de simbolos
                    {
                        id: 'symbols-panel',
                        label: 'Abrir panel de simbolos',
                        category: 'paneles',
                        icon: '◇',
                        action: 'openSymbolsPanel',
                        shortcut: 'F8'
                    },
                    // Editar maestro
                    {
                        id: 'symbols-edit-master',
                        label: 'Editar simbolo maestro',
                        category: 'simbolos',
                        icon: '✎◇',
                        action: 'editMaster',
                        isAvailable: function() {
                            return self.seleccionEsInstancia();
                        }
                    },
                    // Recargar simbolos
                    {
                        id: 'symbols-reload',
                        label: 'Recargar simbolos',
                        category: 'simbolos',
                        icon: '⟳',
                        action: 'reloadSymbols'
                    },
                    // Swap Instance - Cambiar simbolo
                    {
                        id: 'symbols-swap',
                        label: 'Cambiar simbolo de instancia...',
                        category: 'simbolos',
                        icon: '⇄',
                        action: 'swapInstance',
                        shortcut: 'Ctrl+Alt+S',
                        isAvailable: function() {
                            return self.seleccionEsInstancia();
                        }
                    },
                    // Export - Exportar simbolos
                    {
                        id: 'symbols-export',
                        label: 'Exportar todos los simbolos',
                        category: 'simbolos',
                        icon: '↓',
                        action: 'exportSymbols',
                        shortcut: 'Ctrl+Shift+E'
                    },
                    // Import - Importar simbolos
                    {
                        id: 'symbols-import',
                        label: 'Importar simbolos...',
                        category: 'simbolos',
                        icon: '↑',
                        action: 'importSymbols',
                        shortcut: 'Ctrl+Shift+I'
                    }
                ];

                // Agregar comandos al palette
                for (var i = 0; i < comandosSimbolos.length; i++) {
                    palette.commands.push(comandosSimbolos[i]);
                }

                // Registrar handler para ejecutar comandos
                self.registrarHandlerComandos(palette);

                if (window.vbpLog) {
                    vbpLog.log('[VBPSymbolsCommands] Registrados ' + comandosSimbolos.length + ' comandos');
                }
            }

            intentarRegistrar();
        },

        /**
         * Registrar handler para ejecutar comandos de simbolos
         * Extiende el metodo executeCommand del palette
         */
        registrarHandlerComandos: function(palette) {
            var self = this;
            var originalExecute = palette.executeCommand;

            palette.executeCommand = function(cmd) {
                // Verificar si es un comando de simbolos
                if (cmd.category === 'simbolos' || cmd.action === 'openSymbolsPanel') {
                    self.ejecutarComando(cmd);
                    palette.close();
                    return;
                }

                // Ejecutar comando original
                if (typeof originalExecute === 'function') {
                    originalExecute.call(palette, cmd);
                }
            };
        },

        /**
         * Ejecutar comando de simbolos
         */
        ejecutarComando: function(cmd) {
            var self = this;

            switch (cmd.action) {
                case 'createSymbol':
                    self.mostrarDialogoCrearSimbolo();
                    break;

                case 'insertSymbol':
                    self.abrirSelectorSimbolos();
                    break;

                case 'detachInstance':
                    self.desvincularInstanciaSeleccionada();
                    break;

                case 'goToMaster':
                    self.irAlSimboloMaestro();
                    break;

                case 'resetOverrides':
                    self.resetearOverridesSeleccion();
                    break;

                case 'syncInstance':
                    self.sincronizarInstanciaSeleccionada();
                    break;

                case 'openSymbolsPanel':
                    self.abrirPanelSimbolos();
                    break;

                case 'editMaster':
                    self.editarSimboloMaestroSeleccion();
                    break;

                case 'reloadSymbols':
                    self.recargarSimbolos();
                    break;

                case 'swapInstance':
                    self.abrirSwapModal();
                    break;

                case 'exportSymbols':
                    self.exportarSimbolos();
                    break;

                case 'importSymbols':
                    self.importarSimbolos();
                    break;
            }
        },

        /**
         * Registrar atajos de teclado
         */
        registrarAtajos: function() {
            var self = this;

            document.addEventListener('keydown', function(evento) {
                // No capturar si estamos en un input
                var esInput = evento.target.tagName === 'INPUT' ||
                              evento.target.tagName === 'TEXTAREA' ||
                              evento.target.isContentEditable;

                if (esInput) return;

                // Detectar modificadores
                var esCtrl = evento.ctrlKey || evento.metaKey;
                var esAlt = evento.altKey;
                var esShift = evento.shiftKey;
                var tecla = evento.key.toLowerCase();

                // Ctrl+Shift+Y: Crear simbolo desde seleccion
                if (esCtrl && esShift && !esAlt && tecla === 'y') {
                    evento.preventDefault();
                    if (self.haySeleccion()) {
                        self.mostrarDialogoCrearSimbolo();
                    }
                    return;
                }

                // Ctrl+Alt+O: Insertar simbolo
                if (esCtrl && esAlt && !esShift && tecla === 'o') {
                    evento.preventDefault();
                    self.abrirSelectorSimbolos();
                    return;
                }

                // Ctrl+Alt+U: Desvincular instancia
                if (esCtrl && esAlt && !esShift && tecla === 'u') {
                    evento.preventDefault();
                    if (self.seleccionEsInstancia()) {
                        self.desvincularInstanciaSeleccionada();
                    }
                    return;
                }

                // Ctrl+Shift+G: Ir al simbolo maestro (no confundir con Ctrl+G que es group)
                if (esCtrl && esShift && !esAlt && tecla === 'g') {
                    evento.preventDefault();
                    if (self.seleccionEsInstancia()) {
                        self.irAlSimboloMaestro();
                    }
                    return;
                }

                // F8: Abrir panel de simbolos
                if (!esCtrl && !esAlt && !esShift && evento.key === 'F8') {
                    evento.preventDefault();
                    self.abrirPanelSimbolos();
                    return;
                }

                // Ctrl+Alt+S: Swap instance (cambiar simbolo)
                if (esCtrl && esAlt && !esShift && tecla === 's') {
                    evento.preventDefault();
                    if (self.seleccionEsInstancia()) {
                        self.abrirSwapModal();
                    }
                    return;
                }

                // Ctrl+Shift+E: Exportar simbolos
                if (esCtrl && esShift && !esAlt && tecla === 'e') {
                    evento.preventDefault();
                    self.exportarSimbolos();
                    return;
                }

                // Ctrl+Shift+I: Importar simbolos
                if (esCtrl && esShift && !esAlt && tecla === 'i') {
                    evento.preventDefault();
                    self.importarSimbolos();
                    return;
                }
            });
        },

        // =============================================
        // METODOS DE UTILIDAD
        // =============================================

        /**
         * Verificar si hay elementos seleccionados
         */
        haySeleccion: function() {
            var store = Alpine.store('vbp');
            return store && store.selection && store.selection.elementIds && store.selection.elementIds.length > 0;
        },

        /**
         * Verificar si la seleccion actual es una instancia de simbolo
         */
        seleccionEsInstancia: function() {
            var store = Alpine.store('vbp');

            if (!store || !store.selection || !store.selection.elementIds) {
                return false;
            }

            if (store.selection.elementIds.length !== 1) {
                return false;
            }

            var elementId = store.selection.elementIds[0];

            // Verificar con VBPSymbols
            if (window.VBPSymbols && typeof VBPSymbols.esInstancia === 'function') {
                return VBPSymbols.esInstancia(elementId);
            }

            // Fallback: verificar con el store
            if (typeof store.isSymbolInstance === 'function') {
                return store.isSymbolInstance(elementId);
            }

            // Ultimo fallback: verificar estructura del elemento
            var elemento = store.getElementById ? store.getElementById(elementId) : null;
            return elemento && elemento.type === 'symbol-instance';
        },

        /**
         * Obtener ID de la instancia seleccionada
         */
        obtenerIdInstanciaSeleccionada: function() {
            var store = Alpine.store('vbp');
            if (!store || !store.selection || !store.selection.elementIds || store.selection.elementIds.length !== 1) {
                return null;
            }
            return store.selection.elementIds[0];
        },

        // =============================================
        // ACCIONES DE SIMBOLOS
        // =============================================

        /**
         * Mostrar dialogo para crear simbolo
         */
        mostrarDialogoCrearSimbolo: function() {
            var self = this;

            // Usar VBPSymbolsPanel si esta disponible
            if (window.VBPSymbolsPanel && typeof VBPSymbolsPanel.abrirDialogoCrear === 'function') {
                VBPSymbolsPanel.abrirDialogoCrear();
                return;
            }

            // Fallback: prompt basico
            var nombre = prompt('Nombre del simbolo:');

            if (nombre && nombre.trim()) {
                var store = Alpine.store('vbp');

                if (store && typeof store.createSymbolFromSelection === 'function') {
                    store.createSymbolFromSelection(nombre.trim()).then(function(symbol) {
                        self.mostrarNotificacion('Simbolo "' + symbol.name + '" creado', 'success');
                    }).catch(function(error) {
                        self.mostrarNotificacion('Error al crear simbolo: ' + error.message, 'error');
                    });
                } else if (window.VBPSymbols && typeof VBPSymbols.crearSimboloDesdeSeleccion === 'function') {
                    VBPSymbols.crearSimboloDesdeSeleccion(nombre.trim()).then(function(symbol) {
                        self.mostrarNotificacion('Simbolo "' + symbol.name + '" creado', 'success');
                    }).catch(function(error) {
                        self.mostrarNotificacion('Error al crear simbolo: ' + error.message, 'error');
                    });
                }
            }
        },

        /**
         * Abrir selector de simbolos para insertar
         */
        abrirSelectorSimbolos: function() {
            var self = this;

            // Abrir el panel de simbolos en modo insercion
            if (window.VBPSymbolsPanel && typeof VBPSymbolsPanel.abrirEnModoInsercion === 'function') {
                VBPSymbolsPanel.abrirEnModoInsercion();
                return;
            }

            // Alternativa: usar el command palette filtrado
            var palette = Alpine.store('vbpCommandPalette');

            if (palette && palette.commands) {
                // Obtener simbolos disponibles
                var simbolos = [];

                if (window.VBPSymbols && VBPSymbols.symbols) {
                    simbolos = VBPSymbols.symbols;
                } else {
                    var store = Alpine.store('vbp');
                    if (store && store.symbols) {
                        simbolos = store.symbols;
                    }
                }

                if (simbolos.length === 0) {
                    self.mostrarNotificacion('No hay simbolos disponibles. Crea uno primero.', 'warning');
                    return;
                }

                // Crear comandos temporales para insertar cada simbolo
                var comandosInsercion = simbolos.map(function(simbolo) {
                    return {
                        id: 'insert-symbol-' + simbolo.id,
                        label: 'Insertar: ' + simbolo.name,
                        category: 'insertar-simbolo',
                        icon: '◇',
                        action: 'insertSpecificSymbol',
                        value: simbolo.id,
                        temporal: true
                    };
                });

                // Agregar comandos temporales
                for (var i = 0; i < comandosInsercion.length; i++) {
                    palette.commands.push(comandosInsercion[i]);
                }

                // Guardar referencia al handler original
                var executeOriginal = palette.executeCommand;
                var cerrarOriginal = palette.close;

                // Extender para manejar insercion
                palette.executeCommand = function(cmd) {
                    if (cmd.action === 'insertSpecificSymbol') {
                        self.insertarSimbolo(cmd.value);
                        palette.close();
                        return;
                    }
                    executeOriginal.call(palette, cmd);
                };

                // Limpiar al cerrar
                palette.close = function() {
                    // Remover comandos temporales
                    palette.commands = palette.commands.filter(function(cmd) {
                        return !cmd.temporal;
                    });
                    // Restaurar funciones originales
                    palette.executeCommand = executeOriginal;
                    palette.close = cerrarOriginal;
                    // Cerrar
                    cerrarOriginal.call(palette);
                };

                // Abrir palette con filtro
                palette.query = 'Insertar:';
                palette.open();
            } else {
                // Ultimo fallback: abrir panel de simbolos
                self.abrirPanelSimbolos();
            }
        },

        /**
         * Insertar simbolo especifico
         */
        insertarSimbolo: function(symbolId) {
            var self = this;
            var store = Alpine.store('vbp');

            if (store && typeof store.insertSymbolInstance === 'function') {
                store.insertSymbolInstance(symbolId).then(function() {
                    self.mostrarNotificacion('Simbolo insertado', 'success');
                }).catch(function(error) {
                    self.mostrarNotificacion('Error al insertar: ' + error.message, 'error');
                });
            } else if (window.VBPSymbols && typeof VBPSymbols.insertarInstancia === 'function') {
                VBPSymbols.insertarInstancia(symbolId).then(function() {
                    self.mostrarNotificacion('Simbolo insertado', 'success');
                }).catch(function(error) {
                    self.mostrarNotificacion('Error al insertar: ' + error.message, 'error');
                });
            }
        },

        /**
         * Desvincular instancia seleccionada
         */
        desvincularInstanciaSeleccionada: function() {
            var self = this;
            var elementId = this.obtenerIdInstanciaSeleccionada();

            if (!elementId) {
                self.mostrarNotificacion('No hay instancia seleccionada', 'warning');
                return;
            }

            var store = Alpine.store('vbp');

            if (store && typeof store.detachInstance === 'function') {
                store.detachInstance(elementId).then(function() {
                    self.mostrarNotificacion('Instancia desvinculada', 'success');
                }).catch(function(error) {
                    self.mostrarNotificacion('Error al desvincular: ' + error.message, 'error');
                });
            } else if (window.VBPSymbols && typeof VBPSymbols.detachInstancia === 'function') {
                VBPSymbols.detachInstancia(elementId).then(function() {
                    self.mostrarNotificacion('Instancia desvinculada', 'success');
                }).catch(function(error) {
                    self.mostrarNotificacion('Error al desvincular: ' + error.message, 'error');
                });
            }
        },

        /**
         * Ir al simbolo maestro de la instancia seleccionada
         */
        irAlSimboloMaestro: function() {
            var self = this;
            var elementId = this.obtenerIdInstanciaSeleccionada();

            if (!elementId) {
                self.mostrarNotificacion('No hay instancia seleccionada', 'warning');
                return;
            }

            var datos = null;

            // Obtener datos de la instancia
            var store = Alpine.store('vbp');

            if (store && typeof store.getInstanceData === 'function') {
                datos = store.getInstanceData(elementId);
            } else if (window.VBPSymbols && typeof VBPSymbols.obtenerDatosInstancia === 'function') {
                datos = VBPSymbols.obtenerDatosInstancia(elementId);
            }

            if (datos && datos.symbol) {
                // Editar simbolo maestro
                if (store && typeof store.editSymbolMaster === 'function') {
                    store.editSymbolMaster(datos.symbol.id);
                } else if (window.VBPSymbols && typeof VBPSymbols.editarSimboloMaestro === 'function') {
                    VBPSymbols.editarSimboloMaestro(datos.symbol.id);
                }
            } else {
                self.mostrarNotificacion('No se encontro el simbolo maestro', 'error');
            }
        },

        /**
         * Resetear overrides de la instancia seleccionada
         */
        resetearOverridesSeleccion: function() {
            var self = this;
            var elementId = this.obtenerIdInstanciaSeleccionada();

            if (!elementId) {
                self.mostrarNotificacion('No hay instancia seleccionada', 'warning');
                return;
            }

            var store = Alpine.store('vbp');

            if (store && typeof store.resetAllInstanceOverrides === 'function') {
                var resultado = store.resetAllInstanceOverrides(elementId);
                if (resultado) {
                    self.mostrarNotificacion('Modificaciones reseteadas', 'success');
                }
            } else if (window.VBPSymbols && typeof VBPSymbols.resetearTodosOverrides === 'function') {
                var resultado = VBPSymbols.resetearTodosOverrides(elementId);
                if (resultado) {
                    self.mostrarNotificacion('Modificaciones reseteadas', 'success');
                }
            }
        },

        /**
         * Sincronizar instancia seleccionada con el maestro
         */
        sincronizarInstanciaSeleccionada: function() {
            var self = this;
            var elementId = this.obtenerIdInstanciaSeleccionada();

            if (!elementId) {
                self.mostrarNotificacion('No hay instancia seleccionada', 'warning');
                return;
            }

            var store = Alpine.store('vbp');

            if (store && typeof store.syncInstance === 'function') {
                store.syncInstance(elementId).then(function() {
                    self.mostrarNotificacion('Instancia sincronizada', 'success');
                }).catch(function(error) {
                    self.mostrarNotificacion('Error al sincronizar: ' + error.message, 'error');
                });
            } else if (window.VBPSymbols && typeof VBPSymbols.sincronizarInstancia === 'function') {
                VBPSymbols.sincronizarInstancia(elementId).then(function() {
                    self.mostrarNotificacion('Instancia sincronizada', 'success');
                }).catch(function(error) {
                    self.mostrarNotificacion('Error al sincronizar: ' + error.message, 'error');
                });
            }
        },

        /**
         * Abrir panel de simbolos en el sidebar
         */
        abrirPanelSimbolos: function() {
            // Intentar usar el panel nativo si existe
            if (window.VBPSymbolsPanel && typeof VBPSymbolsPanel.abrir === 'function') {
                VBPSymbolsPanel.abrir();
                return;
            }

            // Intentar activar el tab en el sidebar
            var tabSimbolos = document.querySelector('[data-panel="symbols"]');
            if (tabSimbolos) {
                tabSimbolos.click();
                return;
            }

            // Alternativa: buscar por texto
            var tabs = document.querySelectorAll('.vbp-sidebar-tab, .vbp-panel-tab');
            for (var i = 0; i < tabs.length; i++) {
                var textoTab = tabs[i].textContent || tabs[i].innerText;
                if (textoTab.toLowerCase().indexOf('simbol') !== -1 ||
                    textoTab.toLowerCase().indexOf('symbol') !== -1) {
                    tabs[i].click();
                    return;
                }
            }

            // Emitir evento como ultimo recurso
            document.dispatchEvent(new CustomEvent('vbp:panel:open', {
                detail: { panel: 'symbols' }
            }));
        },

        /**
         * Editar simbolo maestro de la seleccion
         */
        editarSimboloMaestroSeleccion: function() {
            this.irAlSimboloMaestro();
        },

        /**
         * Recargar simbolos desde el servidor
         */
        recargarSimbolos: function() {
            var self = this;

            if (window.VBPSymbols && typeof VBPSymbols.recargarSimbolos === 'function') {
                VBPSymbols.recargarSimbolos();
                self.mostrarNotificacion('Simbolos recargados', 'success');
            } else {
                var store = Alpine.store('vbp');
                if (store && typeof store.reloadSymbols === 'function') {
                    store.reloadSymbols();
                    self.mostrarNotificacion('Simbolos recargados', 'success');
                }
            }
        },

        // =============================================
        // NOTIFICACIONES
        // =============================================

        /**
         * Mostrar notificacion al usuario
         */
        mostrarNotificacion: function(mensaje, tipo) {
            tipo = tipo || 'info';

            // Usar VBPToast si esta disponible
            if (window.VBPToast) {
                switch (tipo) {
                    case 'success':
                        VBPToast.success(mensaje);
                        break;
                    case 'error':
                        VBPToast.error(mensaje);
                        break;
                    case 'warning':
                        VBPToast.warning(mensaje);
                        break;
                    default:
                        VBPToast.info(mensaje);
                }
                return;
            }

            // Fallback: console
            if (window.vbpLog) {
                switch (tipo) {
                    case 'error':
                        vbpLog.error(mensaje);
                        break;
                    case 'warning':
                        vbpLog.warn(mensaje);
                        break;
                    default:
                        vbpLog.log(mensaje);
                }
            } else {
                console.log('[VBP]', mensaje);
            }
        },

        // =============================================
        // SWAP INSTANCE
        // =============================================

        /**
         * Abrir modal de swap para cambiar simbolo de instancia
         */
        abrirSwapModal: function() {
            var elementId = this.obtenerIdInstanciaSeleccionada();

            if (!elementId) {
                this.mostrarNotificacion('No hay instancia seleccionada', 'warning');
                return;
            }

            // Verificar que VBPSwapModal este disponible
            if (window.VBPSwapModal && typeof VBPSwapModal.abrir === 'function') {
                VBPSwapModal.abrir(elementId);
                return;
            }

            // Alternativa: emitir evento para que el modal lo capture
            document.dispatchEvent(new CustomEvent('vbp:swap-modal:open', {
                detail: { elementId: elementId }
            }));
        },

        // =============================================
        // IMPORT/EXPORT
        // =============================================

        /**
         * Exportar todos los simbolos
         */
        exportarSimbolos: function() {
            var self = this;

            if (window.VBPSymbols && typeof VBPSymbols.downloadExport === 'function') {
                VBPSymbols.downloadExport([]);
                self.mostrarNotificacion('Exportando simbolos...', 'info');
            } else {
                self.mostrarNotificacion('Funcion de exportacion no disponible', 'warning');
            }
        },

        /**
         * Mostrar modal de importacion de simbolos
         */
        importarSimbolos: function() {
            var self = this;

            if (window.VBPSymbols && typeof VBPSymbols.showImportModal === 'function') {
                VBPSymbols.showImportModal();
            } else {
                // Fallback: crear input de archivo temporal
                self.importarSimbolosFallback();
            }
        },

        /**
         * Fallback para importar simbolos si el modal no esta disponible
         */
        importarSimbolosFallback: function() {
            var self = this;

            // Crear input temporal
            var fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = '.json';
            fileInput.style.display = 'none';

            fileInput.addEventListener('change', function(changeEvent) {
                var selectedFile = changeEvent.target.files[0];
                if (!selectedFile) {
                    document.body.removeChild(fileInput);
                    return;
                }

                if (window.VBPSymbols && typeof VBPSymbols.importFromFile === 'function') {
                    VBPSymbols.importFromFile(selectedFile, { mode: 'merge' })
                        .then(function(importResult) {
                            self.mostrarNotificacion(
                                'Importados ' + importResult.imported + ' simbolos, ' +
                                importResult.updated + ' actualizados',
                                'success'
                            );
                        })
                        .catch(function(importError) {
                            self.mostrarNotificacion('Error: ' + importError.message, 'error');
                        })
                        .finally(function() {
                            document.body.removeChild(fileInput);
                        });
                } else {
                    self.mostrarNotificacion('Funcion de importacion no disponible', 'warning');
                    document.body.removeChild(fileInput);
                }
            });

            document.body.appendChild(fileInput);
            fileInput.click();
        }
    };

    // =============================================
    // AUTO-INICIALIZACION
    // =============================================

    // Esperar a que VBPSymbols este disponible antes de inicializar
    function esperarEInicializar() {
        if (window.VBPSymbols && VBPSymbols.inicializado) {
            VBPSymbolsCommands.init();
        } else {
            // Escuchar evento de simbolos listo
            document.addEventListener('vbp:symbols:ready', function() {
                VBPSymbolsCommands.init();
            });

            // Timeout de seguridad
            setTimeout(function() {
                if (!VBPSymbolsCommands.inicializado) {
                    VBPSymbolsCommands.init();
                }
            }, 2000);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', esperarEInicializar);
    } else {
        esperarEInicializar();
    }

})();

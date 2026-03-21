/**
 * Visual Builder Pro - Keyboard Transform Module
 * Transformaciones: alineación, distribución, rotación, nudge
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPKeyboardTransform = {
    /**
     * Nudge selección
     */
    nudgeSelection: function(dx, dy) {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) return;

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element && element.styles && element.styles.position) {
                var newX = (element.styles.position.x || 0) + dx;
                var newY = (element.styles.position.y || 0) + dy;

                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        position: Object.assign({}, element.styles.position, {
                            x: newX,
                            y: newY
                        })
                    })
                });
            }
        });

        store.isDirty = true;
    },

    /**
     * Mover selección al borde
     */
    moveSelectionToEdge: function(edge) {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) return;

        store.saveToHistory();

        var canvas = document.querySelector('.vbp-canvas');
        var canvasRect = canvas ? canvas.getBoundingClientRect() : { width: 1200, height: 800 };

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element && element.styles) {
                var newPosition = Object.assign({}, element.styles.position || {});

                switch (edge) {
                    case 'top':
                        newPosition.y = 0;
                        break;
                    case 'bottom':
                        var altura = element.styles.size ? element.styles.size.height || 100 : 100;
                        newPosition.y = canvasRect.height - altura;
                        break;
                    case 'left':
                        newPosition.x = 0;
                        break;
                    case 'right':
                        var ancho = element.styles.size ? element.styles.size.width || 100 : 100;
                        newPosition.x = canvasRect.width - ancho;
                        break;
                }

                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        position: newPosition
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Movido a ' + edge);
    },

    /**
     * Alinear elementos
     */
    alignElements: function(alignment) {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length < 2) {
            window.vbpKeyboard.showNotification('Selecciona al menos 2 elementos', 'warning');
            return;
        }

        store.saveToHistory();

        var elementos = store.selection.elementIds.map(function(id) {
            return store.getElement(id);
        }).filter(function(el) { return el !== null; });

        var bounds = elementos.map(function(el) {
            return window.VBPKeyboardTransform.getElementBounds(el);
        });

        var referencia;
        switch (alignment) {
            case 'left':
                referencia = Math.min.apply(null, bounds.map(function(b) { return b.x; }));
                break;
            case 'right':
                referencia = Math.max.apply(null, bounds.map(function(b) { return b.x + b.width; }));
                break;
            case 'top':
                referencia = Math.min.apply(null, bounds.map(function(b) { return b.y; }));
                break;
            case 'bottom':
                referencia = Math.max.apply(null, bounds.map(function(b) { return b.y + b.height; }));
                break;
            case 'centerH':
                var minX = Math.min.apply(null, bounds.map(function(b) { return b.x; }));
                var maxX = Math.max.apply(null, bounds.map(function(b) { return b.x + b.width; }));
                referencia = (minX + maxX) / 2;
                break;
            case 'centerV':
                var minY = Math.min.apply(null, bounds.map(function(b) { return b.y; }));
                var maxY = Math.max.apply(null, bounds.map(function(b) { return b.y + b.height; }));
                referencia = (minY + maxY) / 2;
                break;
        }

        elementos.forEach(function(element, i) {
            var bound = bounds[i];
            var newPosition = Object.assign({}, element.styles ? element.styles.position : {});

            switch (alignment) {
                case 'left':
                    newPosition.x = referencia;
                    break;
                case 'right':
                    newPosition.x = referencia - bound.width;
                    break;
                case 'top':
                    newPosition.y = referencia;
                    break;
                case 'bottom':
                    newPosition.y = referencia - bound.height;
                    break;
                case 'centerH':
                    newPosition.x = referencia - bound.width / 2;
                    break;
                case 'centerV':
                    newPosition.y = referencia - bound.height / 2;
                    break;
            }

            store.updateElement(element.id, {
                styles: Object.assign({}, element.styles, {
                    position: newPosition
                })
            });
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Alineado: ' + alignment);
    },

    /**
     * Distribuir elementos
     */
    distributeElements: function(direction) {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length < 3) {
            window.vbpKeyboard.showNotification('Selecciona al menos 3 elementos para distribuir', 'warning');
            return;
        }

        store.saveToHistory();

        var elementos = store.selection.elementIds.map(function(id) {
            return store.getElement(id);
        }).filter(function(el) { return el !== null; });

        var bounds = elementos.map(function(el) {
            return {
                element: el,
                bounds: window.VBPKeyboardTransform.getElementBounds(el)
            };
        });

        if (direction === 'horizontal') {
            bounds.sort(function(a, b) { return a.bounds.x - b.bounds.x; });

            var primerX = bounds[0].bounds.x;
            var ultimoX = bounds[bounds.length - 1].bounds.x + bounds[bounds.length - 1].bounds.width;
            var anchoTotal = bounds.reduce(function(sum, b) { return sum + b.bounds.width; }, 0);
            var espacioDisponible = ultimoX - primerX - anchoTotal;
            var espacioEntre = espacioDisponible / (bounds.length - 1);

            var xActual = primerX;
            bounds.forEach(function(item) {
                store.updateElement(item.element.id, {
                    styles: Object.assign({}, item.element.styles, {
                        position: Object.assign({}, item.element.styles.position, {
                            x: xActual
                        })
                    })
                });
                xActual += item.bounds.width + espacioEntre;
            });
        } else {
            bounds.sort(function(a, b) { return a.bounds.y - b.bounds.y; });

            var primerY = bounds[0].bounds.y;
            var ultimoY = bounds[bounds.length - 1].bounds.y + bounds[bounds.length - 1].bounds.height;
            var altoTotal = bounds.reduce(function(sum, b) { return sum + b.bounds.height; }, 0);
            var espacioVertical = ultimoY - primerY - altoTotal;
            var espacioEntreV = espacioVertical / (bounds.length - 1);

            var yActual = primerY;
            bounds.forEach(function(item) {
                store.updateElement(item.element.id, {
                    styles: Object.assign({}, item.element.styles, {
                        position: Object.assign({}, item.element.styles.position, {
                            y: yActual
                        })
                    })
                });
                yActual += item.bounds.height + espacioEntreV;
            });
        }

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Distribuido ' + direction + 'mente');
    },

    /**
     * Cambiar orden Z
     */
    changeZOrder: function(direction) {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length !== 1) {
            window.vbpKeyboard.showNotification('Selecciona un elemento', 'warning');
            return;
        }

        store.saveToHistory();

        var elementId = store.selection.elementIds[0];
        var indice = store.elements.findIndex(function(el) { return el.id === elementId; });

        if (indice === -1) return;

        var nuevoIndice;
        switch (direction) {
            case 'forward':
                nuevoIndice = Math.min(indice + 1, store.elements.length - 1);
                break;
            case 'backward':
                nuevoIndice = Math.max(indice - 1, 0);
                break;
            case 'front':
                nuevoIndice = store.elements.length - 1;
                break;
            case 'back':
                nuevoIndice = 0;
                break;
        }

        if (nuevoIndice !== indice) {
            var elemento = store.elements.splice(indice, 1)[0];
            store.elements.splice(nuevoIndice, 0, elemento);
            store.isDirty = true;
            window.vbpKeyboard.showNotification('Orden Z: ' + direction);
        }
    },

    /**
     * Igualar tamaño
     */
    matchSize: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length < 2) {
            window.vbpKeyboard.showNotification('Selecciona al menos 2 elementos', 'warning');
            return;
        }

        store.saveToHistory();

        var primerElemento = store.getElement(store.selection.elementIds[0]);
        if (!primerElemento || !primerElemento.styles || !primerElemento.styles.size) {
            window.vbpKeyboard.showNotification('El primer elemento no tiene tamaño definido', 'warning');
            return;
        }

        var tamanoReferencia = primerElemento.styles.size;

        store.selection.elementIds.slice(1).forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        size: JSON.parse(JSON.stringify(tamanoReferencia))
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Tamaños igualados');
    },

    /**
     * Intercambiar posiciones de elementos
     */
    swapElements: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length !== 2) {
            window.vbpKeyboard.showNotification('Selecciona exactamente 2 elementos para intercambiar', 'warning');
            return;
        }

        store.saveToHistory();

        var el1 = store.getElement(store.selection.elementIds[0]);
        var el2 = store.getElement(store.selection.elementIds[1]);

        if (!el1 || !el2) return;

        var pos1 = el1.styles && el1.styles.position ? JSON.parse(JSON.stringify(el1.styles.position)) : {};
        var pos2 = el2.styles && el2.styles.position ? JSON.parse(JSON.stringify(el2.styles.position)) : {};

        store.updateElement(el1.id, {
            styles: Object.assign({}, el1.styles, { position: pos2 })
        });
        store.updateElement(el2.id, {
            styles: Object.assign({}, el2.styles, { position: pos1 })
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Posiciones intercambiadas');
    },

    /**
     * Envolver en contenedor
     */
    wrapInContainer: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para envolver', 'warning');
            return;
        }

        store.saveToHistory();

        var elementosAEnvolver = [];
        var indicesMasAlto = 0;
        var minX = Infinity, minY = Infinity, maxX = 0, maxY = 0;

        store.selection.elementIds.forEach(function(id) {
            var elemento = store.getElement(id);
            var indice = store.elements.findIndex(function(el) { return el.id === id; });
            if (elemento) {
                elementosAEnvolver.push(JSON.parse(JSON.stringify(elemento)));
                if (indice > indicesMasAlto) indicesMasAlto = indice;

                var bounds = window.VBPKeyboardTransform.getElementBounds(elemento);
                if (bounds.x < minX) minX = bounds.x;
                if (bounds.y < minY) minY = bounds.y;
                if (bounds.x + bounds.width > maxX) maxX = bounds.x + bounds.width;
                if (bounds.y + bounds.height > maxY) maxY = bounds.y + bounds.height;
            }
        });

        store.selection.elementIds.forEach(function(id) {
            var indice = store.elements.findIndex(function(el) { return el.id === id; });
            if (indice !== -1) {
                store.elements.splice(indice, 1);
            }
        });

        var containerId = 'el_' + Math.random().toString(36).substr(2, 9);
        var container = {
            id: containerId,
            type: 'container',
            name: 'Contenedor (' + elementosAEnvolver.length + ' elementos)',
            visible: true,
            locked: false,
            children: elementosAEnvolver,
            data: {},
            styles: {
                position: { x: minX, y: minY },
                size: { width: maxX - minX, height: maxY - minY },
                layout: { display: 'block' }
            }
        };

        var posicionInsercion = Math.min(indicesMasAlto, store.elements.length);
        store.elements.splice(posicionInsercion, 0, container);

        store.isDirty = true;
        store.setSelection([containerId]);

        window.vbpKeyboard.showNotification('Envuelto en contenedor');
    },

    /**
     * Apilar elementos
     */
    stackElements: function(direction) {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length < 2) {
            window.vbpKeyboard.showNotification('Selecciona al menos 2 elementos para apilar', 'warning');
            return;
        }

        store.saveToHistory();

        var elementos = store.selection.elementIds.map(function(id) {
            return store.getElement(id);
        }).filter(function(el) { return el !== null; });

        var bounds = elementos.map(function(el) {
            return window.VBPKeyboardTransform.getElementBounds(el);
        });

        var primerBound = bounds[0];
        var posActual = direction === 'horizontal' ? primerBound.x : primerBound.y;
        var gap = 16;

        elementos.forEach(function(element, i) {
            var bound = bounds[i];
            var newPosition = Object.assign({}, element.styles ? element.styles.position : {});

            if (direction === 'horizontal') {
                newPosition.x = posActual;
                posActual += bound.width + gap;
            } else {
                newPosition.y = posActual;
                posActual += bound.height + gap;
            }

            store.updateElement(element.id, {
                styles: Object.assign({}, element.styles, {
                    position: newPosition
                })
            });
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Apilado ' + direction + 'mente');
    },

    /**
     * Rotar selección
     */
    rotateSelection: function(degrees) {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para rotar', 'warning');
            return;
        }

        store.saveToHistory();

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                var rotacionActual = element.styles && element.styles.transform && element.styles.transform.rotate
                    ? element.styles.transform.rotate
                    : 0;

                var nuevaRotacion = (rotacionActual + degrees) % 360;

                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        transform: Object.assign({}, element.styles ? element.styles.transform : {}, {
                            rotate: nuevaRotacion
                        })
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Rotado ' + degrees + '°');
    },

    /**
     * Resetear rotación
     */
    resetRotation: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) return;

        store.saveToHistory();

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element && element.styles && element.styles.transform) {
                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        transform: Object.assign({}, element.styles.transform, {
                            rotate: 0
                        })
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Rotación reseteada');
    },

    /**
     * Flip elemento
     */
    flipElement: function(direction) {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para voltear', 'warning');
            return;
        }

        store.saveToHistory();

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                var transform = element.styles && element.styles.transform ? element.styles.transform : {};
                var scaleX = transform.scaleX !== undefined ? transform.scaleX : 1;
                var scaleY = transform.scaleY !== undefined ? transform.scaleY : 1;

                if (direction === 'horizontal') {
                    scaleX = scaleX * -1;
                } else {
                    scaleY = scaleY * -1;
                }

                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        transform: Object.assign({}, transform, {
                            scaleX: scaleX,
                            scaleY: scaleY
                        })
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Volteado ' + direction + 'mente');
    },

    /**
     * Resetear posición
     */
    resetPosition: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) return;

        store.saveToHistory();

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element && element.styles) {
                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        position: { x: 0, y: 0 }
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Posición reseteada');
    },

    /**
     * Fit content
     */
    fitContent: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos', 'warning');
            return;
        }

        store.saveToHistory();

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        size: { width: 'auto', height: 'auto' }
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Ajustado al contenido');
    },

    /**
     * Fill parent
     */
    fillParent: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos', 'warning');
            return;
        }

        store.saveToHistory();

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        size: { width: '100%', height: '100%' }
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Llenando contenedor');
    },

    /**
     * Centrar en viewport
     */
    centerInViewport: function() {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) return;

        var elementId = store.selection.elementIds[0];
        var elementoCanvas = document.querySelector('[data-element-id="' + elementId + '"]');

        if (elementoCanvas) {
            elementoCanvas.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'center'
            });
            window.vbpKeyboard.showNotification('Centrado en vista');
        }
    },

    /**
     * Obtener bounds del elemento
     */
    getElementBounds: function(element) {
        var x = 0, y = 0, width = 100, height = 100;

        if (element.styles) {
            if (element.styles.position) {
                x = element.styles.position.x || 0;
                y = element.styles.position.y || 0;
            }
            if (element.styles.size) {
                width = element.styles.size.width || 100;
                height = element.styles.size.height || 100;
            }
        }

        return { x: x, y: y, width: width, height: height };
    },

    /**
     * Set spacing preset
     */
    setSpacingPreset: function(spacing) {
        var store = Alpine.store('vbp');

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos', 'warning');
            return;
        }

        store.saveToHistory();

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        spacing: {
                            padding: { top: spacing, right: spacing, bottom: spacing, left: spacing },
                            margin: element.styles && element.styles.spacing ? element.styles.spacing.margin : {}
                        }
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('Spacing: ' + spacing + 'px');
    }
};

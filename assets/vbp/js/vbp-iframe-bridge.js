/**
 * Visual Builder Pro - Iframe Canvas Bridge (POC)
 *
 * Corre DENTRO del iframe que sirve el canvas renderizado por PHP
 * (identico al frontend). Se encarga de:
 *
 *  - Detectar clicks en elementos (.vbp-iframe-element) y notificar al parent.
 *  - Capturar edicion inline de [contenteditable][data-field|data-path] y
 *    reportar cambios al parent para persistir en el store Alpine.
 *  - Exponer API en window.vbpIframeCanvas para que el parent pueda pedir
 *    refresh, aplicar seleccion, sustituir HTML de un elemento, etc.
 *
 * Comunicacion: mismo origen, acceso directo a window.parent.
 */
(function () {
    'use strict';

    var postIdDelDocumento = (window.VBP_Config && window.VBP_Config.iframePostId) || 0;

    function obtenerRaizElementos() {
        return document.querySelector('.vbp-landing');
    }

    function encontrarWrapperElementoDesde(nodoObjetivo) {
        if (!nodoObjetivo || !nodoObjetivo.closest) return null;
        return nodoObjetivo.closest('.vbp-iframe-element');
    }

    function limpiarSeleccionVisual() {
        var raizLanding = obtenerRaizElementos();
        if (!raizLanding) return;
        var previamenteSeleccionados = raizLanding.querySelectorAll('[data-vbp-selected="true"]');
        Array.prototype.forEach.call(previamenteSeleccionados, function (nodo) {
            nodo.removeAttribute('data-vbp-selected');
        });
    }

    function aplicarSeleccionVisual(idElemento) {
        limpiarSeleccionVisual();
        if (!idElemento) return;
        var raizLanding = obtenerRaizElementos();
        if (!raizLanding) return;
        var wrapperEncontrado = raizLanding.querySelector('.vbp-iframe-element[data-element-id="' + idElemento + '"]');
        if (wrapperEncontrado) wrapperEncontrado.setAttribute('data-vbp-selected', 'true');
    }

    function obtenerHostDelParent() {
        try {
            if (window.parent && window.parent !== window && window.parent.vbpIframeCanvasHost) {
                return window.parent.vbpIframeCanvasHost;
            }
        } catch (errorAcceso) {
            console.warn('[vbp-iframe-bridge] no se pudo acceder al parent:', errorAcceso);
        }
        return null;
    }

    var ultimoIdSeleccionEmitido = undefined;

    function notificarParentSeleccion(idElemento, tipoElemento, rectElemento) {
        // Dedup: solo emitir cuando cambia el elementId seleccionado
        if (idElemento === ultimoIdSeleccionEmitido) return;
        ultimoIdSeleccionEmitido = idElemento;

        var hostParent = obtenerHostDelParent();
        if (!hostParent || typeof hostParent.onElementSelected !== 'function') return;
        hostParent.onElementSelected({
            postId: postIdDelDocumento,
            elementId: idElemento,
            elementType: tipoElemento,
            rect: rectElemento
        });
    }

    function notificarParentEdicionCampo(idElemento, tipoElemento, nombreCampo, valorNuevo, rutaAnidada) {
        var hostParent = obtenerHostDelParent();
        if (!hostParent || typeof hostParent.onFieldEdited !== 'function') return;
        hostParent.onFieldEdited({
            postId: postIdDelDocumento,
            elementId: idElemento,
            elementType: tipoElemento,
            field: nombreCampo,
            path: rutaAnidada,
            value: valorNuevo
        });
    }

    function onClickDocumento(eventoClick) {
        var objetivoClick = eventoClick.target;

        // No interrumpir foco en campos editables
        if (objetivoClick.closest && objetivoClick.closest('[contenteditable="true"]')) {
            // Click en area editable: resolver wrapper y seleccionar, sin preventDefault
            var wrapperDeEditable = encontrarWrapperElementoDesde(objetivoClick);
            if (wrapperDeEditable) {
                aplicarSeleccionVisual(wrapperDeEditable.getAttribute('data-element-id'));
                notificarParentSeleccion(
                    wrapperDeEditable.getAttribute('data-element-id'),
                    wrapperDeEditable.getAttribute('data-element-type'),
                    wrapperDeEditable.getBoundingClientRect()
                );
            }
            return;
        }

        // Prevenir navegacion de links/buttons dentro del canvas
        if (objetivoClick.closest && objetivoClick.closest('a, button')) {
            eventoClick.preventDefault();
        }

        var wrapperElemento = encontrarWrapperElementoDesde(objetivoClick);
        if (!wrapperElemento) {
            limpiarSeleccionVisual();
            notificarParentSeleccion(null, null, null);
            return;
        }

        var idElementoClickeado = wrapperElemento.getAttribute('data-element-id');
        var tipoElementoClickeado = wrapperElemento.getAttribute('data-element-type');
        aplicarSeleccionVisual(idElementoClickeado);
        notificarParentSeleccion(
            idElementoClickeado,
            tipoElementoClickeado,
            wrapperElemento.getBoundingClientRect()
        );
    }

    // Cache del valor inicial de cada contenteditable para detectar cambios reales
    var valoresInicialesContenteditable = new WeakMap();

    function onFocusContenteditable(eventoFoco) {
        var nodoEditable = eventoFoco.target;
        if (!nodoEditable.isContentEditable) return;
        valoresInicialesContenteditable.set(nodoEditable, nodoEditable.innerHTML);
    }

    function onBlurContenteditable(eventoBlur) {
        var nodoEditable = eventoBlur.target;
        if (!nodoEditable.isContentEditable) return;

        var wrapperPadre = encontrarWrapperElementoDesde(nodoEditable);
        if (!wrapperPadre) return;

        var campoData = nodoEditable.getAttribute('data-field');
        var rutaData  = nodoEditable.getAttribute('data-path');
        if (!campoData && !rutaData) return;

        var valorAnterior = valoresInicialesContenteditable.get(nodoEditable);
        var valorActualHtml = nodoEditable.innerHTML;
        if (valorAnterior === valorActualHtml) return; // sin cambio real

        // Para data-field simples usamos innerText (plano), para path podria ser HTML
        var valorEnviar = rutaData ? valorActualHtml : nodoEditable.innerText;

        notificarParentEdicionCampo(
            wrapperPadre.getAttribute('data-element-id'),
            wrapperPadre.getAttribute('data-element-type'),
            campoData,
            valorEnviar,
            rutaData
        );
    }

    function notificarParentRectCambio() {
        if (!ultimoIdSeleccionEmitido) return;
        var hostParent = obtenerHostDelParent();
        if (!hostParent || typeof hostParent.onElementRectChanged !== 'function') return;
        var raizLanding = obtenerRaizElementos();
        if (!raizLanding) return;
        var nodoSeleccionado = raizLanding.querySelector('.vbp-iframe-element[data-element-id="' + ultimoIdSeleccionEmitido + '"]');
        if (!nodoSeleccionado) return;
        hostParent.onElementRectChanged({
            elementId: ultimoIdSeleccionEmitido,
            rect: nodoSeleccionado.getBoundingClientRect()
        });
    }

    var timeoutRectCambio = null;
    function programarNotificacionRectCambio() {
        if (timeoutRectCambio) clearTimeout(timeoutRectCambio);
        timeoutRectCambio = setTimeout(function () {
            timeoutRectCambio = null;
            notificarParentRectCambio();
        }, 60);
    }

    // ---------- Drag & drop desde el panel del parent al iframe ----------
    function obtenerBloqueArrastrado() {
        try {
            return (window.parent && window.parent.__vbpDraggedBlock) || null;
        } catch (errorAcceso) {
            return null;
        }
    }

    function calcularIndiceDropPorY(clientY) {
        var raizLanding = obtenerRaizElementos();
        if (!raizLanding) return 0;
        var wrappersElementos = raizLanding.querySelectorAll('.vbp-iframe-element');
        if (!wrappersElementos.length) return 0;
        for (var i = 0; i < wrappersElementos.length; i++) {
            var rectWrapper = wrappersElementos[i].getBoundingClientRect();
            var puntoMedio  = rectWrapper.top + (rectWrapper.height / 2);
            if (clientY < puntoMedio) return i;
        }
        return wrappersElementos.length;
    }

    function obtenerOCrearIndicadorDrop() {
        var nodoIndicador = document.getElementById('vbp-iframe-drop-indicator');
        if (nodoIndicador) return nodoIndicador;
        nodoIndicador = document.createElement('div');
        nodoIndicador.id = 'vbp-iframe-drop-indicator';
        nodoIndicador.style.cssText = [
            'position:absolute',
            'left:0', 'right:0',
            'height:3px',
            'background:#3b82f6',
            'box-shadow:0 0 10px rgba(59,130,246,0.9)',
            'pointer-events:none',
            'z-index:9999',
            'transition:top 80ms ease',
            'display:none'
        ].join(';');
        document.body.appendChild(nodoIndicador);
        return nodoIndicador;
    }

    function mostrarIndicadorDrop(clientY) {
        var raizLanding = obtenerRaizElementos();
        if (!raizLanding) return;
        var wrappersElementos = raizLanding.querySelectorAll('.vbp-iframe-element');
        var topRelativoAlBody;

        if (!wrappersElementos.length) {
            var rectLanding = raizLanding.getBoundingClientRect();
            topRelativoAlBody = rectLanding.top + window.scrollY;
        } else {
            var indiceCalculado = calcularIndiceDropPorY(clientY);
            if (indiceCalculado >= wrappersElementos.length) {
                var ultimoWrapper = wrappersElementos[wrappersElementos.length - 1];
                topRelativoAlBody = ultimoWrapper.getBoundingClientRect().bottom + window.scrollY;
            } else {
                topRelativoAlBody = wrappersElementos[indiceCalculado].getBoundingClientRect().top + window.scrollY;
            }
        }

        var indicadorDrop = obtenerOCrearIndicadorDrop();
        indicadorDrop.style.top = topRelativoAlBody + 'px';
        indicadorDrop.style.display = 'block';
    }

    function ocultarIndicadorDrop() {
        var nodoIndicador = document.getElementById('vbp-iframe-drop-indicator');
        if (nodoIndicador) nodoIndicador.style.display = 'none';
    }

    function onDragOverIframe(eventoDragover) {
        if (!obtenerBloqueArrastrado()) return;
        eventoDragover.preventDefault();
        if (eventoDragover.dataTransfer) eventoDragover.dataTransfer.dropEffect = 'copy';
        mostrarIndicadorDrop(eventoDragover.clientY);
    }

    function onDropIframe(eventoDrop) {
        var bloqueArrastrado = obtenerBloqueArrastrado();
        if (!bloqueArrastrado) return;
        eventoDrop.preventDefault();
        ocultarIndicadorDrop();

        var indiceCalculado = calcularIndiceDropPorY(eventoDrop.clientY);
        var hostParent = obtenerHostDelParent();
        if (!hostParent || typeof hostParent.onBlockDropped !== 'function') {
            console.warn('[vbp-iframe-bridge] host no disponible para drop');
            return;
        }
        hostParent.onBlockDropped({
            blockType: bloqueArrastrado.type,
            index: indiceCalculado
        });
        // Limpiar bloque en parent
        try { window.parent.__vbpDraggedBlock = null; } catch (_) {}
    }

    function onDragLeaveIframe(eventoDragleave) {
        if (!eventoDragleave.relatedTarget || eventoDragleave.relatedTarget === document.documentElement) {
            ocultarIndicadorDrop();
        }
    }

    function inicializarListeners() {
        document.addEventListener('click', onClickDocumento, true);
        document.addEventListener('focusin', onFocusContenteditable, true);
        document.addEventListener('blur', onBlurContenteditable, true);
        window.addEventListener('scroll', programarNotificacionRectCambio, true);
        window.addEventListener('resize', programarNotificacionRectCambio);

        document.addEventListener('dragover', onDragOverIframe, true);
        document.addEventListener('drop', onDropIframe, true);
        document.addEventListener('dragleave', onDragLeaveIframe, true);

        console.log('[vbp-iframe-bridge] listeners inicializados, postId=' + postIdDelDocumento);
    }

    // API publica para el parent
    window.vbpIframeCanvas = {
        postId: postIdDelDocumento,
        selectElement: aplicarSeleccionVisual,
        clearSelection: limpiarSeleccionVisual,
        getElementById: function (idElemento) {
            var raizLanding = obtenerRaizElementos();
            if (!raizLanding || !idElemento) return null;
            return raizLanding.querySelector('.vbp-iframe-element[data-element-id="' + idElemento + '"]');
        },
        replaceElementHtml: function (idElemento, htmlNuevo) {
            var wrapperExistente = this.getElementById(idElemento);
            if (!wrapperExistente) return false;
            var estabaSeleccionado = wrapperExistente.getAttribute('data-vbp-selected') === 'true';

            var contenedorTemporal = document.createElement('div');
            contenedorTemporal.innerHTML = htmlNuevo;
            var wrapperNuevo = contenedorTemporal.firstElementChild;
            if (!wrapperNuevo) return false;

            if (estabaSeleccionado) {
                wrapperNuevo.setAttribute('data-vbp-selected', 'true');
            }
            wrapperExistente.replaceWith(wrapperNuevo);

            // Tras reemplazar, el rect puede haber cambiado: notificar para reposicionar handles
            if (estabaSeleccionado) programarNotificacionRectCambio();
            return true;
        },
        refresh: function () {
            window.location.reload();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarListeners);
    } else {
        inicializarListeners();
    }
})();

/**
 * Radio Admin JavaScript
 * Flavor Chat IA - Panel de Administración de Radio
 */

(function($) {
    'use strict';

    const RadioAdmin = {
        ajaxurl: typeof flavorRadioAdmin !== 'undefined' ? flavorRadioAdmin.ajaxurl : ajaxurl,
        nonce: typeof flavorRadioAdmin !== 'undefined' ? flavorRadioAdmin.nonce : '',
        refreshInterval: null,
    };

    $(document).ready(function() {
        RadioAdmin.init();
    });

    /**
     * Inicialización
     */
    RadioAdmin.init = function() {
        this.bindEvents();
        this.loadStats();
        this.loadLiveStatus();
        this.startRefresh();
    };

    /**
     * Bind de eventos
     */
    RadioAdmin.bindEvents = function() {
        const self = this;

        // Tabs de navegación
        $(document).on('click', '.radio-admin-tab', function() {
            const tab = $(this).data('tab');
            $('.radio-admin-tab').removeClass('active');
            $(this).addClass('active');
            $('.radio-admin-panel').removeClass('active');
            $(`#radio-panel-${tab}`).addClass('active');

            // Cargar contenido según tab
            switch (tab) {
                case 'programacion':
                    self.loadProgramacion();
                    break;
                case 'dedicatorias':
                    self.loadDedicatorias();
                    break;
                case 'propuestas':
                    self.loadPropuestas();
                    break;
                case 'podcasts':
                    self.loadPodcasts();
                    break;
                case 'oyentes':
                    self.loadOyentes();
                    break;
                case 'chat':
                    self.loadChatMessages();
                    break;
            }
        });

        // Acciones de dedicatorias
        $(document).on('click', '.radio-btn-aprobar-dedicatoria', function() {
            const id = $(this).data('id');
            self.moderarDedicatoria(id, 'aprobar');
        });

        $(document).on('click', '.radio-btn-rechazar-dedicatoria', function() {
            const id = $(this).data('id');
            if (confirm('¿Rechazar esta dedicatoria?')) {
                self.moderarDedicatoria(id, 'rechazar');
            }
        });

        $(document).on('click', '.radio-btn-leer-dedicatoria', function() {
            const id = $(this).data('id');
            self.marcarDedicatoriaLeida(id);
        });

        // Acciones de propuestas
        $(document).on('click', '.radio-btn-aprobar-propuesta', function() {
            const id = $(this).data('id');
            self.moderarPropuesta(id, 'aprobar');
        });

        $(document).on('click', '.radio-btn-rechazar-propuesta', function() {
            const id = $(this).data('id');
            if (confirm('¿Rechazar esta propuesta de programa?')) {
                self.moderarPropuesta(id, 'rechazar');
            }
        });

        // Acciones de chat
        $(document).on('click', '.radio-btn-eliminar-mensaje', function() {
            const id = $(this).data('id');
            if (confirm('¿Eliminar este mensaje del chat?')) {
                self.eliminarMensajeChat(id);
            }
        });

        // Acciones de podcasts
        $(document).on('click', '.radio-btn-eliminar-podcast', function() {
            const id = $(this).data('id');
            if (confirm('¿Eliminar este podcast permanentemente?')) {
                self.eliminarPodcast(id);
            }
        });

        // Modal
        $(document).on('click', '.radio-modal-close, .radio-modal-overlay', function(e) {
            if (e.target === this) {
                self.closeModal();
            }
        });

        // Crear programa
        $(document).on('click', '.radio-btn-nuevo-programa', function() {
            self.openProgramaModal();
        });

        // Guardar programa
        $(document).on('submit', '#radio-programa-form', function(e) {
            e.preventDefault();
            self.guardarPrograma($(this));
        });

        // Crear podcast
        $(document).on('click', '.radio-btn-nuevo-podcast', function() {
            self.openPodcastModal();
        });

        // Toggle en vivo
        $(document).on('click', '.radio-btn-toggle-live', function() {
            self.toggleLive();
        });
    };

    /**
     * Auto-refresh para datos en tiempo real
     */
    RadioAdmin.startRefresh = function() {
        const self = this;

        // Refresh cada 30 segundos
        this.refreshInterval = setInterval(function() {
            self.loadLiveStatus();

            // Si estamos en tab de oyentes o chat, refrescar
            if ($('#radio-panel-oyentes').hasClass('active')) {
                self.loadOyentes();
            }
            if ($('#radio-panel-chat').hasClass('active')) {
                self.loadChatMessages();
            }
        }, 30000);
    };

    /**
     * Cargar estadísticas
     */
    RadioAdmin.loadStats = function() {
        const self = this;
        const container = $('#radio-stats-container');

        if (!container.length) return;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_admin_stats',
                nonce: this.nonce,
            },
            success: function(response) {
                if (response.success) {
                    self.renderStats(response.data);
                }
            }
        });
    };

    /**
     * Renderizar estadísticas
     */
    RadioAdmin.renderStats = function(data) {
        $('#radio-stat-programas').text(data.total_programas || 0);
        $('#radio-stat-dedicatorias').text(data.dedicatorias_pendientes || 0);
        $('#radio-stat-propuestas').text(data.propuestas_pendientes || 0);
        $('#radio-stat-podcasts').text(data.total_podcasts || 0);
        $('#radio-stat-oyentes-hoy').text(data.oyentes_hoy || 0);
        $('#radio-stat-horas-emision').text(data.horas_emision || 0);
    };

    /**
     * Cargar estado en vivo
     */
    RadioAdmin.loadLiveStatus = function() {
        const self = this;
        const container = $('#radio-live-status');

        if (!container.length) return;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_en_vivo',
            },
            success: function(response) {
                if (response.success) {
                    self.renderLiveStatus(container, response.data);
                }
            }
        });
    };

    /**
     * Renderizar estado en vivo
     */
    RadioAdmin.renderLiveStatus = function(container, data) {
        const indicator = container.find('.radio-live-indicator');
        const title = container.find('.radio-live-title');
        const program = container.find('.radio-live-program');
        const listeners = container.find('.radio-live-listeners span');

        if (data.en_vivo) {
            indicator.addClass('on-air');
            title.text('EN VIVO');
            program.text(data.programa_actual ? data.programa_actual.nombre : 'Emisión en directo');
        } else {
            indicator.removeClass('on-air');
            title.text('FUERA DE ANTENA');
            program.text('Sin emisión activa');
        }

        listeners.text(data.oyentes_actuales + ' oyentes');
    };

    /**
     * Toggle estado en vivo
     */
    RadioAdmin.toggleLive = function() {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_toggle_live',
                nonce: this.nonce,
            },
            success: function(response) {
                if (response.success) {
                    self.loadLiveStatus();
                } else {
                    alert(response.data || 'Error al cambiar estado');
                }
            }
        });
    };

    /**
     * Cargar programación
     */
    RadioAdmin.loadProgramacion = function() {
        const self = this;
        const container = $('#radio-programacion-container');

        if (!container.length) return;

        container.html('<div class="radio-admin-loading"><span class="spinner is-active"></span><p>Cargando programación...</p></div>');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_programacion',
            },
            success: function(response) {
                if (response.success && response.data.programacion) {
                    self.renderProgramacion(container, response.data.programacion);
                } else {
                    container.html('<div class="radio-admin-empty"><span class="dashicons dashicons-calendar-alt"></span><h3>Sin programación</h3><p>No hay programas configurados</p></div>');
                }
            }
        });
    };

    /**
     * Renderizar grid de programación
     */
    RadioAdmin.renderProgramacion = function(container, programacion) {
        const dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        const horas = [];

        for (let h = 6; h <= 23; h++) {
            horas.push(h.toString().padStart(2, '0') + ':00');
        }

        let html = '<div class="radio-programacion-grid">';

        // Header
        html += '<div class="radio-prog-header"></div>';
        dias.forEach(dia => {
            html += `<div class="radio-prog-header">${dia}</div>`;
        });

        // Filas por hora
        horas.forEach(hora => {
            html += `<div class="radio-prog-hora">${hora}</div>`;

            for (let d = 1; d <= 7; d++) {
                const prog = programacion.find(p =>
                    p.dia_semana == d &&
                    p.hora_inicio <= hora &&
                    p.hora_fin > hora
                );

                if (prog) {
                    html += `
                        <div class="radio-prog-slot has-program" data-id="${prog.id}">
                            <div class="radio-prog-programa">${prog.programa_nombre}</div>
                            <div class="radio-prog-locutor">${prog.locutor || ''}</div>
                        </div>
                    `;
                } else {
                    html += `<div class="radio-prog-slot" data-dia="${d}" data-hora="${hora}"></div>`;
                }
            }
        });

        html += '</div>';
        container.html(html);
    };

    /**
     * Cargar dedicatorias pendientes
     */
    RadioAdmin.loadDedicatorias = function() {
        const self = this;
        const container = $('#radio-dedicatorias-container');

        if (!container.length) return;

        container.html('<div class="radio-admin-loading"><span class="spinner is-active"></span><p>Cargando dedicatorias...</p></div>');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_dedicatorias',
                estado: 'pendiente',
                limite: 50,
            },
            success: function(response) {
                if (response.success && response.data.dedicatorias && response.data.dedicatorias.length) {
                    self.renderDedicatorias(container, response.data.dedicatorias);
                } else {
                    container.html('<div class="radio-admin-empty"><span class="dashicons dashicons-heart"></span><h3>Sin dedicatorias pendientes</h3><p>Todas las dedicatorias han sido procesadas</p></div>');
                }
            }
        });
    };

    /**
     * Renderizar lista de dedicatorias
     */
    RadioAdmin.renderDedicatorias = function(container, dedicatorias) {
        let html = '<div class="radio-dedicatorias-list">';

        dedicatorias.forEach(function(ded) {
            html += `
                <div class="radio-dedicatoria-card ${ded.estado}" data-id="${ded.id}">
                    <div class="radio-dedicatoria-avatar">
                        ${ded.autor_avatar ? `<img src="${ded.autor_avatar}" alt="">` : '<span class="dashicons dashicons-admin-users"></span>'}
                    </div>
                    <div class="radio-dedicatoria-content">
                        <div class="radio-dedicatoria-header">
                            <div>
                                <span class="radio-dedicatoria-de">${ded.de_nombre || 'Anónimo'}</span>
                                <span class="radio-dedicatoria-para">para <strong>${ded.para_nombre}</strong></span>
                            </div>
                            <span class="radio-dedicatoria-fecha">${ded.fecha_humana}</span>
                        </div>
                        <div class="radio-dedicatoria-mensaje">"${ded.mensaje}"</div>
                        ${ded.cancion_solicitada ? `
                            <div class="radio-dedicatoria-cancion">
                                <span class="dashicons dashicons-format-audio"></span>
                                ${ded.cancion_solicitada}
                            </div>
                        ` : ''}
                        <div class="radio-dedicatoria-actions">
                            <button class="button button-primary button-small radio-btn-aprobar-dedicatoria" data-id="${ded.id}">Aprobar</button>
                            <button class="button button-small radio-btn-leer-dedicatoria" data-id="${ded.id}">Marcar leída</button>
                            <button class="button button-small radio-btn-rechazar-dedicatoria" data-id="${ded.id}">Rechazar</button>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.html(html);
    };

    /**
     * Moderar dedicatoria
     */
    RadioAdmin.moderarDedicatoria = function(id, accion) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_admin_moderar_dedicatoria',
                nonce: this.nonce,
                dedicatoria_id: id,
                accion: accion,
            },
            success: function(response) {
                if (response.success) {
                    $(`.radio-dedicatoria-card[data-id="${id}"]`).fadeOut(300, function() {
                        $(this).remove();
                        // Actualizar contador
                        const count = parseInt($('#radio-stat-dedicatorias').text()) - 1;
                        $('#radio-stat-dedicatorias').text(Math.max(0, count));
                    });
                } else {
                    alert(response.data || 'Error');
                }
            }
        });
    };

    /**
     * Marcar dedicatoria como leída
     */
    RadioAdmin.marcarDedicatoriaLeida = function(id) {
        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_admin_dedicatoria_leida',
                nonce: this.nonce,
                dedicatoria_id: id,
            },
            success: function(response) {
                if (response.success) {
                    const card = $(`.radio-dedicatoria-card[data-id="${id}"]`);
                    card.removeClass('pendiente aprobada').addClass('leida');
                    card.find('.radio-dedicatoria-actions').html('<span class="radio-badge leida">Leída al aire</span>');
                }
            }
        });
    };

    /**
     * Cargar propuestas de programas
     */
    RadioAdmin.loadPropuestas = function() {
        const self = this;
        const container = $('#radio-propuestas-container');

        if (!container.length) return;

        container.html('<div class="radio-admin-loading"><span class="spinner is-active"></span><p>Cargando propuestas...</p></div>');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_propuestas',
                estado: 'pendiente',
            },
            success: function(response) {
                if (response.success && response.data.propuestas && response.data.propuestas.length) {
                    self.renderPropuestas(container, response.data.propuestas);
                } else {
                    container.html('<div class="radio-admin-empty"><span class="dashicons dashicons-lightbulb"></span><h3>Sin propuestas pendientes</h3><p>No hay nuevas propuestas de programas</p></div>');
                }
            }
        });
    };

    /**
     * Renderizar propuestas
     */
    RadioAdmin.renderPropuestas = function(container, propuestas) {
        let html = '<div class="radio-propuestas-list">';

        propuestas.forEach(function(prop) {
            html += `
                <div class="radio-propuesta-card ${prop.estado}" data-id="${prop.id}">
                    <div class="radio-propuesta-header">
                        <h4 class="radio-propuesta-titulo">${prop.nombre_programa}</h4>
                        <span class="radio-propuesta-estado ${prop.estado}">${prop.estado}</span>
                    </div>
                    <div class="radio-propuesta-meta">
                        <span><span class="dashicons dashicons-admin-users"></span> ${prop.autor_nombre}</span>
                        <span><span class="dashicons dashicons-category"></span> ${prop.tipo || 'General'}</span>
                        <span><span class="dashicons dashicons-clock"></span> ${prop.duracion_propuesta || '1 hora'}</span>
                        <span><span class="dashicons dashicons-calendar-alt"></span> ${prop.fecha_humana}</span>
                    </div>
                    <div class="radio-propuesta-descripcion">${prop.descripcion}</div>
                    <div class="radio-propuesta-actions">
                        <button class="button button-primary radio-btn-aprobar-propuesta" data-id="${prop.id}">Aprobar y crear programa</button>
                        <button class="button radio-btn-rechazar-propuesta" data-id="${prop.id}">Rechazar</button>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.html(html);
    };

    /**
     * Moderar propuesta
     */
    RadioAdmin.moderarPropuesta = function(id, accion) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_admin_moderar_propuesta',
                nonce: this.nonce,
                propuesta_id: id,
                accion: accion,
            },
            success: function(response) {
                if (response.success) {
                    $(`.radio-propuesta-card[data-id="${id}"]`).fadeOut(300, function() {
                        $(this).remove();
                        const count = parseInt($('#radio-stat-propuestas').text()) - 1;
                        $('#radio-stat-propuestas').text(Math.max(0, count));
                    });
                } else {
                    alert(response.data || 'Error');
                }
            }
        });
    };

    /**
     * Cargar podcasts
     */
    RadioAdmin.loadPodcasts = function() {
        const self = this;
        const container = $('#radio-podcasts-container');

        if (!container.length) return;

        container.html('<div class="radio-admin-loading"><span class="spinner is-active"></span><p>Cargando podcasts...</p></div>');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_podcasts',
                limite: 20,
            },
            success: function(response) {
                if (response.success && response.data.podcasts && response.data.podcasts.length) {
                    self.renderPodcasts(container, response.data.podcasts);
                } else {
                    container.html('<div class="radio-admin-empty"><span class="dashicons dashicons-microphone"></span><h3>Sin podcasts</h3><p>No hay podcasts publicados</p></div>');
                }
            }
        });
    };

    /**
     * Renderizar podcasts
     */
    RadioAdmin.renderPodcasts = function(container, podcasts) {
        let html = '<div class="radio-podcasts-grid">';

        podcasts.forEach(function(podcast) {
            html += `
                <div class="radio-podcast-card" data-id="${podcast.id}">
                    <div class="radio-podcast-imagen">
                        ${podcast.imagen ? `<img src="${podcast.imagen}" alt="">` : ''}
                        <span class="radio-podcast-duracion">${podcast.duracion_formato || '--:--'}</span>
                    </div>
                    <div class="radio-podcast-info">
                        <div class="radio-podcast-titulo">${podcast.titulo}</div>
                        <div class="radio-podcast-programa">${podcast.programa_nombre || 'Sin programa'}</div>
                        <div class="radio-podcast-stats">
                            <span><span class="dashicons dashicons-visibility"></span> ${podcast.reproducciones || 0}</span>
                            <span><span class="dashicons dashicons-download"></span> ${podcast.descargas || 0}</span>
                        </div>
                    </div>
                    <div class="radio-podcast-actions">
                        <a href="${podcast.archivo_url}" class="button button-small" target="_blank">
                            <span class="dashicons dashicons-download"></span>
                        </a>
                        <button class="button button-small radio-btn-eliminar-podcast" data-id="${podcast.id}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.html(html);
    };

    /**
     * Eliminar podcast
     */
    RadioAdmin.eliminarPodcast = function(id) {
        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_admin_eliminar_podcast',
                nonce: this.nonce,
                podcast_id: id,
            },
            success: function(response) {
                if (response.success) {
                    $(`.radio-podcast-card[data-id="${id}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || 'Error al eliminar');
                }
            }
        });
    };

    /**
     * Cargar oyentes actuales
     */
    RadioAdmin.loadOyentes = function() {
        const self = this;
        const container = $('#radio-oyentes-container');

        if (!container.length) return;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_en_vivo',
            },
            success: function(response) {
                if (response.success) {
                    self.renderOyentes(container, response.data);
                }
            }
        });
    };

    /**
     * Renderizar oyentes
     */
    RadioAdmin.renderOyentes = function(container, data) {
        let html = `
            <div class="radio-oyentes-panel">
                <div class="radio-oyentes-header">
                    <h3>Oyentes en tiempo real</h3>
                    <span class="radio-oyentes-count">${data.oyentes_actuales || 0}</span>
                </div>
        `;

        if (data.oyentes_lista && data.oyentes_lista.length) {
            html += '<div class="radio-oyentes-lista">';
            data.oyentes_lista.forEach(function(oyente) {
                if (oyente.usuario_id) {
                    html += `
                        <div class="radio-oyente-badge">
                            ${oyente.avatar ? `<img src="${oyente.avatar}" alt="">` : ''}
                            <span>${oyente.nombre}</span>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="radio-oyente-badge anonimo">
                            <span class="dashicons dashicons-admin-users"></span>
                            <span>Anónimo</span>
                        </div>
                    `;
                }
            });
            html += '</div>';
        } else {
            html += '<p class="description">No hay oyentes conectados</p>';
        }

        html += '</div>';
        container.html(html);
    };

    /**
     * Cargar mensajes del chat
     */
    RadioAdmin.loadChatMessages = function() {
        const self = this;
        const container = $('#radio-chat-container');

        if (!container.length) return;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_chat',
                limite: 100,
            },
            success: function(response) {
                if (response.success && response.data.mensajes && response.data.mensajes.length) {
                    self.renderChatMessages(container, response.data.mensajes);
                } else {
                    container.html('<div class="radio-admin-empty"><span class="dashicons dashicons-format-chat"></span><h3>Chat vacío</h3><p>No hay mensajes en el chat</p></div>');
                }
            }
        });
    };

    /**
     * Renderizar mensajes del chat
     */
    RadioAdmin.renderChatMessages = function(container, mensajes) {
        let html = '<div class="radio-chat-moderacion">';

        mensajes.forEach(function(msg) {
            const estado = msg.eliminado ? 'eliminado' : (msg.reportado ? 'reportado' : '');
            html += `
                <div class="radio-chat-mensaje ${estado}" data-id="${msg.id}">
                    <img src="${msg.autor_avatar || ''}" class="radio-chat-avatar" alt="">
                    <div class="radio-chat-content">
                        <div class="radio-chat-header">
                            <span class="radio-chat-autor">${msg.autor_nombre}</span>
                            <span class="radio-chat-hora">${msg.hora}</span>
                        </div>
                        <div class="radio-chat-texto">${msg.mensaje}</div>
                    </div>
                    <div class="radio-chat-actions">
                        ${!msg.eliminado ? `<button class="button button-small radio-btn-eliminar-mensaje" data-id="${msg.id}">Eliminar</button>` : '<span class="radio-badge inactivo">Eliminado</span>'}
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.html(html);
    };

    /**
     * Eliminar mensaje del chat
     */
    RadioAdmin.eliminarMensajeChat = function(id) {
        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_admin_eliminar_chat',
                nonce: this.nonce,
                mensaje_id: id,
            },
            success: function(response) {
                if (response.success) {
                    const msg = $(`.radio-chat-mensaje[data-id="${id}"]`);
                    msg.addClass('eliminado');
                    msg.find('.radio-chat-actions').html('<span class="radio-badge inactivo">Eliminado</span>');
                } else {
                    alert(response.data || 'Error');
                }
            }
        });
    };

    /**
     * Abrir modal de programa
     */
    RadioAdmin.openProgramaModal = function(programaId) {
        // Implementar modal de edición/creación de programa
        console.log('Abrir modal programa:', programaId);
    };

    /**
     * Guardar programa
     */
    RadioAdmin.guardarPrograma = function(form) {
        const self = this;
        const data = form.serialize();

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: data + '&action=flavor_radio_admin_guardar_programa&nonce=' + this.nonce,
            success: function(response) {
                if (response.success) {
                    self.closeModal();
                    self.loadProgramacion();
                } else {
                    alert(response.data || 'Error al guardar');
                }
            }
        });
    };

    /**
     * Abrir modal de podcast
     */
    RadioAdmin.openPodcastModal = function() {
        // Implementar modal de subida de podcast
        console.log('Abrir modal podcast');
    };

    /**
     * Cerrar modal
     */
    RadioAdmin.closeModal = function() {
        $('.radio-modal-overlay').removeClass('active');
    };

    // Exponer globalmente
    window.RadioAdmin = RadioAdmin;

})(jQuery);

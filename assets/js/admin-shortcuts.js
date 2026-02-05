/**
 * Chat IA Admin Shortcuts - JavaScript
 *
 * Sistema de atajos para ejecutar acciones directamente sin consumir tokens de IA
 *
 * @since 1.8.0
 */

(function($) {
    'use strict';

    const config = window.chatIAAdminAssistant || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        nonce: '',
        strings: {}
    };

    /**
     * Sistema de Shortcuts
     */
    const AdminShortcuts = {
        flatpickrInstance: null,
        flatpickrRangeInstance: null,

        /**
         * Inicializacion
         */
        init() {
            this.bindEvents();
            this.initDatepickers();
            this.loadTicketOptions();
            this.loadStateOptions();
        },

        /**
         * Vincula eventos
         */
        bindEvents() {
            // Botones de atajo simples
            $(document).on('click', '.shortcut-btn', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const shortcutId = $btn.data('shortcut');

                if ($btn.hasClass('needs-params')) {
                    this.showShortcutModal(shortcutId, $btn.data('fields'));
                } else {
                    this.executeShortcut(shortcutId, this.collectParams($btn));
                }
            });

            // Botones de accion en mensajes
            $(document).on('click', '.message-action-btn', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);

                if ($btn.data('url')) {
                    window.open($btn.data('url'), '_blank');
                } else if ($btn.data('shortcut')) {
                    const params = $btn.data('params') || {};
                    this.executeShortcut($btn.data('shortcut'), params);
                }
            });

            // Modal shortcuts
            $(document).on('submit', '#shortcut-modal-form', (e) => {
                e.preventDefault();
                this.submitModalForm();
            });

            $(document).on('click', '#shortcut-modal-close, #shortcut-modal-overlay', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeModal();
                }
            });

            // Tabs de grupos de shortcuts
            $(document).on('click', '.shortcuts-tab', (e) => {
                e.preventDefault();
                const $tab = $(e.currentTarget);
                const group = $tab.data('group');

                $('.shortcuts-tab').removeClass('active');
                $tab.addClass('active');

                $('.shortcut-group').removeClass('active');
                $(`.shortcut-group[data-group="${group}"]`).addClass('active');
            });

            // Colapsar/expandir panel de atajos
            $(document).on('click', '.shortcuts-panel-toggle', (e) => {
                e.preventDefault();
                $('.shortcuts-panel').toggleClass('collapsed');
                const isCollapsed = $('.shortcuts-panel').hasClass('collapsed');
                localStorage.setItem('shortcutsPanelCollapsed', isCollapsed);
            });

            // Restaurar estado del panel
            if (localStorage.getItem('shortcutsPanelCollapsed') === 'true') {
                $('.shortcuts-panel').addClass('collapsed');
            }
        },

        /**
         * Inicializa datepickers con Flatpickr (si esta disponible) o fallback a input[type=date]
         */
        initDatepickers() {
            // Si Flatpickr esta disponible
            if (typeof flatpickr !== 'undefined') {
                // Datepicker simple
                this.flatpickrInstance = flatpickr('.shortcut-datepicker', {
                    dateFormat: 'Y-m-d',
                    locale: 'es',
                    defaultDate: new Date(),
                    onChange: (selectedDates, dateStr) => {
                        // Preview del estado actual si hay
                        this.previewDateState(dateStr);
                    }
                });

                // Datepicker de rango
                this.flatpickrRangeInstance = flatpickr('.shortcut-daterange', {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    locale: 'es',
                    defaultDate: [new Date(), new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)]
                });
            }
        },

        /**
         * Preview del estado de una fecha
         */
        previewDateState(fecha) {
            const $preview = $('.date-state-preview');
            if (!$preview.length) return;

            $.post(config.ajaxUrl, {
                action: 'chat_ia_execute_shortcut',
                nonce: config.nonce,
                shortcut: 'get_date_state',
                params: JSON.stringify({ fecha: fecha })
            }, (response) => {
                if (response.success && response.data.estado) {
                    $preview.html(`Estado actual: <strong>${response.data.estado}</strong>`).show();
                } else {
                    $preview.html('Sin estado configurado').show();
                }
            });
        },

        /**
         * Carga opciones de tickets en selectores
         */
        loadTicketOptions() {
            const $selects = $('.shortcut-ticket-select');
            if (!$selects.length) return;

            // Usar datos localizados desde PHP (chatIATicketTypes)
            const tipos = window.chatIATicketTypes || [];
            tipos.forEach(tipo => {
                $selects.append(`<option value="${tipo.slug}">${tipo.nombre} (${tipo.precio}€)</option>`);
            });
        },

        /**
         * Carga opciones de estados en selectores
         */
        loadStateOptions() {
            const $selects = $('.shortcut-state-select');
            if (!$selects.length) return;

            // Usar estados predefinidos si existen
            const estados = window.chatIAEstados || [
                { slug: 'abierto', nombre: 'Abierto' },
                { slug: 'cerrado', nombre: 'Cerrado' }
            ];

            estados.forEach(estado => {
                $selects.append(`<option value="${estado.slug}">${estado.nombre}</option>`);
            });
        },

        /**
         * Recoge parametros del contexto del boton
         */
        collectParams($btn) {
            const params = {};
            const $group = $btn.closest('.shortcut-group');

            // Fecha simple
            const $datepicker = $group.find('.shortcut-datepicker');
            if ($datepicker.length && $datepicker.val()) {
                params.fecha = $datepicker.val();
            }

            // Rango de fechas
            const $daterange = $group.find('.shortcut-daterange');
            if ($daterange.length && $daterange.val()) {
                const range = $daterange.val().split(' to ');
                if (range.length === 2) {
                    params.fecha_inicio = range[0];
                    params.fecha_fin = range[1];
                }
            }

            // Estado
            const $state = $group.find('.shortcut-state-select');
            if ($state.length && $state.val()) {
                params.estado = $state.val();
            }

            // Ticket
            const $ticket = $group.find('.shortcut-ticket-select');
            if ($ticket.length && $ticket.val()) {
                params.ticket_slug = $ticket.val();
                params.tipo_ticket = $ticket.val();
            }

            // Inputs adicionales
            $group.find('.shortcut-input').each(function() {
                const name = $(this).attr('name');
                const val = $(this).val();
                if (name && val) {
                    params[name] = val;
                }
            });

            // Params del boton
            const btnParams = $btn.data('params');
            if (btnParams) {
                Object.assign(params, typeof btnParams === 'string' ? JSON.parse(btnParams) : btnParams);
            }

            return params;
        },

        /**
         * Ejecuta un shortcut
         */
        executeShortcut(shortcutId, params = {}) {
            const $btn = $(`.shortcut-btn[data-shortcut="${shortcutId}"]`);

            // Estado de carga
            $btn.addClass('loading').prop('disabled', true);
            const originalText = $btn.html();
            $btn.html('<span class="dashicons dashicons-update spinning"></span>');

            $.ajax({
                url: config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'chat_ia_execute_shortcut',
                    nonce: config.nonce,
                    shortcut: shortcutId,
                    params: JSON.stringify(params)
                },
                success: (response) => {
                    $btn.removeClass('loading').prop('disabled', false).html(originalText);

                    if (response.success) {
                        this.showShortcutResult(response.data);
                    } else {
                        this.showShortcutError(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('[AdminShortcuts] Error:', error);
                    $btn.removeClass('loading').prop('disabled', false).html(originalText);
                    this.showShortcutError({ error: 'Error de conexion: ' + error });
                }
            });
        },

        /**
         * Muestra resultado del shortcut en el chat
         */
        showShortcutResult(result) {
            const message = result.message || JSON.stringify(result.data, null, 2);
            const actions = result.actions || [];
            this.addSystemMessage(message, actions, 'success');
        },

        /**
         * Muestra error del shortcut
         */
        showShortcutError(result) {
            const message = result.error || 'Error desconocido';

            // Error de acceso denegado
            if (result.access_denied) {
                this.addSystemMessage(`🔒 **Acceso restringido:** ${message}`, [], 'warning');
                return;
            }

            this.addSystemMessage(`**Error:** ${message}`, [], 'error');

            // Si faltan campos, mostrar modal
            if (result.missing_fields && result.shortcut) {
                this.showShortcutModal(result.shortcut.tool || '', result.missing_fields);
            }
        },

        /**
         * Agrega mensaje del sistema al chat
         */
        addSystemMessage(content, actions = [], type = 'info') {
            const $messages = $('#assistant-messages');
            if (!$messages.length) {
                // Fallback si el contenedor del chat no existe
                alert('Resultado del atajo:\n\n' + content.replace(/<[^>]*>/g, '').replace(/\*\*/g, ''));
                return;
            }

            const parsedContent = this.parseMarkdown(content);
            const actionsHtml = this.renderActions(actions);
            const typeClass = `system-message-${type}`;

            const html = `
                <div class="assistant-message assistant-message-bot system-message ${typeClass}">
                    <div class="message-avatar">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                    <div class="message-content">
                        ${parsedContent}
                        ${actionsHtml}
                    </div>
                </div>
            `;

            $messages.append(html);
            this.scrollToBottom();
        },

        /**
         * Renderiza botones de accion
         */
        renderActions(actions) {
            if (!actions || !actions.length) return '';

            const buttons = actions.map(action => {
                const dataAttrs = [];
                if (action.url) dataAttrs.push(`data-url="${action.url}"`);
                if (action.shortcut) dataAttrs.push(`data-shortcut="${action.shortcut}"`);
                if (action.params) dataAttrs.push(`data-params='${JSON.stringify(action.params)}'`);

                return `<button class="message-action-btn" ${dataAttrs.join(' ')}>${action.label}</button>`;
            }).join('');

            return `<div class="message-actions">${buttons}</div>`;
        },

        /**
         * Muestra modal para completar parametros
         */
        showShortcutModal(shortcutId, fields) {
            // Obtener configuracion del formulario
            $.post(config.ajaxUrl, {
                action: 'chat_ia_get_shortcut_form',
                nonce: config.nonce,
                shortcut: shortcutId
            }, (response) => {
                if (!response.success) return;

                const shortcut = response.data.shortcut;
                const formFields = response.data.fields;

                this.buildAndShowModal(shortcutId, shortcut, formFields);
            });
        },

        /**
         * Construye y muestra el modal
         */
        buildAndShowModal(shortcutId, shortcut, fields) {
            let fieldsHtml = '';

            Object.entries(fields).forEach(([name, config]) => {
                fieldsHtml += this.renderFormField(name, config);
            });

            const modalHtml = `
                <div id="shortcut-modal-overlay" class="shortcut-modal-overlay">
                    <div class="shortcut-modal">
                        <div class="shortcut-modal-header">
                            <h3>${shortcut.label || shortcut.description}</h3>
                            <button id="shortcut-modal-close" class="shortcut-modal-close">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <form id="shortcut-modal-form" data-shortcut="${shortcutId}">
                            <div class="shortcut-modal-body">
                                ${fieldsHtml}
                            </div>
                            <div class="shortcut-modal-footer">
                                <button type="button" class="button" id="shortcut-modal-cancel">Cancelar</button>
                                <button type="submit" class="button button-primary">Ejecutar</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            // Remover modal anterior si existe
            $('#shortcut-modal-overlay').remove();

            $('body').append(modalHtml);

            // Inicializar datepickers en el modal
            if (typeof flatpickr !== 'undefined') {
                flatpickr('#shortcut-modal-overlay .modal-datepicker', {
                    dateFormat: 'Y-m-d',
                    locale: 'es'
                });
            }

            // Evento cancelar
            $('#shortcut-modal-cancel').on('click', () => this.closeModal());
        },

        /**
         * Renderiza un campo del formulario
         */
        renderFormField(name, config) {
            const id = `modal-field-${name}`;
            const label = config.label || name;
            const type = config.type || 'text';

            let inputHtml = '';

            switch (type) {
                case 'select':
                    const options = Object.entries(config.options || {})
                        .map(([val, label]) => `<option value="${val}">${label}</option>`)
                        .join('');
                    inputHtml = `<select id="${id}" name="${name}" class="modal-input" required>${options}</select>`;
                    break;

                case 'textarea':
                    inputHtml = `<textarea id="${id}" name="${name}" class="modal-input" rows="${config.rows || 3}" placeholder="${config.placeholder || ''}" required></textarea>`;
                    break;

                case 'date':
                    inputHtml = `<input type="text" id="${id}" name="${name}" class="modal-input modal-datepicker" value="${config.default || ''}" required>`;
                    break;

                case 'number':
                    inputHtml = `<input type="number" id="${id}" name="${name}" class="modal-input" value="${config.default || ''}" min="${config.min || ''}" max="${config.max || ''}" step="${config.step || '1'}" required>`;
                    break;

                default:
                    inputHtml = `<input type="${type}" id="${id}" name="${name}" class="modal-input" value="${config.default || ''}" placeholder="${config.placeholder || ''}" required>`;
            }

            return `
                <div class="modal-field">
                    <label for="${id}">${label}</label>
                    ${inputHtml}
                </div>
            `;
        },

        /**
         * Envio del formulario modal
         */
        submitModalForm() {
            const $form = $('#shortcut-modal-form');
            const shortcutId = $form.data('shortcut');
            const params = {};

            $form.find('.modal-input').each(function() {
                const name = $(this).attr('name');
                const val = $(this).val();
                if (name && val) {
                    params[name] = val;
                }
            });

            this.closeModal();
            this.executeShortcut(shortcutId, params);
        },

        /**
         * Cierra el modal
         */
        closeModal() {
            $('#shortcut-modal-overlay').fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Scroll al final del chat
         */
        scrollToBottom() {
            const $messages = $('#assistant-messages');
            if ($messages.length) {
                $messages.scrollTop($messages[0].scrollHeight);
            }
        },

        /**
         * Parsea markdown basico (reutiliza del admin-assistant.js)
         */
        parseMarkdown(text) {
            if (!text) return '';

            // Escapar HTML
            text = this.escapeHtml(text);

            // Bloques de codigo
            text = text.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');

            // Codigo inline
            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Negrita
            text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

            // Cursiva
            text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');

            // Listas desordenadas
            text = text.replace(/^[\-\*] (.+)$/gm, '<li>$1</li>');

            // Tablas markdown
            text = this.parseMarkdownTable(text);

            // Headers
            text = text.replace(/^### (.+)$/gm, '<h4>$1</h4>');
            text = text.replace(/^## (.+)$/gm, '<h3>$1</h3>');
            text = text.replace(/^# (.+)$/gm, '<h2>$1</h2>');

            // Parrafos
            text = text.replace(/\n\n/g, '</p><p>');
            text = '<p>' + text + '</p>';

            // Limpiar parrafos vacios
            text = text.replace(/<p><\/p>/g, '');
            text = text.replace(/<p>(<[hulo])/g, '$1');
            text = text.replace(/(<\/[hulo][^>]*>)<\/p>/g, '$1');

            return text;
        },

        /**
         * Parsea tablas markdown
         */
        parseMarkdownTable(text) {
            const tableRegex = /\|(.+)\|\n\|[-:\s|]+\|\n((?:\|.+\|\n?)+)/g;

            return text.replace(tableRegex, function(match, headerRow, bodyRows) {
                const headers = headerRow.split('|').filter(h => h.trim());
                const rows = bodyRows.trim().split('\n');

                let tableHtml = '<table><thead><tr>';
                headers.forEach(h => {
                    tableHtml += '<th>' + h.trim() + '</th>';
                });
                tableHtml += '</tr></thead><tbody>';

                rows.forEach(row => {
                    const cells = row.split('|').filter(c => c.trim() !== '');
                    if (cells.length > 0) {
                        tableHtml += '<tr>';
                        cells.forEach(c => {
                            tableHtml += '<td>' + c.trim() + '</td>';
                        });
                        tableHtml += '</tr>';
                    }
                });

                tableHtml += '</tbody></table>';
                return tableHtml;
            });
        },

        /**
         * Escapa HTML
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(() => {
        console.log('[AdminShortcuts] DOM ready, initializing...');
        console.log('[AdminShortcuts] Config object:', {
            exists: !!window.chatIAAdminAssistant,
            ajaxUrl: config.ajaxUrl,
            hasNonce: !!config.nonce
        });
        console.log('[AdminShortcuts] Shortcut buttons found:', $('.shortcut-btn').length);
        AdminShortcuts.init();
        console.log('[AdminShortcuts] Initialization complete');
    });

    // Exponer para uso externo
    window.AdminShortcuts = AdminShortcuts;

})(jQuery);

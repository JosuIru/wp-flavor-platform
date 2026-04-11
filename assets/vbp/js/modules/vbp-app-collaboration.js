/**
 * Visual Builder Pro - App Module: Collaboration
 * Sistema de colaboración en tiempo real
 *
 * @package Flavor_Chat_IA
 * @since 2.2.0
 */

window.VBPAppCollaboration = {
    // Estado de colaboración
    collaborationEnabled: false,
    realtimeEnabled: false,
    activeUsers: [],
    currentUserCursor: { x: 0, y: 0, elementId: null },
    userCursors: {},

    // Comentarios
    comments: [],
    showCommentsPanel: false,
    activeCommentThread: null,
    newCommentText: '',
    commentFilter: 'all', // all, unresolved, resolved

    // Permisos
    userRole: 'editor', // editor, commenter, viewer
    canEdit: true,
    canComment: true,

    // Heartbeat
    heartbeatInterval: null,
    lastHeartbeat: null,

    // ============ INICIALIZACIÓN ============

    /**
     * Inicializar sistema de colaboración
     */
    initCollaboration: function() {
        var self = this;

        // Verificar si la colaboración está habilitada
        if (typeof VBP_Config !== 'undefined' && VBP_Config.collaboration) {
            this.collaborationEnabled = VBP_Config.collaboration.enabled || false;
            this.userRole = VBP_Config.collaboration.userRole || 'editor';
        }

        // Verificar si realtime está habilitado
        if (typeof VBP_Config !== 'undefined' && VBP_Config.realtime) {
            this.realtimeEnabled = VBP_Config.realtime.enabled || false;
        }

        if (!this.collaborationEnabled) {
            vbpLog.log(' Colaboración deshabilitada');
            return;
        }

        // Establecer permisos según rol
        this.setPermissions();

        // Si realtime está habilitado, usarlo en lugar del sistema legacy
        if (this.realtimeEnabled && typeof window.VBPRealtimeCollaboration !== 'undefined') {
            vbpLog.log(' Usando sistema realtime para colaboración');
            this.initRealtimeIntegration();
        } else {
            // Fallback al sistema legacy
            this.loadActiveUsers();
            this.startHeartbeat();
            this.trackCursorMovement();
            this.setupWordPressHeartbeat();
        }

        // Cargar comentarios (siempre)
        this.loadComments();

        vbpLog.log(' Colaboración inicializada - Rol:', this.userRole, '- Realtime:', this.realtimeEnabled);
    },

    /**
     * Integrar con el sistema de realtime
     */
    initRealtimeIntegration: function() {
        var self = this;
        var postId = this.getPostId();

        // Conectar al sistema de realtime
        if (typeof Alpine !== 'undefined' && Alpine.store('vbpRealtime')) {
            var realtimeStore = Alpine.store('vbpRealtime');
            realtimeStore.connect(postId);

            // Sincronizar usuarios activos desde realtime store
            document.addEventListener('alpine:effect', function() {
                if (realtimeStore.users) {
                    self.activeUsers = realtimeStore.users;
                }
            });
        }

        // Escuchar eventos de realtime
        document.addEventListener('vbp:realtime:userJoined', function(event) {
            var user = event.detail.user;
            self.showNotification(user.name + ' se unió a la edición', 'info');
        });

        document.addEventListener('vbp:realtime:userLeft', function(event) {
            var user = event.detail.user;
            self.showNotification(user.name + ' salió de la edición', 'info');
        });

        document.addEventListener('vbp:realtime:lockConflict', function(event) {
            var lockedBy = event.detail.lockedBy;
            self.showNotification('Elemento bloqueado por ' + lockedBy, 'warning');
        });
    },

    /**
     * Establecer permisos según rol
     */
    setPermissions: function() {
        switch (this.userRole) {
            case 'editor':
                this.canEdit = true;
                this.canComment = true;
                break;
            case 'commenter':
                this.canEdit = false;
                this.canComment = true;
                break;
            case 'viewer':
                this.canEdit = false;
                this.canComment = false;
                break;
        }
    },

    // ============ PRESENCIA DE USUARIOS ============

    /**
     * Cargar usuarios activos en el documento
     */
    loadActiveUsers: function() {
        var self = this;
        var postId = this.getPostId();

        if (!postId) return;

        fetch(VBP_Config.restUrl + 'collaboration/presence/' + postId, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.activeUsers = data.users || [];
                self.userCursors = data.cursors || {};
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error cargando usuarios activos:', error);
        });
    },

    /**
     * Iniciar heartbeat para presencia
     */
    startHeartbeat: function() {
        var self = this;

        // Enviar presencia cada 15 segundos
        this.heartbeatInterval = setInterval(function() {
            self.sendPresence();
        }, 15000);

        // Enviar presencia inicial
        this.sendPresence();

        // Limpiar al cerrar
        window.addEventListener('beforeunload', function() {
            self.removePresence();
        });
    },

    /**
     * Enviar presencia al servidor
     */
    sendPresence: function() {
        var self = this;
        var postId = this.getPostId();

        if (!postId) return;

        var presenceData = {
            post_id: postId,
            cursor: this.currentUserCursor,
            editing_element: Alpine.store('vbp').selection.elementIds[0] || null
        };

        fetch(VBP_Config.restUrl + 'collaboration/presence', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify(presenceData)
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.activeUsers = data.users || [];
                self.userCursors = data.cursors || {};
                self.lastHeartbeat = Date.now();
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error enviando presencia:', error);
        });
    },

    /**
     * Remover presencia al salir
     */
    removePresence: function() {
        var postId = this.getPostId();
        if (!postId) return;

        // Usar sendBeacon para garantizar envío al cerrar
        if (navigator.sendBeacon) {
            var data = new FormData();
            data.append('post_id', postId);
            data.append('_wpnonce', VBP_Config.restNonce);
            navigator.sendBeacon(VBP_Config.restUrl + 'collaboration/leave', data);
        }
    },

    /**
     * Configurar integración con WordPress Heartbeat
     */
    setupWordPressHeartbeat: function() {
        var self = this;

        if (typeof wp === 'undefined' || !wp.heartbeat) {
            return;
        }

        // Enviar datos en heartbeat
        jQuery(document).on('heartbeat-send', function(event, data) {
            data.vbp_presence = {
                post_id: self.getPostId(),
                cursor: self.currentUserCursor,
                editing_element: Alpine.store('vbp').selection.elementIds[0] || null
            };
        });

        // Recibir datos de heartbeat
        jQuery(document).on('heartbeat-tick', function(event, data) {
            if (data.vbp_presence) {
                self.activeUsers = data.vbp_presence.users || [];
                self.userCursors = data.vbp_presence.cursors || {};
            }
            if (data.vbp_comments) {
                self.mergeComments(data.vbp_comments);
            }
        });
    },

    /**
     * Rastrear movimiento del cursor
     */
    trackCursorMovement: function() {
        var self = this;
        var throttleTimer = null;

        document.addEventListener('mousemove', function(event) {
            if (throttleTimer) return;

            throttleTimer = setTimeout(function() {
                var canvas = document.querySelector('.vbp-canvas');
                if (canvas) {
                    var rect = canvas.getBoundingClientRect();
                    self.currentUserCursor = {
                        x: event.clientX - rect.left,
                        y: event.clientY - rect.top
                    };
                }
                throttleTimer = null;
            }, 100);
        });
    },

    // ============ COMENTARIOS ============

    /**
     * Cargar comentarios del documento
     */
    loadComments: function() {
        var self = this;
        var postId = this.getPostId();

        if (!postId) return;

        fetch(VBP_Config.restUrl + 'collaboration/comments/' + postId, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.comments = data.comments || [];
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error cargando comentarios:', error);
        });
    },

    /**
     * Añadir comentario a un elemento
     */
    addComment: function(elementId, text, position) {
        var self = this;
        var postId = this.getPostId();

        if (!postId || !text.trim()) return;

        var commentData = {
            post_id: postId,
            element_id: elementId,
            text: text.trim(),
            position: position || { x: 0, y: 0 }
        };

        fetch(VBP_Config.restUrl + 'collaboration/comments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify(commentData)
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.comments.push(data.comment);
                self.newCommentText = '';
                self.showNotification('Comentario añadido', 'success');
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error añadiendo comentario:', error);
            self.showNotification('Error añadiendo comentario', 'error');
        });
    },

    /**
     * Responder a un comentario
     */
    replyToComment: function(commentId, text) {
        var self = this;

        if (!text.trim()) return;

        fetch(VBP_Config.restUrl + 'collaboration/comments/' + commentId + '/reply', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({ text: text.trim() })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var comment = self.findComment(commentId);
                if (comment) {
                    if (!comment.replies) comment.replies = [];
                    comment.replies.push(data.reply);
                }
                self.showNotification('Respuesta añadida', 'success');
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error respondiendo a comentario:', error);
        });
    },

    /**
     * Resolver comentario
     */
    resolveComment: function(commentId) {
        var self = this;

        fetch(VBP_Config.restUrl + 'collaboration/comments/' + commentId + '/resolve', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var comment = self.findComment(commentId);
                if (comment) {
                    comment.resolved = true;
                    comment.resolved_by = data.resolved_by;
                    comment.resolved_at = data.resolved_at;
                }
                self.showNotification('Comentario resuelto', 'success');
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error resolviendo comentario:', error);
        });
    },

    /**
     * Eliminar comentario
     */
    deleteComment: function(commentId) {
        var self = this;

        if (!confirm('¿Eliminar este comentario?')) return;

        fetch(VBP_Config.restUrl + 'collaboration/comments/' + commentId, {
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.comments = self.comments.filter(function(c) {
                    return c.id !== commentId;
                });
                self.showNotification('Comentario eliminado', 'success');
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error eliminando comentario:', error);
        });
    },

    /**
     * Buscar comentario por ID
     */
    findComment: function(commentId) {
        return this.comments.find(function(c) {
            return c.id === commentId;
        });
    },

    /**
     * Fusionar comentarios nuevos
     */
    mergeComments: function(newComments) {
        var self = this;
        newComments.forEach(function(newComment) {
            var existing = self.findComment(newComment.id);
            if (existing) {
                Object.assign(existing, newComment);
            } else {
                self.comments.push(newComment);
            }
        });
    },

    /**
     * Obtener comentarios filtrados
     */
    getFilteredComments: function() {
        var self = this;

        return this.comments.filter(function(comment) {
            switch (self.commentFilter) {
                case 'unresolved':
                    return !comment.resolved;
                case 'resolved':
                    return comment.resolved;
                default:
                    return true;
            }
        });
    },

    /**
     * Obtener comentarios de un elemento
     */
    getElementComments: function(elementId) {
        return this.comments.filter(function(comment) {
            return comment.element_id === elementId;
        });
    },

    /**
     * Contar comentarios sin resolver
     */
    getUnresolvedCount: function() {
        return this.comments.filter(function(c) {
            return !c.resolved;
        }).length;
    },

    // ============ UI HELPERS ============

    /**
     * Abrir panel de comentarios
     * Se integra con el store existente de comentarios (vbpComments)
     */
    openCommentsPanel: function() {
        // Usar el store existente si está disponible
        if (typeof Alpine !== 'undefined' && Alpine.store('vbpComments')) {
            Alpine.store('vbpComments').open();
        } else {
            this.showCommentsPanel = true;
        }
        this.loadComments();
    },

    /**
     * Cerrar panel de comentarios
     */
    closeCommentsPanel: function() {
        if (typeof Alpine !== 'undefined' && Alpine.store('vbpComments')) {
            Alpine.store('vbpComments').close();
        } else {
            this.showCommentsPanel = false;
        }
        this.activeCommentThread = null;
    },

    /**
     * Abrir hilo de comentario
     */
    openCommentThread: function(commentId) {
        this.activeCommentThread = commentId;
    },

    /**
     * Iniciar nuevo comentario en elemento
     */
    startCommentOnElement: function(elementId, event) {
        if (!this.canComment) {
            this.showNotification('No tienes permiso para comentar', 'warning');
            return;
        }

        var canvas = document.querySelector('.vbp-canvas');
        var rect = canvas.getBoundingClientRect();

        this.activeCommentThread = {
            isNew: true,
            elementId: elementId,
            position: {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top
            }
        };

        this.showCommentsPanel = true;
    },

    /**
     * Obtener avatar de usuario
     */
    getUserAvatar: function(user) {
        if (user.avatar) return user.avatar;
        // Gravatar fallback
        return 'https://www.gravatar.com/avatar/' + user.email_hash + '?d=mp&s=32';
    },

    /**
     * Obtener color de cursor de usuario
     */
    getUserColor: function(userId) {
        var colors = [
            '#ef4444', '#f97316', '#eab308', '#22c55e',
            '#14b8a6', '#3b82f6', '#8b5cf6', '#ec4899'
        ];
        return colors[userId % colors.length];
    },

    /**
     * Formatear tiempo relativo
     */
    formatTimeAgo: function(timestamp) {
        var now = Date.now();
        var diff = now - new Date(timestamp).getTime();

        var minutes = Math.floor(diff / 60000);
        var hours = Math.floor(diff / 3600000);
        var days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Ahora';
        if (minutes < 60) return minutes + ' min';
        if (hours < 24) return hours + 'h';
        return days + 'd';
    },

    // ============ UTILIDADES ============

    /**
     * Mostrar notificación usando el sistema de toast
     */
    showNotification: function(message, type) {
        type = type || 'info';

        // Usar el store de toast si está disponible
        if (typeof Alpine !== 'undefined' && Alpine.store('vbpToast')) {
            Alpine.store('vbpToast').show(message, type);
        } else {
            // Fallback a console
            console.log('[VBP Collaboration]', type + ':', message);
        }
    },

    /**
     * Obtener ID del post actual
     */
    getPostId: function() {
        if (typeof VBP_Config !== 'undefined' && VBP_Config.postId) {
            return VBP_Config.postId;
        }
        var urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('post_id');
    },

    /**
     * Verificar si el usuario puede editar
     */
    checkEditPermission: function() {
        if (!this.canEdit) {
            this.showNotification('Solo tienes permisos de visualización', 'warning');
            return false;
        }
        return true;
    },

    /**
     * Limpiar al destruir
     */
    destroyCollaboration: function() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }
        this.removePresence();
    }
};

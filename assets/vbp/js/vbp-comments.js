/**
 * Visual Builder Pro - Sistema de Comentarios Colaborativos
 *
 * Permite añadir, gestionar y resolver comentarios en elementos del canvas.
 *
 * @package Flavor_Chat_IA
 * @since 2.2.0
 */

document.addEventListener('alpine:init', function() {
    // Store para el sistema de comentarios
    Alpine.store('vbpComments', {
        // Estado
        isOpen: false,
        isLoading: false,
        isPanelVisible: true,
        showResolved: false,
        error: null,

        // Datos
        comments: [],
        groupedComments: {},
        stats: {
            total: 0,
            resolved: 0,
            pending: 0,
            threads: 0
        },

        // Comentario activo
        activeElementId: null,
        activeCommentId: null,
        replyingTo: null,
        newCommentContent: '',

        // Modo de añadir comentario
        isAddingMode: false,
        pendingPosition: null,

        /**
         * Inicializa el sistema de comentarios
         */
        init: function() {
            this.loadComments();
        },

        /**
         * Carga los comentarios del post actual
         */
        loadComments: function() {
            var self = this;
            var postId = this.getPostId();

            if (!postId) return;

            this.isLoading = true;
            this.error = null;

            fetch(this.getApiUrl('/comments/' + postId), {
                method: 'GET',
                headers: this.getHeaders()
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.isLoading = false;
                if (data.success) {
                    self.comments = data.comments || [];
                    self.groupedComments = data.grouped || {};
                    self.loadStats();
                } else {
                    self.error = data.message || 'Error al cargar comentarios';
                }
            })
            .catch(function(error) {
                self.isLoading = false;
                self.error = 'Error de conexión: ' + error.message;
            });
        },

        /**
         * Carga las estadísticas
         */
        loadStats: function() {
            var self = this;
            var postId = this.getPostId();

            if (!postId) return;

            fetch(this.getApiUrl('/comments/' + postId + '/stats'), {
                method: 'GET',
                headers: this.getHeaders()
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.stats) {
                    self.stats = data.stats;
                }
            })
            .catch(function(error) {
                console.warn('[VBP Comments] Error loading stats:', error);
            });
        },

        /**
         * Abre el panel de comentarios
         */
        open: function(elementId) {
            this.isOpen = true;
            this.activeElementId = elementId || null;
            this.loadComments();
        },

        /**
         * Cierra el panel de comentarios
         */
        close: function() {
            this.isOpen = false;
            this.activeElementId = null;
            this.activeCommentId = null;
            this.replyingTo = null;
            this.newCommentContent = '';
            this.isAddingMode = false;
            this.pendingPosition = null;
        },

        /**
         * Activa el modo de añadir comentario
         */
        startAddingMode: function() {
            this.isAddingMode = true;
            document.body.classList.add('vbp-comment-adding-mode');

            // Mostrar notificación
            if (window.vbpApp && window.vbpApp.showNotification) {
                window.vbpApp.showNotification('Haz clic en un elemento para añadir un comentario', 'info');
            }
        },

        /**
         * Cancela el modo de añadir comentario
         */
        cancelAddingMode: function() {
            this.isAddingMode = false;
            this.pendingPosition = null;
            document.body.classList.remove('vbp-comment-adding-mode');
        },

        /**
         * Maneja el clic en un elemento para añadir comentario
         */
        handleElementClick: function(elementId, event) {
            if (!this.isAddingMode) return;

            // Calcular posición relativa al elemento
            var elementRect = event.target.getBoundingClientRect();
            var posX = event.clientX - elementRect.left;
            var posY = event.clientY - elementRect.top;

            this.activeElementId = elementId;
            this.pendingPosition = { x: posX, y: posY };
            this.isAddingMode = false;
            document.body.classList.remove('vbp-comment-adding-mode');

            // Abrir el panel si no está abierto
            if (!this.isOpen) {
                this.isOpen = true;
            }
        },

        /**
         * Añade un nuevo comentario
         */
        addComment: function() {
            var self = this;

            if (!this.newCommentContent.trim()) {
                this.error = 'El comentario no puede estar vacío';
                return;
            }

            if (!this.activeElementId) {
                this.error = 'Selecciona un elemento primero';
                return;
            }

            var postId = this.getPostId();
            if (!postId) return;

            this.isLoading = true;
            this.error = null;

            var requestBody = {
                element_id: this.activeElementId,
                content: this.newCommentContent,
                position: this.pendingPosition || { x: 0, y: 0 },
                parent_id: this.replyingTo || ''
            };

            fetch(this.getApiUrl('/comments/' + postId), {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify(requestBody)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.isLoading = false;
                if (data.success) {
                    // Añadir comentario a la lista
                    self.comments.push(data.comment);
                    self.newCommentContent = '';
                    self.replyingTo = null;
                    self.pendingPosition = null;
                    self.loadStats();

                    // Mostrar notificación
                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification('Comentario añadido', 'success');
                    }

                    // Refrescar comentarios
                    self.loadComments();
                } else {
                    self.error = data.message || 'Error al añadir comentario';
                }
            })
            .catch(function(error) {
                self.isLoading = false;
                self.error = 'Error de conexión: ' + error.message;
            });
        },

        /**
         * Inicia una respuesta a un comentario
         */
        startReply: function(commentId) {
            this.replyingTo = commentId;
            var comment = this.comments.find(function(c) { return c.id === commentId; });
            if (comment) {
                this.activeElementId = comment.element_id;
            }
        },

        /**
         * Cancela la respuesta
         */
        cancelReply: function() {
            this.replyingTo = null;
        },

        /**
         * Resuelve/reabre un comentario
         */
        toggleResolved: function(commentId) {
            var self = this;
            var comment = this.comments.find(function(c) { return c.id === commentId; });
            if (!comment) return;

            var postId = this.getPostId();
            var newResolved = !comment.resolved;

            fetch(this.getApiUrl('/comments/' + postId + '/' + commentId), {
                method: 'PUT',
                headers: this.getHeaders(),
                body: JSON.stringify({ resolved: newResolved })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Actualizar comentario local
                    comment.resolved = newResolved;
                    self.loadStats();

                    var mensaje = newResolved ? 'Comentario resuelto' : 'Comentario reabierto';
                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification(mensaje, 'success');
                    }
                }
            })
            .catch(function(error) {
                console.error('[VBP Comments] Error toggling resolved:', error);
            });
        },

        /**
         * Elimina un comentario
         */
        deleteComment: function(commentId) {
            var self = this;

            if (!confirm('¿Eliminar este comentario?')) {
                return;
            }

            var postId = this.getPostId();

            fetch(this.getApiUrl('/comments/' + postId + '/' + commentId), {
                method: 'DELETE',
                headers: this.getHeaders()
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Eliminar de la lista local
                    self.comments = self.comments.filter(function(c) {
                        return c.id !== commentId && c.parent_id !== commentId;
                    });
                    self.loadStats();

                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification('Comentario eliminado', 'success');
                    }
                }
            })
            .catch(function(error) {
                console.error('[VBP Comments] Error deleting comment:', error);
            });
        },

        /**
         * Selecciona un elemento en el canvas basado en un comentario
         */
        goToElement: function(elementId) {
            var store = Alpine.store('vbp');
            if (store && store.selectElement) {
                store.selectElement(elementId);
            }
            this.activeElementId = elementId;
        },

        /**
         * Obtiene los comentarios de un elemento específico
         */
        getCommentsForElement: function(elementId) {
            return this.comments.filter(function(c) {
                return c.element_id === elementId;
            });
        },

        /**
         * Obtiene los comentarios principales (sin parent)
         */
        getThreads: function() {
            var self = this;
            return this.comments.filter(function(c) {
                if (!self.showResolved && c.resolved) return false;
                return !c.parent_id;
            });
        },

        /**
         * Obtiene las respuestas de un hilo
         */
        getReplies: function(parentId) {
            return this.comments.filter(function(c) {
                return c.parent_id === parentId;
            });
        },

        /**
         * Obtiene el ID del post actual
         */
        getPostId: function() {
            if (typeof VBP_Config !== 'undefined' && VBP_Config.postId) {
                return VBP_Config.postId;
            }
            return null;
        },

        /**
         * Obtiene la URL base de la API
         */
        getApiUrl: function(endpoint) {
            if (typeof VBP_Config !== 'undefined' && VBP_Config.restUrl) {
                return VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1') + endpoint;
            }
            return '/wp-json/flavor-vbp/v1' + endpoint;
        },

        /**
         * Obtiene los headers para las peticiones
         */
        getHeaders: function() {
            var headers = {
                'Content-Type': 'application/json'
            };
            if (typeof VBP_Config !== 'undefined' && VBP_Config.restNonce) {
                headers['X-WP-Nonce'] = VBP_Config.restNonce;
            }
            return headers;
        },

        /**
         * Comprueba si el usuario actual puede editar un comentario
         */
        canEdit: function(comment) {
            if (typeof VBP_Config !== 'undefined') {
                return VBP_Config.userId === comment.user_id || VBP_Config.isAdmin;
            }
            return false;
        }
    });

    // Componente Alpine para el panel de comentarios
    Alpine.data('vbpCommentsPanel', function() {
        return {
            get store() {
                return Alpine.store('vbpComments');
            },

            init: function() {
                // Inicializar el store
                this.store.init();

                // Escuchar eventos de elementos
                var self = this;
                document.addEventListener('vbp-element-click', function(e) {
                    if (self.store.isAddingMode && e.detail && e.detail.elementId) {
                        self.store.handleElementClick(e.detail.elementId, e.detail.event);
                    }
                });
            }
        };
    });
});

// Listener global para marcar elementos con comentarios
document.addEventListener('DOMContentLoaded', function() {
    // Añadir badges de comentarios a elementos
    function updateCommentBadges() {
        if (typeof Alpine === 'undefined') return;

        var store = Alpine.store('vbpComments');
        if (!store || !store.stats || !store.stats.by_element) return;

        var elementosConComentarios = store.stats.by_element;

        document.querySelectorAll('.vbp-element').forEach(function(el) {
            var elementId = el.dataset.id || el.id;
            var existingBadge = el.querySelector('.vbp-comment-badge');

            if (elementosConComentarios[elementId]) {
                var count = elementosConComentarios[elementId];
                if (existingBadge) {
                    existingBadge.textContent = count;
                } else {
                    var badge = document.createElement('span');
                    badge.className = 'vbp-comment-badge';
                    badge.textContent = count;
                    badge.title = count + ' comentario(s)';
                    el.appendChild(badge);
                }
            } else if (existingBadge) {
                existingBadge.remove();
            }
        });
    }

    // Actualizar badges cuando cambian los comentarios
    var checkInterval = setInterval(function() {
        if (typeof Alpine !== 'undefined' && Alpine.store('vbpComments')) {
            updateCommentBadges();

            // Observar cambios en el store
            Alpine.effect(function() {
                var store = Alpine.store('vbpComments');
                if (store) {
                    // Acceder a stats para crear dependencia reactiva
                    var statsTotal = store.stats.total;
                    updateCommentBadges();
                }
            });

            clearInterval(checkInterval);
        }
    }, 500);
});

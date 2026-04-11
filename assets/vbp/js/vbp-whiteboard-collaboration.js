/**
 * Visual Builder Pro - Whiteboard Collaboration
 *
 * Sistema de colaboracion en tiempo real para el whiteboard:
 * - Cursores de usuarios remotos
 * - Sincronizacion de elementos
 * - Chat de sesion
 * - Sistema de awareness
 * - Votacion en tiempo real
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.6.0
 */

(function() {
    'use strict';

    // Verificar dependencias
    if (typeof Alpine === 'undefined') {
        console.warn('[VBP Whiteboard Collab] Alpine.js no esta disponible');
        return;
    }

    /**
     * Configuracion de colaboracion
     */
    const COLLAB_CONFIG = {
        cursorUpdateInterval: 50,      // ms entre actualizaciones de cursor
        heartbeatInterval: 5000,        // ms entre heartbeats
        reconnectDelay: 3000,           // ms antes de reconectar
        maxReconnectAttempts: 10,
        syncDebounce: 300,              // ms de debounce para sincronizar cambios
        idleTimeout: 120000,            // 2 minutos para estado idle
        awayTimeout: 300000,            // 5 minutos para estado away
        maxChatMessages: 200
    };

    /**
     * Estados de usuario
     */
    const USER_STATUS = {
        ACTIVE: 'active',
        IDLE: 'idle',
        AWAY: 'away',
        OFFLINE: 'offline'
    };

    /**
     * Colores para usuarios
     */
    const USER_COLORS = [
        '#ef4444', // red
        '#f97316', // orange
        '#eab308', // yellow
        '#22c55e', // green
        '#14b8a6', // teal
        '#3b82f6', // blue
        '#8b5cf6', // violet
        '#ec4899', // pink
        '#6366f1', // indigo
        '#06b6d4'  // cyan
    ];

    /**
     * Generador de IDs
     */
    function generateId(prefix = 'id') {
        return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Obtener color para usuario basado en ID
     */
    function getUserColor(userId) {
        if (!userId) return USER_COLORS[0];

        let hash = 0;
        for (let i = 0; i < userId.length; i++) {
            hash = userId.charCodeAt(i) + ((hash << 5) - hash);
        }
        return USER_COLORS[Math.abs(hash) % USER_COLORS.length];
    }

    /**
     * Interpolador de cursores para movimiento suave
     */
    class CursorInterpolator {
        constructor() {
            this.cursors = new Map();
            this.animationFrameId = null;
        }

        updateTarget(userId, targetPosition) {
            const cursorData = this.cursors.get(userId) || {
                current: { ...targetPosition },
                target: { ...targetPosition },
                timestamp: performance.now()
            };

            cursorData.previous = { ...cursorData.current };
            cursorData.target = { ...targetPosition };
            cursorData.timestamp = performance.now();

            this.cursors.set(userId, cursorData);

            if (!this.animationFrameId) {
                this.startAnimation();
            }
        }

        startAnimation() {
            const animate = () => {
                const now = performance.now();
                let hasActiveInterpolation = false;

                this.cursors.forEach((data, userId) => {
                    const elapsed = now - data.timestamp;
                    const duration = 100; // ms de interpolacion
                    const progress = Math.min(elapsed / duration, 1);

                    // Interpolacion easeOutQuad
                    const easedProgress = 1 - (1 - progress) * (1 - progress);

                    data.current.x = data.previous.x + (data.target.x - data.previous.x) * easedProgress;
                    data.current.y = data.previous.y + (data.target.y - data.previous.y) * easedProgress;

                    if (progress < 1) {
                        hasActiveInterpolation = true;
                    }
                });

                // Emitir evento de actualizacion
                document.dispatchEvent(new CustomEvent('whiteboard:cursors-update', {
                    detail: { cursors: Object.fromEntries(this.cursors) }
                }));

                if (hasActiveInterpolation) {
                    this.animationFrameId = requestAnimationFrame(animate);
                } else {
                    this.animationFrameId = null;
                }
            };

            this.animationFrameId = requestAnimationFrame(animate);
        }

        getCursor(userId) {
            const data = this.cursors.get(userId);
            return data ? data.current : null;
        }

        removeCursor(userId) {
            this.cursors.delete(userId);
        }

        clear() {
            this.cursors.clear();
            if (this.animationFrameId) {
                cancelAnimationFrame(this.animationFrameId);
                this.animationFrameId = null;
            }
        }
    }

    /**
     * Cliente de colaboracion WebSocket
     */
    class CollaborationClient {
        constructor(options = {}) {
            this.options = { ...COLLAB_CONFIG, ...options };
            this.websocket = null;
            this.connectionState = 'disconnected';
            this.reconnectAttempts = 0;
            this.heartbeatInterval = null;
            this.userId = options.userId || generateId('user');
            this.userName = options.userName || 'Usuario';
            this.userColor = options.userColor || getUserColor(this.userId);
            this.documentId = options.documentId;
            this.cursorThrottle = null;
            this.lastCursorUpdate = 0;
            this.pendingChanges = [];
            this.syncTimeout = null;

            // Estado de actividad
            this.userStatus = USER_STATUS.ACTIVE;
            this.lastActivity = Date.now();
            this.idleTimeout = null;
            this.awayTimeout = null;

            // Callbacks
            this.onConnect = options.onConnect || (() => {});
            this.onDisconnect = options.onDisconnect || (() => {});
            this.onUserJoin = options.onUserJoin || (() => {});
            this.onUserLeave = options.onUserLeave || (() => {});
            this.onCursorMove = options.onCursorMove || (() => {});
            this.onElementChange = options.onElementChange || (() => {});
            this.onChat = options.onChat || (() => {});
            this.onVote = options.onVote || (() => {});
            this.onError = options.onError || (() => {});

            // Interpolador de cursores
            this.cursorInterpolator = new CursorInterpolator();
        }

        /**
         * Conectar al servidor
         */
        connect(serverUrl) {
            if (this.connectionState === 'connected' || this.connectionState === 'connecting') {
                return;
            }

            this.connectionState = 'connecting';
            this.serverUrl = serverUrl;

            try {
                this.websocket = new WebSocket(serverUrl);

                this.websocket.onopen = () => {
                    this.connectionState = 'connected';
                    this.reconnectAttempts = 0;

                    // Enviar mensaje de join
                    this.send({
                        type: 'join',
                        userId: this.userId,
                        userName: this.userName,
                        userColor: this.userColor,
                        documentId: this.documentId
                    });

                    // Iniciar heartbeat
                    this.startHeartbeat();

                    // Iniciar deteccion de inactividad
                    this.startActivityDetection();

                    this.onConnect();
                };

                this.websocket.onmessage = (event) => {
                    this.handleMessage(event.data);
                };

                this.websocket.onclose = () => {
                    this.connectionState = 'disconnected';
                    this.stopHeartbeat();
                    this.stopActivityDetection();

                    this.onDisconnect();

                    // Intentar reconectar
                    if (this.reconnectAttempts < this.options.maxReconnectAttempts) {
                        this.reconnectAttempts++;
                        setTimeout(() => {
                            this.connect(this.serverUrl);
                        }, this.options.reconnectDelay);
                    }
                };

                this.websocket.onerror = (error) => {
                    this.onError(error);
                };

            } catch (error) {
                this.connectionState = 'disconnected';
                this.onError(error);
            }
        }

        /**
         * Desconectar
         */
        disconnect() {
            if (this.websocket) {
                this.send({ type: 'leave', userId: this.userId });
                this.websocket.close();
                this.websocket = null;
            }

            this.connectionState = 'disconnected';
            this.stopHeartbeat();
            this.stopActivityDetection();
            this.cursorInterpolator.clear();
        }

        /**
         * Enviar mensaje
         */
        send(message) {
            if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                this.websocket.send(JSON.stringify(message));
            }
        }

        /**
         * Manejar mensaje recibido
         */
        handleMessage(data) {
            try {
                const message = JSON.parse(data);

                switch (message.type) {
                    case 'user_join':
                        this.onUserJoin(message.user);
                        break;

                    case 'user_leave':
                        this.cursorInterpolator.removeCursor(message.userId);
                        this.onUserLeave(message.userId);
                        break;

                    case 'cursor':
                        if (message.userId !== this.userId) {
                            this.cursorInterpolator.updateTarget(message.userId, message.position);
                            this.onCursorMove(message.userId, message.position, message.userName);
                        }
                        break;

                    case 'element_add':
                    case 'element_update':
                    case 'element_remove':
                        if (message.userId !== this.userId) {
                            this.onElementChange(message);
                        }
                        break;

                    case 'chat':
                        this.onChat(message);
                        break;

                    case 'vote':
                        this.onVote(message);
                        break;

                    case 'sync':
                        // Sincronizacion completa del estado
                        this.handleSync(message);
                        break;

                    case 'users':
                        // Lista de usuarios actuales
                        message.users.forEach(user => {
                            if (user.userId !== this.userId) {
                                this.onUserJoin(user);
                            }
                        });
                        break;

                    case 'pong':
                        // Respuesta a heartbeat
                        break;
                }
            } catch (error) {
                console.error('[VBP Whiteboard Collab] Error parsing message:', error);
            }
        }

        /**
         * Manejar sincronizacion completa
         */
        handleSync(message) {
            const store = Alpine.store('vbpWhiteboard');

            if (message.elements) {
                // Aplicar cambios sin duplicar
                message.elements.forEach(remoteElement => {
                    const localElement = store.getElementById(remoteElement.id);

                    if (!localElement) {
                        store.elements.push(remoteElement);
                    } else if (remoteElement.updatedAt > (localElement.updatedAt || 0)) {
                        Object.assign(localElement, remoteElement);
                    }
                });
            }
        }

        /**
         * Iniciar heartbeat
         */
        startHeartbeat() {
            this.heartbeatInterval = setInterval(() => {
                this.send({
                    type: 'ping',
                    userId: this.userId,
                    status: this.userStatus,
                    timestamp: Date.now()
                });
            }, this.options.heartbeatInterval);
        }

        /**
         * Detener heartbeat
         */
        stopHeartbeat() {
            if (this.heartbeatInterval) {
                clearInterval(this.heartbeatInterval);
                this.heartbeatInterval = null;
            }
        }

        /**
         * Iniciar deteccion de inactividad
         */
        startActivityDetection() {
            const checkActivity = () => {
                const now = Date.now();
                const timeSinceActivity = now - this.lastActivity;

                if (timeSinceActivity >= this.options.awayTimeout) {
                    this.setStatus(USER_STATUS.AWAY);
                } else if (timeSinceActivity >= this.options.idleTimeout) {
                    this.setStatus(USER_STATUS.IDLE);
                }
            };

            // Listeners de actividad
            const activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart'];
            activityEvents.forEach(event => {
                document.addEventListener(event, () => {
                    this.lastActivity = Date.now();
                    if (this.userStatus !== USER_STATUS.ACTIVE) {
                        this.setStatus(USER_STATUS.ACTIVE);
                    }
                }, { passive: true });
            });

            // Check periodico
            this.activityCheckInterval = setInterval(checkActivity, 30000);
        }

        /**
         * Detener deteccion de inactividad
         */
        stopActivityDetection() {
            if (this.activityCheckInterval) {
                clearInterval(this.activityCheckInterval);
                this.activityCheckInterval = null;
            }
        }

        /**
         * Establecer estado de usuario
         */
        setStatus(status) {
            if (this.userStatus !== status) {
                this.userStatus = status;
                this.send({
                    type: 'status',
                    userId: this.userId,
                    status: status
                });
            }
        }

        /**
         * Actualizar posicion del cursor
         */
        updateCursor(position) {
            const now = Date.now();

            if (now - this.lastCursorUpdate >= this.options.cursorUpdateInterval) {
                this.lastCursorUpdate = now;
                this.send({
                    type: 'cursor',
                    userId: this.userId,
                    userName: this.userName,
                    userColor: this.userColor,
                    position: position,
                    timestamp: now
                });
            }
        }

        /**
         * Notificar cambio de elemento
         */
        notifyElementChange(changeType, element, options = {}) {
            this.send({
                type: 'element_' + changeType,
                userId: this.userId,
                element: element,
                options: options,
                timestamp: Date.now()
            });
        }

        /**
         * Enviar mensaje de chat
         */
        sendChat(text) {
            const message = {
                type: 'chat',
                userId: this.userId,
                userName: this.userName,
                userColor: this.userColor,
                text: text,
                timestamp: Date.now()
            };

            this.send(message);
            return message;
        }

        /**
         * Notificar voto
         */
        notifyVote(stickyId, votes) {
            this.send({
                type: 'vote',
                userId: this.userId,
                stickyId: stickyId,
                votes: votes,
                timestamp: Date.now()
            });
        }

        /**
         * Obtener posiciones interpoladas de cursores
         */
        getInterpolatedCursors() {
            const result = {};
            this.cursorInterpolator.cursors.forEach((data, userId) => {
                result[userId] = {
                    position: data.current,
                    ...data.userInfo
                };
            });
            return result;
        }
    }

    /**
     * Store de colaboracion para Alpine.js
     */
    document.addEventListener('alpine:init', function() {

        Alpine.store('whiteboardCollab', {
            // Estado de conexion
            connected: false,
            connecting: false,
            serverUrl: null,

            // Usuario actual
            currentUser: {
                id: null,
                name: 'Usuario',
                color: '#3b82f6'
            },

            // Usuarios conectados
            users: {},

            // Cursores remotos
            remoteCursors: {},

            // Chat
            chatMessages: [],
            chatVisible: false,
            chatUnread: 0,

            // Cliente de colaboracion
            _client: null,

            /**
             * Inicializar colaboracion
             */
            init(options = {}) {
                const userId = options.userId || generateId('user');
                const userName = options.userName || 'Usuario';
                const userColor = options.userColor || getUserColor(userId);

                this.currentUser = {
                    id: userId,
                    name: userName,
                    color: userColor
                };

                this._client = new CollaborationClient({
                    userId: userId,
                    userName: userName,
                    userColor: userColor,
                    documentId: options.documentId,

                    onConnect: () => {
                        this.connected = true;
                        this.connecting = false;
                        this.dispatchEvent('collab:connected');
                    },

                    onDisconnect: () => {
                        this.connected = false;
                        this.connecting = false;
                        this.dispatchEvent('collab:disconnected');
                    },

                    onUserJoin: (user) => {
                        this.users[user.userId] = user;
                        this.dispatchEvent('collab:user-join', { user });
                    },

                    onUserLeave: (userId) => {
                        delete this.users[userId];
                        delete this.remoteCursors[userId];
                        this.dispatchEvent('collab:user-leave', { userId });
                    },

                    onCursorMove: (userId, position, userName) => {
                        this.remoteCursors[userId] = {
                            position: position,
                            userName: userName,
                            color: this.users[userId]?.color || getUserColor(userId),
                            timestamp: Date.now()
                        };
                    },

                    onElementChange: (message) => {
                        this.handleRemoteChange(message);
                    },

                    onChat: (message) => {
                        this.chatMessages.push(message);
                        if (this.chatMessages.length > COLLAB_CONFIG.maxChatMessages) {
                            this.chatMessages.shift();
                        }
                        if (!this.chatVisible) {
                            this.chatUnread++;
                        }
                        this.dispatchEvent('collab:chat', { message });
                    },

                    onVote: (message) => {
                        const store = Alpine.store('vbpWhiteboard');
                        const sticky = store.getElementById(message.stickyId);
                        if (sticky) {
                            sticky.votes = message.votes;
                        }
                    },

                    onError: (error) => {
                        console.error('[VBP Whiteboard Collab] Error:', error);
                        this.dispatchEvent('collab:error', { error });
                    }
                });

                // Escuchar cambios locales
                this.setupLocalChangeListeners();
            },

            /**
             * Conectar al servidor
             */
            connect(serverUrl) {
                if (!this._client) {
                    console.error('[VBP Whiteboard Collab] Cliente no inicializado');
                    return;
                }

                this.serverUrl = serverUrl;
                this.connecting = true;
                this._client.connect(serverUrl);
            },

            /**
             * Desconectar
             */
            disconnect() {
                if (this._client) {
                    this._client.disconnect();
                }
                this.connected = false;
                this.users = {};
                this.remoteCursors = {};
            },

            /**
             * Configurar listeners para cambios locales
             */
            setupLocalChangeListeners() {
                document.addEventListener('whiteboard:element-add', (e) => {
                    if (this.connected && this._client) {
                        this._client.notifyElementChange('add', e.detail.element);
                    }
                });

                document.addEventListener('whiteboard:element-update', (e) => {
                    if (this.connected && this._client) {
                        const store = Alpine.store('vbpWhiteboard');
                        const element = store.getElementById(e.detail.elementId);
                        if (element) {
                            this._client.notifyElementChange('update', element);
                        }
                    }
                });

                document.addEventListener('whiteboard:element-remove', (e) => {
                    if (this.connected && this._client) {
                        this._client.notifyElementChange('remove', { id: e.detail.elementId });
                    }
                });

                document.addEventListener('whiteboard:vote', (e) => {
                    if (this.connected && this._client) {
                        this._client.notifyVote(e.detail.stickyId, e.detail.votes);
                    }
                });
            },

            /**
             * Manejar cambio remoto
             */
            handleRemoteChange(message) {
                const store = Alpine.store('vbpWhiteboard');

                switch (message.type) {
                    case 'element_add':
                        if (!store.getElementById(message.element.id)) {
                            store.elements.push(message.element);
                        }
                        break;

                    case 'element_update':
                        const element = store.getElementById(message.element.id);
                        if (element) {
                            Object.assign(element, message.element);
                        }
                        break;

                    case 'element_remove':
                        const index = store.elements.findIndex(el => el.id === message.element.id);
                        if (index !== -1) {
                            store.elements.splice(index, 1);
                        }
                        break;
                }
            },

            /**
             * Actualizar cursor local
             */
            updateLocalCursor(position) {
                if (this._client && this.connected) {
                    this._client.updateCursor(position);
                }
            },

            /**
             * Enviar mensaje de chat
             */
            sendChatMessage(text) {
                if (!text.trim()) return;

                if (this._client && this.connected) {
                    const message = this._client.sendChat(text);
                    this.chatMessages.push(message);
                } else {
                    // Modo offline: agregar solo localmente
                    this.chatMessages.push({
                        type: 'chat',
                        userId: this.currentUser.id,
                        userName: this.currentUser.name,
                        userColor: this.currentUser.color,
                        text: text,
                        timestamp: Date.now(),
                        local: true
                    });
                }
            },

            /**
             * Toggle chat
             */
            toggleChat() {
                this.chatVisible = !this.chatVisible;
                if (this.chatVisible) {
                    this.chatUnread = 0;
                }
            },

            /**
             * Obtener lista de usuarios
             */
            getUserList() {
                return Object.values(this.users);
            },

            /**
             * Obtener numero de usuarios conectados
             */
            getUserCount() {
                return Object.keys(this.users).length + 1; // +1 por usuario actual
            },

            /**
             * Emitir evento
             */
            dispatchEvent(name, detail = {}) {
                document.dispatchEvent(new CustomEvent(name, { detail }));
            }
        });

        /**
         * Componente: Cursores remotos
         */
        Alpine.data('remoteCursors', function() {
            return {
                get cursors() {
                    return Alpine.store('whiteboardCollab').remoteCursors;
                },

                getCursorStyle(cursor) {
                    return {
                        left: cursor.position.x + 'px',
                        top: cursor.position.y + 'px',
                        '--cursor-color': cursor.color
                    };
                }
            };
        });

        /**
         * Componente: Panel de usuarios
         */
        Alpine.data('collabUsersPanel', function() {
            return {
                expanded: false,

                get collab() {
                    return Alpine.store('whiteboardCollab');
                },

                get users() {
                    return this.collab.getUserList();
                },

                get userCount() {
                    return this.collab.getUserCount();
                },

                get currentUser() {
                    return this.collab.currentUser;
                },

                toggle() {
                    this.expanded = !this.expanded;
                }
            };
        });

        /**
         * Componente: Chat de sesion
         */
        Alpine.data('collabChat', function() {
            return {
                messageText: '',

                get collab() {
                    return Alpine.store('whiteboardCollab');
                },

                get messages() {
                    return this.collab.chatMessages;
                },

                get visible() {
                    return this.collab.chatVisible;
                },

                get unread() {
                    return this.collab.chatUnread;
                },

                toggle() {
                    this.collab.toggleChat();
                },

                send() {
                    if (!this.messageText.trim()) return;

                    this.collab.sendChatMessage(this.messageText);
                    this.messageText = '';

                    // Scroll al final
                    this.$nextTick(() => {
                        const container = this.$refs.messagesContainer;
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                },

                formatTime(timestamp) {
                    const date = new Date(timestamp);
                    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
            };
        });

    });

    // Exponer clases globalmente
    window.VBPCollaborationClient = CollaborationClient;
    window.VBPCursorInterpolator = CursorInterpolator;
    window.VBPUserColors = USER_COLORS;
    window.VBPGetUserColor = getUserColor;

})();

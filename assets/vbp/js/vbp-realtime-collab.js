/**
 * Visual Builder Pro - Realtime Collaboration Client (v2.5.0)
 *
 * Sistema avanzado de colaboracion en tiempo real con:
 * - Interpolacion suave de cursores con prediccion de velocidad
 * - Sistema de awareness completo
 * - Chat y comentarios en tiempo real
 * - Deteccion y resolucion de conflictos (CRDT/OT)
 * - Following mode
 * - Presencia en paneles
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.5.0
 */

(function() {
    'use strict';

    // Verificar dependencias
    if (typeof Alpine === 'undefined') {
        console.warn('[VBP Realtime] Alpine.js no esta disponible');
        return;
    }

    /**
     * Configuracion por defecto
     */
    const DEFAULT_CONFIG = {
        heartbeatInterval: 5000,
        cursorThrottle: 16,               // ~60fps para interpolacion suave
        cursorInterpolationDuration: 100, // Duracion de interpolacion en ms
        lockTimeout: 30000,
        lockRenewInterval: 20000,
        reconnectDelay: 3000,
        maxReconnectAttempts: 10,
        syncDebounce: 300,
        idleTimeout: 120000,              // 2 minutos para estado idle
        awayTimeout: 300000,              // 5 minutos para estado away
        conflictDetectionWindow: 2000,    // Ventana para detectar conflictos
        maxChatMessages: 100,
        maxOperationHistory: 500,
    };

    /**
     * Estados de usuario posibles
     */
    const USER_STATUS = {
        ACTIVE: 'active',
        IDLE: 'idle',
        AWAY: 'away',
        OFFLINE: 'offline'
    };

    /**
     * Tipos de operacion para CRDT
     */
    const OPERATION_TYPES = {
        SET: 'set',
        DELETE: 'delete',
        INSERT: 'insert',
        MOVE: 'move',
        STYLE: 'style'
    };

    /**
     * Clase para interpolacion suave de cursores
     */
    class CursorInterpolator {
        constructor() {
            this.cursors = new Map();
            this.animationFrameId = null;
            this.lastFrameTime = 0;
        }

        /**
         * Actualizar posicion objetivo de un cursor
         */
        updateTarget(userId, targetPosition, velocity = { x: 0, y: 0 }) {
            const cursorData = this.cursors.get(userId) || {
                current: { ...targetPosition },
                target: { ...targetPosition },
                velocity: { x: 0, y: 0 },
                timestamp: performance.now()
            };

            cursorData.previous = { ...cursorData.current };
            cursorData.target = { ...targetPosition };
            cursorData.velocity = velocity;
            cursorData.timestamp = performance.now();

            this.cursors.set(userId, cursorData);

            if (!this.animationFrameId) {
                this.startAnimation();
            }
        }

        /**
         * Iniciar loop de animacion
         */
        startAnimation() {
            const animate = (currentTime) => {
                const deltaTime = currentTime - this.lastFrameTime;
                this.lastFrameTime = currentTime;

                let hasActiveCursors = false;

                this.cursors.forEach((cursorData, userId) => {
                    const progress = Math.min(
                        (currentTime - cursorData.timestamp) / DEFAULT_CONFIG.cursorInterpolationDuration,
                        1
                    );

                    // Interpolacion con easing cubico
                    const easedProgress = this.easeOutCubic(progress);

                    // Calcular posicion con prediccion de velocidad
                    const predictedTarget = {
                        x: cursorData.target.x + cursorData.velocity.x * (deltaTime / 1000) * 0.3,
                        y: cursorData.target.y + cursorData.velocity.y * (deltaTime / 1000) * 0.3
                    };

                    cursorData.current = {
                        x: this.lerp(cursorData.previous?.x || cursorData.current.x, predictedTarget.x, easedProgress),
                        y: this.lerp(cursorData.previous?.y || cursorData.current.y, predictedTarget.y, easedProgress)
                    };

                    if (progress < 1) {
                        hasActiveCursors = true;
                    }

                    // Emitir evento de actualizacion
                    document.dispatchEvent(new CustomEvent('vbp:cursor:interpolated', {
                        detail: { userId, position: cursorData.current }
                    }));
                });

                if (hasActiveCursors) {
                    this.animationFrameId = requestAnimationFrame(animate);
                } else {
                    this.animationFrameId = null;
                }
            };

            this.lastFrameTime = performance.now();
            this.animationFrameId = requestAnimationFrame(animate);
        }

        /**
         * Interpolacion lineal
         */
        lerp(start, end, progress) {
            return start + (end - start) * progress;
        }

        /**
         * Easing cubico suave
         */
        easeOutCubic(progress) {
            return 1 - Math.pow(1 - progress, 3);
        }

        /**
         * Obtener posicion actual interpolada
         */
        getCurrentPosition(userId) {
            const cursorData = this.cursors.get(userId);
            return cursorData ? cursorData.current : null;
        }

        /**
         * Eliminar cursor
         */
        removeCursor(userId) {
            this.cursors.delete(userId);
        }

        /**
         * Limpiar todos los cursores
         */
        clear() {
            this.cursors.clear();
            if (this.animationFrameId) {
                cancelAnimationFrame(this.animationFrameId);
                this.animationFrameId = null;
            }
        }
    }

    /**
     * Sistema de deteccion y resolucion de conflictos (CRDT simplificado)
     */
    class ConflictResolver {
        constructor() {
            this.operationLog = [];
            this.pendingOperations = new Map();
            this.vectorClock = new Map();
        }

        /**
         * Crear una nueva operacion
         */
        createOperation(type, path, value, userId) {
            const operationId = this.generateOperationId();
            const timestamp = Date.now();

            // Incrementar reloj vectorial
            const currentClock = this.vectorClock.get(userId) || 0;
            this.vectorClock.set(userId, currentClock + 1);

            const operation = {
                id: operationId,
                type,
                path,
                value,
                userId,
                timestamp,
                vectorClock: new Map(this.vectorClock),
                parentId: this.getLastOperationId()
            };

            this.operationLog.push(operation);
            this.trimOperationLog();

            return operation;
        }

        /**
         * Aplicar operacion remota
         */
        applyRemoteOperation(remoteOperation) {
            // Verificar si hay conflicto
            const conflictingOps = this.findConflictingOperations(remoteOperation);

            if (conflictingOps.length > 0) {
                return this.resolveConflict(remoteOperation, conflictingOps);
            }

            // Sin conflicto, aplicar directamente
            this.operationLog.push(remoteOperation);
            this.updateVectorClock(remoteOperation);
            this.trimOperationLog();

            return { applied: true, conflict: false, operation: remoteOperation };
        }

        /**
         * Encontrar operaciones en conflicto
         */
        findConflictingOperations(operation) {
            const conflictWindow = DEFAULT_CONFIG.conflictDetectionWindow;
            const recentOps = this.operationLog.filter(op =>
                Math.abs(op.timestamp - operation.timestamp) < conflictWindow &&
                op.userId !== operation.userId &&
                this.pathsOverlap(op.path, operation.path)
            );

            return recentOps;
        }

        /**
         * Verificar si dos paths se solapan
         */
        pathsOverlap(path1, path2) {
            if (!Array.isArray(path1) || !Array.isArray(path2)) return false;

            const minLength = Math.min(path1.length, path2.length);
            for (let i = 0; i < minLength; i++) {
                if (path1[i] !== path2[i]) return false;
            }
            return true;
        }

        /**
         * Resolver conflicto entre operaciones
         */
        resolveConflict(operation, conflictingOps) {
            // Estrategia: Last-Write-Wins con preferencia por operacion con mayor vectorClock
            const allOps = [...conflictingOps, operation].sort((a, b) => {
                // Primero por timestamp
                if (a.timestamp !== b.timestamp) {
                    return b.timestamp - a.timestamp;
                }
                // Luego por userId (determinista)
                return b.userId - a.userId;
            });

            const winningOp = allOps[0];
            const isCurrentUserWinner = winningOp.userId === operation.userId;

            // Crear evento de conflicto para UI
            const conflictEvent = {
                type: 'conflict',
                elementPath: operation.path,
                conflictingUsers: [...new Set(allOps.map(op => op.userId))],
                operations: allOps,
                winner: winningOp,
                timestamp: Date.now()
            };

            document.dispatchEvent(new CustomEvent('vbp:conflict:detected', {
                detail: conflictEvent
            }));

            return {
                applied: isCurrentUserWinner,
                conflict: true,
                winner: winningOp,
                loser: isCurrentUserWinner ? conflictingOps[0] : operation,
                conflictEvent
            };
        }

        /**
         * Transformar operacion (OT basico)
         */
        transformOperation(operation, againstOperation) {
            // Si ambas operaciones son sobre el mismo path
            if (this.pathsOverlap(operation.path, againstOperation.path)) {
                // La operacion mas reciente gana
                if (operation.timestamp > againstOperation.timestamp) {
                    return operation;
                }
                return null; // Descartar operacion
            }
            return operation;
        }

        /**
         * Actualizar reloj vectorial
         */
        updateVectorClock(operation) {
            const remoteClock = operation.vectorClock;
            if (remoteClock) {
                remoteClock.forEach((value, userId) => {
                    const currentValue = this.vectorClock.get(userId) || 0;
                    this.vectorClock.set(userId, Math.max(currentValue, value));
                });
            }
        }

        /**
         * Generar ID unico de operacion
         */
        generateOperationId() {
            return `op_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        }

        /**
         * Obtener ID de ultima operacion
         */
        getLastOperationId() {
            if (this.operationLog.length === 0) return null;
            return this.operationLog[this.operationLog.length - 1].id;
        }

        /**
         * Limpiar log de operaciones antiguas
         */
        trimOperationLog() {
            if (this.operationLog.length > DEFAULT_CONFIG.maxOperationHistory) {
                this.operationLog = this.operationLog.slice(-DEFAULT_CONFIG.maxOperationHistory);
            }
        }

        /**
         * Obtener historial de operaciones
         */
        getOperationHistory() {
            return [...this.operationLog];
        }
    }

    /**
     * Sistema de chat en tiempo real
     */
    class RealtimeChat {
        constructor(collaboration) {
            this.collaboration = collaboration;
            this.messages = [];
            this.unreadCount = 0;
            this.isOpen = false;
            this.typingUsers = new Map();
            this.reactions = new Map();
        }

        /**
         * Enviar mensaje
         */
        async sendMessage(content, options = {}) {
            if (!content.trim()) return null;

            const message = {
                id: this.generateMessageId(),
                userId: this.collaboration.userId,
                userName: this.collaboration.userName,
                userColor: this.collaboration.userColor,
                userAvatar: this.collaboration.userAvatar,
                content: content.trim(),
                type: options.type || 'text',
                mentions: this.extractMentions(content),
                elementRef: options.elementRef || null,
                timestamp: Date.now(),
                reactions: {}
            };

            // Agregar localmente primero (optimistic update)
            this.addMessage(message);

            // Enviar al servidor
            try {
                await this.collaboration.apiRequest('POST', '/realtime/chat/message', {
                    post_id: this.collaboration.postId,
                    message
                });
                return message;
            } catch (error) {
                console.error('[VBP Chat] Error enviando mensaje:', error);
                // Marcar mensaje como fallido
                message.failed = true;
                this.updateMessage(message.id, { failed: true });
                return null;
            }
        }

        /**
         * Agregar mensaje al historial
         */
        addMessage(message) {
            this.messages.push(message);

            // Limitar historial
            if (this.messages.length > DEFAULT_CONFIG.maxChatMessages) {
                this.messages.shift();
            }

            // Incrementar contador si el chat esta cerrado
            if (!this.isOpen && message.userId !== this.collaboration.userId) {
                this.unreadCount++;
            }

            // Notificar si hay menciones
            if (message.mentions.includes(this.collaboration.userId)) {
                this.collaboration.showToast(
                    `${message.userName} te menciono en el chat`,
                    'info',
                    { avatar: message.userAvatar, color: message.userColor }
                );
            }

            document.dispatchEvent(new CustomEvent('vbp:chat:message', { detail: message }));
        }

        /**
         * Actualizar mensaje existente
         */
        updateMessage(messageId, updates) {
            const messageIndex = this.messages.findIndex(m => m.id === messageId);
            if (messageIndex !== -1) {
                this.messages[messageIndex] = { ...this.messages[messageIndex], ...updates };
                document.dispatchEvent(new CustomEvent('vbp:chat:message:updated', {
                    detail: this.messages[messageIndex]
                }));
            }
        }

        /**
         * Agregar reaccion a mensaje
         */
        async addReaction(messageId, emoji) {
            const message = this.messages.find(m => m.id === messageId);
            if (!message) return;

            const userId = this.collaboration.userId;

            // Toggle reaccion
            if (!message.reactions[emoji]) {
                message.reactions[emoji] = [];
            }

            const userIndex = message.reactions[emoji].indexOf(userId);
            if (userIndex === -1) {
                message.reactions[emoji].push(userId);
            } else {
                message.reactions[emoji].splice(userIndex, 1);
            }

            document.dispatchEvent(new CustomEvent('vbp:chat:reaction', {
                detail: { messageId, emoji, message }
            }));

            // Sincronizar con servidor
            try {
                await this.collaboration.apiRequest('POST', '/realtime/chat/reaction', {
                    post_id: this.collaboration.postId,
                    message_id: messageId,
                    emoji,
                    action: userIndex === -1 ? 'add' : 'remove'
                });
            } catch (error) {
                console.error('[VBP Chat] Error con reaccion:', error);
            }
        }

        /**
         * Indicar que el usuario esta escribiendo
         */
        setTyping(isTyping) {
            this.collaboration.sendPresenceUpdate({
                typing: isTyping
            });
        }

        /**
         * Actualizar usuarios escribiendo
         */
        updateTypingUsers(typingData) {
            Object.entries(typingData).forEach(([userId, isTyping]) => {
                if (parseInt(userId) === this.collaboration.userId) return;

                if (isTyping) {
                    this.typingUsers.set(parseInt(userId), Date.now());
                } else {
                    this.typingUsers.delete(parseInt(userId));
                }
            });

            // Limpiar usuarios que dejaron de escribir hace mucho
            const now = Date.now();
            this.typingUsers.forEach((timestamp, usrId) => {
                if (now - timestamp > 5000) {
                    this.typingUsers.delete(usrId);
                }
            });

            document.dispatchEvent(new CustomEvent('vbp:chat:typing', {
                detail: { typingUsers: Array.from(this.typingUsers.keys()) }
            }));
        }

        /**
         * Extraer menciones del mensaje
         */
        extractMentions(content) {
            const mentionPattern = /@(\w+)/g;
            const mentions = [];
            let match;

            while ((match = mentionPattern.exec(content)) !== null) {
                const userName = match[1];
                // Buscar usuario por nombre
                const user = Array.from(this.collaboration.remoteUsers.values())
                    .find(u => u.name.toLowerCase().includes(userName.toLowerCase()));
                if (user) {
                    mentions.push(user.id);
                }
            }

            return mentions;
        }

        /**
         * Generar ID de mensaje
         */
        generateMessageId() {
            return `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        }

        /**
         * Abrir chat
         */
        open() {
            this.isOpen = true;
            this.unreadCount = 0;
            document.dispatchEvent(new CustomEvent('vbp:chat:opened'));
        }

        /**
         * Cerrar chat
         */
        close() {
            this.isOpen = false;
            document.dispatchEvent(new CustomEvent('vbp:chat:closed'));
        }

        /**
         * Obtener mensajes
         */
        getMessages() {
            return [...this.messages];
        }
    }

    /**
     * Sistema de comentarios anclados a elementos
     */
    class ElementComments {
        constructor(collaboration) {
            this.collaboration = collaboration;
            this.comments = new Map(); // elementId -> comments[]
            this.threads = new Map();  // commentId -> replies[]
        }

        /**
         * Crear comentario en elemento
         */
        async createComment(elementId, content, position = null) {
            const comment = {
                id: this.generateCommentId(),
                elementId,
                userId: this.collaboration.userId,
                userName: this.collaboration.userName,
                userColor: this.collaboration.userColor,
                userAvatar: this.collaboration.userAvatar,
                content: content.trim(),
                position,
                resolved: false,
                timestamp: Date.now(),
                replies: []
            };

            // Agregar localmente
            this.addComment(elementId, comment);

            // Sincronizar con servidor
            try {
                await this.collaboration.apiRequest('POST', '/realtime/comments/create', {
                    post_id: this.collaboration.postId,
                    comment
                });
                return comment;
            } catch (error) {
                console.error('[VBP Comments] Error creando comentario:', error);
                return null;
            }
        }

        /**
         * Responder a comentario
         */
        async replyToComment(commentId, content) {
            const reply = {
                id: this.generateCommentId(),
                parentId: commentId,
                userId: this.collaboration.userId,
                userName: this.collaboration.userName,
                userColor: this.collaboration.userColor,
                userAvatar: this.collaboration.userAvatar,
                content: content.trim(),
                timestamp: Date.now()
            };

            // Agregar localmente
            const parentComment = this.findComment(commentId);
            if (parentComment) {
                parentComment.replies.push(reply);
            }

            // Sincronizar
            try {
                await this.collaboration.apiRequest('POST', '/realtime/comments/reply', {
                    post_id: this.collaboration.postId,
                    comment_id: commentId,
                    reply
                });
                return reply;
            } catch (error) {
                console.error('[VBP Comments] Error respondiendo:', error);
                return null;
            }
        }

        /**
         * Resolver comentario
         */
        async resolveComment(commentId) {
            const comment = this.findComment(commentId);
            if (comment) {
                comment.resolved = true;
                comment.resolvedBy = this.collaboration.userId;
                comment.resolvedAt = Date.now();
            }

            try {
                await this.collaboration.apiRequest('POST', '/realtime/comments/resolve', {
                    post_id: this.collaboration.postId,
                    comment_id: commentId
                });
            } catch (error) {
                console.error('[VBP Comments] Error resolviendo:', error);
            }
        }

        /**
         * Agregar comentario
         */
        addComment(elementId, comment) {
            if (!this.comments.has(elementId)) {
                this.comments.set(elementId, []);
            }
            this.comments.get(elementId).push(comment);

            document.dispatchEvent(new CustomEvent('vbp:comment:added', {
                detail: { elementId, comment }
            }));
        }

        /**
         * Buscar comentario por ID
         */
        findComment(commentId) {
            for (const elementComments of this.comments.values()) {
                const comment = elementComments.find(c => c.id === commentId);
                if (comment) return comment;
            }
            return null;
        }

        /**
         * Obtener comentarios de elemento
         */
        getCommentsForElement(elementId) {
            return this.comments.get(elementId) || [];
        }

        /**
         * Obtener todos los comentarios
         */
        getAllComments() {
            const allComments = [];
            this.comments.forEach((elementComments, elemId) => {
                elementComments.forEach(comment => {
                    allComments.push({ ...comment, elementId: elemId });
                });
            });
            return allComments.sort((a, b) => b.timestamp - a.timestamp);
        }

        /**
         * Generar ID de comentario
         */
        generateCommentId() {
            return `cmt_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        }
    }

    /**
     * Sistema de awareness mejorado
     */
    class AwarenessSystem {
        constructor(collaboration) {
            this.collaboration = collaboration;
            this.userStates = new Map();
            this.lastActivityTime = Date.now();
            this.currentStatus = USER_STATUS.ACTIVE;
            this.activityCheckInterval = null;
        }

        /**
         * Iniciar monitoreo de actividad
         */
        start() {
            // Detectar actividad del usuario
            ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'].forEach(eventType => {
                document.addEventListener(eventType, () => this.recordActivity(), { passive: true });
            });

            // Verificar estado periodicamente
            this.activityCheckInterval = setInterval(() => this.checkActivityStatus(), 30000);

            // Detectar visibilidad de pagina
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.setStatus(USER_STATUS.AWAY);
                } else {
                    this.setStatus(USER_STATUS.ACTIVE);
                }
            });
        }

        /**
         * Detener monitoreo
         */
        stop() {
            if (this.activityCheckInterval) {
                clearInterval(this.activityCheckInterval);
            }
        }

        /**
         * Registrar actividad
         */
        recordActivity() {
            this.lastActivityTime = Date.now();
            if (this.currentStatus !== USER_STATUS.ACTIVE) {
                this.setStatus(USER_STATUS.ACTIVE);
            }
        }

        /**
         * Verificar estado de actividad
         */
        checkActivityStatus() {
            const now = Date.now();
            const inactiveTime = now - this.lastActivityTime;

            if (inactiveTime > DEFAULT_CONFIG.awayTimeout) {
                this.setStatus(USER_STATUS.AWAY);
            } else if (inactiveTime > DEFAULT_CONFIG.idleTimeout) {
                this.setStatus(USER_STATUS.IDLE);
            }
        }

        /**
         * Establecer estado
         */
        setStatus(status) {
            if (this.currentStatus !== status) {
                this.currentStatus = status;
                this.broadcastAwareness();
            }
        }

        /**
         * Obtener estado completo del usuario actual
         */
        getLocalAwareness() {
            const vbpStore = Alpine.store('vbp');

            return {
                userId: this.collaboration.userId,
                name: this.collaboration.userName,
                avatar: this.collaboration.userAvatar,
                color: this.collaboration.userColor,
                status: this.currentStatus,
                cursor: this.collaboration.lastCursorPosition,
                viewport: this.getViewport(),
                selection: vbpStore?.selectedElements || [],
                editingProperty: vbpStore?.editingProperty || null,
                activeTool: vbpStore?.activeTool || 'select',
                activePanel: vbpStore?.activePanel || null,
                lastActivity: this.lastActivityTime
            };
        }

        /**
         * Obtener viewport actual
         */
        getViewport() {
            const canvas = document.querySelector('.vbp-canvas-container');
            const scrollContainer = canvas?.querySelector('.vbp-canvas-scroll');

            return {
                x: scrollContainer?.scrollLeft || 0,
                y: scrollContainer?.scrollTop || 0,
                zoom: Alpine.store('vbp')?.zoom || 1,
                width: canvas?.clientWidth || window.innerWidth,
                height: canvas?.clientHeight || window.innerHeight
            };
        }

        /**
         * Broadcast awareness
         */
        broadcastAwareness() {
            const awareness = this.getLocalAwareness();
            this.collaboration.sendPresenceUpdate({ awareness });
        }

        /**
         * Actualizar awareness de usuarios remotos
         */
        updateRemoteAwareness(userId, awareness) {
            const existingState = this.userStates.get(userId) || {};
            this.userStates.set(userId, { ...existingState, ...awareness });

            document.dispatchEvent(new CustomEvent('vbp:awareness:updated', {
                detail: { userId, awareness: this.userStates.get(userId) }
            }));
        }

        /**
         * Obtener awareness de un usuario
         */
        getUserAwareness(userId) {
            return this.userStates.get(userId);
        }

        /**
         * Obtener todos los estados de awareness
         */
        getAllAwareness() {
            return new Map(this.userStates);
        }
    }

    /**
     * Sistema de following (seguir a otro usuario)
     */
    class FollowingSystem {
        constructor(collaboration) {
            this.collaboration = collaboration;
            this.followingUserId = null;
            this.followers = new Set();
        }

        /**
         * Seguir a un usuario
         */
        follow(userId) {
            if (userId === this.collaboration.userId) return;

            this.followingUserId = userId;

            // Notificar al usuario que esta siendo seguido
            this.collaboration.sendPresenceUpdate({
                following: userId
            });

            document.dispatchEvent(new CustomEvent('vbp:follow:started', {
                detail: { userId }
            }));

            // Sincronizar viewport con el usuario seguido
            this.syncWithFollowedUser();
        }

        /**
         * Dejar de seguir
         */
        unfollow() {
            const previousUserId = this.followingUserId;
            this.followingUserId = null;

            this.collaboration.sendPresenceUpdate({
                following: null
            });

            document.dispatchEvent(new CustomEvent('vbp:follow:stopped', {
                detail: { userId: previousUserId }
            }));
        }

        /**
         * Sincronizar con usuario seguido
         */
        syncWithFollowedUser() {
            if (!this.followingUserId) return;

            const awareness = this.collaboration.awareness.getUserAwareness(this.followingUserId);
            if (awareness?.viewport) {
                this.scrollToViewport(awareness.viewport);
            }
        }

        /**
         * Scroll al viewport indicado
         */
        scrollToViewport(viewport) {
            const scrollContainer = document.querySelector('.vbp-canvas-scroll');
            if (!scrollContainer) return;

            scrollContainer.scrollTo({
                left: viewport.x,
                top: viewport.y,
                behavior: 'smooth'
            });

            // Sincronizar zoom si es diferente
            const vbpStore = Alpine.store('vbp');
            if (vbpStore && vbpStore.zoom !== viewport.zoom) {
                vbpStore.setZoom(viewport.zoom);
            }
        }

        /**
         * Actualizar lista de seguidores
         */
        updateFollowers(followersList) {
            this.followers = new Set(followersList);

            document.dispatchEvent(new CustomEvent('vbp:followers:updated', {
                detail: { followers: Array.from(this.followers) }
            }));
        }

        /**
         * Verificar si esta siendo seguido
         */
        isBeingFollowed() {
            return this.followers.size > 0;
        }

        /**
         * Obtener IDs de seguidores
         */
        getFollowers() {
            return Array.from(this.followers);
        }
    }

    /**
     * Clase principal de colaboracion en tiempo real (mejorada)
     */
    class VBPRealtimeCollaboration {
        constructor(options = {}) {
            this.config = { ...DEFAULT_CONFIG, ...options };
            this.postId = 0;
            this.userId = 0;
            this.userName = '';
            this.userAvatar = '';
            this.userColor = '#3b82f6';
            this.enabled = false;
            this.connected = false;
            this.connecting = false;
            this.lastSyncTimestamp = 0;
            this.reconnectAttempts = 0;
            this.heartbeatSupported = false;

            // Estado de colaboracion
            this.remoteUsers = new Map();
            this.remoteCursors = new Map();
            this.remoteSelections = new Map();
            this.elementLocks = new Map();
            this.ownedLocks = new Set();
            this.pendingChanges = [];

            // Sistemas avanzados
            this.cursorInterpolator = new CursorInterpolator();
            this.conflictResolver = new ConflictResolver();
            this.chat = new RealtimeChat(this);
            this.comments = new ElementComments(this);
            this.awareness = new AwarenessSystem(this);
            this.following = new FollowingSystem(this);

            // Timers y handlers
            this.cursorThrottleTimer = null;
            this.lockRenewInterval = null;
            this.pollTimeout = null;
            this.syncDebounceTimer = null;
            this.lastCursorPosition = { x: 0, y: 0 };
            this.lastCursorVelocity = { x: 0, y: 0 };
            this.lastCursorTime = 0;

            // Bind methods
            this.handleMouseMove = this.handleMouseMove.bind(this);
            this.handleSelectionChange = this.handleSelectionChange.bind(this);
            this.handleHeartbeat = this.handleHeartbeat.bind(this);
            this.handleBeforeUnload = this.handleBeforeUnload.bind(this);
            this.handleElementChange = this.handleElementChange.bind(this);
            this.handleCursorInterpolated = this.handleCursorInterpolated.bind(this);

            // Detectar soporte de Heartbeat
            this.heartbeatSupported = typeof wp !== 'undefined' && wp.heartbeat;

            this.init();
        }

        /**
         * Inicializar el sistema
         */
        init() {
            // Esperar a que Alpine este listo
            document.addEventListener('alpine:init', () => {
                this.registerStore();
            });

            // Si Alpine ya esta inicializado
            if (Alpine.store && typeof Alpine.store === 'function') {
                this.registerStore();
            }

            // Escuchar eventos de interpolacion de cursor
            document.addEventListener('vbp:cursor:interpolated', this.handleCursorInterpolated);
        }

        /**
         * Registrar el store de Alpine.js
         */
        registerStore() {
            const self = this;

            Alpine.store('vbpRealtime', {
                enabled: false,
                connected: false,
                connecting: false,
                users: [],
                locks: {},
                pendingChanges: [],
                ownColor: '#3b82f6',
                lastError: null,
                chatUnread: 0,
                conflicts: [],
                followingUser: null,
                followers: [],

                // Getters
                get activeUsers() {
                    return this.users.filter(user => user.id !== self.userId);
                },

                get hasCollaborators() {
                    return this.activeUsers.length > 0;
                },

                get lockedElements() {
                    return Object.keys(this.locks).filter(elementId => {
                        const lock = this.locks[elementId];
                        return lock && lock.locked_by !== self.userId;
                    });
                },

                // Metodos publicos
                connect(postId) {
                    return self.connect(postId);
                },

                disconnect() {
                    return self.disconnect();
                },

                broadcastCursor(position) {
                    self.updateCursor(position);
                },

                broadcastSelection(elementIds) {
                    self.updateSelection(elementIds);
                },

                requestLock(elementId) {
                    return self.requestLock(elementId);
                },

                releaseLock(elementId) {
                    return self.releaseLock(elementId);
                },

                syncChanges(changes) {
                    self.queueChanges(changes);
                },

                isElementLocked(elementId) {
                    return self.isElementLocked(elementId);
                },

                getLockOwner(elementId) {
                    return self.getLockOwner(elementId);
                },

                canEditElement(elementId) {
                    return self.canEditElement(elementId);
                },

                // Chat
                sendChatMessage(content, options) {
                    return self.chat.sendMessage(content, options);
                },

                getChatMessages() {
                    return self.chat.getMessages();
                },

                addReaction(messageId, emoji) {
                    return self.chat.addReaction(messageId, emoji);
                },

                setTyping(isTyping) {
                    self.chat.setTyping(isTyping);
                },

                openChat() {
                    self.chat.open();
                },

                closeChat() {
                    self.chat.close();
                },

                // Comentarios
                createComment(elementId, content, position) {
                    return self.comments.createComment(elementId, content, position);
                },

                replyToComment(commentId, content) {
                    return self.comments.replyToComment(commentId, content);
                },

                resolveComment(commentId) {
                    return self.comments.resolveComment(commentId);
                },

                getCommentsForElement(elementId) {
                    return self.comments.getCommentsForElement(elementId);
                },

                getAllComments() {
                    return self.comments.getAllComments();
                },

                // Following
                followUser(userId) {
                    self.following.follow(userId);
                    this.followingUser = userId;
                },

                unfollowUser() {
                    self.following.unfollow();
                    this.followingUser = null;
                },

                // Awareness
                getUserAwareness(userId) {
                    return self.awareness.getUserAwareness(userId);
                },

                getAllAwareness() {
                    return self.awareness.getAllAwareness();
                }
            });

            // Escuchar eventos del store VBP principal
            this.setupStoreListeners();
        }

        /**
         * Configurar listeners del store VBP
         */
        setupStoreListeners() {
            // Escuchar cambios de seleccion
            document.addEventListener('vbp:selection:changed', (event) => {
                if (this.connected) {
                    this.updateSelection(event.detail?.elementIds || []);
                }
            });

            // Escuchar cambios de elementos
            document.addEventListener('vbp:element:updated', (event) => {
                if (this.connected && event.detail) {
                    this.handleElementChange(event.detail);
                }
            });

            document.addEventListener('vbp:element:added', (event) => {
                if (this.connected && event.detail) {
                    this.handleElementChange({ type: 'add', ...event.detail });
                }
            });

            document.addEventListener('vbp:element:removed', (event) => {
                if (this.connected && event.detail) {
                    this.handleElementChange({ type: 'remove', ...event.detail });
                }
            });

            // Escuchar cambios de panel activo
            document.addEventListener('vbp:panel:changed', () => {
                if (this.connected) {
                    this.awareness.broadcastAwareness();
                }
            });

            // Escuchar cambios de herramienta
            document.addEventListener('vbp:tool:changed', () => {
                if (this.connected) {
                    this.awareness.broadcastAwareness();
                }
            });
        }

        /**
         * Conectar a la sesion de colaboracion
         */
        async connect(postId) {
            if (this.connected || this.connecting) {
                return;
            }

            this.postId = postId;
            this.connecting = true;
            this.updateStoreState({ connecting: true });

            try {
                // Obtener datos del usuario actual
                if (typeof VBP_Config !== 'undefined') {
                    this.userId = VBP_Config.userId || 0;
                    this.userName = VBP_Config.userName || 'Usuario';
                    this.userAvatar = VBP_Config.userAvatar || '';
                }

                // Unirse a la sesion
                const response = await this.apiRequest('POST', '/realtime/join', {
                    post_id: this.postId,
                    awareness: this.awareness.getLocalAwareness()
                });

                if (response.success) {
                    this.connected = true;
                    this.userColor = response.color || '#3b82f6';
                    this.lastSyncTimestamp = Math.floor(Date.now() / 1000);
                    this.reconnectAttempts = 0;

                    // Actualizar estado
                    this.processUsersUpdate(response.users || []);
                    this.processLocksUpdate(response.locks || {});

                    // Cargar mensajes de chat existentes
                    if (response.chat_messages) {
                        response.chat_messages.forEach(msg => this.chat.addMessage(msg));
                    }

                    // Cargar comentarios existentes
                    if (response.comments) {
                        Object.entries(response.comments).forEach(([elemId, elementComments]) => {
                            elementComments.forEach(comment => this.comments.addComment(elemId, comment));
                        });
                    }

                    // Configurar listeners
                    this.setupEventListeners();

                    // Iniciar sistemas
                    this.awareness.start();

                    // Iniciar heartbeat o polling
                    if (this.heartbeatSupported) {
                        this.setupHeartbeat();
                    } else {
                        this.startPolling();
                    }

                    // Iniciar renovacion de locks
                    this.startLockRenewal();

                    this.updateStoreState({
                        enabled: true,
                        connected: true,
                        connecting: false,
                        ownColor: this.userColor
                    });

                    this.showToast('Colaboracion activada', 'success');
                    console.log('[VBP Realtime] Conectado a sesion:', this.postId);
                } else {
                    throw new Error(response.message || 'Error al conectar');
                }
            } catch (error) {
                console.error('[VBP Realtime] Error de conexion:', error);
                this.updateStoreState({
                    connecting: false,
                    lastError: error.message
                });
                this.handleConnectionError();
            }
        }

        /**
         * Desconectar de la sesion
         */
        async disconnect() {
            if (!this.connected) {
                return;
            }

            // Limpiar timers y sistemas
            this.cleanup();
            this.awareness.stop();
            this.cursorInterpolator.clear();

            // Notificar al servidor
            const leaveData = JSON.stringify({
                post_id: this.postId,
                user_id: this.userId
            });

            if (navigator.sendBeacon) {
                const blob = new Blob([leaveData], { type: 'application/json' });
                navigator.sendBeacon(
                    VBP_Config.restUrl + 'realtime/leave',
                    blob
                );
            } else {
                try {
                    await this.apiRequest('POST', '/realtime/leave', {
                        post_id: this.postId,
                        user_id: this.userId
                    });
                } catch (error) {
                    // Ignorar errores al desconectar
                }
            }

            this.connected = false;
            this.remoteUsers.clear();
            this.remoteCursors.clear();
            this.remoteSelections.clear();
            this.elementLocks.clear();
            this.ownedLocks.clear();

            this.updateStoreState({
                enabled: false,
                connected: false,
                users: [],
                locks: {},
                followingUser: null,
                followers: []
            });

            // Limpiar UI
            this.clearRemoteCursors();
            this.clearLockIndicators();

            console.log('[VBP Realtime] Desconectado');
        }

        /**
         * Configurar event listeners
         */
        setupEventListeners() {
            // Movimiento del cursor
            const canvas = document.querySelector('.vbp-canvas-container');
            if (canvas) {
                canvas.addEventListener('mousemove', this.handleMouseMove);
            }

            // Antes de cerrar la pagina
            window.addEventListener('beforeunload', this.handleBeforeUnload);

            // Visibilidad de pagina
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseUpdates();
                } else {
                    this.resumeUpdates();
                }
            });
        }

        /**
         * Limpiar listeners y timers
         */
        cleanup() {
            const canvas = document.querySelector('.vbp-canvas-container');
            if (canvas) {
                canvas.removeEventListener('mousemove', this.handleMouseMove);
            }

            window.removeEventListener('beforeunload', this.handleBeforeUnload);

            if (this.cursorThrottleTimer) {
                clearTimeout(this.cursorThrottleTimer);
            }

            if (this.lockRenewInterval) {
                clearInterval(this.lockRenewInterval);
            }

            if (this.pollTimeout) {
                clearTimeout(this.pollTimeout);
            }

            if (this.syncDebounceTimer) {
                clearTimeout(this.syncDebounceTimer);
            }

            // Remover heartbeat listener
            if (this.heartbeatSupported) {
                jQuery(document).off('heartbeat-tick.vbpRealtime');
                jQuery(document).off('heartbeat-send.vbpRealtime');
            }
        }

        /**
         * Manejar movimiento del mouse con calculo de velocidad
         */
        handleMouseMove(event) {
            if (!this.connected) return;

            const currentTime = performance.now();

            // Throttle basado en requestAnimationFrame para 60fps
            if (this.cursorThrottleTimer) return;

            this.cursorThrottleTimer = requestAnimationFrame(() => {
                this.cursorThrottleTimer = null;
            });

            const canvas = event.currentTarget;
            const rect = canvas.getBoundingClientRect();
            const scrollContainer = canvas.querySelector('.vbp-canvas-scroll');
            const scrollLeft = scrollContainer ? scrollContainer.scrollLeft : 0;
            const scrollTop = scrollContainer ? scrollContainer.scrollTop : 0;

            const position = {
                x: event.clientX - rect.left + scrollLeft,
                y: event.clientY - rect.top + scrollTop
            };

            // Calcular velocidad
            const deltaTime = currentTime - this.lastCursorTime;
            if (deltaTime > 0 && this.lastCursorTime > 0) {
                this.lastCursorVelocity = {
                    x: (position.x - this.lastCursorPosition.x) / deltaTime * 1000,
                    y: (position.y - this.lastCursorPosition.y) / deltaTime * 1000
                };
            }

            // Solo actualizar si hay movimiento significativo
            const deltaX = Math.abs(position.x - this.lastCursorPosition.x);
            const deltaY = Math.abs(position.y - this.lastCursorPosition.y);

            if (deltaX > 2 || deltaY > 2) {
                this.lastCursorPosition = position;
                this.lastCursorTime = currentTime;
                this.updateCursor(position, this.lastCursorVelocity);
            }
        }

        /**
         * Manejar evento de cursor interpolado
         */
        handleCursorInterpolated(event) {
            const { userId, position } = event.detail;
            this.renderRemoteCursorAtPosition(userId, position);
        }

        /**
         * Manejar cambio de seleccion
         */
        handleSelectionChange(elementIds) {
            if (!this.connected) return;
            this.updateSelection(elementIds);
        }

        /**
         * Manejar cambios de elementos con CRDT
         */
        handleElementChange(detail) {
            if (!this.connected) return;

            // Crear operacion CRDT
            const operationType = detail.type === 'add' ? OPERATION_TYPES.INSERT :
                                  detail.type === 'remove' ? OPERATION_TYPES.DELETE :
                                  OPERATION_TYPES.SET;

            const path = ['elements', detail.id || detail.element?.id];
            if (detail.property) {
                path.push('props', detail.property);
            }

            const operation = this.conflictResolver.createOperation(
                operationType,
                path,
                detail.changes || detail.value,
                this.userId
            );

            const change = {
                type: detail.type || 'update',
                element_id: detail.id || detail.element?.id,
                changes: detail.changes,
                operation,
                timestamp: Date.now()
            };

            this.queueChanges([change]);
        }

        /**
         * Manejar antes de cerrar pagina
         */
        handleBeforeUnload(event) {
            this.disconnect();
        }

        // ============================================
        // CURSOR Y SELECCION
        // ============================================

        /**
         * Actualizar posicion del cursor con velocidad
         */
        updateCursor(position, velocity = { x: 0, y: 0 }) {
            if (!this.connected) return;

            const cursorData = {
                ...position,
                velocity,
                timestamp: Date.now()
            };

            // Enviar via Heartbeat o REST
            if (this.heartbeatSupported) {
                this.pendingCursorUpdate = cursorData;
            } else {
                this.sendPresenceUpdate({ cursor: cursorData });
            }
        }

        /**
         * Actualizar seleccion
         */
        updateSelection(elementIds) {
            if (!this.connected) return;

            const selectionArray = Array.isArray(elementIds) ? elementIds : [];

            if (this.heartbeatSupported) {
                this.pendingSelectionUpdate = selectionArray;
            } else {
                this.sendPresenceUpdate({ selection: selectionArray });
            }
        }

        /**
         * Enviar actualizacion de presencia al servidor
         */
        async sendPresenceUpdate(data) {
            try {
                await this.apiRequest('POST', '/realtime/presence', {
                    post_id: this.postId,
                    ...data
                });
            } catch (error) {
                console.warn('[VBP Realtime] Error actualizando presencia:', error);
            }
        }

        // ============================================
        // LOCKS
        // ============================================

        /**
         * Solicitar lock de un elemento
         */
        async requestLock(elementId) {
            if (!this.connected || !elementId) return false;

            try {
                const response = await this.apiRequest('POST', '/realtime/lock', {
                    post_id: this.postId,
                    element_id: elementId
                });

                if (response.success) {
                    this.ownedLocks.add(elementId);
                    this.elementLocks.set(elementId, response.lock);
                    this.updateLocksState();
                    return true;
                } else {
                    this.showToast(response.message || 'No se pudo bloquear el elemento', 'warning');
                    return false;
                }
            } catch (error) {
                console.error('[VBP Realtime] Error solicitando lock:', error);
                return false;
            }
        }

        /**
         * Liberar lock de un elemento
         */
        async releaseLock(elementId) {
            if (!this.connected || !elementId) return;

            try {
                await this.apiRequest('POST', '/realtime/unlock', {
                    post_id: this.postId,
                    element_id: elementId
                });

                this.ownedLocks.delete(elementId);
                this.elementLocks.delete(elementId);
                this.updateLocksState();
            } catch (error) {
                console.error('[VBP Realtime] Error liberando lock:', error);
            }
        }

        /**
         * Verificar si un elemento esta bloqueado por otro usuario
         */
        isElementLocked(elementId) {
            const lock = this.elementLocks.get(elementId);
            if (!lock) return false;

            if (lock.locked_by === this.userId) return false;

            if (lock.expires_at && lock.expires_at < Math.floor(Date.now() / 1000)) {
                this.elementLocks.delete(elementId);
                return false;
            }

            return true;
        }

        /**
         * Obtener informacion del dueno del lock
         */
        getLockOwner(elementId) {
            const lock = this.elementLocks.get(elementId);
            if (!lock || lock.locked_by === this.userId) return null;

            return {
                id: lock.locked_by,
                name: lock.locked_by_name,
                color: lock.locked_by_color
            };
        }

        /**
         * Verificar si el usuario puede editar un elemento
         */
        canEditElement(elementId) {
            if (!this.connected) return true;

            const lock = this.elementLocks.get(elementId);
            if (!lock) return true;
            if (lock.locked_by === this.userId) return true;

            if (lock.expires_at && lock.expires_at < Math.floor(Date.now() / 1000)) {
                this.elementLocks.delete(elementId);
                return true;
            }

            return false;
        }

        /**
         * Iniciar renovacion periodica de locks
         */
        startLockRenewal() {
            this.lockRenewInterval = setInterval(() => {
                this.renewOwnedLocks();
            }, this.config.lockRenewInterval);
        }

        /**
         * Renovar locks propios
         */
        async renewOwnedLocks() {
            if (!this.connected || this.ownedLocks.size === 0) return;

            if (!this.heartbeatSupported) {
                for (const elementId of this.ownedLocks) {
                    try {
                        await this.apiRequest('POST', '/realtime/lock', {
                            post_id: this.postId,
                            element_id: elementId
                        });
                    } catch (error) {
                        console.warn('[VBP Realtime] Error renovando lock:', elementId);
                    }
                }
            }
        }

        // ============================================
        // SINCRONIZACION DE CAMBIOS
        // ============================================

        /**
         * Encolar cambios para sincronizacion
         */
        queueChanges(changes) {
            this.pendingChanges.push(...changes);

            // Debounce de sincronizacion
            if (this.syncDebounceTimer) {
                clearTimeout(this.syncDebounceTimer);
            }

            this.syncDebounceTimer = setTimeout(() => {
                this.flushChanges();
            }, this.config.syncDebounce);
        }

        /**
         * Enviar cambios pendientes
         */
        async flushChanges() {
            if (!this.connected || this.pendingChanges.length === 0) return;

            const changesToSend = [...this.pendingChanges];
            this.pendingChanges = [];

            try {
                const response = await this.apiRequest('POST', '/realtime/sync', {
                    post_id: this.postId,
                    changes: changesToSend,
                    last_sync: this.lastSyncTimestamp
                });

                if (response.success) {
                    this.lastSyncTimestamp = response.timestamp;
                    this.processRemoteChanges(response.remote_changes || []);
                    this.processUsersUpdate(response.users || []);
                    this.processLocksUpdate(response.locks || {});

                    // Procesar chat y awareness
                    if (response.chat_messages) {
                        response.chat_messages.forEach(msg => {
                            if (!this.chat.messages.find(m => m.id === msg.id)) {
                                this.chat.addMessage(msg);
                            }
                        });
                    }

                    if (response.typing) {
                        this.chat.updateTypingUsers(response.typing);
                    }
                }
            } catch (error) {
                console.error('[VBP Realtime] Error sincronizando cambios:', error);
                // Re-encolar cambios fallidos
                this.pendingChanges = [...changesToSend, ...this.pendingChanges];
            }
        }

        /**
         * Procesar cambios remotos con CRDT
         */
        processRemoteChanges(changes) {
            if (!changes || changes.length === 0) return;

            const store = Alpine.store('vbp');
            if (!store) return;

            changes.forEach(entry => {
                const change = entry.change;
                if (!change) return;

                // Aplicar con resolucion de conflictos
                if (change.operation) {
                    const result = this.conflictResolver.applyRemoteOperation(change.operation);

                    if (result.conflict) {
                        // Notificar conflicto
                        this.updateStoreState({
                            conflicts: [...(Alpine.store('vbpRealtime').conflicts || []), result.conflictEvent]
                        });
                    }

                    if (!result.applied) {
                        console.log('[VBP Realtime] Operacion descartada por conflicto:', change.operation);
                        return;
                    }
                }

                // Disparar evento para que el canvas se actualice
                document.dispatchEvent(new CustomEvent('vbp:remote:change', {
                    detail: {
                        userId: entry.user_id,
                        change: change,
                        timestamp: entry.timestamp
                    }
                }));
            });
        }

        // ============================================
        // HEARTBEAT
        // ============================================

        /**
         * Configurar integracion con WordPress Heartbeat
         */
        setupHeartbeat() {
            if (!this.heartbeatSupported) return;

            const self = this;

            // Enviar datos en heartbeat
            jQuery(document).on('heartbeat-send.vbpRealtime', function(event, data) {
                if (!self.connected) return;

                data.vbp_realtime = {
                    post_id: self.postId,
                    cursor: self.pendingCursorUpdate,
                    selection: self.pendingSelectionUpdate,
                    changes: self.pendingChanges,
                    active_locks: Array.from(self.ownedLocks),
                    awareness: self.awareness.getLocalAwareness(),
                    following: self.following.followingUserId,
                    last_sync: self.lastSyncTimestamp
                };

                // Limpiar datos pendientes
                self.pendingCursorUpdate = null;
                self.pendingSelectionUpdate = null;
                self.pendingChanges = [];
            });

            // Recibir datos de heartbeat
            jQuery(document).on('heartbeat-tick.vbpRealtime', function(event, data) {
                if (!self.connected || !data.vbp_realtime) return;

                const realtimeData = data.vbp_realtime;
                self.lastSyncTimestamp = realtimeData.timestamp || self.lastSyncTimestamp;

                self.processUsersUpdate(realtimeData.users || []);
                self.processCursorsUpdate(realtimeData.cursors || {});
                self.processSelectionsUpdate(realtimeData.selections || {});
                self.processLocksUpdate(realtimeData.locks || {});
                self.processRemoteChanges(realtimeData.remote_changes || []);

                // Procesar awareness
                if (realtimeData.awareness) {
                    Object.entries(realtimeData.awareness).forEach(([usrId, awareness]) => {
                        self.awareness.updateRemoteAwareness(parseInt(usrId), awareness);
                    });
                }

                // Procesar following
                if (realtimeData.followers) {
                    self.following.updateFollowers(realtimeData.followers);
                    self.updateStoreState({ followers: realtimeData.followers });
                }

                // Sincronizar con usuario seguido
                if (self.following.followingUserId) {
                    self.following.syncWithFollowedUser();
                }

                // Chat
                if (realtimeData.chat_messages) {
                    realtimeData.chat_messages.forEach(msg => {
                        if (!self.chat.messages.find(m => m.id === msg.id)) {
                            self.chat.addMessage(msg);
                        }
                    });
                    self.updateStoreState({ chatUnread: self.chat.unreadCount });
                }

                if (realtimeData.typing) {
                    self.chat.updateTypingUsers(realtimeData.typing);
                }
            });

            // Aumentar frecuencia de heartbeat
            wp.heartbeat.interval('fast');
        }

        // ============================================
        // LONG POLLING (FALLBACK)
        // ============================================

        /**
         * Iniciar polling
         */
        startPolling() {
            if (this.heartbeatSupported) return;
            this.poll();
        }

        /**
         * Ejecutar poll
         */
        async poll() {
            if (!this.connected || this.heartbeatSupported) return;

            try {
                const response = await this.apiRequest('GET', `/realtime/poll/${this.postId}`, {
                    last_sync: this.lastSyncTimestamp,
                    timeout: 15
                });

                if (response.success) {
                    this.lastSyncTimestamp = response.timestamp;
                    this.processUsersUpdate(response.users || []);
                    this.processCursorsUpdate(response.cursors || {});
                    this.processSelectionsUpdate(response.selections || {});
                    this.processLocksUpdate(response.locks || {});
                    this.processRemoteChanges(response.remote_changes || []);

                    // Awareness
                    if (response.awareness) {
                        Object.entries(response.awareness).forEach(([usrId, awareness]) => {
                            this.awareness.updateRemoteAwareness(parseInt(usrId), awareness);
                        });
                    }
                }

                // Programar siguiente poll
                this.pollTimeout = setTimeout(() => this.poll(), 100);
            } catch (error) {
                console.warn('[VBP Realtime] Error en polling:', error);
                this.pollTimeout = setTimeout(() => this.poll(), this.config.reconnectDelay);
            }
        }

        // ============================================
        // PROCESAMIENTO DE DATOS
        // ============================================

        /**
         * Procesar actualizacion de usuarios
         */
        processUsersUpdate(users) {
            const previousUsers = new Set(this.remoteUsers.keys());
            const currentUsers = new Set();

            users.forEach(user => {
                if (user.id === this.userId) return;

                currentUsers.add(user.id);

                const existingUser = this.remoteUsers.get(user.id);
                if (!existingUser) {
                    this.remoteUsers.set(user.id, user);
                    this.showUserJoined(user);
                } else {
                    this.remoteUsers.set(user.id, { ...existingUser, ...user });
                }
            });

            // Detectar usuarios que se fueron
            previousUsers.forEach(usrId => {
                if (!currentUsers.has(usrId)) {
                    const user = this.remoteUsers.get(usrId);
                    this.remoteUsers.delete(usrId);
                    this.remoteCursors.delete(usrId);
                    this.remoteSelections.delete(usrId);
                    this.cursorInterpolator.removeCursor(usrId);
                    this.showUserLeft(user);
                    this.removeRemoteCursor(usrId);
                }
            });

            this.updateStoreState({
                users: Array.from(this.remoteUsers.values())
            });
        }

        /**
         * Procesar actualizacion de cursores con interpolacion
         */
        processCursorsUpdate(cursors) {
            Object.entries(cursors).forEach(([usrId, cursorData]) => {
                const numericUserId = parseInt(usrId, 10);
                if (numericUserId === this.userId) return;

                const position = { x: cursorData.x, y: cursorData.y };
                const velocity = cursorData.velocity || { x: 0, y: 0 };

                this.remoteCursors.set(numericUserId, cursorData);

                // Usar interpolador para movimiento suave
                this.cursorInterpolator.updateTarget(numericUserId, position, velocity);
            });
        }

        /**
         * Procesar actualizacion de selecciones
         */
        processSelectionsUpdate(selections) {
            Object.entries(selections).forEach(([usrId, selection]) => {
                const numericUserId = parseInt(usrId, 10);
                if (numericUserId === this.userId) return;

                this.remoteSelections.set(numericUserId, selection);
                this.renderRemoteSelection(numericUserId, selection);
            });
        }

        /**
         * Procesar actualizacion de locks
         */
        processLocksUpdate(locks) {
            const currentLockIds = new Set(Object.keys(locks));
            this.elementLocks.forEach((lock, elementId) => {
                if (!currentLockIds.has(elementId) && lock.locked_by !== this.userId) {
                    this.elementLocks.delete(elementId);
                }
            });

            Object.entries(locks).forEach(([elementId, lock]) => {
                this.elementLocks.set(elementId, lock);
            });

            this.updateLocksState();
            this.renderLockIndicators();
        }

        // ============================================
        // RENDERIZADO DE UI
        // ============================================

        /**
         * Renderizar cursor remoto en posicion interpolada
         */
        renderRemoteCursorAtPosition(userId, position) {
            const user = this.remoteUsers.get(userId);
            if (!user) return;

            let cursorElement = document.getElementById(`vbp-remote-cursor-${userId}`);
            let container = document.querySelector('.vbp-remote-cursors');

            if (!container) {
                const canvasContainer = document.querySelector('.vbp-canvas-container');
                if (!canvasContainer) return;

                container = document.createElement('div');
                container.className = 'vbp-remote-cursors';
                canvasContainer.appendChild(container);
            }

            if (!cursorElement) {
                cursorElement = document.createElement('div');
                cursorElement.id = `vbp-remote-cursor-${userId}`;
                cursorElement.className = 'vbp-remote-cursor vbp-cursor-animated';
                cursorElement.innerHTML = `
                    <svg class="vbp-remote-cursor-svg" width="20" height="20" viewBox="0 0 20 20">
                        <path d="M0 0 L0 14 L4 10 L7 16 L9 15 L6 9 L11 9 Z"
                              fill="${user.color}"
                              stroke="white"
                              stroke-width="1"/>
                    </svg>
                    <div class="vbp-remote-cursor-label" style="background: ${user.color}">
                        ${user.name}
                        <span class="vbp-cursor-status" data-status="${user.status || 'active'}"></span>
                    </div>
                `;
                container.appendChild(cursorElement);

                // Animar entrada
                requestAnimationFrame(() => {
                    cursorElement.classList.add('vbp-cursor-visible');
                });
            }

            // Actualizar posicion sin transition (ya interpolado)
            cursorElement.style.transform = `translate(${position.x}px, ${position.y}px)`;

            // Actualizar estado del usuario
            const statusBadge = cursorElement.querySelector('.vbp-cursor-status');
            if (statusBadge) {
                statusBadge.dataset.status = user.status || 'active';
            }
        }

        /**
         * Eliminar cursor remoto
         */
        removeRemoteCursor(userId) {
            const cursorElement = document.getElementById(`vbp-remote-cursor-${userId}`);
            if (cursorElement) {
                cursorElement.classList.remove('vbp-cursor-visible');
                cursorElement.classList.add('vbp-cursor-leaving');
                setTimeout(() => cursorElement.remove(), 300);
            }
        }

        /**
         * Limpiar todos los cursores remotos
         */
        clearRemoteCursors() {
            const container = document.querySelector('.vbp-remote-cursors');
            if (container) {
                container.innerHTML = '';
            }
            this.cursorInterpolator.clear();
        }

        /**
         * Renderizar seleccion remota
         */
        renderRemoteSelection(userId, elementIds) {
            // Limpiar selecciones anteriores de este usuario
            document.querySelectorAll(`.vbp-remote-selection-${userId}`).forEach(el => {
                el.classList.remove(`vbp-remote-selection-${userId}`, 'vbp-remote-selected');
                el.querySelector('.vbp-selection-badge')?.remove();
            });

            const user = this.remoteUsers.get(userId);
            if (!user || !elementIds || elementIds.length === 0) return;

            // Aplicar nuevas selecciones
            elementIds.forEach(elementId => {
                const element = document.querySelector(`[data-element-id="${elementId}"]`);
                if (element) {
                    element.classList.add('vbp-remote-selected', `vbp-remote-selection-${userId}`);
                    element.style.setProperty('--remote-selection-color', user.color);

                    // Agregar badge con nombre de usuario
                    const badge = document.createElement('div');
                    badge.className = 'vbp-selection-badge';
                    badge.style.backgroundColor = user.color;
                    badge.innerHTML = `
                        <img src="${user.avatar}" alt="${user.name}" class="vbp-selection-badge-avatar">
                        <span>${user.name}</span>
                    `;
                    element.appendChild(badge);
                }
            });
        }

        /**
         * Renderizar indicadores de lock
         */
        renderLockIndicators() {
            // Limpiar indicadores anteriores
            document.querySelectorAll('.vbp-element-locked').forEach(el => {
                el.classList.remove('vbp-element-locked');
                el.removeAttribute('data-locked-by');
                el.querySelector('.vbp-lock-indicator')?.remove();
            });

            // Aplicar nuevos indicadores
            this.elementLocks.forEach((lock, elementId) => {
                if (lock.locked_by === this.userId) return;

                const element = document.querySelector(`[data-element-id="${elementId}"]`);
                if (element) {
                    element.classList.add('vbp-element-locked');
                    element.setAttribute('data-locked-by', lock.locked_by_name);
                    element.style.setProperty('--lock-color', lock.locked_by_color);

                    // Agregar indicador visual de lock
                    const indicator = document.createElement('div');
                    indicator.className = 'vbp-lock-indicator';
                    indicator.style.setProperty('--lock-color', lock.locked_by_color);
                    indicator.innerHTML = `
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1a3 3 0 0 0-3 3v2H4a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-1V4a3 3 0 0 0-3-3zm2 5H6V4a2 2 0 1 1 4 0v2z"/>
                        </svg>
                        <span>${lock.locked_by_name}</span>
                    `;
                    element.appendChild(indicator);
                }
            });
        }

        /**
         * Limpiar indicadores de lock
         */
        clearLockIndicators() {
            document.querySelectorAll('.vbp-element-locked').forEach(el => {
                el.classList.remove('vbp-element-locked');
                el.removeAttribute('data-locked-by');
                el.querySelector('.vbp-lock-indicator')?.remove();
            });
        }

        // ============================================
        // NOTIFICACIONES
        // ============================================

        /**
         * Mostrar toast de usuario que se unio
         */
        showUserJoined(user) {
            this.showToast(`${user.name} se unio a la edicion`, 'info', {
                avatar: user.avatar,
                color: user.color
            });
        }

        /**
         * Mostrar toast de usuario que se fue
         */
        showUserLeft(user) {
            if (!user) return;
            this.showToast(`${user.name} salio de la edicion`, 'info', {
                avatar: user.avatar,
                color: user.color
            });
        }

        /**
         * Mostrar toast
         */
        showToast(message, type = 'info', options = {}) {
            if (typeof window.VBPToast !== 'undefined') {
                window.VBPToast.show(message, type, options);
            } else {
                console.log(`[VBP Realtime] ${type}: ${message}`);
            }
        }

        // ============================================
        // HELPERS
        // ============================================

        /**
         * Realizar peticion a la API
         */
        async apiRequest(method, endpoint, data = null) {
            const url = VBP_Config.restUrl + endpoint.replace(/^\//, '');
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            };

            if (data) {
                if (method === 'GET') {
                    const params = new URLSearchParams(data);
                    options.url = url + '?' + params.toString();
                } else {
                    options.body = JSON.stringify(data);
                }
            }

            const response = await fetch(method === 'GET' && data ? url + '?' + new URLSearchParams(data) : url, options);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return response.json();
        }

        /**
         * Actualizar estado del store
         */
        updateStoreState(state) {
            const store = Alpine.store('vbpRealtime');
            if (store) {
                Object.assign(store, state);
            }
        }

        /**
         * Actualizar estado de locks en el store
         */
        updateLocksState() {
            const locksObject = {};
            this.elementLocks.forEach((lock, elementId) => {
                locksObject[elementId] = lock;
            });

            this.updateStoreState({ locks: locksObject });
        }

        /**
         * Manejar error de conexion
         */
        handleConnectionError() {
            this.reconnectAttempts++;

            if (this.reconnectAttempts <= this.config.maxReconnectAttempts) {
                console.log(`[VBP Realtime] Reintentando conexion (${this.reconnectAttempts}/${this.config.maxReconnectAttempts})`);
                setTimeout(() => {
                    this.connect(this.postId);
                }, this.config.reconnectDelay);
            } else {
                this.showToast('No se pudo conectar a la colaboracion', 'error');
            }
        }

        /**
         * Pausar actualizaciones (cuando la pagina no es visible)
         */
        pauseUpdates() {
            if (this.pollTimeout) {
                clearTimeout(this.pollTimeout);
            }
            this.awareness.setStatus(USER_STATUS.AWAY);
        }

        /**
         * Resumir actualizaciones
         */
        resumeUpdates() {
            this.awareness.setStatus(USER_STATUS.ACTIVE);
            if (this.connected && !this.heartbeatSupported) {
                this.poll();
            }
        }
    }

    // Crear instancia global
    window.VBPRealtimeCollaboration = new VBPRealtimeCollaboration();

    // Exponer para debugging
    if (window.VBP_DEBUG) {
        window.vbpRealtime = window.VBPRealtimeCollaboration;
    }

})();

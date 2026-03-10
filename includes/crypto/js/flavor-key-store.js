/**
 * Flavor Key Store - Almacenamiento seguro de claves en IndexedDB
 *
 * Almacena claves criptográficas de forma segura en el navegador.
 * Los datos se cifran con una clave derivada del usuario.
 *
 * @package FlavorChatIA
 */

(function(global) {
    'use strict';

    const DB_NAME = 'FlavorE2EKeyStore';
    const DB_VERSION = 1;

    /**
     * Almacén de claves usando IndexedDB
     */
    class FlavorKeyStore {
        /**
         * Constructor
         * @param {number} userId - ID del usuario
         */
        constructor(userId) {
            this.userId = userId;
            this.db = null;
            this.encryptionKey = null;
            this.dbName = `${DB_NAME}_${userId}`;
        }

        /**
         * Inicializa el almacén
         * @returns {Promise<void>}
         */
        async initialize() {
            // Derivar clave de cifrado para este almacén
            this.encryptionKey = await this.derivarClaveAlmacen();

            // Abrir/crear base de datos
            this.db = await this.abrirDB();
        }

        /**
         * Deriva una clave para cifrar el almacén local
         * @returns {Promise<CryptoKey>}
         */
        async derivarClaveAlmacen() {
            // Usar una clave basada en el userId y una sal fija del dominio
            const encoder = new TextEncoder();
            const material = encoder.encode(`FlavorE2E_Store_${this.userId}_${window.location.origin}`);

            const keyMaterial = await window.crypto.subtle.importKey(
                'raw',
                material,
                { name: 'PBKDF2' },
                false,
                ['deriveBits', 'deriveKey']
            );

            return await window.crypto.subtle.deriveKey(
                {
                    name: 'PBKDF2',
                    salt: encoder.encode('FlavorE2ELocalSalt'),
                    iterations: 100000,
                    hash: 'SHA-256'
                },
                keyMaterial,
                { name: 'AES-GCM', length: 256 },
                false,
                ['encrypt', 'decrypt']
            );
        }

        /**
         * Abre o crea la base de datos IndexedDB
         * @returns {Promise<IDBDatabase>}
         */
        abrirDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(this.dbName, DB_VERSION);

                request.onerror = () => {
                    reject(new Error('Error al abrir IndexedDB'));
                };

                request.onsuccess = () => {
                    resolve(request.result);
                };

                request.onupgradeneeded = (event) => {
                    const database = event.target.result;

                    // Almacén de identidad (1 registro por usuario)
                    if (!database.objectStoreNames.contains('identity')) {
                        database.createObjectStore('identity', { keyPath: 'id' });
                    }

                    // Almacén de sesiones
                    if (!database.objectStoreNames.contains('sessions')) {
                        const sessionsStore = database.createObjectStore('sessions', { keyPath: 'peerId' });
                        sessionsStore.createIndex('lastUsed', 'lastUsed', { unique: false });
                    }

                    // Almacén de signed prekeys
                    if (!database.objectStoreNames.contains('signedPrekeys')) {
                        database.createObjectStore('signedPrekeys', { keyPath: 'id' });
                    }

                    // Almacén de one-time prekeys
                    if (!database.objectStoreNames.contains('oneTimePrekeys')) {
                        database.createObjectStore('oneTimePrekeys', { keyPath: 'id' });
                    }

                    // Almacén de claves de mensajes saltados
                    if (!database.objectStoreNames.contains('skippedKeys')) {
                        const skippedStore = database.createObjectStore('skippedKeys', { keyPath: 'id', autoIncrement: true });
                        skippedStore.createIndex('sessionId', 'sessionId', { unique: false });
                    }
                };
            });
        }

        /**
         * Cifra datos para almacenamiento
         * @param {*} data - Datos a cifrar
         * @returns {Promise<{iv: Uint8Array, ciphertext: ArrayBuffer}>}
         */
        async cifrarDatos(data) {
            const encoder = new TextEncoder();
            const plaintext = encoder.encode(JSON.stringify(data));

            const iv = new Uint8Array(12);
            window.crypto.getRandomValues(iv);

            const ciphertext = await window.crypto.subtle.encrypt(
                { name: 'AES-GCM', iv: iv },
                this.encryptionKey,
                plaintext
            );

            return {
                iv: Array.from(iv),
                ciphertext: Array.from(new Uint8Array(ciphertext))
            };
        }

        /**
         * Descifra datos almacenados
         * @param {{iv: number[], ciphertext: number[]}} encrypted
         * @returns {Promise<*>}
         */
        async descifrarDatos(encrypted) {
            const iv = new Uint8Array(encrypted.iv);
            const ciphertext = new Uint8Array(encrypted.ciphertext);

            const plaintext = await window.crypto.subtle.decrypt(
                { name: 'AES-GCM', iv: iv },
                this.encryptionKey,
                ciphertext
            );

            const decoder = new TextDecoder();
            return JSON.parse(decoder.decode(plaintext));
        }

        /**
         * Ejecuta una transacción en un object store
         * @param {string} storeName
         * @param {string} mode
         * @returns {IDBObjectStore}
         */
        getStore(storeName, mode = 'readonly') {
            const transaction = this.db.transaction(storeName, mode);
            return transaction.objectStore(storeName);
        }

        /**
         * Promisifica una operación IDB
         * @param {IDBRequest} request
         * @returns {Promise<*>}
         */
        promisifyRequest(request) {
            return new Promise((resolve, reject) => {
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }

        // ========================================
        // IDENTIDAD
        // ========================================

        /**
         * Guarda la identidad del usuario
         * @param {Object} identidad
         * @returns {Promise<void>}
         */
        async guardarIdentidad(identidad) {
            const store = this.getStore('identity', 'readwrite');

            // Serializar las claves para almacenamiento
            const datosSerializados = {
                dispositivoId: identidad.dispositivoId,
                fingerprint: identidad.fingerprint,
                publicKey: Array.from(new Uint8Array(identidad.keyPair.publicKey)),
                privateKey: identidad.keyPair.privateKey // JWK ya es serializable
            };

            const datosCifrados = await this.cifrarDatos(datosSerializados);

            await this.promisifyRequest(
                store.put({
                    id: 'current',
                    data: datosCifrados,
                    updatedAt: Date.now()
                })
            );
        }

        /**
         * Obtiene la identidad del usuario
         * @returns {Promise<Object|null>}
         */
        async obtenerIdentidad() {
            const store = this.getStore('identity');
            const result = await this.promisifyRequest(store.get('current'));

            if (!result) {
                return null;
            }

            try {
                const datos = await this.descifrarDatos(result.data);

                // Reimportar las claves
                const publicKey = new Uint8Array(datos.publicKey).buffer;
                const privateKey = await window.crypto.subtle.importKey(
                    'jwk',
                    datos.privateKey,
                    { name: 'ECDH', namedCurve: 'P-256' },
                    true,
                    ['deriveBits']
                );

                return {
                    dispositivoId: datos.dispositivoId,
                    fingerprint: datos.fingerprint,
                    keyPair: {
                        publicKey: publicKey,
                        privateKey: privateKey
                    }
                };
            } catch (error) {
                console.error('Error al descifrar identidad:', error);
                return null;
            }
        }

        // ========================================
        // SESIONES
        // ========================================

        /**
         * Guarda el estado de una sesión
         * @param {number} peerId - ID del usuario remoto
         * @param {Object} sesion - Estado de la sesión
         * @returns {Promise<void>}
         */
        async guardarSesion(peerId, sesion) {
            const store = this.getStore('sessions', 'readwrite');

            // Serializar el estado de la sesión
            const sesionSerializada = await this.serializarSesion(sesion);
            const datosCifrados = await this.cifrarDatos(sesionSerializada);

            await this.promisifyRequest(
                store.put({
                    peerId: peerId,
                    data: datosCifrados,
                    lastUsed: Date.now()
                })
            );
        }

        /**
         * Obtiene el estado de una sesión
         * @param {number} peerId
         * @returns {Promise<Object|null>}
         */
        async obtenerSesion(peerId) {
            const store = this.getStore('sessions');
            const result = await this.promisifyRequest(store.get(peerId));

            if (!result) {
                return null;
            }

            try {
                const datos = await this.descifrarDatos(result.data);
                return await this.deserializarSesion(datos);
            } catch (error) {
                console.error('Error al descifrar sesión:', error);
                return null;
            }
        }

        /**
         * Elimina una sesión
         * @param {number} peerId
         * @returns {Promise<void>}
         */
        async eliminarSesion(peerId) {
            const store = this.getStore('sessions', 'readwrite');
            await this.promisifyRequest(store.delete(peerId));
        }

        /**
         * Serializa una sesión para almacenamiento
         * @param {Object} sesion
         * @returns {Promise<Object>}
         */
        async serializarSesion(sesion) {
            const resultado = {
                dispositivoRemotoId: sesion.dispositivoRemotoId,
                rootKey: sesion.rootKey ? Array.from(sesion.rootKey) : null,
                chainKeySend: sesion.chainKeySend ? Array.from(sesion.chainKeySend) : null,
                chainKeyRecv: sesion.chainKeyRecv ? Array.from(sesion.chainKeyRecv) : null,
                dhRemotePublic: sesion.dhRemotePublic ? Array.from(sesion.dhRemotePublic) : null,
                dhRatchetPublic: sesion.dhRatchetPublic ? Array.from(new Uint8Array(sesion.dhRatchetPublic)) : null,
                messageNumberSend: sesion.messageNumberSend,
                messageNumberRecv: sesion.messageNumberRecv,
                previousChainLength: sesion.previousChainLength,
                skippedKeys: sesion.skippedKeys || []
            };

            // Exportar par de claves DH si existe
            if (sesion.dhRatchetKeyPair) {
                resultado.dhRatchetPrivateKey = await window.crypto.subtle.exportKey(
                    'jwk',
                    sesion.dhRatchetKeyPair.privateKey
                );
            }

            return resultado;
        }

        /**
         * Deserializa una sesión desde almacenamiento
         * @param {Object} datos
         * @returns {Promise<Object>}
         */
        async deserializarSesion(datos) {
            const sesion = {
                dispositivoRemotoId: datos.dispositivoRemotoId,
                rootKey: datos.rootKey ? new Uint8Array(datos.rootKey) : null,
                chainKeySend: datos.chainKeySend ? new Uint8Array(datos.chainKeySend) : null,
                chainKeyRecv: datos.chainKeyRecv ? new Uint8Array(datos.chainKeyRecv) : null,
                dhRemotePublic: datos.dhRemotePublic ? new Uint8Array(datos.dhRemotePublic) : null,
                dhRatchetPublic: datos.dhRatchetPublic ? new Uint8Array(datos.dhRatchetPublic).buffer : null,
                messageNumberSend: datos.messageNumberSend,
                messageNumberRecv: datos.messageNumberRecv,
                previousChainLength: datos.previousChainLength,
                skippedKeys: datos.skippedKeys || []
            };

            // Reimportar par de claves DH si existe
            if (datos.dhRatchetPrivateKey) {
                const privateKey = await window.crypto.subtle.importKey(
                    'jwk',
                    datos.dhRatchetPrivateKey,
                    { name: 'ECDH', namedCurve: 'P-256' },
                    true,
                    ['deriveBits']
                );

                sesion.dhRatchetKeyPair = {
                    privateKey: privateKey,
                    publicKey: null // Se regenerará desde dhRatchetPublic si es necesario
                };
            }

            return sesion;
        }

        // ========================================
        // SIGNED PREKEYS
        // ========================================

        /**
         * Guarda una Signed PreKey
         * @param {Object} prekey
         * @returns {Promise<void>}
         */
        async guardarSignedPrekey(prekey) {
            const store = this.getStore('signedPrekeys', 'readwrite');

            const prekeySerializada = {
                id: prekey.id,
                publicKey: await window.crypto.subtle.exportKey('raw', prekey.keyPair.publicKey),
                privateKey: await window.crypto.subtle.exportKey('jwk', prekey.keyPair.privateKey),
                createdAt: prekey.createdAt
            };

            const datosCifrados = await this.cifrarDatos(prekeySerializada);

            await this.promisifyRequest(
                store.put({
                    id: prekey.id,
                    data: datosCifrados,
                    createdAt: prekey.createdAt
                })
            );
        }

        /**
         * Obtiene la Signed PreKey actual
         * @returns {Promise<Object|null>}
         */
        async obtenerSignedPrekeyActual() {
            const store = this.getStore('signedPrekeys');

            return new Promise((resolve, reject) => {
                const request = store.openCursor(null, 'prev');
                request.onsuccess = async (event) => {
                    const cursor = event.target.result;
                    if (cursor) {
                        try {
                            const datos = await this.descifrarDatos(cursor.value.data);

                            const publicKey = await window.crypto.subtle.importKey(
                                'raw',
                                new Uint8Array(Object.values(datos.publicKey)),
                                { name: 'ECDH', namedCurve: 'P-256' },
                                true,
                                []
                            );

                            const privateKey = await window.crypto.subtle.importKey(
                                'jwk',
                                datos.privateKey,
                                { name: 'ECDH', namedCurve: 'P-256' },
                                true,
                                ['deriveBits']
                            );

                            resolve({
                                id: datos.id,
                                keyPair: { publicKey, privateKey },
                                createdAt: datos.createdAt
                            });
                        } catch (error) {
                            reject(error);
                        }
                    } else {
                        resolve(null);
                    }
                };
                request.onerror = () => reject(request.error);
            });
        }

        // ========================================
        // ONE-TIME PREKEYS
        // ========================================

        /**
         * Guarda una One-Time PreKey
         * @param {Object} prekey
         * @returns {Promise<void>}
         */
        async guardarOneTimePrekey(prekey) {
            const store = this.getStore('oneTimePrekeys', 'readwrite');

            const prekeySerializada = {
                id: prekey.id,
                publicKey: await window.crypto.subtle.exportKey('raw', prekey.keyPair.publicKey),
                privateKey: await window.crypto.subtle.exportKey('jwk', prekey.keyPair.privateKey)
            };

            const datosCifrados = await this.cifrarDatos(prekeySerializada);

            await this.promisifyRequest(
                store.put({
                    id: prekey.id,
                    data: datosCifrados
                })
            );
        }

        /**
         * Obtiene una One-Time PreKey por ID
         * @param {number} id
         * @returns {Promise<Object|null>}
         */
        async obtenerOneTimePrekey(id) {
            const store = this.getStore('oneTimePrekeys');
            const result = await this.promisifyRequest(store.get(id));

            if (!result) {
                return null;
            }

            try {
                const datos = await this.descifrarDatos(result.data);

                const publicKey = await window.crypto.subtle.importKey(
                    'raw',
                    new Uint8Array(Object.values(datos.publicKey)),
                    { name: 'ECDH', namedCurve: 'P-256' },
                    true,
                    []
                );

                const privateKey = await window.crypto.subtle.importKey(
                    'jwk',
                    datos.privateKey,
                    { name: 'ECDH', namedCurve: 'P-256' },
                    true,
                    ['deriveBits']
                );

                return {
                    id: datos.id,
                    keyPair: { publicKey, privateKey }
                };
            } catch (error) {
                console.error('Error al descifrar one-time prekey:', error);
                return null;
            }
        }

        /**
         * Elimina una One-Time PreKey (después de usarla)
         * @param {number} id
         * @returns {Promise<void>}
         */
        async eliminarOneTimePrekey(id) {
            const store = this.getStore('oneTimePrekeys', 'readwrite');
            await this.promisifyRequest(store.delete(id));
        }

        /**
         * Cuenta las One-Time PreKeys disponibles
         * @returns {Promise<number>}
         */
        async contarOneTimePrekeys() {
            const store = this.getStore('oneTimePrekeys');
            return await this.promisifyRequest(store.count());
        }

        // ========================================
        // CLAVES SALTADAS
        // ========================================

        /**
         * Guarda una clave de mensaje saltada
         * @param {number} sessionId
         * @param {Object} claveInfo
         * @returns {Promise<void>}
         */
        async guardarClaveSaltada(sessionId, claveInfo) {
            const store = this.getStore('skippedKeys', 'readwrite');

            const datosCifrados = await this.cifrarDatos({
                dh: claveInfo.dh,
                n: claveInfo.n,
                key: Array.from(claveInfo.key)
            });

            await this.promisifyRequest(
                store.add({
                    sessionId: sessionId,
                    data: datosCifrados,
                    createdAt: Date.now()
                })
            );
        }

        /**
         * Busca y consume una clave saltada
         * @param {number} sessionId
         * @param {string} dh
         * @param {number} n
         * @returns {Promise<Uint8Array|null>}
         */
        async buscarClaveSaltada(sessionId, dh, n) {
            const store = this.getStore('skippedKeys', 'readwrite');
            const index = store.index('sessionId');

            return new Promise((resolve, reject) => {
                const request = index.openCursor(IDBKeyRange.only(sessionId));
                request.onsuccess = async (event) => {
                    const cursor = event.target.result;
                    if (cursor) {
                        try {
                            const datos = await this.descifrarDatos(cursor.value.data);
                            if (datos.dh === dh && datos.n === n) {
                                // Encontrada, eliminar y devolver
                                cursor.delete();
                                resolve(new Uint8Array(datos.key));
                                return;
                            }
                        } catch (error) {
                            // Continuar buscando
                        }
                        cursor.continue();
                    } else {
                        resolve(null);
                    }
                };
                request.onerror = () => reject(request.error);
            });
        }

        // ========================================
        // LIMPIEZA
        // ========================================

        /**
         * Limpia todos los datos del almacén
         * @returns {Promise<void>}
         */
        async limpiarTodo() {
            const stores = ['identity', 'sessions', 'signedPrekeys', 'oneTimePrekeys', 'skippedKeys'];

            for (const storeName of stores) {
                const store = this.getStore(storeName, 'readwrite');
                await this.promisifyRequest(store.clear());
            }
        }

        /**
         * Elimina la base de datos completa
         * @returns {Promise<void>}
         */
        async eliminarDB() {
            this.db.close();
            await new Promise((resolve, reject) => {
                const request = indexedDB.deleteDatabase(this.dbName);
                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        }

        /**
         * Exporta todos los datos (para backup)
         * @returns {Promise<Object>}
         */
        async exportarDatos() {
            const datos = {};
            const stores = ['identity', 'sessions', 'signedPrekeys', 'oneTimePrekeys'];

            for (const storeName of stores) {
                const store = this.getStore(storeName);
                datos[storeName] = await this.promisifyRequest(store.getAll());
            }

            return datos;
        }
    }

    // Exponer globalmente
    global.FlavorKeyStore = FlavorKeyStore;

})(typeof window !== 'undefined' ? window : global);

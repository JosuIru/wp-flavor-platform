/**
 * Flavor Signal Protocol - Cliente JavaScript
 *
 * Implementación del protocolo Signal (X3DH + Double Ratchet) usando Web Crypto API.
 * Este archivo maneja el cifrado/descifrado de mensajes E2E en el navegador.
 *
 * @package FlavorChatIA
 */

(function(global) {
    'use strict';

    /**
     * Constantes del protocolo
     */
    const PROTOCOL_VERSION = 1;
    const KEY_LENGTH = 32; // 256 bits
    const NONCE_LENGTH = 12;

    /**
     * Clase principal del protocolo Signal
     */
    class FlavorSignalProtocol {
        /**
         * Constructor
         * @param {Object} opciones - Configuración
         * @param {number} opciones.userId - ID del usuario actual
         * @param {string} opciones.apiEndpoint - URL base de la API E2E
         */
        constructor(opciones) {
            this.userId = opciones.userId;
            this.apiEndpoint = opciones.apiEndpoint || '/wp-json/flavor/v1/e2e/';
            this.dispositivoId = null;
            this.identityKeyPair = null;
            this.initialized = false;
            this.keyStore = null;
        }

        /**
         * Inicializa el protocolo
         * @returns {Promise<boolean>}
         */
        async initialize() {
            if (this.initialized) {
                return true;
            }

            // Verificar soporte de Web Crypto
            if (!window.crypto || !window.crypto.subtle) {
                throw new Error('Web Crypto API no disponible');
            }

            // Inicializar almacén de claves
            this.keyStore = new FlavorKeyStore(this.userId);
            await this.keyStore.initialize();

            // Cargar o generar identidad del dispositivo
            await this.cargarOGenerarIdentidad();

            this.initialized = true;
            return true;
        }

        /**
         * Carga la identidad existente o genera una nueva
         * @returns {Promise<void>}
         */
        async cargarOGenerarIdentidad() {
            // Intentar cargar dispositivo existente
            const identidadAlmacenada = await this.keyStore.obtenerIdentidad();

            if (identidadAlmacenada) {
                this.dispositivoId = identidadAlmacenada.dispositivoId;
                this.identityKeyPair = identidadAlmacenada.keyPair;
                return;
            }

            // Verificar estado en el servidor
            const estado = await this.llamarApi('status', 'GET');

            if (estado.usuario_tiene_claves && estado.dispositivos.length > 0) {
                // El usuario tiene claves pero no en este navegador
                // Necesita vincular o restaurar
                throw new Error('NECESITA_VINCULACION');
            }

            // Generar nueva identidad
            await this.generarNuevaIdentidad();
        }

        /**
         * Genera una nueva identidad y la registra en el servidor
         * @returns {Promise<void>}
         */
        async generarNuevaIdentidad() {
            // Generar par de claves de identidad (Ed25519 simulado con ECDSA P-256)
            // Nota: Web Crypto no soporta Ed25519 directamente, usamos ECDH con P-256
            const identityKeyPair = await window.crypto.subtle.generateKey(
                {
                    name: 'ECDH',
                    namedCurve: 'P-256'
                },
                true,
                ['deriveBits']
            );

            // Exportar claves
            const publicKeyRaw = await window.crypto.subtle.exportKey('raw', identityKeyPair.publicKey);
            const privateKeyJwk = await window.crypto.subtle.exportKey('jwk', identityKeyPair.privateKey);

            // Generar ID de dispositivo
            const dispositivoIdBytes = new Uint8Array(32);
            window.crypto.getRandomValues(dispositivoIdBytes);
            const dispositivoId = this.arrayBufferToHex(dispositivoIdBytes);

            // Registrar en el servidor
            const resultado = await this.llamarApi('keys/register', 'POST', {
                dispositivo_id: dispositivoId,
                dispositivo_tipo: this.detectarTipoDispositivo()
            });

            if (!resultado.success) {
                throw new Error('Error al registrar claves en el servidor');
            }

            // Guardar localmente
            this.dispositivoId = dispositivoId;
            this.identityKeyPair = identityKeyPair;

            await this.keyStore.guardarIdentidad({
                dispositivoId: dispositivoId,
                keyPair: {
                    publicKey: publicKeyRaw,
                    privateKey: privateKeyJwk
                },
                fingerprint: resultado.fingerprint
            });

            // Generar prekeys locales
            await this.generarPrekeysFrescas();
        }

        /**
         * Genera prekeys frescas (signed + one-time)
         * @returns {Promise<void>}
         */
        async generarPrekeysFrescas() {
            // Generar Signed PreKey
            const signedPrekey = await this.generarParClaves();
            const signedPrekeyId = Date.now();

            await this.keyStore.guardarSignedPrekey({
                id: signedPrekeyId,
                keyPair: signedPrekey,
                createdAt: Date.now()
            });

            // Generar One-Time PreKeys
            for (let i = 0; i < 100; i++) {
                const onetimePrekey = await this.generarParClaves();
                await this.keyStore.guardarOneTimePrekey({
                    id: i + 1,
                    keyPair: onetimePrekey
                });
            }
        }

        /**
         * Genera un par de claves ECDH
         * @returns {Promise<CryptoKeyPair>}
         */
        async generarParClaves() {
            return await window.crypto.subtle.generateKey(
                {
                    name: 'ECDH',
                    namedCurve: 'P-256'
                },
                true,
                ['deriveBits']
            );
        }

        /**
         * Cifra un mensaje para un usuario
         * @param {number} destinatarioId - ID del usuario destinatario
         * @param {string} mensaje - Mensaje a cifrar
         * @returns {Promise<Object>} - { ciphertext, header, x3dhHeader }
         */
        async encryptMessage(destinatarioId, mensaje) {
            if (!this.initialized) {
                throw new Error('Protocolo no inicializado');
            }

            // Buscar o crear sesión
            let sesion = await this.keyStore.obtenerSesion(destinatarioId);
            let esNuevaSesion = false;
            let x3dhHeader = null;

            if (!sesion) {
                // Crear nueva sesión via X3DH
                const resultado = await this.crearSesionX3DH(destinatarioId);
                sesion = resultado.sesion;
                x3dhHeader = resultado.header;
                esNuevaSesion = true;
            }

            // Cifrar mensaje con el ratchet
            const resultadoCifrado = await this.cifrarConRatchet(sesion, mensaje);

            // Actualizar sesión
            await this.keyStore.guardarSesion(destinatarioId, sesion);

            return {
                ciphertext: resultadoCifrado.ciphertext,
                header: resultadoCifrado.header,
                x3dh_header: esNuevaSesion ? x3dhHeader : null
            };
        }

        /**
         * Cifra un mensaje para TODOS los dispositivos del destinatario
         * @param {number} destinatarioId - ID del usuario destinatario
         * @param {string} mensaje - Mensaje a cifrar
         * @returns {Promise<Array>} - Array de { dispositivo_id, ciphertext, header, x3dh_header }
         */
        async encryptMessageForAllDevices(destinatarioId, mensaje) {
            if (!this.initialized) {
                throw new Error('Protocolo no inicializado');
            }

            // Obtener todos los bundles de dispositivos del destinatario
            const bundleResponse = await this.llamarApi(`prekey-bundle/${destinatarioId}`, 'GET');

            if (!bundleResponse) {
                throw new Error('Usuario no tiene claves E2E configuradas');
            }

            // Normalizar a array de bundles
            const bundles = bundleResponse.bundles || [bundleResponse];
            const resultados = [];

            // Cifrar para cada dispositivo
            for (const bundle of bundles) {
                try {
                    const dispositivoId = bundle.dispositivo_id || 'default';
                    const sessionKey = `${destinatarioId}_${dispositivoId}`;

                    // Buscar o crear sesión para este dispositivo
                    let sesion = await this.keyStore.obtenerSesion(sessionKey);
                    let esNuevaSesion = false;
                    let x3dhHeader = null;

                    if (!sesion) {
                        // Crear nueva sesión via X3DH con este bundle específico
                        const resultado = await this.crearSesionX3DHConBundle(destinatarioId, bundle);
                        sesion = resultado.sesion;
                        x3dhHeader = resultado.header;
                        esNuevaSesion = true;
                    }

                    // Cifrar mensaje con el ratchet
                    const resultadoCifrado = await this.cifrarConRatchet(sesion, mensaje);

                    // Actualizar sesión
                    await this.keyStore.guardarSesion(sessionKey, sesion);

                    resultados.push({
                        dispositivo_id: dispositivoId,
                        ciphertext: resultadoCifrado.ciphertext,
                        header: resultadoCifrado.header,
                        x3dh_header: esNuevaSesion ? x3dhHeader : null
                    });
                } catch (error) {
                    console.warn(`Error cifrando para dispositivo ${bundle.dispositivo_id}:`, error);
                    // Continuar con los demás dispositivos
                }
            }

            if (resultados.length === 0) {
                throw new Error('No se pudo cifrar para ningún dispositivo');
            }

            return resultados;
        }

        /**
         * Crea una nueva sesión usando X3DH con un bundle específico
         * @param {number} destinatarioId
         * @param {Object} bundle - Bundle de prekeys del dispositivo
         * @returns {Promise<Object>}
         */
        async crearSesionX3DHConBundle(destinatarioId, bundle) {
            // Generar par de claves efímeras
            const ephemeralKeyPair = await this.generarParClaves();
            const ephemeralPublic = await window.crypto.subtle.exportKey('raw', ephemeralKeyPair.publicKey);

            // Importar claves del bundle
            const identityKeyRemota = await this.importarClavePublica(
                this.base64UrlDecode(bundle.identity_key.public)
            );
            const signedPrekeyRemota = await this.importarClavePublica(
                this.base64UrlDecode(bundle.signed_prekey.public)
            );

            // Calcular DH compartidos
            const dh1 = await this.realizarDH(this.identityKeyPair.privateKey, signedPrekeyRemota);
            const dh2 = await this.realizarDH(ephemeralKeyPair.privateKey, identityKeyRemota);
            const dh3 = await this.realizarDH(ephemeralKeyPair.privateKey, signedPrekeyRemota);

            let dh4 = new Uint8Array(0);
            let oneTimePrekeyId = null;

            if (bundle.one_time_prekey) {
                const onetimePrekey = await this.importarClavePublica(
                    this.base64UrlDecode(bundle.one_time_prekey.public)
                );
                dh4 = await this.realizarDH(ephemeralKeyPair.privateKey, onetimePrekey);
                oneTimePrekeyId = bundle.one_time_prekey.id;
            }

            // Concatenar DH y derivar secreto compartido
            const dhConcat = this.concatenarArrays([dh1, dh2, dh3, dh4]);
            const sharedSecret = await this.hkdf(dhConcat, 32, 'FlavorX3DH');

            // Crear estado inicial de sesión
            const sesion = {
                rootKey: sharedSecret,
                sendingChain: null,
                receivingChain: null,
                sendingRatchetKey: null,
                receivingRatchetKey: signedPrekeyRemota,
                sendingChainLength: 0,
                receivingChainLength: 0,
                previousSendingChainLength: 0,
                skippedKeys: {},
            };

            // Ejecutar ratchet DH inicial
            await this.ejecutarDHRatchetEnvio(sesion);

            // Construir header X3DH
            const identityPublic = await window.crypto.subtle.exportKey('raw', this.identityKeyPair.publicKey);

            return {
                sesion,
                header: {
                    identity_key: this.arrayBufferToBase64Url(identityPublic),
                    ephemeral_key: this.arrayBufferToBase64Url(ephemeralPublic),
                    one_time_prekey_id: oneTimePrekeyId,
                    dispositivo_id: bundle.dispositivo_id
                }
            };
        }

        /**
         * Crea una nueva sesión usando X3DH
         * @param {number} destinatarioId
         * @returns {Promise<Object>}
         */
        async crearSesionX3DH(destinatarioId) {
            // Obtener PreKey Bundle del servidor
            const bundle = await this.llamarApi(`prekey-bundle/${destinatarioId}`, 'GET');

            if (!bundle || (!bundle.bundles && !bundle.identity_key)) {
                throw new Error('Usuario no tiene claves E2E configuradas');
            }

            // Usar el primer dispositivo disponible
            const targetBundle = bundle.bundles ? bundle.bundles[0] : bundle;

            // Generar par de claves efímeras
            const ephemeralKeyPair = await this.generarParClaves();
            const ephemeralPublic = await window.crypto.subtle.exportKey('raw', ephemeralKeyPair.publicKey);

            // Importar claves del bundle
            const identityKeyRemota = await this.importarClavePublica(
                this.base64UrlDecode(targetBundle.identity_key.public)
            );
            const signedPrekeyRemota = await this.importarClavePublica(
                this.base64UrlDecode(targetBundle.signed_prekey.public)
            );

            // Calcular DH compartidos
            const dh1 = await this.realizarDH(this.identityKeyPair.privateKey, signedPrekeyRemota);
            const dh2 = await this.realizarDH(ephemeralKeyPair.privateKey, identityKeyRemota);
            const dh3 = await this.realizarDH(ephemeralKeyPair.privateKey, signedPrekeyRemota);

            let dh4 = new Uint8Array(0);
            let oneTimePrekeyId = null;

            if (targetBundle.one_time_prekey) {
                const onetimePrekey = await this.importarClavePublica(
                    this.base64UrlDecode(targetBundle.one_time_prekey.public)
                );
                dh4 = await this.realizarDH(ephemeralKeyPair.privateKey, onetimePrekey);
                oneTimePrekeyId = targetBundle.one_time_prekey.id;
            }

            // Concatenar DH y derivar secreto compartido
            const dhConcatenado = this.concatenarBuffers([dh1, dh2, dh3, dh4]);
            const sharedSecret = await this.hkdf(dhConcatenado, KEY_LENGTH, 'FlavorE2E_X3DH');

            // Crear estado inicial del ratchet
            const nuevoRatchetKey = await this.generarParClaves();
            const nuevoRatchetPublic = await window.crypto.subtle.exportKey('raw', nuevoRatchetKey.publicKey);

            const clavesDerived = await this.derivarClaves(sharedSecret, ['rootKey', 'chainKeySend']);

            const sesion = {
                dispositivoRemotoId: targetBundle.dispositivo_id,
                dhRatchetKeyPair: nuevoRatchetKey,
                dhRatchetPublic: nuevoRatchetPublic,
                dhRemotePublic: this.base64UrlDecode(targetBundle.signed_prekey.public),
                rootKey: clavesDerived.rootKey,
                chainKeySend: clavesDerived.chainKeySend,
                chainKeyRecv: null,
                messageNumberSend: 0,
                messageNumberRecv: 0,
                previousChainLength: 0,
                skippedKeys: []
            };

            const header = {
                identity_key: this.base64UrlEncode(
                    await window.crypto.subtle.exportKey('raw', this.identityKeyPair.publicKey)
                ),
                ephemeral_key: this.base64UrlEncode(ephemeralPublic),
                one_time_prekey_id: oneTimePrekeyId
            };

            return { sesion, header };
        }

        /**
         * Cifra un mensaje usando el Double Ratchet
         * @param {Object} sesion - Estado de la sesión
         * @param {string} mensaje - Mensaje a cifrar
         * @returns {Promise<Object>}
         */
        async cifrarConRatchet(sesion, mensaje) {
            // Si no hay chainKeySend, hacer DH ratchet
            if (!sesion.chainKeySend) {
                await this.ejecutarDHRatchetEnvio(sesion);
            }

            // Derivar clave de mensaje
            const clavesMensaje = await this.derivarClaves(sesion.chainKeySend, ['messageKey', 'nextChainKey']);

            // Cifrar mensaje
            const encoder = new TextEncoder();
            const plaintextBytes = encoder.encode(mensaje);

            const nonce = new Uint8Array(NONCE_LENGTH);
            window.crypto.getRandomValues(nonce);

            const claveCifrado = await window.crypto.subtle.importKey(
                'raw',
                clavesMensaje.messageKey,
                { name: 'AES-GCM' },
                false,
                ['encrypt']
            );

            const aad = encoder.encode(JSON.stringify({ n: sesion.messageNumberSend }));

            const ciphertextBytes = await window.crypto.subtle.encrypt(
                {
                    name: 'AES-GCM',
                    iv: nonce,
                    additionalData: aad
                },
                claveCifrado,
                plaintextBytes
            );

            // Construir header
            const header = {
                dh: this.base64UrlEncode(new Uint8Array(sesion.dhRatchetPublic)),
                pn: sesion.previousChainLength,
                n: sesion.messageNumberSend
            };

            // Actualizar estado
            sesion.chainKeySend = clavesMensaje.nextChainKey;
            sesion.messageNumberSend++;

            // Combinar nonce + ciphertext
            const combined = this.concatenarBuffers([nonce, new Uint8Array(ciphertextBytes)]);

            return {
                ciphertext: this.base64UrlEncode(combined),
                header: header
            };
        }

        /**
         * Descifra un mensaje
         * @param {number} remitenteId - ID del usuario remitente
         * @param {string} ciphertext - Texto cifrado (base64)
         * @param {Object} header - Header del mensaje
         * @param {Object} x3dhHeader - Header X3DH (solo para primer mensaje)
         * @returns {Promise<string>}
         */
        async decryptMessage(remitenteId, ciphertext, header, x3dhHeader = null) {
            if (!this.initialized) {
                throw new Error('Protocolo no inicializado');
            }

            let sesion = await this.keyStore.obtenerSesion(remitenteId);

            // Si hay header X3DH, es un mensaje inicial
            if (x3dhHeader && !sesion) {
                sesion = await this.procesarX3DHReceptor(remitenteId, x3dhHeader);
            }

            if (!sesion) {
                throw new Error('No existe sesión con este usuario');
            }

            // Descifrar mensaje
            const mensaje = await this.descifrarConRatchet(sesion, ciphertext, header);

            // Guardar sesión actualizada
            await this.keyStore.guardarSesion(remitenteId, sesion);

            return mensaje;
        }

        /**
         * Procesa un mensaje X3DH como receptor
         * @param {number} remitenteId
         * @param {Object} x3dhHeader
         * @returns {Promise<Object>}
         */
        async procesarX3DHReceptor(remitenteId, x3dhHeader) {
            // Obtener claves locales necesarias
            const signedPrekey = await this.keyStore.obtenerSignedPrekeyActual();
            if (!signedPrekey) {
                throw new Error('No hay Signed PreKey disponible');
            }

            // Importar claves del header
            const identityKeyRemota = await this.importarClavePublica(
                this.base64UrlDecode(x3dhHeader.identity_key)
            );
            const ephemeralKey = await this.importarClavePublica(
                this.base64UrlDecode(x3dhHeader.ephemeral_key)
            );

            // Calcular DH compartidos (orden inverso)
            const dh1 = await this.realizarDH(signedPrekey.keyPair.privateKey, identityKeyRemota);
            const dh2 = await this.realizarDH(this.identityKeyPair.privateKey, ephemeralKey);
            const dh3 = await this.realizarDH(signedPrekey.keyPair.privateKey, ephemeralKey);

            let dh4 = new Uint8Array(0);
            if (x3dhHeader.one_time_prekey_id) {
                const onetimePrekey = await this.keyStore.obtenerOneTimePrekey(x3dhHeader.one_time_prekey_id);
                if (onetimePrekey) {
                    dh4 = await this.realizarDH(onetimePrekey.keyPair.privateKey, ephemeralKey);
                    await this.keyStore.eliminarOneTimePrekey(x3dhHeader.one_time_prekey_id);
                }
            }

            // Derivar secreto compartido
            const dhConcatenado = this.concatenarBuffers([dh1, dh2, dh3, dh4]);
            const sharedSecret = await this.hkdf(dhConcatenado, KEY_LENGTH, 'FlavorE2E_X3DH');

            const clavesDerived = await this.derivarClaves(sharedSecret, ['rootKey', 'chainKeyRecv']);

            // Crear sesión como receptor
            const sesion = {
                dispositivoRemotoId: null, // Se establecerá después
                dhRatchetKeyPair: null, // Se generará al enviar
                dhRatchetPublic: null,
                dhRemotePublic: this.base64UrlDecode(x3dhHeader.ephemeral_key),
                rootKey: clavesDerived.rootKey,
                chainKeySend: null,
                chainKeyRecv: clavesDerived.chainKeyRecv,
                messageNumberSend: 0,
                messageNumberRecv: 0,
                previousChainLength: 0,
                skippedKeys: []
            };

            return sesion;
        }

        /**
         * Descifra un mensaje usando el Double Ratchet
         * @param {Object} sesion
         * @param {string} ciphertextBase64
         * @param {Object} header
         * @returns {Promise<string>}
         */
        async descifrarConRatchet(sesion, ciphertextBase64, header) {
            const ciphertextFull = this.base64UrlDecode(ciphertextBase64);
            const nonce = ciphertextFull.slice(0, NONCE_LENGTH);
            const ciphertextBytes = ciphertextFull.slice(NONCE_LENGTH);

            // Verificar si hay nueva clave DH
            const dhNuevo = this.base64UrlDecode(header.dh);
            const dhActual = sesion.dhRemotePublic || new Uint8Array(0);

            if (!this.arraysIguales(dhNuevo, dhActual)) {
                // Saltar claves si es necesario
                if (sesion.chainKeyRecv) {
                    await this.saltarClavesMensaje(sesion, header.pn);
                }
                // Ejecutar DH ratchet
                await this.ejecutarDHRatchetRecepcion(sesion, dhNuevo);
            }

            // Saltar hasta el mensaje actual
            await this.saltarClavesMensaje(sesion, header.n);

            // Derivar clave de mensaje
            const clavesMensaje = await this.derivarClaves(sesion.chainKeyRecv, ['messageKey', 'nextChainKey']);

            // Descifrar
            const claveCifrado = await window.crypto.subtle.importKey(
                'raw',
                clavesMensaje.messageKey,
                { name: 'AES-GCM' },
                false,
                ['decrypt']
            );

            const encoder = new TextEncoder();
            const aad = encoder.encode(JSON.stringify({ n: header.n }));

            try {
                const plaintextBytes = await window.crypto.subtle.decrypt(
                    {
                        name: 'AES-GCM',
                        iv: nonce,
                        additionalData: aad
                    },
                    claveCifrado,
                    ciphertextBytes
                );

                // Actualizar estado
                sesion.chainKeyRecv = clavesMensaje.nextChainKey;
                sesion.messageNumberRecv = header.n + 1;

                const decoder = new TextDecoder();
                return decoder.decode(plaintextBytes);

            } catch (error) {
                throw new Error('Error al descifrar mensaje');
            }
        }

        /**
         * Ejecuta un paso del DH ratchet para envío
         * @param {Object} sesion
         */
        async ejecutarDHRatchetEnvio(sesion) {
            const nuevoRatchetKey = await this.generarParClaves();
            const nuevoRatchetPublic = await window.crypto.subtle.exportKey('raw', nuevoRatchetKey.publicKey);

            if (sesion.dhRemotePublic) {
                const dhRemoto = await this.importarClavePublica(sesion.dhRemotePublic);
                const dhOutput = await this.realizarDH(nuevoRatchetKey.privateKey, dhRemoto);

                const material = this.concatenarBuffers([sesion.rootKey, dhOutput]);
                const clavesDerived = await this.derivarClaves(material, ['rootKey', 'chainKeySend']);

                sesion.rootKey = clavesDerived.rootKey;
                sesion.chainKeySend = clavesDerived.chainKeySend;
            }

            sesion.previousChainLength = sesion.messageNumberSend;
            sesion.messageNumberSend = 0;
            sesion.dhRatchetKeyPair = nuevoRatchetKey;
            sesion.dhRatchetPublic = nuevoRatchetPublic;
        }

        /**
         * Ejecuta un paso del DH ratchet para recepción
         * @param {Object} sesion
         * @param {Uint8Array} dhRemotoNuevo
         */
        async ejecutarDHRatchetRecepcion(sesion, dhRemotoNuevo) {
            const dhRemotoKey = await this.importarClavePublica(dhRemotoNuevo);

            // Primer ratchet: para recibir
            if (sesion.dhRatchetKeyPair) {
                const dhOutput = await this.realizarDH(sesion.dhRatchetKeyPair.privateKey, dhRemotoKey);
                const material = this.concatenarBuffers([sesion.rootKey, dhOutput]);
                const clavesDerived = await this.derivarClaves(material, ['rootKey', 'chainKeyRecv']);
                sesion.rootKey = clavesDerived.rootKey;
                sesion.chainKeyRecv = clavesDerived.chainKeyRecv;
            }

            // Segundo ratchet: para enviar
            const nuevoRatchetKey = await this.generarParClaves();
            const nuevoRatchetPublic = await window.crypto.subtle.exportKey('raw', nuevoRatchetKey.publicKey);

            const dhOutput = await this.realizarDH(nuevoRatchetKey.privateKey, dhRemotoKey);
            const material = this.concatenarBuffers([sesion.rootKey, dhOutput]);
            const clavesDerived = await this.derivarClaves(material, ['rootKey', 'chainKeySend']);

            sesion.rootKey = clavesDerived.rootKey;
            sesion.chainKeySend = clavesDerived.chainKeySend;
            sesion.dhRatchetKeyPair = nuevoRatchetKey;
            sesion.dhRatchetPublic = nuevoRatchetPublic;
            sesion.dhRemotePublic = dhRemotoNuevo;
            sesion.previousChainLength = sesion.messageNumberRecv;
            sesion.messageNumberRecv = 0;
        }

        /**
         * Salta claves de mensaje para manejar mensajes fuera de orden
         * @param {Object} sesion
         * @param {number} hasta
         */
        async saltarClavesMensaje(sesion, hasta) {
            if (!sesion.chainKeyRecv || sesion.messageNumberRecv >= hasta) {
                return;
            }

            const maxSkip = 1000;
            while (sesion.messageNumberRecv < hasta && sesion.skippedKeys.length < maxSkip) {
                const clavesMensaje = await this.derivarClaves(sesion.chainKeyRecv, ['messageKey', 'nextChainKey']);

                sesion.skippedKeys.push({
                    dh: this.base64UrlEncode(sesion.dhRemotePublic),
                    n: sesion.messageNumberRecv,
                    key: clavesMensaje.messageKey
                });

                sesion.chainKeyRecv = clavesMensaje.nextChainKey;
                sesion.messageNumberRecv++;
            }
        }

        // ========================================
        // UTILIDADES CRIPTOGRÁFICAS
        // ========================================

        /**
         * Realiza un intercambio Diffie-Hellman
         * @param {CryptoKey} privateKey
         * @param {CryptoKey} publicKey
         * @returns {Promise<Uint8Array>}
         */
        async realizarDH(privateKey, publicKey) {
            const sharedBits = await window.crypto.subtle.deriveBits(
                { name: 'ECDH', public: publicKey },
                privateKey,
                256
            );
            return new Uint8Array(sharedBits);
        }

        /**
         * HKDF para derivación de claves
         * @param {Uint8Array} material
         * @param {number} length
         * @param {string} info
         * @returns {Promise<Uint8Array>}
         */
        async hkdf(material, length, info) {
            const encoder = new TextEncoder();
            const baseKey = await window.crypto.subtle.importKey(
                'raw',
                material,
                { name: 'HKDF' },
                false,
                ['deriveBits']
            );

            const derivedBits = await window.crypto.subtle.deriveBits(
                {
                    name: 'HKDF',
                    hash: 'SHA-256',
                    salt: new Uint8Array(32),
                    info: encoder.encode(info)
                },
                baseKey,
                length * 8
            );

            return new Uint8Array(derivedBits);
        }

        /**
         * Deriva múltiples claves de un material
         * @param {Uint8Array} material
         * @param {string[]} nombres
         * @returns {Promise<Object>}
         */
        async derivarClaves(material, nombres) {
            const totalLength = nombres.length * KEY_LENGTH;
            const derivada = await this.hkdf(material, totalLength, 'FlavorE2E_Keys');

            const resultado = {};
            let offset = 0;

            for (const nombre of nombres) {
                resultado[nombre] = derivada.slice(offset, offset + KEY_LENGTH);
                offset += KEY_LENGTH;
            }

            return resultado;
        }

        /**
         * Importa una clave pública ECDH
         * @param {Uint8Array} keyData
         * @returns {Promise<CryptoKey>}
         */
        async importarClavePublica(keyData) {
            return await window.crypto.subtle.importKey(
                'raw',
                keyData,
                { name: 'ECDH', namedCurve: 'P-256' },
                true,
                []
            );
        }

        // ========================================
        // UTILIDADES DE CODIFICACIÓN
        // ========================================

        base64UrlEncode(buffer) {
            const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.length; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            return btoa(binary)
                .replace(/\+/g, '-')
                .replace(/\//g, '_')
                .replace(/=/g, '');
        }

        base64UrlDecode(str) {
            str = str.replace(/-/g, '+').replace(/_/g, '/');
            while (str.length % 4) {
                str += '=';
            }
            const binary = atob(str);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return bytes;
        }

        arrayBufferToHex(buffer) {
            const bytes = new Uint8Array(buffer);
            return Array.from(bytes)
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
        }

        concatenarBuffers(buffers) {
            const totalLength = buffers.reduce((sum, buf) => sum + buf.length, 0);
            const result = new Uint8Array(totalLength);
            let offset = 0;
            for (const buf of buffers) {
                result.set(buf, offset);
                offset += buf.length;
            }
            return result;
        }

        arraysIguales(a, b) {
            if (a.length !== b.length) return false;
            for (let i = 0; i < a.length; i++) {
                if (a[i] !== b[i]) return false;
            }
            return true;
        }

        // ========================================
        // API
        // ========================================

        async llamarApi(endpoint, method = 'GET', data = null) {
            const url = this.apiEndpoint + endpoint;
            const opciones = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.flavorE2E?.nonce || ''
                },
                credentials: 'same-origin'
            };

            if (data && method !== 'GET') {
                opciones.body = JSON.stringify(data);
            }

            const response = await fetch(url, opciones);

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || `Error ${response.status}`);
            }

            return await response.json();
        }

        detectarTipoDispositivo() {
            const ua = navigator.userAgent.toLowerCase();
            if (/android/.test(ua)) return 'android';
            if (/iphone|ipad|ipod/.test(ua)) return 'ios';
            if (/electron/.test(ua)) return 'desktop';
            return 'web';
        }
    }

    // Exponer globalmente
    global.FlavorSignalProtocol = FlavorSignalProtocol;

})(typeof window !== 'undefined' ? window : global);

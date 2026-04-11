/**
 * Visual Builder Pro - Asset Manager
 *
 * Panel centralizado para gestión de medios: Imágenes, SVGs, Videos, Iconos y Unsplash.
 * Integra WordPress Media Library con favoritos, colecciones y drag & drop.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

(function() {
    'use strict';

    /**
     * Configuración del Asset Manager
     */
    var CONFIG = {
        TABS: [
            { id: 'images', label: 'Imágenes', icon: 'image' },
            { id: 'svgs', label: 'SVGs', icon: 'code' },
            { id: 'videos', label: 'Videos', icon: 'videocam' },
            { id: 'icons', label: 'Iconos', icon: 'star' },
            { id: 'unsplash', label: 'Unsplash', icon: 'cloud_download' }
        ],
        ITEMS_PER_PAGE: 24,
        FAVORITES_KEY: 'vbp_asset_favorites',
        RECENT_KEY: 'vbp_asset_recent',
        MAX_RECENT: 20
    };

    /**
     * Asset Manager Component
     */
    window.VBPAssetManager = {
        // Estado del panel
        isOpen: false,
        activeTab: 'images',
        searchQuery: '',
        isLoading: false,

        // Assets cargados
        assets: [],
        totalAssets: 0,
        totalPages: 0,
        currentPage: 1,

        // Favoritos y colecciones
        showFavoritesOnly: false,
        favorites: [],
        collections: [],
        activeCollection: '',

        // Iconos integrados
        iconCategories: [],
        selectedIconCategory: '',

        // Unsplash
        unsplashConfigured: false,
        unsplashImages: [],
        unsplashQuery: '',
        unsplashOrientation: '',
        unsplashPage: 1,
        unsplashTotalPages: 0,

        // Drag & drop
        isDragging: false,
        draggedAsset: null,

        // Selección y callback
        selectedAsset: null,
        onSelectCallback: null,
        targetElement: null,
        targetField: null,

        // Subida
        isUploading: false,
        uploadProgress: 0,

        /**
         * Inicializa el Asset Manager
         */
        init: function() {
            this.loadFavorites();
            this.loadCollections();
            this.checkUnsplashStatus();
            this.setupDragAndDrop();
            this.setupKeyboardShortcuts();
        },

        /**
         * Abre el panel de Asset Manager
         * @param {Object} options - Opciones de apertura
         */
        open: function(options) {
            options = options || {};

            this.isOpen = true;
            this.activeTab = options.tab || 'images';
            this.targetElement = options.targetElement || null;
            this.targetField = options.targetField || 'src';
            this.onSelectCallback = options.onSelect || null;

            // Cargar assets del tab activo
            this.loadAssets();

            // Focus en búsqueda
            var self = this;
            setTimeout(function() {
                var searchInput = document.querySelector('.vbp-am-search-input');
                if (searchInput) {
                    searchInput.focus();
                }
            }, 100);
        },

        /**
         * Cierra el panel
         */
        close: function() {
            this.isOpen = false;
            this.selectedAsset = null;
            this.targetElement = null;
            this.targetField = null;
            this.onSelectCallback = null;
            this.searchQuery = '';
        },

        /**
         * Cambia de tab
         * @param {string} tabId - ID del tab
         */
        switchTab: function(tabId) {
            if (this.activeTab === tabId) return;

            this.activeTab = tabId;
            this.searchQuery = '';
            this.currentPage = 1;
            this.assets = [];
            this.selectedAsset = null;

            if (tabId === 'unsplash') {
                this.unsplashImages = [];
                this.unsplashQuery = '';
            } else if (tabId === 'icons') {
                this.loadIcons();
            } else {
                this.loadAssets();
            }
        },

        /**
         * Carga assets desde la API
         */
        loadAssets: function() {
            if (this.activeTab === 'icons' || this.activeTab === 'unsplash') {
                return;
            }

            var self = this;
            this.isLoading = true;

            var params = new URLSearchParams({
                type: this.activeTab,
                page: this.currentPage,
                per_page: CONFIG.ITEMS_PER_PAGE,
                search: this.searchQuery,
                favorites: this.showFavoritesOnly ? '1' : '',
                collection: this.activeCollection
            });

            fetch(VBP_Config.restUrl + 'assets?' + params.toString(), {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.assets = data.assets || [];
                self.totalAssets = data.total || 0;
                self.totalPages = data.totalPages || 0;
            })
            .catch(function(error) {
                console.error('Error cargando assets:', error);
                self.showNotification('Error cargando medios', 'error');
            })
            .finally(function() {
                self.isLoading = false;
            });
        },

        /**
         * Carga iconos integrados
         */
        loadIcons: function() {
            var self = this;
            this.isLoading = true;

            var params = new URLSearchParams({
                category: this.selectedIconCategory,
                search: this.searchQuery
            });

            fetch(VBP_Config.restUrl + 'assets/icons?' + params.toString(), {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.iconCategories = data.categories || [];
            })
            .catch(function(error) {
                console.error('Error cargando iconos:', error);
            })
            .finally(function() {
                self.isLoading = false;
            });
        },

        /**
         * Busca assets
         */
        search: function() {
            this.currentPage = 1;

            if (this.activeTab === 'unsplash') {
                this.searchUnsplash();
            } else if (this.activeTab === 'icons') {
                this.loadIcons();
            } else {
                this.loadAssets();
            }
        },

        /**
         * Maneja input de búsqueda con debounce
         */
        onSearchInput: function() {
            var self = this;

            if (this._searchTimeout) {
                clearTimeout(this._searchTimeout);
            }

            this._searchTimeout = setTimeout(function() {
                self.search();
            }, 300);
        },

        /**
         * Página siguiente
         */
        nextPage: function() {
            if (this.activeTab === 'unsplash') {
                if (this.unsplashPage < this.unsplashTotalPages) {
                    this.unsplashPage++;
                    this.loadUnsplashPage();
                }
            } else {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.loadAssets();
                }
            }
        },

        /**
         * Página anterior
         */
        prevPage: function() {
            if (this.activeTab === 'unsplash') {
                if (this.unsplashPage > 1) {
                    this.unsplashPage--;
                    this.loadUnsplashPage();
                }
            } else {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.loadAssets();
                }
            }
        },

        // ========================================
        // FAVORITOS
        // ========================================

        /**
         * Carga favoritos desde localStorage
         */
        loadFavorites: function() {
            try {
                var stored = localStorage.getItem(CONFIG.FAVORITES_KEY);
                this.favorites = stored ? JSON.parse(stored) : [];
            } catch (e) {
                this.favorites = [];
            }
        },

        /**
         * Guarda favoritos en localStorage
         */
        saveFavorites: function() {
            try {
                localStorage.setItem(CONFIG.FAVORITES_KEY, JSON.stringify(this.favorites));
            } catch (e) {
                console.warn('No se pudieron guardar favoritos:', e);
            }
        },

        /**
         * Alterna favorito de un asset
         * @param {Object} asset - Asset a marcar/desmarcar
         */
        toggleFavorite: function(asset) {
            var self = this;
            var assetId = asset.id;

            // Actualizar en servidor
            fetch(VBP_Config.restUrl + 'assets/favorites', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({ asset_id: assetId })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    asset.isFavorite = data.isFavorite;

                    // Actualizar array local
                    if (data.isFavorite) {
                        if (self.favorites.indexOf(assetId) === -1) {
                            self.favorites.push(assetId);
                        }
                    } else {
                        self.favorites = self.favorites.filter(function(id) {
                            return id !== assetId;
                        });
                    }

                    self.saveFavorites();
                }
            })
            .catch(function(error) {
                console.error('Error toggling favorite:', error);
            });
        },

        /**
         * Verifica si un asset es favorito
         * @param {number} assetId - ID del asset
         * @returns {boolean}
         */
        isFavorite: function(assetId) {
            return this.favorites.indexOf(assetId) !== -1;
        },

        /**
         * Filtra solo favoritos
         */
        toggleFavoritesFilter: function() {
            this.showFavoritesOnly = !this.showFavoritesOnly;
            this.currentPage = 1;
            this.loadAssets();
        },

        // ========================================
        // COLECCIONES
        // ========================================

        /**
         * Carga colecciones desde la API
         */
        loadCollections: function() {
            var self = this;

            fetch(VBP_Config.restUrl + 'assets/collections', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.collections = data.collections || [];
            })
            .catch(function(error) {
                console.error('Error cargando colecciones:', error);
            });
        },

        /**
         * Crea una nueva colección
         * @param {string} name - Nombre de la colección
         */
        createCollection: function(name) {
            var self = this;

            if (!name || !name.trim()) {
                this.showNotification('Nombre de colección requerido', 'warning');
                return;
            }

            fetch(VBP_Config.restUrl + 'assets/collections', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({ name: name.trim() })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.collection) {
                    self.collections.push(data.collection);
                    self.showNotification('Colección creada', 'success');
                }
            })
            .catch(function(error) {
                console.error('Error creando colección:', error);
                self.showNotification('Error creando colección', 'error');
            });
        },

        /**
         * Añade/quita asset de una colección
         * @param {number} assetId - ID del asset
         * @param {string} collectionId - ID de la colección
         */
        toggleAssetInCollection: function(assetId, collectionId) {
            var self = this;

            fetch(VBP_Config.restUrl + 'assets/collections/' + collectionId + '/assets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({ asset_id: assetId })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    var message = data.inCollection ? 'Añadido a colección' : 'Quitado de colección';
                    self.showNotification(message, 'success');
                }
            })
            .catch(function(error) {
                console.error('Error en colección:', error);
            });
        },

        /**
         * Filtra por colección
         * @param {string} collectionId - ID de la colección
         */
        filterByCollection: function(collectionId) {
            this.activeCollection = collectionId;
            this.currentPage = 1;
            this.loadAssets();
        },

        // ========================================
        // UNSPLASH
        // ========================================

        /**
         * Verifica el estado de Unsplash
         */
        checkUnsplashStatus: function() {
            var self = this;

            fetch(VBP_Config.restUrl + 'unsplash/status', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.unsplashConfigured = data.configured || false;
            })
            .catch(function() {
                self.unsplashConfigured = false;
            });
        },

        /**
         * Busca en Unsplash
         */
        searchUnsplash: function() {
            if (!this.unsplashQuery.trim()) return;

            var self = this;
            this.isLoading = true;
            this.unsplashPage = 1;

            var params = new URLSearchParams({
                query: this.unsplashQuery,
                page: this.unsplashPage,
                per_page: 20,
                orientation: this.unsplashOrientation
            });

            fetch(VBP_Config.restUrl + 'unsplash/search?' + params.toString(), {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) {
                    throw new Error(data.error);
                }
                self.unsplashImages = data.results || [];
                self.unsplashTotalPages = data.totalPages || 0;
            })
            .catch(function(error) {
                self.showNotification('Error buscando en Unsplash: ' + error.message, 'error');
                self.unsplashImages = [];
            })
            .finally(function() {
                self.isLoading = false;
            });
        },

        /**
         * Carga página de Unsplash
         */
        loadUnsplashPage: function() {
            var self = this;
            this.isLoading = true;

            var params = new URLSearchParams({
                query: this.unsplashQuery,
                page: this.unsplashPage,
                per_page: 20,
                orientation: this.unsplashOrientation
            });

            fetch(VBP_Config.restUrl + 'unsplash/search?' + params.toString(), {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.unsplashImages = data.results || [];
            })
            .catch(function(error) {
                self.showNotification('Error cargando página', 'error');
            })
            .finally(function() {
                self.isLoading = false;
            });
        },

        /**
         * Selecciona imagen de Unsplash
         * @param {Object} image - Imagen de Unsplash
         */
        selectUnsplashImage: function(image) {
            var self = this;

            // Registrar descarga (requerido por Unsplash API)
            fetch(VBP_Config.restUrl + 'unsplash/photos/' + image.id + '/download', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            }).catch(function() {});

            // Preparar datos del asset
            var assetData = {
                type: 'unsplash',
                url: image.urls.regular,
                fullUrl: image.urls.full,
                thumbnail: image.urls.thumb,
                alt: image.description || 'Imagen de ' + image.user.name + ' en Unsplash',
                width: image.width,
                height: image.height,
                unsplashId: image.id,
                author: image.user.name,
                authorUrl: image.user.link
            };

            this.handleAssetSelect(assetData);
        },

        // ========================================
        // SELECCIÓN Y INSERCIÓN
        // ========================================

        /**
         * Selecciona un asset
         * @param {Object} asset - Asset seleccionado
         */
        selectAsset: function(asset) {
            this.selectedAsset = asset;
        },

        /**
         * Confirma selección e inserta el asset
         */
        confirmSelection: function() {
            if (!this.selectedAsset) return;

            var assetData = {
                type: this.activeTab,
                id: this.selectedAsset.id,
                url: this.selectedAsset.url,
                thumbnail: this.selectedAsset.thumbnail || this.selectedAsset.url,
                alt: this.selectedAsset.alt || this.selectedAsset.title || '',
                title: this.selectedAsset.title || '',
                width: this.selectedAsset.width,
                height: this.selectedAsset.height,
                mime: this.selectedAsset.mime
            };

            this.handleAssetSelect(assetData);
        },

        /**
         * Maneja la selección final del asset
         * @param {Object} assetData - Datos del asset seleccionado
         */
        handleAssetSelect: function(assetData) {
            // Añadir a recientes
            this.addToRecent(assetData);

            // Ejecutar callback si existe
            if (typeof this.onSelectCallback === 'function') {
                this.onSelectCallback(assetData);
            }

            // Insertar en elemento target si existe
            if (this.targetElement) {
                this.insertIntoElement(assetData);
            }

            this.showNotification('Media seleccionado', 'success');
            this.close();
        },

        /**
         * Inserta el asset en el elemento objetivo
         * @param {Object} assetData - Datos del asset
         */
        insertIntoElement: function(assetData) {
            var store = Alpine.store('vbp');
            if (!store || !this.targetElement) return;

            var element = store.getElement(this.targetElement);
            if (!element) return;

            var data = JSON.parse(JSON.stringify(element.data || {}));

            // Actualizar campo según el tipo
            if (this.targetField === 'src' || this.targetField === 'image') {
                data.src = assetData.url;
                if (assetData.alt) data.alt = assetData.alt;
                if (assetData.width) data.width = assetData.width;
                if (assetData.height) data.height = assetData.height;
                if (assetData.id) data.attachment_id = assetData.id;

                // Metadatos de Unsplash
                if (assetData.unsplashId) {
                    data.unsplashId = assetData.unsplashId;
                    data.unsplashAuthor = assetData.author;
                    data.unsplashAuthorUrl = assetData.authorUrl;
                }
            } else if (this.targetField === 'background') {
                // Para backgrounds
                var styles = JSON.parse(JSON.stringify(element.styles || {}));
                styles.background = styles.background || {};
                styles.background.image = 'url(' + assetData.url + ')';
                store.updateElement(this.targetElement, { styles: styles });
                return;
            } else if (this.targetField === 'icon') {
                data.icon = assetData.svg || assetData.url;
                data.iconType = assetData.type;
            } else {
                // Campo genérico
                data[this.targetField] = assetData.url;
            }

            store.updateElement(this.targetElement, { data: data });
        },

        /**
         * Selecciona un icono
         * @param {Object} icon - Icono seleccionado
         */
        selectIcon: function(icon) {
            var assetData = {
                type: 'icon',
                id: icon.id,
                name: icon.name,
                svg: icon.svg,
                category: icon.category
            };

            this.handleAssetSelect(assetData);
        },

        // ========================================
        // RECIENTES
        // ========================================

        /**
         * Añade asset a recientes
         * @param {Object} asset - Asset a añadir
         */
        addToRecent: function(asset) {
            try {
                var recent = this.getRecent();

                // Evitar duplicados
                recent = recent.filter(function(item) {
                    return item.url !== asset.url;
                });

                // Añadir al principio
                recent.unshift({
                    url: asset.url,
                    thumbnail: asset.thumbnail || asset.url,
                    type: asset.type,
                    title: asset.title || asset.alt || '',
                    addedAt: Date.now()
                });

                // Limitar cantidad
                recent = recent.slice(0, CONFIG.MAX_RECENT);

                localStorage.setItem(CONFIG.RECENT_KEY, JSON.stringify(recent));
            } catch (e) {
                console.warn('No se pudo guardar en recientes:', e);
            }
        },

        /**
         * Obtiene assets recientes
         * @returns {Array}
         */
        getRecent: function() {
            try {
                var stored = localStorage.getItem(CONFIG.RECENT_KEY);
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                return [];
            }
        },

        // ========================================
        // UPLOAD
        // ========================================

        /**
         * Abre el diálogo de upload
         */
        openUploadDialog: function() {
            var input = document.createElement('input');
            input.type = 'file';
            input.accept = this.getAcceptTypes();
            input.multiple = true;

            var self = this;
            input.onchange = function(e) {
                self.handleFileSelect(e.target.files);
            };

            input.click();
        },

        /**
         * Obtiene tipos de archivo aceptados según el tab
         * @returns {string}
         */
        getAcceptTypes: function() {
            switch (this.activeTab) {
                case 'images':
                    return 'image/jpeg,image/png,image/gif,image/webp';
                case 'svgs':
                    return 'image/svg+xml';
                case 'videos':
                    return 'video/mp4,video/webm,video/ogg';
                default:
                    return '*/*';
            }
        },

        /**
         * Maneja archivos seleccionados
         * @param {FileList} files - Archivos seleccionados
         */
        handleFileSelect: function(files) {
            if (!files || files.length === 0) return;

            var self = this;

            Array.from(files).forEach(function(file) {
                self.uploadFile(file);
            });
        },

        /**
         * Sube un archivo
         * @param {File} file - Archivo a subir
         */
        uploadFile: function(file) {
            var self = this;
            this.isUploading = true;
            this.uploadProgress = 0;

            var formData = new FormData();
            formData.append('file', file);

            var xhr = new XMLHttpRequest();

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    self.uploadProgress = Math.round((e.loaded / e.total) * 100);
                }
            };

            xhr.onload = function() {
                self.isUploading = false;
                self.uploadProgress = 0;

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success && response.asset) {
                            self.assets.unshift(response.asset);
                            self.showNotification('Archivo subido: ' + file.name, 'success');
                        } else {
                            throw new Error(response.error || 'Error desconocido');
                        }
                    } catch (e) {
                        self.showNotification('Error subiendo archivo', 'error');
                    }
                } else {
                    self.showNotification('Error subiendo archivo', 'error');
                }
            };

            xhr.onerror = function() {
                self.isUploading = false;
                self.showNotification('Error de conexión', 'error');
            };

            xhr.open('POST', VBP_Config.restUrl + 'assets/upload');
            xhr.setRequestHeader('X-WP-Nonce', VBP_Config.restNonce);
            xhr.send(formData);
        },

        // ========================================
        // DRAG & DROP
        // ========================================

        /**
         * Configura drag & drop
         */
        setupDragAndDrop: function() {
            var self = this;

            // Drop zone en el panel
            document.addEventListener('dragover', function(e) {
                if (self.isOpen && self.activeTab !== 'icons' && self.activeTab !== 'unsplash') {
                    e.preventDefault();
                    self.isDragging = true;
                }
            });

            document.addEventListener('dragleave', function(e) {
                if (e.target.classList && e.target.classList.contains('vbp-am-dropzone')) {
                    self.isDragging = false;
                }
            });

            document.addEventListener('drop', function(e) {
                if (!self.isOpen || self.activeTab === 'icons' || self.activeTab === 'unsplash') return;

                e.preventDefault();
                self.isDragging = false;

                var files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    self.handleFileSelect(files);
                }
            });
        },

        /**
         * Inicia drag de un asset para insertar en canvas
         * @param {Event} e - Evento de drag
         * @param {Object} asset - Asset a arrastrar
         */
        startAssetDrag: function(e, asset) {
            this.draggedAsset = asset;

            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('text/plain', JSON.stringify({
                type: 'vbp-asset',
                asset: asset
            }));

            // Imagen de drag personalizada
            if (asset.thumbnail) {
                var img = new Image();
                img.src = asset.thumbnail;
                e.dataTransfer.setDragImage(img, 50, 50);
            }
        },

        /**
         * Finaliza drag de asset
         */
        endAssetDrag: function() {
            this.draggedAsset = null;
        },

        // ========================================
        // UTILIDADES
        // ========================================

        /**
         * Configura atajos de teclado
         */
        setupKeyboardShortcuts: function() {
            var self = this;

            document.addEventListener('keydown', function(e) {
                if (!self.isOpen) return;

                // Escape para cerrar
                if (e.key === 'Escape') {
                    e.preventDefault();
                    self.close();
                }

                // Enter para confirmar selección
                if (e.key === 'Enter' && self.selectedAsset) {
                    e.preventDefault();
                    self.confirmSelection();
                }

                // Flechas para navegar
                if (e.key === 'ArrowRight' && e.ctrlKey) {
                    e.preventDefault();
                    self.nextPage();
                }

                if (e.key === 'ArrowLeft' && e.ctrlKey) {
                    e.preventDefault();
                    self.prevPage();
                }
            });
        },

        /**
         * Muestra una notificación
         * @param {string} message - Mensaje
         * @param {string} type - Tipo (success, error, warning, info)
         */
        showNotification: function(message, type) {
            if (window.vbpApp && typeof window.vbpApp.showNotification === 'function') {
                window.vbpApp.showNotification(message, type);
            } else {
                console.log('[' + type + '] ' + message);
            }
        },

        /**
         * Formatea tamaño de archivo
         * @param {number} bytes - Tamaño en bytes
         * @returns {string}
         */
        formatFileSize: function(bytes) {
            if (!bytes) return '';
            var sizes = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(1024));
            return Math.round(bytes / Math.pow(1024, i)) + ' ' + sizes[i];
        },

        /**
         * Obtiene la URL del placeholder según el tipo
         * @param {string} type - Tipo de asset
         * @returns {string}
         */
        getPlaceholder: function(type) {
            var basePath = VBP_Config.assetsUrl + 'images/placeholders/';
            switch (type) {
                case 'videos':
                    return basePath + 'video-placeholder.svg';
                case 'svgs':
                    return basePath + 'svg-placeholder.svg';
                default:
                    return basePath + 'image-placeholder.svg';
            }
        },

        /**
         * Obtiene configuración de tabs
         * @returns {Array}
         */
        getTabs: function() {
            return CONFIG.TABS;
        },

        /**
         * Comprueba si hay más páginas
         * @returns {boolean}
         */
        hasMorePages: function() {
            if (this.activeTab === 'unsplash') {
                return this.unsplashPage < this.unsplashTotalPages;
            }
            return this.currentPage < this.totalPages;
        },

        /**
         * Comprueba si hay páginas anteriores
         * @returns {boolean}
         */
        hasPrevPages: function() {
            if (this.activeTab === 'unsplash') {
                return this.unsplashPage > 1;
            }
            return this.currentPage > 1;
        },

        /**
         * Obtiene número de página actual
         * @returns {number}
         */
        getCurrentPage: function() {
            return this.activeTab === 'unsplash' ? this.unsplashPage : this.currentPage;
        },

        /**
         * Obtiene total de páginas
         * @returns {number}
         */
        getTotalPages: function() {
            return this.activeTab === 'unsplash' ? this.unsplashTotalPages : this.totalPages;
        }
    };

    // Registrar como módulo de app si está disponible
    if (typeof window.VBPAppModular !== 'undefined') {
        window.VBPAppModular.register('assetManager', window.VBPAssetManager);
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPAssetManager.init();
        });
    } else {
        window.VBPAssetManager.init();
    }

})();

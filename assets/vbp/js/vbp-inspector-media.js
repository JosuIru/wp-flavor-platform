/**
 * Visual Builder Pro - Inspector Media & Pickers
 *
 * Extrae del inspector base la gestión de color picker, media library
 * y selectores de iconos/emojis para reducir acoplamiento.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.extendVBPInspector = (function(previousExtendInspector) {
    return function(inspector) {
        if (typeof previousExtendInspector === 'function') {
            inspector = previousExtendInspector(inspector) || inspector;
        }

        return Object.assign(inspector, {
            colorPickerOpen: false,
            colorPickerTarget: null,
            colorPickerPosition: { top: 0, left: 0 },
            colorPickerCurrentColor: '#000000',

            colorPresets: [
                '#000000', '#1f2937', '#4b5563', '#9ca3af', '#e5e7eb', '#ffffff',
                '#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#8b5cf6',
                '#fecaca', '#fed7aa', '#fef08a', '#bbf7d0', '#bfdbfe', '#ddd6fe',
                '#6366f1', '#4f46e5', '#4338ca', '#3730a3', '#312e81', '#1e1b4b'
            ],

            mediaTypeConfig: {
                image: {
                    title: 'Seleccionar imagen',
                    button: 'Usar imagen',
                    libraryType: 'image',
                    extensions: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']
                },
                video: {
                    title: 'Seleccionar video',
                    button: 'Usar video',
                    libraryType: 'video',
                    extensions: ['mp4', 'webm', 'ogg', 'mov']
                },
                audio: {
                    title: 'Seleccionar audio',
                    button: 'Usar audio',
                    libraryType: 'audio',
                    extensions: ['mp3', 'wav', 'ogg', 'm4a', 'flac']
                },
                file: {
                    title: 'Seleccionar archivo',
                    button: 'Usar archivo',
                    libraryType: null,
                    extensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar']
                },
                pdf: {
                    title: 'Seleccionar PDF',
                    button: 'Usar PDF',
                    libraryType: 'application/pdf',
                    extensions: ['pdf']
                },
                any: {
                    title: 'Seleccionar archivo',
                    button: 'Usar archivo',
                    libraryType: null,
                    extensions: []
                }
            },

            openColorPicker: function(event, targetPath, currentValue) {
                var self = this;
                var trigger = event.currentTarget || event.target;
                var rect = trigger.getBoundingClientRect();

                this.colorPickerTarget = targetPath;
                this.colorPickerCurrentColor = this.normalizeColorForInput(currentValue, '#000000');
                this.colorPickerPosition = {
                    top: rect.bottom + 8,
                    left: rect.left
                };

                if (this.colorPickerPosition.left + 200 > window.innerWidth) {
                    this.colorPickerPosition.left = window.innerWidth - 216;
                }

                this.colorPickerOpen = true;

                setTimeout(function() {
                    document.addEventListener('click', self.handleColorPickerOutsideClick.bind(self), { once: true });
                }, 10);
            },

            handleColorPickerOutsideClick: function(event) {
                var picker = document.querySelector('.vbp-mini-color-picker');
                if (picker && !picker.contains(event.target)) {
                    this.closeColorPicker();
                    return;
                }

                if (this.colorPickerOpen) {
                    var self = this;
                    setTimeout(function() {
                        document.addEventListener('click', self.handleColorPickerOutsideClick.bind(self), { once: true });
                    }, 10);
                }
            },

            closeColorPicker: function() {
                this.colorPickerOpen = false;
                this.colorPickerTarget = null;
            },

            selectColor: function(color) {
                if (!this.colorPickerTarget) return;
                this.colorPickerCurrentColor = color;
                this.updateStyle(this.colorPickerTarget, color);
                this.closeColorPicker();
            },

            updateColorFromInput: function(event) {
                if (!this.colorPickerTarget) return;
                this.colorPickerCurrentColor = event.target.value;
                this.updateStyle(this.colorPickerTarget, event.target.value);
            },

            copyColorToClipboard: function(color) {
                navigator.clipboard.writeText(color).then(function() {
                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification('Color copiado: ' + color, 'success');
                    }
                });
            },

            extractAttachmentMetadata: function(attachment, mediaType) {
                if (!attachment) return null;

                var metadata = {
                    id: attachment.id || null,
                    url: attachment.url || '',
                    alt: attachment.alt || '',
                    title: attachment.title || '',
                    caption: attachment.caption || '',
                    mime: attachment.mime || attachment.type || '',
                    filename: attachment.filename || '',
                    filesize: attachment.filesizeHumanReadable || '',
                    width: attachment.width || null,
                    height: attachment.height || null
                };

                if (mediaType === 'image' && attachment.sizes) {
                    metadata.sizes = {};
                    ['thumbnail', 'medium', 'medium_large', 'large', 'full'].forEach(function(size) {
                        if (attachment.sizes[size]) {
                            metadata.sizes[size] = {
                                url: attachment.sizes[size].url,
                                width: attachment.sizes[size].width,
                                height: attachment.sizes[size].height
                            };
                        }
                    });
                }

                if (mediaType === 'video' && attachment.image && attachment.image.src) {
                    metadata.poster = attachment.image.src;
                }

                if (mediaType === 'audio' && attachment.meta) {
                    metadata.duration = attachment.meta.length_formatted || null;
                    metadata.artist = attachment.meta.artist || '';
                    metadata.album = attachment.meta.album || '';
                }

                return metadata;
            },

            applyMediaValue: function(field, value, mediaType, metadata, options) {
                options = options || {};
                metadata = metadata || null;

                if (!field) return;

                if (options.isStyleField) {
                    this.updateStyle(field, value, true);

                    if (mediaType === 'image' && metadata && metadata.id && field === 'background.image') {
                        this.updateStyle('background.attachment_id', metadata.id, true);
                    }
                    return;
                }

                this.updateElementData(field, value);

                if (mediaType === 'image') {
                    if (metadata && metadata.alt) this.updateElementData('alt', metadata.alt);
                    if (metadata && metadata.id) this.updateElementData('attachment_id', metadata.id);
                    if (metadata && metadata.sizes) this.updateElementData('sizes', metadata.sizes);
                    if (metadata && metadata.width && metadata.height) {
                        this.updateElementData('width', metadata.width);
                        this.updateElementData('height', metadata.height);
                    }
                } else if (mediaType === 'video') {
                    if (metadata && metadata.poster) this.updateElementData('video_poster', metadata.poster);
                    if (metadata && metadata.id) this.updateElementData('attachment_id', metadata.id);
                } else if (mediaType === 'audio') {
                    if (metadata && metadata.id) this.updateElementData('attachment_id', metadata.id);
                    if (metadata && metadata.duration) this.updateElementData('duration', metadata.duration);
                    if (metadata && metadata.title) this.updateElementData('audio_title', metadata.title);
                } else if (mediaType === 'file' || mediaType === 'pdf') {
                    if (metadata && metadata.id) this.updateElementData('attachment_id', metadata.id);
                    if (metadata && metadata.filename) this.updateElementData('filename', metadata.filename);
                    if (metadata && metadata.filesize) this.updateElementData('filesize', metadata.filesize);
                    if (metadata && metadata.mime) this.updateElementData('mime_type', metadata.mime);
                }
            },

            openMediaLibrary: function(field, mediaType, options) {
                var self = this;
                this.mediaLibraryField = field || 'src';
                this.mediaLibraryItemIndex = null;
                mediaType = mediaType || 'image';
                options = options || {};
                options.isStyleField = options.isStyleField || field.indexOf('background.') === 0;

                if (typeof wp === 'undefined' || !wp.media) {
                    this.showMediaFallbackDialog(field, mediaType);
                    if (options.isStyleField) {
                        this.urlModal.callback = function(url) {
                            if (url && self.isValidUrl(url)) {
                                self.updateStyle(field, url, true);
                            }
                        };
                    }
                    return;
                }

                var typeConfig = this.mediaTypeConfig[mediaType] || this.mediaTypeConfig.any;
                var mediaFrameConfig = {
                    title: typeConfig.title,
                    button: { text: typeConfig.button },
                    multiple: false
                };

                if (typeConfig.libraryType) {
                    mediaFrameConfig.library = { type: typeConfig.libraryType };
                }

                var frame = wp.media(mediaFrameConfig);

                frame.on('select', function() {
                    var selection = frame.state().get('selection').first();
                    if (!selection) {
                        vbpLog.warn('No se seleccionó ningún archivo');
                        return;
                    }

                    var attachment = selection.toJSON();
                    if (!attachment.url) {
                        vbpLog.error('El attachment no tiene URL válida');
                        if (window.vbpApp && window.vbpApp.showNotification) {
                            window.vbpApp.showNotification('Error: archivo sin URL válida', 'error');
                        }
                        return;
                    }

                    var metadata = self.extractAttachmentMetadata(attachment, mediaType);
                    self.applyMediaValue(self.mediaLibraryField, attachment.url, mediaType, metadata, options);

                    if (options.saveMetadata && options.metadataField) {
                        self.updateElementData(options.metadataField, metadata);
                    }

                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification('Archivo seleccionado correctamente', 'success');
                    }
                });

                frame.open();
            },

            showMediaFallbackDialog: function(field, mediaType) {
                var self = this;
                var typeConfig = this.mediaTypeConfig[mediaType] || this.mediaTypeConfig.any;

                this.urlModal.isOpen = true;
                this.urlModal.title = typeConfig.title;
                this.urlModal.url = '';
                this.urlModal.error = '';
                this.urlModal.mediaType = mediaType;
                this.urlModal.callback = function(url) {
                    if (url && self.isValidUrl(url)) {
                        self.updateElementData(field, url);
                        if (window.vbpApp && window.vbpApp.showNotification) {
                            window.vbpApp.showNotification('URL aplicada correctamente', 'success');
                        }
                    }
                };
            },

            confirmUrlModal: function() {
                var url = this.urlModal.url.trim();
                if (!url) {
                    this.urlModal.error = 'Por favor, introduce una URL';
                    return;
                }

                if (!this.isValidUrl(url)) {
                    this.urlModal.error = 'URL no válida. Debe comenzar con http://, https:// o /';
                    return;
                }

                if (this.urlModal.callback) {
                    this.urlModal.callback(url);
                }

                this.closeUrlModal();
            },

            closeUrlModal: function() {
                this.urlModal.isOpen = false;
                this.urlModal.url = '';
                this.urlModal.error = '';
                this.urlModal.callback = null;
            },

            getUrlPlaceholder: function() {
                var typeConfig = this.mediaTypeConfig[this.urlModal.mediaType] || this.mediaTypeConfig.any;
                return 'https://ejemplo.com/archivo.' + (typeConfig.extensions[0] || 'jpg');
            },

            isValidUrl: function(url) {
                if (!url || typeof url !== 'string') return false;
                if (url.startsWith('/')) return true;

                try {
                    new URL(url);
                    return true;
                } catch (e) {
                    return false;
                }
            },

            openMediaLibraryForItem: function(itemIndex, field, mediaType) {
                var self = this;
                mediaType = mediaType || 'image';

                if (typeof itemIndex !== 'number' || itemIndex < 0) {
                    vbpLog.error('Índice de item inválido:', itemIndex);
                    return;
                }

                if (typeof wp !== 'undefined' && wp.media) {
                    var typeConfig = this.mediaTypeConfig[mediaType] || this.mediaTypeConfig.image;
                    var frame = wp.media({
                        title: typeConfig.title,
                        button: { text: typeConfig.button },
                        multiple: false,
                        library: typeConfig.libraryType ? { type: typeConfig.libraryType } : undefined
                    });

                    frame.on('select', function() {
                        var selection = frame.state().get('selection').first();
                        if (!selection) return;

                        var attachment = selection.toJSON();
                        if (!attachment.url) return;

                        self.updateItem(itemIndex, field, attachment.url);
                        if (mediaType === 'image' && attachment.alt) {
                            self.updateItem(itemIndex, 'alt', attachment.alt);
                        }
                    });

                    frame.open();
                    return;
                }

                var url = prompt('Introduce la URL:');
                if (url && this.isValidUrl(url)) {
                    this.updateItem(itemIndex, field, url);
                }
            },

            addMediaCollectionItems: function(config) {
                var self = this;
                config = config || {};

                var collectionField = config.collectionField || 'items';
                var mediaType = config.mediaType || 'image';
                var multiple = config.multiple !== false;
                var typeConfig = this.mediaTypeConfig[mediaType] || this.mediaTypeConfig.image;

                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({
                        title: config.title || typeConfig.title,
                        button: { text: config.buttonText || typeConfig.button },
                        multiple: multiple,
                        library: typeConfig.libraryType ? { type: typeConfig.libraryType } : undefined
                    });

                    frame.on('select', function() {
                        var selection = frame.state().get('selection').toJSON();
                        if (!selection || selection.length === 0) return;

                        var data = JSON.parse(JSON.stringify(self.selectedElement.data || {}));
                        if (!Array.isArray(data[collectionField])) {
                            data[collectionField] = [];
                        }

                        selection.forEach(function(attachment) {
                            if (!attachment.url) return;
                            var item = typeof config.mapAttachment === 'function'
                                ? config.mapAttachment(attachment)
                                : { src: attachment.url };
                            if (item) {
                                data[collectionField].push(item);
                            }
                        });

                        Alpine.store('vbp').updateElement(self.selectedElement.id, { data: data });

                        if (window.vbpApp && window.vbpApp.showNotification) {
                            window.vbpApp.showNotification(
                                selection.length + ' elemento(s) añadido(s)',
                                'success'
                            );
                        }
                    });

                    frame.open();
                    return true;
                }

                this.showMediaFallbackDialog(config.fallbackField || 'src', mediaType);
                return false;
            },

            addGalleryImage: function() {
                if (this.addMediaCollectionItems({
                    collectionField: 'items',
                    mediaType: 'image',
                    title: 'Seleccionar imágenes',
                    buttonText: 'Añadir imágenes',
                    fallbackField: 'src',
                    mapAttachment: function(attachment) {
                        var item = {
                            src: attachment.url,
                            alt: attachment.alt || '',
                            attachment_id: attachment.id || null,
                            width: attachment.width || null,
                            height: attachment.height || null
                        };

                        if (attachment.sizes && attachment.sizes.medium) {
                            item.thumbnail = attachment.sizes.medium.url;
                        }

                        return item;
                    }
                })) {
                    return;
                }

                var self = this;
                this.urlModal.callback = function(url) {
                    if (!url || !self.isValidUrl(url)) return;

                    var data = JSON.parse(JSON.stringify(self.selectedElement.data || {}));
                    if (!Array.isArray(data.items)) {
                        data.items = [];
                    }

                    data.items.push({
                        src: url,
                        alt: '',
                        attachment_id: null,
                        width: null,
                        height: null
                    });

                    Alpine.store('vbp').updateElement(self.selectedElement.id, { data: data });

                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification('Imagen añadida correctamente', 'success');
                    }
                };
            },

            addLogoImage: function() {
                if (this.addMediaCollectionItems({
                    collectionField: 'logos',
                    mediaType: 'image',
                    title: 'Seleccionar logos',
                    buttonText: 'Añadir logos',
                    fallbackField: 'src',
                    mapAttachment: function(attachment) {
                        return {
                            src: attachment.url,
                            alt: attachment.alt || attachment.title || 'Logo',
                            attachment_id: attachment.id || null,
                            width: attachment.width || null,
                            height: attachment.height || null
                        };
                    }
                })) {
                    return;
                }

                var self = this;
                this.urlModal.callback = function(url) {
                    if (!url || !self.isValidUrl(url)) return;

                    var data = JSON.parse(JSON.stringify(self.selectedElement.data || {}));
                    if (!Array.isArray(data.logos)) {
                        data.logos = [];
                    }

                    data.logos.push({
                        src: url,
                        alt: 'Logo',
                        attachment_id: null,
                        width: null,
                        height: null
                    });

                    Alpine.store('vbp').updateElement(self.selectedElement.id, { data: data });

                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification('Logo añadido correctamente', 'success');
                    }
                };
            },

            openFileLibrary: function(field) {
                this.openMediaLibrary(field, 'file');
            },

            openAudioLibrary: function(field) {
                this.openMediaLibrary(field, 'audio');
            },

            openIconSelector: function(field) {
                var self = this;
                if (!this.selectedElement) return;

                field = field || 'icono';
                var currentValue = this.selectedElement.data[field] || '';

                Alpine.store('vbpModals').openIconSelector(
                    function(type, value) {
                        if (value) {
                            self.updateElementData(field, value);
                        }
                    },
                    currentValue,
                    field,
                    null
                );
            },

            openIconSelectorForItem: function(itemIndex, field) {
                var self = this;
                if (!this.selectedElement) return;

                field = field || 'icono';
                var items = this.selectedElement.data.items || [];
                var currentValue = items[itemIndex] ? items[itemIndex][field] || '' : '';

                Alpine.store('vbpModals').openIconSelector(
                    function(type, value) {
                        self.updateItem(itemIndex, field, value);
                    },
                    currentValue,
                    field,
                    itemIndex
                );
            },

            openIconSelectorForTimeline: function(itemIndex, field) {
                var self = this;
                if (!this.selectedElement || !this.selectedElement.data) return;

                field = field || 'icono';
                var timelineItems = typeof this.getEditableCollection === 'function'
                    ? this.getEditableCollection('eventos')
                    : (Array.isArray(this.selectedElement.data.eventos) ? this.selectedElement.data.eventos : []);
                var currentValue = timelineItems[itemIndex]
                    ? timelineItems[itemIndex][field] || ''
                    : '';

                Alpine.store('vbpModals').openIconSelector(
                    function(type, value) {
                        self.updateItem(itemIndex, field, value);
                    },
                    currentValue,
                    field,
                    itemIndex
                );
            },

            openIconSelectorForColumnItem: function(columna, itemIndex, field) {
                var self = this;
                if (!this.selectedElement || !this.selectedElement.data || !this.selectedElement.data[columna] || !this.selectedElement.data[columna].data) {
                    return;
                }

                field = field || 'icono';
                var items = this.selectedElement.data[columna].data.items || [];
                var currentValue = items[itemIndex] ? items[itemIndex][field] || '' : '';

                Alpine.store('vbpModals').openIconSelector(
                    function(type, value) {
                        self.updateColumnItem(columna, itemIndex, field, value);
                    },
                    currentValue,
                    field,
                    itemIndex
                );
            },

            openIconSelectorForSocial: function(index) {
                var self = this;
                if (!this.selectedElement || !this.selectedElement.data.redes) return;

                var currentValue = this.selectedElement.data.redes[index] ? this.selectedElement.data.redes[index].icono || '' : '';

                Alpine.store('vbpModals').openIconSelector(
                    function(type, value) {
                        self.updateSocialItem(index, 'icono', value);
                    },
                    currentValue,
                    'icono',
                    index
                );
            },

            openEmojiPicker: function(event, field) {
                var self = this;
                if (!this.selectedElement) return;

                field = field || 'emoji';
                var rect = event.target.getBoundingClientRect();

                Alpine.store('vbpModals').openEmojiPicker(
                    function(emoji) {
                        self.updateElementData(field, emoji);
                    },
                    { x: rect.left, y: rect.bottom + 5 },
                    field,
                    null
                );
            },

            openEmojiPickerForItem: function(event, itemIndex, field) {
                var self = this;
                if (!this.selectedElement) return;

                field = field || 'emoji';
                var rect = event.target.getBoundingClientRect();

                Alpine.store('vbpModals').openEmojiPicker(
                    function(emoji) {
                        self.updateItem(itemIndex, field, emoji);
                    },
                    { x: rect.left, y: rect.bottom + 5 },
                    field,
                    itemIndex
                );
            }
        });
    };
})(window.extendVBPInspector);

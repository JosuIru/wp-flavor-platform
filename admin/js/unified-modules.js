/**
 * Unified Modules View - JavaScript
 *
 * Funcionalidad para la vista unificada de módulos
 *
 * @package FlavorChatIA
 * @version 4.1.0
 */

(function() {
    'use strict';

    // Verificar que Alpine está disponible
    document.addEventListener('alpine:init', () => {
        Alpine.data('unifiedModulesState', () => ({
            // Estado de filtros
            searchQuery: '',
            activeCategory: 'all',
            filterStatus: 'all',
            filterVisibility: 'all',

            // Estado de operaciones
            savingModules: [],
            hasVisibleModules: true,

            // Estado del modal de documentación
            docsModalOpen: false,
            docsModuleId: '',
            docsModuleName: '',
            docsLoading: false,
            docsData: null,
            docsError: null,

            // Inicialización
            init() {
                this.updateVisibleCount();
            },

            // Filtrar módulos
            shouldShowModule(moduleId, moduleName, category, isActive, visibility) {
                // Filtro por categoría
                if (this.activeCategory !== 'all' && category !== this.activeCategory) {
                    return false;
                }

                // Filtro por búsqueda
                if (this.searchQuery) {
                    const query = this.searchQuery.toLowerCase();
                    const nameMatch = moduleName.toLowerCase().includes(query);
                    const idMatch = moduleId.toLowerCase().includes(query);
                    if (!nameMatch && !idMatch) {
                        return false;
                    }
                }

                // Filtro por estado
                if (this.filterStatus === 'active' && !isActive) {
                    return false;
                }
                if (this.filterStatus === 'inactive' && isActive) {
                    return false;
                }

                // Filtro por visibilidad
                if (this.filterVisibility !== 'all' && visibility !== this.filterVisibility) {
                    return false;
                }

                return true;
            },

            // Establecer categoría activa
            setCategory(category) {
                this.activeCategory = category;
                this.updateVisibleCount();
            },

            // Actualizar filtros
            filterModules() {
                this.updateVisibleCount();
            },

            // Actualizar conteo de módulos visibles
            updateVisibleCount() {
                this.$nextTick(() => {
                    const visibleCards = document.querySelectorAll('.fum-module-card:not([style*="display: none"])');
                    this.hasVisibleModules = visibleCards.length > 0;
                });
            },

            // Toggle módulo
            async toggleModule(moduleId, activate) {
                this.savingModules.push(moduleId);

                try {
                    const response = await fetch(fumData.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'fum_toggle_module',
                            nonce: fumData.nonce,
                            module_id: moduleId,
                            activate: activate ? '1' : '0',
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Actualizar clase de la tarjeta
                        const card = document.querySelector(`.fum-module-card[data-module-id="${moduleId}"]`);
                        if (card) {
                            card.classList.toggle('is-active', activate);
                            card.classList.toggle('is-inactive', !activate);
                        }

                        this.showNotification(data.data.message, 'success');
                    } else {
                        this.showNotification(data.data.message || fumData.i18n.error, 'error');
                        // Revertir el checkbox
                        const checkbox = document.querySelector(`.fum-module-card[data-module-id="${moduleId}"] .fum-toggle input`);
                        if (checkbox) {
                            checkbox.checked = !activate;
                        }
                    }
                } catch (error) {
                    console.error('Error toggling module:', error);
                    this.showNotification(fumData.i18n.error, 'error');
                } finally {
                    this.savingModules = this.savingModules.filter(id => id !== moduleId);
                }
            },

            // Guardar visibilidad
            async saveVisibility(moduleId, visibility, capability) {
                this.savingModules.push(moduleId);

                try {
                    const params = {
                        action: 'fum_save_visibility',
                        nonce: fumData.nonce,
                        module_id: moduleId,
                    };

                    if (visibility !== null) {
                        params.visibility = visibility;
                    }
                    if (capability !== null) {
                        params.capability = capability;
                    }

                    const response = await fetch(fumData.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams(params),
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Actualizar badge de visibilidad si cambió
                        if (visibility !== null) {
                            const card = document.querySelector(`.fum-module-card[data-module-id="${moduleId}"]`);
                            if (card) {
                                const badge = card.querySelector('.fum-badge--public, .fum-badge--members, .fum-badge--private');
                                if (badge) {
                                    badge.className = `fum-badge fum-badge--${visibility.replace('_only', '')}`;
                                    badge.textContent = this.getVisibilityLabel(visibility);
                                }
                            }
                        }
                    } else {
                        this.showNotification(data.data.message || fumData.i18n.error, 'error');
                    }
                } catch (error) {
                    console.error('Error saving visibility:', error);
                    this.showNotification(fumData.i18n.error, 'error');
                } finally {
                    // Pequeño delay para mostrar el indicador de guardado
                    setTimeout(() => {
                        this.savingModules = this.savingModules.filter(id => id !== moduleId);
                    }, 500);
                }
            },

            // Crear landing
            async createLanding(moduleId) {
                const btn = event.target.closest('.fum-btn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="dashicons dashicons-update fum-saving__icon"></span> ' + fumData.i18n.creatingLanding;
                }

                try {
                    const response = await fetch(fumData.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'fum_create_landing',
                            nonce: fumData.nonce,
                            module_id: moduleId,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showNotification(fumData.i18n.landingCreated, 'success');
                        // Recargar la página para mostrar los nuevos botones
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        this.showNotification(data.data.message || fumData.i18n.error, 'error');
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<span class="dashicons dashicons-plus-alt"></span> Crear Landing';
                        }
                    }
                } catch (error) {
                    console.error('Error creating landing:', error);
                    this.showNotification(fumData.i18n.error, 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<span class="dashicons dashicons-plus-alt"></span> Crear Landing';
                    }
                }
            },

            // Abrir modal de documentación
            async openDocs(moduleId, moduleName) {
                this.docsModalOpen = true;
                this.docsModuleId = moduleId;
                this.docsModuleName = moduleName;
                this.docsLoading = true;
                this.docsData = null;
                this.docsError = null;

                try {
                    const response = await fetch(`${fumData.restUrl}modules/docs/${moduleId}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': fumData.restNonce,
                        },
                    });

                    if (!response.ok) {
                        if (response.status === 404) {
                            this.docsError = fumData.i18n.docsNotFound;
                        } else {
                            this.docsError = fumData.i18n.docsError;
                        }
                        return;
                    }

                    const data = await response.json();
                    // La API devuelve {success: true, data: {...}}
                    this.docsData = data.data || data;
                } catch (error) {
                    console.error('Error loading docs:', error);
                    this.docsError = fumData.i18n.docsError;
                } finally {
                    this.docsLoading = false;
                }
            },

            // Obtener etiqueta de visibilidad
            getVisibilityLabel(visibility) {
                const labels = {
                    'public': 'Público',
                    'members_only': 'Miembros',
                    'private': 'Privado',
                };
                return labels[visibility] || visibility;
            },

            // Mostrar notificación
            showNotification(message, type = 'info') {
                // Usar el sistema de notificaciones de WordPress si está disponible
                const notices = document.querySelector('.wrap');
                if (notices) {
                    const notice = document.createElement('div');
                    notice.className = `notice notice-${type === 'error' ? 'error' : 'success'} is-dismissible`;
                    notice.innerHTML = `<p>${message}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>`;

                    // Insertar después del h1
                    const h1 = notices.querySelector('h1');
                    if (h1) {
                        h1.parentNode.insertBefore(notice, h1.nextSibling);
                    } else {
                        notices.insertBefore(notice, notices.firstChild);
                    }

                    // Auto-cerrar después de 3 segundos
                    setTimeout(() => {
                        notice.remove();
                    }, 3000);

                    // Botón de cerrar
                    notice.querySelector('.notice-dismiss').addEventListener('click', () => {
                        notice.remove();
                    });
                }
            },
        }));
    });

    // Fallback si Alpine no está cargado (ejecutar filtrado con JS vanilla)
    document.addEventListener('DOMContentLoaded', function() {
        // Si después de 1 segundo Alpine no ha procesado, usar fallback
        setTimeout(function() {
            const unifiedModules = document.querySelector('.flavor-unified-modules');
            if (unifiedModules && !unifiedModules.__x) {
                console.log('Alpine not detected, using vanilla JS fallback');
                initVanillaFallback();
            }
        }, 1000);
    });

    function initVanillaFallback() {
        const searchInput = document.querySelector('.fum-search__input');
        const categoryTabs = document.querySelectorAll('.fum-category-tab');
        const moduleCards = document.querySelectorAll('.fum-module-card');
        const filterStatus = document.querySelector('.fum-filter-select:nth-of-type(1)');
        const filterVisibility = document.querySelector('.fum-filter-select:nth-of-type(2)');

        let activeCategory = 'all';

        function filterModules() {
            const query = searchInput ? searchInput.value.toLowerCase() : '';
            const status = filterStatus ? filterStatus.value : 'all';
            const visibility = filterVisibility ? filterVisibility.value : 'all';

            moduleCards.forEach(card => {
                const moduleId = card.dataset.moduleId;
                const category = card.dataset.category;
                const isActive = card.classList.contains('is-active');
                const moduleName = card.querySelector('.fum-card__name')?.textContent.toLowerCase() || '';

                let show = true;

                // Filtro por categoría
                if (activeCategory !== 'all' && category !== activeCategory) {
                    show = false;
                }

                // Filtro por búsqueda
                if (show && query) {
                    if (!moduleName.includes(query) && !moduleId.includes(query)) {
                        show = false;
                    }
                }

                // Filtro por estado
                if (show && status === 'active' && !isActive) {
                    show = false;
                }
                if (show && status === 'inactive' && isActive) {
                    show = false;
                }

                card.style.display = show ? '' : 'none';
            });

            // Actualizar empty state
            const visibleCards = document.querySelectorAll('.fum-module-card:not([style*="display: none"])');
            const emptyState = document.querySelector('.fum-empty-state');
            if (emptyState) {
                emptyState.style.display = visibleCards.length === 0 ? '' : 'none';
            }
        }

        // Event listeners
        if (searchInput) {
            searchInput.addEventListener('input', filterModules);
        }

        categoryTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                categoryTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                // Extraer categoría del onclick o data attribute
                const match = this.getAttribute('@click')?.match(/'([^']+)'/);
                if (match) {
                    activeCategory = match[1];
                }
                filterModules();
            });
        });

        if (filterStatus) {
            filterStatus.addEventListener('change', filterModules);
        }

        if (filterVisibility) {
            filterVisibility.addEventListener('change', filterModules);
        }

        // Toggle handlers
        document.querySelectorAll('.fum-toggle input').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const card = this.closest('.fum-module-card');
                const moduleId = card?.dataset.moduleId;
                if (moduleId) {
                    toggleModuleVanilla(moduleId, this.checked);
                }
            });
        });
    }

    function toggleModuleVanilla(moduleId, activate) {
        jQuery.ajax({
            url: fumData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fum_toggle_module',
                nonce: fumData.nonce,
                module_id: moduleId,
                activate: activate ? '1' : '0',
            },
            success: function(response) {
                if (response.success) {
                    const card = document.querySelector(`.fum-module-card[data-module-id="${moduleId}"]`);
                    if (card) {
                        card.classList.toggle('is-active', activate);
                        card.classList.toggle('is-inactive', !activate);
                    }
                }
            },
        });
    }
})();

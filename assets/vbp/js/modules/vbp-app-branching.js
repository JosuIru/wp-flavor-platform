/**
 * Visual Builder Pro - App Module: Branching System
 * Sistema de ramas de diseño para trabajo paralelo y experimentación
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

/* global VBP_Config, VBPAppToast, vbpLog, sanitizeElements */

window.VBPAppBranching = {
	// Estado
	branches: [],
	currentBranch: null,
	isLoadingBranches: false,
	isCreatingBranch: false,
	isCheckingOut: false,
	isMerging: false,
	hasUnsavedChanges: false,

	// Control de inicialización y cleanup
	_initialized: false,
	_eventHandlers: {},

	// Modales
	showBranchPanel: false,
	showCreateBranchModal: false,
	showMergeModal: false,
	showDiffModal: false,
	showHistoryModal: false,
	showConflictModal: false,

	// Datos de formularios
	newBranchName: '',
	newBranchDescription: '',
	newBranchFromId: null,

	// Merge
	mergeSourceBranch: null,
	mergeTargetBranch: null,
	mergeConflicts: [],
	conflictResolutions: {},

	// Diff
	diffBranchA: null,
	diffBranchB: null,
	diffData: null,
	isLoadingDiff: false,

	// Historial
	branchHistory: [],
	isLoadingHistory: false,
	selectedHistoryBranch: null,

	/**
	 * Inicializa el sistema de branching
	 */
	init: function() {
		// Evitar inicialización duplicada
		if (this._initialized) {
			vbpLog.log('VBP Branching: ya inicializado, ignorando');
			return;
		}
		this._initialized = true;

		var self = this;

		// Cargar branches al iniciar
		this.loadBranches();

		// Guardar referencias a los handlers para cleanup
		this._eventHandlers.contentChanged = function() {
			self.hasUnsavedChanges = true;
		};

		this._eventHandlers.contentSaved = function() {
			self.hasUnsavedChanges = false;
		};

		// Escuchar cambios en el documento
		document.addEventListener('vbp:content-changed', this._eventHandlers.contentChanged);

		// Escuchar guardado
		document.addEventListener('vbp:content-saved', this._eventHandlers.contentSaved);

		vbpLog.info('VBP Branching: Inicializado');
	},

	/**
	 * Destruye el módulo y limpia recursos
	 */
	destroy: function() {
		// Remover event listeners
		if (this._eventHandlers.contentChanged) {
			document.removeEventListener('vbp:content-changed', this._eventHandlers.contentChanged);
		}
		if (this._eventHandlers.contentSaved) {
			document.removeEventListener('vbp:content-saved', this._eventHandlers.contentSaved);
		}

		// Limpiar referencias
		this._eventHandlers = {};

		// Resetear estado
		this.branches = [];
		this.currentBranch = null;
		this.hasUnsavedChanges = false;
		this._initialized = false;

		vbpLog.info('VBP Branching: Destruido');
	},

	/**
	 * Carga la lista de branches
	 */
	loadBranches: function() {
		var self = this;
		this.isLoadingBranches = true;

		return fetch(VBP_Config.restUrl + 'branches/' + VBP_Config.postId, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': VBP_Config.restNonce
			}
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				self.branches = data.branches || [];

				// Encontrar branch activa
				var activeBranch = self.branches.find(function(branch) {
					return branch.is_active;
				});

				if (activeBranch) {
					self.currentBranch = activeBranch;
				} else if (self.branches.length > 0) {
					// Usar main por defecto
					var mainBranch = self.branches.find(function(branch) {
						return branch.is_main;
					});
					self.currentBranch = mainBranch || self.branches[0];
				}

				vbpLog.info('VBP Branching: Cargadas ' + self.branches.length + ' ramas');
			}
		})
		.catch(function(error) {
			vbpLog.error('Error cargando branches:', error);
			self.showNotification('Error al cargar las ramas', 'error');
		})
		.finally(function() {
			self.isLoadingBranches = false;
		});
	},

	/**
	 * Obtiene el nombre de la branch actual
	 */
	getCurrentBranchName: function() {
		return this.currentBranch ? this.currentBranch.branch_name : 'main';
	},

	/**
	 * Verifica si la branch actual es main
	 */
	isCurrentBranchMain: function() {
		return this.currentBranch && this.currentBranch.branch_slug === 'main';
	},

	/**
	 * Abre el panel de branches
	 */
	openBranchPanel: function() {
		this.showBranchPanel = true;
		this.loadBranches();
	},

	/**
	 * Cierra el panel de branches
	 */
	closeBranchPanel: function() {
		this.showBranchPanel = false;
	},

	/**
	 * Toggle del panel de branches
	 */
	toggleBranchPanel: function() {
		if (this.showBranchPanel) {
			this.closeBranchPanel();
		} else {
			this.openBranchPanel();
		}
	},

	/**
	 * Abre el modal para crear nueva branch
	 */
	openCreateBranchModal: function(fromBranchId) {
		this.newBranchName = '';
		this.newBranchDescription = '';
		this.newBranchFromId = fromBranchId || null;
		this.showCreateBranchModal = true;
	},

	/**
	 * Cierra el modal de crear branch
	 */
	closeCreateBranchModal: function() {
		this.showCreateBranchModal = false;
		this.newBranchName = '';
		this.newBranchDescription = '';
		this.newBranchFromId = null;
	},

	/**
	 * Crea una nueva branch
	 */
	createBranch: function() {
		var self = this;

		if (!this.newBranchName.trim()) {
			this.showNotification('El nombre de la rama es requerido', 'warning');
			return;
		}

		this.isCreatingBranch = true;

		var requestBody = {
			post_id: VBP_Config.postId,
			name: this.newBranchName.trim(),
			description: this.newBranchDescription.trim()
		};

		if (this.newBranchFromId) {
			requestBody.from_branch = this.newBranchFromId;
		}

		fetch(VBP_Config.restUrl + 'branches', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': VBP_Config.restNonce
			},
			body: JSON.stringify(requestBody)
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				self.showNotification('Rama creada: ' + data.branch.branch_name, 'success');
				self.closeCreateBranchModal();
				self.loadBranches();

				// Si se creó y se hizo checkout automático, actualizar UI
				if (data.branch) {
					self.currentBranch = data.branch;
				}
			} else {
				throw new Error(data.message || 'Error al crear la rama');
			}
		})
		.catch(function(error) {
			self.showNotification('Error: ' + error.message, 'error');
		})
		.finally(function() {
			self.isCreatingBranch = false;
		});
	},

	/**
	 * Realiza checkout a una branch
	 */
	checkout: function(branchId) {
		var self = this;

		if (this.currentBranch && this.currentBranch.id === branchId) {
			return; // Ya está en esta branch
		}

		// Confirmar si hay cambios sin guardar
		if (this.hasUnsavedChanges) {
			var confirmMessage = VBP_Config.strings.unsavedChangesConfirm ||
				'Hay cambios sin guardar. ¿Deseas guardarlos antes de cambiar de rama?';

			if (!confirm(confirmMessage)) {
				return;
			}
		}

		this.isCheckingOut = true;

		var requestBody = {};

		// Si hay cambios, enviar el contenido actual para guardarlo
		if (this.hasUnsavedChanges) {
			var store = Alpine.store('vbp');
			if (store && store.elements) {
				requestBody.current_content = {
					elements: store.elements,
					settings: store.settings || {}
				};
			}
		}

		fetch(VBP_Config.restUrl + 'branches/' + VBP_Config.postId + '/' + branchId + '/checkout', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': VBP_Config.restNonce
			},
			body: JSON.stringify(requestBody)
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				self.currentBranch = data.branch;
				self.hasUnsavedChanges = false;

				// Cargar el contenido de la nueva branch
				if (data.content) {
					var store = Alpine.store('vbp');
					if (store) {
						var elements = data.content.elements || data.content;
						store.elements = sanitizeElements(elements);
						if (data.content.settings) {
							store.settings = data.content.settings;
						}
					}
				}

				self.showNotification('Cambiado a rama: ' + data.branch.branch_name, 'success');
				self.loadBranches();
				self.closeBranchPanel();

				// Emitir evento
				document.dispatchEvent(new CustomEvent('vbp:branch-changed', {
					detail: { branch: data.branch }
				}));
			} else {
				throw new Error(data.message || 'Error al cambiar de rama');
			}
		})
		.catch(function(error) {
			self.showNotification('Error: ' + error.message, 'error');
		})
		.finally(function() {
			self.isCheckingOut = false;
		});
	},

	/**
	 * Guarda en la branch actual
	 */
	saveInCurrentBranch: function(message) {
		var self = this;

		if (!this.currentBranch) {
			this.showNotification('No hay rama activa', 'error');
			return Promise.reject(new Error('No active branch'));
		}

		var store = Alpine.store('vbp');
		if (!store || !store.elements) {
			return Promise.reject(new Error('No content to save'));
		}

		var contentToSave = {
			elements: store.elements,
			settings: store.settings || {}
		};

		return fetch(VBP_Config.restUrl + 'branches/' + VBP_Config.postId + '/' + this.currentBranch.id + '/save', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': VBP_Config.restNonce
			},
			body: JSON.stringify({
				content: contentToSave,
				message: message || ''
			})
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				self.hasUnsavedChanges = false;
				self.showNotification('Guardado en rama: ' + self.currentBranch.branch_name, 'success');
				return data;
			} else {
				throw new Error(data.message || 'Error al guardar');
			}
		});
	},

	/**
	 * Abre el modal de merge
	 */
	openMergeModal: function(sourceBranchId) {
		var self = this;

		this.mergeSourceBranch = this.branches.find(function(branch) {
			return branch.id === sourceBranchId;
		});

		// Por defecto, target es main
		this.mergeTargetBranch = this.branches.find(function(branch) {
			return branch.is_main;
		});

		this.mergeConflicts = [];
		this.conflictResolutions = {};
		this.showMergeModal = true;
	},

	/**
	 * Cierra el modal de merge
	 */
	closeMergeModal: function() {
		this.showMergeModal = false;
		this.mergeSourceBranch = null;
		this.mergeTargetBranch = null;
		this.mergeConflicts = [];
		this.conflictResolutions = {};
	},

	/**
	 * Selecciona branch target para merge
	 */
	selectMergeTarget: function(branchId) {
		var self = this;
		this.mergeTargetBranch = this.branches.find(function(branch) {
			return branch.id === branchId;
		});
	},

	/**
	 * Ejecuta el merge
	 */
	merge: function() {
		var self = this;

		if (!this.mergeSourceBranch || !this.mergeTargetBranch) {
			this.showNotification('Selecciona las ramas origen y destino', 'warning');
			return;
		}

		if (this.mergeSourceBranch.id === this.mergeTargetBranch.id) {
			this.showNotification('No puedes fusionar una rama consigo misma', 'warning');
			return;
		}

		this.isMerging = true;

		fetch(VBP_Config.restUrl + 'branches/' + VBP_Config.postId + '/' + this.mergeSourceBranch.id + '/merge', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': VBP_Config.restNonce
			},
			body: JSON.stringify({
				target_branch_id: this.mergeTargetBranch.id,
				conflict_resolutions: this.conflictResolutions
			})
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				self.showNotification(data.message, 'success');
				self.closeMergeModal();
				self.loadBranches();

				// Si estamos en la rama target, recargar contenido
				if (self.currentBranch && self.currentBranch.id === self.mergeTargetBranch.id) {
					self.checkout(self.mergeTargetBranch.id);
				}
			} else if (data.conflicts && data.conflicts.length > 0) {
				// Hay conflictos
				self.mergeConflicts = data.conflicts;
				self.showConflictModal = true;
				self.showNotification('Hay ' + data.conflicts.length + ' conflictos que resolver', 'warning');
			} else {
				throw new Error(data.message || 'Error al fusionar');
			}
		})
		.catch(function(error) {
			self.showNotification('Error: ' + error.message, 'error');
		})
		.finally(function() {
			self.isMerging = false;
		});
	},

	/**
	 * Resuelve un conflicto
	 */
	resolveConflict: function(elementId, resolution) {
		this.conflictResolutions[elementId] = resolution;

		// Verificar si todos los conflictos están resueltos
		var allResolved = this.mergeConflicts.every(function(conflict) {
			return this.conflictResolutions[conflict.element_id];
		}, this);

		if (allResolved) {
			// Todos resueltos, ejecutar merge
			this.showConflictModal = false;
			this.merge();
		}
	},

	/**
	 * Abre el modal de diff
	 */
	openDiffModal: function(branchAId, branchBId) {
		var self = this;

		this.diffBranchA = this.branches.find(function(branch) {
			return branch.id === branchAId;
		});

		this.diffBranchB = branchBId ? this.branches.find(function(branch) {
			return branch.id === branchBId;
		}) : this.currentBranch;

		this.diffData = null;
		this.showDiffModal = true;

		this.loadDiff();
	},

	/**
	 * Cierra el modal de diff
	 */
	closeDiffModal: function() {
		this.showDiffModal = false;
		this.diffBranchA = null;
		this.diffBranchB = null;
		this.diffData = null;
	},

	/**
	 * Carga el diff entre dos branches
	 */
	loadDiff: function() {
		var self = this;

		if (!this.diffBranchA || !this.diffBranchB) {
			return;
		}

		this.isLoadingDiff = true;

		fetch(VBP_Config.restUrl + 'branches/' + VBP_Config.postId + '/' + this.diffBranchA.id + '/diff', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': VBP_Config.restNonce
			},
			body: JSON.stringify({
				compare_with: this.diffBranchB.id
			})
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				self.diffData = data;
			} else {
				throw new Error(data.message || 'Error al comparar');
			}
		})
		.catch(function(error) {
			self.showNotification('Error: ' + error.message, 'error');
		})
		.finally(function() {
			self.isLoadingDiff = false;
		});
	},

	/**
	 * Abre el historial de una branch
	 */
	openBranchHistory: function(branchId) {
		var self = this;

		var branch = branchId ? this.branches.find(function(branch) {
			return branch.id === branchId;
		}) : this.currentBranch;

		this.selectedHistoryBranch = branch;
		this.branchHistory = [];
		this.showHistoryModal = true;

		this.loadBranchHistory(branch.id);
	},

	/**
	 * Cierra el modal de historial
	 */
	closeHistoryModal: function() {
		this.showHistoryModal = false;
		this.selectedHistoryBranch = null;
		this.branchHistory = [];
	},

	/**
	 * Carga el historial de una branch
	 */
	loadBranchHistory: function(branchId) {
		var self = this;
		this.isLoadingHistory = true;

		fetch(VBP_Config.restUrl + 'branches/' + VBP_Config.postId + '/' + branchId + '/history', {
			method: 'GET',
			headers: {
				'X-WP-Nonce': VBP_Config.restNonce
			}
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				self.branchHistory = data.versions || [];
			} else {
				throw new Error(data.message || 'Error al cargar historial');
			}
		})
		.catch(function(error) {
			self.showNotification('Error: ' + error.message, 'error');
		})
		.finally(function() {
			self.isLoadingHistory = false;
		});
	},

	/**
	 * Restaura una versión del historial
	 */
	restoreFromHistory: function(versionId) {
		var self = this;

		var confirmMessage = VBP_Config.strings.confirmRestoreVersion ||
			'¿Restaurar esta versión? Se guardará una copia del estado actual.';

		if (!confirm(confirmMessage)) {
			return;
		}

		fetch(VBP_Config.restUrl + 'branches/' + VBP_Config.postId + '/' + this.selectedHistoryBranch.id + '/restore/' + versionId, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': VBP_Config.restNonce
			}
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				self.showNotification('Versión restaurada correctamente', 'success');

				// Actualizar contenido si es la rama actual
				if (self.currentBranch && self.currentBranch.id === self.selectedHistoryBranch.id) {
					var store = Alpine.store('vbp');
					if (store && data.content) {
						var elements = data.content.elements || data.content;
						store.elements = sanitizeElements(elements);
						if (data.content.settings) {
							store.settings = data.content.settings;
						}
					}
				}

				self.closeHistoryModal();
				self.loadBranchHistory(self.selectedHistoryBranch.id);
			} else {
				throw new Error(data.message || 'Error al restaurar');
			}
		})
		.catch(function(error) {
			self.showNotification('Error: ' + error.message, 'error');
		});
	},

	/**
	 * Archiva una branch
	 */
	archiveBranch: function(branchId) {
		var self = this;

		var branch = this.branches.find(function(branch) {
			return branch.id === branchId;
		});

		if (!branch) {
			return;
		}

		if (branch.is_main) {
			this.showNotification('No se puede archivar la rama principal', 'warning');
			return;
		}

		var confirmMessage = '¿Archivar la rama "' + branch.branch_name + '"? No podrás editarla pero podrás ver su contenido.';

		if (!confirm(confirmMessage)) {
			return;
		}

		fetch(VBP_Config.restUrl + 'branches/' + VBP_Config.postId + '/' + branchId, {
			method: 'DELETE',
			headers: {
				'X-WP-Nonce': VBP_Config.restNonce
			}
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				self.showNotification('Rama archivada correctamente', 'success');
				self.loadBranches();
			} else {
				throw new Error(data.message || 'Error al archivar');
			}
		})
		.catch(function(error) {
			self.showNotification('Error: ' + error.message, 'error');
		});
	},

	/**
	 * Obtiene la clase CSS para el tipo de diff
	 */
	getDiffTypeClass: function(type) {
		var classes = {
			added: 'vbp-diff-added',
			removed: 'vbp-diff-removed',
			modified: 'vbp-diff-modified'
		};
		return classes[type] || '';
	},

	/**
	 * Obtiene el icono para el tipo de diff
	 */
	getDiffTypeIcon: function(type) {
		var icons = {
			added: '<svg class="vbp-diff-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"/></svg>',
			removed: '<svg class="vbp-diff-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z"/></svg>',
			modified: '<svg class="vbp-diff-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>'
		};
		return icons[type] || '';
	},

	/**
	 * Obtiene la etiqueta para el estado de branch
	 */
	getBranchStatusLabel: function(status) {
		var labels = {
			active: 'Activa',
			merged: 'Fusionada',
			archived: 'Archivada'
		};
		return labels[status] || status;
	},

	/**
	 * Obtiene la clase CSS para el estado de branch
	 */
	getBranchStatusClass: function(status) {
		var classes = {
			active: 'vbp-branch-status-active',
			merged: 'vbp-branch-status-merged',
			archived: 'vbp-branch-status-archived'
		};
		return classes[status] || '';
	},

	/**
	 * Formatea una fecha relativa
	 */
	formatRelativeTime: function(dateString) {
		if (!dateString) {
			return '';
		}

		var date = new Date(dateString);
		var now = new Date();
		var diffInSeconds = Math.floor((now - date) / 1000);

		if (diffInSeconds < 60) {
			return 'Ahora mismo';
		}

		var diffInMinutes = Math.floor(diffInSeconds / 60);
		if (diffInMinutes < 60) {
			return 'Hace ' + diffInMinutes + ' minuto' + (diffInMinutes === 1 ? '' : 's');
		}

		var diffInHours = Math.floor(diffInMinutes / 60);
		if (diffInHours < 24) {
			return 'Hace ' + diffInHours + ' hora' + (diffInHours === 1 ? '' : 's');
		}

		var diffInDays = Math.floor(diffInHours / 24);
		if (diffInDays < 7) {
			return 'Hace ' + diffInDays + ' día' + (diffInDays === 1 ? '' : 's');
		}

		return date.toLocaleDateString('es-ES', {
			day: 'numeric',
			month: 'short',
			year: 'numeric'
		});
	},

	/**
	 * Muestra una notificación usando el sistema de VBP
	 */
	showNotification: function(message, type) {
		if (typeof VBPAppToast !== 'undefined' && VBPAppToast.show) {
			VBPAppToast.show(message, type);
		} else if (window.showNotification) {
			window.showNotification(message, type);
		} else {
			console.log('[VBP Branching] ' + type + ': ' + message);
		}
	}
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', function() {
		if (typeof VBP_Config !== 'undefined' && VBP_Config.postId) {
			VBPAppBranching.init();
		}
	});
} else {
	if (typeof VBP_Config !== 'undefined' && VBP_Config.postId) {
		VBPAppBranching.init();
	}
}

// Registrar en el store de Alpine si está disponible
document.addEventListener('alpine:init', function() {
	Alpine.store('vbpBranching', VBPAppBranching);
});

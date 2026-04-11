/**
 * Visual Builder Pro - Branch Panel UI Component
 * Panel de selección y gestión de ramas con Alpine.js
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

window.VBPBranchPanel = {
	/**
	 * Componente del selector de branch en la toolbar
	 */
	branchSelector: function() {
		return {
			isOpen: false,
			searchQuery: '',

			get branching() {
				return window.VBPAppBranching || Alpine.store('vbpBranching') || {};
			},

			get filteredBranches() {
				var branches = this.branching.branches || [];
				var query = this.searchQuery.toLowerCase().trim();

				if (!query) {
					return branches;
				}

				return branches.filter(function(branch) {
					return branch.branch_name.toLowerCase().includes(query) ||
						branch.branch_slug.toLowerCase().includes(query);
				});
			},

			get currentBranchName() {
				return this.branching.getCurrentBranchName ? this.branching.getCurrentBranchName() : 'main';
			},

			get isLoading() {
				return this.branching.isLoadingBranches || false;
			},

			get isCheckingOut() {
				return this.branching.isCheckingOut || false;
			},

			toggle: function() {
				this.isOpen = !this.isOpen;
				if (this.isOpen) {
					this.searchQuery = '';
					this.branching.loadBranches && this.branching.loadBranches();
				}
			},

			close: function() {
				this.isOpen = false;
				this.searchQuery = '';
			},

			selectBranch: function(branch) {
				if (this.branching.checkout) {
					this.branching.checkout(branch.id);
				}
				this.close();
			},

			createNewBranch: function() {
				if (this.branching.openCreateBranchModal) {
					this.branching.openCreateBranchModal();
				}
				this.close();
			},

			getBranchIcon: function(branch) {
				if (branch.is_main) {
					return '<svg class="vbp-branch-icon vbp-branch-icon-main" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5z"/></svg>';
				}
				if (branch.status === 'merged') {
					return '<svg class="vbp-branch-icon vbp-branch-icon-merged" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>';
				}
				if (branch.status === 'archived') {
					return '<svg class="vbp-branch-icon vbp-branch-icon-archived" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/></svg>';
				}
				return '<svg class="vbp-branch-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"/></svg>';
			}
		};
	},

	/**
	 * Componente del panel lateral de branches
	 */
	branchListPanel: function() {
		return {
			activeTab: 'active', // 'active', 'merged', 'archived'
			contextMenuBranch: null,
			contextMenuPosition: { x: 0, y: 0 },

			get branching() {
				return window.VBPAppBranching || Alpine.store('vbpBranching') || {};
			},

			get branches() {
				return this.branching.branches || [];
			},

			get filteredByStatus() {
				var status = this.activeTab;
				return this.branches.filter(function(branch) {
					if (status === 'active') {
						return branch.status === 'active';
					}
					return branch.status === status;
				});
			},

			get activeBranchesCount() {
				return this.branches.filter(function(b) { return b.status === 'active'; }).length;
			},

			get mergedBranchesCount() {
				return this.branches.filter(function(b) { return b.status === 'merged'; }).length;
			},

			get archivedBranchesCount() {
				return this.branches.filter(function(b) { return b.status === 'archived'; }).length;
			},

			setTab: function(tab) {
				this.activeTab = tab;
			},

			openContextMenu: function(event, branch) {
				event.preventDefault();
				this.contextMenuBranch = branch;
				this.contextMenuPosition = {
					x: event.clientX,
					y: event.clientY
				};
			},

			closeContextMenu: function() {
				this.contextMenuBranch = null;
			},

			checkout: function(branch) {
				this.branching.checkout && this.branching.checkout(branch.id);
				this.closeContextMenu();
			},

			viewHistory: function(branch) {
				this.branching.openBranchHistory && this.branching.openBranchHistory(branch.id);
				this.closeContextMenu();
			},

			compareToCurrent: function(branch) {
				var currentBranch = this.branching.currentBranch;
				if (currentBranch && this.branching.openDiffModal) {
					this.branching.openDiffModal(branch.id, currentBranch.id);
				}
				this.closeContextMenu();
			},

			mergeToMain: function(branch) {
				this.branching.openMergeModal && this.branching.openMergeModal(branch.id);
				this.closeContextMenu();
			},

			createBranchFrom: function(branch) {
				this.branching.openCreateBranchModal && this.branching.openCreateBranchModal(branch.id);
				this.closeContextMenu();
			},

			archiveBranch: function(branch) {
				this.branching.archiveBranch && this.branching.archiveBranch(branch.id);
				this.closeContextMenu();
			}
		};
	},

	/**
	 * Componente del modal de crear branch
	 */
	createBranchModal: function() {
		return {
			get branching() {
				return window.VBPAppBranching || Alpine.store('vbpBranching') || {};
			},

			get isOpen() {
				return this.branching.showCreateBranchModal || false;
			},

			get isCreating() {
				return this.branching.isCreatingBranch || false;
			},

			get fromBranchName() {
				if (!this.branching.newBranchFromId) {
					var currentBranch = this.branching.currentBranch;
					return currentBranch ? currentBranch.branch_name : 'main';
				}

				var branches = this.branching.branches || [];
				var fromBranch = branches.find(function(b) {
					return b.id === this.branching.newBranchFromId;
				}.bind(this));

				return fromBranch ? fromBranch.branch_name : 'main';
			},

			close: function() {
				this.branching.closeCreateBranchModal && this.branching.closeCreateBranchModal();
			},

			create: function() {
				this.branching.createBranch && this.branching.createBranch();
			},

			handleKeydown: function(event) {
				if (event.key === 'Escape') {
					this.close();
				} else if (event.key === 'Enter' && event.ctrlKey) {
					this.create();
				}
			}
		};
	},

	/**
	 * Componente del modal de merge
	 */
	mergeModal: function() {
		return {
			get branching() {
				return window.VBPAppBranching || Alpine.store('vbpBranching') || {};
			},

			get isOpen() {
				return this.branching.showMergeModal || false;
			},

			get isMerging() {
				return this.branching.isMerging || false;
			},

			get sourceBranch() {
				return this.branching.mergeSourceBranch || null;
			},

			get targetBranch() {
				return this.branching.mergeTargetBranch || null;
			},

			get availableTargets() {
				var branches = this.branching.branches || [];
				var sourceId = this.sourceBranch ? this.sourceBranch.id : null;

				return branches.filter(function(branch) {
					return branch.id !== sourceId && branch.status === 'active';
				});
			},

			close: function() {
				this.branching.closeMergeModal && this.branching.closeMergeModal();
			},

			selectTarget: function(branchId) {
				this.branching.selectMergeTarget && this.branching.selectMergeTarget(branchId);
			},

			merge: function() {
				this.branching.merge && this.branching.merge();
			}
		};
	},

	/**
	 * Componente del modal de conflictos
	 */
	conflictModal: function() {
		return {
			currentConflictIndex: 0,
			viewMode: 'split', // 'split', 'unified'

			get branching() {
				return window.VBPAppBranching || Alpine.store('vbpBranching') || {};
			},

			get isOpen() {
				return this.branching.showConflictModal || false;
			},

			get conflicts() {
				return this.branching.mergeConflicts || [];
			},

			get currentConflict() {
				return this.conflicts[this.currentConflictIndex] || null;
			},

			get resolvedCount() {
				var resolutions = this.branching.conflictResolutions || {};
				return Object.keys(resolutions).length;
			},

			get totalCount() {
				return this.conflicts.length;
			},

			get progress() {
				if (this.totalCount === 0) return 0;
				return Math.round((this.resolvedCount / this.totalCount) * 100);
			},

			isResolved: function(conflictElementId) {
				var resolutions = this.branching.conflictResolutions || {};
				return resolutions.hasOwnProperty(conflictElementId);
			},

			getResolution: function(conflictElementId) {
				var resolutions = this.branching.conflictResolutions || {};
				return resolutions[conflictElementId] || null;
			},

			resolveWithSource: function() {
				if (this.currentConflict) {
					this.branching.resolveConflict && this.branching.resolveConflict(
						this.currentConflict.element_id,
						'source'
					);
					this.nextConflict();
				}
			},

			resolveWithTarget: function() {
				if (this.currentConflict) {
					this.branching.resolveConflict && this.branching.resolveConflict(
						this.currentConflict.element_id,
						'target'
					);
					this.nextConflict();
				}
			},

			nextConflict: function() {
				if (this.currentConflictIndex < this.conflicts.length - 1) {
					this.currentConflictIndex++;
				}
			},

			previousConflict: function() {
				if (this.currentConflictIndex > 0) {
					this.currentConflictIndex--;
				}
			},

			goToConflict: function(index) {
				this.currentConflictIndex = index;
			},

			close: function() {
				this.branching.showConflictModal = false;
				this.currentConflictIndex = 0;
			},

			toggleViewMode: function() {
				this.viewMode = this.viewMode === 'split' ? 'unified' : 'split';
			}
		};
	},

	/**
	 * Componente del modal de diff
	 */
	diffModal: function() {
		return {
			viewMode: 'split', // 'split', 'unified'
			filterType: 'all', // 'all', 'added', 'removed', 'modified'

			get branching() {
				return window.VBPAppBranching || Alpine.store('vbpBranching') || {};
			},

			get isOpen() {
				return this.branching.showDiffModal || false;
			},

			get isLoading() {
				return this.branching.isLoadingDiff || false;
			},

			get branchA() {
				return this.branching.diffBranchA || null;
			},

			get branchB() {
				return this.branching.diffBranchB || null;
			},

			get diffData() {
				return this.branching.diffData || null;
			},

			get changes() {
				if (!this.diffData || !this.diffData.diff) return [];
				return this.diffData.diff.changes || [];
			},

			get filteredChanges() {
				if (this.filterType === 'all') {
					return this.changes;
				}
				return this.changes.filter(function(change) {
					return change.type === this.filterType;
				}.bind(this));
			},

			get stats() {
				if (!this.diffData) {
					return { added: 0, removed: 0, modified: 0 };
				}
				return this.diffData.stats || { added: 0, removed: 0, modified: 0 };
			},

			close: function() {
				this.branching.closeDiffModal && this.branching.closeDiffModal();
				this.filterType = 'all';
			},

			setFilter: function(type) {
				this.filterType = type;
			},

			toggleViewMode: function() {
				this.viewMode = this.viewMode === 'split' ? 'unified' : 'split';
			},

			getChangeIcon: function(type) {
				return this.branching.getDiffTypeIcon ? this.branching.getDiffTypeIcon(type) : '';
			},

			getChangeClass: function(type) {
				return this.branching.getDiffTypeClass ? this.branching.getDiffTypeClass(type) : '';
			},

			formatPath: function(path) {
				if (!path || !Array.isArray(path)) return '';
				return path.map(function(p) { return p.type; }).join(' > ');
			}
		};
	},

	/**
	 * Componente del modal de historial
	 */
	historyModal: function() {
		return {
			get branching() {
				return window.VBPAppBranching || Alpine.store('vbpBranching') || {};
			},

			get isOpen() {
				return this.branching.showHistoryModal || false;
			},

			get isLoading() {
				return this.branching.isLoadingHistory || false;
			},

			get branch() {
				return this.branching.selectedHistoryBranch || null;
			},

			get versions() {
				return this.branching.branchHistory || [];
			},

			close: function() {
				this.branching.closeHistoryModal && this.branching.closeHistoryModal();
			},

			restore: function(versionId) {
				this.branching.restoreFromHistory && this.branching.restoreFromHistory(versionId);
			},

			formatTime: function(dateString) {
				return this.branching.formatRelativeTime ?
					this.branching.formatRelativeTime(dateString) : dateString;
			}
		};
	}
};

// Registrar componentes en Alpine cuando esté disponible
document.addEventListener('alpine:init', function() {
	// Registrar como datos globales para uso en templates
	Alpine.data('vbpBranchSelector', VBPBranchPanel.branchSelector);
	Alpine.data('vbpBranchListPanel', VBPBranchPanel.branchListPanel);
	Alpine.data('vbpCreateBranchModal', VBPBranchPanel.createBranchModal);
	Alpine.data('vbpMergeModal', VBPBranchPanel.mergeModal);
	Alpine.data('vbpConflictModal', VBPBranchPanel.conflictModal);
	Alpine.data('vbpDiffModal', VBPBranchPanel.diffModal);
	Alpine.data('vbpHistoryModal', VBPBranchPanel.historyModal);
});

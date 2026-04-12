<?php
/**
 * Visual Builder Pro - Panel de Branches
 *
 * Sistema de ramas de diseño para trabajo paralelo y experimentación.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- Branch Selector en Toolbar -->
<div class="vbp-branch-selector" x-data="vbpBranchSelector()">
	<button
		type="button"
		class="vbp-branch-selector-trigger"
		:class="{ 'is-open': isOpen }"
		@click="toggle()"
		@click.away="close()"
		:title="'<?php esc_attr_e( 'Rama actual:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> ' + currentBranchName"
	>
		<svg class="vbp-branch-selector-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
			<path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"/>
		</svg>
		<span class="vbp-branch-selector-name" x-text="currentBranchName"><?php esc_html_e( 'main', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
		<svg class="vbp-branch-selector-arrow" viewBox="0 0 20 20" fill="currentColor" width="12" height="12">
			<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
		</svg>
	</button>

	<!-- Dropdown de branches -->
	<div
		class="vbp-branch-selector-dropdown"
		x-show="isOpen"
		x-transition:enter="transition ease-out duration-150"
		x-transition:enter-start="opacity-0 transform scale-95"
		x-transition:enter-end="opacity-100 transform scale-100"
		x-transition:leave="transition ease-in duration-100"
		x-transition:leave-start="opacity-100 transform scale-100"
		x-transition:leave-end="opacity-0 transform scale-95"
	>
		<!-- Busqueda -->
		<div class="vbp-branch-selector-search">
			<input
				type="text"
				x-model="searchQuery"
				placeholder="<?php esc_attr_e( 'Buscar rama...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
				@keydown.escape="close()"
			>
		</div>

		<!-- Loading -->
		<template x-if="isLoading">
			<div class="vbp-branch-loading">
				<div class="vbp-branch-spinner"></div>
			</div>
		</template>

		<!-- Lista de branches -->
		<div class="vbp-branch-selector-list" x-show="!isLoading">
			<template x-for="branch in filteredBranches" :key="branch.id">
				<div
					class="vbp-branch-selector-item"
					:class="{ 'is-current': branch.is_active }"
					@click="selectBranch(branch)"
				>
					<span x-html="getBranchIcon(branch)"></span>
					<div class="vbp-branch-selector-item-info">
						<div class="vbp-branch-selector-item-name" x-text="branch.branch_name"></div>
						<div class="vbp-branch-selector-item-meta">
							<span x-text="branch.created_by_name"></span>
							<span>&bull;</span>
							<span x-text="branch.version_count + ' <?php echo esc_js( __( 'versiones', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'"></span>
						</div>
					</div>
					<template x-if="branch.is_active">
						<span class="vbp-branch-selector-item-badge"><?php esc_html_e( 'Activa', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
					</template>
				</div>
			</template>

			<!-- Mensaje vacio -->
			<template x-if="filteredBranches.length === 0 && !isLoading">
				<div class="vbp-branch-panel-empty">
					<p><?php esc_html_e( 'No se encontraron ramas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
				</div>
			</template>
		</div>

		<!-- Footer con boton crear -->
		<div class="vbp-branch-selector-footer">
			<button
				type="button"
				class="vbp-branch-selector-create"
				@click="createNewBranch()"
			>
				<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
					<path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
				</svg>
				<?php esc_html_e( 'Crear nueva rama', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Indicador de cambios sin guardar -->
<template x-if="branching.hasUnsavedChanges">
	<div class="vbp-branch-unsaved-indicator">
		<span class="vbp-branch-unsaved-dot"></span>
		<?php esc_html_e( 'Sin guardar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
	</div>
</template>

<!-- ========================================
     Modal: Crear Nueva Branch
     ======================================== -->
<div
	class="vbp-branch-modal-overlay"
	x-data="vbpCreateBranchModal()"
	:class="{ 'is-open': isOpen }"
	@keydown.escape="close()"
>
	<div class="vbp-branch-modal" @click.away="close()">
		<div class="vbp-branch-modal-header">
			<h3 class="vbp-branch-modal-title"><?php esc_html_e( 'Crear nueva rama', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
			<button type="button" class="vbp-branch-modal-close" @click="close()">
				<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
				</svg>
			</button>
		</div>

		<div class="vbp-branch-modal-body">
			<div class="vbp-branch-form-info">
				<svg class="vbp-branch-form-info-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
					<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"/>
				</svg>
				<span><?php esc_html_e( 'Se creara desde la rama:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> <strong x-text="fromBranchName"></strong></span>
			</div>

			<div class="vbp-branch-form-group">
				<label class="vbp-branch-form-label" for="branch-name"><?php esc_html_e( 'Nombre de la rama', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> *</label>
				<input
					type="text"
					id="branch-name"
					class="vbp-branch-form-input"
					x-model="branching.newBranchName"
					placeholder="<?php esc_attr_e( 'ej: experimento-hero-v2', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
					@keydown.enter="create()"
					autofocus
				>
				<p class="vbp-branch-form-hint"><?php esc_html_e( 'Usa un nombre descriptivo para identificar los cambios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
			</div>

			<div class="vbp-branch-form-group">
				<label class="vbp-branch-form-label" for="branch-description"><?php esc_html_e( 'Descripcion (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
				<textarea
					id="branch-description"
					class="vbp-branch-form-input vbp-branch-form-textarea"
					x-model="branching.newBranchDescription"
					placeholder="<?php esc_attr_e( 'Describe el proposito de esta rama...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
					rows="3"
				></textarea>
			</div>
		</div>

		<div class="vbp-branch-modal-footer">
			<button type="button" class="vbp-branch-btn vbp-branch-btn-secondary" @click="close()">
				<?php esc_html_e( 'Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
			</button>
			<button
				type="button"
				class="vbp-branch-btn vbp-branch-btn-primary"
				@click="create()"
				:disabled="isCreating || !branching.newBranchName.trim()"
			>
				<template x-if="isCreating">
					<div class="vbp-branch-spinner" style="width: 16px; height: 16px; border-width: 2px;"></div>
				</template>
				<template x-if="!isCreating">
					<span><?php esc_html_e( 'Crear rama', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
				</template>
			</button>
		</div>
	</div>
</div>

<!-- ========================================
     Modal: Merge de Branches
     ======================================== -->
<div
	class="vbp-branch-modal-overlay"
	x-data="vbpMergeModal()"
	:class="{ 'is-open': isOpen }"
	@keydown.escape="close()"
>
	<div class="vbp-branch-modal" @click.away="close()">
		<div class="vbp-branch-modal-header">
			<h3 class="vbp-branch-modal-title"><?php esc_html_e( 'Fusionar ramas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
			<button type="button" class="vbp-branch-modal-close" @click="close()">
				<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
				</svg>
			</button>
		</div>

		<div class="vbp-branch-modal-body">
			<!-- Visualizacion del merge -->
			<div class="vbp-branch-merge-flow">
				<div class="vbp-branch-merge-source">
					<span class="vbp-branch-merge-label"><?php esc_html_e( 'Desde', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
					<div class="vbp-branch-merge-branch" x-show="sourceBranch">
						<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
							<path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"/>
						</svg>
						<span x-text="sourceBranch?.branch_name"></span>
					</div>
				</div>
				<div class="vbp-branch-merge-arrow">
					<svg viewBox="0 0 20 20" fill="currentColor" width="24" height="24">
						<path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"/>
					</svg>
				</div>
				<div class="vbp-branch-merge-target">
					<span class="vbp-branch-merge-label"><?php esc_html_e( 'Hacia', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
					<div class="vbp-branch-merge-branch" x-show="targetBranch">
						<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
							<path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"/>
						</svg>
						<span x-text="targetBranch?.branch_name"></span>
					</div>
				</div>
			</div>

			<!-- Selector de rama destino -->
			<div class="vbp-branch-form-group">
				<label class="vbp-branch-form-label"><?php esc_html_e( 'Seleccionar rama destino', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
				<div class="vbp-branch-merge-targets">
					<template x-for="branch in availableTargets" :key="branch.id">
						<button
							type="button"
							class="vbp-branch-merge-target-btn"
							:class="{ 'is-selected': targetBranch?.id === branch.id }"
							@click="selectTarget(branch.id)"
						>
							<svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
								<path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"/>
							</svg>
							<span x-text="branch.branch_name"></span>
							<template x-if="branch.is_main">
								<span class="vbp-branch-status vbp-branch-status-active"><?php esc_html_e( 'main', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
							</template>
						</button>
					</template>
				</div>
			</div>
		</div>

		<div class="vbp-branch-modal-footer">
			<button type="button" class="vbp-branch-btn vbp-branch-btn-secondary" @click="close()">
				<?php esc_html_e( 'Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
			</button>
			<button
				type="button"
				class="vbp-branch-btn vbp-branch-btn-primary"
				@click="merge()"
				:disabled="isMerging || !sourceBranch || !targetBranch"
			>
				<template x-if="isMerging">
					<div class="vbp-branch-spinner" style="width: 16px; height: 16px; border-width: 2px;"></div>
				</template>
				<template x-if="!isMerging">
					<span><?php esc_html_e( 'Fusionar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
				</template>
			</button>
		</div>
	</div>
</div>

<!-- ========================================
     Modal: Resolucion de Conflictos
     ======================================== -->
<div
	class="vbp-branch-modal-overlay"
	x-data="vbpConflictModal()"
	:class="{ 'is-open': isOpen }"
>
	<div class="vbp-branch-modal is-wide" @click.away="close()">
		<div class="vbp-branch-modal-header">
			<h3 class="vbp-branch-modal-title"><?php esc_html_e( 'Resolver conflictos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
			<button type="button" class="vbp-branch-modal-close" @click="close()">
				<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
				</svg>
			</button>
		</div>

		<div class="vbp-branch-modal-body">
			<!-- Barra de progreso -->
			<div class="vbp-conflict-progress">
				<div class="vbp-conflict-progress-bar">
					<div class="vbp-conflict-progress-fill" :style="{ width: progress + '%' }"></div>
				</div>
				<span class="vbp-conflict-progress-text" x-text="resolvedCount + '/' + totalCount + ' <?php echo esc_js( __( 'resueltos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'"></span>
			</div>

			<!-- Navegacion entre conflictos -->
			<div class="vbp-conflict-nav">
				<div class="vbp-conflict-nav-buttons">
					<button type="button" class="vbp-conflict-nav-btn" @click="previousConflict()" :disabled="currentConflictIndex === 0">
						<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
							<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"/>
						</svg>
					</button>
					<button type="button" class="vbp-conflict-nav-btn" @click="nextConflict()" :disabled="currentConflictIndex >= conflicts.length - 1">
						<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
							<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/>
						</svg>
					</button>
				</div>
				<span class="vbp-conflict-nav-indicator">
					<?php esc_html_e( 'Conflicto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
					<span x-text="currentConflictIndex + 1"></span>
					<?php esc_html_e( 'de', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
					<span x-text="totalCount"></span>
				</span>
			</div>

			<!-- Conflicto actual -->
			<template x-if="currentConflict">
				<div class="vbp-conflict-card">
					<div class="vbp-conflict-card-header">
						<svg class="vbp-conflict-card-icon" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"/>
						</svg>
						<span class="vbp-conflict-card-title" x-text="'<?php echo esc_js( __( 'Elemento:', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?> ' + currentConflict.element_id"></span>
					</div>

					<div class="vbp-conflict-options">
						<!-- Opcion: Usar Source -->
						<div class="vbp-conflict-option" :class="{ 'is-selected': getResolution(currentConflict.element_id) === 'source' }">
							<div class="vbp-conflict-option-header">
								<span class="vbp-conflict-option-label"><?php esc_html_e( 'Rama origen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
								<button type="button" class="vbp-conflict-option-btn" @click="resolveWithSource()">
									<?php esc_html_e( 'Usar esta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
								</button>
							</div>
							<div class="vbp-conflict-option-preview">
								<pre x-text="JSON.stringify(currentConflict.source_value, null, 2)"></pre>
							</div>
						</div>

						<!-- Opcion: Usar Target -->
						<div class="vbp-conflict-option" :class="{ 'is-selected': getResolution(currentConflict.element_id) === 'target' }">
							<div class="vbp-conflict-option-header">
								<span class="vbp-conflict-option-label"><?php esc_html_e( 'Rama destino', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
								<button type="button" class="vbp-conflict-option-btn" @click="resolveWithTarget()">
									<?php esc_html_e( 'Usar esta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
								</button>
							</div>
							<div class="vbp-conflict-option-preview">
								<pre x-text="JSON.stringify(currentConflict.target_value, null, 2)"></pre>
							</div>
						</div>
					</div>
				</div>
			</template>
		</div>

		<div class="vbp-branch-modal-footer">
			<button type="button" class="vbp-branch-btn vbp-branch-btn-secondary" @click="close()">
				<?php esc_html_e( 'Cancelar merge', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
			</button>
		</div>
	</div>
</div>

<!-- ========================================
     Modal: Diff Visual
     ======================================== -->
<div
	class="vbp-branch-modal-overlay"
	x-data="vbpDiffModal()"
	:class="{ 'is-open': isOpen }"
	@keydown.escape="close()"
>
	<div class="vbp-branch-modal is-wide" @click.away="close()">
		<div class="vbp-branch-modal-header">
			<h3 class="vbp-branch-modal-title"><?php esc_html_e( 'Comparar ramas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
			<div style="display: flex; align-items: center; gap: 8px;">
				<span x-show="branchA && branchB" style="font-size: 13px; color: var(--vbp-text-muted);">
					<span x-text="branchA?.branch_name"></span>
					<span style="margin: 0 4px;">vs</span>
					<span x-text="branchB?.branch_name"></span>
				</span>
				<button type="button" class="vbp-branch-modal-close" @click="close()">
					<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
						<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
					</svg>
				</button>
			</div>
		</div>

		<div class="vbp-branch-modal-body">
			<!-- Loading -->
			<template x-if="isLoading">
				<div class="vbp-branch-loading" style="padding: 60px;">
					<div class="vbp-branch-spinner"></div>
				</div>
			</template>

			<template x-if="!isLoading && diffData">
				<div>
					<!-- Estadisticas -->
					<div class="vbp-diff-stats">
						<div class="vbp-diff-stat vbp-diff-stat-added">
							<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"/>
							</svg>
							<span class="vbp-diff-stat-count" x-text="stats.added"></span>
							<span><?php esc_html_e( 'anadidos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
						</div>
						<div class="vbp-diff-stat vbp-diff-stat-removed">
							<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z"/>
							</svg>
							<span class="vbp-diff-stat-count" x-text="stats.removed"></span>
							<span><?php esc_html_e( 'eliminados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
						</div>
						<div class="vbp-diff-stat vbp-diff-stat-modified">
							<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
								<path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
							</svg>
							<span class="vbp-diff-stat-count" x-text="stats.modified"></span>
							<span><?php esc_html_e( 'modificados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
						</div>
					</div>

					<!-- Filtros -->
					<div class="vbp-diff-toolbar">
						<div class="vbp-diff-filters">
							<button type="button" class="vbp-diff-filter" :class="{ 'is-active': filterType === 'all' }" @click="setFilter('all')">
								<?php esc_html_e( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> (<span x-text="changes.length"></span>)
							</button>
							<button type="button" class="vbp-diff-filter" :class="{ 'is-active': filterType === 'added' }" @click="setFilter('added')">
								<?php esc_html_e( 'Anadidos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
							</button>
							<button type="button" class="vbp-diff-filter" :class="{ 'is-active': filterType === 'removed' }" @click="setFilter('removed')">
								<?php esc_html_e( 'Eliminados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
							</button>
							<button type="button" class="vbp-diff-filter" :class="{ 'is-active': filterType === 'modified' }" @click="setFilter('modified')">
								<?php esc_html_e( 'Modificados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
							</button>
						</div>
					</div>

					<!-- Lista de cambios -->
					<div class="vbp-diff-changes">
						<template x-for="change in filteredChanges" :key="change.id">
							<div class="vbp-diff-change" :class="getChangeClass(change.type)">
								<div class="vbp-diff-change-header">
									<span x-html="getChangeIcon(change.type)"></span>
									<span class="vbp-diff-change-type" x-text="change.type"></span>
									<code class="vbp-diff-change-id" x-text="change.id"></code>
									<span class="vbp-diff-change-path" x-text="formatPath(change.path)"></span>
								</div>

								<!-- Propiedades modificadas -->
								<template x-if="change.type === 'modified' && change.changes">
									<div class="vbp-diff-change-body">
										<div class="vbp-diff-properties">
											<template x-for="(prop, propIndex) in change.changes" :key="'prop-' + propIndex">
												<div class="vbp-diff-property">
													<span class="vbp-diff-property-name" x-text="prop.property"></span>
													<span class="vbp-diff-property-old" x-text="JSON.stringify(prop.old_value)"></span>
													<span class="vbp-diff-property-new" x-text="JSON.stringify(prop.new_value)"></span>
												</div>
											</template>
										</div>
									</div>
								</template>
							</div>
						</template>

						<!-- Sin cambios -->
						<template x-if="filteredChanges.length === 0">
							<div class="vbp-branch-panel-empty">
								<p><?php esc_html_e( 'No hay cambios que mostrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
							</div>
						</template>
					</div>
				</div>
			</template>
		</div>

		<div class="vbp-branch-modal-footer">
			<button type="button" class="vbp-branch-btn vbp-branch-btn-secondary" @click="close()">
				<?php esc_html_e( 'Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
			</button>
		</div>
	</div>
</div>

<!-- ========================================
     Modal: Historial de Branch
     ======================================== -->
<div
	class="vbp-branch-modal-overlay"
	x-data="vbpHistoryModal()"
	:class="{ 'is-open': isOpen }"
	@keydown.escape="close()"
>
	<div class="vbp-branch-modal" @click.away="close()">
		<div class="vbp-branch-modal-header">
			<h3 class="vbp-branch-modal-title">
				<?php esc_html_e( 'Historial:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
				<span x-text="branch?.branch_name"></span>
			</h3>
			<button type="button" class="vbp-branch-modal-close" @click="close()">
				<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
				</svg>
			</button>
		</div>

		<div class="vbp-branch-modal-body">
			<!-- Loading -->
			<template x-if="isLoading">
				<div class="vbp-branch-loading" style="padding: 40px;">
					<div class="vbp-branch-spinner"></div>
				</div>
			</template>

			<!-- Timeline -->
			<template x-if="!isLoading">
				<div class="vbp-branch-history">
					<template x-for="(version, index) in versions" :key="version.id">
						<div class="vbp-branch-history-item">
							<div class="vbp-branch-history-dot">
								<template x-if="index === 0">
									<svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12">
										<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
									</svg>
								</template>
							</div>
							<div class="vbp-branch-history-content">
								<div class="vbp-branch-history-header">
									<span class="vbp-branch-history-message" x-text="version.message"></span>
									<button
										type="button"
										class="vbp-branch-history-restore"
										@click="restore(version.id)"
										x-show="index > 0"
									>
										<?php esc_html_e( 'Restaurar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
									</button>
								</div>
								<div class="vbp-branch-history-meta">
									<span x-text="version.created_by_name || '<?php echo esc_js( __( 'Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>'"></span>
									<span>&bull;</span>
									<span x-text="formatTime(version.created_at)"></span>
								</div>
							</div>
						</div>
					</template>

					<!-- Sin historial -->
					<template x-if="versions.length === 0">
						<div class="vbp-branch-panel-empty">
							<p><?php esc_html_e( 'No hay versiones en esta rama', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
						</div>
					</template>
				</div>
			</template>
		</div>

		<div class="vbp-branch-modal-footer">
			<button type="button" class="vbp-branch-btn vbp-branch-btn-secondary" @click="close()">
				<?php esc_html_e( 'Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
			</button>
		</div>
	</div>
</div>

<style>
/* Estilos adicionales para el merge flow */
.vbp-branch-merge-flow {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 20px;
	padding: 24px;
	background: var(--vbp-surface-2, #1f2937);
	border-radius: 8px;
	margin-bottom: 20px;
}

.vbp-branch-merge-source,
.vbp-branch-merge-target {
	text-align: center;
}

.vbp-branch-merge-label {
	display: block;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
	color: var(--vbp-text-muted, #9ca3af);
	margin-bottom: 8px;
}

.vbp-branch-merge-branch {
	display: flex;
	align-items: center;
	gap: 6px;
	padding: 10px 16px;
	background: var(--vbp-surface-3, #374151);
	border-radius: 6px;
	font-size: 14px;
	font-weight: 500;
	color: var(--vbp-text, #f3f4f6);
}

.vbp-branch-merge-branch svg {
	color: var(--vbp-branch-primary);
}

.vbp-branch-merge-arrow {
	color: var(--vbp-text-muted, #9ca3af);
}

.vbp-branch-merge-targets {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.vbp-branch-merge-target-btn {
	display: flex;
	align-items: center;
	gap: 8px;
	width: 100%;
	padding: 12px 16px;
	background: var(--vbp-surface-2, #1f2937);
	border: 1px solid var(--vbp-border, #374151);
	border-radius: 6px;
	color: var(--vbp-text, #f3f4f6);
	font-size: 14px;
	text-align: left;
	cursor: pointer;
	transition: all 0.15s ease;
}

.vbp-branch-merge-target-btn:hover {
	background: var(--vbp-surface-3, #374151);
	border-color: var(--vbp-branch-primary);
}

.vbp-branch-merge-target-btn.is-selected {
	background: rgba(99, 102, 241, 0.15);
	border-color: var(--vbp-branch-primary);
}

.vbp-branch-merge-target-btn svg {
	color: var(--vbp-branch-primary);
}
</style>

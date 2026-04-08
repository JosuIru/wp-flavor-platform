/**
 * App Releases JavaScript
 *
 * @package Flavor_Chat_IA
 */

(function ($) {
	'use strict';

	let currentReleaseId = null;

	/**
     * Initialize
     */
	function init() {
		bindEvents();
		loadReleases();
	}

	/**
     * Bind events
     */
	function bindEvents() {
		// Filters
		$('#filter-app-type, #filter-channel, #filter-status').on('change', loadReleases);

		// New release button
		$('#new-release-btn').on('click', openNewReleaseModal);

		// Modal close
		$('.modal-close').on('click', closeModal);
		$('.flavor-modal').on('click', function (e) {
			if ($(e.target).hasClass('flavor-modal')) {
				closeModal();
			}
		});

		// Form submit
		$('#release-form').on('submit', handleFormSubmit);

		// File upload
		setupFileUpload();

		// Release actions (delegated)
		$(document).on('click', '.edit-release', handleEditRelease);
		$(document).on('click', '.delete-release', handleDeleteRelease);
		$(document).on('click', '.publish-release', handlePublishRelease);
		$(document).on('click', '.show-qr', handleShowQr);
		$(document).on('click', '.download-release', handleDownload);
	}

	/**
     * Load releases
     */
	function loadReleases() {
		const $list = $('#releases-list');
		$list.html('<div class="loading">Cargando releases...</div>');

		$.ajax({
			url: flavorReleases.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_release_list',
				nonce: flavorReleases.nonce,
				app_type: $('#filter-app-type').val(),
				channel: $('#filter-channel').val(),
				status: $('#filter-status').val()
			},
			success: function (response) {
				if (response.success) {
					renderReleases(response.data);
				} else {
					$list.html('<div class="empty-state"><span class="dashicons dashicons-warning"></span><p>Error al cargar releases</p></div>');
				}
			},
			error: function () {
				$list.html('<div class="empty-state"><span class="dashicons dashicons-warning"></span><p>Error de conexión</p></div>');
			}
		});
	}

	/**
     * Render releases list
     */
	function renderReleases(releases) {
		const $list = $('#releases-list');

		if (!releases || releases.length === 0) {
			$list.html('<div class="empty-state"><span class="dashicons dashicons-cloud-upload"></span><p>No hay releases. ¡Crea la primera!</p></div>');
			return;
		}

		let html = '';
		releases.forEach(function (release) {
			const statusClass = release.is_published == 1 ? 'published' : 'draft';
			const statusText = release.is_published == 1 ? 'Publicada' : 'Borrador';
			const channelClass = release.channel;
			const platformIcon = release.platform === 'ios' ? 'dashicons-apple' : 'dashicons-smartphone';

			html += `
                <div class="release-item" data-id="${release.id}">
                    <div class="release-version">
                        <span class="version-number">v${escapeHtml(release.version)}</span>
                        <span class="build-number">#${release.build_number}</span>
                    </div>

                    <div class="release-info">
                        <div class="release-meta">
                            <span class="meta-item">
                                <span class="dashicons ${platformIcon}"></span>
                                ${release.platform.toUpperCase()}
                            </span>
                            <span class="meta-item">
                                <span class="dashicons dashicons-admin-users"></span>
                                ${release.app_type === 'admin' ? 'Admin' : 'Cliente'}
                            </span>
                            <span class="meta-item">
                                <span class="dashicons dashicons-portfolio"></span>
                                ${release.file_size_formatted || '-'}
                            </span>
                            <span class="meta-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                ${release.created_at_formatted}
                            </span>
                        </div>
                        ${release.changelog ? `<div class="release-changelog">${escapeHtml(release.changelog.substring(0, 100))}...</div>` : ''}
                    </div>

                    <div class="release-status">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                        <span class="channel-badge ${channelClass}">${release.channel.toUpperCase()}</span>
                    </div>

                    <div class="release-downloads">
                        <span class="downloads-count">${release.downloads || 0}</span>
                        <span class="downloads-label">descargas</span>
                    </div>

                    <div class="release-actions">
                        ${release.is_published != 1 ? `
                            <button type="button" class="button publish-release" data-id="${release.id}" title="Publicar">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        ` : ''}
                        ${release.download_url ? `
                            <button type="button" class="button show-qr" data-url="${release.download_url}" data-version="${release.version}" title="Ver QR">
                                <span class="dashicons dashicons-screenoptions"></span>
                            </button>
                            <a href="${release.download_url}" class="button download-release" title="Descargar" target="_blank">
                                <span class="dashicons dashicons-download"></span>
                            </a>
                        ` : ''}
                        <button type="button" class="button edit-release" data-id="${release.id}" title="Editar">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button delete-release" data-id="${release.id}" title="Eliminar">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `;
		});

		$list.html(html);
	}

	/**
     * Open new release modal
     */
	function openNewReleaseModal() {
		currentReleaseId = null;
		$('#modal-title').text('Nueva Release');
		$('#release-form')[0].reset();
		$('#release_id').val('');
		resetFileUpload();
		$('#release-modal').show();
	}

	/**
     * Close modal
     */
	function closeModal() {
		$('.flavor-modal').hide();
	}

	/**
     * Handle edit release
     */
	function handleEditRelease() {
		const id = $(this).data('id');
		const $item = $(this).closest('.release-item');

		// Por simplicidad, recargamos los datos de la lista
		// En una implementación completa, haríamos una petición al servidor
		currentReleaseId = id;
		$('#modal-title').text('Editar Release');
		$('#release_id').val(id);

		// Rellenar formulario con datos del item
		// Esto es una simplificación; normalmente obtendríamos datos del servidor

		$('#release-modal').show();
	}

	/**
     * Handle delete release
     */
	function handleDeleteRelease() {
		if (!confirm(flavorReleases.i18n.confirmDelete)) {
			return;
		}

		const id = $(this).data('id');

		$.ajax({
			url: flavorReleases.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_release_delete',
				nonce: flavorReleases.nonce,
				release_id: id
			},
			success: function (response) {
				if (response.success) {
					loadReleases();
					showNotice('Release eliminada', 'success');
				} else {
					showNotice(response.data || 'Error', 'error');
				}
			}
		});
	}

	/**
     * Handle publish release
     */
	function handlePublishRelease() {
		if (!confirm(flavorReleases.i18n.confirmPublish)) {
			return;
		}

		const id = $(this).data('id');
		const $btn = $(this);
		$btn.prop('disabled', true);

		$.ajax({
			url: flavorReleases.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_release_publish',
				nonce: flavorReleases.nonce,
				release_id: id
			},
			success: function (response) {
				if (response.success) {
					loadReleases();
					showNotice('Release v' + response.data.version + ' publicada', 'success');
				} else {
					showNotice(response.data || 'Error', 'error');
				}
			},
			complete: function () {
				$btn.prop('disabled', false);
			}
		});
	}

	/**
     * Handle show QR
     */
	function handleShowQr() {
		const url = $(this).data('url');
		const version = $(this).data('version');

		$('.qr-version').text('v' + version);
		$('.qr-url').text(url);

		// Generate QR
		const $qr = $('#qr-code');
		$qr.empty();

		if (typeof QRCode !== 'undefined') {
			QRCode.toCanvas(document.createElement('canvas'), url, {
				width: 200,
				margin: 2
			}, function (error, canvas) {
				if (!error) {
					$qr.append(canvas);
				}
			});
		}

		$('#qr-modal').show();
	}

	/**
     * Handle form submit
     */
	function handleFormSubmit(e) {
		e.preventDefault();

		const $btn = $('#save-release');
		$btn.prop('disabled', true).text(flavorReleases.i18n.uploading);

		const formData = {
			action: currentReleaseId ? 'flavor_release_update' : 'flavor_release_create',
			nonce: flavorReleases.nonce,
			release_id: currentReleaseId || '',
			version: $('#release_version').val(),
			build_number: $('#release_build').val(),
			app_type: $('#release_app_type').val(),
			platform: $('#release_platform').val(),
			channel: $('#release_channel').val(),
			changelog: $('#release_changelog').val(),
			min_os_version: $('#release_min_os').val(),
			is_mandatory: $('#release_mandatory').is(':checked') ? 1 : 0,
			file_name: $('#file_name').val(),
			file_size: $('#file_size').val(),
			file_hash: $('#file_hash').val()
		};

		$.ajax({
			url: flavorReleases.ajaxUrl,
			type: 'POST',
			data: formData,
			success: function (response) {
				if (response.success) {
					closeModal();
					loadReleases();
					showNotice(flavorReleases.i18n.saved, 'success');
				} else {
					showNotice(response.data || flavorReleases.i18n.error, 'error');
				}
			},
			error: function () {
				showNotice(flavorReleases.i18n.error, 'error');
			},
			complete: function () {
				$btn.prop('disabled', false).text('Guardar Release');
			}
		});
	}

	/**
     * Setup file upload
     */
	function setupFileUpload() {
		const $area = $('#apk-upload-area');
		const $input = $('#apk_file');
		const $placeholder = $('#upload-placeholder');
		const $progress = $('#upload-progress');
		const $uploaded = $('#uploaded-file');

		// Click to select
		$area.on('click', function (e) {
			if (!$(e.target).hasClass('remove-file')) {
				$input.click();
			}
		});

		// Drag and drop
		$area.on('dragover dragenter', function (e) {
			e.preventDefault();
			$area.addClass('drag-over');
		});

		$area.on('dragleave dragend', function (e) {
			e.preventDefault();
			$area.removeClass('drag-over');
		});

		$area.on('drop', function (e) {
			e.preventDefault();
			$area.removeClass('drag-over');
			const files = e.originalEvent.dataTransfer.files;
			if (files.length) {
				uploadFile(files[0]);
			}
		});

		// File input change
		$input.on('change', function () {
			if (this.files.length) {
				uploadFile(this.files[0]);
			}
		});

		// Remove file
		$uploaded.find('.remove-file').on('click', function (e) {
			e.stopPropagation();
			resetFileUpload();
		});
	}

	/**
     * Upload file
     */
	function uploadFile(file) {
		const $placeholder = $('#upload-placeholder');
		const $progress = $('#upload-progress');
		const $uploaded = $('#uploaded-file');

		// Validate file type
		const allowedTypes = ['apk', 'aab', 'ipa'];
		const ext = file.name.split('.').pop().toLowerCase();
		if (!allowedTypes.includes(ext)) {
			showNotice('Tipo de archivo no permitido', 'error');
			return;
		}

		$placeholder.hide();
		$progress.show();
		$uploaded.hide();

		const formData = new FormData();
		formData.append('action', 'flavor_release_upload');
		formData.append('nonce', flavorReleases.nonce);
		formData.append('apk_file', file);

		$.ajax({
			url: flavorReleases.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			xhr: function () {
				const xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener('progress', function (e) {
					if (e.lengthComputable) {
						const percent = Math.round((e.loaded / e.total) * 100);
						$progress.find('.progress-bar').css('--progress', percent + '%');
						$progress.find('.progress-text').text(percent + '%');
					}
				});
				return xhr;
			},
			success: function (response) {
				if (response.success) {
					$('#file_name').val(response.data.file_name);
					$('#file_size').val(response.data.file_size);
					$('#file_hash').val(response.data.file_hash);

					$uploaded.find('.file-name').text(response.data.file_name);
					$uploaded.find('.file-size').text(formatFileSize(response.data.file_size));

					$progress.hide();
					$uploaded.show();
				} else {
					showNotice(response.data || 'Error al subir', 'error');
					resetFileUpload();
				}
			},
			error: function () {
				showNotice('Error al subir archivo', 'error');
				resetFileUpload();
			}
		});
	}

	/**
     * Reset file upload
     */
	function resetFileUpload() {
		$('#upload-placeholder').show();
		$('#upload-progress').hide();
		$('#uploaded-file').hide();
		$('#apk_file').val('');
		$('#file_name').val('');
		$('#file_size').val('');
		$('#file_hash').val('');
	}

	/**
     * Format file size
     */
	function formatFileSize(bytes) {
		if (bytes === 0) {return '0 Bytes';}
		const k = 1024;
		const sizes = ['Bytes', 'KB', 'MB', 'GB'];
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
	}

	/**
     * Show notice
     */
	function showNotice(message, type) {
		const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
		$('.flavor-app-releases-wrap h1').after($notice);

		setTimeout(function () {
			$notice.fadeOut(function () {
				$(this).remove();
			});
		}, 5000);
	}

	/**
     * Escape HTML
     */
	function escapeHtml(text) {
		if (!text) {return '';}
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Initialize on document ready
	$(document).ready(init);

})(jQuery);

<?php
/**
 * Vista: Gestor de Medios de Radio
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener estadísticas
global $wpdb;
$tabla_audios = $wpdb->prefix . 'flavor_radio_audios';
$tabla_playlists = $wpdb->prefix . 'flavor_radio_playlists';

$stats = [
    'total_audios' => 0,
    'canciones' => 0,
    'podcasts' => 0,
    'jingles' => 0,
    'duracion_total' => 0,
    'playlists' => 0,
];

if (Flavor_Platform_Helpers::tabla_existe($tabla_audios)) {
    $stats['total_audios'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_audios WHERE activo = 1");
    $stats['canciones'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_audios WHERE activo = 1 AND tipo = 'cancion'");
    $stats['podcasts'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_audios WHERE activo = 1 AND tipo = 'podcast'");
    $stats['jingles'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_audios WHERE activo = 1 AND tipo IN ('jingle', 'cortina')");
    $stats['duracion_total'] = $wpdb->get_var("SELECT SUM(duracion_segundos) FROM $tabla_audios WHERE activo = 1");
}

if (Flavor_Platform_Helpers::tabla_existe($tabla_playlists)) {
    $stats['playlists'] = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_playlists WHERE activa = 1");
}

// Formatear duración total
$horas = floor($stats['duracion_total'] / 3600);
$minutos = floor(($stats['duracion_total'] % 3600) / 60);
?>

<div class="wrap" x-data="radioMediaManager()">
    <h1>
        <span class="dashicons dashicons-format-audio"></span>
        <?php _e('Gestor de Medios - Radio', 'flavor-platform'); ?>
    </h1>

    <!-- Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="#" class="nav-tab" :class="{ 'nav-tab-active': tab === 'biblioteca' }" @click.prevent="tab = 'biblioteca'">
            <span class="dashicons dashicons-playlist-audio"></span> <?php _e('Biblioteca', 'flavor-platform'); ?>
        </a>
        <a href="#" class="nav-tab" :class="{ 'nav-tab-active': tab === 'subir' }" @click.prevent="tab = 'subir'">
            <span class="dashicons dashicons-upload"></span> <?php _e('Subir Audio', 'flavor-platform'); ?>
        </a>
        <a href="#" class="nav-tab" :class="{ 'nav-tab-active': tab === 'playlists' }" @click.prevent="tab = 'playlists'">
            <span class="dashicons dashicons-list-view"></span> <?php _e('Playlists', 'flavor-platform'); ?>
        </a>
        <a href="#" class="nav-tab" :class="{ 'nav-tab-active': tab === 'programacion' }" @click.prevent="tab = 'programacion'">
            <span class="dashicons dashicons-calendar-alt"></span> <?php _e('Programación Auto', 'flavor-platform'); ?>
        </a>
    </nav>

    <!-- Estadísticas -->
    <div class="radio-media-stats" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #2271b1;"><?php echo number_format($stats['total_audios']); ?></div>
            <div style="color: #666; font-size: 13px;"><?php _e('Total Audios', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #00a32a;"><?php echo number_format($stats['canciones']); ?></div>
            <div style="color: #666; font-size: 13px;"><?php _e('Canciones', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #8c49d8;"><?php echo number_format($stats['podcasts']); ?></div>
            <div style="color: #666; font-size: 13px;"><?php _e('Podcasts', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #dba617;"><?php echo number_format($stats['jingles']); ?></div>
            <div style="color: #666; font-size: 13px;"><?php _e('Jingles/Cortinas', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #d63638;"><?php echo $horas; ?>h <?php echo $minutos; ?>m</div>
            <div style="color: #666; font-size: 13px;"><?php _e('Duración Total', 'flavor-platform'); ?></div>
        </div>
    </div>

    <!-- Tab: Biblioteca -->
    <div x-show="tab === 'biblioteca'" class="tab-content" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display: flex; gap: 10px;">
                <select x-model="filtroTipo" @change="loadBiblioteca()" style="padding: 8px;">
                    <option value=""><?php _e('Todos los tipos', 'flavor-platform'); ?></option>
                    <option value="cancion"><?php _e('Canciones', 'flavor-platform'); ?></option>
                    <option value="podcast"><?php _e('Podcasts', 'flavor-platform'); ?></option>
                    <option value="jingle"><?php _e('Jingles', 'flavor-platform'); ?></option>
                    <option value="cortina"><?php _e('Cortinas', 'flavor-platform'); ?></option>
                    <option value="programa"><?php _e('Programas', 'flavor-platform'); ?></option>
                </select>
                <input type="search" x-model="busqueda" @input.debounce.300ms="loadBiblioteca()"
                       placeholder="<?php esc_attr_e('Buscar por título o artista...', 'flavor-platform'); ?>"
                       style="padding: 8px; width: 300px;">
            </div>
            <button class="button button-primary" @click="tab = 'subir'">
                <span class="dashicons dashicons-upload"></span> <?php _e('Subir Audio', 'flavor-platform'); ?>
            </button>
        </div>

        <!-- Tabla de audios -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" @change="toggleSelectAll($event)"></th>
                    <th style="width: 50px;"></th>
                    <th><?php _e('Título', 'flavor-platform'); ?></th>
                    <th style="width: 150px;"><?php _e('Artista', 'flavor-platform'); ?></th>
                    <th style="width: 100px;"><?php _e('Tipo', 'flavor-platform'); ?></th>
                    <th style="width: 80px;"><?php _e('Duración', 'flavor-platform'); ?></th>
                    <th style="width: 100px;"><?php _e('Acciones', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="spinner is-active" style="float: none;"></span>
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && audios.length === 0">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-format-audio" style="font-size: 48px; color: #ddd;"></span>
                            <p><?php _e('No hay audios en la biblioteca', 'flavor-platform'); ?></p>
                        </td>
                    </tr>
                </template>
                <template x-for="audio in audios" :key="audio.id">
                    <tr>
                        <td><input type="checkbox" :value="audio.id" x-model="selectedAudios"></td>
                        <td>
                            <button class="button button-small" @click="playPreview(audio)"
                                    :class="{ 'playing': currentPreview === audio.id }">
                                <span class="dashicons" :class="currentPreview === audio.id ? 'dashicons-controls-pause' : 'dashicons-controls-play'"></span>
                            </button>
                        </td>
                        <td>
                            <strong x-text="audio.titulo"></strong>
                            <span x-show="audio.album" style="color: #999; font-size: 12px;" x-text="'- ' + audio.album"></span>
                        </td>
                        <td x-text="audio.artista || '-'"></td>
                        <td>
                            <span class="audio-type-badge" :class="'type-' + audio.tipo" x-text="audio.tipo"></span>
                        </td>
                        <td x-text="audio.duracion_formatted"></td>
                        <td>
                            <button class="button button-small" @click="addToPlaylist(audio.id)" title="<?php esc_attr_e('Añadir a playlist', 'flavor-platform'); ?>">
                                <span class="dashicons dashicons-plus-alt"></span>
                            </button>
                            <button class="button button-small" @click="deleteAudio(audio.id)" title="<?php esc_attr_e('Eliminar', 'flavor-platform'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <!-- Paginación -->
        <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
            <span x-text="'Mostrando ' + audios.length + ' de ' + totalAudios + ' audios'"></span>
            <div style="display: flex; gap: 5px;">
                <button class="button" :disabled="pagina <= 1" @click="pagina--; loadBiblioteca()">
                    &laquo; <?php _e('Anterior', 'flavor-platform'); ?>
                </button>
                <span style="padding: 5px 10px;" x-text="'Página ' + pagina + ' de ' + totalPaginas"></span>
                <button class="button" :disabled="pagina >= totalPaginas" @click="pagina++; loadBiblioteca()">
                    <?php _e('Siguiente', 'flavor-platform'); ?> &raquo;
                </button>
            </div>
        </div>
    </div>

    <!-- Tab: Subir Audio -->
    <div x-show="tab === 'subir'" class="tab-content" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
        <h2><?php _e('Subir Archivos de Audio', 'flavor-platform'); ?></h2>
        <p style="color: #666;"><?php _e('Formatos permitidos: MP3, OGG, WAV, M4A, AAC. Tamaño máximo: 100MB', 'flavor-platform'); ?></p>

        <div class="upload-area" @dragover.prevent="dragover = true" @dragleave="dragover = false"
             @drop.prevent="handleDrop($event)" :class="{ 'dragover': dragover }"
             style="border: 2px dashed #c3c4c7; border-radius: 8px; padding: 60px; text-align: center; margin: 20px 0; transition: all 0.3s;">

            <span class="dashicons dashicons-upload" style="font-size: 64px; color: #c3c4c7;"></span>
            <h3><?php _e('Arrastra archivos aquí', 'flavor-platform'); ?></h3>
            <p><?php _e('o', 'flavor-platform'); ?></p>
            <input type="file" id="audio-files" multiple accept=".mp3,.ogg,.wav,.m4a,.aac" @change="handleFileSelect($event)" style="display: none;">
            <label for="audio-files" class="button button-primary button-hero"><?php _e('Seleccionar Archivos', 'flavor-platform'); ?></label>
        </div>

        <!-- Tipo de audio -->
        <div style="margin-bottom: 20px;">
            <label><strong><?php _e('Tipo de audio:', 'flavor-platform'); ?></strong></label>
            <select x-model="uploadTipo" style="margin-left: 10px; padding: 8px;">
                <option value="cancion"><?php _e('Canción', 'flavor-platform'); ?></option>
                <option value="podcast"><?php _e('Podcast', 'flavor-platform'); ?></option>
                <option value="jingle"><?php _e('Jingle', 'flavor-platform'); ?></option>
                <option value="cortina"><?php _e('Cortina musical', 'flavor-platform'); ?></option>
                <option value="programa"><?php _e('Programa grabado', 'flavor-platform'); ?></option>
            </select>
        </div>

        <!-- Cola de subida -->
        <div x-show="uploadQueue.length > 0">
            <h3><?php _e('Cola de subida', 'flavor-platform'); ?></h3>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th><?php _e('Archivo', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php _e('Tamaño', 'flavor-platform'); ?></th>
                        <th style="width: 200px;"><?php _e('Progreso', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php _e('Estado', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in uploadQueue" :key="index">
                        <tr>
                            <td x-text="item.file.name"></td>
                            <td x-text="formatFileSize(item.file.size)"></td>
                            <td>
                                <div style="background: #f0f0f0; border-radius: 4px; height: 20px; overflow: hidden;">
                                    <div :style="'width: ' + item.progress + '%; background: #2271b1; height: 100%; transition: width 0.3s;'"></div>
                                </div>
                            </td>
                            <td>
                                <span x-show="item.status === 'pending'" style="color: #666;"><?php _e('Pendiente', 'flavor-platform'); ?></span>
                                <span x-show="item.status === 'uploading'" style="color: #2271b1;"><?php _e('Subiendo...', 'flavor-platform'); ?></span>
                                <span x-show="item.status === 'done'" style="color: #00a32a;"><?php _e('Completado', 'flavor-platform'); ?></span>
                                <span x-show="item.status === 'error'" style="color: #d63638;"><?php _e('Error', 'flavor-platform'); ?></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <button class="button button-primary" @click="startUpload()" :disabled="uploading" style="margin-top: 15px;">
                <span x-show="!uploading"><?php _e('Iniciar Subida', 'flavor-platform'); ?></span>
                <span x-show="uploading"><?php _e('Subiendo...', 'flavor-platform'); ?></span>
            </button>
        </div>
    </div>

    <!-- Tab: Playlists -->
    <div x-show="tab === 'playlists'" class="tab-content" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;"><?php _e('Playlists', 'flavor-platform'); ?></h2>
            <button class="button button-primary" @click="showPlaylistModal = true; editingPlaylist = null; playlistForm = {nombre: '', descripcion: '', tipo: 'manual', orden: 'secuencial', audios: []}">
                <span class="dashicons dashicons-plus-alt"></span> <?php _e('Nueva Playlist', 'flavor-platform'); ?>
            </button>
        </div>

        <div class="playlists-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <template x-for="playlist in playlists" :key="playlist.id">
                <div class="playlist-card" style="background: #f9f9f9; border-radius: 8px; padding: 20px; border: 1px solid #e0e0e0;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h3 style="margin: 0 0 5px 0;" x-text="playlist.nombre"></h3>
                            <p style="margin: 0; color: #666; font-size: 13px;" x-text="playlist.descripcion || 'Sin descripción'"></p>
                        </div>
                        <span class="playlist-type-badge" :class="'type-' + playlist.tipo" x-text="playlist.tipo"></span>
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                        <button class="button" @click="editPlaylist(playlist)">
                            <span class="dashicons dashicons-edit"></span> <?php _e('Editar', 'flavor-platform'); ?>
                        </button>
                        <button class="button" @click="previewPlaylist(playlist)">
                            <span class="dashicons dashicons-controls-play"></span> <?php _e('Previsualizar', 'flavor-platform'); ?>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Tab: Programación Automática -->
    <div x-show="tab === 'programacion'" class="tab-content" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
        <h2><?php _e('Programación Automática', 'flavor-platform'); ?></h2>
        <p style="color: #666;"><?php _e('Configura qué playlists suenan en cada horario. La radio reproducirá automáticamente según esta programación.', 'flavor-platform'); ?></p>

        <div class="programacion-grid" style="display: grid; grid-template-columns: 80px repeat(7, 1fr); gap: 2px; margin-top: 20px; background: #e0e0e0;">
            <!-- Header días -->
            <div style="background: #f0f0f0; padding: 10px; text-align: center;"></div>
            <template x-for="dia in ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']">
                <div style="background: #f0f0f0; padding: 10px; text-align: center; font-weight: bold;" x-text="dia"></div>
            </template>

            <!-- Horas -->
            <template x-for="hora in 24">
                <template x-fragment>
                    <div style="background: #f9f9f9; padding: 8px; text-align: center; font-size: 12px;" x-text="(hora-1) + ':00'"></div>
                    <template x-for="dia in 7">
                        <div class="programacion-slot" style="background: #fff; padding: 5px; min-height: 40px; cursor: pointer;"
                             @click="openSlotConfig(dia, hora-1)"
                             :class="{ 'has-playlist': getSlotPlaylist(dia, hora-1) }">
                            <span x-text="getSlotPlaylist(dia, hora-1)?.nombre || ''" style="font-size: 11px;"></span>
                        </div>
                    </template>
                </template>
            </template>
        </div>
    </div>

    <!-- Audio Preview Player (hidden) -->
    <audio id="preview-player" @ended="currentPreview = null"></audio>
</div>

<style>
.tab-content { display: none; }
.tab-content[style*="block"], [x-show]:not([x-show="false"]) { display: block !important; }

.upload-area.dragover {
    border-color: #2271b1 !important;
    background: #f0f7ff;
}

.audio-type-badge, .playlist-type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.type-cancion { background: #e1f5fe; color: #0277bd; }
.type-podcast { background: #f3e5f5; color: #7b1fa2; }
.type-jingle, .type-cortina { background: #fff3e0; color: #ef6c00; }
.type-programa { background: #e8f5e9; color: #2e7d32; }
.type-manual { background: #e3f2fd; color: #1565c0; }
.type-automatica { background: #fce4ec; color: #c2185b; }

.programacion-slot.has-playlist {
    background: #e3f2fd !important;
}

.button .dashicons {
    vertical-align: middle;
    margin-right: 3px;
}
</style>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('radioMediaManager', () => ({
        tab: 'biblioteca',
        loading: false,
        audios: [],
        totalAudios: 0,
        totalPaginas: 1,
        pagina: 1,
        filtroTipo: '',
        busqueda: '',
        selectedAudios: [],
        currentPreview: null,
        dragover: false,
        uploadQueue: [],
        uploadTipo: 'cancion',
        uploading: false,
        playlists: [],
        showPlaylistModal: false,
        editingPlaylist: null,
        playlistForm: { nombre: '', descripcion: '', tipo: 'manual', orden: 'secuencial', audios: [] },
        programacion: [],

        init() {
            this.loadBiblioteca();
            this.loadPlaylists();
            this.loadProgramacion();
        },

        async loadBiblioteca() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    action: 'flavor_radio_get_audio_library',
                    nonce: '<?php echo wp_create_nonce('flavor_radio_nonce'); ?>',
                    tipo: this.filtroTipo,
                    busqueda: this.busqueda,
                    pagina: this.pagina,
                    por_pagina: 50
                });
                const response = await fetch(ajaxurl + '?' + params);
                const data = await response.json();
                if (data.success) {
                    this.audios = data.data.audios;
                    this.totalAudios = data.data.total;
                    this.totalPaginas = data.data.paginas;
                }
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        },

        async loadPlaylists() {
            try {
                const response = await fetch('<?php echo rest_url('flavor/v1/radio/playlists'); ?>');
                const data = await response.json();
                if (data.success) {
                    this.playlists = data.playlists;
                }
            } catch (e) {
                console.error(e);
            }
        },

        loadProgramacion() {
            // TODO: Cargar programación desde API
        },

        playPreview(audio) {
            const player = document.getElementById('preview-player');
            if (this.currentPreview === audio.id) {
                player.pause();
                this.currentPreview = null;
            } else {
                player.src = audio.archivo_url;
                player.play();
                this.currentPreview = audio.id;
            }
        },

        handleFileSelect(event) {
            this.addFilesToQueue(event.target.files);
        },

        handleDrop(event) {
            this.dragover = false;
            this.addFilesToQueue(event.dataTransfer.files);
        },

        addFilesToQueue(files) {
            for (const file of files) {
                if (this.isValidAudioFile(file)) {
                    this.uploadQueue.push({
                        file: file,
                        progress: 0,
                        status: 'pending'
                    });
                }
            }
        },

        isValidAudioFile(file) {
            const allowed = ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/x-m4a', 'audio/aac', 'audio/mp3'];
            return allowed.includes(file.type) || file.name.match(/\.(mp3|ogg|wav|m4a|aac)$/i);
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        async startUpload() {
            this.uploading = true;
            for (let i = 0; i < this.uploadQueue.length; i++) {
                if (this.uploadQueue[i].status !== 'pending') continue;

                this.uploadQueue[i].status = 'uploading';
                try {
                    await this.uploadFile(i);
                    this.uploadQueue[i].status = 'done';
                    this.uploadQueue[i].progress = 100;
                } catch (e) {
                    this.uploadQueue[i].status = 'error';
                }
            }
            this.uploading = false;
            this.loadBiblioteca();
        },

        uploadFile(index) {
            return new Promise((resolve, reject) => {
                const item = this.uploadQueue[index];
                const formData = new FormData();
                formData.append('action', 'flavor_radio_upload_audio');
                formData.append('nonce', '<?php echo wp_create_nonce('flavor_radio_nonce'); ?>');
                formData.append('audio_file', item.file);
                formData.append('tipo', this.uploadTipo);

                const xhr = new XMLHttpRequest();
                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        this.uploadQueue[index].progress = Math.round((e.loaded / e.total) * 100);
                    }
                };
                xhr.onload = () => {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data.message));
                        }
                    } else {
                        reject(new Error('Upload failed'));
                    }
                };
                xhr.onerror = () => reject(new Error('Network error'));
                xhr.open('POST', ajaxurl);
                xhr.send(formData);
            });
        },

        async deleteAudio(audioId) {
            if (!confirm('<?php echo esc_js(__('¿Eliminar este audio?', 'flavor-platform')); ?>')) return;

            const formData = new FormData();
            formData.append('action', 'flavor_radio_delete_audio');
            formData.append('nonce', '<?php echo wp_create_nonce('flavor_radio_nonce'); ?>');
            formData.append('audio_id', audioId);

            try {
                const response = await fetch(ajaxurl, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    this.loadBiblioteca();
                }
            } catch (e) {
                console.error(e);
            }
        },

        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedAudios = this.audios.map(a => a.id);
            } else {
                this.selectedAudios = [];
            }
        },

        addToPlaylist(audioId) {
            // TODO: Modal para seleccionar playlist
            alert('Funcionalidad de añadir a playlist');
        },

        editPlaylist(playlist) {
            this.editingPlaylist = playlist;
            this.playlistForm = { ...playlist };
            this.showPlaylistModal = true;
        },

        previewPlaylist(playlist) {
            // TODO: Preview de playlist
        },

        getSlotPlaylist(dia, hora) {
            return this.programacion.find(p => p.dia === dia && p.hora === hora);
        },

        openSlotConfig(dia, hora) {
            // TODO: Modal para configurar slot
        }
    }));
});
</script>

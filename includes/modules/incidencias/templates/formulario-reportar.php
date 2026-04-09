<?php
/**
 * Template: Formulario de Reporte de Incidencia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="incidencias-container">
    <section class="incidencias-section">
        <?php if (!empty($atributos['titulo'])): ?>
            <h2 class="incidencias-section-title">
                <span class="dashicons dashicons-warning"></span>
                <?php echo esc_html($atributos['titulo']); ?>
            </h2>
        <?php endif; ?>

        <form id="incidencias-form-reportar" class="incidencias-form" enctype="multipart/form-data">
            <?php wp_nonce_field('incidencias_nonce', 'nonce'); ?>

            <!-- Categoría -->
            <div class="incidencias-form-group">
                <label><?php _e('Tipo de incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                <div class="incidencias-categorias-grid">
                    <?php foreach ($categorias as $categoria): ?>
                        <label class="incidencias-categoria-item">
                            <input type="radio" name="categoria" value="<?php echo esc_attr($categoria->slug); ?>" required>
                            <span class="cat-icon"><?php echo $this->obtener_icono_categoria($categoria->icono); ?></span>
                            <span class="cat-name"><?php echo esc_html($categoria->nombre); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Título y descripción -->
            <div class="incidencias-form-row">
                <div class="incidencias-form-group">
                    <label for="incidencias-titulo"><?php _e('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                    <input type="text" id="incidencias-titulo" name="titulo" required maxlength="255" placeholder="<?php esc_attr_e('Ej: Farola fundida en Calle Mayor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>

            <div class="incidencias-form-group">
                <label for="incidencias-descripcion"><?php _e('Descripcion del problema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                <textarea id="incidencias-descripcion" name="descripcion" required rows="4" placeholder="<?php esc_attr_e('Describe el problema con el mayor detalle posible...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <!-- Ubicación -->
            <div class="incidencias-form-group">
                <label for="incidencias-direccion"><?php _e('Direccion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="incidencias-direccion" name="direccion" placeholder="<?php esc_attr_e('Calle, numero, barrio...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="flex: 1;">
                    <button type="button" id="btn-obtener-ubicacion" class="incidencias-btn incidencias-btn-secondary">
                        <span class="dashicons dashicons-location"></span>
                        <?php _e('Usar mi ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
                <span class="help-text"><?php _e('Puedes introducir la direccion manualmente o usar tu ubicacion actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>

            <?php if ($atributos['mostrar_mapa'] === 'true'): ?>
                <!-- Mapa -->
                <div class="incidencias-form-group">
                    <label><?php _e('Selecciona la ubicacion en el mapa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <div class="incidencias-mapa-container">
                        <div id="incidencias-mapa" class="incidencias-mapa incidencias-mapa-reportar"></div>
                    </div>
                    <div class="incidencias-mapa-info">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Haz clic en el mapa para marcar la ubicacion exacta del problema.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                    <input type="hidden" id="incidencias-latitud" name="latitud">
                    <input type="hidden" id="incidencias-longitud" name="longitud">
                </div>
            <?php endif; ?>

            <!-- Fotos -->
            <div class="incidencias-form-group">
                <label><?php _e('Fotos del problema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div class="incidencias-upload-area">
                    <input type="file" id="incidencias-fotos-input" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <div class="upload-icon"><span class="dashicons dashicons-camera"></span></div>
                    <p class="upload-text"><?php _e('Arrastra tus fotos aqui o haz clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p class="upload-hint"><?php printf(__('Maximo %d fotos, hasta %dMB cada una', FLAVOR_PLATFORM_TEXT_DOMAIN), $configuracion['max_fotos_por_incidencia'], $configuracion['tamano_max_foto']); ?></p>
                </div>
                <div class="incidencias-preview-container"></div>
                <span class="incidencias-fotos-count">0/<?php echo $configuracion['max_fotos_por_incidencia']; ?></span>
            </div>

            <!-- Datos de contacto (si no está logueado) -->
            <?php if (!is_user_logged_in()): ?>
                <div class="incidencias-form-row">
                    <div class="incidencias-form-group">
                        <label for="incidencias-nombre"><?php _e('Tu nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="incidencias-nombre" name="nombre" placeholder="<?php esc_attr_e('Nombre y apellidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                    <div class="incidencias-form-group">
                        <label for="incidencias-email"><?php _e('Email de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php if (!$configuracion['permite_anonimo']): ?><span class="required">*</span><?php endif; ?></label>
                        <input type="email" id="incidencias-email" name="email" placeholder="<?php esc_attr_e('tu@email.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php echo !$configuracion['permite_anonimo'] ? 'required' : ''; ?>>
                    </div>
                </div>
                <div class="incidencias-form-group">
                    <label for="incidencias-telefono"><?php _e('Telefono (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="tel" id="incidencias-telefono" name="telefono" placeholder="<?php esc_attr_e('Para contactarte si es necesario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            <?php endif; ?>

            <!-- Submit -->
            <div class="incidencias-form-group">
                <button type="submit" class="incidencias-btn incidencias-btn-primary incidencias-btn-lg incidencias-btn-block">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Enviar Incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </section>
</div>

<?php
/**
 * Obtener icono de categoría
 */
function obtener_icono_categoria($icono) {
    $iconos_mapa = [
        'lightbulb' => '💡',
        'trash' => '🗑️',
        'road' => '🛣️',
        'bench' => '🪑',
        'tree' => '🌳',
        'volume' => '🔊',
        'droplet' => '💧',
        'sign' => '🚧',
        'wheelchair' => '♿',
        'more' => '➕',
    ];
    return $iconos_mapa[$icono] ?? '📍';
}
?>

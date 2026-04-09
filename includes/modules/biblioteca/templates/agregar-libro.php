<?php
/**
 * Template: Agregar Libro
 */

if (!defined('ABSPATH')) {
    exit;
}

$generos = [
    'Novela', 'Ensayo', 'Poesía', 'Ciencia ficción', 'Fantasía', 'Thriller',
    'Romance', 'Historia', 'Biografía', 'Autoayuda', 'Técnico', 'Académico',
    'Infantil', 'Juvenil', 'Cómic', 'Arte', 'Cocina', 'Viajes', 'Otro'
];

$idiomas = ['Español', 'Inglés', 'Francés', 'Alemán', 'Italiano', 'Portugués', 'Otro'];
?>

<div class="biblioteca-wrapper">
    <a href="<?php echo remove_query_arg('vista'); ?>" class="btn btn-outline btn-sm" style="margin-bottom: 1rem;">
        <span class="dashicons dashicons-arrow-left-alt2"></span>
        <?php _e('Volver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <form class="agregar-libro-form">
        <h2><?php _e('Agregar libro a la biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

        <div class="form-grupo">
            <label><?php _e('ISBN (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <div class="isbn-buscar">
                <input type="text" id="isbn" name="isbn" placeholder="978-84-XXXXXXX-X">
                <button type="button" class="btn-buscar-isbn">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>
            <small style="color: #6b7280;"><?php _e('Introduce el ISBN para autocompletar los datos del libro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
        </div>

        <div class="form-grupo-inline">
            <div class="form-grupo">
                <label><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            <div class="form-grupo">
                <label><?php _e('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <input type="text" id="autor" name="autor" required>
            </div>
        </div>

        <div class="form-grupo-inline">
            <div class="form-grupo">
                <label><?php _e('Editorial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" id="editorial" name="editorial">
            </div>
            <div class="form-grupo">
                <label><?php _e('Año de publicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="number" id="ano" name="ano" min="1800" max="<?php echo date('Y'); ?>">
            </div>
        </div>

        <div class="form-grupo-inline">
            <div class="form-grupo">
                <label><?php _e('Género', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select id="genero" name="genero">
                    <option value=""><?php _e('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($generos as $genero): ?>
                        <option value="<?php echo esc_attr($genero); ?>"><?php echo esc_html($genero); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label><?php _e('Idioma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select id="idioma" name="idioma">
                    <?php foreach ($idiomas as $idioma): ?>
                        <option value="<?php echo esc_attr($idioma); ?>" <?php selected($idioma, 'Español'); ?>>
                            <?php echo esc_html($idioma); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label><?php _e('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="number" id="paginas" name="paginas" min="1">
            </div>
        </div>

        <div class="form-grupo">
            <label><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <textarea id="descripcion" name="descripcion" rows="4" placeholder="<?php esc_attr_e('Breve descripción o sinopsis del libro...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
        </div>

        <div class="form-grupo-inline" style="align-items: flex-start;">
            <div class="form-grupo">
                <label><?php _e('Portada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div class="portada-preview">
                    <div class="placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                        <span><?php _e('Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <input type="hidden" id="portada_url" name="portada_url">
            </div>
            <div style="flex: 1;">
                <div class="form-grupo">
                    <label><?php _e('Estado físico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="estado_fisico" name="estado_fisico">
                        <option value="excelente"><?php _e('Excelente - Como nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="bueno" selected><?php _e('Bueno - Buen estado general', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="aceptable"><?php _e('Aceptable - Algunas marcas de uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="desgastado"><?php _e('Desgastado - Marcas visibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label><?php _e('Tipo de compartición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="tipo" name="tipo">
                        <option value="prestamo"><?php _e('Préstamo - Me lo devuelven', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="donado"><?php _e('Donación - Para la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="intercambio"><?php _e('Intercambio - Lo cambio por otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label><?php _e('Ubicación/punto de recogida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" id="ubicacion" name="ubicacion" placeholder="<?php esc_attr_e('Ej: Portal 3, 2ºA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Agregar libro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <a href="<?php echo remove_query_arg('vista'); ?>" class="btn btn-outline">
                <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </form>
</div>

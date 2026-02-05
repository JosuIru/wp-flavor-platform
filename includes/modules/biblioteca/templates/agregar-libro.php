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
        <?php _e('Volver', 'flavor-chat-ia'); ?>
    </a>

    <form class="agregar-libro-form">
        <h2><?php _e('Agregar libro a la biblioteca', 'flavor-chat-ia'); ?></h2>

        <div class="form-grupo">
            <label><?php _e('ISBN (opcional)', 'flavor-chat-ia'); ?></label>
            <div class="isbn-buscar">
                <input type="text" id="isbn" name="isbn" placeholder="978-84-XXXXXXX-X">
                <button type="button" class="btn-buscar-isbn">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>
            <small style="color: #6b7280;"><?php _e('Introduce el ISBN para autocompletar los datos del libro', 'flavor-chat-ia'); ?></small>
        </div>

        <div class="form-grupo-inline">
            <div class="form-grupo">
                <label><?php _e('Título', 'flavor-chat-ia'); ?> *</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            <div class="form-grupo">
                <label><?php _e('Autor', 'flavor-chat-ia'); ?> *</label>
                <input type="text" id="autor" name="autor" required>
            </div>
        </div>

        <div class="form-grupo-inline">
            <div class="form-grupo">
                <label><?php _e('Editorial', 'flavor-chat-ia'); ?></label>
                <input type="text" id="editorial" name="editorial">
            </div>
            <div class="form-grupo">
                <label><?php _e('Año de publicación', 'flavor-chat-ia'); ?></label>
                <input type="number" id="ano" name="ano" min="1800" max="<?php echo date('Y'); ?>">
            </div>
        </div>

        <div class="form-grupo-inline">
            <div class="form-grupo">
                <label><?php _e('Género', 'flavor-chat-ia'); ?></label>
                <select id="genero" name="genero">
                    <option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($generos as $genero): ?>
                        <option value="<?php echo esc_attr($genero); ?>"><?php echo esc_html($genero); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label><?php _e('Idioma', 'flavor-chat-ia'); ?></label>
                <select id="idioma" name="idioma">
                    <?php foreach ($idiomas as $idioma): ?>
                        <option value="<?php echo esc_attr($idioma); ?>" <?php selected($idioma, 'Español'); ?>>
                            <?php echo esc_html($idioma); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label><?php _e('Páginas', 'flavor-chat-ia'); ?></label>
                <input type="number" id="paginas" name="paginas" min="1">
            </div>
        </div>

        <div class="form-grupo">
            <label><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
            <textarea id="descripcion" name="descripcion" rows="4" placeholder="<?php esc_attr_e('Breve descripción o sinopsis del libro...', 'flavor-chat-ia'); ?>"></textarea>
        </div>

        <div class="form-grupo-inline" style="align-items: flex-start;">
            <div class="form-grupo">
                <label><?php _e('Portada', 'flavor-chat-ia'); ?></label>
                <div class="portada-preview">
                    <div class="placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                        <span><?php _e('Seleccionar imagen', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <input type="hidden" id="portada_url" name="portada_url">
            </div>
            <div style="flex: 1;">
                <div class="form-grupo">
                    <label><?php _e('Estado físico', 'flavor-chat-ia'); ?></label>
                    <select id="estado_fisico" name="estado_fisico">
                        <option value="excelente"><?php _e('Excelente - Como nuevo', 'flavor-chat-ia'); ?></option>
                        <option value="bueno" selected><?php _e('Bueno - Buen estado general', 'flavor-chat-ia'); ?></option>
                        <option value="aceptable"><?php _e('Aceptable - Algunas marcas de uso', 'flavor-chat-ia'); ?></option>
                        <option value="desgastado"><?php _e('Desgastado - Marcas visibles', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label><?php _e('Tipo de compartición', 'flavor-chat-ia'); ?></label>
                    <select id="tipo" name="tipo">
                        <option value="prestamo"><?php _e('Préstamo - Me lo devuelven', 'flavor-chat-ia'); ?></option>
                        <option value="donado"><?php _e('Donación - Para la comunidad', 'flavor-chat-ia'); ?></option>
                        <option value="intercambio"><?php _e('Intercambio - Lo cambio por otro', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label><?php _e('Ubicación/punto de recogida', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="ubicacion" name="ubicacion" placeholder="<?php esc_attr_e('Ej: Portal 3, 2ºA', 'flavor-chat-ia'); ?>">
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Agregar libro', 'flavor-chat-ia'); ?>
            </button>
            <a href="<?php echo remove_query_arg('vista'); ?>" class="btn btn-outline">
                <?php _e('Cancelar', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </form>
</div>

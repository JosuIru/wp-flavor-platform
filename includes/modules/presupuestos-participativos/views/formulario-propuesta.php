<?php
/**
 * Vista: Formulario de Propuesta de Proyecto
 *
 * Variables disponibles:
 * - $edicion: objeto con datos de la edicion actual
 * - $categorias: array de categorias disponibles
 * - $configuracion: array de configuracion del modulo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$presupuesto_minimo = floatval($configuracion['presupuesto_minimo_proyecto'] ?? 1000);
$presupuesto_maximo = floatval($configuracion['presupuesto_maximo_proyecto'] ?? 50000);
?>

<div class="flavor-pp-formulario-contenedor">
    <div class="flavor-pp-formulario-header">
        <h2><?php esc_html_e('Proponer un proyecto', 'flavor-chat-ia'); ?></h2>
        <p class="flavor-pp-formulario-intro">
            <?php esc_html_e('Comparte tu idea para mejorar el barrio. Las propuestas seran revisadas y, si cumplen los requisitos, pasaran a votacion.', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <form id="flavor-pp-form-propuesta" class="flavor-pp-formulario" method="post">
        <?php wp_nonce_field('flavor_presupuestos_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="pp_proponer_proyecto">
        <input type="hidden" name="edicion_id" value="<?php echo esc_attr($edicion->id); ?>">

        <div class="flavor-pp-campo">
            <label for="pp-titulo" class="flavor-pp-label">
                <?php esc_html_e('Titulo del proyecto', 'flavor-chat-ia'); ?>
                <span class="requerido">*</span>
            </label>
            <input type="text" id="pp-titulo" name="titulo" class="flavor-pp-input" required
                   maxlength="200"
                   placeholder="<?php esc_attr_e('Ej: Parque infantil accesible en Plaza Mayor', 'flavor-chat-ia'); ?>">
            <span class="flavor-pp-ayuda"><?php esc_html_e('Maximo 200 caracteres', 'flavor-chat-ia'); ?></span>
        </div>

        <div class="flavor-pp-campo">
            <label for="pp-categoria" class="flavor-pp-label">
                <?php esc_html_e('Categoria', 'flavor-chat-ia'); ?>
                <span class="requerido">*</span>
            </label>
            <select id="pp-categoria" name="categoria" class="flavor-pp-select" required>
                <option value=""><?php esc_html_e('Selecciona una categoria', 'flavor-chat-ia'); ?></option>
                <?php foreach ($categorias as $slug => $nombre): ?>
                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flavor-pp-campo">
            <label for="pp-descripcion" class="flavor-pp-label">
                <?php esc_html_e('Descripcion detallada', 'flavor-chat-ia'); ?>
                <span class="requerido">*</span>
            </label>
            <textarea id="pp-descripcion" name="descripcion" class="flavor-pp-textarea" required
                      rows="6" maxlength="2000"
                      placeholder="<?php esc_attr_e('Describe tu propuesta: que problema resuelve, a quien beneficia, donde se ubicaria...', 'flavor-chat-ia'); ?>"></textarea>
            <span class="flavor-pp-ayuda"><?php esc_html_e('Minimo 50 caracteres, maximo 2000', 'flavor-chat-ia'); ?></span>
        </div>

        <div class="flavor-pp-campo-grupo">
            <div class="flavor-pp-campo flavor-pp-campo-mitad">
                <label for="pp-presupuesto" class="flavor-pp-label">
                    <?php esc_html_e('Presupuesto estimado (EUR)', 'flavor-chat-ia'); ?>
                    <span class="requerido">*</span>
                </label>
                <input type="number" id="pp-presupuesto" name="presupuesto" class="flavor-pp-input" required
                       min="<?php echo esc_attr($presupuesto_minimo); ?>"
                       max="<?php echo esc_attr($presupuesto_maximo); ?>"
                       step="100"
                       placeholder="<?php echo esc_attr(number_format($presupuesto_minimo, 0, ',', '.')); ?>">
                <span class="flavor-pp-ayuda">
                    <?php printf(
                        esc_html__('Entre %s y %s EUR', 'flavor-chat-ia'),
                        number_format($presupuesto_minimo, 0, ',', '.'),
                        number_format($presupuesto_maximo, 0, ',', '.')
                    ); ?>
                </span>
            </div>

            <div class="flavor-pp-campo flavor-pp-campo-mitad">
                <label for="pp-ubicacion" class="flavor-pp-label">
                    <?php esc_html_e('Ubicacion sugerida', 'flavor-chat-ia'); ?>
                </label>
                <input type="text" id="pp-ubicacion" name="ubicacion" class="flavor-pp-input"
                       maxlength="200"
                       placeholder="<?php esc_attr_e('Ej: Calle Mayor, 15', 'flavor-chat-ia'); ?>">
            </div>
        </div>

        <div class="flavor-pp-campo">
            <label for="pp-imagen" class="flavor-pp-label">
                <?php esc_html_e('Imagen ilustrativa (opcional)', 'flavor-chat-ia'); ?>
            </label>
            <div class="flavor-pp-upload-area" id="pp-upload-area">
                <input type="file" id="pp-imagen" name="imagen" accept="image/jpeg,image/png,image/webp" class="flavor-pp-input-file">
                <div class="flavor-pp-upload-placeholder">
                    <span class="dashicons dashicons-upload"></span>
                    <p><?php esc_html_e('Arrastra una imagen o haz clic para seleccionar', 'flavor-chat-ia'); ?></p>
                    <span class="flavor-pp-ayuda"><?php esc_html_e('JPG, PNG o WebP. Maximo 2MB.', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-pp-upload-preview" style="display: none;">
                    <img src="" alt="" id="pp-imagen-preview">
                    <button type="button" class="flavor-pp-btn-quitar-imagen">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="flavor-pp-campo flavor-pp-campo-checkbox">
            <label class="flavor-pp-checkbox-label">
                <input type="checkbox" name="acepto_condiciones" required>
                <span><?php
                    printf(
                        esc_html__('He leido y acepto las %scondiciones de participacion%s', 'flavor-chat-ia'),
                        '<a href="' . esc_url(home_url('/condiciones-presupuestos-participativos/')) . '" target="_blank">',
                        '</a>'
                    );
                ?></span>
            </label>
        </div>

        <div class="flavor-pp-formulario-acciones">
            <button type="submit" class="flavor-pp-boton flavor-pp-boton-primario flavor-pp-boton-grande">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Enviar propuesta', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <div class="flavor-pp-mensaje flavor-pp-mensaje-oculto" id="pp-mensaje-resultado"></div>
    </form>
</div>

<?php
/**
 * Vista: Formulario de propuesta de proyecto
 *
 * @package FlavorChatIA
 * @var object $edicion       Edición activa
 * @var array  $categorias    Categorías disponibles
 * @var array  $configuracion Configuración del módulo
 */

if (!defined('ABSPATH')) {
    exit;
}

$presupuesto_minimo = floatval($configuracion['presupuesto_minimo_proyecto'] ?? 1000);
$presupuesto_maximo = floatval($configuracion['presupuesto_maximo_proyecto'] ?? 50000);
?>

<div class="flavor-pp-formulario-propuesta">
    <div class="flavor-pp-info-proceso">
        <div class="flavor-pp-info-item">
            <span class="dashicons dashicons-calendar-alt"></span>
            <span>
                <?php printf(
                    esc_html__('Edición %d - Fase de propuestas', 'flavor-chat-ia'),
                    intval($edicion->anio)
                ); ?>
            </span>
        </div>
        <div class="flavor-pp-info-item">
            <span class="dashicons dashicons-money-alt"></span>
            <span>
                <?php printf(
                    esc_html__('Presupuesto: entre %s€ y %s€', 'flavor-chat-ia'),
                    number_format($presupuesto_minimo, 0, ',', '.'),
                    number_format($presupuesto_maximo, 0, ',', '.')
                ); ?>
            </span>
        </div>
    </div>

    <form id="flavor-pp-form-propuesta" class="flavor-pp-form" method="post">
        <?php wp_nonce_field('flavor_presupuestos_nonce', 'nonce'); ?>

        <div class="flavor-pp-form-group">
            <label for="pp-titulo" class="flavor-pp-label">
                <?php esc_html_e('Título del proyecto', 'flavor-chat-ia'); ?>
                <span class="required">*</span>
            </label>
            <input type="text" id="pp-titulo" name="titulo" class="flavor-pp-input"
                   required maxlength="200"
                   placeholder="<?php esc_attr_e('Ej: Instalación de columpios accesibles en el parque', 'flavor-chat-ia'); ?>">
            <span class="flavor-pp-help">
                <?php esc_html_e('Máximo 200 caracteres. Sé claro y descriptivo.', 'flavor-chat-ia'); ?>
            </span>
        </div>

        <div class="flavor-pp-form-group">
            <label for="pp-descripcion" class="flavor-pp-label">
                <?php esc_html_e('Descripción del proyecto', 'flavor-chat-ia'); ?>
                <span class="required">*</span>
            </label>
            <textarea id="pp-descripcion" name="descripcion" class="flavor-pp-textarea"
                      required rows="6"
                      placeholder="<?php esc_attr_e('Describe tu propuesta en detalle: qué problema resuelve, a quién beneficia, cómo se implementaría...', 'flavor-chat-ia'); ?>"></textarea>
            <span class="flavor-pp-help">
                <?php esc_html_e('Incluye todos los detalles relevantes para que los técnicos puedan evaluar la viabilidad.', 'flavor-chat-ia'); ?>
            </span>
        </div>

        <div class="flavor-pp-form-row">
            <div class="flavor-pp-form-group flavor-pp-form-half">
                <label for="pp-categoria" class="flavor-pp-label">
                    <?php esc_html_e('Categoría', 'flavor-chat-ia'); ?>
                    <span class="required">*</span>
                </label>
                <select id="pp-categoria" name="categoria" class="flavor-pp-select" required>
                    <option value=""><?php esc_html_e('Selecciona una categoría', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias as $clave_categoria => $etiqueta_categoria): ?>
                        <option value="<?php echo esc_attr($clave_categoria); ?>">
                            <?php echo esc_html($etiqueta_categoria); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-pp-form-group flavor-pp-form-half">
                <label for="pp-presupuesto" class="flavor-pp-label">
                    <?php esc_html_e('Presupuesto estimado (€)', 'flavor-chat-ia'); ?>
                    <span class="required">*</span>
                </label>
                <input type="number" id="pp-presupuesto" name="presupuesto" class="flavor-pp-input"
                       required min="<?php echo esc_attr($presupuesto_minimo); ?>"
                       max="<?php echo esc_attr($presupuesto_maximo); ?>"
                       step="100"
                       placeholder="<?php echo esc_attr($presupuesto_minimo); ?>">
                <span class="flavor-pp-help">
                    <?php printf(
                        esc_html__('Entre %s€ y %s€', 'flavor-chat-ia'),
                        number_format($presupuesto_minimo, 0, ',', '.'),
                        number_format($presupuesto_maximo, 0, ',', '.')
                    ); ?>
                </span>
            </div>
        </div>

        <div class="flavor-pp-form-group">
            <label for="pp-ubicacion" class="flavor-pp-label">
                <?php esc_html_e('Ubicación propuesta', 'flavor-chat-ia'); ?>
            </label>
            <input type="text" id="pp-ubicacion" name="ubicacion" class="flavor-pp-input"
                   placeholder="<?php esc_attr_e('Ej: Parque Central, Calle Mayor esquina Plaza Nueva...', 'flavor-chat-ia'); ?>">
            <span class="flavor-pp-help">
                <?php esc_html_e('Indica dónde se implementaría el proyecto (opcional pero recomendado).', 'flavor-chat-ia'); ?>
            </span>
        </div>

        <div class="flavor-pp-form-group">
            <label class="flavor-pp-label">
                <?php esc_html_e('Imágenes de apoyo', 'flavor-chat-ia'); ?>
            </label>
            <div class="flavor-pp-upload-area" id="pp-upload-area">
                <input type="file" id="pp-imagenes" name="imagenes[]" accept="image/*" multiple
                       style="display: none;">
                <div class="flavor-pp-upload-placeholder">
                    <span class="dashicons dashicons-upload"></span>
                    <p><?php esc_html_e('Arrastra imágenes aquí o haz clic para seleccionar', 'flavor-chat-ia'); ?></p>
                    <small><?php esc_html_e('Máximo 5 imágenes, 2MB cada una', 'flavor-chat-ia'); ?></small>
                </div>
                <div class="flavor-pp-preview-imagenes" id="pp-preview-imagenes"></div>
            </div>
        </div>

        <div class="flavor-pp-form-group">
            <label class="flavor-pp-checkbox-wrapper">
                <input type="checkbox" name="acepto_condiciones" required>
                <span class="flavor-pp-checkbox-label">
                    <?php printf(
                        esc_html__('He leído y acepto las %scondiciones del proceso participativo%s', 'flavor-chat-ia'),
                        '<a href="#" target="_blank">',
                        '</a>'
                    ); ?>
                </span>
            </label>
        </div>

        <div class="flavor-pp-form-actions">
            <button type="button" class="flavor-pp-btn flavor-pp-btn-secondary" id="pp-guardar-borrador">
                <span class="dashicons dashicons-edit"></span>
                <?php esc_html_e('Guardar borrador', 'flavor-chat-ia'); ?>
            </button>
            <button type="submit" class="flavor-pp-btn flavor-pp-btn-primary" id="pp-enviar-propuesta">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Enviar propuesta', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>

    <div class="flavor-pp-mensaje-exito" id="pp-mensaje-exito" style="display: none;">
        <span class="dashicons dashicons-yes-alt"></span>
        <h3><?php esc_html_e('¡Propuesta enviada correctamente!', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('Tu propuesta será revisada por el equipo técnico. Te notificaremos cuando pase a la fase de votación.', 'flavor-chat-ia'); ?></p>
        <div class="flavor-pp-acciones">
            <a href="<?php echo esc_url(home_url('/presupuestos-participativos/')); ?>" class="flavor-pp-btn flavor-pp-btn-secondary">
                <?php esc_html_e('Ver todos los proyectos', 'flavor-chat-ia'); ?>
            </a>
            <button type="button" class="flavor-pp-btn flavor-pp-btn-primary" id="pp-nueva-propuesta">
                <?php esc_html_e('Enviar otra propuesta', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>
</div>

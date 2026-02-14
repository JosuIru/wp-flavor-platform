<?php
/**
 * Vista: Mis propuestas
 *
 * @package FlavorChatIA
 * @var array $propuestas  Lista de propuestas del usuario
 * @var array $categorias  Categorías disponibles
 * @var array $atributos   Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$etiquetas_estado = [
    'borrador' => __('Borrador', 'flavor-chat-ia'),
    'pendiente_validacion' => __('Pendiente de validación', 'flavor-chat-ia'),
    'validado' => __('Validado', 'flavor-chat-ia'),
    'en_votacion' => __('En votación', 'flavor-chat-ia'),
    'seleccionado' => __('Seleccionado', 'flavor-chat-ia'),
    'en_ejecucion' => __('En ejecución', 'flavor-chat-ia'),
    'ejecutado' => __('Ejecutado', 'flavor-chat-ia'),
    'rechazado' => __('Rechazado', 'flavor-chat-ia'),
];

$clases_estado = [
    'borrador' => 'gris',
    'pendiente_validacion' => 'amarillo',
    'validado' => 'verde',
    'en_votacion' => 'azul',
    'seleccionado' => 'verde-oscuro',
    'en_ejecucion' => 'naranja',
    'ejecutado' => 'verde-exito',
    'rechazado' => 'rojo',
];
?>

<div class="flavor-pp-mis-propuestas">

    <div class="flavor-pp-mis-propuestas-header">
        <h2><?php esc_html_e('Mis propuestas', 'flavor-chat-ia'); ?></h2>
        <a href="<?php echo esc_url(home_url('/presupuestos-participativos/proponer/')); ?>"
           class="flavor-pp-btn flavor-pp-btn-primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Nueva propuesta', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <?php if (empty($propuestas)): ?>
        <div class="flavor-pp-vacio">
            <span class="dashicons dashicons-edit-large"></span>
            <h3><?php esc_html_e('Aún no has presentado ninguna propuesta', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('¿Tienes una idea para mejorar tu barrio? ¡Preséntala ahora!', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(home_url('/presupuestos-participativos/proponer/')); ?>"
               class="flavor-pp-btn flavor-pp-btn-primary">
                <?php esc_html_e('Crear mi primera propuesta', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="flavor-pp-propuestas-lista">
            <?php foreach ($propuestas as $propuesta):
                $estado = $propuesta->estado;
                $puede_editar = in_array($estado, ['borrador', 'pendiente_validacion'], true);
                $etiqueta_categoria = $categorias[$propuesta->categoria] ?? ucfirst($propuesta->categoria);
            ?>
                <article class="flavor-pp-mi-propuesta" data-id="<?php echo esc_attr($propuesta->id); ?>">

                    <div class="flavor-pp-propuesta-estado-bar estado-<?php echo esc_attr($clases_estado[$estado] ?? 'gris'); ?>">
                        <span class="flavor-pp-estado-texto">
                            <?php echo esc_html($etiquetas_estado[$estado] ?? ucfirst($estado)); ?>
                        </span>
                        <?php if ($estado === 'rechazado' && !empty($propuesta->motivo_no_viable)): ?>
                            <span class="flavor-pp-motivo-rechazo" title="<?php echo esc_attr($propuesta->motivo_no_viable); ?>">
                                <span class="dashicons dashicons-info"></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-pp-propuesta-content">
                        <div class="flavor-pp-propuesta-header">
                            <span class="flavor-pp-categoria flavor-pp-cat-<?php echo esc_attr($propuesta->categoria); ?>">
                                <?php echo esc_html($etiqueta_categoria); ?>
                            </span>
                            <span class="flavor-pp-fecha">
                                <?php echo esc_html(date_i18n('j M Y', strtotime($propuesta->fecha_creacion))); ?>
                            </span>
                        </div>

                        <h3 class="flavor-pp-propuesta-titulo">
                            <?php echo esc_html($propuesta->titulo); ?>
                        </h3>

                        <p class="flavor-pp-propuesta-descripcion">
                            <?php echo esc_html(wp_trim_words($propuesta->descripcion, 30)); ?>
                        </p>

                        <div class="flavor-pp-propuesta-meta">
                            <div class="flavor-pp-meta-item">
                                <span class="dashicons dashicons-money-alt"></span>
                                <span><?php echo number_format($propuesta->presupuesto_solicitado, 0, ',', '.'); ?> €</span>
                            </div>

                            <?php if (!empty($propuesta->ubicacion)): ?>
                            <div class="flavor-pp-meta-item">
                                <span class="dashicons dashicons-location"></span>
                                <span><?php echo esc_html($propuesta->ubicacion); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if (in_array($estado, ['en_votacion', 'seleccionado', 'en_ejecucion', 'ejecutado'], true)): ?>
                            <div class="flavor-pp-meta-item flavor-pp-votos">
                                <span class="dashicons dashicons-thumbs-up"></span>
                                <span><?php echo intval($propuesta->votos_recibidos); ?> <?php esc_html_e('votos', 'flavor-chat-ia'); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if ($propuesta->ranking > 0): ?>
                            <div class="flavor-pp-meta-item">
                                <span class="dashicons dashicons-awards"></span>
                                <span><?php printf(esc_html__('Posición #%d', 'flavor-chat-ia'), intval($propuesta->ranking)); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($propuesta->porcentaje_ejecucion > 0): ?>
                        <div class="flavor-pp-ejecucion-progreso">
                            <div class="flavor-pp-progreso-bar">
                                <div class="flavor-pp-progreso-fill" style="width: <?php echo intval($propuesta->porcentaje_ejecucion); ?>%;"></div>
                            </div>
                            <span class="flavor-pp-progreso-texto">
                                <?php printf(esc_html__('%d%% ejecutado', 'flavor-chat-ia'), intval($propuesta->porcentaje_ejecucion)); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-pp-propuesta-acciones">
                        <a href="<?php echo esc_url(add_query_arg('proyecto', $propuesta->id, home_url('/presupuestos-participativos/'))); ?>"
                           class="flavor-pp-btn flavor-pp-btn-link">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>

                        <?php if ($puede_editar): ?>
                            <button type="button" class="flavor-pp-btn flavor-pp-btn-link flavor-pp-editar-propuesta"
                                    data-id="<?php echo esc_attr($propuesta->id); ?>">
                                <span class="dashicons dashicons-edit"></span>
                                <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                            </button>

                            <button type="button" class="flavor-pp-btn flavor-pp-btn-link flavor-pp-btn-danger flavor-pp-eliminar-propuesta"
                                    data-id="<?php echo esc_attr($propuesta->id); ?>">
                                <span class="dashicons dashicons-trash"></span>
                                <?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmación de eliminación -->
<div id="flavor-pp-modal-eliminar" class="flavor-pp-modal" style="display: none;">
    <div class="flavor-pp-modal-overlay"></div>
    <div class="flavor-pp-modal-content flavor-pp-modal-small">
        <h3><?php esc_html_e('¿Eliminar propuesta?', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('Esta acción no se puede deshacer. ¿Estás seguro de que deseas eliminar esta propuesta?', 'flavor-chat-ia'); ?></p>
        <div class="flavor-pp-modal-acciones">
            <button type="button" class="flavor-pp-btn flavor-pp-btn-secondary flavor-pp-modal-cancelar">
                <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-pp-btn flavor-pp-btn-danger flavor-pp-modal-confirmar">
                <?php esc_html_e('Sí, eliminar', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>
</div>

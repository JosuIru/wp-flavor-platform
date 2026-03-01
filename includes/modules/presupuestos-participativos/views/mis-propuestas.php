<?php
/**
 * Vista: Mis Propuestas
 *
 * Variables disponibles:
 * - $propuestas: array de propuestas del usuario
 * - $categorias: array de categorias disponibles
 * - $atributos: array con configuracion del shortcode
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto seguros
$propuestas = $propuestas ?? [];
$categorias = $categorias ?? [];
$atributos = $atributos ?? [];
?>

<div class="flavor-pp-mis-propuestas-contenedor">
    <div class="flavor-pp-mis-propuestas-header">
        <h2><?php esc_html_e('Mis Propuestas', 'flavor-chat-ia'); ?></h2>
        <a href="<?php echo esc_url(home_url('/proponer-proyecto/')); ?>" class="flavor-pp-boton flavor-pp-boton-primario">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Nueva propuesta', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <?php if (empty($propuestas)): ?>
        <div class="flavor-pp-vacio">
            <span class="dashicons dashicons-lightbulb"></span>
            <p><?php esc_html_e('Aun no has presentado ninguna propuesta.', 'flavor-chat-ia'); ?></p>
            <p><?php esc_html_e('Comparte tus ideas para mejorar el barrio!', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="flavor-pp-propuestas-lista">
            <?php foreach ($propuestas as $propuesta):
                // Normalizar: soporta tanto arrays como objetos
                $prop_id = is_array($propuesta) ? ($propuesta['id'] ?? 0) : ($propuesta->id ?? 0);
                $prop_categoria = is_array($propuesta) ? ($propuesta['categoria'] ?? '') : ($propuesta->categoria ?? '');
                $prop_titulo = is_array($propuesta) ? ($propuesta['titulo'] ?? '') : ($propuesta->titulo ?? '');
                $prop_descripcion = is_array($propuesta) ? ($propuesta['descripcion'] ?? '') : ($propuesta->descripcion ?? '');
                $prop_presupuesto = is_array($propuesta) ? ($propuesta['presupuesto_solicitado'] ?? $propuesta['presupuesto_estimado'] ?? 0) : ($propuesta->presupuesto_solicitado ?? $propuesta->presupuesto_estimado ?? 0);
                $prop_votos = is_array($propuesta) ? ($propuesta['votos_recibidos'] ?? 0) : ($propuesta->votos_recibidos ?? 0);
                $prop_fecha = is_array($propuesta) ? ($propuesta['fecha_creacion'] ?? '') : ($propuesta->fecha_creacion ?? '');
                $prop_estado = is_array($propuesta) ? ($propuesta['estado'] ?? '') : ($propuesta->estado ?? '');
                $prop_motivo_rechazo = is_array($propuesta) ? ($propuesta['motivo_rechazo'] ?? '') : ($propuesta->motivo_rechazo ?? '');

                $categoria_nombre = $categorias[$prop_categoria] ?? ucfirst($prop_categoria);
                $estados = [
                    'pendiente' => ['label' => __('Pendiente de revision', 'flavor-chat-ia'), 'color' => 'orange'],
                    'validado' => ['label' => __('Validado', 'flavor-chat-ia'), 'color' => 'green'],
                    'en_votacion' => ['label' => __('En votacion', 'flavor-chat-ia'), 'color' => 'blue'],
                    'seleccionado' => ['label' => __('Seleccionado', 'flavor-chat-ia'), 'color' => 'green'],
                    'en_ejecucion' => ['label' => __('En ejecucion', 'flavor-chat-ia'), 'color' => 'purple'],
                    'ejecutado' => ['label' => __('Ejecutado', 'flavor-chat-ia'), 'color' => 'green'],
                    'rechazado' => ['label' => __('Rechazado', 'flavor-chat-ia'), 'color' => 'red'],
                ];
                $estado_info = $estados[$prop_estado] ?? ['label' => ucfirst($prop_estado), 'color' => 'gray'];
            ?>
            <article class="flavor-pp-propuesta-card" data-id="<?php echo esc_attr($prop_id); ?>">
                <div class="flavor-pp-propuesta-estado flavor-pp-estado-<?php echo esc_attr($estado_info['color']); ?>">
                    <?php echo esc_html($estado_info['label']); ?>
                </div>

                <div class="flavor-pp-propuesta-contenido">
                    <span class="flavor-pp-categoria flavor-pp-categoria-<?php echo esc_attr($prop_categoria); ?>">
                        <?php echo esc_html($categoria_nombre); ?>
                    </span>

                    <h3 class="flavor-pp-propuesta-titulo"><?php echo esc_html($prop_titulo); ?></h3>

                    <p class="flavor-pp-propuesta-descripcion">
                        <?php echo esc_html(wp_trim_words($prop_descripcion, 30, '...')); ?>
                    </p>

                    <div class="flavor-pp-propuesta-meta">
                        <span class="flavor-pp-meta-item">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php echo esc_html(number_format($prop_presupuesto, 0, ',', '.')); ?> EUR
                        </span>
                        <span class="flavor-pp-meta-item">
                            <span class="dashicons dashicons-heart"></span>
                            <?php echo esc_html($prop_votos); ?> <?php esc_html_e('votos', 'flavor-chat-ia'); ?>
                        </span>
                        <span class="flavor-pp-meta-item">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($prop_fecha))); ?>
                        </span>
                    </div>
                </div>

                <div class="flavor-pp-propuesta-acciones">
                    <?php if ($prop_estado === 'pendiente'): ?>
                        <button type="button" class="flavor-pp-boton flavor-pp-boton-secundario flavor-pp-btn-editar"
                                data-id="<?php echo esc_attr($prop_id); ?>">
                            <span class="dashicons dashicons-edit"></span>
                            <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="flavor-pp-boton flavor-pp-boton-peligro flavor-pp-btn-eliminar"
                                data-id="<?php echo esc_attr($prop_id); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>

                    <a href="<?php echo esc_url(add_query_arg('proyecto', $prop_id, home_url('/proyectos-participativos/'))); ?>"
                       class="flavor-pp-boton flavor-pp-boton-texto">
                        <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
                    </a>
                </div>

                <?php if ($prop_estado === 'rechazado' && !empty($prop_motivo_rechazo)): ?>
                <div class="flavor-pp-propuesta-rechazo">
                    <strong><?php esc_html_e('Motivo del rechazo:', 'flavor-chat-ia'); ?></strong>
                    <p><?php echo esc_html($prop_motivo_rechazo); ?></p>
                </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

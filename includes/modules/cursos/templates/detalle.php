<?php
/**
 * Template: Detalle del curso
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = $this->get_settings();
$categorias = $settings['categorias'] ?? [];
?>

<div class="curso-detalle">
    <div class="curso-header">
        <div class="curso-info">
            <div class="curso-card-categoria">
                <?php echo esc_html($categorias[$curso['categoria']] ?? $curso['categoria'] ?? __('General', 'flavor-chat-ia')); ?>
            </div>

            <h1><?php echo esc_html($curso['titulo']); ?></h1>

            <?php if ($curso['descripcion_corta']): ?>
                <p class="curso-descripcion-corta"><?php echo esc_html($curso['descripcion_corta']); ?></p>
            <?php endif; ?>

            <div class="curso-instructor-info">
                <img src="<?php echo esc_url($curso['instructor']['avatar']); ?>" alt="">
                <div>
                    <div class="nombre"><?php echo esc_html($curso['instructor']['nombre']); ?></div>
                    <div class="titulo"><?php _e('Instructor', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <div class="curso-stats">
                <div class="curso-stat">
                    <span class="dashicons dashicons-star-filled"></span>
                    <strong><?php echo number_format($curso['valoracion'], 1); ?></strong>
                    <span>(<?php printf(_n('%d valoración', '%d valoraciones', $curso['num_valoraciones'], 'flavor-chat-ia'), $curso['num_valoraciones']); ?>)</span>
                </div>
                <div class="curso-stat">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php printf(__('%d alumnos', 'flavor-chat-ia'), $curso['alumnos']); ?>
                </div>
                <div class="curso-stat">
                    <span class="dashicons dashicons-clock"></span>
                    <?php printf(__('%d horas', 'flavor-chat-ia'), $curso['duracion_horas']); ?>
                </div>
                <div class="curso-stat">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php echo esc_html(ucfirst($curso['nivel'])); ?>
                </div>
                <div class="curso-stat">
                    <span class="dashicons dashicons-laptop"></span>
                    <?php echo esc_html(ucfirst($curso['modalidad'])); ?>
                </div>
            </div>
        </div>

        <div class="curso-sidebar">
            <div class="curso-card-inscripcion">
                <?php if ($curso['video']): ?>
                    <div class="curso-video-preview">
                        <iframe src="<?php echo esc_url($curso['video']); ?>" frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php elseif ($curso['imagen']): ?>
                    <img src="<?php echo esc_url($curso['imagen']); ?>" alt="" style="width:100%;">
                <?php endif; ?>

                <div class="curso-inscripcion-contenido">
                    <div class="curso-precio-grande <?php echo $curso['es_gratuito'] ? 'gratuito' : ''; ?>">
                        <?php echo $curso['es_gratuito'] ? __('Gratis', 'flavor-chat-ia') : number_format($curso['precio'], 2) . ' €'; ?>
                    </div>

                    <?php if ($inscripcion): ?>
                        <?php if ($inscripcion['estado'] === 'completada'): ?>
                            <a href="<?php echo esc_url(add_query_arg(['page' => 'aula', 'curso_id' => $curso['id']], get_permalink())); ?>" class="btn-inscribirse btn-continuar">
                                <?php _e('Revisar curso', 'flavor-chat-ia'); ?>
                            </a>
                            <?php if (!$inscripcion['certificado']): ?>
                                <button type="button" class="btn-certificado" data-curso-id="<?php echo $curso['id']; ?>" style="width:100%; margin-top:0.5rem;">
                                    <span class="dashicons dashicons-awards"></span>
                                    <?php _e('Obtener certificado', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?php echo esc_url(add_query_arg(['page' => 'aula', 'curso_id' => $curso['id']], get_permalink())); ?>" class="btn-inscribirse btn-continuar">
                                <?php _e('Continuar curso', 'flavor-chat-ia'); ?>
                            </a>
                            <div class="progreso-inscripcion" style="margin-top:1rem;">
                                <div class="progreso-bar">
                                    <div class="progreso-bar-fill" style="width: <?php echo $inscripcion['progreso']; ?>%"></div>
                                </div>
                                <div class="progreso-texto" style="text-align:center; margin-top:0.5rem; font-size:0.875rem; color:#6b7280;">
                                    <?php printf(__('%d%% completado', 'flavor-chat-ia'), $inscripcion['progreso']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($curso['plazas_disponibles'] > 0): ?>
                        <button type="button" class="btn-inscribirse" data-curso-id="<?php echo $curso['id']; ?>">
                            <?php echo $curso['es_gratuito'] ? __('Inscribirse gratis', 'flavor-chat-ia') : __('Comprar curso', 'flavor-chat-ia'); ?>
                        </button>
                        <p style="text-align:center; margin-top:0.5rem; font-size:0.875rem; color:#6b7280;">
                            <?php printf(__('%d plazas disponibles', 'flavor-chat-ia'), $curso['plazas_disponibles']); ?>
                        </p>
                    <?php else: ?>
                        <button type="button" class="btn-inscribirse" disabled>
                            <?php _e('Sin plazas disponibles', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>

                    <div class="curso-incluye">
                        <h4><?php _e('Este curso incluye', 'flavor-chat-ia'); ?></h4>
                        <ul>
                            <li>
                                <span class="dashicons dashicons-video-alt3"></span>
                                <?php printf(__('%d lecciones', 'flavor-chat-ia'), count($lecciones)); ?>
                            </li>
                            <li>
                                <span class="dashicons dashicons-clock"></span>
                                <?php printf(__('%d horas de contenido', 'flavor-chat-ia'), $curso['duracion_horas']); ?>
                            </li>
                            <li>
                                <span class="dashicons dashicons-smartphone"></span>
                                <?php _e('Acceso en móvil y escritorio', 'flavor-chat-ia'); ?>
                            </li>
                            <li>
                                <span class="dashicons dashicons-awards"></span>
                                <?php _e('Certificado de finalización', 'flavor-chat-ia'); ?>
                            </li>
                            <li>
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Acceso de por vida', 'flavor-chat-ia'); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="curso-contenido">
        <div class="curso-main">
            <div class="curso-tabs">
                <div class="curso-tab active" data-tab="descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></div>
                <div class="curso-tab" data-tab="contenido"><?php _e('Contenido', 'flavor-chat-ia'); ?></div>
                <?php if (!empty($curso['requisitos']) || !empty($curso['que_aprenderas'])): ?>
                    <div class="curso-tab" data-tab="objetivos"><?php _e('Objetivos', 'flavor-chat-ia'); ?></div>
                <?php endif; ?>
            </div>

            <div class="curso-tab-contenido" data-tab-content="descripcion">
                <div class="curso-descripcion">
                    <?php echo wp_kses_post($curso['descripcion']); ?>
                </div>
            </div>

            <div class="curso-tab-contenido" data-tab-content="contenido" style="display:none;">
                <h3><?php printf(__('%d lecciones', 'flavor-chat-ia'), count($lecciones)); ?></h3>
                <ul class="lecciones-lista">
                    <?php foreach ($lecciones as $leccion): ?>
                        <li class="leccion-item">
                            <span class="leccion-numero"><?php echo $leccion['orden']; ?></span>
                            <div class="leccion-info">
                                <div class="leccion-titulo">
                                    <?php echo esc_html($leccion['titulo']); ?>
                                    <?php if ($leccion['es_gratuita']): ?>
                                        <span class="badge-gratis"><?php _e('Vista previa', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="leccion-meta">
                                    <?php
                                    $tipo_icons = [
                                        'video' => 'video-alt3',
                                        'texto' => 'text-page',
                                        'quiz' => 'clipboard',
                                        'archivo' => 'download',
                                        'enlace' => 'admin-links',
                                    ];
                                    $icono = $tipo_icons[$leccion['tipo']] ?? 'media-document';
                                    ?>
                                    <span class="dashicons dashicons-<?php echo $icono; ?>"></span>
                                    <?php echo esc_html(ucfirst($leccion['tipo'])); ?>
                                    <?php if ($leccion['duracion']): ?>
                                        &bull; <?php printf(__('%d min', 'flavor-chat-ia'), $leccion['duracion']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="leccion-icono dashicons dashicons-lock"></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if (!empty($curso['requisitos']) || !empty($curso['que_aprenderas'])): ?>
                <div class="curso-tab-contenido" data-tab-content="objetivos" style="display:none;">
                    <?php if (!empty($curso['que_aprenderas'])): ?>
                        <h3><?php _e('Lo que aprenderás', 'flavor-chat-ia'); ?></h3>
                        <ul class="lista-objetivos">
                            <?php foreach ($curso['que_aprenderas'] as $objetivo): ?>
                                <li>
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php echo esc_html($objetivo); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($curso['requisitos'])): ?>
                        <h3><?php _e('Requisitos', 'flavor-chat-ia'); ?></h3>
                        <ul class="lista-requisitos">
                            <?php foreach ($curso['requisitos'] as $requisito): ?>
                                <li>
                                    <span class="dashicons dashicons-arrow-right-alt"></span>
                                    <?php echo esc_html($requisito); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

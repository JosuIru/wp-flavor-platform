<?php
/**
 * Template: Mis cursos
 */

if (!defined('ABSPATH')) {
    exit;
}

$cursos = $resultado['success'] ? $resultado['cursos'] : [];
?>

<div class="mis-cursos-wrapper">
    <h2><?php _e('Mis Cursos', 'flavor-chat-ia'); ?></h2>

    <?php if (empty($cursos)): ?>
        <div class="cursos-vacio">
            <span class="dashicons dashicons-welcome-learn-more"></span>
            <p><?php _e('Aún no te has inscrito en ningún curso.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(home_url('/cursos/')); ?>" class="btn-continuar-curso">
                <?php _e('Explorar cursos', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="mis-cursos-tabs" style="margin-bottom: 1.5rem;">
            <button class="tab-btn active" data-filter="todos"><?php _e('Todos', 'flavor-chat-ia'); ?></button>
            <button class="tab-btn" data-filter="activa"><?php _e('En progreso', 'flavor-chat-ia'); ?></button>
            <button class="tab-btn" data-filter="completada"><?php _e('Completados', 'flavor-chat-ia'); ?></button>
        </div>

        <div class="mis-cursos-grid">
            <?php foreach ($cursos as $curso): ?>
                <div class="mi-curso-card" data-estado="<?php echo esc_attr($curso['estado_inscripcion']); ?>">
                    <div class="mi-curso-imagen">
                        <?php if ($curso['imagen']): ?>
                            <img src="<?php echo esc_url($curso['imagen']); ?>" alt="<?php echo esc_attr($curso['titulo']); ?>">
                        <?php else: ?>
                            <img src="https://placehold.co/400x225/e5e7eb/6b7280?text=<?php echo urlencode($curso['titulo']); ?>" alt="">
                        <?php endif; ?>

                        <div class="mi-curso-progreso-overlay">
                            <div class="progreso-bar">
                                <div class="progreso-bar-fill" style="width: <?php echo $curso['progreso']; ?>%"></div>
                            </div>
                            <div class="progreso-texto">
                                <?php printf(__('%d%% completado', 'flavor-chat-ia'), $curso['progreso']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="mi-curso-contenido">
                        <h3 class="mi-curso-titulo"><?php echo esc_html($curso['titulo']); ?></h3>
                        <p class="mi-curso-instructor"><?php printf(__('Por %s', 'flavor-chat-ia'), esc_html($curso['instructor'])); ?></p>

                        <div class="mi-curso-acciones">
                            <?php if ($curso['estado_inscripcion'] === 'completada'): ?>
                                <a href="<?php echo esc_url(add_query_arg(['page' => 'aula', 'curso_id' => $curso['id']], get_permalink())); ?>" class="btn-continuar-curso">
                                    <?php _e('Revisar', 'flavor-chat-ia'); ?>
                                </a>
                                <?php if (!$curso['certificado']): ?>
                                    <button type="button" class="btn-certificado" data-curso-id="<?php echo $curso['id']; ?>">
                                        <span class="dashicons dashicons-awards"></span>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'certificado', 'curso_id' => $curso['id']], get_permalink())); ?>" class="btn-certificado">
                                        <span class="dashicons dashicons-awards"></span>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?php echo esc_url(add_query_arg(['page' => 'aula', 'curso_id' => $curso['id']], get_permalink())); ?>" class="btn-continuar-curso">
                                    <?php _e('Continuar', 'flavor-chat-ia'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
(function($) {
    $('.mis-cursos-tabs .tab-btn').on('click', function() {
        const filter = $(this).data('filter');

        $('.mis-cursos-tabs .tab-btn').removeClass('active');
        $(this).addClass('active');

        if (filter === 'todos') {
            $('.mi-curso-card').show();
        } else {
            $('.mi-curso-card').hide();
            $('.mi-curso-card[data-estado="' + filter + '"]').show();
        }
    });
})(jQuery);
</script>

<style>
.mis-cursos-tabs {
    display: flex;
    gap: 0.5rem;
}
.mis-cursos-tabs .tab-btn {
    padding: 0.5rem 1rem;
    background: var(--cursos-bg, #f9fafb);
    border: 1px solid var(--cursos-border, #e5e7eb);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}
.mis-cursos-tabs .tab-btn:hover,
.mis-cursos-tabs .tab-btn.active {
    background: var(--cursos-primary, #6366f1);
    color: #fff;
    border-color: var(--cursos-primary, #6366f1);
}
</style>

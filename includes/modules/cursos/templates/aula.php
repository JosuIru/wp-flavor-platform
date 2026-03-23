<?php
/**
 * Template: Aula Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_cursos = $wpdb->prefix . 'flavor_cursos';
$tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

$usuario_id = get_current_user_id();

// Verificar inscripción
$inscripcion = $wpdb->get_row($wpdb->prepare(
    "SELECT i.*, c.titulo as curso_titulo
     FROM $tabla_inscripciones i
     INNER JOIN $tabla_cursos c ON i.curso_id = c.id
     WHERE i.curso_id = %d AND i.usuario_id = %d AND i.estado IN ('activo', 'completado')",
    $curso_id,
    $usuario_id
));

if (!$inscripcion) {
    echo '<div class="cursos-error">' . __('No tienes acceso a este curso.', 'flavor-chat-ia') . '</div>';
    return;
}
?>

<div class="aula-wrapper">
    <aside class="aula-sidebar">
        <div class="aula-curso-info">
            <h3 class="aula-curso-titulo"><?php echo esc_html($inscripcion->curso_titulo); ?></h3>

            <div class="aula-progreso">
                <div class="aula-progreso-bar">
                    <div class="aula-progreso-fill" style="width: <?php echo $inscripcion->progreso_porcentaje; ?>%"></div>
                </div>
                <div class="aula-progreso-texto">
                    <span class="completadas">0/0</span>
                    <span class="porcentaje"><?php echo round($inscripcion->progreso_porcentaje); ?>%</span>
                </div>
            </div>
        </div>

        <ul class="aula-lecciones">
            <!-- Se cargan vía JavaScript -->
            <li class="aula-loading" style="padding: 2rem; text-align: center; color: #6b7280;">
                <span class="cursos-spinner"></span>
                <?php _e('Cargando lecciones...', 'flavor-chat-ia'); ?>
            </li>
        </ul>
    </aside>

    <main class="aula-contenido">
        <header class="aula-header">
            <h2><?php _e('Selecciona una lección', 'flavor-chat-ia'); ?></h2>

            <div class="aula-navegacion">
                <button type="button" class="aula-nav-btn anterior" disabled>
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php _e('Anterior', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="aula-nav-btn siguiente" disabled>
                    <?php _e('Siguiente', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </header>

        <div class="aula-leccion-contenido">
            <div style="padding: 4rem 2rem; text-align: center; color: #6b7280;">
                <span class="dashicons dashicons-welcome-learn-more" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 1rem;"></span>
                <p><?php _e('Selecciona una lección del menú lateral para comenzar.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <footer class="aula-footer">
            <div class="aula-tiempo">
                <span class="dashicons dashicons-clock"></span>
                <span class="tiempo-texto"></span>
            </div>
            <button type="button" class="btn-completar" disabled>
                <?php _e('Marcar como completada', 'flavor-chat-ia'); ?>
            </button>
        </footer>
    </main>

    <button type="button" class="aula-toggle-sidebar">
        <span class="dashicons dashicons-menu"></span>
    </button>
</div>

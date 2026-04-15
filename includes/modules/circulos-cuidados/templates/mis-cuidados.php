<?php
/**
 * Template: Mis Cuidados
 *
 * Muestra el historial y estadísticas de cuidados del usuario.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$circulos_cuidados_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Circulos_Cuidados_Module')
    : 'Flavor_Chat_Circulos_Cuidados_Module';
$modulo = new $circulos_cuidados_module_class();
$stats = $modulo->get_estadisticas_usuario($user_id);
$tipos = $circulos_cuidados_module_class::TIPOS_CIRCULO;

// Obtener círculos donde participa
global $wpdb;
$mis_circulos = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'cc_circulo'
       AND p.post_status = 'publish'
       AND pm.meta_key = '_cc_miembros'
       AND pm.meta_value LIKE %s",
    '%"' . $user_id . '"%'
));

// Obtener mis necesidades activas
$mis_necesidades = get_posts([
    'post_type' => 'cc_necesidad',
    'posts_per_page' => 5,
    'author' => $user_id,
    'meta_query' => [
        [
            'key' => '_cc_estado',
            'value' => ['abierta', 'en_proceso'],
            'compare' => 'IN',
        ],
    ],
]);

// Historial de ayuda recibida
$ayuda_recibida = $wpdb->get_results($wpdb->prepare(
    "SELECT rh.*, n.post_title as necesidad_titulo, u.display_name as cuidador_nombre
     FROM {$wpdb->posts} rh
     INNER JOIN {$wpdb->postmeta} pm ON rh.ID = pm.post_id
     INNER JOIN {$wpdb->posts} n ON pm.meta_value = n.ID
     INNER JOIN {$wpdb->users} u ON rh.post_author = u.ID
     WHERE rh.post_type = 'cc_registro_horas'
       AND pm.meta_key = '_cc_necesidad_id'
       AND n.post_author = %d
     ORDER BY rh.post_date DESC
     LIMIT 10",
    $user_id
));

// Historial de ayuda dada
$ayuda_dada = get_posts([
    'post_type' => 'cc_registro_horas',
    'posts_per_page' => 10,
    'author' => $user_id,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="cc-mis-cuidados">
    <h2><?php esc_html_e('Mis Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

    <!-- Estadísticas -->
    <div class="cc-stats-grid">
        <div class="cc-stat-card">
            <div class="cc-stat-card__valor"><?php echo esc_html($stats['circulos']); ?></div>
            <div class="cc-stat-card__label"><?php esc_html_e('Círculos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div class="cc-stat-card cc-stat-card--horas">
            <div class="cc-stat-card__valor"><?php echo esc_html(number_format($stats['horas_cuidado'], 1)); ?>h</div>
            <div class="cc-stat-card__label"><?php esc_html_e('Horas donadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div class="cc-stat-card cc-stat-card--ayudas">
            <div class="cc-stat-card__valor"><?php echo esc_html($stats['necesidades_ayudadas']); ?></div>
            <div class="cc-stat-card__label"><?php esc_html_e('Ayudas dadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <!-- Mis círculos -->
    <section class="cc-seccion">
        <h3 class="cc-seccion__titulo"><?php esc_html_e('Mis Círculos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

        <?php if ($mis_circulos) : ?>
        <div class="cc-listado__grid">
            <?php foreach ($mis_circulos as $circulo) :
                $tipo = get_post_meta($circulo->ID, '_cc_tipo', true);
                $tipo_data = $tipos[$tipo] ?? $tipos['mayores'];
                $miembros = get_post_meta($circulo->ID, '_cc_miembros', true) ?: [];
            ?>
            <article class="cc-circulo-card" style="--cc-color: <?php echo esc_attr($tipo_data['color']); ?>">
                <header class="cc-circulo-card__header">
                    <span class="cc-circulo-card__icono dashicons <?php echo esc_attr($tipo_data['icono']); ?>"></span>
                    <span class="cc-circulo-card__tipo"><?php echo esc_html($tipo_data['nombre']); ?></span>
                </header>
                <h4 class="cc-circulo-card__titulo">
                    <a href="<?php echo esc_url(get_permalink($circulo->ID)); ?>">
                        <?php echo esc_html($circulo->post_title); ?>
                    </a>
                </h4>
                <footer class="cc-circulo-card__footer">
                    <span class="cc-circulo-card__miembros">
                        <span class="dashicons dashicons-groups"></span>
                        <?php echo count($miembros); ?> <?php esc_html_e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <span class="cc-badge-miembro"><?php esc_html_e('Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </footer>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p class="cc-empty-text"><?php esc_html_e('No participas en ningún círculo aún.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php endif; ?>
    </section>

    <!-- Mis necesidades -->
    <section class="cc-seccion">
        <h3 class="cc-seccion__titulo">
            <?php esc_html_e('Mis Necesidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <button class="cc-btn cc-btn--primary" data-cc-modal="cc-modal-crear-necesidad" style="float: right; margin-top: -0.25rem;">
                <?php esc_html_e('+ Nueva necesidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </h3>

        <?php if ($mis_necesidades) : ?>
        <div class="cc-necesidades-lista">
            <?php foreach ($mis_necesidades as $necesidad) :
                $urgencia = get_post_meta($necesidad->ID, '_cc_urgencia', true) ?: 'normal';
                $estado = get_post_meta($necesidad->ID, '_cc_estado', true) ?: 'abierta';
                $ayudantes = get_post_meta($necesidad->ID, '_cc_ayudantes', true) ?: [];
            ?>
            <div class="cc-necesidad-card cc-necesidad-card--<?php echo esc_attr($urgencia); ?>">
                <div class="cc-necesidad-card__header">
                    <h4 class="cc-necesidad-card__titulo"><?php echo esc_html($necesidad->post_title); ?></h4>
                    <span class="cc-urgencia-badge cc-urgencia-badge--<?php echo esc_attr($urgencia); ?>">
                        <?php echo esc_html(ucfirst($urgencia)); ?>
                    </span>
                </div>
                <div class="cc-necesidad-card__meta">
                    <span>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo get_the_date('d M Y', $necesidad); ?>
                    </span>
                    <span>
                        <span class="dashicons dashicons-groups"></span>
                        <?php printf(esc_html__('%d ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN), count($ayudantes)); ?>
                    </span>
                    <span class="cc-estado-badge cc-estado-badge--<?php echo esc_attr($estado); ?>">
                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $estado))); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p class="cc-empty-text"><?php esc_html_e('No tienes necesidades activas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php endif; ?>
    </section>

    <!-- Historial de ayuda dada -->
    <section class="cc-seccion">
        <h3 class="cc-seccion__titulo"><?php esc_html_e('Ayuda que he dado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

        <?php if ($ayuda_dada) : ?>
        <table class="cc-tabla">
            <thead>
                <tr>
                    <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ayuda_dada as $registro) :
                    $horas = get_post_meta($registro->ID, '_cc_horas', true);
                    $descripcion = get_post_meta($registro->ID, '_cc_descripcion', true);
                ?>
                <tr>
                    <td><?php echo get_the_date('d/m/Y', $registro); ?></td>
                    <td><?php echo esc_html($descripcion ?: $registro->post_title); ?></td>
                    <td><strong><?php echo esc_html($horas); ?>h</strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p class="cc-empty-text"><?php esc_html_e('Aún no has registrado horas de cuidado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php endif; ?>
    </section>
</div>

<!-- Modal crear necesidad -->
<div class="cc-modal" id="cc-modal-crear-necesidad">
    <div class="cc-modal__contenido">
        <div class="cc-modal__header">
            <h3 class="cc-modal__titulo"><?php esc_html_e('Solicitar ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <button class="cc-modal__cerrar">&times;</button>
        </div>
        <form class="cc-form-crear-necesidad">
            <div class="cc-modal__body">
                <?php if ($mis_circulos) : ?>
                <div class="cc-form-grupo">
                    <label for="cc-circulo"><?php esc_html_e('Círculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="circulo_id" id="cc-circulo" required>
                        <?php foreach ($mis_circulos as $circulo) : ?>
                        <option value="<?php echo esc_attr($circulo->ID); ?>">
                            <?php echo esc_html($circulo->post_title); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="cc-form-grupo">
                    <label for="cc-titulo"><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="titulo" id="cc-titulo" required
                           placeholder="<?php esc_attr_e('Ej: Necesito compañía para mi madre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <div class="cc-form-grupo">
                    <label for="cc-descripcion"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="descripcion" id="cc-descripcion" rows="3"
                              placeholder="<?php esc_attr_e('Describe qué necesitas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="cc-form-grupo">
                    <label for="cc-tipo-ayuda"><?php esc_html_e('Tipo de ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="tipo_ayuda" id="cc-tipo-ayuda">
                        <option value="acompanamiento"><?php esc_html_e('Acompañamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="recados"><?php esc_html_e('Recados/Compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="transporte"><?php esc_html_e('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="cuidado"><?php esc_html_e('Cuidado directo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="apoyo_emocional"><?php esc_html_e('Apoyo emocional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="otro"><?php esc_html_e('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div class="cc-form-grupo">
                    <label for="cc-urgencia"><?php esc_html_e('Urgencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="urgencia" id="cc-urgencia">
                        <option value="baja"><?php esc_html_e('Baja - Puede esperar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="normal" selected><?php esc_html_e('Normal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="alta"><?php esc_html_e('Alta - Próximos días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="urgente"><?php esc_html_e('Urgente - Hoy/Mañana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div class="cc-form-grupo">
                    <label for="cc-fecha"><?php esc_html_e('Fecha necesaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="datetime-local" name="fecha" id="cc-fecha">
                </div>

                <div class="cc-form-grupo">
                    <label for="cc-horas-est"><?php esc_html_e('Horas estimadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" name="horas_estimadas" id="cc-horas-est" min="0.5" step="0.5" value="2">
                </div>
            </div>
            <div class="cc-modal__footer">
                <button type="button" class="cc-btn cc-btn--secondary cc-modal__cerrar">
                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="submit" class="cc-btn cc-btn--primary">
                    <?php esc_html_e('Publicar necesidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
/**
 * Template: Mis Registros de Huella Ecológica
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$modulo = new Flavor_Chat_Huella_Ecologica_Module();
$stats = $modulo->get_estadisticas_usuario($user_id, 'mes');
$categorias = Flavor_Chat_Huella_Ecologica_Module::CATEGORIAS_HUELLA;
$acciones = Flavor_Chat_Huella_Ecologica_Module::TIPOS_ACCION;

// Obtener últimos registros
global $wpdb;
$registros = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title, p.post_date,
            pm_cat.meta_value as categoria,
            pm_val.meta_value as valor,
            pm_fecha.meta_value as fecha
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm_cat ON p.ID = pm_cat.post_id AND pm_cat.meta_key = '_he_categoria'
     INNER JOIN {$wpdb->postmeta} pm_val ON p.ID = pm_val.post_id AND pm_val.meta_key = '_he_valor'
     INNER JOIN {$wpdb->postmeta} pm_fecha ON p.ID = pm_fecha.post_id AND pm_fecha.meta_key = '_he_fecha'
     WHERE p.post_type = 'he_registro'
       AND p.post_author = %d
     ORDER BY pm_fecha.meta_value DESC
     LIMIT 20",
    $user_id
));

$acciones_registradas = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title,
            pm_tipo.meta_value as tipo,
            pm_red.meta_value as reduccion,
            pm_fecha.meta_value as fecha
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = '_he_tipo'
     INNER JOIN {$wpdb->postmeta} pm_red ON p.ID = pm_red.post_id AND pm_red.meta_key = '_he_reduccion'
     INNER JOIN {$wpdb->postmeta} pm_fecha ON p.ID = pm_fecha.post_id AND pm_fecha.meta_key = '_he_fecha'
     WHERE p.post_type = 'he_accion'
       AND p.post_author = %d
     ORDER BY pm_fecha.meta_value DESC
     LIMIT 20",
    $user_id
));
?>

<div class="he-container">
    <header class="he-header">
        <h2>
            <span class="dashicons dashicons-chart-line"></span>
            <?php esc_html_e('Mi Huella Ecológica', 'flavor-chat-ia'); ?>
        </h2>
        <p><?php esc_html_e('Seguimiento de tu impacto ambiental y acciones de reducción', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Selector de período -->
    <div style="text-align: right; margin-bottom: 1rem;">
        <select id="he-periodo" class="he-select">
            <option value="semana"><?php esc_html_e('Esta semana', 'flavor-chat-ia'); ?></option>
            <option value="mes" selected><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></option>
            <option value="anio"><?php esc_html_e('Este año', 'flavor-chat-ia'); ?></option>
            <option value="total"><?php esc_html_e('Todo el historial', 'flavor-chat-ia'); ?></option>
        </select>
    </div>

    <!-- Estadísticas resumen -->
    <div class="he-stats-grid">
        <div class="he-stat-card" data-stat="huella">
            <span class="he-stat-card__icono dashicons dashicons-cloud"></span>
            <div class="he-stat-card__valor"><?php echo esc_html($stats['huella_total']); ?> kg</div>
            <div class="he-stat-card__label"><?php esc_html_e('CO2 emitido', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="he-stat-card he-stat-card--reduccion" data-stat="reduccion">
            <span class="he-stat-card__icono dashicons dashicons-yes-alt"></span>
            <div class="he-stat-card__valor"><?php echo esc_html($stats['reduccion_total']); ?> kg</div>
            <div class="he-stat-card__label"><?php esc_html_e('CO2 compensado', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="he-stat-card he-stat-card--neta" data-stat="neta">
            <span class="he-stat-card__icono dashicons dashicons-performance"></span>
            <div class="he-stat-card__valor"><?php echo esc_html($stats['huella_neta']); ?> kg</div>
            <div class="he-stat-card__label"><?php esc_html_e('Huella neta', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="he-stat-card">
            <span class="he-stat-card__icono dashicons dashicons-awards"></span>
            <div class="he-stat-card__valor"><?php echo count(array_filter($stats['logros'], fn($l) => $l['obtenido'])); ?></div>
            <div class="he-stat-card__label"><?php esc_html_e('Logros', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Gráfico por categoría -->
        <div class="he-chart-bar">
            <h3><?php esc_html_e('Huella por categoría', 'flavor-chat-ia'); ?></h3>
            <?php
            $max_valor = 1;
            if ($stats['huella_por_categoria']) {
                $max_valor = max(array_column($stats['huella_por_categoria'], 'total')) ?: 1;
            }
            foreach ($categorias as $cat_id => $cat_data) :
                $cat_valor = 0;
                foreach ($stats['huella_por_categoria'] as $cat) {
                    if ($cat['categoria'] === $cat_id) {
                        $cat_valor = $cat['total'];
                        break;
                    }
                }
                $porcentaje = $max_valor > 0 ? ($cat_valor / $max_valor * 100) : 0;
            ?>
            <div class="he-chart-bar__item" data-categoria="<?php echo esc_attr($cat_id); ?>">
                <div class="he-chart-bar__header">
                    <span class="he-chart-bar__label">
                        <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>" style="color: <?php echo esc_attr($cat_data['color']); ?>"></span>
                        <?php echo esc_html($cat_data['nombre']); ?>
                    </span>
                    <span class="he-chart-bar__value"><?php echo esc_html(number_format($cat_valor, 1)); ?> kg</span>
                </div>
                <div class="he-chart-bar__track">
                    <div class="he-chart-bar__fill" style="width: <?php echo esc_attr($porcentaje); ?>%; background: <?php echo esc_attr($cat_data['color']); ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Formulario rápido de registro -->
        <div>
            <h3><?php esc_html_e('Registrar consumo', 'flavor-chat-ia'); ?></h3>
            <form class="he-form-registro" style="background: var(--he-bg-card); padding: 1.5rem; border-radius: var(--he-radius); box-shadow: var(--he-shadow);">
                <div class="he-form-grupo">
                    <label for="he-fecha"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></label>
                    <input type="date" name="fecha" id="he-fecha" value="<?php echo esc_attr(date('Y-m-d')); ?>" required>
                </div>
                <div class="he-form-grupo">
                    <label for="he-categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label>
                    <select name="categoria" id="he-categoria" required>
                        <?php foreach ($categorias as $cat_id => $cat_data) : ?>
                        <option value="<?php echo esc_attr($cat_id); ?>"><?php echo esc_html($cat_data['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="he-form-grupo">
                    <label for="he-valor"><?php esc_html_e('Valor', 'flavor-chat-ia'); ?></label>
                    <div class="he-input-con-unidad">
                        <input type="number" name="valor" id="he-valor" min="0.1" step="0.1" required>
                        <span class="he-unidad">kg CO2</span>
                    </div>
                </div>
                <div class="he-form-grupo">
                    <label for="he-descripcion"><?php esc_html_e('Descripción (opcional)', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="descripcion" id="he-descripcion" placeholder="<?php esc_attr_e('Ej: Viaje en coche al trabajo', 'flavor-chat-ia'); ?>">
                </div>
                <button type="submit" class="he-btn he-btn--primary" style="width: 100%;">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Registrar', 'flavor-chat-ia'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Historial -->
    <div class="he-historial" style="margin-top: 2rem;">
        <div class="he-historial__header">
            <h3><?php esc_html_e('Historial reciente', 'flavor-chat-ia'); ?></h3>
            <div class="he-historial__filtros">
                <button class="he-filtro-btn activo" data-filtro="todos"><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></button>
                <button class="he-filtro-btn" data-filtro="emisiones"><?php esc_html_e('Emisiones', 'flavor-chat-ia'); ?></button>
                <button class="he-filtro-btn" data-filtro="acciones"><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></button>
            </div>
        </div>

        <div class="he-historial__lista">
            <?php if ($registros || $acciones_registradas) : ?>
                <?php foreach ($registros as $registro) :
                    $cat_data = $categorias[$registro->categoria] ?? ['icono' => 'dashicons-marker', 'color' => '#95a5a6', 'nombre' => 'Otro'];
                ?>
                <div class="he-registro-item" data-tipo="emisiones" data-categoria="<?php echo esc_attr($registro->categoria); ?>">
                    <div class="he-registro-item__icono" style="background: <?php echo esc_attr($cat_data['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>"></span>
                    </div>
                    <div class="he-registro-item__info">
                        <div class="he-registro-item__titulo"><?php echo esc_html($cat_data['nombre']); ?></div>
                        <div class="he-registro-item__fecha"><?php echo esc_html(date_i18n('d M Y', strtotime($registro->fecha))); ?></div>
                    </div>
                    <div class="he-registro-item__valor positivo">+<?php echo esc_html(number_format($registro->valor, 1)); ?> kg</div>
                </div>
                <?php endforeach; ?>

                <?php foreach ($acciones_registradas as $accion) :
                    $accion_data = $acciones[$accion->tipo] ?? ['nombre' => 'Acción'];
                ?>
                <div class="he-registro-item" data-tipo="acciones">
                    <div class="he-registro-item__icono" style="background: var(--he-secondary);">
                        <span class="dashicons dashicons-yes"></span>
                    </div>
                    <div class="he-registro-item__info">
                        <div class="he-registro-item__titulo"><?php echo esc_html($accion_data['nombre']); ?></div>
                        <div class="he-registro-item__fecha"><?php echo esc_html(date_i18n('d M Y', strtotime($accion->fecha))); ?></div>
                    </div>
                    <div class="he-registro-item__valor negativo">-<?php echo esc_html(number_format($accion->reduccion, 1)); ?> kg</div>
                </div>
                <?php endforeach; ?>
            <?php else : ?>
            <div class="he-empty-state">
                <span class="dashicons dashicons-chart-line"></span>
                <p><?php esc_html_e('Aún no tienes registros. ¡Empieza a registrar tu huella!', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('huella_ecologica', 'acciones')); ?>" class="he-btn he-btn--primary">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php esc_html_e('Registrar acción reductora', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('huella_ecologica', 'logros')); ?>" class="he-btn he-btn--secondary">
            <span class="dashicons dashicons-awards"></span>
            <?php esc_html_e('Ver mis logros', 'flavor-chat-ia'); ?>
        </a>
    </div>
</div>

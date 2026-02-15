<?php
/**
 * Template: Ranking de Compostaje
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
$usuario_actual = get_current_user_id();

// Período de tiempo
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : 'todo';
$periodo_sql = '';

switch ($periodo) {
    case 'semana':
        $periodo_sql = "AND fecha_aportacion >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $periodo_label = __('Esta semana', 'flavor-chat-ia');
        break;
    case 'mes':
        $periodo_sql = "AND fecha_aportacion >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $periodo_label = __('Este mes', 'flavor-chat-ia');
        break;
    case 'anio':
        $periodo_sql = "AND fecha_aportacion >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $periodo_label = __('Este año', 'flavor-chat-ia');
        break;
    default:
        $periodo_label = __('Todo el tiempo', 'flavor-chat-ia');
}

// Obtener ranking de usuarios
$ranking = $wpdb->get_results(
    "SELECT
        usuario_id,
        COUNT(*) as total_aportaciones,
        COALESCE(SUM(cantidad_kg), 0) as total_kg,
        COALESCE(SUM(puntos_obtenidos + bonus_nivel), 0) as total_puntos,
        COALESCE(SUM(co2_evitado_kg), 0) as co2_evitado
     FROM $tabla_aportaciones
     WHERE validado = 1 $periodo_sql
     GROUP BY usuario_id
     ORDER BY total_puntos DESC
     LIMIT 50"
);

// Obtener posición del usuario actual
$mi_posicion = null;
$mis_stats = null;
if ($usuario_actual) {
    foreach ($ranking as $index => $usuario) {
        if ((int) $usuario->usuario_id === $usuario_actual) {
            $mi_posicion = $index + 1;
            $mis_stats = $usuario;
            break;
        }
    }

    // Si no está en el top 50, buscar su posición real
    if ($mi_posicion === null) {
        $mis_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_aportaciones,
                COALESCE(SUM(cantidad_kg), 0) as total_kg,
                COALESCE(SUM(puntos_obtenidos + bonus_nivel), 0) as total_puntos,
                COALESCE(SUM(co2_evitado_kg), 0) as co2_evitado
             FROM $tabla_aportaciones
             WHERE usuario_id = %d AND validado = 1 $periodo_sql",
            $usuario_actual
        ));

        if ($mis_stats && $mis_stats->total_puntos > 0) {
            $mi_posicion = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) + 1 FROM (
                    SELECT usuario_id, SUM(puntos_obtenidos + bonus_nivel) as puntos
                    FROM $tabla_aportaciones
                    WHERE validado = 1 $periodo_sql
                    GROUP BY usuario_id
                    HAVING puntos > %d
                ) as ranking",
                $mis_stats->total_puntos
            ));
        }
    }
}

// Niveles de gamificación
$niveles = [
    1 => ['nombre' => __('Semilla', 'flavor-chat-ia'), 'kg_min' => 0, 'icono' => '🌱'],
    2 => ['nombre' => __('Brote', 'flavor-chat-ia'), 'kg_min' => 10, 'icono' => '🌿'],
    3 => ['nombre' => __('Planta', 'flavor-chat-ia'), 'kg_min' => 50, 'icono' => '🌳'],
    4 => ['nombre' => __('Árbol', 'flavor-chat-ia'), 'kg_min' => 150, 'icono' => '🌲'],
    5 => ['nombre' => __('Bosque', 'flavor-chat-ia'), 'kg_min' => 500, 'icono' => '🏔️'],
    6 => ['nombre' => __('Ecosistema', 'flavor-chat-ia'), 'kg_min' => 1000, 'icono' => '🌍'],
];

function obtener_nivel_usuario($total_kg, $niveles) {
    $nivel_actual = 1;
    foreach ($niveles as $nivel => $datos) {
        if ($total_kg >= $datos['kg_min']) {
            $nivel_actual = $nivel;
        }
    }
    return $nivel_actual;
}
?>

<div class="compostaje-ranking-wrapper">
    <div class="ranking-header">
        <h2><?php esc_html_e('Ranking de Compostaje', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Los mejores contribuidores de la comunidad', 'flavor-chat-ia'); ?></p>
    </div>

    <!-- Filtros de período -->
    <div class="ranking-filtros">
        <a href="<?php echo esc_url(add_query_arg('periodo', 'semana')); ?>"
           class="filtro-btn <?php echo $periodo === 'semana' ? 'active' : ''; ?>">
            <?php esc_html_e('Semana', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('periodo', 'mes')); ?>"
           class="filtro-btn <?php echo $periodo === 'mes' ? 'active' : ''; ?>">
            <?php esc_html_e('Mes', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('periodo', 'anio')); ?>"
           class="filtro-btn <?php echo $periodo === 'anio' ? 'active' : ''; ?>">
            <?php esc_html_e('Año', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(remove_query_arg('periodo')); ?>"
           class="filtro-btn <?php echo $periodo === 'todo' ? 'active' : ''; ?>">
            <?php esc_html_e('Todo', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Mi posición (si está logueado) -->
    <?php if ($usuario_actual && $mis_stats && $mis_stats->total_puntos > 0): ?>
        <div class="mi-posicion-card">
            <div class="mi-posicion-rank">
                <span class="rank-label"><?php esc_html_e('Tu posición', 'flavor-chat-ia'); ?></span>
                <span class="rank-numero">#<?php echo esc_html($mi_posicion); ?></span>
            </div>
            <div class="mi-posicion-stats">
                <div class="stat">
                    <span class="stat-value"><?php echo esc_html(number_format($mis_stats->total_puntos)); ?></span>
                    <span class="stat-label"><?php esc_html_e('puntos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat">
                    <span class="stat-value"><?php echo esc_html(number_format($mis_stats->total_kg, 1)); ?> kg</span>
                    <span class="stat-label"><?php esc_html_e('compostado', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat">
                    <?php
                    $mi_nivel = obtener_nivel_usuario($mis_stats->total_kg, $niveles);
                    ?>
                    <span class="stat-value"><?php echo $niveles[$mi_nivel]['icono']; ?></span>
                    <span class="stat-label"><?php echo esc_html($niveles[$mi_nivel]['nombre']); ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Podio (Top 3) -->
    <?php if (count($ranking) >= 3): ?>
        <div class="ranking-podio">
            <?php for ($i = 0; $i < 3; $i++):
                $usuario = $ranking[$i];
                $user_data = get_userdata($usuario->usuario_id);
                $nombre_usuario = $user_data ? $user_data->display_name : __('Usuario', 'flavor-chat-ia');
                $avatar = get_avatar_url($usuario->usuario_id, ['size' => 80]);
                $nivel = obtener_nivel_usuario($usuario->total_kg, $niveles);
                $posicion = $i + 1;
                $clases = ['podio-item', 'posicion-' . $posicion];
                if ((int) $usuario->usuario_id === $usuario_actual) $clases[] = 'es-yo';
            ?>
                <div class="<?php echo esc_attr(implode(' ', $clases)); ?>">
                    <div class="podio-medalla">
                        <?php
                        $medallas = ['🥇', '🥈', '🥉'];
                        echo $medallas[$i];
                        ?>
                    </div>
                    <div class="podio-avatar">
                        <img src="<?php echo esc_url($avatar); ?>" alt="">
                        <span class="podio-nivel"><?php echo $niveles[$nivel]['icono']; ?></span>
                    </div>
                    <div class="podio-nombre"><?php echo esc_html($nombre_usuario); ?></div>
                    <div class="podio-puntos"><?php echo esc_html(number_format($usuario->total_puntos)); ?> pts</div>
                    <div class="podio-kg"><?php echo esc_html(number_format($usuario->total_kg, 1)); ?> kg</div>
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- Lista del resto del ranking -->
    <?php if (count($ranking) > 3): ?>
        <div class="ranking-lista">
            <?php for ($i = 3; $i < count($ranking); $i++):
                $usuario = $ranking[$i];
                $user_data = get_userdata($usuario->usuario_id);
                $nombre_usuario = $user_data ? $user_data->display_name : __('Usuario', 'flavor-chat-ia');
                $avatar = get_avatar_url($usuario->usuario_id, ['size' => 40]);
                $nivel = obtener_nivel_usuario($usuario->total_kg, $niveles);
                $es_yo = (int) $usuario->usuario_id === $usuario_actual;
            ?>
                <div class="ranking-item <?php echo $es_yo ? 'es-yo' : ''; ?>">
                    <span class="ranking-posicion"><?php echo esc_html($i + 1); ?></span>
                    <div class="ranking-avatar">
                        <img src="<?php echo esc_url($avatar); ?>" alt="">
                    </div>
                    <div class="ranking-info">
                        <span class="ranking-nombre">
                            <?php echo esc_html($nombre_usuario); ?>
                            <?php if ($es_yo): ?>
                                <span class="badge-yo"><?php esc_html_e('Tú', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="ranking-nivel">
                            <?php echo $niveles[$nivel]['icono']; ?> <?php echo esc_html($niveles[$nivel]['nombre']); ?>
                        </span>
                    </div>
                    <div class="ranking-stats">
                        <span class="ranking-puntos"><?php echo esc_html(number_format($usuario->total_puntos)); ?> pts</span>
                        <span class="ranking-kg"><?php echo esc_html(number_format($usuario->total_kg, 1)); ?> kg</span>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($ranking)): ?>
        <div class="ranking-empty">
            <span class="dashicons dashicons-awards"></span>
            <h3><?php esc_html_e('Aún no hay participantes', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('¡Sé el primero en aparecer en el ranking!', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(add_query_arg('vista', 'registrar', get_permalink())); ?>" class="btn btn-primary">
                <?php esc_html_e('Registrar aportación', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- Niveles explicación -->
    <div class="ranking-niveles">
        <h3><?php esc_html_e('Niveles de Compostador', 'flavor-chat-ia'); ?></h3>
        <div class="niveles-grid">
            <?php foreach ($niveles as $nivel => $datos): ?>
                <div class="nivel-item">
                    <span class="nivel-icono"><?php echo $datos['icono']; ?></span>
                    <span class="nivel-nombre"><?php echo esc_html($datos['nombre']); ?></span>
                    <span class="nivel-req"><?php echo $datos['kg_min'] > 0 ? esc_html($datos['kg_min']) . '+ kg' : esc_html__('Inicio', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.compostaje-ranking-wrapper { max-width: 800px; margin: 0 auto; }

.ranking-header { text-align: center; margin-bottom: 1.5rem; }
.ranking-header h2 { margin: 0 0 0.5rem; font-size: 1.5rem; color: #1f2937; }
.ranking-header p { margin: 0; color: #6b7280; }

.ranking-filtros { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
.filtro-btn { padding: 0.5rem 1rem; border-radius: 20px; background: #f3f4f6; color: #6b7280; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; }
.filtro-btn:hover { background: #e5e7eb; }
.filtro-btn.active { background: #4f46e5; color: white; }

.mi-posicion-card { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border-radius: 16px; padding: 1.5rem; color: white; margin-bottom: 2rem; display: flex; align-items: center; gap: 2rem; flex-wrap: wrap; }
.mi-posicion-rank { text-align: center; }
.mi-posicion-rank .rank-label { display: block; font-size: 0.8rem; opacity: 0.8; margin-bottom: 0.25rem; }
.mi-posicion-rank .rank-numero { font-size: 2.5rem; font-weight: 700; }
.mi-posicion-stats { display: flex; gap: 2rem; flex: 1; justify-content: center; }
.mi-posicion-stats .stat { text-align: center; }
.mi-posicion-stats .stat-value { display: block; font-size: 1.25rem; font-weight: 600; }
.mi-posicion-stats .stat-label { font-size: 0.8rem; opacity: 0.8; }

.ranking-podio { display: flex; justify-content: center; align-items: flex-end; gap: 1rem; margin-bottom: 2rem; padding: 1rem; }
.podio-item { text-align: center; background: white; border-radius: 12px; padding: 1.5rem 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.2s; }
.podio-item.es-yo { background: #eff6ff; border: 2px solid #4f46e5; }
.podio-item.posicion-1 { order: 2; transform: scale(1.1); }
.podio-item.posicion-2 { order: 1; }
.podio-item.posicion-3 { order: 3; }
.podio-medalla { font-size: 2rem; margin-bottom: 0.5rem; }
.podio-avatar { position: relative; width: 64px; height: 64px; margin: 0 auto 0.5rem; }
.podio-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #e5e7eb; }
.podio-nivel { position: absolute; bottom: -4px; right: -4px; font-size: 1.25rem; }
.podio-nombre { font-weight: 600; color: #1f2937; font-size: 0.9rem; margin-bottom: 0.25rem; }
.podio-puntos { font-weight: 700; color: #4f46e5; font-size: 1.1rem; }
.podio-kg { font-size: 0.8rem; color: #6b7280; }

.ranking-lista { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 2rem; }
.ranking-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f3f4f6; }
.ranking-item:last-child { border-bottom: none; }
.ranking-item.es-yo { background: #eff6ff; }
.ranking-posicion { width: 32px; font-weight: 600; color: #6b7280; text-align: center; }
.ranking-avatar img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
.ranking-info { flex: 1; }
.ranking-nombre { display: block; font-weight: 500; color: #1f2937; }
.badge-yo { display: inline-block; padding: 1px 6px; background: #4f46e5; color: white; border-radius: 4px; font-size: 0.65rem; margin-left: 0.5rem; vertical-align: middle; }
.ranking-nivel { font-size: 0.8rem; color: #6b7280; }
.ranking-stats { text-align: right; }
.ranking-puntos { display: block; font-weight: 600; color: #4f46e5; }
.ranking-kg { font-size: 0.8rem; color: #6b7280; }

.ranking-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.ranking-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
.ranking-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.ranking-empty p { margin: 0 0 1.5rem; color: #6b7280; }

.ranking-niveles { background: #f9fafb; border-radius: 12px; padding: 1.5rem; }
.ranking-niveles h3 { margin: 0 0 1rem; font-size: 1rem; color: #374151; text-align: center; }
.niveles-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; }
.nivel-item { display: flex; flex-direction: column; align-items: center; padding: 1rem; background: white; border-radius: 8px; min-width: 100px; }
.nivel-icono { font-size: 1.5rem; margin-bottom: 0.25rem; }
.nivel-nombre { font-weight: 500; color: #1f2937; font-size: 0.85rem; }
.nivel-req { font-size: 0.75rem; color: #6b7280; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #10b981; color: white; }
.btn-primary:hover { background: #059669; }
</style>

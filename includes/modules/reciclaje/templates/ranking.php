<?php
/**
 * Template: Ranking de Reciclaje
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';
$usuario_actual = get_current_user_id();

// Período
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : 'mes';
$periodo_sql = '';

switch ($periodo) {
    case 'semana':
        $periodo_sql = "AND fecha >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        break;
    case 'mes':
        $periodo_sql = "AND fecha >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case 'anio':
        $periodo_sql = "AND fecha >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    default:
        $periodo_sql = "";
}

// Verificar tabla
if (!Flavor_Platform_Helpers::tabla_existe($tabla_puntos)) {
    $tabla_puntos = $wpdb->prefix . 'flavor_puntos_usuario';
}

// Obtener ranking
$ranking = $wpdb->get_results(
    "SELECT usuario_id, SUM(puntos) as total_puntos
     FROM $tabla_puntos
     WHERE 1=1 $periodo_sql
     GROUP BY usuario_id
     ORDER BY total_puntos DESC
     LIMIT 50"
);

// Mi posición
$mi_posicion = null;
$mis_puntos = 0;
if ($usuario_actual) {
    foreach ($ranking as $i => $usuario) {
        if ((int) $usuario->usuario_id === $usuario_actual) {
            $mi_posicion = $i + 1;
            $mis_puntos = $usuario->total_puntos;
            break;
        }
    }
}

$niveles = [
    ['nombre' => 'Iniciado', 'min' => 0, 'icono' => '♻️'],
    ['nombre' => 'Consciente', 'min' => 100, 'icono' => '🌱'],
    ['nombre' => 'Comprometido', 'min' => 500, 'icono' => '🌿'],
    ['nombre' => 'Experto', 'min' => 1500, 'icono' => '🌳'],
    ['nombre' => 'Héroe Verde', 'min' => 5000, 'icono' => '🌍'],
];

function obtener_nivel_reciclaje($puntos, $niveles) {
    $nivel = $niveles[0];
    foreach ($niveles as $n) {
        if ($puntos >= $n['min']) {
            $nivel = $n;
        }
    }
    return $nivel;
}
?>

<div class="reciclaje-ranking-wrapper">
    <div class="ranking-header">
        <h2><?php esc_html_e('Ranking Ecológico', 'flavor-platform'); ?></h2>
        <p><?php esc_html_e('Los mejores recicladores de la comunidad', 'flavor-platform'); ?></p>
    </div>

    <!-- Filtros -->
    <div class="ranking-filtros">
        <a href="<?php echo esc_url(add_query_arg('periodo', 'semana')); ?>"
           class="filtro-btn <?php echo $periodo === 'semana' ? 'active' : ''; ?>">
            <?php esc_html_e('Semana', 'flavor-platform'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('periodo', 'mes')); ?>"
           class="filtro-btn <?php echo $periodo === 'mes' ? 'active' : ''; ?>">
            <?php esc_html_e('Mes', 'flavor-platform'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('periodo', 'anio')); ?>"
           class="filtro-btn <?php echo $periodo === 'anio' ? 'active' : ''; ?>">
            <?php esc_html_e('Año', 'flavor-platform'); ?>
        </a>
        <a href="<?php echo esc_url(remove_query_arg('periodo')); ?>"
           class="filtro-btn <?php echo empty($periodo) || $periodo === 'todo' ? 'active' : ''; ?>">
            <?php esc_html_e('Todo', 'flavor-platform'); ?>
        </a>
    </div>

    <!-- Mi posición -->
    <?php if ($usuario_actual && $mi_posicion): ?>
        <div class="mi-posicion-card">
            <span class="mi-rank">#<?php echo esc_html($mi_posicion); ?></span>
            <span class="mi-puntos"><?php echo esc_html(number_format($mis_puntos)); ?> pts</span>
        </div>
    <?php endif; ?>

    <!-- Podio -->
    <?php if (count($ranking) >= 3): ?>
        <div class="ranking-podio">
            <?php for ($i = 0; $i < 3; $i++):
                $u = $ranking[$i];
                $user_data = get_userdata($u->usuario_id);
                $nombre = $user_data ? $user_data->display_name : __('Usuario', 'flavor-platform');
                $avatar = get_avatar_url($u->usuario_id, ['size' => 64]);
                $nivel = obtener_nivel_reciclaje($u->total_puntos, $niveles);
            ?>
                <div class="podio-item posicion-<?php echo $i + 1; ?>">
                    <span class="podio-medalla"><?php echo ['🥇', '🥈', '🥉'][$i]; ?></span>
                    <img src="<?php echo esc_url($avatar); ?>" class="podio-avatar" alt="">
                    <span class="podio-nivel"><?php echo $nivel['icono']; ?></span>
                    <span class="podio-nombre"><?php echo esc_html($nombre); ?></span>
                    <span class="podio-puntos"><?php echo esc_html(number_format($u->total_puntos)); ?> pts</span>
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- Lista -->
    <?php if (count($ranking) > 3): ?>
        <div class="ranking-lista">
            <?php for ($i = 3; $i < count($ranking); $i++):
                $u = $ranking[$i];
                $user_data = get_userdata($u->usuario_id);
                $nombre = $user_data ? $user_data->display_name : __('Usuario', 'flavor-platform');
                $avatar = get_avatar_url($u->usuario_id, ['size' => 40]);
                $nivel = obtener_nivel_reciclaje($u->total_puntos, $niveles);
                $es_yo = (int) $u->usuario_id === $usuario_actual;
            ?>
                <div class="ranking-item <?php echo $es_yo ? 'es-yo' : ''; ?>">
                    <span class="ranking-pos"><?php echo $i + 1; ?></span>
                    <img src="<?php echo esc_url($avatar); ?>" class="ranking-avatar" alt="">
                    <div class="ranking-info">
                        <span class="ranking-nombre"><?php echo esc_html($nombre); ?></span>
                        <span class="ranking-nivel"><?php echo $nivel['icono'] . ' ' . esc_html($nivel['nombre']); ?></span>
                    </div>
                    <span class="ranking-puntos"><?php echo esc_html(number_format($u->total_puntos)); ?> pts</span>
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($ranking)): ?>
        <div class="ranking-empty">
            <span>♻️</span>
            <h3><?php esc_html_e('Sin participantes aún', 'flavor-platform'); ?></h3>
            <p><?php esc_html_e('¡Sé el primero en aparecer en el ranking!', 'flavor-platform'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.reciclaje-ranking-wrapper { max-width: 700px; margin: 0 auto; }
.ranking-header { text-align: center; margin-bottom: 1.5rem; }
.ranking-header h2 { margin: 0 0 0.5rem; color: #1f2937; }
.ranking-header p { margin: 0; color: #6b7280; }
.ranking-filtros { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem; }
.filtro-btn { padding: 0.5rem 1rem; border-radius: 20px; background: #f3f4f6; color: #6b7280; text-decoration: none; font-size: 0.85rem; transition: all 0.2s; }
.filtro-btn.active { background: #10b981; color: white; }
.mi-posicion-card { display: flex; justify-content: center; gap: 1rem; align-items: center; background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 12px; padding: 1rem 2rem; margin-bottom: 1.5rem; }
.mi-rank { font-size: 1.5rem; font-weight: 700; }
.mi-puntos { font-size: 1.25rem; }
.ranking-podio { display: flex; justify-content: center; align-items: flex-end; gap: 1rem; margin-bottom: 2rem; }
.podio-item { text-align: center; background: white; padding: 1.5rem 1rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.podio-item.posicion-1 { order: 2; transform: scale(1.1); }
.podio-item.posicion-2 { order: 1; }
.podio-item.posicion-3 { order: 3; }
.podio-medalla { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
.podio-avatar { width: 56px; height: 56px; border-radius: 50%; margin-bottom: 0.5rem; }
.podio-nivel { font-size: 1.25rem; }
.podio-nombre { display: block; font-weight: 500; color: #1f2937; font-size: 0.9rem; margin: 0.25rem 0; }
.podio-puntos { color: #10b981; font-weight: 600; }
.ranking-lista { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.ranking-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; }
.ranking-item:last-child { border-bottom: none; }
.ranking-item.es-yo { background: #ecfdf5; }
.ranking-pos { width: 28px; font-weight: 600; color: #6b7280; text-align: center; }
.ranking-avatar { width: 36px; height: 36px; border-radius: 50%; }
.ranking-info { flex: 1; }
.ranking-nombre { display: block; font-weight: 500; color: #1f2937; font-size: 0.9rem; }
.ranking-nivel { font-size: 0.8rem; color: #6b7280; }
.ranking-puntos { font-weight: 600; color: #10b981; }
.ranking-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.ranking-empty span { font-size: 3rem; display: block; margin-bottom: 1rem; }
.ranking-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.ranking-empty p { margin: 0; color: #6b7280; }
</style>

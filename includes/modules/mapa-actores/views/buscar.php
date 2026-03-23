<?php
/**
 * Vista de busqueda rapida de actores.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$query = sanitize_text_field($_GET['q'] ?? '');
$tipo = sanitize_text_field($_GET['tipo'] ?? '');
$limite = isset($_GET['limite']) ? max(1, min(100, (int) $_GET['limite'])) : 20;

global $wpdb;
$tabla = $wpdb->prefix . 'flavor_mapa_actores';

$resultados = [];
if ($query !== '' || $tipo !== '') {
    $where = ['activo = 1'];
    $params = [];

    if ($query !== '') {
        $where[] = '(nombre LIKE %s OR descripcion LIKE %s OR competencias LIKE %s)';
        $like = '%' . $wpdb->esc_like($query) . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($tipo !== '') {
        $where[] = 'tipo = %s';
        $params[] = $tipo;
    }

    $sql = "SELECT id, nombre, tipo, ambito, posicion_general, municipio
            FROM {$tabla}
            WHERE " . implode(' AND ', $where) . "
            ORDER BY nombre ASC
            LIMIT %d";
    $params[] = $limite;

    $resultados = $wpdb->get_results($wpdb->prepare($sql, $params));
}
?>

<section class="flavor-actores-buscar">
    <h2><?php esc_html_e('Buscar actores', 'flavor-chat-ia'); ?></h2>

    <form method="get" aria-label="<?php echo esc_attr__('Formulario de busqueda de actores', 'flavor-chat-ia'); ?>" style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:flex-end;">
        <p>
            <label for="actores_q"><?php esc_html_e('Texto', 'flavor-chat-ia'); ?></label><br>
            <input id="actores_q" type="text" name="q" value="<?php echo esc_attr($query); ?>">
        </p>
        <p>
            <label for="actores_tipo"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></label><br>
            <input id="actores_tipo" type="text" name="tipo" value="<?php echo esc_attr($tipo); ?>">
        </p>
        <p>
            <label for="actores_limite"><?php esc_html_e('Limite', 'flavor-chat-ia'); ?></label><br>
            <input id="actores_limite" type="number" min="1" max="100" name="limite" value="<?php echo esc_attr($limite); ?>">
        </p>
        <p><button type="submit" class="button"><?php esc_html_e('Buscar', 'flavor-chat-ia'); ?></button></p>
    </form>

    <?php if ($query !== '' || $tipo !== ''): ?>
        <h3><?php esc_html_e('Resultados', 'flavor-chat-ia'); ?></h3>
        <?php if (empty($resultados)): ?>
            <p><?php esc_html_e('Sin coincidencias.', 'flavor-chat-ia'); ?></p>
        <?php else: ?>
            <ul>
                <?php foreach ($resultados as $actor): ?>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('actor_id', (int) $actor->id)); ?>"><?php echo esc_html($actor->nombre); ?></a>
                        - <?php echo esc_html($actor->tipo . ' / ' . $actor->ambito . ($actor->municipio ? ' / ' . $actor->municipio : '')); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php
/**
 * Vista completa de listado de denuncias.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

$estado = sanitize_text_field($atts['estado'] ?? $_GET['estado'] ?? '');
$tipo = sanitize_text_field($atts['tipo'] ?? $_GET['tipo'] ?? '');
$busqueda = sanitize_text_field($_GET['busqueda'] ?? '');

$where = ["visibilidad IN ('publica','miembros')"];
$params = [];

if ($estado !== '') {
    $where[] = 'estado = %s';
    $params[] = $estado;
}
if ($tipo !== '') {
    $where[] = 'tipo = %s';
    $params[] = $tipo;
}
if ($busqueda !== '') {
    $where[] = '(titulo LIKE %s OR descripcion LIKE %s OR organismo_destino LIKE %s)';
    $like = '%' . $wpdb->esc_like($busqueda) . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = "SELECT id, titulo, tipo, estado, prioridad, organismo_destino, fecha_presentacion, fecha_limite_respuesta
        FROM {$tabla}
        WHERE " . implode(' AND ', $where) . "
        ORDER BY created_at DESC
        LIMIT 200";

$denuncias = empty($params) ? $wpdb->get_results($sql) : $wpdb->get_results($wpdb->prepare($sql, $params));
?>

<section class="flavor-denuncias-listado">
    <h2><?php esc_html_e('Listado de denuncias', 'flavor-chat-ia'); ?></h2>

    <form method="get" aria-label="<?php echo esc_attr__('Formulario de filtros de denuncias', 'flavor-chat-ia'); ?>" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.5rem;align-items:end;">
        <p>
            <label for="den_busqueda"><?php esc_html_e('Buscar', 'flavor-chat-ia'); ?></label><br>
            <input id="den_busqueda" type="text" name="busqueda" value="<?php echo esc_attr($busqueda); ?>" style="width:100%;">
        </p>
        <p>
            <label for="den_tipo"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></label><br>
            <input id="den_tipo" type="text" name="tipo" value="<?php echo esc_attr($tipo); ?>" style="width:100%;">
        </p>
        <p>
            <label for="den_estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label><br>
            <input id="den_estado" type="text" name="estado" value="<?php echo esc_attr($estado); ?>" style="width:100%;">
        </p>
        <p><button type="submit" class="button"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button></p>
    </form>

    <?php if (empty($denuncias)): ?>
        <p><?php esc_html_e('No hay denuncias para los filtros actuales.', 'flavor-chat-ia'); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Titulo', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Prioridad', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Organismo', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Presentacion', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Limite', 'flavor-chat-ia'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($denuncias as $denuncia): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(add_query_arg('denuncia_id', (int) $denuncia->id)); ?>"><?php echo esc_html($denuncia->titulo); ?></a></td>
                        <td><?php echo esc_html($denuncia->tipo); ?></td>
                        <td><?php echo esc_html($denuncia->estado); ?></td>
                        <td><?php echo esc_html($denuncia->prioridad); ?></td>
                        <td><?php echo esc_html($denuncia->organismo_destino); ?></td>
                        <td><?php echo esc_html(mysql2date(get_option('date_format'), $denuncia->fecha_presentacion)); ?></td>
                        <td><?php echo esc_html($denuncia->fecha_limite_respuesta ? mysql2date(get_option('date_format'), $denuncia->fecha_limite_respuesta) : '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

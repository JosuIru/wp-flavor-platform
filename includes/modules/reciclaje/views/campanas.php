<?php
/**
 * Vista Campañas - Reciclaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_campanas = $wpdb->prefix . 'flavor_reciclaje_campanas';
$tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
$page_slug = sanitize_key($_GET['page'] ?? 'reciclaje-campanas');
$action = sanitize_key($_GET['action'] ?? '');
$campana_id = absint($_GET['id'] ?? 0);

$estados_validos = ['borrador', 'programada', 'activa', 'finalizada', 'cancelada'];
$ambitos_validos = ['comunidad', 'barrio', 'municipio', 'escolar', 'empresa'];

if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['reciclaje_guardar_campana'])) {
    if (!isset($_POST['reciclaje_campana_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['reciclaje_campana_nonce'])), 'reciclaje_guardar_campana')) {
        add_settings_error('reciclaje_campanas', 'nonce_error', __('No se pudo validar la solicitud.', 'flavor-platform'), 'error');
    } else {
        $edit_id = absint($_POST['campana_id'] ?? 0);
        $titulo = sanitize_text_field(wp_unslash($_POST['titulo'] ?? ''));
        $descripcion = sanitize_textarea_field(wp_unslash($_POST['descripcion'] ?? ''));
        $fecha_inicio_raw = sanitize_text_field(wp_unslash($_POST['fecha_inicio'] ?? ''));
        $fecha_fin_raw = sanitize_text_field(wp_unslash($_POST['fecha_fin'] ?? ''));
        $objetivo_kg = max(0, (float) ($_POST['objetivo_kg'] ?? 0));
        $objetivo_participantes = max(0, absint($_POST['objetivo_participantes'] ?? 0));
        $puntos_bonus = max(0, absint($_POST['puntos_bonus'] ?? 0));
        $estado = sanitize_key($_POST['estado'] ?? 'borrador');
        $ambito = sanitize_key($_POST['ambito'] ?? 'comunidad');
        $ubicacion = sanitize_text_field(wp_unslash($_POST['ubicacion'] ?? ''));
        $materiales_csv = sanitize_text_field(wp_unslash($_POST['materiales'] ?? ''));

        if (!in_array($estado, $estados_validos, true)) {
            $estado = 'borrador';
        }
        if (!in_array($ambito, $ambitos_validos, true)) {
            $ambito = 'comunidad';
        }

        $fecha_inicio = $fecha_inicio_raw ? gmdate('Y-m-d H:i:s', strtotime($fecha_inicio_raw)) : null;
        $fecha_fin = $fecha_fin_raw ? gmdate('Y-m-d H:i:s', strtotime($fecha_fin_raw)) : null;

        $materiales = array_values(array_filter(array_map(static function ($item) {
            return sanitize_key(trim((string) $item));
        }, explode(',', $materiales_csv))));

        if ('' === $titulo) {
            add_settings_error('reciclaje_campanas', 'titulo_error', __('El título es obligatorio.', 'flavor-platform'), 'error');
        } elseif ($fecha_inicio && $fecha_fin && strtotime($fecha_fin) < strtotime($fecha_inicio)) {
            add_settings_error('reciclaje_campanas', 'fecha_error', __('La fecha de fin debe ser posterior a la fecha de inicio.', 'flavor-platform'), 'error');
        } else {
            $data = [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'objetivo_kg' => $objetivo_kg,
                'objetivo_participantes' => $objetivo_participantes,
                'puntos_bonus' => $puntos_bonus,
                'estado' => $estado,
                'ambito' => $ambito,
                'materiales' => !empty($materiales) ? wp_json_encode($materiales) : null,
                'ubicacion' => $ubicacion,
            ];

            if ($edit_id > 0) {
                $updated = $wpdb->update(
                    $tabla_campanas,
                    $data,
                    ['id' => $edit_id],
                    ['%s', '%s', '%s', '%s', '%f', '%d', '%d', '%s', '%s', '%s', '%s'],
                    ['%d']
                );

                if (false !== $updated) {
                    add_settings_error('reciclaje_campanas', 'updated', __('Campaña actualizada correctamente.', 'flavor-platform'), 'updated');
                    $action = '';
                } else {
                    add_settings_error('reciclaje_campanas', 'update_error', __('No se pudo actualizar la campaña.', 'flavor-platform'), 'error');
                }
            } else {
                $data['created_by'] = get_current_user_id();
                $inserted = $wpdb->insert(
                    $tabla_campanas,
                    $data,
                    ['%s', '%s', '%s', '%s', '%f', '%d', '%d', '%s', '%s', '%s', '%s', '%d']
                );

                if ($inserted) {
                    add_settings_error('reciclaje_campanas', 'created', __('Campaña creada correctamente.', 'flavor-platform'), 'updated');
                    $action = '';
                } else {
                    add_settings_error('reciclaje_campanas', 'insert_error', __('No se pudo crear la campaña.', 'flavor-platform'), 'error');
                }
            }
        }
    }
}

if ('delete' === $action && $campana_id > 0 && isset($_GET['_wpnonce'])) {
    $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
    if (wp_verify_nonce($nonce, 'reciclaje_delete_campana_' . $campana_id)) {
        $deleted = $wpdb->delete($tabla_campanas, ['id' => $campana_id], ['%d']);
        if ($deleted) {
            add_settings_error('reciclaje_campanas', 'deleted', __('Campaña eliminada.', 'flavor-platform'), 'updated');
        } else {
            add_settings_error('reciclaje_campanas', 'delete_error', __('No se pudo eliminar la campaña.', 'flavor-platform'), 'error');
        }
    } else {
        add_settings_error('reciclaje_campanas', 'delete_nonce_error', __('No se pudo validar la eliminación.', 'flavor-platform'), 'error');
    }
    $action = '';
}

if ('cambiar_estado' === $action && $campana_id > 0 && isset($_GET['estado'], $_GET['_wpnonce'])) {
    $nuevo_estado = sanitize_key(wp_unslash($_GET['estado']));
    $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

    if (in_array($nuevo_estado, $estados_validos, true) && wp_verify_nonce($nonce, 'reciclaje_estado_campana_' . $campana_id)) {
        $updated = $wpdb->update(
            $tabla_campanas,
            ['estado' => $nuevo_estado],
            ['id' => $campana_id],
            ['%s'],
            ['%d']
        );

        if (false !== $updated) {
            add_settings_error('reciclaje_campanas', 'estado_updated', __('Estado actualizado.', 'flavor-platform'), 'updated');
        } else {
            add_settings_error('reciclaje_campanas', 'estado_error', __('No se pudo actualizar el estado.', 'flavor-platform'), 'error');
        }
    }
    $action = '';
}

$campana_edicion = null;
if ('editar' === $action && $campana_id > 0) {
    $campana_edicion = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_campanas} WHERE id = %d", $campana_id));
    if (!$campana_edicion) {
        add_settings_error('reciclaje_campanas', 'not_found', __('La campaña no existe.', 'flavor-platform'), 'error');
        $action = '';
    }
}

$filtro_estado = sanitize_key($_GET['estado_filtro'] ?? 'todos');
$buscar = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
$paged = max(1, absint($_GET['paged'] ?? 1));
$per_page = 12;
$offset = ($paged - 1) * $per_page;

$where_parts = ['1=1'];
$params = [];

if ($filtro_estado !== 'todos' && in_array($filtro_estado, $estados_validos, true)) {
    $where_parts[] = 'estado = %s';
    $params[] = $filtro_estado;
}
if ('' !== $buscar) {
    $where_parts[] = '(titulo LIKE %s OR descripcion LIKE %s OR ubicacion LIKE %s)';
    $like = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = implode(' AND ', $where_parts);

$sql_total = "SELECT COUNT(*) FROM {$tabla_campanas} WHERE {$where_sql}";
$total_registros = (int) (!empty($params) ? $wpdb->get_var($wpdb->prepare($sql_total, ...$params)) : $wpdb->get_var($sql_total));
$total_paginas = max(1, (int) ceil($total_registros / $per_page));

$sql_campanas = "SELECT * FROM {$tabla_campanas} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
$params_list = $params;
$params_list[] = $per_page;
$params_list[] = $offset;
$campanas = $wpdb->get_results($wpdb->prepare($sql_campanas, ...$params_list));

$total_campanas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanas}");
$total_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanas} WHERE estado = 'activa'");
$total_finalizadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanas} WHERE estado = 'finalizada'");
$objetivo_total_kg = (float) $wpdb->get_var("SELECT COALESCE(SUM(objetivo_kg), 0) FROM {$tabla_campanas}");

$kg_mes = 0.0;
if (Flavor_Chat_Helpers::tabla_existe($tabla_depositos)) {
    $inicio_mes = gmdate('Y-m-01 00:00:00');
    $kg_mes = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(cantidad_kg), 0) FROM {$tabla_depositos} WHERE verificado = 1 AND fecha_deposito >= %s",
        $inicio_mes
    ));
}

?>
<div class="wrap flavor-modulo-page">
    <?php
    $this->render_page_header(__('Campañas de Reciclaje', 'flavor-platform'), [
        ['label' => __('Nueva Campaña', 'flavor-platform'), 'url' => admin_url('admin.php?page=' . $page_slug . '&action=nueva'), 'class' => 'button-primary'],
    ]);

    settings_errors('reciclaje_campanas');
    ?>
    <p><?php esc_html_e('Gestiona campañas de concienciación, retos comunitarios y acciones colectivas de reciclaje.', 'flavor-platform'); ?></p>

    <div class="flavor-stats-grid" style="margin-bottom:16px;">
        <div class="flavor-stat-card"><span class="stat-number"><?php echo esc_html(number_format_i18n($total_campanas)); ?></span><span class="stat-label"><?php esc_html_e('Campañas', 'flavor-platform'); ?></span></div>
        <div class="flavor-stat-card"><span class="stat-number"><?php echo esc_html(number_format_i18n($total_activas)); ?></span><span class="stat-label"><?php esc_html_e('Activas', 'flavor-platform'); ?></span></div>
        <div class="flavor-stat-card"><span class="stat-number"><?php echo esc_html(number_format_i18n($total_finalizadas)); ?></span><span class="stat-label"><?php esc_html_e('Finalizadas', 'flavor-platform'); ?></span></div>
        <div class="flavor-stat-card"><span class="stat-number"><?php echo esc_html(number_format_i18n($objetivo_total_kg, 1)); ?> kg</span><span class="stat-label"><?php esc_html_e('Objetivo acumulado', 'flavor-platform'); ?></span></div>
        <div class="flavor-stat-card"><span class="stat-number"><?php echo esc_html(number_format_i18n($kg_mes, 1)); ?> kg</span><span class="stat-label"><?php esc_html_e('Kg verificados este mes', 'flavor-platform'); ?></span></div>
    </div>

    <?php if ('nueva' === $action || ('editar' === $action && $campana_edicion)) : ?>
        <?php
        $form_id = $campana_edicion ? (int) $campana_edicion->id : 0;
        $form_titulo = $campana_edicion ? (string) $campana_edicion->titulo : '';
        $form_descripcion = $campana_edicion ? (string) $campana_edicion->descripcion : '';
        $form_inicio = $campana_edicion && $campana_edicion->fecha_inicio ? gmdate('Y-m-d\\TH:i', strtotime((string) $campana_edicion->fecha_inicio)) : '';
        $form_fin = $campana_edicion && $campana_edicion->fecha_fin ? gmdate('Y-m-d\\TH:i', strtotime((string) $campana_edicion->fecha_fin)) : '';
        $form_objetivo_kg = $campana_edicion ? (float) $campana_edicion->objetivo_kg : 0;
        $form_obj_part = $campana_edicion ? (int) $campana_edicion->objetivo_participantes : 0;
        $form_puntos_bonus = $campana_edicion ? (int) $campana_edicion->puntos_bonus : 0;
        $form_estado = $campana_edicion ? (string) $campana_edicion->estado : 'borrador';
        $form_ambito = $campana_edicion ? (string) $campana_edicion->ambito : 'comunidad';
        $form_materiales = $campana_edicion && $campana_edicion->materiales ? implode(', ', (array) json_decode((string) $campana_edicion->materiales, true)) : '';
        $form_ubicacion = $campana_edicion ? (string) $campana_edicion->ubicacion : '';
        ?>
        <div class="postbox" style="padding:16px; margin-bottom:16px;">
            <h2><?php echo $campana_edicion ? esc_html__('Editar campaña', 'flavor-platform') : esc_html__('Nueva campaña', 'flavor-platform'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . $page_slug)); ?>">
                <?php wp_nonce_field('reciclaje_guardar_campana', 'reciclaje_campana_nonce'); ?>
                <input type="hidden" name="campana_id" value="<?php echo esc_attr($form_id); ?>" />

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="campana_titulo"><?php esc_html_e('Título', 'flavor-platform'); ?></label></th>
                        <td><input id="campana_titulo" type="text" class="regular-text" name="titulo" required value="<?php echo esc_attr($form_titulo); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_descripcion"><?php esc_html_e('Descripción', 'flavor-platform'); ?></label></th>
                        <td><textarea id="campana_descripcion" name="descripcion" rows="4" class="large-text"><?php echo esc_textarea($form_descripcion); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_fecha_inicio"><?php esc_html_e('Inicio', 'flavor-platform'); ?></label></th>
                        <td><input id="campana_fecha_inicio" type="datetime-local" name="fecha_inicio" value="<?php echo esc_attr($form_inicio); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_fecha_fin"><?php esc_html_e('Fin', 'flavor-platform'); ?></label></th>
                        <td><input id="campana_fecha_fin" type="datetime-local" name="fecha_fin" value="<?php echo esc_attr($form_fin); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_objetivo_kg"><?php esc_html_e('Objetivo (kg)', 'flavor-platform'); ?></label></th>
                        <td><input id="campana_objetivo_kg" type="number" name="objetivo_kg" min="0" step="0.1" value="<?php echo esc_attr($form_objetivo_kg); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_objetivo_participantes"><?php esc_html_e('Objetivo participantes', 'flavor-platform'); ?></label></th>
                        <td><input id="campana_objetivo_participantes" type="number" name="objetivo_participantes" min="0" step="1" value="<?php echo esc_attr($form_obj_part); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_puntos_bonus"><?php esc_html_e('Puntos bonus', 'flavor-platform'); ?></label></th>
                        <td><input id="campana_puntos_bonus" type="number" name="puntos_bonus" min="0" step="1" value="<?php echo esc_attr($form_puntos_bonus); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_estado"><?php esc_html_e('Estado', 'flavor-platform'); ?></label></th>
                        <td>
                            <select id="campana_estado" name="estado">
                                <?php foreach ($estados_validos as $estado_item) : ?>
                                    <option value="<?php echo esc_attr($estado_item); ?>" <?php selected($form_estado, $estado_item); ?>><?php echo esc_html(ucfirst($estado_item)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_ambito"><?php esc_html_e('Ámbito', 'flavor-platform'); ?></label></th>
                        <td>
                            <select id="campana_ambito" name="ambito">
                                <?php foreach ($ambitos_validos as $ambito_item) : ?>
                                    <option value="<?php echo esc_attr($ambito_item); ?>" <?php selected($form_ambito, $ambito_item); ?>><?php echo esc_html(ucfirst($ambito_item)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_materiales"><?php esc_html_e('Materiales (CSV)', 'flavor-platform'); ?></label></th>
                        <td>
                            <input id="campana_materiales" type="text" class="regular-text" name="materiales" value="<?php echo esc_attr($form_materiales); ?>" />
                            <p class="description"><?php esc_html_e('Ejemplo: papel, plastico, vidrio, organico', 'flavor-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="campana_ubicacion"><?php esc_html_e('Ubicación', 'flavor-platform'); ?></label></th>
                        <td><input id="campana_ubicacion" type="text" class="regular-text" name="ubicacion" value="<?php echo esc_attr($form_ubicacion); ?>" /></td>
                    </tr>
                </table>

                <p class="submit" style="display:flex; gap:8px;">
                    <button type="submit" name="reciclaje_guardar_campana" class="button button-primary"><?php esc_html_e('Guardar campaña', 'flavor-platform'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $page_slug)); ?>" class="button"><?php esc_html_e('Cancelar', 'flavor-platform'); ?></a>
                </p>
            </form>
        </div>
    <?php endif; ?>

    <form method="get" class="postbox" style="padding:12px; margin-bottom:16px; display:flex; gap:8px; align-items:flex-end;">
        <input type="hidden" name="page" value="<?php echo esc_attr($page_slug); ?>" />
        <div>
            <label for="campanas_estado_filtro"><strong><?php esc_html_e('Estado', 'flavor-platform'); ?></strong></label><br>
            <select id="campanas_estado_filtro" name="estado_filtro">
                <option value="todos" <?php selected($filtro_estado, 'todos'); ?>><?php esc_html_e('Todos', 'flavor-platform'); ?></option>
                <?php foreach ($estados_validos as $estado_item) : ?>
                    <option value="<?php echo esc_attr($estado_item); ?>" <?php selected($filtro_estado, $estado_item); ?>><?php echo esc_html(ucfirst($estado_item)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="campanas_search"><strong><?php esc_html_e('Buscar', 'flavor-platform'); ?></strong></label><br>
            <input id="campanas_search" type="search" name="s" value="<?php echo esc_attr($buscar); ?>" placeholder="<?php esc_attr_e('Título, descripción, ubicación', 'flavor-platform'); ?>" />
        </div>
        <div>
            <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', 'flavor-platform'); ?></button>
        </div>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Campaña', 'flavor-platform'); ?></th>
                <th><?php esc_html_e('Periodo', 'flavor-platform'); ?></th>
                <th><?php esc_html_e('Objetivo', 'flavor-platform'); ?></th>
                <th><?php esc_html_e('Progreso', 'flavor-platform'); ?></th>
                <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                <th><?php esc_html_e('Acciones', 'flavor-platform'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($campanas)) : ?>
                <?php foreach ($campanas as $campana) : ?>
                    <?php
                    $kg_real = 0.0;
                    $participantes = 0;
                    if (Flavor_Chat_Helpers::tabla_existe($tabla_depositos)) {
                        $fecha_inicio_sql = $campana->fecha_inicio ? (string) $campana->fecha_inicio : null;
                        $fecha_fin_sql = $campana->fecha_fin ? (string) $campana->fecha_fin : null;

                        if ($fecha_inicio_sql && $fecha_fin_sql) {
                            $kg_real = (float) $wpdb->get_var($wpdb->prepare(
                                "SELECT COALESCE(SUM(cantidad_kg), 0) FROM {$tabla_depositos} WHERE verificado = 1 AND fecha_deposito BETWEEN %s AND %s",
                                $fecha_inicio_sql,
                                $fecha_fin_sql
                            ));
                            $participantes = (int) $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_depositos} WHERE verificado = 1 AND fecha_deposito BETWEEN %s AND %s",
                                $fecha_inicio_sql,
                                $fecha_fin_sql
                            ));
                        } elseif ($fecha_inicio_sql) {
                            $kg_real = (float) $wpdb->get_var($wpdb->prepare(
                                "SELECT COALESCE(SUM(cantidad_kg), 0) FROM {$tabla_depositos} WHERE verificado = 1 AND fecha_deposito >= %s",
                                $fecha_inicio_sql
                            ));
                            $participantes = (int) $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_depositos} WHERE verificado = 1 AND fecha_deposito >= %s",
                                $fecha_inicio_sql
                            ));
                        }
                    }

                    $objetivo_kg = (float) $campana->objetivo_kg;
                    $progress_pct = $objetivo_kg > 0 ? min(100, ($kg_real / $objetivo_kg) * 100) : 0;

                    $edit_url = admin_url('admin.php?page=' . $page_slug . '&action=editar&id=' . (int) $campana->id);
                    $delete_url = wp_nonce_url(
                        admin_url('admin.php?page=' . $page_slug . '&action=delete&id=' . (int) $campana->id),
                        'reciclaje_delete_campana_' . (int) $campana->id
                    );
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($campana->titulo); ?></strong>
                            <?php if (!empty($campana->ubicacion)) : ?>
                                <div class="description"><?php echo esc_html($campana->ubicacion); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($campana->descripcion)) : ?>
                                <div class="description"><?php echo esc_html(wp_trim_words((string) $campana->descripcion, 16)); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?php echo $campana->fecha_inicio ? esc_html(date_i18n(get_option('date_format'), strtotime((string) $campana->fecha_inicio))) : '—'; ?></div>
                            <div class="description"><?php echo $campana->fecha_fin ? esc_html(date_i18n(get_option('date_format'), strtotime((string) $campana->fecha_fin))) : '—'; ?></div>
                        </td>
                        <td>
                            <div><?php echo esc_html(number_format_i18n((float) $campana->objetivo_kg, 1)); ?> kg</div>
                            <div class="description"><?php echo esc_html(number_format_i18n((int) $campana->objetivo_participantes)); ?> <?php esc_html_e('participantes', 'flavor-platform'); ?></div>
                        </td>
                        <td>
                            <div><?php echo esc_html(number_format_i18n($kg_real, 1)); ?> kg / <?php echo esc_html(number_format_i18n((float) $campana->objetivo_kg, 1)); ?> kg</div>
                            <div style="height:8px; background:#e5e7eb; border-radius:999px; margin-top:6px; overflow:hidden; max-width:180px;">
                                <div style="height:8px; width:<?php echo esc_attr(number_format($progress_pct, 2, '.', '')); ?>%; background:#10b981;"></div>
                            </div>
                            <div class="description"><?php echo esc_html(number_format_i18n($participantes)); ?> <?php esc_html_e('participantes', 'flavor-platform'); ?></div>
                        </td>
                        <td>
                            <strong><?php echo esc_html(ucfirst((string) $campana->estado)); ?></strong>
                            <div class="description"><?php echo esc_html(ucfirst((string) $campana->ambito)); ?></div>
                        </td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Editar', 'flavor-platform'); ?></a>
                            <a class="button button-small" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(__('¿Eliminar campaña?', 'flavor-platform')); ?>');"><?php esc_html_e('Eliminar', 'flavor-platform'); ?></a>
                            <div style="margin-top:6px;">
                                <?php foreach ($estados_validos as $estado_item) : ?>
                                    <?php if ($estado_item === $campana->estado) { continue; } ?>
                                    <?php
                                    $estado_url = wp_nonce_url(
                                        admin_url('admin.php?page=' . $page_slug . '&action=cambiar_estado&id=' . (int) $campana->id . '&estado=' . $estado_item),
                                        'reciclaje_estado_campana_' . (int) $campana->id
                                    );
                                    ?>
                                    <a href="<?php echo esc_url($estado_url); ?>" style="margin-right:6px;"><?php echo esc_html(ucfirst($estado_item)); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No hay campañas con los filtros seleccionados.', 'flavor-platform'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    echo '<div class="tablenav"><div class="tablenav-pages">';
    echo wp_kses_post(paginate_links([
        'base' => add_query_arg('paged', '%#%', remove_query_arg('paged', admin_url('admin.php?page=' . $page_slug))),
        'format' => '',
        'current' => $paged,
        'total' => $total_paginas,
        'add_args' => [
            'page' => $page_slug,
            'estado_filtro' => $filtro_estado,
            's' => $buscar,
        ],
    ]));
    echo '</div></div>';
    ?>
</div>

<?php
/**
 * Template: Lista de Grupos de Consumo
 *
 * Muestra todos los grupos de consumo disponibles.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Templates
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$user_id = get_current_user_id();
$tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

// Obtener grupos del usuario si esta logueado
$grupos_usuario = [];
if ($user_id) {
    $grupos_usuario = $wpdb->get_col($wpdb->prepare(
        "SELECT grupo_id FROM {$tabla_consumidores} WHERE usuario_id = %d AND estado IN ('activo', 'pendiente')",
        $user_id
    ));
}

// Obtener todos los grupos de consumo
$args_grupos = [
    'post_type'      => 'gc_grupo',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
];
$query_grupos = new WP_Query($args_grupos);
$grupos = $query_grupos->posts;

// Obtener estadisticas de cada grupo
$estadisticas_grupos = [];
foreach ($grupos as $grupo) {
    $estadisticas_grupos[$grupo->ID] = [
        'miembros' => (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_consumidores} WHERE grupo_id = %d AND estado = 'activo'",
            $grupo->ID
        )),
        'estado_usuario' => $user_id ? $wpdb->get_var($wpdb->prepare(
            "SELECT estado FROM {$tabla_consumidores} WHERE grupo_id = %d AND usuario_id = %d",
            $grupo->ID,
            $user_id
        )) : null,
    ];
}
?>

<div class="gc-grupos-lista-container">
    <div class="gc-grupos-header">
        <h2>
            <span class="dashicons dashicons-groups"></span>
            <?php esc_html_e('Grupos de Consumo', 'flavor-chat-ia'); ?>
        </h2>
        <p class="gc-grupos-descripcion">
            <?php esc_html_e('Los grupos de consumo son colectivos que organizan pedidos conjuntos a productores locales. Unete a un grupo existente o crea el tuyo.', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <?php if (empty($grupos)) : ?>
    <div class="gc-grupos-empty">
        <span class="dashicons dashicons-groups"></span>
        <h3><?php esc_html_e('No hay grupos disponibles', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('Actualmente no hay grupos de consumo activos. Se el primero en crear uno.', 'flavor-chat-ia'); ?></p>
        <?php if (is_user_logged_in() && current_user_can('gc_crear_grupo')) : ?>
        <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/crear-grupo/')); ?>" class="gc-btn gc-btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Crear grupo', 'flavor-chat-ia'); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php else : ?>

    <!-- Filtros -->
    <div class="gc-grupos-filtros">
        <div class="gc-filtro-busqueda">
            <span class="dashicons dashicons-search"></span>
            <input type="text" id="gc-buscar-grupo" placeholder="<?php esc_attr_e('Buscar grupo...', 'flavor-chat-ia'); ?>">
        </div>
        <div class="gc-filtro-opciones">
            <label class="gc-filtro-checkbox">
                <input type="checkbox" id="gc-filtro-abiertos" checked>
                <span><?php esc_html_e('Solo grupos abiertos', 'flavor-chat-ia'); ?></span>
            </label>
            <?php if ($user_id) : ?>
            <label class="gc-filtro-checkbox">
                <input type="checkbox" id="gc-filtro-mis-grupos">
                <span><?php esc_html_e('Mis grupos', 'flavor-chat-ia'); ?></span>
            </label>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista de grupos -->
    <div class="gc-grupos-grid" id="gc-grupos-grid">
        <?php foreach ($grupos as $grupo) :
            $meta_grupo = [
                'ubicacion'          => get_post_meta($grupo->ID, '_gc_ubicacion', true),
                'punto_recogida'     => get_post_meta($grupo->ID, '_gc_punto_recogida', true),
                'frecuencia_pedidos' => get_post_meta($grupo->ID, '_gc_frecuencia_pedidos', true),
                'cuota_mensual'      => get_post_meta($grupo->ID, '_gc_cuota_mensual', true),
                'admite_nuevos'      => get_post_meta($grupo->ID, '_gc_admite_nuevos', true),
                'descripcion_corta'  => get_post_meta($grupo->ID, '_gc_descripcion_corta', true),
                'imagen'             => get_post_thumbnail_id($grupo->ID),
            ];

            $estadisticas = $estadisticas_grupos[$grupo->ID];
            $es_miembro = in_array($grupo->ID, $grupos_usuario);
            $estado_usuario = $estadisticas['estado_usuario'];
            $admite_nuevos = $meta_grupo['admite_nuevos'] !== '0';
        ?>
        <div class="gc-grupo-card <?php echo $es_miembro ? 'gc-grupo-miembro' : ''; ?> <?php echo !$admite_nuevos ? 'gc-grupo-cerrado' : ''; ?>"
             data-nombre="<?php echo esc_attr(strtolower($grupo->post_title)); ?>"
             data-abierto="<?php echo $admite_nuevos ? '1' : '0'; ?>"
             data-miembro="<?php echo $es_miembro ? '1' : '0'; ?>">

            <?php if ($meta_grupo['imagen']) : ?>
            <div class="gc-grupo-imagen">
                <?php echo wp_get_attachment_image($meta_grupo['imagen'], 'medium', false, ['class' => 'gc-grupo-thumb']); ?>
                <?php if ($es_miembro) : ?>
                <span class="gc-grupo-badge gc-badge-miembro">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Miembro', 'flavor-chat-ia'); ?>
                </span>
                <?php elseif (!$admite_nuevos) : ?>
                <span class="gc-grupo-badge gc-badge-cerrado">
                    <?php esc_html_e('Cerrado', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php else : ?>
            <div class="gc-grupo-imagen gc-grupo-imagen-placeholder">
                <span class="dashicons dashicons-groups"></span>
                <?php if ($es_miembro) : ?>
                <span class="gc-grupo-badge gc-badge-miembro">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Miembro', 'flavor-chat-ia'); ?>
                </span>
                <?php elseif (!$admite_nuevos) : ?>
                <span class="gc-grupo-badge gc-badge-cerrado">
                    <?php esc_html_e('Cerrado', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="gc-grupo-content">
                <h3 class="gc-grupo-nombre">
                    <a href="<?php echo esc_url(get_permalink($grupo->ID)); ?>">
                        <?php echo esc_html($grupo->post_title); ?>
                    </a>
                </h3>

                <?php if ($meta_grupo['descripcion_corta']) : ?>
                <p class="gc-grupo-descripcion">
                    <?php echo esc_html(wp_trim_words($meta_grupo['descripcion_corta'], 20)); ?>
                </p>
                <?php elseif ($grupo->post_excerpt) : ?>
                <p class="gc-grupo-descripcion">
                    <?php echo esc_html(wp_trim_words($grupo->post_excerpt, 20)); ?>
                </p>
                <?php endif; ?>

                <div class="gc-grupo-meta">
                    <?php if ($meta_grupo['ubicacion']) : ?>
                    <div class="gc-meta-item">
                        <span class="dashicons dashicons-location"></span>
                        <span><?php echo esc_html($meta_grupo['ubicacion']); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="gc-meta-item">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span>
                            <?php
                            printf(
                                _n('%d miembro', '%d miembros', $estadisticas['miembros'], 'flavor-chat-ia'),
                                $estadisticas['miembros']
                            );
                            ?>
                        </span>
                    </div>

                    <?php if ($meta_grupo['frecuencia_pedidos']) : ?>
                    <div class="gc-meta-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php echo esc_html(ucfirst($meta_grupo['frecuencia_pedidos'])); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($meta_grupo['cuota_mensual']) : ?>
                    <div class="gc-meta-item">
                        <span class="dashicons dashicons-money-alt"></span>
                        <span><?php echo number_format((float) $meta_grupo['cuota_mensual'], 2, ',', '.'); ?> &euro;/mes</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="gc-grupo-footer">
                <?php if ($es_miembro) : ?>
                    <?php if ($estado_usuario === 'pendiente') : ?>
                    <span class="gc-btn gc-btn-warning gc-btn-disabled">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Solicitud pendiente', 'flavor-chat-ia'); ?>
                    </span>
                    <?php else : ?>
                    <a href="<?php echo esc_url(get_permalink($grupo->ID)); ?>" class="gc-btn gc-btn-primary">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                        <?php esc_html_e('Acceder', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                <?php elseif ($admite_nuevos) : ?>
                    <?php if (is_user_logged_in()) : ?>
                    <button type="button" class="gc-btn gc-btn-success gc-btn-unirse"
                            data-grupo-id="<?php echo esc_attr($grupo->ID); ?>">
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e('Unirse', 'flavor-chat-ia'); ?>
                    </button>
                    <?php else : ?>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink($grupo->ID))); ?>" class="gc-btn gc-btn-secondary">
                        <?php esc_html_e('Inicia sesion para unirte', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                <?php else : ?>
                <span class="gc-btn gc-btn-disabled">
                    <?php esc_html_e('No admite nuevos miembros', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>

                <a href="<?php echo esc_url(get_permalink($grupo->ID)); ?>" class="gc-btn gc-btn-text">
                    <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<script>
(function($) {
    'use strict';

    // Filtrar grupos
    function filtrarGrupos() {
        var busqueda = $('#gc-buscar-grupo').val().toLowerCase();
        var soloAbiertos = $('#gc-filtro-abiertos').is(':checked');
        var misGrupos = $('#gc-filtro-mis-grupos').is(':checked');

        $('.gc-grupo-card').each(function() {
            var $card = $(this);
            var nombre = $card.data('nombre');
            var esAbierto = $card.data('abierto') === 1;
            var esMiembro = $card.data('miembro') === 1;

            var coincideBusqueda = !busqueda || nombre.indexOf(busqueda) !== -1;
            var coincideAbierto = !soloAbiertos || esAbierto;
            var coincideMiembro = !misGrupos || esMiembro;

            if (coincideBusqueda && coincideAbierto && coincideMiembro) {
                $card.show();
            } else {
                $card.hide();
            }
        });
    }

    $('#gc-buscar-grupo').on('input', filtrarGrupos);
    $('#gc-filtro-abiertos, #gc-filtro-mis-grupos').on('change', filtrarGrupos);

    // Solicitar union al grupo
    $('.gc-btn-unirse').on('click', function() {
        var $btn = $(this);
        var grupoId = $btn.data('grupo-id');

        if ($btn.prop('disabled')) return;

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js(__('Enviando...', 'flavor-chat-ia')); ?>');

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'grupos_consumo_solicitar_union',
                nonce: '<?php echo esc_attr(wp_create_nonce('gc_nonce')); ?>',
                grupo_id: grupoId
            },
            success: function(response) {
                if (response.success) {
                    $btn.removeClass('gc-btn-success').addClass('gc-btn-warning gc-btn-disabled')
                        .html('<span class="dashicons dashicons-clock"></span> <?php echo esc_js(__('Solicitud pendiente', 'flavor-chat-ia')); ?>');
                    $btn.closest('.gc-grupo-card').addClass('gc-grupo-miembro');
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error al procesar la solicitud.', 'flavor-chat-ia')); ?>');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-plus"></span> <?php echo esc_js(__('Unirse', 'flavor-chat-ia')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error de conexion.', 'flavor-chat-ia')); ?>');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-plus"></span> <?php echo esc_js(__('Unirse', 'flavor-chat-ia')); ?>');
            }
        });
    });

})(jQuery);
</script>

<style>
.gc-grupos-lista-container {
    max-width: 1000px;
}
.gc-grupos-header {
    margin-bottom: 25px;
}
.gc-grupos-header h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 10px 0;
}
.gc-grupos-descripcion {
    color: #666;
    margin: 0;
}
.gc-grupos-empty {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 10px;
}
.gc-grupos-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
    margin-bottom: 15px;
}
.gc-grupos-empty h3 {
    margin: 0 0 10px 0;
    color: #666;
}
.gc-grupos-empty p {
    color: #999;
    margin-bottom: 20px;
}
.gc-grupos-filtros {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
}
.gc-filtro-busqueda {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ddd;
    flex: 1;
    max-width: 300px;
}
.gc-filtro-busqueda input {
    border: none;
    outline: none;
    flex: 1;
    font-size: 14px;
}
.gc-filtro-opciones {
    display: flex;
    gap: 20px;
}
.gc-filtro-checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-size: 14px;
}
.gc-grupos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.gc-grupo-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.gc-grupo-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}
.gc-grupo-miembro {
    border: 2px solid #4caf50;
}
.gc-grupo-cerrado {
    opacity: 0.8;
}
.gc-grupo-imagen {
    position: relative;
    height: 140px;
    background: #e8f5e9;
    overflow: hidden;
}
.gc-grupo-imagen-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
}
.gc-grupo-imagen-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #a5d6a7;
}
.gc-grupo-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.gc-grupo-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.gc-badge-miembro {
    background: #4caf50;
    color: #fff;
}
.gc-badge-cerrado {
    background: #757575;
    color: #fff;
}
.gc-grupo-content {
    padding: 15px;
}
.gc-grupo-nombre {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
}
.gc-grupo-nombre a {
    color: #333;
    text-decoration: none;
}
.gc-grupo-nombre a:hover {
    color: #4caf50;
}
.gc-grupo-descripcion {
    color: #666;
    font-size: 14px;
    margin: 0 0 15px 0;
    line-height: 1.5;
}
.gc-grupo-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.gc-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: #757575;
}
.gc-meta-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.gc-grupo-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #fafafa;
    border-top: 1px solid #eee;
}
.gc-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}
.gc-btn-primary {
    background: #4caf50;
    color: #fff;
}
.gc-btn-primary:hover {
    background: #388e3c;
    color: #fff;
}
.gc-btn-success {
    background: #2196f3;
    color: #fff;
}
.gc-btn-success:hover {
    background: #1976d2;
    color: #fff;
}
.gc-btn-secondary {
    background: #e0e0e0;
    color: #333;
}
.gc-btn-secondary:hover {
    background: #bdbdbd;
}
.gc-btn-warning {
    background: #ff9800;
    color: #fff;
}
.gc-btn-text {
    background: none;
    color: #666;
    padding: 10px 8px;
}
.gc-btn-text:hover {
    color: #333;
}
.gc-btn-disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
@media (max-width: 600px) {
    .gc-grupos-filtros {
        flex-direction: column;
        align-items: stretch;
    }
    .gc-filtro-busqueda {
        max-width: none;
    }
    .gc-filtro-opciones {
        flex-direction: column;
        gap: 10px;
    }
    .gc-grupos-grid {
        grid-template-columns: 1fr;
    }
    .gc-grupo-footer {
        flex-direction: column;
        gap: 10px;
    }
    .gc-grupo-footer .gc-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

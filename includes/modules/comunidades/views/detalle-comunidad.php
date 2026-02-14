<?php
/**
 * Vista: Detalle de comunidad
 *
 * @package FlavorChatIA
 * @var array  $comunidad              Datos de la comunidad
 * @var array  $miembros               Lista de miembros
 * @var int    $identificador_usuario  ID del usuario actual
 * @var bool   $es_miembro             Si el usuario es miembro
 * @var string $rol_usuario            Rol del usuario en la comunidad
 */

if (!defined('ABSPATH')) {
    exit;
}

$es_admin = $rol_usuario === 'admin';
$es_moderador = in_array($rol_usuario, ['admin', 'moderador'], true);
?>

<div class="flavor-com-detalle" data-comunidad="<?php echo esc_attr($comunidad['id']); ?>">

    <div class="flavor-com-cabecera">
        <?php if (!empty($comunidad['imagen'])): ?>
            <div class="flavor-com-portada" style="background-image: url('<?php echo esc_url($comunidad['imagen']); ?>')"></div>
        <?php else: ?>
            <div class="flavor-com-portada flavor-com-portada-default">
                <span class="dashicons dashicons-groups"></span>
            </div>
        <?php endif; ?>

        <div class="flavor-com-cabecera-info">
            <div class="flavor-com-cabecera-top">
                <span class="flavor-com-categoria">
                    <?php echo esc_html(ucfirst($comunidad['categoria'])); ?>
                </span>
                <?php if ($comunidad['tipo'] === 'cerrada'): ?>
                    <span class="flavor-com-badge-cerrada">
                        <span class="dashicons dashicons-lock"></span>
                        <?php esc_html_e('Comunidad cerrada', 'flavor-chat-ia'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="flavor-com-nombre"><?php echo esc_html($comunidad['nombre']); ?></h1>

            <p class="flavor-com-descripcion-full">
                <?php echo nl2br(esc_html($comunidad['descripcion'])); ?>
            </p>

            <div class="flavor-com-stats">
                <div class="flavor-com-stat">
                    <span class="valor"><?php echo intval($comunidad['miembros_count']); ?></span>
                    <span class="label"><?php esc_html_e('Miembros', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-com-stat">
                    <span class="valor"><?php echo esc_html(date_i18n('M Y', strtotime($comunidad['created_at']))); ?></span>
                    <span class="label"><?php esc_html_e('Creada', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <div class="flavor-com-acciones">
                <?php if (!$identificador_usuario): ?>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-com-btn flavor-com-btn-primary">
                        <?php esc_html_e('Inicia sesión para unirte', 'flavor-chat-ia'); ?>
                    </a>
                <?php elseif ($es_miembro): ?>
                    <?php if ($es_admin): ?>
                        <button type="button" class="flavor-com-btn flavor-com-btn-primary" id="com-configurar">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('Configurar', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="flavor-com-btn flavor-com-btn-secondary flavor-com-btn-salir"
                            data-comunidad="<?php echo esc_attr($comunidad['id']); ?>">
                        <span class="dashicons dashicons-exit"></span>
                        <?php esc_html_e('Abandonar', 'flavor-chat-ia'); ?>
                    </button>
                <?php else: ?>
                    <button type="button" class="flavor-com-btn flavor-com-btn-primary flavor-com-btn-unirse"
                            data-comunidad="<?php echo esc_attr($comunidad['id']); ?>">
                        <span class="dashicons dashicons-plus"></span>
                        <?php echo $comunidad['tipo'] === 'cerrada'
                            ? esc_html__('Solicitar unirse', 'flavor-chat-ia')
                            : esc_html__('Unirse a la comunidad', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="flavor-com-contenido">
        <div class="flavor-com-tabs">
            <button type="button" class="flavor-com-tab active" data-tab="actividad">
                <span class="dashicons dashicons-format-status"></span>
                <?php esc_html_e('Actividad', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-com-tab" data-tab="miembros">
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e('Miembros', 'flavor-chat-ia'); ?>
                <span class="count"><?php echo count($miembros); ?></span>
            </button>
            <?php if ($es_miembro): ?>
            <button type="button" class="flavor-com-tab" data-tab="eventos">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Eventos', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <div class="flavor-com-tab-content">
            <!-- Tab Actividad -->
            <div class="flavor-com-panel active" id="panel-actividad">
                <?php if ($es_miembro): ?>
                <div class="flavor-com-publicar">
                    <form id="flavor-com-form-publicar">
                        <textarea name="contenido" class="flavor-com-textarea"
                                  placeholder="<?php esc_attr_e('Comparte algo con la comunidad...', 'flavor-chat-ia'); ?>"></textarea>
                        <div class="flavor-com-publicar-acciones">
                            <button type="submit" class="flavor-com-btn flavor-com-btn-primary">
                                <?php esc_html_e('Publicar', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <div class="flavor-com-feed" id="com-feed">
                    <div class="flavor-com-loading">
                        <span class="flavor-com-spinner"></span>
                        <?php esc_html_e('Cargando actividad...', 'flavor-chat-ia'); ?>
                    </div>
                </div>
            </div>

            <!-- Tab Miembros -->
            <div class="flavor-com-panel" id="panel-miembros">
                <div class="flavor-com-miembros-lista">
                    <?php if (empty($miembros)): ?>
                        <p class="flavor-com-sin-datos"><?php esc_html_e('No hay miembros.', 'flavor-chat-ia'); ?></p>
                    <?php else: ?>
                        <?php foreach ($miembros as $miembro): ?>
                            <div class="flavor-com-miembro">
                                <img src="<?php echo esc_url($miembro['avatar']); ?>" alt="" class="flavor-com-avatar">
                                <div class="flavor-com-miembro-info">
                                    <span class="nombre"><?php echo esc_html($miembro['nombre']); ?></span>
                                    <span class="rol rol-<?php echo esc_attr($miembro['rol']); ?>">
                                        <?php echo esc_html($miembro['rol_label']); ?>
                                    </span>
                                </div>
                                <span class="flavor-com-fecha-union">
                                    <?php printf(
                                        esc_html__('Desde %s', 'flavor-chat-ia'),
                                        date_i18n('M Y', strtotime($miembro['fecha_alta']))
                                    ); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($es_admin): ?>
                <div class="flavor-com-invitar">
                    <button type="button" class="flavor-com-btn flavor-com-btn-secondary" id="com-invitar-btn">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php esc_html_e('Invitar miembros', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab Eventos -->
            <?php if ($es_miembro): ?>
            <div class="flavor-com-panel" id="panel-eventos">
                <div class="flavor-com-sin-datos">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No hay eventos programados.', 'flavor-chat-ia'); ?></p>
                    <?php if ($es_moderador): ?>
                    <button type="button" class="flavor-com-btn flavor-com-btn-primary">
                        <?php esc_html_e('Crear evento', 'flavor-chat-ia'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

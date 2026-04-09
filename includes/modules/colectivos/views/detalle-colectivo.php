<?php
/**
 * Vista: Detalle de colectivo
 *
 * @package FlavorChatIA
 * @var array  $colectivo             Datos del colectivo
 * @var array  $miembros              Lista de miembros
 * @var array  $proyectos             Lista de proyectos
 * @var array  $asambleas             Lista de asambleas
 * @var int    $identificador_usuario ID del usuario actual
 * @var bool   $es_miembro            Si el usuario es miembro
 * @var string $rol_usuario           Rol del usuario
 */

if (!defined('ABSPATH')) {
    exit;
}

$es_gestor = in_array($rol_usuario, ['presidente', 'secretario', 'tesorero'], true);
?>

<div class="flavor-col-detalle" data-colectivo="<?php echo esc_attr($colectivo['id']); ?>">

    <div class="flavor-col-cabecera">
        <?php if (!empty($colectivo['imagen'])): ?>
            <div class="flavor-col-portada" style="background-image: url('<?php echo esc_url($colectivo['imagen']); ?>')"></div>
        <?php else: ?>
            <div class="flavor-col-portada flavor-col-portada-default">
                <span class="dashicons dashicons-networking"></span>
            </div>
        <?php endif; ?>

        <div class="flavor-col-cabecera-info">
            <div class="flavor-col-badges">
                <span class="flavor-col-tipo-badge flavor-col-tipo-<?php echo esc_attr($colectivo['tipo']); ?>">
                    <?php echo esc_html($colectivo['tipo_label']); ?>
                </span>
                <?php if (!empty($colectivo['sector'])): ?>
                    <span class="flavor-col-sector-badge"><?php echo esc_html($colectivo['sector']); ?></span>
                <?php endif; ?>
            </div>

            <h1 class="flavor-col-nombre"><?php echo esc_html($colectivo['nombre']); ?></h1>

            <p class="flavor-col-descripcion"><?php echo nl2br(esc_html($colectivo['descripcion'])); ?></p>

            <div class="flavor-col-stats-row">
                <div class="flavor-col-stat-item">
                    <span class="valor"><?php echo intval($colectivo['miembros_count']); ?></span>
                    <span class="label"><?php esc_html_e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flavor-col-stat-item">
                    <span class="valor"><?php echo intval($colectivo['proyectos_count']); ?></span>
                    <span class="label"><?php esc_html_e('Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flavor-col-stat-item">
                    <span class="valor"><?php echo esc_html(date_i18n('Y', strtotime($colectivo['created_at']))); ?></span>
                    <span class="label"><?php esc_html_e('Fundado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="flavor-col-acciones">
                <?php if (!$identificador_usuario): ?>
                    <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="flavor-col-btn flavor-col-btn-primary">
                        <?php esc_html_e('Inicia sesión para unirte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php elseif ($es_miembro): ?>
                    <?php if ($es_gestor): ?>
                        <button type="button" class="flavor-col-btn flavor-col-btn-primary" id="col-gestionar">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('Gestionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($rol_usuario !== 'presidente'): ?>
                        <button type="button" class="flavor-col-btn flavor-col-btn-outline flavor-col-btn-abandonar"
                                data-colectivo="<?php echo esc_attr($colectivo['id']); ?>">
                            <span class="dashicons dashicons-exit"></span>
                            <?php esc_html_e('Abandonar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button type="button" class="flavor-col-btn flavor-col-btn-primary flavor-col-btn-unirse"
                            data-colectivo="<?php echo esc_attr($colectivo['id']); ?>">
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e('Solicitar unirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Contacto -->
    <?php if (!empty($colectivo['email_contacto']) || !empty($colectivo['telefono']) || !empty($colectivo['web'])): ?>
        <div class="flavor-col-contacto">
            <?php if (!empty($colectivo['email_contacto'])): ?>
                <a href="mailto:<?php echo esc_attr($colectivo['email_contacto']); ?>" class="flavor-col-contacto-item">
                    <span class="dashicons dashicons-email"></span>
                    <?php echo esc_html($colectivo['email_contacto']); ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($colectivo['telefono'])): ?>
                <a href="tel:<?php echo esc_attr($colectivo['telefono']); ?>" class="flavor-col-contacto-item">
                    <span class="dashicons dashicons-phone"></span>
                    <?php echo esc_html($colectivo['telefono']); ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($colectivo['web'])): ?>
                <a href="<?php echo esc_url($colectivo['web']); ?>" target="_blank" class="flavor-col-contacto-item">
                    <span class="dashicons dashicons-admin-site"></span>
                    <?php esc_html_e('Sitio web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="flavor-col-tabs-container">
        <div class="flavor-col-tabs">
            <button type="button" class="flavor-col-tab active" data-tab="miembros">
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="count"><?php echo count($miembros); ?></span>
            </button>
            <button type="button" class="flavor-col-tab" data-tab="proyectos">
                <span class="dashicons dashicons-portfolio"></span>
                <?php esc_html_e('Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="count"><?php echo count($proyectos); ?></span>
            </button>
            <button type="button" class="flavor-col-tab" data-tab="asambleas">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Asambleas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>

        <!-- Panel Miembros -->
        <div class="flavor-col-panel active" id="panel-miembros">
            <?php if (empty($miembros)): ?>
                <p class="flavor-col-sin-datos"><?php esc_html_e('No hay miembros registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
                <div class="flavor-col-miembros-grid">
                    <?php foreach ($miembros as $miembro): ?>
                        <div class="flavor-col-miembro">
                            <img src="<?php echo esc_url($miembro['avatar']); ?>" alt="" class="flavor-col-avatar">
                            <div class="flavor-col-miembro-info">
                                <span class="nombre"><?php echo esc_html($miembro['nombre']); ?></span>
                                <span class="rol rol-<?php echo esc_attr($miembro['rol']); ?>">
                                    <?php echo esc_html($miembro['rol_label']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Panel Proyectos -->
        <div class="flavor-col-panel" id="panel-proyectos">
            <?php if ($es_miembro): ?>
                <div class="flavor-col-panel-header">
                    <button type="button" class="flavor-col-btn flavor-col-btn-secondary" id="col-nuevo-proyecto">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Nuevo proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (empty($proyectos)): ?>
                <div class="flavor-col-sin-datos">
                    <span class="dashicons dashicons-portfolio"></span>
                    <p><?php esc_html_e('No hay proyectos activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-col-proyectos-lista">
                    <?php foreach ($proyectos as $proyecto): ?>
                        <article class="flavor-col-proyecto">
                            <div class="flavor-col-proyecto-header">
                                <h4><?php echo esc_html($proyecto['titulo']); ?></h4>
                                <span class="flavor-col-estado flavor-col-estado-<?php echo esc_attr($proyecto['estado']); ?>">
                                    <?php echo esc_html($proyecto['estado_label']); ?>
                                </span>
                            </div>

                            <?php if (!empty($proyecto['descripcion'])): ?>
                                <p class="flavor-col-proyecto-desc"><?php echo esc_html($proyecto['descripcion']); ?></p>
                            <?php endif; ?>

                            <div class="flavor-col-proyecto-meta">
                                <?php if ($proyecto['presupuesto'] > 0): ?>
                                    <span class="meta-item">
                                        <span class="dashicons dashicons-money-alt"></span>
                                        <?php echo esc_html($proyecto['presupuesto_fmt']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($proyecto['fecha_inicio']): ?>
                                    <span class="meta-item">
                                        <span class="dashicons dashicons-calendar"></span>
                                        <?php echo esc_html(date_i18n('j M Y', strtotime($proyecto['fecha_inicio']))); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($proyecto['estado'] === 'en_curso'): ?>
                                <div class="flavor-col-progreso">
                                    <div class="flavor-col-progreso-barra">
                                        <div class="flavor-col-progreso-fill" style="width: <?php echo intval($proyecto['progreso']); ?>%"></div>
                                    </div>
                                    <span class="flavor-col-progreso-valor"><?php echo intval($proyecto['progreso']); ?>%</span>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Panel Asambleas -->
        <div class="flavor-col-panel" id="panel-asambleas">
            <?php if ($es_gestor): ?>
                <div class="flavor-col-panel-header">
                    <button type="button" class="flavor-col-btn flavor-col-btn-secondary" id="col-nueva-asamblea">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Convocar asamblea', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (empty($asambleas)): ?>
                <div class="flavor-col-sin-datos">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No hay asambleas programadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-col-asambleas-lista">
                    <?php foreach ($asambleas as $asamblea): ?>
                        <article class="flavor-col-asamblea">
                            <div class="flavor-col-asamblea-fecha">
                                <span class="dia"><?php echo esc_html(date_i18n('j', strtotime($asamblea['fecha']))); ?></span>
                                <span class="mes"><?php echo esc_html(date_i18n('M', strtotime($asamblea['fecha']))); ?></span>
                            </div>
                            <div class="flavor-col-asamblea-info">
                                <h4><?php echo esc_html($asamblea['titulo']); ?></h4>
                                <div class="flavor-col-asamblea-meta">
                                    <span class="tipo"><?php echo esc_html($asamblea['tipo_label']); ?></span>
                                    <span class="hora">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo esc_html(date_i18n('H:i', strtotime($asamblea['fecha']))); ?>
                                    </span>
                                    <?php if (!empty($asamblea['lugar'])): ?>
                                        <span class="lugar">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html($asamblea['lugar']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flavor-col-asamblea-acciones">
                                <span class="flavor-col-estado flavor-col-estado-<?php echo esc_attr($asamblea['estado']); ?>">
                                    <?php echo esc_html($asamblea['estado_label']); ?>
                                </span>
                                <?php if ($es_miembro && $asamblea['estado'] === 'convocada'): ?>
                                    <button type="button" class="flavor-col-btn flavor-col-btn-sm flavor-col-btn-confirmar"
                                            data-asamblea="<?php echo esc_attr($asamblea['id']); ?>">
                                        <?php esc_html_e('Confirmar asistencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nuevo Proyecto -->
<div id="modal-nuevo-proyecto" class="flavor-col-modal" style="display: none;">
    <div class="flavor-col-modal-content">
        <div class="flavor-col-modal-header">
            <h3><?php esc_html_e('Nuevo Proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <button type="button" class="flavor-col-modal-close">&times;</button>
        </div>
        <form id="form-nuevo-proyecto" class="flavor-col-form">
            <input type="hidden" name="colectivo_id" value="<?php echo esc_attr($colectivo['id']); ?>">

            <div class="flavor-col-campo">
                <label for="proy-titulo"><?php esc_html_e('Título del proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <input type="text" id="proy-titulo" name="titulo" class="flavor-col-input" required>
            </div>

            <div class="flavor-col-campo">
                <label for="proy-descripcion"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <textarea id="proy-descripcion" name="descripcion" class="flavor-col-textarea" rows="3"></textarea>
            </div>

            <div class="flavor-col-campo-doble">
                <div class="flavor-col-campo">
                    <label for="proy-presupuesto"><?php esc_html_e('Presupuesto (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" id="proy-presupuesto" name="presupuesto" class="flavor-col-input" min="0" step="0.01">
                </div>
                <div class="flavor-col-campo">
                    <label for="proy-fecha-inicio"><?php esc_html_e('Fecha inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="date" id="proy-fecha-inicio" name="fecha_inicio" class="flavor-col-input">
                </div>
            </div>

            <div class="flavor-col-modal-footer">
                <button type="button" class="flavor-col-btn flavor-col-btn-secondary flavor-col-modal-cancelar">
                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="submit" class="flavor-col-btn flavor-col-btn-primary">
                    <?php esc_html_e('Crear proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nueva Asamblea -->
<div id="modal-nueva-asamblea" class="flavor-col-modal" style="display: none;">
    <div class="flavor-col-modal-content">
        <div class="flavor-col-modal-header">
            <h3><?php esc_html_e('Convocar Asamblea', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <button type="button" class="flavor-col-modal-close">&times;</button>
        </div>
        <form id="form-nueva-asamblea" class="flavor-col-form">
            <input type="hidden" name="colectivo_id" value="<?php echo esc_attr($colectivo['id']); ?>">

            <div class="flavor-col-campo">
                <label for="asam-titulo"><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <input type="text" id="asam-titulo" name="titulo" class="flavor-col-input" required>
            </div>

            <div class="flavor-col-campo-doble">
                <div class="flavor-col-campo">
                    <label for="asam-tipo"><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="asam-tipo" name="tipo" class="flavor-col-select">
                        <option value="ordinaria"><?php esc_html_e('Ordinaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="extraordinaria"><?php esc_html_e('Extraordinaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                <div class="flavor-col-campo">
                    <label for="asam-fecha"><?php esc_html_e('Fecha y hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="datetime-local" id="asam-fecha" name="fecha" class="flavor-col-input" required>
                </div>
            </div>

            <div class="flavor-col-campo">
                <label for="asam-lugar"><?php esc_html_e('Lugar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" id="asam-lugar" name="lugar" class="flavor-col-input">
            </div>

            <div class="flavor-col-campo">
                <label for="asam-orden"><?php esc_html_e('Orden del día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <textarea id="asam-orden" name="orden_del_dia" class="flavor-col-textarea" rows="4"
                          placeholder="<?php esc_attr_e('1. Lectura y aprobación del acta anterior\n2. Informe de actividades\n3. Ruegos y preguntas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div class="flavor-col-modal-footer">
                <button type="button" class="flavor-col-btn flavor-col-btn-secondary flavor-col-modal-cancelar">
                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="submit" class="flavor-col-btn flavor-col-btn-primary">
                    <?php esc_html_e('Convocar asamblea', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>
</div>

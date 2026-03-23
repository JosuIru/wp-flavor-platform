<?php
/**
 * Vista Artista - Kulturaka
 * Panel para artistas: propuestas enviadas, gira, métricas, espacios disponibles
 *
 * @package FlavorChatIA
 * @subpackage Modules\Kulturaka
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$current_user_id = get_current_user_id();

// Tablas
$tabla_artistas = $wpdb->prefix . 'flavor_socios_artistas';
$tabla_nodos = $wpdb->prefix . 'flavor_kulturaka_nodos';
$tabla_propuestas = $wpdb->prefix . 'flavor_kulturaka_propuestas';
$tabla_eventos = $wpdb->prefix . 'flavor_eventos';
$tabla_crowdfunding = $wpdb->prefix . 'flavor_crowdfunding_proyectos';
$tabla_agradecimientos = $wpdb->prefix . 'flavor_kulturaka_agradecimientos';

// Obtener perfil de artista
$artista = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla_artistas WHERE usuario_id = %d AND estado = 'activo'",
    $current_user_id
));

if (!$artista) {
    echo '<div class="notice notice-error"><p>No se encontró tu perfil de artista.</p></div>';
    return;
}

// Mis propuestas
$mis_propuestas = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, n.nombre as espacio_nombre, n.ciudad as espacio_ciudad, n.imagen_principal as espacio_imagen
     FROM $tabla_propuestas p
     LEFT JOIN $tabla_nodos n ON p.nodo_id = n.id
     WHERE p.artista_id = %d
     ORDER BY p.created_at DESC",
    $artista->id
));

// Estadísticas de propuestas
$stats_propuestas = [
    'total' => count($mis_propuestas),
    'pendientes' => count(array_filter($mis_propuestas, fn($p) => in_array($p->estado, ['enviada', 'negociando']))),
    'aceptadas' => count(array_filter($mis_propuestas, fn($p) => $p->estado === 'aceptada')),
    'rechazadas' => count(array_filter($mis_propuestas, fn($p) => $p->estado === 'rechazada')),
];

// Próximos eventos (gira)
$proximos_eventos = [];
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos) {
    $proximos_eventos = $wpdb->get_results($wpdb->prepare(
        "SELECT e.*, n.nombre as espacio_nombre, n.ciudad as espacio_ciudad
         FROM $tabla_eventos e
         LEFT JOIN $tabla_nodos n ON e.espacio_id = n.entidad_id
         WHERE e.artista_id = %d AND e.fecha_inicio >= NOW()
         ORDER BY e.fecha_inicio ASC
         LIMIT 10",
        $artista->id
    )) ?: [];
}

// Mis crowdfundings
$mis_crowdfundings = [];
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_crowdfunding'") === $tabla_crowdfunding) {
    $mis_crowdfundings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_crowdfunding
         WHERE creador_id = %d
         ORDER BY created_at DESC
         LIMIT 5",
        $current_user_id
    )) ?: [];
}

// Espacios que aceptan propuestas
$espacios_disponibles = $wpdb->get_results(
    "SELECT * FROM $tabla_nodos
     WHERE tipo = 'espacio'
       AND estado = 'activo'
       AND acepta_propuestas = 1
     ORDER BY destacado DESC, indice_cooperacion DESC
     LIMIT 12"
);

// Agradecimientos recibidos
$agradecimientos = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, u.display_name as autor_nombre
     FROM $tabla_agradecimientos a
     LEFT JOIN {$wpdb->users} u ON a.usuario_id = u.ID
     WHERE a.destinatario_tipo = 'artista'
       AND a.destinatario_id = %d
       AND a.estado = 'activo'
     ORDER BY a.created_at DESC
     LIMIT 5",
    $artista->id
));

// Ingresos totales (si hay datos)
$ingresos = [
    'eur' => 0,
    'semilla' => 0,
    'hours' => 0,
];
?>

<div class="vista-artista">
    <!-- Header del artista -->
    <div class="artista-header">
        <div class="artista-info">
            <?php if (!empty($artista->imagen)): ?>
                <img src="<?php echo esc_url($artista->imagen); ?>" alt="" class="artista-avatar">
            <?php else: ?>
                <div class="artista-avatar placeholder">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
            <?php endif; ?>

            <div class="artista-datos">
                <h2>
                    <?php echo esc_html($artista->nombre_artistico ?: get_userdata($current_user_id)->display_name); ?>
                    <?php if ($artista->verificado): ?>
                        <span class="verified-badge" title="Artista verificado">✓</span>
                    <?php endif; ?>
                </h2>
                <?php if (!empty($artista->disciplinas)): ?>
                    <p class="artista-disciplinas">
                        <?php
                        $disciplinas = json_decode($artista->disciplinas, true) ?: [];
                        echo esc_html(implode(' • ', array_slice($disciplinas, 0, 3)));
                        ?>
                    </p>
                <?php endif; ?>
                <div class="artista-badges">
                    <?php if ($artista->disponible_gira): ?>
                        <span class="badge disponible">🎤 Disponible para gira</span>
                    <?php endif; ?>
                    <?php if ($artista->acepta_semilla): ?>
                        <span class="badge semilla">🌱 Acepta SEMILLA</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="artista-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=socios-dashboard&action=editar-artista&id=' . $artista->id)); ?>" class="button">
                <span class="dashicons dashicons-edit"></span> Editar perfil
            </a>
            <button type="button" class="button button-primary" onclick="abrirModalPropuesta()">
                <span class="dashicons dashicons-plus-alt"></span> Nueva propuesta
            </button>
        </div>
    </div>

    <!-- Stats rápidos -->
    <div class="artista-stats-grid">
        <div class="stat-card">
            <span class="stat-icon"><span class="dashicons dashicons-email-alt"></span></span>
            <div class="stat-data">
                <span class="stat-number"><?php echo $stats_propuestas['total']; ?></span>
                <span class="stat-label">Propuestas enviadas</span>
            </div>
        </div>
        <div class="stat-card success">
            <span class="stat-icon"><span class="dashicons dashicons-yes-alt"></span></span>
            <div class="stat-data">
                <span class="stat-number"><?php echo $stats_propuestas['aceptadas']; ?></span>
                <span class="stat-label">Aceptadas</span>
            </div>
        </div>
        <div class="stat-card pending">
            <span class="stat-icon"><span class="dashicons dashicons-clock"></span></span>
            <div class="stat-data">
                <span class="stat-number"><?php echo $stats_propuestas['pendientes']; ?></span>
                <span class="stat-label">Pendientes</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><span class="dashicons dashicons-calendar-alt"></span></span>
            <div class="stat-data">
                <span class="stat-number"><?php echo count($proximos_eventos); ?></span>
                <span class="stat-label">Próximos eventos</span>
            </div>
        </div>
    </div>

    <div class="artista-content-grid">
        <!-- Columna principal -->
        <div class="artista-main">
            <!-- Mis propuestas -->
            <section class="section-propuestas">
                <div class="section-header">
                    <h3><span class="dashicons dashicons-email-alt"></span> Mis propuestas</h3>
                    <div class="section-tabs">
                        <button class="tab active" data-filter="all">Todas</button>
                        <button class="tab" data-filter="pendiente">Pendientes</button>
                        <button class="tab" data-filter="aceptada">Aceptadas</button>
                    </div>
                </div>

                <?php if ($mis_propuestas): ?>
                    <div class="propuestas-list">
                        <?php foreach ($mis_propuestas as $prop): ?>
                            <div class="propuesta-card estado-<?php echo esc_attr($prop->estado); ?>"
                                 data-estado="<?php echo esc_attr($prop->estado); ?>">
                                <div class="propuesta-espacio">
                                    <?php if (!empty($prop->espacio_imagen)): ?>
                                        <img src="<?php echo esc_url($prop->espacio_imagen); ?>" alt="" class="espacio-thumb">
                                    <?php else: ?>
                                        <div class="espacio-thumb placeholder">
                                            <span class="dashicons dashicons-building"></span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo esc_html($prop->espacio_nombre ?: 'Espacio #' . $prop->nodo_id); ?></strong>
                                        <?php if ($prop->espacio_ciudad): ?>
                                            <span class="ciudad"><?php echo esc_html($prop->espacio_ciudad); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="propuesta-detalles">
                                    <h4><?php echo esc_html($prop->titulo); ?></h4>
                                    <div class="propuesta-meta">
                                        <span><span class="dashicons dashicons-tag"></span> <?php echo esc_html(ucfirst($prop->tipo_evento)); ?></span>
                                        <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($prop->duracion_minutos); ?> min</span>
                                        <span><span class="dashicons dashicons-money-alt"></span> <?php echo esc_html(ucfirst($prop->modelo_economico)); ?></span>
                                    </div>
                                </div>

                                <div class="propuesta-estado">
                                    <?php
                                    $estados_config = [
                                        'enviada' => ['label' => 'Enviada', 'class' => 'info'],
                                        'vista' => ['label' => 'Vista', 'class' => 'info'],
                                        'negociando' => ['label' => 'Negociando', 'class' => 'warning'],
                                        'aceptada' => ['label' => 'Aceptada', 'class' => 'success'],
                                        'rechazada' => ['label' => 'Rechazada', 'class' => 'error'],
                                        'cancelada' => ['label' => 'Cancelada', 'class' => 'muted'],
                                        'realizada' => ['label' => 'Realizada', 'class' => 'success'],
                                    ];
                                    $estado_info = $estados_config[$prop->estado] ?? ['label' => $prop->estado, 'class' => 'muted'];
                                    ?>
                                    <span class="estado-badge <?php echo esc_attr($estado_info['class']); ?>">
                                        <?php echo esc_html($estado_info['label']); ?>
                                    </span>
                                    <span class="propuesta-fecha"><?php echo human_time_diff(strtotime($prop->created_at)); ?></span>
                                </div>

                                <div class="propuesta-actions">
                                    <button type="button" class="button btn-ver" data-id="<?php echo esc_attr($prop->id); ?>">
                                        Ver detalles
                                    </button>
                                    <?php if ($prop->estado === 'negociando' && $prop->respuesta): ?>
                                        <span class="has-response" title="Tiene respuesta">💬</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="dashicons dashicons-email"></span>
                        <h4>No has enviado propuestas aún</h4>
                        <p>Explora los espacios disponibles y envía tu primera propuesta</p>
                        <button type="button" class="button button-primary" onclick="document.getElementById('espacios-section').scrollIntoView({behavior: 'smooth'})">
                            Ver espacios disponibles
                        </button>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Mi gira / Próximos eventos -->
            <section class="section-gira">
                <div class="section-header">
                    <h3><span class="dashicons dashicons-location-alt"></span> Mi gira</h3>
                </div>

                <?php if ($proximos_eventos): ?>
                    <div class="gira-timeline">
                        <?php foreach ($proximos_eventos as $evento): ?>
                            <div class="gira-item">
                                <div class="gira-fecha">
                                    <span class="dia"><?php echo date('d', strtotime($evento->fecha_inicio)); ?></span>
                                    <span class="mes"><?php echo date_i18n('M', strtotime($evento->fecha_inicio)); ?></span>
                                </div>
                                <div class="gira-linea"></div>
                                <div class="gira-info">
                                    <h4><?php echo esc_html($evento->titulo); ?></h4>
                                    <p class="gira-lugar">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($evento->espacio_nombre); ?>
                                        <?php if ($evento->espacio_ciudad): ?>
                                            - <?php echo esc_html($evento->espacio_ciudad); ?>
                                        <?php endif; ?>
                                    </p>
                                    <span class="gira-hora"><?php echo date('H:i', strtotime($evento->fecha_inicio)); ?>h</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state small">
                        <p>No tienes eventos próximos programados</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Espacios disponibles -->
            <section class="section-espacios" id="espacios-section">
                <div class="section-header">
                    <h3><span class="dashicons dashicons-building"></span> Espacios que aceptan propuestas</h3>
                    <a href="?page=flavor-kulturaka&vista=red" class="ver-mas">Ver mapa →</a>
                </div>

                <?php if ($espacios_disponibles): ?>
                    <div class="espacios-grid">
                        <?php foreach ($espacios_disponibles as $esp): ?>
                            <div class="espacio-card-mini">
                                <?php if (!empty($esp->imagen_principal)): ?>
                                    <div class="esp-imagen" style="background-image: url('<?php echo esc_url($esp->imagen_principal); ?>')"></div>
                                <?php else: ?>
                                    <div class="esp-imagen placeholder" style="background-color: <?php echo esc_attr($esp->color_marca ?: '#ec4899'); ?>">
                                        <span class="dashicons dashicons-building"></span>
                                    </div>
                                <?php endif; ?>

                                <div class="esp-content">
                                    <h5>
                                        <?php echo esc_html($esp->nombre); ?>
                                        <?php if ($esp->verificado): ?>
                                            <span class="verified-mini">✓</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="esp-ciudad">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($esp->ciudad ?: 'Sin ubicación'); ?>
                                    </p>

                                    <div class="esp-monedas">
                                        <?php if ($esp->acepta_eur): ?><span title="Euros">€</span><?php endif; ?>
                                        <?php if ($esp->acepta_semilla): ?><span title="SEMILLA">🌱</span><?php endif; ?>
                                        <?php if ($esp->acepta_hours): ?><span title="HOURS">⏰</span><?php endif; ?>
                                    </div>

                                    <div class="esp-stats">
                                        <span title="Índice de cooperación">⭐ <?php echo number_format($esp->indice_cooperacion, 1); ?></span>
                                        <span title="Artistas apoyados">🎤 <?php echo $esp->artistas_apoyados; ?></span>
                                    </div>

                                    <button type="button" class="btn-proponer" data-nodo="<?php echo esc_attr($esp->id); ?>" data-nombre="<?php echo esc_attr($esp->nombre); ?>">
                                        Enviar propuesta
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No hay espacios disponibles para propuestas en este momento</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Sidebar -->
        <div class="artista-sidebar">
            <!-- Mis crowdfundings -->
            <div class="sidebar-card">
                <h4><span class="dashicons dashicons-heart"></span> Mis crowdfundings</h4>
                <?php if ($mis_crowdfundings): ?>
                    <div class="cf-mini-list">
                        <?php foreach ($mis_crowdfundings as $cf): ?>
                            <?php $porcentaje = $cf->objetivo_eur > 0 ? min(100, ($cf->recaudado_eur / $cf->objetivo_eur) * 100) : 0; ?>
                            <div class="cf-mini">
                                <strong><?php echo esc_html(wp_trim_words($cf->titulo, 5)); ?></strong>
                                <div class="cf-progress-mini">
                                    <div class="bar" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                                </div>
                                <div class="cf-stats-mini">
                                    <span><?php echo number_format($cf->recaudado_eur, 0); ?>€</span>
                                    <span class="estado-<?php echo esc_attr($cf->estado); ?>"><?php echo esc_html(ucfirst($cf->estado)); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">No tienes crowdfundings activos</p>
                <?php endif; ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=crowdfunding-dashboard&action=nuevo')); ?>" class="btn-nuevo-cf">
                    <span class="dashicons dashicons-plus-alt"></span> Crear campaña
                </a>
            </div>

            <!-- Agradecimientos recibidos -->
            <div class="sidebar-card">
                <h4><span class="dashicons dashicons-heart"></span> Agradecimientos recibidos</h4>
                <?php if ($agradecimientos): ?>
                    <div class="agr-list">
                        <?php foreach ($agradecimientos as $agr): ?>
                            <div class="agr-mini">
                                <span class="emoji"><?php echo esc_html($agr->emoji ?: '❤️'); ?></span>
                                <div>
                                    <strong><?php echo esc_html($agr->autor_nombre ?: 'Anónimo'); ?></strong>
                                    <p><?php echo esc_html(wp_trim_words($agr->mensaje, 8)); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">Sin agradecimientos aún</p>
                <?php endif; ?>
            </div>

            <!-- Tips -->
            <div class="sidebar-card tips">
                <h4><span class="dashicons dashicons-lightbulb"></span> Consejos</h4>
                <ul>
                    <li>Completa tu perfil con fotos y vídeos para destacar</li>
                    <li>Propón a espacios con alto índice de cooperación</li>
                    <li>Acepta SEMILLA y HOURS para más oportunidades</li>
                    <li>Responde rápido a las contraofertas</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal nueva propuesta -->
<div id="modal-propuesta" class="kulturaka-modal" style="display:none;">
    <div class="modal-content large">
        <div class="modal-header">
            <h3><span class="dashicons dashicons-email-alt"></span> Nueva propuesta</h3>
            <button type="button" class="modal-close" onclick="cerrarModalPropuesta()">&times;</button>
        </div>
        <form id="form-propuesta" method="post">
            <?php wp_nonce_field('kulturaka_propuesta', 'kulturaka_nonce'); ?>
            <input type="hidden" name="action" value="enviar_propuesta">
            <input type="hidden" name="artista_id" value="<?php echo esc_attr($artista->id); ?>">
            <input type="hidden" name="nodo_id" id="propuesta_nodo_id" value="">

            <div class="form-row">
                <div class="form-group">
                    <label>Espacio destino *</label>
                    <select name="nodo_id_select" id="nodo_select" required onchange="document.getElementById('propuesta_nodo_id').value = this.value">
                        <option value="">Seleccionar espacio...</option>
                        <?php foreach ($espacios_disponibles as $esp): ?>
                            <option value="<?php echo esc_attr($esp->id); ?>">
                                <?php echo esc_html($esp->nombre); ?> - <?php echo esc_html($esp->ciudad); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Título de la propuesta *</label>
                    <input type="text" name="titulo" required placeholder="Ej: Concierto acústico 'Raíces'">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de evento *</label>
                    <select name="tipo_evento" required>
                        <option value="concierto">Concierto</option>
                        <option value="teatro">Teatro</option>
                        <option value="danza">Danza</option>
                        <option value="exposicion">Exposición</option>
                        <option value="taller">Taller</option>
                        <option value="conferencia">Conferencia</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Duración (minutos) *</label>
                    <input type="number" name="duracion_minutos" value="90" min="15" max="480" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full">
                    <label>Descripción *</label>
                    <textarea name="descripcion" rows="4" required placeholder="Describe tu propuesta, qué ofreces, qué necesitas..."></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Modelo económico *</label>
                    <select name="modelo_economico" required>
                        <option value="taquilla">% de taquilla</option>
                        <option value="cache">Caché fijo</option>
                        <option value="mixto">Mixto (caché + %)</option>
                        <option value="gratuito">Gratuito</option>
                        <option value="a_voluntad">A voluntad</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Caché solicitado (€)</label>
                    <input type="number" name="cache_solicitado" min="0" step="50" placeholder="Opcional">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full">
                    <label>Necesidades técnicas</label>
                    <textarea name="necesidades_tecnicas" rows="3" placeholder="Sonido, iluminación, backline, etc."></textarea>
                </div>
            </div>

            <div class="form-row checkboxes">
                <label><input type="checkbox" name="acepta_semilla" value="1" checked> Acepto pago en SEMILLA</label>
                <label><input type="checkbox" name="acepta_hours" value="1" checked> Acepto pago en HOURS</label>
            </div>

            <div class="form-actions">
                <button type="button" class="button" onclick="cerrarModalPropuesta()">Cancelar</button>
                <button type="submit" class="button button-primary">Enviar propuesta</button>
            </div>
        </form>
    </div>
</div>

<style>
.vista-artista {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.artista-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    padding: 24px;
    border-radius: 16px;
    color: white;
}

.artista-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.artista-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,0.3);
}

.artista-avatar.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.2);
}

.artista-avatar.placeholder .dashicons {
    font-size: 36px;
    color: white;
}

.artista-datos h2 {
    margin: 0 0 4px;
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.artista-datos .verified-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: white;
    color: #8b5cf6;
    border-radius: 50%;
    font-size: 12px;
}

.artista-disciplinas {
    margin: 0 0 8px;
    opacity: 0.9;
}

.artista-badges {
    display: flex;
    gap: 8px;
}

.artista-badges .badge {
    padding: 4px 10px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    font-size: 12px;
}

.artista-actions {
    display: flex;
    gap: 12px;
}

.artista-actions .button {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
}

.artista-actions .button.button-primary {
    background: white;
    color: #8b5cf6;
    border-color: white;
}

/* Stats */
.artista-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.stat-card.success .stat-icon { background: #dcfce7; }
.stat-card.success .stat-icon .dashicons { color: #16a34a; }
.stat-card.pending .stat-icon { background: #fef3c7; }
.stat-card.pending .stat-icon .dashicons { color: #d97706; }

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: #f3f4f6;
    border-radius: 12px;
}

.stat-icon .dashicons {
    font-size: 24px;
    color: #6b7280;
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
}

.stat-label {
    font-size: 13px;
    color: #6b7280;
}

/* Content grid */
.artista-content-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
}

/* Sections */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-size: 18px;
    color: #374151;
}

.section-header h3 .dashicons {
    color: #8b5cf6;
}

.section-tabs {
    display: flex;
    gap: 4px;
}

.section-tabs .tab {
    padding: 6px 12px;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
}

.section-tabs .tab.active {
    background: #8b5cf6;
    border-color: #8b5cf6;
    color: white;
}

/* Propuestas */
.propuestas-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.propuesta-card {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    gap: 16px;
    align-items: center;
    background: white;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #8b5cf6;
}

.propuesta-card.estado-aceptada { border-left-color: #16a34a; }
.propuesta-card.estado-rechazada { border-left-color: #dc2626; }
.propuesta-card.estado-negociando { border-left-color: #f59e0b; }

.propuesta-espacio {
    display: flex;
    align-items: center;
    gap: 12px;
}

.espacio-thumb {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
}

.espacio-thumb.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
}

.espacio-thumb.placeholder .dashicons {
    color: #9ca3af;
}

.propuesta-espacio strong {
    display: block;
    color: #1f2937;
    font-size: 14px;
}

.propuesta-espacio .ciudad {
    font-size: 12px;
    color: #6b7280;
}

.propuesta-detalles h4 {
    margin: 0 0 6px;
    font-size: 14px;
    color: #1f2937;
}

.propuesta-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #9ca3af;
}

.propuesta-meta span {
    display: flex;
    align-items: center;
    gap: 3px;
}

.propuesta-meta .dashicons {
    font-size: 13px;
    width: 13px;
    height: 13px;
}

.propuesta-estado {
    text-align: center;
}

.estado-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.estado-badge.info { background: #dbeafe; color: #1e40af; }
.estado-badge.warning { background: #fef3c7; color: #92400e; }
.estado-badge.success { background: #dcfce7; color: #166534; }
.estado-badge.error { background: #fee2e2; color: #991b1b; }
.estado-badge.muted { background: #f3f4f6; color: #6b7280; }

.propuesta-fecha {
    display: block;
    font-size: 11px;
    color: #9ca3af;
    margin-top: 4px;
}

.has-response {
    font-size: 18px;
}

/* Gira */
.gira-timeline {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.gira-item {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.gira-fecha {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 50px;
    padding: 10px;
    background: #f5f3ff;
    border-radius: 8px;
}

.gira-fecha .dia {
    font-size: 22px;
    font-weight: 700;
    color: #8b5cf6;
    line-height: 1;
}

.gira-fecha .mes {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
}

.gira-linea {
    width: 2px;
    height: 100%;
    min-height: 50px;
    background: #e5e7eb;
}

.gira-info h4 {
    margin: 0 0 4px;
    font-size: 14px;
    color: #1f2937;
}

.gira-lugar {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}

.gira-lugar .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.gira-hora {
    font-size: 12px;
    color: #9ca3af;
}

/* Espacios grid */
.espacios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
}

.espacio-card-mini {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.esp-imagen {
    height: 80px;
    background-size: cover;
    background-position: center;
}

.esp-imagen.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
}

.esp-imagen.placeholder .dashicons {
    font-size: 28px;
    color: white;
    opacity: 0.5;
}

.esp-content {
    padding: 12px;
}

.esp-content h5 {
    margin: 0 0 4px;
    font-size: 13px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 4px;
}

.verified-mini {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 14px;
    height: 14px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    font-size: 9px;
}

.esp-ciudad {
    display: flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    color: #6b7280;
    margin: 0 0 6px;
}

.esp-ciudad .dashicons {
    font-size: 11px;
    width: 11px;
    height: 11px;
}

.esp-monedas {
    display: flex;
    gap: 4px;
    margin-bottom: 6px;
}

.esp-monedas span {
    font-size: 12px;
}

.esp-stats {
    display: flex;
    gap: 8px;
    font-size: 11px;
    color: #9ca3af;
    margin-bottom: 8px;
}

.btn-proponer {
    width: 100%;
    padding: 8px;
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
}

.btn-proponer:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139,92,246,0.4);
}

/* Sidebar */
.artista-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.sidebar-card {
    background: white;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.sidebar-card h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px;
    font-size: 14px;
    color: #374151;
}

.sidebar-card h4 .dashicons {
    color: #8b5cf6;
}

.cf-mini-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.cf-mini strong {
    display: block;
    font-size: 13px;
    color: #1f2937;
    margin-bottom: 6px;
}

.cf-progress-mini {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 6px;
}

.cf-progress-mini .bar {
    height: 100%;
    background: linear-gradient(90deg, #8b5cf6, #ec4899);
}

.cf-stats-mini {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #6b7280;
}

.btn-nuevo-cf {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 10px;
    margin-top: 12px;
    background: #f5f3ff;
    color: #8b5cf6;
    border: 1px dashed #c4b5fd;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
}

.agr-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.agr-mini {
    display: flex;
    gap: 8px;
    font-size: 12px;
}

.agr-mini .emoji {
    font-size: 18px;
}

.agr-mini strong {
    display: block;
    color: #374151;
}

.agr-mini p {
    margin: 0;
    color: #6b7280;
}

.sidebar-card.tips {
    background: #fffbeb;
    border: 1px solid #fde68a;
}

.sidebar-card.tips h4 .dashicons {
    color: #f59e0b;
}

.sidebar-card.tips ul {
    margin: 0;
    padding-left: 20px;
}

.sidebar-card.tips li {
    font-size: 12px;
    color: #78350f;
    margin-bottom: 6px;
}

.no-data {
    text-align: center;
    color: #9ca3af;
    font-size: 12px;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #f9fafb;
    border-radius: 12px;
}

.empty-state .dashicons {
    font-size: 40px;
    color: #d1d5db;
    margin-bottom: 12px;
}

.empty-state h4 {
    margin: 0 0 8px;
    color: #374151;
}

.empty-state p {
    margin: 0 0 16px;
    color: #6b7280;
}

.empty-state.small {
    padding: 24px;
}

/* Modal */
.kulturaka-modal .modal-content.large {
    max-width: 600px;
}

.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.form-row .form-group {
    flex: 1;
}

.form-row .form-group.full {
    flex: none;
    width: 100%;
}

.form-row.checkboxes {
    display: flex;
    gap: 20px;
}

.form-row.checkboxes label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
}

@media (max-width: 1024px) {
    .artista-content-grid {
        grid-template-columns: 1fr;
    }

    .artista-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .artista-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .artista-info {
        flex-direction: column;
    }

    .propuesta-card {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}
</style>

<script>
function abrirModalPropuesta(nodoId = '', nodoNombre = '') {
    document.getElementById('modal-propuesta').style.display = 'flex';
    if (nodoId) {
        document.getElementById('propuesta_nodo_id').value = nodoId;
        document.getElementById('nodo_select').value = nodoId;
    }
}

function cerrarModalPropuesta() {
    document.getElementById('modal-propuesta').style.display = 'none';
}

// Botones de proponer en espacios
document.querySelectorAll('.btn-proponer').forEach(btn => {
    btn.addEventListener('click', function() {
        const nodoId = this.dataset.nodo;
        const nodoNombre = this.dataset.nombre;
        abrirModalPropuesta(nodoId, nodoNombre);
    });
});

// Filtros de propuestas
document.querySelectorAll('.section-tabs .tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.section-tabs .tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        const filter = this.dataset.filter;
        document.querySelectorAll('.propuesta-card').forEach(card => {
            if (filter === 'all') {
                card.style.display = '';
            } else if (filter === 'pendiente') {
                card.style.display = ['enviada', 'vista', 'negociando'].includes(card.dataset.estado) ? '' : 'none';
            } else if (filter === 'aceptada') {
                card.style.display = card.dataset.estado === 'aceptada' ? '' : 'none';
            }
        });
    });
});

// Cerrar modal al hacer clic fuera
document.getElementById('modal-propuesta')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalPropuesta();
    }
});
</script>

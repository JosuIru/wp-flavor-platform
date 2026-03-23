<?php
/**
 * Vista Espacio - Kulturaka
 * Panel de gestión para espacios culturales: propuestas recibidas, programación, métricas
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
$tabla_nodos = $wpdb->prefix . 'flavor_kulturaka_nodos';
$tabla_propuestas = $wpdb->prefix . 'flavor_kulturaka_propuestas';
$tabla_eventos = $wpdb->prefix . 'flavor_eventos';
$tabla_metricas = $wpdb->prefix . 'flavor_kulturaka_metricas';
$tabla_agradecimientos = $wpdb->prefix . 'flavor_kulturaka_agradecimientos';

// Obtener el espacio gestionado por el usuario
$espacio = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla_nodos
     WHERE (admin_id = %d OR JSON_CONTAINS(COALESCE(admins, '[]'), %s))
     AND tipo = 'espacio'
     LIMIT 1",
    $current_user_id,
    json_encode((string)$current_user_id)
));

if (!$espacio) {
    echo '<div class="notice notice-error"><p>No se encontró el espacio asociado.</p></div>';
    return;
}

// Propuestas pendientes
$propuestas_pendientes = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, a.nombre_artistico, a.imagen as artista_imagen, u.display_name as artista_nombre
     FROM $tabla_propuestas p
     LEFT JOIN {$wpdb->prefix}flavor_socios_artistas a ON p.artista_id = a.id
     LEFT JOIN {$wpdb->users} u ON p.artista_user_id = u.ID
     WHERE p.nodo_id = %d AND p.estado IN ('enviada', 'negociando')
     ORDER BY p.created_at DESC",
    $espacio->id
));

// Eventos próximos del espacio
$eventos_proximos = [];
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos) {
    $eventos_proximos = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_eventos
         WHERE espacio_id = %d AND fecha_inicio >= NOW() AND estado = 'publicado'
         ORDER BY fecha_inicio ASC
         LIMIT 10",
        $espacio->entidad_id ?: $espacio->id
    )) ?: [];
}

// Métricas del espacio
$metricas = [
    'eventos_totales' => $espacio->eventos_realizados,
    'artistas_apoyados' => $espacio->artistas_apoyados,
    'fondos_recaudados' => $espacio->fondos_recaudados,
    'indice_cooperacion' => $espacio->indice_cooperacion,
    'propuestas_pendientes' => count($propuestas_pendientes),
];

// Agradecimientos recibidos
$agradecimientos_recibidos = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, u.display_name as autor_nombre
     FROM $tabla_agradecimientos a
     LEFT JOIN {$wpdb->users} u ON a.usuario_id = u.ID
     WHERE a.destinatario_tipo = 'espacio'
       AND a.destinatario_id = %d
       AND a.estado = 'activo'
     ORDER BY a.created_at DESC
     LIMIT 5",
    $espacio->id
));

// Historial de propuestas (todas)
$historial_propuestas = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, a.nombre_artistico
     FROM $tabla_propuestas p
     LEFT JOIN {$wpdb->prefix}flavor_socios_artistas a ON p.artista_id = a.id
     WHERE p.nodo_id = %d
     ORDER BY p.created_at DESC
     LIMIT 20",
    $espacio->id
));
?>

<div class="vista-espacio">
    <!-- Header del espacio -->
    <div class="espacio-header" style="--color-marca: <?php echo esc_attr($espacio->color_marca ?: '#ec4899'); ?>">
        <div class="espacio-info">
            <?php if (!empty($espacio->imagen_principal)): ?>
                <img src="<?php echo esc_url($espacio->imagen_principal); ?>" alt="" class="espacio-avatar">
            <?php else: ?>
                <div class="espacio-avatar placeholder">
                    <span class="dashicons dashicons-building"></span>
                </div>
            <?php endif; ?>

            <div class="espacio-datos">
                <h2>
                    <?php echo esc_html($espacio->nombre); ?>
                    <?php if ($espacio->verificado): ?>
                        <span class="verified-badge" title="Espacio verificado">✓</span>
                    <?php endif; ?>
                </h2>
                <p class="espacio-ubicacion">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($espacio->direccion); ?> - <?php echo esc_html($espacio->ciudad); ?>
                </p>
                <?php if ($espacio->aforo_maximo): ?>
                    <span class="espacio-aforo">Aforo: <?php echo number_format($espacio->aforo_maximo); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="espacio-actions">
            <a href="?page=flavor-kulturaka&action=editar-nodo&id=<?php echo $espacio->id; ?>" class="button">
                <span class="dashicons dashicons-edit"></span> Editar perfil
            </a>
            <a href="?page=flavor-kulturaka&action=configurar-nodo&id=<?php echo $espacio->id; ?>" class="button">
                <span class="dashicons dashicons-admin-generic"></span> Configuración
            </a>
        </div>
    </div>

    <!-- Stats rápidos -->
    <div class="espacio-stats-grid">
        <div class="stat-card">
            <span class="stat-icon"><span class="dashicons dashicons-calendar-alt"></span></span>
            <div class="stat-data">
                <span class="stat-number"><?php echo number_format($metricas['eventos_totales']); ?></span>
                <span class="stat-label">Eventos realizados</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><span class="dashicons dashicons-admin-users"></span></span>
            <div class="stat-data">
                <span class="stat-number"><?php echo number_format($metricas['artistas_apoyados']); ?></span>
                <span class="stat-label">Artistas apoyados</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><span class="dashicons dashicons-money-alt"></span></span>
            <div class="stat-data">
                <span class="stat-number"><?php echo number_format($metricas['fondos_recaudados'], 0, ',', '.'); ?>€</span>
                <span class="stat-label">Fondos recaudados</span>
            </div>
        </div>
        <div class="stat-card highlight">
            <span class="stat-icon"><span class="dashicons dashicons-email-alt"></span></span>
            <div class="stat-data">
                <span class="stat-number"><?php echo number_format($metricas['propuestas_pendientes']); ?></span>
                <span class="stat-label">Propuestas pendientes</span>
            </div>
        </div>
    </div>

    <div class="espacio-content-grid">
        <!-- Columna principal -->
        <div class="espacio-main">
            <!-- Propuestas pendientes -->
            <section class="section-propuestas">
                <div class="section-header">
                    <h3><span class="dashicons dashicons-email-alt"></span> Propuestas recibidas</h3>
                    <span class="badge"><?php echo count($propuestas_pendientes); ?> pendientes</span>
                </div>

                <?php if ($propuestas_pendientes): ?>
                    <div class="propuestas-list">
                        <?php foreach ($propuestas_pendientes as $prop): ?>
                            <div class="propuesta-card estado-<?php echo esc_attr($prop->estado); ?>">
                                <div class="propuesta-artista">
                                    <?php if (!empty($prop->artista_imagen)): ?>
                                        <img src="<?php echo esc_url($prop->artista_imagen); ?>" alt="" class="artista-thumb">
                                    <?php else: ?>
                                        <div class="artista-thumb placeholder">
                                            <span class="dashicons dashicons-admin-users"></span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo esc_html($prop->nombre_artistico ?: $prop->artista_nombre); ?></strong>
                                        <span class="propuesta-tipo"><?php echo esc_html(ucfirst($prop->tipo_evento)); ?></span>
                                    </div>
                                </div>

                                <div class="propuesta-detalles">
                                    <h4><?php echo esc_html($prop->titulo); ?></h4>
                                    <p><?php echo esc_html(wp_trim_words($prop->descripcion, 20)); ?></p>

                                    <div class="propuesta-meta">
                                        <?php if ($prop->fechas_propuestas): ?>
                                            <span><span class="dashicons dashicons-calendar"></span> Fechas propuestas disponibles</span>
                                        <?php endif; ?>
                                        <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($prop->duracion_minutos); ?> min</span>
                                        <span><span class="dashicons dashicons-money-alt"></span> <?php echo esc_html(ucfirst($prop->modelo_economico)); ?></span>
                                    </div>
                                </div>

                                <div class="propuesta-estado">
                                    <span class="estado-badge <?php echo esc_attr($prop->estado); ?>">
                                        <?php
                                        $estados_label = [
                                            'enviada' => 'Nueva',
                                            'negociando' => 'Negociando',
                                            'aceptada' => 'Aceptada',
                                            'rechazada' => 'Rechazada',
                                        ];
                                        echo esc_html($estados_label[$prop->estado] ?? $prop->estado);
                                        ?>
                                    </span>
                                    <span class="propuesta-fecha"><?php echo human_time_diff(strtotime($prop->created_at)); ?></span>
                                </div>

                                <div class="propuesta-actions">
                                    <button type="button" class="button button-primary btn-ver-propuesta" data-id="<?php echo esc_attr($prop->id); ?>">
                                        Ver propuesta
                                    </button>
                                    <?php if ($prop->estado === 'enviada'): ?>
                                        <button type="button" class="button btn-responder" data-id="<?php echo esc_attr($prop->id); ?>">
                                            Responder
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="dashicons dashicons-email"></span>
                        <p>No tienes propuestas pendientes</p>
                        <small>Cuando artistas envíen propuestas a tu espacio, aparecerán aquí</small>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Próximos eventos -->
            <section class="section-eventos">
                <div class="section-header">
                    <h3><span class="dashicons dashicons-calendar-alt"></span> Próximos eventos</h3>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=eventos-dashboard&action=nuevo')); ?>" class="button">
                        <span class="dashicons dashicons-plus-alt"></span> Nuevo evento
                    </a>
                </div>

                <?php if ($eventos_proximos): ?>
                    <div class="eventos-timeline">
                        <?php foreach ($eventos_proximos as $evento): ?>
                            <div class="evento-timeline-item">
                                <div class="evento-fecha-col">
                                    <span class="dia"><?php echo date('d', strtotime($evento->fecha_inicio)); ?></span>
                                    <span class="mes"><?php echo date_i18n('M', strtotime($evento->fecha_inicio)); ?></span>
                                    <span class="hora"><?php echo date('H:i', strtotime($evento->fecha_inicio)); ?></span>
                                </div>
                                <div class="evento-linea"></div>
                                <div class="evento-info-col">
                                    <h4><?php echo esc_html($evento->titulo); ?></h4>
                                    <?php if (!empty($evento->artista_nombre)): ?>
                                        <span class="evento-artista"><?php echo esc_html($evento->artista_nombre); ?></span>
                                    <?php endif; ?>
                                    <div class="evento-stats">
                                        <?php if (!empty($evento->inscripciones_count)): ?>
                                            <span><span class="dashicons dashicons-groups"></span> <?php echo $evento->inscripciones_count; ?> inscritos</span>
                                        <?php endif; ?>
                                        <?php if (!empty($evento->precio)): ?>
                                            <span><span class="dashicons dashicons-tickets-alt"></span> <?php echo $evento->precio; ?>€</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state small">
                        <p>No hay eventos programados</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Sidebar -->
        <div class="espacio-sidebar">
            <!-- Distribución de ingresos configurada -->
            <div class="sidebar-card">
                <h4><span class="dashicons dashicons-chart-pie"></span> Distribución de ingresos</h4>
                <div class="distribucion-chart">
                    <div class="dist-item">
                        <span class="dist-color" style="background: #ec4899"></span>
                        <span class="dist-label">Artista</span>
                        <span class="dist-value"><?php echo esc_html($espacio->distribucion_artista); ?>%</span>
                    </div>
                    <div class="dist-item">
                        <span class="dist-color" style="background: #8b5cf6"></span>
                        <span class="dist-label">Espacio</span>
                        <span class="dist-value"><?php echo esc_html($espacio->distribucion_espacio); ?>%</span>
                    </div>
                    <div class="dist-item">
                        <span class="dist-color" style="background: #3b82f6"></span>
                        <span class="dist-label">Comunidad</span>
                        <span class="dist-value"><?php echo esc_html($espacio->distribucion_comunidad); ?>%</span>
                    </div>
                    <div class="dist-item">
                        <span class="dist-color" style="background: #10b981"></span>
                        <span class="dist-label">Plataforma</span>
                        <span class="dist-value"><?php echo esc_html($espacio->distribucion_plataforma); ?>%</span>
                    </div>
                    <div class="dist-item">
                        <span class="dist-color" style="background: #f59e0b"></span>
                        <span class="dist-label">Fondo emergencia</span>
                        <span class="dist-value"><?php echo esc_html($espacio->distribucion_emergencia); ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Monedas aceptadas -->
            <div class="sidebar-card">
                <h4><span class="dashicons dashicons-money-alt"></span> Monedas aceptadas</h4>
                <div class="monedas-list">
                    <?php if ($espacio->acepta_eur): ?>
                        <span class="moneda-badge eur">€ Euros</span>
                    <?php endif; ?>
                    <?php if ($espacio->acepta_semilla): ?>
                        <span class="moneda-badge semilla">🌱 SEMILLA</span>
                    <?php endif; ?>
                    <?php if ($espacio->acepta_hours): ?>
                        <span class="moneda-badge hours">⏰ HOURS</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Agradecimientos recibidos -->
            <div class="sidebar-card">
                <h4><span class="dashicons dashicons-heart"></span> Últimos agradecimientos</h4>
                <?php if ($agradecimientos_recibidos): ?>
                    <div class="agradecimientos-mini">
                        <?php foreach ($agradecimientos_recibidos as $agr): ?>
                            <div class="agr-mini">
                                <span class="emoji"><?php echo esc_html($agr->emoji ?: '❤️'); ?></span>
                                <div>
                                    <strong><?php echo esc_html($agr->autor_nombre ?: 'Anónimo'); ?></strong>
                                    <p><?php echo esc_html(wp_trim_words($agr->mensaje, 10)); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">Sin agradecimientos aún</p>
                <?php endif; ?>
            </div>

            <!-- Índice de cooperación -->
            <div class="sidebar-card highlight">
                <h4><span class="dashicons dashicons-star-filled"></span> Índice de cooperación</h4>
                <div class="indice-display">
                    <span class="indice-valor"><?php echo number_format($espacio->indice_cooperacion, 1); ?></span>
                    <span class="indice-max">/10</span>
                </div>
                <div class="indice-bar">
                    <div class="indice-fill" style="width: <?php echo esc_attr($espacio->indice_cooperacion * 10); ?>%"></div>
                </div>
                <p class="indice-desc">Basado en propuestas aceptadas, distribución justa y feedback de artistas</p>
            </div>
        </div>
    </div>
</div>

<style>
.vista-espacio {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.espacio-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, var(--color-marca), color-mix(in srgb, var(--color-marca) 70%, #000));
    padding: 24px;
    border-radius: 16px;
    color: white;
}

.espacio-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.espacio-avatar {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,0.3);
}

.espacio-avatar.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.2);
}

.espacio-avatar.placeholder .dashicons {
    font-size: 36px;
    color: white;
}

.espacio-datos h2 {
    margin: 0 0 8px;
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.espacio-datos .verified-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: white;
    color: var(--color-marca);
    border-radius: 50%;
    font-size: 12px;
}

.espacio-ubicacion {
    margin: 0 0 8px;
    display: flex;
    align-items: center;
    gap: 6px;
    opacity: 0.9;
}

.espacio-aforo {
    font-size: 13px;
    padding: 4px 10px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
}

.espacio-actions {
    display: flex;
    gap: 12px;
}

.espacio-actions .button {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
}

.espacio-actions .button:hover {
    background: rgba(255,255,255,0.3);
    color: white;
}

/* Stats grid */
.espacio-stats-grid {
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

.stat-card.highlight {
    background: linear-gradient(135deg, #fdf2f8, #faf5ff);
    border: 2px solid #ec4899;
}

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

.stat-card.highlight .stat-icon {
    background: #ec4899;
}

.stat-card.highlight .stat-icon .dashicons {
    color: white;
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
.espacio-content-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
}

/* Propuestas */
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

.section-header .badge {
    padding: 4px 12px;
    background: #fef3c7;
    color: #92400e;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.propuestas-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.propuesta-card {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    gap: 20px;
    align-items: center;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #ec4899;
}

.propuesta-card.estado-negociando {
    border-left-color: #f59e0b;
}

.propuesta-artista {
    display: flex;
    align-items: center;
    gap: 12px;
}

.artista-thumb {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.artista-thumb.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
}

.artista-thumb.placeholder .dashicons {
    color: #9ca3af;
}

.propuesta-artista strong {
    display: block;
    color: #1f2937;
}

.propuesta-tipo {
    font-size: 12px;
    color: #6b7280;
}

.propuesta-detalles h4 {
    margin: 0 0 4px;
    font-size: 15px;
    color: #1f2937;
}

.propuesta-detalles p {
    margin: 0 0 8px;
    font-size: 13px;
    color: #6b7280;
}

.propuesta-meta {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #9ca3af;
}

.propuesta-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.propuesta-meta .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.propuesta-estado {
    text-align: center;
}

.estado-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.estado-badge.enviada {
    background: #dbeafe;
    color: #1e40af;
}

.estado-badge.negociando {
    background: #fef3c7;
    color: #92400e;
}

.propuesta-fecha {
    display: block;
    font-size: 11px;
    color: #9ca3af;
    margin-top: 4px;
}

.propuesta-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Eventos timeline */
.eventos-timeline {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.evento-timeline-item {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.evento-fecha-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 60px;
    padding: 12px;
    background: #fdf2f8;
    border-radius: 8px;
}

.evento-fecha-col .dia {
    font-size: 24px;
    font-weight: 700;
    color: #ec4899;
    line-height: 1;
}

.evento-fecha-col .mes {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
}

.evento-fecha-col .hora {
    font-size: 11px;
    color: #6b7280;
    margin-top: 4px;
}

.evento-linea {
    width: 2px;
    height: 100%;
    min-height: 60px;
    background: #e5e7eb;
    margin: 0 8px;
}

.evento-info-col h4 {
    margin: 0 0 4px;
    font-size: 15px;
    color: #1f2937;
}

.evento-artista {
    font-size: 13px;
    color: #6b7280;
}

.evento-stats {
    display: flex;
    gap: 12px;
    margin-top: 8px;
    font-size: 12px;
    color: #9ca3af;
}

.evento-stats span {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Sidebar */
.espacio-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.sidebar-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.sidebar-card.highlight {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
}

.sidebar-card h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 16px;
    font-size: 14px;
    color: #374151;
}

.sidebar-card h4 .dashicons {
    color: #ec4899;
}

/* Distribución */
.distribucion-chart {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.dist-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.dist-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.dist-label {
    flex: 1;
    color: #6b7280;
}

.dist-value {
    font-weight: 600;
    color: #374151;
}

/* Monedas */
.monedas-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.moneda-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.moneda-badge.eur {
    background: #dbeafe;
    color: #1e40af;
}

.moneda-badge.semilla {
    background: #dcfce7;
    color: #166534;
}

.moneda-badge.hours {
    background: #fef3c7;
    color: #92400e;
}

/* Agradecimientos mini */
.agradecimientos-mini {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.agr-mini {
    display: flex;
    gap: 10px;
    font-size: 13px;
}

.agr-mini .emoji {
    font-size: 20px;
}

.agr-mini strong {
    display: block;
    color: #374151;
}

.agr-mini p {
    margin: 0;
    color: #6b7280;
}

.no-data {
    text-align: center;
    color: #9ca3af;
    font-size: 13px;
}

/* Índice */
.indice-display {
    text-align: center;
    margin-bottom: 12px;
}

.indice-valor {
    font-size: 48px;
    font-weight: 700;
    color: #92400e;
}

.indice-max {
    font-size: 20px;
    color: #9ca3af;
}

.indice-bar {
    height: 8px;
    background: rgba(0,0,0,0.1);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 12px;
}

.indice-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #ec4899);
    border-radius: 4px;
}

.indice-desc {
    font-size: 11px;
    color: #78350f;
    text-align: center;
    margin: 0;
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

.empty-state p {
    margin: 0;
    color: #6b7280;
}

.empty-state small {
    display: block;
    margin-top: 8px;
    color: #9ca3af;
}

.empty-state.small {
    padding: 24px;
}

@media (max-width: 1024px) {
    .espacio-content-grid {
        grid-template-columns: 1fr;
    }

    .espacio-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .espacio-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .espacio-info {
        flex-direction: column;
    }

    .propuesta-card {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}
</style>

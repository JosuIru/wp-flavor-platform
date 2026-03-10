<?php
/**
 * Vista Comunidad - Kulturaka
 * Perspectiva del ciudadano/asistente: eventos cercanos, crowdfundings activos, muro de agradecimientos
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
$tabla_agradecimientos = $wpdb->prefix . 'flavor_kulturaka_agradecimientos';
$tabla_eventos = $wpdb->prefix . 'flavor_eventos';
$tabla_crowdfunding = $wpdb->prefix . 'flavor_crowdfunding_proyectos';

// Obtener eventos próximos
$eventos_proximos = [];
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos) {
    $eventos_proximos = $wpdb->get_results(
        "SELECT e.*, n.nombre as espacio_nombre, n.ciudad as espacio_ciudad
         FROM $tabla_eventos e
         LEFT JOIN $tabla_nodos n ON e.espacio_id = n.entidad_id
         WHERE e.estado = 'publicado'
           AND e.fecha_inicio >= NOW()
         ORDER BY e.fecha_inicio ASC
         LIMIT 6"
    ) ?: [];
}

// Obtener crowdfundings activos
$crowdfundings_activos = [];
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_crowdfunding'") === $tabla_crowdfunding) {
    $crowdfundings_activos = $wpdb->get_results(
        "SELECT *
         FROM $tabla_crowdfunding
         WHERE estado = 'activo'
           AND (fecha_fin IS NULL OR fecha_fin >= NOW())
         ORDER BY destacado DESC, created_at DESC
         LIMIT 4"
    ) ?: [];
}

// Espacios destacados
$espacios_destacados = $wpdb->get_results(
    "SELECT * FROM $tabla_nodos
     WHERE tipo = 'espacio' AND estado = 'activo'
     ORDER BY destacado DESC, eventos_realizados DESC
     LIMIT 6"
) ?: [];

// Muro de agradecimientos completo
$agradecimientos = $wpdb->get_results(
    "SELECT a.*, u.display_name as autor_nombre
     FROM $tabla_agradecimientos a
     LEFT JOIN {$wpdb->users} u ON a.usuario_id = u.ID
     WHERE a.estado = 'activo' AND a.publico = 1
     ORDER BY a.destacado DESC, a.created_at DESC
     LIMIT 20"
) ?: [];

// Métricas de impacto de la red
$metricas_red = [
    'eventos_mes' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla_eventos WHERE MONTH(fecha_inicio) = MONTH(NOW()) AND YEAR(fecha_inicio) = YEAR(NOW())") ?: 0,
    'fondos_recaudados' => $wpdb->get_var("SELECT SUM(recaudado_eur) FROM $tabla_crowdfunding WHERE estado IN ('activo', 'exitoso')") ?: 0,
    'artistas_apoyados' => $wpdb->get_var("SELECT SUM(artistas_apoyados) FROM $tabla_nodos") ?: 0,
];
?>

<div class="vista-comunidad">
    <!-- Banner de bienvenida -->
    <div class="comunidad-welcome">
        <div class="welcome-content">
            <h2>Bienvenido a la red cultural</h2>
            <p>Descubre eventos, apoya proyectos creativos y conecta con tu comunidad cultural local.</p>
        </div>
        <div class="welcome-stats">
            <div class="stat">
                <span class="number"><?php echo number_format($metricas_red['eventos_mes']); ?></span>
                <span class="label">eventos este mes</span>
            </div>
            <div class="stat">
                <span class="number"><?php echo number_format($metricas_red['fondos_recaudados'], 0, ',', '.'); ?>€</span>
                <span class="label">recaudados</span>
            </div>
            <div class="stat">
                <span class="number"><?php echo number_format($metricas_red['artistas_apoyados']); ?></span>
                <span class="label">artistas apoyados</span>
            </div>
        </div>
    </div>

    <!-- Sección: Próximos eventos -->
    <section class="section-eventos">
        <div class="section-header">
            <h3><span class="dashicons dashicons-calendar-alt"></span> Próximos eventos</h3>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-eventos')); ?>" class="ver-mas">Ver todos →</a>
        </div>

        <?php if ($eventos_proximos): ?>
            <div class="eventos-grid">
                <?php foreach ($eventos_proximos as $evento): ?>
                    <div class="evento-card">
                        <?php if (!empty($evento->imagen)): ?>
                            <div class="evento-imagen" style="background-image: url('<?php echo esc_url($evento->imagen); ?>')"></div>
                        <?php else: ?>
                            <div class="evento-imagen placeholder">
                                <span class="dashicons dashicons-calendar"></span>
                            </div>
                        <?php endif; ?>

                        <div class="evento-content">
                            <div class="evento-fecha">
                                <span class="dia"><?php echo date('d', strtotime($evento->fecha_inicio)); ?></span>
                                <span class="mes"><?php echo date_i18n('M', strtotime($evento->fecha_inicio)); ?></span>
                            </div>
                            <div class="evento-info">
                                <h4><?php echo esc_html($evento->titulo); ?></h4>
                                <?php if ($evento->espacio_nombre): ?>
                                    <p class="evento-lugar">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($evento->espacio_nombre); ?>
                                        <?php if ($evento->espacio_ciudad): ?>
                                            - <?php echo esc_html($evento->espacio_ciudad); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($evento->precio)): ?>
                                    <span class="evento-precio"><?php echo esc_html($evento->precio); ?>€</span>
                                <?php else: ?>
                                    <span class="evento-precio gratuito">Gratuito</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p>No hay eventos próximos programados</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-eventos&action=nuevo')); ?>" class="button">Proponer un evento</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Sección: Crowdfundings activos -->
    <section class="section-crowdfunding">
        <div class="section-header">
            <h3><span class="dashicons dashicons-heart"></span> Proyectos que necesitan tu apoyo</h3>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-crowdfunding')); ?>" class="ver-mas">Ver todos →</a>
        </div>

        <?php if ($crowdfundings_activos): ?>
            <div class="crowdfunding-grid">
                <?php foreach ($crowdfundings_activos as $proyecto): ?>
                    <?php
                    $porcentaje = $proyecto->objetivo_eur > 0
                        ? min(100, ($proyecto->recaudado_eur / $proyecto->objetivo_eur) * 100)
                        : 0;
                    $dias_restantes = $proyecto->fecha_fin
                        ? max(0, ceil((strtotime($proyecto->fecha_fin) - time()) / 86400))
                        : null;
                    ?>
                    <div class="crowdfunding-card <?php echo $proyecto->destacado ? 'destacado' : ''; ?>">
                        <?php if ($proyecto->destacado): ?>
                            <span class="badge-destacado">⭐ Destacado</span>
                        <?php endif; ?>

                        <?php if (!empty($proyecto->imagen_principal)): ?>
                            <div class="cf-imagen" style="background-image: url('<?php echo esc_url($proyecto->imagen_principal); ?>')"></div>
                        <?php else: ?>
                            <div class="cf-imagen placeholder">
                                <span class="dashicons dashicons-heart"></span>
                            </div>
                        <?php endif; ?>

                        <div class="cf-content">
                            <span class="cf-categoria"><?php echo esc_html($proyecto->categoria ?: $proyecto->tipo); ?></span>
                            <h4><?php echo esc_html($proyecto->titulo); ?></h4>
                            <p><?php echo esc_html(wp_trim_words($proyecto->descripcion, 15)); ?></p>

                            <div class="cf-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                                </div>
                                <div class="progress-stats">
                                    <span class="recaudado"><?php echo number_format($proyecto->recaudado_eur, 0, ',', '.'); ?>€</span>
                                    <span class="objetivo">de <?php echo number_format($proyecto->objetivo_eur, 0, ',', '.'); ?>€</span>
                                </div>
                            </div>

                            <div class="cf-meta">
                                <span class="aportantes">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php echo number_format($proyecto->aportantes_count); ?> mecenas
                                </span>
                                <?php if ($dias_restantes !== null): ?>
                                    <span class="dias">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo $dias_restantes; ?> días
                                    </span>
                                <?php endif; ?>
                            </div>

                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-crowdfunding&proyecto=' . $proyecto->id)); ?>" class="btn-apoyar">
                                Apoyar proyecto
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="dashicons dashicons-heart"></span>
                <p>No hay proyectos de crowdfunding activos</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Sección: Espacios destacados -->
    <section class="section-espacios">
        <div class="section-header">
            <h3><span class="dashicons dashicons-building"></span> Espacios culturales</h3>
            <a href="?page=flavor-kulturaka&vista=red" class="ver-mas">Ver mapa →</a>
        </div>

        <?php if ($espacios_destacados): ?>
            <div class="espacios-grid">
                <?php foreach ($espacios_destacados as $espacio): ?>
                    <div class="espacio-card">
                        <?php if (!empty($espacio->imagen_principal)): ?>
                            <div class="espacio-imagen" style="background-image: url('<?php echo esc_url($espacio->imagen_principal); ?>')"></div>
                        <?php else: ?>
                            <div class="espacio-imagen placeholder" style="background-color: <?php echo esc_attr($espacio->color_marca ?: '#ec4899'); ?>">
                                <span class="dashicons dashicons-building"></span>
                            </div>
                        <?php endif; ?>

                        <div class="espacio-content">
                            <h4>
                                <?php echo esc_html($espacio->nombre); ?>
                                <?php if ($espacio->verificado): ?>
                                    <span class="verified-badge" title="Verificado">✓</span>
                                <?php endif; ?>
                            </h4>
                            <p class="espacio-ciudad">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($espacio->ciudad ?: 'Sin ubicación'); ?>
                            </p>
                            <div class="espacio-stats">
                                <span><?php echo number_format($espacio->eventos_realizados); ?> eventos</span>
                                <span><?php echo number_format($espacio->artistas_apoyados); ?> artistas</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="dashicons dashicons-building"></span>
                <p>No hay espacios registrados aún</p>
                <a href="?page=flavor-kulturaka&action=crear-nodo" class="button">Registrar espacio</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Sección: Muro de agradecimientos -->
    <section class="section-agradecimientos" id="muro-agradecimientos">
        <div class="section-header">
            <h3><span class="dashicons dashicons-heart"></span> Muro de agradecimientos</h3>
            <button type="button" class="button button-primary" onclick="abrirModalAgradecimiento()">
                <span class="dashicons dashicons-plus-alt"></span> Enviar agradecimiento
            </button>
        </div>

        <?php if ($agradecimientos): ?>
            <div class="agradecimientos-masonry">
                <?php foreach ($agradecimientos as $agr): ?>
                    <div class="agradecimiento-card <?php echo $agr->destacado ? 'destacado' : ''; ?>">
                        <div class="agr-header">
                            <span class="agr-emoji"><?php echo esc_html($agr->emoji ?: '❤️'); ?></span>
                            <div class="agr-autor">
                                <strong><?php echo esc_html($agr->autor_nombre ?: 'Anónimo'); ?></strong>
                                <span class="agr-tipo"><?php echo esc_html(ucfirst($agr->tipo)); ?></span>
                            </div>
                        </div>

                        <?php if ($agr->destinatario_nombre): ?>
                            <div class="agr-destinatario">
                                → <?php echo esc_html($agr->destinatario_nombre); ?>
                            </div>
                        <?php endif; ?>

                        <p class="agr-mensaje"><?php echo esc_html($agr->mensaje); ?></p>

                        <?php if (!empty($agr->imagen)): ?>
                            <div class="agr-imagen">
                                <img src="<?php echo esc_url($agr->imagen); ?>" alt="">
                            </div>
                        <?php endif; ?>

                        <div class="agr-footer">
                            <span class="agr-time"><?php echo human_time_diff(strtotime($agr->created_at)); ?></span>
                            <button class="btn-like" data-id="<?php echo esc_attr($agr->id); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <span class="count"><?php echo $agr->likes_count; ?></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state agradecimientos-empty">
                <span class="emoji-big">💜</span>
                <h4>El muro está vacío</h4>
                <p>Sé el primero en enviar un agradecimiento a alguien de la comunidad</p>
                <button type="button" class="button button-primary" onclick="abrirModalAgradecimiento()">
                    Enviar agradecimiento
                </button>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
.vista-comunidad {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

/* Welcome banner */
.comunidad-welcome {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #f0abfc 0%, #c4b5fd 100%);
    padding: 32px;
    border-radius: 16px;
    color: #581c87;
}

.welcome-content h2 {
    margin: 0 0 8px;
    font-size: 24px;
}

.welcome-content p {
    margin: 0;
    opacity: 0.8;
}

.welcome-stats {
    display: flex;
    gap: 32px;
}

.welcome-stats .stat {
    text-align: center;
}

.welcome-stats .number {
    display: block;
    font-size: 28px;
    font-weight: 700;
}

.welcome-stats .label {
    font-size: 12px;
    opacity: 0.8;
}

/* Section headers */
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
    color: #ec4899;
}

.ver-mas {
    color: #ec4899;
    text-decoration: none;
    font-weight: 500;
}

/* Eventos grid */
.eventos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.evento-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.evento-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.evento-imagen {
    height: 140px;
    background-size: cover;
    background-position: center;
}

.evento-imagen.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
}

.evento-imagen.placeholder .dashicons {
    font-size: 40px;
    color: white;
    opacity: 0.5;
}

.evento-content {
    display: flex;
    padding: 16px;
    gap: 16px;
}

.evento-fecha {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 12px;
    background: #fdf2f8;
    border-radius: 8px;
    min-width: 50px;
}

.evento-fecha .dia {
    font-size: 24px;
    font-weight: 700;
    color: #ec4899;
    line-height: 1;
}

.evento-fecha .mes {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
}

.evento-info h4 {
    margin: 0 0 8px;
    font-size: 15px;
    color: #1f2937;
}

.evento-lugar {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 8px;
}

.evento-lugar .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.evento-precio {
    display: inline-block;
    padding: 4px 10px;
    background: #ec4899;
    color: white;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.evento-precio.gratuito {
    background: #10b981;
}

/* Crowdfunding grid */
.crowdfunding-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.crowdfunding-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    position: relative;
}

.crowdfunding-card.destacado {
    border: 2px solid #ec4899;
}

.badge-destacado {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #fef3c7;
    color: #92400e;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    z-index: 1;
}

.cf-imagen {
    height: 160px;
    background-size: cover;
    background-position: center;
}

.cf-imagen.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f472b6, #a78bfa);
}

.cf-imagen.placeholder .dashicons {
    font-size: 48px;
    color: white;
    opacity: 0.5;
}

.cf-content {
    padding: 16px;
}

.cf-categoria {
    display: inline-block;
    padding: 3px 8px;
    background: #fdf2f8;
    color: #ec4899;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.cf-content h4 {
    margin: 0 0 8px;
    font-size: 16px;
    color: #1f2937;
}

.cf-content > p {
    margin: 0 0 12px;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
}

.cf-progress {
    margin-bottom: 12px;
}

.progress-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #ec4899, #8b5cf6);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}

.progress-stats .recaudado {
    font-weight: 700;
    color: #ec4899;
}

.progress-stats .objetivo {
    color: #9ca3af;
}

.cf-meta {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 12px;
}

.cf-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.cf-meta .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.btn-apoyar {
    display: block;
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
}

.btn-apoyar:hover {
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(236,72,153,0.4);
}

/* Espacios grid */
.espacios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.espacio-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.espacio-imagen {
    height: 100px;
    background-size: cover;
    background-position: center;
}

.espacio-imagen.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
}

.espacio-imagen.placeholder .dashicons {
    font-size: 32px;
    color: white;
    opacity: 0.5;
}

.espacio-content {
    padding: 12px;
}

.espacio-content h4 {
    margin: 0 0 4px;
    font-size: 14px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 6px;
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    font-size: 10px;
}

.espacio-ciudad {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #6b7280;
    margin: 0 0 8px;
}

.espacio-ciudad .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.espacio-stats {
    display: flex;
    gap: 12px;
    font-size: 11px;
    color: #9ca3af;
}

/* Muro de agradecimientos */
.agradecimientos-masonry {
    column-count: 3;
    column-gap: 16px;
}

.agradecimiento-card {
    break-inside: avoid;
    background: white;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #ec4899;
}

.agradecimiento-card.destacado {
    background: linear-gradient(135deg, #fdf2f8, #faf5ff);
    border-left-color: #8b5cf6;
}

.agr-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.agr-emoji {
    font-size: 28px;
}

.agr-autor strong {
    display: block;
    color: #1f2937;
    font-size: 14px;
}

.agr-tipo {
    font-size: 11px;
    color: #9ca3af;
}

.agr-destinatario {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 8px;
    padding-left: 40px;
}

.agr-mensaje {
    margin: 0 0 12px;
    font-size: 14px;
    color: #374151;
    line-height: 1.5;
}

.agr-imagen img {
    width: 100%;
    border-radius: 8px;
    margin-bottom: 12px;
}

.agr-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.agr-time {
    font-size: 11px;
    color: #9ca3af;
}

.btn-like {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    cursor: pointer;
    color: #9ca3af;
    font-size: 12px;
}

.btn-like:hover {
    background: #fdf2f8;
    border-color: #ec4899;
    color: #ec4899;
}

.btn-like .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Empty states */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    background: #f9fafb;
    border-radius: 12px;
}

.empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #d1d5db;
    margin-bottom: 16px;
}

.empty-state p {
    color: #6b7280;
    margin: 0 0 16px;
}

.agradecimientos-empty .emoji-big {
    font-size: 64px;
    display: block;
    margin-bottom: 16px;
}

.agradecimientos-empty h4 {
    margin: 0 0 8px;
    color: #374151;
}

@media (max-width: 1024px) {
    .agradecimientos-masonry {
        column-count: 2;
    }
}

@media (max-width: 768px) {
    .comunidad-welcome {
        flex-direction: column;
        gap: 24px;
        text-align: center;
    }

    .welcome-stats {
        width: 100%;
        justify-content: center;
    }

    .agradecimientos-masonry {
        column-count: 1;
    }
}
</style>

<?php
/**
 * Template: Próximos Talleres
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Talleres
 */

if (!defined('ABSPATH')) {
    exit;
}

$talleres = isset($talleres) ? $talleres : [];
?>
<div class="flavor-talleres-proximos">
    <h2 class="flavor-talleres-titulo"><?php esc_html_e('Próximos Talleres', 'flavor-chat-ia'); ?></h2>

    <?php if (empty($talleres)): ?>
        <div class="flavor-talleres-vacio">
            <span class="dashicons dashicons-calendar-alt"></span>
            <p><?php esc_html_e('No hay talleres programados próximamente.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="flavor-talleres-grid">
            <?php foreach ($talleres as $taller):
                $fecha_inicio = isset($taller->fecha_inicio) ? strtotime($taller->fecha_inicio) : 0;
                $plazas_disponibles = isset($taller->plazas_maximas) && isset($taller->inscritos)
                    ? $taller->plazas_maximas - $taller->inscritos
                    : 0;
            ?>
            <article class="flavor-taller-card">
                <?php if (!empty($taller->imagen_url)): ?>
                <div class="flavor-taller-imagen">
                    <img src="<?php echo esc_url($taller->imagen_url); ?>" alt="<?php echo esc_attr($taller->titulo ?? ''); ?>" loading="lazy">
                </div>
                <?php endif; ?>

                <div class="flavor-taller-contenido">
                    <div class="flavor-taller-fecha">
                        <span class="flavor-taller-dia"><?php echo esc_html(date_i18n('d', $fecha_inicio)); ?></span>
                        <span class="flavor-taller-mes"><?php echo esc_html(date_i18n('M', $fecha_inicio)); ?></span>
                    </div>

                    <div class="flavor-taller-info">
                        <?php if (!empty($taller->categoria)): ?>
                        <span class="flavor-taller-categoria"><?php echo esc_html($taller->categoria); ?></span>
                        <?php endif; ?>

                        <h3 class="flavor-taller-nombre">
                            <a href="<?php echo esc_url(add_query_arg('taller_id', $taller->id, home_url('/taller/'))); ?>">
                                <?php echo esc_html($taller->titulo ?? $taller->nombre ?? ''); ?>
                            </a>
                        </h3>

                        <p class="flavor-taller-descripcion">
                            <?php echo esc_html(wp_trim_words($taller->descripcion ?? '', 20, '...')); ?>
                        </p>

                        <div class="flavor-taller-meta">
                            <span class="flavor-meta-item">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html(date_i18n('H:i', $fecha_inicio)); ?>
                            </span>

                            <?php if (!empty($taller->ubicacion)): ?>
                            <span class="flavor-meta-item">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($taller->ubicacion); ?>
                            </span>
                            <?php endif; ?>

                            <?php if ($plazas_disponibles > 0): ?>
                            <span class="flavor-meta-item flavor-plazas-disponibles">
                                <span class="dashicons dashicons-groups"></span>
                                <?php printf(esc_html__('%d plazas', 'flavor-chat-ia'), $plazas_disponibles); ?>
                            </span>
                            <?php elseif (isset($taller->plazas_maximas)): ?>
                            <span class="flavor-meta-item flavor-plazas-completo">
                                <?php esc_html_e('Completo', 'flavor-chat-ia'); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-taller-acciones">
                        <a href="<?php echo esc_url(add_query_arg('taller_id', $taller->id, home_url('/taller/'))); ?>" class="flavor-btn flavor-btn-primario">
                            <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.flavor-talleres-proximos {
    padding: 20px 0;
}
.flavor-talleres-titulo {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 20px;
    color: #1e293b;
}
.flavor-talleres-vacio {
    text-align: center;
    padding: 40px 20px;
    background: #f8fafc;
    border-radius: 12px;
    color: #64748b;
}
.flavor-talleres-vacio .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
}
.flavor-talleres-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.flavor-taller-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-taller-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}
.flavor-taller-imagen {
    height: 160px;
    overflow: hidden;
}
.flavor-taller-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.flavor-taller-contenido {
    padding: 16px;
    display: flex;
    gap: 16px;
}
.flavor-taller-fecha {
    flex-shrink: 0;
    width: 50px;
    text-align: center;
    padding: 8px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border-radius: 8px;
}
.flavor-taller-dia {
    display: block;
    font-size: 20px;
    font-weight: 700;
    line-height: 1;
}
.flavor-taller-mes {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    margin-top: 4px;
}
.flavor-taller-info {
    flex: 1;
}
.flavor-taller-categoria {
    display: inline-block;
    padding: 2px 8px;
    background: #e0e7ff;
    color: #4f46e5;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.flavor-taller-nombre {
    margin: 0 0 8px;
    font-size: 16px;
    font-weight: 600;
}
.flavor-taller-nombre a {
    color: #1e293b;
    text-decoration: none;
}
.flavor-taller-nombre a:hover {
    color: #3b82f6;
}
.flavor-taller-descripcion {
    margin: 0 0 12px;
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}
.flavor-taller-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: #64748b;
}
.flavor-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}
.flavor-meta-item .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}
.flavor-plazas-disponibles {
    color: #22c55e;
}
.flavor-plazas-completo {
    color: #ef4444;
    font-weight: 500;
}
.flavor-taller-acciones {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}
.flavor-btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s;
}
.flavor-btn-primario {
    background: #3b82f6;
    color: white;
}
.flavor-btn-primario:hover {
    background: #2563eb;
    color: white;
}
</style>

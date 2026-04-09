<?php
/**
 * Template: Mi participación en incidencias
 * @var object $participacion Datos de participación del usuario
 * @var array $logros Logros desbloqueados
 */
if (!defined('ABSPATH')) exit;

$usuario = wp_get_current_user();
$nivel = $participacion->nivel;
$puntos = $participacion->puntos_totales;

// Determinar título del nivel
$titulos_nivel = [
    1 => __('Vecino', FLAVOR_PLATFORM_TEXT_DOMAIN),
    2 => __('Observador', FLAVOR_PLATFORM_TEXT_DOMAIN),
    3 => __('Reportero', FLAVOR_PLATFORM_TEXT_DOMAIN),
    4 => __('Colaborador', FLAVOR_PLATFORM_TEXT_DOMAIN),
    5 => __('Vigilante', FLAVOR_PLATFORM_TEXT_DOMAIN),
    6 => __('Guardián', FLAVOR_PLATFORM_TEXT_DOMAIN),
    7 => __('Protector', FLAVOR_PLATFORM_TEXT_DOMAIN),
    8 => __('Defensor', FLAVOR_PLATFORM_TEXT_DOMAIN),
    9 => __('Héroe Urbano', FLAVOR_PLATFORM_TEXT_DOMAIN),
    10 => __('Leyenda del Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
$titulo_nivel = $titulos_nivel[$nivel] ?? $titulos_nivel[1];

// Puntos para siguiente nivel
$puntos_niveles = [0, 30, 75, 150, 300, 500, 750, 1000, 1500, 2000];
$siguiente_nivel = $nivel < 10 ? $puntos_niveles[$nivel] : null;
$progreso = $siguiente_nivel ? min(100, ($puntos / $siguiente_nivel) * 100) : 100;
?>
<div class="inc-mi-part">
    <div class="inc-mi-part__header">
        <div class="inc-mi-part__avatar">
            <?php echo get_avatar(get_current_user_id(), 80); ?>
            <span class="inc-mi-part__nivel-badge">Nv.<?php echo esc_html($nivel); ?></span>
        </div>
        <div class="inc-mi-part__info">
            <h3><?php echo esc_html($usuario->display_name); ?></h3>
            <span class="inc-mi-part__titulo"><?php echo esc_html($titulo_nivel); ?></span>
        </div>
    </div>

    <!-- Progreso de nivel -->
    <div class="inc-mi-part__progreso">
        <div class="inc-mi-part__progreso-header">
            <span><?php printf(esc_html__('%d puntos', FLAVOR_PLATFORM_TEXT_DOMAIN), $puntos); ?></span>
            <?php if ($siguiente_nivel): ?>
                <span><?php printf(esc_html__('Siguiente nivel: %d pts', FLAVOR_PLATFORM_TEXT_DOMAIN), $siguiente_nivel); ?></span>
            <?php else: ?>
                <span><?php esc_html_e('¡Nivel máximo!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <?php endif; ?>
        </div>
        <div class="inc-mi-part__progreso-bar">
            <div class="inc-mi-part__progreso-fill" style="width: <?php echo esc_attr($progreso); ?>%"></div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="inc-mi-part__stats">
        <div class="inc-mi-part__stat">
            <span class="inc-mi-part__stat-icono" style="background: #f44336;">
                <span class="dashicons dashicons-flag"></span>
            </span>
            <div class="inc-mi-part__stat-info">
                <span class="inc-mi-part__stat-valor"><?php echo esc_html($participacion->incidencias_reportadas); ?></span>
                <span class="inc-mi-part__stat-label"><?php esc_html_e('Reportadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <div class="inc-mi-part__stat">
            <span class="inc-mi-part__stat-icono" style="background: #4caf50;">
                <span class="dashicons dashicons-yes-alt"></span>
            </span>
            <div class="inc-mi-part__stat-info">
                <span class="inc-mi-part__stat-valor"><?php echo esc_html($participacion->incidencias_resueltas); ?></span>
                <span class="inc-mi-part__stat-label"><?php esc_html_e('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <div class="inc-mi-part__stat">
            <span class="inc-mi-part__stat-icono" style="background: #e91e63;">
                <span class="dashicons dashicons-heart"></span>
            </span>
            <div class="inc-mi-part__stat-info">
                <span class="inc-mi-part__stat-valor"><?php echo esc_html($participacion->voluntariados_completados); ?></span>
                <span class="inc-mi-part__stat-label"><?php esc_html_e('Voluntariados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <div class="inc-mi-part__stat">
            <span class="inc-mi-part__stat-icono" style="background: #ff9800;">
                <span class="dashicons dashicons-clock"></span>
            </span>
            <div class="inc-mi-part__stat-info">
                <span class="inc-mi-part__stat-valor"><?php echo esc_html(number_format($participacion->horas_voluntariado, 1)); ?>h</span>
                <span class="inc-mi-part__stat-label"><?php esc_html_e('Horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Actividad adicional -->
    <div class="inc-mi-part__actividad">
        <div class="inc-mi-part__actividad-item">
            <span class="dashicons dashicons-thumbs-up"></span>
            <span><?php printf(esc_html__('%d votos dados', FLAVOR_PLATFORM_TEXT_DOMAIN), $participacion->votos_dados); ?></span>
        </div>
        <div class="inc-mi-part__actividad-item">
            <span class="dashicons dashicons-admin-comments"></span>
            <span><?php printf(esc_html__('%d comentarios útiles', FLAVOR_PLATFORM_TEXT_DOMAIN), $participacion->comentarios_utiles); ?></span>
        </div>
    </div>

    <!-- Logros -->
    <div class="inc-mi-part__logros">
        <h4><?php esc_html_e('Logros desbloqueados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <div class="inc-mi-part__logros-grid">
            <?php
            $logros_definidos = [
                'primer_reporte' => ['icono' => 'dashicons-flag', 'nombre' => __('Primer reporte', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'reportero_activo' => ['icono' => 'dashicons-edit', 'nombre' => __('10 reportes', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'reportero_experto' => ['icono' => 'dashicons-awards', 'nombre' => __('50 reportes', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'solucionador' => ['icono' => 'dashicons-yes-alt', 'nombre' => __('5 resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'voluntario' => ['icono' => 'dashicons-heart', 'nombre' => __('Primer voluntariado', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'voluntario_experto' => ['icono' => 'dashicons-superhero', 'nombre' => __('10 voluntariados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'comentarista' => ['icono' => 'dashicons-format-chat', 'nombre' => __('20 comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'nivel_5' => ['icono' => 'dashicons-star-half', 'nombre' => __('Nivel 5', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'nivel_10' => ['icono' => 'dashicons-star-filled', 'nombre' => __('Nivel 10', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ];

            foreach ($logros_definidos as $key => $logro_def):
                $desbloqueado = in_array($key, $logros);
            ?>
                <div class="inc-mi-part__logro inc-mi-part__logro--<?php echo $desbloqueado ? 'desbloqueado' : 'bloqueado'; ?>">
                    <span class="dashicons <?php echo esc_attr($logro_def['icono']); ?>"></span>
                    <span><?php echo esc_html($logro_def['nombre']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Puntuación desglosada -->
    <div class="inc-mi-part__desglose">
        <h4><?php esc_html_e('Cómo has ganado puntos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <ul>
            <li>
                <span><?php esc_html_e('Incidencias reportadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="inc-mi-part__puntos-detalle">
                    <?php echo esc_html($participacion->incidencias_reportadas); ?> × 10 =
                    <strong><?php echo esc_html($participacion->incidencias_reportadas * 10); ?></strong>
                </span>
            </li>
            <li>
                <span><?php esc_html_e('Incidencias resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="inc-mi-part__puntos-detalle">
                    <?php echo esc_html($participacion->incidencias_resueltas); ?> × 5 =
                    <strong><?php echo esc_html($participacion->incidencias_resueltas * 5); ?></strong>
                </span>
            </li>
            <li>
                <span><?php esc_html_e('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="inc-mi-part__puntos-detalle">
                    <?php echo esc_html($participacion->comentarios_utiles); ?> × 2 =
                    <strong><?php echo esc_html($participacion->comentarios_utiles * 2); ?></strong>
                </span>
            </li>
            <li>
                <span><?php esc_html_e('Horas voluntariado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="inc-mi-part__puntos-detalle">
                    <?php echo esc_html(number_format($participacion->horas_voluntariado, 1)); ?> × 15 =
                    <strong><?php echo esc_html(intval($participacion->horas_voluntariado * 15)); ?></strong>
                </span>
            </li>
        </ul>
    </div>
</div>
<style>
.inc-mi-part{--inc-primary:#f44336;--inc-primary-light:#ffebee;--inc-gold:#ffc107;--inc-text:#333;--inc-text-light:#666;--inc-border:#e0e0e0;background:#fff;border:1px solid var(--inc-border);border-radius:12px;padding:1.5rem;max-width:450px}
.inc-mi-part__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.inc-mi-part__avatar{position:relative}
.inc-mi-part__avatar img{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--inc-primary)}
.inc-mi-part__nivel-badge{position:absolute;bottom:-4px;right:-4px;background:var(--inc-primary);color:#fff;padding:.2rem .5rem;border-radius:10px;font-size:.75rem;font-weight:600}
.inc-mi-part__info h3{margin:0;font-size:1.2rem}
.inc-mi-part__titulo{display:inline-block;padding:.25rem .6rem;background:linear-gradient(135deg,var(--inc-gold),#ffb300);color:#333;border-radius:12px;font-size:.8rem;font-weight:600;margin-top:.25rem}
.inc-mi-part__progreso{margin-bottom:1.5rem}
.inc-mi-part__progreso-header{display:flex;justify-content:space-between;font-size:.8rem;color:var(--inc-text-light);margin-bottom:.5rem}
.inc-mi-part__progreso-bar{height:12px;background:#e0e0e0;border-radius:6px;overflow:hidden}
.inc-mi-part__progreso-fill{height:100%;background:linear-gradient(90deg,var(--inc-primary),#e91e63);border-radius:6px;transition:width .5s ease}
.inc-mi-part__stats{display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;margin-bottom:1.5rem}
.inc-mi-part__stat{display:flex;flex-direction:column;align-items:center;text-align:center;padding:.5rem;background:#f5f5f5;border-radius:10px}
.inc-mi-part__stat-icono{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;margin-bottom:.25rem}
.inc-mi-part__stat-icono .dashicons{color:#fff;font-size:1.1rem;width:1.1rem;height:1.1rem}
.inc-mi-part__stat-valor{font-size:1.25rem;font-weight:700;line-height:1.2}
.inc-mi-part__stat-label{font-size:.65rem;color:var(--inc-text-light)}
.inc-mi-part__actividad{display:flex;gap:1rem;margin-bottom:1.5rem;padding:.75rem;background:#f5f5f5;border-radius:10px}
.inc-mi-part__actividad-item{display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:var(--inc-text-light)}
.inc-mi-part__actividad-item .dashicons{color:var(--inc-primary);font-size:1rem;width:1rem;height:1rem}
.inc-mi-part__logros{margin-bottom:1.5rem}
.inc-mi-part__logros h4{margin:0 0 .75rem;font-size:.9rem;color:var(--inc-text-light);text-transform:uppercase}
.inc-mi-part__logros-grid{display:flex;flex-wrap:wrap;gap:.5rem}
.inc-mi-part__logro{display:flex;align-items:center;gap:.25rem;padding:.3rem .5rem;border-radius:15px;font-size:.7rem}
.inc-mi-part__logro--desbloqueado{background:linear-gradient(135deg,#fff8e1,#ffecb3);border:1px solid var(--inc-gold)}
.inc-mi-part__logro--desbloqueado .dashicons{color:#f57c00;font-size:.85rem;width:.85rem;height:.85rem}
.inc-mi-part__logro--bloqueado{background:#f5f5f5;color:#bbb}
.inc-mi-part__logro--bloqueado .dashicons{font-size:.85rem;width:.85rem;height:.85rem;color:#ccc}
.inc-mi-part__desglose h4{margin:0 0 .75rem;font-size:.9rem;color:var(--inc-text-light);text-transform:uppercase}
.inc-mi-part__desglose ul{margin:0;padding:0;list-style:none}
.inc-mi-part__desglose li{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px dashed var(--inc-border);font-size:.85rem}
.inc-mi-part__desglose li:last-child{border-bottom:none}
.inc-mi-part__puntos-detalle{color:var(--inc-text-light)}
.inc-mi-part__puntos-detalle strong{color:var(--inc-primary)}
@media(max-width:400px){.inc-mi-part__stats{grid-template-columns:repeat(2,1fr)}}
</style>

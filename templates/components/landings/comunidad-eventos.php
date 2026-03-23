<?php
/**
 * Template para Comunidad - Sección de Eventos
 *
 * Variables: $titulo, $eventos, $mostrar_todos_url, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? __('Próximos Eventos', 'flavor-chat-ia');
$color_primario = $color_primario ?? '#e91e63';
$id_seccion = $id_seccion ?? '';

$eventos_default = [
    [
        'titulo' => __('Asamblea General', 'flavor-chat-ia'),
        'fecha' => date('Y-m-d', strtotime('+5 days')),
        'hora' => '18:00',
        'lugar' => __('Salón de Actos', 'flavor-chat-ia'),
        'tipo' => 'asamblea',
        'plazas' => 50,
        'inscritos' => 32,
    ],
    [
        'titulo' => __('Taller de Huerto Urbano', 'flavor-chat-ia'),
        'fecha' => date('Y-m-d', strtotime('+7 days')),
        'hora' => '10:00',
        'lugar' => __('Huerto Comunitario', 'flavor-chat-ia'),
        'tipo' => 'taller',
        'plazas' => 15,
        'inscritos' => 12,
    ],
    [
        'titulo' => __('Mercadillo de Intercambio', 'flavor-chat-ia'),
        'fecha' => date('Y-m-d', strtotime('+14 days')),
        'hora' => '11:00',
        'lugar' => __('Plaza Central', 'flavor-chat-ia'),
        'tipo' => 'mercadillo',
        'plazas' => 0,
        'inscritos' => 0,
    ],
];

$eventos = $eventos ?? $eventos_default;

$tipo_colores = [
    'asamblea' => '#1d4ed8',
    'taller' => '#059669',
    'mercadillo' => '#d97706',
    'fiesta' => '#dc2626',
    'charla' => '#7c3aed',
];
?>

<section<?php if ($id_seccion): ?> id="<?php echo esc_attr($id_seccion); ?>"<?php endif; ?> class="flavor-comunidad-eventos" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-eventos-header">
            <div>
                <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
                <p class="flavor-eventos-subtitulo"><?php esc_html_e('Participa en las actividades de nuestra comunidad', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="<?php echo esc_url($mostrar_todos_url ?? '#eventos'); ?>" class="flavor-ver-calendario">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Ver calendario', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <div class="flavor-eventos-lista">
            <?php foreach ($eventos as $evento):
                $fecha_obj = new DateTime($evento['fecha']);
                $tipo_color = $tipo_colores[$evento['tipo']] ?? $color_primario;
                $plazas_disponibles = $evento['plazas'] > 0 ? $evento['plazas'] - $evento['inscritos'] : null;
            ?>
                <article class="flavor-evento-card" style="--tipo-color: <?php echo esc_attr($tipo_color); ?>;">
                    <div class="flavor-evento-fecha">
                        <span class="flavor-fecha-dia"><?php echo esc_html($fecha_obj->format('d')); ?></span>
                        <span class="flavor-fecha-mes"><?php echo esc_html(ucfirst(date_i18n('M', $fecha_obj->getTimestamp()))); ?></span>
                    </div>

                    <div class="flavor-evento-contenido">
                        <div class="flavor-evento-tipo">
                            <?php echo esc_html(ucfirst($evento['tipo'])); ?>
                        </div>
                        <h3 class="flavor-evento-titulo"><?php echo esc_html($evento['titulo']); ?></h3>
                        <div class="flavor-evento-detalles">
                            <span class="flavor-detalle">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($evento['hora']); ?>
                            </span>
                            <span class="flavor-detalle">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($evento['lugar']); ?>
                            </span>
                            <?php if ($plazas_disponibles !== null): ?>
                                <span class="flavor-detalle flavor-plazas <?php echo $plazas_disponibles < 5 ? 'flavor-plazas--pocas' : ''; ?>">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php printf(esc_html__('%d plazas disponibles', 'flavor-chat-ia'), $plazas_disponibles); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-evento-accion">
                        <?php
                        $evento_url = '#inscribirse';
                        if (!empty($evento['url'])) {
                            $evento_url = $evento['url'];
                        } elseif (!empty($evento['id'])) {
                            $evento_url = get_permalink($evento['id']);
                        } elseif (!empty($evento['slug'])) {
                            $evento_url = home_url('/eventos/' . $evento['slug'] . '/');
                        }
                        ?>
                        <a href="<?php echo esc_url($evento_url); ?>" class="flavor-inscribirse-btn">
                            <?php esc_html_e('Inscribirse', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-eventos-cta">
            <div class="flavor-cta-content">
                <h3><?php esc_html_e('¿Quieres organizar un evento?', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Los miembros pueden proponer actividades para la comunidad', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="<?php echo esc_url($proponer_evento_url ?? home_url('/eventos/proponer/')); ?>" class="flavor-button flavor-button--outline">
                <?php esc_html_e('Proponer evento', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</section>

<style>
.flavor-comunidad-eventos {
    padding: 5rem 0;
    background: #fdf2f8;
}
.flavor-eventos-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.flavor-eventos-header .flavor-section-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.25rem;
}
.flavor-eventos-subtitulo {
    color: #6b7280;
    margin: 0;
}
.flavor-ver-calendario {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: white;
    border-radius: 8px;
    color: var(--color-primario);
    text-decoration: none;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s;
}
.flavor-ver-calendario:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
.flavor-eventos-lista {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 3rem;
}
.flavor-evento-card {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border-left: 4px solid var(--tipo-color);
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-evento-card:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.flavor-evento-fecha {
    flex-shrink: 0;
    width: 60px;
    text-align: center;
    padding: 0.75rem;
    background: #fdf2f8;
    border-radius: 8px;
}
.flavor-fecha-dia {
    display: block;
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--color-primario);
    line-height: 1;
}
.flavor-fecha-mes {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    margin-top: 0.25rem;
}
.flavor-evento-contenido {
    flex: 1;
    min-width: 0;
}
.flavor-evento-tipo {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: var(--tipo-color);
    color: white;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}
.flavor-evento-titulo {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem;
}
.flavor-evento-detalles {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}
.flavor-detalle {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: #6b7280;
}
.flavor-detalle .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.flavor-plazas--pocas {
    color: #dc2626;
    font-weight: 500;
}
.flavor-evento-accion {
    flex-shrink: 0;
}
.flavor-inscribirse-btn {
    display: inline-block;
    padding: 0.625rem 1.25rem;
    background: var(--color-primario);
    color: white;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none;
    transition: filter 0.2s;
}
.flavor-inscribirse-btn:hover {
    filter: brightness(1.1);
}
.flavor-eventos-cta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    background: white;
    border-radius: 12px;
    padding: 1.5rem 2rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.flavor-cta-content h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.25rem;
}
.flavor-cta-content p {
    color: #6b7280;
    margin: 0;
    font-size: 0.9375rem;
}
.flavor-button--outline {
    padding: 0.75rem 1.5rem;
    border: 2px solid var(--color-primario);
    color: var(--color-primario);
    background: transparent;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
}
.flavor-button--outline:hover {
    background: var(--color-primario);
    color: white;
}
@media (max-width: 768px) {
    .flavor-comunidad-eventos {
        padding: 3rem 0;
    }
    .flavor-evento-card {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    .flavor-evento-fecha {
        width: auto;
        display: flex;
        gap: 0.5rem;
        align-items: baseline;
    }
    .flavor-fecha-mes {
        margin-top: 0;
    }
    .flavor-eventos-cta {
        flex-direction: column;
        text-align: center;
    }
}
</style>

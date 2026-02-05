<?php
/**
 * Template para Coworking - Sección de Espacios y Tarifas
 *
 * Variables: $titulo, $espacios, $planes, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? __('Nuestros Espacios', 'flavor-chat-ia');
$color_primario = $color_primario ?? '#00bcd4';

$espacios_default = [
    [
        'nombre' => __('Hot Desk', 'flavor-chat-ia'),
        'descripcion' => __('Puesto flexible en zona compartida', 'flavor-chat-ia'),
        'icono' => 'laptop',
        'caracteristicas' => ['WiFi de alta velocidad', 'Café ilimitado', 'Taquilla personal'],
        'precio' => '150',
        'periodo' => __('/mes', 'flavor-chat-ia'),
        'destacado' => false,
    ],
    [
        'nombre' => __('Dedicated Desk', 'flavor-chat-ia'),
        'descripcion' => __('Tu escritorio fijo 24/7', 'flavor-chat-ia'),
        'icono' => 'desktop',
        'caracteristicas' => ['Puesto fijo asignado', 'Almacenamiento', 'Acceso 24h', 'Sala reuniones 4h/mes'],
        'precio' => '250',
        'periodo' => __('/mes', 'flavor-chat-ia'),
        'destacado' => true,
    ],
    [
        'nombre' => __('Oficina Privada', 'flavor-chat-ia'),
        'descripcion' => __('Espacio cerrado para tu equipo', 'flavor-chat-ia'),
        'icono' => 'building',
        'caracteristicas' => ['Desde 2 personas', 'Mobiliario incluido', 'Dirección fiscal', 'Sala reuniones 8h/mes'],
        'precio' => '450',
        'periodo' => __('/mes', 'flavor-chat-ia'),
        'destacado' => false,
    ],
];

$espacios = $espacios ?? $espacios_default;

$amenities = [
    ['icono' => 'wifi', 'nombre' => __('WiFi Gigabit', 'flavor-chat-ia')],
    ['icono' => 'coffee', 'nombre' => __('Café & Té', 'flavor-chat-ia')],
    ['icono' => 'printer', 'nombre' => __('Impresora', 'flavor-chat-ia')],
    ['icono' => 'groups', 'nombre' => __('Salas de Reuniones', 'flavor-chat-ia')],
    ['icono' => 'phone', 'nombre' => __('Cabinas Telefónicas', 'flavor-chat-ia')],
    ['icono' => 'calendar', 'nombre' => __('Eventos Networking', 'flavor-chat-ia')],
];
?>

<section class="flavor-coworking-espacios" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-espacios-header">
            <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
            <p class="flavor-espacios-subtitulo"><?php esc_html_e('Encuentra el espacio perfecto para trabajar', 'flavor-chat-ia'); ?></p>
        </div>

        <div class="flavor-espacios-grid">
            <?php foreach ($espacios as $espacio): ?>
                <article class="flavor-espacio-card <?php echo $espacio['destacado'] ? 'flavor-espacio--destacado' : ''; ?>">
                    <?php if ($espacio['destacado']): ?>
                        <div class="flavor-espacio-badge"><?php esc_html_e('Más Popular', 'flavor-chat-ia'); ?></div>
                    <?php endif; ?>

                    <div class="flavor-espacio-icono">
                        <span class="dashicons dashicons-<?php echo esc_attr($espacio['icono']); ?>"></span>
                    </div>

                    <h3 class="flavor-espacio-nombre"><?php echo esc_html($espacio['nombre']); ?></h3>
                    <p class="flavor-espacio-descripcion"><?php echo esc_html($espacio['descripcion']); ?></p>

                    <div class="flavor-espacio-precio">
                        <span class="flavor-precio-cantidad"><?php echo esc_html($espacio['precio']); ?>€</span>
                        <span class="flavor-precio-periodo"><?php echo esc_html($espacio['periodo']); ?></span>
                    </div>

                    <ul class="flavor-espacio-caracteristicas">
                        <?php foreach ($espacio['caracteristicas'] as $caracteristica): ?>
                            <li>
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php echo esc_html($caracteristica); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <a href="#reservar" class="flavor-espacio-boton <?php echo $espacio['destacado'] ? 'flavor-boton--primario' : 'flavor-boton--secundario'; ?>">
                        <?php esc_html_e('Reservar Visita', 'flavor-chat-ia'); ?>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-amenities">
            <h3 class="flavor-amenities-titulo"><?php esc_html_e('Todo incluido', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-amenities-grid">
                <?php foreach ($amenities as $amenity): ?>
                    <div class="flavor-amenity-item">
                        <span class="dashicons dashicons-<?php echo esc_attr($amenity['icono']); ?>"></span>
                        <span><?php echo esc_html($amenity['nombre']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-coworking-espacios {
    padding: 5rem 0;
    background: linear-gradient(180deg, #f0fdfa 0%, #ccfbf1 100%);
}
.flavor-espacios-header {
    text-align: center;
    margin-bottom: 3rem;
}
.flavor-espacios-header .flavor-section-title {
    font-size: 2.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem;
}
.flavor-espacios-subtitulo {
    color: #6b7280;
    font-size: 1.125rem;
    margin: 0;
}
.flavor-espacios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 4rem;
}
.flavor-espacio-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    position: relative;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 2px solid transparent;
}
.flavor-espacio-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.1);
}
.flavor-espacio--destacado {
    border-color: var(--color-primario);
    transform: scale(1.02);
}
.flavor-espacio--destacado:hover {
    transform: scale(1.02) translateY(-4px);
}
.flavor-espacio-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--color-primario);
    color: white;
    padding: 0.375rem 1rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}
.flavor-espacio-icono {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-espacio-icono .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: var(--color-primario);
}
.flavor-espacio-nombre {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    color: #1f2937;
}
.flavor-espacio-descripcion {
    font-size: 0.9375rem;
    color: #6b7280;
    margin: 0 0 1.5rem;
}
.flavor-espacio-precio {
    margin-bottom: 1.5rem;
}
.flavor-precio-cantidad {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1f2937;
}
.flavor-precio-periodo {
    font-size: 1rem;
    color: #6b7280;
}
.flavor-espacio-caracteristicas {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem;
    text-align: left;
}
.flavor-espacio-caracteristicas li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    font-size: 0.9375rem;
    color: #4b5563;
}
.flavor-espacio-caracteristicas .dashicons {
    color: #10b981;
    font-size: 18px;
}
.flavor-espacio-boton {
    display: block;
    width: 100%;
    padding: 0.875rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s;
}
.flavor-boton--primario {
    background: var(--color-primario);
    color: white;
}
.flavor-boton--primario:hover {
    filter: brightness(1.1);
}
.flavor-boton--secundario {
    background: #f3f4f6;
    color: #1f2937;
}
.flavor-boton--secundario:hover {
    background: var(--color-primario);
    color: white;
}
.flavor-amenities {
    text-align: center;
}
.flavor-amenities-titulo {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 1.5rem;
}
.flavor-amenities-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
}
.flavor-amenity-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: white;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #4b5563;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.flavor-amenity-item .dashicons {
    color: var(--color-primario);
}
@media (max-width: 768px) {
    .flavor-coworking-espacios {
        padding: 3rem 0;
    }
    .flavor-espacios-header .flavor-section-title {
        font-size: 1.75rem;
    }
    .flavor-espacio--destacado {
        transform: none;
    }
}
</style>

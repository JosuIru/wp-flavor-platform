<?php
/**
 * Template para Restaurante - Sección de Menú
 *
 * Variables: $titulo, $categorias, $mostrar_precios, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? __('Nuestra Carta', 'flavor-chat-ia');
$color_primario = $color_primario ?? '#f56e28';

// Categorías de ejemplo si no hay datos
$categorias_default = [
    [
        'nombre' => __('Entrantes', 'flavor-chat-ia'),
        'icono' => 'carrot',
        'platos' => [
            ['nombre' => 'Ensalada Mediterránea', 'descripcion' => 'Tomate, pepino, aceitunas, queso feta', 'precio' => '8.50'],
            ['nombre' => 'Croquetas Caseras', 'descripcion' => 'Jamón ibérico, bechamel suave', 'precio' => '9.00'],
            ['nombre' => 'Tabla de Quesos', 'descripcion' => 'Selección de quesos artesanos', 'precio' => '12.00'],
        ]
    ],
    [
        'nombre' => __('Principales', 'flavor-chat-ia'),
        'icono' => 'food',
        'platos' => [
            ['nombre' => 'Risotto de Setas', 'descripcion' => 'Arroz carnaroli, setas de temporada, parmesano', 'precio' => '14.50'],
            ['nombre' => 'Solomillo a la Brasa', 'descripcion' => 'Con guarnición de verduras asadas', 'precio' => '22.00'],
            ['nombre' => 'Lubina al Horno', 'descripcion' => 'Con patatas panaderas y alioli', 'precio' => '18.00'],
        ]
    ],
    [
        'nombre' => __('Postres', 'flavor-chat-ia'),
        'icono' => 'heart',
        'platos' => [
            ['nombre' => 'Tiramisú', 'descripcion' => 'Receta tradicional italiana', 'precio' => '6.50'],
            ['nombre' => 'Tarta de Queso', 'descripcion' => 'Estilo vasco con frutos rojos', 'precio' => '7.00'],
        ]
    ],
];

$categorias = $categorias ?? $categorias_default;
?>

<section class="flavor-restaurante-menu" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-menu-header">
            <span class="flavor-menu-badge"><?php esc_html_e('Carta', 'flavor-chat-ia'); ?></span>
            <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
            <p class="flavor-menu-subtitulo"><?php esc_html_e('Productos frescos y de temporada', 'flavor-chat-ia'); ?></p>
        </div>

        <div class="flavor-menu-categorias">
            <?php foreach ($categorias as $categoria): ?>
                <div class="flavor-menu-categoria">
                    <div class="flavor-categoria-header">
                        <span class="flavor-categoria-icono dashicons dashicons-<?php echo esc_attr($categoria['icono']); ?>"></span>
                        <h3 class="flavor-categoria-nombre"><?php echo esc_html($categoria['nombre']); ?></h3>
                    </div>
                    <div class="flavor-platos-lista">
                        <?php foreach ($categoria['platos'] as $plato): ?>
                            <div class="flavor-plato-item">
                                <div class="flavor-plato-info">
                                    <h4 class="flavor-plato-nombre"><?php echo esc_html($plato['nombre']); ?></h4>
                                    <p class="flavor-plato-descripcion"><?php echo esc_html($plato['descripcion']); ?></p>
                                </div>
                                <div class="flavor-plato-precio">
                                    <span class="flavor-precio-valor"><?php echo esc_html($plato['precio']); ?>€</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flavor-menu-acciones">
            <a href="#reservar" class="flavor-button flavor-button--primary">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Reservar Mesa', 'flavor-chat-ia'); ?>
            </a>
            <a href="#" class="flavor-button flavor-button--secondary">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Descargar Carta PDF', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</section>

<style>
.flavor-restaurante-menu {
    padding: 5rem 0;
    background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
}
.flavor-menu-header {
    text-align: center;
    margin-bottom: 3rem;
}
.flavor-menu-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: var(--color-primario);
    color: white;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 1rem;
}
.flavor-menu-header .flavor-section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem;
}
.flavor-menu-subtitulo {
    color: #6b7280;
    font-size: 1.125rem;
    margin: 0;
}
.flavor-menu-categorias {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}
.flavor-menu-categoria {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.flavor-categoria-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #fef3c7;
}
.flavor-categoria-icono {
    width: 40px;
    height: 40px;
    background: var(--color-primario);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.flavor-categoria-nombre {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #1f2937;
}
.flavor-platos-lista {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.flavor-plato-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px dashed #e5e7eb;
}
.flavor-plato-item:last-child {
    border-bottom: none;
}
.flavor-plato-nombre {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: #1f2937;
}
.flavor-plato-descripcion {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}
.flavor-plato-precio {
    flex-shrink: 0;
}
.flavor-precio-valor {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--color-primario);
}
.flavor-menu-acciones {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.flavor-menu-acciones .flavor-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.flavor-button--primary {
    background: var(--color-primario);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-button--primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 110, 40, 0.3);
}
.flavor-button--secondary {
    background: white;
    color: #1f2937;
    border: 2px solid #e5e7eb;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: border-color 0.2s;
}
.flavor-button--secondary:hover {
    border-color: var(--color-primario);
    color: var(--color-primario);
}
@media (max-width: 768px) {
    .flavor-restaurante-menu {
        padding: 3rem 0;
    }
    .flavor-menu-header .flavor-section-title {
        font-size: 2rem;
    }
    .flavor-menu-categorias {
        grid-template-columns: 1fr;
    }
}
</style>

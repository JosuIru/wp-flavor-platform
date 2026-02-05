<?php
/**
 * Template para Marketplace - Anuncios Recientes
 *
 * Variables: $titulo, $categorias, $anuncios, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? __('Anuncios cerca de ti', 'flavor-chat-ia');
$color_primario = $color_primario ?? '#ff9800';

$categorias_default = [
    ['slug' => 'venta', 'nombre' => __('Venta', 'flavor-chat-ia'), 'icono' => 'tag', 'color' => '#22c55e'],
    ['slug' => 'regalo', 'nombre' => __('Regalo', 'flavor-chat-ia'), 'icono' => 'heart', 'color' => '#ec4899'],
    ['slug' => 'intercambio', 'nombre' => __('Intercambio', 'flavor-chat-ia'), 'icono' => 'randomize', 'color' => '#8b5cf6'],
    ['slug' => 'alquiler', 'nombre' => __('Alquiler', 'flavor-chat-ia'), 'icono' => 'calendar', 'color' => '#06b6d4'],
    ['slug' => 'servicios', 'nombre' => __('Servicios', 'flavor-chat-ia'), 'icono' => 'admin-tools', 'color' => '#f59e0b'],
];

$anuncios_default = [
    [
        'titulo' => __('Bicicleta de montaña', 'flavor-chat-ia'),
        'categoria' => 'venta',
        'precio' => '120€',
        'ubicacion' => __('Centro', 'flavor-chat-ia'),
        'tiempo' => __('Hace 2 horas', 'flavor-chat-ia'),
        'imagen' => '',
    ],
    [
        'titulo' => __('Sofá 3 plazas', 'flavor-chat-ia'),
        'categoria' => 'regalo',
        'precio' => __('Gratis', 'flavor-chat-ia'),
        'ubicacion' => __('Ensanche', 'flavor-chat-ia'),
        'tiempo' => __('Hace 5 horas', 'flavor-chat-ia'),
        'imagen' => '',
    ],
    [
        'titulo' => __('Clases de guitarra', 'flavor-chat-ia'),
        'categoria' => 'servicios',
        'precio' => '15€/h',
        'ubicacion' => __('A domicilio', 'flavor-chat-ia'),
        'tiempo' => __('Hace 1 día', 'flavor-chat-ia'),
        'imagen' => '',
    ],
    [
        'titulo' => __('Taladro profesional', 'flavor-chat-ia'),
        'categoria' => 'alquiler',
        'precio' => '5€/día',
        'ubicacion' => __('San Juan', 'flavor-chat-ia'),
        'tiempo' => __('Hace 1 día', 'flavor-chat-ia'),
        'imagen' => '',
    ],
];

$categorias = $categorias ?? $categorias_default;
$anuncios = $anuncios ?? $anuncios_default;

// Crear mapa de categorías para lookup
$cat_map = [];
foreach ($categorias as $cat) {
    $cat_map[$cat['slug']] = $cat;
}
?>

<section class="flavor-marketplace-anuncios" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-marketplace-header">
            <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
            <a href="#publicar" class="flavor-publicar-btn">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Publicar anuncio', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <!-- Filtros por categoría -->
        <div class="flavor-categorias-filtro">
            <button class="flavor-filtro-btn is-active" data-categoria="todos">
                <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
            </button>
            <?php foreach ($categorias as $cat): ?>
                <button class="flavor-filtro-btn" data-categoria="<?php echo esc_attr($cat['slug']); ?>" style="--cat-color: <?php echo esc_attr($cat['color']); ?>;">
                    <span class="dashicons dashicons-<?php echo esc_attr($cat['icono']); ?>"></span>
                    <?php echo esc_html($cat['nombre']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de anuncios -->
        <div class="flavor-anuncios-grid">
            <?php foreach ($anuncios as $anuncio):
                $cat = $cat_map[$anuncio['categoria']] ?? $categorias[0];
            ?>
                <article class="flavor-anuncio-card" data-categoria="<?php echo esc_attr($anuncio['categoria']); ?>">
                    <div class="flavor-anuncio-imagen">
                        <?php if (!empty($anuncio['imagen'])): ?>
                            <img src="<?php echo esc_url($anuncio['imagen']); ?>" alt="<?php echo esc_attr($anuncio['titulo']); ?>">
                        <?php else: ?>
                            <div class="flavor-imagen-placeholder">
                                <span class="dashicons dashicons-format-image"></span>
                            </div>
                        <?php endif; ?>
                        <span class="flavor-anuncio-categoria" style="background: <?php echo esc_attr($cat['color']); ?>;">
                            <?php echo esc_html($cat['nombre']); ?>
                        </span>
                    </div>
                    <div class="flavor-anuncio-contenido">
                        <h3 class="flavor-anuncio-titulo"><?php echo esc_html($anuncio['titulo']); ?></h3>
                        <div class="flavor-anuncio-precio"><?php echo esc_html($anuncio['precio']); ?></div>
                        <div class="flavor-anuncio-meta">
                            <span class="flavor-meta-ubicacion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($anuncio['ubicacion']); ?>
                            </span>
                            <span class="flavor-meta-tiempo"><?php echo esc_html($anuncio['tiempo']); ?></span>
                        </div>
                    </div>
                    <a href="#anuncio" class="flavor-anuncio-link" aria-label="<?php echo esc_attr($anuncio['titulo']); ?>"></a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-marketplace-acciones">
            <a href="#todos" class="flavor-ver-mas">
                <?php esc_html_e('Ver todos los anuncios', 'flavor-chat-ia'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>

        <!-- Cómo funciona -->
        <div class="flavor-como-funciona">
            <h3><?php esc_html_e('¿Cómo funciona?', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-pasos">
                <div class="flavor-paso">
                    <div class="flavor-paso-numero">1</div>
                    <h4><?php esc_html_e('Publica', 'flavor-chat-ia'); ?></h4>
                    <p><?php esc_html_e('Sube fotos y describe lo que ofreces', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-paso">
                    <div class="flavor-paso-numero">2</div>
                    <h4><?php esc_html_e('Conecta', 'flavor-chat-ia'); ?></h4>
                    <p><?php esc_html_e('Los vecinos interesados te contactan', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-paso">
                    <div class="flavor-paso-numero">3</div>
                    <h4><?php esc_html_e('Acuerda', 'flavor-chat-ia'); ?></h4>
                    <p><?php esc_html_e('Quedáis en persona para el intercambio', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-marketplace-anuncios {
    padding: 5rem 0;
    background: #fffbeb;
}
.flavor-marketplace-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.flavor-marketplace-header .flavor-section-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.flavor-publicar-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--color-primario);
    color: white;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-publicar-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(255, 152, 0, 0.3);
}
.flavor-categorias-filtro {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 2rem;
}
.flavor-filtro-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.625rem 1rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
}
.flavor-filtro-btn:hover {
    border-color: var(--cat-color, var(--color-primario));
    color: var(--cat-color, var(--color-primario));
}
.flavor-filtro-btn.is-active {
    background: var(--cat-color, var(--color-primario));
    border-color: var(--cat-color, var(--color-primario));
    color: white;
}
.flavor-filtro-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.flavor-anuncios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.flavor-anuncio-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}
.flavor-anuncio-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.1);
}
.flavor-anuncio-imagen {
    position: relative;
    aspect-ratio: 4/3;
    background: #f3f4f6;
}
.flavor-anuncio-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.flavor-imagen-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d1d5db;
}
.flavor-imagen-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}
.flavor-anuncio-categoria {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}
.flavor-anuncio-contenido {
    padding: 1rem;
}
.flavor-anuncio-titulo {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.flavor-anuncio-precio {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-primario);
    margin-bottom: 0.75rem;
}
.flavor-anuncio-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.8125rem;
    color: #6b7280;
}
.flavor-meta-ubicacion {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.flavor-meta-ubicacion .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}
.flavor-anuncio-link {
    position: absolute;
    inset: 0;
}
.flavor-marketplace-acciones {
    text-align: center;
    margin-bottom: 3rem;
}
.flavor-ver-mas {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-primario);
    font-weight: 600;
    text-decoration: none;
    transition: gap 0.2s;
}
.flavor-ver-mas:hover {
    gap: 0.75rem;
}
.flavor-como-funciona {
    background: white;
    border-radius: 16px;
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
}
.flavor-como-funciona h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 2rem;
}
.flavor-pasos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 2rem;
}
.flavor-paso {
    position: relative;
}
.flavor-paso-numero {
    width: 40px;
    height: 40px;
    margin: 0 auto 1rem;
    background: var(--color-primario);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
}
.flavor-paso h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem;
}
.flavor-paso p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}
@media (max-width: 768px) {
    .flavor-marketplace-anuncios {
        padding: 3rem 0;
    }
    .flavor-marketplace-header {
        flex-direction: column;
        text-align: center;
    }
    .flavor-marketplace-header .flavor-section-title {
        font-size: 1.5rem;
    }
    .flavor-categorias-filtro {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtros = document.querySelectorAll('.flavor-filtro-btn');
    const anuncios = document.querySelectorAll('.flavor-anuncio-card');

    filtros.forEach(function(filtro) {
        filtro.addEventListener('click', function() {
            const categoria = this.dataset.categoria;

            // Actualizar estado activo
            filtros.forEach(function(f) { f.classList.remove('is-active'); });
            this.classList.add('is-active');

            // Filtrar anuncios
            anuncios.forEach(function(anuncio) {
                if (categoria === 'todos' || anuncio.dataset.categoria === categoria) {
                    anuncio.style.display = '';
                } else {
                    anuncio.style.display = 'none';
                }
            });
        });
    });
});
</script>

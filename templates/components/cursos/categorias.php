<?php
/**
 * Template: Grid de Categorías de Cursos
 *
 * Muestra una cuadrícula de categorías de cursos con iconos y conteo
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : 'Explora por Categoría';
$subtitulo = isset($args['subtitulo']) ? $args['subtitulo'] : 'Encuentra el área de conocimiento que impulse tu carrera';
$columnas = isset($args['columnas']) ? intval($args['columnas']) : 4;
$estilo_visual = isset($args['estilo_visual']) ? $args['estilo_visual'] : 'tarjetas'; // tarjetas, iconos, compacto
$categorias = isset($args['categorias']) ? $args['categorias'] : array();

// Datos de demostración si no se proporcionan categorías
if (empty($categorias)) {
    $categorias = array(
        array(
            'id' => 1,
            'nombre' => 'Programación',
            'slug' => 'programacion',
            'descripcion' => 'Desarrollo web, móvil, backend y más',
            'icono' => 'code',
            'color' => '#6366f1',
            'color_claro' => '#eef2ff',
            'total_cursos' => 124,
            'imagen' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=300&h=200&fit=crop'
        ),
        array(
            'id' => 2,
            'nombre' => 'Diseño',
            'slug' => 'diseno',
            'descripcion' => 'UX/UI, gráfico, ilustración digital',
            'icono' => 'palette',
            'color' => '#ec4899',
            'color_claro' => '#fdf2f8',
            'total_cursos' => 89,
            'imagen' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=300&h=200&fit=crop'
        ),
        array(
            'id' => 3,
            'nombre' => 'Marketing Digital',
            'slug' => 'marketing-digital',
            'descripcion' => 'SEO, SEM, redes sociales y analítica',
            'icono' => 'megaphone',
            'color' => '#f59e0b',
            'color_claro' => '#fffbeb',
            'total_cursos' => 67,
            'imagen' => 'https://images.unsplash.com/photo-1432888622747-4eb9a8efeb07?w=300&h=200&fit=crop'
        ),
        array(
            'id' => 4,
            'nombre' => 'Idiomas',
            'slug' => 'idiomas',
            'descripcion' => 'Inglés, francés, alemán y más',
            'icono' => 'globe',
            'color' => '#10b981',
            'color_claro' => '#ecfdf5',
            'total_cursos' => 45,
            'imagen' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=300&h=200&fit=crop'
        ),
        array(
            'id' => 5,
            'nombre' => 'Negocios',
            'slug' => 'negocios',
            'descripcion' => 'Emprendimiento, finanzas y liderazgo',
            'icono' => 'briefcase',
            'color' => '#8b5cf6',
            'color_claro' => '#f5f3ff',
            'total_cursos' => 78,
            'imagen' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=300&h=200&fit=crop'
        ),
        array(
            'id' => 6,
            'nombre' => 'Data Science',
            'slug' => 'data-science',
            'descripcion' => 'Análisis de datos, ML e IA',
            'icono' => 'chart',
            'color' => '#06b6d4',
            'color_claro' => '#ecfeff',
            'total_cursos' => 56,
            'imagen' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=300&h=200&fit=crop'
        ),
        array(
            'id' => 7,
            'nombre' => 'Fotografía',
            'slug' => 'fotografia',
            'descripcion' => 'Técnicas, edición y composición',
            'icono' => 'camera',
            'color' => '#ef4444',
            'color_claro' => '#fef2f2',
            'total_cursos' => 34,
            'imagen' => 'https://images.unsplash.com/photo-1452780212940-6f5c0d14d848?w=300&h=200&fit=crop'
        ),
        array(
            'id' => 8,
            'nombre' => 'Música',
            'slug' => 'musica',
            'descripcion' => 'Producción, instrumentos y teoría',
            'icono' => 'music',
            'color' => '#84cc16',
            'color_claro' => '#f7fee7',
            'total_cursos' => 28,
            'imagen' => 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=300&h=200&fit=crop'
        )
    );
}

// Función para renderizar iconos SVG
function flavor_renderizar_icono_categoria($icono, $color = '#6366f1') {
    $iconos = array(
        'code' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>',
        'palette' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.555C21.965 6.012 17.461 2 12 2z"></path></svg>',
        'megaphone' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"></path><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path></svg>',
        'globe' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
        'briefcase' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>',
        'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
        'camera' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path><circle cx="12" cy="13" r="3"></circle></svg>',
        'music' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>'
    );

    return isset($iconos[$icono]) ? $iconos[$icono] : $iconos['code'];
}
?>

<section class="flavor-categorias-section">
    <div class="flavor-categorias-container">

        <?php if ($titulo_seccion || $subtitulo) : ?>
        <header class="flavor-categorias-header">
            <?php if ($titulo_seccion) : ?>
                <h2 class="flavor-categorias-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <?php endif; ?>
            <?php if ($subtitulo) : ?>
                <p class="flavor-categorias-subtitulo"><?php echo esc_html($subtitulo); ?></p>
            <?php endif; ?>
        </header>
        <?php endif; ?>

        <div class="flavor-categorias-grid flavor-categorias-grid--<?php echo esc_attr($columnas); ?>-cols flavor-categorias-grid--<?php echo esc_attr($estilo_visual); ?>">

            <?php foreach ($categorias as $categoria) : ?>
            <a href="#categoria-<?php echo esc_attr($categoria['slug']); ?>" class="flavor-categoria-card flavor-categoria-card--<?php echo esc_attr($estilo_visual); ?>">

                <?php if ($estilo_visual === 'tarjetas') : ?>
                    <div class="flavor-categoria-imagen-wrapper">
                        <img
                            src="<?php echo esc_url($categoria['imagen']); ?>"
                            alt="<?php echo esc_attr($categoria['nombre']); ?>"
                            class="flavor-categoria-imagen"
                            loading="lazy"
                        >
                        <div class="flavor-categoria-overlay" style="background: linear-gradient(to top, <?php echo esc_attr($categoria['color']); ?>dd, transparent);"></div>
                    </div>
                    <div class="flavor-categoria-info">
                        <div class="flavor-categoria-icono-wrapper" style="background: <?php echo esc_attr($categoria['color_claro']); ?>;">
                            <span class="flavor-categoria-icono">
                                <?php echo flavor_renderizar_icono_categoria($categoria['icono'], $categoria['color']); ?>
                            </span>
                        </div>
                        <h3 class="flavor-categoria-nombre"><?php echo esc_html($categoria['nombre']); ?></h3>
                        <p class="flavor-categoria-descripcion"><?php echo esc_html($categoria['descripcion']); ?></p>
                        <span class="flavor-categoria-total"><?php echo esc_html($categoria['total_cursos']); ?> cursos</span>
                    </div>

                <?php elseif ($estilo_visual === 'iconos') : ?>
                    <div class="flavor-categoria-icono-grande" style="background: <?php echo esc_attr($categoria['color_claro']); ?>;">
                        <span class="flavor-categoria-icono">
                            <?php echo flavor_renderizar_icono_categoria($categoria['icono'], $categoria['color']); ?>
                        </span>
                    </div>
                    <h3 class="flavor-categoria-nombre"><?php echo esc_html($categoria['nombre']); ?></h3>
                    <span class="flavor-categoria-total"><?php echo esc_html($categoria['total_cursos']); ?> cursos</span>

                <?php else : ?>
                    <div class="flavor-categoria-icono-compacto" style="background: <?php echo esc_attr($categoria['color']); ?>;">
                        <span class="flavor-categoria-icono">
                            <?php echo flavor_renderizar_icono_categoria($categoria['icono'], '#ffffff'); ?>
                        </span>
                    </div>
                    <div class="flavor-categoria-texto-compacto">
                        <h3 class="flavor-categoria-nombre"><?php echo esc_html($categoria['nombre']); ?></h3>
                        <span class="flavor-categoria-total"><?php echo esc_html($categoria['total_cursos']); ?> cursos</span>
                    </div>
                    <svg class="flavor-categoria-flecha" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                <?php endif; ?>

            </a>
            <?php endforeach; ?>

        </div>

        <div class="flavor-categorias-footer">
            <a href="#todas-categorias" class="flavor-btn flavor-btn--outline">
                Ver todas las categorías
            </a>
        </div>

    </div>
</section>

<style>
/* Sección de Categorías - Estilos */
.flavor-categorias-section {
    padding: 4rem 1rem;
    background: #ffffff;
}

.flavor-categorias-container {
    max-width: 1280px;
    margin: 0 auto;
}

/* Header */
.flavor-categorias-header {
    text-align: center;
    margin-bottom: 3rem;
}

.flavor-categorias-titulo {
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.75rem 0;
}

.flavor-categorias-subtitulo {
    font-size: 1.125rem;
    color: #64748b;
    margin: 0;
    max-width: 550px;
    margin-left: auto;
    margin-right: auto;
}

/* Grid */
.flavor-categorias-grid {
    display: grid;
    gap: 1.5rem;
}

.flavor-categorias-grid--2-cols {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
}

.flavor-categorias-grid--3-cols {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.flavor-categorias-grid--4-cols {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
}

/* ====== ESTILO TARJETAS ====== */
.flavor-categoria-card--tarjetas {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.flavor-categoria-card--tarjetas:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
}

.flavor-categoria-imagen-wrapper {
    position: relative;
    height: 140px;
    overflow: hidden;
}

.flavor-categoria-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.flavor-categoria-card--tarjetas:hover .flavor-categoria-imagen {
    transform: scale(1.1);
}

.flavor-categoria-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 70%;
    pointer-events: none;
}

.flavor-categoria-card--tarjetas .flavor-categoria-info {
    padding: 1.25rem;
    text-align: center;
}

.flavor-categoria-icono-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border-radius: 1rem;
    margin-bottom: 0.875rem;
    margin-top: -2.5rem;
    position: relative;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.flavor-categoria-icono-wrapper .flavor-categoria-icono {
    width: 28px;
    height: 28px;
}

.flavor-categoria-card--tarjetas .flavor-categoria-nombre {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.flavor-categoria-card--tarjetas .flavor-categoria-descripcion {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0 0 0.75rem 0;
    line-height: 1.5;
}

.flavor-categoria-card--tarjetas .flavor-categoria-total {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6366f1;
}

/* ====== ESTILO ICONOS ====== */
.flavor-categoria-card--iconos {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 2rem 1.5rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.flavor-categoria-card--iconos:hover {
    border-color: transparent;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
}

.flavor-categoria-icono-grande {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 1.25rem;
    margin-bottom: 1.25rem;
    transition: transform 0.3s ease;
}

.flavor-categoria-card--iconos:hover .flavor-categoria-icono-grande {
    transform: scale(1.1);
}

.flavor-categoria-icono-grande .flavor-categoria-icono {
    width: 40px;
    height: 40px;
}

.flavor-categoria-card--iconos .flavor-categoria-nombre {
    font-size: 1.0625rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.375rem 0;
}

.flavor-categoria-card--iconos .flavor-categoria-total {
    font-size: 0.8125rem;
    color: #64748b;
}

/* ====== ESTILO COMPACTO ====== */
.flavor-categoria-card--compacto {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.flavor-categoria-card--compacto:hover {
    border-color: #6366f1;
    background: #f8fafc;
}

.flavor-categoria-icono-compacto {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 0.625rem;
    flex-shrink: 0;
}

.flavor-categoria-icono-compacto .flavor-categoria-icono {
    width: 24px;
    height: 24px;
}

.flavor-categoria-texto-compacto {
    flex-grow: 1;
}

.flavor-categoria-card--compacto .flavor-categoria-nombre {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.125rem 0;
}

.flavor-categoria-card--compacto .flavor-categoria-total {
    font-size: 0.8125rem;
    color: #64748b;
}

.flavor-categoria-flecha {
    color: #94a3b8;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}

.flavor-categoria-card--compacto:hover .flavor-categoria-flecha {
    transform: translateX(4px);
    color: #6366f1;
}

/* Iconos SVG */
.flavor-categoria-icono {
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-categoria-icono svg {
    width: 100%;
    height: 100%;
}

/* Footer */
.flavor-categorias-footer {
    text-align: center;
    margin-top: 2.5rem;
}

.flavor-btn--outline {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 2rem;
    background: transparent;
    color: #6366f1;
    border: 2px solid #6366f1;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-btn--outline:hover {
    background: #6366f1;
    color: #ffffff;
}

/* Responsive */
@media (max-width: 1024px) {
    .flavor-categorias-grid--4-cols {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
}

@media (max-width: 768px) {
    .flavor-categorias-section {
        padding: 3rem 1rem;
    }

    .flavor-categorias-grid {
        gap: 1rem;
    }

    .flavor-categorias-grid--tarjetas,
    .flavor-categorias-grid--iconos {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-categorias-grid--compacto {
        grid-template-columns: 1fr;
    }

    .flavor-categoria-icono-grande {
        width: 64px;
        height: 64px;
    }

    .flavor-categoria-icono-grande .flavor-categoria-icono {
        width: 32px;
        height: 32px;
    }
}

@media (max-width: 480px) {
    .flavor-categorias-header {
        margin-bottom: 2rem;
    }

    .flavor-categorias-subtitulo {
        font-size: 1rem;
    }

    .flavor-categorias-grid--tarjetas,
    .flavor-categorias-grid--iconos {
        grid-template-columns: 1fr;
    }

    .flavor-categoria-card--iconos {
        flex-direction: row;
        text-align: left;
        padding: 1.25rem;
    }

    .flavor-categoria-icono-grande {
        margin-bottom: 0;
        margin-right: 1rem;
        width: 56px;
        height: 56px;
    }
}
</style>

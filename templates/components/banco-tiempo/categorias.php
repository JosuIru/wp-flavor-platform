<?php
/**
 * Template: Grid de Categorías del Banco de Tiempo
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo = $args['titulo'] ?? 'Categorias de Servicios';
$subtitulo = $args['subtitulo'] ?? 'Explora los servicios disponibles en nuestra comunidad';
$categorias = $args['categorias'] ?? [];
$columnas = $args['columnas'] ?? 3;
$mostrar_contador = $args['mostrar_contador'] ?? true;
$enlace_ver_todos = $args['enlace_ver_todos'] ?? '';

// Datos de demostracion si no hay categorias reales
if (empty($categorias)) {
    $categorias = [
        [
            'id' => 1,
            'nombre' => 'Cuidado Personal',
            'descripcion' => 'Servicios de bienestar, peluqueria y estetica',
            'icono' => 'dashicons-heart',
            'color' => '#e74c3c',
            'servicios_count' => 24,
            'enlace' => '#cuidado-personal'
        ],
        [
            'id' => 2,
            'nombre' => 'Educacion y Formacion',
            'descripcion' => 'Clases particulares, idiomas y tutorías',
            'icono' => 'dashicons-welcome-learn-more',
            'color' => '#3498db',
            'servicios_count' => 38,
            'enlace' => '#educacion'
        ],
        [
            'id' => 3,
            'nombre' => 'Hogar y Jardineria',
            'descripcion' => 'Reparaciones, limpieza y mantenimiento',
            'icono' => 'dashicons-admin-home',
            'color' => '#27ae60',
            'servicios_count' => 45,
            'enlace' => '#hogar'
        ],
        [
            'id' => 4,
            'nombre' => 'Tecnologia',
            'descripcion' => 'Soporte informatico y reparacion de dispositivos',
            'icono' => 'dashicons-laptop',
            'color' => '#9b59b6',
            'servicios_count' => 19,
            'enlace' => '#tecnologia'
        ],
        [
            'id' => 5,
            'nombre' => 'Transporte',
            'descripcion' => 'Acompanamiento, mudanzas y recados',
            'icono' => 'dashicons-car',
            'color' => '#f39c12',
            'servicios_count' => 12,
            'enlace' => '#transporte'
        ],
        [
            'id' => 6,
            'nombre' => 'Arte y Creatividad',
            'descripcion' => 'Musica, pintura, fotografia y manualidades',
            'icono' => 'dashicons-art',
            'color' => '#1abc9c',
            'servicios_count' => 31,
            'enlace' => '#arte'
        ],
    ];
}

// Determinar clase de columnas
$clase_columnas = 'flavor-categorias-grid--cols-' . intval($columnas);
?>

<section class="flavor-banco-tiempo-categorias">
    <div class="flavor-categorias-header">
        <?php if ($titulo) : ?>
            <h2 class="flavor-categorias-titulo"><?php echo esc_html($titulo); ?></h2>
        <?php endif; ?>

        <?php if ($subtitulo) : ?>
            <p class="flavor-categorias-subtitulo"><?php echo esc_html($subtitulo); ?></p>
        <?php endif; ?>
    </div>

    <div class="flavor-categorias-grid <?php echo esc_attr($clase_columnas); ?>">
        <?php foreach ($categorias as $categoria) :
            $categoria_id = $categoria['id'] ?? 0;
            $categoria_nombre = $categoria['nombre'] ?? 'Sin nombre';
            $categoria_descripcion = $categoria['descripcion'] ?? '';
            $categoria_icono = $categoria['icono'] ?? 'dashicons-category';
            $categoria_color = $categoria['color'] ?? '#6c757d';
            $categoria_servicios_count = $categoria['servicios_count'] ?? 0;
            $categoria_enlace = $categoria['enlace'] ?? '#';
        ?>
            <a href="<?php echo esc_url($categoria_enlace); ?>"
               class="flavor-categoria-card"
               data-categoria-id="<?php echo esc_attr($categoria_id); ?>"
               style="--flavor-categoria-color: <?php echo esc_attr($categoria_color); ?>">

                <div class="flavor-categoria-icono-wrapper">
                    <span class="dashicons <?php echo esc_attr($categoria_icono); ?> flavor-categoria-icono"></span>
                </div>

                <div class="flavor-categoria-contenido">
                    <h3 class="flavor-categoria-nombre"><?php echo esc_html($categoria_nombre); ?></h3>

                    <?php if ($categoria_descripcion) : ?>
                        <p class="flavor-categoria-descripcion"><?php echo esc_html($categoria_descripcion); ?></p>
                    <?php endif; ?>

                    <?php if ($mostrar_contador && $categoria_servicios_count > 0) : ?>
                        <span class="flavor-categoria-contador">
                            <?php
                            printf(
                                _n('%d servicio', '%d servicios', $categoria_servicios_count, 'flavor-chat-ia'),
                                $categoria_servicios_count
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flavor-categoria-flecha">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($enlace_ver_todos) : ?>
        <div class="flavor-categorias-footer">
            <a href="<?php echo esc_url($enlace_ver_todos); ?>" class="flavor-btn flavor-btn--outline">
                Ver todas las categorias
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </a>
        </div>
    <?php endif; ?>
</section>

<style>
.flavor-banco-tiempo-categorias {
    padding: 3rem 1rem;
    max-width: 1200px;
    margin: 0 auto;
}

.flavor-categorias-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.flavor-categorias-titulo {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 0.75rem 0;
}

.flavor-categorias-subtitulo {
    font-size: 1.125rem;
    color: #6c757d;
    margin: 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.flavor-categorias-grid {
    display: grid;
    gap: 1.5rem;
}

.flavor-categorias-grid--cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.flavor-categorias-grid--cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.flavor-categorias-grid--cols-4 {
    grid-template-columns: repeat(4, 1fr);
}

.flavor-categoria-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.flavor-categoria-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: var(--flavor-categoria-color, #6c757d);
}

.flavor-categoria-icono-wrapper {
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--flavor-categoria-color, #6c757d);
    border-radius: 12px;
    opacity: 0.9;
}

.flavor-categoria-icono {
    font-size: 28px;
    color: #ffffff;
}

.flavor-categoria-contenido {
    flex: 1;
    min-width: 0;
}

.flavor-categoria-nombre {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0 0 0.25rem 0;
}

.flavor-categoria-descripcion {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0 0 0.5rem 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.flavor-categoria-contador {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--flavor-categoria-color, #6c757d);
    background: rgba(0, 0, 0, 0.04);
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
}

.flavor-categoria-flecha {
    flex-shrink: 0;
    color: #c5c5c5;
    transition: all 0.3s ease;
}

.flavor-categoria-card:hover .flavor-categoria-flecha {
    color: var(--flavor-categoria-color, #6c757d);
    transform: translateX(4px);
}

.flavor-categorias-footer {
    text-align: center;
    margin-top: 2rem;
}

.flavor-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.flavor-btn--outline {
    color: #1a1a2e;
    background: transparent;
    border: 2px solid #e0e0e0;
}

.flavor-btn--outline:hover {
    background: #1a1a2e;
    border-color: #1a1a2e;
    color: #ffffff;
}

/* Responsive */
@media (max-width: 992px) {
    .flavor-categorias-grid--cols-4,
    .flavor-categorias-grid--cols-3 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .flavor-banco-tiempo-categorias {
        padding: 2rem 1rem;
    }

    .flavor-categorias-titulo {
        font-size: 1.5rem;
    }

    .flavor-categorias-subtitulo {
        font-size: 1rem;
    }

    .flavor-categorias-grid--cols-4,
    .flavor-categorias-grid--cols-3,
    .flavor-categorias-grid--cols-2 {
        grid-template-columns: 1fr;
    }

    .flavor-categoria-card {
        padding: 1.25rem;
    }

    .flavor-categoria-icono-wrapper {
        width: 48px;
        height: 48px;
    }

    .flavor-categoria-icono {
        font-size: 24px;
    }
}
</style>

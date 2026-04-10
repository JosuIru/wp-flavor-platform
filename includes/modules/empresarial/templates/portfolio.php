<?php
/**
 * Template: Portfolio / Casos de Éxito
 *
 * @package FlavorPlatform
 * @var array $atts Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion = esc_html($atts['titulo']);
$descripcion_seccion = esc_html($atts['descripcion']);
$tipo_layout = sanitize_key($atts['layout']);
$numero_columnas = absint($atts['columnas']);
$limite_proyectos = absint($atts['limite']);

// Obtener proyectos desde base de datos
global $wpdb;
$tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

$proyectos_completados = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, titulo, cliente_nombre, descripcion, presupuesto, created_at
         FROM $tabla_proyectos
         WHERE estado = 'completado'
         ORDER BY created_at DESC
         LIMIT %d",
        $limite_proyectos
    ),
    ARRAY_A
);

// Si no hay proyectos completados, mostrar ejemplos
if (empty($proyectos_completados)) {
    $proyectos_completados = [
        [
            'id'          => 1,
            'titulo'      => __('Transformación Digital', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cliente_nombre' => 'Tech Corp',
            'descripcion' => __('Implementación completa de sistemas digitales que aumentaron la productividad en un 40%.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'categoria'   => __('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'imagen'      => '',
        ],
        [
            'id'          => 2,
            'titulo'      => __('Estrategia de Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cliente_nombre' => 'Retail Plus',
            'descripcion' => __('Campaña de marketing digital que generó un incremento del 150% en ventas online.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'categoria'   => __('Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'imagen'      => '',
        ],
        [
            'id'          => 3,
            'titulo'      => __('Optimización de Procesos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cliente_nombre' => 'Industrias Modernas',
            'descripcion' => __('Reingeniería de procesos que redujo costes operativos en un 25%.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'categoria'   => __('Consultoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'imagen'      => '',
        ],
        [
            'id'          => 4,
            'titulo'      => __('Desarrollo de App', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cliente_nombre' => 'Startup Innovation',
            'descripcion' => __('Desarrollo de aplicación móvil con más de 50.000 descargas en el primer mes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'categoria'   => __('Desarrollo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'imagen'      => '',
        ],
        [
            'id'          => 5,
            'titulo'      => __('Formación Corporativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cliente_nombre' => 'Grupo Financiero',
            'descripcion' => __('Programa de formación para 500 empleados en nuevas metodologías ágiles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'categoria'   => __('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'imagen'      => '',
        ],
        [
            'id'          => 6,
            'titulo'      => __('Infraestructura Cloud', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cliente_nombre' => 'E-Commerce Global',
            'descripcion' => __('Migración a la nube con 99.9% de disponibilidad y reducción de costes del 30%.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'categoria'   => __('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'imagen'      => '',
        ],
    ];
}

// Obtener categorías únicas
$categorias_unicas = [];
foreach ($proyectos_completados as $proyecto) {
    if (!empty($proyecto['categoria']) && !in_array($proyecto['categoria'], $categorias_unicas)) {
        $categorias_unicas[] = $proyecto['categoria'];
    }
}
?>

<section class="flavor-emp-portfolio flavor-emp-portfolio-<?php echo esc_attr($tipo_layout); ?>">
    <div class="flavor-emp-portfolio-header">
        <?php if ($titulo_seccion): ?>
            <h2 class="flavor-emp-seccion-titulo"><?php echo $titulo_seccion; ?></h2>
        <?php endif; ?>
        <?php if ($descripcion_seccion): ?>
            <p class="flavor-emp-seccion-descripcion"><?php echo $descripcion_seccion; ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($categorias_unicas)): ?>
        <div class="flavor-emp-portfolio-filtros">
            <button type="button" class="filtro-btn active" data-categoria="todos">
                <?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <?php foreach ($categorias_unicas as $categoria): ?>
                <button type="button" class="filtro-btn" data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
                    <?php echo esc_html($categoria); ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="flavor-emp-portfolio-grid columnas-<?php echo esc_attr($numero_columnas); ?>">
        <?php foreach ($proyectos_completados as $proyecto):
            $categoria_proyecto = $proyecto['categoria'] ?? '';
            $slug_categoria = sanitize_title($categoria_proyecto);
        ?>
            <article class="flavor-emp-proyecto-card" data-categoria="<?php echo esc_attr($slug_categoria); ?>">
                <div class="proyecto-imagen">
                    <?php if (!empty($proyecto['imagen'])): ?>
                        <img src="<?php echo esc_url($proyecto['imagen']); ?>" alt="<?php echo esc_attr($proyecto['titulo']); ?>">
                    <?php else: ?>
                        <div class="proyecto-imagen-placeholder">
                            <span class="dashicons dashicons-portfolio"></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($categoria_proyecto)): ?>
                        <span class="proyecto-categoria"><?php echo esc_html($categoria_proyecto); ?></span>
                    <?php endif; ?>

                    <div class="proyecto-overlay">
                        <button type="button" class="ver-proyecto" data-id="<?php echo esc_attr($proyecto['id']); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>

                <div class="proyecto-info">
                    <h3 class="proyecto-titulo"><?php echo esc_html($proyecto['titulo']); ?></h3>
                    <span class="proyecto-cliente"><?php echo esc_html($proyecto['cliente_nombre']); ?></span>

                    <?php if (!empty($proyecto['descripcion'])): ?>
                        <p class="proyecto-descripcion"><?php echo esc_html(wp_trim_words($proyecto['descripcion'], 20)); ?></p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Modal de proyecto -->
<div id="modal-proyecto" class="flavor-emp-modal" style="display: none;">
    <div class="flavor-emp-modal-overlay"></div>
    <div class="flavor-emp-modal-content">
        <button type="button" class="flavor-emp-modal-close">&times;</button>
        <div class="modal-body" id="proyecto-detalle">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>

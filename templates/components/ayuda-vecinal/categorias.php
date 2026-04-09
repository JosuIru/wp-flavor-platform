<?php
/**
 * Template: Grid de Categorías de Ayuda Vecinal
 *
 * Variables disponibles en $args:
 * - titulo: Título de la sección
 * - subtitulo: Subtítulo o descripción
 * - categorias: Array de categorías de ayuda
 * - columnas: Número de columnas del grid (2, 3 o 4)
 * - mostrar_contador: Si mostrar contador de voluntarios/solicitudes
 * - estilo: Estilo visual ('cards', 'minimal', 'colorful')
 * - clase_adicional: Clases CSS adicionales
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_seccion = isset($args['titulo']) ? $args['titulo'] : __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo_seccion = isset($args['subtitulo']) ? $args['subtitulo'] : __('Conectamos vecinos que necesitan ayuda con quienes pueden ofrecerla. Juntos somos más fuertes.', FLAVOR_PLATFORM_TEXT_DOMAIN);
$numero_columnas = isset($args['columnas']) ? intval($args['columnas']) : 3;
$mostrar_contador_voluntarios = isset($args['mostrar_contador']) ? (bool) $args['mostrar_contador'] : true;
$estilo_visual = isset($args['estilo']) ? sanitize_key($args['estilo']) : 'cards';
$clase_css_adicional = isset($args['clase_adicional']) ? sanitize_html_class($args['clase_adicional']) : '';

// Categorías de ayuda vecinal (datos de demostración si no hay datos reales)
$categorias_ayuda = isset($args['categorias']) && !empty($args['categorias']) ? $args['categorias'] : array(
    array(
        'id' => 'compras',
        'nombre' => __('Compras y Recados', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Ayuda con la compra de alimentos, medicinas y productos esenciales', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-cart',
        'color' => '#10b981',
        'voluntarios' => 45,
        'solicitudes_activas' => 12,
        'enlace' => '#compras'
    ),
    array(
        'id' => 'acompanamiento',
        'nombre' => __('Acompañamiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Visitas, conversación telefónica o paseos para personas que viven solas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-groups',
        'color' => '#8b5cf6',
        'voluntarios' => 32,
        'solicitudes_activas' => 8,
        'enlace' => '#acompanamiento'
    ),
    array(
        'id' => 'transporte',
        'nombre' => __('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Acompañamiento a citas médicas, gestiones o traslados necesarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-car',
        'color' => '#3b82f6',
        'voluntarios' => 28,
        'solicitudes_activas' => 6,
        'enlace' => '#transporte'
    ),
    array(
        'id' => 'tareas-hogar',
        'nombre' => __('Tareas del Hogar', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Pequeñas reparaciones, limpieza o ayuda con tareas domésticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-admin-home',
        'color' => '#f59e0b',
        'voluntarios' => 21,
        'solicitudes_activas' => 15,
        'enlace' => '#tareas-hogar'
    ),
    array(
        'id' => 'cuidado-mascotas',
        'nombre' => __('Cuidado de Mascotas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Paseo de perros, alimentación temporal o cuidado de animales', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-pets',
        'color' => '#ec4899',
        'voluntarios' => 38,
        'solicitudes_activas' => 4,
        'enlace' => '#cuidado-mascotas'
    ),
    array(
        'id' => 'tecnologia',
        'nombre' => __('Ayuda Tecnológica', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Apoyo con dispositivos, trámites online o videollamadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-laptop',
        'color' => '#06b6d4',
        'voluntarios' => 19,
        'solicitudes_activas' => 9,
        'enlace' => '#tecnologia'
    ),
    array(
        'id' => 'cuidado-ninos',
        'nombre' => __('Cuidado de Niños', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Apoyo puntual con el cuidado de menores en situaciones de emergencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-buddicons-buddypress-logo',
        'color' => '#f97316',
        'voluntarios' => 15,
        'solicitudes_activas' => 3,
        'enlace' => '#cuidado-ninos'
    ),
    array(
        'id' => 'tutoria',
        'nombre' => __('Tutoría y Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Clases de apoyo escolar, idiomas o habilidades básicas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-welcome-learn-more',
        'color' => '#6366f1',
        'voluntarios' => 27,
        'solicitudes_activas' => 11,
        'enlace' => '#tutoria'
    ),
    array(
        'id' => 'otros',
        'nombre' => __('Otras Ayudas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Cualquier otra necesidad que puedas tener, estamos para ayudar', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-heart',
        'color' => '#ef4444',
        'voluntarios' => 52,
        'solicitudes_activas' => 7,
        'enlace' => '#otros'
    )
);

// Calcular totales
$total_voluntarios = 0;
$total_solicitudes_activas = 0;
foreach ($categorias_ayuda as $categoria) {
    $total_voluntarios += isset($categoria['voluntarios']) ? intval($categoria['voluntarios']) : 0;
    $total_solicitudes_activas += isset($categoria['solicitudes_activas']) ? intval($categoria['solicitudes_activas']) : 0;
}

// Limitar columnas entre 2 y 4
$numero_columnas = max(2, min(4, $numero_columnas));

// Generar ID único para el componente
$id_componente = 'flavor-ayuda-vecinal-' . wp_rand(1000, 9999);
?>

<section id="<?php echo esc_attr($id_componente); ?>" class="flavor-ayuda-vecinal flavor-estilo-<?php echo esc_attr($estilo_visual); ?> <?php echo esc_attr($clase_css_adicional); ?>">

    <!-- Encabezado de la sección -->
    <div class="flavor-ayuda-header">
        <div class="flavor-ayuda-header-contenido">
            <span class="flavor-ayuda-etiqueta">
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Comunidad Solidaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="flavor-ayuda-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="flavor-ayuda-subtitulo"><?php echo esc_html($subtitulo_seccion); ?></p>
        </div>

        <?php if ($mostrar_contador_voluntarios) : ?>
        <!-- Estadísticas generales -->
        <div class="flavor-ayuda-estadisticas">
            <div class="flavor-estadistica-item">
                <span class="flavor-estadistica-numero"><?php echo esc_html(number_format_i18n($total_voluntarios)); ?></span>
                <span class="flavor-estadistica-etiqueta"><?php esc_html_e('Voluntarios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="flavor-estadistica-separador"></div>
            <div class="flavor-estadistica-item">
                <span class="flavor-estadistica-numero"><?php echo esc_html(number_format_i18n($total_solicitudes_activas)); ?></span>
                <span class="flavor-estadistica-etiqueta"><?php esc_html_e('Solicitudes activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Grid de categorías -->
    <div class="flavor-categorias-ayuda-grid flavor-columnas-<?php echo esc_attr($numero_columnas); ?>">
        <?php foreach ($categorias_ayuda as $indice_categoria => $categoria) :
            $id_categoria = isset($categoria['id']) ? $categoria['id'] : 'categoria-' . $indice_categoria;
            $nombre_categoria = isset($categoria['nombre']) ? $categoria['nombre'] : '';
            $descripcion_categoria = isset($categoria['descripcion']) ? $categoria['descripcion'] : '';
            $icono_categoria = isset($categoria['icono']) ? $categoria['icono'] : 'dashicons-marker';
            $color_categoria = isset($categoria['color']) ? $categoria['color'] : '#3b82f6';
            $cantidad_voluntarios = isset($categoria['voluntarios']) ? intval($categoria['voluntarios']) : 0;
            $cantidad_solicitudes = isset($categoria['solicitudes_activas']) ? intval($categoria['solicitudes_activas']) : 0;
            $enlace_categoria = isset($categoria['enlace']) ? $categoria['enlace'] : '#';
        ?>
        <article class="flavor-categoria-ayuda-card" data-categoria="<?php echo esc_attr($id_categoria); ?>">
            <a href="<?php echo esc_url($enlace_categoria); ?>" class="flavor-categoria-ayuda-enlace">

                <!-- Icono de la categoría -->
                <div class="flavor-categoria-ayuda-icono" style="--color-categoria: <?php echo esc_attr($color_categoria); ?>">
                    <span class="dashicons <?php echo esc_attr($icono_categoria); ?>"></span>
                </div>

                <!-- Contenido de la tarjeta -->
                <div class="flavor-categoria-ayuda-contenido">
                    <h3 class="flavor-categoria-ayuda-nombre"><?php echo esc_html($nombre_categoria); ?></h3>
                    <p class="flavor-categoria-ayuda-descripcion"><?php echo esc_html($descripcion_categoria); ?></p>

                    <?php if ($mostrar_contador_voluntarios) : ?>
                    <!-- Contadores -->
                    <div class="flavor-categoria-ayuda-stats">
                        <span class="flavor-stat-voluntarios" title="<?php esc_attr_e('Voluntarios disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php echo esc_html($cantidad_voluntarios); ?>
                        </span>
                        <?php if ($cantidad_solicitudes > 0) : ?>
                        <span class="flavor-stat-solicitudes" title="<?php esc_attr_e('Solicitudes activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-megaphone"></span>
                            <?php echo esc_html($cantidad_solicitudes); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Indicador de acción -->
                <div class="flavor-categoria-ayuda-accion">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </div>

                <!-- Barra de color decorativa -->
                <div class="flavor-categoria-ayuda-barra" style="background-color: <?php echo esc_attr($color_categoria); ?>"></div>

            </a>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Llamada a la acción -->
    <div class="flavor-ayuda-cta">
        <div class="flavor-cta-contenido">
            <div class="flavor-cta-texto">
                <h3 class="flavor-cta-titulo"><?php esc_html_e('¿Quieres ayudar?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="flavor-cta-descripcion">
                    <?php esc_html_e('Únete como voluntario y ayuda a tus vecinos cuando más lo necesiten.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>
            <div class="flavor-cta-botones">
                <a href="#ser-voluntario" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Ser Voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="#pedir-ayuda" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-sos"></span>
                    <?php esc_html_e('Necesito Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>

</section>

<style>
/* Contenedor principal */
.flavor-ayuda-vecinal {
    max-width: 1200px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
}

/* Encabezado */
.flavor-ayuda-header {
    text-align: center;
    margin-bottom: 3rem;
}

.flavor-ayuda-header-contenido {
    margin-bottom: 1.5rem;
}

.flavor-ayuda-etiqueta {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.flavor-ayuda-etiqueta .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #dc2626;
}

.flavor-ayuda-titulo {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 1rem 0;
    line-height: 1.2;
}

.flavor-ayuda-subtitulo {
    font-size: 1.125rem;
    color: #64748b;
    margin: 0 auto;
    max-width: 600px;
    line-height: 1.6;
}

/* Estadísticas generales */
.flavor-ayuda-estadisticas {
    display: inline-flex;
    align-items: center;
    gap: 2rem;
    background: #ffffff;
    padding: 1rem 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.flavor-estadistica-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.flavor-estadistica-numero {
    font-size: 2rem;
    font-weight: 800;
    color: #3b82f6;
    line-height: 1;
}

.flavor-estadistica-etiqueta {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.25rem;
}

.flavor-estadistica-separador {
    width: 1px;
    height: 40px;
    background: #e2e8f0;
}

/* Grid de categorías */
.flavor-categorias-ayuda-grid {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.flavor-columnas-2 {
    grid-template-columns: repeat(2, 1fr);
}

.flavor-columnas-3 {
    grid-template-columns: repeat(3, 1fr);
}

.flavor-columnas-4 {
    grid-template-columns: repeat(4, 1fr);
}

/* Tarjeta de categoría */
.flavor-categoria-ayuda-card {
    position: relative;
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.flavor-categoria-ayuda-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.flavor-categoria-ayuda-enlace {
    display: flex;
    flex-direction: column;
    padding: 1.5rem;
    text-decoration: none;
    color: inherit;
    height: 100%;
    position: relative;
}

/* Icono de la categoría */
.flavor-categoria-ayuda-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    background: color-mix(in srgb, var(--color-categoria) 15%, white);
    border-radius: 14px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.flavor-categoria-ayuda-icono .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: var(--color-categoria);
}

.flavor-categoria-ayuda-card:hover .flavor-categoria-ayuda-icono {
    background: var(--color-categoria);
    transform: scale(1.1);
}

.flavor-categoria-ayuda-card:hover .flavor-categoria-ayuda-icono .dashicons {
    color: #ffffff;
}

/* Contenido de la tarjeta */
.flavor-categoria-ayuda-contenido {
    flex: 1;
}

.flavor-categoria-ayuda-nombre {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    transition: color 0.2s ease;
}

.flavor-categoria-ayuda-card:hover .flavor-categoria-ayuda-nombre {
    color: var(--color-categoria);
}

.flavor-categoria-ayuda-descripcion {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

/* Estadísticas de la categoría */
.flavor-categoria-ayuda-stats {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}

.flavor-stat-voluntarios,
.flavor-stat-solicitudes {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.813rem;
    color: #64748b;
}

.flavor-stat-voluntarios .dashicons,
.flavor-stat-solicitudes .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-stat-voluntarios {
    color: #10b981;
}

.flavor-stat-solicitudes {
    color: #f59e0b;
}

/* Indicador de acción */
.flavor-categoria-ayuda-accion {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border-radius: 50%;
    opacity: 0;
    transform: translateX(-10px);
    transition: all 0.3s ease;
}

.flavor-categoria-ayuda-accion .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: #64748b;
}

.flavor-categoria-ayuda-card:hover .flavor-categoria-ayuda-accion {
    opacity: 1;
    transform: translateX(0);
    background: var(--color-categoria);
}

.flavor-categoria-ayuda-card:hover .flavor-categoria-ayuda-accion .dashicons {
    color: #ffffff;
}

/* Barra de color decorativa */
.flavor-categoria-ayuda-barra {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.flavor-categoria-ayuda-card:hover .flavor-categoria-ayuda-barra {
    transform: scaleX(1);
}

/* Llamada a la acción */
.flavor-ayuda-cta {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 20px;
    padding: 2.5rem;
    position: relative;
    overflow: hidden;
}

.flavor-ayuda-cta::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.flavor-cta-contenido {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    position: relative;
    z-index: 1;
}

.flavor-cta-texto {
    flex: 1;
}

.flavor-cta-titulo {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 0.5rem 0;
}

.flavor-cta-descripcion {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
}

.flavor-cta-botones {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

/* Botones */
.flavor-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    font-size: 0.938rem;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.flavor-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.flavor-btn-primary {
    background: #ffffff;
    color: #1d4ed8;
}

.flavor-btn-primary:hover {
    background: #f0f9ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.flavor-btn-secondary {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.flavor-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Estilo minimal */
.flavor-estilo-minimal .flavor-categoria-ayuda-card {
    box-shadow: none;
    border: 2px solid #e2e8f0;
}

.flavor-estilo-minimal .flavor-categoria-ayuda-card:hover {
    border-color: var(--color-categoria);
}

/* Estilo colorful */
.flavor-estilo-colorful .flavor-categoria-ayuda-icono {
    background: var(--color-categoria);
}

.flavor-estilo-colorful .flavor-categoria-ayuda-icono .dashicons {
    color: #ffffff;
}

.flavor-estilo-colorful .flavor-categoria-ayuda-card:hover .flavor-categoria-ayuda-icono {
    transform: rotate(10deg) scale(1.1);
}

/* Responsive */
@media (max-width: 1024px) {
    .flavor-columnas-4 {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-ayuda-vecinal {
        padding: 2rem 1rem;
    }

    .flavor-ayuda-titulo {
        font-size: 1.875rem;
    }

    .flavor-ayuda-estadisticas {
        flex-direction: column;
        gap: 1rem;
    }

    .flavor-estadistica-separador {
        width: 60px;
        height: 1px;
    }

    .flavor-columnas-3,
    .flavor-columnas-4 {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-cta-contenido {
        flex-direction: column;
        text-align: center;
    }

    .flavor-cta-botones {
        flex-direction: column;
        width: 100%;
    }

    .flavor-btn {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .flavor-columnas-2,
    .flavor-columnas-3,
    .flavor-columnas-4 {
        grid-template-columns: 1fr;
    }

    .flavor-categoria-ayuda-enlace {
        flex-direction: row;
        align-items: center;
        gap: 1rem;
    }

    .flavor-categoria-ayuda-icono {
        margin-bottom: 0;
        flex-shrink: 0;
    }

    .flavor-categoria-ayuda-stats {
        margin-top: 0.75rem;
        padding-top: 0.75rem;
    }

    .flavor-categoria-ayuda-accion {
        position: static;
        opacity: 1;
        transform: none;
        margin-left: auto;
    }

    .flavor-ayuda-cta {
        padding: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contenedorAyudaVecinal = document.getElementById('<?php echo esc_js($id_componente); ?>');

    if (contenedorAyudaVecinal) {
        const tarjetasCategorias = contenedorAyudaVecinal.querySelectorAll('.flavor-categoria-ayuda-card');

        // Animación de entrada escalonada
        tarjetasCategorias.forEach(function(tarjeta, indice) {
            tarjeta.style.opacity = '0';
            tarjeta.style.transform = 'translateY(20px)';

            setTimeout(function() {
                tarjeta.style.transition = 'all 0.4s ease';
                tarjeta.style.opacity = '1';
                tarjeta.style.transform = 'translateY(0)';
            }, indice * 100);
        });

        // Animación de contador (si está visible)
        const numerosEstadisticas = contenedorAyudaVecinal.querySelectorAll('.flavor-estadistica-numero');

        const observadorInterseccion = new IntersectionObserver(function(entradas) {
            entradas.forEach(function(entrada) {
                if (entrada.isIntersecting) {
                    numerosEstadisticas.forEach(function(elementoNumero) {
                        const valorFinal = parseInt(elementoNumero.textContent.replace(/\D/g, ''), 10);
                        animarContador(elementoNumero, 0, valorFinal, 1500);
                    });
                    observadorInterseccion.disconnect();
                }
            });
        }, { threshold: 0.5 });

        const contenedorEstadisticas = contenedorAyudaVecinal.querySelector('.flavor-ayuda-estadisticas');
        if (contenedorEstadisticas) {
            observadorInterseccion.observe(contenedorEstadisticas);
        }

        function animarContador(elemento, valorInicial, valorFinal, duracion) {
            const tiempoInicio = performance.now();

            function actualizarContador(tiempoActual) {
                const tiempoTranscurrido = tiempoActual - tiempoInicio;
                const progreso = Math.min(tiempoTranscurrido / duracion, 1);

                // Función de ease-out
                const valorActual = Math.floor(valorInicial + (valorFinal - valorInicial) * (1 - Math.pow(1 - progreso, 3)));

                elemento.textContent = valorActual.toLocaleString();

                if (progreso < 1) {
                    requestAnimationFrame(actualizarContador);
                }
            }

            requestAnimationFrame(actualizarContador);
        }
    }
});
</script>

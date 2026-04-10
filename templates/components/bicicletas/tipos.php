<?php
/**
 * Tipos de Bicicletas - Grid de Opciones
 *
 * @package FlavorPlatform
 * @subpackage Templates/Components/Bicicletas
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : 'Elige tu bicicleta ideal';
$descripcion_seccion = isset($args['descripcion_seccion']) ? $args['descripcion_seccion'] : 'Tenemos diferentes tipos de bicicletas para adaptarnos a tus necesidades';
$tipos_bicicletas = isset($args['tipos_bicicletas']) ? $args['tipos_bicicletas'] : array();
$columnas = isset($args['columnas']) ? $args['columnas'] : 4;
$mostrar_precios = isset($args['mostrar_precios']) ? $args['mostrar_precios'] : true;
$mostrar_disponibilidad = isset($args['mostrar_disponibilidad']) ? $args['mostrar_disponibilidad'] : true;
$color_acento = isset($args['color_acento']) ? $args['color_acento'] : '#00c853';

// Datos de demostración si no hay datos reales
if (empty($tipos_bicicletas)) {
    $tipos_bicicletas = array(
        array(
            'nombre' => 'Urbana Clásica',
            'descripcion' => 'Perfecta para desplazamientos diarios por la ciudad. Ligera, cómoda y fácil de manejar.',
            'imagen' => '',
            'icono' => 'urbana',
            'caracteristicas' => array(
                'Peso: 12 kg',
                'Velocidades: 3',
                'Cesta incluida',
                'Luces LED'
            ),
            'precio_hora' => '1.50',
            'precio_dia' => '8.00',
            'disponibles' => 245,
            'destacada' => false,
            'color_fondo' => '#e8f5e9'
        ),
        array(
            'nombre' => 'Eléctrica',
            'descripcion' => 'Pedaleo asistido para llegar más lejos sin esfuerzo. Ideal para trayectos largos o zonas con cuestas.',
            'imagen' => '',
            'icono' => 'electrica',
            'caracteristicas' => array(
                'Autonomía: 60 km',
                'Velocidad máx: 25 km/h',
                'Motor 250W',
                'Carga rápida'
            ),
            'precio_hora' => '3.00',
            'precio_dia' => '15.00',
            'disponibles' => 128,
            'destacada' => true,
            'color_fondo' => '#e3f2fd'
        ),
        array(
            'nombre' => 'Cargo',
            'descripcion' => 'Transporta tus compras o a los más pequeños con seguridad. Gran capacidad de carga.',
            'imagen' => '',
            'icono' => 'cargo',
            'caracteristicas' => array(
                'Capacidad: 80 kg',
                'Caja frontal',
                'Cinturones seguridad',
                'Asistencia eléctrica'
            ),
            'precio_hora' => '4.00',
            'precio_dia' => '20.00',
            'disponibles' => 45,
            'destacada' => false,
            'color_fondo' => '#fff3e0'
        ),
        array(
            'nombre' => 'Plegable',
            'descripcion' => 'Combínala con el transporte público. Compacta y versátil para tus trayectos multimodales.',
            'imagen' => '',
            'icono' => 'plegable',
            'caracteristicas' => array(
                'Peso: 10 kg',
                'Plegado: 15 seg',
                'Ruedas 20"',
                'Bolsa transporte'
            ),
            'precio_hora' => '2.00',
            'precio_dia' => '10.00',
            'disponibles' => 89,
            'destacada' => false,
            'color_fondo' => '#f3e5f5'
        ),
        array(
            'nombre' => 'Infantil',
            'descripcion' => 'Diseñadas especialmente para los más pequeños. Seguras y divertidas.',
            'imagen' => '',
            'icono' => 'infantil',
            'caracteristicas' => array(
                'Edades: 6-12 años',
                'Ruedas estabilizadoras',
                'Altura ajustable',
                'Casco incluido'
            ),
            'precio_hora' => '1.00',
            'precio_dia' => '5.00',
            'disponibles' => 67,
            'destacada' => false,
            'color_fondo' => '#fce4ec'
        ),
        array(
            'nombre' => 'Tandem',
            'descripcion' => 'Perfecta para pasear en pareja o en familia. Una experiencia única y divertida.',
            'imagen' => '',
            'icono' => 'tandem',
            'caracteristicas' => array(
                '2 plazas',
                'Frenos disco',
                'Cambio 7 vel',
                'Timbre doble'
            ),
            'precio_hora' => '5.00',
            'precio_dia' => '25.00',
            'disponibles' => 23,
            'destacada' => false,
            'color_fondo' => '#e0f7fa'
        ),
        array(
            'nombre' => 'Mountain Bike',
            'descripcion' => 'Para los más aventureros. Perfecta para parques y caminos no asfaltados.',
            'imagen' => '',
            'icono' => 'mountain',
            'caracteristicas' => array(
                'Suspensión delantera',
                '21 velocidades',
                'Neumáticos anchos',
                'Frenos disco'
            ),
            'precio_hora' => '3.50',
            'precio_dia' => '18.00',
            'disponibles' => 56,
            'destacada' => false,
            'color_fondo' => '#efebe9'
        ),
        array(
            'nombre' => 'Adaptada',
            'descripcion' => 'Bicicletas adaptadas para personas con movilidad reducida. Inclusión y libertad.',
            'imagen' => '',
            'icono' => 'adaptada',
            'caracteristicas' => array(
                'Triciclos estables',
                'Hand-bikes',
                'Asistencia eléctrica',
                'Personal de apoyo'
            ),
            'precio_hora' => '0.00',
            'precio_dia' => '0.00',
            'disponibles' => 34,
            'destacada' => false,
            'color_fondo' => '#e8eaf6',
            'etiqueta_especial' => 'Gratuita'
        )
    );
}

// Función para obtener el SVG del icono
function flavor_obtener_icono_bicicleta($tipo_icono) {
    $iconos = array(
        'urbana' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><circle cx="14" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="50" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><path d="M14 44 L24 24 L40 24 L50 44" stroke="currentColor" stroke-width="3" fill="none"/><path d="M24 24 L32 44 L40 24" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="24" cy="20" r="3" fill="currentColor"/><path d="M24 24 L18 24" stroke="currentColor" stroke-width="3"/></svg>',
        'electrica' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><circle cx="14" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="50" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><path d="M14 44 L24 24 L40 24 L50 44" stroke="currentColor" stroke-width="3" fill="none"/><path d="M24 24 L32 44 L40 24" stroke="currentColor" stroke-width="3" fill="none"/><polygon points="30,18 34,18 32,24 36,24 30,34 32,26 28,26" fill="currentColor"/><rect x="28" y="36" width="8" height="6" rx="1" fill="currentColor"/></svg>',
        'cargo' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><circle cx="50" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="10" cy="44" r="6" stroke="currentColor" stroke-width="3" fill="none"/><rect x="4" y="24" width="20" height="16" rx="2" stroke="currentColor" stroke-width="3" fill="none"/><path d="M24 32 L40 24 L50 44" stroke="currentColor" stroke-width="3" fill="none"/><path d="M40 24 L40 44" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="40" cy="20" r="3" fill="currentColor"/></svg>',
        'plegable' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><circle cx="16" cy="46" r="8" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="48" cy="46" r="8" stroke="currentColor" stroke-width="3" fill="none"/><path d="M16 46 L28 26 L40 26 L48 46" stroke="currentColor" stroke-width="3" fill="none"/><path d="M28 26 L32 46 L40 26" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="28" cy="22" r="2" fill="currentColor"/><path d="M32 34 L32 38" stroke="currentColor" stroke-width="2" stroke-dasharray="2,2"/></svg>',
        'infantil' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><circle cx="18" cy="44" r="8" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="46" cy="44" r="8" stroke="currentColor" stroke-width="3" fill="none"/><path d="M18 44 L26 28 L38 28 L46 44" stroke="currentColor" stroke-width="3" fill="none"/><path d="M26 28 L32 44 L38 28" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="26" cy="24" r="2" fill="currentColor"/><circle cx="14" cy="52" r="4" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="50" cy="52" r="4" stroke="currentColor" stroke-width="2" fill="none"/></svg>',
        'tandem' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><circle cx="8" cy="44" r="8" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="56" cy="44" r="8" stroke="currentColor" stroke-width="3" fill="none"/><path d="M8 44 L18 28 L46 28 L56 44" stroke="currentColor" stroke-width="3" fill="none"/><path d="M18 28 L24 44 L32 28 L38 44 L46 28" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="18" cy="24" r="2" fill="currentColor"/><circle cx="46" cy="24" r="2" fill="currentColor"/></svg>',
        'mountain' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><circle cx="14" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="50" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><path d="M14 44 L24 24 L40 24 L50 44" stroke="currentColor" stroke-width="3" fill="none"/><path d="M24 24 L32 44 L40 24" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="24" cy="20" r="3" fill="currentColor"/><path d="M24 24 L24 30" stroke="currentColor" stroke-width="4"/><circle cx="14" cy="44" r="6" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="50" cy="44" r="6" stroke="currentColor" stroke-width="2" fill="none"/></svg>',
        'adaptada' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><circle cx="14" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="50" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><circle cx="32" cy="44" r="10" stroke="currentColor" stroke-width="3" fill="none"/><path d="M14 44 L24 28 L40 28 L50 44" stroke="currentColor" stroke-width="3" fill="none"/><rect x="20" y="20" width="24" height="12" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="32" cy="16" r="4" stroke="currentColor" stroke-width="2" fill="none"/></svg>'
    );

    return isset($iconos[$tipo_icono]) ? $iconos[$tipo_icono] : $iconos['urbana'];
}
?>

<section class="flavor-tipos-bicicletas" id="tipos">
    <div class="flavor-tipos-container">
        <div class="flavor-tipos-header">
            <h2 class="flavor-tipos-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="flavor-tipos-descripcion"><?php echo esc_html($descripcion_seccion); ?></p>
        </div>

        <div class="flavor-tipos-grid" style="--flavor-columnas: <?php echo esc_attr($columnas); ?>;">
            <?php foreach ($tipos_bicicletas as $indice => $bicicleta) :
                $clase_destacada = !empty($bicicleta['destacada']) ? 'flavor-tipo-destacada' : '';
                $color_fondo = isset($bicicleta['color_fondo']) ? $bicicleta['color_fondo'] : '#f8fafc';
                $tiene_etiqueta_especial = isset($bicicleta['etiqueta_especial']) && !empty($bicicleta['etiqueta_especial']);
            ?>
            <article class="flavor-tipo-card <?php echo $clase_destacada; ?>" data-indice="<?php echo esc_attr($indice); ?>">
                <?php if (!empty($bicicleta['destacada'])) : ?>
                <div class="flavor-tipo-badge">Popular</div>
                <?php endif; ?>

                <?php if ($tiene_etiqueta_especial) : ?>
                <div class="flavor-tipo-badge flavor-badge-especial"><?php echo esc_html($bicicleta['etiqueta_especial']); ?></div>
                <?php endif; ?>

                <div class="flavor-tipo-imagen" style="background-color: <?php echo esc_attr($color_fondo); ?>;">
                    <?php if (!empty($bicicleta['imagen'])) : ?>
                        <img src="<?php echo esc_url($bicicleta['imagen']); ?>" alt="<?php echo esc_attr($bicicleta['nombre']); ?>">
                    <?php else : ?>
                        <div class="flavor-tipo-icono" style="color: <?php echo esc_attr($color_acento); ?>;">
                            <?php echo flavor_obtener_icono_bicicleta($bicicleta['icono']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flavor-tipo-contenido">
                    <h3 class="flavor-tipo-nombre"><?php echo esc_html($bicicleta['nombre']); ?></h3>
                    <p class="flavor-tipo-descripcion-card"><?php echo esc_html($bicicleta['descripcion']); ?></p>

                    <?php if (!empty($bicicleta['caracteristicas'])) : ?>
                    <ul class="flavor-tipo-caracteristicas">
                        <?php foreach ($bicicleta['caracteristicas'] as $caracteristica) : ?>
                        <li class="flavor-caracteristica-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr($color_acento); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <?php echo esc_html($caracteristica); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <div class="flavor-tipo-footer">
                        <?php if ($mostrar_precios && isset($bicicleta['precio_hora'])) : ?>
                        <div class="flavor-tipo-precios">
                            <?php if (floatval($bicicleta['precio_hora']) > 0) : ?>
                            <div class="flavor-precio-item">
                                <span class="flavor-precio-valor"><?php echo esc_html($bicicleta['precio_hora']); ?>&euro;</span>
                                <span class="flavor-precio-periodo">/hora</span>
                            </div>
                            <div class="flavor-precio-item flavor-precio-dia">
                                <span class="flavor-precio-valor"><?php echo esc_html($bicicleta['precio_dia']); ?>&euro;</span>
                                <span class="flavor-precio-periodo">/día</span>
                            </div>
                            <?php else : ?>
                            <div class="flavor-precio-item flavor-precio-gratis">
                                <span class="flavor-precio-valor">Gratis</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($mostrar_disponibilidad && isset($bicicleta['disponibles'])) : ?>
                        <div class="flavor-tipo-disponibilidad">
                            <span class="flavor-disponibilidad-punto"></span>
                            <span class="flavor-disponibilidad-texto"><?php echo esc_html($bicicleta['disponibles']); ?> disponibles</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <a href="#reservar" class="flavor-tipo-boton" style="--color-acento: <?php echo esc_attr($color_acento); ?>;">
                        Reservar ahora
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-tipos-cta">
            <p class="flavor-cta-texto">¿No encuentras lo que buscas?</p>
            <a href="#contacto" class="flavor-cta-enlace">
                Contáctanos para soluciones personalizadas
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
        </div>
    </div>
</section>

<style>
.flavor-tipos-bicicletas {
    padding: 5rem 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.flavor-tipos-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.flavor-tipos-header {
    text-align: center;
    margin-bottom: 4rem;
}

.flavor-tipos-titulo {
    font-size: clamp(1.75rem, 4vw, 2.75rem);
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 1rem;
}

.flavor-tipos-descripcion {
    font-size: 1.15rem;
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto;
}

.flavor-tipos-grid {
    display: grid;
    grid-template-columns: repeat(var(--flavor-columnas), 1fr);
    gap: 2rem;
}

.flavor-tipo-card {
    position: relative;
    background: #ffffff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.4s ease;
    display: flex;
    flex-direction: column;
}

.flavor-tipo-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

.flavor-tipo-destacada {
    border: 2px solid #00c853;
}

.flavor-tipo-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: linear-gradient(135deg, #00c853 0%, #00e676 100%);
    color: #ffffff;
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 2;
}

.flavor-badge-especial {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.flavor-tipo-imagen {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.flavor-tipo-imagen img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.flavor-tipo-icono {
    width: 100px;
    height: 100px;
    transition: transform 0.4s ease;
}

.flavor-tipo-card:hover .flavor-tipo-icono {
    transform: scale(1.1);
}

.flavor-tipo-icono svg {
    width: 100%;
    height: 100%;
}

.flavor-tipo-contenido {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.flavor-tipo-nombre {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.flavor-tipo-descripcion-card {
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.6;
    margin: 0 0 1rem 0;
}

.flavor-tipo-caracteristicas {
    list-style: none;
    padding: 0;
    margin: 0 0 1.25rem 0;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.flavor-caracteristica-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    color: #4b5563;
}

.flavor-tipo-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
    margin-top: auto;
}

.flavor-tipo-precios {
    display: flex;
    gap: 1rem;
}

.flavor-precio-item {
    display: flex;
    align-items: baseline;
    gap: 0.15rem;
}

.flavor-precio-valor {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}

.flavor-precio-periodo {
    font-size: 0.8rem;
    color: #9ca3af;
}

.flavor-precio-dia {
    opacity: 0.7;
}

.flavor-precio-dia .flavor-precio-valor {
    font-size: 1rem;
}

.flavor-precio-gratis .flavor-precio-valor {
    color: #00c853;
}

.flavor-tipo-disponibilidad {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.flavor-disponibilidad-punto {
    width: 8px;
    height: 8px;
    background: #00c853;
    border-radius: 50%;
    animation: flavor-pulso 2s infinite;
}

@keyframes flavor-pulso {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.flavor-disponibilidad-texto {
    font-size: 0.8rem;
    color: #6b7280;
}

.flavor-tipo-boton {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.9rem 1.5rem;
    background: var(--color-acento);
    color: #ffffff;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.flavor-tipo-boton:hover {
    filter: brightness(1.1);
    transform: translateY(-2px);
}

.flavor-tipo-boton svg {
    transition: transform 0.3s ease;
}

.flavor-tipo-boton:hover svg {
    transform: translateX(4px);
}

.flavor-tipos-cta {
    text-align: center;
    margin-top: 4rem;
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 16px;
}

.flavor-cta-texto {
    font-size: 1.1rem;
    color: #4b5563;
    margin: 0 0 0.75rem 0;
}

.flavor-cta-enlace {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #00c853;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.flavor-cta-enlace:hover {
    gap: 0.75rem;
}

@media (max-width: 1200px) {
    .flavor-tipos-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 900px) {
    .flavor-tipos-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .flavor-tipos-bicicletas {
        padding: 3rem 0;
    }

    .flavor-tipos-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .flavor-tipo-caracteristicas {
        grid-template-columns: 1fr;
    }

    .flavor-tipo-footer {
        flex-direction: column;
        gap: 0.75rem;
        align-items: flex-start;
    }
}
</style>

<?php
/**
 * Cómo Usar - Pasos del Servicio de Bicicletas
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Bicicletas
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : 'Cómo funciona';
$subtitulo_seccion = isset($args['subtitulo_seccion']) ? $args['subtitulo_seccion'] : 'En solo 4 pasos estarás pedaleando';
$descripcion_seccion = isset($args['descripcion_seccion']) ? $args['descripcion_seccion'] : 'Usar nuestro servicio de bicicletas compartidas es muy sencillo. Sigue estos pasos y comienza tu aventura sobre dos ruedas.';
$pasos = isset($args['pasos']) ? $args['pasos'] : array();
$color_primario = isset($args['color_primario']) ? $args['color_primario'] : '#00c853';
$color_secundario = isset($args['color_secundario']) ? $args['color_secundario'] : '#667eea';
$mostrar_video = isset($args['mostrar_video']) ? $args['mostrar_video'] : false;
$url_video = isset($args['url_video']) ? $args['url_video'] : '';
$mostrar_faq = isset($args['mostrar_faq']) ? $args['mostrar_faq'] : true;
$preguntas_frecuentes = isset($args['preguntas_frecuentes']) ? $args['preguntas_frecuentes'] : array();
$texto_boton_cta = isset($args['texto_boton_cta']) ? $args['texto_boton_cta'] : 'Descargar la App';
$enlace_boton_cta = isset($args['enlace_boton_cta']) ? $args['enlace_boton_cta'] : '#descargar';

// Datos de demostración para pasos si no hay datos reales
if (empty($pasos)) {
    $pasos = array(
        array(
            'numero' => 1,
            'titulo' => 'Descarga la App',
            'descripcion' => 'Disponible en iOS y Android. Regístrate con tu email o redes sociales en menos de un minuto.',
            'icono' => 'descargar',
            'consejos' => array(
                'La app es totalmente gratuita',
                'Acepta notificaciones para ofertas',
                'Verifica tu email para activar'
            )
        ),
        array(
            'numero' => 2,
            'titulo' => 'Encuentra una estación',
            'descripcion' => 'Usa el mapa en tiempo real para localizar la estación más cercana con bicicletas disponibles.',
            'icono' => 'mapa',
            'consejos' => array(
                'Activa la geolocalización',
                'Filtra por tipo de bicicleta',
                'Reserva hasta 15 min antes'
            )
        ),
        array(
            'numero' => 3,
            'titulo' => 'Desbloquea tu bici',
            'descripcion' => 'Escanea el código QR de la bicicleta o introduce el número manualmente. El candado se abrirá automáticamente.',
            'icono' => 'desbloquear',
            'consejos' => array(
                'Revisa el estado de la bici',
                'Ajusta el sillín a tu altura',
                'Comprueba los frenos'
            )
        ),
        array(
            'numero' => 4,
            'titulo' => 'Pedalea y Devuelve',
            'descripcion' => 'Disfruta del paseo y cuando termines, deja la bicicleta en cualquier estación disponible.',
            'icono' => 'pedalear',
            'consejos' => array(
                'Puedes hacer paradas intermedias',
                'Asegura el candado al devolver',
                'Confirma la devolución en la app'
            )
        )
    );
}

// Datos de demostración para FAQ si no hay datos reales
if (empty($preguntas_frecuentes) && $mostrar_faq) {
    $preguntas_frecuentes = array(
        array(
            'pregunta' => '¿Necesito casco para usar las bicicletas?',
            'respuesta' => 'El uso de casco no es obligatorio por ley para mayores de edad, pero lo recomendamos encarecidamente por tu seguridad. En nuestras estaciones principales encontrarás cascos disponibles para alquilar.'
        ),
        array(
            'pregunta' => '¿Qué pasa si la bicicleta se avería durante el trayecto?',
            'respuesta' => 'Reporta la avería directamente desde la app y te indicaremos la estación más cercana donde puedes cambiar de bicicleta sin coste adicional. El tiempo de avería no se cobrará.'
        ),
        array(
            'pregunta' => '¿Puedo dejar la bicicleta en cualquier lugar?',
            'respuesta' => 'No, las bicicletas deben devolverse siempre en una de nuestras estaciones. Dejarla en otro lugar puede suponer un cargo adicional y dificulta que otros usuarios puedan usarla.'
        ),
        array(
            'pregunta' => '¿Cómo se realiza el pago?',
            'respuesta' => 'El pago se realiza automáticamente al finalizar el trayecto mediante la tarjeta registrada en la app. También ofrecemos bonos de viajes y suscripciones mensuales con descuentos.'
        ),
        array(
            'pregunta' => '¿Las bicicletas tienen seguro?',
            'respuesta' => 'Sí, todas nuestras bicicletas incluyen un seguro de responsabilidad civil que te cubre durante el uso del servicio. Consulta las condiciones en la app.'
        )
    );
}

// Función para obtener el icono SVG de cada paso
function flavor_obtener_icono_paso($tipo_icono) {
    $iconos = array(
        'descargar' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
        'mapa' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
        'desbloquear' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>',
        'pedalear' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18.5" cy="17.5" r="3.5"></circle><circle cx="5.5" cy="17.5" r="3.5"></circle><circle cx="15" cy="5" r="1"></circle><path d="M12 17.5V14l-3-3 4-3 2 3h2"></path></svg>',
        'qr' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="3" height="3"></rect><line x1="21" y1="14" x2="21" y2="17"></line><line x1="14" y1="21" x2="17" y2="21"></line><line x1="21" y1="21" x2="21" y2="21"></line></svg>'
    );

    return isset($iconos[$tipo_icono]) ? $iconos[$tipo_icono] : $iconos['pedalear'];
}

// ID único para el componente
$id_componente = 'flavor-como-usar-' . uniqid();
?>

<section class="flavor-como-usar" id="como-usar">
    <div class="flavor-como-usar-container">
        <div class="flavor-como-usar-header">
            <span class="flavor-header-etiqueta" style="color: <?php echo esc_attr($color_primario); ?>;">
                <?php echo esc_html($subtitulo_seccion); ?>
            </span>
            <h2 class="flavor-como-usar-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="flavor-como-usar-descripcion"><?php echo esc_html($descripcion_seccion); ?></p>
        </div>

        <div class="flavor-pasos-contenedor">
            <div class="flavor-pasos-linea" style="background: linear-gradient(180deg, <?php echo esc_attr($color_primario); ?> 0%, <?php echo esc_attr($color_secundario); ?> 100%);"></div>

            <?php foreach ($pasos as $indice => $paso) :
                $es_par = ($indice % 2 === 0);
                $clase_posicion = $es_par ? 'flavor-paso-izquierda' : 'flavor-paso-derecha';
            ?>
            <div class="flavor-paso-item <?php echo $clase_posicion; ?>" data-paso="<?php echo esc_attr($paso['numero']); ?>">
                <div class="flavor-paso-numero" style="background: linear-gradient(135deg, <?php echo esc_attr($color_primario); ?> 0%, <?php echo esc_attr($color_secundario); ?> 100%);">
                    <?php echo esc_html($paso['numero']); ?>
                </div>

                <div class="flavor-paso-card">
                    <div class="flavor-paso-icono" style="color: <?php echo esc_attr($color_primario); ?>;">
                        <?php echo flavor_obtener_icono_paso($paso['icono']); ?>
                    </div>

                    <div class="flavor-paso-contenido">
                        <h3 class="flavor-paso-titulo"><?php echo esc_html($paso['titulo']); ?></h3>
                        <p class="flavor-paso-descripcion"><?php echo esc_html($paso['descripcion']); ?></p>

                        <?php if (!empty($paso['consejos'])) : ?>
                        <div class="flavor-paso-consejos">
                            <span class="flavor-consejos-titulo">Consejos:</span>
                            <ul class="flavor-consejos-lista">
                                <?php foreach ($paso['consejos'] as $consejo) : ?>
                                <li class="flavor-consejo-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr($color_primario); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <?php echo esc_html($consejo); ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($mostrar_video && !empty($url_video)) : ?>
        <div class="flavor-video-tutorial">
            <div class="flavor-video-wrapper">
                <div class="flavor-video-overlay">
                    <button class="flavor-video-play" aria-label="Reproducir video">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                    </button>
                    <span class="flavor-video-texto">Ver video tutorial</span>
                </div>
                <iframe class="flavor-video-iframe" data-src="<?php echo esc_url($url_video); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
        <?php endif; ?>

        <div class="flavor-como-usar-cta">
            <div class="flavor-cta-contenido">
                <h3 class="flavor-cta-titulo">¿Listo para empezar?</h3>
                <p class="flavor-cta-texto">Descarga nuestra app y únete a miles de usuarios que ya disfrutan de la movilidad sostenible.</p>
                <div class="flavor-cta-botones">
                    <a href="<?php echo esc_url($enlace_boton_cta); ?>" class="flavor-boton-cta flavor-boton-ios">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                        </svg>
                        <span class="flavor-boton-texto">
                            <small>Descargar en</small>
                            App Store
                        </span>
                    </a>
                    <a href="<?php echo esc_url($enlace_boton_cta); ?>" class="flavor-boton-cta flavor-boton-android">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.198l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491zM5.864 2.658L16.802 8.99l-2.303 2.303-8.635-8.635z"/>
                        </svg>
                        <span class="flavor-boton-texto">
                            <small>Disponible en</small>
                            Google Play
                        </span>
                    </a>
                </div>
            </div>
            <div class="flavor-cta-imagen">
                <div class="flavor-telefono-mockup">
                    <div class="flavor-telefono-pantalla">
                        <div class="flavor-app-preview">
                            <div class="flavor-app-header">
                                <span class="flavor-app-logo">BikeShare</span>
                            </div>
                            <div class="flavor-app-mapa"></div>
                            <div class="flavor-app-boton">Desbloquear Bici</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($mostrar_faq && !empty($preguntas_frecuentes)) : ?>
        <div class="flavor-faq-seccion" id="<?php echo esc_attr($id_componente); ?>-faq">
            <h3 class="flavor-faq-titulo">Preguntas frecuentes</h3>

            <div class="flavor-faq-lista">
                <?php foreach ($preguntas_frecuentes as $indice_faq => $item_faq) : ?>
                <div class="flavor-faq-item" data-faq="<?php echo esc_attr($indice_faq); ?>">
                    <button class="flavor-faq-pregunta" aria-expanded="false">
                        <span class="flavor-pregunta-texto"><?php echo esc_html($item_faq['pregunta']); ?></span>
                        <span class="flavor-faq-icono">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="flavor-faq-respuesta">
                        <p><?php echo esc_html($item_faq['respuesta']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-como-usar {
    padding: 5rem 0;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 50%, #f8fafc 100%);
    overflow: hidden;
}

.flavor-como-usar-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.flavor-como-usar-header {
    text-align: center;
    margin-bottom: 4rem;
}

.flavor-header-etiqueta {
    display: inline-block;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 0.75rem;
}

.flavor-como-usar-titulo {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    color: #1a1a2e;
    margin: 0 0 1rem 0;
}

.flavor-como-usar-descripcion {
    font-size: 1.15rem;
    color: #6b7280;
    max-width: 650px;
    margin: 0 auto;
    line-height: 1.7;
}

.flavor-pasos-contenedor {
    position: relative;
    max-width: 900px;
    margin: 0 auto 4rem;
}

.flavor-pasos-linea {
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 4px;
    transform: translateX(-50%);
    border-radius: 2px;
    opacity: 0.3;
}

.flavor-paso-item {
    position: relative;
    display: flex;
    align-items: flex-start;
    margin-bottom: 3rem;
}

.flavor-paso-item:last-child {
    margin-bottom: 0;
}

.flavor-paso-izquierda {
    flex-direction: row;
}

.flavor-paso-derecha {
    flex-direction: row-reverse;
}

.flavor-paso-numero {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
    color: #ffffff;
    z-index: 2;
    box-shadow: 0 4px 15px rgba(0, 200, 83, 0.4);
}

.flavor-paso-card {
    width: calc(50% - 50px);
    background: #ffffff;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    display: flex;
    gap: 1.5rem;
    transition: all 0.4s ease;
}

.flavor-paso-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
}

.flavor-paso-izquierda .flavor-paso-card {
    margin-right: auto;
}

.flavor-paso-derecha .flavor-paso-card {
    margin-left: auto;
}

.flavor-paso-icono {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, rgba(0, 200, 83, 0.1) 0%, rgba(102, 126, 234, 0.1) 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-paso-icono svg {
    width: 28px;
    height: 28px;
}

.flavor-paso-contenido {
    flex: 1;
}

.flavor-paso-titulo {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.flavor-paso-descripcion {
    font-size: 0.95rem;
    color: #6b7280;
    line-height: 1.6;
    margin: 0 0 1rem 0;
}

.flavor-paso-consejos {
    background: #f9fafb;
    border-radius: 12px;
    padding: 1rem;
}

.flavor-consejos-titulo {
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.5rem;
}

.flavor-consejos-lista {
    list-style: none;
    padding: 0;
    margin: 0;
}

.flavor-consejo-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #4b5563;
    padding: 0.35rem 0;
}

.flavor-como-usar-cta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
    background: linear-gradient(135deg, #1a1a2e 0%, #2d2d44 100%);
    border-radius: 24px;
    padding: 3rem;
    margin-bottom: 4rem;
    overflow: hidden;
}

.flavor-cta-contenido {
    color: #ffffff;
}

.flavor-cta-titulo {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 700;
    margin: 0 0 1rem 0;
}

.flavor-cta-texto {
    font-size: 1rem;
    opacity: 0.8;
    line-height: 1.6;
    margin: 0 0 1.5rem 0;
}

.flavor-cta-botones {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.flavor-boton-cta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    background: #ffffff;
    color: #1a1a2e;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.flavor-boton-cta:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.flavor-boton-texto {
    display: flex;
    flex-direction: column;
    text-align: left;
    line-height: 1.2;
}

.flavor-boton-texto small {
    font-size: 0.7rem;
    opacity: 0.7;
}

.flavor-boton-texto {
    font-weight: 600;
    font-size: 1rem;
}

.flavor-cta-imagen {
    display: flex;
    justify-content: center;
}

.flavor-telefono-mockup {
    width: 220px;
    height: 440px;
    background: #1a1a2e;
    border-radius: 36px;
    padding: 12px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    border: 3px solid #3d3d5c;
}

.flavor-telefono-pantalla {
    width: 100%;
    height: 100%;
    background: #ffffff;
    border-radius: 26px;
    overflow: hidden;
}

.flavor-app-preview {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.flavor-app-header {
    padding: 1rem;
    background: linear-gradient(135deg, #00c853 0%, #00e676 100%);
    color: #ffffff;
    text-align: center;
}

.flavor-app-logo {
    font-weight: 700;
    font-size: 1rem;
}

.flavor-app-mapa {
    flex: 1;
    background: linear-gradient(180deg, #e8f5e9 0%, #c8e6c9 100%);
    position: relative;
}

.flavor-app-mapa::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    background: #00c853;
    border-radius: 50%;
    border: 3px solid #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.flavor-app-boton {
    padding: 1rem;
    background: #00c853;
    color: #ffffff;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.flavor-faq-seccion {
    max-width: 800px;
    margin: 0 auto;
}

.flavor-faq-titulo {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    text-align: center;
    margin: 0 0 2rem 0;
}

.flavor-faq-lista {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.flavor-faq-item {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.flavor-faq-item:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

.flavor-faq-item.flavor-faq-activa {
    border-color: #00c853;
}

.flavor-faq-pregunta {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    transition: all 0.3s ease;
}

.flavor-faq-pregunta:hover {
    color: #00c853;
}

.flavor-pregunta-texto {
    flex: 1;
    padding-right: 1rem;
}

.flavor-faq-icono {
    flex-shrink: 0;
    transition: transform 0.3s ease;
    color: #9ca3af;
}

.flavor-faq-activa .flavor-faq-icono {
    transform: rotate(180deg);
    color: #00c853;
}

.flavor-faq-respuesta {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease, padding 0.4s ease;
}

.flavor-faq-activa .flavor-faq-respuesta {
    max-height: 500px;
}

.flavor-faq-respuesta p {
    padding: 0 1.5rem 1.25rem;
    margin: 0;
    font-size: 0.95rem;
    color: #6b7280;
    line-height: 1.7;
}

@media (max-width: 900px) {
    .flavor-pasos-linea {
        left: 25px;
    }

    .flavor-paso-item {
        flex-direction: row !important;
    }

    .flavor-paso-numero {
        left: 25px;
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .flavor-paso-card {
        width: calc(100% - 70px);
        margin-left: 70px !important;
        margin-right: 0 !important;
        flex-direction: column;
        gap: 1rem;
    }

    .flavor-como-usar-cta {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .flavor-cta-botones {
        justify-content: center;
    }

    .flavor-cta-imagen {
        order: -1;
    }

    .flavor-telefono-mockup {
        width: 180px;
        height: 360px;
    }
}

@media (max-width: 600px) {
    .flavor-como-usar {
        padding: 3rem 0;
    }

    .flavor-paso-card {
        padding: 1.5rem;
    }

    .flavor-como-usar-cta {
        padding: 2rem;
        border-radius: 16px;
    }

    .flavor-boton-cta {
        width: 100%;
        justify-content: center;
    }

    .flavor-faq-pregunta {
        padding: 1rem;
        font-size: 0.95rem;
    }

    .flavor-faq-respuesta p {
        padding: 0 1rem 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ Accordion
    const preguntasFaq = document.querySelectorAll('.flavor-faq-pregunta');

    preguntasFaq.forEach(function(pregunta) {
        pregunta.addEventListener('click', function() {
            const itemFaq = this.closest('.flavor-faq-item');
            const estaActiva = itemFaq.classList.contains('flavor-faq-activa');

            // Cerrar todas las preguntas
            document.querySelectorAll('.flavor-faq-item').forEach(function(item) {
                item.classList.remove('flavor-faq-activa');
                item.querySelector('.flavor-faq-pregunta').setAttribute('aria-expanded', 'false');
            });

            // Si no estaba activa, abrirla
            if (!estaActiva) {
                itemFaq.classList.add('flavor-faq-activa');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Animación de pasos al hacer scroll
    const pasos = document.querySelectorAll('.flavor-paso-item');
    const opcionesObservador = {
        threshold: 0.2,
        rootMargin: '0px 0px -50px 0px'
    };

    const observadorPasos = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, opcionesObservador);

    pasos.forEach(function(paso) {
        paso.style.opacity = '0';
        paso.style.transform = 'translateY(30px)';
        paso.style.transition = 'all 0.6s ease';
        observadorPasos.observe(paso);
    });

    // Video tutorial (si existe)
    const botonPlay = document.querySelector('.flavor-video-play');
    if (botonPlay) {
        botonPlay.addEventListener('click', function() {
            const contenedorVideo = this.closest('.flavor-video-wrapper');
            const iframe = contenedorVideo.querySelector('.flavor-video-iframe');
            const overlay = contenedorVideo.querySelector('.flavor-video-overlay');

            if (iframe.dataset.src) {
                iframe.src = iframe.dataset.src + '?autoplay=1';
                overlay.style.display = 'none';
            }
        });
    }
});
</script>

<?php
/**
 * Template: CTA App Móvil
 * Call to action para descargar la aplicación móvil con badges de App Store y Google Play
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo = isset($args['titulo']) ? $args['titulo'] : __('Lleva tu comunidad contigo', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo = isset($args['subtitulo']) ? $args['subtitulo'] : __('Descarga la app y mantente conectado con tus vecinos desde cualquier lugar', FLAVOR_PLATFORM_TEXT_DOMAIN);
$descripcion = isset($args['descripcion']) ? $args['descripcion'] : '';
$url_app_store = isset($args['url_app_store']) ? $args['url_app_store'] : '#';
$url_google_play = isset($args['url_google_play']) ? $args['url_google_play'] : '#';
$imagen_preview = isset($args['imagen_preview']) ? $args['imagen_preview'] : '';
$mostrar_qr = isset($args['mostrar_qr']) ? $args['mostrar_qr'] : false;
$url_qr = isset($args['url_qr']) ? $args['url_qr'] : '';
$caracteristicas = isset($args['caracteristicas']) ? $args['caracteristicas'] : array();
$variante = isset($args['variante']) ? $args['variante'] : 'horizontal'; // horizontal, vertical, compacto
$color_fondo = isset($args['color_fondo']) ? $args['color_fondo'] : 'gradiente'; // gradiente, solido, claro
$clases_adicionales = isset($args['clases_adicionales']) ? $args['clases_adicionales'] : '';

// Características por defecto si no se proporcionan
if (empty($caracteristicas)) {
    $caracteristicas = array(
        array(
            'icono' => 'notificaciones',
            'texto' => __('Notificaciones en tiempo real', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ),
        array(
            'icono' => 'chat',
            'texto' => __('Chat con tus vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ),
        array(
            'icono' => 'eventos',
            'texto' => __('Eventos de tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ),
        array(
            'icono' => 'seguro',
            'texto' => __('Acceso seguro y privado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ),
    );
}

// Iconos SVG para características
$iconos_caracteristicas = array(
    'notificaciones' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 6.66667C15 5.34058 14.4732 4.0688 13.5355 3.13113C12.5979 2.19345 11.3261 1.66667 10 1.66667C8.67392 1.66667 7.40215 2.19345 6.46447 3.13113C5.52678 4.0688 5 5.34058 5 6.66667C5 12.5 2.5 14.1667 2.5 14.1667H17.5C17.5 14.1667 15 12.5 15 6.66667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.4417 17.5C11.2952 17.7526 11.0849 17.9622 10.8319 18.1079C10.5788 18.2537 10.292 18.3304 10 18.3304C9.70803 18.3304 9.42117 18.2537 9.16814 18.1079C8.91512 17.9622 8.70484 17.7526 8.55833 17.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'chat' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.5 9.58333C17.5029 10.6832 17.2459 11.7683 16.75 12.75C16.162 13.9265 15.2581 14.916 14.1395 15.6078C13.021 16.2995 11.7319 16.6662 10.4167 16.6667C9.31678 16.6695 8.23176 16.4126 7.25 15.9167L2.5 17.5L4.08333 12.75C3.58744 11.7682 3.33047 10.6832 3.33333 9.58333C3.33384 8.26813 3.70051 6.97905 4.39227 5.86045C5.08402 4.74186 6.07355 3.83797 7.25 3.25C8.23176 2.75411 9.31678 2.49713 10.4167 2.5H10.8333C12.5703 2.59583 14.2109 3.32897 15.441 4.55907C16.671 5.78917 17.4042 7.42971 17.5 9.16667V9.58333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'eventos' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.8333 3.33333H4.16667C3.24619 3.33333 2.5 4.07952 2.5 5V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V5C17.5 4.07952 16.7538 3.33333 15.8333 3.33333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.3333 1.66667V5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.66667 1.66667V5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.5 8.33333H17.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'seguro' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 18.3333C10 18.3333 16.6667 15 16.6667 10V4.16667L10 1.66667L3.33333 4.16667V10C3.33333 15 10 18.3333 10 18.3333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.5 10L9.16667 11.6667L12.5 8.33333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'default' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 18.3333C14.6024 18.3333 18.3333 14.6024 18.3333 10C18.3333 5.39763 14.6024 1.66667 10 1.66667C5.39763 1.66667 1.66667 5.39763 1.66667 10C1.66667 14.6024 5.39763 18.3333 10 18.3333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 6.66667V10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 13.3333H10.0083" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
);

/**
 * Obtener icono SVG para una característica
 */
function flavor_obtener_icono_caracteristica($nombre_icono, $iconos_disponibles) {
    return isset($iconos_disponibles[$nombre_icono]) ? $iconos_disponibles[$nombre_icono] : $iconos_disponibles['default'];
}
?>

<section class="flavor-cta-app flavor-cta-app--<?php echo esc_attr($variante); ?> flavor-cta-app--fondo-<?php echo esc_attr($color_fondo); ?> <?php echo esc_attr($clases_adicionales); ?>">
    <div class="flavor-cta-app__contenedor">
        <div class="flavor-cta-app__contenido">
            <div class="flavor-cta-app__textos">
                <h2 class="flavor-cta-app__titulo"><?php echo esc_html($titulo); ?></h2>
                <p class="flavor-cta-app__subtitulo"><?php echo esc_html($subtitulo); ?></p>

                <?php if ($descripcion) : ?>
                    <p class="flavor-cta-app__descripcion"><?php echo esc_html($descripcion); ?></p>
                <?php endif; ?>

                <?php if (!empty($caracteristicas) && $variante !== 'compacto') : ?>
                    <ul class="flavor-cta-app__caracteristicas">
                        <?php foreach ($caracteristicas as $caracteristica) :
                            $icono_nombre = isset($caracteristica['icono']) ? $caracteristica['icono'] : 'default';
                            $caracteristica_texto = isset($caracteristica['texto']) ? $caracteristica['texto'] : '';
                        ?>
                            <li class="flavor-cta-app__caracteristica">
                                <span class="flavor-cta-app__caracteristica-icono">
                                    <?php echo flavor_obtener_icono_caracteristica($icono_nombre, $iconos_caracteristicas); ?>
                                </span>
                                <span class="flavor-cta-app__caracteristica-texto"><?php echo esc_html($caracteristica_texto); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="flavor-cta-app__descargas">
                <p class="flavor-cta-app__descargas-label"><?php esc_html_e('Disponible en:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="flavor-cta-app__badges">
                    <!-- Badge App Store -->
                    <a
                        href="<?php echo esc_url($url_app_store); ?>"
                        class="flavor-cta-app__badge flavor-cta-app__badge--app-store"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="<?php esc_attr_e('Descargar en App Store', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                        <svg class="flavor-cta-app__badge-icono" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.71 19.5C17.88 20.74 17 21.95 15.66 21.97C14.32 22 13.89 21.18 12.37 21.18C10.84 21.18 10.37 21.95 9.09997 22C7.78997 22.05 6.79997 20.68 5.95997 19.47C4.24997 17 2.93997 12.45 4.69997 9.39C5.56997 7.87 7.12997 6.91 8.81997 6.88C10.1 6.86 11.32 7.75 12.11 7.75C12.89 7.75 14.37 6.68 15.92 6.84C16.57 6.87 18.39 7.1 19.56 8.82C19.47 8.88 17.39 10.1 17.41 12.63C17.44 15.65 20.06 16.66 20.09 16.67C20.06 16.74 19.67 18.11 18.71 19.5ZM13 3.5C13.73 2.67 14.94 2.04 15.94 2C16.07 3.17 15.6 4.35 14.9 5.19C14.21 6.04 13.07 6.7 11.95 6.61C11.8 5.46 12.36 4.26 13 3.5Z"/>
                        </svg>
                        <div class="flavor-cta-app__badge-texto">
                            <span class="flavor-cta-app__badge-small"><?php esc_html_e('Descargar en', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="flavor-cta-app__badge-store">App Store</span>
                        </div>
                    </a>

                    <!-- Badge Google Play -->
                    <a
                        href="<?php echo esc_url($url_google_play); ?>"
                        class="flavor-cta-app__badge flavor-cta-app__badge--google-play"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="<?php esc_attr_e('Disponible en Google Play', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                        <svg class="flavor-cta-app__badge-icono" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.60867 1.81818C3.22272 2.21818 3 2.81818 3 3.56364V20.4364C3 21.1818 3.22272 21.7818 3.60867 22.1818L3.68168 22.2545L13.4033 12.5455V12.4545V12.3636L3.68168 2.65455L3.60867 1.81818Z"/>
                            <path d="M16.6667 15.8182L13.4033 12.5455V12.4545V12.3636L16.6667 9.09091L16.7577 9.14545L20.5318 11.3091C21.5959 11.9091 21.5959 12.9091 20.5318 13.5091L16.7577 15.6727L16.6667 15.8182Z"/>
                            <path d="M16.7577 15.6727L13.4033 12.4545L3.60867 22.1818C3.97663 22.5818 4.56362 22.6364 5.22262 22.2545L16.7577 15.6727Z"/>
                            <path d="M16.7577 9.14545L5.22262 2.56364C4.56362 2.18182 3.97663 2.23636 3.60867 2.63636L13.4033 12.3636L16.7577 9.14545Z"/>
                        </svg>
                        <div class="flavor-cta-app__badge-texto">
                            <span class="flavor-cta-app__badge-small"><?php esc_html_e('Disponible en', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="flavor-cta-app__badge-store">Google Play</span>
                        </div>
                    </a>
                </div>

                <?php if ($mostrar_qr && $url_qr) : ?>
                    <div class="flavor-cta-app__qr">
                        <img
                            src="<?php echo esc_url($url_qr); ?>"
                            alt="<?php esc_attr_e('Código QR para descargar la app', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                            class="flavor-cta-app__qr-imagen"
                        />
                        <span class="flavor-cta-app__qr-texto"><?php esc_html_e('Escanea para descargar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($imagen_preview && $variante === 'horizontal') : ?>
            <div class="flavor-cta-app__preview">
                <img
                    src="<?php echo esc_url($imagen_preview); ?>"
                    alt="<?php esc_attr_e('Vista previa de la aplicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    class="flavor-cta-app__preview-imagen"
                />
            </div>
        <?php elseif ($variante === 'horizontal') : ?>
            <!-- Preview placeholder con mockup de teléfono -->
            <div class="flavor-cta-app__preview flavor-cta-app__preview--placeholder">
                <div class="flavor-cta-app__mockup-telefono">
                    <div class="flavor-cta-app__mockup-pantalla">
                        <div class="flavor-cta-app__mockup-notch"></div>
                        <div class="flavor-cta-app__mockup-contenido">
                            <div class="flavor-cta-app__mockup-header">
                                <div class="flavor-cta-app__mockup-avatar"></div>
                                <div class="flavor-cta-app__mockup-lineas">
                                    <div class="flavor-cta-app__mockup-linea flavor-cta-app__mockup-linea--titulo"></div>
                                    <div class="flavor-cta-app__mockup-linea flavor-cta-app__mockup-linea--subtitulo"></div>
                                </div>
                            </div>
                            <div class="flavor-cta-app__mockup-mensajes">
                                <div class="flavor-cta-app__mockup-mensaje flavor-cta-app__mockup-mensaje--recibido"></div>
                                <div class="flavor-cta-app__mockup-mensaje flavor-cta-app__mockup-mensaje--enviado"></div>
                                <div class="flavor-cta-app__mockup-mensaje flavor-cta-app__mockup-mensaje--recibido flavor-cta-app__mockup-mensaje--corto"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-cta-app {
    border-radius: 16px;
    overflow: hidden;
}

/* Fondos */
.flavor-cta-app--fondo-gradiente {
    background: linear-gradient(135deg, #4A90D9 0%, #7B68EE 100%);
    color: #ffffff;
}

.flavor-cta-app--fondo-solido {
    background: #4A90D9;
    color: #ffffff;
}

.flavor-cta-app--fondo-claro {
    background: #f8f9fa;
    color: #1a1a2e;
    border: 1px solid #e9ecef;
}

.flavor-cta-app__contenedor {
    padding: 2.5rem;
    display: flex;
    align-items: center;
    gap: 3rem;
}

.flavor-cta-app__contenido {
    flex: 1;
}

.flavor-cta-app__textos {
    margin-bottom: 1.5rem;
}

.flavor-cta-app__titulo {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
}

.flavor-cta-app--fondo-claro .flavor-cta-app__titulo {
    color: #1a1a2e;
}

.flavor-cta-app__subtitulo {
    font-size: 1.0625rem;
    margin: 0;
    opacity: 0.9;
    line-height: 1.5;
}

.flavor-cta-app--fondo-claro .flavor-cta-app__subtitulo {
    color: #6c757d;
}

.flavor-cta-app__descripcion {
    font-size: 0.9375rem;
    margin: 1rem 0 0 0;
    opacity: 0.85;
    line-height: 1.6;
}

/* Características */
.flavor-cta-app__caracteristicas {
    list-style: none;
    margin: 1.5rem 0 0 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.flavor-cta-app__caracteristica {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.flavor-cta-app__caracteristica-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    flex-shrink: 0;
}

.flavor-cta-app--fondo-claro .flavor-cta-app__caracteristica-icono {
    background: #e8f4fd;
    color: #4A90D9;
}

.flavor-cta-app__caracteristica-texto {
    font-size: 0.875rem;
    font-weight: 500;
}

.flavor-cta-app--fondo-claro .flavor-cta-app__caracteristica-texto {
    color: #495057;
}

/* Descargas */
.flavor-cta-app__descargas-label {
    font-size: 0.8125rem;
    font-weight: 500;
    margin: 0 0 0.75rem 0;
    opacity: 0.8;
}

.flavor-cta-app--fondo-claro .flavor-cta-app__descargas-label {
    color: #6c757d;
}

.flavor-cta-app__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.flavor-cta-app__badge {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.625rem 1rem;
    background: #000000;
    color: #ffffff;
    border-radius: 8px;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.flavor-cta-app__badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    color: #ffffff;
}

.flavor-cta-app__badge-icono {
    width: 24px;
    height: 24px;
}

.flavor-cta-app__badge-texto {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.flavor-cta-app__badge-small {
    font-size: 0.625rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.flavor-cta-app__badge-store {
    font-size: 0.9375rem;
    font-weight: 600;
}

/* QR */
.flavor-cta-app__qr {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    max-width: 140px;
}

.flavor-cta-app--fondo-claro .flavor-cta-app__qr {
    background: #ffffff;
    border: 1px solid #e9ecef;
}

.flavor-cta-app__qr-imagen {
    width: 100px;
    height: 100px;
    border-radius: 8px;
}

.flavor-cta-app__qr-texto {
    font-size: 0.75rem;
    text-align: center;
    opacity: 0.8;
}

/* Preview */
.flavor-cta-app__preview {
    flex-shrink: 0;
    width: 280px;
}

.flavor-cta-app__preview-imagen {
    width: 100%;
    height: auto;
    display: block;
}

/* Mockup placeholder */
.flavor-cta-app__mockup-telefono {
    background: #1a1a2e;
    border-radius: 32px;
    padding: 8px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.flavor-cta-app__mockup-pantalla {
    background: #ffffff;
    border-radius: 24px;
    overflow: hidden;
    aspect-ratio: 9/16;
    max-height: 400px;
}

.flavor-cta-app__mockup-notch {
    height: 28px;
    background: #f1f3f4;
    position: relative;
}

.flavor-cta-app__mockup-notch::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 60px;
    height: 20px;
    background: #1a1a2e;
    border-radius: 10px;
}

.flavor-cta-app__mockup-contenido {
    padding: 1rem;
}

.flavor-cta-app__mockup-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 1rem;
}

.flavor-cta-app__mockup-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #4A90D9, #7B68EE);
    border-radius: 50%;
}

.flavor-cta-app__mockup-lineas {
    flex: 1;
}

.flavor-cta-app__mockup-linea {
    height: 10px;
    background: #e9ecef;
    border-radius: 5px;
}

.flavor-cta-app__mockup-linea--titulo {
    width: 70%;
    margin-bottom: 6px;
}

.flavor-cta-app__mockup-linea--subtitulo {
    width: 50%;
    height: 8px;
}

.flavor-cta-app__mockup-mensajes {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flavor-cta-app__mockup-mensaje {
    height: 36px;
    border-radius: 12px;
    max-width: 80%;
}

.flavor-cta-app__mockup-mensaje--recibido {
    background: #f1f3f4;
    align-self: flex-start;
}

.flavor-cta-app__mockup-mensaje--enviado {
    background: linear-gradient(135deg, #4A90D9, #7B68EE);
    align-self: flex-end;
}

.flavor-cta-app__mockup-mensaje--corto {
    max-width: 50%;
}

/* Variante Vertical */
.flavor-cta-app--vertical .flavor-cta-app__contenedor {
    flex-direction: column;
    text-align: center;
}

.flavor-cta-app--vertical .flavor-cta-app__caracteristicas {
    justify-content: center;
    grid-template-columns: repeat(2, auto);
}

.flavor-cta-app--vertical .flavor-cta-app__badges {
    justify-content: center;
}

.flavor-cta-app--vertical .flavor-cta-app__qr {
    margin-left: auto;
    margin-right: auto;
}

/* Variante Compacto */
.flavor-cta-app--compacto .flavor-cta-app__contenedor {
    padding: 1.5rem;
    flex-direction: row;
    flex-wrap: wrap;
}

.flavor-cta-app--compacto .flavor-cta-app__contenido {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1.5rem;
    width: 100%;
}

.flavor-cta-app--compacto .flavor-cta-app__textos {
    margin-bottom: 0;
    flex: 1;
    min-width: 200px;
}

.flavor-cta-app--compacto .flavor-cta-app__titulo {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}

.flavor-cta-app--compacto .flavor-cta-app__subtitulo {
    font-size: 0.9375rem;
}

.flavor-cta-app--compacto .flavor-cta-app__descargas {
    flex-shrink: 0;
}

.flavor-cta-app--compacto .flavor-cta-app__descargas-label {
    display: none;
}

.flavor-cta-app--compacto .flavor-cta-app__preview {
    display: none;
}

/* Responsive */
@media (max-width: 1024px) {
    .flavor-cta-app__preview {
        width: 220px;
    }
}

@media (max-width: 768px) {
    .flavor-cta-app__contenedor {
        flex-direction: column;
        padding: 2rem 1.5rem;
    }

    .flavor-cta-app__preview {
        width: 200px;
        order: -1;
    }

    .flavor-cta-app__titulo {
        font-size: 1.5rem;
    }

    .flavor-cta-app__caracteristicas {
        grid-template-columns: 1fr;
    }

    .flavor-cta-app--compacto .flavor-cta-app__contenido {
        flex-direction: column;
        text-align: center;
    }

    .flavor-cta-app--compacto .flavor-cta-app__badges {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .flavor-cta-app__contenedor {
        padding: 1.5rem 1rem;
    }

    .flavor-cta-app__badges {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-cta-app__badge {
        justify-content: center;
    }

    .flavor-cta-app__preview {
        display: none;
    }

    .flavor-cta-app__qr {
        display: none;
    }
}
</style>

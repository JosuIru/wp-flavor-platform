<?php
/**
 * Template: Radio Player - Reproductor de Radio en Vivo
 * Reproductor de radio con controles de play/pause, volumen y programa actual
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$nombre_radio = isset($args['nombre_radio']) ? $args['nombre_radio'] : __('Radio Comunitaria', 'flavor-chat-ia');
$frecuencia = isset($args['frecuencia']) ? $args['frecuencia'] : '98.5 FM';
$stream_url = isset($args['stream_url']) ? $args['stream_url'] : '';
$mostrar_programa = isset($args['mostrar_programa']) ? $args['mostrar_programa'] : true;
$mostrar_horario = isset($args['mostrar_horario']) ? $args['mostrar_horario'] : true;
$mostrar_oyentes = isset($args['mostrar_oyentes']) ? $args['mostrar_oyentes'] : true;
$estilo_reproductor = isset($args['estilo']) ? $args['estilo'] : 'completo'; // 'completo', 'compacto', 'mini'
$autoplay = isset($args['autoplay']) ? $args['autoplay'] : false;
$volumen_inicial = isset($args['volumen_inicial']) ? intval($args['volumen_inicial']) : 75;

// Datos de demostración del programa actual
$programa_actual = isset($args['programa_actual']) ? $args['programa_actual'] : array(
    'nombre' => 'Buenos Días Comunidad',
    'conductor' => 'María González',
    'descripcion' => 'El mejor programa matutino con noticias locales, música y entrevistas.',
    'imagen' => 'https://picsum.photos/seed/radio1/200/200',
    'hora_inicio' => '06:00',
    'hora_fin' => '10:00',
    'en_vivo' => true
);

// Datos de demostración del próximo programa
$proximo_programa = isset($args['proximo_programa']) ? $args['proximo_programa'] : array(
    'nombre' => 'Música Sin Fronteras',
    'conductor' => 'DJ Pepe Luna',
    'hora_inicio' => '10:00',
    'hora_fin' => '12:00'
);

// Oyentes actuales (demo)
$oyentes_actuales = isset($args['oyentes']) ? intval($args['oyentes']) : 234;

// ID único para el reproductor
$reproductor_id = 'flavor-radio-' . uniqid();
?>

<div class="flavor-radio-reproductor flavor-radio-reproductor--<?php echo esc_attr($estilo_reproductor); ?>"
     id="<?php echo esc_attr($reproductor_id); ?>"
     data-stream-url="<?php echo esc_url($stream_url); ?>"
     data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
     data-volumen-inicial="<?php echo esc_attr($volumen_inicial); ?>">

    <?php if ($estilo_reproductor === 'completo'): ?>
    <!-- ===== REPRODUCTOR COMPLETO ===== -->
    <div class="flavor-radio-container">

        <!-- Cabecera con info de la radio -->
        <header class="flavor-radio-header">
            <div class="flavor-radio-branding">
                <div class="flavor-radio-logo">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="2"></circle>
                        <path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"></path>
                    </svg>
                </div>
                <div class="flavor-radio-info">
                    <h2 class="flavor-radio-nombre"><?php echo esc_html($nombre_radio); ?></h2>
                    <span class="flavor-radio-frecuencia"><?php echo esc_html($frecuencia); ?></span>
                </div>
            </div>

            <?php if ($programa_actual['en_vivo']): ?>
            <div class="flavor-radio-estado">
                <span class="flavor-radio-en-vivo">
                    <span class="flavor-radio-en-vivo-punto"></span>
                    <?php esc_html_e('EN VIVO', 'flavor-chat-ia'); ?>
                </span>
                <?php if ($mostrar_oyentes): ?>
                <span class="flavor-radio-oyentes">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span class="flavor-radio-oyentes-numero"><?php echo esc_html($oyentes_actuales); ?></span>
                    <?php esc_html_e('escuchando', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </header>

        <!-- Programa actual -->
        <?php if ($mostrar_programa && $programa_actual): ?>
        <div class="flavor-radio-programa">
            <div class="flavor-radio-programa-imagen">
                <img src="<?php echo esc_url($programa_actual['imagen']); ?>"
                     alt="<?php echo esc_attr($programa_actual['nombre']); ?>"
                     loading="lazy">
                <div class="flavor-radio-visualizador">
                    <span class="flavor-radio-barra"></span>
                    <span class="flavor-radio-barra"></span>
                    <span class="flavor-radio-barra"></span>
                    <span class="flavor-radio-barra"></span>
                    <span class="flavor-radio-barra"></span>
                </div>
            </div>
            <div class="flavor-radio-programa-info">
                <span class="flavor-radio-programa-etiqueta"><?php esc_html_e('Ahora sonando', 'flavor-chat-ia'); ?></span>
                <h3 class="flavor-radio-programa-nombre"><?php echo esc_html($programa_actual['nombre']); ?></h3>
                <p class="flavor-radio-programa-conductor">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 14c3.31 0 6-2.69 6-6s-2.69-6-6-6-6 2.69-6 6 2.69 6 6 6z"></path>
                        <path d="M2 21c0-3.31 4.03-6 10-6s10 2.69 10 6"></path>
                    </svg>
                    <?php echo esc_html($programa_actual['conductor']); ?>
                </p>
                <?php if ($mostrar_horario): ?>
                <p class="flavor-radio-programa-horario">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php echo esc_html($programa_actual['hora_inicio']); ?> - <?php echo esc_html($programa_actual['hora_fin']); ?>
                </p>
                <?php endif; ?>
                <p class="flavor-radio-programa-descripcion"><?php echo esc_html($programa_actual['descripcion']); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Controles principales -->
        <div class="flavor-radio-controles">
            <div class="flavor-radio-controles-principales">
                <!-- Botón de retroceder -->
                <button type="button" class="flavor-radio-btn flavor-radio-btn-retroceder"
                        aria-label="<?php esc_attr_e('Retroceder 10 segundos', 'flavor-chat-ia'); ?>"
                        title="<?php esc_attr_e('Retroceder 10 segundos', 'flavor-chat-ia'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="11 19 2 12 11 5 11 19"></polygon>
                        <polygon points="22 19 13 12 22 5 22 19"></polygon>
                    </svg>
                </button>

                <!-- Botón principal play/pause -->
                <button type="button" class="flavor-radio-btn flavor-radio-btn-play"
                        aria-label="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
                    <svg class="flavor-icono-play" width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <svg class="flavor-icono-pause" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                        <rect x="6" y="4" width="4" height="16"></rect>
                        <rect x="14" y="4" width="4" height="16"></rect>
                    </svg>
                </button>

                <!-- Botón de adelantar -->
                <button type="button" class="flavor-radio-btn flavor-radio-btn-adelantar"
                        aria-label="<?php esc_attr_e('Adelantar 10 segundos', 'flavor-chat-ia'); ?>"
                        title="<?php esc_attr_e('Adelantar 10 segundos', 'flavor-chat-ia'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 19 22 12 13 5 13 19"></polygon>
                        <polygon points="2 19 11 12 2 5 2 19"></polygon>
                    </svg>
                </button>
            </div>

            <!-- Control de volumen -->
            <div class="flavor-radio-volumen">
                <button type="button" class="flavor-radio-btn flavor-radio-btn-volumen"
                        aria-label="<?php esc_attr_e('Silenciar', 'flavor-chat-ia'); ?>">
                    <svg class="flavor-icono-volumen-alto" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                    <svg class="flavor-icono-volumen-bajo" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                    <svg class="flavor-icono-volumen-mudo" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <line x1="23" y1="9" x2="17" y2="15"></line>
                        <line x1="17" y1="9" x2="23" y2="15"></line>
                    </svg>
                </button>
                <div class="flavor-radio-volumen-slider">
                    <input type="range"
                           class="flavor-radio-volumen-input"
                           min="0"
                           max="100"
                           value="<?php echo esc_attr($volumen_inicial); ?>"
                           aria-label="<?php esc_attr_e('Volumen', 'flavor-chat-ia'); ?>">
                    <div class="flavor-radio-volumen-track">
                        <div class="flavor-radio-volumen-fill" style="width: <?php echo esc_attr($volumen_inicial); ?>%;"></div>
                    </div>
                </div>
                <span class="flavor-radio-volumen-valor"><?php echo esc_html($volumen_inicial); ?>%</span>
            </div>
        </div>

        <!-- Próximo programa -->
        <?php if ($proximo_programa): ?>
        <div class="flavor-radio-proximo">
            <span class="flavor-radio-proximo-etiqueta">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <?php esc_html_e('A continuación', 'flavor-chat-ia'); ?>
            </span>
            <div class="flavor-radio-proximo-info">
                <span class="flavor-radio-proximo-nombre"><?php echo esc_html($proximo_programa['nombre']); ?></span>
                <span class="flavor-radio-proximo-horario"><?php echo esc_html($proximo_programa['hora_inicio']); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Acciones adicionales -->
        <footer class="flavor-radio-footer">
            <button type="button" class="flavor-radio-accion" aria-label="<?php esc_attr_e('Compartir', 'flavor-chat-ia'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="18" cy="5" r="3"></circle>
                    <circle cx="6" cy="12" r="3"></circle>
                    <circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                </svg>
                <?php esc_html_e('Compartir', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-radio-accion" aria-label="<?php esc_attr_e('Ver programación', 'flavor-chat-ia'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <?php esc_html_e('Programación', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-radio-accion flavor-radio-accion--favorito" aria-label="<?php esc_attr_e('Agregar a favoritos', 'flavor-chat-ia'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <?php esc_html_e('Favorito', 'flavor-chat-ia'); ?>
            </button>
        </footer>
    </div>

    <?php elseif ($estilo_reproductor === 'compacto'): ?>
    <!-- ===== REPRODUCTOR COMPACTO ===== -->
    <div class="flavor-radio-container flavor-radio-container--compacto">
        <div class="flavor-radio-compacto-izquierda">
            <?php if ($programa_actual['en_vivo']): ?>
            <span class="flavor-radio-en-vivo flavor-radio-en-vivo--sm">
                <span class="flavor-radio-en-vivo-punto"></span>
            </span>
            <?php endif; ?>

            <div class="flavor-radio-compacto-info">
                <span class="flavor-radio-nombre flavor-radio-nombre--sm"><?php echo esc_html($nombre_radio); ?></span>
                <span class="flavor-radio-programa-nombre flavor-radio-programa-nombre--sm">
                    <?php echo esc_html($programa_actual['nombre']); ?>
                </span>
            </div>
        </div>

        <div class="flavor-radio-compacto-controles">
            <button type="button" class="flavor-radio-btn flavor-radio-btn-play flavor-radio-btn-play--sm"
                    aria-label="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
                <svg class="flavor-icono-play" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                <svg class="flavor-icono-pause" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                    <rect x="6" y="4" width="4" height="16"></rect>
                    <rect x="14" y="4" width="4" height="16"></rect>
                </svg>
            </button>

            <div class="flavor-radio-volumen flavor-radio-volumen--sm">
                <button type="button" class="flavor-radio-btn flavor-radio-btn-volumen flavor-radio-btn--sm"
                        aria-label="<?php esc_attr_e('Silenciar', 'flavor-chat-ia'); ?>">
                    <svg class="flavor-icono-volumen-alto" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                    <svg class="flavor-icono-volumen-mudo" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <line x1="23" y1="9" x2="17" y2="15"></line>
                        <line x1="17" y1="9" x2="23" y2="15"></line>
                    </svg>
                </button>
                <input type="range"
                       class="flavor-radio-volumen-input flavor-radio-volumen-input--sm"
                       min="0"
                       max="100"
                       value="<?php echo esc_attr($volumen_inicial); ?>"
                       aria-label="<?php esc_attr_e('Volumen', 'flavor-chat-ia'); ?>">
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ===== REPRODUCTOR MINI ===== -->
    <div class="flavor-radio-container flavor-radio-container--mini">
        <?php if ($programa_actual['en_vivo']): ?>
        <span class="flavor-radio-en-vivo flavor-radio-en-vivo--xs">
            <span class="flavor-radio-en-vivo-punto"></span>
        </span>
        <?php endif; ?>

        <button type="button" class="flavor-radio-btn flavor-radio-btn-play flavor-radio-btn-play--xs"
                aria-label="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
            <svg class="flavor-icono-play" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="5 3 19 12 5 21 5 3"></polygon>
            </svg>
            <svg class="flavor-icono-pause" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                <rect x="6" y="4" width="4" height="16"></rect>
                <rect x="14" y="4" width="4" height="16"></rect>
            </svg>
        </button>

        <span class="flavor-radio-mini-texto">
            <strong><?php echo esc_html($frecuencia); ?></strong>
        </span>
    </div>
    <?php endif; ?>

    <!-- Audio element (oculto) -->
    <audio class="flavor-radio-audio" preload="none">
        <?php if ($stream_url): ?>
        <source src="<?php echo esc_url($stream_url); ?>" type="audio/mpeg">
        <?php endif; ?>
        <?php esc_html_e('Tu navegador no soporta el elemento de audio.', 'flavor-chat-ia'); ?>
    </audio>
</div>

<style>
/* ===== ESTILOS PARA REPRODUCTOR DE RADIO ===== */

/* Variables CSS */
.flavor-radio-reproductor {
    --flavor-radio-primary: #4f46e5;
    --flavor-radio-primary-dark: #4338ca;
    --flavor-radio-bg: #ffffff;
    --flavor-radio-bg-secondary: #f8f9fa;
    --flavor-radio-text: #1a1a2e;
    --flavor-radio-text-secondary: #6c757d;
    --flavor-radio-border: #e2e8f0;
    --flavor-radio-live: #ef4444;
    --flavor-radio-success: #10b981;
}

.flavor-radio-reproductor {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

.flavor-radio-audio {
    display: none;
}

/* ===== REPRODUCTOR COMPLETO ===== */
.flavor-radio-reproductor--completo .flavor-radio-container {
    background: var(--flavor-radio-bg);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    max-width: 400px;
}

/* Header */
.flavor-radio-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem;
    background: linear-gradient(135deg, var(--flavor-radio-primary), var(--flavor-radio-primary-dark));
    color: white;
}

.flavor-radio-branding {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-radio-logo {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-radio-nombre {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
}

.flavor-radio-frecuencia {
    font-size: 0.875rem;
    opacity: 0.9;
}

.flavor-radio-estado {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.flavor-radio-en-vivo {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: var(--flavor-radio-live);
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.flavor-radio-en-vivo-punto {
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    animation: flavor-radio-pulso 1.5s ease-in-out infinite;
}

@keyframes flavor-radio-pulso {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
}

.flavor-radio-oyentes {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8125rem;
    opacity: 0.9;
}

/* Programa actual */
.flavor-radio-programa {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--flavor-radio-bg-secondary);
}

.flavor-radio-programa-imagen {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
}

.flavor-radio-programa-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-radio-visualizador {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: flex-end;
    gap: 3px;
    height: 20px;
}

.flavor-radio-barra {
    width: 4px;
    background: white;
    border-radius: 2px;
    animation: flavor-radio-ecualizador 0.5s ease-in-out infinite alternate;
}

.flavor-radio-barra:nth-child(1) { height: 40%; animation-delay: 0s; }
.flavor-radio-barra:nth-child(2) { height: 70%; animation-delay: 0.1s; }
.flavor-radio-barra:nth-child(3) { height: 50%; animation-delay: 0.2s; }
.flavor-radio-barra:nth-child(4) { height: 80%; animation-delay: 0.3s; }
.flavor-radio-barra:nth-child(5) { height: 60%; animation-delay: 0.4s; }

@keyframes flavor-radio-ecualizador {
    0% { transform: scaleY(0.5); }
    100% { transform: scaleY(1); }
}

.flavor-radio-reproductor:not(.flavor-radio-reproductor--reproduciendo) .flavor-radio-barra {
    animation: none;
    height: 20% !important;
}

.flavor-radio-programa-info {
    flex: 1;
    min-width: 0;
}

.flavor-radio-programa-etiqueta {
    display: inline-block;
    font-size: 0.75rem;
    color: var(--flavor-radio-primary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.flavor-radio-programa-nombre {
    font-size: 1rem;
    font-weight: 600;
    color: var(--flavor-radio-text);
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.flavor-radio-programa-conductor,
.flavor-radio-programa-horario {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8125rem;
    color: var(--flavor-radio-text-secondary);
    margin: 0 0 0.25rem 0;
}

.flavor-radio-programa-descripcion {
    font-size: 0.8125rem;
    color: var(--flavor-radio-text-secondary);
    margin: 0.5rem 0 0 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Controles */
.flavor-radio-controles {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.25rem;
}

.flavor-radio-controles-principales {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.flavor-radio-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    color: var(--flavor-radio-text);
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-radio-btn:hover {
    color: var(--flavor-radio-primary);
}

.flavor-radio-btn-retroceder,
.flavor-radio-btn-adelantar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--flavor-radio-bg-secondary);
}

.flavor-radio-btn-retroceder:hover,
.flavor-radio-btn-adelantar:hover {
    background: var(--flavor-radio-border);
}

.flavor-radio-btn-play {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--flavor-radio-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
}

.flavor-radio-btn-play:hover {
    background: var(--flavor-radio-primary-dark);
    color: white;
    transform: scale(1.05);
}

/* Volumen */
.flavor-radio-volumen {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-radio-btn-volumen {
    width: 36px;
    height: 36px;
    border-radius: 8px;
}

.flavor-radio-volumen-slider {
    position: relative;
    flex: 1;
    height: 6px;
}

.flavor-radio-volumen-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.flavor-radio-volumen-track {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--flavor-radio-border);
    border-radius: 3px;
}

.flavor-radio-volumen-fill {
    height: 100%;
    background: var(--flavor-radio-primary);
    border-radius: 3px;
    transition: width 0.1s ease;
}

.flavor-radio-volumen-valor {
    font-size: 0.75rem;
    color: var(--flavor-radio-text-secondary);
    min-width: 36px;
    text-align: right;
}

/* Próximo programa */
.flavor-radio-proximo {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    background: var(--flavor-radio-bg-secondary);
    border-top: 1px solid var(--flavor-radio-border);
}

.flavor-radio-proximo-etiqueta {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: var(--flavor-radio-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.flavor-radio-proximo-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.flavor-radio-proximo-nombre {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--flavor-radio-text);
}

.flavor-radio-proximo-horario {
    font-size: 0.8125rem;
    color: var(--flavor-radio-text-secondary);
}

/* Footer / Acciones */
.flavor-radio-footer {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--flavor-radio-border);
}

.flavor-radio-accion {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0.5rem 1rem;
    border: none;
    background: transparent;
    color: var(--flavor-radio-text-secondary);
    font-size: 0.8125rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-radio-accion:hover {
    background: var(--flavor-radio-bg-secondary);
    color: var(--flavor-radio-primary);
}

.flavor-radio-accion--favorito.flavor-radio-accion--activo {
    color: var(--flavor-radio-live);
}

.flavor-radio-accion--favorito.flavor-radio-accion--activo svg {
    fill: currentColor;
}

/* ===== REPRODUCTOR COMPACTO ===== */
.flavor-radio-reproductor--compacto .flavor-radio-container--compacto {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: var(--flavor-radio-bg);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.flavor-radio-compacto-izquierda {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-radio-en-vivo--sm {
    padding: 4px 8px;
    font-size: 0;
}

.flavor-radio-compacto-info {
    display: flex;
    flex-direction: column;
}

.flavor-radio-nombre--sm {
    font-size: 0.75rem;
    color: var(--flavor-radio-text-secondary);
}

.flavor-radio-programa-nombre--sm {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--flavor-radio-text);
}

.flavor-radio-compacto-controles {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-radio-btn-play--sm {
    width: 40px;
    height: 40px;
}

.flavor-radio-volumen--sm {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.flavor-radio-btn--sm {
    width: 32px;
    height: 32px;
}

.flavor-radio-volumen-input--sm {
    width: 80px;
    height: 4px;
    -webkit-appearance: none;
    appearance: none;
    background: var(--flavor-radio-border);
    border-radius: 2px;
    cursor: pointer;
}

.flavor-radio-volumen-input--sm::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 12px;
    height: 12px;
    background: var(--flavor-radio-primary);
    border-radius: 50%;
    cursor: pointer;
}

/* ===== REPRODUCTOR MINI ===== */
.flavor-radio-reproductor--mini .flavor-radio-container--mini {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--flavor-radio-bg);
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.flavor-radio-en-vivo--xs {
    padding: 2px 6px;
    font-size: 0;
}

.flavor-radio-en-vivo--xs .flavor-radio-en-vivo-punto {
    width: 6px;
    height: 6px;
}

.flavor-radio-btn-play--xs {
    width: 28px;
    height: 28px;
    background: var(--flavor-radio-primary);
    color: white;
    border-radius: 50%;
}

.flavor-radio-mini-texto {
    font-size: 0.8125rem;
    color: var(--flavor-radio-text);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 480px) {
    .flavor-radio-reproductor--completo .flavor-radio-container {
        max-width: 100%;
        border-radius: 0;
    }

    .flavor-radio-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .flavor-radio-estado {
        flex-direction: row;
        align-items: center;
        width: 100%;
    }

    .flavor-radio-programa {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .flavor-radio-programa-imagen {
        width: 120px;
        height: 120px;
    }

    .flavor-radio-programa-conductor,
    .flavor-radio-programa-horario {
        justify-content: center;
    }

    .flavor-radio-footer {
        flex-wrap: wrap;
    }

    .flavor-radio-volumen--sm {
        display: none;
    }
}
</style>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const reproductoresRadio = document.querySelectorAll('.flavor-radio-reproductor');

        reproductoresRadio.forEach(function(contenedorReproductor) {
            const elementoAudio = contenedorReproductor.querySelector('.flavor-radio-audio');
            const botonPlay = contenedorReproductor.querySelector('.flavor-radio-btn-play');
            const iconoPlay = contenedorReproductor.querySelector('.flavor-icono-play');
            const iconoPause = contenedorReproductor.querySelector('.flavor-icono-pause');
            const botonVolumen = contenedorReproductor.querySelector('.flavor-radio-btn-volumen');
            const inputVolumen = contenedorReproductor.querySelector('.flavor-radio-volumen-input');
            const rellenoVolumen = contenedorReproductor.querySelector('.flavor-radio-volumen-fill');
            const valorVolumen = contenedorReproductor.querySelector('.flavor-radio-volumen-valor');
            const botonRetroceder = contenedorReproductor.querySelector('.flavor-radio-btn-retroceder');
            const botonAdelantar = contenedorReproductor.querySelector('.flavor-radio-btn-adelantar');
            const botonFavorito = contenedorReproductor.querySelector('.flavor-radio-accion--favorito');

            const urlStream = contenedorReproductor.dataset.streamUrl;
            const volumenInicial = parseInt(contenedorReproductor.dataset.volumenInicial) || 75;
            const autoplay = contenedorReproductor.dataset.autoplay === 'true';

            let estaReproduciendo = false;
            let estaSilenciado = false;
            let volumenAnterior = volumenInicial;

            // Inicializar volumen
            if (elementoAudio) {
                elementoAudio.volume = volumenInicial / 100;
            }

            // Función para actualizar iconos de play/pause
            function actualizarIconosReproduccion() {
                if (iconoPlay && iconoPause) {
                    iconoPlay.style.display = estaReproduciendo ? 'none' : 'block';
                    iconoPause.style.display = estaReproduciendo ? 'block' : 'none';
                }

                if (estaReproduciendo) {
                    contenedorReproductor.classList.add('flavor-radio-reproductor--reproduciendo');
                } else {
                    contenedorReproductor.classList.remove('flavor-radio-reproductor--reproduciendo');
                }
            }

            // Función para actualizar iconos de volumen
            function actualizarIconosVolumen(valorVolumenActual) {
                const iconoVolumenAlto = contenedorReproductor.querySelector('.flavor-icono-volumen-alto');
                const iconoVolumenBajo = contenedorReproductor.querySelector('.flavor-icono-volumen-bajo');
                const iconoVolumenMudo = contenedorReproductor.querySelector('.flavor-icono-volumen-mudo');

                if (!iconoVolumenAlto) return;

                iconoVolumenAlto.style.display = 'none';
                if (iconoVolumenBajo) iconoVolumenBajo.style.display = 'none';
                if (iconoVolumenMudo) iconoVolumenMudo.style.display = 'none';

                if (valorVolumenActual === 0 || estaSilenciado) {
                    if (iconoVolumenMudo) iconoVolumenMudo.style.display = 'block';
                    else iconoVolumenAlto.style.display = 'block';
                } else if (valorVolumenActual < 50) {
                    if (iconoVolumenBajo) iconoVolumenBajo.style.display = 'block';
                    else iconoVolumenAlto.style.display = 'block';
                } else {
                    iconoVolumenAlto.style.display = 'block';
                }
            }

            // Evento de play/pause
            if (botonPlay && elementoAudio) {
                botonPlay.addEventListener('click', function() {
                    if (estaReproduciendo) {
                        elementoAudio.pause();
                        estaReproduciendo = false;
                    } else {
                        if (urlStream && elementoAudio.src !== urlStream) {
                            elementoAudio.src = urlStream;
                        }
                        elementoAudio.play().catch(function(error) {
                            console.warn('Error al reproducir:', error);
                        });
                        estaReproduciendo = true;
                    }
                    actualizarIconosReproduccion();

                    // Emitir evento personalizado
                    const eventoReproduccion = new CustomEvent('flavor:radio:toggle', {
                        detail: { reproduciendo: estaReproduciendo }
                    });
                    document.dispatchEvent(eventoReproduccion);
                });
            }

            // Evento de volumen
            if (inputVolumen && elementoAudio) {
                inputVolumen.addEventListener('input', function() {
                    const nuevoVolumen = parseInt(this.value);
                    elementoAudio.volume = nuevoVolumen / 100;

                    if (rellenoVolumen) {
                        rellenoVolumen.style.width = nuevoVolumen + '%';
                    }

                    if (valorVolumen) {
                        valorVolumen.textContent = nuevoVolumen + '%';
                    }

                    estaSilenciado = nuevoVolumen === 0;
                    actualizarIconosVolumen(nuevoVolumen);
                });
            }

            // Botón de silenciar
            if (botonVolumen && elementoAudio) {
                botonVolumen.addEventListener('click', function() {
                    if (estaSilenciado) {
                        elementoAudio.volume = volumenAnterior / 100;
                        if (inputVolumen) inputVolumen.value = volumenAnterior;
                        if (rellenoVolumen) rellenoVolumen.style.width = volumenAnterior + '%';
                        if (valorVolumen) valorVolumen.textContent = volumenAnterior + '%';
                        estaSilenciado = false;
                        actualizarIconosVolumen(volumenAnterior);
                    } else {
                        volumenAnterior = elementoAudio.volume * 100;
                        elementoAudio.volume = 0;
                        if (inputVolumen) inputVolumen.value = 0;
                        if (rellenoVolumen) rellenoVolumen.style.width = '0%';
                        if (valorVolumen) valorVolumen.textContent = '0%';
                        estaSilenciado = true;
                        actualizarIconosVolumen(0);
                    }
                });
            }

            // Botones de retroceder/adelantar (para streams grabados)
            if (botonRetroceder && elementoAudio) {
                botonRetroceder.addEventListener('click', function() {
                    elementoAudio.currentTime = Math.max(0, elementoAudio.currentTime - 10);
                });
            }

            if (botonAdelantar && elementoAudio) {
                botonAdelantar.addEventListener('click', function() {
                    elementoAudio.currentTime = Math.min(elementoAudio.duration || 0, elementoAudio.currentTime + 10);
                });
            }

            // Botón de favorito
            if (botonFavorito) {
                botonFavorito.addEventListener('click', function() {
                    this.classList.toggle('flavor-radio-accion--activo');

                    const eventoFavorito = new CustomEvent('flavor:radio:favorito', {
                        detail: { esFavorito: this.classList.contains('flavor-radio-accion--activo') }
                    });
                    document.dispatchEvent(eventoFavorito);
                });
            }

            // Eventos del elemento audio
            if (elementoAudio) {
                elementoAudio.addEventListener('play', function() {
                    estaReproduciendo = true;
                    actualizarIconosReproduccion();
                });

                elementoAudio.addEventListener('pause', function() {
                    estaReproduciendo = false;
                    actualizarIconosReproduccion();
                });

                elementoAudio.addEventListener('error', function() {
                    console.warn('Error en la transmisión de radio');
                    estaReproduciendo = false;
                    actualizarIconosReproduccion();
                });
            }

            // Autoplay si está habilitado
            if (autoplay && elementoAudio && urlStream) {
                elementoAudio.src = urlStream;
                elementoAudio.play().catch(function() {
                    // Los navegadores modernos bloquean autoplay sin interacción del usuario
                    console.info('Autoplay bloqueado por el navegador. Se requiere interacción del usuario.');
                });
            }

            // Inicializar iconos
            actualizarIconosReproduccion();
            actualizarIconosVolumen(volumenInicial);
        });
    });
})();
</script>

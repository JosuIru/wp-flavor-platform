<?php
/**
 * Template: Historias - Stories/historias efimeras
 *
 * Muestra las historias de los usuarios seguidos y permite crear nuevas.
 *
 * @package FlavorChatIA
 * @subpackage RedSocial/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$usuario_id = get_current_user_id();
$tabla_historias = $wpdb->prefix . 'flavor_social_historias';
$tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
$tabla_vistas_historias = $wpdb->prefix . 'flavor_social_vistas_historias';

// Obtener historias del usuario actual
$mis_historias = [];
if ($usuario_id) {
    $mis_historias = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_historias
         WHERE autor_id = %d AND fecha_expiracion > NOW()
         ORDER BY fecha_creacion ASC",
        $usuario_id
    ));
}

// Obtener historias de usuarios seguidos (agrupadas por autor)
$historias_seguidos = [];
if ($usuario_id) {
    $historias_seguidos = $wpdb->get_results($wpdb->prepare(
        "SELECT h.*, u.display_name, u.user_login,
                (SELECT COUNT(*) FROM $tabla_historias WHERE autor_id = h.autor_id AND fecha_expiracion > NOW()) as total_historias,
                (SELECT MAX(fecha_creacion) FROM $tabla_historias WHERE autor_id = h.autor_id AND fecha_expiracion > NOW()) as ultima_historia
         FROM $tabla_historias h
         INNER JOIN {$wpdb->users} u ON h.autor_id = u.ID
         INNER JOIN $tabla_seguimientos s ON h.autor_id = s.seguido_id
         WHERE s.seguidor_id = %d
         AND h.fecha_expiracion > NOW()
         AND h.id = (
             SELECT MIN(id) FROM $tabla_historias
             WHERE autor_id = h.autor_id AND fecha_expiracion > NOW()
         )
         ORDER BY ultima_historia DESC
         LIMIT 20",
        $usuario_id
    ));
} else {
    // Para visitantes, mostrar historias publicas
    $historias_seguidos = $wpdb->get_results(
        "SELECT h.*, u.display_name, u.user_login,
                (SELECT COUNT(*) FROM $tabla_historias WHERE autor_id = h.autor_id AND fecha_expiracion > NOW()) as total_historias,
                (SELECT MAX(fecha_creacion) FROM $tabla_historias WHERE autor_id = h.autor_id AND fecha_expiracion > NOW()) as ultima_historia
         FROM $tabla_historias h
         INNER JOIN {$wpdb->users} u ON h.autor_id = u.ID
         WHERE h.fecha_expiracion > NOW()
         AND h.id = (
             SELECT MIN(id) FROM $tabla_historias
             WHERE autor_id = h.autor_id AND fecha_expiracion > NOW()
         )
         ORDER BY ultima_historia DESC
         LIMIT 20"
    );
}

// Verificar si se esta viendo una historia especifica
$ver_historia_usuario = isset($_GET['ver']) ? absint($_GET['ver']) : 0;
?>

<div class="rs-container">
    <div class="rs-historias-page">
        <!-- Carrusel de historias -->
        <div class="rs-historias-carrusel">
            <h2 class="rs-historias-titulo"><?php echo esc_html__('Historias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="rs-historias-scroll">
                <!-- Crear nueva historia -->
                <?php if ($usuario_id): ?>
                    <div class="rs-historia-item rs-historia-crear" id="rs-crear-historia">
                        <div class="rs-historia-avatar-wrapper rs-historia-crear-avatar">
                            <img src="<?php echo esc_url(get_avatar_url($usuario_id, ['size' => 70])); ?>" alt="">
                            <span class="rs-historia-add-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </span>
                        </div>
                        <span class="rs-historia-nombre"><?php echo esc_html__('Tu historia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php if (!empty($mis_historias)): ?>
                            <span class="rs-historia-badge"><?php echo count($mis_historias); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Historias de usuarios seguidos -->
                <?php foreach ($historias_seguidos as $historia): ?>
                    <div class="rs-historia-item"
                         data-usuario-id="<?php echo esc_attr($historia->autor_id); ?>"
                         data-total="<?php echo esc_attr($historia->total_historias); ?>">
                        <div class="rs-historia-avatar-wrapper rs-historia-activa">
                            <img src="<?php echo esc_url(get_avatar_url($historia->autor_id, ['size' => 70])); ?>" alt="">
                        </div>
                        <span class="rs-historia-nombre"><?php echo esc_html($historia->display_name); ?></span>
                        <?php if ($historia->total_historias > 1): ?>
                            <span class="rs-historia-badge"><?php echo esc_html($historia->total_historias); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($historias_seguidos) && $usuario_id): ?>
                    <div class="rs-historias-vacio">
                        <p><?php echo esc_html__('Las historias de las personas que sigues apareceran aqui.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal para crear historia -->
        <?php if ($usuario_id): ?>
            <div class="rs-modal rs-modal-crear-historia" id="rs-modal-crear-historia" style="display: none;">
                <div class="rs-modal-overlay"></div>
                <div class="rs-modal-contenido">
                    <div class="rs-modal-header">
                        <h3><?php echo esc_html__('Crear historia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <button class="rs-modal-cerrar" id="rs-cerrar-modal-historia">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <div class="rs-crear-historia-contenido">
                        <!-- Tabs de tipo -->
                        <div class="rs-historia-tipos">
                            <button class="rs-historia-tipo active" data-tipo="imagen">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="M21 15l-5-5L5 21"/>
                                </svg>
                                <?php echo esc_html__('Foto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button class="rs-historia-tipo" data-tipo="texto">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                <?php echo esc_html__('Texto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button class="rs-historia-tipo" data-tipo="video">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="23 7 16 12 23 17 23 7"/>
                                    <rect x="1" y="5" width="15" height="14" rx="2"/>
                                </svg>
                                <?php echo esc_html__('Video', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>

                        <!-- Contenido segun tipo -->
                        <div class="rs-historia-editor" id="rs-historia-editor">
                            <!-- Editor de imagen -->
                            <div class="rs-historia-editor-imagen active" data-editor="imagen">
                                <div class="rs-historia-preview" id="rs-historia-preview-imagen">
                                    <div class="rs-historia-upload-area">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <path d="M21 15l-5-5L5 21"/>
                                        </svg>
                                        <p><?php echo esc_html__('Arrastra una imagen o haz clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                        <input type="file" accept="image/*" id="rs-input-historia-imagen" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Editor de texto -->
                            <div class="rs-historia-editor-texto" data-editor="texto" style="display: none;">
                                <div class="rs-historia-preview" id="rs-historia-preview-texto">
                                    <textarea class="rs-historia-texto-input"
                                              id="rs-historia-texto"
                                              placeholder="<?php echo esc_attr__('Escribe tu historia...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                              maxlength="280"></textarea>
                                </div>
                                <div class="rs-historia-colores">
                                    <span><?php echo esc_html__('Color de fondo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <div class="rs-colores-lista">
                                        <button class="rs-color-btn active" data-color="#6366f1" style="background: #6366f1;"></button>
                                        <button class="rs-color-btn" data-color="#ec4899" style="background: #ec4899;"></button>
                                        <button class="rs-color-btn" data-color="#10b981" style="background: #10b981;"></button>
                                        <button class="rs-color-btn" data-color="#f59e0b" style="background: #f59e0b;"></button>
                                        <button class="rs-color-btn" data-color="#ef4444" style="background: #ef4444;"></button>
                                        <button class="rs-color-btn" data-color="#3b82f6" style="background: #3b82f6;"></button>
                                        <button class="rs-color-btn" data-color="#1e293b" style="background: #1e293b;"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- Editor de video -->
                            <div class="rs-historia-editor-video" data-editor="video" style="display: none;">
                                <div class="rs-historia-preview" id="rs-historia-preview-video">
                                    <div class="rs-historia-upload-area">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <polygon points="23 7 16 12 23 17 23 7"/>
                                            <rect x="1" y="5" width="15" height="14" rx="2"/>
                                        </svg>
                                        <p><?php echo esc_html__('Arrastra un video o haz clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                        <span class="rs-historia-video-nota"><?php echo esc_html__('Maximo 30 segundos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        <input type="file" accept="video/*" id="rs-input-historia-video" style="display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rs-historia-acciones">
                            <button class="rs-btn-secundario" id="rs-cancelar-historia">
                                <?php echo esc_html__('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button class="rs-btn-primary" id="rs-publicar-historia" disabled>
                                <?php echo esc_html__('Compartir historia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Visor de historias (modal fullscreen) -->
        <div class="rs-visor-historias" id="rs-visor-historias" style="display: none;">
            <div class="rs-visor-overlay"></div>
            <div class="rs-visor-contenido">
                <!-- Barra de progreso -->
                <div class="rs-visor-progreso" id="rs-visor-progreso">
                    <!-- Se generan dinamicamente las barras -->
                </div>

                <!-- Header del visor -->
                <div class="rs-visor-header">
                    <div class="rs-visor-autor">
                        <img class="rs-visor-avatar" id="rs-visor-avatar" src="" alt="">
                        <div class="rs-visor-info">
                            <span class="rs-visor-nombre" id="rs-visor-nombre"></span>
                            <span class="rs-visor-tiempo" id="rs-visor-tiempo"></span>
                        </div>
                    </div>
                    <button class="rs-visor-cerrar" id="rs-visor-cerrar">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <!-- Contenido de la historia -->
                <div class="rs-visor-media" id="rs-visor-media">
                    <!-- Se carga dinamicamente -->
                </div>

                <!-- Navegacion -->
                <button class="rs-visor-nav rs-visor-prev" id="rs-visor-prev">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button class="rs-visor-nav rs-visor-next" id="rs-visor-next">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>

                <!-- Footer del visor -->
                <div class="rs-visor-footer">
                    <?php if ($usuario_id): ?>
                        <div class="rs-visor-responder">
                            <input type="text"
                                   class="rs-visor-input"
                                   id="rs-visor-respuesta"
                                   placeholder="<?php echo esc_attr__('Enviar mensaje...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <button class="rs-visor-enviar" id="rs-visor-enviar">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mis historias recientes -->
        <?php if (!empty($mis_historias)): ?>
            <div class="rs-mis-historias">
                <h3 class="rs-seccion-titulo"><?php echo esc_html__('Tus historias activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="rs-mis-historias-lista">
                    <?php foreach ($mis_historias as $historia): ?>
                        <div class="rs-mi-historia-card" data-historia-id="<?php echo esc_attr($historia->id); ?>">
                            <?php if ($historia->tipo === 'texto'): ?>
                                <div class="rs-mi-historia-preview rs-historia-texto"
                                     style="background: <?php echo esc_attr($historia->color_fondo ?: '#6366f1'); ?>;">
                                    <p><?php echo esc_html(wp_trim_words($historia->texto, 10)); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="rs-mi-historia-preview">
                                    <?php if ($historia->tipo === 'video'): ?>
                                        <video src="<?php echo esc_url($historia->contenido_url); ?>" muted></video>
                                    <?php else: ?>
                                        <img src="<?php echo esc_url($historia->contenido_url); ?>" alt="">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="rs-mi-historia-info">
                                <span class="rs-mi-historia-vistas">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <?php echo number_format($historia->vistas); ?>
                                </span>
                                <span class="rs-mi-historia-tiempo">
                                    <?php
                                    $tiempo_restante = strtotime($historia->fecha_expiracion) - current_time('timestamp');
                                    $horas_restantes = floor($tiempo_restante / 3600);
                                    if ($horas_restantes > 0) {
                                        printf(esc_html__('%dh restantes', FLAVOR_PLATFORM_TEXT_DOMAIN), $horas_restantes);
                                    } else {
                                        $minutos_restantes = floor($tiempo_restante / 60);
                                        printf(esc_html__('%dm restantes', FLAVOR_PLATFORM_TEXT_DOMAIN), max(1, $minutos_restantes));
                                    }
                                    ?>
                                </span>
                            </div>
                            <button class="rs-mi-historia-eliminar" data-historia-id="<?php echo esc_attr($historia->id); ?>" title="<?php echo esc_attr__('Eliminar historia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.rs-historias-page {
    padding: 24px 0;
    max-width: 900px;
    margin: 0 auto;
}

.rs-historias-titulo {
    margin: 0 0 20px;
    font-size: 22px;
    font-weight: 600;
}

.rs-historias-carrusel {
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    padding: 20px;
    margin-bottom: 24px;
}

.rs-historias-scroll {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding-bottom: 8px;
    scrollbar-width: thin;
}

.rs-historias-scroll::-webkit-scrollbar {
    height: 6px;
}

.rs-historias-scroll::-webkit-scrollbar-thumb {
    background: var(--rs-border);
    border-radius: 3px;
}

.rs-historia-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    flex-shrink: 0;
    position: relative;
}

.rs-historia-avatar-wrapper {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    padding: 3px;
    background: var(--rs-border);
}

.rs-historia-avatar-wrapper.rs-historia-activa {
    background: linear-gradient(135deg, var(--rs-primary), #ec4899, #f59e0b);
}

.rs-historia-avatar-wrapper img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--rs-bg-card);
}

.rs-historia-crear-avatar {
    position: relative;
}

.rs-historia-add-icon {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 24px;
    height: 24px;
    background: var(--rs-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--rs-bg-card);
}

.rs-historia-nombre {
    font-size: 12px;
    color: var(--rs-text);
    max-width: 72px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: center;
}

.rs-historia-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: var(--rs-primary);
    color: white;
    font-size: 11px;
    font-weight: 600;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rs-historias-vacio {
    text-align: center;
    padding: 20px 40px;
    color: var(--rs-text-muted);
}

/* Modal crear historia */
.rs-modal {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rs-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
}

.rs-modal-contenido {
    position: relative;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: auto;
}

.rs-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--rs-border);
}

.rs-modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.rs-modal-cerrar {
    background: none;
    border: none;
    color: var(--rs-text-muted);
    cursor: pointer;
    padding: 4px;
}

.rs-crear-historia-contenido {
    padding: 20px;
}

.rs-historia-tipos {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}

.rs-historia-tipo {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px;
    border: 2px solid var(--rs-border);
    border-radius: var(--rs-radius-sm);
    background: transparent;
    color: var(--rs-text-muted);
    cursor: pointer;
    transition: var(--rs-transition);
}

.rs-historia-tipo:hover,
.rs-historia-tipo.active {
    border-color: var(--rs-primary);
    color: var(--rs-primary);
    background: rgba(99, 102, 241, 0.05);
}

.rs-historia-preview {
    aspect-ratio: 9/16;
    max-height: 400px;
    border-radius: var(--rs-radius);
    overflow: hidden;
    background: var(--rs-bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
}

.rs-historia-upload-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 40px;
    text-align: center;
    color: var(--rs-text-muted);
    cursor: pointer;
    border: 2px dashed var(--rs-border);
    border-radius: var(--rs-radius);
    margin: 20px;
    transition: var(--rs-transition);
}

.rs-historia-upload-area:hover {
    border-color: var(--rs-primary);
    background: rgba(99, 102, 241, 0.05);
}

.rs-historia-texto-input {
    width: 100%;
    height: 100%;
    padding: 24px;
    border: none;
    background: #6366f1;
    color: white;
    font-size: 24px;
    font-weight: 500;
    text-align: center;
    resize: none;
}

.rs-historia-texto-input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.rs-historia-colores {
    margin-top: 16px;
}

.rs-historia-colores span {
    font-size: 13px;
    color: var(--rs-text-muted);
    margin-bottom: 8px;
    display: block;
}

.rs-colores-lista {
    display: flex;
    gap: 8px;
}

.rs-color-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: var(--rs-transition);
}

.rs-color-btn:hover,
.rs-color-btn.active {
    border-color: var(--rs-text);
    transform: scale(1.1);
}

.rs-historia-acciones {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.rs-historia-acciones button {
    flex: 1;
}

.rs-btn-secundario {
    padding: 12px 24px;
    border: 1px solid var(--rs-border);
    background: transparent;
    color: var(--rs-text);
    border-radius: var(--rs-radius-sm);
    font-weight: 500;
    cursor: pointer;
    transition: var(--rs-transition);
}

.rs-btn-secundario:hover {
    background: var(--rs-bg-light);
}

/* Visor de historias */
.rs-visor-historias {
    position: fixed;
    inset: 0;
    z-index: 1001;
    background: #000;
}

.rs-visor-contenido {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.rs-visor-progreso {
    position: absolute;
    top: 16px;
    left: 16px;
    right: 16px;
    display: flex;
    gap: 4px;
    z-index: 10;
}

.rs-visor-progreso-bar {
    flex: 1;
    height: 3px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    overflow: hidden;
}

.rs-visor-progreso-fill {
    height: 100%;
    background: white;
    width: 0;
    transition: width 0.1s linear;
}

.rs-visor-header {
    position: absolute;
    top: 32px;
    left: 16px;
    right: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 10;
}

.rs-visor-autor {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rs-visor-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
}

.rs-visor-nombre {
    color: white;
    font-weight: 600;
}

.rs-visor-tiempo {
    color: rgba(255, 255, 255, 0.7);
    font-size: 13px;
}

.rs-visor-cerrar {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 8px;
}

.rs-visor-media {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 80px 60px;
}

.rs-visor-media img,
.rs-visor-media video {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.rs-visor-media .rs-visor-texto {
    padding: 40px;
    color: white;
    font-size: 28px;
    font-weight: 500;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: var(--rs-radius);
}

.rs-visor-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--rs-transition);
    z-index: 10;
}

.rs-visor-nav:hover {
    background: rgba(255, 255, 255, 0.3);
}

.rs-visor-prev {
    left: 16px;
}

.rs-visor-next {
    right: 16px;
}

.rs-visor-footer {
    position: absolute;
    bottom: 16px;
    left: 16px;
    right: 16px;
    z-index: 10;
}

.rs-visor-responder {
    display: flex;
    gap: 12px;
    max-width: 500px;
    margin: 0 auto;
}

.rs-visor-input {
    flex: 1;
    padding: 12px 20px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 15px;
}

.rs-visor-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.rs-visor-enviar {
    background: var(--rs-primary);
    border: none;
    color: white;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mis historias */
.rs-mis-historias {
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    padding: 20px;
}

.rs-seccion-titulo {
    margin: 0 0 16px;
    font-size: 16px;
    font-weight: 600;
}

.rs-mis-historias-lista {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
}

.rs-mi-historia-card {
    position: relative;
    border-radius: var(--rs-radius-sm);
    overflow: hidden;
    cursor: pointer;
}

.rs-mi-historia-preview {
    aspect-ratio: 9/16;
    max-height: 200px;
}

.rs-mi-historia-preview img,
.rs-mi-historia-preview video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.rs-mi-historia-preview.rs-historia-texto {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    color: white;
    font-size: 12px;
    text-align: center;
}

.rs-mi-historia-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 8px;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
    display: flex;
    flex-direction: column;
    gap: 2px;
    color: white;
    font-size: 11px;
}

.rs-mi-historia-vistas {
    display: flex;
    align-items: center;
    gap: 4px;
}

.rs-mi-historia-eliminar {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(0, 0, 0, 0.5);
    border: none;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: var(--rs-transition);
}

.rs-mi-historia-card:hover .rs-mi-historia-eliminar {
    opacity: 1;
}

.rs-mi-historia-eliminar:hover {
    background: var(--rs-danger);
}

@media (max-width: 640px) {
    .rs-visor-media {
        padding: 80px 20px;
    }

    .rs-visor-nav {
        width: 40px;
        height: 40px;
    }

    .rs-modal-contenido {
        margin: 16px;
    }
}
</style>

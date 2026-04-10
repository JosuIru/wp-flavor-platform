<?php
/**
 * Template: Formulario de suscripcion a podcast
 *
 * Variables disponibles:
 * @var object $serie            - Datos de la serie (opcional)
 * @var bool   $esta_suscrito    - Si el usuario ya esta suscrito
 * @var string $estilo           - Estilo del formulario: 'card', 'inline', 'modal'
 * @var bool   $mostrar_opciones - Si mostrar opciones de notificacion
 * @var array  $plataformas      - Plataformas disponibles para suscripcion
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$serie = $serie ?? null;
$esta_suscrito = $esta_suscrito ?? false;
$estilo = $estilo ?? 'card';
$mostrar_opciones = $mostrar_opciones ?? true;
$plataformas = $plataformas ?? [
    'spotify' => [
        'nombre' => 'Spotify',
        'icono' => 'spotify',
        'color' => '#1DB954',
    ],
    'apple' => [
        'nombre' => 'Apple Podcasts',
        'icono' => 'apple',
        'color' => '#9933CC',
    ],
    'google' => [
        'nombre' => 'Google Podcasts',
        'icono' => 'google',
        'color' => '#4285F4',
    ],
];

// ID unico para el formulario
$formulario_id = 'flavor-suscripcion-' . ($serie ? intval($serie->id) : wp_rand(1000, 9999));

// Verificar si el usuario esta logueado
$usuario_logueado = is_user_logged_in();
$usuario_id = get_current_user_id();
?>

<div class="flavor-podcast-suscripcion flavor-suscripcion-<?php echo esc_attr($estilo); ?>"
     id="<?php echo esc_attr($formulario_id); ?>"
     data-serie-id="<?php echo $serie ? intval($serie->id) : 0; ?>">

    <?php if ($estilo === 'card'): ?>
    <!-- Estilo Card -->
    <div class="flavor-suscripcion-card">
        <?php if ($serie): ?>
        <div class="flavor-suscripcion-header">
            <?php if (!empty($serie->imagen_url)): ?>
            <div class="flavor-suscripcion-cover">
                <img src="<?php echo esc_url($serie->imagen_url); ?>" alt="">
            </div>
            <?php endif; ?>
            <div class="flavor-suscripcion-info">
                <h3 class="flavor-suscripcion-titulo"><?php echo esc_html($serie->titulo); ?></h3>
                <?php if (!empty($serie->autor_nombre)): ?>
                <p class="flavor-suscripcion-autor"><?php echo esc_html($serie->autor_nombre); ?></p>
                <?php endif; ?>
                <?php if (isset($serie->total_episodios)): ?>
                <span class="flavor-suscripcion-episodios">
                    <?php echo sprintf(esc_html__('%d episodios', FLAVOR_PLATFORM_TEXT_DOMAIN), intval($serie->total_episodios)); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="flavor-suscripcion-body">
            <?php if (!$usuario_logueado): ?>
            <!-- Usuario no logueado -->
            <div class="flavor-suscripcion-login">
                <p><?php esc_html_e('Inicia sesion para suscribirte y recibir notificaciones de nuevos episodios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url(wp_login_url(add_query_arg(null, null))); ?>"
                   class="flavor-btn flavor-btn-primary flavor-btn-block">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php esc_html_e('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php elseif ($esta_suscrito): ?>
            <!-- Ya suscrito -->
            <div class="flavor-suscripcion-activa">
                <div class="flavor-suscripcion-estado">
                    <span class="flavor-icono-check">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </span>
                    <span><?php esc_html_e('Estas suscrito a esta serie', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>

                <?php if ($mostrar_opciones): ?>
                <div class="flavor-suscripcion-opciones">
                    <h4><?php esc_html_e('Preferencias de notificacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <form class="flavor-form-preferencias">
                        <label class="flavor-checkbox-label">
                            <input type="checkbox" name="notif_email" value="1" checked>
                            <span class="flavor-checkbox-custom"></span>
                            <span class="flavor-checkbox-texto">
                                <span class="dashicons dashicons-email-alt"></span>
                                <?php esc_html_e('Recibir por email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </label>
                        <label class="flavor-checkbox-label">
                            <input type="checkbox" name="notif_push" value="1">
                            <span class="flavor-checkbox-custom"></span>
                            <span class="flavor-checkbox-texto">
                                <span class="dashicons dashicons-bell"></span>
                                <?php esc_html_e('Notificaciones push', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </label>
                        <button type="submit" class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-btn-guardar-prefs">
                            <?php esc_html_e('Guardar preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <button type="button"
                        class="flavor-btn flavor-btn-outline flavor-btn-block flavor-btn-cancelar-suscripcion"
                        data-serie-id="<?php echo $serie ? intval($serie->id) : 0; ?>">
                    <span class="dashicons dashicons-no"></span>
                    <?php esc_html_e('Cancelar suscripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <?php else: ?>
            <!-- Formulario de suscripcion -->
            <div class="flavor-suscripcion-formulario">
                <div class="flavor-suscripcion-beneficios">
                    <h4><?php esc_html_e('Al suscribirte recibiras:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li>
                            <span class="dashicons dashicons-bell"></span>
                            <?php esc_html_e('Notificaciones de nuevos episodios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </li>
                        <li>
                            <span class="dashicons dashicons-email-alt"></span>
                            <?php esc_html_e('Resumen semanal por email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </li>
                        <li>
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php esc_html_e('Acceso a contenido exclusivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </li>
                    </ul>
                </div>

                <button type="button"
                        class="flavor-btn flavor-btn-primary flavor-btn-block flavor-btn-suscribir-grande"
                        data-serie-id="<?php echo $serie ? intval($serie->id) : 0; ?>">
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Suscribirse gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>

                <p class="flavor-suscripcion-nota">
                    <?php esc_html_e('Puedes cancelar en cualquier momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Plataformas externas -->
        <?php if (!empty($plataformas) && $serie): ?>
        <div class="flavor-suscripcion-plataformas">
            <p class="flavor-plataformas-titulo">
                <?php esc_html_e('Tambien disponible en:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <div class="flavor-plataformas-grid">
                <?php foreach ($plataformas as $clave_plataforma => $datos_plataforma):
                    $url_plataforma = '';
                    if (!empty($serie->{'url_' . $clave_plataforma})) {
                        $url_plataforma = $serie->{'url_' . $clave_plataforma};
                    }
                    if (empty($url_plataforma)) continue;
                ?>
                <a href="<?php echo esc_url($url_plataforma); ?>"
                   class="flavor-plataforma-btn"
                   style="--plataforma-color: <?php echo esc_attr($datos_plataforma['color']); ?>"
                   target="_blank"
                   rel="noopener noreferrer">
                    <span class="flavor-plataforma-nombre"><?php echo esc_html($datos_plataforma['nombre']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- RSS Feed -->
        <?php if ($serie && !empty($serie->rss_url)): ?>
        <div class="flavor-suscripcion-rss">
            <button type="button" class="flavor-btn-rss" data-rss="<?php echo esc_url($serie->rss_url); ?>">
                <span class="dashicons dashicons-rss"></span>
                <?php esc_html_e('Copiar enlace RSS', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($estilo === 'inline'): ?>
    <!-- Estilo Inline -->
    <div class="flavor-suscripcion-inline">
        <?php if (!$usuario_logueado): ?>
        <a href="<?php echo esc_url(wp_login_url(add_query_arg(null, null))); ?>"
           class="flavor-btn flavor-btn-primary">
            <span class="dashicons dashicons-heart"></span>
            <?php esc_html_e('Suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php elseif ($esta_suscrito): ?>
        <button type="button"
                class="flavor-btn flavor-btn-outline flavor-suscrito flavor-btn-cancelar-suscripcion"
                data-serie-id="<?php echo $serie ? intval($serie->id) : 0; ?>">
            <span class="dashicons dashicons-yes"></span>
            <?php esc_html_e('Suscrito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php else: ?>
        <button type="button"
                class="flavor-btn flavor-btn-primary flavor-btn-suscribir"
                data-serie-id="<?php echo $serie ? intval($serie->id) : 0; ?>">
            <span class="dashicons dashicons-heart"></span>
            <?php esc_html_e('Suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php endif; ?>
    </div>

    <?php elseif ($estilo === 'modal'): ?>
    <!-- Estilo Modal (contenido del modal) -->
    <div class="flavor-suscripcion-modal-contenido">
        <button type="button" class="flavor-modal-cerrar">
            <span class="dashicons dashicons-no-alt"></span>
        </button>

        <div class="flavor-modal-header">
            <?php if ($serie && !empty($serie->imagen_url)): ?>
            <div class="flavor-modal-cover">
                <img src="<?php echo esc_url($serie->imagen_url); ?>" alt="">
            </div>
            <?php endif; ?>
            <h3><?php esc_html_e('Suscribete a este podcast', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <?php if ($serie): ?>
            <p class="flavor-modal-serie"><?php echo esc_html($serie->titulo); ?></p>
            <?php endif; ?>
        </div>

        <div class="flavor-modal-body">
            <?php if (!$usuario_logueado): ?>
            <p><?php esc_html_e('Necesitas iniciar sesion para suscribirte.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(wp_login_url(add_query_arg(null, null))); ?>"
               class="flavor-btn flavor-btn-primary flavor-btn-block">
                <?php esc_html_e('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <?php else: ?>
            <div class="flavor-modal-opciones">
                <label class="flavor-checkbox-label">
                    <input type="checkbox" name="notif_email" value="1" checked>
                    <span class="flavor-checkbox-custom"></span>
                    <?php esc_html_e('Notificarme por email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
            </div>

            <button type="button"
                    class="flavor-btn flavor-btn-primary flavor-btn-block flavor-btn-suscribir"
                    data-serie-id="<?php echo $serie ? intval($serie->id) : 0; ?>">
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Confirmar suscripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
.flavor-podcast-suscripcion {
    --suscripcion-primary: var(--podcast-primary, #6366f1);
    --suscripcion-success: #22c55e;
}

/* ========================
   Estilo Card
   ======================== */
.flavor-suscripcion-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.flavor-suscripcion-header {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(129,140,248,0.08));
}

.flavor-suscripcion-cover {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.flavor-suscripcion-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-suscripcion-info {
    flex: 1;
    min-width: 0;
}

.flavor-suscripcion-titulo {
    margin: 0 0 0.25rem;
    font-size: 1.1rem;
    color: #1e293b;
}

.flavor-suscripcion-autor {
    margin: 0 0 0.5rem;
    font-size: 0.9rem;
    color: #64748b;
}

.flavor-suscripcion-episodios {
    font-size: 0.8rem;
    color: var(--suscripcion-primary);
}

.flavor-suscripcion-body {
    padding: 1.5rem;
}

/* Login */
.flavor-suscripcion-login {
    text-align: center;
}

.flavor-suscripcion-login p {
    margin: 0 0 1rem;
    color: #64748b;
    font-size: 0.95rem;
}

/* Suscripcion activa */
.flavor-suscripcion-activa {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.flavor-suscripcion-estado {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(34,197,94,0.1);
    border-radius: 10px;
    color: var(--suscripcion-success);
    font-weight: 500;
}

.flavor-icono-check {
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-icono-check .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.flavor-suscripcion-opciones h4 {
    margin: 0 0 0.75rem;
    font-size: 0.9rem;
    color: #64748b;
}

.flavor-form-preferencias {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flavor-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: background 0.2s;
}

.flavor-checkbox-label:hover {
    background: #f8fafc;
}

.flavor-checkbox-label input {
    display: none;
}

.flavor-checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 4px;
    position: relative;
    flex-shrink: 0;
    transition: all 0.2s;
}

.flavor-checkbox-label input:checked + .flavor-checkbox-custom {
    background: var(--suscripcion-primary);
    border-color: var(--suscripcion-primary);
}

.flavor-checkbox-label input:checked + .flavor-checkbox-custom::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 6px;
    width: 5px;
    height: 10px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.flavor-checkbox-texto {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #475569;
    font-size: 0.9rem;
}

.flavor-checkbox-texto .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #94a3b8;
}

.flavor-btn-guardar-prefs {
    align-self: flex-start;
    margin-top: 0.5rem;
}

/* Formulario suscripcion */
.flavor-suscripcion-formulario {
    text-align: center;
}

.flavor-suscripcion-beneficios {
    text-align: left;
    margin-bottom: 1.5rem;
}

.flavor-suscripcion-beneficios h4 {
    margin: 0 0 0.75rem;
    font-size: 0.9rem;
    color: #64748b;
}

.flavor-suscripcion-beneficios ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-suscripcion-beneficios li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    color: #475569;
    font-size: 0.9rem;
}

.flavor-suscripcion-beneficios .dashicons {
    color: var(--suscripcion-primary);
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.flavor-btn-suscribir-grande {
    padding: 1rem 1.5rem;
    font-size: 1rem;
}

.flavor-suscripcion-nota {
    margin: 1rem 0 0;
    font-size: 0.8rem;
    color: #94a3b8;
}

/* Plataformas */
.flavor-suscripcion-plataformas {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid #f1f5f9;
}

.flavor-plataformas-titulo {
    margin: 0 0 0.75rem;
    font-size: 0.85rem;
    color: #64748b;
    text-align: center;
}

.flavor-plataformas-grid {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.flavor-plataforma-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: color-mix(in srgb, var(--plataforma-color) 10%, transparent);
    border-radius: 20px;
    color: var(--plataforma-color);
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.flavor-plataforma-btn:hover {
    background: color-mix(in srgb, var(--plataforma-color) 20%, transparent);
    transform: translateY(-1px);
}

/* RSS */
.flavor-suscripcion-rss {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f1f5f9;
    text-align: center;
}

.flavor-btn-rss {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-rss:hover {
    background: #f8fafc;
    color: #475569;
}

.flavor-btn-rss .dashicons {
    color: #f97316;
}

/* ========================
   Estilo Inline
   ======================== */
.flavor-suscripcion-inline {
    display: inline-block;
}

.flavor-suscripcion-inline .flavor-btn {
    white-space: nowrap;
}

.flavor-suscripcion-inline .flavor-suscrito {
    border-color: var(--suscripcion-success);
    color: var(--suscripcion-success);
}

/* ========================
   Estilo Modal
   ======================== */
.flavor-suscripcion-modal .flavor-suscripcion-modal-contenido {
    position: relative;
    background: #fff;
    border-radius: 16px;
    max-width: 400px;
    margin: 2rem auto;
    overflow: hidden;
}

.flavor-modal-cerrar {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0.25rem;
    z-index: 1;
}

.flavor-modal-cerrar:hover {
    color: #64748b;
}

.flavor-modal-header {
    text-align: center;
    padding: 2rem 2rem 1rem;
    background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(129,140,248,0.08));
}

.flavor-modal-cover {
    width: 100px;
    height: 100px;
    border-radius: 16px;
    overflow: hidden;
    margin: 0 auto 1rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.flavor-modal-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-modal-header h3 {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
    color: #1e293b;
}

.flavor-modal-serie {
    margin: 0;
    color: var(--suscripcion-primary);
    font-size: 0.95rem;
}

.flavor-modal-body {
    padding: 1.5rem 2rem 2rem;
}

.flavor-modal-opciones {
    margin-bottom: 1.5rem;
}

/* ========================
   Botones comunes
   ======================== */
.flavor-btn-block {
    display: flex;
    width: 100%;
    justify-content: center;
}

/* ========================
   Estados de carga
   ======================== */
.flavor-suscripcion-cargando {
    pointer-events: none;
    opacity: 0.7;
}

.flavor-suscripcion-cargando .flavor-btn::before {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    margin-right: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
(function() {
    var contenedor = document.getElementById('<?php echo esc_js($formulario_id); ?>');
    if (!contenedor) return;

    var serieId = contenedor.dataset.serieId;

    // Botones de suscripcion
    var btnsSubscribir = contenedor.querySelectorAll('.flavor-btn-suscribir, .flavor-btn-suscribir-grande');
    btnsSubscribir.forEach(function(btn) {
        btn.addEventListener('click', function() {
            suscribir(this);
        });
    });

    // Botones de cancelar
    var btnsCancelar = contenedor.querySelectorAll('.flavor-btn-cancelar-suscripcion');
    btnsCancelar.forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (confirm('<?php echo esc_js(__('Seguro que quieres cancelar la suscripcion?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
                cancelarSuscripcion(this);
            }
        });
    });

    // Boton RSS
    var btnRss = contenedor.querySelector('.flavor-btn-rss');
    if (btnRss) {
        btnRss.addEventListener('click', function() {
            var rssUrl = this.dataset.rss;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(rssUrl).then(function() {
                    var textoOriginal = btnRss.innerHTML;
                    btnRss.innerHTML = '<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Copiado!', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                    setTimeout(function() {
                        btnRss.innerHTML = textoOriginal;
                    }, 2000);
                });
            } else {
                prompt('<?php echo esc_js(__('Copia este enlace RSS:', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', rssUrl);
            }
        });
    }

    // Guardar preferencias
    var formPrefs = contenedor.querySelector('.flavor-form-preferencias');
    if (formPrefs) {
        formPrefs.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            // Aqui iria la llamada AJAX para guardar preferencias
            var btnGuardar = this.querySelector('.flavor-btn-guardar-prefs');
            var textoOriginal = btnGuardar.textContent;
            btnGuardar.textContent = '<?php echo esc_js(__('Guardado!', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
            setTimeout(function() {
                btnGuardar.textContent = textoOriginal;
            }, 2000);
        });
    }

    function suscribir(btn) {
        if (typeof jQuery === 'undefined' || typeof flavorPodcastConfig === 'undefined') return;

        contenedor.classList.add('flavor-suscripcion-cargando');

        jQuery.ajax({
            url: flavorPodcastConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_podcast_suscribir',
                nonce: flavorPodcastConfig.nonce,
                serie_id: serieId
            },
            success: function(response) {
                contenedor.classList.remove('flavor-suscripcion-cargando');
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error al suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                }
            },
            error: function() {
                contenedor.classList.remove('flavor-suscripcion-cargando');
                alert('<?php echo esc_js(__('Error de conexion', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
            }
        });
    }

    function cancelarSuscripcion(btn) {
        if (typeof jQuery === 'undefined' || typeof flavorPodcastConfig === 'undefined') return;

        contenedor.classList.add('flavor-suscripcion-cargando');

        jQuery.ajax({
            url: flavorPodcastConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_podcast_suscribir',
                nonce: flavorPodcastConfig.nonce,
                serie_id: serieId
            },
            success: function(response) {
                contenedor.classList.remove('flavor-suscripcion-cargando');
                if (response.success && !response.data.suscrito) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error al cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                }
            },
            error: function() {
                contenedor.classList.remove('flavor-suscripcion-cargando');
                alert('<?php echo esc_js(__('Error de conexion', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
            }
        });
    }
})();
</script>

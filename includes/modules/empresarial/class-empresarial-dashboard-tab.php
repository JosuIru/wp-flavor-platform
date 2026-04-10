<?php
/**
 * Dashboard Tab para Empresarial
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Empresarial_Dashboard_Tab {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public function registrar_tabs($tabs) {
        // Solo mostrar a usuarios con permisos de empresa
        if (!current_user_can('edit_posts')) {
            return $tabs;
        }

        $tabs['empresarial'] = [
            'label' => __('Mi Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-building',
            'callback' => [$this, 'render_tab'],
            'priority' => 65,
        ];
        return $tabs;
    }

    public function render_tab() {
        $datos = $this->obtener_datos();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'perfil';

        ?>
        <div class="flavor-empresarial-dashboard">
            <!-- Navegación interna -->
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=empresarial&subtab=perfil" class="subtab <?php echo $subtab === 'perfil' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-id"></span> Perfil Empresa
                </a>
                <a href="?tab=empresarial&subtab=servicios" class="subtab <?php echo $subtab === 'servicios' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-clipboard"></span> Servicios
                </a>
                <a href="?tab=empresarial&subtab=equipo" class="subtab <?php echo $subtab === 'equipo' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-groups"></span> Equipo
                </a>
                <a href="?tab=empresarial&subtab=testimonios" class="subtab <?php echo $subtab === 'testimonios' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-format-quote"></span> Testimonios
                </a>
                <a href="?tab=empresarial&subtab=portfolio" class="subtab <?php echo $subtab === 'portfolio' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-portfolio"></span> Portfolio
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'servicios':
                        echo do_shortcode('[empresarial_servicios]');
                        break;
                    case 'equipo':
                        echo do_shortcode('[empresarial_equipo]');
                        break;
                    case 'testimonios':
                        echo do_shortcode('[empresarial_testimonios]');
                        break;
                    case 'portfolio':
                        echo do_shortcode('[empresarial_portfolio]');
                        break;
                    default:
                        $this->render_perfil_resumen($datos);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_perfil_resumen($datos) {
        $empresa = $datos['empresa'] ?? [];
        ?>
        <div class="empresa-perfil-resumen">
            <div class="flavor-notice flavor-notice-info">
                <p><?php esc_html_e('Este dashboard muestra el estado público del módulo. La edición avanzada del perfil empresarial sigue dependiendo de las vistas nativas y del panel administrativo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-card">
                <h3><?php echo esc_html($empresa['nombre'] ?? __('Mi empresa', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></h3>
                <?php if (!empty($empresa['eslogan'])): ?>
                    <p><strong><?php esc_html_e('Eslogan:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html($empresa['eslogan']); ?></p>
                <?php endif; ?>
                <?php if (!empty($empresa['descripcion'])): ?>
                    <p><?php echo esc_html($empresa['descripcion']); ?></p>
                <?php endif; ?>

                <div class="empresa-meta">
                    <?php if (!empty($empresa['email'])): ?>
                        <p><strong><?php esc_html_e('Email:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html($empresa['email']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($empresa['telefono'])): ?>
                        <p><strong><?php esc_html_e('Teléfono:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html($empresa['telefono']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($empresa['web'])): ?>
                        <p><strong><?php esc_html_e('Web:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <a href="<?php echo esc_url($empresa['web']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($empresa['web']); ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($empresa['direccion'])): ?>
                        <p><strong><?php esc_html_e('Dirección:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html($empresa['direccion']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_perfil($datos) {
        $empresa = $datos['empresa'];
        ?>
        <div class="empresa-perfil">
            <h3>Perfil de la empresa</h3>

            <form id="form-perfil-empresa" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_perfil_empresa', 'empresa_nonce'); ?>

                <!-- Logo y banner -->
                <div class="form-row imagenes-empresa">
                    <div class="form-group">
                        <label>Logo</label>
                        <div class="image-upload">
                            <?php if (!empty($empresa['logo'])): ?>
                                <img src="<?php echo esc_url($empresa['logo']); ?>" alt="Logo" class="preview-logo">
                            <?php endif; ?>
                            <input type="file" name="logo" accept="image/*">
                            <small>Recomendado: 200x200px, PNG o SVG</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Banner / Hero</label>
                        <div class="image-upload">
                            <?php if (!empty($empresa['banner'])): ?>
                                <img src="<?php echo esc_url($empresa['banner']); ?>" alt="Banner" class="preview-banner">
                            <?php endif; ?>
                            <input type="file" name="banner" accept="image/*">
                            <small>Recomendado: 1920x600px</small>
                        </div>
                    </div>
                </div>

                <!-- Datos básicos -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre de la empresa *</label>
                        <input type="text" name="nombre" value="<?php echo esc_attr($empresa['nombre'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Eslogan</label>
                        <input type="text" name="eslogan" value="<?php echo esc_attr($empresa['eslogan'] ?? ''); ?>" placeholder="Tu propuesta de valor">
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="4"><?php echo esc_textarea($empresa['descripcion'] ?? ''); ?></textarea>
                </div>

                <!-- Contacto -->
                <h4>Información de contacto</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo esc_attr($empresa['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" value="<?php echo esc_attr($empresa['telefono'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Sitio web</label>
                        <input type="url" name="web" value="<?php echo esc_url($empresa['web'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" value="<?php echo esc_attr($empresa['direccion'] ?? ''); ?>">
                </div>

                <!-- Redes sociales -->
                <h4>Redes sociales</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label><span class="dashicons dashicons-facebook"></span> Facebook</label>
                        <input type="url" name="facebook" value="<?php echo esc_url($empresa['facebook'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><span class="dashicons dashicons-twitter"></span> Twitter/X</label>
                        <input type="url" name="twitter" value="<?php echo esc_url($empresa['twitter'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><span class="dashicons dashicons-instagram"></span> Instagram</label>
                        <input type="url" name="instagram" value="<?php echo esc_url($empresa['instagram'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><span class="dashicons dashicons-linkedin"></span> LinkedIn</label>
                        <input type="url" name="linkedin" value="<?php echo esc_url($empresa['linkedin'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
        <?php
    }

    private function render_servicios($datos) {
        $servicios = $datos['servicios'] ?? [];
        ?>
        <div class="empresa-servicios">
            <div class="seccion-header">
                <h3>Servicios</h3>
                <button class="flavor-btn flavor-btn-primary" id="btn-nuevo-servicio">
                    <span class="dashicons dashicons-plus"></span> Añadir servicio
                </button>
            </div>

            <div class="servicios-grid">
                <?php if (empty($servicios)): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-clipboard"></span>
                        <p>No has añadido servicios todavía</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($servicios as $indice => $servicio): ?>
                        <div class="servicio-card" data-indice="<?php echo $indice; ?>">
                            <?php if (!empty($servicio['icono'])): ?>
                                <div class="servicio-icono">
                                    <span class="dashicons <?php echo esc_attr($servicio['icono']); ?>"></span>
                                </div>
                            <?php endif; ?>
                            <h4><?php echo esc_html($servicio['titulo']); ?></h4>
                            <p><?php echo esc_html($servicio['descripcion']); ?></p>
                            <?php if (!empty($servicio['precio'])): ?>
                                <span class="servicio-precio">Desde <?php echo esc_html($servicio['precio']); ?>€</span>
                            <?php endif; ?>
                            <div class="servicio-acciones">
                                <button class="flavor-btn flavor-btn-sm editar-servicio">Editar</button>
                                <button class="flavor-btn flavor-btn-sm flavor-btn-danger eliminar-servicio">Eliminar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Modal añadir/editar servicio -->
            <div id="modal-servicio" class="flavor-modal" style="display:none;">
                <div class="flavor-modal-content">
                    <div class="flavor-modal-header">
                        <h3>Servicio</h3>
                        <button class="cerrar-modal">&times;</button>
                    </div>
                    <div class="flavor-modal-body">
                        <form id="form-servicio">
                            <input type="hidden" name="indice" value="-1">
                            <div class="form-group">
                                <label>Título *</label>
                                <input type="text" name="titulo" required>
                            </div>
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea name="descripcion" rows="3"></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Icono (dashicons)</label>
                                    <select name="icono">
                                        <option value="">Sin icono</option>
                                        <option value="dashicons-admin-tools">Herramientas</option>
                                        <option value="dashicons-analytics">Analítica</option>
                                        <option value="dashicons-cart">Carrito</option>
                                        <option value="dashicons-admin-site">Web</option>
                                        <option value="dashicons-megaphone">Marketing</option>
                                        <option value="dashicons-hammer">Construcción</option>
                                        <option value="dashicons-welcome-learn-more">Formación</option>
                                        <option value="dashicons-admin-customizer">Diseño</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Precio desde (€)</label>
                                    <input type="number" name="precio" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="flavor-btn flavor-btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_equipo($datos) {
        $equipo = $datos['equipo'] ?? [];
        ?>
        <div class="empresa-equipo">
            <div class="seccion-header">
                <h3>Equipo</h3>
                <button class="flavor-btn flavor-btn-primary" id="btn-nuevo-miembro">
                    <span class="dashicons dashicons-plus"></span> Añadir miembro
                </button>
            </div>

            <div class="equipo-grid">
                <?php if (empty($equipo)): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-groups"></span>
                        <p>No has añadido miembros del equipo</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($equipo as $indice => $miembro): ?>
                        <div class="miembro-card" data-indice="<?php echo $indice; ?>">
                            <div class="miembro-foto">
                                <?php if (!empty($miembro['foto'])): ?>
                                    <img src="<?php echo esc_url($miembro['foto']); ?>" alt="">
                                <?php else: ?>
                                    <span class="dashicons dashicons-admin-users"></span>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo esc_html($miembro['nombre']); ?></h4>
                            <span class="miembro-cargo"><?php echo esc_html($miembro['cargo']); ?></span>
                            <p><?php echo esc_html($miembro['bio']); ?></p>
                            <div class="miembro-redes">
                                <?php if (!empty($miembro['linkedin'])): ?>
                                    <a href="<?php echo esc_url($miembro['linkedin']); ?>" target="_blank">
                                        <span class="dashicons dashicons-linkedin"></span>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($miembro['email'])): ?>
                                    <a href="mailto:<?php echo esc_attr($miembro['email']); ?>">
                                        <span class="dashicons dashicons-email"></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="miembro-acciones">
                                <button class="flavor-btn flavor-btn-sm editar-miembro">Editar</button>
                                <button class="flavor-btn flavor-btn-sm flavor-btn-danger eliminar-miembro">Eliminar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_testimonios($datos) {
        $testimonios = $datos['testimonios'] ?? [];
        ?>
        <div class="empresa-testimonios">
            <div class="seccion-header">
                <h3>Testimonios de clientes</h3>
                <button class="flavor-btn flavor-btn-primary" id="btn-nuevo-testimonio">
                    <span class="dashicons dashicons-plus"></span> Añadir testimonio
                </button>
            </div>

            <div class="testimonios-lista">
                <?php if (empty($testimonios)): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-format-quote"></span>
                        <p>No has añadido testimonios</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($testimonios as $indice => $testimonio): ?>
                        <div class="testimonio-card" data-indice="<?php echo $indice; ?>">
                            <div class="testimonio-contenido">
                                <blockquote><?php echo esc_html($testimonio['texto']); ?></blockquote>
                                <div class="testimonio-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="dashicons dashicons-star-<?php echo $i <= ($testimonio['rating'] ?? 5) ? 'filled' : 'empty'; ?>"></span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="testimonio-autor">
                                <?php if (!empty($testimonio['foto'])): ?>
                                    <img src="<?php echo esc_url($testimonio['foto']); ?>" alt="">
                                <?php endif; ?>
                                <div>
                                    <strong><?php echo esc_html($testimonio['nombre']); ?></strong>
                                    <?php if (!empty($testimonio['empresa'])): ?>
                                        <span><?php echo esc_html($testimonio['empresa']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="testimonio-acciones">
                                <button class="flavor-btn flavor-btn-sm editar-testimonio">Editar</button>
                                <button class="flavor-btn flavor-btn-sm flavor-btn-danger eliminar-testimonio">Eliminar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_portfolio($datos) {
        $proyectos = $datos['portfolio'] ?? [];
        ?>
        <div class="empresa-portfolio">
            <div class="seccion-header">
                <h3>Portfolio / Proyectos</h3>
                <button class="flavor-btn flavor-btn-primary" id="btn-nuevo-proyecto">
                    <span class="dashicons dashicons-plus"></span> Añadir proyecto
                </button>
            </div>

            <div class="portfolio-grid">
                <?php if (empty($proyectos)): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-portfolio"></span>
                        <p>No has añadido proyectos al portfolio</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($proyectos as $indice => $proyecto): ?>
                        <div class="proyecto-card" data-indice="<?php echo $indice; ?>">
                            <div class="proyecto-imagen">
                                <?php if (!empty($proyecto['imagen'])): ?>
                                    <img src="<?php echo esc_url($proyecto['imagen']); ?>" alt="">
                                <?php else: ?>
                                    <div class="placeholder">
                                        <span class="dashicons dashicons-format-image"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="proyecto-info">
                                <h4><?php echo esc_html($proyecto['titulo']); ?></h4>
                                <p><?php echo esc_html($proyecto['descripcion']); ?></p>
                                <?php if (!empty($proyecto['categoria'])): ?>
                                    <span class="proyecto-categoria"><?php echo esc_html($proyecto['categoria']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($proyecto['url'])): ?>
                                    <a href="<?php echo esc_url($proyecto['url']); ?>" target="_blank" class="proyecto-link">
                                        Ver proyecto <span class="dashicons dashicons-external"></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="proyecto-acciones">
                                <button class="flavor-btn flavor-btn-sm editar-proyecto">Editar</button>
                                <button class="flavor-btn flavor-btn-sm flavor-btn-danger eliminar-proyecto">Eliminar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function obtener_datos() {
        $user_id = get_current_user_id();
        $opcion_base = 'flavor_empresarial_' . $user_id;

        return [
            'empresa' => get_option($opcion_base . '_perfil', []),
            'servicios' => get_option($opcion_base . '_servicios', []),
            'equipo' => get_option($opcion_base . '_equipo', []),
            'testimonios' => get_option($opcion_base . '_testimonios', []),
            'portfolio' => get_option($opcion_base . '_portfolio', []),
        ];
    }
}

Flavor_Empresarial_Dashboard_Tab::get_instance();

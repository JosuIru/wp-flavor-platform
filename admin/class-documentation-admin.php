<?php
/**
 * Página de Documentación del Plugin
 *
 * Documentación completa, guías, tips y soporte para Flavor Platform.
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para la página de documentación
 */
class Flavor_Documentation_Admin {

    /**
     * Instancia singleton
     *
     * @var Flavor_Documentation_Admin|null
     */
    private static $instancia = null;

    /**
     * Versión de la documentación
     */
    const DOC_VERSION = '3.1.0';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Documentation_Admin
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'encolar_assets']);
    }

    /**
     * Encola assets específicos para la página de documentación
     *
     * @param string $sufijo_hook
     */
    public function encolar_assets($sufijo_hook) {
        if (strpos($sufijo_hook, 'flavor-platform-docs') === false && strpos($sufijo_hook, 'flavor-documentation') === false) {
            return;
        }

        // Estilos inline para la documentación
        wp_add_inline_style('flavor-admin', $this->obtener_estilos_documentacion());
    }

    /**
     * Renderiza la página de documentación
     */
    public function renderizar_pagina() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $seccion_activa = isset($_GET['seccion']) ? sanitize_key($_GET['seccion']) : 'inicio';
        $secciones = $this->obtener_secciones();
        ?>
        <div class="wrap flavor-docs-wrap">
            <div class="flavor-docs-header">
                <div class="flavor-docs-header-content">
                    <h1>
                        <span class="dashicons dashicons-book-alt"></span>
                        <?php _e('Documentación de Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h1>
                    <p class="flavor-docs-version">
                        <?php printf(__('Versión %s', FLAVOR_PLATFORM_TEXT_DOMAIN), FLAVOR_CHAT_IA_VERSION); ?>
                    </p>
                </div>
                <div class="flavor-docs-search">
                    <input type="text"
                           id="flavor-docs-search"
                           placeholder="<?php esc_attr_e('Buscar en la documentación...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                           class="flavor-docs-search-input">
                    <span class="dashicons dashicons-search"></span>
                </div>
            </div>

            <div class="flavor-docs-container">
                <!-- Sidebar de navegación -->
                <nav class="flavor-docs-nav">
                    <?php foreach ($secciones as $id_seccion => $seccion): ?>
                        <?php if (isset($seccion['separador']) && $seccion['separador']): ?>
                            <div class="flavor-docs-nav-separator">
                                <?php echo esc_html($seccion['titulo']); ?>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo esc_url(add_query_arg('seccion', $id_seccion)); ?>"
                               class="flavor-docs-nav-item <?php echo $seccion_activa === $id_seccion ? 'activo' : ''; ?>">
                                <span class="dashicons <?php echo esc_attr($seccion['icono']); ?>"></span>
                                <?php echo esc_html($seccion['titulo']); ?>
                                <?php if (!empty($seccion['badge'])): ?>
                                    <span class="flavor-docs-badge"><?php echo esc_html($seccion['badge']); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>

                <!-- Contenido principal -->
                <main class="flavor-docs-content">
                    <?php $this->renderizar_seccion($seccion_activa); ?>
                </main>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Búsqueda en documentación
            const searchInput = document.getElementById('flavor-docs-search');
            const content = document.querySelector('.flavor-docs-content');

            if (searchInput && content) {
                searchInput.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase();
                    const sections = content.querySelectorAll('.flavor-docs-section');

                    sections.forEach(section => {
                        const text = section.textContent.toLowerCase();
                        section.style.display = term === '' || text.includes(term) ? 'block' : 'none';
                    });
                });
            }

            // Scroll suave a anclas
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            // Copiar código al portapapeles
            document.querySelectorAll('.flavor-docs-code-copy').forEach(btn => {
                btn.addEventListener('click', function() {
                    const code = this.parentElement.querySelector('code').textContent;
                    navigator.clipboard.writeText(code).then(() => {
                        this.textContent = '✓ Copiado';
                        setTimeout(() => { this.textContent = 'Copiar'; }, 2000);
                    });
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Obtiene las secciones de documentación
     *
     * @return array
     */
    private function obtener_secciones() {
        return [
            'inicio' => [
                'titulo' => __('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-home',
            ],
            'guia-rapida' => [
                'titulo' => __('Guía Rápida', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-welcome-learn-more',
                'badge' => __('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            // ═══ ADMINISTRACIÓN ═══
            'sep-admin' => [
                'titulo' => __('Administración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'separador' => true,
            ],
            'admin-dashboard' => [
                'titulo' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-dashboard',
            ],
            'admin-compositor' => [
                'titulo' => __('Compositor', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-layout',
            ],
            'admin-diseno' => [
                'titulo' => __('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-art',
            ],
            'admin-paginas' => [
                'titulo' => __('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-page',
            ],
            'admin-config' => [
                'titulo' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-generic',
            ],
            'admin-herramientas' => [
                'titulo' => __('Herramientas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-tools',
            ],
            // ═══ MÓDULOS COMUNIDAD ═══
            'sep-modulos-comunidad' => [
                'titulo' => __('Módulos Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'separador' => true,
            ],
            'mod-socios' => [
                'titulo' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-groups',
            ],
            'mod-eventos' => [
                'titulo' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-calendar',
            ],
            'mod-banco-tiempo' => [
                'titulo' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-clock',
            ],
            'mod-grupos-consumo' => [
                'titulo' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-carrot',
            ],
            'mod-marketplace' => [
                'titulo' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-megaphone',
            ],
            'mod-ayuda-vecinal' => [
                'titulo' => __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-heart',
            ],
            'mod-reservas' => [
                'titulo' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-calendar-alt',
            ],
            // ═══ MÓDULOS CONTENIDO ═══
            'sep-modulos-contenido' => [
                'titulo' => __('Módulos Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'separador' => true,
            ],
            'mod-cursos' => [
                'titulo' => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-welcome-learn-more',
            ],
            'mod-biblioteca' => [
                'titulo' => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-book',
            ],
            'mod-podcast' => [
                'titulo' => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-microphone',
            ],
            // ═══ CHAT IA ═══
            'sep-chat' => [
                'titulo' => __('Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'separador' => true,
            ],
            'chat-config' => [
                'titulo' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-format-chat',
            ],
            'motores-ia' => [
                'titulo' => __('Motores de IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-superhero',
            ],
            'escalados' => [
                'titulo' => __('Escalados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-sos',
            ],
            // ═══ DESARROLLO ═══
            'sep-dev' => [
                'titulo' => __('Desarrollo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'separador' => true,
            ],
            'api-rest' => [
                'titulo' => __('API REST', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-rest-api',
            ],
            'shortcodes' => [
                'titulo' => __('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-editor-code',
            ],
            'base-datos' => [
                'titulo' => __('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-database',
            ],
            'hooks' => [
                'titulo' => __('Hooks & Filtros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-tools',
            ],
            // ═══ EXTRAS ═══
            'sep-extra' => [
                'titulo' => __('Extras', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'separador' => true,
            ],
            'addons' => [
                'titulo' => __('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-plugins',
            ],
            'tips' => [
                'titulo' => __('Tips', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-lightbulb',
            ],
            'faq' => [
                'titulo' => __('FAQ', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-editor-help',
            ],
            'soporte' => [
                'titulo' => __('Soporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-email',
            ],
            'changelog' => [
                'titulo' => __('Changelog', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-backup',
            ],
        ];
    }

    /**
     * Renderiza una sección específica
     *
     * @param string $seccion
     */
    private function renderizar_seccion($seccion) {
        $metodo = 'renderizar_seccion_' . str_replace('-', '_', $seccion);

        if (method_exists($this, $metodo)) {
            call_user_func([$this, $metodo]);
        } else {
            $this->renderizar_seccion_inicio();
        }
    }

    /**
     * Sección: Inicio
     */
    private function renderizar_seccion_inicio() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Bienvenido a Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-docs-intro-cards">
                <div class="flavor-docs-intro-card">
                    <div class="flavor-docs-intro-icon" style="background: #dbeafe;">
                        <span class="dashicons dashicons-superhero-alt" style="color: #2563eb;"></span>
                    </div>
                    <h3><?php _e('¿Qué es Flavor Platform?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Flavor Platform es una solución integral para WordPress que combina un asistente de IA conversacional, sistema modular de comunidades, landing pages dinámicas y herramientas de gestión avanzadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>

                <div class="flavor-docs-intro-card">
                    <div class="flavor-docs-intro-icon" style="background: #dcfce7;">
                        <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span>
                    </div>
                    <h3><?php _e('Características Principales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <ul>
                        <li><?php _e('Asistente IA multi-proveedor (Claude, OpenAI, DeepSeek, Mistral)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('15+ módulos de comunidad preconstruidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Sistema de plantillas con un clic', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('API REST completa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Compatible con apps móviles (PWA)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-intro-card">
                    <div class="flavor-docs-intro-icon" style="background: #fef3c7;">
                        <span class="dashicons dashicons-performance" style="color: #d97706;"></span>
                    </div>
                    <h3><?php _e('Empezar Rápido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <ol>
                        <li><?php _e('Ve a Compositor & Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Selecciona una plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Activa con datos de demo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('¡Listo para usar!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ol>
                    <a href="<?php echo admin_url('admin.php?page=flavor-module-dashboards'); ?>" class="button button-primary">
                        <?php _e('Ir a Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                    </a>
                </div>
            </div>

            <div class="flavor-docs-quick-links">
                <h3><?php _e('Enlaces Rápidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="flavor-docs-quick-links-grid">
                    <a href="<?php echo admin_url('admin.php?page=flavor-dashboard'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php _e('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-module-dashboards'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-screenoptions"></span>
                        <?php _e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-platform-settings'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-design-settings'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-art"></span>
                        <?php _e('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-addons'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php _e('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-platform-health-check'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-heart"></span>
                        <?php _e('Diagnóstico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Guía Rápida
     */
    private function renderizar_seccion_guia_rapida() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Guía de Inicio Rápido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-docs-steps">
                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">1</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('Selecciona una Plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <p><?php _e('Ve a <strong>Compositor & Módulos</strong> y elige una plantilla que se adapte a tu proyecto:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <ul>
                            <li><strong><?php _e('Grupo de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Cooperativas alimentarias y pedidos colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><strong><?php _e('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Intercambio de servicios por horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><strong><?php _e('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Asociaciones, clubs y entidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><strong><?php _e('Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Comunidades vecinales y locales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><strong><?php _e('Tienda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('E-commerce con WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">2</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('Activa la Plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <p><?php _e('Al hacer clic en una plantilla, verás un preview con todo lo que se instalará:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <ul>
                            <li><?php _e('Módulos que se activarán', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Páginas que se crearán', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Secciones de la landing page', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        </ul>
                        <p><?php _e('Marca la opción <strong>"Cargar datos de demostración"</strong> para tener contenido de ejemplo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>

                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">3</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('Configura el Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <p><?php _e('Para activar el asistente de IA, ve a <strong>Configuración</strong> y:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <ol>
                            <li><?php _e('Selecciona un proveedor de IA (Claude recomendado)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Introduce tu API key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Personaliza el nombre y personalidad del asistente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Activa el widget flotante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        </ol>
                    </div>
                </div>

                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">4</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('Personaliza el Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <p><?php _e('En <strong>Diseño y Apariencia</strong> puedes:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <ul>
                            <li><?php _e('Cambiar colores principales y secundarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Seleccionar tipografías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Ajustar espaciados y bordes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Configurar el modo oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">5</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('¡Listo!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <p><?php _e('Tu aplicación está configurada. Ahora puedes:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <ul>
                            <li><?php _e('Visitar las páginas creadas en el frontend', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Probar el chat de IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Ajustar módulos según tus necesidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Añadir contenido real', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <div>
                    <strong><?php _e('Consejo Pro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <p><?php _e('Usa el Dashboard para monitorizar el uso del chat, ver estadísticas y gestionar escalados en tiempo real.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Compositor & Plantillas
     */
    private function renderizar_seccion_compositor() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Compositor & Plantillas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('El Compositor es el corazón de Flavor Platform. Te permite crear aplicaciones completas con un solo clic seleccionando plantillas prediseñadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('¿Qué es una Plantilla?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Una plantilla es un conjunto preconfigurado que incluye:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><strong><?php _e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Funcionalidades específicas (eventos, marketplace, miembros...)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Páginas de WordPress con shortcodes preconfigurados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Landing Page', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Secciones visuales (hero, features, CTA...)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Ajustes óptimos para el caso de uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Datos Demo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Contenido de ejemplo para probar (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Plantillas Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Caso de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Módulos Principales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Grupo de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('Cooperativas alimentarias, compras colectivas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Grupos Consumo, Productores, Ciclos de pedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('Intercambio de servicios, economía colaborativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Banco Tiempo, Servicios, Saldos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('Asociaciones, clubs, entidades sin ánimo de lucro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Miembros, Eventos, Cuotas, Comunicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('Comunidades vecinales, gestión de barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Ayuda Vecinal, Huertos, Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Tienda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('E-commerce, catálogos de productos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('WooCommerce, Marketplace, Sellos Calidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Proceso de Activación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Cuando activas una plantilla, el sistema ejecuta automáticamente:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ol>
                <li><strong><?php _e('Instalación de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Activa los módulos requeridos y opcionales seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Creación de Tablas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Crea las tablas de base de datos necesarias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Generación de Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Crea páginas de WordPress con los shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Configuración de Landing', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Configura las secciones de la página principal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Aplicación de Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('Establece los ajustes recomendados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Carga de Demo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> - <?php _e('(Opcional) Inserta datos de ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ol>

            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong><?php _e('Importante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <p><?php _e('Cambiar de plantilla no elimina los datos existentes, pero puede desactivar módulos que estabas usando. Recomendamos hacer una copia de seguridad antes de cambiar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Módulos
     */
    private function renderizar_seccion_modulos() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Los módulos son las unidades funcionales de Flavor Platform. Cada módulo añade características específicas que puedes activar o desactivar según tus necesidades.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Módulos de Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-modules-grid">
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-groups" style="color: #f43f5e;"></span>
                    <h4><?php _e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Gestión de membresías, cuotas periódicas y carnets digitales.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-calendar" style="color: #3b82f6;"></span>
                    <h4><?php _e('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Calendario de eventos, inscripciones y gestión de asistencia.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-clock" style="color: #8b5cf6;"></span>
                    <h4><?php _e('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Intercambio de servicios por horas entre miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-carrot" style="color: #22c55e;"></span>
                    <h4><?php _e('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Pedidos colectivos, productores locales y ciclos de compra.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-heart" style="color: #ef4444;"></span>
                    <h4><?php _e('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Red de apoyo mutuo entre vecinos del barrio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-admin-multisite" style="color: #06b6d4;"></span>
                    <h4><?php _e('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Reserva de salas, locales y recursos compartidos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <h3><?php _e('Módulos de Comercio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-modules-grid">
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-cart" style="color: #7c3aed;"></span>
                    <h4><?php _e('WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Integración completa con WooCommerce para tiendas online.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-megaphone" style="color: #f59e0b;"></span>
                    <h4><?php _e('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Tablón de anuncios para compra, venta e intercambio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-awards" style="color: #10b981;"></span>
                    <h4><?php _e('Sellos de Calidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Certificaciones y etiquetas para productos y servicios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <h3><?php _e('Módulos de Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-modules-grid">
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-book" style="color: #6366f1;"></span>
                    <h4><?php _e('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Catálogo y préstamo de libros comunitarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-microphone" style="color: #14b8a6;"></span>
                    <h4><?php _e('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Publicación y gestión de contenido de audio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-welcome-learn-more" style="color: #a855f7;"></span>
                    <h4><?php _e('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Formación y talleres educativos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <h3><?php _e('Activar/Desactivar Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Para gestionar módulos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ol>
                <li><?php _e('Ve a <strong>Compositor & Módulos</strong>', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Haz clic en la pestaña <strong>Módulos</strong>', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Usa los toggles para activar o desactivar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Los módulos requeridos por la plantilla activa no se pueden desactivar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ol>
        </div>
        <?php
    }

    /**
     * Sección: Landing Pages
     */
    private function renderizar_seccion_landings() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Landing Pages', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Las landing pages son páginas frontend preconfiguradas para cada tipo de aplicación. Se generan automáticamente con secciones modulares.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Secciones Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Sección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Variantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Hero</strong></td>
                            <td><?php _e('Cabecera principal con título, subtítulo y CTA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Centrado, Con imagen, Video, Gradiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Features</strong></td>
                            <td><?php _e('Grid de características con iconos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('3 columnas, 4 columnas, Lista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong>CTA</strong></td>
                            <td><?php _e('Llamada a la acción destacada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Simple, Con imagen, Dual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Grid</strong></td>
                            <td><?php _e('Listado de contenidos en rejilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Cards, Masonry, Carousel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Stats</strong></td>
                            <td><?php _e('Estadísticas numéricas destacadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Horizontal, Con iconos, Animadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Testimonios</strong></td>
                            <td><?php _e('Opiniones de usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Slider, Grid, Quote', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong>FAQ</strong></td>
                            <td><?php _e('Preguntas frecuentes en acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Acordeón, Dos columnas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Contacto</strong></td>
                            <td><?php _e('Formulario de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Simple, Con mapa, Split', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Shortcode Principal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_landing id="grupo_consumo"]</code></pre>
            </div>

            <h3><?php _e('Páginas de Demo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('En el Compositor encontrarás la sección <strong>Páginas de Demostración</strong> donde puedes:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><?php _e('Crear todas las páginas de landing con un clic', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Ver el estado de cada página (creada, demo, existe)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Eliminar las páginas de demo cuando ya no las necesites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Sección: Diseño & Temas
     */
    private function renderizar_seccion_diseno() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Diseño & Temas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Personaliza completamente la apariencia de tu aplicación usando el sistema de design tokens.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Design Tokens', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Flavor Platform usa un sistema de tokens CSS que te permite cambiar toda la apariencia modificando unas pocas variables:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div class="flavor-docs-tokens-grid">
                <div class="flavor-docs-token-group">
                    <h4><?php _e('Colores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><code>--flavor-primary</code> - <?php _e('Color principal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><code>--flavor-secondary</code> - <?php _e('Color secundario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><code>--flavor-accent</code> - <?php _e('Color de acento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><code>--flavor-success</code> - <?php _e('Éxito/Confirmación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><code>--flavor-warning</code> - <?php _e('Advertencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><code>--flavor-error</code> - <?php _e('Error', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>
                <div class="flavor-docs-token-group">
                    <h4><?php _e('Tipografía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><code>--flavor-font-family</code> - <?php _e('Fuente principal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><code>--flavor-font-size-base</code> - <?php _e('Tamaño base', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><code>--flavor-line-height</code> - <?php _e('Altura de línea', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>
                <div class="flavor-docs-token-group">
                    <h4><?php _e('Espaciado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><code>--flavor-space-xs</code> - 4px</li>
                        <li><code>--flavor-space-sm</code> - 8px</li>
                        <li><code>--flavor-space-md</code> - 16px</li>
                        <li><code>--flavor-space-lg</code> - 24px</li>
                        <li><code>--flavor-space-xl</code> - 32px</li>
                    </ul>
                </div>
                <div class="flavor-docs-token-group">
                    <h4><?php _e('Bordes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><code>--flavor-radius-sm</code> - 4px</li>
                        <li><code>--flavor-radius-md</code> - 8px</li>
                        <li><code>--flavor-radius-lg</code> - 12px</li>
                        <li><code>--flavor-radius-full</code> - 9999px</li>
                    </ul>
                </div>
            </div>

            <h3><?php _e('Modo Oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('El modo oscuro se activa automáticamente según las preferencias del sistema del usuario, o puedes forzarlo en la configuración.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
        <?php
    }

    /**
     * Sección: Configuración del Chat
     */
    private function renderizar_seccion_chat_config() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Configuración del Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <h3><?php _e('Configuración Básica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Opción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Recomendación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Nombre del Asistente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('Nombre que verán los usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Usa un nombre amigable relacionado con tu marca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Rol/Personalidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('Instrucciones de sistema para el comportamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Sé específico sobre el contexto de tu negocio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Tono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('Estilo de comunicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Friendly para B2C, Professional para B2B', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Widget Flotante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('Mostrar el chat en todas las páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Actívalo para mejor accesibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Posición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td><?php _e('Esquina donde aparece el widget', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><?php _e('Bottom-right es el estándar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Base de Conocimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Añade información específica que el asistente debe conocer:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><?php _e('Información de tu empresa/organización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Productos y servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Horarios y contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Preguntas frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Políticas y condiciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('FAQs Precargadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Define preguntas frecuentes con respuestas predefinidas para respuestas instantáneas sin consumir tokens de IA.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <div>
                    <strong><?php _e('Ahorra Costos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <p><?php _e('Las FAQs con matching exacto responden sin llamar a la IA. Ideal para preguntas repetitivas como horarios, precios o direcciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Motores de IA
     */
    private function renderizar_seccion_motores_ia() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Motores de IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Flavor Platform es multi-proveedor: puedes elegir entre diferentes servicios de IA según tus necesidades y presupuesto.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Proveedor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Modelos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Mejor Para', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Costo Aprox.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Claude (Anthropic)</strong></td>
                            <td>Claude 3.5 Sonnet, Claude 3 Opus</td>
                            <td><?php _e('Calidad máxima, contexto largo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td>$3-15 / 1M tokens</td>
                        </tr>
                        <tr>
                            <td><strong>OpenAI</strong></td>
                            <td>GPT-4o, GPT-4o-mini</td>
                            <td><?php _e('Balance calidad/precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td>$0.15-5 / 1M tokens</td>
                        </tr>
                        <tr>
                            <td><strong>DeepSeek</strong></td>
                            <td>DeepSeek Chat, DeepSeek Coder</td>
                            <td><?php _e('Máximo ahorro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td>$0.14 / 1M tokens</td>
                        </tr>
                        <tr>
                            <td><strong>Mistral</strong></td>
                            <td>Mistral Small, Mistral Large</td>
                            <td><?php _e('Europa, GDPR compliance', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td>$0.2-2 / 1M tokens</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Obtener API Keys', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><strong>Claude:</strong> <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></li>
                <li><strong>OpenAI:</strong> <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></li>
                <li><strong>DeepSeek:</strong> <a href="https://platform.deepseek.com/" target="_blank">platform.deepseek.com</a></li>
                <li><strong>Mistral:</strong> <a href="https://console.mistral.ai/" target="_blank">console.mistral.ai</a></li>
            </ul>

            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-shield"></span>
                <div>
                    <strong><?php _e('Seguridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <p><?php _e('Las API keys se almacenan de forma segura en la base de datos de WordPress. Nunca las compartas públicamente ni las incluyas en código versionado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Sistema de Escalados
     */
    private function renderizar_seccion_escalados() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Sistema de Escalados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Cuando el chat no puede resolver una consulta, puede escalar a atención humana automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Canales de Escalado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><strong>WhatsApp</strong> - <?php _e('Envía al usuario a un chat de WhatsApp', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong>Teléfono</strong> - <?php _e('Muestra un número de teléfono para llamar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong>Email</strong> - <?php _e('Abre el cliente de correo del usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Configuración de Horarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Define los horarios de atención para que el chat informe al usuario cuándo puede recibir atención humana.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-docs-code-block">
                <pre><code>L-V 9:00-18:00
S 10:00-14:00</code></pre>
            </div>

            <h3><?php _e('Gestión de Escalados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('En la página de <strong>Escalados</strong> puedes:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><?php _e('Ver todos los escalados pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Filtrar por estado (pendiente, contactado, resuelto)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Ver el resumen de la conversación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Marcar escalados como resueltos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Añadir notas internas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Sección: API REST
     */
    private function renderizar_seccion_api_rest() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('API REST', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Flavor Platform expone una API REST completa para integrar con aplicaciones externas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Base URL', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code><?php echo esc_html(rest_url('flavor/v1/')); ?></code></pre>
            </div>

            <h3><?php _e('Autenticación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('La API soporta varios métodos de autenticación:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><strong>Cookie Auth</strong> - <?php _e('Para usuarios logueados en WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong>Application Passwords</strong> - <?php _e('Para integraciones externas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong>JWT</strong> - <?php _e('Con plugins como JWT Auth (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Endpoints Principales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>POST</code></td>
                            <td><code>/chat/message</code></td>
                            <td><?php _e('Enviar mensaje al chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/chat/history/{session_id}</code></td>
                            <td><?php _e('Obtener historial de conversación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/modules</code></td>
                            <td><?php _e('Listar módulos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/modules/{id}/items</code></td>
                            <td><?php _e('Obtener ítems de un módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>POST</code></td>
                            <td><code>/modules/{id}/actions</code></td>
                            <td><?php _e('Ejecutar acción en módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/user/dashboard</code></td>
                            <td><?php _e('Datos del dashboard de usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Ejemplo de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>// Enviar mensaje al chat
fetch('<?php echo esc_js(rest_url('flavor/v1/chat/message')); ?>', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
    },
    body: JSON.stringify({
        message: 'Hola, ¿cuáles son vuestros horarios?',
        session_id: 'abc123'
    })
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>
            </div>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('Rate Limiting', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <p><?php _e('La API tiene límite de 60 peticiones por minuto por IP para endpoints públicos. Los endpoints autenticados tienen límites más altos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Shortcodes
     */
    private function renderizar_seccion_shortcodes() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <h3><?php _e('Chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_chat]
[flavor_chat position="inline" height="500px"]</code></pre>
            </div>

            <h3><?php _e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_module_listing module="eventos" limit="6" columns="3"]
[flavor_module_form module="banco_tiempo" action="crear_servicio"]
[flavor_module_detail module="marketplace" id="123"]</code></pre>
            </div>

            <h3><?php _e('Landing Pages', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_landing id="grupo_consumo"]
[flavor_section type="hero" variant="centered" title="Bienvenido"]
[flavor_section type="features" items="caracteristica1,caracteristica2,caracteristica3"]
[flavor_section type="cta" title="Únete" button_text="Registrarse" button_url="/registro/"]</code></pre>
            </div>

            <h3><?php _e('Dashboard de Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_user_dashboard]
[flavor_user_dashboard tabs="perfil,pedidos,suscripciones"]</code></pre>
            </div>

            <h3><?php _e('Atributos Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Atributo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>limit</code></td>
                            <td><?php _e('Número máximo de ítems', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code>limit="10"</code></td>
                        </tr>
                        <tr>
                            <td><code>columns</code></td>
                            <td><?php _e('Columnas del grid', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code>columns="3"</code></td>
                        </tr>
                        <tr>
                            <td><code>orderby</code></td>
                            <td><?php _e('Campo de ordenación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code>orderby="date"</code></td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td><?php _e('Dirección de orden', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code>order="DESC"</code></td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php _e('Clases CSS adicionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td><code>class="mi-clase"</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Webhooks
     */
    private function renderizar_seccion_webhooks() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Webhooks', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Los webhooks permiten que tu aplicación reciba notificaciones en tiempo real cuando ocurren eventos importantes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Eventos Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>chat.message</code></td>
                            <td><?php _e('Nuevo mensaje en el chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>chat.escalation</code></td>
                            <td><?php _e('Conversación escalada a humano', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>module.item_created</code></td>
                            <td><?php _e('Nuevo ítem creado en módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>module.item_updated</code></td>
                            <td><?php _e('Ítem actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>user.registered</code></td>
                            <td><?php _e('Nuevo usuario registrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><code>payment.completed</code></td>
                            <td><?php _e('Pago completado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Payload de Ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>{
    "event": "chat.escalation",
    "timestamp": "2024-01-15T10:30:00Z",
    "data": {
        "conversation_id": 123,
        "session_id": "abc123",
        "reason": "user_requested",
        "summary": "El usuario necesita ayuda con su pedido #456",
        "contact": {
            "method": "whatsapp",
            "value": "+34600000000"
        }
    },
    "site_url": "https://tu-sitio.com"
}</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Hooks & Filtros
     */
    private function renderizar_seccion_hooks() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Hooks & Filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Flavor Platform ofrece numerosos hooks y filtros para personalizar su comportamiento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Actions (Acciones)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <pre><code>// Antes de enviar mensaje al chat
do_action('flavor_before_chat_message', $message, $session_id);

// Después de crear ítem en módulo
do_action('flavor_module_item_created', $item_id, $module_id, $data);

// Al activar una plantilla
do_action('flavor_template_activated', $template_id, $options);

// Al escalar conversación
do_action('flavor_chat_escalated', $conversation_id, $reason);</code></pre>
            </div>

            <h3><?php _e('Filters (Filtros)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <pre><code>// Modificar respuesta del chat antes de enviar
$response = apply_filters('flavor_chat_response', $response, $message, $context);

// Modificar ítems de módulo antes de mostrar
$items = apply_filters('flavor_module_items', $items, $module_id, $args);

// Modificar plantillas disponibles
$templates = apply_filters('flavor_available_templates', $templates);

// Modificar configuración de diseño
$tokens = apply_filters('flavor_design_tokens', $tokens);</code></pre>
            </div>

            <h3><?php _e('Ejemplo de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>// En tu functions.php o plugin personalizado

// Añadir firma a todas las respuestas del chat
add_filter('flavor_chat_response', function($response, $message, $context) {
    return $response . "\n\n---\n_Respuesta automática de MiBot_";
}, 10, 3);

// Log de escalados
add_action('flavor_chat_escalated', function($conversation_id, $reason) {
    error_log("Escalado #{$conversation_id}: {$reason}");
}, 10, 2);</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Addons
     */
    private function renderizar_seccion_addons() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Los addons amplían las funcionalidades de Flavor Platform con características premium y especializadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Gestión de Addons', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('En la página de <strong>Addons</strong> puedes:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><?php _e('Ver los addons instalados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Activar/desactivar addons', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Gestionar licencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Actualizar addons', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Explora el <strong>Marketplace</strong> para descubrir nuevos addons:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><?php _e('Addons gratuitos de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Addons premium con soporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Integraciones con servicios externos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Desarrollar Addons', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Puedes crear tus propios addons siguiendo la estructura:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-docs-code-block">
                <pre><code>flavor-mi-addon/
├── flavor-mi-addon.php      // Archivo principal
├── includes/
│   └── class-mi-addon.php   // Clase principal
├── assets/
│   ├── css/
│   └── js/
└── readme.txt</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Tips & Mejores Prácticas
     */
    private function renderizar_seccion_tips() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Tips & Mejores Prácticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-docs-tips-grid">
                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-performance" style="color: #f59e0b;"></span>
                    <h4><?php _e('Rendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><?php _e('Usa caché de objetos (Redis/Memcached) para sitios con mucho tráfico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Activa solo los módulos que realmente necesites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Usa FAQs precargadas para reducir llamadas a la IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Configura límites de tokens por mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-shield" style="color: #22c55e;"></span>
                    <h4><?php _e('Seguridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><?php _e('Mantén WordPress y plugins actualizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('No compartas API keys en código público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Usa HTTPS siempre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Revisa los escalados regularmente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-admin-comments" style="color: #3b82f6;"></span>
                    <h4><?php _e('Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><?php _e('Sé específico en las instrucciones de sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Añade ejemplos en la base de conocimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Define claramente qué no debe hacer el bot', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Prueba con diferentes escenarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-admin-appearance" style="color: #8b5cf6;"></span>
                    <h4><?php _e('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><?php _e('Usa colores que reflejen tu marca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Mantén la consistencia en toda la app', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Prueba en móvil y escritorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Considera el modo oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-backup" style="color: #ef4444;"></span>
                    <h4><?php _e('Backups', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><?php _e('Haz backup antes de cambiar de plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Usa la función Export/Import regularmente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Guarda copias de tu configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Prueba los cambios en staging primero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-chart-line" style="color: #14b8a6;"></span>
                    <h4><?php _e('Monitorización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul>
                        <li><?php _e('Revisa el Dashboard diariamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Analiza las conversaciones escaladas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Monitoriza el consumo de tokens', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><?php _e('Usa el Diagnóstico para detectar problemas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: FAQ
     */
    private function renderizar_seccion_faq() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Preguntas Frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-docs-faq">
                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Cuánto cuesta usar Flavor Platform?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('El plugin es gratuito. Los costes vienen de los proveedores de IA (Claude, OpenAI, etc.) según tu uso. Puedes empezar con DeepSeek que es muy económico (~$0.14/millón de tokens).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Necesito conocimientos de programación?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('No. El sistema de plantillas y el compositor visual te permiten crear aplicaciones completas sin escribir código. Solo necesitas programar si quieres personalizaciones avanzadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Puedo usar múltiples plantillas?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Solo puedes tener una plantilla activa, pero puedes activar módulos adicionales de otras plantillas manualmente desde el Compositor.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Es compatible con mi tema de WordPress?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Flavor Platform está diseñado para funcionar con cualquier tema. Usa CSS aislado y no interfiere con los estilos de tu tema. Si encuentras conflictos, contacta con soporte.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Cómo elimino los datos de demostración?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Ve a Compositor & Módulos, baja hasta "Datos de Demostración" y haz clic en "Eliminar datos demo" del módulo que quieras limpiar, o "Eliminar todos" para borrar todo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿El chat funciona en móviles?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Sí, el widget de chat es completamente responsive y funciona en móviles, tablets y escritorio. También es compatible con PWA para experiencia tipo app nativa.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Puedo personalizar las respuestas del chat?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Sí, mediante: 1) Instrucciones de sistema (rol/personalidad), 2) Base de conocimiento, 3) FAQs precargadas, 4) Filtros PHP para desarrolladores.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Cómo actualizo el plugin?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Las actualizaciones aparecen en el panel de plugins de WordPress como cualquier otro plugin. Recomendamos hacer backup antes de actualizar versiones mayores.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </details>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Soporte
     */
    private function renderizar_seccion_soporte() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Soporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-docs-support-grid">
                <div class="flavor-docs-support-card">
                    <span class="dashicons dashicons-book" style="color: #3b82f6;"></span>
                    <h3><?php _e('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Estás en ella. Usa la navegación lateral para explorar todas las secciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>

                <div class="flavor-docs-support-card">
                    <span class="dashicons dashicons-admin-tools" style="color: #f59e0b;"></span>
                    <h3><?php _e('Diagnóstico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Usa la herramienta de diagnóstico para identificar y resolver problemas comunes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=flavor-platform-health-check'); ?>" class="button">
                        <?php _e('Ir a Diagnóstico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>

                <div class="flavor-docs-support-card">
                    <span class="dashicons dashicons-email" style="color: #22c55e;"></span>
                    <h3><?php _e('Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('¿Tienes preguntas o necesitas ayuda personalizada?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="mailto:soporte@gailu.net" class="button button-primary">
                        soporte@gailu.net
                    </a>
                </div>

                <div class="flavor-docs-support-card">
                    <span class="dashicons dashicons-admin-site-alt3" style="color: #8b5cf6;"></span>
                    <h3><?php _e('Sitio Web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Visita nuestra web para más recursos, tutoriales y novedades.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="https://gailu.net" target="_blank" class="button">
                        gailu.net ↗
                    </a>
                </div>
            </div>

            <div class="flavor-docs-system-info">
                <h3><?php _e('Información del Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Incluye esta información al reportar problemas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <div class="flavor-docs-code-block">
                    <button class="flavor-docs-code-copy">Copiar</button>
                    <pre><code><?php
                    echo "Flavor Platform: " . FLAVOR_CHAT_IA_VERSION . "\n";
                    echo "WordPress: " . get_bloginfo('version') . "\n";
                    echo "PHP: " . phpversion() . "\n";
                    echo "Tema: " . wp_get_theme()->get('Name') . " " . wp_get_theme()->get('Version') . "\n";
                    $settings = flavor_get_main_settings();
                    echo "Perfil Activo: " . ($settings['app_profile'] ?? 'personalizado');
                    ?></code></pre>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Changelog
     */
    private function renderizar_seccion_changelog() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Changelog', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-docs-changelog">
                <div class="flavor-docs-changelog-version">
                    <div class="flavor-docs-changelog-header">
                        <span class="version-badge">3.1.0</span>
                        <span class="version-date"><?php _e('Febrero 2026', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="version-tag nuevo"><?php _e('Actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <ul>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Template Orchestrator - Activación automatizada de plantillas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Sistema de suscripciones y cuotas para miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Dashboard de usuario frontend (Mi Cuenta)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Push Notifications via Firebase', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Gestor de Newsletter con campañas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Página de documentación integrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge mejora">Mejora</span> <?php _e('Rendimiento de caché mejorado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge mejora">Mejora</span> <?php _e('Rate limiter para API REST', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge fix">Fix</span> <?php _e('Datos demo de Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-changelog-version">
                    <div class="flavor-docs-changelog-header">
                        <span class="version-badge">3.0.0</span>
                        <span class="version-date"><?php _e('Enero 2026', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <ul>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Sistema de Addons con marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Multi-proveedor IA (Claude, OpenAI, DeepSeek, Mistral)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Page Builder con secciones modulares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Deep Links para apps móviles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge mejora">Mejora</span> <?php _e('Arquitectura modular refactorizada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-changelog-version">
                    <div class="flavor-docs-changelog-header">
                        <span class="version-badge">2.0.0</span>
                        <span class="version-date"><?php _e('Noviembre 2025', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <ul>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Perfiles de aplicación (plantillas)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Sistema de módulos activables', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Landing pages dinámicas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('API REST completa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-changelog-version">
                    <div class="flavor-docs-changelog-header">
                        <span class="version-badge">1.0.0</span>
                        <span class="version-date"><?php _e('Septiembre 2025', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <ul>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Lanzamiento inicial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Asistente IA con Claude', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Sistema de escalados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Integración WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    // ════════════════════════════════════════════════════════════════════════════════
    // SECCIONES DE ADMINISTRACIÓN
    // ════════════════════════════════════════════════════════════════════════════════

    /**
     * Sección: Admin Dashboard
     */
    private function renderizar_seccion_admin_dashboard() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Dashboard de Administración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-docs-intro"><?php _e('El Dashboard es tu centro de control para monitorear el estado de la plataforma.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Widgets Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-grid">
                <div class="flavor-docs-card">
                    <span class="dashicons dashicons-chart-bar" style="color: #2563eb;"></span>
                    <h4><?php _e('Estadísticas Generales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Usuarios activos, conversaciones del asistente IA, módulos activos y uso de API.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-card">
                    <span class="dashicons dashicons-admin-users" style="color: #16a34a;"></span>
                    <h4><?php _e('Actividad de Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Registros recientes, últimos logins y usuarios por rol.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-card">
                    <span class="dashicons dashicons-format-chat" style="color: #d97706;"></span>
                    <h4><?php _e('Estadísticas del Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Conversaciones activas, escalados pendientes y uso de tokens.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-docs-card">
                    <span class="dashicons dashicons-admin-plugins" style="color: #7c3aed;"></span>
                    <h4><?php _e('Estado de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php _e('Módulos activos/inactivos y estado de sus tablas de base de datos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <h3><?php _e('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><strong><?php _e('Ir al Compositor:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Configura tu aplicación desde cero.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Ver Escalados:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Atiende las conversaciones que requieren intervención humana.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Diagnóstico:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Verifica el estado técnico de la instalación.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Exportar Datos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Realiza copias de seguridad de la configuración.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <p><?php _e('El Dashboard se actualiza automáticamente cada 5 minutos. Los contadores muestran datos de los últimos 30 días.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Admin Compositor
     */
    private function renderizar_seccion_admin_compositor() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Compositor & Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-docs-intro"><?php _e('El Compositor es la herramienta central para configurar tu aplicación. Selecciona plantillas predefinidas o personaliza módulos individualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Plantillas Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Módulos Incluidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php _e('Comunidad Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><?php _e('Gestión de comunidades de vecinos, eventos y espacios comunes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        <td>socios, eventos, espacios-comunes, ayuda-vecinal</td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Asociación Cultural', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><?php _e('Para asociaciones con eventos, cursos y biblioteca.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        <td>socios, eventos, cursos, biblioteca</td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Economía Colaborativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><?php _e('Banco de tiempo, grupos de consumo y marketplace.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        <td>banco-tiempo, grupos-consumo, marketplace</td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Ayuntamiento Digital', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><?php _e('Portal ciudadano con trámites, participación y transparencia.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        <td>tramites, participacion, transparencia, incidencias</td>
                    </tr>
                </tbody>
            </table>

            <h3><?php _e('Template Orchestrator', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('El Template Orchestrator automatiza la activación completa:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ol>
                <li><?php _e('Activa los módulos requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Crea las tablas de base de datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Registra los CPTs necesarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Genera páginas con shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Carga la landing page de la plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Aplica configuración predeterminada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Inserta datos de demostración (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ol>

            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>// Activar plantilla programáticamente
$orchestrator = Flavor_Template_Orchestrator::get_instance();
$resultado = $orchestrator->activar_plantilla('comunidad-vecinal', [
    'datos_demo' => true,
    'landing' => true
]);</code></pre>
            </div>

            <h3><?php _e('Gestión Manual de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('También puedes activar/desactivar módulos individualmente desde la pestaña "Módulos" del Compositor. Cada módulo muestra:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><strong><?php _e('Estado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Activo/Inactivo con toggle switch.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Dependencias:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Módulos que requiere para funcionar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Descripción:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Funcionalidad que aporta.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Categoría:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Comunidad, Contenido, Economía, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Sección: Admin Diseño
     */
    private function renderizar_seccion_admin_diseno() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Diseño y Apariencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Personaliza la apariencia visual de tu aplicación sin necesidad de código.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Opciones de Personalización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <h4><?php _e('Colores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <li><strong><?php _e('Color Primario:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Usado en botones, enlaces y elementos destacados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Color Secundario:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Para acentos y elementos secundarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Color de Fondo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Fondo general de la aplicación.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Color de Texto:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Color principal del texto.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h4><?php _e('Tipografía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <li><?php _e('Fuente de encabezados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Fuente de cuerpo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Tamaño base (rem)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Peso de fuente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h4><?php _e('Logotipos e Imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <li><strong><?php _e('Logo principal:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Se muestra en el header.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Logo alternativo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Versión clara para fondos oscuros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Favicon:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Icono de la pestaña del navegador.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Imagen de login:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Fondo de la página de acceso.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Layouts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Los layouts definen la estructura de las secciones de landing pages. Se acceden desde Diseño → Layouts.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>hero</td><td><?php _e('Cabecera principal con imagen/video de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>features</td><td><?php _e('Características en columnas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>cta</td><td><?php _e('Llamada a la acción con botón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>testimonios</td><td><?php _e('Carrusel de testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>pricing</td><td><?php _e('Tabla de precios/planes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>faq</td><td><?php _e('Preguntas frecuentes con acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>contacto</td><td><?php _e('Formulario de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <p><?php _e('Los cambios de diseño se aplican en tiempo real. Usa la vista previa antes de guardar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Admin Páginas
     */
    private function renderizar_seccion_admin_paginas() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Gestión de Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Crea y gestiona las páginas de tu aplicación con shortcodes integrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Generador de Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('El generador de páginas crea automáticamente páginas WordPress con los shortcodes necesarios para cada módulo activo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h4><?php _e('Páginas por Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Páginas Generadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Socios</td>
                        <td>Directorio de socios, Mi perfil, Alta de socio</td>
                    </tr>
                    <tr>
                        <td>Eventos</td>
                        <td>Calendario, Próximos eventos, Mis inscripciones</td>
                    </tr>
                    <tr>
                        <td>Banco de Tiempo</td>
                        <td>Ofertas, Demandas, Mi balance, Historial</td>
                    </tr>
                    <tr>
                        <td>Grupos de Consumo</td>
                        <td>Productos, Pedidos, Ciclos, Mi cesta</td>
                    </tr>
                    <tr>
                        <td>Marketplace</td>
                        <td>Anuncios, Publicar anuncio, Mis anuncios</td>
                    </tr>
                </tbody>
            </table>

            <h3><?php _e('Página Builder', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('El Page Builder integrado permite crear landing pages arrastrando secciones:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ol>
                <li><?php _e('Edita una página con el editor clásico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Activa el metabox "Flavor Page Builder"', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Arrastra secciones desde la paleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Configura cada sección individualmente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Guarda y previsualiza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ol>

            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-warning"></span>
                <p><?php _e('El Page Builder es incompatible con Gutenberg. Usa el editor clásico para páginas con Page Builder.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Admin Configuración
     */
    private function renderizar_seccion_admin_config() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Configuración General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Configuración del Asistente IA, APIs externas y opciones globales de la plataforma.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Pestañas de Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <h4><?php _e('General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <li><?php _e('Nombre de la organización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Email de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Idioma predeterminado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Zona horaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h4><?php _e('Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <li><strong><?php _e('Motor de IA:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> Claude, OpenAI, DeepSeek, Mistral</li>
                <li><strong><?php _e('API Keys:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Claves de acceso a los proveedores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Modelo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Selección del modelo específico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('System Prompt:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Instrucciones base para el asistente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Límites:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Tokens máximos, mensajes por sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h4><?php _e('Escalados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <li><?php _e('Activar/desactivar sistema de escalados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Canales de notificación (Email, WhatsApp, Telegram)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Destinatarios de alertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Palabras clave para escalado automático', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h4><?php _e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <li><?php _e('Email: Configuración SMTP', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Push: Firebase Cloud Messaging', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('WhatsApp: API Business o Twilio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Telegram: Bot Token', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Seguridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-shield"></span>
                <p><?php _e('Las API keys se almacenan cifradas en la base de datos. Nunca las compartas ni las incluyas en backups públicos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Admin Herramientas
     */
    private function renderizar_seccion_admin_herramientas() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Herramientas del Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Herramientas de mantenimiento, diagnóstico y administración avanzada.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Export / Import', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Exporta e importa configuraciones completas de la plataforma:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><strong><?php _e('Configuración:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Opciones globales, ajustes de chat, diseño.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Módulos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Estado de activación y configuración de módulos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Plantillas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Datos de la plantilla activa.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><strong><?php _e('Layouts:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Configuraciones de landing pages.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Diagnóstico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('El Health Check verifica:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><?php _e('Versión de PHP y extensiones requeridas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Permisos de directorios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Conexión con APIs externas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Estado de tablas de base de datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Conflictos con otros plugins', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Uso de memoria y tiempo de ejecución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Registro de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('El Activity Log registra acciones importantes:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><?php _e('Cambios de configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Activación/desactivación de módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Errores de API', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Acciones de usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>// Registrar una acción personalizada
Flavor_Activity_Logger::log('mi_accion', [
    'usuario' => get_current_user_id(),
    'datos' => $mis_datos
]);</code></pre>
            </div>
        </div>
        <?php
    }

    // ════════════════════════════════════════════════════════════════════════════════
    // SECCIONES DE MÓDULOS - COMUNIDAD
    // ════════════════════════════════════════════════════════════════════════════════

    /**
     * Sección: Módulo Socios
     */
    private function renderizar_seccion_mod_socios() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Gestión integral de membresías, cuotas y directorio de miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('Registro y alta de miembros con validación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Tipos de miembro personalizables', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Sistema de cuotas con renovación automática', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Directorio público/privado de miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Carnets digitales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Historial de pagos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Integración con pasarelas de pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_socios</td><td><?php _e('Datos de miembros vinculados a usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_socios_cuotas</td><td><?php _e('Cuotas y pagos de miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_socios_tipos</td><td><?php _e('Tipos de membresía disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[flavor_socios_directorio] - Directorio de miembros
[flavor_socios_perfil] - Perfil del miembro actual
[flavor_socios_alta] - Formulario de registro
[flavor_socios_carnet] - Carnet digital del miembro</code></pre>
            </div>

            <h3><?php _e('API REST', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>/flavor/v1/socios</td><td>GET</td><td><?php _e('Listar socios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/socios/{id}</td><td>GET</td><td><?php _e('Obtener socio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/socios/perfil</td><td>GET/PUT</td><td><?php _e('Mi perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/socios/cuotas</td><td>GET</td><td><?php _e('Mis cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Hooks Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>// Acciones
do_action('flavor_socio_registrado', $socio_id, $datos);
do_action('flavor_socio_cuota_pagada', $socio_id, $cuota_id);
do_action('flavor_socio_cuota_vencida', $socio_id);

// Filtros
apply_filters('flavor_socios_tipos_disponibles', $tipos);
apply_filters('flavor_socios_campos_registro', $campos);</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Módulo Eventos
     */
    private function renderizar_seccion_mod_eventos() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Sistema completo de gestión de eventos con inscripciones, calendario y recordatorios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('CPT de eventos con categorías y etiquetas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Calendario visual interactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Sistema de inscripciones con límite de plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Lista de espera automática', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Recordatorios por email/push', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Eventos recurrentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Integración con Google Calendar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('CPT: flavor_evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Campos personalizados del evento:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><code>_evento_fecha_inicio</code> - <?php _e('Fecha y hora de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>_evento_fecha_fin</code> - <?php _e('Fecha y hora de fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>_evento_ubicacion</code> - <?php _e('Dirección o lugar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>_evento_plazas</code> - <?php _e('Límite de inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>_evento_precio</code> - <?php _e('Coste de inscripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>_evento_recurrencia</code> - <?php _e('Patrón de repetición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_eventos_inscripciones</td><td><?php _e('Inscripciones a eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_eventos_asistencia</td><td><?php _e('Control de asistencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[flavor_eventos_calendario] - Calendario de eventos
[flavor_eventos_proximos limit="5"] - Próximos eventos
[flavor_eventos_mis_inscripciones] - Mis inscripciones
[flavor_evento_detalle id="123"] - Detalle de un evento</code></pre>
            </div>

            <h3><?php _e('API REST', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>/flavor/v1/eventos</td><td>GET</td><td><?php _e('Listar eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/eventos/{id}</td><td>GET</td><td><?php _e('Detalle del evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/eventos/{id}/inscribir</td><td>POST</td><td><?php _e('Inscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/eventos/mis-inscripciones</td><td>GET</td><td><?php _e('Mis inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/eventos/calendario</td><td>GET</td><td><?php _e('Datos para calendario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Sección: Módulo Banco de Tiempo
     */
    private function renderizar_seccion_mod_banco_tiempo() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Plataforma de intercambio de servicios basada en tiempo como moneda.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('Publicación de ofertas y demandas de servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Sistema de créditos en horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Transacciones entre usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Balance personal de horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Historial de intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Categorías de servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Sistema de valoraciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_bt_ofertas</td><td><?php _e('Servicios ofrecidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_bt_demandas</td><td><?php _e('Servicios solicitados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_bt_transacciones</td><td><?php _e('Intercambios realizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_bt_balances</td><td><?php _e('Saldo de horas por usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[flavor_bt_ofertas] - Listado de ofertas
[flavor_bt_demandas] - Listado de demandas
[flavor_bt_mi_balance] - Mi balance de horas
[flavor_bt_historial] - Historial de transacciones
[flavor_bt_publicar_oferta] - Formulario nueva oferta
[flavor_bt_publicar_demanda] - Formulario nueva demanda</code></pre>
            </div>

            <h3><?php _e('API REST', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>/flavor/v1/banco-tiempo/ofertas</td><td>GET/POST</td><td><?php _e('Listar/crear ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/banco-tiempo/demandas</td><td>GET/POST</td><td><?php _e('Listar/crear demandas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/banco-tiempo/transaccion</td><td>POST</td><td><?php _e('Registrar intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/banco-tiempo/balance</td><td>GET</td><td><?php _e('Mi balance', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <p><?php _e('Los nuevos usuarios reciben un crédito inicial configurable (por defecto: 5 horas) para comenzar a intercambiar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Módulo Grupos de Consumo
     */
    private function renderizar_seccion_mod_grupos_consumo() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge parcial"><?php _e('Estado: En Desarrollo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Gestión de grupos de consumo responsable con productores, productos, ciclos de pedido y entregas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('Gestión de productores locales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Catálogo de productos por productor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Ciclos de pedido con fechas de apertura/cierre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Sistema de pedidos por usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Consolidado de pedidos para productores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Gestión de entregas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Notificaciones de ciclos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('CPTs del Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th>CPT</th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>gc_productor</td><td><?php _e('Productores locales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>gc_producto</td><td><?php _e('Productos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>gc_ciclo</td><td><?php _e('Ciclos de pedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_gc_pedidos</td><td><?php _e('Pedidos de usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_gc_entregas</td><td><?php _e('Control de entregas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_gc_consolidado</td><td><?php _e('Pedidos consolidados por productor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_gc_notificaciones</td><td><?php _e('Historial de notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[gc_ciclo_actual] - Ciclo de pedido activo
[gc_productos] - Catálogo de productos
[gc_mi_pedido] - Mi pedido actual</code></pre>
            </div>

            <h3><?php _e('Próximas Funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul class="flavor-docs-roadmap">
                <li><span class="badge pendiente"><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span> <?php _e('Sistema de suscripciones a cestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><span class="badge pendiente"><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span> <?php _e('Lista de compra personal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><span class="badge pendiente"><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span> <?php _e('Notificaciones WhatsApp/Telegram', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><span class="badge pendiente"><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span> <?php _e('Dashboard de usuario ampliado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Sección: Módulo Marketplace
     */
    private function renderizar_seccion_mod_marketplace() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Tablón de anuncios para compraventa, intercambio y donación entre usuarios de la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('Publicación de anuncios con imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Tipos: Venta, Compra, Intercambio, Donación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Categorías personalizables', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Búsqueda y filtros avanzados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Sistema de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Mensajería entre usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Moderación de anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('CPT: flavor_anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Campos personalizados:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><code>_anuncio_tipo</code> - venta|compra|intercambio|donacion</li>
                <li><code>_anuncio_precio</code> - <?php _e('Precio (0 para donación)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>_anuncio_estado</code> - disponible|reservado|vendido</li>
                <li><code>_anuncio_ubicacion</code> - <?php _e('Zona/barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>_anuncio_contacto</code> - <?php _e('Método de contacto preferido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_marketplace_favoritos</td><td><?php _e('Anuncios guardados por usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_marketplace_mensajes</td><td><?php _e('Mensajes entre compradores y vendedores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[flavor_marketplace] - Listado de anuncios
[flavor_marketplace_publicar] - Formulario de publicación
[flavor_marketplace_mis_anuncios] - Mis anuncios
[flavor_marketplace_favoritos] - Mis favoritos</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Módulo Ayuda Vecinal
     */
    private function renderizar_seccion_mod_ayuda_vecinal() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Plataforma de apoyo mutuo entre vecinos para tareas cotidianas y emergencias.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('Solicitudes de ayuda con urgencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Ofertas de ayuda voluntaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Categorías: Compras, Acompañamiento, Transporte, Mascotas, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Geolocalización por barrios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Sistema de voluntarios verificados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Notificaciones en tiempo real', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_ayuda_solicitudes</td><td><?php _e('Solicitudes de ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_ayuda_ofertas</td><td><?php _e('Ofertas de voluntarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_ayuda_voluntarios</td><td><?php _e('Registro de voluntarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_ayuda_matches</td><td><?php _e('Conexiones ayuda-voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[flavor_ayuda_solicitar] - Formulario de solicitud
[flavor_ayuda_ofrecer] - Ofrecer ayuda
[flavor_ayuda_listado] - Solicitudes activas
[flavor_ayuda_mis_ayudas] - Mi historial</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Módulo Reservas
     */
    private function renderizar_seccion_mod_reservas() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Sistema genérico de reservas para cualquier tipo de recurso: espacios, equipos, servicios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('Recursos configurables (salas, coches, herramientas, etc.)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Calendario de disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Franjas horarias personalizables', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Límites de reserva por usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Confirmación automática o manual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Recordatorios automáticos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Cancelaciones con política configurable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_reservas_recursos</td><td><?php _e('Recursos reservables', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_reservas</td><td><?php _e('Reservas realizadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_reservas_bloqueos</td><td><?php _e('Bloqueos de disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[flavor_reservas_calendario recurso="1"] - Calendario del recurso
[flavor_reservas_lista] - Lista de recursos disponibles
[flavor_reservas_mis_reservas] - Mis reservas
[flavor_reservas_formulario recurso="1"] - Formulario de reserva</code></pre>
            </div>

            <h3><?php _e('API REST', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>/flavor/v1/reservas/recursos</td><td>GET</td><td><?php _e('Listar recursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/reservas/disponibilidad/{id}</td><td>GET</td><td><?php _e('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/reservas</td><td>POST</td><td><?php _e('Crear reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>/flavor/v1/reservas/{id}/cancelar</td><td>POST</td><td><?php _e('Cancelar reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ════════════════════════════════════════════════════════════════════════════════
    // SECCIONES DE MÓDULOS - CONTENIDO
    // ════════════════════════════════════════════════════════════════════════════════

    /**
     * Sección: Módulo Cursos
     */
    private function renderizar_seccion_mod_cursos() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Plataforma de formación online con cursos, lecciones y seguimiento del progreso.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('Cursos con múltiples lecciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Contenido multimedia (vídeo, audio, PDF)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Progreso del alumno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Certificados de finalización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Cuestionarios y evaluaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Cursos gratuitos y de pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Instructores múltiples', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('CPTs del Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th>CPT</th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>flavor_curso</td><td><?php _e('Cursos principales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>flavor_leccion</td><td><?php _e('Lecciones de cada curso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_cursos_matriculas</td><td><?php _e('Matrículas de alumnos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_cursos_progreso</td><td><?php _e('Progreso por lección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_cursos_certificados</td><td><?php _e('Certificados generados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[flavor_cursos] - Catálogo de cursos
[flavor_curso id="123"] - Detalle del curso
[flavor_mis_cursos] - Mis cursos matriculados
[flavor_leccion id="456"] - Contenido de la lección</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Módulo Biblioteca
     */
    private function renderizar_seccion_mod_biblioteca() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Gestión de biblioteca digital con préstamos, reservas y catálogo de recursos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('Catálogo de libros, revistas, DVDs, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Sistema de préstamos con fechas límite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Reservas de ejemplares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Historial de préstamos por usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Multas por retraso (configurable)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Búsqueda avanzada por título, autor, ISBN', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Descarga de recursos digitales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('CPT: flavor_recurso_biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Campos personalizados:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <ul>
                <li><code>_recurso_tipo</code> - libro|revista|dvd|digital</li>
                <li><code>_recurso_autor</code> - <?php _e('Autor/es', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>_recurso_isbn</code> - ISBN</li>
                <li><code>_recurso_editorial</code> - <?php _e('Editorial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>_recurso_ejemplares</code> - <?php _e('Número de ejemplares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_biblioteca_prestamos</td><td><?php _e('Préstamos activos e históricos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_biblioteca_reservas</td><td><?php _e('Reservas pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[flavor_biblioteca] - Catálogo completo
[flavor_biblioteca_buscar] - Buscador de recursos
[flavor_biblioteca_mis_prestamos] - Mis préstamos activos
[flavor_biblioteca_historial] - Historial de préstamos</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Sección: Módulo Podcast
     */
    private function renderizar_seccion_mod_podcast() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Módulo: Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <p class="flavor-docs-intro"><?php _e('Publicación y gestión de contenido de audio con reproductor integrado y feed RSS.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Características', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php _e('Series/Programas con episodios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Reproductor de audio integrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Feed RSS compatible con plataformas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Transcripciones de episodios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Estadísticas de reproducciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Suscripciones a programas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php _e('Integración con Spotify, Apple Podcasts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <h3><?php _e('CPTs del Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th>CPT</th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>flavor_programa</td><td><?php _e('Series/Programas de podcast', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>flavor_episodio</td><td><?php _e('Episodios individuales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_podcast_stats</td><td><?php _e('Estadísticas de reproducción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <tr><td>wp_flavor_podcast_suscripciones</td><td><?php _e('Suscriptores a programas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>[flavor_podcast_programas] - Lista de programas
[flavor_podcast_episodios programa="123"] - Episodios de un programa
[flavor_podcast_player id="456"] - Reproductor de episodio
[flavor_podcast_ultimos limit="5"] - Últimos episodios</code></pre>
            </div>

            <h3><?php _e('Feed RSS', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('El feed RSS se genera automáticamente:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>https://tusitio.com/feed/podcast/nombre-programa</code></pre>
            </div>
        </div>
        <?php
    }

    // ════════════════════════════════════════════════════════════════════════════════
    // SECCIÓN BASE DE DATOS
    // ════════════════════════════════════════════════════════════════════════════════

    /**
     * Sección: Base de Datos
     */
    private function renderizar_seccion_base_datos() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Estructura de Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Flavor Platform utiliza tablas personalizadas además de los CPTs nativos de WordPress.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h3><?php _e('Tablas del Core', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Filas Aprox.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_chat_sessions</td><td><?php _e('Sesiones de chat IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td><td>Media</td></tr>
                    <tr><td>wp_flavor_chat_messages</td><td><?php _e('Mensajes de conversaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td><td>Alta</td></tr>
                    <tr><td>wp_flavor_chat_escalations</td><td><?php _e('Escalados a humanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td><td>Baja</td></tr>
                    <tr><td>wp_flavor_activity_log</td><td><?php _e('Registro de actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td><td>Alta</td></tr>
                    <tr><td>wp_flavor_notifications</td><td><?php _e('Notificaciones enviadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td><td>Media</td></tr>
                    <tr><td>wp_flavor_user_preferences</td><td><?php _e('Preferencias de usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td><td>Baja</td></tr>
                </tbody>
            </table>

            <h3><?php _e('Tablas por Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Cada módulo puede crear sus propias tablas durante la instalación:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <h4><?php _e('Patrón de Nomenclatura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>wp_flavor_{modulo}_{entidad}

Ejemplos:
- wp_flavor_socios_cuotas
- wp_flavor_eventos_inscripciones
- wp_flavor_bt_transacciones
- wp_flavor_gc_pedidos</code></pre>
            </div>

            <h3><?php _e('Crear Tablas Personalizadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Los módulos definen sus tablas en el archivo install.php:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>// modules/{nombre}/install.php
function flavor_{nombre}_install_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $tabla = $wpdb->prefix . 'flavor_{nombre}_datos';

    $sql = "CREATE TABLE IF NOT EXISTS {$tabla} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        datos longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}</code></pre>
            </div>

            <h3><?php _e('Consultas Seguras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-shield"></span>
                <p><?php _e('Siempre usa $wpdb->prepare() para consultas con parámetros externos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <pre><code>// ✅ Correcto
$resultado = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$tabla} WHERE id = %d AND estado = %s",
    $id,
    'activo'
));

// ❌ Incorrecto - vulnerable a SQL injection
$resultado = $wpdb->get_row(
    "SELECT * FROM {$tabla} WHERE id = {$id}"
);</code></pre>
            </div>

            <h3><?php _e('Índices Recomendados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><code>usuario_id</code> - <?php _e('En todas las tablas con relación a usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>created_at</code> - <?php _e('Para consultas ordenadas por fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>estado</code> - <?php _e('Para filtrar por estado activo/inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><code>post_id</code> - <?php _e('Si hay relación con CPTs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Obtiene los estilos CSS para la documentación
     *
     * @return string
     */
    private function obtener_estilos_documentacion() {
        return '
        .flavor-docs-wrap {
            max-width: 1400px;
            margin: 20px auto;
        }

        .flavor-docs-header {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .flavor-docs-header h1 {
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
        }

        .flavor-docs-header h1 .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
        }

        .flavor-docs-version {
            opacity: 0.8;
            margin: 5px 0 0;
        }

        .flavor-docs-search {
            position: relative;
        }

        .flavor-docs-search-input {
            padding: 10px 40px 10px 16px;
            border: none;
            border-radius: 8px;
            width: 300px;
            font-size: 14px;
        }

        .flavor-docs-search .dashicons {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .flavor-docs-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
        }

        .flavor-docs-nav {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            position: sticky;
            top: 32px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }

        .flavor-docs-nav-separator {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            padding: 16px 12px 8px;
            letter-spacing: 0.5px;
        }

        .flavor-docs-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: #334155;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.15s ease;
        }

        .flavor-docs-nav-item:hover {
            background: #f1f5f9;
            color: #1e40af;
        }

        .flavor-docs-nav-item.activo {
            background: #1e40af;
            color: white;
        }

        .flavor-docs-nav-item .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        .flavor-docs-badge {
            background: #fbbf24;
            color: #78350f;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            margin-left: auto;
        }

        .flavor-docs-content {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 32px;
        }

        .flavor-docs-section h2 {
            font-size: 28px;
            margin: 0 0 24px;
            color: #1e293b;
        }

        .flavor-docs-section h3 {
            font-size: 20px;
            margin: 32px 0 16px;
            color: #334155;
        }

        .flavor-docs-section h4 {
            font-size: 16px;
            margin: 24px 0 12px;
            color: #475569;
        }

        .flavor-docs-intro {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .flavor-docs-intro-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .flavor-docs-intro-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
        }

        .flavor-docs-intro-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .flavor-docs-intro-icon .dashicons {
            font-size: 28px;
            width: 28px;
            height: 28px;
        }

        .flavor-docs-intro-card h3 {
            margin: 0 0 12px;
            font-size: 18px;
        }

        .flavor-docs-intro-card ul,
        .flavor-docs-intro-card ol {
            margin: 0;
            padding-left: 20px;
        }

        .flavor-docs-intro-card li {
            margin-bottom: 6px;
            color: #475569;
        }

        .flavor-docs-quick-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }

        .flavor-docs-quick-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: #334155;
            transition: all 0.15s ease;
        }

        .flavor-docs-quick-link:hover {
            background: #1e40af;
            color: white;
            border-color: #1e40af;
        }

        .flavor-docs-quick-link .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }

        .flavor-docs-steps {
            margin: 24px 0;
        }

        .flavor-docs-step {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .flavor-docs-step:last-child {
            border-bottom: none;
        }

        .flavor-docs-step-number {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .flavor-docs-step-content h3 {
            margin: 0 0 12px;
        }

        .flavor-docs-step-content p {
            color: #64748b;
            margin: 0 0 12px;
        }

        .flavor-docs-tip,
        .flavor-docs-warning {
            display: flex;
            gap: 16px;
            padding: 20px;
            border-radius: 12px;
            margin: 24px 0;
        }

        .flavor-docs-tip {
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
        }

        .flavor-docs-tip .dashicons {
            color: #059669;
        }

        .flavor-docs-warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
        }

        .flavor-docs-warning .dashicons {
            color: #d97706;
        }

        .flavor-docs-tip strong,
        .flavor-docs-warning strong {
            display: block;
            margin-bottom: 4px;
        }

        .flavor-docs-tip p,
        .flavor-docs-warning p {
            margin: 0;
            color: #334155;
        }

        .flavor-docs-table-container {
            overflow-x: auto;
            margin: 16px 0;
        }

        .flavor-docs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .flavor-docs-table th,
        .flavor-docs-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .flavor-docs-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #334155;
        }

        .flavor-docs-table code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }

        .flavor-docs-code-block {
            position: relative;
            background: #1e293b;
            border-radius: 12px;
            margin: 16px 0;
            overflow: hidden;
        }

        .flavor-docs-code-block pre {
            margin: 0;
            padding: 20px;
            overflow-x: auto;
        }

        .flavor-docs-code-block code {
            color: #e2e8f0;
            font-family: "SF Mono", Monaco, Consolas, monospace;
            font-size: 13px;
            line-height: 1.6;
        }

        .flavor-docs-code-copy {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #334155;
            color: #e2e8f0;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .flavor-docs-code-copy:hover {
            background: #475569;
        }

        .flavor-docs-modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin: 16px 0;
        }

        .flavor-docs-module-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }

        .flavor-docs-module-card .dashicons {
            font-size: 28px;
            width: 28px;
            height: 28px;
            margin-bottom: 12px;
        }

        .flavor-docs-module-card h4 {
            margin: 0 0 8px;
            font-size: 16px;
        }

        .flavor-docs-module-card p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .flavor-docs-tokens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .flavor-docs-token-group {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }

        .flavor-docs-token-group h4 {
            margin: 0 0 12px;
            font-size: 14px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .flavor-docs-token-group ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .flavor-docs-token-group li {
            font-size: 13px;
            margin-bottom: 8px;
            color: #334155;
        }

        .flavor-docs-token-group code {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        .flavor-docs-faq-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .flavor-docs-faq-item summary {
            padding: 16px 20px;
            cursor: pointer;
            font-weight: 500;
            color: #334155;
            background: #f8fafc;
            list-style: none;
        }

        .flavor-docs-faq-item summary::-webkit-details-marker {
            display: none;
        }

        .flavor-docs-faq-item summary::before {
            content: "+";
            margin-right: 12px;
            font-weight: 700;
            color: #1e40af;
        }

        .flavor-docs-faq-item[open] summary::before {
            content: "−";
        }

        .flavor-docs-faq-answer {
            padding: 20px;
            color: #64748b;
            line-height: 1.6;
        }

        .flavor-docs-support-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin: 24px 0;
        }

        .flavor-docs-support-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
        }

        .flavor-docs-support-card .dashicons {
            font-size: 40px;
            width: 40px;
            height: 40px;
            margin-bottom: 16px;
        }

        .flavor-docs-support-card h3 {
            margin: 0 0 12px;
        }

        .flavor-docs-support-card p {
            color: #64748b;
            margin: 0 0 16px;
        }

        .flavor-docs-system-info {
            margin-top: 32px;
            padding-top: 32px;
            border-top: 1px solid #e2e8f0;
        }

        .flavor-docs-tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .flavor-docs-tip-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
        }

        .flavor-docs-tip-card .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            margin-bottom: 12px;
        }

        .flavor-docs-tip-card h4 {
            margin: 0 0 12px;
        }

        .flavor-docs-tip-card ul {
            margin: 0;
            padding-left: 20px;
        }

        .flavor-docs-tip-card li {
            color: #64748b;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .flavor-docs-changelog-version {
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid #e2e8f0;
        }

        .flavor-docs-changelog-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .version-badge {
            background: #1e40af;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
        }

        .version-date {
            color: #64748b;
        }

        .version-tag {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .version-tag.nuevo {
            background: #dcfce7;
            color: #166534;
        }

        .flavor-docs-changelog ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .flavor-docs-changelog li {
            padding: 8px 0;
            color: #334155;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .flavor-docs-changelog .badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .flavor-docs-changelog .badge.nuevo {
            background: #dbeafe;
            color: #1e40af;
        }

        .flavor-docs-changelog .badge.mejora {
            background: #dcfce7;
            color: #166534;
        }

        .flavor-docs-changelog .badge.fix {
            background: #fef3c7;
            color: #92400e;
        }

        /* Grid y Cards para módulos */
        .flavor-docs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }

        .flavor-docs-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .flavor-docs-card .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            margin-bottom: 12px;
        }

        .flavor-docs-card h4 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #1e293b;
        }

        .flavor-docs-card p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Status badges para módulos */
        .flavor-docs-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .flavor-docs-status-badge.completo {
            background: #dcfce7;
            color: #166534;
        }

        .flavor-docs-status-badge.parcial {
            background: #fef3c7;
            color: #92400e;
        }

        .flavor-docs-status-badge.basico {
            background: #e2e8f0;
            color: #475569;
        }

        /* Warning y Tip boxes */
        .flavor-docs-warning {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }

        .flavor-docs-warning .dashicons {
            color: #dc2626;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .flavor-docs-warning p {
            margin: 0;
            color: #991b1b;
            font-size: 14px;
        }

        /* Roadmap list */
        .flavor-docs-roadmap {
            list-style: none;
            padding: 0;
            margin: 16px 0;
        }

        .flavor-docs-roadmap li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .flavor-docs-roadmap li:last-child {
            border-bottom: none;
        }

        .flavor-docs-roadmap .badge.pendiente {
            background: #e0e7ff;
            color: #3730a3;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        @media (max-width: 1024px) {
            .flavor-docs-container {
                grid-template-columns: 1fr;
            }

            .flavor-docs-nav {
                position: static;
                max-height: none;
            }
        }

        @media (max-width: 600px) {
            .flavor-docs-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .flavor-docs-search-input {
                width: 100%;
            }
        }
        ';
    }
}

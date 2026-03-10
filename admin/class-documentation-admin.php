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
        if (strpos($sufijo_hook, 'flavor-documentation') === false) {
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
            wp_die(__('No tienes permisos para acceder a esta página.', 'flavor-chat-ia'));
        }

        $seccion_activa = isset($_GET['seccion']) ? sanitize_key($_GET['seccion']) : 'inicio';
        $secciones = $this->obtener_secciones();
        ?>
        <div class="wrap flavor-docs-wrap">
            <div class="flavor-docs-header">
                <div class="flavor-docs-header-content">
                    <h1>
                        <span class="dashicons dashicons-book-alt"></span>
                        <?php _e('Documentación de Flavor Platform', 'flavor-chat-ia'); ?>
                    </h1>
                    <p class="flavor-docs-version">
                        <?php printf(__('Versión %s', 'flavor-chat-ia'), FLAVOR_CHAT_IA_VERSION); ?>
                    </p>
                </div>
                <div class="flavor-docs-search">
                    <input type="text"
                           id="flavor-docs-search"
                           placeholder="<?php esc_attr_e('Buscar en la documentación...', 'flavor-chat-ia'); ?>"
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
                'titulo' => __('Inicio', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-home',
            ],
            'guia-rapida' => [
                'titulo' => __('Guía Rápida', 'flavor-chat-ia'),
                'icono' => 'dashicons-welcome-learn-more',
                'badge' => __('Nuevo', 'flavor-chat-ia'),
            ],
            // ═══ ADMINISTRACIÓN ═══
            'sep-admin' => [
                'titulo' => __('Administración', 'flavor-chat-ia'),
                'separador' => true,
            ],
            'admin-dashboard' => [
                'titulo' => __('Dashboard', 'flavor-chat-ia'),
                'icono' => 'dashicons-dashboard',
            ],
            'admin-compositor' => [
                'titulo' => __('Compositor', 'flavor-chat-ia'),
                'icono' => 'dashicons-layout',
            ],
            'admin-diseno' => [
                'titulo' => __('Diseño', 'flavor-chat-ia'),
                'icono' => 'dashicons-art',
            ],
            'admin-paginas' => [
                'titulo' => __('Páginas', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-page',
            ],
            'admin-config' => [
                'titulo' => __('Configuración', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-generic',
            ],
            'admin-herramientas' => [
                'titulo' => __('Herramientas', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-tools',
            ],
            // ═══ MÓDULOS COMUNIDAD ═══
            'sep-modulos-comunidad' => [
                'titulo' => __('Módulos Comunidad', 'flavor-chat-ia'),
                'separador' => true,
            ],
            'mod-socios' => [
                'titulo' => __('Socios', 'flavor-chat-ia'),
                'icono' => 'dashicons-groups',
            ],
            'mod-eventos' => [
                'titulo' => __('Eventos', 'flavor-chat-ia'),
                'icono' => 'dashicons-calendar',
            ],
            'mod-banco-tiempo' => [
                'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'icono' => 'dashicons-clock',
            ],
            'mod-grupos-consumo' => [
                'titulo' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'icono' => 'dashicons-carrot',
            ],
            'mod-marketplace' => [
                'titulo' => __('Marketplace', 'flavor-chat-ia'),
                'icono' => 'dashicons-megaphone',
            ],
            'mod-ayuda-vecinal' => [
                'titulo' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'icono' => 'dashicons-heart',
            ],
            'mod-reservas' => [
                'titulo' => __('Reservas', 'flavor-chat-ia'),
                'icono' => 'dashicons-calendar-alt',
            ],
            // ═══ MÓDULOS CONTENIDO ═══
            'sep-modulos-contenido' => [
                'titulo' => __('Módulos Contenido', 'flavor-chat-ia'),
                'separador' => true,
            ],
            'mod-cursos' => [
                'titulo' => __('Cursos', 'flavor-chat-ia'),
                'icono' => 'dashicons-welcome-learn-more',
            ],
            'mod-biblioteca' => [
                'titulo' => __('Biblioteca', 'flavor-chat-ia'),
                'icono' => 'dashicons-book',
            ],
            'mod-podcast' => [
                'titulo' => __('Podcast', 'flavor-chat-ia'),
                'icono' => 'dashicons-microphone',
            ],
            // ═══ CHAT IA ═══
            'sep-chat' => [
                'titulo' => __('Chat IA', 'flavor-chat-ia'),
                'separador' => true,
            ],
            'chat-config' => [
                'titulo' => __('Configuración', 'flavor-chat-ia'),
                'icono' => 'dashicons-format-chat',
            ],
            'motores-ia' => [
                'titulo' => __('Motores de IA', 'flavor-chat-ia'),
                'icono' => 'dashicons-superhero',
            ],
            'escalados' => [
                'titulo' => __('Escalados', 'flavor-chat-ia'),
                'icono' => 'dashicons-sos',
            ],
            // ═══ DESARROLLO ═══
            'sep-dev' => [
                'titulo' => __('Desarrollo', 'flavor-chat-ia'),
                'separador' => true,
            ],
            'api-rest' => [
                'titulo' => __('API REST', 'flavor-chat-ia'),
                'icono' => 'dashicons-rest-api',
            ],
            'shortcodes' => [
                'titulo' => __('Shortcodes', 'flavor-chat-ia'),
                'icono' => 'dashicons-editor-code',
            ],
            'base-datos' => [
                'titulo' => __('Base de Datos', 'flavor-chat-ia'),
                'icono' => 'dashicons-database',
            ],
            'hooks' => [
                'titulo' => __('Hooks & Filtros', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-tools',
            ],
            // ═══ EXTRAS ═══
            'sep-extra' => [
                'titulo' => __('Extras', 'flavor-chat-ia'),
                'separador' => true,
            ],
            'addons' => [
                'titulo' => __('Addons', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-plugins',
            ],
            'tips' => [
                'titulo' => __('Tips', 'flavor-chat-ia'),
                'icono' => 'dashicons-lightbulb',
            ],
            'faq' => [
                'titulo' => __('FAQ', 'flavor-chat-ia'),
                'icono' => 'dashicons-editor-help',
            ],
            'soporte' => [
                'titulo' => __('Soporte', 'flavor-chat-ia'),
                'icono' => 'dashicons-email',
            ],
            'changelog' => [
                'titulo' => __('Changelog', 'flavor-chat-ia'),
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
            <h2><?php _e('Bienvenido a Flavor Platform', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-docs-intro-cards">
                <div class="flavor-docs-intro-card">
                    <div class="flavor-docs-intro-icon" style="background: #dbeafe;">
                        <span class="dashicons dashicons-superhero-alt" style="color: #2563eb;"></span>
                    </div>
                    <h3><?php _e('¿Qué es Flavor Platform?', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('Flavor Platform es una solución integral para WordPress que combina un asistente de IA conversacional, sistema modular de comunidades, landing pages dinámicas y herramientas de gestión avanzadas.', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-docs-intro-card">
                    <div class="flavor-docs-intro-icon" style="background: #dcfce7;">
                        <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span>
                    </div>
                    <h3><?php _e('Características Principales', 'flavor-chat-ia'); ?></h3>
                    <ul>
                        <li><?php _e('Chat IA multi-proveedor (Claude, OpenAI, DeepSeek, Mistral)', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('15+ módulos de comunidad preconstruidos', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Sistema de plantillas con un clic', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('API REST completa', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Compatible con apps móviles (PWA)', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-intro-card">
                    <div class="flavor-docs-intro-icon" style="background: #fef3c7;">
                        <span class="dashicons dashicons-performance" style="color: #d97706;"></span>
                    </div>
                    <h3><?php _e('Empezar Rápido', 'flavor-chat-ia'); ?></h3>
                    <ol>
                        <li><?php _e('Ve a Compositor & Módulos', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Selecciona una plantilla', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Activa con datos de demo', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('¡Listo para usar!', 'flavor-chat-ia'); ?></li>
                    </ol>
                    <a href="<?php echo admin_url('admin.php?page=flavor-module-dashboards'); ?>" class="button button-primary">
                        <?php _e('Ir a Módulos', 'flavor-chat-ia'); ?> →
                    </a>
                </div>
            </div>

            <div class="flavor-docs-quick-links">
                <h3><?php _e('Enlaces Rápidos', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-docs-quick-links-grid">
                    <a href="<?php echo admin_url('admin.php?page=flavor-dashboard'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php _e('Dashboard', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-module-dashboards'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-screenoptions"></span>
                        <?php _e('Módulos', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-chat-config'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('Configuración', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-design-settings'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-art"></span>
                        <?php _e('Diseño', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-addons'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php _e('Addons', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-health-check'); ?>" class="flavor-docs-quick-link">
                        <span class="dashicons dashicons-heart"></span>
                        <?php _e('Diagnóstico', 'flavor-chat-ia'); ?>
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
            <h2><?php _e('Guía de Inicio Rápido', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-docs-steps">
                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">1</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('Selecciona una Plantilla', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('Ve a <strong>Compositor & Módulos</strong> y elige una plantilla que se adapte a tu proyecto:', 'flavor-chat-ia'); ?></p>
                        <ul>
                            <li><strong><?php _e('Grupo de Consumo', 'flavor-chat-ia'); ?></strong> - <?php _e('Cooperativas alimentarias y pedidos colectivos', 'flavor-chat-ia'); ?></li>
                            <li><strong><?php _e('Banco de Tiempo', 'flavor-chat-ia'); ?></strong> - <?php _e('Intercambio de servicios por horas', 'flavor-chat-ia'); ?></li>
                            <li><strong><?php _e('Comunidad', 'flavor-chat-ia'); ?></strong> - <?php _e('Asociaciones, clubs y entidades', 'flavor-chat-ia'); ?></li>
                            <li><strong><?php _e('Barrio', 'flavor-chat-ia'); ?></strong> - <?php _e('Comunidades vecinales y locales', 'flavor-chat-ia'); ?></li>
                            <li><strong><?php _e('Tienda', 'flavor-chat-ia'); ?></strong> - <?php _e('E-commerce con WooCommerce', 'flavor-chat-ia'); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">2</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('Activa la Plantilla', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('Al hacer clic en una plantilla, verás un preview con todo lo que se instalará:', 'flavor-chat-ia'); ?></p>
                        <ul>
                            <li><?php _e('Módulos que se activarán', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Páginas que se crearán', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Secciones de la landing page', 'flavor-chat-ia'); ?></li>
                        </ul>
                        <p><?php _e('Marca la opción <strong>"Cargar datos de demostración"</strong> para tener contenido de ejemplo.', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">3</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('Configura el Chat IA', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('Para activar el asistente de IA, ve a <strong>Configuración</strong> y:', 'flavor-chat-ia'); ?></p>
                        <ol>
                            <li><?php _e('Selecciona un proveedor de IA (Claude recomendado)', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Introduce tu API key', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Personaliza el nombre y personalidad del asistente', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Activa el widget flotante', 'flavor-chat-ia'); ?></li>
                        </ol>
                    </div>
                </div>

                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">4</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('Personaliza el Diseño', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('En <strong>Diseño y Apariencia</strong> puedes:', 'flavor-chat-ia'); ?></p>
                        <ul>
                            <li><?php _e('Cambiar colores principales y secundarios', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Seleccionar tipografías', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Ajustar espaciados y bordes', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Configurar el modo oscuro', 'flavor-chat-ia'); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="flavor-docs-step">
                    <div class="flavor-docs-step-number">5</div>
                    <div class="flavor-docs-step-content">
                        <h3><?php _e('¡Listo!', 'flavor-chat-ia'); ?></h3>
                        <p><?php _e('Tu aplicación está configurada. Ahora puedes:', 'flavor-chat-ia'); ?></p>
                        <ul>
                            <li><?php _e('Visitar las páginas creadas en el frontend', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Probar el chat de IA', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Ajustar módulos según tus necesidades', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Añadir contenido real', 'flavor-chat-ia'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <div>
                    <strong><?php _e('Consejo Pro', 'flavor-chat-ia'); ?></strong>
                    <p><?php _e('Usa el Dashboard para monitorizar el uso del chat, ver estadísticas y gestionar escalados en tiempo real.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Compositor & Plantillas', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('El Compositor es el corazón de Flavor Platform. Te permite crear aplicaciones completas con un solo clic seleccionando plantillas prediseñadas.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('¿Qué es una Plantilla?', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Una plantilla es un conjunto preconfigurado que incluye:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><strong><?php _e('Módulos', 'flavor-chat-ia'); ?></strong> - <?php _e('Funcionalidades específicas (eventos, marketplace, socios...)', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Páginas', 'flavor-chat-ia'); ?></strong> - <?php _e('Páginas de WordPress con shortcodes preconfigurados', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Landing Page', 'flavor-chat-ia'); ?></strong> - <?php _e('Secciones visuales (hero, features, CTA...)', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Configuración', 'flavor-chat-ia'); ?></strong> - <?php _e('Ajustes óptimos para el caso de uso', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Datos Demo', 'flavor-chat-ia'); ?></strong> - <?php _e('Contenido de ejemplo para probar (opcional)', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Plantillas Disponibles', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Plantilla', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Caso de Uso', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Módulos Principales', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Grupo de Consumo', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('Cooperativas alimentarias, compras colectivas', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Grupos Consumo, Productores, Ciclos de pedido', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Banco de Tiempo', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('Intercambio de servicios, economía colaborativa', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Banco Tiempo, Servicios, Saldos', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Comunidad', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('Asociaciones, clubs, entidades sin ánimo de lucro', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Socios, Eventos, Cuotas, Comunicación', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Barrio', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('Comunidades vecinales, gestión de barrio', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Ayuda Vecinal, Huertos, Espacios Comunes', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Tienda', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('E-commerce, catálogos de productos', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('WooCommerce, Marketplace, Sellos Calidad', 'flavor-chat-ia'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Proceso de Activación', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Cuando activas una plantilla, el sistema ejecuta automáticamente:', 'flavor-chat-ia'); ?></p>
            <ol>
                <li><strong><?php _e('Instalación de Módulos', 'flavor-chat-ia'); ?></strong> - <?php _e('Activa los módulos requeridos y opcionales seleccionados', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Creación de Tablas', 'flavor-chat-ia'); ?></strong> - <?php _e('Crea las tablas de base de datos necesarias', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Generación de Páginas', 'flavor-chat-ia'); ?></strong> - <?php _e('Crea páginas de WordPress con los shortcodes', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Configuración de Landing', 'flavor-chat-ia'); ?></strong> - <?php _e('Configura las secciones de la página principal', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Aplicación de Configuración', 'flavor-chat-ia'); ?></strong> - <?php _e('Establece los ajustes recomendados', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Carga de Demo', 'flavor-chat-ia'); ?></strong> - <?php _e('(Opcional) Inserta datos de ejemplo', 'flavor-chat-ia'); ?></li>
            </ol>

            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong><?php _e('Importante', 'flavor-chat-ia'); ?></strong>
                    <p><?php _e('Cambiar de plantilla no elimina los datos existentes, pero puede desactivar módulos que estabas usando. Recomendamos hacer una copia de seguridad antes de cambiar.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Módulos', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Los módulos son las unidades funcionales de Flavor Platform. Cada módulo añade características específicas que puedes activar o desactivar según tus necesidades.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Módulos de Comunidad', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-modules-grid">
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-groups" style="color: #f43f5e;"></span>
                    <h4><?php _e('Socios', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Gestión de membresías, cuotas periódicas y carnets digitales.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-calendar" style="color: #3b82f6;"></span>
                    <h4><?php _e('Eventos', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Calendario de eventos, inscripciones y gestión de asistencia.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-clock" style="color: #8b5cf6;"></span>
                    <h4><?php _e('Banco de Tiempo', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Intercambio de servicios por horas entre miembros.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-carrot" style="color: #22c55e;"></span>
                    <h4><?php _e('Grupos de Consumo', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Pedidos colectivos, productores locales y ciclos de compra.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-heart" style="color: #ef4444;"></span>
                    <h4><?php _e('Ayuda Vecinal', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Red de apoyo mutuo entre vecinos del barrio.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-admin-multisite" style="color: #06b6d4;"></span>
                    <h4><?php _e('Espacios Comunes', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Reserva de salas, locales y recursos compartidos.', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <h3><?php _e('Módulos de Comercio', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-modules-grid">
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-cart" style="color: #7c3aed;"></span>
                    <h4><?php _e('WooCommerce', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Integración completa con WooCommerce para tiendas online.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-megaphone" style="color: #f59e0b;"></span>
                    <h4><?php _e('Marketplace', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Tablón de anuncios para compra, venta e intercambio.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-awards" style="color: #10b981;"></span>
                    <h4><?php _e('Sellos de Calidad', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Certificaciones y etiquetas para productos y servicios.', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <h3><?php _e('Módulos de Contenido', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-modules-grid">
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-book" style="color: #6366f1;"></span>
                    <h4><?php _e('Biblioteca', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Catálogo y préstamo de libros comunitarios.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-microphone" style="color: #14b8a6;"></span>
                    <h4><?php _e('Podcast', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Publicación y gestión de contenido de audio.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-module-card">
                    <span class="dashicons dashicons-welcome-learn-more" style="color: #a855f7;"></span>
                    <h4><?php _e('Cursos', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Formación y talleres educativos.', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <h3><?php _e('Activar/Desactivar Módulos', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Para gestionar módulos:', 'flavor-chat-ia'); ?></p>
            <ol>
                <li><?php _e('Ve a <strong>Compositor & Módulos</strong>', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Haz clic en la pestaña <strong>Módulos</strong>', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Usa los toggles para activar o desactivar', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Los módulos requeridos por la plantilla activa no se pueden desactivar', 'flavor-chat-ia'); ?></li>
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
            <h2><?php _e('Landing Pages', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Las landing pages son páginas frontend preconfiguradas para cada tipo de aplicación. Se generan automáticamente con secciones modulares.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Secciones Disponibles', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Sección', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Variantes', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Hero</strong></td>
                            <td><?php _e('Cabecera principal con título, subtítulo y CTA', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Centrado, Con imagen, Video, Gradiente', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Features</strong></td>
                            <td><?php _e('Grid de características con iconos', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('3 columnas, 4 columnas, Lista', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>CTA</strong></td>
                            <td><?php _e('Llamada a la acción destacada', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Simple, Con imagen, Dual', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Grid</strong></td>
                            <td><?php _e('Listado de contenidos en rejilla', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Cards, Masonry, Carousel', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Stats</strong></td>
                            <td><?php _e('Estadísticas numéricas destacadas', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Horizontal, Con iconos, Animadas', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Testimonios</strong></td>
                            <td><?php _e('Opiniones de usuarios', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Slider, Grid, Quote', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>FAQ</strong></td>
                            <td><?php _e('Preguntas frecuentes en acordeón', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Acordeón, Dos columnas', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Contacto</strong></td>
                            <td><?php _e('Formulario de contacto', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Simple, Con mapa, Split', 'flavor-chat-ia'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Shortcode Principal', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_landing id="grupo_consumo"]</code></pre>
            </div>

            <h3><?php _e('Páginas de Demo', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('En el Compositor encontrarás la sección <strong>Páginas de Demostración</strong> donde puedes:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><?php _e('Crear todas las páginas de landing con un clic', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Ver el estado de cada página (creada, demo, existe)', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Eliminar las páginas de demo cuando ya no las necesites', 'flavor-chat-ia'); ?></li>
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
            <h2><?php _e('Diseño & Temas', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Personaliza completamente la apariencia de tu aplicación usando el sistema de design tokens.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Design Tokens', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Flavor Platform usa un sistema de tokens CSS que te permite cambiar toda la apariencia modificando unas pocas variables:', 'flavor-chat-ia'); ?></p>

            <div class="flavor-docs-tokens-grid">
                <div class="flavor-docs-token-group">
                    <h4><?php _e('Colores', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><code>--flavor-primary</code> - <?php _e('Color principal', 'flavor-chat-ia'); ?></li>
                        <li><code>--flavor-secondary</code> - <?php _e('Color secundario', 'flavor-chat-ia'); ?></li>
                        <li><code>--flavor-accent</code> - <?php _e('Color de acento', 'flavor-chat-ia'); ?></li>
                        <li><code>--flavor-success</code> - <?php _e('Éxito/Confirmación', 'flavor-chat-ia'); ?></li>
                        <li><code>--flavor-warning</code> - <?php _e('Advertencia', 'flavor-chat-ia'); ?></li>
                        <li><code>--flavor-error</code> - <?php _e('Error', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>
                <div class="flavor-docs-token-group">
                    <h4><?php _e('Tipografía', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><code>--flavor-font-family</code> - <?php _e('Fuente principal', 'flavor-chat-ia'); ?></li>
                        <li><code>--flavor-font-size-base</code> - <?php _e('Tamaño base', 'flavor-chat-ia'); ?></li>
                        <li><code>--flavor-line-height</code> - <?php _e('Altura de línea', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>
                <div class="flavor-docs-token-group">
                    <h4><?php _e('Espaciado', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><code>--flavor-space-xs</code> - 4px</li>
                        <li><code>--flavor-space-sm</code> - 8px</li>
                        <li><code>--flavor-space-md</code> - 16px</li>
                        <li><code>--flavor-space-lg</code> - 24px</li>
                        <li><code>--flavor-space-xl</code> - 32px</li>
                    </ul>
                </div>
                <div class="flavor-docs-token-group">
                    <h4><?php _e('Bordes', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><code>--flavor-radius-sm</code> - 4px</li>
                        <li><code>--flavor-radius-md</code> - 8px</li>
                        <li><code>--flavor-radius-lg</code> - 12px</li>
                        <li><code>--flavor-radius-full</code> - 9999px</li>
                    </ul>
                </div>
            </div>

            <h3><?php _e('Modo Oscuro', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('El modo oscuro se activa automáticamente según las preferencias del sistema del usuario, o puedes forzarlo en la configuración.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php
    }

    /**
     * Sección: Configuración del Chat
     */
    private function renderizar_seccion_chat_config() {
        ?>
        <div class="flavor-docs-section">
            <h2><?php _e('Configuración del Chat IA', 'flavor-chat-ia'); ?></h2>

            <h3><?php _e('Configuración Básica', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Opción', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Recomendación', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Nombre del Asistente', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('Nombre que verán los usuarios', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Usa un nombre amigable relacionado con tu marca', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Rol/Personalidad', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('Instrucciones de sistema para el comportamiento', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Sé específico sobre el contexto de tu negocio', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Tono', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('Estilo de comunicación', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Friendly para B2C, Professional para B2B', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Widget Flotante', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('Mostrar el chat en todas las páginas', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Actívalo para mejor accesibilidad', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Posición', 'flavor-chat-ia'); ?></strong></td>
                            <td><?php _e('Esquina donde aparece el widget', 'flavor-chat-ia'); ?></td>
                            <td><?php _e('Bottom-right es el estándar', 'flavor-chat-ia'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Base de Conocimiento', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Añade información específica que el asistente debe conocer:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><?php _e('Información de tu empresa/organización', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Productos y servicios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Horarios y contacto', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Preguntas frecuentes', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Políticas y condiciones', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('FAQs Precargadas', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Define preguntas frecuentes con respuestas predefinidas para respuestas instantáneas sin consumir tokens de IA.', 'flavor-chat-ia'); ?></p>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <div>
                    <strong><?php _e('Ahorra Costos', 'flavor-chat-ia'); ?></strong>
                    <p><?php _e('Las FAQs con matching exacto responden sin llamar a la IA. Ideal para preguntas repetitivas como horarios, precios o direcciones.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Motores de IA', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Flavor Platform es multi-proveedor: puedes elegir entre diferentes servicios de IA según tus necesidades y presupuesto.', 'flavor-chat-ia'); ?></p>

            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Proveedor', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Modelos', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Mejor Para', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Costo Aprox.', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Claude (Anthropic)</strong></td>
                            <td>Claude 3.5 Sonnet, Claude 3 Opus</td>
                            <td><?php _e('Calidad máxima, contexto largo', 'flavor-chat-ia'); ?></td>
                            <td>$3-15 / 1M tokens</td>
                        </tr>
                        <tr>
                            <td><strong>OpenAI</strong></td>
                            <td>GPT-4o, GPT-4o-mini</td>
                            <td><?php _e('Balance calidad/precio', 'flavor-chat-ia'); ?></td>
                            <td>$0.15-5 / 1M tokens</td>
                        </tr>
                        <tr>
                            <td><strong>DeepSeek</strong></td>
                            <td>DeepSeek Chat, DeepSeek Coder</td>
                            <td><?php _e('Máximo ahorro', 'flavor-chat-ia'); ?></td>
                            <td>$0.14 / 1M tokens</td>
                        </tr>
                        <tr>
                            <td><strong>Mistral</strong></td>
                            <td>Mistral Small, Mistral Large</td>
                            <td><?php _e('Europa, GDPR compliance', 'flavor-chat-ia'); ?></td>
                            <td>$0.2-2 / 1M tokens</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Obtener API Keys', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><strong>Claude:</strong> <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></li>
                <li><strong>OpenAI:</strong> <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></li>
                <li><strong>DeepSeek:</strong> <a href="https://platform.deepseek.com/" target="_blank">platform.deepseek.com</a></li>
                <li><strong>Mistral:</strong> <a href="https://console.mistral.ai/" target="_blank">console.mistral.ai</a></li>
            </ul>

            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-shield"></span>
                <div>
                    <strong><?php _e('Seguridad', 'flavor-chat-ia'); ?></strong>
                    <p><?php _e('Las API keys se almacenan de forma segura en la base de datos de WordPress. Nunca las compartas públicamente ni las incluyas en código versionado.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Sistema de Escalados', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Cuando el chat no puede resolver una consulta, puede escalar a atención humana automáticamente.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Canales de Escalado', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><strong>WhatsApp</strong> - <?php _e('Envía al usuario a un chat de WhatsApp', 'flavor-chat-ia'); ?></li>
                <li><strong>Teléfono</strong> - <?php _e('Muestra un número de teléfono para llamar', 'flavor-chat-ia'); ?></li>
                <li><strong>Email</strong> - <?php _e('Abre el cliente de correo del usuario', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Configuración de Horarios', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Define los horarios de atención para que el chat informe al usuario cuándo puede recibir atención humana.', 'flavor-chat-ia'); ?></p>
            <div class="flavor-docs-code-block">
                <pre><code>L-V 9:00-18:00
S 10:00-14:00</code></pre>
            </div>

            <h3><?php _e('Gestión de Escalados', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('En la página de <strong>Escalados</strong> puedes:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><?php _e('Ver todos los escalados pendientes', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Filtrar por estado (pendiente, contactado, resuelto)', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Ver el resumen de la conversación', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Marcar escalados como resueltos', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Añadir notas internas', 'flavor-chat-ia'); ?></li>
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
            <h2><?php _e('API REST', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Flavor Platform expone una API REST completa para integrar con aplicaciones externas.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Base URL', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code><?php echo esc_html(rest_url('flavor/v1/')); ?></code></pre>
            </div>

            <h3><?php _e('Autenticación', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('La API soporta varios métodos de autenticación:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><strong>Cookie Auth</strong> - <?php _e('Para usuarios logueados en WordPress', 'flavor-chat-ia'); ?></li>
                <li><strong>Application Passwords</strong> - <?php _e('Para integraciones externas', 'flavor-chat-ia'); ?></li>
                <li><strong>JWT</strong> - <?php _e('Con plugins como JWT Auth (opcional)', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Endpoints Principales', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Método', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Endpoint', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>POST</code></td>
                            <td><code>/chat/message</code></td>
                            <td><?php _e('Enviar mensaje al chat', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/chat/history/{session_id}</code></td>
                            <td><?php _e('Obtener historial de conversación', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/modules</code></td>
                            <td><?php _e('Listar módulos disponibles', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/modules/{id}/items</code></td>
                            <td><?php _e('Obtener ítems de un módulo', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>POST</code></td>
                            <td><code>/modules/{id}/actions</code></td>
                            <td><?php _e('Ejecutar acción en módulo', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/user/dashboard</code></td>
                            <td><?php _e('Datos del dashboard de usuario', 'flavor-chat-ia'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Ejemplo de Uso', 'flavor-chat-ia'); ?></h3>
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
                    <strong><?php _e('Rate Limiting', 'flavor-chat-ia'); ?></strong>
                    <p><?php _e('La API tiene límite de 60 peticiones por minuto por IP para endpoints públicos. Los endpoints autenticados tienen límites más altos.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h2>

            <h3><?php _e('Chat', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_chat]
[flavor_chat position="inline" height="500px"]</code></pre>
            </div>

            <h3><?php _e('Módulos', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_module_listing module="eventos" limit="6" columns="3"]
[flavor_module_form module="banco_tiempo" action="crear_servicio"]
[flavor_module_detail module="marketplace" id="123"]</code></pre>
            </div>

            <h3><?php _e('Landing Pages', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_landing id="grupo_consumo"]
[flavor_section type="hero" variant="centered" title="Bienvenido"]
[flavor_section type="features" items="caracteristica1,caracteristica2,caracteristica3"]
[flavor_section type="cta" title="Únete" button_text="Registrarse" button_url="/registro/"]</code></pre>
            </div>

            <h3><?php _e('Dashboard de Usuario', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy">Copiar</button>
                <pre><code>[flavor_user_dashboard]
[flavor_user_dashboard tabs="perfil,pedidos,suscripciones"]</code></pre>
            </div>

            <h3><?php _e('Atributos Comunes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Atributo', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Ejemplo', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>limit</code></td>
                            <td><?php _e('Número máximo de ítems', 'flavor-chat-ia'); ?></td>
                            <td><code>limit="10"</code></td>
                        </tr>
                        <tr>
                            <td><code>columns</code></td>
                            <td><?php _e('Columnas del grid', 'flavor-chat-ia'); ?></td>
                            <td><code>columns="3"</code></td>
                        </tr>
                        <tr>
                            <td><code>orderby</code></td>
                            <td><?php _e('Campo de ordenación', 'flavor-chat-ia'); ?></td>
                            <td><code>orderby="date"</code></td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td><?php _e('Dirección de orden', 'flavor-chat-ia'); ?></td>
                            <td><code>order="DESC"</code></td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php _e('Clases CSS adicionales', 'flavor-chat-ia'); ?></td>
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
            <h2><?php _e('Webhooks', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Los webhooks permiten que tu aplicación reciba notificaciones en tiempo real cuando ocurren eventos importantes.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Eventos Disponibles', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-table-container">
                <table class="flavor-docs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Evento', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>chat.message</code></td>
                            <td><?php _e('Nuevo mensaje en el chat', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>chat.escalation</code></td>
                            <td><?php _e('Conversación escalada a humano', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>module.item_created</code></td>
                            <td><?php _e('Nuevo ítem creado en módulo', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>module.item_updated</code></td>
                            <td><?php _e('Ítem actualizado', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>user.registered</code></td>
                            <td><?php _e('Nuevo usuario registrado', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><code>payment.completed</code></td>
                            <td><?php _e('Pago completado', 'flavor-chat-ia'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3><?php _e('Payload de Ejemplo', 'flavor-chat-ia'); ?></h3>
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
            <h2><?php _e('Hooks & Filtros', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Flavor Platform ofrece numerosos hooks y filtros para personalizar su comportamiento.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Actions (Acciones)', 'flavor-chat-ia'); ?></h3>
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

            <h3><?php _e('Filters (Filtros)', 'flavor-chat-ia'); ?></h3>
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

            <h3><?php _e('Ejemplo de Uso', 'flavor-chat-ia'); ?></h3>
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
            <h2><?php _e('Addons', 'flavor-chat-ia'); ?></h2>

            <p class="flavor-docs-intro"><?php _e('Los addons amplían las funcionalidades de Flavor Platform con características premium y especializadas.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Gestión de Addons', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('En la página de <strong>Addons</strong> puedes:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><?php _e('Ver los addons instalados', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Activar/desactivar addons', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Gestionar licencias', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Actualizar addons', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Marketplace', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Explora el <strong>Marketplace</strong> para descubrir nuevos addons:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><?php _e('Addons gratuitos de la comunidad', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Addons premium con soporte', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Integraciones con servicios externos', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Desarrollar Addons', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Puedes crear tus propios addons siguiendo la estructura:', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Tips & Mejores Prácticas', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-docs-tips-grid">
                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-performance" style="color: #f59e0b;"></span>
                    <h4><?php _e('Rendimiento', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><?php _e('Usa caché de objetos (Redis/Memcached) para sitios con mucho tráfico', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Activa solo los módulos que realmente necesites', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Usa FAQs precargadas para reducir llamadas a la IA', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Configura límites de tokens por mensaje', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-shield" style="color: #22c55e;"></span>
                    <h4><?php _e('Seguridad', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><?php _e('Mantén WordPress y plugins actualizados', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('No compartas API keys en código público', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Usa HTTPS siempre', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Revisa los escalados regularmente', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-admin-comments" style="color: #3b82f6;"></span>
                    <h4><?php _e('Chat IA', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><?php _e('Sé específico en las instrucciones de sistema', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Añade ejemplos en la base de conocimiento', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Define claramente qué no debe hacer el bot', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Prueba con diferentes escenarios', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-admin-appearance" style="color: #8b5cf6;"></span>
                    <h4><?php _e('Diseño', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><?php _e('Usa colores que reflejen tu marca', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Mantén la consistencia en toda la app', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Prueba en móvil y escritorio', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Considera el modo oscuro', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-backup" style="color: #ef4444;"></span>
                    <h4><?php _e('Backups', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><?php _e('Haz backup antes de cambiar de plantilla', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Usa la función Export/Import regularmente', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Guarda copias de tu configuración', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Prueba los cambios en staging primero', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-tip-card">
                    <span class="dashicons dashicons-chart-line" style="color: #14b8a6;"></span>
                    <h4><?php _e('Monitorización', 'flavor-chat-ia'); ?></h4>
                    <ul>
                        <li><?php _e('Revisa el Dashboard diariamente', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Analiza las conversaciones escaladas', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Monitoriza el consumo de tokens', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Usa el Diagnóstico para detectar problemas', 'flavor-chat-ia'); ?></li>
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
            <h2><?php _e('Preguntas Frecuentes', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-docs-faq">
                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Cuánto cuesta usar Flavor Platform?', 'flavor-chat-ia'); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('El plugin es gratuito. Los costes vienen de los proveedores de IA (Claude, OpenAI, etc.) según tu uso. Puedes empezar con DeepSeek que es muy económico (~$0.14/millón de tokens).', 'flavor-chat-ia'); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Necesito conocimientos de programación?', 'flavor-chat-ia'); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('No. El sistema de plantillas y el compositor visual te permiten crear aplicaciones completas sin escribir código. Solo necesitas programar si quieres personalizaciones avanzadas.', 'flavor-chat-ia'); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Puedo usar múltiples plantillas?', 'flavor-chat-ia'); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Solo puedes tener una plantilla activa, pero puedes activar módulos adicionales de otras plantillas manualmente desde el Compositor.', 'flavor-chat-ia'); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Es compatible con mi tema de WordPress?', 'flavor-chat-ia'); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Flavor Platform está diseñado para funcionar con cualquier tema. Usa CSS aislado y no interfiere con los estilos de tu tema. Si encuentras conflictos, contacta con soporte.', 'flavor-chat-ia'); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Cómo elimino los datos de demostración?', 'flavor-chat-ia'); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Ve a Compositor & Módulos, baja hasta "Datos de Demostración" y haz clic en "Eliminar datos demo" del módulo que quieras limpiar, o "Eliminar todos" para borrar todo.', 'flavor-chat-ia'); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿El chat funciona en móviles?', 'flavor-chat-ia'); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Sí, el widget de chat es completamente responsive y funciona en móviles, tablets y escritorio. También es compatible con PWA para experiencia tipo app nativa.', 'flavor-chat-ia'); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Puedo personalizar las respuestas del chat?', 'flavor-chat-ia'); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Sí, mediante: 1) Instrucciones de sistema (rol/personalidad), 2) Base de conocimiento, 3) FAQs precargadas, 4) Filtros PHP para desarrolladores.', 'flavor-chat-ia'); ?></p>
                    </div>
                </details>

                <details class="flavor-docs-faq-item">
                    <summary><?php _e('¿Cómo actualizo el plugin?', 'flavor-chat-ia'); ?></summary>
                    <div class="flavor-docs-faq-answer">
                        <p><?php _e('Las actualizaciones aparecen en el panel de plugins de WordPress como cualquier otro plugin. Recomendamos hacer backup antes de actualizar versiones mayores.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Soporte', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-docs-support-grid">
                <div class="flavor-docs-support-card">
                    <span class="dashicons dashicons-book" style="color: #3b82f6;"></span>
                    <h3><?php _e('Documentación', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('Estás en ella. Usa la navegación lateral para explorar todas las secciones.', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-docs-support-card">
                    <span class="dashicons dashicons-admin-tools" style="color: #f59e0b;"></span>
                    <h3><?php _e('Diagnóstico', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('Usa la herramienta de diagnóstico para identificar y resolver problemas comunes.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=flavor-health-check'); ?>" class="button">
                        <?php _e('Ir a Diagnóstico', 'flavor-chat-ia'); ?>
                    </a>
                </div>

                <div class="flavor-docs-support-card">
                    <span class="dashicons dashicons-email" style="color: #22c55e;"></span>
                    <h3><?php _e('Contacto', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('¿Tienes preguntas o necesitas ayuda personalizada?', 'flavor-chat-ia'); ?></p>
                    <a href="mailto:soporte@gailu.net" class="button button-primary">
                        soporte@gailu.net
                    </a>
                </div>

                <div class="flavor-docs-support-card">
                    <span class="dashicons dashicons-admin-site-alt3" style="color: #8b5cf6;"></span>
                    <h3><?php _e('Sitio Web', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('Visita nuestra web para más recursos, tutoriales y novedades.', 'flavor-chat-ia'); ?></p>
                    <a href="https://gailu.net" target="_blank" class="button">
                        gailu.net ↗
                    </a>
                </div>
            </div>

            <div class="flavor-docs-system-info">
                <h3><?php _e('Información del Sistema', 'flavor-chat-ia'); ?></h3>
                <p><?php _e('Incluye esta información al reportar problemas:', 'flavor-chat-ia'); ?></p>
                <div class="flavor-docs-code-block">
                    <button class="flavor-docs-code-copy">Copiar</button>
                    <pre><code><?php
                    echo "Flavor Platform: " . FLAVOR_CHAT_IA_VERSION . "\n";
                    echo "WordPress: " . get_bloginfo('version') . "\n";
                    echo "PHP: " . phpversion() . "\n";
                    echo "Tema: " . wp_get_theme()->get('Name') . " " . wp_get_theme()->get('Version') . "\n";
                    echo "Perfil Activo: " . get_option('flavor_chat_ia_settings')['app_profile'] ?? 'personalizado';
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
            <h2><?php _e('Changelog', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-docs-changelog">
                <div class="flavor-docs-changelog-version">
                    <div class="flavor-docs-changelog-header">
                        <span class="version-badge">3.1.0</span>
                        <span class="version-date"><?php _e('Febrero 2026', 'flavor-chat-ia'); ?></span>
                        <span class="version-tag nuevo"><?php _e('Actual', 'flavor-chat-ia'); ?></span>
                    </div>
                    <ul>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Template Orchestrator - Activación automatizada de plantillas', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Sistema de suscripciones y cuotas para socios', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Dashboard de usuario frontend (Mi Cuenta)', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Push Notifications via Firebase', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Gestor de Newsletter con campañas', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Página de documentación integrada', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge mejora">Mejora</span> <?php _e('Rendimiento de caché mejorado', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge mejora">Mejora</span> <?php _e('Rate limiter para API REST', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge fix">Fix</span> <?php _e('Datos demo de Grupos de Consumo', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-changelog-version">
                    <div class="flavor-docs-changelog-header">
                        <span class="version-badge">3.0.0</span>
                        <span class="version-date"><?php _e('Enero 2026', 'flavor-chat-ia'); ?></span>
                    </div>
                    <ul>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Sistema de Addons con marketplace', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Multi-proveedor IA (Claude, OpenAI, DeepSeek, Mistral)', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Page Builder con secciones modulares', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Deep Links para apps móviles', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge mejora">Mejora</span> <?php _e('Arquitectura modular refactorizada', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-changelog-version">
                    <div class="flavor-docs-changelog-header">
                        <span class="version-badge">2.0.0</span>
                        <span class="version-date"><?php _e('Noviembre 2025', 'flavor-chat-ia'); ?></span>
                    </div>
                    <ul>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Perfiles de aplicación (plantillas)', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Sistema de módulos activables', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Landing pages dinámicas', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('API REST completa', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>

                <div class="flavor-docs-changelog-version">
                    <div class="flavor-docs-changelog-header">
                        <span class="version-badge">1.0.0</span>
                        <span class="version-date"><?php _e('Septiembre 2025', 'flavor-chat-ia'); ?></span>
                    </div>
                    <ul>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Lanzamiento inicial', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Chat IA con Claude', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Sistema de escalados', 'flavor-chat-ia'); ?></li>
                        <li><span class="badge nuevo">Nuevo</span> <?php _e('Integración WooCommerce', 'flavor-chat-ia'); ?></li>
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
            <h2><?php _e('Dashboard de Administración', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-docs-intro"><?php _e('El Dashboard es tu centro de control para monitorear el estado de la plataforma.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Widgets Disponibles', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-grid">
                <div class="flavor-docs-card">
                    <span class="dashicons dashicons-chart-bar" style="color: #2563eb;"></span>
                    <h4><?php _e('Estadísticas Generales', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Usuarios activos, conversaciones del chat IA, módulos activos y uso de API.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-card">
                    <span class="dashicons dashicons-admin-users" style="color: #16a34a;"></span>
                    <h4><?php _e('Actividad de Usuarios', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Registros recientes, últimos logins y usuarios por rol.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-card">
                    <span class="dashicons dashicons-format-chat" style="color: #d97706;"></span>
                    <h4><?php _e('Chat IA Stats', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Conversaciones activas, escalados pendientes y uso de tokens.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-docs-card">
                    <span class="dashicons dashicons-admin-plugins" style="color: #7c3aed;"></span>
                    <h4><?php _e('Estado de Módulos', 'flavor-chat-ia'); ?></h4>
                    <p><?php _e('Módulos activos/inactivos y estado de sus tablas de base de datos.', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <h3><?php _e('Acciones Rápidas', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><strong><?php _e('Ir al Compositor:', 'flavor-chat-ia'); ?></strong> <?php _e('Configura tu aplicación desde cero.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Ver Escalados:', 'flavor-chat-ia'); ?></strong> <?php _e('Atiende las conversaciones que requieren intervención humana.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Diagnóstico:', 'flavor-chat-ia'); ?></strong> <?php _e('Verifica el estado técnico de la instalación.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Exportar Datos:', 'flavor-chat-ia'); ?></strong> <?php _e('Realiza copias de seguridad de la configuración.', 'flavor-chat-ia'); ?></li>
            </ul>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <p><?php _e('El Dashboard se actualiza automáticamente cada 5 minutos. Los contadores muestran datos de los últimos 30 días.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Compositor & Módulos', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-docs-intro"><?php _e('El Compositor es la herramienta central para configurar tu aplicación. Selecciona plantillas predefinidas o personaliza módulos individualmente.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Plantillas Disponibles', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Plantilla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Módulos Incluidos', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php _e('Comunidad Vecinal', 'flavor-chat-ia'); ?></strong></td>
                        <td><?php _e('Gestión de comunidades de vecinos, eventos y espacios comunes.', 'flavor-chat-ia'); ?></td>
                        <td>socios, eventos, espacios-comunes, ayuda-vecinal</td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Asociación Cultural', 'flavor-chat-ia'); ?></strong></td>
                        <td><?php _e('Para asociaciones con eventos, cursos y biblioteca.', 'flavor-chat-ia'); ?></td>
                        <td>socios, eventos, cursos, biblioteca</td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Economía Colaborativa', 'flavor-chat-ia'); ?></strong></td>
                        <td><?php _e('Banco de tiempo, grupos de consumo y marketplace.', 'flavor-chat-ia'); ?></td>
                        <td>banco-tiempo, grupos-consumo, marketplace</td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Ayuntamiento Digital', 'flavor-chat-ia'); ?></strong></td>
                        <td><?php _e('Portal ciudadano con trámites, participación y transparencia.', 'flavor-chat-ia'); ?></td>
                        <td>tramites, participacion, transparencia, incidencias</td>
                    </tr>
                </tbody>
            </table>

            <h3><?php _e('Template Orchestrator', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('El Template Orchestrator automatiza la activación completa:', 'flavor-chat-ia'); ?></p>
            <ol>
                <li><?php _e('Activa los módulos requeridos', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Crea las tablas de base de datos', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Registra los CPTs necesarios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Genera páginas con shortcodes', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Carga la landing page de la plantilla', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Aplica configuración predeterminada', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Inserta datos de demostración (opcional)', 'flavor-chat-ia'); ?></li>
            </ol>

            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
                <pre><code>// Activar plantilla programáticamente
$orchestrator = Flavor_Template_Orchestrator::get_instance();
$resultado = $orchestrator->activar_plantilla('comunidad-vecinal', [
    'datos_demo' => true,
    'landing' => true
]);</code></pre>
            </div>

            <h3><?php _e('Gestión Manual de Módulos', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('También puedes activar/desactivar módulos individualmente desde la pestaña "Módulos" del Compositor. Cada módulo muestra:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><strong><?php _e('Estado:', 'flavor-chat-ia'); ?></strong> <?php _e('Activo/Inactivo con toggle switch.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Dependencias:', 'flavor-chat-ia'); ?></strong> <?php _e('Módulos que requiere para funcionar.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Descripción:', 'flavor-chat-ia'); ?></strong> <?php _e('Funcionalidad que aporta.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Categoría:', 'flavor-chat-ia'); ?></strong> <?php _e('Comunidad, Contenido, Economía, etc.', 'flavor-chat-ia'); ?></li>
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
            <h2><?php _e('Diseño y Apariencia', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Personaliza la apariencia visual de tu aplicación sin necesidad de código.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Opciones de Personalización', 'flavor-chat-ia'); ?></h3>

            <h4><?php _e('Colores', 'flavor-chat-ia'); ?></h4>
            <ul>
                <li><strong><?php _e('Color Primario:', 'flavor-chat-ia'); ?></strong> <?php _e('Usado en botones, enlaces y elementos destacados.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Color Secundario:', 'flavor-chat-ia'); ?></strong> <?php _e('Para acentos y elementos secundarios.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Color de Fondo:', 'flavor-chat-ia'); ?></strong> <?php _e('Fondo general de la aplicación.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Color de Texto:', 'flavor-chat-ia'); ?></strong> <?php _e('Color principal del texto.', 'flavor-chat-ia'); ?></li>
            </ul>

            <h4><?php _e('Tipografía', 'flavor-chat-ia'); ?></h4>
            <ul>
                <li><?php _e('Fuente de encabezados', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Fuente de cuerpo', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Tamaño base (rem)', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Peso de fuente', 'flavor-chat-ia'); ?></li>
            </ul>

            <h4><?php _e('Logotipos e Imágenes', 'flavor-chat-ia'); ?></h4>
            <ul>
                <li><strong><?php _e('Logo principal:', 'flavor-chat-ia'); ?></strong> <?php _e('Se muestra en el header.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Logo alternativo:', 'flavor-chat-ia'); ?></strong> <?php _e('Versión clara para fondos oscuros.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Favicon:', 'flavor-chat-ia'); ?></strong> <?php _e('Icono de la pestaña del navegador.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Imagen de login:', 'flavor-chat-ia'); ?></strong> <?php _e('Fondo de la página de acceso.', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Layouts', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Los layouts definen la estructura de las secciones de landing pages. Se acceden desde Diseño → Layouts.', 'flavor-chat-ia'); ?></p>

            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>hero</td><td><?php _e('Cabecera principal con imagen/video de fondo', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>features</td><td><?php _e('Características en columnas', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>cta</td><td><?php _e('Llamada a la acción con botón', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>testimonios</td><td><?php _e('Carrusel de testimonios', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>pricing</td><td><?php _e('Tabla de precios/planes', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>faq</td><td><?php _e('Preguntas frecuentes con acordeón', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>contacto</td><td><?php _e('Formulario de contacto', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <p><?php _e('Los cambios de diseño se aplican en tiempo real. Usa la vista previa antes de guardar.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Gestión de Páginas', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Crea y gestiona las páginas de tu aplicación con shortcodes integrados.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Generador de Páginas', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('El generador de páginas crea automáticamente páginas WordPress con los shortcodes necesarios para cada módulo activo.', 'flavor-chat-ia'); ?></p>

            <h4><?php _e('Páginas por Módulo', 'flavor-chat-ia'); ?></h4>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Módulo', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Páginas Generadas', 'flavor-chat-ia'); ?></th>
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

            <h3><?php _e('Página Builder', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('El Page Builder integrado permite crear landing pages arrastrando secciones:', 'flavor-chat-ia'); ?></p>
            <ol>
                <li><?php _e('Edita una página con el editor clásico', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Activa el metabox "Flavor Page Builder"', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Arrastra secciones desde la paleta', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Configura cada sección individualmente', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Guarda y previsualiza', 'flavor-chat-ia'); ?></li>
            </ol>

            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-warning"></span>
                <p><?php _e('El Page Builder es incompatible con Gutenberg. Usa el editor clásico para páginas con Page Builder.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Configuración General', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Configuración del Chat IA, APIs externas y opciones globales de la plataforma.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Pestañas de Configuración', 'flavor-chat-ia'); ?></h3>

            <h4><?php _e('General', 'flavor-chat-ia'); ?></h4>
            <ul>
                <li><?php _e('Nombre de la organización', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Email de contacto', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Idioma predeterminado', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Zona horaria', 'flavor-chat-ia'); ?></li>
            </ul>

            <h4><?php _e('Chat IA', 'flavor-chat-ia'); ?></h4>
            <ul>
                <li><strong><?php _e('Motor de IA:', 'flavor-chat-ia'); ?></strong> Claude, OpenAI, DeepSeek, Mistral</li>
                <li><strong><?php _e('API Keys:', 'flavor-chat-ia'); ?></strong> <?php _e('Claves de acceso a los proveedores', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Modelo:', 'flavor-chat-ia'); ?></strong> <?php _e('Selección del modelo específico', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('System Prompt:', 'flavor-chat-ia'); ?></strong> <?php _e('Instrucciones base para el asistente', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Límites:', 'flavor-chat-ia'); ?></strong> <?php _e('Tokens máximos, mensajes por sesión', 'flavor-chat-ia'); ?></li>
            </ul>

            <h4><?php _e('Escalados', 'flavor-chat-ia'); ?></h4>
            <ul>
                <li><?php _e('Activar/desactivar sistema de escalados', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Canales de notificación (Email, WhatsApp, Telegram)', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Destinatarios de alertas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Palabras clave para escalado automático', 'flavor-chat-ia'); ?></li>
            </ul>

            <h4><?php _e('Notificaciones', 'flavor-chat-ia'); ?></h4>
            <ul>
                <li><?php _e('Email: Configuración SMTP', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Push: Firebase Cloud Messaging', 'flavor-chat-ia'); ?></li>
                <li><?php _e('WhatsApp: API Business o Twilio', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Telegram: Bot Token', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Seguridad', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-shield"></span>
                <p><?php _e('Las API keys se almacenan cifradas en la base de datos. Nunca las compartas ni las incluyas en backups públicos.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Herramientas del Sistema', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Herramientas de mantenimiento, diagnóstico y administración avanzada.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Export / Import', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Exporta e importa configuraciones completas de la plataforma:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><strong><?php _e('Configuración:', 'flavor-chat-ia'); ?></strong> <?php _e('Opciones globales, ajustes de chat, diseño.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Módulos:', 'flavor-chat-ia'); ?></strong> <?php _e('Estado de activación y configuración de módulos.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Plantillas:', 'flavor-chat-ia'); ?></strong> <?php _e('Datos de la plantilla activa.', 'flavor-chat-ia'); ?></li>
                <li><strong><?php _e('Layouts:', 'flavor-chat-ia'); ?></strong> <?php _e('Configuraciones de landing pages.', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Diagnóstico', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('El Health Check verifica:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><?php _e('Versión de PHP y extensiones requeridas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Permisos de directorios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Conexión con APIs externas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Estado de tablas de base de datos', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Conflictos con otros plugins', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Uso de memoria y tiempo de ejecución', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Registro de Actividad', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('El Activity Log registra acciones importantes:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><?php _e('Cambios de configuración', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Activación/desactivación de módulos', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Errores de API', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Acciones de usuarios', 'flavor-chat-ia'); ?></li>
            </ul>

            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
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
            <h2><?php _e('Módulo: Socios', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Gestión integral de membresías, cuotas y directorio de socios.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('Registro y alta de socios con validación', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Tipos de socio personalizables', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Sistema de cuotas con renovación automática', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Directorio público/privado de socios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Carnets digitales', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Historial de pagos', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Integración con pasarelas de pago', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_socios</td><td><?php _e('Datos de socios vinculados a usuarios', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_socios_cuotas</td><td><?php _e('Cuotas y pagos de socios', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_socios_tipos</td><td><?php _e('Tipos de membresía disponibles', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
                <pre><code>[flavor_socios_directorio] - Directorio de socios
[flavor_socios_perfil] - Perfil del socio actual
[flavor_socios_alta] - Formulario de registro
[flavor_socios_carnet] - Carnet digital del socio</code></pre>
            </div>

            <h3><?php _e('API REST', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Método', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>/flavor/v1/socios</td><td>GET</td><td><?php _e('Listar socios', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/socios/{id}</td><td>GET</td><td><?php _e('Obtener socio', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/socios/perfil</td><td>GET/PUT</td><td><?php _e('Mi perfil', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/socios/cuotas</td><td>GET</td><td><?php _e('Mis cuotas', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Hooks Disponibles', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
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
            <h2><?php _e('Módulo: Eventos', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Sistema completo de gestión de eventos con inscripciones, calendario y recordatorios.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('CPT de eventos con categorías y etiquetas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Calendario visual interactivo', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Sistema de inscripciones con límite de plazas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Lista de espera automática', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Recordatorios por email/push', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Eventos recurrentes', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Integración con Google Calendar', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('CPT: flavor_evento', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Campos personalizados del evento:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><code>_evento_fecha_inicio</code> - <?php _e('Fecha y hora de inicio', 'flavor-chat-ia'); ?></li>
                <li><code>_evento_fecha_fin</code> - <?php _e('Fecha y hora de fin', 'flavor-chat-ia'); ?></li>
                <li><code>_evento_ubicacion</code> - <?php _e('Dirección o lugar', 'flavor-chat-ia'); ?></li>
                <li><code>_evento_plazas</code> - <?php _e('Límite de inscripciones', 'flavor-chat-ia'); ?></li>
                <li><code>_evento_precio</code> - <?php _e('Coste de inscripción', 'flavor-chat-ia'); ?></li>
                <li><code>_evento_recurrencia</code> - <?php _e('Patrón de repetición', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_eventos_inscripciones</td><td><?php _e('Inscripciones a eventos', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_eventos_asistencia</td><td><?php _e('Control de asistencia', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
                <pre><code>[flavor_eventos_calendario] - Calendario de eventos
[flavor_eventos_proximos limit="5"] - Próximos eventos
[flavor_eventos_mis_inscripciones] - Mis inscripciones
[flavor_evento_detalle id="123"] - Detalle de un evento</code></pre>
            </div>

            <h3><?php _e('API REST', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Método', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>/flavor/v1/eventos</td><td>GET</td><td><?php _e('Listar eventos', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/eventos/{id}</td><td>GET</td><td><?php _e('Detalle del evento', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/eventos/{id}/inscribir</td><td>POST</td><td><?php _e('Inscribirse', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/eventos/mis-inscripciones</td><td>GET</td><td><?php _e('Mis inscripciones', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/eventos/calendario</td><td>GET</td><td><?php _e('Datos para calendario', 'flavor-chat-ia'); ?></td></tr>
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
            <h2><?php _e('Módulo: Banco de Tiempo', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Plataforma de intercambio de servicios basada en tiempo como moneda.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('Publicación de ofertas y demandas de servicios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Sistema de créditos en horas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Transacciones entre usuarios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Balance personal de horas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Historial de intercambios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Categorías de servicios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Sistema de valoraciones', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_bt_ofertas</td><td><?php _e('Servicios ofrecidos', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_bt_demandas</td><td><?php _e('Servicios solicitados', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_bt_transacciones</td><td><?php _e('Intercambios realizados', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_bt_balances</td><td><?php _e('Saldo de horas por usuario', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
                <pre><code>[flavor_bt_ofertas] - Listado de ofertas
[flavor_bt_demandas] - Listado de demandas
[flavor_bt_mi_balance] - Mi balance de horas
[flavor_bt_historial] - Historial de transacciones
[flavor_bt_publicar_oferta] - Formulario nueva oferta
[flavor_bt_publicar_demanda] - Formulario nueva demanda</code></pre>
            </div>

            <h3><?php _e('API REST', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Método', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>/flavor/v1/banco-tiempo/ofertas</td><td>GET/POST</td><td><?php _e('Listar/crear ofertas', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/banco-tiempo/demandas</td><td>GET/POST</td><td><?php _e('Listar/crear demandas', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/banco-tiempo/transaccion</td><td>POST</td><td><?php _e('Registrar intercambio', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/banco-tiempo/balance</td><td>GET</td><td><?php _e('Mi balance', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <div class="flavor-docs-tip">
                <span class="dashicons dashicons-lightbulb"></span>
                <p><?php _e('Los nuevos usuarios reciben un crédito inicial configurable (por defecto: 5 horas) para comenzar a intercambiar.', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Módulo: Grupos de Consumo', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge parcial"><?php _e('Estado: En Desarrollo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Gestión de grupos de consumo responsable con productores, productos, ciclos de pedido y entregas.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('Gestión de productores locales', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Catálogo de productos por productor', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Ciclos de pedido con fechas de apertura/cierre', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Sistema de pedidos por usuario', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Consolidado de pedidos para productores', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Gestión de entregas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Notificaciones de ciclos', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('CPTs del Módulo', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th>CPT</th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>gc_productor</td><td><?php _e('Productores locales', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>gc_producto</td><td><?php _e('Productos disponibles', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>gc_ciclo</td><td><?php _e('Ciclos de pedido', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_gc_pedidos</td><td><?php _e('Pedidos de usuarios', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_gc_entregas</td><td><?php _e('Control de entregas', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_gc_consolidado</td><td><?php _e('Pedidos consolidados por productor', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_gc_notificaciones</td><td><?php _e('Historial de notificaciones', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
                <pre><code>[gc_ciclo_actual] - Ciclo de pedido activo
[gc_productos] - Catálogo de productos
[gc_mi_pedido] - Mi pedido actual</code></pre>
            </div>

            <h3><?php _e('Próximas Funcionalidades', 'flavor-chat-ia'); ?></h3>
            <ul class="flavor-docs-roadmap">
                <li><span class="badge pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></span> <?php _e('Sistema de suscripciones a cestas', 'flavor-chat-ia'); ?></li>
                <li><span class="badge pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></span> <?php _e('Lista de compra personal', 'flavor-chat-ia'); ?></li>
                <li><span class="badge pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></span> <?php _e('Notificaciones WhatsApp/Telegram', 'flavor-chat-ia'); ?></li>
                <li><span class="badge pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></span> <?php _e('Dashboard de usuario ampliado', 'flavor-chat-ia'); ?></li>
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
            <h2><?php _e('Módulo: Marketplace', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Tablón de anuncios para compraventa, intercambio y donación entre usuarios de la comunidad.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('Publicación de anuncios con imágenes', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Tipos: Venta, Compra, Intercambio, Donación', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Categorías personalizables', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Búsqueda y filtros avanzados', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Sistema de favoritos', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Mensajería entre usuarios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Moderación de anuncios', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('CPT: flavor_anuncio', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Campos personalizados:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><code>_anuncio_tipo</code> - venta|compra|intercambio|donacion</li>
                <li><code>_anuncio_precio</code> - <?php _e('Precio (0 para donación)', 'flavor-chat-ia'); ?></li>
                <li><code>_anuncio_estado</code> - disponible|reservado|vendido</li>
                <li><code>_anuncio_ubicacion</code> - <?php _e('Zona/barrio', 'flavor-chat-ia'); ?></li>
                <li><code>_anuncio_contacto</code> - <?php _e('Método de contacto preferido', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_marketplace_favoritos</td><td><?php _e('Anuncios guardados por usuarios', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_marketplace_mensajes</td><td><?php _e('Mensajes entre compradores y vendedores', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
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
            <h2><?php _e('Módulo: Ayuda Vecinal', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Plataforma de apoyo mutuo entre vecinos para tareas cotidianas y emergencias.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('Solicitudes de ayuda con urgencia', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Ofertas de ayuda voluntaria', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Categorías: Compras, Acompañamiento, Transporte, Mascotas, etc.', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Geolocalización por barrios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Sistema de voluntarios verificados', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Notificaciones en tiempo real', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_ayuda_solicitudes</td><td><?php _e('Solicitudes de ayuda', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_ayuda_ofertas</td><td><?php _e('Ofertas de voluntarios', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_ayuda_voluntarios</td><td><?php _e('Registro de voluntarios', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_ayuda_matches</td><td><?php _e('Conexiones ayuda-voluntario', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
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
            <h2><?php _e('Módulo: Reservas', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Sistema genérico de reservas para cualquier tipo de recurso: espacios, equipos, servicios.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('Recursos configurables (salas, coches, herramientas, etc.)', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Calendario de disponibilidad', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Franjas horarias personalizables', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Límites de reserva por usuario', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Confirmación automática o manual', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Recordatorios automáticos', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Cancelaciones con política configurable', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_reservas_recursos</td><td><?php _e('Recursos reservables', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_reservas</td><td><?php _e('Reservas realizadas', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_reservas_bloqueos</td><td><?php _e('Bloqueos de disponibilidad', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
                <pre><code>[flavor_reservas_calendario recurso="1"] - Calendario del recurso
[flavor_reservas_lista] - Lista de recursos disponibles
[flavor_reservas_mis_reservas] - Mis reservas
[flavor_reservas_formulario recurso="1"] - Formulario de reserva</code></pre>
            </div>

            <h3><?php _e('API REST', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Método', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>/flavor/v1/reservas/recursos</td><td>GET</td><td><?php _e('Listar recursos', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/reservas/disponibilidad/{id}</td><td>GET</td><td><?php _e('Disponibilidad', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/reservas</td><td>POST</td><td><?php _e('Crear reserva', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>/flavor/v1/reservas/{id}/cancelar</td><td>POST</td><td><?php _e('Cancelar reserva', 'flavor-chat-ia'); ?></td></tr>
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
            <h2><?php _e('Módulo: Cursos', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Plataforma de formación online con cursos, lecciones y seguimiento del progreso.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('Cursos con múltiples lecciones', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Contenido multimedia (vídeo, audio, PDF)', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Progreso del alumno', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Certificados de finalización', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Cuestionarios y evaluaciones', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Cursos gratuitos y de pago', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Instructores múltiples', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('CPTs del Módulo', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th>CPT</th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>flavor_curso</td><td><?php _e('Cursos principales', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>flavor_leccion</td><td><?php _e('Lecciones de cada curso', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_cursos_matriculas</td><td><?php _e('Matrículas de alumnos', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_cursos_progreso</td><td><?php _e('Progreso por lección', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_cursos_certificados</td><td><?php _e('Certificados generados', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
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
            <h2><?php _e('Módulo: Biblioteca', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Gestión de biblioteca digital con préstamos, reservas y catálogo de recursos.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('Catálogo de libros, revistas, DVDs, etc.', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Sistema de préstamos con fechas límite', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Reservas de ejemplares', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Historial de préstamos por usuario', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Multas por retraso (configurable)', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Búsqueda avanzada por título, autor, ISBN', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Descarga de recursos digitales', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('CPT: flavor_recurso_biblioteca', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Campos personalizados:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><code>_recurso_tipo</code> - libro|revista|dvd|digital</li>
                <li><code>_recurso_autor</code> - <?php _e('Autor/es', 'flavor-chat-ia'); ?></li>
                <li><code>_recurso_isbn</code> - ISBN</li>
                <li><code>_recurso_editorial</code> - <?php _e('Editorial', 'flavor-chat-ia'); ?></li>
                <li><code>_recurso_ejemplares</code> - <?php _e('Número de ejemplares', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_biblioteca_prestamos</td><td><?php _e('Préstamos activos e históricos', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_biblioteca_reservas</td><td><?php _e('Reservas pendientes', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
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
            <h2><?php _e('Módulo: Podcast', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-docs-status-badge completo"><?php _e('Estado: Completo', 'flavor-chat-ia'); ?></div>
            <p class="flavor-docs-intro"><?php _e('Publicación y gestión de contenido de audio con reproductor integrado y feed RSS.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Características', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><?php _e('Series/Programas con episodios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Reproductor de audio integrado', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Feed RSS compatible con plataformas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Transcripciones de episodios', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Estadísticas de reproducciones', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Suscripciones a programas', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Integración con Spotify, Apple Podcasts', 'flavor-chat-ia'); ?></li>
            </ul>

            <h3><?php _e('CPTs del Módulo', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th>CPT</th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>flavor_programa</td><td><?php _e('Series/Programas de podcast', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>flavor_episodio</td><td><?php _e('Episodios individuales', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Base de Datos', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_podcast_stats</td><td><?php _e('Estadísticas de reproducción', 'flavor-chat-ia'); ?></td></tr>
                    <tr><td>wp_flavor_podcast_suscripciones</td><td><?php _e('Suscriptores a programas', 'flavor-chat-ia'); ?></td></tr>
                </tbody>
            </table>

            <h3><?php _e('Shortcodes', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
                <pre><code>[flavor_podcast_programas] - Lista de programas
[flavor_podcast_episodios programa="123"] - Episodios de un programa
[flavor_podcast_player id="456"] - Reproductor de episodio
[flavor_podcast_ultimos limit="5"] - Últimos episodios</code></pre>
            </div>

            <h3><?php _e('Feed RSS', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('El feed RSS se genera automáticamente:', 'flavor-chat-ia'); ?></p>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
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
            <h2><?php _e('Estructura de Base de Datos', 'flavor-chat-ia'); ?></h2>
            <p class="flavor-docs-intro"><?php _e('Flavor Platform utiliza tablas personalizadas además de los CPTs nativos de WordPress.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Tablas del Core', 'flavor-chat-ia'); ?></h3>
            <table class="flavor-docs-table">
                <thead>
                    <tr>
                        <th><?php _e('Tabla', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Filas Aprox.', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>wp_flavor_chat_sessions</td><td><?php _e('Sesiones de chat IA', 'flavor-chat-ia'); ?></td><td>Media</td></tr>
                    <tr><td>wp_flavor_chat_messages</td><td><?php _e('Mensajes de conversaciones', 'flavor-chat-ia'); ?></td><td>Alta</td></tr>
                    <tr><td>wp_flavor_chat_escalations</td><td><?php _e('Escalados a humanos', 'flavor-chat-ia'); ?></td><td>Baja</td></tr>
                    <tr><td>wp_flavor_activity_log</td><td><?php _e('Registro de actividad', 'flavor-chat-ia'); ?></td><td>Alta</td></tr>
                    <tr><td>wp_flavor_notifications</td><td><?php _e('Notificaciones enviadas', 'flavor-chat-ia'); ?></td><td>Media</td></tr>
                    <tr><td>wp_flavor_user_preferences</td><td><?php _e('Preferencias de usuarios', 'flavor-chat-ia'); ?></td><td>Baja</td></tr>
                </tbody>
            </table>

            <h3><?php _e('Tablas por Módulo', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Cada módulo puede crear sus propias tablas durante la instalación:', 'flavor-chat-ia'); ?></p>

            <h4><?php _e('Patrón de Nomenclatura', 'flavor-chat-ia'); ?></h4>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
                <pre><code>wp_flavor_{modulo}_{entidad}

Ejemplos:
- wp_flavor_socios_cuotas
- wp_flavor_eventos_inscripciones
- wp_flavor_bt_transacciones
- wp_flavor_gc_pedidos</code></pre>
            </div>

            <h3><?php _e('Crear Tablas Personalizadas', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Los módulos definen sus tablas en el archivo install.php:', 'flavor-chat-ia'); ?></p>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
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

            <h3><?php _e('Consultas Seguras', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-docs-warning">
                <span class="dashicons dashicons-shield"></span>
                <p><?php _e('Siempre usa $wpdb->prepare() para consultas con parámetros externos:', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flavor-docs-code-block">
                <button class="flavor-docs-code-copy"><?php _e('Copiar', 'flavor-chat-ia'); ?></button>
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

            <h3><?php _e('Índices Recomendados', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li><code>usuario_id</code> - <?php _e('En todas las tablas con relación a usuarios', 'flavor-chat-ia'); ?></li>
                <li><code>created_at</code> - <?php _e('Para consultas ordenadas por fecha', 'flavor-chat-ia'); ?></li>
                <li><code>estado</code> - <?php _e('Para filtrar por estado activo/inactivo', 'flavor-chat-ia'); ?></li>
                <li><code>post_id</code> - <?php _e('Si hay relación con CPTs', 'flavor-chat-ia'); ?></li>
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

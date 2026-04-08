<?php
/**
 * Script de configuración inicial para Herri Antifaxistak
 * Sitio web bilingüe (Euskera/Castellano) para comunidad antifascista
 *
 * @package FlavorChatIA
 * @subpackage HerriAntifaxistak
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para configurar el sitio Herri Antifaxistak
 */
class Herri_Antifaxistak_Setup {

    /**
     * Paleta de colores de la comunidad
     */
    private const COLORES = [
        'primario'   => '#DC2626', // Rojo antifa
        'secundario' => '#1F2937', // Gris oscuro/negro
        'acento'     => '#FBBF24', // Amarillo/dorado
        'fondo'      => '#F9FAFB', // Gris muy claro
        'texto'      => '#111827', // Negro
    ];

    /**
     * Módulos a activar
     */
    private const MODULOS_ACTIVOS = [
        'eventos',
        'comunidades',
        'red_social',
        'chat_grupos',
        'campanias',
        'chat_interno',
        'foros',
        'participacion',
        'multimedia',
        'encuestas',
        'colectivos',
    ];

    /**
     * Instancia singleton
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'agregar_menu_admin']);
        add_action('admin_init', [$this, 'procesar_setup']);

        // Cargar estilos CSS en el frontend si el setup está completado
        if (get_option('herri_setup_completado', false)) {
            add_action('wp_enqueue_scripts', [$this, 'cargar_estilos_frontend'], 100);
        }
    }

    /**
     * Carga los estilos CSS personalizados en el frontend
     */
    public function cargar_estilos_frontend() {
        wp_enqueue_style(
            'herri-antifaxistak-styles',
            FLAVOR_CHAT_IA_URL . 'assets/css/herri-antifaxistak.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );
    }

    /**
     * Agrega el menú de administración
     */
    public function agregar_menu_admin() {
        add_submenu_page(
            'flavor-chat-ia',
            'Setup Herri Antifaxistak',
            '🔧 Setup Herri',
            'manage_options',
            'herri-setup',
            [$this, 'render_pagina_setup']
        );
    }

    /**
     * Renderiza la página de setup
     */
    public function render_pagina_setup() {
        $configuracion_completada = get_option('herri_setup_completado', false);
        ?>
        <div class="wrap">
            <h1>🏴 Configuración de Herri Antifaxistak</h1>

            <?php if ($configuracion_completada): ?>
                <div class="notice notice-success">
                    <p><strong>✅ Configuración completada.</strong> El sitio está listo.</p>
                </div>
                <p>
                    <a href="<?php echo home_url(); ?>" class="button button-primary" target="_blank">
                        Ver sitio →
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=herri-setup&action=reset'); ?>"
                       class="button"
                       onclick="return confirm('¿Seguro que quieres reiniciar la configuración?');">
                        🔄 Reiniciar configuración
                    </a>
                </p>
            <?php else: ?>
                <div class="card" style="max-width: 800px; padding: 2rem;">
                    <h2>Este asistente configurará:</h2>
                    <ul style="list-style: disc; margin-left: 2rem;">
                        <li><strong>Módulos:</strong> Eventos, Comunidades, Red Social, Chat, Campañas, Foros, Participación...</li>
                        <li><strong>Páginas:</strong> Inicio, Nor Gara, Albisteak, Ekitaldiak, Kanpainak, Kontaktua, Nire Ataria</li>
                        <li><strong>Colores:</strong> Paleta antifascista (rojo, negro, amarillo)</li>
                        <li><strong>Idiomas:</strong> Bilingüe Euskera/Castellano</li>
                    </ul>

                    <h3>Paleta de colores:</h3>
                    <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                        <div style="width: 60px; height: 60px; background: #DC2626; border-radius: 8px;" title="Primario"></div>
                        <div style="width: 60px; height: 60px; background: #1F2937; border-radius: 8px;" title="Secundario"></div>
                        <div style="width: 60px; height: 60px; background: #FBBF24; border-radius: 8px;" title="Acento"></div>
                    </div>

                    <form method="post" action="">
                        <?php wp_nonce_field('herri_setup_action', 'herri_setup_nonce'); ?>
                        <input type="hidden" name="herri_action" value="ejecutar_setup">
                        <p>
                            <button type="submit" class="button button-primary button-hero">
                                🚀 Ejecutar configuración
                            </button>
                        </p>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Procesa el setup
     */
    public function procesar_setup() {
        // Reset
        if (isset($_GET['page']) && $_GET['page'] === 'herri-setup' && isset($_GET['action']) && $_GET['action'] === 'reset') {
            delete_option('herri_setup_completado');
            wp_redirect(admin_url('admin.php?page=herri-setup'));
            exit;
        }

        // Setup
        if (!isset($_POST['herri_action']) || $_POST['herri_action'] !== 'ejecutar_setup') {
            return;
        }

        if (!wp_verify_nonce($_POST['herri_setup_nonce'], 'herri_setup_action')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // Ejecutar configuración
        $this->activar_modulos();
        $this->configurar_colores();
        $this->crear_paginas();
        $this->crear_contenido_demo();
        $this->configurar_menus();
        $this->configurar_pagina_inicio();

        update_option('herri_setup_completado', true);

        // Flush rewrite rules
        flush_rewrite_rules();

        wp_redirect(admin_url('admin.php?page=herri-setup&setup=complete'));
        exit;
    }

    /**
     * Activa los módulos necesarios
     */
    private function activar_modulos() {
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $configuracion['active_modules'] = self::MODULOS_ACTIVOS;
        update_option('flavor_chat_ia_settings', $configuracion);

        if (class_exists('Flavor_Chat_Module_Loader')) {
            Flavor_Chat_Module_Loader::invalidate_active_modules_cache();
        }
    }

    /**
     * Configura los colores del tema
     */
    private function configurar_colores() {
        $colores_tema = [
            'color_primario'   => self::COLORES['primario'],
            'color_secundario' => self::COLORES['secundario'],
            'color_acento'     => self::COLORES['acento'],
            'color_fondo'      => self::COLORES['fondo'],
            'color_texto'      => self::COLORES['texto'],
        ];
        update_option('flavor_theme_colors', $colores_tema);

        $configuracion = get_option('flavor_chat_ia_settings', []);
        $configuracion['widget_color'] = self::COLORES['primario'];
        $configuracion['theme_colors'] = $colores_tema;
        update_option('flavor_chat_ia_settings', $configuracion);
    }

    /**
     * Crea las páginas del sitio
     */
    private function crear_paginas() {
        $paginas = $this->get_definicion_paginas();

        foreach ($paginas as $pagina) {
            $this->crear_pagina($pagina);
        }
    }

    /**
     * Crea una página individual
     */
    private function crear_pagina($datos) {
        $pagina_existente = get_page_by_path($datos['slug']);
        if ($pagina_existente) {
            wp_update_post([
                'ID'           => $pagina_existente->ID,
                'post_content' => $datos['content'],
                'post_title'   => $datos['title'],
            ]);
            return $pagina_existente->ID;
        }

        $parent_id = 0;
        if (!empty($datos['parent'])) {
            $pagina_padre = get_page_by_path($datos['parent']);
            if ($pagina_padre) {
                $parent_id = $pagina_padre->ID;
            }
        }

        $id_pagina = wp_insert_post([
            'post_title'     => $datos['title'],
            'post_name'      => $datos['slug'],
            'post_content'   => $datos['content'],
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_parent'    => $parent_id,
            'post_author'    => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ]);

        return $id_pagina;
    }

    /**
     * Configura la página de inicio
     */
    private function configurar_pagina_inicio() {
        $pagina_inicio = get_page_by_path('hasiera');
        if ($pagina_inicio) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $pagina_inicio->ID);
        }

        // Página de blog
        $pagina_blog = get_page_by_path('albisteak');
        if ($pagina_blog) {
            update_option('page_for_posts', $pagina_blog->ID);
        }
    }

    /**
     * Devuelve la definición de páginas
     */
    private function get_definicion_paginas() {
        $c = self::COLORES;

        return [
            // =============================================
            // HASIERA / INICIO
            // =============================================
            [
                'title' => 'Hasiera',
                'slug'  => 'hasiera',
                'parent' => '',
                'content' => $this->get_contenido_inicio($c),
            ],

            // =============================================
            // NOR GARA / QUIÉNES SOMOS
            // =============================================
            [
                'title' => 'Nor Gara',
                'slug'  => 'nor-gara',
                'parent' => '',
                'content' => $this->get_contenido_nor_gara($c),
            ],

            // =============================================
            // ALBISTEAK / NOTICIAS
            // =============================================
            [
                'title' => 'Albisteak',
                'slug'  => 'albisteak',
                'parent' => '',
                'content' => $this->get_contenido_albisteak($c),
            ],

            // =============================================
            // EKITALDIAK / EVENTOS
            // =============================================
            [
                'title' => 'Ekitaldiak',
                'slug'  => 'ekitaldiak',
                'parent' => '',
                'content' => $this->get_contenido_ekitaldiak($c),
            ],

            // =============================================
            // KANPAINAK / CAMPAÑAS
            // =============================================
            [
                'title' => 'Kanpainak',
                'slug'  => 'kanpainak',
                'parent' => '',
                'content' => $this->get_contenido_kanpainak($c),
            ],

            // =============================================
            // KONTAKTUA / CONTACTO
            // =============================================
            [
                'title' => 'Kontaktua',
                'slug'  => 'kontaktua',
                'parent' => '',
                'content' => $this->get_contenido_kontaktua($c),
            ],

            // =============================================
            // NIRE ATARIA / MI PORTAL
            // =============================================
            [
                'title' => 'Nire Ataria',
                'slug'  => 'nire-ataria',
                'parent' => '',
                'content' => $this->get_contenido_nire_ataria($c),
            ],
        ];
    }

    /**
     * Contenido página inicio
     */
    private function get_contenido_inicio($c) {
        return '
<div class="herri-page herri-inicio">

<!-- HERO -->
<section style="background: linear-gradient(135deg, ' . $c['secundario'] . ' 0%, #374151 100%); padding: 5rem 1.5rem; text-align: center; color: white;">
    <div style="max-width: 900px; margin: 0 auto;">
        <h1 style="font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 800; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 2px;">
            HERRI ANTIFAXISTAK
        </h1>
        <p style="font-size: clamp(1rem, 2.5vw, 1.4rem); margin-bottom: 2rem; opacity: 0.9;">
            Nafarroa antifaxista · Gure herriak defendatzen<br>
            <em style="font-size: 0.9em;">Navarra antifascista · Defendiendo nuestros pueblos</em>
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="/nor-gara/" style="background: ' . $c['primario'] . '; color: white; padding: 0.875rem 1.75rem; text-decoration: none; font-weight: 700; border-radius: 4px; text-transform: uppercase; font-size: 0.9rem;">
                Ezagutu gaitzazu
            </a>
            <a href="/ekitaldiak/" style="background: ' . $c['acento'] . '; color: ' . $c['secundario'] . '; padding: 0.875rem 1.75rem; text-decoration: none; font-weight: 700; border-radius: 4px; text-transform: uppercase; font-size: 0.9rem;">
                Ekitaldiak
            </a>
        </div>
    </div>
</section>

<!-- ÚLTIMAS NOTICIAS -->
<section style="padding: 4rem 1.5rem; background: ' . $c['fondo'] . ';">
    <div style="max-width: 1100px; margin: 0 auto;">
        <h2 style="text-align: center; font-size: 1.75rem; margin-bottom: 0.5rem; color: ' . $c['secundario'] . ';">
            Azken Albisteak
        </h2>
        <p style="text-align: center; color: #6B7280; margin-bottom: 2.5rem;">Últimas noticias</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            [flavor module="posts" view="grid" limit="3" columns="3"]
        </div>

        <p style="text-align: center; margin-top: 2rem;">
            <a href="/albisteak/" style="color: ' . $c['primario'] . '; font-weight: 600; text-decoration: none;">
                Albiste guztiak ikusi / Ver todas →
            </a>
        </p>
    </div>
</section>

<!-- PRÓXIMOS EVENTOS -->
<section style="padding: 4rem 1.5rem; background: white;">
    <div style="max-width: 1100px; margin: 0 auto;">
        <h2 style="text-align: center; font-size: 1.75rem; margin-bottom: 0.5rem; color: ' . $c['secundario'] . ';">
            Hurrengo Ekitaldiak
        </h2>
        <p style="text-align: center; color: #6B7280; margin-bottom: 2.5rem;">Próximos eventos</p>

        [flavor module="eventos" view="listado" limit="3" columns="3"]

        <p style="text-align: center; margin-top: 2rem;">
            <a href="/ekitaldiak/" style="color: ' . $c['primario'] . '; font-weight: 600; text-decoration: none;">
                Ekitaldi guztiak / Todos los eventos →
            </a>
        </p>
    </div>
</section>

<!-- CAMPAÑAS ACTIVAS -->
<section style="padding: 4rem 1.5rem; background: ' . $c['secundario'] . '; color: white;">
    <div style="max-width: 1100px; margin: 0 auto;">
        <h2 style="text-align: center; font-size: 1.75rem; margin-bottom: 0.5rem;">
            Kanpaina Aktiboak
        </h2>
        <p style="text-align: center; opacity: 0.8; margin-bottom: 2.5rem;">Campañas activas</p>

        [flavor module="campanias" view="listado" limit="2" columns="2"]

        <p style="text-align: center; margin-top: 2rem;">
            <a href="/kanpainak/" style="color: ' . $c['acento'] . '; font-weight: 600; text-decoration: none;">
                Kanpaina guztiak / Todas las campañas →
            </a>
        </p>
    </div>
</section>

<!-- CTA UNIRSE -->
<section style="padding: 4rem 1.5rem; background: ' . $c['primario'] . '; color: white; text-align: center;">
    <div style="max-width: 700px; margin: 0 auto;">
        <h2 style="font-size: 2rem; margin-bottom: 1rem;">Batu gurekin!</h2>
        <p style="font-size: 1.1rem; margin-bottom: 2rem; opacity: 0.95;">
            Parte hartu gure komunitatean · Únete a nuestra comunidad
        </p>
        <a href="/nire-ataria/" style="background: white; color: ' . $c['primario'] . '; padding: 1rem 2.5rem; text-decoration: none; font-weight: 700; border-radius: 4px; display: inline-block;">
            Erregistratu / Sartu
        </a>
    </div>
</section>

</div>';
    }

    /**
     * Contenido página Nor Gara
     */
    private function get_contenido_nor_gara($c) {
        return '
<div class="herri-page herri-nor-gara">

<!-- HERO -->
<section style="background: ' . $c['secundario'] . '; padding: 4rem 1.5rem; color: white; text-align: center;">
    <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Nor Gara</h1>
    <p style="opacity: 0.9; font-size: 1.1rem;">Quiénes Somos · Nafarroako herri antifaxista</p>
</section>

<!-- CONTENIDO -->
<section style="padding: 4rem 1.5rem; max-width: 900px; margin: 0 auto;">

    <h2 style="color: ' . $c['primario'] . '; margin-bottom: 1.5rem; border-left: 4px solid ' . $c['primario'] . '; padding-left: 1rem;">Manifestua / Manifiesto</h2>

    <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem;">
        <strong>Herri Antifaxistak</strong> Nafarroako kolektibo antifaxista bat da,
        faxismoaren, arrazismoaren eta diskriminazio mota guztien aurka borrokatzen duena.
    </p>
    <p style="font-size: 1rem; line-height: 1.8; margin-bottom: 1.5rem; color: #4B5563; font-style: italic;">
        Herri Antifaxistak es un colectivo antifascista de Navarra que lucha contra
        el fascismo, el racismo y todas las formas de discriminación.
    </p>

    <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 2rem;">
        Gure helburua da herrietan kontzientzia antifaxista sortzea,
        ekintza zuzenaren bidez gure komunitateak defendatzea eta
        elkartasun sareak eraikitzea.
    </p>

    <h2 style="color: ' . $c['primario'] . '; margin-bottom: 1.5rem; margin-top: 3rem; border-left: 4px solid ' . $c['primario'] . '; padding-left: 1rem;">
        Gure Printziopioak / Nuestros Principios
    </h2>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
        <div style="background: ' . $c['fondo'] . '; padding: 1.5rem; border-radius: 8px; border-left: 3px solid ' . $c['primario'] . ';">
            <h3 style="color: ' . $c['secundario'] . '; margin-bottom: 0.5rem;">✊ Antifaxismoa</h3>
            <p style="margin: 0; color: #4B5563;">Faxismo mota guztien aurka / Contra toda forma de fascismo</p>
        </div>
        <div style="background: ' . $c['fondo'] . '; padding: 1.5rem; border-radius: 8px; border-left: 3px solid ' . $c['primario'] . ';">
            <h3 style="color: ' . $c['secundario'] . '; margin-bottom: 0.5rem;">🌍 Antiarrazismoa</h3>
            <p style="margin: 0; color: #4B5563;">Arrazakeria eta xenofobiaren aurka / Contra el racismo y la xenofobia</p>
        </div>
        <div style="background: ' . $c['fondo'] . '; padding: 1.5rem; border-radius: 8px; border-left: 3px solid ' . $c['primario'] . ';">
            <h3 style="color: ' . $c['secundario'] . '; margin-bottom: 0.5rem;">♀️ Feminismoa</h3>
            <p style="margin: 0; color: #4B5563;">Emakumeen eskubideen alde / Por los derechos de las mujeres</p>
        </div>
        <div style="background: ' . $c['fondo'] . '; padding: 1.5rem; border-radius: 8px; border-left: 3px solid ' . $c['primario'] . ';">
            <h3 style="color: ' . $c['secundario'] . '; margin-bottom: 0.5rem;">🏳️‍🌈 LGBTIQ+</h3>
            <p style="margin: 0; color: #4B5563;">Pertsona guztien duintasuna / Dignidad de todas las personas</p>
        </div>
        <div style="background: ' . $c['fondo'] . '; padding: 1.5rem; border-radius: 8px; border-left: 3px solid ' . $c['primario'] . ';">
            <h3 style="color: ' . $c['secundario'] . '; margin-bottom: 0.5rem;">🤝 Internazionalismoa</h3>
            <p style="margin: 0; color: #4B5563;">Herri zapalduekin elkartasuna / Solidaridad con los pueblos oprimidos</p>
        </div>
        <div style="background: ' . $c['fondo'] . '; padding: 1.5rem; border-radius: 8px; border-left: 3px solid ' . $c['primario'] . ';">
            <h3 style="color: ' . $c['secundario'] . '; margin-bottom: 0.5rem;">🌱 Ekologia</h3>
            <p style="margin: 0; color: #4B5563;">Lurraren defentsa / Defensa de la tierra</p>
        </div>
    </div>

    <h2 style="color: ' . $c['primario'] . '; margin-bottom: 1.5rem; border-left: 4px solid ' . $c['primario'] . '; padding-left: 1rem;">
        Nola Parte Hartu / Cómo Participar
    </h2>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        <a href="/ekitaldiak/" style="background: white; padding: 2rem; border-radius: 8px; text-align: center; text-decoration: none; border: 2px solid #E5E7EB; transition: all 0.2s;">
            <span style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">📅</span>
            <h3 style="color: ' . $c['secundario'] . '; margin-bottom: 0.25rem;">Ekitaldiak</h3>
            <p style="color: #6B7280; margin: 0; font-size: 0.9rem;">Eventos</p>
        </a>
        <a href="/kanpainak/" style="background: white; padding: 2rem; border-radius: 8px; text-align: center; text-decoration: none; border: 2px solid #E5E7EB; transition: all 0.2s;">
            <span style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">✊</span>
            <h3 style="color: ' . $c['secundario'] . '; margin-bottom: 0.25rem;">Kanpainak</h3>
            <p style="color: #6B7280; margin: 0; font-size: 0.9rem;">Campañas</p>
        </a>
        <a href="/nire-ataria/" style="background: white; padding: 2rem; border-radius: 8px; text-align: center; text-decoration: none; border: 2px solid #E5E7EB; transition: all 0.2s;">
            <span style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">💬</span>
            <h3 style="color: ' . $c['secundario'] . '; margin-bottom: 0.25rem;">Komunikazioa</h3>
            <p style="color: #6B7280; margin: 0; font-size: 0.9rem;">Comunicación</p>
        </a>
    </div>
</section>

<!-- CTA -->
<section style="background: ' . $c['primario'] . '; padding: 3rem 1.5rem; text-align: center; color: white;">
    <h2 style="margin-bottom: 1rem;">Prest zaude? ¿Preparado/a?</h2>
    <a href="/nire-ataria/" style="background: white; color: ' . $c['primario'] . '; padding: 1rem 2rem; text-decoration: none; font-weight: 700; border-radius: 4px; display: inline-block;">
        Erregistratu orain / Regístrate ahora
    </a>
</section>

</div>';
    }

    /**
     * Contenido página Albisteak (Noticias)
     */
    private function get_contenido_albisteak($c) {
        return '
<div class="herri-page herri-albisteak">

<section style="background: ' . $c['secundario'] . '; padding: 3rem 1.5rem; color: white; text-align: center;">
    <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Albisteak</h1>
    <p style="opacity: 0.9;">Noticias y comunicados</p>
</section>

<section style="padding: 3rem 1.5rem; max-width: 1100px; margin: 0 auto;">
    [flavor module="posts" view="listado" limit="12" columns="3"]
</section>

</div>';
    }

    /**
     * Contenido página Ekitaldiak (Eventos)
     */
    private function get_contenido_ekitaldiak($c) {
        return '
<div class="herri-page herri-ekitaldiak">

<section style="background: ' . $c['secundario'] . '; padding: 3rem 1.5rem; color: white; text-align: center;">
    <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Ekitaldiak</h1>
    <p style="opacity: 0.9;">Eventos · Manifestazioak, hitzaldiak, bilerak</p>
</section>

<section style="padding: 3rem 1.5rem; max-width: 1100px; margin: 0 auto;">
    <h2 style="color: ' . $c['primario'] . '; margin-bottom: 2rem; border-left: 4px solid ' . $c['primario'] . '; padding-left: 1rem;">
        Hurrengo Ekitaldiak / Próximos Eventos
    </h2>
    [flavor module="eventos" view="listado" limit="9" columns="3"]
</section>

<section style="padding: 3rem 1.5rem; background: ' . $c['fondo'] . ';">
    <div style="max-width: 1100px; margin: 0 auto;">
        <h2 style="color: ' . $c['secundario'] . '; margin-bottom: 2rem; border-left: 4px solid ' . $c['primario'] . '; padding-left: 1rem;">
            Egutegia / Calendario
        </h2>
        [flavor module="eventos" view="calendario"]
    </div>
</section>

</div>';
    }

    /**
     * Contenido página Kanpainak (Campañas)
     */
    private function get_contenido_kanpainak($c) {
        return '
<div class="herri-page herri-kanpainak">

<section style="background: ' . $c['primario'] . '; padding: 3rem 1.5rem; color: white; text-align: center;">
    <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Kanpainak</h1>
    <p style="opacity: 0.9;">Campañas · Ekintza kolektiboak</p>
</section>

<section style="padding: 3rem 1.5rem; max-width: 1100px; margin: 0 auto;">
    <h2 style="color: ' . $c['secundario'] . '; margin-bottom: 2rem; border-left: 4px solid ' . $c['primario'] . '; padding-left: 1rem;">
        Kanpaina Aktiboak / Campañas Activas
    </h2>
    [flavor module="campanias" view="listado" limit="6" columns="2"]
</section>

<section style="padding: 3rem 1.5rem; background: ' . $c['fondo'] . ';">
    <div style="max-width: 1100px; margin: 0 auto;">
        <h2 style="color: ' . $c['secundario'] . '; margin-bottom: 2rem; border-left: 4px solid ' . $c['primario'] . '; padding-left: 1rem;">
            Parte Hartu / Participa
        </h2>
        [propuestas_activas]
        [votacion_activa]
    </div>
</section>

</div>';
    }

    /**
     * Contenido página Kontaktua (Contacto)
     */
    private function get_contenido_kontaktua($c) {
        return '
<div class="herri-page herri-kontaktua">

<section style="background: ' . $c['secundario'] . '; padding: 3rem 1.5rem; color: white; text-align: center;">
    <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Kontaktua</h1>
    <p style="opacity: 0.9;">Contacto · Jarri gurekin harremanetan</p>
</section>

<section style="padding: 4rem 1.5rem; max-width: 900px; margin: 0 auto;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 3rem;">

        <div>
            <h2 style="color: ' . $c['primario'] . '; margin-bottom: 1.5rem;">Sare Sozialak / Redes</h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: ' . $c['fondo'] . '; border-radius: 8px;">
                    <span style="font-size: 1.5rem;">📧</span>
                    <div>
                        <strong>Email</strong><br>
                        <span style="color: #6B7280;">herriantifaxistak@riseup.net</span>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: ' . $c['fondo'] . '; border-radius: 8px;">
                    <span style="font-size: 1.5rem;">📱</span>
                    <div>
                        <strong>Telegram</strong><br>
                        <span style="color: #6B7280;">@herriantifaxistak</span>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: ' . $c['fondo'] . '; border-radius: 8px;">
                    <span style="font-size: 1.5rem;">🐘</span>
                    <div>
                        <strong>Mastodon</strong><br>
                        <span style="color: #6B7280;">@herriantifaxistak@kolektiva.social</span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <h2 style="color: ' . $c['primario'] . '; margin-bottom: 1.5rem;">Segurtasuna / Seguridad</h2>
            <div style="padding: 1.5rem; background: #FEF3C7; border-radius: 8px; border-left: 4px solid ' . $c['acento'] . ';">
                <h3 style="margin-bottom: 0.5rem; color: ' . $c['secundario'] . ';">⚠️ Kontuan izan</h3>
                <p style="font-size: 0.95rem; margin: 0; color: #92400E;">
                    Komunikazio guztiak zifratutako kanalak erabiliz egitea gomendatzen dugu.
                    Ez bidali informazio sentikorra zifratu gabeko emailez.
                </p>
                <p style="font-size: 0.9rem; margin-top: 1rem; color: #92400E; font-style: italic;">
                    Recomendamos usar canales cifrados para comunicaciones sensibles.
                </p>
            </div>

            <div style="margin-top: 2rem; padding: 1.5rem; background: ' . $c['fondo'] . '; border-radius: 8px;">
                <h3 style="margin-bottom: 0.5rem; color: ' . $c['secundario'] . ';">🔐 Signal / Wire</h3>
                <p style="font-size: 0.95rem; margin: 0; color: #6B7280;">
                    Mezularitza segurua nahi baduzu, Signal edo Wire erabil dezakezu.
                </p>
            </div>
        </div>
    </div>
</section>

</div>';
    }

    /**
     * Contenido página Nire Ataria (Mi Portal)
     */
    private function get_contenido_nire_ataria($c) {
        return '
<div class="herri-page herri-nire-ataria">

[flavor_mi_portal
    mostrar_tabs="yes"
    tabs="hasiera,komunikazioa,ekitaldiak,parte_hartzea,kanpainak,profila"
    mostrar_actividad="yes"
    mostrar_notificaciones="yes"
]

</div>';
    }

    /**
     * Crea contenido de demostración
     */
    private function crear_contenido_demo() {
        // Crear categorías
        $categoria_salaketak = wp_create_category('Salaketak');
        $categoria_ekintzak = wp_create_category('Ekintzak');
        $categoria_komunikatuak = wp_create_category('Komunikatuak');

        $posts_demo = [
            [
                'title' => 'Manifestazioa larunbatean faxismoaren aurka',
                'content' => '<p>Larunbat honetan Gazteluko Plazan elkartuko gara faxismoaren igoeraren aurka.</p>
<p>Manifestazioa 12:00etan hasiko da eta alde zaharrean zehar egingo dugu ibilbidea.</p>
<p><strong>Ez dute pasako! Gora herri antifaxista!</strong></p>
<hr>
<p><em>Este sábado nos concentramos en la Plaza del Castillo para denunciar el auge del fascismo.</em></p>',
                'category' => $categoria_ekintzak,
            ],
            [
                'title' => 'Salaketa: pintada naziak Barañainen',
                'content' => '<p>Barañaingo auzoan ikur naziekin pintadak agertu dira.</p>
<p>Herri Antifaxistak-etik gorroto adierazpen hauek gogorki gaitzesten ditugu.</p>
<p>Pintadak edo ekintza faxistak ikusten badituzu, jarri gurekin harremanetan dokumentatzeko.</p>
<hr>
<p><em>Denunciamos la aparición de pintadas con simbología nazi en Barañáin.</em></p>',
                'category' => $categoria_salaketak,
            ],
            [
                'title' => 'Komunikatua: Elkartasuna migratzaileekin',
                'content' => '<p>Herri Antifaxistak-etik gure elkartasuna adierazi nahi diegu migratzaile guztiei.</p>
<p>Muga-politikak gaitzesten ditugu eta CIEak berehala ixtea exijitzen dugu.</p>
<p><strong>Inor ez da ilegala.</strong></p>
<hr>
<p><em>Expresamos nuestra solidaridad con todas las personas migrantes y exigimos el cierre de los CIE.</em></p>',
                'category' => $categoria_komunikatuak,
            ],
        ];

        foreach ($posts_demo as $post_data) {
            $existente = get_page_by_title($post_data['title'], OBJECT, 'post');
            if ($existente) {
                continue;
            }

            wp_insert_post([
                'post_title'   => $post_data['title'],
                'post_content' => $post_data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'post',
                'post_author'  => get_current_user_id(),
                'post_category' => [$post_data['category']],
            ]);
        }
    }

    /**
     * Configura los menús de navegación
     */
    private function configurar_menus() {
        $nombre_menu = 'Herri Menu Nagusia';
        $menu_existente = wp_get_nav_menu_object($nombre_menu);

        if ($menu_existente) {
            $menu_id = $menu_existente->term_id;
        } else {
            $menu_id = wp_create_nav_menu($nombre_menu);
        }

        if (is_wp_error($menu_id)) {
            return;
        }

        // Limpiar items existentes
        $items_menu = wp_get_nav_menu_items($menu_id);
        if ($items_menu) {
            foreach ($items_menu as $item) {
                wp_delete_post($item->ID, true);
            }
        }

        // Añadir items del menú (bilingüe)
        $items = [
            ['title' => 'Hasiera', 'url' => home_url('/hasiera/'), 'order' => 1],
            ['title' => 'Nor Gara', 'url' => home_url('/nor-gara/'), 'order' => 2],
            ['title' => 'Albisteak', 'url' => home_url('/albisteak/'), 'order' => 3],
            ['title' => 'Ekitaldiak', 'url' => home_url('/ekitaldiak/'), 'order' => 4],
            ['title' => 'Kanpainak', 'url' => home_url('/kanpainak/'), 'order' => 5],
            ['title' => 'Kontaktua', 'url' => home_url('/kontaktua/'), 'order' => 6],
            ['title' => 'Nire Ataria', 'url' => home_url('/nire-ataria/'), 'order' => 7],
        ];

        foreach ($items as $item) {
            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title'  => $item['title'],
                'menu-item-url'    => $item['url'],
                'menu-item-status' => 'publish',
                'menu-item-position' => $item['order'],
            ]);
        }

        // Asignar a ubicación del tema
        $locations = get_theme_mod('nav_menu_locations', []);
        $locations['primary'] = $menu_id;
        $locations['main'] = $menu_id;
        $locations['header'] = $menu_id;
        $locations['navigation'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
}

// Inicializar
Herri_Antifaxistak_Setup::get_instance();

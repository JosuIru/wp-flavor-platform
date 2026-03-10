<?php
/**
 * Generador de datos de demostración
 *
 * Crea usuarios, productos de marketplace, servicios de banco de tiempo,
 * grupos de consumo y talleres con datos de ejemplo.
 *
 * @package Flavor_Chat_IA
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Demo_Data_Generator {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Usuarios de demo
     */
    private $demo_users = [
        [
            'username' => 'maria_cosmetica',
            'email' => 'maria@demo.local',
            'display_name' => 'María García',
            'business' => 'Cosmética Natural Estella',
            'category' => 'Belleza y Cuidado Personal',
            'location' => 'Estella-Lizarra',
            'description' => 'Elaboración artesanal de jabones, cremas y productos de cosmética natural con ingredientes locales.'
        ],
        [
            'username' => 'carlos_carpintero',
            'email' => 'carlos@demo.local',
            'display_name' => 'Carlos Martínez',
            'business' => 'Carpintería Artesana Ayegui',
            'category' => 'Artesanía y Oficios',
            'location' => 'Ayegui',
            'description' => 'Carpintería tradicional especializada en muebles a medida y restauración de mobiliario antiguo.'
        ],
        [
            'username' => 'ana_diseno',
            'email' => 'ana@demo.local',
            'display_name' => 'Ana López',
            'business' => 'Diseño Gráfico Local',
            'category' => 'Servicios Profesionales',
            'location' => 'Estella-Lizarra',
            'description' => 'Diseño gráfico, branding e identidad visual para pequeños negocios y emprendedores de la comarca.'
        ],
        [
            'username' => 'pedro_huerta',
            'email' => 'pedro@demo.local',
            'display_name' => 'Pedro Sánchez',
            'business' => 'Huerta Ecológica Villatuerta',
            'category' => 'Alimentación y Agricultura',
            'location' => 'Villatuerta',
            'description' => 'Producción ecológica de verduras y hortalizas de temporada. Venta directa y grupos de consumo.'
        ],
        [
            'username' => 'laura_asesora',
            'email' => 'laura@demo.local',
            'display_name' => 'Laura Fernández',
            'business' => 'Asesoría Fiscal Rural',
            'category' => 'Servicios Profesionales',
            'location' => 'Estella-Lizarra',
            'description' => 'Asesoría fiscal y contable especializada en autónomos, cooperativas y pequeñas empresas rurales.'
        ],
    ];

    /**
     * Productos de marketplace
     */
    private $marketplace_items = [
        [
            'title' => 'Jabón artesanal de lavanda',
            'price' => 8,
            'category' => 'Cosmética Natural',
            'user_key' => 'maria_cosmetica',
            'description' => 'Jabón elaborado con aceite de oliva virgen extra de la zona y esencia de lavanda del Pirineo. 100% natural, sin químicos. Ideal para pieles sensibles.',
            'type' => 'producto'
        ],
        [
            'title' => 'Tabla de cortar madera de nogal',
            'price' => 45,
            'category' => 'Artesanía',
            'user_key' => 'carlos_carpintero',
            'description' => 'Tabla de cortar artesanal fabricada con madera de nogal local. Tratada con aceite alimentario. Medidas: 40x25cm. Pieza única.',
            'type' => 'producto'
        ],
        [
            'title' => 'Pack diseño logo + tarjetas',
            'price' => 150,
            'category' => 'Servicios',
            'user_key' => 'ana_diseno',
            'description' => 'Diseño de logotipo profesional con 3 propuestas + diseño de tarjetas de visita. Incluye archivos en todos los formatos. Ideal para nuevos negocios.',
            'type' => 'servicio'
        ],
        [
            'title' => 'Cesta verduras de temporada',
            'price' => 25,
            'category' => 'Alimentación',
            'user_key' => 'pedro_huerta',
            'description' => 'Cesta semanal con 5-6 kg de verduras ecológicas de temporada. Recogida en Villatuerta o puntos de la comarca. Producto fresco, recién cosechado.',
            'type' => 'producto'
        ],
        [
            'title' => 'Miel de la Ribera',
            'price' => 12,
            'category' => 'Alimentación',
            'user_key' => 'pedro_huerta',
            'description' => 'Miel multifloral de la Ribera del Ega. Tarro de 500g. Producción artesanal de apicultor local colaborador.',
            'type' => 'producto'
        ],
        [
            'title' => 'Estantería a medida',
            'price' => 200,
            'category' => 'Artesanía',
            'user_key' => 'carlos_carpintero',
            'description' => 'Fabricación de estantería personalizada en madera maciza. Medidas y acabado a elegir. Presupuesto sin compromiso.',
            'type' => 'servicio'
        ],
        [
            'title' => 'Crema facial hidratante natural',
            'price' => 22,
            'category' => 'Cosmética Natural',
            'user_key' => 'maria_cosmetica',
            'description' => 'Crema facial con aceite de rosa mosqueta y manteca de karité. Sin parabenos ni conservantes artificiales. Tarro 50ml.',
            'type' => 'producto'
        ],
        [
            'title' => 'Asesoría fiscal inicial autónomos',
            'price' => 50,
            'category' => 'Servicios',
            'user_key' => 'laura_asesora',
            'description' => 'Sesión de asesoría de 1 hora para nuevos autónomos. Revisamos tu situación, obligaciones fiscales y optimización. Ideal para empezar con buen pie.',
            'type' => 'servicio'
        ],
    ];

    /**
     * Servicios banco de tiempo
     */
    private $banco_tiempo_services = [
        [
            'title' => 'Taller de cosmética natural',
            'hours' => 2,
            'user_key' => 'maria_cosmetica',
            'description' => 'Taller práctico donde aprenderás a hacer tu propio jabón o crema. Incluye materiales.',
            'category' => 'Formación'
        ],
        [
            'title' => 'Reparación de muebles',
            'hours' => 1,
            'user_key' => 'carlos_carpintero',
            'description' => 'Reparaciones pequeñas de muebles: patas, cajones, bisagras, etc. Precio por hora.',
            'category' => 'Hogar'
        ],
        [
            'title' => 'Diseño de logotipo básico',
            'hours' => 3,
            'user_key' => 'ana_diseno',
            'description' => 'Diseño de un logotipo sencillo para tu negocio o proyecto. Incluye 2 propuestas y archivos finales.',
            'category' => 'Diseño'
        ],
        [
            'title' => 'Asesoría fiscal básica',
            'hours' => 1,
            'user_key' => 'laura_asesora',
            'description' => 'Consulta sobre dudas fiscales, declaraciones, facturas, etc. Orientación general.',
            'category' => 'Asesoría'
        ],
        [
            'title' => 'Clase de huerto urbano',
            'hours' => 2,
            'user_key' => 'pedro_huerta',
            'description' => 'Aprende a cultivar tus propias verduras en casa o en un pequeño espacio. Teoría y práctica.',
            'category' => 'Formación'
        ],
    ];

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 100);
        add_action('admin_post_flavor_generate_demo_data', [$this, 'handle_generate_demo']);
        add_action('admin_post_flavor_cleanup_demo_data', [$this, 'handle_cleanup_demo']);
    }

    /**
     * Añadir menú de admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            'Demo Tejido Empresarial',
            '🎯 Demo Datos',
            'manage_options',
            'flavor-demo-data',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderizar página de admin
     */
    public function render_admin_page() {
        $demo_exists = $this->check_demo_exists();
        ?>
        <div class="wrap">
            <h1>Generador de Datos de Demostración</h1>

            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <h2>Datos de Ejemplo - Tejido Empresarial</h2>
                <p>Este generador crea datos de ejemplo para demostrar el ecosistema de emprendimiento:</p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><strong>5 usuarios</strong> emprendedores ficticios</li>
                    <li><strong>8 productos</strong> en el marketplace</li>
                    <li><strong>5 servicios</strong> en el banco de tiempo</li>
                    <li><strong>1 grupo de consumo</strong> con productores</li>
                    <li><strong>1 taller</strong> de formación</li>
                </ul>

                <?php if ($demo_exists): ?>
                    <div class="notice notice-success inline" style="margin: 15px 0;">
                        <p><strong>✓ Datos de demo ya generados</strong></p>
                    </div>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top: 15px;">
                        <input type="hidden" name="action" value="flavor_cleanup_demo_data">
                        <?php wp_nonce_field('flavor_demo_data', 'demo_nonce'); ?>
                        <button type="submit" class="button" onclick="return confirm('¿Eliminar todos los datos de demo?');">
                            🗑️ Limpiar datos de demo
                        </button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="flavor_generate_demo_data">
                        <?php wp_nonce_field('flavor_demo_data', 'demo_nonce'); ?>
                        <button type="submit" class="button button-primary button-hero" style="margin-top: 15px;">
                            🚀 Generar Datos de Demo
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <h3>Usuarios de Demo</h3>
                <table class="widefat" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Negocio</th>
                            <th>Ubicación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->demo_users as $user): ?>
                        <tr>
                            <td><code><?php echo esc_html($user['username']); ?></code></td>
                            <td><?php echo esc_html($user['business']); ?></td>
                            <td><?php echo esc_html($user['location']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 10px;"><small>Contraseña para todos: <code>demo2026</code></small></p>
            </div>
        </div>
        <?php
    }

    /**
     * Verificar si existen datos de demo
     */
    private function check_demo_exists() {
        $user = get_user_by('login', 'maria_cosmetica');
        return $user !== false;
    }

    /**
     * Generar datos de demo
     */
    public function handle_generate_demo() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('flavor_demo_data', 'demo_nonce');

        $results = [];

        // 1. Crear usuarios
        $user_ids = [];
        foreach ($this->demo_users as $user_data) {
            $user_id = $this->create_demo_user($user_data);
            if ($user_id) {
                $user_ids[$user_data['username']] = $user_id;
                $results[] = "✓ Usuario creado: {$user_data['username']}";
            }
        }

        // 2. Crear productos en marketplace
        foreach ($this->marketplace_items as $item) {
            if (isset($user_ids[$item['user_key']])) {
                $this->create_marketplace_item($item, $user_ids[$item['user_key']]);
                $results[] = "✓ Producto marketplace: {$item['title']}";
            }
        }

        // 3. Crear servicios banco de tiempo
        foreach ($this->banco_tiempo_services as $service) {
            if (isset($user_ids[$service['user_key']])) {
                $this->create_banco_tiempo_service($service, $user_ids[$service['user_key']]);
                $results[] = "✓ Servicio banco tiempo: {$service['title']}";
            }
        }

        // 4. Crear grupo de consumo
        $this->create_grupo_consumo($user_ids);
        $results[] = "✓ Grupo de consumo: Cesta de la Tierra Estella";

        // 5. Crear taller
        if (isset($user_ids['ana_diseno'])) {
            $this->create_taller($user_ids['ana_diseno']);
            $results[] = "✓ Taller: Marketing Digital para Pequeños Negocios";
        }

        // Guardar log
        update_option('flavor_demo_data_log', $results);

        wp_redirect(admin_url('admin.php?page=flavor-demo-data&generated=1'));
        exit;
    }

    /**
     * Crear usuario de demo
     */
    private function create_demo_user($data) {
        $existing = get_user_by('login', $data['username']);
        if ($existing) {
            return $existing->ID;
        }

        $user_id = wp_create_user(
            $data['username'],
            'demo2026',
            $data['email']
        );

        if (is_wp_error($user_id)) {
            return false;
        }

        wp_update_user([
            'ID' => $user_id,
            'display_name' => $data['display_name'],
            'description' => $data['description']
        ]);

        // Metadatos de emprendedor
        update_user_meta($user_id, 'business_name', $data['business']);
        update_user_meta($user_id, 'business_category', $data['category']);
        update_user_meta($user_id, 'location', $data['location']);
        update_user_meta($user_id, 'is_demo_user', true);
        update_user_meta($user_id, 'banco_tiempo_balance', 5); // 5 horas iniciales

        return $user_id;
    }

    /**
     * Crear item de marketplace
     */
    private function create_marketplace_item($item, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_marketplace_items';

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            // Usar post type si no hay tabla
            $post_id = wp_insert_post([
                'post_title' => $item['title'],
                'post_content' => $item['description'],
                'post_status' => 'publish',
                'post_type' => 'marketplace_item',
                'post_author' => $user_id
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'price', $item['price']);
                update_post_meta($post_id, 'category', $item['category']);
                update_post_meta($post_id, 'type', $item['type']);
                update_post_meta($post_id, 'is_demo', true);
            }
            return $post_id;
        }

        return $wpdb->insert($table, [
            'user_id' => $user_id,
            'title' => $item['title'],
            'description' => $item['description'],
            'price' => $item['price'],
            'category' => $item['category'],
            'type' => $item['type'],
            'status' => 'active',
            'is_demo' => 1,
            'created_at' => current_time('mysql')
        ]);
    }

    /**
     * Crear servicio de banco de tiempo
     */
    private function create_banco_tiempo_service($service, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_banco_tiempo_services';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            // Alternativa con post type
            $post_id = wp_insert_post([
                'post_title' => $service['title'],
                'post_content' => $service['description'],
                'post_status' => 'publish',
                'post_type' => 'banco_tiempo_service',
                'post_author' => $user_id
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'hours', $service['hours']);
                update_post_meta($post_id, 'category', $service['category']);
                update_post_meta($post_id, 'is_demo', true);
            }
            return $post_id;
        }

        return $wpdb->insert($table, [
            'user_id' => $user_id,
            'title' => $service['title'],
            'description' => $service['description'],
            'hours' => $service['hours'],
            'category' => $service['category'],
            'status' => 'active',
            'is_demo' => 1,
            'created_at' => current_time('mysql')
        ]);
    }

    /**
     * Crear grupo de consumo
     */
    private function create_grupo_consumo($user_ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_grupos_consumo';

        $group_data = [
            'name' => 'Cesta de la Tierra Estella',
            'description' => 'Grupo de consumo de productos ecológicos locales. Recogida semanal los jueves en Plaza de los Fueros.',
            'pickup_location' => 'Plaza de los Fueros, Estella',
            'cycle' => 'semanal',
            'cycle_day' => 'jueves',
            'is_demo' => 1
        ];

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            // Alternativa con options
            update_option('flavor_demo_grupo_consumo', $group_data);
            return true;
        }

        return $wpdb->insert($table, array_merge($group_data, [
            'created_at' => current_time('mysql')
        ]));
    }

    /**
     * Crear taller de formación
     */
    private function create_taller($instructor_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_talleres';

        $taller_data = [
            'title' => 'Marketing Digital para Pequeños Negocios',
            'description' => 'Aprende a promocionar tu negocio en redes sociales y Google. Taller práctico con ordenador. Dirigido a emprendedores de la comarca.',
            'instructor_id' => $instructor_id,
            'location' => 'Biblioteca de Estella',
            'date' => date('Y-m-d', strtotime('next Saturday')),
            'time' => '10:00',
            'duration' => 3,
            'max_participants' => 15,
            'current_participants' => 8,
            'price' => 0,
            'banco_tiempo_price' => 2,
            'is_demo' => 1
        ];

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            // Usar post type
            $post_id = wp_insert_post([
                'post_title' => $taller_data['title'],
                'post_content' => $taller_data['description'],
                'post_status' => 'publish',
                'post_type' => 'taller',
                'post_author' => $instructor_id
            ]);

            if ($post_id) {
                foreach ($taller_data as $key => $value) {
                    if (!in_array($key, ['title', 'description'])) {
                        update_post_meta($post_id, $key, $value);
                    }
                }
            }
            return $post_id;
        }

        return $wpdb->insert($table, array_merge($taller_data, [
            'created_at' => current_time('mysql')
        ]));
    }

    /**
     * Limpiar datos de demo
     */
    public function handle_cleanup_demo() {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('flavor_demo_data', 'demo_nonce');

        global $wpdb;

        // Eliminar usuarios de demo
        foreach ($this->demo_users as $user_data) {
            $user = get_user_by('login', $user_data['username']);
            if ($user) {
                wp_delete_user($user->ID);
            }
        }

        // Eliminar posts de demo
        $post_types = ['marketplace_item', 'banco_tiempo_service', 'taller'];
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'meta_key' => 'is_demo',
                'meta_value' => true,
                'posts_per_page' => -1
            ]);
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }
        }

        // Limpiar tablas personalizadas (si existen)
        $tables = [
            'flavor_marketplace_items',
            'flavor_banco_tiempo_services',
            'flavor_grupos_consumo',
            'flavor_talleres'
        ];
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $wpdb->query("DELETE FROM $full_table WHERE is_demo = 1");
        }

        // Limpiar options
        delete_option('flavor_demo_grupo_consumo');
        delete_option('flavor_demo_data_log');

        wp_redirect(admin_url('admin.php?page=flavor-demo-data&cleaned=1'));
        exit;
    }
}

// Inicializar
Flavor_Demo_Data_Generator::get_instance();

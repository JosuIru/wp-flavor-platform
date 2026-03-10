<?php
/**
 * Shortcodes para Contenido Federado
 *
 * Muestra contenido de la red en el frontend.
 *
 * @package FlavorChatIA\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Federation_Shortcodes {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Shortcodes de contenido federado
        add_shortcode('red_eventos', [$this, 'shortcode_eventos']);
        add_shortcode('red_cursos', [$this, 'shortcode_cursos']);
        add_shortcode('red_talleres', [$this, 'shortcode_talleres']);
        add_shortcode('red_marketplace', [$this, 'shortcode_marketplace']);
        add_shortcode('red_carpooling', [$this, 'shortcode_carpooling']);
        add_shortcode('red_espacios', [$this, 'shortcode_espacios']);
        add_shortcode('red_banco_tiempo', [$this, 'shortcode_banco_tiempo']);
        add_shortcode('red_productores', [$this, 'shortcode_productores']);

        // Shortcode genérico
        add_shortcode('red_contenido', [$this, 'shortcode_contenido']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Encola assets del frontend
     */
    public function enqueue_assets() {
        wp_register_style(
            'flavor-federation-frontend',
            false
        );
        wp_add_inline_style('flavor-federation-frontend', $this->get_inline_styles());
    }

    /**
     * Estilos inline para los shortcodes
     */
    private function get_inline_styles() {
        return '
        .flavor-fed-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .flavor-fed-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .flavor-fed-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .flavor-fed-card-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .flavor-fed-card-body {
            padding: 16px;
        }
        .flavor-fed-card-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px;
            color: #1a1a2e;
        }
        .flavor-fed-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }
        .flavor-fed-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: #f0f4f8;
            border-radius: 20px;
            font-size: 12px;
            color: #4a5568;
        }
        .flavor-fed-badge.primary {
            background: #e3f2fd;
            color: #1565c0;
        }
        .flavor-fed-badge.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .flavor-fed-card-desc {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 12px;
        }
        .flavor-fed-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }
        .flavor-fed-node {
            font-size: 11px;
            color: #999;
        }
        .flavor-fed-price {
            font-size: 18px;
            font-weight: 700;
            color: #2e7d32;
        }
        .flavor-fed-price.free {
            color: #1565c0;
        }
        .flavor-fed-empty {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .flavor-fed-empty-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .flavor-fed-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .flavor-fed-header h3 {
            margin: 0;
            font-size: 20px;
        }
        .flavor-fed-filters {
            display: flex;
            gap: 10px;
        }
        .flavor-fed-filter {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
        }
        .flavor-fed-filter:hover, .flavor-fed-filter.active {
            border-color: #667eea;
            color: #667eea;
        }
        ';
    }

    /**
     * Obtiene contenido federado de una tabla
     */
    private function get_federated_content($table_suffix, $args = []) {
        global $wpdb;

        $defaults = [
            'limite' => 12,
            'orden' => 'DESC',
            'order_by' => 'actualizado_en',
            'where' => [],
        ];
        $args = wp_parse_args($args, $defaults);

        $tabla = $wpdb->prefix . 'flavor_network_' . $table_suffix;
        $nodo_local = get_option('flavor_network_node_id', '');

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $where_sql = "nodo_id != %s AND visible_en_red = 1";
        $params = [$nodo_local];

        foreach ($args['where'] as $col => $val) {
            $where_sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['orden']) ?: 'actualizado_en DESC';
        $limite = absint($args['limite']);

        $query = $wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE {$where_sql} ORDER BY {$order_by} LIMIT %d",
            array_merge($params, [$limite])
        );

        return $wpdb->get_results($query);
    }

    /**
     * Renderiza mensaje vacío
     */
    private function render_empty($icon, $message) {
        return '<div class="flavor-fed-empty">
            <div class="flavor-fed-empty-icon">' . $icon . '</div>
            <p>' . esc_html($message) . '</p>
        </div>';
    }

    /**
     * Shortcode: Eventos de la red
     */
    public function shortcode_eventos($atts) {
        wp_enqueue_style('flavor-federation-frontend');

        $atts = shortcode_atts([
            'limite' => 12,
            'titulo' => __('Eventos de la Red', 'flavor-chat-ia'),
        ], $atts);

        $eventos = $this->get_federated_content('events', [
            'limite' => $atts['limite'],
            'order_by' => 'fecha_inicio',
            'orden' => 'ASC',
            'where' => ['estado' => 'activo'],
        ]);

        if (empty($eventos)) {
            return $this->render_empty('📅', __('No hay eventos de la red disponibles', 'flavor-chat-ia'));
        }

        ob_start();
        ?>
        <div class="flavor-fed-section">
            <div class="flavor-fed-header">
                <h3>📅 <?php echo esc_html($atts['titulo']); ?></h3>
            </div>
            <div class="flavor-fed-grid">
                <?php foreach ($eventos as $evento): ?>
                    <div class="flavor-fed-card">
                        <?php if (!empty($evento->imagen_url)): ?>
                            <img src="<?php echo esc_url($evento->imagen_url); ?>" alt="" class="flavor-fed-card-img">
                        <?php else: ?>
                            <div class="flavor-fed-card-img" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #fff;">📅</div>
                        <?php endif; ?>
                        <div class="flavor-fed-card-body">
                            <h4 class="flavor-fed-card-title"><?php echo esc_html($evento->titulo); ?></h4>
                            <div class="flavor-fed-card-meta">
                                <span class="flavor-fed-badge primary">
                                    📆 <?php echo esc_html(date_i18n('d M', strtotime($evento->fecha_inicio))); ?>
                                </span>
                                <?php if (!empty($evento->ubicacion)): ?>
                                    <span class="flavor-fed-badge">
                                        📍 <?php echo esc_html(wp_trim_words($evento->ubicacion, 3)); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($evento->es_online): ?>
                                    <span class="flavor-fed-badge success">🌐 Online</span>
                                <?php endif; ?>
                            </div>
                            <p class="flavor-fed-card-desc"><?php echo esc_html(wp_trim_words($evento->descripcion, 15)); ?></p>
                            <div class="flavor-fed-card-footer">
                                <span class="flavor-fed-node">🔗 <?php echo esc_html(substr($evento->nodo_id, 0, 8)); ?>...</span>
                                <?php if ($evento->precio > 0): ?>
                                    <span class="flavor-fed-price"><?php echo number_format($evento->precio, 2); ?> €</span>
                                <?php else: ?>
                                    <span class="flavor-fed-price free"><?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Cursos de la red
     */
    public function shortcode_cursos($atts) {
        wp_enqueue_style('flavor-federation-frontend');

        $atts = shortcode_atts([
            'limite' => 12,
            'titulo' => __('Cursos de la Red', 'flavor-chat-ia'),
            'modalidad' => '',
        ], $atts);

        $where = ['estado' => 'publicado'];
        if (!empty($atts['modalidad'])) {
            $where['modalidad'] = $atts['modalidad'];
        }

        $cursos = $this->get_federated_content('courses', [
            'limite' => $atts['limite'],
            'where' => $where,
        ]);

        if (empty($cursos)) {
            return $this->render_empty('📚', __('No hay cursos de la red disponibles', 'flavor-chat-ia'));
        }

        ob_start();
        ?>
        <div class="flavor-fed-section">
            <div class="flavor-fed-header">
                <h3>📚 <?php echo esc_html($atts['titulo']); ?></h3>
            </div>
            <div class="flavor-fed-grid">
                <?php foreach ($cursos as $curso): ?>
                    <div class="flavor-fed-card">
                        <?php if (!empty($curso->imagen_url)): ?>
                            <img src="<?php echo esc_url($curso->imagen_url); ?>" alt="" class="flavor-fed-card-img">
                        <?php else: ?>
                            <div class="flavor-fed-card-img" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #fff;">📚</div>
                        <?php endif; ?>
                        <div class="flavor-fed-card-body">
                            <h4 class="flavor-fed-card-title"><?php echo esc_html($curso->titulo); ?></h4>
                            <div class="flavor-fed-card-meta">
                                <?php if (!empty($curso->categoria)): ?>
                                    <span class="flavor-fed-badge"><?php echo esc_html($curso->categoria); ?></span>
                                <?php endif; ?>
                                <span class="flavor-fed-badge primary">
                                    <?php echo $curso->modalidad === 'online' ? '🌐 Online' : '📍 Presencial'; ?>
                                </span>
                                <?php if ($curso->duracion_horas > 0): ?>
                                    <span class="flavor-fed-badge">⏱️ <?php echo number_format($curso->duracion_horas, 0); ?>h</span>
                                <?php endif; ?>
                            </div>
                            <p class="flavor-fed-card-desc"><?php echo esc_html(wp_trim_words($curso->descripcion, 15)); ?></p>
                            <div class="flavor-fed-card-footer">
                                <span class="flavor-fed-node">👨‍🏫 <?php echo esc_html($curso->instructor_nombre ?: __('Instructor', 'flavor-chat-ia')); ?></span>
                                <?php if ($curso->es_gratuito): ?>
                                    <span class="flavor-fed-price free"><?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?></span>
                                <?php else: ?>
                                    <span class="flavor-fed-price"><?php echo number_format($curso->precio, 2); ?> €</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Talleres de la red
     */
    public function shortcode_talleres($atts) {
        wp_enqueue_style('flavor-federation-frontend');

        $atts = shortcode_atts([
            'limite' => 12,
            'titulo' => __('Talleres de la Red', 'flavor-chat-ia'),
        ], $atts);

        $talleres = $this->get_federated_content('workshops', [
            'limite' => $atts['limite'],
            'where' => ['estado' => 'publicado'],
        ]);

        if (empty($talleres)) {
            return $this->render_empty('🎓', __('No hay talleres de la red disponibles', 'flavor-chat-ia'));
        }

        ob_start();
        ?>
        <div class="flavor-fed-section">
            <div class="flavor-fed-header">
                <h3>🎓 <?php echo esc_html($atts['titulo']); ?></h3>
            </div>
            <div class="flavor-fed-grid">
                <?php foreach ($talleres as $taller): ?>
                    <div class="flavor-fed-card">
                        <?php if (!empty($taller->imagen_url)): ?>
                            <img src="<?php echo esc_url($taller->imagen_url); ?>" alt="" class="flavor-fed-card-img">
                        <?php else: ?>
                            <div class="flavor-fed-card-img" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #fff;">🎓</div>
                        <?php endif; ?>
                        <div class="flavor-fed-card-body">
                            <h4 class="flavor-fed-card-title"><?php echo esc_html($taller->titulo); ?></h4>
                            <div class="flavor-fed-card-meta">
                                <?php if (!empty($taller->categoria)): ?>
                                    <span class="flavor-fed-badge"><?php echo esc_html($taller->categoria); ?></span>
                                <?php endif; ?>
                                <span class="flavor-fed-badge primary"><?php echo esc_html(ucfirst($taller->nivel)); ?></span>
                                <?php
                                $plazas_disponibles = $taller->max_participantes - $taller->inscritos_actuales;
                                if ($plazas_disponibles > 0): ?>
                                    <span class="flavor-fed-badge success">👥 <?php echo $plazas_disponibles; ?> plazas</span>
                                <?php else: ?>
                                    <span class="flavor-fed-badge" style="background: #ffebee; color: #c62828;">Completo</span>
                                <?php endif; ?>
                            </div>
                            <p class="flavor-fed-card-desc"><?php echo esc_html(wp_trim_words($taller->descripcion, 15)); ?></p>
                            <div class="flavor-fed-card-footer">
                                <span class="flavor-fed-node">🔗 <?php echo esc_html(substr($taller->nodo_id, 0, 8)); ?>...</span>
                                <?php if ($taller->es_gratuito): ?>
                                    <span class="flavor-fed-price free"><?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?></span>
                                <?php else: ?>
                                    <span class="flavor-fed-price"><?php echo number_format($taller->precio, 2); ?> €</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Marketplace de la red
     */
    public function shortcode_marketplace($atts) {
        wp_enqueue_style('flavor-federation-frontend');

        $atts = shortcode_atts([
            'limite' => 12,
            'titulo' => __('Marketplace de la Red', 'flavor-chat-ia'),
            'tipo' => '',
        ], $atts);

        $where = ['estado' => 'publicado'];
        if (!empty($atts['tipo'])) {
            $where['tipo'] = $atts['tipo'];
        }

        $anuncios = $this->get_federated_content('marketplace', [
            'limite' => $atts['limite'],
            'where' => $where,
        ]);

        if (empty($anuncios)) {
            return $this->render_empty('🛒', __('No hay anuncios de la red disponibles', 'flavor-chat-ia'));
        }

        $tipo_icons = [
            'venta' => '🏷️',
            'regalo' => '🎁',
            'intercambio' => '🔄',
            'alquiler' => '🏠',
            'servicio' => '🔧',
            'compra' => '🔍',
        ];

        ob_start();
        ?>
        <div class="flavor-fed-section">
            <div class="flavor-fed-header">
                <h3>🛒 <?php echo esc_html($atts['titulo']); ?></h3>
            </div>
            <div class="flavor-fed-grid">
                <?php foreach ($anuncios as $anuncio): ?>
                    <div class="flavor-fed-card">
                        <?php if (!empty($anuncio->imagen_principal)): ?>
                            <img src="<?php echo esc_url($anuncio->imagen_principal); ?>" alt="" class="flavor-fed-card-img">
                        <?php else: ?>
                            <div class="flavor-fed-card-img" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #fff;">🛒</div>
                        <?php endif; ?>
                        <div class="flavor-fed-card-body">
                            <h4 class="flavor-fed-card-title"><?php echo esc_html($anuncio->titulo); ?></h4>
                            <div class="flavor-fed-card-meta">
                                <span class="flavor-fed-badge primary">
                                    <?php echo ($tipo_icons[$anuncio->tipo] ?? '📦') . ' ' . esc_html(ucfirst($anuncio->tipo)); ?>
                                </span>
                                <?php if (!empty($anuncio->categoria)): ?>
                                    <span class="flavor-fed-badge"><?php echo esc_html($anuncio->categoria); ?></span>
                                <?php endif; ?>
                                <?php if ($anuncio->envio_disponible): ?>
                                    <span class="flavor-fed-badge success">📦 Envío</span>
                                <?php endif; ?>
                            </div>
                            <p class="flavor-fed-card-desc"><?php echo esc_html(wp_trim_words($anuncio->descripcion, 15)); ?></p>
                            <div class="flavor-fed-card-footer">
                                <span class="flavor-fed-node">👤 <?php echo esc_html($anuncio->usuario_nombre ?: __('Usuario', 'flavor-chat-ia')); ?></span>
                                <?php if ($anuncio->es_gratuito || $anuncio->tipo === 'regalo'): ?>
                                    <span class="flavor-fed-price free"><?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?></span>
                                <?php elseif ($anuncio->precio): ?>
                                    <span class="flavor-fed-price"><?php echo number_format($anuncio->precio, 2); ?> €</span>
                                <?php else: ?>
                                    <span class="flavor-fed-price">—</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Carpooling de la red
     */
    public function shortcode_carpooling($atts) {
        wp_enqueue_style('flavor-federation-frontend');

        $atts = shortcode_atts([
            'limite' => 12,
            'titulo' => __('Viajes Compartidos de la Red', 'flavor-chat-ia'),
        ], $atts);

        $viajes = $this->get_federated_content('carpooling', [
            'limite' => $atts['limite'],
            'order_by' => 'fecha_salida',
            'orden' => 'ASC',
            'where' => ['estado' => 'activo'],
        ]);

        if (empty($viajes)) {
            return $this->render_empty('🚗', __('No hay viajes compartidos disponibles', 'flavor-chat-ia'));
        }

        ob_start();
        ?>
        <div class="flavor-fed-section">
            <div class="flavor-fed-header">
                <h3>🚗 <?php echo esc_html($atts['titulo']); ?></h3>
            </div>
            <div class="flavor-fed-grid">
                <?php foreach ($viajes as $viaje): ?>
                    <div class="flavor-fed-card">
                        <div class="flavor-fed-card-img" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #fff;">🚗</div>
                        <div class="flavor-fed-card-body">
                            <h4 class="flavor-fed-card-title">
                                <?php echo esc_html($viaje->origen); ?> → <?php echo esc_html($viaje->destino); ?>
                            </h4>
                            <div class="flavor-fed-card-meta">
                                <span class="flavor-fed-badge primary">
                                    📆 <?php echo esc_html(date_i18n('d M H:i', strtotime($viaje->fecha_salida))); ?>
                                </span>
                                <span class="flavor-fed-badge success">
                                    👥 <?php echo $viaje->plazas_disponibles; ?>/<?php echo $viaje->plazas_totales; ?> plazas
                                </span>
                            </div>
                            <div class="flavor-fed-card-meta">
                                <?php if ($viaje->permite_equipaje): ?>
                                    <span class="flavor-fed-badge">🧳 Equipaje</span>
                                <?php endif; ?>
                                <?php if ($viaje->permite_mascotas): ?>
                                    <span class="flavor-fed-badge">🐕 Mascotas</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($viaje->notas)): ?>
                                <p class="flavor-fed-card-desc"><?php echo esc_html(wp_trim_words($viaje->notas, 12)); ?></p>
                            <?php endif; ?>
                            <div class="flavor-fed-card-footer">
                                <span class="flavor-fed-node">🚘 <?php echo esc_html($viaje->conductor_nombre ?: __('Conductor', 'flavor-chat-ia')); ?></span>
                                <?php if ($viaje->precio_plaza > 0): ?>
                                    <span class="flavor-fed-price"><?php echo number_format($viaje->precio_plaza, 2); ?> €/plaza</span>
                                <?php else: ?>
                                    <span class="flavor-fed-price free"><?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Espacios de la red
     */
    public function shortcode_espacios($atts) {
        wp_enqueue_style('flavor-federation-frontend');

        $atts = shortcode_atts([
            'limite' => 12,
            'titulo' => __('Espacios de la Red', 'flavor-chat-ia'),
        ], $atts);

        $espacios = $this->get_federated_content('spaces', [
            'limite' => $atts['limite'],
            'where' => ['estado' => 'disponible'],
        ]);

        if (empty($espacios)) {
            return $this->render_empty('🏠', __('No hay espacios de la red disponibles', 'flavor-chat-ia'));
        }

        ob_start();
        ?>
        <div class="flavor-fed-section">
            <div class="flavor-fed-header">
                <h3>🏠 <?php echo esc_html($atts['titulo']); ?></h3>
            </div>
            <div class="flavor-fed-grid">
                <?php foreach ($espacios as $espacio): ?>
                    <div class="flavor-fed-card">
                        <?php if (!empty($espacio->foto_principal)): ?>
                            <img src="<?php echo esc_url($espacio->foto_principal); ?>" alt="" class="flavor-fed-card-img">
                        <?php else: ?>
                            <div class="flavor-fed-card-img" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #fff;">🏠</div>
                        <?php endif; ?>
                        <div class="flavor-fed-card-body">
                            <h4 class="flavor-fed-card-title"><?php echo esc_html($espacio->nombre); ?></h4>
                            <div class="flavor-fed-card-meta">
                                <span class="flavor-fed-badge"><?php echo esc_html(ucfirst(str_replace('_', ' ', $espacio->tipo))); ?></span>
                                <?php if ($espacio->capacidad_personas > 0): ?>
                                    <span class="flavor-fed-badge primary">👥 <?php echo $espacio->capacidad_personas; ?> personas</span>
                                <?php endif; ?>
                                <?php if ($espacio->superficie_m2 > 0): ?>
                                    <span class="flavor-fed-badge"><?php echo number_format($espacio->superficie_m2, 0); ?> m²</span>
                                <?php endif; ?>
                            </div>
                            <p class="flavor-fed-card-desc"><?php echo esc_html(wp_trim_words($espacio->descripcion, 15)); ?></p>
                            <div class="flavor-fed-card-footer">
                                <span class="flavor-fed-node">📍 <?php echo esc_html(wp_trim_words($espacio->ubicacion, 3) ?: substr($espacio->nodo_id, 0, 8) . '...'); ?></span>
                                <?php if ($espacio->precio_hora > 0): ?>
                                    <span class="flavor-fed-price"><?php echo number_format($espacio->precio_hora, 2); ?> €/h</span>
                                <?php else: ?>
                                    <span class="flavor-fed-price free"><?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Banco de tiempo de la red
     */
    public function shortcode_banco_tiempo($atts) {
        wp_enqueue_style('flavor-federation-frontend');

        $atts = shortcode_atts([
            'limite' => 12,
            'titulo' => __('Banco de Tiempo de la Red', 'flavor-chat-ia'),
            'tipo' => '',
        ], $atts);

        $where = ['estado' => 'activo'];
        if (!empty($atts['tipo'])) {
            $where['tipo'] = $atts['tipo'];
        }

        $servicios = $this->get_federated_content('time_bank', [
            'limite' => $atts['limite'],
            'where' => $where,
        ]);

        if (empty($servicios)) {
            return $this->render_empty('⏰', __('No hay servicios del banco de tiempo disponibles', 'flavor-chat-ia'));
        }

        ob_start();
        ?>
        <div class="flavor-fed-section">
            <div class="flavor-fed-header">
                <h3>⏰ <?php echo esc_html($atts['titulo']); ?></h3>
            </div>
            <div class="flavor-fed-grid">
                <?php foreach ($servicios as $servicio): ?>
                    <div class="flavor-fed-card">
                        <div class="flavor-fed-card-img" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #fff;">
                            <?php echo $servicio->tipo === 'oferta' ? '🤝' : '🙋'; ?>
                        </div>
                        <div class="flavor-fed-card-body">
                            <h4 class="flavor-fed-card-title"><?php echo esc_html($servicio->titulo); ?></h4>
                            <div class="flavor-fed-card-meta">
                                <span class="flavor-fed-badge <?php echo $servicio->tipo === 'oferta' ? 'success' : 'primary'; ?>">
                                    <?php echo $servicio->tipo === 'oferta' ? '🤝 Ofrezco' : '🙋 Busco'; ?>
                                </span>
                                <?php if (!empty($servicio->categoria)): ?>
                                    <span class="flavor-fed-badge"><?php echo esc_html($servicio->categoria); ?></span>
                                <?php endif; ?>
                                <span class="flavor-fed-badge">
                                    <?php echo $servicio->modalidad === 'online' ? '🌐 Online' : '📍 Presencial'; ?>
                                </span>
                            </div>
                            <p class="flavor-fed-card-desc"><?php echo esc_html(wp_trim_words($servicio->descripcion, 15)); ?></p>
                            <div class="flavor-fed-card-footer">
                                <span class="flavor-fed-node">👤 <?php echo esc_html($servicio->usuario_nombre ?: __('Usuario', 'flavor-chat-ia')); ?></span>
                                <span class="flavor-fed-price"><?php echo number_format($servicio->horas_estimadas, 1); ?> h</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Productores de la red
     */
    public function shortcode_productores($atts) {
        wp_enqueue_style('flavor-federation-frontend');

        $atts = shortcode_atts([
            'limite' => 12,
            'titulo' => __('Productores de la Red', 'flavor-chat-ia'),
        ], $atts);

        $productores = $this->get_federated_content('producers', [
            'limite' => $atts['limite'],
            'where' => ['estado' => 'activo'],
        ]);

        if (empty($productores)) {
            return $this->render_empty('🌾', __('No hay productores de la red disponibles', 'flavor-chat-ia'));
        }

        ob_start();
        ?>
        <div class="flavor-fed-section">
            <div class="flavor-fed-header">
                <h3>🌾 <?php echo esc_html($atts['titulo']); ?></h3>
            </div>
            <div class="flavor-fed-grid">
                <?php foreach ($productores as $productor): ?>
                    <div class="flavor-fed-card">
                        <?php if (!empty($productor->logo_url)): ?>
                            <img src="<?php echo esc_url($productor->logo_url); ?>" alt="" class="flavor-fed-card-img">
                        <?php else: ?>
                            <div class="flavor-fed-card-img" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #fff;">🌾</div>
                        <?php endif; ?>
                        <div class="flavor-fed-card-body">
                            <h4 class="flavor-fed-card-title"><?php echo esc_html($productor->nombre); ?></h4>
                            <div class="flavor-fed-card-meta">
                                <?php if ($productor->certificacion_eco): ?>
                                    <span class="flavor-fed-badge success">🌿 Ecológico</span>
                                <?php endif; ?>
                                <?php if ($productor->productos_count > 0): ?>
                                    <span class="flavor-fed-badge"><?php echo $productor->productos_count; ?> productos</span>
                                <?php endif; ?>
                                <?php if ($productor->radio_entrega_km > 0): ?>
                                    <span class="flavor-fed-badge primary">📦 <?php echo $productor->radio_entrega_km; ?> km</span>
                                <?php endif; ?>
                            </div>
                            <p class="flavor-fed-card-desc"><?php echo esc_html(wp_trim_words($productor->descripcion, 15)); ?></p>
                            <div class="flavor-fed-card-footer">
                                <span class="flavor-fed-node">📍 <?php echo esc_html($productor->ubicacion ?: substr($productor->nodo_id, 0, 8) . '...'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode genérico para cualquier tipo de contenido
     */
    public function shortcode_contenido($atts) {
        $atts = shortcode_atts([
            'tipo' => 'events',
            'limite' => 12,
        ], $atts);

        $method = 'shortcode_' . str_replace('_', '', $atts['tipo']);

        if (method_exists($this, $method)) {
            return $this->$method($atts);
        }

        return $this->render_empty('📦', __('Tipo de contenido no válido', 'flavor-chat-ia'));
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Network_Federation_Shortcodes::get_instance();
});

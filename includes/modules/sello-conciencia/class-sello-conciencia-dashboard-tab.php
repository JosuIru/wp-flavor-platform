<?php
/**
 * Dashboard Tab para Sello Conciencia
 *
 * Sistema de certificación de comercios y productos con criterios
 * de sostenibilidad, ética y economía social.
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Sello_Conciencia_Dashboard_Tab {

    private static $instance = null;
    private $mensajes = [];

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
        $this->maybe_create_tables();
    }

    public function registrar_tabs($tabs) {
        $tabs['sello-conciencia'] = [
            'label' => __('Sello Conciencia', 'flavor-platform'),
            'icon' => 'dashicons-awards',
            'callback' => [$this, 'render_tab'],
            'priority' => 80,
        ];
        return $tabs;
    }

    public function render_tab() {
        $this->handle_solicitud_submission();
        $datos = $this->obtener_datos_usuario();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'mis-sellos';

        ?>
        <div class="flavor-sello-dashboard">
            <?php if (!empty($this->mensajes)) : ?>
                <?php foreach ($this->mensajes as $mensaje) : ?>
                    <div class="flavor-notice flavor-notice-<?php echo esc_attr($mensaje['tipo']); ?>">
                        <p><?php echo esc_html($mensaje['texto']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="flavor-dashboard-subtabs">
                <a href="?tab=sello-conciencia&subtab=mis-sellos" class="subtab <?php echo $subtab === 'mis-sellos' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-awards"></span> Mis Sellos
                </a>
                <a href="?tab=sello-conciencia&subtab=solicitar" class="subtab <?php echo $subtab === 'solicitar' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt"></span> Solicitar Sello
                </a>
                <a href="?tab=sello-conciencia&subtab=directorio" class="subtab <?php echo $subtab === 'directorio' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-location"></span> Directorio
                </a>
                <a href="?tab=sello-conciencia&subtab=criterios" class="subtab <?php echo $subtab === 'criterios' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-info"></span> Criterios
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'solicitar':
                        $this->render_solicitar();
                        break;
                    case 'directorio':
                        $this->render_directorio($datos);
                        break;
                    case 'criterios':
                        $this->render_criterios();
                        break;
                    case 'detalle':
                    case 'ver':
                        $this->render_detalle();
                        break;
                    default:
                        $this->render_mis_sellos($datos);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function maybe_create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_sellos = $wpdb->prefix . 'flavor_sellos_conciencia';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_sellos_solicitudes';

        $sql_sellos = "CREATE TABLE IF NOT EXISTS $tabla_sellos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo varchar(60) NOT NULL,
            nombre_entidad varchar(255) NOT NULL,
            descripcion longtext NULL,
            categorias text NULL,
            nivel varchar(20) NOT NULL DEFAULT 'bronce',
            estado varchar(20) NOT NULL DEFAULT 'activo',
            direccion text NULL,
            fecha_emision datetime NULL,
            fecha_expiracion datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY nivel (nivel)
        ) $charset_collate;";

        $sql_solicitudes = "CREATE TABLE IF NOT EXISTS $tabla_solicitudes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo varchar(60) NOT NULL,
            nombre_entidad varchar(255) NOT NULL,
            nif varchar(64) NULL,
            anio_fundacion int(4) NULL,
            descripcion longtext NOT NULL,
            direccion text NULL,
            web varchar(255) NULL,
            categorias text NULL,
            documentos text NULL,
            estado varchar(30) NOT NULL DEFAULT 'pendiente',
            notas_revision text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        dbDelta($sql_sellos);
        dbDelta($sql_solicitudes);
    }

    private function handle_solicitud_submission() {
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST'
            || empty($_POST['sello_form_action'])
            || sanitize_text_field(wp_unslash((string) $_POST['sello_form_action'])) !== 'crear_solicitud'
        ) {
            return;
        }

        if (!is_user_logged_in()) {
            $this->mensajes[] = [
                'tipo' => 'error',
                'texto' => __('Debes iniciar sesión para enviar una solicitud.', 'flavor-platform'),
            ];
            return;
        }

        $nonce = sanitize_text_field(wp_unslash((string) ($_POST['sello_nonce'] ?? '')));
        if (!wp_verify_nonce($nonce, 'flavor_solicitud_sello')) {
            $this->mensajes[] = [
                'tipo' => 'error',
                'texto' => __('No se pudo validar el formulario. Inténtalo de nuevo.', 'flavor-platform'),
            ];
            return;
        }

        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $nombre_entidad = sanitize_text_field($_POST['nombre_entidad'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $acepto = !empty($_POST['acepto_condiciones']);

        if ($tipo === '' || $nombre_entidad === '' || $descripcion === '' || !$acepto) {
            $this->mensajes[] = [
                'tipo' => 'error',
                'texto' => __('Completa los campos obligatorios y acepta las condiciones.', 'flavor-platform'),
            ];
            return;
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_sellos_solicitudes';
        $usuario_id = get_current_user_id();

        $pendiente = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_solicitudes WHERE usuario_id = %d AND estado IN ('pendiente', 'revision', 'en_revision')",
            $usuario_id
        ));
        if ($pendiente > 0) {
            $this->mensajes[] = [
                'tipo' => 'warning',
                'texto' => __('Ya tienes una solicitud en proceso. Espera su revisión antes de enviar otra.', 'flavor-platform'),
            ];
            return;
        }

        $categorias = isset($_POST['categorias']) ? (array) $_POST['categorias'] : [];
        $categorias = array_filter(array_map('sanitize_text_field', $categorias));

        $documentos = [];
        if (!empty($_FILES['documentos']['name']) && is_array($_FILES['documentos']['name'])) {
            foreach ($_FILES['documentos']['name'] as $idx => $nombre) {
                if (trim((string) $nombre) !== '') {
                    $documentos[] = sanitize_file_name((string) $nombre);
                }
            }
        }

        $insertado = $wpdb->insert(
            $tabla_solicitudes,
            [
                'usuario_id' => $usuario_id,
                'tipo' => $tipo,
                'nombre_entidad' => $nombre_entidad,
                'nif' => sanitize_text_field($_POST['nif'] ?? ''),
                'anio_fundacion' => absint($_POST['anio_fundacion'] ?? 0) ?: null,
                'descripcion' => $descripcion,
                'direccion' => sanitize_textarea_field($_POST['direccion'] ?? ''),
                'web' => esc_url_raw($_POST['web'] ?? ''),
                'categorias' => implode(',', $categorias),
                'documentos' => wp_json_encode($documentos),
                'estado' => 'pendiente',
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($insertado === false) {
            $this->mensajes[] = [
                'tipo' => 'error',
                'texto' => __('No se pudo guardar la solicitud. Revisa la configuración de base de datos.', 'flavor-platform'),
            ];
            return;
        }

        $this->mensajes[] = [
            'tipo' => 'success',
            'texto' => __('Solicitud enviada correctamente. La revisaremos y te notificaremos el resultado.', 'flavor-platform'),
        ];
    }

    private function render_mis_sellos($datos) {
        ?>
        <div class="mis-sellos">
            <?php if (empty($datos['sellos'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-awards"></span>
                    <h3>Aún no tienes ningún Sello Conciencia</h3>
                    <p>Solicita la certificación para tu negocio o producto y demuestra tu compromiso con la sostenibilidad.</p>
                    <a href="?tab=sello-conciencia&subtab=solicitar" class="flavor-btn flavor-btn-primary">Solicitar certificación</a>
                </div>
            <?php else: ?>
                <div class="sellos-grid">
                    <?php foreach ($datos['sellos'] as $sello): ?>
                        <div class="sello-card nivel-<?php echo esc_attr($sello->nivel); ?>">
                            <div class="sello-badge">
                                <span class="nivel-icono"><?php echo $this->get_icono_nivel($sello->nivel); ?></span>
                                <span class="nivel-nombre"><?php echo ucfirst($sello->nivel); ?></span>
                            </div>
                            <div class="sello-info">
                                <h4><?php echo esc_html($sello->nombre_entidad); ?></h4>
                                <span class="sello-tipo"><?php echo ucfirst($sello->tipo); ?></span>
                                <div class="sello-categorias">
                                    <?php foreach (explode(',', $sello->categorias) as $cat): ?>
                                        <span class="categoria-tag"><?php echo esc_html(trim($cat)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="sello-validez">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    Válido hasta: <?php echo date_i18n('j M Y', strtotime($sello->fecha_expiracion)); ?>
                                </div>
                            </div>
                            <div class="sello-acciones">
                                <a href="?tab=sello-conciencia&subtab=detalle&id=<?php echo $sello->id; ?>" class="flavor-btn flavor-btn-sm">Ver detalle</a>
                                <button class="flavor-btn flavor-btn-sm descargar-certificado" data-id="<?php echo $sello->id; ?>">
                                    <span class="dashicons dashicons-download"></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Solicitudes pendientes -->
                <?php if (!empty($datos['solicitudes_pendientes'])): ?>
                    <div class="solicitudes-pendientes">
                        <h3>Solicitudes en proceso</h3>
                        <?php foreach ($datos['solicitudes_pendientes'] as $sol): ?>
                            <div class="solicitud-item estado-<?php echo $sol->estado; ?>">
                                <div class="solicitud-info">
                                    <strong><?php echo esc_html($sol->nombre_entidad); ?></strong>
                                    <span class="fecha">Solicitado: <?php echo date_i18n('j M Y', strtotime($sol->created_at)); ?></span>
                                </div>
                                <span class="badge badge-<?php echo $sol->estado; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $sol->estado)); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <style>
            .sellos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
            .sello-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .sello-card.nivel-bronce { border-left: 4px solid #cd7f32; }
            .sello-card.nivel-plata { border-left: 4px solid #c0c0c0; }
            .sello-card.nivel-oro { border-left: 4px solid #ffd700; }
            .sello-card.nivel-platino { border-left: 4px solid #e5e4e2; }
            .sello-badge { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
            .nivel-icono { font-size: 32px; }
            .nivel-nombre { font-weight: 700; font-size: 18px; }
            .sello-categorias { display: flex; flex-wrap: wrap; gap: 5px; margin: 10px 0; }
            .categoria-tag { background: #e3f2fd; color: #1976d2; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
            .sello-validez { font-size: 13px; color: #666; margin-top: 10px; }
            .solicitud-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f5f5f5; border-radius: 8px; margin-bottom: 10px; }
        </style>
        <?php
    }

    private function render_solicitar() {
        ?>
        <div class="solicitar-sello">
            <h3>Solicitar Sello Conciencia</h3>

            <div class="info-proceso">
                <h4>¿Cómo funciona?</h4>
                <ol>
                    <li>Completa el formulario de solicitud</li>
                    <li>Nuestro equipo revisará la documentación</li>
                    <li>Evaluación de criterios de sostenibilidad</li>
                    <li>Visita de verificación (si aplica)</li>
                    <li>Emisión del certificado y sello</li>
                </ol>
            </div>

            <form id="form-solicitud-sello" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_solicitud_sello', 'sello_nonce'); ?>
                <input type="hidden" name="sello_form_action" value="crear_solicitud">

                <h4>Datos de la entidad</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo de entidad *</label>
                        <select name="tipo" required>
                            <option value="">Selecciona...</option>
                            <option value="comercio">Comercio / Tienda</option>
                            <option value="productor">Productor / Fabricante</option>
                            <option value="servicio">Empresa de servicios</option>
                            <option value="hosteleria">Hostelería</option>
                            <option value="cooperativa">Cooperativa</option>
                            <option value="asociacion">Asociación</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nombre de la entidad *</label>
                        <input type="text" name="nombre_entidad" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>NIF/CIF</label>
                        <input type="text" name="nif">
                    </div>
                    <div class="form-group">
                        <label>Año de fundación</label>
                        <input type="number" name="anio_fundacion" min="1900" max="<?php echo date('Y'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción de la actividad *</label>
                    <textarea name="descripcion" rows="4" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion">
                    </div>
                    <div class="form-group">
                        <label>Sitio web</label>
                        <input type="url" name="web">
                    </div>
                </div>

                <h4>Categorías de certificación</h4>
                <div class="categorias-check">
                    <label><input type="checkbox" name="categorias[]" value="sostenibilidad_ambiental"> Sostenibilidad ambiental</label>
                    <label><input type="checkbox" name="categorias[]" value="comercio_justo"> Comercio justo</label>
                    <label><input type="checkbox" name="categorias[]" value="economia_social"> Economía social y solidaria</label>
                    <label><input type="checkbox" name="categorias[]" value="km0"> Km 0 / Producción local</label>
                    <label><input type="checkbox" name="categorias[]" value="bienestar_animal"> Bienestar animal</label>
                    <label><input type="checkbox" name="categorias[]" value="inclusion_social"> Inclusión social</label>
                    <label><input type="checkbox" name="categorias[]" value="economia_circular"> Economía circular</label>
                    <label><input type="checkbox" name="categorias[]" value="salud_bienestar"> Salud y bienestar</label>
                </div>

                <h4>Documentación</h4>
                <div class="form-group">
                    <label>Documentos de soporte (certificados, memorias, etc.)</label>
                    <input type="file" name="documentos[]" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                    <small>Puedes subir varios archivos. Máx. 10MB cada uno.</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="acepto_condiciones" required>
                        Acepto las condiciones del programa Sello Conciencia y autorizo la verificación de los datos
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Enviar solicitud', 'flavor-platform'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    private function render_directorio($datos) {
        ?>
        <div class="directorio-sello">
            <h3>Directorio de entidades certificadas</h3>

            <div class="directorio-filtros">
                <form method="get">
                    <input type="hidden" name="tab" value="sello-conciencia">
                    <input type="hidden" name="subtab" value="directorio">
                    <input type="text" name="buscar" placeholder="Buscar..." value="<?php echo esc_attr($_GET['buscar'] ?? ''); ?>">
                    <select name="tipo">
                        <option value="">Todos los tipos</option>
                        <option value="comercio">Comercio</option>
                        <option value="productor">Productor</option>
                        <option value="servicio">Servicios</option>
                        <option value="hosteleria">Hostelería</option>
                    </select>
                    <select name="nivel">
                        <option value="">Todos los niveles</option>
                        <option value="bronce">Bronce</option>
                        <option value="plata">Plata</option>
                        <option value="oro">Oro</option>
                        <option value="platino">Platino</option>
                    </select>
                    <button type="submit" class="flavor-btn">Filtrar</button>
                </form>
            </div>

            <div class="directorio-grid">
                <?php if (empty($datos['directorio'])): ?>
                    <p class="sin-resultados">No se encontraron entidades certificadas</p>
                <?php else: ?>
                    <?php foreach ($datos['directorio'] as $entidad): ?>
                        <div class="entidad-card nivel-<?php echo $entidad->nivel; ?>">
                            <div class="entidad-header">
                                <span class="nivel-badge"><?php echo $this->get_icono_nivel($entidad->nivel); ?></span>
                                <h4><?php echo esc_html($entidad->nombre_entidad); ?></h4>
                            </div>
                            <p class="entidad-tipo"><?php echo ucfirst($entidad->tipo); ?></p>
                            <p class="entidad-desc"><?php echo esc_html(wp_trim_words($entidad->descripcion, 20)); ?></p>
                            <?php if ($entidad->direccion): ?>
                                <p class="entidad-direccion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($entidad->direccion); ?>
                                </p>
                            <?php endif; ?>
                            <a href="?tab=sello-conciencia&subtab=ver&id=<?php echo $entidad->id; ?>" class="flavor-btn flavor-btn-sm">Ver más</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_criterios() {
        ?>
        <div class="criterios-sello">
            <h3>Criterios de certificación</h3>

            <div class="niveles-info">
                <div class="nivel-card nivel-bronce">
                    <h4><?php echo $this->get_icono_nivel('bronce'); ?> Nivel Bronce</h4>
                    <p>Cumplimiento de criterios básicos de sostenibilidad.</p>
                    <ul>
                        <li>Reducción de residuos</li>
                        <li>Proveedores locales (mínimo 20%)</li>
                        <li>Compromiso de mejora continua</li>
                    </ul>
                </div>

                <div class="nivel-card nivel-plata">
                    <h4><?php echo $this->get_icono_nivel('plata'); ?> Nivel Plata</h4>
                    <p>Compromiso avanzado con la sostenibilidad.</p>
                    <ul>
                        <li>Todos los criterios de Bronce</li>
                        <li>Proveedores locales (mínimo 40%)</li>
                        <li>Certificaciones adicionales</li>
                        <li>Plan de responsabilidad social</li>
                    </ul>
                </div>

                <div class="nivel-card nivel-oro">
                    <h4><?php echo $this->get_icono_nivel('oro'); ?> Nivel Oro</h4>
                    <p>Excelencia en prácticas sostenibles.</p>
                    <ul>
                        <li>Todos los criterios de Plata</li>
                        <li>Proveedores locales (mínimo 60%)</li>
                        <li>Huella de carbono reducida</li>
                        <li>Economía circular implementada</li>
                        <li>Impacto social positivo demostrado</li>
                    </ul>
                </div>

                <div class="nivel-card nivel-platino">
                    <h4><?php echo $this->get_icono_nivel('platino'); ?> Nivel Platino</h4>
                    <p>Referente en sostenibilidad y ética.</p>
                    <ul>
                        <li>Todos los criterios de Oro</li>
                        <li>Proveedores 100% éticos y sostenibles</li>
                        <li>Carbono neutral o negativo</li>
                        <li>Innovación en sostenibilidad</li>
                        <li>Modelo replicable y formación</li>
                    </ul>
                </div>
            </div>
        </div>

        <style>
            .niveles-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
            .nivel-card { padding: 20px; border-radius: 12px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .nivel-card h4 { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
            .nivel-card ul { margin-top: 15px; padding-left: 20px; }
            .nivel-card.nivel-bronce { border-top: 4px solid #cd7f32; }
            .nivel-card.nivel-plata { border-top: 4px solid #c0c0c0; }
            .nivel-card.nivel-oro { border-top: 4px solid #ffd700; }
            .nivel-card.nivel-platino { border-top: 4px solid #e5e4e2; }
        </style>
        <?php
    }

    private function render_detalle() {
        global $wpdb;

        $sello_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $tabla_sellos = $wpdb->prefix . 'flavor_sellos_conciencia';
        $user_id = get_current_user_id();

        if (!$sello_id) {
            echo '<div class="flavor-empty-state"><p>' . esc_html__('Se requiere el identificador del sello.', 'flavor-platform') . '</p></div>';
            return;
        }

        $sello = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_sellos WHERE id = %d AND (estado = 'activo' OR usuario_id = %d)",
            $sello_id,
            $user_id
        ));

        if (!$sello) {
            echo '<div class="flavor-empty-state"><p>' . esc_html__('El sello solicitado no existe o no está disponible.', 'flavor-platform') . '</p></div>';
            return;
        }

        $categorias = array_filter(array_map('trim', explode(',', (string) ($sello->categorias ?? ''))));
        ?>
        <div class="sello-detalle">
            <div class="sello-detalle__header nivel-<?php echo esc_attr($sello->nivel); ?>">
                <div class="sello-detalle__badge">
                    <span class="nivel-icono"><?php echo esc_html($this->get_icono_nivel($sello->nivel)); ?></span>
                    <div>
                        <h3><?php echo esc_html($sello->nombre_entidad); ?></h3>
                        <p><?php echo esc_html(ucfirst((string) $sello->tipo)); ?> · <?php echo esc_html(ucfirst((string) $sello->nivel)); ?></p>
                    </div>
                </div>
                <a href="?tab=sello-conciencia&subtab=mis-sellos" class="flavor-btn flavor-btn-sm">
                    <?php esc_html_e('Volver', 'flavor-platform'); ?>
                </a>
            </div>

            <div class="sello-detalle__meta">
                <div><strong><?php esc_html_e('Estado', 'flavor-platform'); ?>:</strong> <?php echo esc_html(ucfirst((string) $sello->estado)); ?></div>
                <div><strong><?php esc_html_e('Emitido', 'flavor-platform'); ?>:</strong> <?php echo esc_html(date_i18n('j M Y', strtotime((string) $sello->fecha_emision))); ?></div>
                <div><strong><?php esc_html_e('Válido hasta', 'flavor-platform'); ?>:</strong> <?php echo esc_html(date_i18n('j M Y', strtotime((string) $sello->fecha_expiracion))); ?></div>
                <?php if (!empty($sello->direccion)) : ?>
                    <div><strong><?php esc_html_e('Dirección', 'flavor-platform'); ?>:</strong> <?php echo esc_html($sello->direccion); ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($sello->descripcion)) : ?>
                <div class="sello-detalle__bloque">
                    <h4><?php esc_html_e('Descripción', 'flavor-platform'); ?></h4>
                    <p><?php echo esc_html($sello->descripcion); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($categorias)) : ?>
                <div class="sello-detalle__bloque">
                    <h4><?php esc_html_e('Categorías', 'flavor-platform'); ?></h4>
                    <div class="sello-categorias">
                        <?php foreach ($categorias as $cat) : ?>
                            <span class="categoria-tag"><?php echo esc_html($cat); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .sello-detalle { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
            .sello-detalle__header { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 16px; }
            .sello-detalle__badge { display: flex; gap: 16px; align-items: center; }
            .sello-detalle__badge .nivel-icono { font-size: 42px; }
            .sello-detalle__meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 20px; }
            .sello-detalle__bloque { margin-top: 18px; }
        </style>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_sellos = $wpdb->prefix . 'flavor_sellos_conciencia';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_sellos_solicitudes';

        // Sellos del usuario
        $sellos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_sellos WHERE usuario_id = %d AND estado = 'activo' ORDER BY fecha_emision DESC",
            $user_id
        ));

        // Solicitudes pendientes
        $solicitudes_pendientes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_solicitudes WHERE usuario_id = %d AND estado NOT IN ('aprobada', 'rechazada') ORDER BY created_at DESC",
            $user_id
        ));

        // Directorio
        $where = "estado = 'activo'";
        $buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
        $tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
        $nivel_filtro = isset($_GET['nivel']) ? sanitize_text_field($_GET['nivel']) : '';

        if ($buscar) {
            $where .= $wpdb->prepare(" AND (nombre_entidad LIKE %s OR descripcion LIKE %s)", '%' . $buscar . '%', '%' . $buscar . '%');
        }
        if ($tipo_filtro) {
            $where .= $wpdb->prepare(" AND tipo = %s", $tipo_filtro);
        }
        if ($nivel_filtro) {
            $where .= $wpdb->prepare(" AND nivel = %s", $nivel_filtro);
        }

        $directorio = $wpdb->get_results("SELECT * FROM $tabla_sellos WHERE $where ORDER BY nivel DESC, nombre_entidad ASC LIMIT 50");

        return [
            'sellos' => $sellos ?: [],
            'solicitudes_pendientes' => $solicitudes_pendientes ?: [],
            'directorio' => $directorio ?: [],
        ];
    }

    private function get_icono_nivel($nivel) {
        $iconos = [
            'bronce' => '🥉',
            'plata' => '🥈',
            'oro' => '🥇',
            'platino' => '💎',
        ];
        return $iconos[$nivel] ?? '🏅';
    }
}

Flavor_Sello_Conciencia_Dashboard_Tab::get_instance();

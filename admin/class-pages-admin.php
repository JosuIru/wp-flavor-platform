<?php
/**
 * Admin Pages Manager
 *
 * Página de administración para crear/gestionar páginas de módulos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

class Flavor_Pages_Admin {

    /**
     * Instancia singleton
     *
     * @var Flavor_Pages_Admin|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Pages_Admin
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
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager
        add_action('admin_post_flavor_create_pages', [$this, 'handle_create_pages']);
        add_action('admin_post_flavor_delete_pages', [$this, 'handle_delete_pages']);
    }

    /**
     * Renderiza la página de admin
     */
    public function render_admin_page() {
        // Obtener estado de páginas
        $estado_paginas = Flavor_Page_Creator::get_pages_status();
        $estado_v3 = $this->get_v3_status();
        $total_paginas = count($estado_paginas['exists']) + count($estado_paginas['missing']);
        $conteo_creadas = count($estado_paginas['exists']);
        $conteo_faltantes = count($estado_paginas['missing']);
        ?>
        <div class="wrap">
            <h1><?php _e('Crear Páginas de Módulos', 'flavor-chat-ia'); ?></h1>

            <div class="notice notice-info">
                <p><strong><?php _e('Sistema de Páginas Automáticas', 'flavor-chat-ia'); ?></strong></p>
                <p><?php _e('Esta pantalla combina la foto del creador legacy basado en listado fijo con el estado actual de la migración modular V3.', 'flavor-chat-ia'); ?></p>
            </div>

            <?php if (isset($_GET['created'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php _e('Páginas creadas exitosamente!', 'flavor-chat-ia'); ?></strong></p>
                    <p><?php printf(__('Se crearon %d páginas. Ya puedes navegar a las URLs de los formularios.', 'flavor-chat-ia'), intval($_GET['created'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="notice notice-warning is-dismissible">
                    <p><strong><?php _e('Páginas eliminadas', 'flavor-chat-ia'); ?></strong></p>
                    <p><?php printf(__('Se eliminaron %d páginas.', 'flavor-chat-ia'), intval($_GET['deleted'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($estado_v3['available']): ?>
                <div class="card" style="max-width: 1100px; margin-top: 20px;">
                    <h2><?php _e('Estado Modular V3', 'flavor-chat-ia'); ?></h2>
                    <p style="max-width: 900px;">
                        <?php _e('La arquitectura actual del plugin ya permite que los módulos definan sus propias páginas con `get_pages_definition()`. Esta lectura refleja qué parte del ecosistema ya usa ese sistema.', 'flavor-chat-ia'); ?>
                    </p>

                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
                        <div style="text-align: center; padding: 20px; background: var(--flavor-bg, #f0f0f1); border-radius: var(--flavor-radius-md, 8px);">
                            <div style="font-size: var(--flavor-font-size-2xl, 42px); font-weight: bold; color: var(--flavor-primary, #2271b1);"><?php echo esc_html($estado_v3['total_modules']); ?></div>
                            <div style="color: var(--flavor-text-secondary, #666);"><?php _e('Módulos cargados', 'flavor-chat-ia'); ?></div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: var(--flavor-success-light, #d1e7dd); border-radius: var(--flavor-radius-md, 8px);">
                            <div style="font-size: var(--flavor-font-size-2xl, 42px); font-weight: bold; color: var(--flavor-success, #0f5132);"><?php echo esc_html($estado_v3['migrated_modules']); ?></div>
                            <div style="color: var(--flavor-success, #0f5132);"><?php _e('Módulos con páginas V3', 'flavor-chat-ia'); ?></div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #e8f1ff; border-radius: var(--flavor-radius-md, 8px);">
                            <div style="font-size: var(--flavor-font-size-2xl, 42px); font-weight: bold; color: #135e96;"><?php echo esc_html($estado_v3['defined_pages']); ?></div>
                            <div style="color: #135e96;"><?php _e('Páginas definidas en V3', 'flavor-chat-ia'); ?></div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #fff7e6; border-radius: var(--flavor-radius-md, 8px);">
                            <div style="font-size: var(--flavor-font-size-2xl, 42px); font-weight: bold; color: #8a5300;"><?php echo esc_html($estado_v3['created_pages']); ?></div>
                            <div style="color: #8a5300;"><?php _e('Páginas creadas con owner V3', 'flavor-chat-ia'); ?></div>
                        </div>
                    </div>

                    <div style="margin: 18px 0 24px;">
                        <strong><?php _e('Cobertura V3', 'flavor-chat-ia'); ?>:</strong>
                        <?php echo esc_html($estado_v3['migration_percentage']); ?>%
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="background: #fff; padding: 16px; border-left: 4px solid var(--flavor-success, #0f5132);">
                            <h3 style="margin-top: 0;"><?php printf(__('Módulos ya migrados (%d)', 'flavor-chat-ia'), $estado_v3['migrated_modules']); ?></h3>
                            <?php if (!empty($estado_v3['migrated_preview'])): ?>
                                <ul style="margin-bottom: 0;">
                                    <?php foreach ($estado_v3['migrated_preview'] as $module_info): ?>
                                        <li>
                                            <strong><?php echo esc_html($module_info['name']); ?></strong>
                                            <code><?php echo esc_html($module_info['id']); ?></code>
                                            <?php printf(__('(%d páginas)', 'flavor-chat-ia'), intval($module_info['page_count'])); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p style="margin-bottom: 0;"><?php _e('Todavía no hay módulos con definiciones V3 activas.', 'flavor-chat-ia'); ?></p>
                            <?php endif; ?>
                        </div>

                        <div style="background: #fff; padding: 16px; border-left: 4px solid #dba617;">
                            <h3 style="margin-top: 0;"><?php printf(__('Módulos aún sin páginas V3 (%d)', 'flavor-chat-ia'), $estado_v3['pending_modules']); ?></h3>
                            <?php if (!empty($estado_v3['pending_preview'])): ?>
                                <ul style="margin-bottom: 0;">
                                    <?php foreach ($estado_v3['pending_preview'] as $module_info): ?>
                                        <li>
                                            <strong><?php echo esc_html($module_info['name']); ?></strong>
                                            <code><?php echo esc_html($module_info['id']); ?></code>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p style="margin-bottom: 0;"><?php _e('Todos los módulos cargados exponen ya páginas V3.', 'flavor-chat-ia'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p style="margin-top: 20px;">
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=flavor-systems-panel')); ?>">
                            <?php _e('Abrir Panel de Sistemas', 'flavor-chat-ia'); ?>
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning" style="margin-top: 20px;">
                    <p><?php _e('No se ha podido obtener el estado de migración V3 en esta carga de admin. Se muestra solo el creador legacy.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>

            <!-- Estado actual -->
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Estado Legacy', 'flavor-chat-ia'); ?></h2>
                <p><?php _e('Este bloque sigue mostrando el catálogo histórico de páginas que el creador clásico sabe generar de forma masiva.', 'flavor-chat-ia'); ?></p>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
                    <div style="text-align: center; padding: 20px; background: var(--flavor-bg, #f0f0f1); border-radius: var(--flavor-radius-md, 8px);">
                        <div style="font-size: var(--flavor-font-size-2xl, 48px); font-weight: bold; color: var(--flavor-primary, #2271b1);"><?php echo esc_html($total_paginas); ?></div>
                        <div style="color: var(--flavor-text-secondary, #666);"><?php _e('Total de Páginas', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: var(--flavor-success-light, #d1e7dd); border-radius: var(--flavor-radius-md, 8px);">
                        <div style="font-size: var(--flavor-font-size-2xl, 48px); font-weight: bold; color: var(--flavor-success, #0f5132);"><?php echo esc_html($conteo_creadas); ?></div>
                        <div style="color: var(--flavor-success, #0f5132);"><?php _e('Creadas', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: <?php echo $conteo_faltantes > 0 ? 'var(--flavor-error-light, #f8d7da)' : 'var(--flavor-success-light, #d1e7dd)'; ?>; border-radius: var(--flavor-radius-md, 8px);">
                        <div style="font-size: var(--flavor-font-size-2xl, 48px); font-weight: bold; color: <?php echo $conteo_faltantes > 0 ? 'var(--flavor-error, #842029)' : 'var(--flavor-success, #0f5132)'; ?>;"><?php echo esc_html($conteo_faltantes); ?></div>
                        <div style="color: <?php echo $conteo_faltantes > 0 ? 'var(--flavor-error, #842029)' : 'var(--flavor-success, #0f5132)'; ?>;"><?php _e('Faltantes', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>

                <?php if ($conteo_faltantes > 0): ?>
                    <h3><?php printf(__('Páginas Faltantes (%d)', 'flavor-chat-ia'), $conteo_faltantes); ?></h3>
                    <ul style="background: #fff; padding: 15px; border-left: 4px solid var(--flavor-error, #d63638);">
                        <?php foreach ($estado_paginas['missing'] as $pagina_faltante): ?>
                            <li><code><?php echo esc_html($pagina_faltante['slug']); ?></code> - <?php echo esc_html($pagina_faltante['title']); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="flavor_create_pages">
                        <?php wp_nonce_field('flavor_create_pages_nonce'); ?>
                        <button type="submit" class="button button-primary button-hero">
                            <?php printf(__('Crear %d Páginas Faltantes', 'flavor-chat-ia'), $conteo_faltantes); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div style="background: var(--flavor-success-light, #d1e7dd); color: var(--flavor-success, #0f5132); padding: 15px; border-radius: var(--flavor-radius-md, 8px); margin-top: 20px;">
                        <strong><?php _e('Todas las páginas están creadas', 'flavor-chat-ia'); ?></strong>
                        <p style="margin: 10px 0 0 0;"><?php _e('Todos los formularios de módulos tienen sus páginas correspondientes.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($conteo_creadas > 0): ?>
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php printf(__('Páginas Creadas (%d)', 'flavor-chat-ia'), $conteo_creadas); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Título', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Slug', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('URL', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estado_paginas['exists'] as $pagina_existente): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($pagina_existente['title']); ?></strong></td>
                                    <td><code><?php echo esc_html($pagina_existente['slug']); ?></code></td>
                                    <td><a href="<?php echo esc_url($pagina_existente['url']); ?>" target="_blank"><?php _e('Ver página', 'flavor-chat-ia'); ?> &rarr;</a></td>
                                    <td><a href="<?php echo esc_url(get_edit_post_link(url_to_postid($pagina_existente['url']))); ?>"><?php _e('Editar', 'flavor-chat-ia'); ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card" style="max-width: 800px; margin-top: 20px; border-left: 4px solid var(--flavor-error, #d63638);">
                    <h2 style="color: var(--flavor-error, #d63638);"><?php _e('Zona Peligrosa', 'flavor-chat-ia'); ?></h2>
                    <p><?php _e('Elimina todas las páginas creadas automáticamente.', 'flavor-chat-ia'); ?> <strong><?php _e('Esta acción no se puede deshacer.', 'flavor-chat-ia'); ?></strong></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php esc_attr_e('¿Estás seguro de que quieres eliminar TODAS las páginas creadas? Esta acción no se puede deshacer.', 'flavor-chat-ia'); ?>');">
                        <input type="hidden" name="action" value="flavor_delete_pages">
                        <?php wp_nonce_field('flavor_delete_pages_nonce'); ?>
                        <button type="submit" class="button button-secondary"><?php _e('Eliminar Todas las Páginas', 'flavor-chat-ia'); ?></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <style>
            .card { background: white; padding: 20px; box-shadow: var(--flavor-shadow-sm, 0 1px 3px rgba(0,0,0,0.1)); border-radius: var(--flavor-radius-md, 8px); }
            .card h2 { margin-top: 0; }
        </style>
        <?php
    }

    /**
     * Obtiene el estado resumido de la migración modular V3.
     *
     * @return array
     */
    private function get_v3_status() {
        $empty = [
            'available' => false,
            'total_modules' => 0,
            'migrated_modules' => 0,
            'pending_modules' => 0,
            'defined_pages' => 0,
            'created_pages' => 0,
            'migration_percentage' => 0,
            'migrated_preview' => [],
            'pending_preview' => [],
        ];

        if (!class_exists('Flavor_Page_Creator_V3') || !class_exists('Flavor_Chat_Module_Loader')) {
            return $empty;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        if (!$loader || !method_exists($loader, 'get_loaded_modules')) {
            return $empty;
        }

        $creator = Flavor_Page_Creator_V3::get_instance();
        $stats = method_exists($creator, 'get_stats') ? $creator->get_stats() : [];
        $loaded_modules = $loader->get_loaded_modules();
        $modules_info = [];

        foreach ($loaded_modules as $module_id => $module_instance) {
            $page_count = 0;
            $is_migrated = method_exists($module_instance, 'get_pages_definition');

            if ($is_migrated) {
                $definitions = $module_instance->get_pages_definition();
                $page_count = is_array($definitions) ? count($definitions) : 0;
                $is_migrated = $page_count > 0;
            }

            $modules_info[] = [
                'id' => (string) $module_id,
                'name' => $this->get_module_label($module_instance, $module_id),
                'page_count' => $page_count,
                'is_migrated' => $is_migrated,
            ];
        }

        usort($modules_info, function ($left, $right) {
            return strcasecmp($left['name'], $right['name']);
        });

        $migrated = array_values(array_filter($modules_info, function ($module_info) {
            return !empty($module_info['is_migrated']);
        }));
        $pending = array_values(array_filter($modules_info, function ($module_info) {
            return empty($module_info['is_migrated']);
        }));

        $total_modules = count($modules_info);
        $migrated_count = count($migrated);

        return [
            'available' => true,
            'total_modules' => $total_modules,
            'migrated_modules' => $migrated_count,
            'pending_modules' => count($pending),
            'defined_pages' => isset($stats['total_pages_defined']) ? (int) $stats['total_pages_defined'] : 0,
            'created_pages' => isset($stats['total_pages_created']) ? (int) $stats['total_pages_created'] : 0,
            'migration_percentage' => $total_modules > 0 ? round(($migrated_count / $total_modules) * 100, 2) : 0,
            'migrated_preview' => array_slice($migrated, 0, 8),
            'pending_preview' => array_slice($pending, 0, 8),
        ];
    }

    /**
     * Obtiene una etiqueta legible del módulo.
     *
     * @param object $module_instance Instancia del módulo.
     * @param string $module_id ID del módulo.
     * @return string
     */
    private function get_module_label($module_instance, $module_id) {
        if (is_object($module_instance)) {
            if (method_exists($module_instance, 'get_name')) {
                return (string) $module_instance->get_name();
            }

            if (isset($module_instance->name) && is_string($module_instance->name) && $module_instance->name !== '') {
                return $module_instance->name;
            }
        }

        return (string) $module_id;
    }

    /**
     * Maneja la creación de páginas
     */
    public function handle_create_pages() {
        check_admin_referer('flavor_create_pages_nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $resultado = Flavor_Page_Creator::create_all_pages();

        Flavor_Chat_Helpers::safe_redirect(add_query_arg([
            'page' => 'flavor-create-pages',
            'created' => $resultado['total'],
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Maneja la eliminación de páginas
     */
    public function handle_delete_pages() {
        check_admin_referer('flavor_delete_pages_nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $paginas_eliminadas = Flavor_Page_Creator::delete_all_pages();

        Flavor_Chat_Helpers::safe_redirect(add_query_arg([
            'page' => 'flavor-create-pages',
            'deleted' => count($paginas_eliminadas),
        ], admin_url('admin.php')));
        exit;
    }
}

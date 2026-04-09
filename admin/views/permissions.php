<?php
/**
 * Panel de administracion de permisos
 *
 * Vista para gestionar roles y capabilities de Flavor Platform
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('flavor_manage_permissions') && !current_user_can('administrator')) {
    wp_die(__('No tienes permisos para acceder a esta pagina.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

$role_manager = Flavor_Role_Manager::get_instance();
$tabs_activa = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'roles';
$modulos = $role_manager->obtener_modulos_con_capabilities();
$roles_definidos = $role_manager->obtener_roles();
$roles_personalizados = $role_manager->obtener_roles_personalizados();
$capabilities_agrupadas = $role_manager->obtener_capabilities_agrupadas();

// Mensajes de feedback
$mensaje = '';
$tipo_mensaje = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flavor_permissions_nonce'])) {
    if (wp_verify_nonce($_POST['flavor_permissions_nonce'], 'flavor_manage_permissions')) {
        $accion = isset($_POST['accion']) ? sanitize_key($_POST['accion']) : '';

        switch ($accion) {
            case 'crear_rol':
                $datos_rol = [
                    'slug' => sanitize_key($_POST['rol_slug']),
                    'label' => sanitize_text_field($_POST['rol_label']),
                    'description' => sanitize_textarea_field($_POST['rol_description']),
                    'capabilities' => isset($_POST['capabilities']) ? array_map('sanitize_key', $_POST['capabilities']) : [],
                    'modulo' => !empty($_POST['rol_modulo']) ? sanitize_key($_POST['rol_modulo']) : null,
                ];

                $resultado = $role_manager->crear_rol_personalizado($datos_rol);

                if (is_wp_error($resultado)) {
                    $mensaje = $resultado->get_error_message();
                    $tipo_mensaje = 'error';
                } else {
                    $mensaje = __('Rol creado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $tipo_mensaje = 'success';
                    $roles_personalizados = $role_manager->obtener_roles_personalizados();
                }
                break;

            case 'eliminar_rol':
                $slug = sanitize_key($_POST['rol_slug']);
                if ($role_manager->eliminar_rol_personalizado($slug)) {
                    $mensaje = __('Rol eliminado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $tipo_mensaje = 'success';
                    $roles_personalizados = $role_manager->obtener_roles_personalizados();
                } else {
                    $mensaje = __('No se pudo eliminar el rol.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $tipo_mensaje = 'error';
                }
                break;

            case 'actualizar_capabilities':
                $slug = sanitize_key($_POST['rol_slug']);
                $nuevas_caps = isset($_POST['capabilities']) ? array_map('sanitize_key', $_POST['capabilities']) : [];

                if ($role_manager->actualizar_capabilities_rol($slug, $nuevas_caps)) {
                    $mensaje = __('Permisos actualizados correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = __('No se pudieron actualizar los permisos.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $tipo_mensaje = 'error';
                }
                break;

            case 'asignar_rol_usuario':
                $user_id = intval($_POST['user_id']);
                $modulo = sanitize_key($_POST['modulo']);
                $rol = sanitize_key($_POST['rol']);

                if (Flavor_Permission_Helper::assign_module_role($user_id, $modulo, $rol)) {
                    $mensaje = __('Rol asignado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = __('No se pudo asignar el rol.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $tipo_mensaje = 'error';
                }
                break;

            case 'revocar_rol_usuario':
                $user_id = intval($_POST['user_id']);
                $modulo = sanitize_key($_POST['modulo']);

                if (Flavor_Permission_Helper::revoke_module_role($user_id, $modulo)) {
                    $mensaje = __('Rol revocado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = __('No se pudo revocar el rol.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $tipo_mensaje = 'error';
                }
                break;
        }
    }
}
?>

<div class="wrap flavor-permissions-admin">
    <h1>
        <span class="dashicons dashicons-shield-alt"></span>
        <?php esc_html_e('Gestion de Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <?php if ($mensaje): ?>
        <div class="notice notice-<?php echo esc_attr($tipo_mensaje); ?> <?php esc_html_e('is-dismissible">
', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>            <p><?php echo esc_html($mensaje); ?></p>
        </div>
    <?php endif; ?>

    <nav class="nav-tab-wrapper">
        <a href="?page=flavor-permissions&tab=roles"
           class="nav-tab <?php echo $tabs_activa === 'roles' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-groups"></span>
            <?php esc_html_e('Roles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <a href="?page=flavor-permissions&tab=capabilities"
           class="nav-tab <?php echo $tabs_activa === 'capabilities' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-network"></span>
            <?php esc_html_e('Capabilities', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <a href="?page=flavor-permissions&tab=usuarios"
           class="nav-tab <?php echo $tabs_activa === 'usuarios' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-users"></span>
            <?php esc_html_e('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <a href="?page=flavor-permissions&tab=modulos"
           class="nav-tab <?php echo $tabs_activa === 'modulos' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-screenoptions"></span>
            <?php esc_html_e('Por Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php
        switch ($tabs_activa) {
            case 'roles':
                include dirname(__FILE__) . '/permissions-tab-roles.php';
                break;
            case 'capabilities':
                include dirname(__FILE__) . '/permissions-tab-capabilities.php';
                break;
            case 'usuarios':
                include dirname(__FILE__) . '/permissions-tab-usuarios.php';
                break;
            case 'modulos':
                include dirname(__FILE__) . '/permissions-tab-modulos.php';
                break;
            default:
                include dirname(__FILE__) . '/permissions-tab-roles.php';
        }
        ?>
    </div>
</div>

<style>
.flavor-permissions-admin {
    max-width: 1400px;
}

.flavor-permissions-admin h1 {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-permissions-admin .nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.flavor-permissions-admin .nav-tab .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.tab-content {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    padding: 20px;
}

.flavor-card {
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}

.flavor-card h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.capabilities-matrix {
    overflow-x: auto;
}

.capabilities-matrix table {
    border-collapse: collapse;
    width: 100%;
}

.capabilities-matrix th,
.capabilities-matrix td {
    padding: 8px 12px;
    border: 1px solid #ddd;
    text-align: center;
}

.capabilities-matrix th {
    background: #f5f5f5;
    font-weight: 600;
}

.capabilities-matrix th.capability-name {
    text-align: left;
    min-width: 200px;
}

.capabilities-matrix td.capability-name {
    text-align: left;
    font-size: 13px;
}

.capabilities-matrix .cap-granted {
    color: #46b450;
}

.capabilities-matrix .cap-denied {
    color: #dc3232;
}

.capabilities-matrix input[type="checkbox"] {
    margin: 0;
}

.role-card {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 15px;
    background: #fafafa;
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    margin-bottom: 10px;
}

.role-card h4 {
    margin: 0 0 5px 0;
}

.role-card .role-meta {
    font-size: 12px;
    color: #666;
}

.role-card .role-actions {
    display: flex;
    gap: 5px;
}

.module-section {
    margin-bottom: 30px;
}

.module-section h3 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 10px 15px;
    margin: 0;
    border-radius: 4px 4px 0 0;
}

.module-capabilities {
    border: 1px solid #ddd;
    border-top: none;
    padding: 15px;
    border-radius: 0 0 4px 4px;
}

.user-permissions-card {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
}

.user-permissions-card .user-info {
    border-right: 1px solid #eee;
    padding-right: 20px;
}

.user-permissions-card .user-info img {
    border-radius: 50%;
    margin-bottom: 10px;
}

.user-permissions-card .user-modules {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.module-role-badge {
    display: inline-block;
    padding: 3px 8px;
    background: #e7e7e7;
    border-radius: 3px;
    font-size: 12px;
}

.module-role-badge.has-role {
    background: #d4edda;
    color: #155724;
}

.create-role-form {
    max-width: 600px;
}

.create-role-form .form-field {
    margin-bottom: 15px;
}

.create-role-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.create-role-form input[type="text"],
.create-role-form textarea,
.create-role-form select {
    width: 100%;
    max-width: 400px;
}

.capabilities-checklist {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    background: #fafafa;
}

.capabilities-checklist .cap-group {
    margin-bottom: 15px;
}

.capabilities-checklist .cap-group-title {
    font-weight: 600;
    margin-bottom: 8px;
    padding-bottom: 5px;
    border-bottom: 1px solid #ddd;
}

.capabilities-checklist label {
    display: block;
    padding: 3px 0;
    font-weight: normal;
}

.inline-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.inline-form select,
.inline-form input {
    min-width: 150px;
}

@media screen and (max-width: 782px) {
    .user-permissions-card {
        grid-template-columns: 1fr;
    }

    .user-permissions-card .user-info {
        border-right: none;
        border-bottom: 1px solid #eee;
        padding-right: 0;
        padding-bottom: 15px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle de capabilities por grupo
    $('.toggle-capabilities').on('click', function() {
        var grupo = $(this).data('grupo');
        var checked = $(this).prop('checked');
        $('input[data-grupo="' + grupo + '"]').prop('checked', checked);
    });

    // Confirmacion para eliminar rol
    $('.delete-role-btn').on('click', function(e) {
        if (!confirm('<?php echo esc_js(__('¿Estas seguro de que deseas eliminar este rol?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
            e.preventDefault();
        }
    });

    // Filtro de usuarios
    $('#filter-users').on('keyup', function() {
        var filter = $(this).val().toLowerCase();
        $('.user-permissions-card').each(function() {
            var nombre = $(this).find('.user-name').text().toLowerCase();
            var email = $(this).find('.user-email').text().toLowerCase();
            if (nombre.indexOf(filter) > -1 || email.indexOf(filter) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Cambio dinamico de roles por modulo
    $('.module-role-select').on('change', function() {
        var $form = $(this).closest('form');
        if ($(this).val()) {
            $form.find('.assign-btn').prop('disabled', false);
        } else {
            $form.find('.assign-btn').prop('disabled', true);
        }
    });
});
</script>

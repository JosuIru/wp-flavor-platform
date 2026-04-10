<?php
/**
 * Vista de administración de Deep Links
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-deep-links-admin">
    <?php if (class_exists('Flavor_Admin_Page_Chrome')) : ?>
        <?php Flavor_Admin_Page_Chrome::render_breadcrumbs('configuration', 'flavor-platform-deep-links', __('Deep Links', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
        <?php Flavor_Admin_Page_Chrome::render_compact_nav(Flavor_Admin_Page_Chrome::get_section_links('configuration', 'flavor-platform-deep-links')); ?>
    <?php endif; ?>
    <div class="notice notice-info flavor-admin-callout">
        <div class="flavor-admin-callout__content">
            <p class="flavor-admin-callout__text">
                <?php _e('Este gestor dedicado complementa la pestaña de Deep Links dentro de Apps Móviles. Aquí puedes revisar configuraciones multiempresa y gestionar registros específicos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <div class="flavor-admin-callout__actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-apps&tab=deeplinks')); ?>" class="button button-secondary">
                    <?php _e('Volver a Apps Móviles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>
    <h1 class="wp-heading-inline">
        <?php _e('Gestión de Deep Links para Apps', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <button type="button" class="page-title-action flavor-add-company">
        <?php _e('Nueva Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </button>
    <hr class="wp-header-end">

    <div class="flavor-dl-intro">
        <p><?php _e('Configura enlaces dinámicos para que las apps móviles se conecten automáticamente a diferentes empresas u organizaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <p><?php _e('Cada empresa puede tener su propia configuración de colores, logo y módulos activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>

    <!-- Lista de empresas -->
    <div class="flavor-dl-companies-list">
        <?php if (empty($companies)): ?>
            <div class="flavor-dl-empty-state">
                <div class="dashicons dashicons-smartphone"></div>
                <h3><?php _e('No hay empresas configuradas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Crea la primera configuración para generar enlaces de deep linking', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <button type="button" class="button button-primary flavor-add-company">
                    <?php _e('Crear Primera Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        <?php else: ?>
            <div class="flavor-dl-grid">
                <?php foreach ($companies as $company):
                    $config_data = json_decode($company->configuracion, true);
                    $colores = $config_data['colores'] ?? [];
                ?>
                    <div class="flavor-dl-card" data-slug="<?php echo esc_attr($company->slug); ?>">
                        <div class="flavor-dl-card-header">
                            <div class="flavor-dl-logo">
                                <?php if ($company->logo_url): ?>
                                    <img src="<?php echo esc_url($company->logo_url); ?>" alt="<?php echo esc_attr($company->nombre); ?>">
                                <?php else: ?>
                                    <div class="flavor-dl-logo-placeholder">
                                        <span class="dashicons dashicons-building"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-dl-status <?php echo $company->activo ? 'active' : 'inactive'; ?>">
                                <?php echo $company->activo ? __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>

                        <div class="flavor-dl-card-body">
                            <h3><?php echo esc_html($company->nombre); ?></h3>
                            <p class="flavor-dl-slug"><?php echo esc_html($company->slug); ?></p>
                            <?php if ($company->descripcion): ?>
                                <p class="flavor-dl-description"><?php echo esc_html($company->descripcion); ?></p>
                            <?php endif; ?>

                            <div class="flavor-dl-colors">
                                <?php if (!empty($colores['primario'])): ?>
                                    <span class="flavor-dl-color-sample" data-color="<?php echo esc_attr($colores['primario']); ?>" title="Color primario"></span>
                                <?php endif; ?>
                                <?php if (!empty($colores['secundario'])): ?>
                                    <span class="flavor-dl-color-sample" data-color="<?php echo esc_attr($colores['secundario']); ?>" title="Color secundario"></span>
                                <?php endif; ?>
                                <?php if (!empty($colores['acento'])): ?>
                                    <span class="flavor-dl-color-sample" data-color="<?php echo esc_attr($colores['acento']); ?>" title="Color acento"></span>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-dl-api-base">
                                <strong><?php _e('API:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <code><?php echo esc_html($company->api_base); ?></code>
                            </div>
                        </div>

                        <div class="flavor-dl-card-footer">
                            <button type="button" class="button flavor-edit-company" data-slug="<?php echo esc_attr($company->slug); ?>">
                                <span class="dashicons dashicons-edit"></span>
                                <?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button type="button" class="button flavor-generate-links" data-slug="<?php echo esc_attr($company->slug); ?>">
                                <span class="dashicons dashicons-share"></span>
                                <?php _e('Enlaces', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button type="button" class="button button-link-delete flavor-delete-company" data-slug="<?php echo esc_attr($company->slug); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para crear/editar empresa -->
    <div id="flavor-dl-modal" class="flavor-dl-modal flavor-dl-modal--hidden">
        <div class="flavor-dl-modal-overlay"></div>
        <div class="flavor-dl-modal-content">
            <div class="flavor-dl-modal-header">
                <h2 id="flavor-dl-modal-title"><?php _e('Nueva Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <button type="button" class="flavor-dl-modal-close">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
            <div class="flavor-dl-modal-body">
                <form id="flavor-dl-company-form">
                    <input type="hidden" id="company-id" name="id">

                    <!-- Información básica -->
                    <div class="flavor-dl-form-section">
                        <h3><?php _e('Información Básica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                        <div class="flavor-dl-form-group">
                            <label for="company-slug"><?php _e('Slug (identificador único)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                            <input type="text" id="company-slug" name="slug" required pattern="[a-z0-9-]+" class="widefat">
                            <p class="description"><?php _e('Solo letras minúsculas, números y guiones. Ej: basabere, acme-corp', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>

                        <div class="flavor-dl-form-group">
                            <label for="company-nombre"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                            <input type="text" id="company-nombre" name="nombre" required class="widefat">
                        </div>

                        <div class="flavor-dl-form-group">
                            <label for="company-descripcion"><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <textarea id="company-descripcion" name="descripcion" rows="3" class="widefat"></textarea>
                        </div>

                        <div class="flavor-dl-form-group">
                            <label for="company-logo"><?php _e('URL del Logo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="url" id="company-logo" name="logo_url" class="widefat">
                            <button type="button" class="button flavor-upload-logo"><?php _e('Seleccionar Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        </div>

                        <div class="flavor-dl-form-group">
                            <label for="company-api-base"><?php _e('URL Base de API', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                            <input type="url" id="company-api-base" name="api_base" required class="widefat" placeholder="https://api.midominio.com/empresas/acme">
                            <p class="description"><?php _e('URL base donde la app enviará las peticiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>

                        <div class="flavor-dl-form-group">
                            <label>
                                <input type="checkbox" id="company-activo" name="activo" value="1" checked>
                                <?php _e('Empresa activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Colores -->
                    <div class="flavor-dl-form-section">
                        <h3><?php _e('Colores de la App', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                        <div class="flavor-dl-colors-grid">
                            <div class="flavor-dl-form-group">
                                <label for="color-primario"><?php _e('Primario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="color-primario" name="colores[primario]" value="#3B82F6" class="flavor-color-picker">
                            </div>

                            <div class="flavor-dl-form-group">
                                <label for="color-secundario"><?php _e('Secundario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="color-secundario" name="colores[secundario]" value="#8B5CF6" class="flavor-color-picker">
                            </div>

                            <div class="flavor-dl-form-group">
                                <label for="color-acento"><?php _e('Acento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="color-acento" name="colores[acento]" value="#10B981" class="flavor-color-picker">
                            </div>

                            <div class="flavor-dl-form-group">
                                <label for="color-fondo"><?php _e('Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="color-fondo" name="colores[fondo]" value="#FFFFFF" class="flavor-color-picker">
                            </div>

                            <div class="flavor-dl-form-group">
                                <label for="color-texto"><?php _e('Texto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="color-texto" name="colores[texto]" value="#1F2937" class="flavor-color-picker">
                            </div>

                            <div class="flavor-dl-form-group">
                                <label for="color-error"><?php _e('Error', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="color-error" name="colores[error]" value="#EF4444" class="flavor-color-picker">
                            </div>

                            <div class="flavor-dl-form-group">
                                <label for="color-exito"><?php _e('Éxito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="color-exito" name="colores[exito]" value="#10B981" class="flavor-color-picker">
                            </div>

                            <div class="flavor-dl-form-group">
                                <label for="color-advertencia"><?php _e('Advertencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="color-advertencia" name="colores[advertencia]" value="#F59E0B" class="flavor-color-picker">
                            </div>
                        </div>
                    </div>

                    <!-- Configuración adicional -->
                    <div class="flavor-dl-form-section">
                        <h3><?php _e('Configuración Adicional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                        <div class="flavor-dl-form-group">
                            <label for="config-tema"><?php _e('Tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select id="config-tema" name="tema" class="widefat">
                                <option value="light"><?php _e('Claro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="dark"><?php _e('Oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="auto"><?php _e('Automático (según sistema)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>

                        <div class="flavor-dl-form-group">
                            <label for="config-idioma"><?php _e('Idioma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select id="config-idioma" name="idioma" class="widefat">
                                <option value="es">Español</option>
                                <option value="eu">Euskera</option>
                                <option value="en">English</option>
                                <option value="fr">Français</option>
                            </select>
                        </div>

                        <div class="flavor-dl-form-group">
                            <label><?php _e('Módulos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <div class="flavor-dl-checkboxes">
                                <label><input type="checkbox" name="modulos_activos[]" value="chat"> Chat IA</label>
                                <label><input type="checkbox" name="modulos_activos[]" value="reservas"> Reservas</label>
                                <label><input type="checkbox" name="modulos_activos[]" value="marketplace"> Marketplace</label>
                                <label><input type="checkbox" name="modulos_activos[]" value="grupos_consumo"> Grupos de Consumo</label>
                                <label><input type="checkbox" name="modulos_activos[]" value="banco_tiempo"> Banco de Tiempo</label>
                                <label><input type="checkbox" name="modulos_activos[]" value="carpooling"> Carpooling</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="flavor-dl-modal-footer">
                <button type="button" class="button" id="flavor-dl-cancel"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <button type="button" class="button button-primary" id="flavor-dl-save"><?php _e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
        </div>
    </div>

    <!-- Modal para enlaces generados -->
    <div id="flavor-dl-links-modal" class="flavor-dl-modal flavor-dl-modal--hidden">
        <div class="flavor-dl-modal-overlay"></div>
        <div class="flavor-dl-modal-content">
            <div class="flavor-dl-modal-header">
                <h2><?php _e('Enlaces Generados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <button type="button" class="flavor-dl-modal-close">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
            <div class="flavor-dl-modal-body">
                <div id="flavor-dl-generated-links"></div>
            </div>
            <div class="flavor-dl-modal-footer">
                <button type="button" class="button" id="flavor-dl-close-links"><?php _e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
        </div>
    </div>
</div>

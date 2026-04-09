<?php
/**
 * Vista del Setup Wizard - Asistente de configuración inicial
 *
 * @package FlavorPlatform
 * @subpackage Admin/Views
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos del wizard
$pasos = $this->get_pasos();
$paso_actual = $this->get_paso_actual();
$indice_paso_actual = $this->get_indice_paso_actual();
$datos_wizard = $this->get_datos_wizard();
$temas_visuales = $this->get_temas_visuales();
$modulos_disponibles = $this->get_modulos_disponibles();
$perfiles = $this->get_perfiles();
$claves_pasos = array_keys($pasos);
$total_pasos = count($pasos);
$porcentaje_progreso = (($indice_paso_actual) / ($total_pasos - 1)) * 100;
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html__('Configuración Inicial - Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></title>
    <?php wp_head(); ?>
</head>
<body class="flavor-wizard-body">

<div class="flavor-wizard" id="flavor-wizard">
    <!-- Header del Wizard -->
    <header class="flavor-wizard__header">
        <div class="flavor-wizard__logo">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="20" cy="20" r="20" fill="url(#gradient)"/>
                <path d="M12 20C12 15.5817 15.5817 12 20 12C24.4183 12 28 15.5817 28 20C28 24.4183 24.4183 28 20 28" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="20" cy="20" r="4" fill="white"/>
                <defs>
                    <linearGradient id="gradient" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3b82f6"/>
                        <stop offset="1" stop-color="#8b5cf6"/>
                    </linearGradient>
                </defs>
            </svg>
            <span class="flavor-wizard__brand"><?php esc_html_e('Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <button type="button" class="flavor-wizard__skip-btn" id="wizard-skip-btn">
            <?php echo esc_html__('Saltar configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
    </header>

    <!-- Barra de Progreso -->
    <div class="flavor-wizard__progress">
        <div class="flavor-wizard__progress-bar">
            <div class="flavor-wizard__progress-fill" style="width: <?php echo esc_attr($porcentaje_progreso); ?>%"></div>
        </div>
        <div class="flavor-wizard__steps-nav">
            <?php foreach ($pasos as $clave_paso => $datos_paso):
                $indice_paso = array_search($clave_paso, $claves_pasos);
                $clase_estado = '';
                if ($indice_paso < $indice_paso_actual) {
                    $clase_estado = 'flavor-wizard__step--completed';
                } elseif ($indice_paso === $indice_paso_actual) {
                    $clase_estado = 'flavor-wizard__step--active';
                }
            ?>
                <div class="flavor-wizard__step <?php echo esc_attr($clase_estado); ?>"
                     data-step="<?php echo esc_attr($clave_paso); ?>"
                     data-index="<?php echo esc_attr($indice_paso); ?>">
                    <div class="flavor-wizard__step-number">
                        <?php if ($indice_paso < $indice_paso_actual): ?>
                            <span class="dashicons dashicons-yes"></span>
                        <?php else: ?>
                            <?php echo esc_html($datos_paso['numero']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-wizard__step-info">
                        <span class="flavor-wizard__step-name"><?php echo esc_html($datos_paso['nombre']); ?></span>
                        <span class="flavor-wizard__step-desc"><?php echo esc_html($datos_paso['descripcion']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Contenedor de Contenido -->
    <main class="flavor-wizard__main">
        <div class="flavor-wizard__content" id="wizard-content">

            <!-- Paso 1: Bienvenida y Tipo de Organización -->
            <section class="flavor-wizard__panel <?php echo $paso_actual === 'bienvenida' ? 'flavor-wizard__panel--active' : ''; ?>"
                     data-step="bienvenida">
                <div class="flavor-wizard__panel-header">
                    <h1 class="flavor-wizard__title">
                        <?php echo esc_html__('Bienvenido a Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h1>
                    <p class="flavor-wizard__subtitle">
                        <?php echo esc_html__('Selecciona el tipo de organización que mejor describe tu proyecto. Esto nos ayudará a configurar los módulos adecuados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <div class="flavor-wizard__profiles-grid">
                    <?php foreach ($perfiles as $clave_perfil => $datos_perfil):
                        $es_seleccionado = ($datos_wizard['perfil'] === $clave_perfil);
                        $color_perfil = $datos_perfil['color'] ?? '#3b82f6';
                    ?>
                        <label class="flavor-wizard__profile-card <?php echo $es_seleccionado ? 'flavor-wizard__profile-card--selected' : ''; ?>"
                               style="--profile-color: <?php echo esc_attr($color_perfil); ?>">
                            <input type="radio"
                                   name="perfil"
                                   value="<?php echo esc_attr($clave_perfil); ?>"
                                   <?php checked($es_seleccionado); ?>
                                   class="flavor-wizard__profile-input">
                            <div class="flavor-wizard__profile-icon">
                                <span class="dashicons <?php echo esc_attr($datos_perfil['icono']); ?>"></span>
                            </div>
                            <div class="flavor-wizard__profile-content">
                                <h3 class="flavor-wizard__profile-name"><?php echo esc_html($datos_perfil['nombre']); ?></h3>
                                <p class="flavor-wizard__profile-desc"><?php echo esc_html($datos_perfil['descripcion']); ?></p>
                            </div>
                            <div class="flavor-wizard__profile-check">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Paso 2: Información Básica -->
            <section class="flavor-wizard__panel <?php echo $paso_actual === 'info_basica' ? 'flavor-wizard__panel--active' : ''; ?>"
                     data-step="info_basica">
                <div class="flavor-wizard__panel-header">
                    <h1 class="flavor-wizard__title">
                        <?php echo esc_html__('Información de tu sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h1>
                    <p class="flavor-wizard__subtitle">
                        <?php echo esc_html__('Configura el nombre, logo y colores principales de tu plataforma.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <div class="flavor-wizard__form-grid">
                    <!-- Nombre del sitio -->
                    <div class="flavor-wizard__form-group flavor-wizard__form-group--full">
                        <label for="nombre_sitio" class="flavor-wizard__label">
                            <?php echo esc_html__('Nombre del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <span class="flavor-wizard__required">*</span>
                        </label>
                        <input type="text"
                               id="nombre_sitio"
                               name="nombre_sitio"
                               class="flavor-wizard__input"
                               value="<?php echo esc_attr($datos_wizard['nombre_sitio']); ?>"
                               placeholder="<?php echo esc_attr__('Ej: Mi Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                               required>
                        <span class="flavor-wizard__input-error"></span>
                    </div>

                    <!-- Logo -->
                    <div class="flavor-wizard__form-group flavor-wizard__form-group--full">
                        <label class="flavor-wizard__label">
                            <?php echo esc_html__('Logo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <div class="flavor-wizard__logo-upload">
                            <div class="flavor-wizard__logo-preview" id="logo-preview">
                                <?php if (!empty($datos_wizard['logo_url'])): ?>
                                    <img src="<?php echo esc_url($datos_wizard['logo_url']); ?>" alt="<?php esc_attr_e('Logo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <?php else: ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                    <span class="flavor-wizard__logo-placeholder-text">
                                        <?php echo esc_html__('Sin logo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-wizard__logo-actions">
                                <button type="button" class="flavor-wizard__btn flavor-wizard__btn--secondary" id="upload-logo-btn">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php echo esc_html__('Subir logo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                                <button type="button" class="flavor-wizard__btn flavor-wizard__btn--text" id="remove-logo-btn"
                                        style="<?php echo empty($datos_wizard['logo_url']) ? 'display:none;' : ''; ?>">
                                    <?php echo esc_html__('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                                <input type="hidden" id="logo_url" name="logo_url" value="<?php echo esc_attr($datos_wizard['logo_url']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Colores -->
                    <div class="flavor-wizard__form-group">
                        <label for="color_primario" class="flavor-wizard__label">
                            <?php echo esc_html__('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <div class="flavor-wizard__color-picker-wrap">
                            <input type="text"
                                   id="color_primario"
                                   name="color_primario"
                                   class="flavor-wizard__color-picker"
                                   value="<?php echo esc_attr($datos_wizard['color_primario']); ?>">
                        </div>
                    </div>

                    <div class="flavor-wizard__form-group">
                        <label for="color_secundario" class="flavor-wizard__label">
                            <?php echo esc_html__('Color secundario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <div class="flavor-wizard__color-picker-wrap">
                            <input type="text"
                                   id="color_secundario"
                                   name="color_secundario"
                                   class="flavor-wizard__color-picker"
                                   value="<?php echo esc_attr($datos_wizard['color_secundario']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Preview en vivo -->
                <div class="flavor-wizard__live-preview">
                    <h3 class="flavor-wizard__preview-title">
                        <?php echo esc_html__('Vista previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="flavor-wizard__preview-card" id="info-preview">
                        <div class="flavor-wizard__preview-header" id="preview-header">
                            <div class="flavor-wizard__preview-logo" id="preview-logo">
                                <?php if (!empty($datos_wizard['logo_url'])): ?>
                                    <img src="<?php echo esc_url($datos_wizard['logo_url']); ?>" alt="<?php esc_attr_e('Logo preview', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <?php else: ?>
                                    <span class="dashicons dashicons-admin-site"></span>
                                <?php endif; ?>
                            </div>
                            <span class="flavor-wizard__preview-name" id="preview-name">
                                <?php echo esc_html($datos_wizard['nombre_sitio'] ?: 'Mi Sitio'); ?>
                            </span>
                        </div>
                        <div class="flavor-wizard__preview-body">
                            <div class="flavor-wizard__preview-btn" id="preview-btn-primary">
                                <?php echo esc_html__('Botón primario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                            <div class="flavor-wizard__preview-btn flavor-wizard__preview-btn--secondary" id="preview-btn-secondary">
                                <?php echo esc_html__('Botón secundario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Paso 3: Módulos Adicionales -->
            <section class="flavor-wizard__panel <?php echo $paso_actual === 'modulos' ? 'flavor-wizard__panel--active' : ''; ?>"
                     data-step="modulos">
                <div class="flavor-wizard__panel-header">
                    <h1 class="flavor-wizard__title">
                        <?php echo esc_html__('Selecciona módulos adicionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h1>
                    <p class="flavor-wizard__subtitle">
                        <?php echo esc_html__('Activa las funcionalidades que necesites. Puedes cambiarlas en cualquier momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <!-- Filtro por categorías -->
                <div class="flavor-wizard__module-filters">
                    <button type="button" class="flavor-wizard__filter-btn flavor-wizard__filter-btn--active" data-category="all">
                        <?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-wizard__filter-btn" data-category="contenido">
                        <?php echo esc_html__('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-wizard__filter-btn" data-category="comunidad">
                        <?php echo esc_html__('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-wizard__filter-btn" data-category="comercio">
                        <?php echo esc_html__('Comercio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-wizard__filter-btn" data-category="comunicacion">
                        <?php echo esc_html__('Comunicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-wizard__filter-btn" data-category="operaciones">
                        <?php echo esc_html__('Operaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-wizard__filter-btn" data-category="sostenibilidad">
                        <?php echo esc_html__('Sostenibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-wizard__filter-btn" data-category="gobernanza">
                        <?php echo esc_html__('Gobernanza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <div class="flavor-wizard__modules-grid">
                    <?php foreach ($modulos_disponibles as $clave_modulo => $datos_modulo):
                        $esta_activo = in_array($clave_modulo, $datos_wizard['modulos_activos'] ?? []);
                    ?>
                        <label class="flavor-wizard__module-card <?php echo $esta_activo ? 'flavor-wizard__module-card--active' : ''; ?>"
                               data-category="<?php echo esc_attr($datos_modulo['categoria']); ?>">
                            <input type="checkbox"
                                   name="modulos_activos[]"
                                   value="<?php echo esc_attr($clave_modulo); ?>"
                                   <?php checked($esta_activo); ?>
                                   class="flavor-wizard__module-input">
                            <div class="flavor-wizard__module-icon">
                                <span class="dashicons <?php echo esc_attr($datos_modulo['icono']); ?>"></span>
                            </div>
                            <div class="flavor-wizard__module-content">
                                <h4 class="flavor-wizard__module-name"><?php echo esc_html($datos_modulo['nombre']); ?></h4>
                                <p class="flavor-wizard__module-desc"><?php echo esc_html($datos_modulo['descripcion']); ?></p>
                            </div>
                            <div class="flavor-wizard__module-toggle">
                                <span class="flavor-wizard__toggle"></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-wizard__modules-counter">
                    <span id="modules-count">0</span> <?php echo esc_html__('módulos seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            </section>

            <!-- Paso 4: Diseño / Tema Visual -->
            <section class="flavor-wizard__panel <?php echo $paso_actual === 'diseno' ? 'flavor-wizard__panel--active' : ''; ?>"
                     data-step="diseno">
                <div class="flavor-wizard__panel-header">
                    <h1 class="flavor-wizard__title">
                        <?php echo esc_html__('Elige un tema visual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h1>
                    <p class="flavor-wizard__subtitle">
                        <?php echo esc_html__('Selecciona el estilo que mejor represente tu proyecto. Podrás personalizarlo después.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <div class="flavor-wizard__themes-container">
                    <div class="flavor-wizard__themes-grid">
                        <?php foreach ($temas_visuales as $clave_tema => $datos_tema):
                            $es_seleccionado = ($datos_wizard['tema_visual'] === $clave_tema);
                        ?>
                            <label class="flavor-wizard__theme-card <?php echo $es_seleccionado ? 'flavor-wizard__theme-card--selected' : ''; ?>"
                                   data-theme="<?php echo esc_attr($clave_tema); ?>">
                                <input type="radio"
                                       name="tema_visual"
                                       value="<?php echo esc_attr($clave_tema); ?>"
                                       <?php checked($es_seleccionado); ?>
                                       class="flavor-wizard__theme-input">
                                <div class="flavor-wizard__theme-preview <?php echo esc_attr($datos_tema['preview_class']); ?>"
                                     style="--theme-primary: <?php echo esc_attr($datos_tema['color_primario']); ?>;
                                            --theme-secondary: <?php echo esc_attr($datos_tema['color_secundario']); ?>;
                                            --theme-bg: <?php echo esc_attr($datos_tema['color_fondo']); ?>;">
                                    <div class="flavor-wizard__theme-mockup">
                                        <div class="flavor-wizard__theme-mockup-header"></div>
                                        <div class="flavor-wizard__theme-mockup-content">
                                            <div class="flavor-wizard__theme-mockup-card"></div>
                                            <div class="flavor-wizard__theme-mockup-btn"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flavor-wizard__theme-info">
                                    <h4 class="flavor-wizard__theme-name"><?php echo esc_html($datos_tema['nombre']); ?></h4>
                                    <p class="flavor-wizard__theme-desc"><?php echo esc_html($datos_tema['descripcion']); ?></p>
                                </div>
                                <div class="flavor-wizard__theme-check">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    $starter_theme = wp_get_theme('flavor-starter');
                    $starter_bundled = file_exists(FLAVOR_CHAT_IA_PATH . 'assets/companion-theme/flavor-starter/style.css');
                    if (get_stylesheet() !== 'flavor-starter' && ($starter_theme->exists() || $starter_bundled)) :
                        $starter_action = $starter_theme->exists() ? 'flavor_activate_starter_theme' : 'flavor_install_starter_theme';
                        $starter_url = wp_nonce_url(admin_url('admin-post.php?action=' . $starter_action), $starter_action);
                    ?>
                        <div class="flavor-wizard__theme-cta" style="margin-top: 16px;">
                            <a class="button button-primary" href="<?php echo esc_url($starter_url); ?>">
                                <?php echo esc_html($starter_theme->exists() ? __('Activar tema Flavor Starter', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Instalar y activar tema Flavor Starter', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Preview grande del tema seleccionado -->
                    <div class="flavor-wizard__theme-full-preview" id="theme-full-preview">
                        <h3 class="flavor-wizard__preview-title">
                            <?php echo esc_html__('Vista previa de la landing', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <div class="flavor-wizard__landing-preview" id="landing-preview">
                            <div class="flavor-wizard__landing-header">
                                <div class="flavor-wizard__landing-logo"></div>
                                <div class="flavor-wizard__landing-nav">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                            <div class="flavor-wizard__landing-hero">
                                <div class="flavor-wizard__landing-title"></div>
                                <div class="flavor-wizard__landing-subtitle"></div>
                                <div class="flavor-wizard__landing-cta"></div>
                            </div>
                            <div class="flavor-wizard__landing-features">
                                <div class="flavor-wizard__landing-feature"></div>
                                <div class="flavor-wizard__landing-feature"></div>
                                <div class="flavor-wizard__landing-feature"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Paso 5: Datos Demo -->
            <section class="flavor-wizard__panel <?php echo $paso_actual === 'demo_data' ? 'flavor-wizard__panel--active' : ''; ?>"
                     data-step="demo_data">
                <div class="flavor-wizard__panel-header">
                    <h1 class="flavor-wizard__title">
                        <?php echo esc_html__('Importar datos de ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h1>
                    <p class="flavor-wizard__subtitle">
                        <?php echo esc_html__('Puedes importar contenido de ejemplo para explorar las funcionalidades de tu plataforma.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <div class="flavor-wizard__demo-options">
                    <div class="flavor-wizard__demo-card flavor-wizard__demo-card--import">
                        <div class="flavor-wizard__demo-icon">
                            <span class="dashicons dashicons-database-import"></span>
                        </div>
                        <div class="flavor-wizard__demo-content">
                            <h3><?php echo esc_html__('Importar datos de ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p><?php echo esc_html__('Se crearán contenidos ficticios adaptados al perfil que has seleccionado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                            <ul class="flavor-wizard__demo-list" id="demo-content-list">
                                <!-- Se llenará dinámicamente según el perfil -->
                            </ul>

                            <div class="flavor-wizard__demo-actions">
                                <button type="button" class="flavor-wizard__btn flavor-wizard__btn--primary" id="import-demo-btn">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php echo esc_html__('Importar datos de ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>

                            <!-- Barra de progreso de importación -->
                            <div class="flavor-wizard__import-progress" id="import-progress" style="display: none;">
                                <div class="flavor-wizard__import-progress-bar">
                                    <div class="flavor-wizard__import-progress-fill" id="import-progress-fill"></div>
                                </div>
                                <span class="flavor-wizard__import-status" id="import-status">
                                    <?php echo esc_html__('Importando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            </div>

                            <!-- Resultado de la importación -->
                            <div class="flavor-wizard__import-result" id="import-result" style="display: none;">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span id="import-result-text"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flavor-wizard__demo-card flavor-wizard__demo-card--skip">
                        <div class="flavor-wizard__demo-icon">
                            <span class="dashicons dashicons-no-alt"></span>
                        </div>
                        <div class="flavor-wizard__demo-content">
                            <h3><?php echo esc_html__('Empezar desde cero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p><?php echo esc_html__('Prefiero crear mi propio contenido sin datos de ejemplo. Puedo importarlos más tarde desde el panel de administración.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="importar_demo" id="importar_demo" value="<?php echo $datos_wizard['importar_demo'] ? 'true' : 'false'; ?>">
            </section>

            <!-- Paso 6: Notificaciones -->
            <section class="flavor-wizard__panel <?php echo $paso_actual === 'notificaciones' ? 'flavor-wizard__panel--active' : ''; ?>"
                     data-step="notificaciones">
                <div class="flavor-wizard__panel-header">
                    <h1 class="flavor-wizard__title">
                        <?php echo esc_html__('Configurar notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h1>
                    <p class="flavor-wizard__subtitle">
                        <?php echo esc_html__('Define cómo quieres mantener informados a tus usuarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <div class="flavor-wizard__notifications-grid">
                    <!-- Email -->
                    <div class="flavor-wizard__notification-card">
                        <div class="flavor-wizard__notification-header">
                            <div class="flavor-wizard__notification-icon">
                                <span class="dashicons dashicons-email"></span>
                            </div>
                            <div class="flavor-wizard__notification-toggle-wrap">
                                <label class="flavor-wizard__switch">
                                    <input type="checkbox"
                                           name="notificaciones_email"
                                           id="notificaciones_email"
                                           value="<?php echo esc_attr__('true', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                           <?php checked($datos_wizard['notificaciones_email']); ?>>
                                    <span class="flavor-wizard__switch-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="flavor-wizard__notification-content">
                            <h3><?php echo esc_html__('Notificaciones por Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p><?php echo esc_html__('Enviar emails automáticos para eventos, recordatorios y actualizaciones importantes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="flavor-wizard__notification-options" id="email-options">
                            <div class="flavor-wizard__form-group">
                                <label for="email_remitente" class="flavor-wizard__label">
                                    <?php echo esc_html__('Email remitente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </label>
                                <input type="email"
                                       id="email_remitente"
                                       name="email_remitente"
                                       class="flavor-wizard__input"
                                       value="<?php echo esc_attr(get_option('admin_email')); ?>"
                                       placeholder="<?php echo esc_attr__('noreply@tusitio.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Push -->
                    <div class="flavor-wizard__notification-card">
                        <div class="flavor-wizard__notification-header">
                            <div class="flavor-wizard__notification-icon">
                                <span class="dashicons dashicons-bell"></span>
                            </div>
                            <div class="flavor-wizard__notification-toggle-wrap">
                                <label class="flavor-wizard__switch">
                                    <input type="checkbox"
                                           name="notificaciones_push"
                                           id="notificaciones_push"
                                           value="<?php echo esc_attr__('true', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                           <?php checked($datos_wizard['notificaciones_push']); ?>>
                                    <span class="flavor-wizard__switch-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="flavor-wizard__notification-content">
                            <h3><?php echo esc_html__('Notificaciones Push', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p><?php echo esc_html__('Enviar notificaciones push al navegador o aplicación móvil para alertas en tiempo real.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="flavor-wizard__notification-info">
                            <span class="dashicons dashicons-info-outline"></span>
                            <?php echo esc_html__('Requiere configurar Firebase Cloud Messaging después.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Paso 7: Resumen y Finalizar -->
            <section class="flavor-wizard__panel <?php echo $paso_actual === 'resumen' ? 'flavor-wizard__panel--active' : ''; ?>"
                     data-step="resumen">
                <div class="flavor-wizard__panel-header">
                    <h1 class="flavor-wizard__title">
                        <?php echo esc_html__('Resumen de configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h1>
                    <p class="flavor-wizard__subtitle">
                        <?php echo esc_html__('Revisa tu configuración antes de finalizar. Podrás cambiar todo esto después.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <div class="flavor-wizard__summary">
                    <!-- Perfil -->
                    <div class="flavor-wizard__summary-item">
                        <div class="flavor-wizard__summary-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                        <div class="flavor-wizard__summary-content">
                            <h4><?php echo esc_html__('Tipo de organización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <p id="summary-perfil">-</p>
                        </div>
                        <button type="button" class="flavor-wizard__summary-edit" data-goto="bienvenida">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </div>

                    <!-- Info básica -->
                    <div class="flavor-wizard__summary-item">
                        <div class="flavor-wizard__summary-icon">
                            <span class="dashicons dashicons-admin-settings"></span>
                        </div>
                        <div class="flavor-wizard__summary-content">
                            <h4><?php echo esc_html__('Información del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <p id="summary-info">-</p>
                        </div>
                        <button type="button" class="flavor-wizard__summary-edit" data-goto="info_basica">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </div>

                    <!-- Módulos -->
                    <div class="flavor-wizard__summary-item">
                        <div class="flavor-wizard__summary-icon">
                            <span class="dashicons dashicons-admin-plugins"></span>
                        </div>
                        <div class="flavor-wizard__summary-content">
                            <h4><?php echo esc_html__('Módulos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <p id="summary-modulos">-</p>
                        </div>
                        <button type="button" class="flavor-wizard__summary-edit" data-goto="modulos">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </div>

                    <!-- Tema -->
                    <div class="flavor-wizard__summary-item">
                        <div class="flavor-wizard__summary-icon">
                            <span class="dashicons dashicons-art"></span>
                        </div>
                        <div class="flavor-wizard__summary-content">
                            <h4><?php echo esc_html__('Tema visual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <p id="summary-tema">-</p>
                        </div>
                        <button type="button" class="flavor-wizard__summary-edit" data-goto="diseno">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </div>

                    <!-- Demo data -->
                    <div class="flavor-wizard__summary-item">
                        <div class="flavor-wizard__summary-icon">
                            <span class="dashicons dashicons-database"></span>
                        </div>
                        <div class="flavor-wizard__summary-content">
                            <h4><?php echo esc_html__('Datos de ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <p id="summary-demo">-</p>
                        </div>
                        <button type="button" class="flavor-wizard__summary-edit" data-goto="demo_data">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </div>

                    <!-- Notificaciones -->
                    <div class="flavor-wizard__summary-item">
                        <div class="flavor-wizard__summary-icon">
                            <span class="dashicons dashicons-bell"></span>
                        </div>
                        <div class="flavor-wizard__summary-content">
                            <h4><?php echo esc_html__('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <p id="summary-notificaciones">-</p>
                        </div>
                        <button type="button" class="flavor-wizard__summary-edit" data-goto="notificaciones">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </div>
                </div>

                <div class="flavor-wizard__complete-box">
                    <div class="flavor-wizard__complete-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <h3><?php echo esc_html__('Todo listo para empezar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php echo esc_html__('Haz clic en "Finalizar" para aplicar la configuración e ir al panel de control.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php
                    $starter_theme = wp_get_theme('flavor-starter');
                    $starter_bundled = file_exists(FLAVOR_CHAT_IA_PATH . 'assets/companion-theme/flavor-starter/style.css');
                    if (get_stylesheet() !== 'flavor-starter' && ($starter_theme->exists() || $starter_bundled)) :
                        $starter_action = $starter_theme->exists() ? 'flavor_activate_starter_theme' : 'flavor_install_starter_theme';
                        $starter_url = wp_nonce_url(admin_url('admin-post.php?action=' . $starter_action), $starter_action);
                    ?>
                        <p style="margin-top:10px;">
                            <a class="button button-primary" href="<?php echo esc_url($starter_url); ?>">
                                <?php echo esc_html($starter_theme->exists() ? __('Activar tema Flavor Starter', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Instalar y activar tema Flavor Starter', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

    <!-- Footer con navegación -->
    <footer class="flavor-wizard__footer">
        <div class="flavor-wizard__footer-left">
            <button type="button" class="flavor-wizard__btn flavor-wizard__btn--secondary" id="wizard-prev-btn"
                    style="<?php echo $indice_paso_actual === 0 ? 'visibility: hidden;' : ''; ?>">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php echo esc_html__('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
        <div class="flavor-wizard__footer-center">
            <span class="flavor-wizard__step-indicator">
                <?php echo esc_html__('Paso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <strong id="current-step-num"><?php echo esc_html($indice_paso_actual + 1); ?></strong>
                <?php echo esc_html__('de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <strong><?php echo esc_html($total_pasos); ?></strong>
            </span>
        </div>
        <div class="flavor-wizard__footer-right">
            <button type="button"
                    class="flavor-wizard__btn flavor-wizard__btn--success"
                    id="wizard-complete-btn"
                    style="<?php echo $paso_actual === 'resumen' ? '' : 'display: none;'; ?>">
                <span class="dashicons dashicons-yes"></span>
                <?php echo esc_html__('Finalizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button"
                    class="flavor-wizard__btn flavor-wizard__btn--primary"
                    id="wizard-next-btn"
                    style="<?php echo $paso_actual === 'resumen' ? 'display: none;' : ''; ?>">
                <?php echo esc_html__('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
        </div>
    </footer>

    <!-- Modal de progreso guardado -->
    <?php if (!empty($this->mostrar_dialogo_continuar)): ?>
    <div class="flavor-wizard__modal" id="continue-modal">
        <div class="flavor-wizard__modal-content">
            <div class="flavor-wizard__modal-icon">
                <span class="dashicons dashicons-backup"></span>
            </div>
            <h3><?php echo esc_html__('Tienes progreso guardado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php echo esc_html__('Detectamos que ya habías comenzado la configuración. ¿Quieres continuar donde lo dejaste?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-wizard__modal-actions">
                <button type="button" class="flavor-wizard__btn flavor-wizard__btn--secondary" id="start-fresh-btn">
                    <?php echo esc_html__('Empezar de nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="flavor-wizard__btn flavor-wizard__btn--primary" id="continue-btn">
                    <?php echo esc_html__('Continuar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Loader -->
    <div class="flavor-wizard__loader" id="wizard-loader" style="display: none;">
        <div class="flavor-wizard__loader-spinner"></div>
        <span id="loader-text"><?php echo esc_html__('Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </div>

</div>

<?php wp_footer(); ?>
</body>
</html>

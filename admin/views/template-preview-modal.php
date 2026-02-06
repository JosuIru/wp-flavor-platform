<?php
/**
 * Modal de Preview de Plantilla
 *
 * Muestra la vista previa de lo que se instalara al activar una plantilla.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 *
 * @var string $plantilla_id ID de la plantilla
 * @var array  $definicion   Definicion completa de la plantilla
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$plantilla_id = $plantilla_id ?? '';
$definicion = $definicion ?? [];

// Extraer datos de la definicion
$nombre_plantilla = $definicion['nombre'] ?? __('Plantilla', 'flavor-chat-ia');
$icono_plantilla = $definicion['icono'] ?? 'dashicons-admin-generic';
$color_plantilla = $definicion['color'] ?? '#2271b1';
$descripcion_plantilla = $definicion['descripcion'] ?? '';

$modulos_requeridos = $definicion['modulos_requeridos'] ?? [];
$modulos_opcionales = $definicion['modulos_opcionales'] ?? [];
$paginas = $definicion['paginas'] ?? [];
$landing = $definicion['landing'] ?? [];
$secciones_landing = $landing['secciones'] ?? [];
?>

<div class="flavor-template-modal"
     x-show="mostrarPreviewModal"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="cerrarPreviewModal()"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modal-titulo-plantilla">

    <div class="flavor-template-modal-backdrop" @click="cerrarPreviewModal()"></div>

    <div class="flavor-template-modal-content"
         x-trap.noscroll="mostrarPreviewModal"
         @click.stop>

        <!-- Header -->
        <header class="flavor-template-modal-header">
            <div class="flavor-template-modal-header-info">
                <div class="flavor-template-modal-icon" style="background: <?php echo esc_attr($color_plantilla); ?>15;">
                    <span class="dashicons <?php echo esc_attr($icono_plantilla); ?>"
                          style="color: <?php echo esc_attr($color_plantilla); ?>;"></span>
                </div>
                <div>
                    <h2 id="modal-titulo-plantilla"><?php echo esc_html($nombre_plantilla); ?></h2>
                    <p class="descripcion-corta"><?php echo esc_html($descripcion_plantilla); ?></p>
                </div>
            </div>
            <button type="button"
                    class="flavor-template-modal-close"
                    @click="cerrarPreviewModal()"
                    aria-label="<?php esc_attr_e('Cerrar modal', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </header>

        <!-- Contenido del Preview -->
        <div class="flavor-template-preview">

            <!-- Seccion de Modulos -->
            <section class="flavor-template-section">
                <h3>
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('Modulos', 'flavor-chat-ia'); ?>
                </h3>

                <?php if (!empty($modulos_requeridos) || !empty($modulos_opcionales)): ?>
                    <ul class="flavor-template-modules">
                        <?php foreach ($modulos_requeridos as $modulo_id): ?>
                            <li class="modulo-item requerido">
                                <label>
                                    <input type="checkbox"
                                           checked
                                           disabled
                                           aria-label="<?php echo esc_attr(sprintf(__('Modulo requerido: %s', 'flavor-chat-ia'), $modulo_id)); ?>">
                                    <span class="modulo-nombre"><?php echo esc_html(ucfirst(str_replace('_', ' ', $modulo_id))); ?></span>
                                    <span class="modulo-badge requerido"><?php _e('Requerido', 'flavor-chat-ia'); ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>

                        <?php foreach ($modulos_opcionales as $modulo_id): ?>
                            <li class="modulo-item opcional">
                                <label>
                                    <input type="checkbox"
                                           name="modulos_opcionales[]"
                                           value="<?php echo esc_attr($modulo_id); ?>"
                                           x-model="modulosSeleccionados"
                                           aria-label="<?php echo esc_attr(sprintf(__('Modulo opcional: %s', 'flavor-chat-ia'), $modulo_id)); ?>">
                                    <span class="modulo-nombre"><?php echo esc_html(ucfirst(str_replace('_', ' ', $modulo_id))); ?></span>
                                    <span class="modulo-badge opcional"><?php _e('Opcional', 'flavor-chat-ia'); ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="flavor-template-empty"><?php _e('Esta plantilla no incluye modulos predefinidos.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </section>

            <!-- Seccion de Paginas -->
            <section class="flavor-template-section">
                <h3>
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php _e('Paginas a crear', 'flavor-chat-ia'); ?>
                </h3>

                <?php if (!empty($paginas)): ?>
                    <ul class="flavor-template-pages">
                        <?php foreach ($paginas as $pagina_id => $pagina): ?>
                            <li class="pagina-item">
                                <span class="dashicons <?php echo esc_attr($pagina['icono'] ?? 'dashicons-media-document'); ?>"></span>
                                <span class="pagina-titulo"><?php echo esc_html($pagina['titulo'] ?? $pagina_id); ?></span>
                                <span class="pagina-slug">/<?php echo esc_html($pagina['slug'] ?? $pagina_id); ?>/</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="flavor-template-empty"><?php _e('No se crearan paginas adicionales.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </section>

            <!-- Seccion de Landing Preview -->
            <?php if (!empty($secciones_landing)): ?>
            <section class="flavor-template-section">
                <h3>
                    <span class="dashicons dashicons-welcome-widgets-menus"></span>
                    <?php _e('Secciones de Landing', 'flavor-chat-ia'); ?>
                </h3>

                <div class="flavor-template-landing-preview">
                    <?php
                    $iconos_secciones = [
                        'hero' => 'dashicons-cover-image',
                        'caracteristicas' => 'dashicons-grid-view',
                        'servicios' => 'dashicons-performance',
                        'beneficios' => 'dashicons-awards',
                        'testimonios' => 'dashicons-format-quote',
                        'equipo' => 'dashicons-groups',
                        'precios' => 'dashicons-money-alt',
                        'faq' => 'dashicons-editor-help',
                        'contacto' => 'dashicons-email-alt',
                        'cta' => 'dashicons-megaphone',
                        'galeria' => 'dashicons-images-alt2',
                        'estadisticas' => 'dashicons-chart-bar',
                        'mapa' => 'dashicons-location',
                        'newsletter' => 'dashicons-email',
                        'footer' => 'dashicons-editor-insertmore',
                    ];

                    foreach ($secciones_landing as $seccion_id => $seccion):
                        $icono_seccion = $iconos_secciones[$seccion_id] ?? 'dashicons-layout';
                        $nombre_seccion = $seccion['titulo'] ?? ucfirst(str_replace('_', ' ', $seccion_id));
                    ?>
                        <div class="landing-seccion-mini" title="<?php echo esc_attr($nombre_seccion); ?>">
                            <span class="dashicons <?php echo esc_attr($icono_seccion); ?>"></span>
                            <span class="seccion-nombre"><?php echo esc_html($nombre_seccion); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Opcion de Datos Demo -->
            <section class="flavor-template-section flavor-template-demo-option">
                <label class="flavor-demo-checkbox">
                    <input type="checkbox"
                           name="cargar_demo"
                           x-model="cargarDatosDemo"
                           value="1">
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-label">
                        <strong><?php _e('Cargar datos de demostracion', 'flavor-chat-ia'); ?></strong>
                    </span>
                </label>
                <p class="description">
                    <?php _e('Incluye contenido de ejemplo (usuarios, productos, servicios) para probar rapidamente la plantilla. Puedes eliminar estos datos mas tarde.', 'flavor-chat-ia'); ?>
                </p>
            </section>

        </div>

        <!-- Footer con acciones -->
        <footer class="flavor-template-modal-footer">
            <button type="button"
                    class="button button-secondary"
                    @click="cerrarPreviewModal()">
                <?php _e('Cancelar', 'flavor-chat-ia'); ?>
            </button>
            <button type="button"
                    class="button button-primary button-hero"
                    @click="activarPlantilla(plantillaSeleccionadaId)"
                    :disabled="activandoPlantilla">
                <span x-show="!activandoPlantilla">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Activar Plantilla', 'flavor-chat-ia'); ?>
                </span>
                <span x-show="activandoPlantilla" class="spinner-inline">
                    <span class="spinner is-active"></span>
                    <?php _e('Activando...', 'flavor-chat-ia'); ?>
                </span>
            </button>
        </footer>

    </div>
</div>

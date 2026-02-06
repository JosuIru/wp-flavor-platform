<?php
/**
 * Modal de Preview de Plantilla
 *
 * Muestra la vista previa de lo que se instalara al activar una plantilla.
 * Usa datos cargados via AJAX en plantillaSeleccionadaData.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}
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

        <!-- Template para mostrar contenido solo cuando haya datos -->
        <template x-if="plantillaSeleccionadaData">
            <div>
                <!-- Header -->
                <header class="flavor-template-modal-header">
                    <div class="flavor-template-modal-header-info">
                        <div class="flavor-template-modal-icon" :style="'background: ' + (plantillaSeleccionadaData.color || '#2271b1') + '15;'">
                            <span class="dashicons" :class="plantillaSeleccionadaData.icono || 'dashicons-admin-generic'"
                                  :style="'color: ' + (plantillaSeleccionadaData.color || '#2271b1') + ';'"></span>
                        </div>
                        <div>
                            <h2 id="modal-titulo-plantilla" x-text="plantillaSeleccionadaData.nombre"></h2>
                            <p class="description" x-text="plantillaSeleccionadaData.descripcion"></p>
                        </div>
                    </div>
                    <button type="button"
                            class="flavor-template-modal-close"
                            @click="cerrarPreviewModal()"
                            aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </header>

                <!-- Contenido del Preview -->
                <div class="flavor-template-preview">

                    <!-- Seccion de Modulos -->
                    <section class="flavor-template-section">
                        <h3>
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php _e('Módulos', 'flavor-chat-ia'); ?>
                        </h3>

                        <ul class="flavor-template-modules">
                            <!-- Módulos requeridos -->
                            <template x-if="plantillaSeleccionadaData.modulos_requeridos && plantillaSeleccionadaData.modulos_requeridos.length > 0">
                                <template x-for="modulo in plantillaSeleccionadaData.modulos_requeridos" :key="modulo">
                                    <li class="modulo-item requerido">
                                        <label>
                                            <input type="checkbox" checked disabled>
                                            <span class="modulo-nombre" x-text="modulo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                            <span class="modulo-badge requerido"><?php _e('Requerido', 'flavor-chat-ia'); ?></span>
                                        </label>
                                    </li>
                                </template>
                            </template>

                            <!-- Módulos opcionales -->
                            <template x-if="plantillaSeleccionadaData.modulos_opcionales && plantillaSeleccionadaData.modulos_opcionales.length > 0">
                                <template x-for="modulo in plantillaSeleccionadaData.modulos_opcionales" :key="modulo">
                                    <li class="modulo-item opcional">
                                        <label>
                                            <input type="checkbox"
                                                   :value="modulo"
                                                   x-model="modulosSeleccionados">
                                            <span class="modulo-nombre" x-text="modulo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                            <span class="modulo-badge opcional"><?php _e('Opcional', 'flavor-chat-ia'); ?></span>
                                        </label>
                                    </li>
                                </template>
                            </template>
                        </ul>
                    </section>

                    <!-- Seccion de Paginas -->
                    <template x-if="plantillaSeleccionadaData.paginas && plantillaSeleccionadaData.paginas.length > 0">
                        <section class="flavor-template-section">
                            <h3>
                                <span class="dashicons dashicons-admin-page"></span>
                                <?php _e('Páginas', 'flavor-chat-ia'); ?>
                            </h3>
                            <ul class="flavor-template-pages">
                                <template x-for="pagina in plantillaSeleccionadaData.paginas" :key="pagina.slug">
                                    <li>
                                        <span class="dashicons dashicons-media-default"></span>
                                        <span x-text="pagina.titulo"></span>
                                    </li>
                                </template>
                            </ul>
                        </section>
                    </template>

                    <!-- Seccion de Landing Page -->
                    <template x-if="plantillaSeleccionadaData.landing">
                        <section class="flavor-template-section">
                            <h3>
                                <span class="dashicons dashicons-welcome-view-site"></span>
                                <?php _e('Landing Page', 'flavor-chat-ia'); ?>
                            </h3>
                            <div class="flavor-template-landing-preview">
                                <p class="description" x-text="plantillaSeleccionadaData.landing.titulo"></p>

                                <template x-if="plantillaSeleccionadaData.landing.secciones && plantillaSeleccionadaData.landing.secciones.length > 0">
                                    <ul class="flavor-template-sections">
                                        <template x-for="seccion in plantillaSeleccionadaData.landing.secciones" :key="seccion.tipo">
                                            <li>
                                                <span class="dashicons dashicons-editor-table"></span>
                                                <span x-text="seccion.tipo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                            </div>
                        </section>
                    </template>

                    <!-- Opcion de datos demo -->
                    <section class="flavor-template-section flavor-template-demo-option">
                        <label class="flavor-template-demo-checkbox">
                            <input type="checkbox"
                                   x-model="cargarDatosDemo">
                            <div>
                                <strong><?php _e('Cargar datos de demostración', 'flavor-chat-ia'); ?></strong>
                                <p class="description"><?php _e('Incluir contenido de ejemplo para ver cómo funciona la plantilla', 'flavor-chat-ia'); ?></p>
                            </div>
                        </label>
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
        </template>

    </div>
</div>

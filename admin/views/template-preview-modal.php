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
     aria-labelledby="<?php echo esc_attr__('modal-titulo-plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

    <div class="flavor-template-modal-backdrop" @click="cerrarPreviewModal()"></div>

    <div class="flavor-template-modal-content"
         x-trap.noscroll="mostrarPreviewModal"
         x-init="if (!window.AlpineFocus) { $el.removeAttribute('x-trap.noscroll'); }"
         @click.stop>

        <!-- Template para mostrar contenido solo cuando haya datos -->
        <template x-if="plantillaSeleccionadaData">
            <div>
                <!-- Header -->
                <header class="flavor-template-modal-header">
                    <div class="flavor-template-modal-header-info">
        <div class="flavor-template-modal-icon"
             :style="plantillaSeleccionadaData
                 ? 'background: ' + (plantillaSeleccionadaData.color || '#2271b1') + '15;'
                 : ''">
            <span class="dashicons"
                  :class="plantillaSeleccionadaData ? (plantillaSeleccionadaData.icono || 'dashicons-admin-generic') : 'dashicons-admin-generic'"
                  :style="plantillaSeleccionadaData ? 'color: ' + (plantillaSeleccionadaData.color || '#2271b1') + ';' : ''"></span>
        </div>
                        <div>
                            <h2 id="modal-titulo-plantilla" x-text="plantillaSeleccionadaData ? plantillaSeleccionadaData.nombre : ''"></h2>
                            <p class="description" x-text="plantillaSeleccionadaData ? plantillaSeleccionadaData.descripcion : ''"></p>
                        </div>
                    </div>
                    <button type="button"
                            class="flavor-template-modal-close"
                            @click="cerrarPreviewModal()"
                            aria-label="<?php esc_attr_e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </header>

                <!-- Contenido del Preview -->
                <div class="flavor-template-preview">

                    <template x-if="esPreviewSugerencia()">
                        <section class="flavor-template-section flavor-template-section--suggested">
                            <div class="flavor-template-suggestion-note">
                                <span class="dashicons dashicons-star-filled"></span>
                                <div>
                                    <strong><?php _e('Sugerencia automática', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                    <p class="description"><?php _e('Esta plantilla encaja con los módulos, contextos y capacidades que ya tienes activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </div>
                            </div>
                        </section>
                    </template>

                    <template x-if="plantillaSeleccionadaData?.ecosistema">
                        <section class="flavor-template-section">
                            <h3>
                                <span class="dashicons dashicons-networking"></span>
                                <?php _e('Lectura ecosistemica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>

                            <template x-if="plantillaSeleccionadaData.ecosistema.roles && plantillaSeleccionadaData.ecosistema.roles.length > 0">
                                <div class="flavor-template-ecosystem-block">
                                    <div class="flavor-template-ecosystem-label"><?php _e('Capas del perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                    <div class="flavor-template-ecosystem-tags">
                                        <template x-for="rol in plantillaSeleccionadaData.ecosistema.roles" :key="rol">
                                            <span class="flavor-template-ecosystem-tag" x-text="rol"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="plantillaSeleccionadaData.ecosistema.capacidades && plantillaSeleccionadaData.ecosistema.capacidades.length > 0">
                                <div class="flavor-template-ecosystem-block">
                                    <div class="flavor-template-ecosystem-label"><?php _e('Capacidades activadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                    <div class="flavor-template-ecosystem-tags">
                                        <template x-for="capacidad in plantillaSeleccionadaData.ecosistema.capacidades" :key="capacidad">
                                            <span class="flavor-template-ecosystem-tag is-capability" x-text="capacidad"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="plantillaSeleccionadaData.ecosistema.contextos && plantillaSeleccionadaData.ecosistema.contextos.length > 0">
                                <div class="flavor-template-ecosystem-block">
                                    <div class="flavor-template-ecosystem-label"><?php _e('Contextos prioritarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                    <div class="flavor-template-ecosystem-tags">
                                        <template x-for="contexto in plantillaSeleccionadaData.ecosistema.contextos" :key="contexto">
                                            <span class="flavor-template-ecosystem-tag" x-text="contexto"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="plantillaSeleccionadaData.ecosistema.recomendados && plantillaSeleccionadaData.ecosistema.recomendados.length > 0">
                                <div class="flavor-template-ecosystem-block">
                                    <div class="flavor-template-ecosystem-label"><?php _e('Capas recomendadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                    <div class="flavor-template-ecosystem-tags">
                                        <template x-for="recomendado in plantillaSeleccionadaData.ecosistema.recomendados" :key="recomendado">
                                            <span class="flavor-template-ecosystem-tag is-recommended" x-text="recomendado"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="plantillaSeleccionadaData.ecosistema.recomendados_contexto && plantillaSeleccionadaData.ecosistema.recomendados_contexto.length > 0">
                                <div class="flavor-template-ecosystem-block">
                                    <div class="flavor-template-ecosystem-label"><?php _e('Siguientes capas por contexto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                    <div class="flavor-template-ecosystem-tags">
                                        <template x-for="recomendadoContexto in plantillaSeleccionadaData.ecosistema.recomendados_contexto" :key="recomendadoContexto">
                                            <span class="flavor-template-ecosystem-tag is-recommended" x-text="recomendadoContexto"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </section>
                    </template>

                    <!-- Seccion de Modulos -->
                    <section class="flavor-template-section">
                        <h3>
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php _e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>

                        <ul class="flavor-template-modules">
                            <!-- Módulos requeridos -->
                            <template x-if="obtenerModulosRequeridosPreview().length > 0">
                                <template x-for="modulo in obtenerModulosRequeridosPreview()" :key="modulo">
                                    <li class="modulo-item requerido">
                                        <label>
                                            <input type="checkbox" checked disabled>
                                            <span class="modulo-nombre" x-text="modulo.replace(/[_-]+/g, ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                            <span class="modulo-badge requerido"><?php _e('Requerido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </label>
                                    </li>
                                </template>
                            </template>

                            <!-- Módulos opcionales -->
                            <template x-if="obtenerModulosOpcionalesPreview().length > 0">
                                <li class="modulo-item opcional" style="background: #f6f7f7; border: 1px solid #dcdcde; margin-bottom: 10px;">
                                    <label>
                                        <input type="checkbox"
                                               :checked="todosOpcionalesSeleccionados()"
                                               @change="toggleTodosOpcionales($event.target.checked)">
                                        <span class="modulo-nombre"><?php _e('Seleccionar todos los módulos opcionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    </label>
                                </li>
                            </template>
                            <template x-if="obtenerModulosOpcionalesPreview().length > 0">
                                <template x-for="modulo in obtenerModulosOpcionalesPreview()" :key="modulo">
                                    <li class="modulo-item opcional">
                                        <label>
                                            <input type="checkbox"
                                                   :value="modulo"
                                                   x-model="modulosSeleccionados">
                                            <span class="modulo-nombre" x-text="modulo.replace(/[_-]+/g, ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                            <span class="modulo-badge opcional"><?php _e('Opcional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        </label>
                                    </li>
                                </template>
                            </template>
                        </ul>
                    </section>

                    <!-- Seccion de Paginas -->
                    <template x-if="plantillaSeleccionadaData?.paginas?.length > 0">
                        <section class="flavor-template-section">
                            <h3>
                                <span class="dashicons dashicons-admin-page"></span>
                                <?php _e('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                    <template x-if="plantillaSeleccionadaData?.landing">
                        <section class="flavor-template-section">
                            <h3>
                                <span class="dashicons dashicons-welcome-view-site"></span>
                                <?php _e('Landing Page', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                                <strong><?php _e('Cargar datos de demostración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <p class="description"><?php _e('Incluir contenido de ejemplo para ver cómo funciona la plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        </label>
                    </section>

                </div>

                <!-- Footer con acciones -->
                <footer class="flavor-template-modal-footer">
                    <button type="button"
                            class="button button-secondary"
                            @click="cerrarPreviewModal()">
                        <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button"
                            class="button button-primary button-hero"
                            @click="activarPlantilla(plantillaSeleccionadaId)"
                            :disabled="activandoPlantilla">
                        <span x-show="!activandoPlantilla && esPreviewSugerencia()">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php _e('Activar sugerencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                        <span x-show="!activandoPlantilla && !esPreviewSugerencia()">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Activar Plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                        <span x-show="activandoPlantilla" class="spinner-inline">
                            <span class="spinner is-active"></span>
                            <?php _e('Activando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                    </button>
                </footer>

            </div>
        </template>

    </div>
</div>

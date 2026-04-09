<?php
/**
 * Vista de Progreso de Instalacion de Plantilla
 *
 * Muestra el progreso durante la activacion de una plantilla.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-template-progress"
     x-show="mostrarProgreso"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     role="dialog"
     aria-modal="true"
     aria-labelledby="progreso-titulo">

    <div class="flavor-template-progress-backdrop"></div>

    <div class="flavor-template-progress-content">

        <!-- Header -->
        <header class="flavor-template-progress-header">
            <h2 id="progreso-titulo">
                <span class="dashicons dashicons-update" :class="{ 'spin': !instalacionCompletada && !hayError }"></span>
                <span x-text="tituloProgreso"></span>
            </h2>
        </header>

        <!-- Barra de progreso -->
        <div class="flavor-progress-bar-container">
            <div class="flavor-progress-bar">
                <div class="flavor-progress-bar-fill"
                     :style="{ width: porcentajeProgreso + '%' }"
                     :class="{ 'completado': instalacionCompletada, 'error': hayError }">
                </div>
            </div>
            <span class="flavor-progress-percentage" x-text="porcentajeProgreso + '%'"></span>
        </div>

        <!-- Lista de pasos -->
        <ul class="flavor-progress-steps">
            <template x-for="(paso, indice) in pasosInstalacion" :key="indice">
                <li class="flavor-progress-step"
                    :class="{
                        'pendiente': paso.estado === 'pendiente',
                        'en-proceso': paso.estado === 'en_proceso',
                        'completado': paso.estado === 'completado',
                        'error': paso.estado === 'error',
                        'omitido': paso.estado === 'omitido'
                    }">
                    <span class="paso-indicador">
                        <template x-if="paso.estado === 'pendiente'">
                            <span class="indicador-pendiente">&#9675;</span>
                        </template>
                        <template x-if="paso.estado === 'en_proceso'">
                            <span class="indicador-proceso spin">&#8635;</span>
                        </template>
                        <template x-if="paso.estado === 'completado'">
                            <span class="indicador-completado">&#10003;</span>
                        </template>
                        <template x-if="paso.estado === 'error'">
                            <span class="indicador-error">&#10007;</span>
                        </template>
                        <template x-if="paso.estado === 'omitido'">
                            <span class="indicador-omitido">&#8212;</span>
                        </template>
                    </span>
                    <span class="paso-nombre" x-text="paso.nombre"></span>
                    <span class="paso-descripcion" x-show="paso.descripcion" x-text="paso.descripcion"></span>
                </li>
            </template>
        </ul>

        <!-- Mensaje de resultado -->
        <div class="flavor-progress-resultado" x-show="instalacionCompletada || hayError">
            <template x-if="instalacionCompletada && !hayError">
                <div class="resultado-exito">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div>
                        <strong><?php _e('Plantilla activada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <p><?php _e('Todos los componentes han sido instalados y configurados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
            </template>
            <template x-if="hayError">
                <div class="resultado-error">
                    <span class="dashicons dashicons-warning"></span>
                    <div>
                        <strong><?php _e('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <p x-text="mensajeError"></p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <footer class="flavor-template-progress-footer" x-show="instalacionCompletada || hayError">
            <template x-if="hayError">
                <button type="button"
                        class="button button-secondary"
                        @click="reiniciarInstalacion()">
                    <span class="dashicons dashicons-controls-repeat"></span>
                    <?php _e('Reintentar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </template>
            <button type="button"
                    class="button button-primary"
                    @click="cerrarProgreso()">
                <template x-if="instalacionCompletada && !hayError">
                    <span>
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Continuar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </template>
                <template x-if="hayError">
                    <span><?php _e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </template>
            </button>
        </footer>

    </div>
</div>

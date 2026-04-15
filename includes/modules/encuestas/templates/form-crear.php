<?php
/**
 * Template: Formulario de creación de encuesta
 *
 * Variables disponibles:
 * - $contexto_tipo: string
 * - $contexto_id: int
 * - $tipos_campo: array
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$contexto_tipo = $contexto_tipo ?? 'general';
$contexto_id = $contexto_id ?? 0;
?>

<div class="flavor-encuesta-crear-avanzado">
    <form id="flavor-encuesta-crear-avanzado-form" class="flavor-encuesta-crear__form">
        <?php wp_nonce_field('flavor_encuestas_nonce', 'encuesta_nonce'); ?>
        <input type="hidden" name="contexto_tipo" value="<?php echo esc_attr($contexto_tipo); ?>">
        <input type="hidden" name="contexto_id" value="<?php echo esc_attr($contexto_id); ?>">

        <!-- Información básica -->
        <div class="flavor-encuesta-crear__section">
            <h3><?php esc_html_e('Información básica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="flavor-encuesta-crear__field">
                <label for="encuesta-titulo"><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <input type="text"
                       id="encuesta-titulo"
                       name="titulo"
                       required
                       placeholder="<?php esc_attr_e('Título de la encuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>

            <div class="flavor-encuesta-crear__field">
                <label for="encuesta-descripcion"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <textarea id="encuesta-descripcion"
                          name="descripcion"
                          rows="3"
                          placeholder="<?php esc_attr_e('Descripción opcional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div class="flavor-encuesta-crear__row">
                <div class="flavor-encuesta-crear__field">
                    <label for="encuesta-tipo"><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="encuesta-tipo" name="tipo">
                        <option value="encuesta"><?php esc_html_e('Encuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="formulario"><?php esc_html_e('Formulario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="quiz"><?php esc_html_e('Quiz', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div class="flavor-encuesta-crear__field">
                    <label for="encuesta-mostrar-resultados"><?php esc_html_e('Mostrar resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="encuesta-mostrar-resultados" name="mostrar_resultados">
                        <option value="al_votar"><?php esc_html_e('Al votar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="siempre"><?php esc_html_e('Siempre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="al_cerrar"><?php esc_html_e('Al cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="nunca"><?php esc_html_e('Nunca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Campos/Preguntas -->
        <div class="flavor-encuesta-crear__section">
            <h3><?php esc_html_e('Preguntas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div id="campos-container" class="flavor-encuesta-crear__campos">
                <!-- Los campos se añaden dinámicamente -->
            </div>

            <button type="button" id="agregar-campo" class="flavor-encuesta-crear__add-campo">
                + <?php esc_html_e('Añadir pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>

        <!-- Configuración -->
        <div class="flavor-encuesta-crear__section">
            <h3><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="flavor-encuesta-crear__options">
                <label class="flavor-encuesta-crear__checkbox">
                    <input type="checkbox" name="es_anonima" value="1">
                    <?php esc_html_e('Permitir respuestas anónimas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>

                <label class="flavor-encuesta-crear__checkbox">
                    <input type="checkbox" name="permite_multiples" value="1">
                    <?php esc_html_e('Permitir múltiples respuestas del mismo usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
            </div>

            <div class="flavor-encuesta-crear__field">
                <label for="encuesta-fecha-cierre"><?php esc_html_e('Fecha de cierre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="datetime-local" id="encuesta-fecha-cierre" name="fecha_cierre">
                <small><?php esc_html_e('Dejar vacío para encuesta sin fecha límite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flavor-encuesta-crear__actions">
            <button type="submit" name="estado" value="activa" class="flavor-encuesta-crear__submit">
                <?php esc_html_e('Publicar encuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="submit" name="estado" value="borrador" class="flavor-encuesta-crear__draft">
                <?php esc_html_e('Guardar borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </form>
</div>

<!-- Template para campo -->
<script type="text/template" id="template-campo">
    <div class="flavor-encuesta-crear__campo" data-campo-index="{index}">
        <div class="flavor-encuesta-crear__campo-header">
            <span class="flavor-encuesta-crear__campo-numero">#{index}</span>
            <button type="button" class="flavor-encuesta-crear__campo-remove" aria-label="<?php esc_attr_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">×</button>
        </div>

        <div class="flavor-encuesta-crear__campo-body">
            <div class="flavor-encuesta-crear__row">
                <div class="flavor-encuesta-crear__field flavor-encuesta-crear__field--grow">
                    <input type="text"
                           name="campos[{index}][etiqueta]"
                           placeholder="<?php esc_attr_e('Pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                           required>
                </div>
                <div class="flavor-encuesta-crear__field">
                    <select name="campos[{index}][tipo]" class="campo-tipo-select">
                        <?php
                        $encuestas_module_class = function_exists('flavor_get_runtime_class_name')
                            ? flavor_get_runtime_class_name('Flavor_Chat_Encuestas_Module')
                            : 'Flavor_Chat_Encuestas_Module';
                        foreach ($encuestas_module_class::TIPOS_CAMPO as $valor => $etiqueta):
                        ?>
                            <option value="<?php echo esc_attr($valor); ?>"><?php echo esc_html($etiqueta); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flavor-encuesta-crear__campo-opciones" style="display: none;">
                <label><?php esc_html_e('Opciones de respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div class="opciones-container">
                    <div class="flavor-encuesta-crear__opcion">
                        <input type="text" name="campos[{index}][opciones][]" placeholder="<?php esc_attr_e('Opción 1', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <button type="button" class="remove-opcion">×</button>
                    </div>
                    <div class="flavor-encuesta-crear__opcion">
                        <input type="text" name="campos[{index}][opciones][]" placeholder="<?php esc_attr_e('Opción 2', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <button type="button" class="remove-opcion">×</button>
                    </div>
                </div>
                <button type="button" class="add-opcion"><?php esc_html_e('+ Añadir opción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div class="flavor-encuesta-crear__campo-config">
                <label class="flavor-encuesta-crear__checkbox">
                    <input type="checkbox" name="campos[{index}][es_requerido]" value="1" checked>
                    <?php esc_html_e('Obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
            </div>
        </div>
    </div>
</script>

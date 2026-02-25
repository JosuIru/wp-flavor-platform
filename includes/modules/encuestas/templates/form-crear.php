<?php
/**
 * Template: Formulario de creación de encuesta
 *
 * Variables disponibles:
 * - $contexto_tipo: string
 * - $contexto_id: int
 * - $tipos_campo: array
 *
 * @package FlavorChatIA
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
            <h3><?php esc_html_e('Información básica', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-encuesta-crear__field">
                <label for="encuesta-titulo"><?php esc_html_e('Título', 'flavor-chat-ia'); ?> *</label>
                <input type="text"
                       id="encuesta-titulo"
                       name="titulo"
                       required
                       placeholder="<?php esc_attr_e('Título de la encuesta', 'flavor-chat-ia'); ?>">
            </div>

            <div class="flavor-encuesta-crear__field">
                <label for="encuesta-descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                <textarea id="encuesta-descripcion"
                          name="descripcion"
                          rows="3"
                          placeholder="<?php esc_attr_e('Descripción opcional', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div class="flavor-encuesta-crear__row">
                <div class="flavor-encuesta-crear__field">
                    <label for="encuesta-tipo"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></label>
                    <select id="encuesta-tipo" name="tipo">
                        <option value="encuesta"><?php esc_html_e('Encuesta', 'flavor-chat-ia'); ?></option>
                        <option value="formulario"><?php esc_html_e('Formulario', 'flavor-chat-ia'); ?></option>
                        <option value="quiz"><?php esc_html_e('Quiz', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-encuesta-crear__field">
                    <label for="encuesta-mostrar-resultados"><?php esc_html_e('Mostrar resultados', 'flavor-chat-ia'); ?></label>
                    <select id="encuesta-mostrar-resultados" name="mostrar_resultados">
                        <option value="al_votar"><?php esc_html_e('Al votar', 'flavor-chat-ia'); ?></option>
                        <option value="siempre"><?php esc_html_e('Siempre', 'flavor-chat-ia'); ?></option>
                        <option value="al_cerrar"><?php esc_html_e('Al cerrar', 'flavor-chat-ia'); ?></option>
                        <option value="nunca"><?php esc_html_e('Nunca', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Campos/Preguntas -->
        <div class="flavor-encuesta-crear__section">
            <h3><?php esc_html_e('Preguntas', 'flavor-chat-ia'); ?></h3>

            <div id="campos-container" class="flavor-encuesta-crear__campos">
                <!-- Los campos se añaden dinámicamente -->
            </div>

            <button type="button" id="agregar-campo" class="flavor-encuesta-crear__add-campo">
                + <?php esc_html_e('Añadir pregunta', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <!-- Configuración -->
        <div class="flavor-encuesta-crear__section">
            <h3><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-encuesta-crear__options">
                <label class="flavor-encuesta-crear__checkbox">
                    <input type="checkbox" name="es_anonima" value="1">
                    <?php esc_html_e('Permitir respuestas anónimas', 'flavor-chat-ia'); ?>
                </label>

                <label class="flavor-encuesta-crear__checkbox">
                    <input type="checkbox" name="permite_multiples" value="1">
                    <?php esc_html_e('Permitir múltiples respuestas del mismo usuario', 'flavor-chat-ia'); ?>
                </label>
            </div>

            <div class="flavor-encuesta-crear__field">
                <label for="encuesta-fecha-cierre"><?php esc_html_e('Fecha de cierre', 'flavor-chat-ia'); ?></label>
                <input type="datetime-local" id="encuesta-fecha-cierre" name="fecha_cierre">
                <small><?php esc_html_e('Dejar vacío para encuesta sin fecha límite', 'flavor-chat-ia'); ?></small>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flavor-encuesta-crear__actions">
            <button type="submit" name="estado" value="activa" class="flavor-encuesta-crear__submit">
                <?php esc_html_e('Publicar encuesta', 'flavor-chat-ia'); ?>
            </button>
            <button type="submit" name="estado" value="borrador" class="flavor-encuesta-crear__draft">
                <?php esc_html_e('Guardar borrador', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>

<!-- Template para campo -->
<script type="text/template" id="template-campo">
    <div class="flavor-encuesta-crear__campo" data-campo-index="{index}">
        <div class="flavor-encuesta-crear__campo-header">
            <span class="flavor-encuesta-crear__campo-numero">#{index}</span>
            <button type="button" class="flavor-encuesta-crear__campo-remove" aria-label="<?php esc_attr_e('Eliminar', 'flavor-chat-ia'); ?>">×</button>
        </div>

        <div class="flavor-encuesta-crear__campo-body">
            <div class="flavor-encuesta-crear__row">
                <div class="flavor-encuesta-crear__field flavor-encuesta-crear__field--grow">
                    <input type="text"
                           name="campos[{index}][etiqueta]"
                           placeholder="<?php esc_attr_e('Pregunta', 'flavor-chat-ia'); ?>"
                           required>
                </div>
                <div class="flavor-encuesta-crear__field">
                    <select name="campos[{index}][tipo]" class="campo-tipo-select">
                        <?php foreach (Flavor_Chat_Encuestas_Module::TIPOS_CAMPO as $valor => $etiqueta): ?>
                            <option value="<?php echo esc_attr($valor); ?>"><?php echo esc_html($etiqueta); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flavor-encuesta-crear__campo-opciones" style="display: none;">
                <label><?php esc_html_e('Opciones de respuesta', 'flavor-chat-ia'); ?></label>
                <div class="opciones-container">
                    <div class="flavor-encuesta-crear__opcion">
                        <input type="text" name="campos[{index}][opciones][]" placeholder="<?php esc_attr_e('Opción 1', 'flavor-chat-ia'); ?>">
                        <button type="button" class="remove-opcion">×</button>
                    </div>
                    <div class="flavor-encuesta-crear__opcion">
                        <input type="text" name="campos[{index}][opciones][]" placeholder="<?php esc_attr_e('Opción 2', 'flavor-chat-ia'); ?>">
                        <button type="button" class="remove-opcion">×</button>
                    </div>
                </div>
                <button type="button" class="add-opcion"><?php esc_html_e('+ Añadir opción', 'flavor-chat-ia'); ?></button>
            </div>

            <div class="flavor-encuesta-crear__campo-config">
                <label class="flavor-encuesta-crear__checkbox">
                    <input type="checkbox" name="campos[{index}][es_requerido]" value="1" checked>
                    <?php esc_html_e('Obligatorio', 'flavor-chat-ia'); ?>
                </label>
            </div>
        </div>
    </div>
</script>

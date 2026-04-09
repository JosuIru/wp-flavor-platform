<?php
/**
 * Renderer para el módulo de Encuestas
 *
 * Genera el HTML para las diferentes vistas de encuestas
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona el renderizado de encuestas
 */
class Flavor_Encuestas_Renderer {

    /**
     * Referencia al módulo principal
     *
     * @var Flavor_Chat_Encuestas_Module
     */
    private $module;

    /**
     * Constructor
     *
     * @param Flavor_Chat_Encuestas_Module $module Módulo principal
     */
    public function __construct($module) {
        $this->module = $module;
    }

    // =========================================================================
    // RENDERIZADO DE ENCUESTA COMPLETA
    // =========================================================================

    /**
     * Renderiza una encuesta completa
     *
     * @param int $encuesta_id ID de la encuesta
     * @return string HTML
     */
    public function render_encuesta($encuesta_id) {
        $encuesta = $this->module->obtener_encuesta($encuesta_id);

        if (!$encuesta) {
            return $this->render_error(__('Encuesta no encontrada', 'flavor-platform'));
        }

        // Verificar estado
        if ($encuesta->estado === 'borrador' && !$this->module->puede_editar_encuesta($encuesta_id)) {
            return $this->render_error(__('Esta encuesta no está disponible', 'flavor-platform'));
        }

        $usuario_id = get_current_user_id();
        $ya_participo = $this->module->usuario_ya_participo($encuesta_id, $usuario_id, $this->obtener_sesion_id());
        $puede_ver_resultados = $this->module->puede_ver_resultados($encuesta_id);

        ob_start();
        ?>
        <div class="flavor-encuesta" data-encuesta-id="<?php echo esc_attr($encuesta_id); ?>">
            <div class="flavor-encuesta__header">
                <h3 class="flavor-encuesta__titulo"><?php echo esc_html($encuesta->titulo); ?></h3>

                <?php if ($encuesta->descripcion): ?>
                    <p class="flavor-encuesta__descripcion"><?php echo wp_kses_post($encuesta->descripcion); ?></p>
                <?php endif; ?>

                <div class="flavor-encuesta__meta">
                    <?php echo $this->render_meta_badges($encuesta); ?>
                </div>
            </div>

            <?php if ($encuesta->estado === 'cerrada'): ?>
                <div class="flavor-encuesta__notice flavor-encuesta__notice--info">
                    <?php esc_html_e('Esta encuesta ha finalizado', 'flavor-platform'); ?>
                </div>
            <?php endif; ?>

            <?php if ($ya_participo && !$encuesta->permite_multiples): ?>
                <?php if ($puede_ver_resultados): ?>
                    <div class="flavor-encuesta__notice flavor-encuesta__notice--success">
                        <?php esc_html_e('Ya has participado en esta encuesta', 'flavor-platform'); ?>
                    </div>
                    <?php echo $this->render_resultados_inline($encuesta_id); ?>
                <?php else: ?>
                    <div class="flavor-encuesta__notice flavor-encuesta__notice--info">
                        <?php esc_html_e('Ya has participado. Los resultados se mostrarán cuando la encuesta cierre.', 'flavor-platform'); ?>
                    </div>
                <?php endif; ?>
            <?php elseif ($encuesta->estado === 'activa'): ?>
                <?php echo $this->render_formulario_respuesta($encuesta); ?>
            <?php elseif ($puede_ver_resultados): ?>
                <?php echo $this->render_resultados_inline($encuesta_id); ?>
            <?php endif; ?>

            <div class="flavor-encuesta__footer">
                <span class="flavor-encuesta__participantes">
                    <?php
                    printf(
                        esc_html(_n('%d participante', '%d participantes', $encuesta->total_participantes, 'flavor-platform')),
                        $encuesta->total_participantes
                    );
                    ?>
                </span>

                <?php if ($encuesta->fecha_cierre && $encuesta->estado === 'activa'): ?>
                    <span class="flavor-encuesta__cierre">
                        <?php
                        $tiempo_restante = strtotime($encuesta->fecha_cierre) - time();
                        if ($tiempo_restante > 0) {
                            printf(
                                esc_html__('Cierra en %s', 'flavor-platform'),
                                human_time_diff(time(), strtotime($encuesta->fecha_cierre))
                            );
                        }
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza versión mini para chat
     *
     * @param int $encuesta_id ID de la encuesta
     * @return string HTML
     */
    public function render_encuesta_mini($encuesta_id) {
        $encuesta = $this->module->obtener_encuesta($encuesta_id);

        if (!$encuesta) {
            return '';
        }

        $ya_participo = $this->module->usuario_ya_participo($encuesta_id, get_current_user_id(), $this->obtener_sesion_id());

        ob_start();
        ?>
        <div class="flavor-encuesta-mini" data-encuesta-id="<?php echo esc_attr($encuesta_id); ?>">
            <div class="flavor-encuesta-mini__header">
                <span class="flavor-encuesta-mini__icon">📊</span>
                <span class="flavor-encuesta-mini__titulo"><?php echo esc_html($encuesta->titulo); ?></span>
            </div>

            <?php if ($encuesta->estado === 'activa' && !$ya_participo): ?>
                <div class="flavor-encuesta-mini__opciones">
                    <?php
                    // Solo mostrar primer campo si es selección única
                    if (!empty($encuesta->campos) && $encuesta->campos[0]->tipo === 'seleccion_unica'):
                        $campo = $encuesta->campos[0];
                        foreach ($campo->opciones as $indice => $opcion):
                    ?>
                        <button type="button"
                                class="flavor-encuesta-mini__opcion"
                                data-campo-id="<?php echo esc_attr($campo->id); ?>"
                                data-opcion="<?php echo esc_attr($indice); ?>">
                            <?php echo esc_html($opcion); ?>
                        </button>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
            <?php else: ?>
                <div class="flavor-encuesta-mini__resultados">
                    <?php echo $this->render_resultados_mini($encuesta_id); ?>
                </div>
            <?php endif; ?>

            <div class="flavor-encuesta-mini__meta">
                <?php echo esc_html($encuesta->total_participantes); ?> votos
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // FORMULARIO DE RESPUESTA
    // =========================================================================

    /**
     * Renderiza el formulario de respuesta
     *
     * @param object $encuesta Objeto encuesta con campos
     * @return string HTML
     */
    private function render_formulario_respuesta($encuesta) {
        if (empty($encuesta->campos)) {
            return $this->render_error(__('Esta encuesta no tiene preguntas', 'flavor-platform'));
        }

        ob_start();
        ?>
        <form class="flavor-encuesta__form" data-encuesta-id="<?php echo esc_attr($encuesta->id); ?>">
            <?php wp_nonce_field('flavor_encuestas_nonce', 'encuesta_nonce'); ?>

            <div class="flavor-encuesta__campos">
                <?php
                foreach ($encuesta->campos as $campo) {
                    echo $this->render_campo($campo);
                }
                ?>
            </div>

            <div class="flavor-encuesta__actions">
                <button type="submit" class="flavor-encuesta__submit">
                    <?php esc_html_e('Enviar respuesta', 'flavor-platform'); ?>
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un campo del formulario
     *
     * @param object $campo Datos del campo
     * @return string HTML
     */
    private function render_campo($campo) {
        $campo_id = 'campo_' . $campo->id;
        $requerido = $campo->es_requerido ? 'required' : '';

        ob_start();
        ?>
        <div class="flavor-encuesta__campo flavor-encuesta__campo--<?php echo esc_attr($campo->tipo); ?>"
             data-campo-id="<?php echo esc_attr($campo->id); ?>">

            <label class="flavor-encuesta__label">
                <?php echo esc_html($campo->etiqueta); ?>
                <?php if ($campo->es_requerido): ?>
                    <span class="flavor-encuesta__required">*</span>
                <?php endif; ?>
            </label>

            <?php if ($campo->descripcion): ?>
                <p class="flavor-encuesta__help"><?php echo esc_html($campo->descripcion); ?></p>
            <?php endif; ?>

            <?php
            switch ($campo->tipo) {
                case 'texto':
                    echo $this->render_campo_texto($campo, $campo_id, $requerido);
                    break;

                case 'textarea':
                    echo $this->render_campo_textarea($campo, $campo_id, $requerido);
                    break;

                case 'email':
                    echo $this->render_campo_email($campo, $campo_id, $requerido);
                    break;

                case 'telefono':
                    echo $this->render_campo_telefono($campo, $campo_id, $requerido);
                    break;

                case 'url':
                    echo $this->render_campo_url($campo, $campo_id, $requerido);
                    break;

                case 'seleccion_unica':
                    echo $this->render_campo_seleccion_unica($campo, $campo_id, $requerido);
                    break;

                case 'seleccion_multiple':
                    echo $this->render_campo_seleccion_multiple($campo, $campo_id, $requerido);
                    break;

                case 'si_no':
                    echo $this->render_campo_si_no($campo, $campo_id, $requerido);
                    break;

                case 'escala':
                    echo $this->render_campo_escala($campo, $campo_id, $requerido);
                    break;

                case 'estrellas':
                    echo $this->render_campo_estrellas($campo, $campo_id, $requerido);
                    break;

                case 'numero':
                    echo $this->render_campo_numero($campo, $campo_id, $requerido);
                    break;

                case 'fecha':
                    echo $this->render_campo_fecha($campo, $campo_id, $requerido);
                    break;

                case 'fecha_hora':
                    echo $this->render_campo_fecha_hora($campo, $campo_id, $requerido);
                    break;

                case 'rango':
                    echo $this->render_campo_rango($campo, $campo_id, $requerido);
                    break;

                case 'nps':
                    echo $this->render_campo_nps($campo, $campo_id, $requerido);
                    break;
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Campo de texto corto
     */
    private function render_campo_texto($campo, $campo_id, $requerido) {
        return sprintf(
            '<input type="text" name="respuestas[%d]" id="%s" class="flavor-encuesta__input" %s>',
            $campo->id,
            esc_attr($campo_id),
            $requerido
        );
    }

    /**
     * Campo de texto largo
     */
    private function render_campo_textarea($campo, $campo_id, $requerido) {
        return sprintf(
            '<textarea name="respuestas[%d]" id="%s" class="flavor-encuesta__textarea" rows="4" %s></textarea>',
            $campo->id,
            esc_attr($campo_id),
            $requerido
        );
    }

    /**
     * Campo email
     */
    private function render_campo_email($campo, $campo_id, $requerido) {
        return sprintf(
            '<input type="email" name="respuestas[%d]" id="%s" class="flavor-encuesta__input" %s>',
            $campo->id,
            esc_attr($campo_id),
            $requerido
        );
    }

    /**
     * Campo teléfono
     */
    private function render_campo_telefono($campo, $campo_id, $requerido) {
        return sprintf(
            '<input type="tel" name="respuestas[%d]" id="%s" class="flavor-encuesta__input" %s>',
            $campo->id,
            esc_attr($campo_id),
            $requerido
        );
    }

    /**
     * Campo URL
     */
    private function render_campo_url($campo, $campo_id, $requerido) {
        return sprintf(
            '<input type="url" name="respuestas[%d]" id="%s" class="flavor-encuesta__input" %s>',
            $campo->id,
            esc_attr($campo_id),
            $requerido
        );
    }

    /**
     * Campo de selección única (radio)
     */
    private function render_campo_seleccion_unica($campo, $campo_id, $requerido) {
        if (empty($campo->opciones)) {
            return '';
        }

        $html = '<div class="flavor-encuesta__options">';
        foreach ($campo->opciones as $indice => $opcion) {
            $html .= sprintf(
                '<label class="flavor-encuesta__option">
                    <input type="radio" name="respuestas[%d]" value="%d" %s>
                    <span class="flavor-encuesta__option-text">%s</span>
                </label>',
                $campo->id,
                $indice,
                $requerido,
                esc_html($opcion)
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Campo de selección múltiple (checkbox)
     */
    private function render_campo_seleccion_multiple($campo, $campo_id, $requerido) {
        if (empty($campo->opciones)) {
            return '';
        }

        $html = '<div class="flavor-encuesta__options flavor-encuesta__options--multiple">';
        foreach ($campo->opciones as $indice => $opcion) {
            $html .= sprintf(
                '<label class="flavor-encuesta__option">
                    <input type="checkbox" name="respuestas[%d][]" value="%d">
                    <span class="flavor-encuesta__option-text">%s</span>
                </label>',
                $campo->id,
                $indice,
                esc_html($opcion)
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Campo Sí/No
     */
    private function render_campo_si_no($campo, $campo_id, $requerido) {
        return sprintf(
            '<div class="flavor-encuesta__options flavor-encuesta__options--binary">
                <label class="flavor-encuesta__option">
                    <input type="radio" name="respuestas[%1$d]" value="1" %2$s>
                    <span class="flavor-encuesta__option-text">%3$s</span>
                </label>
                <label class="flavor-encuesta__option">
                    <input type="radio" name="respuestas[%1$d]" value="0" %2$s>
                    <span class="flavor-encuesta__option-text">%4$s</span>
                </label>
            </div>',
            $campo->id,
            $requerido,
            esc_html__('Sí', 'flavor-platform'),
            esc_html__('No', 'flavor-platform')
        );
    }

    /**
     * Campo de escala (1-10)
     */
    private function render_campo_escala($campo, $campo_id, $requerido) {
        $config = is_array($campo->configuracion) ? $campo->configuracion : [];
        $min = isset($config['min']) ? $config['min'] : 1;
        $max = isset($config['max']) ? $config['max'] : 10;

        $html = '<div class="flavor-encuesta__scale">';
        for ($i = $min; $i <= $max; $i++) {
            $html .= sprintf(
                '<label class="flavor-encuesta__scale-item">
                    <input type="radio" name="respuestas[%d]" value="%d" %s>
                    <span>%d</span>
                </label>',
                $campo->id,
                $i,
                $requerido,
                $i
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Campo de estrellas (1-5)
     */
    private function render_campo_estrellas($campo, $campo_id, $requerido) {
        $html = '<div class="flavor-encuesta__stars" data-campo-id="' . esc_attr($campo->id) . '">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= sprintf(
                '<label class="flavor-encuesta__star">
                    <input type="radio" name="respuestas[%d]" value="%d" %s>
                    <span class="flavor-encuesta__star-icon" data-value="%d">☆</span>
                </label>',
                $campo->id,
                $i,
                $requerido,
                $i
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Campo numérico
     */
    private function render_campo_numero($campo, $campo_id, $requerido) {
        $config = is_array($campo->configuracion) ? $campo->configuracion : [];
        $min = isset($config['min']) ? 'min="' . esc_attr($config['min']) . '"' : '';
        $max = isset($config['max']) ? 'max="' . esc_attr($config['max']) . '"' : '';
        $step = isset($config['step']) ? 'step="' . esc_attr($config['step']) . '"' : '';

        return sprintf(
            '<input type="number" name="respuestas[%d]" id="%s" class="flavor-encuesta__input flavor-encuesta__input--number" %s %s %s %s>',
            $campo->id,
            esc_attr($campo_id),
            $min,
            $max,
            $step,
            $requerido
        );
    }

    /**
     * Campo de fecha
     */
    private function render_campo_fecha($campo, $campo_id, $requerido) {
        return sprintf(
            '<input type="date" name="respuestas[%d]" id="%s" class="flavor-encuesta__input flavor-encuesta__input--date" %s>',
            $campo->id,
            esc_attr($campo_id),
            $requerido
        );
    }

    /**
     * Campo de fecha y hora
     */
    private function render_campo_fecha_hora($campo, $campo_id, $requerido) {
        return sprintf(
            '<input type="datetime-local" name="respuestas[%d]" id="%s" class="flavor-encuesta__input flavor-encuesta__input--date" %s>',
            $campo->id,
            esc_attr($campo_id),
            $requerido
        );
    }

    /**
     * Campo de rango (slider)
     */
    private function render_campo_rango($campo, $campo_id, $requerido) {
        $config = is_array($campo->configuracion) ? $campo->configuracion : [];
        $min = isset($config['min']) ? (int) $config['min'] : 1;
        $max = isset($config['max']) ? (int) $config['max'] : 10;
        $step = isset($config['step']) ? (float) $config['step'] : 1;
        $initial = isset($config['default']) ? (float) $config['default'] : $min;

        return sprintf(
            '<div class="flavor-encuesta__range">
                <input type="range" name="respuestas[%1$d]" id="%2$s" class="flavor-encuesta__input flavor-encuesta__input--range" min="%3$s" max="%4$s" step="%5$s" value="%6$s" oninput="this.nextElementSibling.textContent=this.value" %7$s>
                <span class="flavor-encuesta__range-value">%6$s</span>
            </div>',
            $campo->id,
            esc_attr($campo_id),
            esc_attr($min),
            esc_attr($max),
            esc_attr($step),
            esc_attr($initial),
            $requerido
        );
    }

    /**
     * Campo NPS (0-10)
     */
    private function render_campo_nps($campo, $campo_id, $requerido) {
        $html = '<div class="flavor-encuesta__scale flavor-encuesta__scale--nps">';
        for ($i = 0; $i <= 10; $i++) {
            $html .= sprintf(
                '<label class="flavor-encuesta__scale-item">
                    <input type="radio" name="respuestas[%d]" value="%d" %s>
                    <span>%d</span>
                </label>',
                $campo->id,
                $i,
                $requerido,
                $i
            );
        }
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // RESULTADOS
    // =========================================================================

    /**
     * Renderiza resultados completos
     *
     * @param int $encuesta_id ID de la encuesta
     * @param string $formato barras, pastel, texto
     * @return string HTML
     */
    public function render_resultados($encuesta_id, $formato = 'barras') {
        $resultados = $this->module->obtener_resultados($encuesta_id);

        if (empty($resultados) || empty($resultados['campos'])) {
            return $this->render_error(__('No hay resultados disponibles', 'flavor-platform'));
        }

        ob_start();
        ?>
        <div class="flavor-encuesta-resultados" data-encuesta-id="<?php echo esc_attr($encuesta_id); ?>">
            <div class="flavor-encuesta-resultados__header">
                <h3><?php echo esc_html($resultados['titulo']); ?></h3>
                <div class="flavor-encuesta-resultados__meta">
                    <?php
                    printf(
                        esc_html__('%d participantes', 'flavor-platform'),
                        $resultados['total_participantes']
                    );
                    ?>
                </div>
            </div>

            <div class="flavor-encuesta-resultados__campos">
                <?php
                foreach ($resultados['campos'] as $campo) {
                    echo $this->render_resultado_campo($campo, $formato, $resultados['total_participantes']);
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza resultados inline (después de votar)
     */
    private function render_resultados_inline($encuesta_id) {
        return $this->render_resultados($encuesta_id, 'barras');
    }

    /**
     * Renderiza resultados mini (para chat)
     */
    private function render_resultados_mini($encuesta_id) {
        $resultados = $this->module->obtener_resultados($encuesta_id);

        if (empty($resultados) || empty($resultados['campos'])) {
            return '';
        }

        $campo = $resultados['campos'][0]; // Solo primer campo
        $total = $resultados['total_participantes'];

        if (empty($campo['conteos']) || $total === 0) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-encuesta-mini__bars">
            <?php
            foreach ($campo['opciones'] as $indice => $opcion):
                $conteo = $campo['conteos'][$indice] ?? 0;
                $porcentaje = $total > 0 ? round(($conteo / $total) * 100) : 0;
            ?>
                <div class="flavor-encuesta-mini__bar-item">
                    <div class="flavor-encuesta-mini__bar-label">
                        <span><?php echo esc_html($opcion); ?></span>
                        <span><?php echo esc_html($porcentaje); ?>%</span>
                    </div>
                    <div class="flavor-encuesta-mini__bar-bg">
                        <div class="flavor-encuesta-mini__bar-fill" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza resultado de un campo
     */
    private function render_resultado_campo($campo, $formato, $total_participantes) {
        ob_start();
        ?>
        <div class="flavor-encuesta-resultados__campo">
            <h4 class="flavor-encuesta-resultados__pregunta"><?php echo esc_html($campo['etiqueta']); ?></h4>

            <?php
            switch ($campo['tipo']) {
                case 'seleccion_unica':
                case 'si_no':
                case 'seleccion_multiple':
                    echo $this->render_resultado_seleccion($campo, $formato, $total_participantes);
                    break;

                case 'escala':
                case 'estrellas':
                    echo $this->render_resultado_escala($campo, $total_participantes);
                    break;

                case 'numero':
                case 'rango':
                case 'nps':
                    echo $this->render_resultado_numero($campo);
                    break;

                default:
                    echo $this->render_resultado_texto($campo);
                    break;
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza resultado de selección con barras
     */
    private function render_resultado_seleccion($campo, $formato, $total) {
        if (empty($campo['conteos']) || $total === 0) {
            return '<p class="flavor-encuesta-resultados__empty">' .
                   esc_html__('Sin respuestas', 'flavor-platform') . '</p>';
        }

        // Para sí/no, usar opciones predefinidas
        $opciones = $campo['opciones'];
        if ($campo['tipo'] === 'si_no') {
            $opciones = [1 => __('Sí', 'flavor-platform'), 0 => __('No', 'flavor-platform')];
        }

        ob_start();
        ?>
        <div class="flavor-encuesta-resultados__bars">
            <?php
            foreach ($opciones as $indice => $opcion):
                $conteo = $campo['conteos'][$indice] ?? 0;
                $porcentaje = $total > 0 ? round(($conteo / $total) * 100) : 0;
            ?>
                <div class="flavor-encuesta-resultados__bar">
                    <div class="flavor-encuesta-resultados__bar-header">
                        <span class="flavor-encuesta-resultados__bar-label"><?php echo esc_html($opcion); ?></span>
                        <span class="flavor-encuesta-resultados__bar-stats">
                            <?php echo esc_html($conteo); ?> (<?php echo esc_html($porcentaje); ?>%)
                        </span>
                    </div>
                    <div class="flavor-encuesta-resultados__bar-track">
                        <div class="flavor-encuesta-resultados__bar-fill"
                             style="width: <?php echo esc_attr($porcentaje); ?>%"
                             data-porcentaje="<?php echo esc_attr($porcentaje); ?>">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza resultado de escala/estrellas
     */
    private function render_resultado_escala($campo, $total) {
        if (empty($campo['conteos']) || $total === 0) {
            return '<p class="flavor-encuesta-resultados__empty">' .
                   esc_html__('Sin respuestas', 'flavor-platform') . '</p>';
        }

        // Calcular promedio
        $suma = 0;
        $count = 0;
        foreach ($campo['conteos'] as $valor => $cantidad) {
            $suma += $valor * $cantidad;
            $count += $cantidad;
        }
        $promedio = $count > 0 ? round($suma / $count, 1) : 0;

        ob_start();
        ?>
        <div class="flavor-encuesta-resultados__stats">
            <div class="flavor-encuesta-resultados__stat">
                <span class="flavor-encuesta-resultados__stat-value"><?php echo esc_html($promedio); ?></span>
                <span class="flavor-encuesta-resultados__stat-label"><?php esc_html_e('Promedio', 'flavor-platform'); ?></span>
            </div>
            <div class="flavor-encuesta-resultados__stat">
                <span class="flavor-encuesta-resultados__stat-value"><?php echo esc_html($count); ?></span>
                <span class="flavor-encuesta-resultados__stat-label"><?php esc_html_e('Respuestas', 'flavor-platform'); ?></span>
            </div>
        </div>

        <?php if ($campo['tipo'] === 'estrellas'): ?>
            <div class="flavor-encuesta-resultados__stars-display">
                <?php
                for ($i = 1; $i <= 5; $i++) {
                    $clase = $i <= round($promedio) ? 'filled' : '';
                    echo '<span class="flavor-encuesta-resultados__star ' . esc_attr($clase) . '">★</span>';
                }
                ?>
            </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza resultado numérico
     */
    private function render_resultado_numero($campo) {
        if (empty($campo['estadisticas'])) {
            return '<p class="flavor-encuesta-resultados__empty">' .
                   esc_html__('Sin respuestas', 'flavor-platform') . '</p>';
        }

        $stats = $campo['estadisticas'];

        ob_start();
        ?>
        <div class="flavor-encuesta-resultados__stats flavor-encuesta-resultados__stats--numeric">
            <div class="flavor-encuesta-resultados__stat">
                <span class="flavor-encuesta-resultados__stat-value"><?php echo esc_html(number_format($stats['promedio'], 2)); ?></span>
                <span class="flavor-encuesta-resultados__stat-label"><?php esc_html_e('Promedio', 'flavor-platform'); ?></span>
            </div>
            <div class="flavor-encuesta-resultados__stat">
                <span class="flavor-encuesta-resultados__stat-value"><?php echo esc_html($stats['minimo']); ?></span>
                <span class="flavor-encuesta-resultados__stat-label"><?php esc_html_e('Mínimo', 'flavor-platform'); ?></span>
            </div>
            <div class="flavor-encuesta-resultados__stat">
                <span class="flavor-encuesta-resultados__stat-value"><?php echo esc_html($stats['maximo']); ?></span>
                <span class="flavor-encuesta-resultados__stat-label"><?php esc_html_e('Máximo', 'flavor-platform'); ?></span>
            </div>
            <div class="flavor-encuesta-resultados__stat">
                <span class="flavor-encuesta-resultados__stat-value"><?php echo esc_html($stats['total']); ?></span>
                <span class="flavor-encuesta-resultados__stat-label"><?php esc_html_e('Respuestas', 'flavor-platform'); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza resultado de texto
     */
    private function render_resultado_texto($campo) {
        if (empty($campo['respuestas_texto'])) {
            return '<p class="flavor-encuesta-resultados__empty">' .
                   esc_html__('Sin respuestas', 'flavor-platform') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-encuesta-resultados__text-responses">
            <?php foreach ($campo['respuestas_texto'] as $respuesta): ?>
                <div class="flavor-encuesta-resultados__text-item">
                    <?php echo esc_html($respuesta); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // FORMULARIO DE CREACIÓN
    // =========================================================================

    /**
     * Renderiza formulario de creación de encuesta
     *
     * @param array $atts Atributos
     * @return string HTML
     */
    public function render_formulario_crear($atts) {
        if (!is_user_logged_in()) {
            return $this->render_error(__('Debes iniciar sesión para crear encuestas', 'flavor-platform'));
        }

        $contexto = $atts['contexto'] ?? 'general';
        $contexto_id = $atts['contexto_id'] ?? 0;

        ob_start();
        ?>
        <div class="flavor-encuesta-crear">
            <form class="flavor-encuesta-crear__form" id="flavor-encuesta-crear-form">
                <?php wp_nonce_field('flavor_encuestas_nonce', 'encuesta_nonce'); ?>

                <div class="flavor-encuesta-crear__section">
                    <label for="encuesta-contexto-tipo" class="flavor-encuesta-crear__label">
                        <?php esc_html_e('Vincular encuesta a', 'flavor-platform'); ?>
                    </label>
                    <select id="encuesta-contexto-tipo" name="contexto_tipo" class="flavor-encuesta-crear__input">
                        <option value="general" <?php selected($contexto, 'general'); ?>><?php esc_html_e('General (sin vínculo)', 'flavor-platform'); ?></option>
                        <option value="comunidad" <?php selected($contexto, 'comunidad'); ?>><?php esc_html_e('Comunidad', 'flavor-platform'); ?></option>
                        <option value="foro" <?php selected($contexto, 'foro'); ?>><?php esc_html_e('Foro', 'flavor-platform'); ?></option>
                        <option value="chat_grupo" <?php selected($contexto, 'chat_grupo'); ?>><?php esc_html_e('Chat de grupo', 'flavor-platform'); ?></option>
                        <option value="red_social" <?php selected($contexto, 'red_social'); ?>><?php esc_html_e('Red social', 'flavor-platform'); ?></option>
                        <option value="evento" <?php selected($contexto, 'evento'); ?>><?php esc_html_e('Evento', 'flavor-platform'); ?></option>
                        <option value="curso" <?php selected($contexto, 'curso'); ?>><?php esc_html_e('Curso', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="flavor-encuesta-crear__section" id="encuesta-contexto-id-wrap">
                    <label for="encuesta-contexto-id" class="flavor-encuesta-crear__label">
                        <?php esc_html_e('ID del elemento destino', 'flavor-platform'); ?>
                    </label>
                    <input type="number"
                           id="encuesta-contexto-id"
                           name="contexto_id"
                           class="flavor-encuesta-crear__input"
                           min="0"
                           value="<?php echo esc_attr((int) $contexto_id); ?>"
                           placeholder="<?php esc_attr_e('Ej: ID de foro, comunidad, grupo...', 'flavor-platform'); ?>">
                    <p class="flavor-encuesta-crear__help">
                        <?php esc_html_e('Puedes poner el ID manualmente o usar el buscador de abajo.', 'flavor-platform'); ?>
                    </p>
                </div>

                <div class="flavor-encuesta-crear__section" id="encuesta-contexto-search-wrap">
                    <label for="encuesta-contexto-buscar" class="flavor-encuesta-crear__label">
                        <?php esc_html_e('Buscar destino por nombre', 'flavor-platform'); ?>
                    </label>
                    <input type="text"
                           id="encuesta-contexto-buscar"
                           class="flavor-encuesta-crear__input"
                           autocomplete="off"
                           placeholder="<?php esc_attr_e('Escribe al menos 2 letras...', 'flavor-platform'); ?>">
                    <div id="encuesta-contexto-resultados" class="flavor-encuesta-crear__context-results" style="display:none;"></div>
                    <div id="encuesta-contexto-seleccion" class="flavor-encuesta-crear__context-selection" style="display:none;">
                        <span id="encuesta-contexto-seleccion-label"></span>
                        <button type="button" class="flavor-encuesta-crear__context-clear" id="encuesta-contexto-seleccion-clear">
                            <?php esc_html_e('Cambiar', 'flavor-platform'); ?>
                        </button>
                    </div>
                </div>

                <div class="flavor-encuesta-crear__section">
                    <label for="encuesta-titulo" class="flavor-encuesta-crear__label">
                        <?php esc_html_e('Título de la encuesta', 'flavor-platform'); ?> *
                    </label>
                    <input type="text"
                           id="encuesta-titulo"
                           name="titulo"
                           class="flavor-encuesta-crear__input"
                           required
                           placeholder="<?php esc_attr_e('¿Cuál es tu pregunta?', 'flavor-platform'); ?>">
                </div>

                <div class="flavor-encuesta-crear__section">
                    <label for="encuesta-descripcion" class="flavor-encuesta-crear__label">
                        <?php esc_html_e('Descripción (opcional)', 'flavor-platform'); ?>
                    </label>
                    <textarea id="encuesta-descripcion"
                              name="descripcion"
                              class="flavor-encuesta-crear__textarea"
                              rows="2"
                              placeholder="<?php esc_attr_e('Añade contexto o instrucciones...', 'flavor-platform'); ?>"></textarea>
                </div>

                <div class="flavor-encuesta-crear__section">
                    <label class="flavor-encuesta-crear__label">
                        <?php esc_html_e('Preguntas', 'flavor-platform'); ?> *
                    </label>

                    <div id="encuesta-preguntas" class="flavor-encuesta-crear__preguntas">
                        <div class="flavor-encuesta-crear__pregunta" data-index="0">
                            <div class="flavor-encuesta-crear__pregunta-head">
                                <strong><?php esc_html_e('Pregunta 1', 'flavor-platform'); ?></strong>
                                <button type="button" class="flavor-encuesta-crear__pregunta-remove" aria-label="<?php esc_attr_e('Eliminar pregunta', 'flavor-platform'); ?>">×</button>
                            </div>

                            <input type="text"
                                   name="campo_etiqueta[]"
                                   class="flavor-encuesta-crear__input"
                                   placeholder="<?php esc_attr_e('Escribe la pregunta', 'flavor-platform'); ?>"
                                   required>

                            <select name="campo_tipo[]" class="flavor-encuesta-crear__input flavor-encuesta-crear__pregunta-tipo">
                                <option value="seleccion_unica"><?php esc_html_e('Selección única', 'flavor-platform'); ?></option>
                                <option value="seleccion_multiple"><?php esc_html_e('Selección múltiple', 'flavor-platform'); ?></option>
                                <option value="texto"><?php esc_html_e('Texto corto', 'flavor-platform'); ?></option>
                                <option value="textarea"><?php esc_html_e('Texto largo', 'flavor-platform'); ?></option>
                                <option value="email"><?php esc_html_e('Email', 'flavor-platform'); ?></option>
                                <option value="telefono"><?php esc_html_e('Teléfono', 'flavor-platform'); ?></option>
                                <option value="url"><?php esc_html_e('URL', 'flavor-platform'); ?></option>
                                <option value="numero"><?php esc_html_e('Número', 'flavor-platform'); ?></option>
                                <option value="rango"><?php esc_html_e('Rango (slider)', 'flavor-platform'); ?></option>
                                <option value="escala"><?php esc_html_e('Escala (1-10)', 'flavor-platform'); ?></option>
                                <option value="nps"><?php esc_html_e('NPS (0-10)', 'flavor-platform'); ?></option>
                                <option value="estrellas"><?php esc_html_e('Estrellas (1-5)', 'flavor-platform'); ?></option>
                                <option value="si_no"><?php esc_html_e('Sí/No', 'flavor-platform'); ?></option>
                                <option value="fecha"><?php esc_html_e('Fecha', 'flavor-platform'); ?></option>
                                <option value="fecha_hora"><?php esc_html_e('Fecha y hora', 'flavor-platform'); ?></option>
                            </select>

                            <div class="flavor-encuesta-crear__pregunta-opciones">
                                <div class="flavor-encuesta-crear__opciones">
                                    <div class="flavor-encuesta-crear__opcion">
                                        <input type="text" class="flavor-encuesta-crear__input" name="campo_opciones_0[]" placeholder="<?php esc_attr_e('Opción 1', 'flavor-platform'); ?>" required>
                                        <button type="button" class="flavor-encuesta-crear__pregunta-remove-opcion" aria-label="<?php esc_attr_e('Eliminar', 'flavor-platform'); ?>">×</button>
                                    </div>
                                    <div class="flavor-encuesta-crear__opcion">
                                        <input type="text" class="flavor-encuesta-crear__input" name="campo_opciones_0[]" placeholder="<?php esc_attr_e('Opción 2', 'flavor-platform'); ?>" required>
                                        <button type="button" class="flavor-encuesta-crear__pregunta-remove-opcion" aria-label="<?php esc_attr_e('Eliminar', 'flavor-platform'); ?>">×</button>
                                    </div>
                                </div>
                                <button type="button" class="flavor-encuesta-crear__add-opcion flavor-encuesta-crear__pregunta-add-opcion">
                                    + <?php esc_html_e('Añadir opción', 'flavor-platform'); ?>
                                </button>
                            </div>

                            <div class="flavor-encuesta-crear__pregunta-range" style="display:none;">
                                <div class="flavor-encuesta-crear__range-config">
                                    <input type="number" name="campo_config_min[]" class="flavor-encuesta-crear__input" placeholder="<?php esc_attr_e('Mínimo', 'flavor-platform'); ?>" value="1">
                                    <input type="number" name="campo_config_max[]" class="flavor-encuesta-crear__input" placeholder="<?php esc_attr_e('Máximo', 'flavor-platform'); ?>" value="10">
                                    <input type="number" step="0.1" name="campo_config_step[]" class="flavor-encuesta-crear__input" placeholder="<?php esc_attr_e('Paso', 'flavor-platform'); ?>" value="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="flavor-encuesta-crear__add-opcion" id="agregar-pregunta">
                        + <?php esc_html_e('Añadir pregunta', 'flavor-platform'); ?>
                    </button>
                </div>

                <div class="flavor-encuesta-crear__section flavor-encuesta-crear__section--options">
                    <label class="flavor-encuesta-crear__checkbox">
                        <input type="checkbox" name="permite_multiples" value="1">
                        <?php esc_html_e('Permitir varias respuestas por usuario', 'flavor-platform'); ?>
                    </label>

                    <label class="flavor-encuesta-crear__checkbox">
                        <input type="checkbox" name="es_anonima" value="1">
                        <?php esc_html_e('Encuesta anónima', 'flavor-platform'); ?>
                    </label>
                </div>

                <div class="flavor-encuesta-crear__section">
                    <label for="encuesta-cierre" class="flavor-encuesta-crear__label">
                        <?php esc_html_e('Fecha de cierre (opcional)', 'flavor-platform'); ?>
                    </label>
                    <input type="datetime-local"
                           id="encuesta-cierre"
                           name="fecha_cierre"
                           class="flavor-encuesta-crear__input">
                </div>

                <div class="flavor-encuesta-crear__actions">
                    <button type="submit" class="flavor-encuesta-crear__submit">
                        <?php esc_html_e('Crear encuesta', 'flavor-platform'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // LISTA DE ENCUESTAS POR CONTEXTO
    // =========================================================================

    /**
     * Renderiza lista de encuestas de un contexto
     *
     * @param array $atts Atributos
     * @return string HTML
     */
    public function render_lista_contexto($atts) {
        $encuestas = $this->module->listar_por_contexto(
            $atts['tipo'],
            $atts['id'],
            [
                'estado' => $atts['estado'],
                'limit'  => $atts['limit'],
            ]
        );

        if (empty($encuestas)) {
            $html = '<div class="flavor-encuestas-lista__empty-state">';
            $html .= '<p class="flavor-encuestas-lista__empty">' .
                     esc_html__('No hay encuestas disponibles', 'flavor-platform') . '</p>';

            if (is_user_logged_in()) {
                $html .= '<p><a class="flavor-encuestas-lista__empty-cta" href="' .
                         esc_url(home_url('/mi-portal/encuestas/crear/')) . '">' .
                         esc_html__('Crear encuesta', 'flavor-platform') . '</a></p>';
            }

            $html .= '</div>';
            return $html;
        }

        ob_start();
        ?>
        <div class="flavor-encuestas-lista">
            <?php foreach ($encuestas as $encuesta): ?>
                <div class="flavor-encuestas-lista__item">
                    <a href="<?php echo esc_url($this->get_encuesta_url($encuesta->id)); ?>"
                       class="flavor-encuestas-lista__link">
                        <h4 class="flavor-encuestas-lista__titulo"><?php echo esc_html($encuesta->titulo); ?></h4>
                        <div class="flavor-encuestas-lista__meta">
                            <span class="flavor-encuestas-lista__participantes">
                                <?php echo esc_html($encuesta->total_participantes); ?> participantes
                            </span>
                            <span class="flavor-encuestas-lista__estado flavor-encuestas-lista__estado--<?php echo esc_attr($encuesta->estado); ?>">
                                <?php echo esc_html($encuesta->estado); ?>
                            </span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Renderiza badges de metadatos
     */
    private function render_meta_badges($encuesta) {
        $badges = [];

        // Tipo
        $tipos = [
            'encuesta'   => __('Encuesta', 'flavor-platform'),
            'formulario' => __('Formulario', 'flavor-platform'),
            'quiz'       => __('Quiz', 'flavor-platform'),
        ];
        $badges[] = '<span class="flavor-encuesta__badge flavor-encuesta__badge--tipo">' .
                    esc_html($tipos[$encuesta->tipo] ?? $encuesta->tipo) . '</span>';

        // Anónima
        if ($encuesta->es_anonima) {
            $badges[] = '<span class="flavor-encuesta__badge flavor-encuesta__badge--anonima">' .
                        esc_html__('Anónima', 'flavor-platform') . '</span>';
        }

        // Estado
        $estados_labels = [
            'borrador'  => __('Borrador', 'flavor-platform'),
            'activa'    => __('Activa', 'flavor-platform'),
            'cerrada'   => __('Cerrada', 'flavor-platform'),
            'archivada' => __('Archivada', 'flavor-platform'),
        ];
        $badges[] = '<span class="flavor-encuesta__badge flavor-encuesta__badge--estado flavor-encuesta__badge--' .
                    esc_attr($encuesta->estado) . '">' .
                    esc_html($estados_labels[$encuesta->estado] ?? $encuesta->estado) . '</span>';

        return implode('', $badges);
    }

    /**
     * Renderiza mensaje de error
     */
    private function render_error($mensaje) {
        return '<div class="flavor-encuesta__error">' . esc_html($mensaje) . '</div>';
    }

    /**
     * Obtiene URL de una encuesta
     */
    private function get_encuesta_url($encuesta_id) {
        return add_query_arg('encuesta_id', $encuesta_id, get_permalink());
    }

    /**
     * Obtiene sesión ID
     */
    private function obtener_sesion_id() {
        return isset($_COOKIE['flavor_encuestas_sid'])
            ? sanitize_text_field($_COOKIE['flavor_encuestas_sid'])
            : null;
    }
}

<?php
/**
 * Vista: Listado de Proyectos de Presupuestos Participativos
 *
 * Variables disponibles:
 * - $proyectos: array de proyectos
 * - $edicion: objeto con datos de la edicion actual
 * - $fase: string con la fase actual (propuestas, votacion, implementacion, cerrada)
 * - $categorias: array de categorias disponibles
 * - $identificador_usuario: int ID del usuario actual
 * - $votos_usuario: array de IDs de proyectos votados por el usuario
 * - $atributos: array con configuracion del shortcode
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas = intval($atributos['columnas'] ?? 3);
$mostrar_filtros = ($atributos['mostrar_filtros'] ?? 'si') === 'si';
?>

<div class="flavor-pp-contenedor" data-fase="<?php echo esc_attr($fase); ?>">

    <?php if ($mostrar_filtros && !empty($categorias)): ?>
    <div class="flavor-pp-filtros">
        <div class="flavor-pp-filtros-grupo">
            <label for="flavor-pp-filtro-categoria"><?php esc_html_e('Categoria:', 'flavor-chat-ia'); ?></label>
            <select id="flavor-pp-filtro-categoria" class="flavor-pp-select">
                <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                <?php foreach ($categorias as $slug => $nombre): ?>
                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flavor-pp-filtros-grupo">
            <label for="flavor-pp-filtro-orden"><?php esc_html_e('Ordenar por:', 'flavor-chat-ia'); ?></label>
            <select id="flavor-pp-filtro-orden" class="flavor-pp-select">
                <option value="votos"><?php esc_html_e('Mas votados', 'flavor-chat-ia'); ?></option>
                <option value="recientes"><?php esc_html_e('Mas recientes', 'flavor-chat-ia'); ?></option>
                <option value="presupuesto"><?php esc_html_e('Mayor presupuesto', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <div class="flavor-pp-filtros-grupo">
            <input type="text" id="flavor-pp-buscar" class="flavor-pp-input" placeholder="<?php esc_attr_e('Buscar proyectos...', 'flavor-chat-ia'); ?>">
        </div>
    </div>
    <?php endif; ?>

    <?php if ($fase === 'votacion' && $identificador_usuario): ?>
    <div class="flavor-pp-info-votacion">
        <span class="dashicons dashicons-info"></span>
        <?php
        $votos_restantes = max(0, intval($this->settings['votos_maximos_por_persona'] ?? 3) - count($votos_usuario));
        printf(
            esc_html__('Te quedan %d votos disponibles.', 'flavor-chat-ia'),
            $votos_restantes
        );
        ?>
    </div>
    <?php endif; ?>

    <?php if (empty($proyectos)): ?>
        <div class="flavor-pp-vacio">
            <span class="dashicons dashicons-clipboard"></span>
            <p><?php esc_html_e('No hay proyectos disponibles en este momento.', 'flavor-chat-ia'); ?></p>
            <?php if ($fase === 'propuestas'): ?>
                <a href="<?php echo esc_url(home_url('/proponer-proyecto/')); ?>" class="flavor-pp-boton flavor-pp-boton-primario">
                    <?php esc_html_e('Proponer un proyecto', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="flavor-pp-grid flavor-pp-columnas-<?php echo esc_attr($columnas); ?>">
            <?php foreach ($proyectos as $proyecto):
                $votado = in_array($proyecto->id, $votos_usuario);
                $es_mio = ($identificador_usuario && $proyecto->proponente_id == $identificador_usuario);
                $categoria_nombre = $categorias[$proyecto->categoria] ?? ucfirst($proyecto->categoria);
            ?>
            <article class="flavor-pp-proyecto <?php echo $votado ? 'votado' : ''; ?>" data-id="<?php echo esc_attr($proyecto->id); ?>" data-categoria="<?php echo esc_attr($proyecto->categoria); ?>">

                <?php if (!empty($proyecto->imagen_url)): ?>
                <div class="flavor-pp-proyecto-imagen">
                    <img src="<?php echo esc_url($proyecto->imagen_url); ?>" alt="<?php echo esc_attr($proyecto->titulo); ?>" loading="lazy">
                </div>
                <?php endif; ?>

                <div class="flavor-pp-proyecto-contenido">
                    <span class="flavor-pp-categoria flavor-pp-categoria-<?php echo esc_attr($proyecto->categoria); ?>">
                        <?php echo esc_html($categoria_nombre); ?>
                    </span>

                    <h3 class="flavor-pp-proyecto-titulo">
                        <a href="<?php echo esc_url(add_query_arg('proyecto', $proyecto->id, home_url('/proyectos-participativos/'))); ?>">
                            <?php echo esc_html($proyecto->titulo); ?>
                        </a>
                    </h3>

                    <p class="flavor-pp-proyecto-descripcion">
                        <?php echo esc_html(wp_trim_words($proyecto->descripcion, 25, '...')); ?>
                    </p>

                    <div class="flavor-pp-proyecto-meta">
                        <span class="flavor-pp-meta-item" title="<?php esc_attr_e('Presupuesto estimado', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php echo esc_html(number_format($proyecto->presupuesto_estimado, 0, ',', '.')); ?> EUR
                        </span>

                        <span class="flavor-pp-meta-item" title="<?php esc_attr_e('Votos recibidos', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-heart"></span>
                            <?php echo esc_html($proyecto->votos_recibidos ?? 0); ?>
                        </span>

                        <?php if (!empty($proyecto->ubicacion)): ?>
                        <span class="flavor-pp-meta-item" title="<?php esc_attr_e('Ubicacion', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($proyecto->ubicacion); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($fase === 'votacion' && $identificador_usuario && !$es_mio): ?>
                    <div class="flavor-pp-proyecto-acciones">
                        <?php if ($votado): ?>
                            <button type="button" class="flavor-pp-boton flavor-pp-boton-secundario flavor-pp-btn-quitar-voto" data-proyecto-id="<?php echo esc_attr($proyecto->id); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <?php esc_html_e('Quitar voto', 'flavor-chat-ia'); ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="flavor-pp-boton flavor-pp-boton-primario flavor-pp-btn-votar" data-proyecto-id="<?php echo esc_attr($proyecto->id); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <?php esc_html_e('Votar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($es_mio): ?>
                    <div class="flavor-pp-proyecto-badge">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php esc_html_e('Tu propuesta', 'flavor-chat-ia'); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flavor-pp-proyecto-estado flavor-pp-estado-<?php echo esc_attr($proyecto->estado); ?>">
                    <?php
                    $estados = [
                        'pendiente' => __('Pendiente de revision', 'flavor-chat-ia'),
                        'validado' => __('Validado', 'flavor-chat-ia'),
                        'en_votacion' => __('En votacion', 'flavor-chat-ia'),
                        'seleccionado' => __('Seleccionado', 'flavor-chat-ia'),
                        'en_ejecucion' => __('En ejecucion', 'flavor-chat-ia'),
                        'ejecutado' => __('Ejecutado', 'flavor-chat-ia'),
                        'rechazado' => __('Rechazado', 'flavor-chat-ia'),
                    ];
                    echo esc_html($estados[$proyecto->estado] ?? ucfirst($proyecto->estado));
                    ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

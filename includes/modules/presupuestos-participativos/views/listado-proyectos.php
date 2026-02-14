<?php
/**
 * Vista: Listado de proyectos de presupuestos participativos
 *
 * @package FlavorChatIA
 * @var array  $proyectos        Lista de proyectos
 * @var array  $atributos        Atributos del shortcode
 * @var string $edicion          Año de la edición
 * @var string $fase             Fase actual
 * @var array  $categorias       Categorías disponibles
 * @var array  $votos_usuario    IDs de proyectos votados por el usuario
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas_grid = intval($atributos['columnas'] ?? 3);
$mostrar_filtros = ($atributos['mostrar_filtros'] ?? 'si') === 'si';
$identificador_usuario = get_current_user_id();
?>

<div class="flavor-pp-listado" data-columnas="<?php echo esc_attr($columnas_grid); ?>">

    <?php if ($mostrar_filtros): ?>
    <div class="flavor-pp-filtros">
        <div class="flavor-pp-filtros-grupo">
            <label for="pp-filtro-categoria"><?php esc_html_e('Categoría:', 'flavor-chat-ia'); ?></label>
            <select id="pp-filtro-categoria" class="flavor-pp-select">
                <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                <?php foreach ($categorias as $clave_categoria => $etiqueta_categoria): ?>
                    <option value="<?php echo esc_attr($clave_categoria); ?>"
                        <?php selected($atributos['categoria'] ?? '', $clave_categoria); ?>>
                        <?php echo esc_html($etiqueta_categoria); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flavor-pp-filtros-grupo">
            <label for="pp-filtro-orden"><?php esc_html_e('Ordenar por:', 'flavor-chat-ia'); ?></label>
            <select id="pp-filtro-orden" class="flavor-pp-select">
                <option value="votos"><?php esc_html_e('Más votados', 'flavor-chat-ia'); ?></option>
                <option value="recientes"><?php esc_html_e('Más recientes', 'flavor-chat-ia'); ?></option>
                <option value="presupuesto"><?php esc_html_e('Mayor presupuesto', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <div class="flavor-pp-filtros-grupo">
            <input type="text" id="pp-busqueda" class="flavor-pp-input"
                   placeholder="<?php esc_attr_e('Buscar proyectos...', 'flavor-chat-ia'); ?>">
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($edicion)): ?>
    <div class="flavor-pp-info-edicion">
        <span class="flavor-pp-edicion-anio">
            <?php printf(esc_html__('Edición %s', 'flavor-chat-ia'), esc_html($edicion)); ?>
        </span>
        <span class="flavor-pp-fase flavor-pp-fase-<?php echo esc_attr($fase); ?>">
            <?php
            $etiquetas_fase = [
                'propuestas' => __('Recibiendo propuestas', 'flavor-chat-ia'),
                'evaluacion' => __('En evaluación', 'flavor-chat-ia'),
                'votacion' => __('Votación abierta', 'flavor-chat-ia'),
                'implementacion' => __('En implementación', 'flavor-chat-ia'),
                'cerrada' => __('Edición cerrada', 'flavor-chat-ia'),
            ];
            echo esc_html($etiquetas_fase[$fase] ?? ucfirst($fase));
            ?>
        </span>
    </div>
    <?php endif; ?>

    <?php if (empty($proyectos)): ?>
        <div class="flavor-pp-vacio">
            <span class="dashicons dashicons-portfolio"></span>
            <p><?php esc_html_e('No hay proyectos disponibles en este momento.', 'flavor-chat-ia'); ?></p>
            <?php if ($fase === 'propuestas' && is_user_logged_in()): ?>
                <a href="<?php echo esc_url(home_url('/presupuestos-participativos/proponer/')); ?>" class="flavor-pp-btn flavor-pp-btn-primary">
                    <?php esc_html_e('Proponer un proyecto', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="flavor-pp-grid flavor-pp-grid-<?php echo esc_attr($columnas_grid); ?>">
            <?php foreach ($proyectos as $proyecto):
                $ya_votado = in_array($proyecto['id'], $votos_usuario);
                $etiqueta_categoria = $categorias[$proyecto['categoria']] ?? ucfirst($proyecto['categoria']);
            ?>
                <article class="flavor-pp-proyecto-card <?php echo $ya_votado ? 'flavor-pp-votado' : ''; ?>"
                         data-id="<?php echo esc_attr($proyecto['id']); ?>">

                    <div class="flavor-pp-proyecto-header">
                        <span class="flavor-pp-categoria flavor-pp-cat-<?php echo esc_attr($proyecto['categoria']); ?>">
                            <?php echo esc_html($etiqueta_categoria); ?>
                        </span>
                        <?php if ($ya_votado): ?>
                            <span class="flavor-pp-votado-badge" title="<?php esc_attr_e('Ya has votado', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h3 class="flavor-pp-proyecto-titulo">
                        <a href="<?php echo esc_url(add_query_arg('proyecto', $proyecto['id'], home_url('/presupuestos-participativos/'))); ?>">
                            <?php echo esc_html($proyecto['titulo']); ?>
                        </a>
                    </h3>

                    <p class="flavor-pp-proyecto-descripcion">
                        <?php echo esc_html($proyecto['descripcion']); ?>
                    </p>

                    <div class="flavor-pp-proyecto-meta">
                        <div class="flavor-pp-meta-item">
                            <span class="dashicons dashicons-money-alt"></span>
                            <span class="flavor-pp-presupuesto">
                                <?php echo number_format($proyecto['presupuesto'], 0, ',', '.'); ?> €
                            </span>
                        </div>

                        <?php if (!empty($proyecto['ubicacion'])): ?>
                        <div class="flavor-pp-meta-item">
                            <span class="dashicons dashicons-location"></span>
                            <span><?php echo esc_html($proyecto['ubicacion']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-pp-proyecto-footer">
                        <div class="flavor-pp-votos">
                            <span class="dashicons dashicons-thumbs-up"></span>
                            <span class="flavor-pp-votos-count"><?php echo intval($proyecto['votos']); ?></span>
                            <span class="flavor-pp-votos-label"><?php esc_html_e('votos', 'flavor-chat-ia'); ?></span>
                        </div>

                        <?php if ($fase === 'votacion' && $identificador_usuario && !$ya_votado): ?>
                            <button type="button" class="flavor-pp-btn flavor-pp-btn-votar"
                                    data-proyecto="<?php echo esc_attr($proyecto['id']); ?>">
                                <span class="dashicons dashicons-thumbs-up"></span>
                                <?php esc_html_e('Votar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>

                        <a href="<?php echo esc_url(add_query_arg('proyecto', $proyecto['id'], home_url('/presupuestos-participativos/'))); ?>"
                           class="flavor-pp-btn flavor-pp-btn-ver">
                            <?php esc_html_e('Ver más', 'flavor-chat-ia'); ?>
                        </a>
                    </div>

                    <?php if ($proyecto['porcentaje_ejecucion'] > 0): ?>
                    <div class="flavor-pp-progreso">
                        <div class="flavor-pp-progreso-bar" style="width: <?php echo intval($proyecto['porcentaje_ejecucion']); ?>%"></div>
                        <span class="flavor-pp-progreso-texto">
                            <?php printf(esc_html__('%d%% ejecutado', 'flavor-chat-ia'), intval($proyecto['porcentaje_ejecucion'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-pp-cargar-mas" style="display: none;">
            <button type="button" class="flavor-pp-btn flavor-pp-btn-secondary" id="pp-cargar-mas">
                <?php esc_html_e('Cargar más proyectos', 'flavor-chat-ia'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

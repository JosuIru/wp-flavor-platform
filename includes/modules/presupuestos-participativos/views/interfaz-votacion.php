<?php
/**
 * Vista: Interfaz de Votacion
 *
 * Variables disponibles:
 * - $proyectos: array de proyectos disponibles para votar
 * - $edicion: objeto con datos de la edicion actual
 * - $votos_usuario: array de IDs de proyectos ya votados
 * - $votos_maximos: int numero maximo de votos permitidos
 * - $votos_restantes: int votos restantes del usuario
 * - $categorias: array de categorias
 * - $configuracion: array de configuracion
 * - $atributos: array con configuracion del shortcode
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas = intval($atributos['columnas'] ?? 2);
?>

<div class="flavor-pp-votacion-contenedor">
    <div class="flavor-pp-votacion-header">
        <h2><?php esc_html_e('Votacion de Proyectos', 'flavor-chat-ia'); ?></h2>
        <p class="flavor-pp-votacion-intro">
            <?php printf(
                esc_html__('Edicion %d - Selecciona los proyectos que quieres apoyar con tu voto.', 'flavor-chat-ia'),
                intval($edicion->anio)
            ); ?>
        </p>
    </div>

    <div class="flavor-pp-votacion-info">
        <div class="flavor-pp-votos-contador">
            <span class="flavor-pp-votos-icono">
                <span class="dashicons dashicons-heart"></span>
            </span>
            <span class="flavor-pp-votos-texto">
                <?php printf(
                    esc_html__('Votos disponibles: %d de %d', 'flavor-chat-ia'),
                    $votos_restantes,
                    $votos_maximos
                ); ?>
            </span>
            <div class="flavor-pp-votos-barra">
                <div class="flavor-pp-votos-progreso" style="width: <?php echo esc_attr((($votos_maximos - $votos_restantes) / $votos_maximos) * 100); ?>%"></div>
            </div>
        </div>

        <?php if (!empty($votos_usuario)): ?>
        <div class="flavor-pp-mis-votos">
            <strong><?php esc_html_e('Has votado:', 'flavor-chat-ia'); ?></strong>
            <span class="flavor-pp-votos-lista">
                <?php echo count($votos_usuario); ?> <?php esc_html_e('proyecto(s)', 'flavor-chat-ia'); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($proyectos)): ?>
        <div class="flavor-pp-vacio">
            <span class="dashicons dashicons-clipboard"></span>
            <p><?php esc_html_e('No hay proyectos disponibles para votar en este momento.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="flavor-pp-filtros flavor-pp-filtros-votacion">
            <div class="flavor-pp-filtros-grupo">
                <label for="flavor-pp-filtro-cat-votacion"><?php esc_html_e('Filtrar por categoria:', 'flavor-chat-ia'); ?></label>
                <select id="flavor-pp-filtro-cat-votacion" class="flavor-pp-select flavor-pp-filtro-votacion">
                    <option value=""><?php esc_html_e('Todas las categorias', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias as $slug => $nombre): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flavor-pp-grid flavor-pp-grid-votacion flavor-pp-columnas-<?php echo esc_attr($columnas); ?>">
            <?php foreach ($proyectos as $proyecto):
                $votado = in_array($proyecto->id, $votos_usuario);
                $categoria_nombre = $categorias[$proyecto->categoria] ?? ucfirst($proyecto->categoria);
            ?>
            <article class="flavor-pp-proyecto flavor-pp-proyecto-votacion <?php echo $votado ? 'votado' : ''; ?>"
                     data-id="<?php echo esc_attr($proyecto->id); ?>"
                     data-categoria="<?php echo esc_attr($proyecto->categoria); ?>">

                <?php if (!empty($proyecto->imagen_url)): ?>
                <div class="flavor-pp-proyecto-imagen">
                    <img src="<?php echo esc_url($proyecto->imagen_url); ?>" alt="<?php echo esc_attr($proyecto->titulo); ?>" loading="lazy">
                    <?php if ($votado): ?>
                    <div class="flavor-pp-votado-badge">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Votado', 'flavor-chat-ia'); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="flavor-pp-proyecto-contenido">
                    <span class="flavor-pp-categoria flavor-pp-categoria-<?php echo esc_attr($proyecto->categoria); ?>">
                        <?php echo esc_html($categoria_nombre); ?>
                    </span>

                    <h3 class="flavor-pp-proyecto-titulo"><?php echo esc_html($proyecto->titulo); ?></h3>

                    <p class="flavor-pp-proyecto-descripcion">
                        <?php echo esc_html(wp_trim_words($proyecto->descripcion, 30, '...')); ?>
                    </p>

                    <div class="flavor-pp-proyecto-meta">
                        <span class="flavor-pp-meta-item">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php echo esc_html(number_format($proyecto->presupuesto_estimado, 0, ',', '.')); ?> EUR
                        </span>
                        <span class="flavor-pp-meta-item">
                            <span class="dashicons dashicons-heart"></span>
                            <?php echo esc_html($proyecto->votos_recibidos ?? 0); ?> <?php esc_html_e('votos', 'flavor-chat-ia'); ?>
                        </span>
                    </div>

                    <div class="flavor-pp-proyecto-acciones">
                        <?php if ($votado): ?>
                            <button type="button"
                                    class="flavor-pp-boton flavor-pp-boton-secundario flavor-pp-btn-quitar-voto"
                                    data-proyecto-id="<?php echo esc_attr($proyecto->id); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <?php esc_html_e('Quitar voto', 'flavor-chat-ia'); ?>
                            </button>
                        <?php else: ?>
                            <button type="button"
                                    class="flavor-pp-boton flavor-pp-boton-primario flavor-pp-btn-votar"
                                    data-proyecto-id="<?php echo esc_attr($proyecto->id); ?>"
                                    <?php echo ($votos_restantes <= 0) ? 'disabled' : ''; ?>>
                                <span class="dashicons dashicons-heart"></span>
                                <?php esc_html_e('Votar este proyecto', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>

                        <a href="<?php echo esc_url(add_query_arg('proyecto', $proyecto->id, home_url('/proyectos-participativos/'))); ?>"
                           class="flavor-pp-boton flavor-pp-boton-texto">
                            <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

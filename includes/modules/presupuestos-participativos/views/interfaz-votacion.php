<?php
/**
 * Vista: Interfaz de votación de proyectos
 *
 * @package FlavorChatIA
 * @var object $edicion          Edición activa
 * @var array  $proyectos        Proyectos disponibles para votar
 * @var array  $votos_usuario    IDs de proyectos votados
 * @var int    $votos_maximos    Máximo de votos permitidos
 * @var int    $votos_restantes  Votos disponibles
 * @var array  $categorias       Categorías
 * @var array  $atributos        Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas_grid = intval($atributos['columnas'] ?? 2);
?>

<div class="flavor-pp-votacion" data-votos-maximos="<?php echo esc_attr($votos_maximos); ?>">

    <div class="flavor-pp-votacion-header">
        <div class="flavor-pp-info-votacion">
            <h2>
                <?php printf(
                    esc_html__('Votación - Presupuestos Participativos %d', 'flavor-chat-ia'),
                    intval($edicion->anio)
                ); ?>
            </h2>
            <p>
                <?php printf(
                    esc_html__('Selecciona hasta %d proyectos que consideres prioritarios para el barrio.', 'flavor-chat-ia'),
                    $votos_maximos
                ); ?>
            </p>
        </div>

        <div class="flavor-pp-contador-votos">
            <div class="flavor-pp-votos-disponibles <?php echo $votos_restantes === 0 ? 'agotados' : ''; ?>">
                <span class="flavor-pp-votos-numero"><?php echo intval($votos_restantes); ?></span>
                <span class="flavor-pp-votos-texto">
                    <?php echo esc_html(_n('voto disponible', 'votos disponibles', $votos_restantes, 'flavor-chat-ia')); ?>
                </span>
            </div>
            <div class="flavor-pp-votos-emitidos">
                <span><?php esc_html_e('Votos emitidos:', 'flavor-chat-ia'); ?></span>
                <span class="flavor-pp-votos-count"><?php echo count($votos_usuario); ?></span>
                <span>/</span>
                <span><?php echo intval($votos_maximos); ?></span>
            </div>
        </div>
    </div>

    <?php if ($votos_restantes === 0): ?>
    <div class="flavor-pp-notice flavor-pp-notice-success">
        <span class="dashicons dashicons-yes-alt"></span>
        <?php esc_html_e('¡Has utilizado todos tus votos! Puedes cambiarlos si lo deseas.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <div class="flavor-pp-filtros-votacion">
        <select id="pp-filtro-categoria-votacion" class="flavor-pp-select">
            <option value=""><?php esc_html_e('Todas las categorías', 'flavor-chat-ia'); ?></option>
            <?php foreach ($categorias as $clave_categoria => $etiqueta_categoria): ?>
                <option value="<?php echo esc_attr($clave_categoria); ?>">
                    <?php echo esc_html($etiqueta_categoria); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="flavor-pp-toggle-vista">
            <button type="button" class="active" data-vista="grid" title="<?php esc_attr_e('Vista en cuadrícula', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-grid-view"></span>
            </button>
            <button type="button" data-vista="lista" title="<?php esc_attr_e('Vista en lista', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-list-view"></span>
            </button>
        </div>
    </div>

    <?php if (empty($proyectos)): ?>
        <div class="flavor-pp-vacio">
            <span class="dashicons dashicons-portfolio"></span>
            <p><?php esc_html_e('No hay proyectos disponibles para votar en este momento.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="flavor-pp-grid-votacion flavor-pp-grid-<?php echo esc_attr($columnas_grid); ?>">
            <?php foreach ($proyectos as $proyecto):
                $ya_votado = in_array($proyecto->id, $votos_usuario);
                $etiqueta_categoria = $categorias[$proyecto->categoria] ?? ucfirst($proyecto->categoria);
            ?>
                <article class="flavor-pp-proyecto-votacion <?php echo $ya_votado ? 'votado' : ''; ?>"
                         data-id="<?php echo esc_attr($proyecto->id); ?>"
                         data-categoria="<?php echo esc_attr($proyecto->categoria); ?>">

                    <div class="flavor-pp-proyecto-content">
                        <span class="flavor-pp-categoria flavor-pp-cat-<?php echo esc_attr($proyecto->categoria); ?>">
                            <?php echo esc_html($etiqueta_categoria); ?>
                        </span>

                        <h3 class="flavor-pp-proyecto-titulo">
                            <?php echo esc_html($proyecto->titulo); ?>
                        </h3>

                        <p class="flavor-pp-proyecto-descripcion">
                            <?php echo esc_html(wp_trim_words($proyecto->descripcion, 30)); ?>
                        </p>

                        <div class="flavor-pp-proyecto-datos">
                            <div class="flavor-pp-dato">
                                <span class="dashicons dashicons-money-alt"></span>
                                <span><?php echo number_format($proyecto->presupuesto_solicitado, 0, ',', '.'); ?> €</span>
                            </div>
                            <?php if (!empty($proyecto->ubicacion)): ?>
                            <div class="flavor-pp-dato">
                                <span class="dashicons dashicons-location"></span>
                                <span><?php echo esc_html($proyecto->ubicacion); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="flavor-pp-votos-actuales">
                            <span class="dashicons dashicons-thumbs-up"></span>
                            <span class="count"><?php echo intval($proyecto->votos_recibidos); ?></span>
                            <span class="label"><?php esc_html_e('votos', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>

                    <div class="flavor-pp-proyecto-acciones">
                        <?php if ($ya_votado): ?>
                            <button type="button" class="flavor-pp-btn flavor-pp-btn-votado flavor-pp-btn-quitar-voto"
                                    data-proyecto="<?php echo esc_attr($proyecto->id); ?>">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span class="texto-votado"><?php esc_html_e('Votado', 'flavor-chat-ia'); ?></span>
                                <span class="texto-quitar"><?php esc_html_e('Quitar voto', 'flavor-chat-ia'); ?></span>
                            </button>
                        <?php else: ?>
                            <button type="button" class="flavor-pp-btn flavor-pp-btn-votar"
                                    data-proyecto="<?php echo esc_attr($proyecto->id); ?>"
                                    <?php echo $votos_restantes === 0 ? 'disabled' : ''; ?>>
                                <span class="dashicons dashicons-thumbs-up"></span>
                                <?php esc_html_e('Votar este proyecto', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>

                        <button type="button" class="flavor-pp-btn flavor-pp-btn-link flavor-pp-ver-detalles"
                                data-proyecto="<?php echo esc_attr($proyecto->id); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Modal de detalles -->
    <div id="flavor-pp-modal-proyecto" class="flavor-pp-modal" style="display: none;">
        <div class="flavor-pp-modal-overlay"></div>
        <div class="flavor-pp-modal-content">
            <button type="button" class="flavor-pp-modal-cerrar">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
            <div class="flavor-pp-modal-body">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

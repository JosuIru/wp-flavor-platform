<?php
/**
 * Vista: Listado de Proyectos de Presupuestos Participativos
 *
 * Variables disponibles:
 * - $proyectos: array de proyectos/propuestas
 * - $edicion/$proceso: objeto con datos del proceso actual
 * - $fase: string con la fase actual (propuestas, votacion, cerrado)
 * - $categorias: array de categorias disponibles
 * - $identificador_usuario: int ID del usuario actual
 * - $votos_usuario: array de IDs de proyectos votados por el usuario
 * - $votos_maximos: int numero máximo de votos permitidos
 * - $atributos: array con configuracion del shortcode
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto seguros
$proyectos = $proyectos ?? [];
$fase = $fase ?? 'cerrado';
$categorias = $categorias ?? [];
$identificador_usuario = $identificador_usuario ?? get_current_user_id();
$votos_usuario = $votos_usuario ?? [];
$votos_maximos = $votos_maximos ?? 3;
$atributos = $atributos ?? [];

$columnas = intval($atributos['columnas'] ?? 3);
$mostrar_filtros = ($atributos['mostrar_filtros'] ?? 'si') === 'si';
?>

<div class="flavor-pp-contenedor" data-fase="<?php echo esc_attr($fase); ?>">

    <?php if ($mostrar_filtros && !empty($categorias)): ?>
    <div class="flavor-pp-filtros">
        <div class="flavor-pp-filtros-grupo">
            <label for="flavor-pp-filtro-categoria"><?php esc_html_e('Categoria:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="flavor-pp-filtro-categoria" class="flavor-pp-select">
                <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($categorias as $slug => $nombre): ?>
                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flavor-pp-filtros-grupo">
            <label for="flavor-pp-filtro-orden"><?php esc_html_e('Ordenar por:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="flavor-pp-filtro-orden" class="flavor-pp-select">
                <option value="votos"><?php esc_html_e('Mas votados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="recientes"><?php esc_html_e('Mas recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="presupuesto"><?php esc_html_e('Mayor presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>

        <div class="flavor-pp-filtros-grupo">
            <input type="text" id="flavor-pp-buscar" class="flavor-pp-input" placeholder="<?php esc_attr_e('Buscar proyectos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>
    </div>
    <?php endif; ?>

    <?php if ($fase === 'votacion' && $identificador_usuario): ?>
    <div class="flavor-pp-info-votacion">
        <span class="dashicons dashicons-info"></span>
        <?php
        // Usar variable $votos_maximos si está disponible, o valor por defecto
        $max_votos = $votos_maximos ?? 3;
        $votos_usados = is_array($votos_usuario) ? count($votos_usuario) : 0;
        $votos_restantes = max(0, intval($max_votos) - $votos_usados);
        printf(
            esc_html__('Te quedan %d votos disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $votos_restantes
        );
        ?>
    </div>
    <?php endif; ?>

    <?php if (empty($proyectos)): ?>
        <div class="flavor-pp-vacio">
            <span class="dashicons dashicons-clipboard"></span>
            <p><?php esc_html_e('No hay proyectos disponibles en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php if ($fase === 'propuestas'): ?>
                <a href="<?php echo esc_url(home_url('/proponer-proyecto/')); ?>" class="flavor-pp-boton flavor-pp-boton-primario">
                    <?php esc_html_e('Proponer un proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="flavor-pp-grid flavor-pp-columnas-<?php echo esc_attr($columnas); ?>">
            <?php foreach ($proyectos as $proyecto):
                // Normalizar: soporta tanto arrays como objetos
                $proyecto_id = is_array($proyecto) ? ($proyecto['id'] ?? 0) : ($proyecto->id ?? 0);
                $proyecto_proponente = is_array($proyecto) ? ($proyecto['proponente_id'] ?? 0) : ($proyecto->proponente_id ?? 0);
                $proyecto_categoria = is_array($proyecto) ? ($proyecto['categoria'] ?? '') : ($proyecto->categoria ?? '');
                $proyecto_imagen = is_array($proyecto) ? ($proyecto['imagen'] ?? $proyecto['imagen_url'] ?? '') : ($proyecto->imagen ?? $proyecto->imagen_url ?? '');
                $proyecto_titulo = is_array($proyecto) ? ($proyecto['titulo'] ?? '') : ($proyecto->titulo ?? '');
                $proyecto_descripcion = is_array($proyecto) ? ($proyecto['descripcion'] ?? '') : ($proyecto->descripcion ?? '');
                $proyecto_presupuesto = is_array($proyecto) ? ($proyecto['presupuesto_solicitado'] ?? $proyecto['presupuesto_estimado'] ?? 0) : ($proyecto->presupuesto_solicitado ?? $proyecto->presupuesto_estimado ?? 0);
                $proyecto_votos = is_array($proyecto) ? ($proyecto['votos_recibidos'] ?? 0) : ($proyecto->votos_recibidos ?? 0);
                $proyecto_ubicacion = is_array($proyecto) ? ($proyecto['ubicacion'] ?? '') : ($proyecto->ubicacion ?? '');
                $proyecto_estado = is_array($proyecto) ? ($proyecto['estado'] ?? '') : ($proyecto->estado ?? '');

                $votado = in_array($proyecto_id, $votos_usuario);
                $es_mio = ($identificador_usuario && $proyecto_proponente == $identificador_usuario);
                $categoria_nombre = $categorias[$proyecto_categoria] ?? ucfirst($proyecto_categoria);
            ?>
            <article class="flavor-pp-proyecto <?php echo $votado ? 'votado' : ''; ?>" data-id="<?php echo esc_attr($proyecto_id); ?>" data-categoria="<?php echo esc_attr($proyecto_categoria); ?>">

                <?php if (!empty($proyecto_imagen)): ?>
                <div class="flavor-pp-proyecto-imagen">
                    <img src="<?php echo esc_url($proyecto_imagen); ?>" alt="<?php echo esc_attr($proyecto_titulo); ?>" loading="lazy">
                </div>
                <?php endif; ?>

                <div class="flavor-pp-proyecto-contenido">
                    <span class="flavor-pp-categoria flavor-pp-categoria-<?php echo esc_attr($proyecto_categoria); ?>">
                        <?php echo esc_html($categoria_nombre); ?>
                    </span>

                    <h3 class="flavor-pp-proyecto-titulo">
                        <a href="<?php echo esc_url(add_query_arg('proyecto', $proyecto_id, home_url('/proyectos-participativos/'))); ?>">
                            <?php echo esc_html($proyecto_titulo); ?>
                        </a>
                    </h3>

                    <p class="flavor-pp-proyecto-descripcion">
                        <?php echo esc_html(wp_trim_words($proyecto_descripcion, 25, '...')); ?>
                    </p>

                    <div class="flavor-pp-proyecto-meta">
                        <span class="flavor-pp-meta-item" title="<?php esc_attr_e('Presupuesto estimado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php echo esc_html(number_format($proyecto_presupuesto, 0, ',', '.')); ?> EUR
                        </span>

                        <span class="flavor-pp-meta-item" title="<?php esc_attr_e('Votos recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-heart"></span>
                            <?php echo esc_html($proyecto_votos); ?>
                        </span>

                        <?php if (!empty($proyecto_ubicacion)): ?>
                        <span class="flavor-pp-meta-item" title="<?php esc_attr_e('Ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($proyecto_ubicacion); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($fase === 'votacion' && $identificador_usuario && !$es_mio): ?>
                    <div class="flavor-pp-proyecto-acciones">
                        <?php if ($votado): ?>
                            <button type="button" class="flavor-pp-boton flavor-pp-boton-secundario flavor-pp-btn-quitar-voto" data-proyecto-id="<?php echo esc_attr($proyecto_id); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <?php esc_html_e('Quitar voto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="flavor-pp-boton flavor-pp-boton-primario flavor-pp-btn-votar" data-proyecto-id="<?php echo esc_attr($proyecto_id); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <?php esc_html_e('Votar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($es_mio): ?>
                    <div class="flavor-pp-proyecto-badge">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php esc_html_e('Tu propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flavor-pp-proyecto-estado flavor-pp-estado-<?php echo esc_attr($proyecto_estado); ?>">
                    <?php
                    $estados = [
                        'pendiente' => __('Pendiente de revision', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'validado' => __('Validado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'en_votacion' => __('En votacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'seleccionado' => __('Seleccionado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'en_ejecucion' => __('En ejecucion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'ejecutado' => __('Ejecutado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'rechazado' => __('Rechazado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ];
                    echo esc_html($estados[$proyecto_estado] ?? ucfirst($proyecto_estado));
                    ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

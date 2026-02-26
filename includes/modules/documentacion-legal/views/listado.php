<?php
/**
 * Vista de listado de documentos legales
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$tabla = $wpdb->prefix . 'flavor_documentacion_legal';
$tabla_categorias = $wpdb->prefix . 'flavor_documentacion_legal_categorias';

$tipo_filtro = sanitize_text_field($atts['tipo'] ?? $_GET['tipo'] ?? '');
$categoria_filtro = sanitize_text_field($atts['categoria'] ?? $_GET['categoria'] ?? '');
$limite = intval($atts['limite'] ?? 12);

$where = "WHERE estado = 'publicado'";
$params = [];

if ($tipo_filtro) {
    $where .= " AND tipo = %s";
    $params[] = $tipo_filtro;
}
if ($categoria_filtro) {
    $where .= " AND categoria = %s";
    $params[] = $categoria_filtro;
}

$params[] = $limite;

$documentos = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla $where ORDER BY destacado DESC, created_at DESC LIMIT %d",
    $params
));

$categorias = $wpdb->get_results("SELECT * FROM $tabla_categorias ORDER BY orden");

$tipos = [
    'ley' => 'Ley',
    'decreto' => 'Decreto',
    'ordenanza' => 'Ordenanza',
    'sentencia' => 'Sentencia',
    'modelo_denuncia' => 'Modelo de Denuncia',
    'modelo_recurso' => 'Modelo de Recurso',
    'guia' => 'Guia',
    'informe' => 'Informe',
    'otro' => 'Otro',
];
?>

<div class="flavor-docs-wrapper">
    <!-- Filtros -->
    <div class="flavor-docs-buscador">
        <form class="flavor-docs-buscador-form" method="get">
            <input type="search" name="q" placeholder="Buscar documentos..." value="<?php echo esc_attr($_GET['q'] ?? ''); ?>">

            <select name="tipo" class="flavor-filtro-tipo">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos as $valor => $etiqueta): ?>
                    <option value="<?php echo esc_attr($valor); ?>" <?php selected($tipo_filtro, $valor); ?>>
                        <?php echo esc_html($etiqueta); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="categoria" class="flavor-filtro-categoria">
                <option value="">Todas las categorias</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($categoria_filtro, $cat->slug); ?>>
                        <?php echo esc_html($cat->nombre); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="flavor-btn flavor-btn-primary">Buscar</button>
        </form>
    </div>

    <!-- Resultados -->
    <div class="flavor-docs-resultados">
        <?php if (empty($documentos)): ?>
            <div class="flavor-empty-state" style="text-align: center; padding: 3rem;">
                <span class="dashicons dashicons-media-document" style="font-size: 3rem; color: #9ca3af;"></span>
                <p style="color: #6b7280; margin-top: 1rem;">No hay documentos que mostrar.</p>
            </div>
        <?php else: ?>
            <div class="flavor-docs-grid">
                <?php foreach ($documentos as $doc): ?>
                    <article class="flavor-doc-card <?php echo $doc->verificado ? 'verificado' : ''; ?>">
                        <div class="flavor-doc-header">
                            <div class="flavor-doc-icono">
                                <span class="dashicons dashicons-media-document"></span>
                            </div>
                            <div class="flavor-doc-meta">
                                <span class="flavor-doc-tipo"><?php echo esc_html($tipos[$doc->tipo] ?? $doc->tipo); ?></span>
                                <h3 class="flavor-doc-titulo">
                                    <a href="<?php echo esc_url(add_query_arg('documento_id', $doc->id)); ?>">
                                        <?php echo esc_html($doc->titulo); ?>
                                    </a>
                                </h3>
                            </div>
                        </div>

                        <?php if ($doc->descripcion): ?>
                            <p class="flavor-doc-descripcion"><?php echo esc_html(wp_trim_words($doc->descripcion, 20)); ?></p>
                        <?php endif; ?>

                        <div class="flavor-doc-footer">
                            <div class="flavor-doc-stats">
                                <span class="flavor-doc-stat">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php echo number_format($doc->descargas); ?>
                                </span>
                                <?php if ($doc->fecha_publicacion): ?>
                                    <span class="flavor-doc-stat">
                                        <span class="dashicons dashicons-calendar"></span>
                                        <?php echo date_i18n('Y', strtotime($doc->fecha_publicacion)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($doc->verificado): ?>
                                <span class="flavor-verificado-badge">
                                    <span class="dashicons dashicons-yes"></span> Verificado
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

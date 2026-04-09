<?php
/**
 * Vista: Buscar Documentos Legales
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = [
    'normativa' => __('Normativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'estatutos' => __('Estatutos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'contratos' => __('Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'formularios' => __('Formularios', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'guias' => __('Guias', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$busqueda_actual = sanitize_text_field($_GET['q'] ?? '');
$categoria_actual = sanitize_text_field($_GET['categoria'] ?? '');
?>

<div class="doc-legal-buscar">
    <h2><?php _e('Buscar documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

    <form class="doc-legal-buscar-form" method="get" action="<?php echo esc_url(home_url('/documentacion-legal/')); ?>">
        <div class="doc-legal-buscar-campo">
            <input type="text" name="q" id="buscar-documentos" value="<?php echo esc_attr($busqueda_actual); ?>" placeholder="<?php esc_attr_e('Buscar por titulo, descripcion, contenido...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <button type="submit" class="doc-legal-btn doc-legal-btn-primary">
                <span class="dashicons dashicons-search"></span>
            </button>
        </div>

        <div class="doc-legal-filtros">
            <div class="doc-legal-filtro">
                <label for="filtro-categoria"><?php _e('Categoria:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="categoria" id="filtro-categoria">
                    <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($categorias as $slug => $nombre): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($categoria_actual, $slug); ?>>
                        <?php echo esc_html($nombre); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="doc-legal-filtro">
                <label for="filtro-orden"><?php _e('Ordenar por:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="orden" id="filtro-orden">
                    <option value="recientes"><?php _e('Mas recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="visitas"><?php _e('Mas vistos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="titulo"><?php _e('Titulo A-Z', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>
        </div>
    </form>

    <div id="doc-legal-resultados" class="doc-legal-resultados">
        <?php if (!empty($busqueda_actual)): ?>
        <p class="doc-legal-resultados-info">
            <?php printf(__('Resultados para: "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($busqueda_actual)); ?>
        </p>
        <?php endif; ?>
        <!-- Los resultados se cargan via AJAX o se renderizan aqui -->
    </div>

    <div class="doc-legal-busqueda-sugerencias">
        <h3><?php _e('Busquedas populares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <div class="doc-legal-tags-lista">
            <a href="?q=estatutos" class="doc-legal-tag"><?php _e('Estatutos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            <a href="?q=reglamento" class="doc-legal-tag"><?php _e('Reglamento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            <a href="?q=formulario" class="doc-legal-tag"><?php _e('Formularios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            <a href="?q=contrato" class="doc-legal-tag"><?php _e('Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
        </div>
    </div>
</div>

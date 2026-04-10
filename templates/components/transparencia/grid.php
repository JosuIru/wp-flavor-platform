<?php
/**
 * Template: Grid de datos públicos disponibles
 *
 * Variables disponibles en $args:
 * - titulo: Título del grid
 * - categorias: Array de categorías con sus datos
 * - mostrar_buscador: Boolean para mostrar buscador
 * - mostrar_filtros: Boolean para mostrar filtros
 * - columnas: Número de columnas (2, 3, 4)
 * - items_por_pagina: Número de items por página
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_grid = isset($args['titulo']) ? $args['titulo'] : __('Portal de Datos Abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN);
$mostrar_buscador = isset($args['mostrar_buscador']) ? $args['mostrar_buscador'] : true;
$mostrar_filtros = isset($args['mostrar_filtros']) ? $args['mostrar_filtros'] : true;
$numero_columnas = isset($args['columnas']) ? intval($args['columnas']) : 3;
$items_por_pagina = isset($args['items_por_pagina']) ? intval($args['items_por_pagina']) : 12;

// Datos de demostración de categorías y documentos
$categorias_datos = isset($args['categorias']) ? $args['categorias'] : array(
    'contratos' => array(
        'nombre' => __('Contratos Públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-media-text',
        'color' => '#3b82f6',
        'descripcion' => __('Licitaciones, adjudicaciones y contratos menores', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'documentos' => array(
            array(
                'titulo' => __('Contrato de mantenimiento de parques', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-15',
                'tipo' => 'PDF',
                'tamano' => '1.2 MB',
                'descargas' => 234,
                'url' => '#'
            ),
            array(
                'titulo' => __('Licitación servicio de limpieza viaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-10',
                'tipo' => 'PDF',
                'tamano' => '2.5 MB',
                'descargas' => 189,
                'url' => '#'
            ),
            array(
                'titulo' => __('Adjudicación obras plaza central', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-05',
                'tipo' => 'PDF',
                'tamano' => '850 KB',
                'descargas' => 312,
                'url' => '#'
            )
        )
    ),
    'subvenciones' => array(
        'nombre' => __('Subvenciones y Ayudas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-money-alt',
        'color' => '#22c55e',
        'descripcion' => __('Convocatorias, resoluciones y beneficiarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'documentos' => array(
            array(
                'titulo' => __('Subvenciones a asociaciones culturales 2024', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-02-01',
                'tipo' => 'PDF',
                'tamano' => '560 KB',
                'descargas' => 445,
                'url' => '#'
            ),
            array(
                'titulo' => __('Ayudas emergencia social Q1 2024', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-28',
                'tipo' => 'XLSX',
                'tamano' => '320 KB',
                'descargas' => 278,
                'url' => '#'
            )
        )
    ),
    'actas' => array(
        'nombre' => __('Actas y Acuerdos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-clipboard',
        'color' => '#f59e0b',
        'descripcion' => __('Plenos, juntas de gobierno y comisiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'documentos' => array(
            array(
                'titulo' => __('Acta Pleno Ordinario Enero 2024', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-25',
                'tipo' => 'PDF',
                'tamano' => '1.8 MB',
                'descargas' => 567,
                'url' => '#'
            ),
            array(
                'titulo' => __('Acta Junta de Gobierno Local 15/01/2024', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-18',
                'tipo' => 'PDF',
                'tamano' => '920 KB',
                'descargas' => 234,
                'url' => '#'
            ),
            array(
                'titulo' => __('Acuerdos Comisión de Urbanismo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-12',
                'tipo' => 'PDF',
                'tamano' => '650 KB',
                'descargas' => 189,
                'url' => '#'
            )
        )
    ),
    'presupuestos' => array(
        'nombre' => __('Presupuestos y Cuentas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-chart-bar',
        'color' => '#8b5cf6',
        'descripcion' => __('Presupuestos, liquidaciones y cuentas anuales', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'documentos' => array(
            array(
                'titulo' => __('Presupuesto General 2024', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2023-12-20',
                'tipo' => 'PDF',
                'tamano' => '4.2 MB',
                'descargas' => 1250,
                'url' => '#'
            ),
            array(
                'titulo' => __('Liquidación Presupuesto 2023', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-30',
                'tipo' => 'PDF',
                'tamano' => '3.1 MB',
                'descargas' => 890,
                'url' => '#'
            )
        )
    ),
    'personal' => array(
        'nombre' => __('Personal y Retribuciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-groups',
        'color' => '#ec4899',
        'descripcion' => __('Plantilla, retribuciones y declaraciones de bienes', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'documentos' => array(
            array(
                'titulo' => __('Relación de puestos de trabajo 2024', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-02',
                'tipo' => 'PDF',
                'tamano' => '780 KB',
                'descargas' => 423,
                'url' => '#'
            ),
            array(
                'titulo' => __('Retribuciones altos cargos 2024', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-01-15',
                'tipo' => 'PDF',
                'tamano' => '245 KB',
                'descargas' => 678,
                'url' => '#'
            )
        )
    ),
    'urbanismo' => array(
        'nombre' => __('Urbanismo y Obras', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-building',
        'color' => '#06b6d4',
        'descripcion' => __('Planes urbanísticos, licencias y proyectos de obra', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'documentos' => array(
            array(
                'titulo' => __('Plan General de Ordenación Urbana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2023-06-15',
                'tipo' => 'PDF',
                'tamano' => '25 MB',
                'descargas' => 2340,
                'url' => '#'
            ),
            array(
                'titulo' => __('Licencias de obra concedidas Enero 2024', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => '2024-02-01',
                'tipo' => 'XLSX',
                'tamano' => '180 KB',
                'descargas' => 156,
                'url' => '#'
            )
        )
    )
);

// Tipos de archivo disponibles para filtro
$tipos_archivo_filtro = array('PDF', 'XLSX', 'DOC', 'CSV', 'ZIP');

// Años disponibles para filtro
$años_filtro = array('2024', '2023', '2022', '2021');
?>

<div class="flavor-grid-widget">
    <header class="flavor-grid-header">
        <h2 class="flavor-grid-titulo"><?php echo esc_html($titulo_grid); ?></h2>

        <?php if ($mostrar_buscador) : ?>
        <div class="flavor-grid-buscador">
            <span class="dashicons dashicons-search flavor-buscador-icono"></span>
            <input
                type="search"
                class="flavor-grid-input-busqueda"
                id="flavor-buscador-documentos"
                placeholder="<?php esc_attr_e('Buscar documentos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
            >
        </div>
        <?php endif; ?>
    </header>

    <?php if ($mostrar_filtros) : ?>
    <div class="flavor-grid-filtros">
        <div class="flavor-filtros-grupo">
            <label class="flavor-filtro-label"><?php esc_html_e('Categoría:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select class="flavor-filtro-select" id="flavor-filtro-categoria">
                <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($categorias_datos as $clave_categoria => $datos_categoria) : ?>
                <option value="<?php echo esc_attr($clave_categoria); ?>"><?php echo esc_html($datos_categoria['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flavor-filtros-grupo">
            <label class="flavor-filtro-label"><?php esc_html_e('Tipo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select class="flavor-filtro-select" id="flavor-filtro-tipo">
                <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($tipos_archivo_filtro as $tipo_archivo) : ?>
                <option value="<?php echo esc_attr($tipo_archivo); ?>"><?php echo esc_html($tipo_archivo); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flavor-filtros-grupo">
            <label class="flavor-filtro-label"><?php esc_html_e('Año:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select class="flavor-filtro-select" id="flavor-filtro-año">
                <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($años_filtro as $año) : ?>
                <option value="<?php echo esc_attr($año); ?>"><?php echo esc_html($año); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="button" class="flavor-filtros-reset" id="flavor-reset-filtros">
            <span class="dashicons dashicons-dismiss"></span>
            <?php esc_html_e('Limpiar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
    </div>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <div class="flavor-grid-estadisticas">
        <?php
        $total_documentos = 0;
        $total_descargas = 0;
        foreach ($categorias_datos as $categoria) {
            $total_documentos += count($categoria['documentos']);
            foreach ($categoria['documentos'] as $documento) {
                $total_descargas += $documento['descargas'];
            }
        }
        ?>
        <div class="flavor-estadistica-item">
            <span class="flavor-estadistica-numero"><?php echo esc_html(count($categorias_datos)); ?></span>
            <span class="flavor-estadistica-texto"><?php esc_html_e('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-estadistica-item">
            <span class="flavor-estadistica-numero"><?php echo esc_html($total_documentos); ?></span>
            <span class="flavor-estadistica-texto"><?php esc_html_e('Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-estadistica-item">
            <span class="flavor-estadistica-numero"><?php echo esc_html(number_format($total_descargas, 0, ',', '.')); ?></span>
            <span class="flavor-estadistica-texto"><?php esc_html_e('Descargas totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <!-- Grid de categorías -->
    <div class="flavor-grid-categorias" style="--flavor-columnas: <?php echo esc_attr($numero_columnas); ?>">
        <?php foreach ($categorias_datos as $clave_categoria => $datos_categoria) : ?>
        <article class="flavor-categoria-card" data-categoria="<?php echo esc_attr($clave_categoria); ?>">
            <header class="flavor-categoria-header" style="border-color: <?php echo esc_attr($datos_categoria['color']); ?>">
                <div class="flavor-categoria-icono" style="background-color: <?php echo esc_attr($datos_categoria['color']); ?>15; color: <?php echo esc_attr($datos_categoria['color']); ?>">
                    <span class="dashicons <?php echo esc_attr($datos_categoria['icono']); ?>"></span>
                </div>
                <div class="flavor-categoria-info">
                    <h3 class="flavor-categoria-nombre"><?php echo esc_html($datos_categoria['nombre']); ?></h3>
                    <p class="flavor-categoria-descripcion"><?php echo esc_html($datos_categoria['descripcion']); ?></p>
                </div>
                <span class="flavor-categoria-contador" style="background-color: <?php echo esc_attr($datos_categoria['color']); ?>">
                    <?php echo esc_html(count($datos_categoria['documentos'])); ?>
                </span>
            </header>

            <ul class="flavor-documentos-lista">
                <?php foreach ($datos_categoria['documentos'] as $documento) :
                    $icono_tipo_archivo = 'dashicons-media-default';
                    switch (strtolower($documento['tipo'])) {
                        case 'pdf':
                            $icono_tipo_archivo = 'dashicons-pdf';
                            break;
                        case 'xlsx':
                        case 'xls':
                            $icono_tipo_archivo = 'dashicons-media-spreadsheet';
                            break;
                        case 'doc':
                        case 'docx':
                            $icono_tipo_archivo = 'dashicons-media-document';
                            break;
                    }
                ?>
                <li class="flavor-documento-item" data-tipo="<?php echo esc_attr($documento['tipo']); ?>" data-fecha="<?php echo esc_attr($documento['fecha']); ?>">
                    <a href="<?php echo esc_url($documento['url']); ?>" class="flavor-documento-enlace">
                        <span class="dashicons <?php echo esc_attr($icono_tipo_archivo); ?> flavor-documento-icono"></span>
                        <div class="flavor-documento-info">
                            <span class="flavor-documento-titulo"><?php echo esc_html($documento['titulo']); ?></span>
                            <span class="flavor-documento-meta">
                                <span class="flavor-documento-fecha"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($documento['fecha']))); ?></span>
                                <span class="flavor-documento-separador">|</span>
                                <span class="flavor-documento-tamano"><?php echo esc_html($documento['tamano']); ?></span>
                                <span class="flavor-documento-separador">|</span>
                                <span class="flavor-documento-descargas">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php echo esc_html($documento['descargas']); ?>
                                </span>
                            </span>
                        </div>
                        <span class="flavor-documento-tipo-badge"><?php echo esc_html($documento['tipo']); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <footer class="flavor-categoria-footer">
                <a href="#" class="flavor-categoria-ver-todos" style="color: <?php echo esc_attr($datos_categoria['color']); ?>">
                    <?php esc_html_e('Ver todos los documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </footer>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Documentos recientes -->
    <section class="flavor-grid-recientes">
        <h3 class="flavor-recientes-titulo">
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e('Documentos recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h3>
        <div class="flavor-recientes-lista">
            <?php
            // Recopilar todos los documentos con su categoría
            $todos_documentos = array();
            foreach ($categorias_datos as $clave_categoria => $datos_categoria) {
                foreach ($datos_categoria['documentos'] as $documento) {
                    $documento['categoria'] = $datos_categoria['nombre'];
                    $documento['categoria_color'] = $datos_categoria['color'];
                    $todos_documentos[] = $documento;
                }
            }
            // Ordenar por fecha descendente
            usort($todos_documentos, function($documento_a, $documento_b) {
                return strtotime($documento_b['fecha']) - strtotime($documento_a['fecha']);
            });
            // Mostrar los 5 más recientes
            $documentos_recientes = array_slice($todos_documentos, 0, 5);
            foreach ($documentos_recientes as $documento) :
            ?>
            <div class="flavor-reciente-item">
                <a href="<?php echo esc_url($documento['url']); ?>" class="flavor-reciente-enlace">
                    <span class="flavor-reciente-titulo"><?php echo esc_html($documento['titulo']); ?></span>
                    <span class="flavor-reciente-categoria" style="background-color: <?php echo esc_attr($documento['categoria_color']); ?>15; color: <?php echo esc_attr($documento['categoria_color']); ?>">
                        <?php echo esc_html($documento['categoria']); ?>
                    </span>
                    <span class="flavor-reciente-fecha"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($documento['fecha']))); ?></span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer class="flavor-grid-footer">
        <p class="flavor-grid-nota">
            <?php esc_html_e('Todos los datos publicados se actualizan periódicamente conforme a la normativa de transparencia vigente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
        <div class="flavor-grid-enlaces">
            <a href="#" class="flavor-grid-enlace">
                <span class="dashicons dashicons-rss"></span>
                <?php esc_html_e('Suscribirse a actualizaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="#" class="flavor-grid-enlace">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Descargar catálogo completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </footer>
</div>

<style>
.flavor-grid-widget {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    padding: 24px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header */
.flavor-grid-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}

.flavor-grid-titulo {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
}

.flavor-grid-buscador {
    position: relative;
    flex: 1;
    max-width: 320px;
}

.flavor-buscador-icono {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.flavor-grid-input-busqueda {
    width: 100%;
    padding: 10px 14px 10px 40px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.flavor-grid-input-busqueda:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

/* Filtros */
.flavor-grid-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 24px;
    align-items: center;
}

.flavor-filtros-grupo {
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-filtro-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
    white-space: nowrap;
}

.flavor-filtro-select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    background: #ffffff;
    cursor: pointer;
}

.flavor-filtros-reset {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    border: none;
    background: transparent;
    color: #6b7280;
    font-size: 0.875rem;
    cursor: pointer;
    margin-left: auto;
    transition: color 0.2s;
}

.flavor-filtros-reset:hover {
    color: #1f2937;
}

.flavor-filtros-reset .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Estadísticas */
.flavor-grid-estadisticas {
    display: flex;
    gap: 24px;
    margin-bottom: 24px;
    padding: 16px 0;
    border-bottom: 1px solid #e5e7eb;
}

.flavor-estadistica-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.flavor-estadistica-numero {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.flavor-estadistica-texto {
    font-size: 0.8125rem;
    color: #6b7280;
}

/* Grid de categorías */
.flavor-grid-categorias {
    display: grid;
    grid-template-columns: repeat(var(--flavor-columnas, 3), 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.flavor-categoria-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}

.flavor-categoria-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.flavor-categoria-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    border-left: 4px solid;
    background: #f9fafb;
}

.flavor-categoria-icono {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-categoria-icono .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.flavor-categoria-info {
    flex: 1;
    min-width: 0;
}

.flavor-categoria-nombre {
    margin: 0 0 4px;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.flavor-categoria-descripcion {
    margin: 0;
    font-size: 0.8125rem;
    color: #6b7280;
    line-height: 1.4;
}

.flavor-categoria-contador {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #ffffff;
}

/* Lista de documentos */
.flavor-documentos-lista {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-documento-item {
    border-bottom: 1px solid #f3f4f6;
}

.flavor-documento-item:last-child {
    border-bottom: none;
}

.flavor-documento-enlace {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    text-decoration: none;
    transition: background-color 0.2s;
}

.flavor-documento-enlace:hover {
    background: #f9fafb;
}

.flavor-documento-icono {
    color: #6b7280;
    font-size: 20px;
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.flavor-documento-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.flavor-documento-titulo {
    font-size: 0.875rem;
    font-weight: 500;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-documento-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: #9ca3af;
}

.flavor-documento-separador {
    color: #d1d5db;
}

.flavor-documento-descargas {
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.flavor-documento-descargas .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.flavor-documento-tipo-badge {
    padding: 2px 8px;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
}

/* Footer categoría */
.flavor-categoria-footer {
    padding: 12px 16px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

.flavor-categoria-ver-todos {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
    transition: opacity 0.2s;
}

.flavor-categoria-ver-todos:hover {
    opacity: 0.8;
}

.flavor-categoria-ver-todos .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Documentos recientes */
.flavor-grid-recientes {
    background: #f9fafb;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 24px;
}

.flavor-recientes-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 16px;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.flavor-recientes-titulo .dashicons {
    color: #6b7280;
}

.flavor-recientes-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.flavor-reciente-item {
    background: #ffffff;
    border-radius: 6px;
    transition: box-shadow 0.2s;
}

.flavor-reciente-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.flavor-reciente-enlace {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    text-decoration: none;
}

.flavor-reciente-titulo {
    flex: 1;
    font-size: 0.875rem;
    font-weight: 500;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-reciente-categoria {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.flavor-reciente-fecha {
    font-size: 0.8125rem;
    color: #9ca3af;
    white-space: nowrap;
}

/* Footer */
.flavor-grid-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.flavor-grid-nota {
    margin: 0;
    font-size: 0.8125rem;
    color: #6b7280;
}

.flavor-grid-enlaces {
    display: flex;
    gap: 16px;
}

.flavor-grid-enlace {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #2563eb;
    text-decoration: none;
    transition: color 0.2s;
}

.flavor-grid-enlace:hover {
    color: #1d4ed8;
}

.flavor-grid-enlace .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Responsive */
@media (max-width: 1024px) {
    .flavor-grid-categorias {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-grid-widget {
        padding: 16px;
    }

    .flavor-grid-header {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-grid-buscador {
        max-width: none;
    }

    .flavor-grid-categorias {
        grid-template-columns: 1fr;
    }

    .flavor-grid-filtros {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-filtros-grupo {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-filtros-reset {
        margin-left: 0;
        justify-content: center;
    }

    .flavor-grid-estadisticas {
        flex-wrap: wrap;
    }

    .flavor-reciente-enlace {
        flex-wrap: wrap;
    }

    .flavor-reciente-titulo {
        width: 100%;
    }

    .flavor-grid-footer {
        flex-direction: column;
        text-align: center;
    }

    .flavor-grid-enlaces {
        flex-direction: column;
        width: 100%;
    }

    .flavor-grid-enlace {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .flavor-categoria-header {
        flex-wrap: wrap;
    }

    .flavor-categoria-contador {
        margin-left: auto;
    }

    .flavor-documento-meta {
        flex-wrap: wrap;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campoBusqueda = document.getElementById('flavor-buscador-documentos');
    const selectCategoria = document.getElementById('flavor-filtro-categoria');
    const selectTipo = document.getElementById('flavor-filtro-tipo');
    const selectAño = document.getElementById('flavor-filtro-año');
    const botonReset = document.getElementById('flavor-reset-filtros');
    const tarjetasCategorias = document.querySelectorAll('.flavor-categoria-card');
    const itemsDocumentos = document.querySelectorAll('.flavor-documento-item');

    function aplicarFiltros() {
        const terminoBusqueda = campoBusqueda ? campoBusqueda.value.toLowerCase() : '';
        const categoriaSeleccionada = selectCategoria ? selectCategoria.value : '';
        const tipoSeleccionado = selectTipo ? selectTipo.value : '';
        const añoSeleccionado = selectAño ? selectAño.value : '';

        tarjetasCategorias.forEach(function(tarjeta) {
            const categoriaTarjeta = tarjeta.dataset.categoria;
            const debeMostrarCategoria = !categoriaSeleccionada || categoriaTarjeta === categoriaSeleccionada;

            if (debeMostrarCategoria) {
                tarjeta.style.display = '';

                const documentosTarjeta = tarjeta.querySelectorAll('.flavor-documento-item');
                let tieneDocumentosVisibles = false;

                documentosTarjeta.forEach(function(documento) {
                    const tituloDocumento = documento.querySelector('.flavor-documento-titulo').textContent.toLowerCase();
                    const tipoDocumento = documento.dataset.tipo;
                    const fechaDocumento = documento.dataset.fecha;
                    const añoDocumento = fechaDocumento ? fechaDocumento.substring(0, 4) : '';

                    const coincideBusqueda = !terminoBusqueda || tituloDocumento.includes(terminoBusqueda);
                    const coincideTipo = !tipoSeleccionado || tipoDocumento === tipoSeleccionado;
                    const coincideAño = !añoSeleccionado || añoDocumento === añoSeleccionado;

                    if (coincideBusqueda && coincideTipo && coincideAño) {
                        documento.style.display = '';
                        tieneDocumentosVisibles = true;
                    } else {
                        documento.style.display = 'none';
                    }
                });

                // Si la categoría no tiene documentos visibles y hay filtros activos, ocultarla
                if (!tieneDocumentosVisibles && (terminoBusqueda || tipoSeleccionado || añoSeleccionado)) {
                    tarjeta.style.display = 'none';
                }
            } else {
                tarjeta.style.display = 'none';
            }
        });
    }

    // Eventos de filtrado
    if (campoBusqueda) {
        campoBusqueda.addEventListener('input', aplicarFiltros);
    }

    if (selectCategoria) {
        selectCategoria.addEventListener('change', aplicarFiltros);
    }

    if (selectTipo) {
        selectTipo.addEventListener('change', aplicarFiltros);
    }

    if (selectAño) {
        selectAño.addEventListener('change', aplicarFiltros);
    }

    // Reset de filtros
    if (botonReset) {
        botonReset.addEventListener('click', function() {
            if (campoBusqueda) campoBusqueda.value = '';
            if (selectCategoria) selectCategoria.value = '';
            if (selectTipo) selectTipo.value = '';
            if (selectAño) selectAño.value = '';

            tarjetasCategorias.forEach(function(tarjeta) {
                tarjeta.style.display = '';
            });

            itemsDocumentos.forEach(function(documento) {
                documento.style.display = '';
            });
        });
    }
});
</script>

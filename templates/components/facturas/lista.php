<?php
/**
 * Template: Listado de Facturas del Usuario
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo = $args['titulo'] ?? 'Mis Facturas';
$subtitulo = $args['subtitulo'] ?? 'Historial de facturas y pagos';
$facturas = $args['facturas'] ?? [];
$mostrar_filtros = $args['mostrar_filtros'] ?? true;
$mostrar_busqueda = $args['mostrar_busqueda'] ?? true;
$mostrar_paginacion = $args['mostrar_paginacion'] ?? true;
$facturas_por_pagina = $args['facturas_por_pagina'] ?? 10;
$pagina_actual = $args['pagina_actual'] ?? 1;
$total_facturas = $args['total_facturas'] ?? 0;
$moneda_simbolo = $args['moneda_simbolo'] ?? '€';

// Datos de demostracion si no hay facturas reales
if (empty($facturas)) {
    $facturas = [
        [
            'id' => 'FAC-2024-001',
            'fecha' => '2024-01-15',
            'fecha_vencimiento' => '2024-02-15',
            'concepto' => 'Suscripcion mensual - Plan Premium',
            'importe' => 49.99,
            'estado' => 'pagada',
            'metodo_pago' => 'Tarjeta de credito',
            'enlace_descarga' => '#'
        ],
        [
            'id' => 'FAC-2024-002',
            'fecha' => '2024-02-15',
            'fecha_vencimiento' => '2024-03-15',
            'concepto' => 'Suscripcion mensual - Plan Premium',
            'importe' => 49.99,
            'estado' => 'pagada',
            'metodo_pago' => 'Tarjeta de credito',
            'enlace_descarga' => '#'
        ],
        [
            'id' => 'FAC-2024-003',
            'fecha' => '2024-03-15',
            'fecha_vencimiento' => '2024-04-15',
            'concepto' => 'Servicio adicional - Soporte prioritario',
            'importe' => 29.99,
            'estado' => 'pendiente',
            'metodo_pago' => '',
            'enlace_descarga' => '#'
        ],
        [
            'id' => 'FAC-2024-004',
            'fecha' => '2024-03-20',
            'fecha_vencimiento' => '2024-04-20',
            'concepto' => 'Suscripcion mensual - Plan Premium',
            'importe' => 49.99,
            'estado' => 'vencida',
            'metodo_pago' => '',
            'enlace_descarga' => '#'
        ],
        [
            'id' => 'FAC-2024-005',
            'fecha' => '2024-04-01',
            'fecha_vencimiento' => '2024-05-01',
            'concepto' => 'Creditos adicionales x100',
            'importe' => 19.99,
            'estado' => 'borrador',
            'metodo_pago' => '',
            'enlace_descarga' => ''
        ],
    ];
    $total_facturas = count($facturas);
}

// Calcular totales para el resumen
$total_pagado = 0;
$total_pendiente = 0;
$total_vencido = 0;

foreach ($facturas as $factura) {
    $estado = $factura['estado'] ?? 'pendiente';
    $importe = floatval($factura['importe'] ?? 0);

    switch ($estado) {
        case 'pagada':
            $total_pagado += $importe;
            break;
        case 'pendiente':
            $total_pendiente += $importe;
            break;
        case 'vencida':
            $total_vencido += $importe;
            break;
    }
}

// Funcion auxiliar para obtener clase y texto del estado
function flavor_obtener_estado_factura($estado) {
    $estados = [
        'pagada' => ['clase' => 'flavor-estado--pagada', 'texto' => 'Pagada', 'icono' => 'dashicons-yes-alt'],
        'pendiente' => ['clase' => 'flavor-estado--pendiente', 'texto' => 'Pendiente', 'icono' => 'dashicons-clock'],
        'vencida' => ['clase' => 'flavor-estado--vencida', 'texto' => 'Vencida', 'icono' => 'dashicons-warning'],
        'borrador' => ['clase' => 'flavor-estado--borrador', 'texto' => 'Borrador', 'icono' => 'dashicons-edit'],
        'cancelada' => ['clase' => 'flavor-estado--cancelada', 'texto' => 'Cancelada', 'icono' => 'dashicons-dismiss'],
    ];

    return $estados[$estado] ?? $estados['pendiente'];
}

// Funcion auxiliar para formatear fecha
function flavor_formatear_fecha($fecha) {
    if (empty($fecha)) return '-';
    $timestamp = strtotime($fecha);
    return date_i18n('d M Y', $timestamp);
}
?>

<section class="flavor-facturas-lista">
    <div class="flavor-facturas-header">
        <div class="flavor-facturas-header-texto">
            <?php if ($titulo) : ?>
                <h2 class="flavor-facturas-titulo"><?php echo esc_html($titulo); ?></h2>
            <?php endif; ?>

            <?php if ($subtitulo) : ?>
                <p class="flavor-facturas-subtitulo"><?php echo esc_html($subtitulo); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen de totales -->
    <div class="flavor-facturas-resumen">
        <div class="flavor-resumen-card flavor-resumen-card--pagado">
            <span class="flavor-resumen-icono dashicons dashicons-yes-alt"></span>
            <div class="flavor-resumen-info">
                <span class="flavor-resumen-etiqueta">Total Pagado</span>
                <span class="flavor-resumen-valor"><?php echo esc_html($moneda_simbolo . number_format($total_pagado, 2, ',', '.')); ?></span>
            </div>
        </div>
        <div class="flavor-resumen-card flavor-resumen-card--pendiente">
            <span class="flavor-resumen-icono dashicons dashicons-clock"></span>
            <div class="flavor-resumen-info">
                <span class="flavor-resumen-etiqueta">Pendiente</span>
                <span class="flavor-resumen-valor"><?php echo esc_html($moneda_simbolo . number_format($total_pendiente, 2, ',', '.')); ?></span>
            </div>
        </div>
        <div class="flavor-resumen-card flavor-resumen-card--vencido">
            <span class="flavor-resumen-icono dashicons dashicons-warning"></span>
            <div class="flavor-resumen-info">
                <span class="flavor-resumen-etiqueta">Vencido</span>
                <span class="flavor-resumen-valor"><?php echo esc_html($moneda_simbolo . number_format($total_vencido, 2, ',', '.')); ?></span>
            </div>
        </div>
    </div>

    <?php if ($mostrar_filtros || $mostrar_busqueda) : ?>
    <div class="flavor-facturas-controles">
        <?php if ($mostrar_busqueda) : ?>
        <div class="flavor-facturas-busqueda">
            <span class="dashicons dashicons-search flavor-busqueda-icono"></span>
            <input type="text"
                   class="flavor-busqueda-input"
                   placeholder="Buscar por numero o concepto..."
                   id="flavor-facturas-busqueda">
        </div>
        <?php endif; ?>

        <?php if ($mostrar_filtros) : ?>
        <div class="flavor-facturas-filtros">
            <select class="flavor-filtro-select" id="flavor-filtro-estado">
                <option value="">Todos los estados</option>
                <option value="pagada">Pagadas</option>
                <option value="pendiente">Pendientes</option>
                <option value="vencida">Vencidas</option>
                <option value="borrador">Borradores</option>
            </select>

            <select class="flavor-filtro-select" id="flavor-filtro-periodo">
                <option value="">Todo el periodo</option>
                <option value="mes">Este mes</option>
                <option value="trimestre">Ultimo trimestre</option>
                <option value="ano">Este ano</option>
            </select>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($facturas)) : ?>
    <!-- Vista de tabla para desktop -->
    <div class="flavor-facturas-tabla-wrapper">
        <table class="flavor-facturas-tabla">
            <thead>
                <tr>
                    <th class="flavor-th-numero">Numero</th>
                    <th class="flavor-th-fecha">Fecha</th>
                    <th class="flavor-th-concepto">Concepto</th>
                    <th class="flavor-th-importe">Importe</th>
                    <th class="flavor-th-estado">Estado</th>
                    <th class="flavor-th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($facturas as $factura) :
                    $factura_id = $factura['id'] ?? '-';
                    $factura_fecha = $factura['fecha'] ?? '';
                    $factura_vencimiento = $factura['fecha_vencimiento'] ?? '';
                    $factura_concepto = $factura['concepto'] ?? 'Sin concepto';
                    $factura_importe = floatval($factura['importe'] ?? 0);
                    $factura_estado = $factura['estado'] ?? 'pendiente';
                    $factura_enlace_descarga = $factura['enlace_descarga'] ?? '';

                    $estado_info = flavor_obtener_estado_factura($factura_estado);
                ?>
                <tr class="flavor-factura-fila" data-estado="<?php echo esc_attr($factura_estado); ?>">
                    <td class="flavor-td-numero">
                        <span class="flavor-factura-id"><?php echo esc_html($factura_id); ?></span>
                    </td>
                    <td class="flavor-td-fecha">
                        <span class="flavor-fecha-emision"><?php echo esc_html(flavor_formatear_fecha($factura_fecha)); ?></span>
                        <?php if ($factura_vencimiento && $factura_estado !== 'pagada') : ?>
                        <span class="flavor-fecha-vencimiento">Vence: <?php echo esc_html(flavor_formatear_fecha($factura_vencimiento)); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="flavor-td-concepto">
                        <span class="flavor-factura-concepto"><?php echo esc_html($factura_concepto); ?></span>
                    </td>
                    <td class="flavor-td-importe">
                        <span class="flavor-factura-importe"><?php echo esc_html($moneda_simbolo . number_format($factura_importe, 2, ',', '.')); ?></span>
                    </td>
                    <td class="flavor-td-estado">
                        <span class="flavor-estado-badge <?php echo esc_attr($estado_info['clase']); ?>">
                            <span class="dashicons <?php echo esc_attr($estado_info['icono']); ?>"></span>
                            <?php echo esc_html($estado_info['texto']); ?>
                        </span>
                    </td>
                    <td class="flavor-td-acciones">
                        <div class="flavor-acciones-grupo">
                            <?php if ($factura_enlace_descarga) : ?>
                            <a href="<?php echo esc_url($factura_enlace_descarga); ?>"
                               class="flavor-btn-accion flavor-btn-accion--descargar"
                               title="Descargar PDF"
                               download>
                                <span class="dashicons dashicons-pdf"></span>
                            </a>
                            <?php endif; ?>

                            <?php if ($factura_estado === 'pendiente' || $factura_estado === 'vencida') : ?>
                            <a href="#"
                               class="flavor-btn-accion flavor-btn-accion--pagar"
                               title="Pagar ahora"
                               data-factura-id="<?php echo esc_attr($factura_id); ?>">
                                <span class="dashicons dashicons-money-alt"></span>
                            </a>
                            <?php endif; ?>

                            <a href="#"
                               class="flavor-btn-accion flavor-btn-accion--ver"
                               title="Ver detalles"
                               data-factura-id="<?php echo esc_attr($factura_id); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Vista de tarjetas para movil -->
    <div class="flavor-facturas-cards">
        <?php foreach ($facturas as $factura) :
            $factura_id = $factura['id'] ?? '-';
            $factura_fecha = $factura['fecha'] ?? '';
            $factura_vencimiento = $factura['fecha_vencimiento'] ?? '';
            $factura_concepto = $factura['concepto'] ?? 'Sin concepto';
            $factura_importe = floatval($factura['importe'] ?? 0);
            $factura_estado = $factura['estado'] ?? 'pendiente';
            $factura_enlace_descarga = $factura['enlace_descarga'] ?? '';

            $estado_info = flavor_obtener_estado_factura($factura_estado);
        ?>
        <div class="flavor-factura-card" data-estado="<?php echo esc_attr($factura_estado); ?>">
            <div class="flavor-factura-card-header">
                <span class="flavor-factura-card-id"><?php echo esc_html($factura_id); ?></span>
                <span class="flavor-estado-badge <?php echo esc_attr($estado_info['clase']); ?>">
                    <span class="dashicons <?php echo esc_attr($estado_info['icono']); ?>"></span>
                    <?php echo esc_html($estado_info['texto']); ?>
                </span>
            </div>

            <div class="flavor-factura-card-body">
                <p class="flavor-factura-card-concepto"><?php echo esc_html($factura_concepto); ?></p>
                <div class="flavor-factura-card-meta">
                    <span class="flavor-factura-card-fecha">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo esc_html(flavor_formatear_fecha($factura_fecha)); ?>
                    </span>
                    <span class="flavor-factura-card-importe"><?php echo esc_html($moneda_simbolo . number_format($factura_importe, 2, ',', '.')); ?></span>
                </div>
            </div>

            <div class="flavor-factura-card-footer">
                <?php if ($factura_enlace_descarga) : ?>
                <a href="<?php echo esc_url($factura_enlace_descarga); ?>"
                   class="flavor-btn flavor-btn--sm flavor-btn--outline"
                   download>
                    <span class="dashicons dashicons-pdf"></span>
                    Descargar
                </a>
                <?php endif; ?>

                <?php if ($factura_estado === 'pendiente' || $factura_estado === 'vencida') : ?>
                <a href="#"
                   class="flavor-btn flavor-btn--sm flavor-btn--primary"
                   data-factura-id="<?php echo esc_attr($factura_id); ?>">
                    <span class="dashicons dashicons-money-alt"></span>
                    Pagar
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($mostrar_paginacion && $total_facturas > $facturas_por_pagina) :
        $total_paginas = ceil($total_facturas / $facturas_por_pagina);
    ?>
    <div class="flavor-facturas-paginacion">
        <span class="flavor-paginacion-info">
            Mostrando <?php echo esc_html((($pagina_actual - 1) * $facturas_por_pagina) + 1); ?> -
            <?php echo esc_html(min($pagina_actual * $facturas_por_pagina, $total_facturas)); ?>
            de <?php echo esc_html($total_facturas); ?> facturas
        </span>

        <div class="flavor-paginacion-controles">
            <?php if ($pagina_actual > 1) : ?>
            <a href="#" class="flavor-paginacion-btn" data-pagina="<?php echo esc_attr($pagina_actual - 1); ?>">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </a>
            <?php endif; ?>

            <?php for ($pagina_numero = 1; $pagina_numero <= $total_paginas; $pagina_numero++) : ?>
            <a href="#"
               class="flavor-paginacion-btn <?php echo $pagina_numero === $pagina_actual ? 'flavor-paginacion-btn--activo' : ''; ?>"
               data-pagina="<?php echo esc_attr($pagina_numero); ?>">
                <?php echo esc_html($pagina_numero); ?>
            </a>
            <?php endfor; ?>

            <?php if ($pagina_actual < $total_paginas) : ?>
            <a href="#" class="flavor-paginacion-btn" data-pagina="<?php echo esc_attr($pagina_actual + 1); ?>">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php else : ?>
    <div class="flavor-facturas-vacio">
        <span class="dashicons dashicons-media-document flavor-vacio-icono"></span>
        <h3 class="flavor-vacio-titulo">No hay facturas</h3>
        <p class="flavor-vacio-texto">Aun no tienes ninguna factura registrada en tu cuenta.</p>
    </div>
    <?php endif; ?>
</section>

<style>
.flavor-facturas-lista {
    padding: 2rem 1rem;
    max-width: 1200px;
    margin: 0 auto;
}

.flavor-facturas-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.flavor-facturas-titulo {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 0.5rem 0;
}

.flavor-facturas-subtitulo {
    font-size: 1rem;
    color: #6c757d;
    margin: 0;
}

/* Resumen de totales */
.flavor-facturas-resumen {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.flavor-resumen-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border-left: 4px solid;
}

.flavor-resumen-card--pagado {
    border-left-color: #27ae60;
}

.flavor-resumen-card--pendiente {
    border-left-color: #f39c12;
}

.flavor-resumen-card--vencido {
    border-left-color: #e74c3c;
}

.flavor-resumen-icono {
    font-size: 32px;
    opacity: 0.7;
}

.flavor-resumen-card--pagado .flavor-resumen-icono {
    color: #27ae60;
}

.flavor-resumen-card--pendiente .flavor-resumen-icono {
    color: #f39c12;
}

.flavor-resumen-card--vencido .flavor-resumen-icono {
    color: #e74c3c;
}

.flavor-resumen-info {
    display: flex;
    flex-direction: column;
}

.flavor-resumen-etiqueta {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.flavor-resumen-valor {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a2e;
}

/* Controles */
.flavor-facturas-controles {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.flavor-facturas-busqueda {
    position: relative;
    flex: 1;
    max-width: 320px;
}

.flavor-busqueda-icono {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 18px;
}

.flavor-busqueda-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    font-size: 0.9375rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    transition: all 0.2s ease;
}

.flavor-busqueda-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.flavor-facturas-filtros {
    display: flex;
    gap: 0.75rem;
}

.flavor-filtro-select {
    padding: 0.75rem 2.5rem 0.75rem 1rem;
    font-size: 0.9375rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E") right 0.75rem center/1.25em no-repeat;
    cursor: pointer;
    appearance: none;
}

.flavor-filtro-select:focus {
    outline: none;
    border-color: #3b82f6;
}

/* Tabla */
.flavor-facturas-tabla-wrapper {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.flavor-facturas-tabla {
    width: 100%;
    border-collapse: collapse;
}

.flavor-facturas-tabla th {
    padding: 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6c757d;
    background: #f8f9fa;
    border-bottom: 1px solid #e5e7eb;
}

.flavor-facturas-tabla td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.flavor-factura-fila:last-child td {
    border-bottom: none;
}

.flavor-factura-fila:hover {
    background: #f9fafb;
}

.flavor-factura-id {
    font-weight: 600;
    color: #3b82f6;
}

.flavor-fecha-emision {
    display: block;
    color: #1a1a2e;
}

.flavor-fecha-vencimiento {
    display: block;
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.25rem;
}

.flavor-factura-concepto {
    color: #1a1a2e;
}

.flavor-factura-importe {
    font-weight: 600;
    color: #1a1a2e;
}

/* Estados */
.flavor-estado-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 20px;
}

.flavor-estado-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-estado--pagada {
    color: #166534;
    background: #dcfce7;
}

.flavor-estado--pendiente {
    color: #92400e;
    background: #fef3c7;
}

.flavor-estado--vencida {
    color: #991b1b;
    background: #fee2e2;
}

.flavor-estado--borrador {
    color: #4b5563;
    background: #f3f4f6;
}

.flavor-estado--cancelada {
    color: #6b7280;
    background: #e5e7eb;
}

/* Acciones */
.flavor-acciones-grupo {
    display: flex;
    gap: 0.5rem;
}

.flavor-btn-accion {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    color: #6c757d;
    background: #f3f4f6;
    text-decoration: none;
    transition: all 0.2s ease;
}

.flavor-btn-accion:hover {
    background: #e5e7eb;
}

.flavor-btn-accion--descargar:hover {
    color: #dc2626;
    background: #fee2e2;
}

.flavor-btn-accion--pagar:hover {
    color: #16a34a;
    background: #dcfce7;
}

.flavor-btn-accion--ver:hover {
    color: #2563eb;
    background: #dbeafe;
}

.flavor-btn-accion .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Tarjetas movil */
.flavor-facturas-cards {
    display: none;
}

.flavor-factura-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    margin-bottom: 1rem;
    overflow: hidden;
}

.flavor-factura-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e5e7eb;
}

.flavor-factura-card-id {
    font-weight: 600;
    color: #3b82f6;
}

.flavor-factura-card-body {
    padding: 1rem;
}

.flavor-factura-card-concepto {
    margin: 0 0 0.75rem 0;
    color: #1a1a2e;
    font-size: 0.9375rem;
}

.flavor-factura-card-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-factura-card-fecha {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.flavor-factura-card-fecha .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.flavor-factura-card-importe {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1a1a2e;
}

.flavor-factura-card-footer {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    border-top: 1px solid #f3f4f6;
}

/* Botones */
.flavor-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    font-size: 0.9375rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.flavor-btn--sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.flavor-btn--sm .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.flavor-btn--primary {
    color: #ffffff;
    background: #3b82f6;
}

.flavor-btn--primary:hover {
    background: #2563eb;
}

.flavor-btn--outline {
    color: #374151;
    background: transparent;
    border: 1px solid #d1d5db;
}

.flavor-btn--outline:hover {
    background: #f3f4f6;
}

/* Paginacion */
.flavor-facturas-paginacion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.flavor-paginacion-info {
    font-size: 0.875rem;
    color: #6c757d;
}

.flavor-paginacion-controles {
    display: flex;
    gap: 0.25rem;
}

.flavor-paginacion-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.5rem;
    font-size: 0.875rem;
    color: #374151;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.flavor-paginacion-btn:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.flavor-paginacion-btn--activo {
    color: #ffffff;
    background: #3b82f6;
    border-color: #3b82f6;
}

.flavor-paginacion-btn--activo:hover {
    background: #2563eb;
    border-color: #2563eb;
}

/* Estado vacio */
.flavor-facturas-vacio {
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.flavor-vacio-icono {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.flavor-vacio-titulo {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0 0 0.5rem 0;
}

.flavor-vacio-texto {
    color: #6c757d;
    margin: 0;
}

/* Responsive */
@media (max-width: 992px) {
    .flavor-facturas-resumen {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .flavor-facturas-lista {
        padding: 1.5rem 1rem;
    }

    .flavor-facturas-tabla-wrapper {
        display: none;
    }

    .flavor-facturas-cards {
        display: block;
    }

    .flavor-facturas-controles {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-facturas-busqueda {
        max-width: none;
    }

    .flavor-facturas-filtros {
        flex-direction: column;
    }

    .flavor-facturas-paginacion {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .flavor-facturas-titulo {
        font-size: 1.5rem;
    }

    .flavor-factura-card-footer {
        flex-direction: column;
    }

    .flavor-factura-card-footer .flavor-btn {
        justify-content: center;
    }
}
</style>

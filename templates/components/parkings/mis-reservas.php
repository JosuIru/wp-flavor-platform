<?php
/**
 * Template: Lista de reservas del usuario
 *
 * @package Flavor_Platform
 * @subpackage Templates/Components/Parkings
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo = isset($args['titulo']) ? $args['titulo'] : 'Mis Reservas de Parking';
$mostrar_filtros = isset($args['mostrar_filtros']) ? $args['mostrar_filtros'] : true;
$filtro_activo = isset($args['filtro_activo']) ? $args['filtro_activo'] : 'todas';
$usuario_nombre = isset($args['usuario_nombre']) ? $args['usuario_nombre'] : 'Juan García';

// Datos de demostración de reservas
$reservas_demo = isset($args['reservas']) ? $args['reservas'] : array(
    array(
        'id' => 'RES-2024-001',
        'parking_nombre' => 'Parking Residencial Las Flores',
        'parking_direccion' => 'Calle Mayor 45, Madrid',
        'plaza_numero' => 'A-15',
        'planta' => '-1',
        'fecha_inicio' => '2024-01-20 08:00:00',
        'fecha_fin' => '2024-01-20 18:00:00',
        'tipo_reserva' => 'dia',
        'estado' => 'activa',
        'vehiculo' => array(
            'matricula' => '1234 ABC',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Gris'
        ),
        'precio_total' => 15.00,
        'metodo_pago' => 'Tarjeta **** 4521',
        'codigo_acceso' => 'PLF-7829',
        'puede_cancelar' => true,
        'puede_extender' => true
    ),
    array(
        'id' => 'RES-2024-002',
        'parking_nombre' => 'Parking Vecinal Centro',
        'parking_direccion' => 'Calle Gran Vía 78, Madrid',
        'plaza_numero' => 'B-08',
        'planta' => '-2',
        'fecha_inicio' => '2024-01-22 10:00:00',
        'fecha_fin' => '2024-01-22 14:00:00',
        'tipo_reserva' => 'horas',
        'estado' => 'pendiente',
        'vehiculo' => array(
            'matricula' => '1234 ABC',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Gris'
        ),
        'precio_total' => 7.20,
        'metodo_pago' => 'Pendiente de pago',
        'codigo_acceso' => null,
        'puede_cancelar' => true,
        'puede_extender' => false
    ),
    array(
        'id' => 'RES-2024-003',
        'parking_nombre' => 'Parking Comunidad Verde',
        'parking_direccion' => 'Calle del Prado 56, Madrid',
        'plaza_numero' => 'C-22',
        'planta' => '0',
        'fecha_inicio' => '2024-02-01 00:00:00',
        'fecha_fin' => '2024-02-28 23:59:00',
        'tipo_reserva' => 'mensual',
        'estado' => 'activa',
        'vehiculo' => array(
            'matricula' => '5678 XYZ',
            'marca' => 'Volkswagen',
            'modelo' => 'Golf',
            'color' => 'Azul'
        ),
        'precio_total' => 110.00,
        'metodo_pago' => 'Domiciliación bancaria',
        'codigo_acceso' => 'PVE-3456',
        'puede_cancelar' => false,
        'puede_extender' => true
    ),
    array(
        'id' => 'RES-2023-089',
        'parking_nombre' => 'Parking Sol',
        'parking_direccion' => 'Plaza del Sol 10, Madrid',
        'plaza_numero' => 'D-03',
        'planta' => '-1',
        'fecha_inicio' => '2024-01-10 09:00:00',
        'fecha_fin' => '2024-01-10 19:00:00',
        'tipo_reserva' => 'dia',
        'estado' => 'completada',
        'vehiculo' => array(
            'matricula' => '1234 ABC',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Gris'
        ),
        'precio_total' => 18.00,
        'metodo_pago' => 'Tarjeta **** 4521',
        'codigo_acceso' => null,
        'puede_cancelar' => false,
        'puede_extender' => false,
        'valorada' => true,
        'valoracion' => 4
    ),
    array(
        'id' => 'RES-2023-078',
        'parking_nombre' => 'Parking Barrio Norte',
        'parking_direccion' => 'Avenida de la Paz 23, Madrid',
        'plaza_numero' => 'E-11',
        'planta' => '0',
        'fecha_inicio' => '2024-01-05 08:00:00',
        'fecha_fin' => '2024-01-05 20:00:00',
        'tipo_reserva' => 'dia',
        'estado' => 'cancelada',
        'motivo_cancelacion' => 'Cancelada por el usuario',
        'fecha_cancelacion' => '2024-01-04 15:30:00',
        'vehiculo' => array(
            'matricula' => '1234 ABC',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Gris'
        ),
        'precio_total' => 12.00,
        'reembolso' => 12.00,
        'metodo_pago' => 'Tarjeta **** 4521',
        'codigo_acceso' => null,
        'puede_cancelar' => false,
        'puede_extender' => false
    ),
    array(
        'id' => 'RES-2023-065',
        'parking_nombre' => 'Parking Plaza Mayor',
        'parking_direccion' => 'Plaza Mayor 2, Madrid',
        'plaza_numero' => 'F-07',
        'planta' => '-3',
        'fecha_inicio' => '2023-12-28 10:00:00',
        'fecha_fin' => '2023-12-28 22:00:00',
        'tipo_reserva' => 'dia',
        'estado' => 'completada',
        'vehiculo' => array(
            'matricula' => '5678 XYZ',
            'marca' => 'Volkswagen',
            'modelo' => 'Golf',
            'color' => 'Azul'
        ),
        'precio_total' => 22.00,
        'metodo_pago' => 'Tarjeta **** 4521',
        'codigo_acceso' => null,
        'puede_cancelar' => false,
        'puede_extender' => false,
        'valorada' => false
    )
);

// Contar reservas por estado
$contadores_estado = array(
    'todas' => count($reservas_demo),
    'activa' => 0,
    'pendiente' => 0,
    'completada' => 0,
    'cancelada' => 0
);

foreach ($reservas_demo as $reserva) {
    if (isset($contadores_estado[$reserva['estado']])) {
        $contadores_estado[$reserva['estado']]++;
    }
}

// Estados y sus propiedades
$estados_config = array(
    'activa' => array(
        'label' => 'Activa',
        'color' => '#22c55e',
        'bg' => '#dcfce7',
        'icono' => '&#10003;'
    ),
    'pendiente' => array(
        'label' => 'Pendiente',
        'color' => '#f59e0b',
        'bg' => '#fef3c7',
        'icono' => '&#8987;'
    ),
    'completada' => array(
        'label' => 'Completada',
        'color' => '#6366f1',
        'bg' => '#e0e7ff',
        'icono' => '&#10004;'
    ),
    'cancelada' => array(
        'label' => 'Cancelada',
        'color' => '#ef4444',
        'bg' => '#fef2f2',
        'icono' => '&#10006;'
    )
);

// Tipos de reserva
$tipos_reserva = array(
    'horas' => 'Por horas',
    'dia' => 'Día completo',
    'mensual' => 'Mensual'
);
?>

<div class="flavor-mis-reservas-container">
    <div class="flavor-mis-reservas-header">
        <div class="flavor-mis-reservas-titulo-wrapper">
            <h2 class="flavor-mis-reservas-titulo"><?php echo esc_html($titulo); ?></h2>
            <span class="flavor-mis-reservas-usuario">
                <span class="flavor-mis-reservas-avatar"><?php echo esc_html(substr($usuario_nombre, 0, 1)); ?></span>
                <?php echo esc_html($usuario_nombre); ?>
            </span>
        </div>
        <button type="button" class="flavor-mis-reservas-btn-nueva">
            <span>+</span> Nueva reserva
        </button>
    </div>

    <?php if ($mostrar_filtros) : ?>
        <div class="flavor-mis-reservas-filtros">
            <div class="flavor-mis-reservas-tabs">
                <button type="button" class="flavor-mis-reservas-tab <?php echo $filtro_activo === 'todas' ? 'active' : ''; ?>" data-filtro="todas">
                    Todas <span class="flavor-mis-reservas-tab-count"><?php echo esc_html($contadores_estado['todas']); ?></span>
                </button>
                <button type="button" class="flavor-mis-reservas-tab <?php echo $filtro_activo === 'activa' ? 'active' : ''; ?>" data-filtro="activa">
                    Activas <span class="flavor-mis-reservas-tab-count"><?php echo esc_html($contadores_estado['activa']); ?></span>
                </button>
                <button type="button" class="flavor-mis-reservas-tab <?php echo $filtro_activo === 'pendiente' ? 'active' : ''; ?>" data-filtro="pendiente">
                    Pendientes <span class="flavor-mis-reservas-tab-count"><?php echo esc_html($contadores_estado['pendiente']); ?></span>
                </button>
                <button type="button" class="flavor-mis-reservas-tab <?php echo $filtro_activo === 'completada' ? 'active' : ''; ?>" data-filtro="completada">
                    Historial <span class="flavor-mis-reservas-tab-count"><?php echo esc_html($contadores_estado['completada']); ?></span>
                </button>
                <button type="button" class="flavor-mis-reservas-tab <?php echo $filtro_activo === 'cancelada' ? 'active' : ''; ?>" data-filtro="cancelada">
                    Canceladas <span class="flavor-mis-reservas-tab-count"><?php echo esc_html($contadores_estado['cancelada']); ?></span>
                </button>
            </div>

            <div class="flavor-mis-reservas-buscar">
                <span class="flavor-mis-reservas-buscar-icon">&#128269;</span>
                <input type="text" placeholder="Buscar por ID, parking o matrícula..." class="flavor-mis-reservas-buscar-input">
            </div>
        </div>
    <?php endif; ?>

    <div class="flavor-mis-reservas-lista">
        <?php if (empty($reservas_demo)) : ?>
            <div class="flavor-mis-reservas-vacio">
                <span class="flavor-mis-reservas-vacio-icon">&#128663;</span>
                <h3>No tienes reservas</h3>
                <p>Cuando realices una reserva, aparecerá aquí.</p>
                <button type="button" class="flavor-mis-reservas-btn-buscar">Buscar parking</button>
            </div>
        <?php else : ?>
            <?php foreach ($reservas_demo as $reserva) :
                $estado_config = $estados_config[$reserva['estado']];
                $fecha_inicio = strtotime($reserva['fecha_inicio']);
                $fecha_fin = strtotime($reserva['fecha_fin']);
                $es_hoy = date('Y-m-d', $fecha_inicio) === date('Y-m-d');
                $es_pasada = $fecha_fin < time();
            ?>
                <article class="flavor-mis-reservas-card flavor-mis-reservas-estado-<?php echo esc_attr($reserva['estado']); ?>">
                    <div class="flavor-mis-reservas-card-header">
                        <div class="flavor-mis-reservas-card-id-estado">
                            <span class="flavor-mis-reservas-card-id"><?php echo esc_html($reserva['id']); ?></span>
                            <span class="flavor-mis-reservas-estado-badge" style="background: <?php echo esc_attr($estado_config['bg']); ?>; color: <?php echo esc_attr($estado_config['color']); ?>;">
                                <span><?php echo $estado_config['icono']; ?></span>
                                <?php echo esc_html($estado_config['label']); ?>
                            </span>
                            <?php if ($es_hoy && $reserva['estado'] === 'activa') : ?>
                                <span class="flavor-mis-reservas-badge-hoy">HOY</span>
                            <?php endif; ?>
                        </div>
                        <span class="flavor-mis-reservas-tipo-badge"><?php echo esc_html($tipos_reserva[$reserva['tipo_reserva']] ?? $reserva['tipo_reserva']); ?></span>
                    </div>

                    <div class="flavor-mis-reservas-card-body">
                        <div class="flavor-mis-reservas-parking-info">
                            <h3 class="flavor-mis-reservas-parking-nombre"><?php echo esc_html($reserva['parking_nombre']); ?></h3>
                            <p class="flavor-mis-reservas-parking-direccion">
                                <span class="flavor-mis-reservas-icon">&#128205;</span>
                                <?php echo esc_html($reserva['parking_direccion']); ?>
                            </p>
                        </div>

                        <div class="flavor-mis-reservas-detalles-grid">
                            <div class="flavor-mis-reservas-detalle">
                                <span class="flavor-mis-reservas-detalle-label">Plaza</span>
                                <span class="flavor-mis-reservas-detalle-valor flavor-mis-reservas-plaza">
                                    <?php echo esc_html($reserva['plaza_numero']); ?>
                                    <small>(Planta <?php echo esc_html($reserva['planta']); ?>)</small>
                                </span>
                            </div>

                            <div class="flavor-mis-reservas-detalle">
                                <span class="flavor-mis-reservas-detalle-label">Fecha entrada</span>
                                <span class="flavor-mis-reservas-detalle-valor">
                                    <?php echo date('d/m/Y', $fecha_inicio); ?>
                                    <small><?php echo date('H:i', $fecha_inicio); ?>h</small>
                                </span>
                            </div>

                            <div class="flavor-mis-reservas-detalle">
                                <span class="flavor-mis-reservas-detalle-label">Fecha salida</span>
                                <span class="flavor-mis-reservas-detalle-valor">
                                    <?php echo date('d/m/Y', $fecha_fin); ?>
                                    <small><?php echo date('H:i', $fecha_fin); ?>h</small>
                                </span>
                            </div>

                            <div class="flavor-mis-reservas-detalle">
                                <span class="flavor-mis-reservas-detalle-label">Importe</span>
                                <span class="flavor-mis-reservas-detalle-valor flavor-mis-reservas-precio">
                                    <?php echo number_format($reserva['precio_total'], 2); ?>€
                                </span>
                            </div>
                        </div>

                        <div class="flavor-mis-reservas-vehiculo">
                            <span class="flavor-mis-reservas-vehiculo-icon">&#128663;</span>
                            <div class="flavor-mis-reservas-vehiculo-info">
                                <span class="flavor-mis-reservas-matricula"><?php echo esc_html($reserva['vehiculo']['matricula']); ?></span>
                                <span class="flavor-mis-reservas-vehiculo-modelo">
                                    <?php echo esc_html($reserva['vehiculo']['marca'] . ' ' . $reserva['vehiculo']['modelo']); ?> - <?php echo esc_html($reserva['vehiculo']['color']); ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($reserva['codigo_acceso']) : ?>
                            <div class="flavor-mis-reservas-codigo-acceso">
                                <span class="flavor-mis-reservas-codigo-label">Código de acceso</span>
                                <div class="flavor-mis-reservas-codigo-valor">
                                    <span class="flavor-mis-reservas-codigo"><?php echo esc_html($reserva['codigo_acceso']); ?></span>
                                    <button type="button" class="flavor-mis-reservas-btn-copiar" title="Copiar código">
                                        &#128203;
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($reserva['estado'] === 'cancelada' && isset($reserva['motivo_cancelacion'])) : ?>
                            <div class="flavor-mis-reservas-cancelacion-info">
                                <p class="flavor-mis-reservas-motivo">
                                    <strong>Motivo:</strong> <?php echo esc_html($reserva['motivo_cancelacion']); ?>
                                </p>
                                <?php if (isset($reserva['reembolso'])) : ?>
                                    <p class="flavor-mis-reservas-reembolso">
                                        <span class="flavor-mis-reservas-icon-check">&#10003;</span>
                                        Reembolso: <?php echo number_format($reserva['reembolso'], 2); ?>€
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($reserva['estado'] === 'completada' && !($reserva['valorada'] ?? false)) : ?>
                            <div class="flavor-mis-reservas-valorar-prompt">
                                <span>&#11088;</span>
                                <span>¿Cómo fue tu experiencia?</span>
                                <button type="button" class="flavor-mis-reservas-btn-valorar">Valorar</button>
                            </div>
                        <?php endif; ?>

                        <?php if ($reserva['estado'] === 'completada' && ($reserva['valorada'] ?? false)) : ?>
                            <div class="flavor-mis-reservas-valoracion-mostrada">
                                <span class="flavor-mis-reservas-valoracion-label">Tu valoración:</span>
                                <span class="flavor-mis-reservas-estrellas">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <span class="<?php echo $i <= ($reserva['valoracion'] ?? 0) ? 'flavor-mis-reservas-estrella-llena' : 'flavor-mis-reservas-estrella-vacia'; ?>">&#9733;</span>
                                    <?php endfor; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-mis-reservas-card-footer">
                        <div class="flavor-mis-reservas-pago-info">
                            <span class="flavor-mis-reservas-icon-pago">&#128179;</span>
                            <span><?php echo esc_html($reserva['metodo_pago']); ?></span>
                        </div>

                        <div class="flavor-mis-reservas-acciones">
                            <?php if ($reserva['estado'] === 'activa') : ?>
                                <button type="button" class="flavor-mis-reservas-btn-qr" title="Ver código QR">
                                    <span>&#9638;</span> QR
                                </button>
                            <?php endif; ?>

                            <?php if ($reserva['puede_extender']) : ?>
                                <button type="button" class="flavor-mis-reservas-btn-extender">
                                    <span>&#8635;</span> Extender
                                </button>
                            <?php endif; ?>

                            <?php if ($reserva['estado'] === 'pendiente') : ?>
                                <button type="button" class="flavor-mis-reservas-btn-pagar">
                                    <span>&#128179;</span> Pagar ahora
                                </button>
                            <?php endif; ?>

                            <?php if ($reserva['puede_cancelar']) : ?>
                                <button type="button" class="flavor-mis-reservas-btn-cancelar">
                                    <span>&#10006;</span> Cancelar
                                </button>
                            <?php endif; ?>

                            <button type="button" class="flavor-mis-reservas-btn-detalles">
                                Ver detalles <span>&#8594;</span>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="flavor-mis-reservas-paginacion">
        <span class="flavor-mis-reservas-pag-info">Mostrando 1-<?php echo count($reservas_demo); ?> de <?php echo count($reservas_demo); ?> reservas</span>
        <div class="flavor-mis-reservas-pag-controles">
            <button type="button" class="flavor-mis-reservas-pag-btn" disabled>
                <span>&#8592;</span>
            </button>
            <span class="flavor-mis-reservas-pag-actual">Página 1 de 1</span>
            <button type="button" class="flavor-mis-reservas-pag-btn" disabled>
                <span>&#8594;</span>
            </button>
        </div>
    </div>
</div>

<style>
.flavor-mis-reservas-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

/* Header */
.flavor-mis-reservas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.flavor-mis-reservas-titulo-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
}

.flavor-mis-reservas-titulo {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a1a2e;
}

.flavor-mis-reservas-usuario {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: #f1f5f9;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #475569;
}

.flavor-mis-reservas-avatar {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #4f46e5;
    color: white;
    border-radius: 50%;
    font-weight: 600;
    font-size: 0.8rem;
}

.flavor-mis-reservas-btn-nueva {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-mis-reservas-btn-nueva:hover {
    background: #4338ca;
}

/* Filtros */
.flavor-mis-reservas-filtros {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
}

.flavor-mis-reservas-tabs {
    display: flex;
    gap: 5px;
    overflow-x: auto;
    padding-bottom: 5px;
}

.flavor-mis-reservas-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.flavor-mis-reservas-tab:hover {
    background: #e2e8f0;
}

.flavor-mis-reservas-tab.active {
    background: #4f46e5;
    color: white;
}

.flavor-mis-reservas-tab-count {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

.flavor-mis-reservas-tab.active .flavor-mis-reservas-tab-count {
    background: rgba(255,255,255,0.25);
}

.flavor-mis-reservas-buscar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}

.flavor-mis-reservas-buscar-icon {
    color: #94a3b8;
}

.flavor-mis-reservas-buscar-input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 0.9rem;
    outline: none;
}

/* Lista de reservas */
.flavor-mis-reservas-lista {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 25px;
}

/* Estado vacío */
.flavor-mis-reservas-vacio {
    text-align: center;
    padding: 60px 20px;
    background: #f8fafc;
    border-radius: 16px;
}

.flavor-mis-reservas-vacio-icon {
    font-size: 4rem;
    opacity: 0.3;
}

.flavor-mis-reservas-vacio h3 {
    margin: 15px 0 10px;
    color: #475569;
}

.flavor-mis-reservas-vacio p {
    color: #94a3b8;
    margin-bottom: 20px;
}

.flavor-mis-reservas-btn-buscar {
    padding: 12px 30px;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    cursor: pointer;
}

/* Card de reserva */
.flavor-mis-reservas-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    overflow: hidden;
    border-left: 4px solid #e2e8f0;
    transition: all 0.2s;
}

.flavor-mis-reservas-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.flavor-mis-reservas-estado-activa {
    border-left-color: #22c55e;
}

.flavor-mis-reservas-estado-pendiente {
    border-left-color: #f59e0b;
}

.flavor-mis-reservas-estado-completada {
    border-left-color: #6366f1;
}

.flavor-mis-reservas-estado-cancelada {
    border-left-color: #ef4444;
    opacity: 0.85;
}

.flavor-mis-reservas-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}

.flavor-mis-reservas-card-id-estado {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-mis-reservas-card-id {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    font-family: monospace;
}

.flavor-mis-reservas-estado-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.flavor-mis-reservas-badge-hoy {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    animation: flavor-pulse-hoy 2s infinite;
}

@keyframes flavor-pulse-hoy {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.flavor-mis-reservas-tipo-badge {
    font-size: 0.8rem;
    color: #64748b;
    background: white;
    padding: 5px 12px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

/* Card body */
.flavor-mis-reservas-card-body {
    padding: 20px;
}

.flavor-mis-reservas-parking-info {
    margin-bottom: 20px;
}

.flavor-mis-reservas-parking-nombre {
    margin: 0 0 8px;
    font-size: 1.15rem;
    font-weight: 600;
    color: #1a1a2e;
}

.flavor-mis-reservas-parking-direccion {
    margin: 0;
    font-size: 0.9rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.flavor-mis-reservas-icon {
    font-size: 0.9rem;
}

/* Grid de detalles */
.flavor-mis-reservas-detalles-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 15px;
}

.flavor-mis-reservas-detalle {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-mis-reservas-detalle-label {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.flavor-mis-reservas-detalle-valor {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1a1a2e;
    display: flex;
    flex-direction: column;
}

.flavor-mis-reservas-detalle-valor small {
    font-size: 0.8rem;
    font-weight: 400;
    color: #64748b;
}

.flavor-mis-reservas-plaza {
    font-family: monospace;
    font-size: 1.1rem;
}

.flavor-mis-reservas-precio {
    color: #4f46e5;
}

/* Vehículo */
.flavor-mis-reservas-vehiculo {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f1f5f9;
    border-radius: 10px;
    margin-bottom: 15px;
}

.flavor-mis-reservas-vehiculo-icon {
    font-size: 1.5rem;
}

.flavor-mis-reservas-vehiculo-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.flavor-mis-reservas-matricula {
    font-weight: 700;
    font-size: 1rem;
    color: #1a1a2e;
    font-family: monospace;
    letter-spacing: 1px;
}

.flavor-mis-reservas-vehiculo-modelo {
    font-size: 0.85rem;
    color: #64748b;
}

/* Código de acceso */
.flavor-mis-reservas-codigo-acceso {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border-radius: 10px;
    margin-bottom: 15px;
}

.flavor-mis-reservas-codigo-label {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.8);
}

.flavor-mis-reservas-codigo-valor {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-mis-reservas-codigo {
    font-family: monospace;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    letter-spacing: 2px;
}

.flavor-mis-reservas-btn-copiar {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-mis-reservas-btn-copiar:hover {
    background: rgba(255,255,255,0.3);
}

/* Información de cancelación */
.flavor-mis-reservas-cancelacion-info {
    padding: 12px;
    background: #fef2f2;
    border-radius: 10px;
    margin-bottom: 15px;
}

.flavor-mis-reservas-motivo {
    margin: 0 0 8px;
    font-size: 0.9rem;
    color: #991b1b;
}

.flavor-mis-reservas-reembolso {
    margin: 0;
    font-size: 0.9rem;
    color: #22c55e;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Valoración */
.flavor-mis-reservas-valorar-prompt {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #fef3c7;
    border-radius: 10px;
}

.flavor-mis-reservas-btn-valorar {
    margin-left: auto;
    padding: 6px 16px;
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
}

.flavor-mis-reservas-valoracion-mostrada {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
}

.flavor-mis-reservas-valoracion-label {
    color: #64748b;
}

.flavor-mis-reservas-estrellas {
    display: flex;
    gap: 2px;
}

.flavor-mis-reservas-estrella-llena {
    color: #f59e0b;
}

.flavor-mis-reservas-estrella-vacia {
    color: #e2e8f0;
}

/* Card footer */
.flavor-mis-reservas-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
}

.flavor-mis-reservas-pago-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #64748b;
}

.flavor-mis-reservas-acciones {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.flavor-mis-reservas-acciones button {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.flavor-mis-reservas-btn-qr {
    background: #e0e7ff;
    color: #4f46e5;
}

.flavor-mis-reservas-btn-extender {
    background: #dcfce7;
    color: #22c55e;
}

.flavor-mis-reservas-btn-pagar {
    background: #f59e0b;
    color: white;
}

.flavor-mis-reservas-btn-cancelar {
    background: #fef2f2;
    color: #ef4444;
}

.flavor-mis-reservas-btn-detalles {
    background: #f1f5f9;
    color: #475569;
}

.flavor-mis-reservas-acciones button:hover {
    filter: brightness(0.95);
}

/* Paginación */
.flavor-mis-reservas-paginacion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #f1f5f9;
}

.flavor-mis-reservas-pag-info {
    font-size: 0.85rem;
    color: #64748b;
}

.flavor-mis-reservas-pag-controles {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-mis-reservas-pag-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-mis-reservas-pag-btn:hover:not(:disabled) {
    border-color: #4f46e5;
    color: #4f46e5;
}

.flavor-mis-reservas-pag-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.flavor-mis-reservas-pag-actual {
    font-size: 0.85rem;
    color: #475569;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-mis-reservas-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-mis-reservas-btn-nueva {
        width: 100%;
        justify-content: center;
    }

    .flavor-mis-reservas-detalles-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-mis-reservas-card-footer {
        flex-direction: column;
        gap: 15px;
    }

    .flavor-mis-reservas-acciones {
        width: 100%;
        justify-content: flex-end;
    }

    .flavor-mis-reservas-paginacion {
        flex-direction: column;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .flavor-mis-reservas-titulo-wrapper {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-mis-reservas-detalles-grid {
        grid-template-columns: 1fr;
    }

    .flavor-mis-reservas-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .flavor-mis-reservas-acciones {
        flex-direction: column;
    }

    .flavor-mis-reservas-acciones button {
        width: 100%;
        justify-content: center;
    }
}
</style>

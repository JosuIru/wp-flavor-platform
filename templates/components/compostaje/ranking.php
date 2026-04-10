<?php
/**
 * Template: Ranking de composteros
 *
 * @package FlavorPlatform
 * @since 1.0.0
 *
 * Variables esperadas en $args:
 * - ranking_mensual: array con ranking del mes actual
 * - ranking_anual: array con ranking anual
 * - usuario_actual_id: ID del usuario actual para destacar su posición
 * - periodo_activo: 'mensual' o 'anual'
 * - mes_actual: nombre del mes actual
 * - año_actual: año actual
 */

if (!defined('ABSPATH')) exit;

// Datos de demostración para ranking mensual
$rankingMensual = $args['ranking_mensual'] ?? [
    ['posicion' => 1, 'usuario_id' => 101, 'nombre' => 'María García', 'avatar' => '', 'kg' => 45.2, 'aportaciones' => 18, 'nivel' => 'Maestro', 'medalla' => '🥇'],
    ['posicion' => 2, 'usuario_id' => 102, 'nombre' => 'Carlos López', 'avatar' => '', 'kg' => 38.7, 'aportaciones' => 15, 'nivel' => 'Experto', 'medalla' => '🥈'],
    ['posicion' => 3, 'usuario_id' => 103, 'nombre' => 'Ana Martínez', 'avatar' => '', 'kg' => 32.1, 'aportaciones' => 12, 'nivel' => 'Experto', 'medalla' => '🥉'],
    ['posicion' => 4, 'usuario_id' => 104, 'nombre' => 'Pedro Sánchez', 'avatar' => '', 'kg' => 28.5, 'aportaciones' => 11, 'nivel' => 'Avanzado', 'medalla' => ''],
    ['posicion' => 5, 'usuario_id' => 105, 'nombre' => 'Laura Fernández', 'avatar' => '', 'kg' => 25.3, 'aportaciones' => 10, 'nivel' => 'Avanzado', 'medalla' => ''],
    ['posicion' => 6, 'usuario_id' => 106, 'nombre' => 'Miguel Torres', 'avatar' => '', 'kg' => 22.8, 'aportaciones' => 9, 'nivel' => 'Avanzado', 'medalla' => ''],
    ['posicion' => 7, 'usuario_id' => 107, 'nombre' => 'Elena Ruiz', 'avatar' => '', 'kg' => 20.1, 'aportaciones' => 8, 'nivel' => 'Intermedio', 'medalla' => ''],
    ['posicion' => 8, 'usuario_id' => 108, 'nombre' => 'David Moreno', 'avatar' => '', 'kg' => 18.6, 'aportaciones' => 8, 'nivel' => 'Intermedio', 'medalla' => ''],
    ['posicion' => 9, 'usuario_id' => 109, 'nombre' => 'Carmen Díaz', 'avatar' => '', 'kg' => 16.4, 'aportaciones' => 7, 'nivel' => 'Intermedio', 'medalla' => ''],
    ['posicion' => 10, 'usuario_id' => 110, 'nombre' => 'Javier Hernández', 'avatar' => '', 'kg' => 14.9, 'aportaciones' => 6, 'nivel' => 'Intermedio', 'medalla' => ''],
];

// Datos de demostración para ranking anual
$rankingAnual = $args['ranking_anual'] ?? [
    ['posicion' => 1, 'usuario_id' => 101, 'nombre' => 'María García', 'avatar' => '', 'kg' => 512.8, 'aportaciones' => 186, 'nivel' => 'Maestro', 'medalla' => '🥇'],
    ['posicion' => 2, 'usuario_id' => 103, 'nombre' => 'Ana Martínez', 'avatar' => '', 'kg' => 478.3, 'aportaciones' => 172, 'nivel' => 'Experto', 'medalla' => '🥈'],
    ['posicion' => 3, 'usuario_id' => 102, 'nombre' => 'Carlos López', 'avatar' => '', 'kg' => 445.1, 'aportaciones' => 165, 'nivel' => 'Experto', 'medalla' => '🥉'],
    ['posicion' => 4, 'usuario_id' => 106, 'nombre' => 'Miguel Torres', 'avatar' => '', 'kg' => 398.6, 'aportaciones' => 148, 'nivel' => 'Avanzado', 'medalla' => ''],
    ['posicion' => 5, 'usuario_id' => 104, 'nombre' => 'Pedro Sánchez', 'avatar' => '', 'kg' => 356.2, 'aportaciones' => 134, 'nivel' => 'Avanzado', 'medalla' => ''],
    ['posicion' => 6, 'usuario_id' => 105, 'nombre' => 'Laura Fernández', 'avatar' => '', 'kg' => 312.7, 'aportaciones' => 121, 'nivel' => 'Avanzado', 'medalla' => ''],
    ['posicion' => 7, 'usuario_id' => 108, 'nombre' => 'David Moreno', 'avatar' => '', 'kg' => 287.4, 'aportaciones' => 108, 'nivel' => 'Intermedio', 'medalla' => ''],
    ['posicion' => 8, 'usuario_id' => 107, 'nombre' => 'Elena Ruiz', 'avatar' => '', 'kg' => 265.9, 'aportaciones' => 98, 'nivel' => 'Intermedio', 'medalla' => ''],
    ['posicion' => 9, 'usuario_id' => 111, 'nombre' => 'Rosa Jiménez', 'avatar' => '', 'kg' => 243.2, 'aportaciones' => 92, 'nivel' => 'Intermedio', 'medalla' => ''],
    ['posicion' => 10, 'usuario_id' => 109, 'nombre' => 'Carmen Díaz', 'avatar' => '', 'kg' => 221.8, 'aportaciones' => 86, 'nivel' => 'Intermedio', 'medalla' => ''],
];

$usuarioActualId = $args['usuario_actual_id'] ?? 107;
$periodoActivo = $args['periodo_activo'] ?? 'mensual';
$mesActual = $args['mes_actual'] ?? date_i18n('F');
$añoActual = $args['año_actual'] ?? date('Y');

// Estadísticas globales
$estadisticasGlobales = [
    'total_participantes' => 156,
    'kg_totales_mes' => 892.5,
    'kg_totales_año' => 8745.3,
];

// Obtener posición del usuario actual
$posicionUsuarioMensual = null;
$posicionUsuarioAnual = null;
foreach ($rankingMensual as $participante) {
    if ($participante['usuario_id'] === $usuarioActualId) {
        $posicionUsuarioMensual = $participante;
        break;
    }
}
foreach ($rankingAnual as $participante) {
    if ($participante['usuario_id'] === $usuarioActualId) {
        $posicionUsuarioAnual = $participante;
        break;
    }
}
?>

<div class="flavor-ranking-compostaje">
    <!-- Cabecera del ranking -->
    <div class="flavor-ranking-header">
        <div class="flavor-ranking-titulo-container">
            <h2 class="flavor-ranking-titulo">
                <span class="flavor-ranking-icono">🏆</span>
                Ranking de Composteros
            </h2>
            <p class="flavor-ranking-subtitulo">
                Los mejores contribuidores a la comunidad de compostaje
            </p>
        </div>

        <!-- Selector de periodo -->
        <div class="flavor-periodo-selector">
            <button
                class="flavor-periodo-btn <?php echo $periodoActivo === 'mensual' ? 'flavor-periodo-activo' : ''; ?>"
                data-periodo="mensual"
            >
                📅 <?php echo esc_html($mesActual); ?>
            </button>
            <button
                class="flavor-periodo-btn <?php echo $periodoActivo === 'anual' ? 'flavor-periodo-activo' : ''; ?>"
                data-periodo="anual"
            >
                📆 Año <?php echo esc_html($añoActual); ?>
            </button>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="flavor-ranking-stats">
        <div class="flavor-ranking-stat">
            <span class="flavor-stat-numero"><?php echo esc_html($estadisticasGlobales['total_participantes']); ?></span>
            <span class="flavor-stat-label">Participantes</span>
        </div>
        <div class="flavor-ranking-stat">
            <span class="flavor-stat-numero"><?php echo esc_html(number_format($estadisticasGlobales['kg_totales_mes'], 0)); ?> kg</span>
            <span class="flavor-stat-label">Este mes</span>
        </div>
        <div class="flavor-ranking-stat">
            <span class="flavor-stat-numero"><?php echo esc_html(number_format($estadisticasGlobales['kg_totales_año'] / 1000, 1)); ?> t</span>
            <span class="flavor-stat-label">Este año</span>
        </div>
    </div>

    <!-- Podio (Top 3) -->
    <div class="flavor-podio" data-periodo="mensual" <?php echo $periodoActivo !== 'mensual' ? 'style="display:none"' : ''; ?>>
        <?php if (count($rankingMensual) >= 3) : ?>
            <div class="flavor-podio-item flavor-podio-segundo">
                <div class="flavor-podio-avatar">
                    <span class="flavor-avatar-emoji">🌿</span>
                    <span class="flavor-podio-medalla">🥈</span>
                </div>
                <span class="flavor-podio-nombre"><?php echo esc_html($rankingMensual[1]['nombre']); ?></span>
                <span class="flavor-podio-kg"><?php echo esc_html(number_format($rankingMensual[1]['kg'], 1)); ?> kg</span>
                <div class="flavor-podio-pedestal flavor-pedestal-plata">2</div>
            </div>
            <div class="flavor-podio-item flavor-podio-primero">
                <div class="flavor-podio-corona">👑</div>
                <div class="flavor-podio-avatar flavor-avatar-oro">
                    <span class="flavor-avatar-emoji">🌿</span>
                    <span class="flavor-podio-medalla">🥇</span>
                </div>
                <span class="flavor-podio-nombre"><?php echo esc_html($rankingMensual[0]['nombre']); ?></span>
                <span class="flavor-podio-kg"><?php echo esc_html(number_format($rankingMensual[0]['kg'], 1)); ?> kg</span>
                <div class="flavor-podio-pedestal flavor-pedestal-oro">1</div>
            </div>
            <div class="flavor-podio-item flavor-podio-tercero">
                <div class="flavor-podio-avatar">
                    <span class="flavor-avatar-emoji">🌿</span>
                    <span class="flavor-podio-medalla">🥉</span>
                </div>
                <span class="flavor-podio-nombre"><?php echo esc_html($rankingMensual[2]['nombre']); ?></span>
                <span class="flavor-podio-kg"><?php echo esc_html(number_format($rankingMensual[2]['kg'], 1)); ?> kg</span>
                <div class="flavor-podio-pedestal flavor-pedestal-bronce">3</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="flavor-podio" data-periodo="anual" <?php echo $periodoActivo !== 'anual' ? 'style="display:none"' : ''; ?>>
        <?php if (count($rankingAnual) >= 3) : ?>
            <div class="flavor-podio-item flavor-podio-segundo">
                <div class="flavor-podio-avatar">
                    <span class="flavor-avatar-emoji">🌿</span>
                    <span class="flavor-podio-medalla">🥈</span>
                </div>
                <span class="flavor-podio-nombre"><?php echo esc_html($rankingAnual[1]['nombre']); ?></span>
                <span class="flavor-podio-kg"><?php echo esc_html(number_format($rankingAnual[1]['kg'], 1)); ?> kg</span>
                <div class="flavor-podio-pedestal flavor-pedestal-plata">2</div>
            </div>
            <div class="flavor-podio-item flavor-podio-primero">
                <div class="flavor-podio-corona">👑</div>
                <div class="flavor-podio-avatar flavor-avatar-oro">
                    <span class="flavor-avatar-emoji">🌿</span>
                    <span class="flavor-podio-medalla">🥇</span>
                </div>
                <span class="flavor-podio-nombre"><?php echo esc_html($rankingAnual[0]['nombre']); ?></span>
                <span class="flavor-podio-kg"><?php echo esc_html(number_format($rankingAnual[0]['kg'], 1)); ?> kg</span>
                <div class="flavor-podio-pedestal flavor-pedestal-oro">1</div>
            </div>
            <div class="flavor-podio-item flavor-podio-tercero">
                <div class="flavor-podio-avatar">
                    <span class="flavor-avatar-emoji">🌿</span>
                    <span class="flavor-podio-medalla">🥉</span>
                </div>
                <span class="flavor-podio-nombre"><?php echo esc_html($rankingAnual[2]['nombre']); ?></span>
                <span class="flavor-podio-kg"><?php echo esc_html(number_format($rankingAnual[2]['kg'], 1)); ?> kg</span>
                <div class="flavor-podio-pedestal flavor-pedestal-bronce">3</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabla de ranking mensual -->
    <div class="flavor-ranking-tabla-container" data-periodo="mensual" <?php echo $periodoActivo !== 'mensual' ? 'style="display:none"' : ''; ?>>
        <h3 class="flavor-tabla-titulo">Ranking completo - <?php echo esc_html($mesActual); ?></h3>
        <div class="flavor-tabla-scroll">
            <table class="flavor-ranking-tabla">
                <thead>
                    <tr>
                        <th class="flavor-col-posicion">#</th>
                        <th class="flavor-col-usuario">Usuario</th>
                        <th class="flavor-col-nivel">Nivel</th>
                        <th class="flavor-col-aportaciones">Aportaciones</th>
                        <th class="flavor-col-kg">Kg compostados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankingMensual as $participante) :
                        $esUsuarioActual = $participante['usuario_id'] === $usuarioActualId;
                    ?>
                        <tr class="<?php echo $esUsuarioActual ? 'flavor-fila-actual' : ''; ?>">
                            <td class="flavor-col-posicion">
                                <?php if (!empty($participante['medalla'])) : ?>
                                    <span class="flavor-posicion-medalla"><?php echo esc_html($participante['medalla']); ?></span>
                                <?php else : ?>
                                    <span class="flavor-posicion-numero"><?php echo esc_html($participante['posicion']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="flavor-col-usuario">
                                <div class="flavor-usuario-info">
                                    <span class="flavor-usuario-avatar">🌿</span>
                                    <span class="flavor-usuario-nombre">
                                        <?php echo esc_html($participante['nombre']); ?>
                                        <?php if ($esUsuarioActual) : ?>
                                            <span class="flavor-badge-tu">(Tú)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="flavor-col-nivel">
                                <span class="flavor-nivel-badge flavor-nivel-<?php echo esc_attr(strtolower($participante['nivel'])); ?>">
                                    <?php echo esc_html($participante['nivel']); ?>
                                </span>
                            </td>
                            <td class="flavor-col-aportaciones"><?php echo esc_html($participante['aportaciones']); ?></td>
                            <td class="flavor-col-kg">
                                <strong><?php echo esc_html(number_format($participante['kg'], 1)); ?></strong> kg
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabla de ranking anual -->
    <div class="flavor-ranking-tabla-container" data-periodo="anual" <?php echo $periodoActivo !== 'anual' ? 'style="display:none"' : ''; ?>>
        <h3 class="flavor-tabla-titulo">Ranking completo - Año <?php echo esc_html($añoActual); ?></h3>
        <div class="flavor-tabla-scroll">
            <table class="flavor-ranking-tabla">
                <thead>
                    <tr>
                        <th class="flavor-col-posicion">#</th>
                        <th class="flavor-col-usuario">Usuario</th>
                        <th class="flavor-col-nivel">Nivel</th>
                        <th class="flavor-col-aportaciones">Aportaciones</th>
                        <th class="flavor-col-kg">Kg compostados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankingAnual as $participante) :
                        $esUsuarioActual = $participante['usuario_id'] === $usuarioActualId;
                    ?>
                        <tr class="<?php echo $esUsuarioActual ? 'flavor-fila-actual' : ''; ?>">
                            <td class="flavor-col-posicion">
                                <?php if (!empty($participante['medalla'])) : ?>
                                    <span class="flavor-posicion-medalla"><?php echo esc_html($participante['medalla']); ?></span>
                                <?php else : ?>
                                    <span class="flavor-posicion-numero"><?php echo esc_html($participante['posicion']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="flavor-col-usuario">
                                <div class="flavor-usuario-info">
                                    <span class="flavor-usuario-avatar">🌿</span>
                                    <span class="flavor-usuario-nombre">
                                        <?php echo esc_html($participante['nombre']); ?>
                                        <?php if ($esUsuarioActual) : ?>
                                            <span class="flavor-badge-tu">(Tú)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="flavor-col-nivel">
                                <span class="flavor-nivel-badge flavor-nivel-<?php echo esc_attr(strtolower($participante['nivel'])); ?>">
                                    <?php echo esc_html($participante['nivel']); ?>
                                </span>
                            </td>
                            <td class="flavor-col-aportaciones"><?php echo esc_html($participante['aportaciones']); ?></td>
                            <td class="flavor-col-kg">
                                <strong><?php echo esc_html(number_format($participante['kg'], 1)); ?></strong> kg
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tu posición (si no está en top 10) -->
    <?php if ($posicionUsuarioMensual && $posicionUsuarioMensual['posicion'] > 10) : ?>
        <div class="flavor-tu-posicion" data-periodo="mensual" <?php echo $periodoActivo !== 'mensual' ? 'style="display:none"' : ''; ?>>
            <span class="flavor-tu-posicion-label">Tu posición:</span>
            <span class="flavor-tu-posicion-numero">#<?php echo esc_html($posicionUsuarioMensual['posicion']); ?></span>
            <span class="flavor-tu-posicion-kg"><?php echo esc_html(number_format($posicionUsuarioMensual['kg'], 1)); ?> kg</span>
        </div>
    <?php endif; ?>

    <!-- Motivación -->
    <div class="flavor-ranking-motivacion">
        <p>💪 ¡Cada aportación cuenta! Sigue compostando para subir en el ranking.</p>
    </div>
</div>

<style>
.flavor-ranking-compostaje {
    max-width: 900px;
    margin: 0 auto;
    padding: 1.5rem;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

/* Header */
.flavor-ranking-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.flavor-ranking-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    color: #1b5e20;
    margin: 0;
}

.flavor-ranking-icono {
    font-size: 1.8rem;
}

.flavor-ranking-subtitulo {
    color: #666;
    margin: 0.25rem 0 0;
    font-size: 0.95rem;
}

.flavor-periodo-selector {
    display: flex;
    gap: 0.5rem;
    background: #f5f5f5;
    padding: 0.25rem;
    border-radius: 10px;
}

.flavor-periodo-btn {
    padding: 0.6rem 1rem;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-periodo-btn:hover {
    color: #333;
}

.flavor-periodo-activo {
    background: #fff;
    color: #2e7d32;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

/* Estadísticas rápidas */
.flavor-ranking-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.flavor-ranking-stat {
    text-align: center;
    padding: 1rem;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    border-radius: 12px;
}

.flavor-stat-numero {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #2e7d32;
}

.flavor-stat-label {
    font-size: 0.85rem;
    color: #558b2f;
}

/* Podio */
.flavor-podio {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1rem;
}

.flavor-podio-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.flavor-podio-primero {
    order: 2;
}

.flavor-podio-segundo {
    order: 1;
}

.flavor-podio-tercero {
    order: 3;
}

.flavor-podio-corona {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.flavor-podio-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, #81c784, #4caf50);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin-bottom: 0.5rem;
}

.flavor-avatar-oro {
    width: 85px;
    height: 85px;
    background: linear-gradient(135deg, #ffd54f, #ffb300);
    box-shadow: 0 4px 15px rgba(255, 179, 0, 0.4);
}

.flavor-avatar-emoji {
    font-size: 2rem;
}

.flavor-podio-medalla {
    position: absolute;
    bottom: -5px;
    right: -5px;
    font-size: 1.5rem;
}

.flavor-podio-nombre {
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
    max-width: 100px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-podio-kg {
    font-size: 0.9rem;
    color: #4caf50;
    font-weight: 500;
}

.flavor-podio-pedestal {
    width: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    border-radius: 8px 8px 0 0;
    margin-top: 0.5rem;
}

.flavor-pedestal-oro {
    height: 80px;
    background: linear-gradient(135deg, #ffd54f, #ff8f00);
}

.flavor-pedestal-plata {
    height: 60px;
    background: linear-gradient(135deg, #bdbdbd, #757575);
}

.flavor-pedestal-bronce {
    height: 45px;
    background: linear-gradient(135deg, #bcaaa4, #8d6e63);
}

/* Tabla de ranking */
.flavor-ranking-tabla-container {
    margin-bottom: 1.5rem;
}

.flavor-tabla-titulo {
    font-size: 1.1rem;
    color: #333;
    margin: 0 0 1rem;
}

.flavor-tabla-scroll {
    overflow-x: auto;
}

.flavor-ranking-tabla {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

.flavor-ranking-tabla th {
    background: #f5f5f5;
    padding: 0.875rem 1rem;
    text-align: left;
    font-weight: 600;
    color: #555;
    border-bottom: 2px solid #e0e0e0;
}

.flavor-ranking-tabla td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #eee;
}

.flavor-ranking-tabla tr:hover {
    background: #fafafa;
}

.flavor-fila-actual {
    background: #e8f5e9 !important;
}

.flavor-fila-actual td {
    border-color: #c8e6c9;
}

.flavor-col-posicion {
    width: 60px;
    text-align: center;
}

.flavor-posicion-medalla {
    font-size: 1.5rem;
}

.flavor-posicion-numero {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    background: #f0f0f0;
    border-radius: 50%;
    font-weight: 600;
    color: #666;
}

.flavor-usuario-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-usuario-avatar {
    font-size: 1.5rem;
}

.flavor-usuario-nombre {
    font-weight: 500;
}

.flavor-badge-tu {
    display: inline-block;
    padding: 0.15rem 0.5rem;
    background: #4caf50;
    color: #fff;
    border-radius: 10px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.flavor-nivel-badge {
    display: inline-block;
    padding: 0.25rem 0.6rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.flavor-nivel-maestro {
    background: linear-gradient(135deg, #ffd54f, #ff8f00);
    color: #fff;
}

.flavor-nivel-experto {
    background: linear-gradient(135deg, #81c784, #4caf50);
    color: #fff;
}

.flavor-nivel-avanzado {
    background: #e3f2fd;
    color: #1976d2;
}

.flavor-nivel-intermedio {
    background: #f5f5f5;
    color: #666;
}

.flavor-col-kg strong {
    color: #2e7d32;
}

/* Tu posición */
.flavor-tu-posicion {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.flavor-tu-posicion-label {
    color: #555;
}

.flavor-tu-posicion-numero {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2e7d32;
}

.flavor-tu-posicion-kg {
    color: #4caf50;
    font-weight: 500;
}

/* Motivación */
.flavor-ranking-motivacion {
    text-align: center;
    padding: 1rem;
    background: #fff8e1;
    border-radius: 10px;
    border-left: 4px solid #ffc107;
}

.flavor-ranking-motivacion p {
    margin: 0;
    color: #f57c00;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-ranking-header {
        flex-direction: column;
    }

    .flavor-periodo-selector {
        width: 100%;
        justify-content: center;
    }

    .flavor-ranking-stats {
        grid-template-columns: 1fr;
    }

    .flavor-podio {
        flex-wrap: wrap;
    }

    .flavor-podio-item {
        flex: 1;
        min-width: 100px;
    }

    .flavor-ranking-tabla {
        font-size: 0.85rem;
    }

    .flavor-ranking-tabla th,
    .flavor-ranking-tabla td {
        padding: 0.625rem 0.5rem;
    }

    .flavor-col-nivel,
    .flavor-col-aportaciones {
        display: none;
    }
}

@media (max-width: 480px) {
    .flavor-ranking-compostaje {
        padding: 1rem;
    }

    .flavor-ranking-titulo {
        font-size: 1.25rem;
    }

    .flavor-podio-pedestal {
        width: 60px;
    }

    .flavor-pedestal-oro { height: 60px; }
    .flavor-pedestal-plata { height: 45px; }
    .flavor-pedestal-bronce { height: 35px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const botonesPeriodo = document.querySelectorAll('.flavor-periodo-btn');

    botonesPeriodo.forEach(boton => {
        boton.addEventListener('click', function() {
            const periodoSeleccionado = this.dataset.periodo;

            // Actualizar botones activos
            botonesPeriodo.forEach(btn => btn.classList.remove('flavor-periodo-activo'));
            this.classList.add('flavor-periodo-activo');

            // Mostrar/ocultar contenido según periodo
            document.querySelectorAll('.flavor-podio, .flavor-ranking-tabla-container, .flavor-tu-posicion').forEach(elemento => {
                if (elemento.dataset.periodo === periodoSeleccionado) {
                    elemento.style.display = '';
                } else {
                    elemento.style.display = 'none';
                }
            });
        });
    });
});
</script>

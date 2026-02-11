<?php
/**
 * Template: Dashboard de estadísticas del usuario de compostaje
 *
 * @package FlavorChatIA
 * @since 1.0.0
 *
 * Variables esperadas en $args:
 * - usuario: array con datos del usuario
 * - estadisticas: array con estadísticas del usuario
 * - historial: array con últimas aportaciones
 * - logros: array con logros desbloqueados
 */

if (!defined('ABSPATH')) exit;

// Datos del usuario
$datosUsuario = $args['usuario'] ?? [
    'nombre' => 'Usuario Demo',
    'avatar' => '',
    'miembro_desde' => '2024-01-15',
    'nivel' => 'Compostero Experto',
];

// Estadísticas del usuario (datos de demostración)
$estadisticas = $args['estadisticas'] ?? [
    'kg_totales' => 127.5,
    'kg_mes_actual' => 18.3,
    'aportaciones_totales' => 45,
    'aportaciones_mes' => 8,
    'co2_evitado' => 38.25,
    'arboles_equivalentes' => 1.9,
    'ranking_posicion' => 12,
    'ranking_total' => 156,
    'racha_dias' => 7,
    'mejor_racha' => 21,
];

// Historial de aportaciones recientes
$historialAportaciones = $args['historial'] ?? [
    [
        'fecha' => '2024-12-10',
        'tipo' => 'Frutas y verduras',
        'peso' => 2.5,
        'compostera' => 'Plaza Mayor',
    ],
    [
        'fecha' => '2024-12-08',
        'tipo' => 'Posos de café',
        'peso' => 0.8,
        'compostera' => 'Parque Central',
    ],
    [
        'fecha' => '2024-12-05',
        'tipo' => 'Hojas secas',
        'peso' => 3.2,
        'compostera' => 'Plaza Mayor',
    ],
    [
        'fecha' => '2024-12-02',
        'tipo' => 'Cáscaras de huevo',
        'peso' => 0.5,
        'compostera' => 'Huerto Comunitario',
    ],
];

// Logros desbloqueados
$logrosUsuario = $args['logros'] ?? [
    ['icono' => '🌱', 'nombre' => 'Primera aportación', 'desbloqueado' => true],
    ['icono' => '🥇', 'nombre' => '10 kg compostados', 'desbloqueado' => true],
    ['icono' => '🏆', 'nombre' => '50 kg compostados', 'desbloqueado' => true],
    ['icono' => '💯', 'nombre' => '100 kg compostados', 'desbloqueado' => true],
    ['icono' => '🔥', 'nombre' => 'Racha de 7 días', 'desbloqueado' => true],
    ['icono' => '⭐', 'nombre' => 'Top 20 del mes', 'desbloqueado' => true],
    ['icono' => '🌳', 'nombre' => '1 árbol salvado', 'desbloqueado' => true],
    ['icono' => '🎯', 'nombre' => '50 aportaciones', 'desbloqueado' => false],
];

// Calcular progreso hacia siguiente nivel
$kgParaSiguienteNivel = 150;
$progresoNivel = min(100, ($estadisticas['kg_totales'] / $kgParaSiguienteNivel) * 100);
?>

<div class="flavor-estadisticas-usuario">
    <!-- Cabecera del perfil -->
    <div class="flavor-perfil-header">
        <div class="flavor-perfil-avatar">
            <?php if (!empty($datosUsuario['avatar'])) : ?>
                <img src="<?php echo esc_url($datosUsuario['avatar']); ?>" alt="Avatar">
            <?php else : ?>
                <span class="flavor-avatar-placeholder">🌿</span>
            <?php endif; ?>
        </div>
        <div class="flavor-perfil-info">
            <h2 class="flavor-perfil-nombre"><?php echo esc_html($datosUsuario['nombre']); ?></h2>
            <span class="flavor-perfil-nivel"><?php echo esc_html($datosUsuario['nivel']); ?></span>
            <p class="flavor-perfil-miembro">
                Miembro desde <?php echo esc_html(date_i18n('F Y', strtotime($datosUsuario['miembro_desde']))); ?>
            </p>
        </div>
        <div class="flavor-perfil-racha">
            <span class="flavor-racha-numero"><?php echo esc_html($estadisticas['racha_dias']); ?></span>
            <span class="flavor-racha-texto">días de racha 🔥</span>
        </div>
    </div>

    <!-- Progreso de nivel -->
    <div class="flavor-progreso-nivel">
        <div class="flavor-progreso-info">
            <span>Progreso hacia Compostero Maestro</span>
            <span><?php echo esc_html(number_format($estadisticas['kg_totales'], 1)); ?> / <?php echo esc_html($kgParaSiguienteNivel); ?> kg</span>
        </div>
        <div class="flavor-progreso-barra">
            <div class="flavor-progreso-relleno" style="width: <?php echo esc_attr($progresoNivel); ?>%"></div>
        </div>
    </div>

    <!-- Tarjetas de estadísticas principales -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card flavor-stat-principal">
            <div class="flavor-stat-icono">⚖️</div>
            <div class="flavor-stat-contenido">
                <span class="flavor-stat-valor"><?php echo esc_html(number_format($estadisticas['kg_totales'], 1)); ?></span>
                <span class="flavor-stat-unidad">kg</span>
            </div>
            <span class="flavor-stat-etiqueta">Total compostado</span>
            <span class="flavor-stat-secundario">+<?php echo esc_html(number_format($estadisticas['kg_mes_actual'], 1)); ?> kg este mes</span>
        </div>

        <div class="flavor-stat-card flavor-stat-co2">
            <div class="flavor-stat-icono">💨</div>
            <div class="flavor-stat-contenido">
                <span class="flavor-stat-valor"><?php echo esc_html(number_format($estadisticas['co2_evitado'], 1)); ?></span>
                <span class="flavor-stat-unidad">kg</span>
            </div>
            <span class="flavor-stat-etiqueta">CO₂ evitado</span>
            <span class="flavor-stat-secundario">≈ <?php echo esc_html(number_format($estadisticas['arboles_equivalentes'], 1)); ?> árboles/año</span>
        </div>

        <div class="flavor-stat-card flavor-stat-ranking">
            <div class="flavor-stat-icono">🏅</div>
            <div class="flavor-stat-contenido">
                <span class="flavor-stat-valor">#<?php echo esc_html($estadisticas['ranking_posicion']); ?></span>
            </div>
            <span class="flavor-stat-etiqueta">Posición en ranking</span>
            <span class="flavor-stat-secundario">de <?php echo esc_html($estadisticas['ranking_total']); ?> participantes</span>
        </div>

        <div class="flavor-stat-card flavor-stat-aportaciones">
            <div class="flavor-stat-icono">📦</div>
            <div class="flavor-stat-contenido">
                <span class="flavor-stat-valor"><?php echo esc_html($estadisticas['aportaciones_totales']); ?></span>
            </div>
            <span class="flavor-stat-etiqueta">Aportaciones totales</span>
            <span class="flavor-stat-secundario"><?php echo esc_html($estadisticas['aportaciones_mes']); ?> este mes</span>
        </div>
    </div>

    <!-- Sección de logros -->
    <div class="flavor-seccion-logros">
        <h3 class="flavor-seccion-titulo">
            <span>🏆</span> Logros conseguidos
        </h3>
        <div class="flavor-logros-grid">
            <?php foreach ($logrosUsuario as $logro) : ?>
                <div class="flavor-logro <?php echo $logro['desbloqueado'] ? 'flavor-logro-desbloqueado' : 'flavor-logro-bloqueado'; ?>">
                    <span class="flavor-logro-icono"><?php echo esc_html($logro['icono']); ?></span>
                    <span class="flavor-logro-nombre"><?php echo esc_html($logro['nombre']); ?></span>
                    <?php if (!$logro['desbloqueado']) : ?>
                        <span class="flavor-logro-candado">🔒</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Historial de aportaciones recientes -->
    <div class="flavor-seccion-historial">
        <h3 class="flavor-seccion-titulo">
            <span>📋</span> Últimas aportaciones
        </h3>
        <div class="flavor-historial-lista">
            <?php foreach ($historialAportaciones as $aportacion) : ?>
                <div class="flavor-historial-item">
                    <div class="flavor-historial-fecha">
                        <span class="flavor-fecha-dia"><?php echo esc_html(date_i18n('d', strtotime($aportacion['fecha']))); ?></span>
                        <span class="flavor-fecha-mes"><?php echo esc_html(date_i18n('M', strtotime($aportacion['fecha']))); ?></span>
                    </div>
                    <div class="flavor-historial-detalle">
                        <span class="flavor-historial-tipo"><?php echo esc_html($aportacion['tipo']); ?></span>
                        <span class="flavor-historial-ubicacion">📍 <?php echo esc_html($aportacion['compostera']); ?></span>
                    </div>
                    <div class="flavor-historial-peso">
                        <span class="flavor-peso-valor"><?php echo esc_html(number_format($aportacion['peso'], 1)); ?></span>
                        <span class="flavor-peso-unidad">kg</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="#" class="flavor-ver-todo">Ver historial completo →</a>
    </div>

    <!-- Impacto ambiental personal -->
    <div class="flavor-impacto-personal">
        <h3 class="flavor-seccion-titulo">
            <span>🌍</span> Tu impacto ambiental
        </h3>
        <div class="flavor-impacto-visual">
            <div class="flavor-impacto-item">
                <div class="flavor-impacto-circulo">
                    <span class="flavor-impacto-numero"><?php echo esc_html(number_format($estadisticas['co2_evitado'], 0)); ?></span>
                    <span class="flavor-impacto-unidad">kg CO₂</span>
                </div>
                <p>evitado en la atmósfera</p>
            </div>
            <div class="flavor-impacto-item">
                <div class="flavor-impacto-circulo flavor-impacto-arboles">
                    <span class="flavor-impacto-numero"><?php echo esc_html(number_format($estadisticas['arboles_equivalentes'], 1)); ?></span>
                    <span class="flavor-impacto-unidad">árboles</span>
                </div>
                <p>equivalente en absorción anual</p>
            </div>
            <div class="flavor-impacto-item">
                <div class="flavor-impacto-circulo flavor-impacto-compost">
                    <span class="flavor-impacto-numero"><?php echo esc_html(number_format($estadisticas['kg_totales'] * 0.4, 0)); ?></span>
                    <span class="flavor-impacto-unidad">kg compost</span>
                </div>
                <p>generado para huertos</p>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-estadisticas-usuario {
    max-width: 900px;
    margin: 0 auto;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f5f9f5 0%, #fff 100%);
    border-radius: 16px;
}

/* Cabecera del perfil */
.flavor-perfil-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    margin-bottom: 1.5rem;
}

.flavor-perfil-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4caf50, #81c784);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-perfil-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.flavor-avatar-placeholder {
    font-size: 2.5rem;
}

.flavor-perfil-info {
    flex: 1;
}

.flavor-perfil-nombre {
    margin: 0 0 0.25rem;
    font-size: 1.5rem;
    color: #1b5e20;
}

.flavor-perfil-nivel {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    color: #fff;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.flavor-perfil-miembro {
    margin: 0.5rem 0 0;
    color: #757575;
    font-size: 0.9rem;
}

.flavor-perfil-racha {
    text-align: center;
    padding: 1rem;
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    border-radius: 12px;
}

.flavor-racha-numero {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #e65100;
}

.flavor-racha-texto {
    font-size: 0.85rem;
    color: #f57c00;
}

/* Progreso de nivel */
.flavor-progreso-nivel {
    background: #fff;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.flavor-progreso-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: #555;
}

.flavor-progreso-barra {
    height: 12px;
    background: #e8f5e9;
    border-radius: 6px;
    overflow: hidden;
}

.flavor-progreso-relleno {
    height: 100%;
    background: linear-gradient(90deg, #4caf50, #8bc34a);
    border-radius: 6px;
    transition: width 0.5s ease;
}

/* Grid de estadísticas */
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.flavor-stat-card {
    background: #fff;
    padding: 1.25rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.flavor-stat-icono {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.flavor-stat-contenido {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.25rem;
}

.flavor-stat-valor {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2e7d32;
}

.flavor-stat-unidad {
    font-size: 1rem;
    color: #4caf50;
}

.flavor-stat-etiqueta {
    display: block;
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.25rem;
}

.flavor-stat-secundario {
    display: block;
    font-size: 0.8rem;
    color: #999;
    margin-top: 0.25rem;
}

.flavor-stat-co2 .flavor-stat-valor { color: #0277bd; }
.flavor-stat-ranking .flavor-stat-valor { color: #f9a825; }
.flavor-stat-aportaciones .flavor-stat-valor { color: #7b1fa2; }

/* Sección de logros */
.flavor-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.15rem;
    color: #333;
    margin: 0 0 1rem;
}

.flavor-seccion-logros {
    background: #fff;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.flavor-logros-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.flavor-logro {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    position: relative;
    transition: transform 0.2s;
}

.flavor-logro-desbloqueado {
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
}

.flavor-logro-bloqueado {
    background: #f5f5f5;
    opacity: 0.6;
}

.flavor-logro:hover {
    transform: scale(1.05);
}

.flavor-logro-icono {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.flavor-logro-nombre {
    font-size: 0.8rem;
    color: #555;
}

.flavor-logro-candado {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    font-size: 0.9rem;
}

/* Historial */
.flavor-seccion-historial {
    background: #fff;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.flavor-historial-lista {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flavor-historial-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #fafafa;
    border-radius: 10px;
    transition: background 0.2s;
}

.flavor-historial-item:hover {
    background: #f0f7f0;
}

.flavor-historial-fecha {
    width: 50px;
    text-align: center;
    padding: 0.5rem;
    background: #4caf50;
    border-radius: 8px;
    color: #fff;
}

.flavor-fecha-dia {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
}

.flavor-fecha-mes {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
}

.flavor-historial-detalle {
    flex: 1;
}

.flavor-historial-tipo {
    display: block;
    font-weight: 600;
    color: #333;
}

.flavor-historial-ubicacion {
    font-size: 0.85rem;
    color: #757575;
}

.flavor-historial-peso {
    text-align: right;
}

.flavor-peso-valor {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2e7d32;
}

.flavor-peso-unidad {
    font-size: 0.9rem;
    color: #4caf50;
}

.flavor-ver-todo {
    display: block;
    text-align: center;
    margin-top: 1rem;
    color: #4caf50;
    text-decoration: none;
    font-weight: 600;
}

.flavor-ver-todo:hover {
    text-decoration: underline;
}

/* Impacto personal */
.flavor-impacto-personal {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    padding: 1.5rem;
    border-radius: 12px;
}

.flavor-impacto-visual {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    text-align: center;
}

.flavor-impacto-item p {
    margin: 0.5rem 0 0;
    font-size: 0.9rem;
    color: #555;
}

.flavor-impacto-circulo {
    width: 120px;
    height: 120px;
    margin: 0 auto;
    border-radius: 50%;
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3);
}

.flavor-impacto-arboles {
    background: linear-gradient(135deg, #8bc34a, #558b2f);
}

.flavor-impacto-compost {
    background: linear-gradient(135deg, #795548, #5d4037);
}

.flavor-impacto-numero {
    font-size: 1.75rem;
    font-weight: 700;
}

.flavor-impacto-unidad {
    font-size: 0.8rem;
    opacity: 0.9;
}

/* Responsive */
@media (max-width: 900px) {
    .flavor-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-logros-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .flavor-estadisticas-usuario {
        padding: 1rem;
    }

    .flavor-perfil-header {
        flex-direction: column;
        text-align: center;
    }

    .flavor-perfil-racha {
        width: 100%;
    }

    .flavor-stats-grid {
        grid-template-columns: 1fr;
    }

    .flavor-logros-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-impacto-visual {
        grid-template-columns: 1fr;
    }

    .flavor-impacto-circulo {
        width: 100px;
        height: 100px;
    }

    .flavor-historial-item {
        flex-wrap: wrap;
    }
}
</style>

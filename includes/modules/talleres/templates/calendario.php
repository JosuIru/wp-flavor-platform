<?php
/**
 * Template: Calendario de Talleres
 *
 * @package Flavor_Platform
 * @subpackage Modules/Talleres
 */

if (!defined('ABSPATH')) {
    exit;
}

$mes = isset($atts['mes']) ? intval($atts['mes']) : intval(date('m'));
$anio = isset($atts['anio']) ? intval($atts['anio']) : intval(date('Y'));

$primer_dia = mktime(0, 0, 0, $mes, 1, $anio);
$dias_en_mes = date('t', $primer_dia);
$dia_semana_inicio = date('N', $primer_dia);
$nombre_mes = date_i18n('F Y', $primer_dia);

$mes_anterior = $mes - 1;
$anio_anterior = $anio;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $anio_anterior--;
}

$mes_siguiente = $mes + 1;
$anio_siguiente = $anio;
if ($mes_siguiente > 12) {
    $mes_siguiente = 1;
    $anio_siguiente++;
}
?>
<div class="flavor-calendario-talleres">
    <div class="flavor-calendario-header">
        <a href="<?php echo esc_url(add_query_arg(['mes' => $mes_anterior, 'anio' => $anio_anterior])); ?>" class="flavor-calendario-nav">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </a>
        <h2 class="flavor-calendario-titulo"><?php echo esc_html(ucfirst($nombre_mes)); ?></h2>
        <a href="<?php echo esc_url(add_query_arg(['mes' => $mes_siguiente, 'anio' => $anio_siguiente])); ?>" class="flavor-calendario-nav">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
    </div>

    <div class="flavor-calendario-grid">
        <div class="flavor-calendario-dias-semana">
            <span><?php esc_html_e('Lun', 'flavor-platform'); ?></span>
            <span><?php esc_html_e('Mar', 'flavor-platform'); ?></span>
            <span><?php esc_html_e('Mié', 'flavor-platform'); ?></span>
            <span><?php esc_html_e('Jue', 'flavor-platform'); ?></span>
            <span><?php esc_html_e('Vie', 'flavor-platform'); ?></span>
            <span><?php esc_html_e('Sáb', 'flavor-platform'); ?></span>
            <span><?php esc_html_e('Dom', 'flavor-platform'); ?></span>
        </div>

        <div class="flavor-calendario-dias">
            <?php
            // Días vacíos antes del primer día del mes
            for ($i = 1; $i < $dia_semana_inicio; $i++): ?>
                <div class="flavor-calendario-dia flavor-dia-vacio"></div>
            <?php endfor;

            // Días del mes
            $hoy = date('Y-m-d');
            for ($dia = 1; $dia <= $dias_en_mes; $dia++):
                $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                $es_hoy = $fecha === $hoy;
                $es_fin_semana = in_array(date('N', strtotime($fecha)), [6, 7]);
            ?>
                <div class="flavor-calendario-dia<?php echo $es_hoy ? ' flavor-dia-hoy' : ''; ?><?php echo $es_fin_semana ? ' flavor-dia-finde' : ''; ?>">
                    <span class="flavor-dia-numero"><?php echo esc_html($dia); ?></span>
                    <div class="flavor-dia-eventos" data-fecha="<?php echo esc_attr($fecha); ?>">
                        <!-- Los eventos se cargarían via AJAX -->
                    </div>
                </div>
            <?php endfor;

            // Días vacíos después del último día
            $total_celdas = $dia_semana_inicio - 1 + $dias_en_mes;
            $celdas_restantes = 7 - ($total_celdas % 7);
            if ($celdas_restantes < 7):
                for ($i = 0; $i < $celdas_restantes; $i++): ?>
                    <div class="flavor-calendario-dia flavor-dia-vacio"></div>
                <?php endfor;
            endif;
            ?>
        </div>
    </div>

    <div class="flavor-calendario-leyenda">
        <span class="flavor-leyenda-item">
            <span class="flavor-leyenda-color flavor-leyenda-taller"></span>
            <?php esc_html_e('Taller programado', 'flavor-platform'); ?>
        </span>
        <span class="flavor-leyenda-item">
            <span class="flavor-leyenda-color flavor-leyenda-completo"></span>
            <?php esc_html_e('Completo', 'flavor-platform'); ?>
        </span>
    </div>
</div>

<style>
.flavor-calendario-talleres {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 24px;
}
.flavor-calendario-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.flavor-calendario-titulo {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
}
.flavor-calendario-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #f1f5f9;
    border-radius: 8px;
    color: #64748b;
    text-decoration: none;
    transition: background 0.2s;
}
.flavor-calendario-nav:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.flavor-calendario-dias-semana {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.flavor-calendario-dias {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}
.flavor-calendario-dia {
    min-height: 80px;
    padding: 8px;
    background: #f8fafc;
    border-radius: 8px;
    position: relative;
}
.flavor-dia-vacio {
    background: transparent;
}
.flavor-dia-hoy {
    background: #eff6ff;
    border: 2px solid #3b82f6;
}
.flavor-dia-finde {
    background: #fef2f2;
}
.flavor-dia-numero {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
}
.flavor-dia-hoy .flavor-dia-numero {
    color: #3b82f6;
    font-weight: 700;
}
.flavor-dia-eventos {
    margin-top: 4px;
    font-size: 11px;
}
.flavor-calendario-leyenda {
    display: flex;
    gap: 20px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    font-size: 13px;
    color: #64748b;
}
.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.flavor-leyenda-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}
.flavor-leyenda-taller {
    background: #22c55e;
}
.flavor-leyenda-completo {
    background: #ef4444;
}
@media (max-width: 768px) {
    .flavor-calendario-dia {
        min-height: 60px;
        padding: 4px;
    }
    .flavor-dia-numero {
        font-size: 12px;
    }
    .flavor-calendario-dias-semana span {
        font-size: 10px;
    }
}
</style>

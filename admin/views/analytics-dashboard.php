<?php
/**
 * Vista del Dashboard de Analytics
 *
 * @package Flavor_Chat_IA
 * @since 1.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap flavor-analytics-dashboard">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e('Analytics Dashboard', 'flavor-chat-ia'); ?>
    </h1>

    <!-- Controles de período -->
    <div class="flavor-analytics-controls">
        <div class="periodo-selector">
            <label for="periodo-select"><?php esc_html_e('Período:', 'flavor-chat-ia'); ?></label>
            <select id="periodo-select" class="flavor-select">
                <option value="hoy"><?php esc_html_e('Hoy', 'flavor-chat-ia'); ?></option>
                <option value="semana"><?php esc_html_e('Últimos 7 días', 'flavor-chat-ia'); ?></option>
                <option value="mes" selected><?php esc_html_e('Últimos 30 días', 'flavor-chat-ia'); ?></option>
                <option value="ano"><?php esc_html_e('Último año', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <div class="export-buttons">
            <button type="button" class="button" id="btn-export-csv">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Exportar CSV', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="button" id="btn-refresh">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Actualizar', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>

    <!-- Alertas del sistema -->
    <div id="alertas-container" class="flavor-alertas">
        <!-- Se llena vía AJAX -->
    </div>

    <!-- KPIs principales -->
    <div class="flavor-kpis-grid" id="kpis-container">
        <div class="flavor-kpi-card">
            <div class="kpi-icon usuarios">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value" id="kpi-usuarios-activos">-</span>
                <span class="kpi-label"><?php esc_html_e('Usuarios Activos', 'flavor-chat-ia'); ?></span>
                <span class="kpi-secondary" id="kpi-usuarios-total">-</span>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="kpi-icon publicaciones">
                <span class="dashicons dashicons-admin-post"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value" id="kpi-publicaciones">-</span>
                <span class="kpi-label"><?php esc_html_e('Publicaciones', 'flavor-chat-ia'); ?></span>
                <span class="kpi-secondary" id="kpi-comentarios">-</span>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="kpi-icon engagement">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value" id="kpi-engagement">-</span>
                <span class="kpi-label"><?php esc_html_e('Engagement Rate', 'flavor-chat-ia'); ?></span>
                <span class="kpi-secondary" id="kpi-interacciones">-</span>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="kpi-icon nuevos">
                <span class="dashicons dashicons-plus-alt2"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value" id="kpi-nuevos-usuarios">-</span>
                <span class="kpi-label"><?php esc_html_e('Nuevos Usuarios', 'flavor-chat-ia'); ?></span>
                <span class="kpi-secondary" id="kpi-porcentaje-activos">-</span>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="flavor-charts-grid">
        <div class="flavor-chart-card">
            <div class="chart-header">
                <h3><?php esc_html_e('Actividad', 'flavor-chat-ia'); ?></h3>
                <div class="chart-tabs">
                    <button type="button" class="chart-tab active" data-metrica="usuarios">
                        <?php esc_html_e('Usuarios', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="chart-tab" data-metrica="publicaciones">
                        <?php esc_html_e('Publicaciones', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="chart-tab" data-metrica="interacciones">
                        <?php esc_html_e('Interacciones', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="chart-actividad"></canvas>
            </div>
        </div>

        <div class="flavor-chart-card">
            <div class="chart-header">
                <h3><?php esc_html_e('Estadísticas por Módulo', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="chart-container">
                <canvas id="chart-modulos"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas de datos -->
    <div class="flavor-tables-grid">
        <!-- Usuarios más activos -->
        <div class="flavor-table-card">
            <div class="table-header">
                <h3>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Usuarios más activos', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="table-content">
                <table class="wp-list-table widefat fixed striped" id="tabla-usuarios-activos">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Usuario', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Publicaciones', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Comentarios', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Puntos', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="loading-row">
                                <span class="spinner is-active"></span>
                                <?php esc_html_e('Cargando...', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Contenido popular -->
        <div class="flavor-table-card">
            <div class="table-header">
                <h3>
                    <span class="dashicons dashicons-megaphone"></span>
                    <?php esc_html_e('Contenido popular', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="table-content">
                <table class="wp-list-table widefat fixed striped" id="tabla-contenido-popular">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Contenido', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Autor', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Likes', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Comentarios', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="loading-row">
                                <span class="spinner is-active"></span>
                                <?php esc_html_e('Cargando...', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Estadísticas de módulos -->
    <div class="flavor-modules-stats" id="modulos-stats-container">
        <h3>
            <span class="dashicons dashicons-screenoptions"></span>
            <?php esc_html_e('Estadísticas por Módulo', 'flavor-chat-ia'); ?>
        </h3>
        <div class="modules-grid">
            <!-- Se llena vía AJAX -->
            <div class="loading-placeholder">
                <span class="spinner is-active"></span>
                <?php esc_html_e('Cargando estadísticas de módulos...', 'flavor-chat-ia'); ?>
            </div>
        </div>
    </div>
</div>

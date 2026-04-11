/**
 * Visual Builder Pro - Performance Panel
 * Panel de UI para visualizacion de metricas de rendimiento
 *
 * @package Flavor_Platform
 * @since 2.2.0
 */

(function() {
    'use strict';

    /**
     * Componente Alpine para el panel de rendimiento
     */
    window.VBPPerformancePanel = {
        // Estado local del panel
        activeTab: 'overview',
        showMiniChart: true,
        chartType: 'fps',
        animatedScore: 0,

        /**
         * Inicializar panel
         */
        init: function() {
            var self = this;

            // Animar puntuacion al abrir
            this.$watch('$store.vbpPerformance.isPanelOpen', function(isOpen) {
                if (isOpen) {
                    self.animateScore();
                }
            });
        },

        /**
         * Animar puntuacion de rendimiento
         */
        animateScore: function() {
            var self = this;
            var targetScore = this.$store.vbpPerformance.getPerformanceScore();
            var currentScore = 0;
            var duration = 500;
            var startTime = performance.now();

            function animate(currentTime) {
                var elapsed = currentTime - startTime;
                var progress = Math.min(elapsed / duration, 1);

                // Easing
                progress = 1 - Math.pow(1 - progress, 3);

                self.animatedScore = Math.round(progress * targetScore);

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            }

            requestAnimationFrame(animate);
        },

        /**
         * Obtener color segun el valor y umbral
         */
        getValueColor: function(value, warningThreshold, errorThreshold, inverse) {
            inverse = inverse || false;

            if (inverse) {
                if (value <= errorThreshold) return 'error';
                if (value <= warningThreshold) return 'warning';
                return 'success';
            } else {
                if (value >= errorThreshold) return 'error';
                if (value >= warningThreshold) return 'warning';
                return 'success';
            }
        },

        /**
         * Obtener color del FPS
         */
        getFPSColor: function() {
            var fps = this.$store.vbpPerformance.metrics.fps;
            if (fps >= 50) return 'success';
            if (fps >= 30) return 'warning';
            return 'error';
        },

        /**
         * Obtener color del puntaje
         */
        getScoreColor: function() {
            var score = this.$store.vbpPerformance.getPerformanceScore();
            if (score >= 80) return 'success';
            if (score >= 60) return 'warning';
            if (score >= 40) return 'caution';
            return 'error';
        },

        /**
         * Obtener icono de estado
         */
        getHealthIcon: function() {
            var health = this.$store.vbpPerformance.getHealthStatus();
            var icons = {
                excellent: 'check_circle',
                good: 'thumb_up',
                fair: 'warning',
                poor: 'error',
                critical: 'dangerous'
            };
            return icons[health] || 'help';
        },

        /**
         * Renderizar mini grafico SVG
         */
        renderMiniChart: function(metric, width, height) {
            width = width || 120;
            height = height || 40;
            var data = this.$store.vbpPerformance.getChartData(metric, 30);
            var max = Math.max.apply(null, data) || 1;
            var min = Math.min.apply(null, data) || 0;
            var range = max - min || 1;

            var points = [];
            var stepX = width / (data.length - 1);

            for (var i = 0; i < data.length; i++) {
                var x = i * stepX;
                var y = height - ((data[i] - min) / range * height * 0.8 + height * 0.1);
                points.push(x + ',' + y);
            }

            return 'M' + points.join(' L');
        },

        /**
         * Renderizar grafico de area
         */
        renderAreaChart: function(metric, width, height) {
            width = width || 280;
            height = height || 80;
            var data = this.$store.vbpPerformance.getChartData(metric, 60);
            var max = Math.max.apply(null, data) || 1;

            var pathData = 'M0,' + height;
            var stepX = width / (data.length - 1);

            for (var i = 0; i < data.length; i++) {
                var x = i * stepX;
                var y = height - (data[i] / max * height * 0.9 + height * 0.05);
                pathData += ' L' + x + ',' + y;
            }

            pathData += ' L' + width + ',' + height + ' Z';
            return pathData;
        },

        /**
         * Obtener etiqueta del chart
         */
        getChartLabel: function(metric) {
            var labels = {
                fps: 'FPS',
                renderTime: 'Render (ms)',
                elementCount: 'Elementos',
                memoryUsage: 'Memoria (MB)'
            };
            return labels[metric] || metric;
        },

        /**
         * Obtener valor actual para el chart
         */
        getChartCurrentValue: function(metric) {
            var metrics = this.$store.vbpPerformance.metrics;
            switch(metric) {
                case 'fps': return metrics.fps;
                case 'renderTime': return metrics.renderTime.toFixed(1);
                case 'elementCount': return metrics.elementCount;
                case 'memoryUsage': return metrics.memoryUsage;
                default: return 0;
            }
        },

        /**
         * Formatear tiempo relativo
         */
        formatTimeAgo: function(timestamp) {
            var seconds = Math.floor((Date.now() - timestamp) / 1000);
            if (seconds < 60) return 'hace ' + seconds + 's';
            var minutes = Math.floor(seconds / 60);
            if (minutes < 60) return 'hace ' + minutes + 'm';
            var hours = Math.floor(minutes / 60);
            return 'hace ' + hours + 'h';
        },

        /**
         * Cambiar metrica del chart
         */
        setChartType: function(type) {
            this.chartType = type;
        },

        /**
         * Cambiar tab activo
         */
        setActiveTab: function(tab) {
            this.activeTab = tab;
        },

        /**
         * Exportar reporte de rendimiento
         */
        exportReport: function() {
            var data = this.$store.vbpPerformance.exportMetrics();
            var jsonString = JSON.stringify(data, null, 2);
            var blob = new Blob([jsonString], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = 'vbp-performance-report-' + new Date().toISOString().slice(0, 10) + '.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        /**
         * Copiar reporte al portapapeles
         */
        copyReport: function() {
            var data = this.$store.vbpPerformance.exportMetrics();
            var text = this.formatReportAsText(data);

            navigator.clipboard.writeText(text).then(function() {
                // Mostrar notificacion de exito
                if (window.VBPToast) {
                    window.VBPToast.success('Reporte copiado al portapapeles');
                }
            }).catch(function() {
                // Fallback
                var textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            });
        },

        /**
         * Formatear reporte como texto legible
         */
        formatReportAsText: function(data) {
            var lines = [
                '=== VBP Performance Report ===',
                'Fecha: ' + data.exportedAt,
                '',
                '--- Metricas ---',
                'Puntuacion: ' + data.score + '/100 (' + data.health + ')',
                'FPS: ' + data.metrics.fps,
                'Tiempo de render: ' + data.metrics.renderTime + 'ms',
                'Elementos: ' + data.metrics.elementCount,
                'Profundidad: ' + data.metrics.nestingDepth + ' niveles',
                'Memoria: ' + data.metrics.memoryUsage + 'MB',
                'Tamano JSON: ' + this.$store.vbpPerformance.formatBytes(data.metrics.jsonSize),
                '',
                '--- Sesion ---',
                'Guardados: ' + data.sessionStats.totalSaves,
                'Undos: ' + data.sessionStats.totalUndos,
                'Redos: ' + data.sessionStats.totalRedos,
                'Pico elementos: ' + data.sessionStats.peakElementCount,
                'Pico memoria: ' + data.sessionStats.peakMemoryUsage + 'MB',
                'Pico render: ' + data.sessionStats.peakRenderTime.toFixed(1) + 'ms'
            ];

            if (data.warnings && data.warnings.length > 0) {
                lines.push('');
                lines.push('--- Warnings ---');
                for (var i = 0; i < data.warnings.length; i++) {
                    lines.push('[' + data.warnings[i].level.toUpperCase() + '] ' + data.warnings[i].message);
                }
            }

            return lines.join('\n');
        }
    };

    /**
     * HTML Template del panel (para insercion dinamica si es necesario)
     */
    window.VBPPerformancePanelTemplate = function() {
        return /* html */`
<div x-data="VBPPerformancePanel"
     x-show="$store.vbpPerformance.isPanelOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-x-4"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-x-0"
     x-transition:leave-end="opacity-0 translate-x-4"
     class="vbp-performance-panel">

    <!-- Header -->
    <div class="vbp-performance-header">
        <div class="vbp-performance-title">
            <span class="material-icons">speed</span>
            <span>Rendimiento</span>
        </div>
        <button @click="$store.vbpPerformance.closePanel()" class="vbp-performance-close">
            <span class="material-icons">close</span>
        </button>
    </div>

    <!-- Score Card -->
    <div class="vbp-performance-score-card" :class="'health-' + $store.vbpPerformance.getHealthStatus()">
        <div class="vbp-score-circle">
            <svg viewBox="0 0 100 100">
                <circle class="bg" cx="50" cy="50" r="45" />
                <circle class="progress"
                        cx="50" cy="50" r="45"
                        :stroke-dasharray="(animatedScore / 100 * 283) + ' 283'" />
            </svg>
            <div class="vbp-score-value">
                <span x-text="animatedScore"></span>
            </div>
        </div>
        <div class="vbp-score-info">
            <div class="vbp-score-label" x-text="$store.vbpPerformance.getHealthStatus()"></div>
            <div class="vbp-score-description">
                <span x-text="$store.vbpPerformance.warnings.length"></span> warnings activos
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="vbp-performance-tabs">
        <button @click="setActiveTab('overview')" :class="{ active: activeTab === 'overview' }">Vista General</button>
        <button @click="setActiveTab('metrics')" :class="{ active: activeTab === 'metrics' }">Metricas</button>
        <button @click="setActiveTab('warnings')" :class="{ active: activeTab === 'warnings' }">
            Warnings
            <span x-show="$store.vbpPerformance.warnings.length > 0"
                  class="vbp-tab-badge"
                  x-text="$store.vbpPerformance.warnings.length"></span>
        </button>
    </div>

    <!-- Content -->
    <div class="vbp-performance-content">

        <!-- Overview Tab -->
        <div x-show="activeTab === 'overview'" class="vbp-performance-tab-content">
            <!-- Quick Stats -->
            <div class="vbp-quick-stats">
                <div class="vbp-stat-item" :class="getFPSColor()">
                    <div class="vbp-stat-value" x-text="$store.vbpPerformance.metrics.fps"></div>
                    <div class="vbp-stat-label">FPS</div>
                </div>
                <div class="vbp-stat-item" :class="getValueColor($store.vbpPerformance.metrics.elementCount, 500, 1000)">
                    <div class="vbp-stat-value" x-text="$store.vbpPerformance.metrics.elementCount"></div>
                    <div class="vbp-stat-label">Elementos</div>
                </div>
                <div class="vbp-stat-item" :class="getValueColor($store.vbpPerformance.metrics.renderTime, 100, 250)">
                    <div class="vbp-stat-value" x-text="$store.vbpPerformance.metrics.renderTime.toFixed(0) + 'ms'"></div>
                    <div class="vbp-stat-label">Render</div>
                </div>
                <div class="vbp-stat-item" :class="getValueColor($store.vbpPerformance.metrics.memoryUsage, 100, 250)">
                    <div class="vbp-stat-value" x-text="$store.vbpPerformance.metrics.memoryUsage + 'MB'"></div>
                    <div class="vbp-stat-label">Memoria</div>
                </div>
            </div>

            <!-- Mini Chart -->
            <div class="vbp-mini-chart-container">
                <div class="vbp-chart-header">
                    <span class="vbp-chart-title" x-text="getChartLabel(chartType)"></span>
                    <select x-model="chartType" class="vbp-chart-select">
                        <option value="fps">FPS</option>
                        <option value="renderTime">Render Time</option>
                        <option value="elementCount">Elementos</option>
                        <option value="memoryUsage">Memoria</option>
                    </select>
                </div>
                <svg class="vbp-mini-chart" viewBox="0 0 280 80">
                    <path :d="renderAreaChart(chartType, 280, 80)"
                          class="vbp-chart-area"
                          :class="'chart-' + chartType" />
                </svg>
                <div class="vbp-chart-current">
                    <span x-text="getChartCurrentValue(chartType)"></span>
                    <span class="unit" x-text="chartType === 'fps' ? 'fps' : (chartType === 'renderTime' ? 'ms' : (chartType === 'memoryUsage' ? 'MB' : ''))"></span>
                </div>
            </div>

            <!-- Optimization Suggestions -->
            <div class="vbp-suggestions" x-show="$store.vbpPerformance.optimizationSuggestions.length > 0">
                <div class="vbp-suggestions-title">
                    <span class="material-icons">lightbulb</span>
                    Sugerencias de optimizacion
                </div>
                <template x-for="suggestion in $store.vbpPerformance.optimizationSuggestions" :key="suggestion.type">
                    <div class="vbp-suggestion-item" :class="'priority-' + suggestion.priority">
                        <div class="vbp-suggestion-title" x-text="suggestion.title"></div>
                        <div class="vbp-suggestion-desc" x-text="suggestion.description"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Metrics Tab -->
        <div x-show="activeTab === 'metrics'" class="vbp-performance-tab-content">
            <div class="vbp-metrics-grid">
                <!-- Rendimiento -->
                <div class="vbp-metrics-section">
                    <div class="vbp-section-title">Rendimiento</div>
                    <div class="vbp-metric-row">
                        <span class="label">FPS actual</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.fps"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Tiempo de render</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.renderTime.toFixed(2) + ' ms'"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Render promedio</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.averageRenderTime.toFixed(2) + ' ms'"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Tiempo de carga</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.loadTime + ' ms'"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Ultimo guardado</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.lastSaveTime + ' ms'"></span>
                    </div>
                </div>

                <!-- Documento -->
                <div class="vbp-metrics-section">
                    <div class="vbp-section-title">Documento</div>
                    <div class="vbp-metric-row">
                        <span class="label">Total elementos</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.elementCount"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Prof. anidamiento</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.nestingDepth + ' niveles'"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Tamano JSON</span>
                        <span class="value" x-text="$store.vbpPerformance.formatBytes($store.vbpPerformance.metrics.jsonSize)"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Nodos DOM</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.domNodes"></span>
                    </div>
                </div>

                <!-- Sistema -->
                <div class="vbp-metrics-section">
                    <div class="vbp-section-title">Sistema</div>
                    <div class="vbp-metric-row">
                        <span class="label">Memoria usada</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.memoryUsage + ' MB'"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Listeners (est.)</span>
                        <span class="value" x-text="$store.vbpPerformance.metrics.listenerCount"></span>
                    </div>
                </div>

                <!-- Sesion -->
                <div class="vbp-metrics-section">
                    <div class="vbp-section-title">Sesion</div>
                    <div class="vbp-metric-row">
                        <span class="label">Duracion</span>
                        <span class="value" x-text="$store.vbpPerformance.getSessionDuration()"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Guardados</span>
                        <span class="value" x-text="$store.vbpPerformance.sessionStats.totalSaves"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Undos/Redos</span>
                        <span class="value" x-text="$store.vbpPerformance.sessionStats.totalUndos + '/' + $store.vbpPerformance.sessionStats.totalRedos"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Pico elementos</span>
                        <span class="value" x-text="$store.vbpPerformance.sessionStats.peakElementCount"></span>
                    </div>
                    <div class="vbp-metric-row">
                        <span class="label">Pico memoria</span>
                        <span class="value" x-text="$store.vbpPerformance.sessionStats.peakMemoryUsage + ' MB'"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warnings Tab -->
        <div x-show="activeTab === 'warnings'" class="vbp-performance-tab-content">
            <template x-if="$store.vbpPerformance.warnings.length === 0">
                <div class="vbp-warnings-empty">
                    <span class="material-icons">check_circle</span>
                    <p>Sin warnings activos</p>
                    <span class="hint">El rendimiento es optimo</span>
                </div>
            </template>

            <div class="vbp-warnings-list">
                <template x-for="warning in $store.vbpPerformance.warnings" :key="warning.id">
                    <div class="vbp-warning-item" :class="'level-' + warning.level">
                        <span class="material-icons vbp-warning-icon"
                              x-text="warning.level === 'error' ? 'error' : 'warning'"></span>
                        <div class="vbp-warning-content">
                            <div class="vbp-warning-message" x-text="warning.message"></div>
                            <div class="vbp-warning-time" x-text="formatTimeAgo(warning.timestamp)"></div>
                        </div>
                        <button @click="$store.vbpPerformance.removeWarning(warning.id)" class="vbp-warning-dismiss">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                </template>
            </div>

            <div class="vbp-warnings-actions" x-show="$store.vbpPerformance.warnings.length > 0">
                <button @click="$store.vbpPerformance.clearWarnings()" class="vbp-btn-secondary">
                    <span class="material-icons">delete_sweep</span>
                    Limpiar todo
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="vbp-performance-footer">
        <button @click="exportReport()" class="vbp-btn-secondary" title="Exportar reporte JSON">
            <span class="material-icons">download</span>
        </button>
        <button @click="copyReport()" class="vbp-btn-secondary" title="Copiar reporte">
            <span class="material-icons">content_copy</span>
        </button>
        <button @click="$store.vbpPerformance.resetSessionStats()" class="vbp-btn-secondary" title="Resetear estadisticas">
            <span class="material-icons">refresh</span>
        </button>
        <div class="vbp-monitor-toggle">
            <label class="vbp-toggle">
                <input type="checkbox" x-model="$store.vbpPerformance.isEnabled" />
                <span class="slider"></span>
            </label>
            <span>Monitor</span>
        </div>
    </div>
</div>

<!-- Mini Badge (cuando el panel esta cerrado) -->
<div x-data
     x-show="!$store.vbpPerformance.isPanelOpen && $store.vbpPerformance.isEnabled"
     @click="$store.vbpPerformance.openPanel()"
     class="vbp-performance-badge"
     :class="{ 'has-warnings': $store.vbpPerformance.warnings.length > 0 }">
    <span class="vbp-badge-fps" x-text="$store.vbpPerformance.metrics.fps + ' FPS'"></span>
    <span class="vbp-badge-score"
          :class="'score-' + $store.vbpPerformance.getHealthStatus()"
          x-text="$store.vbpPerformance.getPerformanceScore()"></span>
</div>
`;
    };

})();

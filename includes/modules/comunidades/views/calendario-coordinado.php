<?php
/**
 * Vista: Calendario Coordinado de Eventos
 *
 * Muestra eventos de todas las comunidades del usuario + eventos de la red federada
 *
 * Variables disponibles:
 * - $todos_eventos: Array de eventos combinados
 * - $atributos: Atributos del shortcode
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$vista = $atributos['vista'] ?? 'mes';
$mes_actual = date('n');
$anio_actual = date('Y');
?>

<div class="flavor-calendario-coordinado" data-nonce="<?php echo esc_attr(wp_create_nonce('flavor_comunidades_nonce')); ?>">

    <!-- Cabecera -->
    <header class="flavor-calendario-header">
        <h2 class="flavor-calendario-titulo">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php esc_html_e('Calendario de Comunidades', 'flavor-chat-ia'); ?>
        </h2>

        <div class="flavor-calendario-leyenda">
            <span class="flavor-leyenda-item local">
                <span class="flavor-leyenda-dot"></span>
                <?php esc_html_e('Eventos locales', 'flavor-chat-ia'); ?>
            </span>
            <span class="flavor-leyenda-item federado">
                <span class="flavor-leyenda-dot"></span>
                <?php esc_html_e('Red federada', 'flavor-chat-ia'); ?>
            </span>
        </div>
    </header>

    <!-- Navegación del mes -->
    <nav class="flavor-calendario-nav">
        <button type="button" class="flavor-cal-nav-btn" id="mes-anterior" aria-label="<?php esc_attr_e('Mes anterior', 'flavor-chat-ia'); ?>">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>

        <span class="flavor-calendario-mes-actual" id="titulo-mes">
            <?php echo esc_html(date_i18n('F Y')); ?>
        </span>

        <button type="button" class="flavor-cal-nav-btn" id="mes-siguiente" aria-label="<?php esc_attr_e('Mes siguiente', 'flavor-chat-ia'); ?>">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>

        <button type="button" class="flavor-cal-nav-btn flavor-cal-hoy" id="ir-hoy">
            <?php esc_html_e('Hoy', 'flavor-chat-ia'); ?>
        </button>
    </nav>

    <!-- Vista de lista de eventos próximos -->
    <div class="flavor-calendario-contenido">
        <div class="flavor-eventos-lista" id="eventos-lista">
            <?php if (empty($todos_eventos)): ?>
                <div class="flavor-eventos-vacio">
                    <span class="dashicons dashicons-calendar"></span>
                    <h3><?php esc_html_e('No hay eventos próximos', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Los eventos de tus comunidades aparecerán aquí.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $eventos_por_fecha = [];
                foreach ($todos_eventos as $evento) {
                    $fecha_key = date('Y-m-d', strtotime($evento['fecha_inicio']));
                    if (!isset($eventos_por_fecha[$fecha_key])) {
                        $eventos_por_fecha[$fecha_key] = [];
                    }
                    $eventos_por_fecha[$fecha_key][] = $evento;
                }
                ksort($eventos_por_fecha);
                ?>

                <?php foreach ($eventos_por_fecha as $fecha => $eventos_del_dia): ?>
                    <div class="flavor-eventos-dia">
                        <div class="flavor-eventos-dia-header">
                            <span class="flavor-eventos-dia-nombre">
                                <?php echo esc_html(date_i18n('l', strtotime($fecha))); ?>
                            </span>
                            <span class="flavor-eventos-dia-fecha">
                                <?php echo esc_html(date_i18n('j F', strtotime($fecha))); ?>
                            </span>
                        </div>

                        <div class="flavor-eventos-dia-lista">
                            <?php foreach ($eventos_del_dia as $evento): ?>
                                <article class="flavor-evento-card <?php echo esc_attr($evento['origen']); ?>">
                                    <div class="flavor-evento-hora">
                                        <?php
                                        $hora = date('H:i', strtotime($evento['fecha_inicio']));
                                        echo $hora !== '00:00' ? esc_html($hora) : esc_html__('Todo el día', 'flavor-chat-ia');
                                        ?>
                                    </div>

                                    <div class="flavor-evento-info">
                                        <h4 class="flavor-evento-titulo">
                                            <?php if ($evento['origen'] === 'federado' && !empty($evento['url'])): ?>
                                                <a href="<?php echo esc_url($evento['url']); ?>" target="_blank" rel="noopener">
                                                    <?php echo esc_html($evento['titulo']); ?>
                                                    <span class="dashicons dashicons-external"></span>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo esc_url($evento['url'] ?? '#'); ?>">
                                                    <?php echo esc_html($evento['titulo']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </h4>

                                        <?php if (!empty($evento['ubicacion'])): ?>
                                            <div class="flavor-evento-ubicacion">
                                                <span class="dashicons dashicons-location"></span>
                                                <?php echo esc_html($evento['ubicacion']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($evento['origen'] === 'federado' && !empty($evento['nodo_nombre'])): ?>
                                            <div class="flavor-evento-nodo">
                                                <span class="dashicons dashicons-networking"></span>
                                                <?php echo esc_html($evento['nodo_nombre']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flavor-evento-indicador" style="background-color: <?php echo esc_attr($evento['color'] ?? '#3b82f6'); ?>"></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.flavor-calendario-coordinado {
    max-width: 900px;
    margin: 0 auto;
    font-family: var(--gc-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
}

.flavor-calendario-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.flavor-calendario-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-size: 1.5em;
    color: var(--gc-gray-900, #111827);
}

.flavor-calendario-titulo .dashicons {
    color: var(--gc-primary, #2e7d32);
}

.flavor-calendario-leyenda {
    display: flex;
    gap: 16px;
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85em;
    color: var(--gc-gray-600, #4b5563);
}

.flavor-leyenda-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.flavor-leyenda-item.local .flavor-leyenda-dot {
    background: #3b82f6;
}

.flavor-leyenda-item.federado .flavor-leyenda-dot {
    background: #10b981;
}

/* Navegación */
.flavor-calendario-nav {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    padding: 12px 16px;
    background: var(--gc-gray-50, #f9fafb);
    border-radius: var(--gc-border-radius, 12px);
}

.flavor-cal-nav-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    background: white;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: 8px;
    cursor: pointer;
    color: var(--gc-gray-700, #374151);
    transition: all 0.2s;
}

.flavor-cal-nav-btn:hover {
    background: var(--gc-gray-100, #f3f4f6);
    border-color: var(--gc-gray-400, #9ca3af);
}

.flavor-cal-hoy {
    padding: 8px 16px;
    margin-left: auto;
}

.flavor-calendario-mes-actual {
    font-size: 1.2em;
    font-weight: 600;
    color: var(--gc-gray-900, #111827);
    text-transform: capitalize;
}

/* Lista de eventos */
.flavor-eventos-vacio {
    text-align: center;
    padding: 60px 20px;
    background: var(--gc-gray-50, #f9fafb);
    border-radius: var(--gc-border-radius, 12px);
}

.flavor-eventos-vacio .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: var(--gc-gray-400, #9ca3af);
    margin-bottom: 16px;
}

.flavor-eventos-vacio h3 {
    margin: 0 0 8px;
    color: var(--gc-gray-700, #374151);
}

.flavor-eventos-vacio p {
    margin: 0;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-eventos-dia {
    margin-bottom: 24px;
}

.flavor-eventos-dia-header {
    display: flex;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--gc-primary, #2e7d32);
}

.flavor-eventos-dia-nombre {
    font-weight: 600;
    color: var(--gc-gray-900, #111827);
    text-transform: capitalize;
}

.flavor-eventos-dia-fecha {
    color: var(--gc-gray-500, #6b7280);
    font-size: 0.9em;
}

.flavor-eventos-dia-lista {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.flavor-evento-card {
    display: flex;
    gap: 16px;
    padding: 16px;
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.flavor-evento-card:hover {
    box-shadow: var(--gc-shadow, 0 4px 6px -1px rgba(0,0,0,0.1));
}

.flavor-evento-indicador {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}

.flavor-evento-hora {
    min-width: 60px;
    font-weight: 600;
    color: var(--gc-gray-700, #374151);
    font-size: 0.95em;
}

.flavor-evento-info {
    flex: 1;
}

.flavor-evento-titulo {
    margin: 0 0 6px;
    font-size: 1em;
}

.flavor-evento-titulo a {
    color: var(--gc-gray-900, #111827);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.flavor-evento-titulo a:hover {
    color: var(--gc-primary, #2e7d32);
}

.flavor-evento-titulo .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: var(--gc-gray-400, #9ca3af);
}

.flavor-evento-ubicacion,
.flavor-evento-nodo {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85em;
    color: var(--gc-gray-500, #6b7280);
    margin-top: 4px;
}

.flavor-evento-ubicacion .dashicons,
.flavor-evento-nodo .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-evento-nodo {
    color: var(--gc-primary, #2e7d32);
}

.flavor-evento-card.federado {
    border-left: 4px solid #10b981;
}

.flavor-evento-card.local {
    border-left: 4px solid #3b82f6;
}

@media (max-width: 600px) {
    .flavor-calendario-header {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-calendario-nav {
        flex-wrap: wrap;
    }

    .flavor-evento-card {
        flex-direction: column;
        gap: 8px;
    }

    .flavor-evento-hora {
        min-width: auto;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var contenedor = document.querySelector('.flavor-calendario-coordinado');
        if (!contenedor) return;

        var mesActual = <?php echo intval($mes_actual); ?>;
        var anioActual = <?php echo intval($anio_actual); ?>;
        var nonce = contenedor.dataset.nonce;
        var tituloMes = document.getElementById('titulo-mes');

        var meses = [
            '<?php echo esc_js(__('Enero', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Febrero', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Marzo', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Abril', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Mayo', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Junio', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Julio', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Agosto', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Septiembre', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Octubre', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Noviembre', 'flavor-chat-ia')); ?>',
            '<?php echo esc_js(__('Diciembre', 'flavor-chat-ia')); ?>'
        ];

        function actualizarTitulo() {
            tituloMes.textContent = meses[mesActual - 1] + ' ' + anioActual;
        }

        function cargarEventos() {
            var formData = new FormData();
            formData.append('action', 'comunidades_calendario_eventos');
            formData.append('mes', mesActual);
            formData.append('anio', anioActual);
            formData.append('incluir_red', 'true');

            fetch(flavorComunidadesConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    // Actualizar lista de eventos (simplificado)
                    console.log('Eventos cargados:', data.data.eventos);
                }
            });
        }

        document.getElementById('mes-anterior').addEventListener('click', function() {
            mesActual--;
            if (mesActual < 1) {
                mesActual = 12;
                anioActual--;
            }
            actualizarTitulo();
            cargarEventos();
        });

        document.getElementById('mes-siguiente').addEventListener('click', function() {
            mesActual++;
            if (mesActual > 12) {
                mesActual = 1;
                anioActual++;
            }
            actualizarTitulo();
            cargarEventos();
        });

        document.getElementById('ir-hoy').addEventListener('click', function() {
            mesActual = new Date().getMonth() + 1;
            anioActual = new Date().getFullYear();
            actualizarTitulo();
            cargarEventos();
        });
    });
})();
</script>

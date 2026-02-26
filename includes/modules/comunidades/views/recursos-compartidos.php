<?php
/**
 * Vista: Recursos Compartidos entre Comunidades
 *
 * Muestra recursos (recetas, biblioteca, multimedia, etc.) compartidos entre comunidades
 *
 * Variables disponibles:
 * - $recursos: Array de recursos combinados
 * - $atributos: Atributos del shortcode
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas = intval($atributos['columnas'] ?? 4);
$tipos_permitidos = array_map('trim', explode(',', $atributos['tipos'] ?? 'recetas,biblioteca,multimedia'));

// Iconos y labels por tipo
$tipo_config = [
    'recetas' => [
        'icon'  => 'dashicons-carrot',
        'label' => __('Receta', 'flavor-chat-ia'),
        'color' => '#f59e0b',
    ],
    'biblioteca' => [
        'icon'  => 'dashicons-book',
        'label' => __('Documento', 'flavor-chat-ia'),
        'color' => '#3b82f6',
    ],
    'multimedia' => [
        'icon'  => 'dashicons-format-gallery',
        'label' => __('Multimedia', 'flavor-chat-ia'),
        'color' => '#8b5cf6',
    ],
    'podcast' => [
        'icon'  => 'dashicons-microphone',
        'label' => __('Podcast', 'flavor-chat-ia'),
        'color' => '#ec4899',
    ],
    'videos' => [
        'icon'  => 'dashicons-video-alt3',
        'label' => __('Video', 'flavor-chat-ia'),
        'color' => '#ef4444',
    ],
];
?>

<div class="flavor-recursos-compartidos">

    <!-- Cabecera -->
    <header class="flavor-recursos-header">
        <h2 class="flavor-recursos-titulo">
            <span class="dashicons dashicons-share-alt2"></span>
            <?php esc_html_e('Recursos Compartidos', 'flavor-chat-ia'); ?>
        </h2>

        <!-- Filtros por tipo -->
        <div class="flavor-recursos-filtros">
            <button type="button" class="flavor-filtro-btn activo" data-tipo="todos">
                <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
            </button>
            <?php foreach ($tipos_permitidos as $tipo): ?>
                <?php if (isset($tipo_config[$tipo])): ?>
                <button type="button" class="flavor-filtro-btn" data-tipo="<?php echo esc_attr($tipo); ?>">
                    <span class="<?php echo esc_attr($tipo_config[$tipo]['icon']); ?>"></span>
                    <?php echo esc_html($tipo_config[$tipo]['label']); ?>
                </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </header>

    <!-- Grid de recursos -->
    <div class="flavor-recursos-contenido">
        <?php if (empty($recursos)): ?>
            <div class="flavor-recursos-vacio">
                <span class="dashicons dashicons-portfolio"></span>
                <h3><?php esc_html_e('No hay recursos disponibles', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Los recursos compartidos aparecerán aquí.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php else: ?>
            <div class="flavor-recursos-grid" style="--columnas: <?php echo esc_attr($columnas); ?>;">
                <?php foreach ($recursos as $recurso):
                    $tipo = $recurso->tipo ?? $recurso->tipo_contenido ?? 'biblioteca';
                    $config = $tipo_config[$tipo] ?? $tipo_config['biblioteca'];
                    $es_federado = ($recurso->origen ?? 'local') === 'federado';
                    $url = $es_federado ? ($recurso->url_externa ?? '#') : ($recurso->url ?? '#');
                    $imagen = $recurso->imagen ?? $recurso->imagen_url ?? '';
                ?>
                <article class="flavor-recurso-card" data-tipo="<?php echo esc_attr($tipo); ?>" data-origen="<?php echo esc_attr($recurso->origen ?? 'local'); ?>">

                    <!-- Imagen o placeholder -->
                    <div class="flavor-recurso-imagen">
                        <?php if ($imagen): ?>
                            <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($recurso->titulo ?? ''); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="flavor-recurso-imagen-placeholder" style="background-color: <?php echo esc_attr($config['color']); ?>20;">
                                <span class="<?php echo esc_attr($config['icon']); ?>" style="color: <?php echo esc_attr($config['color']); ?>;"></span>
                            </div>
                        <?php endif; ?>

                        <!-- Badge de tipo -->
                        <span class="flavor-recurso-tipo" style="background-color: <?php echo esc_attr($config['color']); ?>;">
                            <span class="<?php echo esc_attr($config['icon']); ?>"></span>
                            <?php echo esc_html($config['label']); ?>
                        </span>

                        <!-- Badge federado -->
                        <?php if ($es_federado): ?>
                        <span class="flavor-recurso-federado" title="<?php esc_attr_e('De la red federada', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-networking"></span>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="flavor-recurso-info">
                        <h4 class="flavor-recurso-titulo">
                            <?php echo esc_html($recurso->titulo ?? ''); ?>
                        </h4>

                        <?php if (!empty($recurso->descripcion)): ?>
                        <p class="flavor-recurso-descripcion">
                            <?php echo esc_html(wp_trim_words($recurso->descripcion, 15)); ?>
                        </p>
                        <?php endif; ?>

                        <div class="flavor-recurso-meta">
                            <?php if (!empty($recurso->autor)): ?>
                            <span class="flavor-recurso-autor">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo esc_html($recurso->autor); ?>
                            </span>
                            <?php endif; ?>

                            <?php if ($es_federado && !empty($recurso->nodo_nombre)): ?>
                            <span class="flavor-recurso-nodo">
                                <span class="dashicons dashicons-admin-site"></span>
                                <?php echo esc_html($recurso->nodo_nombre); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="flavor-recurso-acciones">
                        <a href="<?php echo esc_url($url); ?>"
                           class="flavor-btn-ver"
                           <?php echo $es_federado ? 'target="_blank" rel="noopener"' : ''; ?>>
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                            <?php if ($es_federado): ?>
                                <span class="dashicons dashicons-external"></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.flavor-recursos-compartidos {
    max-width: 1200px;
    margin: 0 auto;
    font-family: var(--gc-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
}

.flavor-recursos-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.flavor-recursos-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-size: 1.5em;
    color: var(--gc-gray-900, #111827);
}

.flavor-recursos-titulo .dashicons {
    color: var(--gc-primary, #2e7d32);
}

.flavor-recursos-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-filtro-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: white;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.85em;
    color: var(--gc-gray-600, #4b5563);
    transition: all 0.2s;
}

.flavor-filtro-btn:hover {
    border-color: var(--gc-primary, #2e7d32);
    color: var(--gc-primary, #2e7d32);
}

.flavor-filtro-btn.activo {
    background: var(--gc-primary, #2e7d32);
    border-color: var(--gc-primary, #2e7d32);
    color: white;
}

.flavor-filtro-btn .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Vacío */
.flavor-recursos-vacio {
    text-align: center;
    padding: 60px 20px;
    background: var(--gc-gray-50, #f9fafb);
    border-radius: var(--gc-border-radius, 12px);
}

.flavor-recursos-vacio .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: var(--gc-gray-400, #9ca3af);
    margin-bottom: 16px;
}

.flavor-recursos-vacio h3 {
    margin: 0 0 8px;
    color: var(--gc-gray-700, #374151);
}

.flavor-recursos-vacio p {
    margin: 0;
    color: var(--gc-gray-500, #6b7280);
}

/* Grid */
.flavor-recursos-grid {
    display: grid;
    grid-template-columns: repeat(var(--columnas, 4), 1fr);
    gap: 20px;
}

.flavor-recurso-card {
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
    overflow: hidden;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
}

.flavor-recurso-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--gc-shadow-lg, 0 10px 15px -3px rgba(0,0,0,0.1));
}

.flavor-recurso-card.oculto {
    display: none;
}

/* Imagen */
.flavor-recurso-imagen {
    position: relative;
    height: 140px;
    overflow: hidden;
}

.flavor-recurso-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-recurso-imagen-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-recurso-imagen-placeholder .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
}

.flavor-recurso-tipo {
    position: absolute;
    top: 10px;
    left: 10px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    color: white;
    border-radius: 4px;
    font-size: 0.7em;
    font-weight: 600;
    text-transform: uppercase;
}

.flavor-recurso-tipo .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.flavor-recurso-federado {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-recurso-federado .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: var(--gc-primary, #2e7d32);
}

/* Info */
.flavor-recurso-info {
    padding: 16px;
    flex: 1;
}

.flavor-recurso-titulo {
    margin: 0 0 8px;
    font-size: 1em;
    color: var(--gc-gray-900, #111827);
    line-height: 1.3;
}

.flavor-recurso-descripcion {
    margin: 0 0 10px;
    font-size: 0.85em;
    color: var(--gc-gray-500, #6b7280);
    line-height: 1.4;
}

.flavor-recurso-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.8em;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-recurso-autor,
.flavor-recurso-nodo {
    display: flex;
    align-items: center;
    gap: 4px;
}

.flavor-recurso-meta .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.flavor-recurso-nodo {
    color: var(--gc-primary, #2e7d32);
}

/* Acciones */
.flavor-recurso-acciones {
    padding: 12px 16px;
    border-top: 1px solid var(--gc-gray-100, #f3f4f6);
}

.flavor-btn-ver {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: var(--gc-primary, #2e7d32);
    color: white;
    border-radius: var(--gc-button-radius, 6px);
    text-decoration: none;
    font-size: 0.9em;
    font-weight: 500;
    transition: all 0.2s;
}

.flavor-btn-ver:hover {
    background: var(--gc-primary-dark, #1b5e20);
    color: white;
}

.flavor-btn-ver .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

@media (max-width: 900px) {
    .flavor-recursos-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .flavor-recursos-header {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-recursos-filtros {
        justify-content: center;
    }

    .flavor-recursos-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var contenedor = document.querySelector('.flavor-recursos-compartidos');
        if (!contenedor) return;

        var botonesFiltro = contenedor.querySelectorAll('.flavor-filtro-btn');
        var cards = contenedor.querySelectorAll('.flavor-recurso-card');

        botonesFiltro.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tipo = btn.dataset.tipo;

                // Actualizar botones activos
                botonesFiltro.forEach(function(b) {
                    b.classList.remove('activo');
                });
                btn.classList.add('activo');

                // Filtrar cards
                cards.forEach(function(card) {
                    if (tipo === 'todos' || card.dataset.tipo === tipo) {
                        card.classList.remove('oculto');
                    } else {
                        card.classList.add('oculto');
                    }
                });
            });
        });
    });
})();
</script>

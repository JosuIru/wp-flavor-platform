<?php
/**
 * Template: Tablero de Trueques / Cestas de Trueque
 *
 * @package FlavorPlatform
 * @subpackage GruposConsumo
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $trueques Lista de trueques activos
 * @var array $atts Atributos del shortcode
 * @var array $filtros Filtros aplicados
 */

if (!defined('ABSPATH')) {
    exit;
}

$nonce = wp_create_nonce('gc_conciencia_nonce');
$usuario_logueado = is_user_logged_in();
$usuario_actual = get_current_user_id();

$tipos_labels = [
    'trueque'  => __('Trueque', 'flavor-platform'),
    'regalo'   => __('Regalo', 'flavor-platform'),
    'prestamo' => __('Préstamo', 'flavor-platform'),
];

$tipos_icons = [
    'trueque'  => 'randomize',
    'regalo'   => 'heart',
    'prestamo' => 'backup',
];

$tipos_colors = [
    'trueque'  => '#1976d2',
    'regalo'   => '#c2185b',
    'prestamo' => '#7b1fa2',
];
?>

<div class="gc-trueques-notice" id="gc-trueques-notice" style="display:none;"></div>

<div class="gc-trueques" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="gc-trueques__header">
        <h3 class="gc-trueques__titulo">
            <span class="dashicons dashicons-randomize"></span>
            <?php esc_html_e('Cestas de Trueque', 'flavor-platform'); ?>
        </h3>
        <p class="gc-trueques__descripcion">
            <?php esc_html_e('Intercambia productos con otros miembros de la comunidad. Sin dinero, solo solidaridad.', 'flavor-platform'); ?>
        </p>
    </div>

    <!-- Filtros -->
    <div class="gc-trueques__filtros">
        <div class="gc-trueques__busqueda">
            <span class="dashicons dashicons-search"></span>
            <input
                type="text"
                id="gc-trueque-buscar"
                placeholder="<?php esc_attr_e('Buscar productos...', 'flavor-platform'); ?>"
                class="gc-trueques__input"
            >
        </div>

        <div class="gc-trueques__tipos">
            <button type="button" class="gc-trueques__tipo-btn active" data-tipo="">
                <?php esc_html_e('Todos', 'flavor-platform'); ?>
            </button>
            <?php foreach ($tipos_labels as $tipo => $label): ?>
                <button
                    type="button"
                    class="gc-trueques__tipo-btn"
                    data-tipo="<?php echo esc_attr($tipo); ?>"
                    style="--tipo-color: <?php echo esc_attr($tipos_colors[$tipo]); ?>"
                >
                    <span class="dashicons dashicons-<?php echo esc_attr($tipos_icons[$tipo]); ?>"></span>
                    <?php echo esc_html($label); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if ($usuario_logueado): ?>
            <button type="button" id="gc-nuevo-trueque-btn" class="gc-btn gc-btn--primary">
                <span class="dashicons dashicons-plus"></span>
                <?php esc_html_e('Publicar oferta', 'flavor-platform'); ?>
            </button>
        <?php endif; ?>
    </div>

    <!-- Lista de trueques -->
    <div class="gc-trueques__lista" id="gc-trueques-lista">
        <?php if (empty($trueques)): ?>
            <div class="gc-trueques__vacio">
                <span class="dashicons dashicons-coffee"></span>
                <p><?php esc_html_e('No hay ofertas de trueque activas. ¡Sé el primero en publicar!', 'flavor-platform'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($trueques as $trueque): ?>
                <?php
                $tipo = $trueque['tipo'] ?? 'trueque';
                $es_propio = ($trueque['usuario_ofrece_id'] == $usuario_actual);
                $productos = is_array($trueque['productos_ofrecidos']) ? $trueque['productos_ofrecidos'] : [$trueque['productos_ofrecidos']];
                $deseados = $trueque['productos_deseados'] ?? null;
                $tiempo_restante = '';
                if (!empty($trueque['fecha_expiracion'])) {
                    $expira = strtotime($trueque['fecha_expiracion']);
                    $ahora = current_time('timestamp');
                    $diff = $expira - $ahora;
                    if ($diff > 0) {
                        $dias = floor($diff / 86400);
                        $tiempo_restante = $dias > 0 ? sprintf(_n('%d día', '%d días', $dias, 'flavor-platform'), $dias) : __('Hoy', 'flavor-platform');
                    }
                }
                ?>
                <article
                    class="gc-trueque-card <?php echo $es_propio ? 'gc-trueque-card--propio' : ''; ?>"
                    data-trueque-id="<?php echo esc_attr($trueque['id']); ?>"
                    data-tipo="<?php echo esc_attr($tipo); ?>"
                >
                    <div class="gc-trueque-card__tipo" style="background: <?php echo esc_attr($tipos_colors[$tipo]); ?>">
                        <span class="dashicons dashicons-<?php echo esc_attr($tipos_icons[$tipo]); ?>"></span>
                        <?php echo esc_html($tipos_labels[$tipo]); ?>
                    </div>

                    <div class="gc-trueque-card__contenido">
                        <h4 class="gc-trueque-card__titulo"><?php echo esc_html($trueque['titulo']); ?></h4>

                        <div class="gc-trueque-card__usuario">
                            <?php echo get_avatar($trueque['usuario_ofrece_id'], 24); ?>
                            <span><?php echo esc_html($trueque['nombre_usuario']); ?></span>
                            <?php if ($es_propio): ?>
                                <span class="gc-trueque-card__badge gc-trueque-card__badge--tuyo"><?php esc_html_e('Tu oferta', 'flavor-platform'); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="gc-trueque-card__productos">
                            <div class="gc-trueque-card__ofrece">
                                <strong><?php esc_html_e('Ofrece:', 'flavor-platform'); ?></strong>
                                <ul>
                                    <?php foreach ((array)$productos as $producto): ?>
                                        <li><?php echo esc_html(is_array($producto) ? ($producto['nombre'] ?? '') : $producto); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <?php if ($tipo === 'trueque' && $deseados): ?>
                                <div class="gc-trueque-card__busca">
                                    <strong><?php esc_html_e('Busca:', 'flavor-platform'); ?></strong>
                                    <ul>
                                        <?php foreach ((array)$deseados as $deseado): ?>
                                            <li><?php echo esc_html(is_array($deseado) ? ($deseado['nombre'] ?? '') : $deseado); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($trueque['descripcion'])): ?>
                            <p class="gc-trueque-card__descripcion">
                                <?php echo esc_html(wp_trim_words($trueque['descripcion'], 20)); ?>
                            </p>
                        <?php endif; ?>

                        <div class="gc-trueque-card__meta">
                            <?php if ($tiempo_restante): ?>
                                <span class="gc-trueque-card__expira">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($tiempo_restante); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($trueque['ubicacion_intercambio'])): ?>
                                <span class="gc-trueque-card__ubicacion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($trueque['ubicacion_intercambio']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($trueque['valor_estimado'])): ?>
                                <span class="gc-trueque-card__valor">
                                    ~<?php echo esc_html(number_format($trueque['valor_estimado'], 2)); ?>&euro;
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="gc-trueque-card__acciones">
                        <?php if ($usuario_logueado && !$es_propio): ?>
                            <button type="button" class="gc-btn gc-btn--primary gc-contactar-btn" data-trueque-id="<?php echo esc_attr($trueque['id']); ?>">
                                <span class="dashicons dashicons-email"></span>
                                <?php esc_html_e('Contactar', 'flavor-platform'); ?>
                            </button>
                        <?php elseif ($es_propio): ?>
                            <button type="button" class="gc-btn gc-btn--outline gc-completar-btn" data-trueque-id="<?php echo esc_attr($trueque['id']); ?>">
                                <span class="dashicons dashicons-yes"></span>
                                <?php esc_html_e('Completar', 'flavor-platform'); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url(wp_login_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', ''))); ?>" class="gc-btn gc-btn--outline">
                                <?php esc_html_e('Iniciar sesión', 'flavor-platform'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal nuevo trueque -->
    <?php if ($usuario_logueado): ?>
        <div id="gc-modal-trueque" class="gc-modal" style="display: none;">
            <div class="gc-modal__overlay"></div>
            <div class="gc-modal__contenido">
                <button type="button" class="gc-modal__cerrar">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>

                <h3 class="gc-modal__titulo"><?php esc_html_e('Publicar oferta', 'flavor-platform'); ?></h3>

                <form id="gc-form-trueque" class="gc-form">
                    <div class="gc-form__grupo">
                        <label for="gc-trueque-tipo"><?php esc_html_e('Tipo de oferta', 'flavor-platform'); ?></label>
                        <select id="gc-trueque-tipo" name="tipo" required>
                            <option value="trueque"><?php esc_html_e('Trueque', 'flavor-platform'); ?></option>
                            <option value="regalo"><?php esc_html_e('Regalo', 'flavor-platform'); ?></option>
                            <option value="prestamo"><?php esc_html_e('Préstamo', 'flavor-platform'); ?></option>
                        </select>
                    </div>

                    <div class="gc-form__grupo">
                        <label for="gc-trueque-titulo"><?php esc_html_e('Título', 'flavor-platform'); ?></label>
                        <input type="text" id="gc-trueque-titulo" name="titulo" required maxlength="100" placeholder="<?php esc_attr_e('Ej: Huevos de gallinas felices', 'flavor-platform'); ?>">
                    </div>

                    <div class="gc-form__grupo">
                        <label for="gc-trueque-productos"><?php esc_html_e('Productos que ofreces', 'flavor-platform'); ?></label>
                        <textarea id="gc-trueque-productos" name="productos_ofrecidos" required rows="3" placeholder="<?php esc_attr_e('Una línea por producto...', 'flavor-platform'); ?>"></textarea>
                        <small><?php esc_html_e('Escribe cada producto en una línea', 'flavor-platform'); ?></small>
                    </div>

                    <div class="gc-form__grupo gc-form__grupo--deseados" id="gc-grupo-deseados">
                        <label for="gc-trueque-deseados"><?php esc_html_e('Productos que buscas', 'flavor-platform'); ?></label>
                        <textarea id="gc-trueque-deseados" name="productos_deseados" rows="2" placeholder="<?php esc_attr_e('Opcional: lo que te gustaría recibir a cambio...', 'flavor-platform'); ?>"></textarea>
                    </div>

                    <div class="gc-form__grupo">
                        <label for="gc-trueque-descripcion"><?php esc_html_e('Descripción', 'flavor-platform'); ?></label>
                        <textarea id="gc-trueque-descripcion" name="descripcion" rows="2" placeholder="<?php esc_attr_e('Detalles adicionales...', 'flavor-platform'); ?>"></textarea>
                    </div>

                    <div class="gc-form__fila">
                        <div class="gc-form__grupo">
                            <label for="gc-trueque-ubicacion"><?php esc_html_e('Lugar de intercambio', 'flavor-platform'); ?></label>
                            <input type="text" id="gc-trueque-ubicacion" name="ubicacion" placeholder="<?php esc_attr_e('Ej: Centro social', 'flavor-platform'); ?>">
                        </div>

                        <div class="gc-form__grupo">
                            <label for="gc-trueque-vigencia"><?php esc_html_e('Vigencia (días)', 'flavor-platform'); ?></label>
                            <input type="number" id="gc-trueque-vigencia" name="dias_vigencia" min="1" max="90" value="30">
                        </div>
                    </div>

                    <div class="gc-form__acciones">
                        <button type="button" class="gc-btn gc-btn--outline gc-modal__cancelar"><?php esc_html_e('Cancelar', 'flavor-platform'); ?></button>
                        <button type="submit" class="gc-btn gc-btn--primary"><?php esc_html_e('Publicar', 'flavor-platform'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal contactar -->
        <div id="gc-modal-contactar" class="gc-modal" style="display: none;">
            <div class="gc-modal__overlay"></div>
            <div class="gc-modal__contenido">
                <button type="button" class="gc-modal__cerrar">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>

                <h3 class="gc-modal__titulo"><?php esc_html_e('Enviar mensaje', 'flavor-platform'); ?></h3>

                <form id="gc-form-contactar" class="gc-form">
                    <input type="hidden" name="trueque_id" id="gc-contactar-trueque-id">

                    <div class="gc-form__grupo">
                        <label for="gc-contactar-mensaje"><?php esc_html_e('Tu mensaje', 'flavor-platform'); ?></label>
                        <textarea id="gc-contactar-mensaje" name="mensaje" required rows="4" placeholder="<?php esc_attr_e('Hola, me interesa tu oferta...', 'flavor-platform'); ?>"></textarea>
                    </div>

                    <div class="gc-form__acciones">
                        <button type="button" class="gc-btn gc-btn--outline gc-modal__cancelar"><?php esc_html_e('Cancelar', 'flavor-platform'); ?></button>
                        <button type="submit" class="gc-btn gc-btn--primary"><?php esc_html_e('Enviar', 'flavor-platform'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.gc-trueques-notice {
    display: none;
    margin: 0 0 16px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.95rem;
}

.gc-trueques-notice.error {
    display: block;
    background: #fee2e2;
    color: #991b1b;
}

.gc-trueques-notice.success {
    display: block;
    background: #dcfce7;
    color: #166534;
}

.gc-trueques {
    --gc-primary: #1976d2;
    --gc-success: #2e7d32;
    --gc-text: #333;
    --gc-text-light: #666;
    --gc-border: #e0e0e0;
    --gc-radius: 12px;
    margin: 2rem 0;
}

.gc-trueques__header {
    text-align: center;
    margin-bottom: 2rem;
}

.gc-trueques__titulo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    color: var(--gc-primary);
    margin: 0 0 0.5rem;
}

.gc-trueques__descripcion {
    color: var(--gc-text-light);
    max-width: 600px;
    margin: 0 auto;
}

.gc-trueques__filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f5f5f5;
    border-radius: var(--gc-radius);
}

.gc-trueques__busqueda {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 200px;
    background: #fff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: 1px solid var(--gc-border);
}

.gc-trueques__busqueda .dashicons {
    color: #999;
}

.gc-trueques__input {
    border: none;
    flex: 1;
    font-size: 0.95rem;
}

.gc-trueques__input:focus {
    outline: none;
}

.gc-trueques__tipos {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.gc-trueques__tipo-btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--gc-border);
    border-radius: 20px;
    background: #fff;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s;
}

.gc-trueques__tipo-btn:hover,
.gc-trueques__tipo-btn.active {
    background: var(--tipo-color, var(--gc-primary));
    color: #fff;
    border-color: var(--tipo-color, var(--gc-primary));
}

.gc-trueques__tipo-btn .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

.gc-trueques__lista {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.gc-trueques__vacio {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem 2rem;
    background: #f5f5f5;
    border-radius: var(--gc-radius);
}

.gc-trueques__vacio .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    color: #ccc;
}

.gc-trueque-card {
    background: #fff;
    border: 1px solid var(--gc-border);
    border-radius: var(--gc-radius);
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}

.gc-trueque-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.gc-trueque-card--propio {
    border-color: var(--gc-primary);
    border-width: 2px;
}

.gc-trueque-card__tipo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.gc-trueque-card__tipo .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

.gc-trueque-card__contenido {
    padding: 1rem;
}

.gc-trueque-card__titulo {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
    color: var(--gc-text);
}

.gc-trueque-card__usuario {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: var(--gc-text-light);
}

.gc-trueque-card__usuario img {
    border-radius: 50%;
}

.gc-trueque-card__badge {
    padding: 0.15rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

.gc-trueque-card__badge--tuyo {
    background: #e3f2fd;
    color: var(--gc-primary);
}

.gc-trueque-card__productos {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.gc-trueque-card__ofrece,
.gc-trueque-card__busca {
    flex: 1;
}

.gc-trueque-card__ofrece strong,
.gc-trueque-card__busca strong {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #999;
    margin-bottom: 0.25rem;
}

.gc-trueque-card__productos ul {
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 0.9rem;
}

.gc-trueque-card__productos li {
    padding: 0.15rem 0;
}

.gc-trueque-card__descripcion {
    font-size: 0.85rem;
    color: var(--gc-text-light);
    margin: 0 0 0.75rem;
}

.gc-trueque-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.8rem;
    color: #999;
}

.gc-trueque-card__meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.gc-trueque-card__meta .dashicons {
    font-size: 0.9rem;
    width: 0.9rem;
    height: 0.9rem;
}

.gc-trueque-card__valor {
    background: #e8f5e9;
    color: var(--gc-success);
    padding: 0.15rem 0.5rem;
    border-radius: 10px;
    font-weight: 500;
}

.gc-trueque-card__acciones {
    padding: 0.75rem 1rem;
    background: #fafafa;
    border-top: 1px solid var(--gc-border);
}

/* Botones */
.gc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.gc-btn--primary {
    background: var(--gc-primary);
    color: #fff;
}

.gc-btn--primary:hover {
    background: #1565c0;
}

.gc-btn--outline {
    background: transparent;
    border: 1px solid var(--gc-border);
    color: var(--gc-text);
}

.gc-btn--outline:hover {
    background: #f5f5f5;
}

/* Modal */
.gc-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gc-modal__overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.gc-modal__contenido {
    position: relative;
    background: #fff;
    padding: 2rem;
    border-radius: var(--gc-radius);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.gc-modal__cerrar {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    cursor: pointer;
    color: #999;
}

.gc-modal__cerrar:hover {
    color: #333;
}

.gc-modal__titulo {
    margin: 0 0 1.5rem;
    font-size: 1.25rem;
}

/* Formulario */
.gc-form__grupo {
    margin-bottom: 1rem;
}

.gc-form__grupo label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: var(--gc-text);
}

.gc-form__grupo input,
.gc-form__grupo select,
.gc-form__grupo textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--gc-border);
    border-radius: 6px;
    font-size: 0.95rem;
}

.gc-form__grupo input:focus,
.gc-form__grupo select:focus,
.gc-form__grupo textarea:focus {
    outline: none;
    border-color: var(--gc-primary);
}

.gc-form__grupo small {
    color: #999;
    font-size: 0.8rem;
}

.gc-form__fila {
    display: flex;
    gap: 1rem;
}

.gc-form__fila .gc-form__grupo {
    flex: 1;
}

.gc-form__acciones {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.5rem;
}

@media (max-width: 600px) {
    .gc-trueques__filtros {
        flex-direction: column;
    }

    .gc-trueques__busqueda {
        width: 100%;
    }

    .gc-trueques__lista {
        grid-template-columns: 1fr;
    }

    .gc-form__fila {
        flex-direction: column;
    }

    .gc-trueque-card__productos {
        flex-direction: column;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.gc-trueques');
        if (!container) return;
        const notice = document.getElementById('gc-trueques-notice');

        const nonce = container.dataset.nonce;
        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';

        function mostrarAviso(mensaje, tipo) {
            if (!notice) return;
            notice.className = 'gc-trueques-notice ' + (tipo || 'error');
            notice.textContent = mensaje;
            notice.style.display = 'block';
        }

        // Filtro por tipo
        const tipoBtns = container.querySelectorAll('.gc-trueques__tipo-btn');
        const cards = container.querySelectorAll('.gc-trueque-card');

        tipoBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                tipoBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const tipo = this.dataset.tipo;
                cards.forEach(card => {
                    if (!tipo || card.dataset.tipo === tipo) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Búsqueda
        const inputBuscar = document.getElementById('gc-trueque-buscar');
        if (inputBuscar) {
            inputBuscar.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                cards.forEach(card => {
                    const texto = card.textContent.toLowerCase();
                    card.style.display = texto.includes(query) ? '' : 'none';
                });
            });
        }

        // Modal nuevo trueque
        const btnNuevo = document.getElementById('gc-nuevo-trueque-btn');
        const modalTrueque = document.getElementById('gc-modal-trueque');

        if (btnNuevo && modalTrueque) {
            btnNuevo.addEventListener('click', () => modalTrueque.style.display = 'flex');

            modalTrueque.querySelectorAll('.gc-modal__cerrar, .gc-modal__overlay, .gc-modal__cancelar').forEach(el => {
                el.addEventListener('click', () => modalTrueque.style.display = 'none');
            });

            // Toggle campo deseados según tipo
            const selectTipo = document.getElementById('gc-trueque-tipo');
            const grupoDeseados = document.getElementById('gc-grupo-deseados');

            selectTipo.addEventListener('change', function() {
                grupoDeseados.style.display = this.value === 'trueque' ? '' : 'none';
            });

            // Enviar formulario
            document.getElementById('gc-form-trueque').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const productos = formData.get('productos_ofrecidos').split('\n').filter(p => p.trim());
                const deseados = formData.get('productos_deseados')?.split('\n').filter(p => p.trim()) || [];

                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = '<?php echo esc_js(__('Publicando...', 'flavor-platform')); ?>';

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'gc_publicar_trueque',
                        nonce: nonce,
                        titulo: formData.get('titulo'),
                        tipo: formData.get('tipo'),
                        productos_ofrecidos: JSON.stringify(productos),
                        productos_deseados: JSON.stringify(deseados),
                        descripcion: formData.get('descripcion'),
                        ubicacion: formData.get('ubicacion'),
                        dias_vigencia: formData.get('dias_vigencia')
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        mostrarAviso(data.data.message, 'success');
                        location.reload();
                    } else {
                        mostrarAviso(data.data.message || '<?php echo esc_js(__('Error al publicar', 'flavor-platform')); ?>', 'error');
                        btn.disabled = false;
                        btn.textContent = '<?php echo esc_js(__('Publicar', 'flavor-platform')); ?>';
                    }
                })
                .catch(() => {
                    mostrarAviso('<?php echo esc_js(__('Error de conexión', 'flavor-platform')); ?>', 'error');
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js(__('Publicar', 'flavor-platform')); ?>';
                });
            });
        }

        // Modal contactar
        const modalContactar = document.getElementById('gc-modal-contactar');

        if (modalContactar) {
            container.querySelectorAll('.gc-contactar-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('gc-contactar-trueque-id').value = this.dataset.truequeId;
                    modalContactar.style.display = 'flex';
                });
            });

            modalContactar.querySelectorAll('.gc-modal__cerrar, .gc-modal__overlay, .gc-modal__cancelar').forEach(el => {
                el.addEventListener('click', () => modalContactar.style.display = 'none');
            });

            document.getElementById('gc-form-contactar').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = '<?php echo esc_js(__('Enviando...', 'flavor-platform')); ?>';

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'gc_responder_trueque',
                        nonce: nonce,
                        trueque_id: formData.get('trueque_id'),
                        mensaje: formData.get('mensaje')
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        mostrarAviso(data.data.message, 'success');
                        modalContactar.style.display = 'none';
                        this.reset();
                    } else {
                        mostrarAviso(data.data.message || '<?php echo esc_js(__('Error al enviar', 'flavor-platform')); ?>', 'error');
                    }
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js(__('Enviar', 'flavor-platform')); ?>';
                })
                .catch(() => {
                    mostrarAviso('<?php echo esc_js(__('Error de conexión', 'flavor-platform')); ?>', 'error');
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js(__('Enviar', 'flavor-platform')); ?>';
                });
            });
        }
    });
})();
</script>

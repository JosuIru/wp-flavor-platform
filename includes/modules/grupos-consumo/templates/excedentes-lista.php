<?php
/**
 * Template: Lista de Excedentes Solidarios
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $excedentes Lista de excedentes disponibles
 * @var array $atts Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$nonce = wp_create_nonce('gc_conciencia_nonce');
?>

<div class="gc-excedentes" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="gc-excedentes__header">
        <h3 class="gc-excedentes__titulo">
            <span class="dashicons dashicons-carrot"></span>
            <?php esc_html_e('Excedentes Solidarios', 'flavor-platform'); ?>
        </h3>
        <p class="gc-excedentes__descripcion">
            <?php esc_html_e('Productos sobrantes a precio reducido o gratuito. Evitamos el desperdicio y creamos comunidad.', 'flavor-platform'); ?>
        </p>
    </div>

    <?php if (empty($excedentes)): ?>
        <div class="gc-excedentes__vacio">
            <span class="dashicons dashicons-yes-alt"></span>
            <p><?php esc_html_e('No hay excedentes disponibles en este momento. Todos los productos han encontrado hogar.', 'flavor-platform'); ?></p>
        </div>
    <?php else: ?>
        <div class="gc-excedentes__lista">
            <?php foreach ($excedentes as $excedente): ?>
                <?php
                $cantidad_disponible = floatval($excedente['cantidad_disponible']);
                $tiene_precio = !empty($excedente['precio_solidario']) && $excedente['precio_solidario'] > 0;
                ?>
                <article class="gc-excedente-card" data-excedente-id="<?php echo esc_attr($excedente['id']); ?>">
                    <div class="gc-excedente-card__imagen">
                        <?php
                        $imagen_id = get_post_thumbnail_id($excedente['producto_id']);
                        if ($imagen_id):
                            echo wp_get_attachment_image($imagen_id, 'medium', false, ['class' => 'gc-excedente-card__img']);
                        else:
                        ?>
                            <div class="gc-excedente-card__placeholder">
                                <span class="dashicons dashicons-carrot"></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!$tiene_precio): ?>
                            <span class="gc-excedente-card__badge gc-excedente-card__badge--gratis">
                                <?php esc_html_e('Gratis', 'flavor-platform'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="gc-excedente-card__contenido">
                        <h4 class="gc-excedente-card__nombre">
                            <?php echo esc_html($excedente['nombre_producto']); ?>
                        </h4>

                        <div class="gc-excedente-card__meta">
                            <span class="gc-excedente-card__cantidad">
                                <strong><?php echo esc_html(number_format($cantidad_disponible, 1)); ?></strong>
                                <?php echo esc_html($excedente['unidad_producto'] ?? 'ud.'); ?>
                                <?php esc_html_e('disponibles', 'flavor-platform'); ?>
                            </span>

                            <?php if ($tiene_precio): ?>
                                <span class="gc-excedente-card__precio">
                                    <?php echo esc_html(number_format($excedente['precio_solidario'], 2)); ?>&euro;
                                    <small>/<?php echo esc_html($excedente['unidad_producto'] ?? 'ud.'); ?></small>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($excedente['motivo_excedente'])): ?>
                            <p class="gc-excedente-card__motivo">
                                <small><?php echo esc_html($excedente['motivo_excedente']); ?></small>
                            </p>
                        <?php endif; ?>

                        <?php if (is_user_logged_in()): ?>
                            <form class="gc-excedente-card__form gc-reclamar-form">
                                <input type="hidden" name="excedente_id" value="<?php echo esc_attr($excedente['id']); ?>">

                                <div class="gc-excedente-card__input-group">
                                    <label for="cantidad-<?php echo esc_attr($excedente['id']); ?>" class="screen-reader-text">
                                        <?php esc_html_e('Cantidad', 'flavor-platform'); ?>
                                    </label>
                                    <input
                                        type="number"
                                        id="cantidad-<?php echo esc_attr($excedente['id']); ?>"
                                        name="cantidad"
                                        min="0.1"
                                        max="<?php echo esc_attr($cantidad_disponible); ?>"
                                        step="0.1"
                                        value="1"
                                        class="gc-excedente-card__input"
                                        required
                                    >
                                    <span class="gc-excedente-card__unidad">
                                        <?php echo esc_html($excedente['unidad_producto'] ?? 'ud.'); ?>
                                    </span>
                                </div>

                                <button type="submit" class="gc-excedente-card__btn gc-btn gc-btn--primary">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php esc_html_e('Reclamar', 'flavor-platform'); ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="gc-excedente-card__login">
                                <a href="<?php echo esc_url(wp_login_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', ''))); ?>">
                                    <?php esc_html_e('Inicia sesión para reclamar', 'flavor-platform'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.gc-excedentes {
    --gc-primary: #2e7d32;
    --gc-primary-light: #e8f5e9;
    --gc-success: #43a047;
    --gc-warning: #ff9800;
    --gc-text: #333;
    --gc-text-light: #666;
    --gc-border: #e0e0e0;
    --gc-radius: 12px;
    margin: 2rem 0;
}

.gc-excedentes__header {
    text-align: center;
    margin-bottom: 2rem;
}

.gc-excedentes__titulo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    color: var(--gc-primary);
    margin: 0 0 0.5rem;
}

.gc-excedentes__titulo .dashicons {
    font-size: 1.75rem;
    width: 1.75rem;
    height: 1.75rem;
}

.gc-excedentes__descripcion {
    color: var(--gc-text-light);
    max-width: 600px;
    margin: 0 auto;
}

.gc-excedentes__vacio {
    text-align: center;
    padding: 3rem 2rem;
    background: var(--gc-primary-light);
    border-radius: var(--gc-radius);
}

.gc-excedentes__vacio .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    color: var(--gc-success);
}

.gc-excedentes__lista {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.gc-excedente-card {
    background: #fff;
    border: 1px solid var(--gc-border);
    border-radius: var(--gc-radius);
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}

.gc-excedente-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.gc-excedente-card__imagen {
    position: relative;
    aspect-ratio: 4/3;
    background: #f5f5f5;
}

.gc-excedente-card__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gc-excedente-card__placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--gc-primary-light), #c8e6c9);
}

.gc-excedente-card__placeholder .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    color: var(--gc-primary);
    opacity: 0.5;
}

.gc-excedente-card__badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.gc-excedente-card__badge--gratis {
    background: var(--gc-success);
    color: #fff;
}

.gc-excedente-card__contenido {
    padding: 1rem;
}

.gc-excedente-card__nombre {
    font-size: 1.1rem;
    margin: 0 0 0.5rem;
    color: var(--gc-text);
}

.gc-excedente-card__meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.gc-excedente-card__cantidad {
    color: var(--gc-text-light);
    font-size: 0.9rem;
}

.gc-excedente-card__precio {
    background: var(--gc-warning);
    color: #fff;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
}

.gc-excedente-card__precio small {
    font-weight: normal;
    opacity: 0.8;
}

.gc-excedente-card__motivo {
    color: var(--gc-text-light);
    font-style: italic;
    margin: 0 0 0.75rem;
}

.gc-excedente-card__form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.gc-excedente-card__input-group {
    display: flex;
    align-items: center;
    flex: 1;
    border: 1px solid var(--gc-border);
    border-radius: 6px;
    overflow: hidden;
}

.gc-excedente-card__input {
    width: 60px;
    border: none;
    padding: 0.5rem;
    text-align: center;
    font-size: 1rem;
}

.gc-excedente-card__input:focus {
    outline: none;
}

.gc-excedente-card__unidad {
    padding: 0.5rem;
    background: #f5f5f5;
    color: var(--gc-text-light);
    font-size: 0.85rem;
}

.gc-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.gc-btn--primary {
    background: var(--gc-primary);
    color: #fff;
}

.gc-btn--primary:hover {
    background: #1b5e20;
}

.gc-btn--primary:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.gc-excedente-card__login {
    text-align: center;
    margin: 0;
}

.gc-excedente-card__login a {
    color: var(--gc-primary);
    text-decoration: none;
}

.gc-excedente-card__login a:hover {
    text-decoration: underline;
}

@media (max-width: 600px) {
    .gc-excedentes__lista {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.gc-excedentes');
        if (!container) return;

        const nonce = container.dataset.nonce;

        container.addEventListener('submit', function(e) {
            if (!e.target.classList.contains('gc-reclamar-form')) return;
            e.preventDefault();

            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            const excedente_id = form.querySelector('[name="excedente_id"]').value;
            const cantidad = form.querySelector('[name="cantidad"]').value;

            btn.disabled = true;
            btn.innerHTML = '<span class="dashicons dashicons-update"></span> <?php echo esc_js(__('Procesando...', 'flavor-platform')); ?>';

            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'gc_reclamar_excedente',
                    nonce: nonce,
                    excedente_id: excedente_id,
                    cantidad: cantidad
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    form.innerHTML = '<p style="color: var(--gc-success); margin: 0;"><span class="dashicons dashicons-yes"></span> ' + data.data.message + '</p>';
                } else {
                    if (window.gcToast) {
                        window.gcToast(data.data.message || '<?php echo esc_js(__('Error al procesar', 'flavor-platform')); ?>', 'error');
                    }
                    btn.disabled = false;
                    btn.innerHTML = '<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Reclamar', 'flavor-platform')); ?>';
                }
            })
            .catch(() => {
                if (window.gcToast) {
                    window.gcToast('<?php echo esc_js(__('Error de conexión', 'flavor-platform')); ?>', 'error');
                }
                btn.disabled = false;
                btn.innerHTML = '<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Reclamar', 'flavor-platform')); ?>';
            });
        });
    });
})();
</script>

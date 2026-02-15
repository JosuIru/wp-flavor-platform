<?php
/**
 * Template: Ranking de la Comunidad
 *
 * @package FlavorChatIA
 * @subpackage BancoTiempo
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $ranking Lista de usuarios ordenados
 * @var array $atts Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipo = $atts['tipo'] ?? 'puntos';
$titulo_tipo = match ($tipo) {
    'intercambios' => __('Por intercambios', 'flavor-chat-ia'),
    'rating'       => __('Por valoración', 'flavor-chat-ia'),
    default        => __('Por puntos de confianza', 'flavor-chat-ia'),
};
?>

<div class="bt-ranking">
    <div class="bt-ranking__header">
        <h3 class="bt-ranking__titulo">
            <span class="dashicons dashicons-awards"></span>
            <?php esc_html_e('Ranking de la Comunidad', 'flavor-chat-ia'); ?>
        </h3>
        <span class="bt-ranking__tipo"><?php echo esc_html($titulo_tipo); ?></span>
    </div>

    <?php if (empty($ranking)): ?>
        <p class="bt-ranking__vacio"><?php esc_html_e('Aún no hay suficientes datos para mostrar el ranking.', 'flavor-chat-ia'); ?></p>
    <?php else: ?>
        <ol class="bt-ranking__lista">
            <?php foreach ($ranking as $posicion => $usuario): ?>
                <?php
                $es_top3 = $posicion < 3;
                $medalla = match ($posicion) {
                    0 => '🥇',
                    1 => '🥈',
                    2 => '🥉',
                    default => '',
                };
                $badges = $usuario['badges'] ?? [];
                ?>
                <li class="bt-ranking__item <?php echo $es_top3 ? 'bt-ranking__item--top' : ''; ?>">
                    <span class="bt-ranking__posicion">
                        <?php if ($medalla): ?>
                            <span class="bt-ranking__medalla"><?php echo $medalla; ?></span>
                        <?php else: ?>
                            <?php echo esc_html($posicion + 1); ?>
                        <?php endif; ?>
                    </span>

                    <span class="bt-ranking__avatar">
                        <img src="<?php echo esc_url($usuario['avatar']); ?>" alt="">
                        <?php if ($usuario['estado_verificacion'] === 'verificado' || $usuario['estado_verificacion'] === 'mentor'): ?>
                            <span class="bt-ranking__verificado">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </span>
                        <?php endif; ?>
                    </span>

                    <div class="bt-ranking__info">
                        <span class="bt-ranking__nombre">
                            <?php echo esc_html($usuario['display_name']); ?>
                            <span class="bt-ranking__nivel">Nv.<?php echo esc_html($usuario['nivel']); ?></span>
                        </span>
                        <span class="bt-ranking__stats">
                            <?php if ($tipo === 'puntos'): ?>
                                <?php printf(esc_html__('%d pts', 'flavor-chat-ia'), intval($usuario['puntos_confianza'])); ?>
                            <?php elseif ($tipo === 'intercambios'): ?>
                                <?php printf(
                                    esc_html(_n('%d intercambio', '%d intercambios', intval($usuario['total_intercambios_completados']), 'flavor-chat-ia')),
                                    intval($usuario['total_intercambios_completados'])
                                ); ?>
                            <?php else: ?>
                                <?php
                                $rating = floatval($usuario['rating_promedio']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo '<span class="dashicons dashicons-star-' . ($i <= round($rating) ? 'filled' : 'empty') . '"></span>';
                                }
                                ?>
                                <span><?php echo esc_html(number_format($rating, 1)); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if (!empty($badges)): ?>
                        <div class="bt-ranking__badges">
                            <?php
                            $mostrar_badges = array_slice($badges, 0, 3);
                            foreach ($mostrar_badges as $badge_id):
                            ?>
                                <span class="bt-ranking__badge" title="<?php echo esc_attr($badge_id); ?>">
                                    <span class="dashicons dashicons-awards"></span>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($badges) > 3): ?>
                                <span class="bt-ranking__badge-more">+<?php echo count($badges) - 3; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>

<style>
.bt-ranking {
    --bt-primary: #1976d2;
    --bt-gold: #ffc107;
    --bt-silver: #9e9e9e;
    --bt-bronze: #cd7f32;
    --bt-text: #333;
    --bt-text-light: #666;
    --bt-border: #e0e0e0;
    --bt-radius: 12px;
    background: #fff;
    border: 1px solid var(--bt-border);
    border-radius: var(--bt-radius);
    padding: 1.5rem;
    max-width: 500px;
}

.bt-ranking__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.bt-ranking__titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1.1rem;
}

.bt-ranking__titulo .dashicons {
    color: var(--bt-gold);
}

.bt-ranking__tipo {
    background: #f5f5f5;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    color: var(--bt-text-light);
}

.bt-ranking__vacio {
    text-align: center;
    color: var(--bt-text-light);
    padding: 2rem;
}

.bt-ranking__lista {
    list-style: none;
    margin: 0;
    padding: 0;
}

.bt-ranking__item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    transition: background 0.2s;
}

.bt-ranking__item:hover {
    background: #f5f5f5;
}

.bt-ranking__item--top {
    background: linear-gradient(135deg, #fffde7, #fff8e1);
}

.bt-ranking__posicion {
    width: 30px;
    text-align: center;
    font-weight: 600;
    color: var(--bt-text-light);
}

.bt-ranking__medalla {
    font-size: 1.25rem;
}

.bt-ranking__avatar {
    position: relative;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.bt-ranking__avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.bt-ranking__verificado {
    position: absolute;
    bottom: -2px;
    right: -2px;
    background: #2e7d32;
    color: #fff;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bt-ranking__verificado .dashicons {
    font-size: 10px;
    width: 10px;
    height: 10px;
}

.bt-ranking__info {
    flex: 1;
    min-width: 0;
}

.bt-ranking__nombre {
    display: block;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bt-ranking__nivel {
    display: inline-block;
    background: var(--bt-primary);
    color: #fff;
    padding: 0 0.4rem;
    border-radius: 8px;
    font-size: 0.65rem;
    margin-left: 0.25rem;
    vertical-align: middle;
}

.bt-ranking__stats {
    font-size: 0.8rem;
    color: var(--bt-text-light);
}

.bt-ranking__stats .dashicons-star-filled {
    color: var(--bt-gold);
    font-size: 0.85rem;
    width: 0.85rem;
    height: 0.85rem;
}

.bt-ranking__stats .dashicons-star-empty {
    color: #ddd;
    font-size: 0.85rem;
    width: 0.85rem;
    height: 0.85rem;
}

.bt-ranking__badges {
    display: flex;
    gap: 0.25rem;
}

.bt-ranking__badge {
    width: 24px;
    height: 24px;
    background: #fff8e1;
    border: 1px solid var(--bt-gold);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bt-ranking__badge .dashicons {
    color: var(--bt-gold);
    font-size: 0.85rem;
    width: 0.85rem;
    height: 0.85rem;
}

.bt-ranking__badge-more {
    font-size: 0.7rem;
    color: var(--bt-text-light);
    padding: 0 0.25rem;
}
</style>

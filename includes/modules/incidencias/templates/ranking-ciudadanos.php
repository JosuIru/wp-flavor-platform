<?php
/**
 * Template: Ranking de ciudadanos activos
 * @var array $ranking Lista de usuarios top
 * @var int $user_id ID del usuario actual
 */
if (!defined('ABSPATH')) exit;

$titulos_nivel = [
    1 => __('Vecino', 'flavor-chat-ia'),
    2 => __('Observador', 'flavor-chat-ia'),
    3 => __('Reportero', 'flavor-chat-ia'),
    4 => __('Colaborador', 'flavor-chat-ia'),
    5 => __('Vigilante', 'flavor-chat-ia'),
    6 => __('Guardián', 'flavor-chat-ia'),
    7 => __('Protector', 'flavor-chat-ia'),
    8 => __('Defensor', 'flavor-chat-ia'),
    9 => __('Héroe Urbano', 'flavor-chat-ia'),
    10 => __('Leyenda', 'flavor-chat-ia'),
];
?>
<div class="inc-ranking">
    <div class="inc-ranking__header">
        <span class="inc-ranking__icono">
            <span class="dashicons dashicons-awards"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Ciudadanos más activos', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Vecinos que más contribuyen a mejorar el barrio', 'flavor-chat-ia'); ?></p>
        </div>
    </div>

    <?php if (empty($ranking)): ?>
        <div class="inc-ranking__vacio">
            <span class="dashicons dashicons-groups"></span>
            <p><?php esc_html_e('Aún no hay participación registrada. ¡Sé el primero!', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <!-- Top 3 destacados -->
        <div class="inc-ranking__podium">
            <?php
            $top3 = array_slice($ranking, 0, 3);
            $posiciones = [2, 1, 3]; // Orden visual: segundo, primero, tercero
            foreach ($posiciones as $idx):
                $pos = $idx - 1;
                if (!isset($top3[$pos])) continue;
                $usuario = $top3[$pos];
                $es_actual = $usuario->usuario_id == $user_id;
            ?>
                <div class="inc-ranking__podium-item inc-ranking__podium-item--<?php echo esc_attr($idx); ?> <?php echo $es_actual ? 'inc-ranking__podium-item--actual' : ''; ?>">
                    <div class="inc-ranking__podium-avatar">
                        <?php echo get_avatar($usuario->usuario_id, 60); ?>
                        <span class="inc-ranking__posicion"><?php echo esc_html($idx); ?></span>
                    </div>
                    <strong class="inc-ranking__nombre"><?php echo esc_html($usuario->display_name); ?></strong>
                    <span class="inc-ranking__titulo"><?php echo esc_html($titulos_nivel[$usuario->nivel] ?? $titulos_nivel[1]); ?></span>
                    <span class="inc-ranking__puntos"><?php echo esc_html(number_format($usuario->puntos_totales)); ?> pts</span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Lista completa -->
        <div class="inc-ranking__lista">
            <?php foreach ($ranking as $index => $usuario):
                $posicion = $index + 1;
                $es_actual = $usuario->usuario_id == $user_id;
            ?>
                <div class="inc-ranking__item <?php echo $es_actual ? 'inc-ranking__item--actual' : ''; ?>">
                    <span class="inc-ranking__item-pos"><?php echo esc_html($posicion); ?></span>
                    <div class="inc-ranking__item-avatar">
                        <?php echo get_avatar($usuario->usuario_id, 40); ?>
                    </div>
                    <div class="inc-ranking__item-info">
                        <strong><?php echo esc_html($usuario->display_name); ?></strong>
                        <span class="inc-ranking__item-titulo">
                            Nv.<?php echo esc_html($usuario->nivel); ?> -
                            <?php echo esc_html($titulos_nivel[$usuario->nivel] ?? $titulos_nivel[1]); ?>
                        </span>
                    </div>
                    <div class="inc-ranking__item-stats">
                        <span title="<?php esc_attr_e('Reportadas', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-flag"></span>
                            <?php echo esc_html($usuario->incidencias_reportadas); ?>
                        </span>
                        <span title="<?php esc_attr_e('Voluntariados', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-heart"></span>
                            <?php echo esc_html($usuario->voluntariados_completados); ?>
                        </span>
                    </div>
                    <span class="inc-ranking__item-puntos">
                        <?php echo esc_html(number_format($usuario->puntos_totales)); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Cómo subir en el ranking -->
    <div class="inc-ranking__tips">
        <h4><?php esc_html_e('¿Cómo subir en el ranking?', 'flavor-chat-ia'); ?></h4>
        <div class="inc-ranking__tips-grid">
            <div class="inc-ranking__tip">
                <span class="dashicons dashicons-flag"></span>
                <span><?php esc_html_e('Reporta incidencias (+10 pts)', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="inc-ranking__tip">
                <span class="dashicons dashicons-heart"></span>
                <span><?php esc_html_e('Voluntariado (+15 pts/h)', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="inc-ranking__tip">
                <span class="dashicons dashicons-admin-comments"></span>
                <span><?php esc_html_e('Comenta útilmente (+2 pts)', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="inc-ranking__tip">
                <span class="dashicons dashicons-yes-alt"></span>
                <span><?php esc_html_e('Resuelve problemas (+5 pts)', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>
</div>
<style>
.inc-ranking{--inc-primary:#f44336;--inc-gold:#ffc107;--inc-silver:#9e9e9e;--inc-bronze:#cd7f32;--inc-text:#333;--inc-text-light:#666;--inc-border:#e0e0e0;background:#fff;border:1px solid var(--inc-border);border-radius:12px;padding:1.5rem}
.inc-ranking__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.inc-ranking__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--inc-gold);border-radius:50%}
.inc-ranking__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.inc-ranking__header h3{margin:0;font-size:1.1rem}
.inc-ranking__header p{margin:0;font-size:.85rem;color:var(--inc-text-light)}
.inc-ranking__vacio{text-align:center;padding:2rem}
.inc-ranking__vacio .dashicons{font-size:3rem;width:3rem;height:3rem;color:#ccc}
.inc-ranking__vacio p{color:var(--inc-text-light);margin:.5rem 0 0}
.inc-ranking__podium{display:flex;justify-content:center;align-items:flex-end;gap:1rem;margin-bottom:2rem;padding:1rem}
.inc-ranking__podium-item{text-align:center;padding:1rem;border-radius:12px;background:#f9f9f9}
.inc-ranking__podium-item--1{order:2;background:linear-gradient(135deg,#fff8e1,#ffecb3);border:2px solid var(--inc-gold)}
.inc-ranking__podium-item--2{order:1;background:linear-gradient(135deg,#fafafa,#e0e0e0);border:2px solid var(--inc-silver)}
.inc-ranking__podium-item--3{order:3;background:linear-gradient(135deg,#fff3e0,#ffe0b2);border:2px solid var(--inc-bronze)}
.inc-ranking__podium-item--actual{box-shadow:0 0 0 3px var(--inc-primary)}
.inc-ranking__podium-avatar{position:relative;margin-bottom:.5rem}
.inc-ranking__podium-avatar img{width:60px;height:60px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.inc-ranking__podium-item--1 .inc-ranking__podium-avatar img{width:80px;height:80px}
.inc-ranking__posicion{position:absolute;bottom:-5px;right:-5px;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:#fff}
.inc-ranking__podium-item--1 .inc-ranking__posicion{background:var(--inc-gold);width:28px;height:28px}
.inc-ranking__podium-item--2 .inc-ranking__posicion{background:var(--inc-silver)}
.inc-ranking__podium-item--3 .inc-ranking__posicion{background:var(--inc-bronze)}
.inc-ranking__nombre{display:block;font-size:.9rem;margin-bottom:.25rem}
.inc-ranking__titulo{display:block;font-size:.7rem;color:var(--inc-text-light);margin-bottom:.25rem}
.inc-ranking__puntos{display:inline-block;padding:.2rem .5rem;background:var(--inc-primary);color:#fff;border-radius:10px;font-size:.75rem;font-weight:600}
.inc-ranking__lista{margin-bottom:1.5rem}
.inc-ranking__item{display:flex;align-items:center;gap:.75rem;padding:.75rem;border-bottom:1px solid var(--inc-border)}
.inc-ranking__item:last-child{border-bottom:none}
.inc-ranking__item--actual{background:#fff3e0;border-radius:8px;border-bottom:none;margin-bottom:.25rem}
.inc-ranking__item-pos{width:24px;height:24px;display:flex;align-items:center;justify-content:center;background:#f5f5f5;border-radius:50%;font-size:.8rem;font-weight:600;color:var(--inc-text-light)}
.inc-ranking__item-avatar img{width:40px;height:40px;border-radius:50%}
.inc-ranking__item-info{flex:1}
.inc-ranking__item-info strong{display:block;font-size:.9rem}
.inc-ranking__item-titulo{font-size:.75rem;color:var(--inc-text-light)}
.inc-ranking__item-stats{display:flex;gap:.75rem;font-size:.8rem;color:var(--inc-text-light)}
.inc-ranking__item-stats span{display:flex;align-items:center;gap:.25rem}
.inc-ranking__item-stats .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.inc-ranking__item-puntos{font-weight:700;color:var(--inc-primary);min-width:60px;text-align:right}
.inc-ranking__tips{padding:1rem;background:#f5f5f5;border-radius:10px}
.inc-ranking__tips h4{margin:0 0 .75rem;font-size:.9rem}
.inc-ranking__tips-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem}
.inc-ranking__tip{display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:var(--inc-text-light)}
.inc-ranking__tip .dashicons{color:var(--inc-primary);font-size:.9rem;width:.9rem;height:.9rem}
@media(max-width:500px){.inc-ranking__podium{flex-direction:column;align-items:center}.inc-ranking__podium-item{order:initial!important;width:100%}.inc-ranking__item-stats{display:none}.inc-ranking__tips-grid{grid-template-columns:1fr}}
</style>

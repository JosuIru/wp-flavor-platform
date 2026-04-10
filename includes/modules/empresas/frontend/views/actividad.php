<?php
/**
 * Actividad de la Empresa - Frontend
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos_iconos = [
    'empresa_creada' => ['icon' => 'dashicons-plus-alt', 'color' => '#10b981'],
    'empresa_actualizada' => ['icon' => 'dashicons-edit', 'color' => '#3b82f6'],
    'miembro_agregado' => ['icon' => 'dashicons-admin-users', 'color' => '#8b5cf6'],
    'miembro_eliminado' => ['icon' => 'dashicons-dismiss', 'color' => '#ef4444'],
    'documento_subido' => ['icon' => 'dashicons-upload', 'color' => '#f59e0b'],
    'documento_eliminado' => ['icon' => 'dashicons-trash', 'color' => '#ef4444'],
    'rol_cambiado' => ['icon' => 'dashicons-admin-network', 'color' => '#6366f1'],
    'default' => ['icon' => 'dashicons-info', 'color' => '#6b7280'],
];
?>
<div class="flavor-empresa-actividad">
    <!-- Navegación -->
    <div style="margin-bottom:20px;">
        <a href="<?php echo esc_url(remove_query_arg('vista')); ?>" class="flavor-btn flavor-btn-link">
            ← <?php esc_html_e('Volver al dashboard', 'flavor-platform'); ?>
        </a>
    </div>

    <!-- Cabecera -->
    <div class="flavor-card" style="margin-bottom:24px;">
        <h2 style="margin:0 0 4px;font-size:20px;"><?php esc_html_e('Registro de actividad', 'flavor-platform'); ?></h2>
        <p style="margin:0;color:#666;"><?php printf(esc_html__('Actividad reciente en %s', 'flavor-platform'), esc_html($empresa->nombre)); ?></p>
    </div>

    <!-- Timeline de actividad -->
    <?php if (!empty($actividad)): ?>
    <div class="flavor-actividad-timeline">
        <?php
        $actividad_por_dia = [];
        foreach ($actividad as $act) {
            $fecha = date_i18n('Y-m-d', strtotime($act->created_at));
            $actividad_por_dia[$fecha][] = $act;
        }
        ?>

        <?php foreach ($actividad_por_dia as $fecha => $items): ?>
        <div class="flavor-actividad-dia">
            <div class="flavor-actividad-fecha">
                <?php
                $hoy = date('Y-m-d');
                $ayer = date('Y-m-d', strtotime('-1 day'));
                if ($fecha === $hoy) {
                    esc_html_e('Hoy', 'flavor-platform');
                } elseif ($fecha === $ayer) {
                    esc_html_e('Ayer', 'flavor-platform');
                } else {
                    echo esc_html(date_i18n('j F Y', strtotime($fecha)));
                }
                ?>
            </div>

            <div class="flavor-actividad-items">
                <?php foreach ($items as $act): ?>
                <?php
                $tipo_config = $tipos_iconos[$act->tipo] ?? $tipos_iconos['default'];
                ?>
                <div class="flavor-actividad-item">
                    <div class="flavor-actividad-icono" style="background:<?php echo esc_attr($tipo_config['color']); ?>20;color:<?php echo esc_attr($tipo_config['color']); ?>;">
                        <span class="dashicons <?php echo esc_attr($tipo_config['icon']); ?>"></span>
                    </div>

                    <div class="flavor-actividad-contenido">
                        <div class="flavor-actividad-descripcion">
                            <?php echo esc_html($act->descripcion); ?>
                        </div>
                        <div class="flavor-actividad-meta">
                            <?php if ($act->display_name): ?>
                            <span class="flavor-actividad-autor">
                                <?php echo get_avatar($act->user_id, 20, '', '', ['extra_attr' => 'style="border-radius:50%;vertical-align:middle;margin-right:4px;"']); ?>
                                <?php echo esc_html($act->display_name); ?>
                            </span>
                            <?php endif; ?>
                            <span class="flavor-actividad-hora">
                                <?php echo esc_html(date_i18n('H:i', strtotime($act->created_at))); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="flavor-card" style="text-align:center;padding:60px;">
        <span class="dashicons dashicons-backup" style="font-size:48px;width:48px;height:48px;color:#94a3b8;"></span>
        <h3><?php esc_html_e('Sin actividad', 'flavor-platform'); ?></h3>
        <p style="color:#666;"><?php esc_html_e('No hay actividad registrada en esta empresa.', 'flavor-platform'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.flavor-empresa-actividad .flavor-actividad-timeline {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.flavor-empresa-actividad .flavor-actividad-dia {
    position: relative;
}
.flavor-empresa-actividad .flavor-actividad-fecha {
    font-weight: 600;
    font-size: 14px;
    color: #374151;
    margin-bottom: 12px;
    padding: 8px 16px;
    background: #f8fafc;
    border-radius: 8px;
    display: inline-block;
}
.flavor-empresa-actividad .flavor-actividad-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-left: 20px;
    border-left: 2px solid #e5e7eb;
    margin-left: 8px;
}
.flavor-empresa-actividad .flavor-actividad-item {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    position: relative;
}
.flavor-empresa-actividad .flavor-actividad-item::before {
    content: '';
    position: absolute;
    left: -26px;
    top: 50%;
    transform: translateY(-50%);
    width: 10px;
    height: 10px;
    background: #fff;
    border: 2px solid #d1d5db;
    border-radius: 50%;
}
.flavor-empresa-actividad .flavor-actividad-icono {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.flavor-empresa-actividad .flavor-actividad-icono .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}
.flavor-empresa-actividad .flavor-actividad-contenido {
    flex: 1;
}
.flavor-empresa-actividad .flavor-actividad-descripcion {
    font-size: 14px;
    color: #374151;
    line-height: 1.5;
}
.flavor-empresa-actividad .flavor-actividad-meta {
    display: flex;
    gap: 12px;
    margin-top: 6px;
    font-size: 12px;
    color: #6b7280;
}
.flavor-empresa-actividad .flavor-actividad-autor {
    display: flex;
    align-items: center;
}
</style>

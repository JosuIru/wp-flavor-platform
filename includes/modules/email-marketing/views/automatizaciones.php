<?php
/**
 * Vista: Automatizaciones
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$auto_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

$triggers_disponibles = [
    'suscripcion' => [
        'nombre' => __('Nueva suscripción', 'flavor-chat-ia'),
        'descripcion' => __('Cuando alguien se suscribe a una lista', 'flavor-chat-ia'),
        'icono' => 'groups',
    ],
    'nuevo_usuario' => [
        'nombre' => __('Nuevo usuario', 'flavor-chat-ia'),
        'descripcion' => __('Cuando se registra un nuevo usuario en WordPress', 'flavor-chat-ia'),
        'icono' => 'admin-users',
    ],
    'nuevo_socio' => [
        'nombre' => __('Nuevo socio', 'flavor-chat-ia'),
        'descripcion' => __('Cuando se registra un nuevo socio', 'flavor-chat-ia'),
        'icono' => 'id-alt',
    ],
    'compra_completada' => [
        'nombre' => __('Compra completada', 'flavor-chat-ia'),
        'descripcion' => __('Cuando un cliente completa una compra', 'flavor-chat-ia'),
        'icono' => 'cart',
    ],
    'fecha' => [
        'nombre' => __('Fecha específica', 'flavor-chat-ia'),
        'descripcion' => __('En una fecha determinada (cumpleaños, aniversario)', 'flavor-chat-ia'),
        'icono' => 'calendar-alt',
    ],
];

if ($action === 'new' || $action === 'edit'):
    $automatizacion = null;
    if ($auto_id) {
        $automatizacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_automatizaciones WHERE id = %d",
            $auto_id
        ));
    }
?>

<div class="wrap em-automatizaciones-editor">
    <h1>
        <?php echo $automatizacion ? __('Editar automatización', 'flavor-chat-ia') : __('Nueva automatización', 'flavor-chat-ia'); ?>
        <a href="<?php echo admin_url('admin.php?page=flavor-em-automatizaciones'); ?>" class="page-title-action">
            <?php _e('Volver', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <form id="em-form-automatizacion">
        <input type="hidden" name="automatizacion_id" value="<?php echo esc_attr($auto_id); ?>">

        <div class="em-auto-layout">
            <!-- Info básica -->
            <div class="em-auto-info">
                <div class="em-form-section">
                    <label for="em-auto-nombre"><?php _e('Nombre', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="em-auto-nombre" name="nombre" required
                           value="<?php echo esc_attr($automatizacion->nombre ?? ''); ?>"
                           placeholder="<?php esc_attr_e('Ej: Serie de bienvenida', 'flavor-chat-ia'); ?>">
                </div>

                <div class="em-form-section">
                    <label for="em-auto-descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
                    <textarea id="em-auto-descripcion" name="descripcion" rows="2"><?php echo esc_textarea($automatizacion->descripcion ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Selector de trigger -->
            <div class="em-auto-trigger">
                <h3><?php _e('Disparador', 'flavor-chat-ia'); ?></h3>
                <p class="description"><?php _e('¿Qué inicia esta automatización?', 'flavor-chat-ia'); ?></p>

                <div class="em-triggers-grid">
                    <?php foreach ($triggers_disponibles as $trigger_id => $trigger): ?>
                        <label class="em-trigger-option">
                            <input type="radio" name="trigger_tipo" value="<?php echo esc_attr($trigger_id); ?>"
                                <?php checked(($automatizacion->trigger_tipo ?? ''), $trigger_id); ?>>
                            <div class="em-trigger-card">
                                <span class="dashicons dashicons-<?php echo esc_attr($trigger['icono']); ?>"></span>
                                <strong><?php echo esc_html($trigger['nombre']); ?></strong>
                                <span><?php echo esc_html($trigger['descripcion']); ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Constructor de pasos -->
            <div class="em-auto-pasos">
                <h3><?php _e('Secuencia de pasos', 'flavor-chat-ia'); ?></h3>

                <div class="em-pasos-lista" id="em-pasos-lista">
                    <?php
                    $pasos = $automatizacion ? json_decode($automatizacion->pasos, true) : [];
                    if (empty($pasos)) {
                        $pasos = [['tipo' => 'email', 'espera' => '0 minutes', 'asunto' => '', 'contenido' => '']];
                    }

                    foreach ($pasos as $index => $paso):
                    ?>
                        <div class="em-paso" data-index="<?php echo $index; ?>">
                            <div class="em-paso-header">
                                <span class="em-paso-numero"><?php echo $index + 1; ?></span>
                                <select name="pasos[<?php echo $index; ?>][tipo]" class="em-paso-tipo">
                                    <option value="email" <?php selected($paso['tipo'], 'email'); ?>><?php _e('Enviar email', 'flavor-chat-ia'); ?></option>
                                    <option value="espera" <?php selected($paso['tipo'], 'espera'); ?>><?php _e('Esperar', 'flavor-chat-ia'); ?></option>
                                    <option value="tag" <?php selected($paso['tipo'], 'tag'); ?>><?php _e('Añadir tag', 'flavor-chat-ia'); ?></option>
                                    <option value="lista" <?php selected($paso['tipo'], 'lista'); ?>><?php _e('Mover a lista', 'flavor-chat-ia'); ?></option>
                                </select>
                                <button type="button" class="button em-eliminar-paso">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                            </div>

                            <div class="em-paso-contenido em-paso-email" style="<?php echo $paso['tipo'] !== 'email' ? 'display:none;' : ''; ?>">
                                <div class="em-paso-espera">
                                    <label><?php _e('Esperar:', 'flavor-chat-ia'); ?></label>
                                    <input type="text" name="pasos[<?php echo $index; ?>][espera]"
                                           value="<?php echo esc_attr($paso['espera'] ?? '0 minutes'); ?>"
                                           placeholder="1 day, 2 hours, 30 minutes">
                                </div>

                                <div class="em-paso-asunto">
                                    <label><?php _e('Asunto:', 'flavor-chat-ia'); ?></label>
                                    <input type="text" name="pasos[<?php echo $index; ?>][asunto]"
                                           value="<?php echo esc_attr($paso['asunto'] ?? ''); ?>">
                                </div>

                                <div class="em-paso-cuerpo">
                                    <label><?php _e('Contenido:', 'flavor-chat-ia'); ?></label>
                                    <textarea name="pasos[<?php echo $index; ?>][contenido]" rows="5"><?php echo esc_textarea($paso['contenido'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="em-paso-contenido em-paso-tag" style="<?php echo $paso['tipo'] !== 'tag' ? 'display:none;' : ''; ?>">
                                <label><?php _e('Tag a añadir:', 'flavor-chat-ia'); ?></label>
                                <input type="text" name="pasos[<?php echo $index; ?>][tag]"
                                       value="<?php echo esc_attr($paso['tag'] ?? ''); ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="button em-btn-agregar-paso">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Añadir paso', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Acciones -->
            <div class="em-auto-acciones">
                <button type="submit" name="guardar" class="button button-primary button-large">
                    <?php _e('Guardar automatización', 'flavor-chat-ia'); ?>
                </button>

                <?php if ($automatizacion): ?>
                    <?php if ($automatizacion->estado === 'activa'): ?>
                        <button type="button" class="button em-btn-pausar" data-id="<?php echo esc_attr($auto_id); ?>">
                            <span class="dashicons dashicons-controls-pause"></span>
                            <?php _e('Pausar', 'flavor-chat-ia'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="button button-primary em-btn-activar" data-id="<?php echo esc_attr($auto_id); ?>">
                            <span class="dashicons dashicons-controls-play"></span>
                            <?php _e('Activar', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php else: // Lista de automatizaciones ?>

<?php
$automatizaciones = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}flavor_em_automatizaciones ORDER BY creado_en DESC"
);
?>

<div class="wrap em-automatizaciones">
    <h1>
        <?php _e('Automatizaciones', 'flavor-chat-ia'); ?>
        <a href="<?php echo admin_url('admin.php?page=flavor-em-automatizaciones&action=new'); ?>" class="page-title-action">
            <?php _e('Nueva automatización', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <p class="description">
        <?php _e('Las automatizaciones envían secuencias de emails automáticamente cuando ocurren ciertos eventos.', 'flavor-chat-ia'); ?>
    </p>

    <?php if (empty($automatizaciones)): ?>
        <div class="em-empty-state">
            <span class="dashicons dashicons-controls-repeat"></span>
            <h2><?php _e('No hay automatizaciones', 'flavor-chat-ia'); ?></h2>
            <p><?php _e('Crea tu primera automatización para enviar emails automáticamente.', 'flavor-chat-ia'); ?></p>

            <div class="em-auto-plantillas">
                <h3><?php _e('Plantillas populares:', 'flavor-chat-ia'); ?></h3>
                <div class="em-plantillas-grid">
                    <a href="<?php echo admin_url('admin.php?page=flavor-em-automatizaciones&action=new&plantilla=bienvenida'); ?>" class="em-plantilla-card">
                        <span class="dashicons dashicons-format-status"></span>
                        <strong><?php _e('Serie de bienvenida', 'flavor-chat-ia'); ?></strong>
                        <span><?php _e('3 emails en 7 días', 'flavor-chat-ia'); ?></span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-em-automatizaciones&action=new&plantilla=onboarding'); ?>" class="em-plantilla-card">
                        <span class="dashicons dashicons-admin-users"></span>
                        <strong><?php _e('Onboarding de usuarios', 'flavor-chat-ia'); ?></strong>
                        <span><?php _e('5 emails educativos', 'flavor-chat-ia'); ?></span>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="em-auto-grid">
            <?php foreach ($automatizaciones as $auto): ?>
                <?php
                $pasos = json_decode($auto->pasos, true) ?: [];
                $trigger = $triggers_disponibles[$auto->trigger_tipo] ?? null;
                ?>
                <div class="em-auto-card" data-id="<?php echo esc_attr($auto->id); ?>">
                    <div class="em-auto-header">
                        <span class="em-auto-estado em-estado-<?php echo esc_attr($auto->estado); ?>">
                            <?php
                            $estados = [
                                'activa' => __('Activa', 'flavor-chat-ia'),
                                'pausada' => __('Pausada', 'flavor-chat-ia'),
                                'borrador' => __('Borrador', 'flavor-chat-ia'),
                            ];
                            echo esc_html($estados[$auto->estado] ?? $auto->estado);
                            ?>
                        </span>
                        <h3>
                            <a href="<?php echo admin_url('admin.php?page=flavor-em-automatizaciones&action=edit&id=' . $auto->id); ?>">
                                <?php echo esc_html($auto->nombre); ?>
                            </a>
                        </h3>
                    </div>

                    <?php if ($auto->descripcion): ?>
                        <p class="em-auto-descripcion"><?php echo esc_html($auto->descripcion); ?></p>
                    <?php endif; ?>

                    <div class="em-auto-trigger-info">
                        <?php if ($trigger): ?>
                            <span class="dashicons dashicons-<?php echo esc_attr($trigger['icono']); ?>"></span>
                            <?php echo esc_html($trigger['nombre']); ?>
                        <?php endif; ?>
                        <span class="em-auto-pasos-count">
                            <?php printf(_n('%d paso', '%d pasos', count($pasos), 'flavor-chat-ia'), count($pasos)); ?>
                        </span>
                    </div>

                    <div class="em-auto-stats">
                        <div class="em-stat">
                            <span class="em-stat-valor"><?php echo number_format($auto->total_inscritos); ?></span>
                            <span class="em-stat-label"><?php _e('Inscritos', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="em-stat">
                            <span class="em-stat-valor"><?php echo number_format($auto->total_completados); ?></span>
                            <span class="em-stat-label"><?php _e('Completados', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>

                    <div class="em-auto-acciones">
                        <a href="<?php echo admin_url('admin.php?page=flavor-em-automatizaciones&action=edit&id=' . $auto->id); ?>" class="button button-small">
                            <?php _e('Editar', 'flavor-chat-ia'); ?>
                        </a>
                        <?php if ($auto->estado === 'activa'): ?>
                            <button type="button" class="button button-small em-btn-pausar-auto" data-id="<?php echo esc_attr($auto->id); ?>">
                                <?php _e('Pausar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="button button-small button-primary em-btn-activar-auto" data-id="<?php echo esc_attr($auto->id); ?>">
                                <?php _e('Activar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>

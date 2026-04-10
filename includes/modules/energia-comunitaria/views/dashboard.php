<?php
/**
 * Vista: Dashboard de Energía Comunitaria
 * Migrado al sistema dm-* centralizado
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Nombres de tablas
$tabla_comunidades = $wpdb->prefix . 'flavor_energia_comunidades';
$tabla_instalaciones = $wpdb->prefix . 'flavor_energia_instalaciones';
$tabla_lecturas = $wpdb->prefix . 'flavor_energia_lecturas';
$tabla_participantes = $wpdb->prefix . 'flavor_energia_participantes';
$tabla_incidencias = $wpdb->prefix . 'flavor_energia_incidencias';

// Verificar existencia de tablas
$tabla_comunidades_existe = Flavor_Platform_Helpers::tabla_existe($tabla_comunidades);
$tabla_instalaciones_existe = Flavor_Platform_Helpers::tabla_existe($tabla_instalaciones);
$tabla_lecturas_existe = Flavor_Platform_Helpers::tabla_existe($tabla_lecturas);
$tabla_participantes_existe = Flavor_Platform_Helpers::tabla_existe($tabla_participantes);
$tabla_incidencias_existe = Flavor_Platform_Helpers::tabla_existe($tabla_incidencias);
$tablas_disponibles = $tabla_comunidades_existe;

// Inicializar estadísticas
$stats = [
    'comunidades' => 0,
    'instalaciones' => 0,
    'participantes' => 0,
    'kwh_mes' => 0,
    'ahorro_mes' => 0,
    'co2_evitado' => 0,
    'incidencias_abiertas' => 0,
    'potencia_total' => 0,
];

// Obtener estadísticas si las tablas existen
if ($tabla_comunidades_existe) {
    $stats['comunidades'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_comunidades WHERE estado = 'activa'");
}

if ($tabla_instalaciones_existe) {
    $stats['instalaciones'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_instalaciones WHERE estado = 'activa'");
    $stats['potencia_total'] = (float) ($wpdb->get_var("SELECT SUM(potencia_kw) FROM $tabla_instalaciones WHERE estado = 'activa'") ?: 0);
}

if ($tabla_participantes_existe) {
    $stats['participantes'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $tabla_participantes WHERE estado = 'activo'");
}

if ($tabla_lecturas_existe) {
    $mes_actual = date('Y-m');
    $stats['kwh_mes'] = (float) ($wpdb->get_var($wpdb->prepare(
        "SELECT SUM(kwh_generados) FROM $tabla_lecturas WHERE DATE_FORMAT(fecha_lectura, '%%Y-%%m') = %s",
        $mes_actual
    )) ?: 0);
    $stats['ahorro_mes'] = $stats['kwh_mes'] * 0.18;
    $stats['co2_evitado'] = $stats['kwh_mes'] * 0.23;
}

if ($tabla_incidencias_existe) {
    $stats['incidencias_abiertas'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('abierta', 'en_progreso')");
}

// Comunidades energéticas
$comunidades = [];
if ($tabla_comunidades_existe && $tabla_instalaciones_existe && $tabla_participantes_existe) {
    $comunidades = $wpdb->get_results(
        "SELECT c.*,
                (SELECT COUNT(*) FROM $tabla_instalaciones i WHERE i.energia_comunidad_id = c.id AND i.estado = 'activa') as num_instalaciones,
                (SELECT COUNT(DISTINCT p.user_id) FROM $tabla_participantes p WHERE p.energia_comunidad_id = c.id AND p.estado = 'activo') as num_participantes,
                (SELECT SUM(i.potencia_kw) FROM $tabla_instalaciones i WHERE i.energia_comunidad_id = c.id AND i.estado = 'activa') as potencia_total
         FROM $tabla_comunidades c
         WHERE c.estado = 'activa'
         ORDER BY c.created_at DESC
         LIMIT 10"
    );
}

?>

<div class="dm-dashboard" x-data="energiaDashboard()">
    <div class="dm-header">
        <h1 class="dm-header__title">
            <span class="dashicons dashicons-lightbulb"></span>
            <?php esc_html_e('Energía Comunitaria', 'flavor-platform'); ?>
        </h1>
        <p class="dm-header__description">
            <?php esc_html_e('Gestión de comunidades energéticas, instalaciones y consumo compartido', 'flavor-platform'); ?>
        </p>
    </div>

    <?php if (!$tablas_disponibles): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Sin datos disponibles:', 'flavor-platform'); ?></strong>
        <?php esc_html_e('Faltan tablas del módulo Energía Comunitaria o aún no hay actividad registrada.', 'flavor-platform'); ?>
    </div>
    <?php endif; ?>

    <!-- KPIs Principales -->
    <div class="dm-stats-grid dm-stats-grid--3">
        <div class="dm-stat-card dm-stat-card--highlight" style="--dm-stat-gradient: linear-gradient(135deg, var(--dm-warning) 0%, var(--dm-warning-hover) 100%);">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['comunidades']); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Comunidades Energéticas', 'flavor-platform'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--highlight" style="--dm-stat-gradient: linear-gradient(135deg, var(--dm-success) 0%, var(--dm-success-hover) 100%);">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['instalaciones']); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Instalaciones Activas', 'flavor-platform'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--highlight" style="--dm-stat-gradient: linear-gradient(135deg, var(--dm-primary) 0%, var(--dm-primary-hover) 100%);">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-chart-area"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['kwh_mes'], 1); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('kWh este mes', 'flavor-platform'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--highlight" style="--dm-stat-gradient: linear-gradient(135deg, var(--dm-purple) 0%, #7c3aed 100%);">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['ahorro_mes'], 0); ?>€</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Ahorro estimado', 'flavor-platform'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--highlight" style="--dm-stat-gradient: linear-gradient(135deg, var(--dm-info) 0%, #0891b2 100%);">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-site"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['co2_evitado'], 0); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('kg CO₂ evitados', 'flavor-platform'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--highlight" style="--dm-stat-gradient: linear-gradient(135deg, var(--dm-pink) 0%, #db2777 100%);">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['participantes']); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Participantes', 'flavor-platform'); ?></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="dm-tabs">
        <button class="dm-tabs__item" :class="{ 'dm-tabs__item--active': tab === 'comunidades' }" @click="tab = 'comunidades'">
            <span class="dashicons dashicons-groups"></span> <?php esc_html_e('Comunidades', 'flavor-platform'); ?>
        </button>
        <button class="dm-tabs__item" :class="{ 'dm-tabs__item--active': tab === 'instalaciones' }" @click="tab = 'instalaciones'">
            <span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('Instalaciones', 'flavor-platform'); ?>
        </button>
        <button class="dm-tabs__item" :class="{ 'dm-tabs__item--active': tab === 'lecturas' }" @click="tab = 'lecturas'">
            <span class="dashicons dashicons-chart-area"></span> <?php esc_html_e('Lecturas', 'flavor-platform'); ?>
        </button>
        <button class="dm-tabs__item" :class="{ 'dm-tabs__item--active': tab === 'reparto' }" @click="tab = 'reparto'">
            <span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Reparto', 'flavor-platform'); ?>
        </button>
        <button class="dm-tabs__item" :class="{ 'dm-tabs__item--active': tab === 'incidencias' }" @click="tab = 'incidencias'">
            <span class="dashicons dashicons-warning"></span> <?php esc_html_e('Incidencias', 'flavor-platform'); ?>
            <?php if ($stats['incidencias_abiertas'] > 0): ?>
                <span class="dm-badge dm-badge--error" style="margin-left: 6px;">
                    <?php echo $stats['incidencias_abiertas']; ?>
                </span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Tab: Comunidades -->
    <div x-show="tab === 'comunidades'" x-cloak class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Comunidades Energéticas', 'flavor-platform'); ?></h3>
            <button class="dm-btn dm-btn--primary dm-btn--sm" @click="showModalComunidad = true">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nueva Comunidad', 'flavor-platform'); ?>
            </button>
        </div>

        <?php if ($comunidades): ?>
        <div class="dm-grid dm-grid--3" style="padding: 20px;">
            <?php foreach ($comunidades as $comunidad): ?>
            <div class="dm-action-card">
                <div class="dm-action-card__header">
                    <h4><?php echo esc_html($comunidad->nombre); ?></h4>
                    <span class="dm-badge dm-badge--<?php echo $comunidad->estado === 'activa' ? 'success' : 'warning'; ?>">
                        <?php echo esc_html(ucfirst($comunidad->estado)); ?>
                    </span>
                </div>
                <p class="dm-text-muted dm-text-sm" style="margin: 8px 0;">
                    <?php echo esc_html(ucfirst($comunidad->modelo_reparto ?? 'proporcional')); ?>
                </p>

                <?php if (!empty($comunidad->descripcion)): ?>
                <p class="dm-text-sm" style="margin: 0 0 12px; line-height: 1.5;">
                    <?php echo esc_html(wp_trim_words($comunidad->descripcion, 15)); ?>
                </p>
                <?php endif; ?>

                <div class="dm-stats-grid dm-stats-grid--3" style="margin: 12px 0;">
                    <div class="dm-mini-stat">
                        <span class="dm-mini-stat__value dm-text-warning"><?php echo intval($comunidad->num_instalaciones); ?></span>
                        <span class="dm-mini-stat__label"><?php esc_html_e('Inst.', 'flavor-platform'); ?></span>
                    </div>
                    <div class="dm-mini-stat">
                        <span class="dm-mini-stat__value dm-text-success"><?php echo intval($comunidad->num_participantes); ?></span>
                        <span class="dm-mini-stat__label"><?php esc_html_e('Miembros', 'flavor-platform'); ?></span>
                    </div>
                    <div class="dm-mini-stat">
                        <span class="dm-mini-stat__value dm-text-primary"><?php echo number_format(floatval($comunidad->potencia_total), 1); ?></span>
                        <span class="dm-mini-stat__label"><?php esc_html_e('kW', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <div style="display: flex; gap: 8px; margin-top: auto;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-energia-comunidad&id=' . $comunidad->id)); ?>"
                       class="dm-btn dm-btn--primary dm-btn--sm" style="flex: 1;">
                        <?php esc_html_e('Gestionar', 'flavor-platform'); ?>
                    </a>
                    <button class="dm-btn dm-btn--ghost dm-btn--sm" @click="verDetalle(<?php echo $comunidad->id; ?>)">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="dm-empty">
            <span class="dashicons dashicons-lightbulb"></span>
            <p><?php esc_html_e('No hay comunidades energéticas registradas.', 'flavor-platform'); ?></p>
            <button class="dm-btn dm-btn--primary" @click="showModalComunidad = true">
                <?php esc_html_e('Crear primera comunidad', 'flavor-platform'); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Instalaciones -->
    <div x-show="tab === 'instalaciones'" x-cloak class="dm-card">
        <div style="padding: 20px;">
            <?php echo do_shortcode('[flavor_energia_instalaciones]'); ?>
        </div>
    </div>

    <!-- Tab: Lecturas -->
    <div x-show="tab === 'lecturas'" x-cloak class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Registro de Lecturas', 'flavor-platform'); ?></h3>
            <button class="dm-btn dm-btn--primary dm-btn--sm" @click="showModalLectura = true">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nueva Lectura', 'flavor-platform'); ?>
            </button>
        </div>
        <div style="padding: 20px;">
            <?php echo do_shortcode('[flavor_energia_balance]'); ?>
        </div>
    </div>

    <!-- Tab: Reparto -->
    <div x-show="tab === 'reparto'" x-cloak class="dm-card">
        <div style="padding: 20px;">
            <?php echo do_shortcode('[flavor_energia_cierres]'); ?>
        </div>
    </div>

    <!-- Tab: Incidencias -->
    <div x-show="tab === 'incidencias'" x-cloak class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Incidencias y Mantenimiento', 'flavor-platform'); ?></h3>
            <button class="dm-btn dm-btn--primary dm-btn--sm" @click="showModalIncidencia = true">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Reportar Incidencia', 'flavor-platform'); ?>
            </button>
        </div>
        <div style="padding: 20px;">
            <?php echo do_shortcode('[flavor_energia_mantenimiento]'); ?>
        </div>
    </div>

    <!-- Modal Nueva Comunidad -->
    <div x-show="showModalComunidad" x-cloak class="dm-modal-backdrop" @click.self="showModalComunidad = false">
        <div class="dm-modal">
            <div class="dm-modal__header">
                <h2><?php esc_html_e('Nueva Comunidad Energética', 'flavor-platform'); ?></h2>
                <button class="dm-modal__close" @click="showModalComunidad = false">&times;</button>
            </div>
            <div class="dm-modal__body">
                <?php echo do_shortcode('[flavor_energia_form_comunidad]'); ?>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Lectura -->
    <div x-show="showModalLectura" x-cloak class="dm-modal-backdrop" @click.self="showModalLectura = false">
        <div class="dm-modal">
            <div class="dm-modal__header">
                <h2><?php esc_html_e('Registrar Lectura', 'flavor-platform'); ?></h2>
                <button class="dm-modal__close" @click="showModalLectura = false">&times;</button>
            </div>
            <div class="dm-modal__body">
                <?php echo do_shortcode('[flavor_energia_form_lectura]'); ?>
            </div>
        </div>
    </div>

    <!-- Modal Reportar Incidencia -->
    <div x-show="showModalIncidencia" x-cloak class="dm-modal-backdrop" @click.self="showModalIncidencia = false">
        <div class="dm-modal">
            <div class="dm-modal__header">
                <h2><?php esc_html_e('Reportar Incidencia', 'flavor-platform'); ?></h2>
                <button class="dm-modal__close" @click="showModalIncidencia = false">&times;</button>
            </div>
            <div class="dm-modal__body">
                <?php echo do_shortcode('[flavor_energia_form_incidencia]'); ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('energiaDashboard', () => ({
        tab: 'comunidades',
        showModalComunidad: false,
        showModalLectura: false,
        showModalIncidencia: false,

        verDetalle(id) {
            window.location.href = '<?php echo esc_url(admin_url('admin.php?page=flavor-energia-comunidad&id=')); ?>' + id;
        }
    }));
});
</script>

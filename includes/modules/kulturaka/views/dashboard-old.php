<?php
/**
 * Dashboard principal de Kulturaka
 * Muestra las 3 vistas principales: Espacio, Artista, Comunidad
 *
 * @package FlavorChatIA
 * @subpackage Modules\Kulturaka
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener instancia del módulo
$module = Flavor_Kulturaka_Module::get_instance();
$current_user_id = get_current_user_id();

// Determinar la vista activa
$vista_activa = isset($_GET['vista']) ? sanitize_text_field($_GET['vista']) : 'comunidad';

// Obtener estadísticas generales
global $wpdb;
$tabla_nodos = $wpdb->prefix . 'flavor_kulturaka_nodos';
$tabla_agradecimientos = $wpdb->prefix . 'flavor_kulturaka_agradecimientos';
$tabla_propuestas = $wpdb->prefix . 'flavor_kulturaka_propuestas';

// Stats generales
$total_nodos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_nodos WHERE estado = 'activo'") ?: 0;
$total_espacios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_nodos WHERE tipo = 'espacio' AND estado = 'activo'") ?: 0;
$total_comunidades = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_nodos WHERE tipo = 'comunidad' AND estado = 'activo'") ?: 0;
$total_agradecimientos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_agradecimientos WHERE estado = 'activo'") ?: 0;
$propuestas_activas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas WHERE estado IN ('enviada', 'negociando')") ?: 0;

// Verificar si el usuario tiene rol de artista o gestiona espacio
$es_artista = false;
$es_gestor_espacio = false;
$artista_id = null;
$nodo_id = null;

// Verificar perfil de artista
$tabla_artistas = $wpdb->prefix . 'flavor_socios_artistas';
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_artistas'") === $tabla_artistas) {
    $artista = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_artistas WHERE usuario_id = %d AND estado = 'activo'",
        $current_user_id
    ));
    if ($artista) {
        $es_artista = true;
        $artista_id = $artista->id;
    }
}

// Verificar si gestiona algún nodo/espacio
$nodo_gestionado = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla_nodos WHERE admin_id = %d OR JSON_CONTAINS(admins, %s)",
    $current_user_id,
    json_encode((string)$current_user_id)
));
if ($nodo_gestionado) {
    $es_gestor_espacio = true;
    $nodo_id = $nodo_gestionado->id;
}
?>

<div class="kulturaka-dashboard">
    <!-- Header con estadísticas globales -->
    <div class="kulturaka-header">
        <div class="kulturaka-logo">
            <span class="dashicons dashicons-heart"></span>
            <h1>Kulturaka</h1>
            <span class="kulturaka-tagline">Red cultural descentralizada</span>
        </div>

        <div class="kulturaka-global-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html($total_espacios); ?></span>
                <span class="stat-label">Espacios</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html($total_comunidades); ?></span>
                <span class="stat-label">Comunidades</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html($total_agradecimientos); ?></span>
                <span class="stat-label">Agradecimientos</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html($propuestas_activas); ?></span>
                <span class="stat-label">Propuestas activas</span>
            </div>
        </div>
    </div>

    <!-- Navegación de vistas -->
    <div class="kulturaka-nav">
        <a href="?page=flavor-kulturaka&vista=comunidad"
           class="nav-tab <?php echo $vista_activa === 'comunidad' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-groups"></span>
            Vista Comunidad
        </a>
        <?php if ($es_gestor_espacio): ?>
        <a href="?page=flavor-kulturaka&vista=espacio"
           class="nav-tab <?php echo $vista_activa === 'espacio' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-building"></span>
            Vista Espacio
        </a>
        <?php endif; ?>
        <?php if ($es_artista): ?>
        <a href="?page=flavor-kulturaka&vista=artista"
           class="nav-tab <?php echo $vista_activa === 'artista' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-admin-users"></span>
            Vista Artista
        </a>
        <?php endif; ?>
        <a href="?page=flavor-kulturaka&vista=red"
           class="nav-tab <?php echo $vista_activa === 'red' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-networking"></span>
            Red de Nodos
        </a>
    </div>

    <!-- Contenido de la vista -->
    <div class="kulturaka-content">
        <?php
        switch ($vista_activa) {
            case 'espacio':
                if ($es_gestor_espacio) {
                    include dirname(__FILE__) . '/vista-espacio.php';
                } else {
                    echo '<div class="notice notice-warning"><p>No tienes ningún espacio asociado. <a href="?page=flavor-kulturaka&action=crear-nodo">Registra tu espacio cultural</a></p></div>';
                }
                break;

            case 'artista':
                if ($es_artista) {
                    include dirname(__FILE__) . '/vista-artista.php';
                } else {
                    echo '<div class="notice notice-warning"><p>No tienes perfil de artista. <a href="?page=socios-dashboard&action=crear-artista">Crea tu perfil de artista</a></p></div>';
                }
                break;

            case 'red':
                include dirname(__FILE__) . '/vista-red.php';
                break;

            case 'comunidad':
            default:
                include dirname(__FILE__) . '/vista-comunidad.php';
                break;
        }
        ?>
    </div>

    <!-- Muro de agradecimientos (siempre visible en sidebar o footer) -->
    <div class="kulturaka-gratitude-wall-mini">
        <h3><span class="dashicons dashicons-heart"></span> Últimos agradecimientos</h3>
        <?php
        $ultimos_agradecimientos = $wpdb->get_results(
            "SELECT a.*, u.display_name as autor_nombre
             FROM $tabla_agradecimientos a
             LEFT JOIN {$wpdb->users} u ON a.usuario_id = u.ID
             WHERE a.estado = 'activo' AND a.publico = 1
             ORDER BY a.created_at DESC
             LIMIT 5"
        );

        if ($ultimos_agradecimientos): ?>
            <div class="gratitude-items">
                <?php foreach ($ultimos_agradecimientos as $agr): ?>
                    <div class="gratitude-item">
                        <span class="gratitude-emoji"><?php echo esc_html($agr->emoji ?: '❤️'); ?></span>
                        <div class="gratitude-content">
                            <strong><?php echo esc_html($agr->autor_nombre ?: 'Anónimo'); ?></strong>
                            <?php if ($agr->destinatario_nombre): ?>
                                → <?php echo esc_html($agr->destinatario_nombre); ?>
                            <?php endif; ?>
                            <p><?php echo esc_html(wp_trim_words($agr->mensaje, 15)); ?></p>
                            <span class="gratitude-time"><?php echo human_time_diff(strtotime($agr->created_at)); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="?page=flavor-kulturaka&vista=comunidad#muro-agradecimientos" class="ver-todos">Ver todos →</a>
        <?php else: ?>
            <p class="no-gratitude">Sé el primero en enviar un agradecimiento</p>
        <?php endif; ?>

        <button type="button" class="btn-enviar-agradecimiento" onclick="abrirModalAgradecimiento()">
            <span class="dashicons dashicons-plus-alt"></span> Enviar agradecimiento
        </button>
    </div>
</div>

<!-- Modal para enviar agradecimiento -->
<div id="modal-agradecimiento" class="kulturaka-modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="dashicons dashicons-heart"></span> Enviar agradecimiento</h3>
            <button type="button" class="modal-close" onclick="cerrarModalAgradecimiento()">&times;</button>
        </div>
        <form id="form-agradecimiento" method="post">
            <?php wp_nonce_field('kulturaka_agradecimiento', 'kulturaka_nonce'); ?>
            <input type="hidden" name="action" value="enviar_agradecimiento">

            <div class="form-group">
                <label>¿A quién agradeces?</label>
                <select name="destinatario_tipo" id="destinatario_tipo" required>
                    <option value="">Seleccionar...</option>
                    <option value="artista">Un artista</option>
                    <option value="espacio">Un espacio cultural</option>
                    <option value="comunidad">Una comunidad</option>
                    <option value="persona">Una persona</option>
                    <option value="evento">Un evento</option>
                </select>
            </div>

            <div class="form-group" id="grupo-destinatario-nombre">
                <label>Nombre del destinatario</label>
                <input type="text" name="destinatario_nombre" placeholder="Escribe el nombre...">
            </div>

            <div class="form-group">
                <label>Tu mensaje de agradecimiento</label>
                <textarea name="mensaje" rows="4" required placeholder="Gracias por..."></textarea>
            </div>

            <div class="form-group">
                <label>Tipo de agradecimiento</label>
                <div class="tipo-agradecimiento-options">
                    <label><input type="radio" name="tipo" value="gracias" checked> 🙏 Gracias</label>
                    <label><input type="radio" name="tipo" value="apoyo"> 💪 Apoyo</label>
                    <label><input type="radio" name="tipo" value="colaboracion"> 🤝 Colaboración</label>
                    <label><input type="radio" name="tipo" value="inspiracion"> ✨ Inspiración</label>
                </div>
            </div>

            <div class="form-group">
                <label>Emoji</label>
                <div class="emoji-selector">
                    <?php
                    $emojis = ['❤️', '🙏', '✨', '🎵', '🎭', '🎨', '💪', '🤝', '🌟', '🔥'];
                    foreach ($emojis as $emoji):
                    ?>
                        <label class="emoji-option">
                            <input type="radio" name="emoji" value="<?php echo esc_attr($emoji); ?>" <?php echo $emoji === '❤️' ? 'checked' : ''; ?>>
                            <span><?php echo $emoji; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="publico" value="1" checked>
                    Hacer público en el muro de agradecimientos
                </label>
            </div>

            <div class="form-actions">
                <button type="button" class="button" onclick="cerrarModalAgradecimiento()">Cancelar</button>
                <button type="submit" class="button button-primary">Enviar agradecimiento</button>
            </div>
        </form>
    </div>
</div>

<style>
.kulturaka-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    position: relative;
}

.kulturaka-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 24px;
}

.kulturaka-logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.kulturaka-logo .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
}

.kulturaka-logo h1 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
}

.kulturaka-tagline {
    font-size: 14px;
    opacity: 0.9;
    margin-left: 8px;
    padding-left: 12px;
    border-left: 2px solid rgba(255,255,255,0.5);
}

.kulturaka-global-stats {
    display: flex;
    gap: 32px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: 700;
}

.stat-label {
    font-size: 13px;
    opacity: 0.9;
}

.kulturaka-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0;
}

.kulturaka-nav .nav-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.kulturaka-nav .nav-tab:hover {
    color: #ec4899;
    border-bottom-color: #fce7f3;
}

.kulturaka-nav .nav-tab.active {
    color: #ec4899;
    border-bottom-color: #ec4899;
}

.kulturaka-nav .nav-tab .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.kulturaka-content {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    min-height: 400px;
}

.kulturaka-gratitude-wall-mini {
    position: fixed;
    right: 20px;
    top: 200px;
    width: 280px;
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 100;
}

.kulturaka-gratitude-wall-mini h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 16px;
    font-size: 15px;
    color: #ec4899;
}

.gratitude-items {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 300px;
    overflow-y: auto;
}

.gratitude-item {
    display: flex;
    gap: 10px;
    padding: 10px;
    background: #fdf2f8;
    border-radius: 8px;
}

.gratitude-emoji {
    font-size: 20px;
}

.gratitude-content {
    flex: 1;
    font-size: 13px;
}

.gratitude-content strong {
    color: #374151;
}

.gratitude-content p {
    margin: 4px 0;
    color: #6b7280;
}

.gratitude-time {
    font-size: 11px;
    color: #9ca3af;
}

.ver-todos {
    display: block;
    text-align: center;
    padding: 10px;
    color: #ec4899;
    text-decoration: none;
    font-size: 13px;
    margin-top: 12px;
}

.btn-enviar-agradecimiento {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    margin-top: 12px;
}

.btn-enviar-agradecimiento:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(236,72,153,0.4);
}

/* Modal */
.kulturaka-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.kulturaka-modal .modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.kulturaka-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.kulturaka-modal .modal-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #ec4899;
}

.kulturaka-modal .modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #9ca3af;
}

.kulturaka-modal form {
    padding: 20px;
}

.kulturaka-modal .form-group {
    margin-bottom: 16px;
}

.kulturaka-modal .form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #374151;
}

.kulturaka-modal .form-group input[type="text"],
.kulturaka-modal .form-group select,
.kulturaka-modal .form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}

.tipo-agradecimiento-options,
.emoji-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tipo-agradecimiento-options label,
.emoji-selector .emoji-option {
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
}

.tipo-agradecimiento-options label:has(input:checked),
.emoji-selector .emoji-option:has(input:checked) {
    background: #fdf2f8;
    border-color: #ec4899;
}

.tipo-agradecimiento-options input,
.emoji-selector input {
    display: none;
}

.emoji-selector .emoji-option span {
    font-size: 20px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 1200px) {
    .kulturaka-gratitude-wall-mini {
        position: static;
        width: 100%;
        margin-top: 24px;
    }
}

@media (max-width: 768px) {
    .kulturaka-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .kulturaka-logo {
        flex-direction: column;
    }

    .kulturaka-tagline {
        margin-left: 0;
        padding-left: 0;
        border-left: none;
    }

    .kulturaka-global-stats {
        flex-wrap: wrap;
        justify-content: center;
    }

    .kulturaka-nav {
        flex-wrap: wrap;
    }
}
</style>

<script>
function abrirModalAgradecimiento() {
    document.getElementById('modal-agradecimiento').style.display = 'flex';
}

function cerrarModalAgradecimiento() {
    document.getElementById('modal-agradecimiento').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('modal-agradecimiento')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalAgradecimiento();
    }
});

// Manejar envío del formulario
document.getElementById('form-agradecimiento')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('¡Agradecimiento enviado!');
            cerrarModalAgradecimiento();
            location.reload();
        } else {
            alert('Error: ' + (data.data?.message || 'No se pudo enviar'));
        }
    })
    .catch(error => {
        alert('Error de conexión');
    });
});
</script>

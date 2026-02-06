<?php
/**
 * Template: Mapa de Incidencias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="incidencias-container">
    <section class="incidencias-section">
        <?php if (!empty($atributos['titulo'])): ?>
            <h2 class="incidencias-section-title">
                <span class="dashicons dashicons-location"></span>
                <?php echo esc_html($atributos['titulo']); ?>
            </h2>
        <?php endif; ?>

        <!-- Filtros del mapa -->
        <div class="incidencias-filtros">
            <div class="incidencias-filtro-group">
                <label for="mapa-filtro-categoria"><?php _e('Categoria:', 'flavor-chat-ia'); ?></label>
                <select id="mapa-filtro-categoria" name="categoria">
                    <option value=""><?php _e('Todas', 'flavor-chat-ia'); ?></option>
                    <?php
                    $categorias = $this->obtener_categorias_activas();
                    foreach ($categorias as $categoria):
                    ?>
                        <option value="<?php echo esc_attr($categoria->slug); ?>" <?php selected($atributos['categoria'], $categoria->slug); ?>>
                            <?php echo esc_html($categoria->nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="incidencias-filtro-group">
                <label for="mapa-filtro-estado"><?php _e('Estado:', 'flavor-chat-ia'); ?></label>
                <select id="mapa-filtro-estado" name="estado">
                    <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                    <option value="pendiente" <?php selected($atributos['estado'], 'pendiente'); ?>><?php _e('Pendientes', 'flavor-chat-ia'); ?></option>
                    <option value="en_proceso" <?php selected($atributos['estado'], 'en_proceso'); ?>><?php _e('En proceso', 'flavor-chat-ia'); ?></option>
                    <option value="resuelta" <?php selected($atributos['estado'], 'resuelta'); ?>><?php _e('Resueltas', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
        </div>

        <!-- Mapa -->
        <div class="incidencias-mapa-container">
            <div id="incidencias-mapa" class="incidencias-mapa incidencias-mapa-visualizar" style="height: <?php echo esc_attr($atributos['altura']); ?>px;"></div>
        </div>

        <!-- Leyenda -->
        <div class="incidencias-mapa-leyenda" style="display: flex; gap: 20px; margin-top: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 16px; height: 16px; border-radius: 50%; background: #f59e0b;"></span>
                <span><?php _e('Pendiente', 'flavor-chat-ia'); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 16px; height: 16px; border-radius: 50%; background: #3b82f6;"></span>
                <span><?php _e('En proceso', 'flavor-chat-ia'); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 16px; height: 16px; border-radius: 50%; background: #22c55e;"></span>
                <span><?php _e('Resuelta', 'flavor-chat-ia'); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 16px; height: 16px; border-radius: 50%; background: #ef4444;"></span>
                <span><?php _e('Urgente', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </section>
</div>

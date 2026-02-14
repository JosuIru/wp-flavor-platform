<?php
/**
 * Vista: Crear colectivo
 *
 * @package FlavorChatIA
 * @var int   $identificador_usuario  ID del usuario actual
 * @var array $tipos_disponibles      Tipos de colectivos
 * @var array $sectores_disponibles   Sectores disponibles
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-col-crear">
    <?php if (!$identificador_usuario): ?>
        <div class="flavor-col-login-requerido">
            <span class="dashicons dashicons-lock"></span>
            <h3><?php esc_html_e('Inicia sesión para crear un colectivo', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Necesitas una cuenta para crear y gestionar colectivos.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-col-btn flavor-col-btn-primary">
                <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="flavor-col-form-container">
            <form id="flavor-col-form-crear" class="flavor-col-form">

                <div class="flavor-col-form-section">
                    <h3><?php esc_html_e('Información básica', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-col-campo">
                        <label for="col-nombre"><?php esc_html_e('Nombre del colectivo', 'flavor-chat-ia'); ?> *</label>
                        <input type="text" id="col-nombre" name="nombre" class="flavor-col-input" required
                               placeholder="<?php esc_attr_e('Ej: Asociación Vecinal La Esperanza', 'flavor-chat-ia'); ?>">
                    </div>

                    <div class="flavor-col-campo-doble">
                        <div class="flavor-col-campo">
                            <label for="col-tipo"><?php esc_html_e('Tipo de organización', 'flavor-chat-ia'); ?> *</label>
                            <select id="col-tipo" name="tipo" class="flavor-col-select" required>
                                <?php foreach ($tipos_disponibles as $clave => $etiqueta): ?>
                                    <option value="<?php echo esc_attr($clave); ?>"><?php echo esc_html($etiqueta); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flavor-col-campo">
                            <label for="col-sector"><?php esc_html_e('Sector de actividad', 'flavor-chat-ia'); ?></label>
                            <select id="col-sector" name="sector" class="flavor-col-select">
                                <option value=""><?php esc_html_e('Selecciona un sector', 'flavor-chat-ia'); ?></option>
                                <?php foreach ($sectores_disponibles as $clave => $etiqueta): ?>
                                    <option value="<?php echo esc_attr($clave); ?>"><?php echo esc_html($etiqueta); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flavor-col-campo">
                        <label for="col-descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?> *</label>
                        <textarea id="col-descripcion" name="descripcion" class="flavor-col-textarea" rows="4" required
                                  placeholder="<?php esc_attr_e('Describe los objetivos y actividades del colectivo...', 'flavor-chat-ia'); ?>"></textarea>
                    </div>
                </div>

                <div class="flavor-col-form-section">
                    <h3><?php esc_html_e('Datos de contacto', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-col-campo-doble">
                        <div class="flavor-col-campo">
                            <label for="col-email"><?php esc_html_e('Email de contacto', 'flavor-chat-ia'); ?></label>
                            <input type="email" id="col-email" name="email_contacto" class="flavor-col-input"
                                   placeholder="contacto@ejemplo.org">
                        </div>

                        <div class="flavor-col-campo">
                            <label for="col-telefono"><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></label>
                            <input type="tel" id="col-telefono" name="telefono" class="flavor-col-input"
                                   placeholder="+34 600 000 000">
                        </div>
                    </div>

                    <div class="flavor-col-campo">
                        <label for="col-direccion"><?php esc_html_e('Dirección / Sede', 'flavor-chat-ia'); ?></label>
                        <textarea id="col-direccion" name="direccion" class="flavor-col-textarea" rows="2"
                                  placeholder="<?php esc_attr_e('Dirección de la sede o punto de encuentro', 'flavor-chat-ia'); ?>"></textarea>
                    </div>

                    <div class="flavor-col-campo">
                        <label for="col-web"><?php esc_html_e('Página web', 'flavor-chat-ia'); ?></label>
                        <input type="url" id="col-web" name="web" class="flavor-col-input"
                               placeholder="https://ejemplo.org">
                    </div>
                </div>

                <div class="flavor-col-form-acciones">
                    <button type="submit" id="col-crear-btn" class="flavor-col-btn flavor-col-btn-primary flavor-col-btn-lg">
                        <span class="dashicons dashicons-networking"></span>
                        <?php esc_html_e('Crear colectivo', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>

            <div id="col-mensaje-exito" class="flavor-col-exito" style="display: none;">
                <span class="dashicons dashicons-yes-alt"></span>
                <h3><?php esc_html_e('¡Colectivo creado con éxito!', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Has sido registrado como presidente del colectivo.', 'flavor-chat-ia'); ?></p>
                <a href="#" id="col-ir-colectivo" class="flavor-col-btn flavor-col-btn-primary">
                    <?php esc_html_e('Ver mi colectivo', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

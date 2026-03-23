<?php
/**
 * Formulario de Empresa - Admin
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos = [
    'sl' => __('Sociedad Limitada (S.L.)', 'flavor-chat-ia'),
    'sa' => __('Sociedad Anónima (S.A.)', 'flavor-chat-ia'),
    'autonomo' => __('Autónomo', 'flavor-chat-ia'),
    'cooperativa' => __('Cooperativa', 'flavor-chat-ia'),
    'asociacion' => __('Asociación', 'flavor-chat-ia'),
    'comunidad_bienes' => __('Comunidad de Bienes', 'flavor-chat-ia'),
    'sociedad_civil' => __('Sociedad Civil', 'flavor-chat-ia'),
    'otro' => __('Otro', 'flavor-chat-ia'),
];

$sectores = $this->get_setting('sectores', []);
?>
<div class="wrap flavor-modulo-page">
    <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado')); ?>" class="button" style="margin-bottom:16px;">
        ← <?php esc_html_e('Volver al listado', 'flavor-chat-ia'); ?>
    </a>

    <div style="max-width:800px;">
        <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <h2 style="margin:0 0 20px;">
                <?php echo $es_edicion ? esc_html__('Editar empresa', 'flavor-chat-ia') : esc_html__('Nueva empresa', 'flavor-chat-ia'); ?>
            </h2>

            <form method="post">
                <?php wp_nonce_field('guardar_empresa'); ?>

                <!-- Datos básicos -->
                <h3 style="font-size:14px;color:#666;border-bottom:1px solid #e5e7eb;padding-bottom:8px;margin:20px 0 16px;">
                    <?php esc_html_e('Datos básicos', 'flavor-chat-ia'); ?>
                </h3>

                <table class="form-table">
                    <tr>
                        <th><label for="nombre"><?php esc_html_e('Nombre comercial', 'flavor-chat-ia'); ?> *</label></th>
                        <td>
                            <input type="text" id="nombre" name="nombre" class="regular-text" required
                                   value="<?php echo esc_attr($empresa->nombre ?? ''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="razon_social"><?php esc_html_e('Razón social', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" id="razon_social" name="razon_social" class="regular-text"
                                   value="<?php echo esc_attr($empresa->razon_social ?? ''); ?>" />
                            <p class="description"><?php esc_html_e('Nombre legal completo de la empresa', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cif_nif"><?php esc_html_e('CIF/NIF', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" id="cif_nif" name="cif_nif" class="regular-text" style="max-width:200px;"
                                   value="<?php echo esc_attr($empresa->cif_nif ?? ''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tipo"><?php esc_html_e('Tipo de empresa', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="tipo" name="tipo">
                                <?php foreach ($tipos as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected(($empresa->tipo ?? 'sl'), $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sector"><?php esc_html_e('Sector', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="sector" name="sector">
                                <option value=""><?php esc_html_e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                                <?php foreach ($sectores as $s): ?>
                                <option value="<?php echo esc_attr($s); ?>" <?php selected(($empresa->sector ?? ''), $s); ?>>
                                    <?php echo esc_html(ucfirst($s)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <textarea id="descripcion" name="descripcion" rows="4" class="large-text"><?php echo esc_textarea($empresa->descripcion ?? ''); ?></textarea>
                        </td>
                    </tr>
                </table>

                <!-- Contacto -->
                <h3 style="font-size:14px;color:#666;border-bottom:1px solid #e5e7eb;padding-bottom:8px;margin:30px 0 16px;">
                    <?php esc_html_e('Contacto', 'flavor-chat-ia'); ?>
                </h3>

                <table class="form-table">
                    <tr>
                        <th><label for="email"><?php esc_html_e('Email', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="email" id="email" name="email" class="regular-text"
                                   value="<?php echo esc_attr($empresa->email ?? ''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="telefono"><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="tel" id="telefono" name="telefono" class="regular-text" style="max-width:200px;"
                                   value="<?php echo esc_attr($empresa->telefono ?? ''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="web"><?php esc_html_e('Sitio web', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="url" id="web" name="web" class="regular-text"
                                   value="<?php echo esc_attr($empresa->web ?? ''); ?>" placeholder="https://" />
                        </td>
                    </tr>
                </table>

                <!-- Dirección -->
                <h3 style="font-size:14px;color:#666;border-bottom:1px solid #e5e7eb;padding-bottom:8px;margin:30px 0 16px;">
                    <?php esc_html_e('Dirección', 'flavor-chat-ia'); ?>
                </h3>

                <table class="form-table">
                    <tr>
                        <th><label for="direccion"><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" id="direccion" name="direccion" class="large-text"
                                   value="<?php echo esc_attr($empresa->direccion ?? ''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ciudad"><?php esc_html_e('Ciudad', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" id="ciudad" name="ciudad" class="regular-text"
                                   value="<?php echo esc_attr($empresa->ciudad ?? ''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="provincia"><?php esc_html_e('Provincia', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" id="provincia" name="provincia" class="regular-text"
                                   value="<?php echo esc_attr($empresa->provincia ?? ''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="codigo_postal"><?php esc_html_e('Código postal', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" id="codigo_postal" name="codigo_postal" style="max-width:100px;"
                                   value="<?php echo esc_attr($empresa->codigo_postal ?? ''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pais"><?php esc_html_e('País', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" id="pais" name="pais" class="regular-text"
                                   value="<?php echo esc_attr($empresa->pais ?? 'España'); ?>" />
                        </td>
                    </tr>
                </table>

                <div style="margin-top:24px;padding-top:16px;border-top:1px solid #e5e7eb;">
                    <?php submit_button($es_edicion ? __('Actualizar empresa', 'flavor-chat-ia') : __('Crear empresa', 'flavor-chat-ia'), 'primary', 'guardar_empresa'); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
/**
 * Vista Productos - Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Esta vista aprovecha el Custom Post Type 'gc_producto'
// Redirigir a la página de edición de productos
wp_redirect(admin_url('edit.php?post_type=gc_producto'));
exit;
?>

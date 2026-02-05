<?php
/**
 * Vista Productos - Marketplace
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Redirigir a la gestión de anuncios (Custom Post Type)
wp_redirect(admin_url('edit.php?post_type=marketplace_item'));
exit;
?>

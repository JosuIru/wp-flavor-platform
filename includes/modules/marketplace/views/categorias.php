<?php
/**
 * Vista Categorías - Marketplace
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Redirigir a la gestión de categorías
wp_redirect(admin_url('edit-tags.php?taxonomy=marketplace_categoria&post_type=marketplace_item'));
exit;
?>

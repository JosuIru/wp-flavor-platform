<?php
/**
 * Vista Productores - Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Redirigir a la gestión de productores (Custom Post Type)
wp_redirect(admin_url('edit.php?post_type=gc_productor'));
exit;
?>

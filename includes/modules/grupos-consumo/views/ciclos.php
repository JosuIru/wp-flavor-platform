<?php
/**
 * Vista Ciclos - Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Redirigir a la gestión de ciclos (Custom Post Type)
wp_redirect(admin_url('edit.php?post_type=gc_ciclo'));
exit;
?>

/**
 * Generador de Datos Demo - Binding de la página dedicada
 *
 * El formulario de generación y el de limpieza son formularios clásicos que
 * hacen POST a admin-post.php (handlers admin_post_flavor_generate_demo_data y
 * admin_post_flavor_cleanup_demo_data), por lo que NO se interceptan aquí.
 *
 * Este script solo gestiona el botón "Seleccionar todos", que es puramente de
 * interfaz y no requiere backend.
 *
 * @package FlavorPlatform
 */

(function ($) {
	'use strict';

	$(function () {
		var $btn = $('#select-all-modules');
		if (!$btn.length) {
			return;
		}

		$btn.on('click', function () {
			var $checkboxes = $('#demo-generator-form input[name="modules[]"]');
			var allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
			$checkboxes.prop('checked', !allChecked);
		});
	});

})(jQuery);

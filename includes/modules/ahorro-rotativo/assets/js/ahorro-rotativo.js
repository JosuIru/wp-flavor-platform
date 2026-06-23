/* Ahorro Rotativo — frontend.
 * Pide confirmación antes de activar un círculo (acción irreversible: fija turnos). */
(function () {
	'use strict';
	document.addEventListener('submit', function (evento) {
		var formulario = evento.target;
		if (!formulario.querySelector) {return;}
		var accion = formulario.querySelector('input[name="action"]');
		if (accion && accion.value === 'flavor_ar_activar_circulo') {
			var mensaje = 'Al activar el círculo se fijan los turnos y se generan las rondas. ¿Continuar?';
			if (!window.confirm(mensaje)) {
				evento.preventDefault();
			}
		}
	});
})();

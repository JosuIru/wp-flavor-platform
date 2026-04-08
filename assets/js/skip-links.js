/**
 * Skip Links - Accesibilidad WCAG 2.4.1
 *
 * Inyecta un skip link al inicio del body para permitir
 * a usuarios de teclado saltar al contenido principal.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

(function () {
	'use strict';

	/**
     * Configuracion de traducciones
     * @type {Object}
     */
	const TRADUCCIONES_SKIP_LINK = {
		es: {
			texto: 'Ir al contenido principal',
			ariaLabel: 'Saltar navegacion e ir directamente al contenido principal'
		},
		en: {
			texto: 'Skip to main content',
			ariaLabel: 'Skip navigation and go directly to main content'
		},
		fr: {
			texto: 'Aller au contenu principal',
			ariaLabel: 'Passer la navigation et aller directement au contenu principal'
		},
		de: {
			texto: 'Zum Hauptinhalt springen',
			ariaLabel: 'Navigation überspringen und direkt zum Hauptinhalt gehen'
		},
		it: {
			texto: 'Vai al contenuto principale',
			ariaLabel: 'Salta la navigazione e vai direttamente al contenuto principale'
		},
		pt: {
			texto: 'Ir para o conteudo principal',
			ariaLabel: 'Pular navegacao e ir diretamente para o conteudo principal'
		},
		ca: {
			texto: 'Anar al contingut principal',
			ariaLabel: 'Saltar navegacio i anar directament al contingut principal'
		},
		eu: {
			texto: 'Joan eduki nagusira',
			ariaLabel: 'Saltatu nabigazioa eta joan zuzenean eduki nagusira'
		},
		gl: {
			texto: 'Ir ao contido principal',
			ariaLabel: 'Saltar navegacion e ir directamente ao contido principal'
		}
	};

	/**
     * Selectores para identificar el contenido principal
     * @type {Array}
     */
	const SELECTORES_CONTENIDO_PRINCIPAL = [
		'#main-content',
		'#content',
		'#main',
		'[role="main"]',
		'main',
		'.site-content',
		'.content-area',
		'#primary',
		'.main-content'
	];

	/**
     * Detecta el idioma actual de la pagina
     * @returns {string} Codigo de idioma (es, en, etc.)
     */
	function detectarIdiomaPagina() {
		// 1. Intentar obtener de la configuracion de Flavor
		if (typeof flavorChatConfig !== 'undefined' && flavorChatConfig.language) {
			return flavorChatConfig.language.substring(0, 2).toLowerCase();
		}

		// 2. Intentar obtener de la configuracion de skip links
		if (typeof flavorSkipLinksConfig !== 'undefined' && flavorSkipLinksConfig.language) {
			return flavorSkipLinksConfig.language.substring(0, 2).toLowerCase();
		}

		// 3. Obtener del atributo lang del HTML
		const atributoLangHtml = document.documentElement.lang;
		if (atributoLangHtml) {
			return atributoLangHtml.substring(0, 2).toLowerCase();
		}

		// 4. Obtener del navegador
		const idiomaNavegador = navigator.language || navigator.userLanguage;
		if (idiomaNavegador) {
			return idiomaNavegador.substring(0, 2).toLowerCase();
		}

		// 5. Valor por defecto
		return 'es';
	}

	/**
     * Obtiene las traducciones para el idioma actual
     * @returns {Object} Objeto con texto y ariaLabel
     */
	function obtenerTraduccionesIdioma() {
		const codigoIdiomaActual = detectarIdiomaPagina();
		return TRADUCCIONES_SKIP_LINK[codigoIdiomaActual] || TRADUCCIONES_SKIP_LINK.es;
	}

	/**
     * Encuentra el elemento de contenido principal
     * @returns {HTMLElement|null}
     */
	function encontrarContenidoPrincipal() {
		for (const selectorContenido of SELECTORES_CONTENIDO_PRINCIPAL) {
			const elementoEncontrado = document.querySelector(selectorContenido);
			if (elementoEncontrado) {
				return elementoEncontrado;
			}
		}
		return null;
	}

	/**
     * Prepara el elemento de destino para recibir el foco
     * @param {HTMLElement} elementoDestino
     */
	function prepararElementoDestinoFoco(elementoDestino) {
		if (!elementoDestino) {return;}

		// Asegurar que el elemento tiene un ID
		if (!elementoDestino.id) {
			elementoDestino.id = 'main-content';
		}

		// Hacer el elemento focusable si no lo es
		if (!elementoDestino.hasAttribute('tabindex')) {
			elementoDestino.setAttribute('tabindex', '-1');
		}

		// Agregar role="main" si no tiene role y no es un <main>
		if (!elementoDestino.hasAttribute('role') && elementoDestino.tagName.toLowerCase() !== 'main') {
			elementoDestino.setAttribute('role', 'main');
		}
	}

	/**
     * Crea el elemento skip link
     * @returns {HTMLAnchorElement}
     */
	function crearElementoSkipLink() {
		const traducciones = obtenerTraduccionesIdioma();
		const contenidoPrincipal = encontrarContenidoPrincipal();

		// Preparar el destino
		prepararElementoDestinoFoco(contenidoPrincipal);

		// Determinar el ID del destino
		const idDestino = contenidoPrincipal ? contenidoPrincipal.id : 'main-content';

		// Crear el link
		const enlaceSkipLink = document.createElement('a');
		enlaceSkipLink.href = '#' + idDestino;
		enlaceSkipLink.className = 'flavor-skip-link';
		enlaceSkipLink.setAttribute('aria-label', traducciones.ariaLabel);

		// Crear el contenedor del texto
		const contenedorTexto = document.createElement('span');
		contenedorTexto.className = 'flavor-skip-link__text';
		contenedorTexto.textContent = traducciones.texto;

		enlaceSkipLink.appendChild(contenedorTexto);

		return enlaceSkipLink;
	}

	/**
     * Maneja el click en el skip link
     * @param {Event} eventoClick
     */
	function manejarClickSkipLink(eventoClick) {
		eventoClick.preventDefault();

		const enlaceSkipLink = eventoClick.currentTarget;
		const idElementoDestino = enlaceSkipLink.getAttribute('href').substring(1);
		const elementoDestino = document.getElementById(idElementoDestino);

		if (elementoDestino) {
			// Scroll suave si esta soportado y no hay preferencia de movimiento reducido
			const prefiereMovimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

			if (!prefiereMovimientoReducido) {
				elementoDestino.scrollIntoView({
					behavior: 'smooth',
					block: 'start'
				});
			} else {
				elementoDestino.scrollIntoView({
					block: 'start'
				});
			}

			// Establecer foco en el elemento destino
			elementoDestino.focus();

			// Actualizar la URL sin recargar la pagina
			if (history.pushState) {
				history.pushState(null, null, '#' + idElementoDestino);
			}
		}
	}

	/**
     * Maneja el evento keydown para mejorar accesibilidad
     * @param {KeyboardEvent} eventoTeclado
     */
	function manejarTecladoSkipLink(eventoTeclado) {
		// Solo manejar Enter y Space
		if (eventoTeclado.key === 'Enter' || eventoTeclado.key === ' ') {
			manejarClickSkipLink(eventoTeclado);
		}
	}

	/**
     * Inyecta el skip link en el DOM
     */
	function inyectarSkipLink() {
		// Verificar si ya existe un skip link
		const skipLinkExistente = document.querySelector('.flavor-skip-link, .skip-link, [class*="skip-to"]');
		if (skipLinkExistente) {
			return;
		}

		// Crear el skip link
		const enlaceSkipLink = crearElementoSkipLink();

		// Agregar event listeners
		enlaceSkipLink.addEventListener('click', manejarClickSkipLink);
		enlaceSkipLink.addEventListener('keydown', manejarTecladoSkipLink);

		// Insertar al inicio del body
		const elementoBody = document.body;
		if (elementoBody.firstChild) {
			elementoBody.insertBefore(enlaceSkipLink, elementoBody.firstChild);
		} else {
			elementoBody.appendChild(enlaceSkipLink);
		}
	}

	/**
     * Inicializa el sistema de skip links
     */
	function inicializarSkipLinks() {
		// Verificar que el DOM esta listo
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', inyectarSkipLink);
		} else {
			inyectarSkipLink();
		}
	}

	// Inicializar
	inicializarSkipLinks();

	// Exponer funciones para uso externo si es necesario
	window.FlavorSkipLinks = {
		inyectar: inyectarSkipLink,
		detectarIdioma: detectarIdiomaPagina,
		encontrarContenidoPrincipal: encontrarContenidoPrincipal
	};

})();

/**
 * Flavor Platform - Template Orchestrator UI
 *
 * Componente interactivo para la gestion del orquestador de plantillas.
 * Usa Alpine.js para reactividad.
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

/**
 * Inicializa el componente de orquestador cuando Alpine esta listo
 */
const flavorTemplateOrchestratorFactory = () => ({
	// Estado del modal de preview
	mostrarPreviewModal: false,
	plantillaSeleccionadaId: null,
	plantillaSeleccionadaData: {},
	previewOrigen: 'perfil',

	// Estado de modulos seleccionados para instalar
	modulosSeleccionados: [],

	// Opcion de cargar datos demo
	cargarDatosDemo: false,

	// Estado de activacion
	activandoPlantilla: false,

	// Estado del progreso
	mostrarProgreso: false,
	pasosInstalacion: [],
	pasoActualIndice: 0,
	instalacionCompletada: false,
	hayError: false,
	mensajeError: '',

	// Datos inyectados desde PHP
	datosOrquestador: window.flavorOrchestratorData || {},

	/**
         * Inicializa el componente
         */
	init() {
		this.inicializarPasosInstalacion();
		this.asegurarInstanciasUnicasModal();
	},

	/**
         * Evita duplicados de modal/progreso en el DOM si fueron renderizados mas de una vez
         */
	asegurarInstanciasUnicasModal() {
		const wrapper = document.querySelector('.flavor-composer-wrapper');
		if (!wrapper) {
			return;
		}

		['.flavor-template-modal', '.flavor-template-progress'].forEach(selector => {
			const nodos = wrapper.querySelectorAll(selector);
			if (nodos.length <= 1) {
				return;
			}

			nodos.forEach((nodo, indice) => {
				if (indice > 0) {
					nodo.remove();
				}
			});
		});
	},

	/**
         * Inicializa la lista de pasos de instalacion
         */
	inicializarPasosInstalacion() {
		this.pasosInstalacion = [
			{
				id: 'modulos',
				nombre: this.datosOrquestador.i18n?.pasoModulos || 'Instalando modulos',
				estado: 'pendiente',
				descripcion: ''
			},
			{
				id: 'tablas',
				nombre: this.datosOrquestador.i18n?.pasoTablas || 'Creando tablas de base de datos',
				estado: 'pendiente',
				descripcion: ''
			},
			{
				id: 'paginas',
				nombre: this.datosOrquestador.i18n?.pasoPaginas || 'Creando paginas',
				estado: 'pendiente',
				descripcion: ''
			},
			{
				id: 'landing',
				nombre: this.datosOrquestador.i18n?.pasoLanding || 'Configurando landing page',
				estado: 'pendiente',
				descripcion: ''
			},
			{
				id: 'configuracion',
				nombre: this.datosOrquestador.i18n?.pasoConfiguracion || 'Aplicando configuracion',
				estado: 'pendiente',
				descripcion: ''
			},
			{
				id: 'demo',
				nombre: this.datosOrquestador.i18n?.pasoDemo || 'Cargando datos de demostracion',
				estado: 'pendiente',
				descripcion: ''
			}
		];
	},

	/**
         * Calcula el porcentaje de progreso
         */
	get porcentajeProgreso() {
		const totalPasos = this.pasosInstalacion.length;
		if (!totalPasos) {
			return 0;
		}

		if (this.hayError) {
			const completados = this.pasosInstalacion.filter(paso => paso.estado === 'completado').length;
			return Math.round((completados / totalPasos) * 100);
		}

		const completados = this.pasosInstalacion.filter(paso =>
			paso.estado === 'completado' || paso.estado === 'omitido'
		).length;

		return Math.round((completados / totalPasos) * 100);
	},

	/**
         * Obtiene el titulo del progreso segun el estado
         */
	get tituloProgreso() {
		if (this.hayError) {
			return this.datosOrquestador.i18n?.tituloError || 'Error en la instalacion';
		}
		if (this.instalacionCompletada) {
			return this.datosOrquestador.i18n?.tituloCompletado || 'Instalacion completada';
		}
		return this.datosOrquestador.i18n?.tituloInstalando || 'Instalando plantilla...';
	},

	/**
         * Abre el modal de preview para una plantilla
         *
         * @param {string} plantillaId - ID de la plantilla
         */
	abrirPreviewPlantilla(plantillaId, origen = 'perfil') {
		this.plantillaSeleccionadaId = plantillaId;
		this.previewOrigen = origen;
		this.modulosSeleccionados = [];
		this.cargarDatosDemo = false;

		// Cargar datos de la plantilla via AJAX
		this.cargarDatosPlantilla(plantillaId);
	},

	/**
         * Carga los datos de la plantilla via AJAX
         *
         * @param {string} plantillaId - ID de la plantilla
         */
	async cargarDatosPlantilla(plantillaId) {
		try {
			const formData = new FormData();
			formData.append('action', 'flavor_template_preview');
			formData.append('plantilla_id', plantillaId);
			formData.append('_wpnonce', this.datosOrquestador.nonces?.preview || '');

			const respuesta = await fetch(this.datosOrquestador.ajaxUrl || ajaxurl, {
				method: 'POST',
				body: formData
			});

			const datos = await respuesta.json();

			if (datos.success) {
				this.plantillaSeleccionadaData = {
					...datos.data,
					modulos_requeridos: this.normalizarListaModulos(datos.data?.modulos_requeridos),
					modulos_opcionales: this.normalizarListaModulos(datos.data?.modulos_opcionales)
				};
				this.mostrarPreviewModal = true;
			} else {
				this.mostrarNotificacion(
					datos.data?.mensaje || 'Error al cargar la plantilla',
					'error'
				);
			}
		} catch (error) {
			console.error('Error cargando plantilla:', error);
			this.mostrarNotificacion(
				this.datosOrquestador.i18n?.errorCarga || 'Error de conexion al cargar la plantilla',
				'error'
			);
		}
	},

	/**
         * Cierra el modal de preview
         */
	cerrarPreviewModal() {
		this.mostrarPreviewModal = false;
		this.plantillaSeleccionadaId = null;
		this.plantillaSeleccionadaData = null;
		this.previewOrigen = 'perfil';
	},

	/**
         * Indica si el preview actual proviene de una sugerencia automatica
         *
         * @returns {boolean}
         */
	esPreviewSugerencia() {
		return this.previewOrigen === 'sugerencia';
	},

	/**
         * Activa directamente una plantilla sugerida reutilizando el flujo actual
         *
         * @param {string} plantillaId - ID de la plantilla sugerida
         */
	activarSugerenciaPlantilla(plantillaId) {
		this.plantillaSeleccionadaId = plantillaId;
		this.previewOrigen = 'sugerencia';
		this.modulosSeleccionados = [];
		this.cargarDatosDemo = false;
		this.activarPlantilla(plantillaId);
	},

	/**
         * Normaliza una lista de modulos eliminando vacios y duplicados
         *
         * @param {Array} modulos
         * @returns {Array}
         */
	normalizarListaModulos(modulos) {
		if (!Array.isArray(modulos)) {
			return [];
		}

		const unicos = [];
		const vistos = new Set();

		modulos.forEach(modulo => {
			const id = typeof modulo === 'string'
				? modulo
					.trim()
					.toLowerCase()
					.replace(/[\s-]+/g, '_')
					.replace(/_+/g, '_')
					.replace(/^_+|_+$/g, '')
				: '';
			if (!id || vistos.has(id)) {
				return;
			}
			vistos.add(id);
			unicos.push(id);
		});

		return unicos;
	},

	/**
         * Obtiene la lista unica de modulos requeridos para el preview
         *
         * @returns {Array}
         */
	obtenerModulosRequeridosPreview() {
		return this.normalizarListaModulos(this.plantillaSeleccionadaData?.modulos_requeridos);
	},

	/**
         * Obtiene la lista unica de modulos opcionales para el preview
         *
         * @returns {Array}
         */
	obtenerModulosOpcionalesPreview() {
		return this.normalizarListaModulos(this.plantillaSeleccionadaData?.modulos_opcionales);
	},

	/**
         * Verifica si todos los modulos opcionales estan seleccionados
         *
         * @returns {boolean}
         */
	todosOpcionalesSeleccionados() {
		const modulosOpcionales = this.obtenerModulosOpcionalesPreview();
		return modulosOpcionales.length > 0 &&
                modulosOpcionales.every(modulo => this.modulosSeleccionados.includes(modulo));
	},

	/**
         * Selecciona o deselecciona todos los modulos opcionales
         *
         * @param {boolean} seleccionarTodos - true para seleccionar todo, false para limpiar
         */
	toggleTodosOpcionales(seleccionarTodos) {
		const modulosOpcionales = this.obtenerModulosOpcionalesPreview();
		this.modulosSeleccionados = seleccionarTodos ? [...modulosOpcionales] : [];
	},

	/**
         * Activa la plantilla seleccionada
         *
         * @param {string} plantillaId - ID de la plantilla a activar
         */
	async activarPlantilla(plantillaId) {
		if (this.activandoPlantilla) {return;}

		this.activandoPlantilla = true;
		this.inicializarPasosInstalacion();
		this.instalacionCompletada = false;
		this.hayError = false;
		this.mensajeError = '';
		this.pasoActualIndice = 0;

		// Cerrar modal de preview y mostrar progreso
		this.mostrarPreviewModal = false;
		this.mostrarProgreso = true;

		// Preparar opciones de instalacion
		const opciones = {
			plantilla_id: plantillaId,
			modulos_opcionales: this.modulosSeleccionados,
			cargar_demo: this.cargarDatosDemo,
			_wpnonce: this.datosOrquestador.nonces?.activar || ''
		};

		// Ejecutar instalacion paso a paso
		await this.ejecutarInstalacionPorPasos(opciones);
	},

	/**
         * Ejecuta la instalacion paso a paso para mejor UX
         *
         * @param {Object} opciones - Opciones de instalacion
         */
	async ejecutarInstalacionPorPasos(opciones) {
		const pasosIds = ['modulos', 'tablas', 'paginas', 'landing', 'configuracion'];

		// Agregar paso de demo si esta habilitado
		if (opciones.cargar_demo) {
			pasosIds.push('demo');
		} else {
			// Marcar paso de demo como omitido
			this.actualizarProgreso('demo', 'omitido');
		}

		for (let indice = 0; indice < pasosIds.length; indice++) {
			const pasoId = pasosIds[indice];
			this.pasoActualIndice = indice;

			// Marcar paso actual como en proceso
			this.actualizarProgreso(pasoId, 'en_proceso');

			try {
				// Ejecutar el paso via AJAX
				const resultado = await this.ejecutarPasoInstalacion(pasoId, opciones);

				if (resultado.success) {
					this.actualizarProgreso(pasoId, 'completado', resultado.data?.descripcion || '');

					// Pequena pausa para mejor percepcion visual
					await this.esperar(300);
				} else {
					this.actualizarProgreso(pasoId, 'error', resultado.data?.mensaje || '');
					this.hayError = true;
					this.mensajeError = resultado.data?.mensaje || 'Error desconocido';
					break;
				}
			} catch (error) {
				console.error(`Error en paso ${pasoId}:`, error);
				this.actualizarProgreso(pasoId, 'error');
				this.hayError = true;
				if (error.name === 'AbortError') {
					this.mensajeError = this.datosOrquestador.i18n?.errorGeneral || 'Tiempo de espera agotado durante la instalacion';
				} else {
					this.mensajeError = this.datosOrquestador.i18n?.errorGeneral || 'Error de conexion durante la instalacion';
				}
				break;
			}
		}

		this.activandoPlantilla = false;

		if (!this.hayError) {
			this.instalacionCompletada = true;
		}
	},

	/**
         * Ejecuta un paso de instalacion individual via AJAX
         *
         * @param {string} pasoId - ID del paso
         * @param {Object} opciones - Opciones de instalacion
         * @returns {Promise<Object>} - Resultado del paso
         */
	async ejecutarPasoInstalacion(pasoId, opciones) {
		const formData = new FormData();
		formData.append('action', 'flavor_template_install_step');
		formData.append('paso', pasoId);
		formData.append('plantilla_id', opciones.plantilla_id);
		formData.append('modulos_opcionales', JSON.stringify(opciones.modulos_opcionales));
		formData.append('cargar_demo', opciones.cargar_demo ? '1' : '0');
		formData.append('_wpnonce', opciones._wpnonce);

		const controller = new AbortController();
		const timeoutId = setTimeout(() => controller.abort(), 20000);

		const respuesta = await fetch(this.datosOrquestador.ajaxUrl || ajaxurl, {
			method: 'POST',
			body: formData,
			signal: controller.signal
		});
		clearTimeout(timeoutId);

		if (!respuesta.ok) {
			throw new Error(`HTTP ${respuesta.status}`);
		}

		return await respuesta.json();
	},

	/**
         * Actualiza el estado de un paso de progreso
         *
         * @param {string} pasoId - ID del paso
         * @param {string} estado - Nuevo estado (pendiente, en_proceso, completado, error, omitido)
         * @param {string} descripcion - Descripcion adicional opcional
         */
	actualizarProgreso(pasoId, estado, descripcion = '') {
		const pasoIndice = this.pasosInstalacion.findIndex(paso => paso.id === pasoId);

		if (pasoIndice !== -1) {
			this.pasosInstalacion[pasoIndice].estado = estado;
			if (descripcion) {
				this.pasosInstalacion[pasoIndice].descripcion = descripcion;
			}
		}
	},

	/**
         * Cierra el modal de progreso
         */
	cerrarProgreso() {
		this.mostrarProgreso = false;

		// Si la instalacion fue exitosa, recargar la pagina
		if (this.instalacionCompletada && !this.hayError) {
			window.location.reload();
		}
	},

	/**
         * Reintenta la instalacion desde el principio
         */
	reiniciarInstalacion() {
		this.mostrarProgreso = false;
		this.hayError = false;
		this.mensajeError = '';

		// Volver a abrir el preview
		if (this.plantillaSeleccionadaId) {
			this.abrirPreviewPlantilla(this.plantillaSeleccionadaId);
		}
	},

	/**
         * Muestra una notificacion al usuario
         *
         * @param {string} mensaje - Mensaje a mostrar
         * @param {string} tipo - Tipo de notificacion (success, error, warning, info)
         */
	mostrarNotificacion(mensaje, tipo = 'info') {
		// Usar el sistema de notificaciones de WordPress si esta disponible
		if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
			const tiposWP = {
				'success': 'success',
				'error': 'error',
				'warning': 'warning',
				'info': 'info'
			};

			wp.data.dispatch('core/notices').createNotice(
				tiposWP[tipo] || 'info',
				mensaje,
				{ isDismissible: true }
			);
		} else {
			// Fallback: alert simple
			alert(mensaje);
		}
	},

	/**
         * Utilidad para esperar un tiempo
         *
         * @param {number} milisegundos - Tiempo a esperar en milisegundos
         * @returns {Promise} - Promesa que se resuelve despues del tiempo
         */
	esperar(milisegundos) {
		return new Promise(resolve => setTimeout(resolve, milisegundos));
	}
});

document.addEventListener('alpine:init', () => {
	Alpine.data('flavorTemplateOrchestrator', flavorTemplateOrchestratorFactory);
});

window.flavorTemplateOrchestrator = flavorTemplateOrchestratorFactory;

/**
 * Funcion global para abrir el preview de una plantilla
 * Compatible con el sistema actual de clics en tarjetas
 *
 * @param {string} plantillaId - ID de la plantilla
 */
window.abrirPreviewPlantilla = function (plantillaId, origen = 'perfil') {
	// Buscar la instancia de Alpine en el wrapper
	const wrapper = document.querySelector('.flavor-composer-wrapper');

	if (wrapper && wrapper.__x) {
		wrapper.__x.$data.abrirPreviewPlantilla(plantillaId, origen);
	} else if (window.Alpine) {
		// Fallback: usar el scope global de Alpine
		const componente = Alpine.$data(wrapper);
		if (componente && componente.abrirPreviewPlantilla) {
			componente.abrirPreviewPlantilla(plantillaId, origen);
		}
	}
};

/**
 * Funcion global para activar una plantilla
 *
 * @param {string} plantillaId - ID de la plantilla
 * @param {Object} opciones - Opciones de activacion
 */
window.activarPlantilla = function (plantillaId, opciones = {}) {
	const wrapper = document.querySelector('.flavor-composer-wrapper');

	if (wrapper) {
		const componente = Alpine.$data(wrapper);
		if (componente && componente.activarPlantilla) {
			componente.activarPlantilla(plantillaId);
		}
	}
};

/**
 * Funcion global para actualizar el progreso (usada por callbacks del backend)
 *
 * @param {string} paso - ID del paso
 * @param {string} estado - Estado del paso
 */
window.actualizarProgresoPlantilla = function (paso, estado) {
	const wrapper = document.querySelector('.flavor-composer-wrapper');

	if (wrapper) {
		const componente = Alpine.$data(wrapper);
		if (componente && componente.actualizarProgreso) {
			componente.actualizarProgreso(paso, estado);
		}
	}
};

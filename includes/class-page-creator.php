<?php
/**
 * Flavor Page Creator
 *
 * Crea automáticamente todas las páginas necesarias para los módulos con formularios
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

class Flavor_Page_Creator {

    /**
     * Definición de páginas a crear
     */
    private static function get_pages_to_create() {
        return [
            // MI CUENTA - Dashboard de usuario frontend
            [
                'title' => 'Mi Cuenta',
                'slug' => 'mi-cuenta',
                'content' => '[flavor_mi_cuenta]',
                'parent' => 0,
            ],

            // TALLERES - 4 páginas
            [
                'title' => 'Talleres',
                'slug' => 'talleres',
                'content' => '[flavor_module_listing module="talleres" action="talleres_disponibles" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Crear Taller',
                'slug' => 'crear',
                'content' => '<h1>Comparte tu Conocimiento</h1>
<p>Organiza un taller y enseña a tu comunidad</p>

[flavor_module_form module="talleres" action="crear_taller"]',
                'parent' => 'talleres',
            ],
            [
                'title' => 'Inscribirse en Taller',
                'slug' => 'inscribirse',
                'content' => '<h1>Inscríbete en el Taller</h1>

[flavor_module_form module="talleres" action="inscribirse"]',
                'parent' => 'talleres',
            ],
            [
                'title' => 'Mis Talleres',
                'slug' => 'mis-talleres',
                'content' => '<h1>Mis Talleres</h1>

[flavor_module_dashboard module="talleres"]',
                'parent' => 'talleres',
            ],

            // FACTURAS - 4 páginas
            [
                'title' => 'Facturas',
                'slug' => 'facturas',
                'content' => '[flavor_module_listing module="facturas" action="listar_facturas" columnas="2"]',
                'parent' => 0,
            ],
            [
                'title' => 'Nueva Factura',
                'slug' => 'crear',
                'content' => '<h1>Nueva Factura</h1>
<p>Crea una factura en segundos</p>

[flavor_module_form module="facturas" action="crear_factura"]',
                'parent' => 'facturas',
            ],
            [
                'title' => 'Mis Facturas',
                'slug' => 'mis-facturas',
                'content' => '<h1>Mis Facturas</h1>

[flavor_module_dashboard module="facturas"]',
                'parent' => 'facturas',
            ],
            [
                'title' => 'Buscar Facturas',
                'slug' => 'buscar',
                'content' => '<h1>Buscar Facturas</h1>

[flavor_module_form module="facturas" action="buscar_facturas"]',
                'parent' => 'facturas',
            ],

            // SOCIOS - 5 páginas
            [
                'title' => 'Miembros',
                'slug' => 'socios',
                'content' => '<h1>Únete a Nuestra Comunidad</h1>
<p>Descubre los beneficios de ser miembro</p>

<a href="/socios/unirme/" class="flavor-button flavor-button-primary">Hacerse Miembro</a>',
                'parent' => 0,
            ],
            [
                'title' => 'Hacerse Miembro',
                'slug' => 'unirme',
                'content' => '<h1>Únete como Miembro</h1>

[flavor_module_form module="socios" action="dar_alta_socio"]',
                'parent' => 'socios',
            ],
            [
                'title' => 'Mi Perfil de Miembro',
                'slug' => 'mi-perfil',
                'content' => '<h1>Mi Perfil de Miembro</h1>

[flavor_module_dashboard module="socios"]',
                'parent' => 'socios',
            ],
            [
                'title' => 'Pagar Cuota',
                'slug' => 'pagar-cuota',
                'content' => '<h1>Pagar Cuota</h1>

[flavor_module_form module="socios" action="pagar_cuota"]',
                'parent' => 'socios',
            ],
            [
                'title' => 'Actualizar Datos',
                'slug' => 'actualizar-datos',
                'content' => '<h1>Actualizar Mis Datos</h1>

[flavor_module_form module="socios" action="actualizar_datos"]',
                'parent' => 'socios',
            ],

            // FICHAJE-EMPLEADOS - 6 páginas
            [
                'title' => 'Fichaje',
                'slug' => 'fichaje',
                'content' => '<h1>Control de Fichajes</h1>

[flavor_module_dashboard module="fichaje_empleados"]

<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 2rem;">
    <a href="/fichaje/entrada/" class="flavor-button flavor-button-primary">Fichar Entrada</a>
    <a href="/fichaje/salida/" class="flavor-button">Fichar Salida</a>
    <a href="/fichaje/pausar/" class="flavor-button">Pausar Jornada</a>
    <a href="/fichaje/reanudar/" class="flavor-button">Reanudar</a>
</div>',
                'parent' => 0,
            ],
            [
                'title' => 'Fichar Entrada',
                'slug' => 'entrada',
                'content' => '[flavor_module_form module="fichaje_empleados" action="fichar_entrada"]',
                'parent' => 'fichaje',
            ],
            [
                'title' => 'Fichar Salida',
                'slug' => 'salida',
                'content' => '[flavor_module_form module="fichaje_empleados" action="fichar_salida"]',
                'parent' => 'fichaje',
            ],
            [
                'title' => 'Pausar Jornada',
                'slug' => 'pausar',
                'content' => '[flavor_module_form module="fichaje_empleados" action="pausar_jornada"]',
                'parent' => 'fichaje',
            ],
            [
                'title' => 'Reanudar Jornada',
                'slug' => 'reanudar',
                'content' => '[flavor_module_form module="fichaje_empleados" action="reanudar_jornada"]',
                'parent' => 'fichaje',
            ],
            [
                'title' => 'Solicitar Corrección de Fichaje',
                'slug' => 'solicitar-correccion',
                'content' => '<h1>Solicitar Corrección de Fichaje</h1>
<p>¿Olvidaste fichar? Solicita una corrección</p>

[flavor_module_form module="fichaje_empleados" action="solicitar_cambio"]',
                'parent' => 'fichaje',
            ],

            // EVENTOS - 4 páginas
            [
                'title' => 'Eventos',
                'slug' => 'eventos',
                'content' => '<h1>Eventos de la Comunidad</h1>

[flavor_module_listing module="eventos" action="eventos_proximos" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Crear Evento',
                'slug' => 'crear',
                'content' => '<h1>Organiza un Evento</h1>
<p>Crea encuentros para la comunidad</p>

[flavor_module_form module="eventos" action="crear_evento"]',
                'parent' => 'eventos',
            ],
            [
                'title' => 'Inscribirse en Evento',
                'slug' => 'inscribirse',
                'content' => '[flavor_module_form module="eventos" action="inscribirse_evento"]',
                'parent' => 'eventos',
            ],
            [
                'title' => 'Mis Eventos',
                'slug' => 'mis-eventos',
                'content' => '<h1>Mis Eventos</h1>

[flavor_module_dashboard module="eventos"]',
                'parent' => 'eventos',
            ],

            // FOROS - 4 páginas
            [
                'title' => 'Foros',
                'slug' => 'foros',
                'content' => '<h1>Foros de la Comunidad</h1>

[flavor_module_listing module="foros" action="listar_temas" columnas="1"]',
                'parent' => 0,
            ],
            [
                'title' => 'Nuevo Tema',
                'slug' => 'nuevo-tema',
                'content' => '<h1>Crear Nuevo Tema</h1>
<p>Inicia una nueva discusión</p>

[flavor_module_form module="foros" action="crear_tema"]',
                'parent' => 'foros',
            ],
            [
                'title' => 'Responder Tema',
                'slug' => 'tema',
                'content' => '[flavor_module_form module="foros" action="responder_tema"]',
                'parent' => 'foros',
            ],
            [
                'title' => 'Editar Mensaje',
                'slug' => 'editar',
                'content' => '[flavor_module_form module="foros" action="editar_mensaje"]',
                'parent' => 'foros',
            ],

            // DEX SOLANA - 4 páginas
            [
                'title' => 'DEX Solana',
                'slug' => 'dex-solana',
                'content' => '<h1>DEX Solana</h1>
<p>Intercambia tokens en la blockchain de Solana</p>

[flavor_module_listing module="dex_solana" action="obtener_portfolio"]',
                'parent' => 0,
            ],
            [
                'title' => 'Swap de Tokens',
                'slug' => 'swap',
                'content' => '<h1>Swap de Tokens</h1>
<p>Intercambia tokens al mejor precio</p>

[flavor_module_form module="dex_solana" action="ejecutar_swap"]',
                'parent' => 'dex-solana',
            ],
            [
                'title' => 'Gestionar Liquidez',
                'slug' => 'liquidez',
                'content' => '<h1>Gestionar Liquidez</h1>
<p>Añade o retira liquidez de pools</p>

[flavor_module_form module="dex_solana" action="agregar_liquidez"]',
                'parent' => 'dex-solana',
            ],
            [
                'title' => 'Mi Portfolio DEX',
                'slug' => 'portfolio',
                'content' => '<h1>Mi Portfolio</h1>

[flavor_module_dashboard module="dex_solana"]',
                'parent' => 'dex-solana',
            ],

            // TRADING IA - 5 páginas
            [
                'title' => 'Trading IA',
                'slug' => 'trading-ia',
                'content' => '<h1>Trading IA</h1>
<p>Trading automatizado con inteligencia artificial</p>

[flavor_module_listing module="trading_ia" action="obtener_estado"]',
                'parent' => 0,
            ],
            [
                'title' => 'Compra Manual',
                'slug' => 'comprar',
                'content' => '<h1>Compra Manual</h1>
<p>Ejecuta una compra manual de tokens</p>

[flavor_module_form module="trading_ia" action="ejecutar_compra_manual"]',
                'parent' => 'trading-ia',
            ],
            [
                'title' => 'Venta Manual',
                'slug' => 'vender',
                'content' => '<h1>Venta Manual</h1>
<p>Ejecuta una venta manual de tokens</p>

[flavor_module_form module="trading_ia" action="ejecutar_venta_manual"]',
                'parent' => 'trading-ia',
            ],
            [
                'title' => 'Crear Regla de Trading',
                'slug' => 'regla',
                'content' => '<h1>Crear Regla de Trading</h1>
<p>Define reglas automáticas para tu bot de trading</p>

[flavor_module_form module="trading_ia" action="crear_regla"]',
                'parent' => 'trading-ia',
            ],
            [
                'title' => 'Mi Dashboard Trading',
                'slug' => 'dashboard',
                'content' => '<h1>Dashboard de Trading</h1>

[flavor_module_dashboard module="trading_ia"]',
                'parent' => 'trading-ia',
            ],

            // INCIDENCIAS URBANAS - 3 páginas
            [
                'title' => 'Incidencias',
                'slug' => 'incidencias',
                'content' => '<h1>Incidencias Urbanas</h1>
<p>Reporta y consulta incidencias en tu barrio</p>

[flavor_module_listing module="incidencias" action="listar_incidencias" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Reportar Incidencia',
                'slug' => 'reportar',
                'content' => '<h1>Reportar Incidencia</h1>
<p>Informa de un problema en tu barrio: baches, farolas, limpieza...</p>

[flavor_module_form module="incidencias" action="reportar_incidencia"]',
                'parent' => 'incidencias',
            ],
            [
                'title' => 'Mis Incidencias',
                'slug' => 'mis-incidencias',
                'content' => '<h1>Mis Incidencias</h1>

[flavor_module_dashboard module="incidencias"]',
                'parent' => 'incidencias',
            ],

            // PARTICIPACIÓN CIUDADANA - 4 páginas
            [
                'title' => 'Participación',
                'slug' => 'participacion',
                'content' => '<h1>Participación Ciudadana</h1>
<p>Propuestas, votaciones y consultas para tu comunidad</p>

[flavor_module_listing module="participacion" action="listar_propuestas" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Nueva Propuesta',
                'slug' => 'nueva-propuesta',
                'content' => '<h1>Crear Propuesta</h1>
<p>Comparte tu idea para mejorar la comunidad</p>

[flavor_module_form module="participacion" action="crear_propuesta"]',
                'parent' => 'participacion',
            ],
            [
                'title' => 'Votar',
                'slug' => 'votar',
                'content' => '<h1>Votaciones Activas</h1>

[flavor_module_form module="participacion" action="votar"]',
                'parent' => 'participacion',
            ],
            [
                'title' => 'Mis Propuestas',
                'slug' => 'mis-propuestas',
                'content' => '<h1>Mis Propuestas</h1>

[flavor_module_dashboard module="participacion"]',
                'parent' => 'participacion',
            ],

            // PRESUPUESTOS PARTICIPATIVOS - 4 páginas
            [
                'title' => 'Presupuestos Participativos',
                'slug' => 'presupuestos',
                'content' => '<h1>Presupuestos Participativos</h1>
<p>Decide cómo se invierte el presupuesto municipal</p>

[flavor_module_listing module="presupuestos_participativos" action="listar_proyectos" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Proponer Proyecto',
                'slug' => 'proponer',
                'content' => '<h1>Proponer Proyecto</h1>
<p>Presenta tu proyecto para el presupuesto participativo</p>

[flavor_module_form module="presupuestos_participativos" action="proponer_proyecto"]',
                'parent' => 'presupuestos',
            ],
            [
                'title' => 'Votar Proyectos',
                'slug' => 'votar',
                'content' => '<h1>Votar Proyectos</h1>

[flavor_module_form module="presupuestos_participativos" action="votar_proyectos"]',
                'parent' => 'presupuestos',
            ],
            [
                'title' => 'Resultados',
                'slug' => 'resultados',
                'content' => '<h1>Resultados</h1>

[flavor_module_listing module="presupuestos_participativos" action="resultados"]',
                'parent' => 'presupuestos',
            ],

            // AVISOS MUNICIPALES - 2 páginas
            [
                'title' => 'Avisos Municipales',
                'slug' => 'avisos',
                'content' => '<h1>Avisos Municipales</h1>
<p>Canal oficial de comunicación del ayuntamiento</p>

[flavor_module_listing module="avisos_municipales" action="listar_avisos" columnas="1" limite="20"]',
                'parent' => 0,
            ],
            [
                'title' => 'Mis Avisos',
                'slug' => 'mis-avisos',
                'content' => '<h1>Mis Avisos</h1>

[flavor_module_dashboard module="avisos_municipales"]',
                'parent' => 'avisos',
            ],

            // BANCO DE TIEMPO - 4 páginas
            [
                'title' => 'Banco de Tiempo',
                'slug' => 'banco-tiempo',
                'content' => '<h1>Banco de Tiempo</h1>
<p>Intercambia servicios y tiempo con tu comunidad</p>

[flavor_module_listing module="banco_tiempo" action="listar_servicios" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Ofrecer Servicio',
                'slug' => 'ofrecer',
                'content' => '<h1>Ofrecer un Servicio</h1>
<p>Comparte tus habilidades con la comunidad</p>

[flavor_module_form module="banco_tiempo" action="crear_servicio"]',
                'parent' => 'banco-tiempo',
            ],
            [
                'title' => 'Solicitar Servicio',
                'slug' => 'solicitar',
                'content' => '<h1>Solicitar un Servicio</h1>

[flavor_module_form module="banco_tiempo" action="solicitar_servicio"]',
                'parent' => 'banco-tiempo',
            ],
            [
                'title' => 'Mis Intercambios',
                'slug' => 'mis-intercambios',
                'content' => '<h1>Mis Intercambios</h1>

[flavor_module_dashboard module="banco_tiempo"]',
                'parent' => 'banco-tiempo',
            ],

            // GRUPOS DE CONSUMO - 6 páginas
            [
                'title' => 'Grupos de Consumo',
                'slug' => 'grupos-consumo',
                'content' => '<h1>Grupos de Consumo</h1>
[gc_nav]
<p>Consume local, apoya a productores cercanos y forma parte de una comunidad sostenible.</p>

<div class="gc-home-cta">
    <a href="/grupos-consumo/productos/" class="flavor-button flavor-button-primary">Ver catálogo</a>
    <a href="/grupos-consumo/panel/" class="flavor-button">Panel GC</a>
    <a href="/grupos-consumo/mi-cesta/" class="flavor-button">Mi cesta</a>
    <a href="/grupos-consumo/unirme/" class="flavor-button">Unirme a un grupo</a>
</div>

<h2>Ciclo actual</h2>
[gc_ciclo_actual]

<h2>Panel rápido</h2>
[gc_panel]

<h2>Catálogo destacado</h2>
[gc_catalogo mostrar_filtros="0" columnas="3" limite="6"]

<h2>Productores cercanos</h2>
[gc_productores_cercanos]

<h2>Grupos activos</h2>
[gc_grupos_lista columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Panel GC',
                'slug' => 'panel',
                'content' => '<h1>Panel Grupos de Consumo</h1>
[gc_nav]

[gc_panel]',
                'parent' => 'grupos-consumo',
            ],
            [
                'title' => 'Catálogo de Productos',
                'slug' => 'productos',
                'content' => '<h1>Catálogo de Productos</h1>
[gc_nav]
<p>Productos frescos y locales de nuestros productores.</p>

[gc_catalogo mostrar_filtros="1" columnas="3" limite="12"]',
                'parent' => 'grupos-consumo',
            ],
            [
                'title' => 'Mi Pedido',
                'slug' => 'mi-pedido',
                'content' => '<h1>Mi Pedido</h1>
[gc_nav]

[gc_carrito]',
                'parent' => 'grupos-consumo',
            ],
            [
                'title' => 'Mis Pedidos',
                'slug' => 'mis-pedidos',
                'content' => '<h1>Historial de Pedidos</h1>
[gc_nav]

[gc_historial limite="20"]',
                'parent' => 'grupos-consumo',
            ],
            [
                'title' => 'Mi Cesta',
                'slug' => 'mi-cesta',
                'content' => '<h1>Mi Cesta</h1>
[gc_nav]

[gc_mi_cesta]',
                'parent' => 'grupos-consumo',
            ],
            [
                'title' => 'Suscripciones',
                'slug' => 'suscripciones',
                'content' => '<h1>Suscripciones</h1>
[gc_nav]

[gc_suscripciones]',
                'parent' => 'grupos-consumo',
            ],
            [
                'title' => 'Ciclo Actual',
                'slug' => 'ciclo',
                'content' => '<h1>Ciclo de Pedidos Actual</h1>
[gc_nav]

[gc_ciclo_actual]

[gc_calendario meses="3"]',
                'parent' => 'grupos-consumo',
            ],
            [
                'title' => 'Productores Cercanos',
                'slug' => 'productores-cercanos',
                'content' => '<h1>Productores Cercanos</h1>
[gc_nav]
<p>Descubre productores que entregan en tu zona.</p>

[gc_productores_cercanos]',
                'parent' => 'grupos-consumo',
            ],
            [
                'title' => 'Unirme a un Grupo',
                'slug' => 'unirme',
                'content' => '<h1>Unirme a un Grupo de Consumo</h1>
[gc_nav]
<p>Completa el formulario para solicitar tu incorporación.</p>

[gc_formulario_union]',
                'parent' => 'grupos-consumo',
            ],

            // MARKETPLACE - 3 páginas
            [
                'title' => 'Marketplace',
                'slug' => 'marketplace',
                'content' => '<h1>Marketplace</h1>
<p>Anuncios de regalo, venta e intercambio</p>

[flavor_module_listing module="marketplace" action="listar_anuncios" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Publicar Anuncio',
                'slug' => 'publicar',
                'content' => '<h1>Publicar Anuncio</h1>
<p>Publica un artículo para vender, regalar o intercambiar</p>

[flavor_module_form module="marketplace" action="crear_anuncio"]',
                'parent' => 'marketplace',
            ],
            [
                'title' => 'Mis Anuncios',
                'slug' => 'mis-anuncios',
                'content' => '<h1>Mis Anuncios</h1>

[flavor_module_dashboard module="marketplace"]',
                'parent' => 'marketplace',
            ],

            // BARES Y HOSTELERÍA - 2 páginas
            [
                'title' => 'Bares y Hostelería',
                'slug' => 'bares',
                'content' => '<h1>Bares y Hostelería</h1>
<p>Descubre la oferta gastronómica de tu barrio</p>

[flavor_module_listing module="bares" action="listar_bares" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Mi Negocio',
                'slug' => 'mi-negocio',
                'content' => '<h1>Mi Negocio</h1>

[flavor_module_dashboard module="bares"]',
                'parent' => 'bares',
            ],

            // BIBLIOTECA COMUNITARIA - 3 páginas
            [
                'title' => 'Biblioteca',
                'slug' => 'biblioteca',
                'content' => '<h1>Biblioteca Comunitaria</h1>
<p>Préstamo e intercambio de libros entre vecinos</p>

[flavor_module_listing module="biblioteca" action="listar_libros" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Prestar Libro',
                'slug' => 'prestar',
                'content' => '<h1>Prestar un Libro</h1>
<p>Añade un libro para compartir con la comunidad</p>

[flavor_module_form module="biblioteca" action="registrar_libro"]',
                'parent' => 'biblioteca',
            ],
            [
                'title' => 'Mis Préstamos',
                'slug' => 'mis-prestamos',
                'content' => '<h1>Mis Préstamos</h1>

[flavor_module_dashboard module="biblioteca"]',
                'parent' => 'biblioteca',
            ],

            // CARPOOLING - 3 páginas
            [
                'title' => 'Carpooling',
                'slug' => 'carpooling',
                'content' => '<h1>Viajes Compartidos</h1>
<p>Comparte coche y reduce costes y emisiones</p>

[flavor_module_listing module="carpooling" action="listar_viajes" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Publicar Viaje',
                'slug' => 'publicar-viaje',
                'content' => '<h1>Publicar Viaje</h1>
<p>Ofrece plazas en tu próximo viaje</p>

[flavor_module_form module="carpooling" action="crear_viaje"]',
                'parent' => 'carpooling',
            ],
            [
                'title' => 'Mis Viajes',
                'slug' => 'mis-viajes',
                'content' => '<h1>Mis Viajes</h1>

[flavor_module_dashboard module="carpooling"]',
                'parent' => 'carpooling',
            ],

            // BICICLETAS COMPARTIDAS - 3 páginas
            [
                'title' => 'Bicicletas Compartidas',
                'slug' => 'bicicletas',
                'content' => '<h1>Bicicletas Compartidas</h1>
<p>Préstamo y uso compartido de bicicletas comunitarias</p>

[flavor_module_listing module="bicicletas_compartidas" action="listar_bicicletas" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Reservar Bicicleta',
                'slug' => 'reservar',
                'content' => '<h1>Reservar Bicicleta</h1>

[flavor_module_form module="bicicletas_compartidas" action="reservar_bicicleta"]',
                'parent' => 'bicicletas',
            ],
            [
                'title' => 'Mis Reservas de Bici',
                'slug' => 'mis-reservas',
                'content' => '<h1>Mis Reservas</h1>

[flavor_module_dashboard module="bicicletas_compartidas"]',
                'parent' => 'bicicletas',
            ],

            // ESPACIOS COMUNES - 3 páginas
            [
                'title' => 'Espacios Comunes',
                'slug' => 'espacios',
                'content' => '<h1>Espacios Comunes</h1>
<p>Reserva espacios comunitarios para tus actividades</p>

[flavor_module_listing module="espacios_comunes" action="listar_espacios" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Reservar Espacio',
                'slug' => 'reservar',
                'content' => '<h1>Reservar Espacio</h1>
<p>Selecciona fecha, hora y espacio</p>

[flavor_module_form module="espacios_comunes" action="reservar_espacio"]',
                'parent' => 'espacios',
            ],
            [
                'title' => 'Mis Reservas de Espacios',
                'slug' => 'mis-reservas',
                'content' => '<h1>Mis Reservas</h1>

[flavor_module_dashboard module="espacios_comunes"]',
                'parent' => 'espacios',
            ],

            // HUERTOS URBANOS - 3 páginas
            [
                'title' => 'Huertos Urbanos',
                'slug' => 'huertos',
                'content' => '<h1>Huertos Urbanos</h1>
<p>Gestión de parcelas y cosechas comunitarias</p>

[flavor_module_listing module="huertos_urbanos" action="listar_huertos" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Solicitar Parcela',
                'slug' => 'solicitar-parcela',
                'content' => '<h1>Solicitar Parcela</h1>
<p>Solicita una parcela en los huertos comunitarios</p>

[flavor_module_form module="huertos_urbanos" action="solicitar_parcela"]',
                'parent' => 'huertos',
            ],
            [
                'title' => 'Mi Huerto',
                'slug' => 'mi-huerto',
                'content' => '<h1>Mi Huerto</h1>

[flavor_module_dashboard module="huertos_urbanos"]',
                'parent' => 'huertos',
            ],

            // COMPOSTAJE COMUNITARIO - 3 páginas
            [
                'title' => 'Compostaje',
                'slug' => 'compostaje',
                'content' => '<h1>Compostaje Comunitario</h1>
<p>Composteras y recogida de residuos orgánicos</p>

[flavor_module_listing module="compostaje" action="listar_composteras" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Registrar Aporte',
                'slug' => 'registrar',
                'content' => '<h1>Registrar Aporte</h1>
<p>Registra tu aporte de residuos orgánicos</p>

[flavor_module_form module="compostaje" action="registrar_aporte"]',
                'parent' => 'compostaje',
            ],
            [
                'title' => 'Mis Aportes',
                'slug' => 'mis-aportes',
                'content' => '<h1>Mis Aportes</h1>

[flavor_module_dashboard module="compostaje"]',
                'parent' => 'compostaje',
            ],

            // AYUDA VECINAL - 3 páginas
            [
                'title' => 'Ayuda Vecinal',
                'slug' => 'ayuda-vecinal',
                'content' => '<h1>Ayuda Vecinal</h1>
<p>Red de ayuda mutua entre vecinos</p>

[flavor_module_listing module="ayuda_vecinal" action="listar_solicitudes" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Pedir Ayuda',
                'slug' => 'pedir',
                'content' => '<h1>Solicitar Ayuda</h1>
<p>¿Necesitas ayuda? Tu comunidad está aquí</p>

[flavor_module_form module="ayuda_vecinal" action="solicitar_ayuda"]',
                'parent' => 'ayuda-vecinal',
            ],
            [
                'title' => 'Ofrecer Ayuda',
                'slug' => 'ofrecer',
                'content' => '<h1>Ofrecer Ayuda</h1>
<p>Ayuda a un vecino que lo necesita</p>

[flavor_module_form module="ayuda_vecinal" action="ofrecer_ayuda"]',
                'parent' => 'ayuda-vecinal',
            ],

            // CURSOS Y FORMACIÓN - 3 páginas
            [
                'title' => 'Cursos',
                'slug' => 'cursos',
                'content' => '<h1>Cursos y Formación</h1>
<p>Formación continua para la comunidad</p>

[flavor_module_listing module="cursos" action="listar_cursos" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Inscribirse en Curso',
                'slug' => 'inscribirse',
                'content' => '<h1>Inscríbete en el Curso</h1>

[flavor_module_form module="cursos" action="inscribirse"]',
                'parent' => 'cursos',
            ],
            [
                'title' => 'Mis Cursos',
                'slug' => 'mis-cursos',
                'content' => '<h1>Mis Cursos</h1>

[flavor_module_dashboard module="cursos"]',
                'parent' => 'cursos',
            ],

            // MULTIMEDIA - 3 páginas
            [
                'title' => 'Multimedia',
                'slug' => 'multimedia',
                'content' => '<h1>Galería Multimedia</h1>
<p>Fotos, vídeos y documentos de la comunidad</p>

[flavor_module_listing module="multimedia" action="listar_multimedia" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Subir Contenido',
                'slug' => 'subir',
                'content' => '<h1>Subir Contenido</h1>
<p>Comparte fotos, vídeos o documentos</p>

[flavor_module_form module="multimedia" action="subir_multimedia"]',
                'parent' => 'multimedia',
            ],
            [
                'title' => 'Mi Contenido',
                'slug' => 'mi-contenido',
                'content' => '<h1>Mi Contenido</h1>

[flavor_module_dashboard module="multimedia"]',
                'parent' => 'multimedia',
            ],

            // PODCAST - 2 páginas
            [
                'title' => 'Podcast',
                'slug' => 'podcast',
                'content' => '<h1>Podcast Comunitario</h1>
<p>Episodios y suscripciones</p>

[flavor_module_listing module="podcast" action="listar_episodios" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Mis Suscripciones',
                'slug' => 'mis-suscripciones',
                'content' => '<h1>Mis Suscripciones</h1>

[flavor_module_dashboard module="podcast"]',
                'parent' => 'podcast',
            ],

            // RADIO COMUNITARIA - 2 páginas
            [
                'title' => 'Radio Comunitaria',
                'slug' => 'radio',
                'content' => '<h1>Radio Comunitaria</h1>
<p>Programación y emisión en directo</p>

[flavor_module_listing module="radio" action="listar_programas" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Programación',
                'slug' => 'programacion',
                'content' => '<h1>Programación</h1>

[flavor_module_listing module="radio" action="programacion"]',
                'parent' => 'radio',
            ],

            // RECICLAJE - 2 páginas
            [
                'title' => 'Reciclaje',
                'slug' => 'reciclaje',
                'content' => '<h1>Puntos de Reciclaje</h1>
<p>Recogida selectiva y estadísticas ambientales</p>

[flavor_module_listing module="reciclaje" action="listar_puntos_reciclaje" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Estadísticas Reciclaje',
                'slug' => 'estadisticas',
                'content' => '<h1>Estadísticas de Reciclaje</h1>

[flavor_module_listing module="reciclaje" action="estadisticas_reciclaje"]',
                'parent' => 'reciclaje',
            ],

            // RED SOCIAL - 3 páginas
            [
                'title' => 'Red Social',
                'slug' => 'red-social',
                'content' => '<h1>Red Social</h1>
<p>Publicaciones, perfiles y seguidores</p>

[flavor_module_listing module="red_social" action="listar_publicaciones" columnas="1" limite="20"]',
                'parent' => 0,
            ],
            [
                'title' => 'Publicar',
                'slug' => 'publicar',
                'content' => '<h1>Nueva Publicación</h1>

[flavor_module_form module="red_social" action="crear_publicacion"]',
                'parent' => 'red-social',
            ],
            [
                'title' => 'Mi Perfil',
                'slug' => 'perfil',
                'content' => '<h1>Mi Perfil</h1>

[flavor_module_dashboard module="red_social"]',
                'parent' => 'red-social',
            ],

            // TRÁMITES - 3 páginas
            [
                'title' => 'Trámites',
                'slug' => 'tramites',
                'content' => '<h1>Trámites y Gestiones</h1>
<p>Solicitudes y gestiones administrativas</p>

[flavor_module_listing module="tramites" action="listar_tramites" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Nueva Solicitud',
                'slug' => 'nueva-solicitud',
                'content' => '<h1>Nueva Solicitud</h1>
<p>Inicia un trámite administrativo</p>

[flavor_module_form module="tramites" action="iniciar_tramite"]',
                'parent' => 'tramites',
            ],
            [
                'title' => 'Mis Trámites',
                'slug' => 'mis-tramites',
                'content' => '<h1>Mis Trámites</h1>

[flavor_module_dashboard module="tramites"]',
                'parent' => 'tramites',
            ],

            // TRANSPARENCIA - 2 páginas
            [
                'title' => 'Transparencia',
                'slug' => 'transparencia',
                'content' => '<h1>Portal de Transparencia</h1>
<p>Datos públicos, presupuestos y rendición de cuentas</p>

[flavor_module_listing module="transparencia" action="listar_documentos" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Datos Abiertos',
                'slug' => 'datos-abiertos',
                'content' => '<h1>Datos Abiertos</h1>

[flavor_module_listing module="transparencia" action="datos_abiertos"]',
                'parent' => 'transparencia',
            ],

            // COLECTIVOS - 3 páginas
            [
                'title' => 'Colectivos',
                'slug' => 'colectivos',
                'content' => '<h1>Colectivos y Asociaciones</h1>
<p>Descubre los colectivos de tu comunidad</p>

[flavor_module_listing module="colectivos" action="listar_colectivos" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Crear Colectivo',
                'slug' => 'crear',
                'content' => '<h1>Crear Colectivo</h1>
<p>Funda un nuevo colectivo en tu comunidad</p>

[flavor_module_form module="colectivos" action="crear_colectivo"]',
                'parent' => 'colectivos',
            ],
            [
                'title' => 'Mi Colectivo',
                'slug' => 'mi-colectivo',
                'content' => '<h1>Mi Colectivo</h1>

[flavor_module_dashboard module="colectivos"]',
                'parent' => 'colectivos',
            ],

            // COMUNIDADES - 3 páginas
            [
                'title' => 'Comunidades',
                'slug' => 'comunidades',
                'content' => '<h1>Comunidades</h1>
<p>Sub-comunidades y grupos temáticos</p>

[flavor_module_listing module="comunidades" action="listar_comunidades" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Crear Comunidad',
                'slug' => 'crear',
                'content' => '<h1>Crear Comunidad</h1>

[flavor_module_form module="comunidades" action="crear_comunidad"]',
                'parent' => 'comunidades',
            ],
            [
                'title' => 'Mi Comunidad',
                'slug' => 'mi-comunidad',
                'content' => '<h1>Mi Comunidad</h1>

[flavor_module_dashboard module="comunidades"]',
                'parent' => 'comunidades',
            ],

            // PARKINGS COMPARTIDOS - 3 páginas
            [
                'title' => 'Parkings',
                'slug' => 'parkings',
                'content' => '<h1>Parkings Compartidos</h1>
<p>Plazas de aparcamiento compartidas entre vecinos</p>

[flavor_module_listing module="parkings" action="listar_parkings" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Publicar Plaza',
                'slug' => 'publicar',
                'content' => '<h1>Publicar Plaza de Parking</h1>
<p>Ofrece tu plaza cuando no la uses</p>

[flavor_module_form module="parkings" action="publicar_parking"]',
                'parent' => 'parkings',
            ],
            [
                'title' => 'Mis Plazas',
                'slug' => 'mis-plazas',
                'content' => '<h1>Mis Plazas</h1>

[flavor_module_dashboard module="parkings"]',
                'parent' => 'parkings',
            ],

            // CHAT GRUPOS - 2 páginas
            [
                'title' => 'Chat de Grupos',
                'slug' => 'chat-grupos',
                'content' => '<h1>Chat de Grupos</h1>
<p>Canales de comunicación comunitaria</p>

[flavor_module_listing module="chat_grupos" action="grupos_publicos" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => 'Mis Grupos',
                'slug' => 'mis-grupos',
                'content' => '<h1>Mis Grupos</h1>

[flavor_module_dashboard module="chat_grupos"]',
                'parent' => 'chat-grupos',
            ],

            // CHAT INTERNO - 1 página
            [
                'title' => 'Mensajes',
                'slug' => 'mensajes',
                'content' => '<h1>Mis Mensajes</h1>

[flavor_module_dashboard module="chat_interno"]',
                'parent' => 0,
            ],

            // CLIENTES (CRM - Admin) - 2 páginas
            [
                'title' => 'Clientes',
                'slug' => 'clientes',
                'content' => '<h1>Gestión de Clientes</h1>

[flavor_module_listing module="clientes" action="listar_clientes" columnas="2" limite="20"]',
                'parent' => 0,
            ],
            [
                'title' => 'Nuevo Cliente',
                'slug' => 'nuevo',
                'content' => '<h1>Nuevo Cliente</h1>

[flavor_module_form module="clientes" action="crear_cliente"]',
                'parent' => 'clientes',
            ],

            // EMPRESARIAL - 2 páginas
            [
                'title' => 'Empresarial',
                'slug' => 'empresarial',
                'content' => '<h1>Gestión Empresarial</h1>
<p>CRM, proyectos y tareas</p>

[flavor_module_dashboard module="empresarial"]',
                'parent' => 0,
            ],
            [
                'title' => 'Contacto Empresarial',
                'slug' => 'contacto',
                'content' => '<h1>Contacto</h1>

[flavor_module_form module="empresarial" action="contacto"]',
                'parent' => 'empresarial',
            ],
        ];
    }

    /**
     * Obtiene páginas filtradas por módulos.
     *
     * @param array $module_ids
     * @return array
     */
    private static function get_pages_for_modules($module_ids) {
        $all_pages = self::get_pages_to_create();
        $modules = array_values(array_unique(array_map([__CLASS__, 'normalize_module_id'], (array) $module_ids)));
        if (empty($modules)) {
            return $all_pages;
        }

        $filtered = [];
        foreach ($all_pages as $page) {
            $page_modules = self::extract_modules_from_page($page);
            if (empty($page_modules)) {
                $filtered[] = $page;
                continue;
            }
            $matches = array_intersect($modules, $page_modules);
            if (!empty($matches)) {
                $filtered[] = $page;
            }
        }

        return self::ensure_parent_pages($filtered, $all_pages);
    }

    /**
     * Normaliza ID de módulo.
     */
    private static function normalize_module_id($module_id) {
        $module_id = is_string($module_id) ? $module_id : '';
        $module_id = trim(str_replace('-', '_', $module_id));
        return $module_id;
    }

    /**
     * Extrae módulos a partir de un array de página.
     *
     * @param array $page
     * @return array
     */
    private static function extract_modules_from_page($page) {
        if (!empty($page['module'])) {
            return [self::normalize_module_id($page['module'])];
        }
        if (!empty($page['modules']) && is_array($page['modules'])) {
            return array_values(array_unique(array_map([__CLASS__, 'normalize_module_id'], $page['modules'])));
        }
        $content = $page['content'] ?? '';
        return self::extract_modules_from_content($content);
    }

    /**
     * Extrae módulos desde el contenido (shortcodes).
     *
     * @param string $content
     * @return array
     */
    private static function extract_modules_from_content($content) {
        if (!is_string($content) || $content === '') {
            return [];
        }
        $modules = [];
        if (preg_match_all('/module=\"([a-z0-9_\\-]+)\"/i', $content, $matches)) {
            foreach ($matches[1] as $module_id) {
                $module_id = self::normalize_module_id($module_id);
                if ($module_id !== '') {
                    $modules[] = $module_id;
                }
            }
        }
        if (preg_match('/\\[gc_[a-z0-9_]+/i', $content)) {
            $modules[] = 'grupos_consumo';
        }
        return array_values(array_unique($modules));
    }

    /**
     * Asegura que los padres necesarios estén incluidos.
     *
     * @param array $filtered
     * @param array $all_pages
     * @return array
     */
    private static function ensure_parent_pages($filtered, $all_pages) {
        $by_slug = [];
        foreach ($all_pages as $page) {
            if (!empty($page['slug'])) {
                $by_slug[$page['slug']] = $page;
            }
        }
        $slugs = array_column($filtered, 'slug');
        $added = true;
        while ($added) {
            $added = false;
            foreach ($filtered as $page) {
                $parent = $page['parent'] ?? 0;
                if (is_string($parent) && $parent !== '' && !in_array($parent, $slugs, true)) {
                    if (isset($by_slug[$parent])) {
                        $filtered[] = $by_slug[$parent];
                        $slugs[] = $parent;
                        $added = true;
                    }
                }
            }
        }
        return $filtered;
    }

    /**
     * Busca una página existente a partir de su definición.
     *
     * @param array $page_data
     * @return WP_Post|null
     */
    private static function find_existing_page($page_data) {
        $page = null;
        if (!empty($page_data['parent']) && is_string($page_data['parent'])) {
            $parent_page = get_page_by_path($page_data['parent']);
            $parent_id = $parent_page ? $parent_page->ID : 0;
            $args = [
                'name' => $page_data['slug'],
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'post_parent' => $parent_id,
            ];
            $pages = get_posts($args);
            if (!empty($pages)) {
                $page = $pages[0];
            }
        } else {
            $page = get_page_by_path($page_data['slug']);
        }

        return $page;
    }

    /**
     * Determina si se puede actualizar el contenido existente.
     *
     * @param string $module_id
     * @param WP_Post $page
     * @return bool
     */
    private static function should_update_page($module_id, $page) {
        $flag = get_post_meta($page->ID, '_flavor_auto_page', true);
        if ($flag) {
            return true;
        }
        $content = $page->post_content ?? '';
        if ($module_id === 'grupos_consumo') {
            return strpos($content, '[gc_') !== false;
        }
        return false;
    }

    /**
     * Actualiza o crea páginas para un módulo específico.
     *
     * @param string $module_id
     * @return array
     */
    public static function refresh_module_pages($module_id) {
        // DESACTIVADO: Evitar recreación automática de páginas
        // Las páginas se deben crear manualmente desde el admin
        return ['created' => [], 'updated' => [], 'skipped' => []];

        $module_id = self::normalize_module_id($module_id);
        self::ensure_wp_post_revisions_constant();
        $pages_data = self::get_pages_for_modules([$module_id]);
        $created = [];
        $updated = [];
        $skipped = [];

        foreach ($pages_data as $page_data) {
            $page = self::find_existing_page($page_data);
            if ($page) {
                if (self::should_update_page($module_id, $page)) {
                    wp_update_post([
                        'ID' => $page->ID,
                        'post_title' => $page_data['title'],
                        'post_content' => $page_data['content'],
                    ]);
                    update_post_meta($page->ID, '_flavor_auto_page', 1);
                    update_post_meta($page->ID, '_flavor_auto_page_modules', implode(',', self::extract_modules_from_page($page_data)));
                    $updated[] = $page_data['title'];
                } else {
                    $skipped[] = $page_data['title'];
                }
                continue;
            }

            $parent_id = 0;
            if (!empty($page_data['parent']) && is_string($page_data['parent'])) {
                $parent_page = get_page_by_path($page_data['parent']);
                $parent_id = $parent_page ? $parent_page->ID : 0;
            }

            $page_id = wp_insert_post([
                'post_title'    => $page_data['title'],
                'post_name'     => $page_data['slug'],
                'post_content'  => $page_data['content'],
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_parent'   => $parent_id,
                'post_author'   => get_current_user_id(),
            ]);

            if (!is_wp_error($page_id)) {
                update_post_meta($page_id, '_flavor_auto_page', 1);
                update_post_meta($page_id, '_flavor_auto_page_modules', implode(',', self::extract_modules_from_page($page_data)));
                update_post_meta($page_id, '_flavor_full_width', 1);

                // Intentar asignar template full-width si el tema lo soporta
                if ($page_data['full_width'] ?? true) {
                    // Buscar template full-width disponible en el tema
                    $templates = ['template-full-width.php', 'full-width.php', 'page-templates/full-width.php'];
                    foreach ($templates as $template) {
                        $template_file = get_template_directory() . '/' . $template;
                        if (file_exists($template_file)) {
                            update_post_meta($page_id, '_wp_page_template', $template);
                            break;
                        }
                    }
                }

                $created[] = $page_data['title'];
            }
        }

        if (!empty($created) || !empty($updated)) {
            flush_rewrite_rules();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Crea páginas solo para módulos indicados.
     *
     * @param array $module_ids
     * @return array
     */
    public static function create_pages_for_modules($module_ids) {
        // DESACTIVADO: Evitar creación automática de páginas
        return ['created' => [], 'errors' => []];

        self::ensure_wp_post_revisions_constant();
        $pages_data = self::get_pages_for_modules($module_ids);
        return self::create_pages_from_list($pages_data);
    }

    /**
     * Asegura que la constante WP_POST_REVISIONS exista para evitar errores fatales.
     *
     * @return void
     */
    private static function ensure_wp_post_revisions_constant() {
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', true);
        }
    }

    /**
     * Crea páginas a partir de una lista de definiciones.
     *
     * @param array $pages_data
     * @return array
     */
    private static function create_pages_from_list($pages_data) {
        $pages_created = [];
        $pages_skipped = [];
        $parent_ids = [];

        // Separar páginas padre e hijas para crear en orden correcto
        $parent_pages = [];
        $child_pages = [];

        foreach ($pages_data as $page_data) {
            if (($page_data['parent'] ?? 0) === 0) {
                $parent_pages[] = $page_data;
            } else {
                $child_pages[] = $page_data;
            }
        }

        // PASO 1: Crear páginas padre primero
        foreach ($parent_pages as $page_data) {
            // Verificar si la página ya existe
            $existing_page = get_page_by_path($page_data['slug']);

            if ($existing_page) {
                $pages_skipped[] = $page_data['title'];
                $parent_ids[$page_data['slug']] = $existing_page->ID;

                $pages_created[] = [
                    'id' => $existing_page->ID,
                    'title' => $page_data['title'],
                    'slug' => $page_data['slug'],
                    'url' => get_permalink($existing_page->ID),
                    'already_existed' => true,
                ];
                continue;
            }

            $page_id = wp_insert_post([
                'post_title'    => $page_data['title'],
                'post_name'     => $page_data['slug'],
                'post_content'  => $page_data['content'],
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_parent'   => 0,
                'post_author'   => get_current_user_id(),
            ]);

            if (!is_wp_error($page_id)) {
                $pages_created[] = [
                    'id' => $page_id,
                    'title' => $page_data['title'],
                    'slug' => $page_data['slug'],
                    'url' => get_permalink($page_id),
                ];

                $parent_ids[$page_data['slug']] = $page_id;
                update_post_meta($page_id, '_flavor_auto_page', 1);
                update_post_meta($page_id, '_flavor_auto_page_modules', implode(',', self::extract_modules_from_page($page_data)));
            }
        }

        // PASO 2: Crear páginas hijas
        foreach ($child_pages as $page_data) {
            $parent_id = 0;
            if (!empty($page_data['parent']) && is_string($page_data['parent'])) {
                if (isset($parent_ids[$page_data['parent']])) {
                    $parent_id = $parent_ids[$page_data['parent']];
                } else {
                    $parent_page = get_page_by_path($page_data['parent']);
                    if ($parent_page) {
                        $parent_id = $parent_page->ID;
                        $parent_ids[$page_data['parent']] = $parent_id;
                    }
                }
            }

            $existing_page = null;
            $args = [
                'name' => $page_data['slug'],
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'post_parent' => $parent_id,
            ];
            $pages = get_posts($args);
            if (!empty($pages)) {
                $existing_page = $pages[0];
            }

            if ($existing_page) {
                $pages_skipped[] = $page_data['title'];
                continue;
            }

            $page_id = wp_insert_post([
                'post_title'    => $page_data['title'],
                'post_name'     => $page_data['slug'],
                'post_content'  => $page_data['content'],
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_parent'   => $parent_id,
                'post_author'   => get_current_user_id(),
            ]);

            if (!is_wp_error($page_id)) {
                $pages_created[] = [
                    'id' => $page_id,
                    'title' => $page_data['title'],
                    'slug' => $page_data['slug'],
                    'url' => get_permalink($page_id),
                ];
                update_post_meta($page_id, '_flavor_auto_page', 1);
                update_post_meta($page_id, '_flavor_auto_page_modules', implode(',', self::extract_modules_from_page($page_data)));
            }
        }

        flush_rewrite_rules();

        return [
            'created' => $pages_created,
            'skipped' => $pages_skipped,
            'total' => count($pages_created),
        ];
    }

    /**
     * Crea todas las páginas
     */
    public static function create_all_pages() {
        $pages_data = self::get_pages_to_create();
        return self::create_pages_from_list($pages_data);
    }

    /**
     * Elimina todas las páginas creadas (para testing)
     */
    public static function delete_all_pages() {
        $pages_data = self::get_pages_to_create();
        $deleted = [];

        foreach ($pages_data as $page_data) {
            $page = null;

            // Buscar de forma robusta (igual que en get_pages_status)
            if (!empty($page_data['parent']) && is_string($page_data['parent'])) {
                $parent_page = get_page_by_path($page_data['parent']);
                $parent_id = $parent_page ? $parent_page->ID : 0;

                $args = [
                    'name' => $page_data['slug'],
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'post_parent' => $parent_id,
                ];

                $pages = get_posts($args);
                if (!empty($pages)) {
                    $page = $pages[0];
                }
            } else {
                $page = get_page_by_path($page_data['slug']);
            }

            if ($page) {
                wp_delete_post($page->ID, true); // true = forzar eliminación permanente
                $deleted[] = $page_data['title'];
            }
        }

        flush_rewrite_rules();

        return $deleted;
    }

    /**
     * Obtiene el estado de las páginas
     */
    public static function get_pages_status() {
        $pages_data = self::get_pages_to_create();
        $status = [
            'exists' => [],
            'missing' => [],
        ];

        foreach ($pages_data as $page_data) {
            // Buscar la página de forma más robusta
            $page = null;

            // Si tiene parent, buscar por nombre de post y parent
            if (!empty($page_data['parent']) && is_string($page_data['parent'])) {
                $parent_page = get_page_by_path($page_data['parent']);
                $parent_id = $parent_page ? $parent_page->ID : 0;

                // Buscar página hija por slug y parent
                $args = [
                    'name' => $page_data['slug'],
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'post_parent' => $parent_id,
                ];

                $pages = get_posts($args);
                if (!empty($pages)) {
                    $page = $pages[0];
                }
            } else {
                // Página padre, buscar normalmente
                $page = get_page_by_path($page_data['slug']);
            }

            if ($page) {
                $status['exists'][] = [
                    'title' => $page_data['title'],
                    'slug' => $page_data['slug'],
                    'url' => get_permalink($page->ID),
                ];
            } else {
                $status['missing'][] = [
                    'title' => $page_data['title'],
                    'slug' => $page_data['slug'],
                ];
            }
        }

        return $status;
    }
}

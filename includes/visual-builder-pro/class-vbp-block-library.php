<?php
/**
 * Visual Builder Pro - Librería de Bloques
 *
 * Registra todos los bloques disponibles incluyendo secciones,
 * componentes básicos y widgets de módulos.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar la librería de bloques
 *
 * @since 2.0.0
 */
class Flavor_VBP_Block_Library {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Block_Library|null
     */
    private static $instancia = null;

    /**
     * Bloques registrados
     *
     * @var array
     */
    private $bloques = array();

    /**
     * Categorías de bloques
     *
     * @var array
     */
    private $categorias = array();

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Block_Library
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->registrar_categorias();
        $this->registrar_bloques_secciones();
        $this->registrar_bloques_basicos();
        $this->registrar_bloques_layout();
        $this->registrar_bloques_formularios();
        $this->registrar_bloques_media();
        $this->registrar_bloques_modulos();
        $this->registrar_bloques_dashboard_widgets();

        // Hook para que otros plugins puedan añadir bloques
        do_action( 'vbp_register_blocks', $this );
    }

    /**
     * Campos de estilo comunes para todos los bloques de módulos
     *
     * @return array
     */
    private function get_campos_estilo_comunes() {
        return array(
            '_separator_estilo' => array(
                'type'  => 'separator',
                'label' => __( '🎨 Estilo Visual', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'esquema_color' => array(
                'type'    => 'select',
                'label'   => __( 'Esquema de color', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'default'   => __( 'Por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'primary'   => __( 'Primario (azul)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'success'   => __( 'Éxito (verde)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'warning'   => __( 'Advertencia (amarillo)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'danger'    => __( 'Peligro (rojo)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'purple'    => __( 'Púrpura', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'pink'      => __( 'Rosa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'dark'      => __( 'Oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'custom'    => __( 'Personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'default',
            ),
            'color_primario' => array(
                'type'      => 'color',
                'label'     => __( 'Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default'   => '#3b82f6',
                'condition' => array( 'esquema_color' => 'custom' ),
            ),
            'color_secundario' => array(
                'type'      => 'color',
                'label'     => __( 'Color secundario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default'   => '#64748b',
                'condition' => array( 'esquema_color' => 'custom' ),
            ),
            'radio_bordes' => array(
                'type'    => 'select',
                'label'   => __( 'Bordes redondeados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'none'   => __( 'Sin redondear', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'sm'     => __( 'Pequeño (4px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'md'     => __( 'Mediano (8px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'lg'     => __( 'Grande (12px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'xl'     => __( 'Extra grande (16px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'full'   => __( 'Completo (circular)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'lg',
            ),
            'sombra' => array(
                'type'    => 'select',
                'label'   => __( 'Sombra', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'none' => __( 'Sin sombra', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'sm'   => __( 'Sutil', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'md'   => __( 'Media', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'lg'   => __( 'Pronunciada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'xl'   => __( 'Muy pronunciada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'md',
            ),
            'animacion_entrada' => array(
                'type'    => 'select',
                'label'   => __( 'Animación de entrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'none'      => __( 'Sin animación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'fade'      => __( 'Aparecer', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'slide-up'  => __( 'Deslizar arriba', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'slide-down'=> __( 'Deslizar abajo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'zoom'      => __( 'Zoom', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'bounce'    => __( 'Rebote', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'fade',
            ),
        );
    }

    /**
     * Campos de cabecera de sección (título, subtítulo, colores de fondo)
     * Para bloques que necesitan un wrapper de sección con encabezado.
     *
     * @return array
     */
    private function get_campos_header_seccion() {
        return array(
            '_separator_seccion' => array(
                'type'  => 'separator',
                'label' => __( '📋 Cabecera de Sección', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'titulo' => array(
                'type'    => 'text',
                'label'   => __( 'Título de sección', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '',
                'ai'      => true,
            ),
            'subtitulo' => array(
                'type'    => 'text',
                'label'   => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '',
                'ai'      => true,
            ),
            'titulo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color del título', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#ffffff',
            ),
            'subtitulo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color del subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#9CA3AF',
            ),
            'color_fondo' => array(
                'type'    => 'color',
                'label'   => __( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => 'transparent',
            ),
        );
    }

    /**
     * Campos de colores para secciones (Hero, CTA, Features, etc.)
     *
     * @return array
     */
    private function get_campos_colores_seccion() {
        return array(
            // Colores de texto
            '_separator_colores_texto' => array(
                'type'  => 'separator',
                'label' => __( '🎨 Colores de Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'titulo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color del título', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#1f2937',
            ),
            'subtitulo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color del subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#6b7280',
            ),
            'texto_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color del texto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#374151',
            ),

            // Colores de botón
            '_separator_colores_boton' => array(
                'type'  => 'separator',
                'label' => __( '🔘 Colores de Botón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'boton_color_fondo' => array(
                'type'    => 'color',
                'label'   => __( 'Fondo del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#3b82f6',
            ),
            'boton_color_texto' => array(
                'type'    => 'color',
                'label'   => __( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#ffffff',
            ),
            'boton_color_hover' => array(
                'type'    => 'color',
                'label'   => __( 'Fondo hover', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#2563eb',
            ),

            // Fondo de sección
            '_separator_fondo_seccion' => array(
                'type'  => 'separator',
                'label' => __( '🖼️ Fondo de Sección', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'seccion_fondo_tipo' => array(
                'type'    => 'select',
                'label'   => __( 'Tipo de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'color'    => __( 'Color sólido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'gradient' => __( 'Gradiente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'image'    => __( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'color',
            ),
            'seccion_fondo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#ffffff',
            ),
            'seccion_fondo_gradiente_inicio' => array(
                'type'    => 'color',
                'label'   => __( 'Gradiente inicio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#3b82f6',
            ),
            'seccion_fondo_gradiente_fin' => array(
                'type'    => 'color',
                'label'   => __( 'Gradiente fin', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#8b5cf6',
            ),
            'seccion_fondo_imagen' => array(
                'type'  => 'image',
                'label' => __( 'Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'seccion_overlay_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => 'rgba(0,0,0,0.5)',
            ),

            // Tarjetas/Cards
            '_separator_tarjetas' => array(
                'type'  => 'separator',
                'label' => __( '🃏 Colores de Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'card_fondo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Fondo de tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#ffffff',
            ),
            'card_borde_color' => array(
                'type'    => 'color',
                'label'   => __( 'Borde de tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#e5e7eb',
            ),
            'card_titulo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Título de tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#1f2937',
            ),
            'card_texto_color' => array(
                'type'    => 'color',
                'label'   => __( 'Texto de tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#6b7280',
            ),
            'card_icono_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color de iconos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#3b82f6',
            ),

            // Acentos
            '_separator_acentos' => array(
                'type'  => 'separator',
                'label' => __( '✨ Colores de Acento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'acento_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color de acento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#3b82f6',
            ),
            'destacado_fondo' => array(
                'type'    => 'color',
                'label'   => __( 'Fondo destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#eff6ff',
            ),
            'destacado_borde' => array(
                'type'    => 'color',
                'label'   => __( 'Borde destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => '#3b82f6',
            ),
        );
    }

    /**
     * Campos para bloques tipo listado/grid
     *
     * @return array
     */
    private function get_campos_listado() {
        return array(
            '_separator_layout' => array(
                'type'  => 'separator',
                'label' => __( '📐 Disposición', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'columnas' => array(
                'type'    => 'select',
                'label'   => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    '1' => '1 columna',
                    '2' => '2 columnas',
                    '3' => '3 columnas',
                    '4' => '4 columnas',
                    'auto' => __( 'Automático', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => '3',
            ),
            'columnas_tablet' => array(
                'type'    => 'select',
                'label'   => __( 'Columnas (tablet)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    '1' => '1 columna',
                    '2' => '2 columnas',
                    '3' => '3 columnas',
                ),
                'default' => '2',
            ),
            'columnas_movil' => array(
                'type'    => 'select',
                'label'   => __( 'Columnas (móvil)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    '1' => '1 columna',
                    '2' => '2 columnas',
                ),
                'default' => '1',
            ),
            'espacio_items' => array(
                'type'    => 'select',
                'label'   => __( 'Espacio entre items', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'xs'  => __( 'Muy pequeño (8px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'sm'  => __( 'Pequeño (12px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'md'  => __( 'Mediano (16px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'lg'  => __( 'Grande (24px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'xl'  => __( 'Extra grande (32px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'md',
            ),
        );
    }

    /**
     * Campos para bloques tipo tarjeta
     *
     * @return array
     */
    private function get_campos_tarjeta() {
        return array(
            '_separator_tarjeta' => array(
                'type'  => 'separator',
                'label' => __( '🃏 Estilo de Tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'estilo_tarjeta' => array(
                'type'    => 'select',
                'label'   => __( 'Estilo de tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'default'    => __( 'Por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'elevated'   => __( 'Elevada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'outlined'   => __( 'Con borde', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'filled'     => __( 'Rellena', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'glass'      => __( 'Cristal (glassmorphism)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'gradient'   => __( 'Gradiente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'minimal'    => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'elevated',
            ),
            'mostrar_imagen' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar imagen destacada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => true,
            ),
            'ratio_imagen' => array(
                'type'      => 'select',
                'label'     => __( 'Proporción imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options'   => array(
                    '1:1'   => __( 'Cuadrada (1:1)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    '4:3'   => __( 'Estándar (4:3)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    '16:9'  => __( 'Panorámica (16:9)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    '3:2'   => __( 'Fotografía (3:2)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    '21:9'  => __( 'Cine (21:9)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default'   => '16:9',
                'condition' => array( 'mostrar_imagen' => true ),
            ),
            'hover_effect' => array(
                'type'    => 'select',
                'label'   => __( 'Efecto hover', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'none'      => __( 'Ninguno', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'lift'      => __( 'Elevar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'grow'      => __( 'Crecer', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'glow'      => __( 'Resplandor', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'border'    => __( 'Borde color', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'overlay'   => __( 'Superposición', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'lift',
            ),
            'mostrar_badges' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar badges/etiquetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => true,
            ),
            'mostrar_meta' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar meta info (fecha, autor)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => true,
            ),
        );
    }

    /**
     * Campos para bloques con cabecera
     *
     * @return array
     */
    private function get_campos_cabecera() {
        return array(
            '_separator_cabecera' => array(
                'type'  => 'separator',
                'label' => __( '📝 Cabecera', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'mostrar_titulo' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar título', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => true,
            ),
            'titulo_personalizado' => array(
                'type'      => 'text',
                'label'     => __( 'Título personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default'   => '',
                'placeholder' => __( 'Dejar vacío para usar título por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'condition' => array( 'mostrar_titulo' => true ),
            ),
            'mostrar_descripcion' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => false,
            ),
            'descripcion' => array(
                'type'      => 'textarea',
                'label'     => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default'   => '',
                'rows'      => 2,
                'condition' => array( 'mostrar_descripcion' => true ),
            ),
            'mostrar_icono' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => true,
            ),
            'alineacion_cabecera' => array(
                'type'    => 'select',
                'label'   => __( 'Alineación cabecera', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'left'   => __( 'Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'center' => __( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'right'  => __( 'Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'left',
            ),
        );
    }

    /**
     * Campos para paginación y carga
     *
     * @return array
     */
    private function get_campos_paginacion() {
        return array(
            '_separator_paginacion' => array(
                'type'  => 'separator',
                'label' => __( '📄 Paginación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'paginacion' => array(
                'type'    => 'select',
                'label'   => __( 'Tipo de paginación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'options' => array(
                    'none'     => __( 'Sin paginación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'numbers'  => __( 'Números de página', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'loadmore' => __( 'Botón cargar más', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'infinite' => __( 'Scroll infinito', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'loadmore',
            ),
            'items_pagina' => array(
                'type'      => 'number',
                'label'     => __( 'Items por página', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default'   => 12,
                'min'       => 3,
                'max'       => 48,
                'condition' => array( 'paginacion' => array( 'numbers', 'loadmore', 'infinite' ) ),
            ),
        );
    }

    /**
     * Registra las categorías de bloques
     */
    private function registrar_categorias() {
        $this->categorias = array(
            'sections' => array(
                'id'    => 'sections',
                'name'  => __( 'Secciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
                'order' => 10,
            ),
            'basic' => array(
                'id'    => 'basic',
                'name'  => __( 'Básicos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
                'order' => 20,
            ),
            'layout' => array(
                'id'    => 'layout',
                'name'  => __( 'Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
                'order' => 30,
            ),
            'forms' => array(
                'id'    => 'forms',
                'name'  => __( 'Formularios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
                'order' => 40,
            ),
            'media' => array(
                'id'    => 'media',
                'name'  => __( 'Media', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5,3 19,12 5,21"/></svg>',
                'order' => 50,
            ),
            'modules' => array(
                'id'    => 'modules',
                'name'  => __( 'Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
                'order' => 60,
            ),
            'maps' => array(
                'id'    => 'maps',
                'name'  => __( 'Mapas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1,6 1,22 8,18 16,22 23,18 23,2 16,6 8,2"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
                'order' => 70,
            ),
            'economy' => array(
                'id'    => 'economy',
                'name'  => __( 'Economía Social', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 100 4h4a2 2 0 110 4H8"/><path d="M12 6v2m0 8v2"/></svg>',
                'order' => 80,
            ),
            'community' => array(
                'id'    => 'community',
                'name'  => __( 'Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
                'order' => 90,
            ),
            'dashboard' => array(
                'id'    => 'dashboard',
                'name'  => __( 'Widgets Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="3" height="5" fill="currentColor" opacity="0.3"/><rect x="14" y="7" width="3" height="9" fill="currentColor" opacity="0.3"/><path d="M7 16h3M14 16h3"/></svg>',
                'order' => 95,
            ),
            'dynamic' => array(
                'id'    => 'dynamic',
                'name'  => __( 'Campos Dinámicos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15l3 3 3-3"/></svg>',
                'order' => 100,
            ),
        );
    }

    /**
     * Registra bloques de tipo sección
     */
    private function registrar_bloques_secciones() {
        // Hero
        $this->registrar_bloque( array(
            'id'       => 'hero',
            'name'     => __( 'Hero', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
            'variants' => array(
                'fullscreen'   => __( 'Pantalla completa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'split'        => __( 'Dividido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'centered'     => __( 'Centrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'video'        => __( 'Con video', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'slider'       => __( 'Slider', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'glassmorphism'=> __( 'Glassmorphism', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'gradient'     => __( 'Gradiente animado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'parallax'     => __( 'Parallax', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'particles'    => __( 'Con partículas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'minimal'      => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                '3d'           => __( 'Efecto 3D', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'typewriter'   => __( 'Efecto máquina de escribir', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                // Contenido
                '_separator_contenido' => array( 'type' => 'separator', 'label' => __( '📝 Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'titulo'        => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo'     => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'boton_texto'   => array( 'type' => 'text', 'label' => __( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_url'     => array( 'type' => 'url', 'label' => __( 'URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_2_texto' => array( 'type' => 'text', 'label' => __( 'Segundo botón (texto)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_2_url'   => array( 'type' => 'url', 'label' => __( 'Segundo botón (URL)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),

                // Colores de texto
                '_separator_colores_texto' => array( 'type' => 'separator', 'label' => __( '🎨 Colores de Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'titulo_color'    => array( 'type' => 'color', 'label' => __( 'Color del título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '#ffffff' ),
                'subtitulo_color' => array( 'type' => 'color', 'label' => __( 'Color del subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '#e0e0e0' ),

                // Colores de botones
                '_separator_colores_botones' => array( 'type' => 'separator', 'label' => __( '🔘 Botón Principal', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_color_fondo' => array( 'type' => 'color', 'label' => __( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '#3b82f6' ),
                'boton_color_texto' => array( 'type' => 'color', 'label' => __( 'Color del texto', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '#ffffff' ),
                'boton_border_radius' => array( 'type' => 'text', 'label' => __( 'Border radius', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '8px' ),

                '_separator_boton_secundario' => array( 'type' => 'separator', 'label' => __( '🔘 Botón Secundario', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_2_color_fondo' => array( 'type' => 'color', 'label' => __( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => 'transparent' ),
                'boton_2_color_texto' => array( 'type' => 'color', 'label' => __( 'Color del texto', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '#ffffff' ),
                'boton_2_color_borde' => array( 'type' => 'color', 'label' => __( 'Color del borde', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '#ffffff' ),

                // Fondo
                '_separator_fondo' => array( 'type' => 'separator', 'label' => __( '🖼️ Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'imagen_fondo'  => array( 'type' => 'image', 'label' => __( 'Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'video_url'     => array( 'type' => 'url', 'label' => __( 'URL del video (YouTube/Vimeo)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'color_fondo'   => array( 'type' => 'color', 'label' => __( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '#1a1a2e' ),
                'overlay_color' => array( 'type' => 'color', 'label' => __( 'Color overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'overlay_opacity' => array( 'type' => 'range', 'label' => __( 'Opacidad overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'min' => 0, 'max' => 100 ),

                // Layout
                '_separator_layout' => array( 'type' => 'separator', 'label' => __( '📐 Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'altura'        => array( 'type' => 'select', 'label' => __( 'Altura', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'auto' => 'Auto', '50vh' => '50%', '75vh' => '75%', '100vh' => '100%' ) ),
                'alineacion'    => array( 'type' => 'select', 'label' => __( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha' ) ),
                'padding_vertical' => array( 'type' => 'text', 'label' => __( 'Padding vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '80px' ),
            ),
            'presets' => array(
                'startup' => array(
                    'name' => 'Startup SaaS',
                    'data' => array( 'titulo' => 'Transforma tu negocio digital', 'subtitulo' => 'La plataforma todo-en-uno que necesitas para escalar', 'boton_texto' => 'Empieza gratis' ),
                ),
                'ecommerce' => array(
                    'name' => 'E-commerce',
                    'data' => array( 'titulo' => 'Nueva colección disponible', 'subtitulo' => 'Descubre las últimas tendencias', 'boton_texto' => 'Ver productos' ),
                ),
                'comunidad' => array(
                    'name' => 'Comunidad',
                    'data' => array( 'titulo' => 'Únete a nuestra comunidad', 'subtitulo' => 'Miles de personas ya forman parte', 'boton_texto' => 'Registrarse' ),
                ),
            ),
        ) );

        // Features
        $this->registrar_bloque( array(
            'id'       => 'features',
            'name'     => __( 'Características', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
            'variants' => array(
                'grid'        => __( 'Cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'list'        => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'alternating' => __( 'Alternado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards'       => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'zigzag'      => __( 'Zigzag', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'timeline'    => __( 'Línea de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'tabs'        => __( 'Pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'accordion'   => __( 'Acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icons-only'  => __( 'Solo iconos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'bento'       => __( 'Bento Grid', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'hover-cards' => __( 'Tarjetas con hover', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'    => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo' => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'columnas'  => array( 'type' => 'select', 'label' => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '2' => '2', '3' => '3', '4' => '4' ) ),
                'items'     => array( 'type' => 'repeater', 'label' => __( 'Características', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'fields' => array(
                    'icono'       => array( 'type' => 'icon', 'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'titulo'      => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'descripcion' => array( 'type' => 'textarea', 'label' => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'enlace'      => array( 'type' => 'url', 'label' => __( 'Enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                ) ),
            ),
            'presets' => array(
                'saas' => array(
                    'name' => 'SaaS Features',
                    'data' => array(
                        'titulo' => 'Todo lo que necesitas',
                        'items' => array(
                            array( 'icono' => 'rocket', 'titulo' => 'Rápido', 'descripcion' => 'Rendimiento optimizado' ),
                            array( 'icono' => 'shield', 'titulo' => 'Seguro', 'descripcion' => 'Protección de datos' ),
                            array( 'icono' => 'refresh', 'titulo' => 'Actualizado', 'descripcion' => 'Mejoras constantes' ),
                        ),
                    ),
                ),
            ),
        ) );

        // Testimonios
        $this->registrar_bloque( array(
            'id'       => 'testimonials',
            'name'     => __( 'Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
            'variants' => array(
                'carousel'    => __( 'Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'grid'        => __( 'Cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'single'      => __( 'Único destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'masonry'     => __( 'Masonry', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'video'       => __( 'Video testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'rating'      => __( 'Con estrellas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'avatar-large'=> __( 'Avatar grande', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'quote-card'  => __( 'Tarjeta con cita', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'logos'       => __( 'Con logos de empresas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'twitter'     => __( 'Estilo Twitter/X', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo'    => array( 'type' => 'text', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'autoplay'     => array( 'type' => 'toggle', 'label' => __( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'mostrar_rating' => array( 'type' => 'toggle', 'label' => __( 'Mostrar rating', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'testimonios'  => array( 'type' => 'repeater', 'label' => __( 'Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'fields' => array(
                    'texto'    => array( 'type' => 'textarea', 'label' => __( 'Testimonio', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'nombre'   => array( 'type' => 'text', 'label' => __( 'Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'cargo'    => array( 'type' => 'text', 'label' => __( 'Cargo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'empresa'  => array( 'type' => 'text', 'label' => __( 'Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'avatar'   => array( 'type' => 'image', 'label' => __( 'Foto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'logo'     => array( 'type' => 'image', 'label' => __( 'Logo empresa', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'rating'   => array( 'type' => 'select', 'label' => __( 'Rating', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '5' => '5 estrellas', '4' => '4 estrellas', '3' => '3 estrellas' ) ),
                    'video_url'=> array( 'type' => 'url', 'label' => __( 'Video (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                ) ),
            ),
        ) );

        // Precios
        $this->registrar_bloque( array(
            'id'       => 'pricing',
            'name'     => __( 'Precios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 100 4h4a2 2 0 110 4H8"/><path d="M12 6v2m0 8v2"/></svg>',
            'variants' => array(
                'cards'       => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'table'       => __( 'Tabla comparativa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'toggle'      => __( 'Toggle mensual/anual', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'comparison'  => __( 'Comparación lado a lado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'slider'      => __( 'Slider de planes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'minimal'     => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'gradient'    => __( 'Gradiente destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'enterprise'  => __( 'Enterprise con contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'freemium'    => __( 'Freemium destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'horizontal'  => __( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'      => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo'   => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'moneda'      => array( 'type' => 'text', 'label' => __( 'Moneda', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => '€' ),
                'periodo'     => array( 'type' => 'select', 'label' => __( 'Período', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'mes' => 'Mes', 'año' => 'Año', 'único' => 'Pago único' ) ),
                'mostrar_toggle' => array( 'type' => 'toggle', 'label' => __( 'Mostrar toggle mensual/anual', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'descuento_anual' => array( 'type' => 'number', 'label' => __( 'Descuento anual (%)', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => 20 ),
                'planes'      => array( 'type' => 'repeater', 'label' => __( 'Planes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'fields' => array(
                    'nombre'        => array( 'type' => 'text', 'label' => __( 'Nombre del plan', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'descripcion'   => array( 'type' => 'textarea', 'label' => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'precio'        => array( 'type' => 'number', 'label' => __( 'Precio', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'precio_anual'  => array( 'type' => 'number', 'label' => __( 'Precio anual', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'destacado'     => array( 'type' => 'toggle', 'label' => __( 'Plan destacado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'etiqueta'      => array( 'type' => 'text', 'label' => __( 'Etiqueta (ej: Popular)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'boton_texto'   => array( 'type' => 'text', 'label' => __( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'boton_url'     => array( 'type' => 'url', 'label' => __( 'URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'caracteristicas' => array( 'type' => 'textarea', 'label' => __( 'Características (una por línea)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'icono'         => array( 'type' => 'icon', 'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                ) ),
            ),
            'presets' => array(
                'saas' => array(
                    'name' => 'SaaS Estándar',
                    'data' => array(
                        'titulo' => 'Planes y precios',
                        'subtitulo' => 'Elige el plan que mejor se adapte a tus necesidades',
                        'moneda' => '€',
                        'planes' => array(
                            array( 'nombre' => 'Básico', 'precio' => 9, 'caracteristicas' => "5 usuarios\n10GB almacenamiento\nSoporte email" ),
                            array( 'nombre' => 'Pro', 'precio' => 29, 'destacado' => true, 'etiqueta' => 'Popular', 'caracteristicas' => "25 usuarios\n100GB almacenamiento\nSoporte prioritario" ),
                            array( 'nombre' => 'Enterprise', 'precio' => 99, 'caracteristicas' => "Usuarios ilimitados\n1TB almacenamiento\nSoporte 24/7" ),
                        ),
                    ),
                ),
                'freemium' => array(
                    'name' => 'Freemium',
                    'data' => array(
                        'titulo' => 'Empieza gratis',
                        'planes' => array(
                            array( 'nombre' => 'Gratis', 'precio' => 0, 'caracteristicas' => "Funciones básicas\n1 usuario\nSoporte comunidad" ),
                            array( 'nombre' => 'Premium', 'precio' => 19, 'destacado' => true, 'caracteristicas' => "Todas las funciones\nUsuarios ilimitados\nSoporte prioritario" ),
                        ),
                    ),
                ),
            ),
        ) );

        // CTA
        $this->registrar_bloque( array(
            'id'       => 'cta',
            'name'     => __( 'Llamada a acción', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>',
            'variants' => array(
                'simple'      => __( 'Simple', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'banner'      => __( 'Banner', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'split'       => __( 'Dividido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'gradient'    => __( 'Gradiente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'newsletter'  => __( 'Newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'countdown'   => __( 'Con cuenta atrás', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'floating'    => __( 'Flotante', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'video'       => __( 'Con video', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'testimonial' => __( 'Con testimonio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'stats'       => __( 'Con estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'descripcion'  => array( 'type' => 'textarea', 'label' => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'boton_texto'  => array( 'type' => 'text', 'label' => __( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_url'    => array( 'type' => 'url', 'label' => __( 'URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_estilo' => array( 'type' => 'select', 'label' => __( 'Estilo del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'primary' => 'Primario', 'secondary' => 'Secundario', 'white' => 'Blanco', 'outline' => 'Contorno' ) ),
                'boton_2_texto'=> array( 'type' => 'text', 'label' => __( 'Segundo botón (texto)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_2_url'  => array( 'type' => 'url', 'label' => __( 'Segundo botón (URL)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'imagen_fondo' => array( 'type' => 'image', 'label' => __( 'Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'video_url'    => array( 'type' => 'url', 'label' => __( 'Video de fondo (URL)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'overlay_color'=> array( 'type' => 'color', 'label' => __( 'Color overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'overlay_opacity' => array( 'type' => 'range', 'label' => __( 'Opacidad overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'min' => 0, 'max' => 100 ),
                'formulario_email' => array( 'type' => 'toggle', 'label' => __( 'Mostrar campo email', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'fecha_limite' => array( 'type' => 'datetime', 'label' => __( 'Fecha límite (countdown)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'alineacion'   => array( 'type' => 'select', 'label' => __( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha' ) ),
            ),
            'presets' => array(
                'newsletter' => array(
                    'name' => 'Newsletter',
                    'data' => array( 'titulo' => 'Suscríbete a nuestra newsletter', 'descripcion' => 'Recibe las últimas novedades directamente en tu email', 'boton_texto' => 'Suscribirse', 'formulario_email' => true ),
                ),
                'prueba_gratis' => array(
                    'name' => 'Prueba gratis',
                    'data' => array( 'titulo' => '¿Listo para empezar?', 'descripcion' => 'Prueba gratis durante 14 días. Sin tarjeta de crédito.', 'boton_texto' => 'Empezar prueba gratis' ),
                ),
                'oferta_limitada' => array(
                    'name' => 'Oferta limitada',
                    'data' => array( 'titulo' => '🔥 Oferta por tiempo limitado', 'descripcion' => '50% de descuento solo esta semana', 'boton_texto' => 'Aprovechar oferta' ),
                ),
            ),
        ) );

        // FAQ
        $this->registrar_bloque( array(
            'id'       => 'faq',
            'name'     => __( 'FAQ', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
            'variants' => array(
                'accordion'    => __( 'Acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'two-columns'  => __( 'Dos columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'categories'   => __( 'Por categorías', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'tabs'         => __( 'Con pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'search'       => __( 'Con buscador', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'sidebar'      => __( 'Con sidebar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards'        => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'minimal'      => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'    => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo' => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'mostrar_buscador' => array( 'type' => 'toggle', 'label' => __( 'Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'abrir_primero'    => array( 'type' => 'toggle', 'label' => __( 'Abrir primera pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'icono_expandir'   => array( 'type' => 'select', 'label' => __( 'Icono expandir', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'plus' => 'Plus/Minus', 'chevron' => 'Chevron', 'arrow' => 'Flecha' ) ),
                'preguntas' => array( 'type' => 'repeater', 'label' => __( 'Preguntas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'fields' => array(
                    'pregunta'  => array( 'type' => 'text', 'label' => __( 'Pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'respuesta' => array( 'type' => 'editor', 'label' => __( 'Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'categoria' => array( 'type' => 'text', 'label' => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'icono'     => array( 'type' => 'icon', 'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                ) ),
                'texto_contacto' => array( 'type' => 'text', 'label' => __( 'Texto de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'enlace_contacto' => array( 'type' => 'url', 'label' => __( 'Enlace de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
            'presets' => array(
                'general' => array(
                    'name' => 'FAQ General',
                    'data' => array(
                        'titulo' => 'Preguntas frecuentes',
                        'subtitulo' => 'Encuentra respuestas a las preguntas más comunes',
                        'preguntas' => array(
                            array( 'pregunta' => '¿Cómo puedo empezar?', 'respuesta' => 'Regístrate gratis y sigue los pasos del asistente.' ),
                            array( 'pregunta' => '¿Ofrecen soporte técnico?', 'respuesta' => 'Sí, ofrecemos soporte 24/7 por email y chat.' ),
                            array( 'pregunta' => '¿Puedo cancelar en cualquier momento?', 'respuesta' => 'Por supuesto, sin permanencia ni cargos ocultos.' ),
                        ),
                        'texto_contacto' => '¿No encuentras lo que buscas? Contáctanos',
                    ),
                ),
            ),
        ) );

        // Contacto
        $this->registrar_bloque( array(
            'id'       => 'contact',
            'name'     => __( 'Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
            'variants' => array(
                'form'          => __( 'Formulario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'split'         => __( 'Dividido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'map'           => __( 'Con mapa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'info'          => __( 'Solo información', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards'         => __( 'Tarjetas de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'chat'          => __( 'Estilo chat', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'minimal'       => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'fullwidth-map' => __( 'Mapa ancho completo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'         => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo'      => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'email'          => array( 'type' => 'text', 'label' => __( 'Email', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'telefono'       => array( 'type' => 'text', 'label' => __( 'Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'whatsapp'       => array( 'type' => 'text', 'label' => __( 'WhatsApp', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'direccion'      => array( 'type' => 'textarea', 'label' => __( 'Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'horario'        => array( 'type' => 'textarea', 'label' => __( 'Horario de atención', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'mapa_lat'       => array( 'type' => 'number', 'label' => __( 'Latitud', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'mapa_lng'       => array( 'type' => 'number', 'label' => __( 'Longitud', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'mapa_zoom'      => array( 'type' => 'number', 'label' => __( 'Zoom del mapa', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => 15 ),
                'redes_sociales' => array( 'type' => 'repeater', 'label' => __( 'Redes sociales', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'fields' => array(
                    'red'   => array( 'type' => 'select', 'label' => __( 'Red', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'facebook' => 'Facebook', 'twitter' => 'Twitter/X', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'tiktok' => 'TikTok' ) ),
                    'url'   => array( 'type' => 'url', 'label' => __( 'URL', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                ) ),
                'formulario_campos' => array( 'type' => 'multiselect', 'label' => __( 'Campos del formulario', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'nombre' => 'Nombre', 'email' => 'Email', 'telefono' => 'Teléfono', 'asunto' => 'Asunto', 'mensaje' => 'Mensaje' ) ),
                'boton_texto'    => array( 'type' => 'text', 'label' => __( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => 'Enviar mensaje' ),
            ),
            'presets' => array(
                'empresa' => array(
                    'name' => 'Empresa',
                    'data' => array(
                        'titulo' => 'Contacta con nosotros',
                        'subtitulo' => 'Estamos aquí para ayudarte',
                        'formulario_campos' => array( 'nombre', 'email', 'asunto', 'mensaje' ),
                    ),
                ),
            ),
        ) );

        // Equipo
        $this->registrar_bloque( array(
            'id'       => 'team',
            'name'     => __( 'Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
            'variants' => array(
                'grid'        => __( 'Cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'carousel'    => __( 'Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'list'        => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards'       => __( 'Tarjetas hover', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'circular'    => __( 'Fotos circulares', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'minimal'     => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'detailed'    => __( 'Detallado con bio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'hierarchy'   => __( 'Organigrama', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'    => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo' => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'columnas'  => array( 'type' => 'select', 'label' => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5' ) ),
                'miembros'  => array( 'type' => 'repeater', 'label' => __( 'Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'fields' => array(
                    'nombre'    => array( 'type' => 'text', 'label' => __( 'Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'cargo'     => array( 'type' => 'text', 'label' => __( 'Cargo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'bio'       => array( 'type' => 'textarea', 'label' => __( 'Biografía', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'foto'      => array( 'type' => 'image', 'label' => __( 'Foto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'email'     => array( 'type' => 'text', 'label' => __( 'Email', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'linkedin'  => array( 'type' => 'url', 'label' => __( 'LinkedIn', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'twitter'   => array( 'type' => 'url', 'label' => __( 'Twitter/X', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'departamento' => array( 'type' => 'text', 'label' => __( 'Departamento', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                ) ),
                'mostrar_redes' => array( 'type' => 'toggle', 'label' => __( 'Mostrar redes sociales', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'mostrar_email' => array( 'type' => 'toggle', 'label' => __( 'Mostrar email', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
            'presets' => array(
                'startup' => array(
                    'name' => 'Startup',
                    'data' => array(
                        'titulo' => 'Nuestro equipo',
                        'subtitulo' => 'Las personas detrás del proyecto',
                        'columnas' => '4',
                        'mostrar_redes' => true,
                    ),
                ),
            ),
        ) );

        // Galería
        $this->registrar_bloque( array(
            'id'       => 'gallery',
            'name'     => __( 'Galería', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
            'variants' => array(
                'grid'        => __( 'Cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'masonry'     => __( 'Masonry', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'carousel'    => __( 'Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'lightbox'    => __( 'Con lightbox', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'justified'   => __( 'Justificada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'slider'      => __( 'Slider fullwidth', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'mosaic'      => __( 'Mosaico', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'polaroid'    => __( 'Estilo Polaroid', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'filterable'  => __( 'Con filtros', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'      => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo'   => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'imagenes'    => array( 'type' => 'gallery', 'label' => __( 'Imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'columnas'    => array( 'type' => 'select', 'label' => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ) ),
                'gap'         => array( 'type' => 'select', 'label' => __( 'Espaciado', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '0' => 'Sin espacio', '4' => 'Pequeño', '8' => 'Normal', '16' => 'Grande' ) ),
                'lightbox'    => array( 'type' => 'toggle', 'label' => __( 'Lightbox', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'captions'    => array( 'type' => 'toggle', 'label' => __( 'Mostrar títulos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'hover_effect'=> array( 'type' => 'select', 'label' => __( 'Efecto hover', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'none' => 'Ninguno', 'zoom' => 'Zoom', 'overlay' => 'Overlay', 'grayscale' => 'Escala de grises', 'blur' => 'Desenfoque' ) ),
                'aspect_ratio'=> array( 'type' => 'select', 'label' => __( 'Proporción', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'auto' => 'Auto', 'square' => 'Cuadrado', '4:3' => '4:3', '16:9' => '16:9', '3:2' => '3:2' ) ),
                'autoplay'    => array( 'type' => 'toggle', 'label' => __( 'Autoplay (carrusel)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'categorias'  => array( 'type' => 'text', 'label' => __( 'Categorías (separadas por coma)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        // Blog
        $this->registrar_bloque( array(
            'id'       => 'blog',
            'name'     => __( 'Blog', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>',
            'variants' => array(
                'grid'          => __( 'Cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'list'          => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'featured'      => __( 'Destacado + grid', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'masonry'       => __( 'Masonry', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'carousel'      => __( 'Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'minimal'       => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards-overlay' => __( 'Tarjetas con overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'timeline'      => __( 'Línea de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo'    => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'cantidad'     => array( 'type' => 'number', 'label' => __( 'Cantidad de posts', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => 6 ),
                'categoria'    => array( 'type' => 'taxonomy', 'label' => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'taxonomy' => 'category' ),
                'etiquetas'    => array( 'type' => 'taxonomy', 'label' => __( 'Etiquetas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'taxonomy' => 'post_tag' ),
                'columnas'     => array( 'type' => 'select', 'label' => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '2' => '2', '3' => '3', '4' => '4' ) ),
                'orden'        => array( 'type' => 'select', 'label' => __( 'Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'date' => 'Fecha', 'title' => 'Título', 'rand' => 'Aleatorio', 'comment_count' => 'Comentarios' ) ),
                'mostrar_extracto' => array( 'type' => 'toggle', 'label' => __( 'Mostrar extracto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'mostrar_fecha' => array( 'type' => 'toggle', 'label' => __( 'Mostrar fecha', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'mostrar_autor' => array( 'type' => 'toggle', 'label' => __( 'Mostrar autor', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'mostrar_categorias' => array( 'type' => 'toggle', 'label' => __( 'Mostrar categorías', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'mostrar_imagen' => array( 'type' => 'toggle', 'label' => __( 'Mostrar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => true ),
                'leer_mas_texto' => array( 'type' => 'text', 'label' => __( 'Texto "Leer más"', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => 'Leer más' ),
                'ver_todos_url' => array( 'type' => 'url', 'label' => __( 'URL "Ver todos"', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'ver_todos_texto' => array( 'type' => 'text', 'label' => __( 'Texto "Ver todos"', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
            'presets' => array(
                'noticias' => array(
                    'name' => 'Noticias',
                    'data' => array(
                        'titulo' => 'Últimas noticias',
                        'cantidad' => 6,
                        'columnas' => '3',
                        'mostrar_fecha' => true,
                        'mostrar_extracto' => true,
                    ),
                ),
            ),
        ) );

        // Video
        $this->registrar_bloque( array(
            'id'       => 'video-section',
            'name'     => __( 'Video', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5,3 19,12 5,21"/></svg>',
            'variants' => array(
                'embed'       => __( 'Embed simple', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'background'  => __( 'Video de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'lightbox'    => __( 'Con lightbox', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'split'       => __( 'Dividido con texto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'fullscreen'  => __( 'Pantalla completa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'playlist'    => __( 'Playlist', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'testimonial' => __( 'Video testimonio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'tutorial'    => __( 'Tutorial con pasos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'descripcion'  => array( 'type' => 'textarea', 'label' => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'video_url'    => array( 'type' => 'url', 'label' => __( 'URL del video (YouTube/Vimeo)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'video_file'   => array( 'type' => 'file', 'label' => __( 'Archivo de video', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'accept' => 'video/*' ),
                'thumbnail'    => array( 'type' => 'image', 'label' => __( 'Imagen de portada', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'autoplay'     => array( 'type' => 'toggle', 'label' => __( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'loop'         => array( 'type' => 'toggle', 'label' => __( 'Loop', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'muted'        => array( 'type' => 'toggle', 'label' => __( 'Silenciado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'controls'     => array( 'type' => 'toggle', 'label' => __( 'Mostrar controles', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => true ),
                'aspect_ratio' => array( 'type' => 'select', 'label' => __( 'Proporción', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '16:9' => '16:9', '4:3' => '4:3', '21:9' => '21:9 (Cine)', '1:1' => 'Cuadrado' ) ),
                'play_icon'    => array( 'type' => 'select', 'label' => __( 'Estilo icono play', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'default' => 'Defecto', 'circle' => 'Círculo', 'minimal' => 'Minimalista', 'youtube' => 'Estilo YouTube' ) ),
                'overlay_color'=> array( 'type' => 'color', 'label' => __( 'Color overlay', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_texto'  => array( 'type' => 'text', 'label' => __( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'boton_url'    => array( 'type' => 'url', 'label' => __( 'URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
            'presets' => array(
                'demo' => array(
                    'name' => 'Demo producto',
                    'data' => array(
                        'titulo' => 'Mira cómo funciona',
                        'descripcion' => 'Un recorrido completo por todas las funcionalidades',
                        'controls' => true,
                    ),
                ),
            ),
        ) );

        // Estadísticas
        $this->registrar_bloque( array(
            'id'       => 'stats',
            'name'     => __( 'Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>',
            'variants' => array(
                'counters'    => __( 'Contadores', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'progress'    => __( 'Barras de progreso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'charts'      => __( 'Gráficos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards'       => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icons'       => __( 'Con iconos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'radial'      => __( 'Progreso circular', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'minimal'     => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'comparison'  => __( 'Comparación antes/después', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'subtitulo'    => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ai' => true ),
                'columnas'     => array( 'type' => 'select', 'label' => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5' ) ),
                'estadisticas' => array( 'type' => 'repeater', 'label' => __( 'Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'fields' => array(
                    'valor'       => array( 'type' => 'text', 'label' => __( 'Valor', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'etiqueta'    => array( 'type' => 'text', 'label' => __( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'descripcion' => array( 'type' => 'text', 'label' => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'icono'       => array( 'type' => 'icon', 'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'prefijo'     => array( 'type' => 'text', 'label' => __( 'Prefijo (ej: $, €)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'sufijo'      => array( 'type' => 'text', 'label' => __( 'Sufijo (ej: %, +)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    'porcentaje'  => array( 'type' => 'number', 'label' => __( 'Porcentaje (para barras)', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'min' => 0, 'max' => 100 ),
                    'color'       => array( 'type' => 'color', 'label' => __( 'Color', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                ) ),
                'animacion'    => array( 'type' => 'toggle', 'label' => __( 'Animación de conteo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => true ),
                'duracion'     => array( 'type' => 'number', 'label' => __( 'Duración animación (ms)', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => 2000 ),
                'color_fondo'  => array( 'type' => 'color', 'label' => __( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
            'presets' => array(
                'empresa' => array(
                    'name' => 'Métricas empresa',
                    'data' => array(
                        'titulo' => 'Nuestros números',
                        'columnas' => '4',
                        'animacion' => true,
                        'estadisticas' => array(
                            array( 'valor' => '10000', 'sufijo' => '+', 'etiqueta' => 'Clientes' ),
                            array( 'valor' => '50', 'sufijo' => '+', 'etiqueta' => 'Países' ),
                            array( 'valor' => '99', 'sufijo' => '%', 'etiqueta' => 'Satisfacción' ),
                            array( 'valor' => '24', 'sufijo' => '/7', 'etiqueta' => 'Soporte' ),
                        ),
                    ),
                ),
            ),
        ) );

        // Carrusel Avanzado
        $this->registrar_bloque( array(
            'id'       => 'carousel',
            'name'     => __( 'Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M8 18l4 2 4-2"/><circle cx="8" cy="21" r="1" fill="currentColor"/><circle cx="12" cy="21" r="1" fill="currentColor"/><circle cx="16" cy="21" r="1" fill="currentColor"/></svg>',
            'variants' => array(
                'simple'       => __( 'Simple', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards'        => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'fullwidth'    => __( 'Ancho completo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'testimonials' => __( 'Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'gallery'      => __( 'Galería', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'items' => array(
                    'type'   => 'repeater',
                    'label'  => __( 'Slides', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'fields' => array(
                        'imagen'      => array( 'type' => 'image', 'label' => __( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'titulo'      => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'descripcion' => array( 'type' => 'textarea', 'label' => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'enlace_url'  => array( 'type' => 'text', 'label' => __( 'URL del enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'enlace_texto'=> array( 'type' => 'text', 'label' => __( 'Texto del enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                    ),
                ),
                '_separator_config' => array(
                    'type'  => 'separator',
                    'label' => __( '⚙️ Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'autoplay' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'intervalo' => array(
                    'type'    => 'number',
                    'label'   => __( 'Intervalo (segundos)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => 5,
                    'min'     => 1,
                    'max'     => 30,
                ),
                'mostrar_flechas' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar flechas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'mostrar_dots' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar indicadores', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'loop' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Loop infinito', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'slides_visibles' => array(
                    'type'    => 'number',
                    'label'   => __( 'Slides visibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => 1,
                    'min'     => 1,
                    'max'     => 5,
                ),
                'efecto_transicion' => array(
                    'type'    => 'select',
                    'label'   => __( 'Efecto de transición', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => array(
                        'slide' => __( 'Deslizar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'fade'  => __( 'Desvanecer', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'scale' => __( 'Escalar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'default' => 'slide',
                ),
            ),
        ) );

        // Pestañas (Tabs)
        $this->registrar_bloque( array(
            'id'       => 'tabs',
            'name'     => __( 'Pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 3v6"/></svg>',
            'variants' => array(
                'horizontal' => __( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'vertical'   => __( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'pills'      => __( 'Pills', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'underlined' => __( 'Subrayado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'boxed'      => __( 'Con caja', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array_merge(
                $this->get_campos_header_seccion(),
                array(
                    'tabs' => array(
                        'type'   => 'repeater',
                        'label'  => __( 'Pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'fields' => array(
                            'titulo'    => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'icono'     => array( 'type' => 'icon', 'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'contenido' => array( 'type' => 'editor', 'label' => __( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        ),
                    ),
                    '_separator_estilo' => array(
                        'type'  => 'separator',
                        'label' => __( '🎨 Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'tab_activa_defecto' => array(
                        'type'    => 'number',
                        'label'   => __( 'Tab activa por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 0,
                        'min'     => 0,
                    ),
                    'alineacion_tabs' => array(
                        'type'    => 'select',
                        'label'   => __( 'Alineación tabs', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array(
                            'left'   => __( 'Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'center' => __( 'Centro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'right'  => __( 'Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'full'   => __( 'Ancho completo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'default' => 'left',
                    ),
                    'animacion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Animación al cambiar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                )
            ),
        ) );

        // Acordeón
        $this->registrar_bloque( array(
            'id'       => 'accordion',
            'name'     => __( 'Acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="17" width="18" height="4" rx="1"/><path d="M15 5l2-2 2 2" fill="currentColor"/></svg>',
            'variants' => array(
                'simple'   => __( 'Simple', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'bordered' => __( 'Con bordes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards'    => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'minimal'  => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'faq'      => __( 'FAQ', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array_merge(
                $this->get_campos_header_seccion(),
                array(
                    'items' => array(
                        'type'   => 'repeater',
                        'label'  => __( 'Items', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'fields' => array(
                            'titulo'    => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'contenido' => array( 'type' => 'editor', 'label' => __( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'icono'     => array( 'type' => 'icon', 'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'abierto'   => array( 'type' => 'toggle', 'label' => __( 'Abierto por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'default' => false ),
                        ),
                    ),
                    '_separator_config' => array(
                        'type'  => 'separator',
                        'label' => __( '⚙️ Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'multiple_abiertos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir múltiples abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => false,
                    ),
                    'icono_expandir' => array(
                        'type'    => 'select',
                        'label'   => __( 'Icono expandir', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array(
                            'chevron' => __( 'Chevron', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'plus'    => __( 'Plus/Minus', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'arrow'   => __( 'Flecha', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'default' => 'chevron',
                    ),
                    'animacion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Animación suave', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                )
            ),
        ) );

        // Línea de Tiempo (Timeline)
        $this->registrar_bloque( array(
            'id'       => 'timeline',
            'name'     => __( 'Línea de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="2" x2="12" y2="22"/><circle cx="12" cy="6" r="3"/><circle cx="12" cy="12" r="3"/><circle cx="12" cy="18" r="3"/><path d="M15 6h6"/><path d="M3 12h6"/><path d="M15 18h6"/></svg>',
            'variants' => array(
                'vertical'    => __( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'horizontal'  => __( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'alternating' => __( 'Alternado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'compact'     => __( 'Compacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'roadmap'     => __( 'Roadmap', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array_merge(
                $this->get_campos_header_seccion(),
                array(
                    'eventos' => array(
                        'type'   => 'repeater',
                        'label'  => __( 'Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'fields' => array(
                            'fecha'       => array( 'type' => 'text', 'label' => __( 'Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'titulo'      => array( 'type' => 'text', 'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'descripcion' => array( 'type' => 'textarea', 'label' => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'icono'       => array( 'type' => 'icon', 'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'imagen'      => array( 'type' => 'image', 'label' => __( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'color'       => array( 'type' => 'color', 'label' => __( 'Color del marcador', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'estado'      => array(
                                'type'    => 'select',
                                'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                                'options' => array(
                                    'completed' => __( 'Completado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                                    'current'   => __( 'Actual', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                                    'upcoming'  => __( 'Próximo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                                ),
                                'default' => 'completed',
                            ),
                        ),
                    ),
                    '_separator_estilo' => array(
                        'type'  => 'separator',
                        'label' => __( '🎨 Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'color_linea' => array(
                        'type'    => 'color',
                        'label'   => __( 'Color de la línea', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => '#3b82f6',
                    ),
                    'color_marcador' => array(
                        'type'    => 'color',
                        'label'   => __( 'Color de marcadores', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => '#3b82f6',
                    ),
                    'animacion_scroll' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Animar al hacer scroll', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_conectores' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar líneas conectoras', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                )
            ),
        ) );

        // ====================================
        // CARRUSEL AVANZADO
        // ====================================
        $this->registrar_bloque( array(
            'id'       => 'carousel',
            'name'     => __( 'Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h4v4H6z"/><circle cx="18" cy="12" r="1"/><circle cx="15" cy="12" r="1"/></svg>',
            'variants' => array(
                'simple'       => __( 'Simple', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards'        => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'fullwidth'    => __( 'Ancho completo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'testimonials' => __( 'Testimonios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'slides' => array(
                    'type'   => 'repeater',
                    'label'  => __( 'Slides', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'fields' => array(
                        'imagen' => array(
                            'type'  => 'image',
                            'label' => __( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'titulo' => array(
                            'type'  => 'text',
                            'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'descripcion' => array(
                            'type'  => 'textarea',
                            'label' => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'enlace' => array(
                            'type'  => 'url',
                            'label' => __( 'Enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'texto_boton' => array(
                            'type'  => 'text',
                            'label' => __( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                    ),
                ),
                '_separator_config' => array(
                    'type'  => 'separator',
                    'label' => __( '⚙️ Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'autoplay' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'intervalo' => array(
                    'type'    => 'number',
                    'label'   => __( 'Intervalo (segundos)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => 5,
                    'min'     => 1,
                    'max'     => 30,
                ),
                'mostrar_flechas' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar flechas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'mostrar_dots' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar indicadores', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'loop' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Loop infinito', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'slides_visibles' => array(
                    'type'    => 'number',
                    'label'   => __( 'Slides visibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => 1,
                    'min'     => 1,
                    'max'     => 6,
                ),
                'efecto' => array(
                    'type'    => 'select',
                    'label'   => __( 'Efecto de transición', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => array(
                        'slide' => __( 'Deslizar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'fade'  => __( 'Fade', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'zoom'  => __( 'Zoom', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'default' => 'slide',
                ),
            ),
        ) );

        // ====================================
        // TABS / PESTAÑAS
        // ====================================
        $this->registrar_bloque( array(
            'id'       => 'tabs',
            'name'     => __( 'Pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 3v6"/></svg>',
            'variants' => array(
                'horizontal' => __( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'vertical'   => __( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'pills'      => __( 'Pills', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'underlined' => __( 'Subrayado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'tabs' => array(
                    'type'   => 'repeater',
                    'label'  => __( 'Pestañas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'fields' => array(
                        'titulo' => array(
                            'type'  => 'text',
                            'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'icono' => array(
                            'type'  => 'icon',
                            'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'contenido' => array(
                            'type'  => 'richtext',
                            'label' => __( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                    ),
                ),
                '_separator_estilo' => array(
                    'type'  => 'separator',
                    'label' => __( '🎨 Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'color_activo' => array(
                    'type'    => 'color',
                    'label'   => __( 'Color activo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => '#6366f1',
                ),
                'color_fondo_activo' => array(
                    'type'    => 'color',
                    'label'   => __( 'Fondo activo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => '#eef2ff',
                ),
                'animacion' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Animar cambio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
            ),
        ) );

        // ====================================
        // ACORDEÓN
        // ====================================
        $this->registrar_bloque( array(
            'id'       => 'accordion',
            'name'     => __( 'Acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="17" width="18" height="4" rx="1"/></svg>',
            'variants' => array(
                'simple'   => __( 'Simple', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'bordered' => __( 'Con bordes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'cards'    => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'minimal'  => __( 'Minimalista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'fields'   => array(
                'items' => array(
                    'type'   => 'repeater',
                    'label'  => __( 'Items', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'fields' => array(
                        'titulo' => array(
                            'type'  => 'text',
                            'label' => __( 'Título', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'contenido' => array(
                            'type'  => 'richtext',
                            'label' => __( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'icono' => array(
                            'type'  => 'icon',
                            'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'abierto' => array(
                            'type'    => 'toggle',
                            'label'   => __( 'Abierto por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'default' => false,
                        ),
                    ),
                ),
                '_separator_config' => array(
                    'type'  => 'separator',
                    'label' => __( '⚙️ Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'multiple_abiertos' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Permitir múltiples abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => false,
                ),
                'icono_abrir' => array(
                    'type'    => 'select',
                    'label'   => __( 'Icono expandir', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => array(
                        'chevron' => __( 'Chevron', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'plus'    => __( 'Plus/Minus', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'arrow'   => __( 'Flecha', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'default' => 'chevron',
                ),
                '_separator_estilo' => array(
                    'type'  => 'separator',
                    'label' => __( '🎨 Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'color_header' => array(
                    'type'    => 'color',
                    'label'   => __( 'Color cabecera', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => '#f8fafc',
                ),
                'color_borde' => array(
                    'type'    => 'color',
                    'label'   => __( 'Color borde', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => '#e2e8f0',
                ),
            ),
        ) );
    }

    /**
     * Registra bloques básicos
     */
    private function registrar_bloques_basicos() {
        // Texto
        $this->registrar_bloque( array(
            'id'       => 'text',
            'name'     => __( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4,7 4,4 20,4 20,7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
            'fields'   => array(
                'contenido'   => array( 'type' => 'textarea', 'label' => __( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'alineacion'  => array( 'type' => 'select', 'label' => __( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado' ) ),
                'tamano'      => array( 'type' => 'select', 'label' => __( 'Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'sm' => 'Pequeño', 'base' => 'Normal', 'lg' => 'Grande', 'xl' => 'Extra grande' ) ),
                'color'       => array( 'type' => 'color', 'label' => __( 'Color', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        // Encabezado
        $this->registrar_bloque( array(
            'id'       => 'heading',
            'name'     => __( 'Encabezado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4v16M18 4v16M6 12h12"/></svg>',
            'fields'   => array(
                'texto'      => array( 'type' => 'text', 'label' => __( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'nivel'      => array( 'type' => 'select', 'label' => __( 'Nivel', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6' ) ),
                'alineacion' => array( 'type' => 'select', 'label' => __( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha' ) ),
                'color'      => array( 'type' => 'color', 'label' => __( 'Color', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        // Imagen
        $this->registrar_bloque( array(
            'id'       => 'image',
            'name'     => __( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
            'fields'   => array(
                'imagen'     => array( 'type' => 'image', 'label' => __( 'Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'alt'        => array( 'type' => 'text', 'label' => __( 'Texto alternativo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'tamano'     => array( 'type' => 'select', 'label' => __( 'Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'auto' => 'Auto', 'full' => 'Completo', 'contain' => 'Contener', 'cover' => 'Cubrir' ) ),
                'alineacion' => array( 'type' => 'select', 'label' => __( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha' ) ),
                'enlace'     => array( 'type' => 'url', 'label' => __( 'Enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'lightbox'   => array( 'type' => 'toggle', 'label' => __( 'Lightbox', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        // Botón
        $this->registrar_bloque( array(
            'id'       => 'button',
            'name'     => __( 'Botón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"/></svg>',
            'fields'   => array(
                'texto'       => array( 'type' => 'text', 'label' => __( 'Texto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'url'         => array( 'type' => 'url', 'label' => __( 'URL', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'estilo'      => array( 'type' => 'select', 'label' => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'primary' => 'Primario', 'secondary' => 'Secundario', 'outline' => 'Contorno', 'ghost' => 'Transparente' ) ),
                'tamano'      => array( 'type' => 'select', 'label' => __( 'Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'sm' => 'Pequeño', 'md' => 'Mediano', 'lg' => 'Grande' ) ),
                'icono'       => array( 'type' => 'icon', 'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'nueva_vent'  => array( 'type' => 'toggle', 'label' => __( 'Nueva ventana', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'ancho_full'  => array( 'type' => 'toggle', 'label' => __( 'Ancho completo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        // Divisor
        $this->registrar_bloque( array(
            'id'       => 'divider',
            'name'     => __( 'Divisor', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/></svg>',
            'fields'   => array(
                'estilo'  => array( 'type' => 'select', 'label' => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'solid' => 'Sólido', 'dashed' => 'Guiones', 'dotted' => 'Puntos', 'double' => 'Doble' ) ),
                'color'   => array( 'type' => 'color', 'label' => __( 'Color', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'grosor'  => array( 'type' => 'select', 'label' => __( 'Grosor', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '1' => '1px', '2' => '2px', '3' => '3px', '4' => '4px' ) ),
                'ancho'   => array( 'type' => 'select', 'label' => __( 'Ancho', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '25' => '25%', '50' => '50%', '75' => '75%', '100' => '100%' ) ),
                'margen'  => array( 'type' => 'spacing', 'label' => __( 'Margen', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        // Espaciador
        $this->registrar_bloque( array(
            'id'       => 'spacer',
            'name'     => __( 'Espaciador', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
            'fields'   => array(
                'altura'         => array( 'type' => 'number', 'label' => __( 'Altura (px)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'altura_mobile'  => array( 'type' => 'number', 'label' => __( 'Altura móvil (px)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        // Icono
        $this->registrar_bloque( array(
            'id'       => 'icon',
            'name'     => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>',
            'fields'   => array(
                'icono'       => array( 'type' => 'icon', 'label' => __( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'tamano'      => array( 'type' => 'select', 'label' => __( 'Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'sm' => 'Pequeño', 'md' => 'Mediano', 'lg' => 'Grande', 'xl' => 'Extra grande' ) ),
                'color'       => array( 'type' => 'color', 'label' => __( 'Color', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'fondo'       => array( 'type' => 'color', 'label' => __( 'Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'borde'       => array( 'type' => 'toggle', 'label' => __( 'Borde', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'redondeado'  => array( 'type' => 'toggle', 'label' => __( 'Redondeado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'enlace'      => array( 'type' => 'url', 'label' => __( 'Enlace', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        // HTML
        $this->registrar_bloque( array(
            'id'       => 'html',
            'name'     => __( 'HTML', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>',
            'fields'   => array(
                'codigo'      => array( 'type' => 'code', 'label' => __( 'Código HTML', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'language' => 'html' ),
                'contenedor'  => array( 'type' => 'toggle', 'label' => __( 'Contenedor', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        // Shortcode
        $this->registrar_bloque( array(
            'id'       => 'shortcode',
            'name'     => __( 'Shortcode', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 17l6-6-6-6M12 19h8"/></svg>',
            'fields'   => array(
                'shortcode'   => array( 'type' => 'text', 'label' => __( 'Shortcode', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'placeholder' => '[mi_shortcode]' ),
                'descripcion' => array( 'type' => 'text', 'label' => __( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );
    }

    /**
     * Registra bloques de layout
     */
    private function registrar_bloques_layout() {
        $this->registrar_bloque( array(
            'id'       => 'container',
            'name'     => __( 'Contenedor', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'layout',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
            'fields'   => array(
                'max_width'  => array( 'type' => 'select', 'label' => __( 'Ancho máximo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'full' => 'Completo', '1200px' => '1200px', '960px' => '960px', '720px' => '720px' ) ),
                'padding'    => array( 'type' => 'spacing', 'label' => __( 'Padding', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'background' => array( 'type' => 'color', 'label' => __( 'Fondo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'row',
            'name'     => __( 'Fila', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'layout',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="1"/></svg>',
            'fields'   => array(
                'align'   => array( 'type' => 'select', 'label' => __( 'Alineación', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'start' => 'Inicio', 'center' => 'Centro', 'end' => 'Final', 'stretch' => 'Estirar' ) ),
                'gap'     => array( 'type' => 'number', 'label' => __( 'Espacio', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'reverse' => array( 'type' => 'toggle', 'label' => __( 'Invertir', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'columns',
            'name'     => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'layout',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="18" rx="1"/></svg>',
            'fields'   => array(
                'columnas' => array( 'type' => 'select', 'label' => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ) ),
                'gap'      => array( 'type' => 'number', 'label' => __( 'Espacio', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'stack_on' => array( 'type' => 'select', 'label' => __( 'Apilar en', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'mobile' => 'Móvil', 'tablet' => 'Tablet', 'never' => 'Nunca' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'grid',
            'name'     => __( 'Grid', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'layout',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
            'fields'   => array(
                'columnas' => array( 'type' => 'number', 'label' => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'filas'    => array( 'type' => 'number', 'label' => __( 'Filas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'gap'      => array( 'type' => 'number', 'label' => __( 'Espacio', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );
    }

    /**
     * Registra bloques de formularios
     */
    private function registrar_bloques_formularios() {
        $this->registrar_bloque( array(
            'id'       => 'form',
            'name'     => __( 'Formulario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/></svg>',
            'fields'   => array(
                'action'       => array( 'type' => 'url', 'label' => __( 'URL de envío', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'method'       => array( 'type' => 'select', 'label' => __( 'Método', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'POST' => 'POST', 'GET' => 'GET' ) ),
                'submit_text'  => array( 'type' => 'text', 'label' => __( 'Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'success_msg'  => array( 'type' => 'text', 'label' => __( 'Mensaje de éxito', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'input',
            'name'     => __( 'Campo de texto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 12h.01"/></svg>',
            'fields'   => array(
                'label'       => array( 'type' => 'text', 'label' => __( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'placeholder' => array( 'type' => 'text', 'label' => __( 'Placeholder', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'type'        => array( 'type' => 'select', 'label' => __( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( 'text' => 'Texto', 'email' => 'Email', 'tel' => 'Teléfono', 'number' => 'Número', 'password' => 'Contraseña' ) ),
                'required'    => array( 'type' => 'toggle', 'label' => __( 'Requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'textarea',
            'name'     => __( 'Área de texto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="13" y2="16"/></svg>',
            'fields'   => array(
                'label'       => array( 'type' => 'text', 'label' => __( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'placeholder' => array( 'type' => 'text', 'label' => __( 'Placeholder', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'rows'        => array( 'type' => 'number', 'label' => __( 'Filas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'required'    => array( 'type' => 'toggle', 'label' => __( 'Requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'select',
            'name'     => __( 'Selector', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><polyline points="8,10 12,14 16,10"/></svg>',
            'fields'   => array(
                'label'    => array( 'type' => 'text', 'label' => __( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'options'  => array( 'type' => 'repeater', 'label' => __( 'Opciones', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'required' => array( 'type' => 'toggle', 'label' => __( 'Requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'checkbox',
            'name'     => __( 'Checkbox', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="9,12 11,14 15,10"/></svg>',
            'fields'   => array(
                'label'    => array( 'type' => 'text', 'label' => __( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'checked'  => array( 'type' => 'toggle', 'label' => __( 'Marcado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'required' => array( 'type' => 'toggle', 'label' => __( 'Requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );
    }

    /**
     * Registra bloques de media
     */
    private function registrar_bloques_media() {
        $this->registrar_bloque( array(
            'id'       => 'video-embed',
            'name'     => __( 'Video embed', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'media',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polygon points="10,8 16,12 10,16 10,8"/></svg>',
            'fields'   => array(
                'url'        => array( 'type' => 'url', 'label' => __( 'URL del video', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'autoplay'   => array( 'type' => 'toggle', 'label' => __( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'muted'      => array( 'type' => 'toggle', 'label' => __( 'Sin sonido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'loop'       => array( 'type' => 'toggle', 'label' => __( 'Bucle', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'aspect'     => array( 'type' => 'select', 'label' => __( 'Aspecto', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'options' => array( '16:9' => '16:9', '4:3' => '4:3', '1:1' => '1:1' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'audio',
            'name'     => __( 'Audio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'media',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
            'fields'   => array(
                'url'      => array( 'type' => 'url', 'label' => __( 'URL del audio', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'autoplay' => array( 'type' => 'toggle', 'label' => __( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'loop'     => array( 'type' => 'toggle', 'label' => __( 'Bucle', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'controls' => array( 'type' => 'toggle', 'label' => __( 'Controles', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'map',
            'name'     => __( 'Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'media',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1,6 1,22 8,18 16,22 23,18 23,2 16,6 8,2"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
            'fields'   => array(
                'lat'     => array( 'type' => 'number', 'label' => __( 'Latitud', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'lng'     => array( 'type' => 'number', 'label' => __( 'Longitud', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'zoom'    => array( 'type' => 'number', 'label' => __( 'Zoom', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'height'  => array( 'type' => 'number', 'label' => __( 'Altura (px)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'marker'  => array( 'type' => 'toggle', 'label' => __( 'Mostrar marcador', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'embed',
            'name'     => __( 'Embed HTML', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category' => 'media',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>',
            'fields'   => array(
                'code'   => array( 'type' => 'code', 'label' => __( 'Código HTML', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                'height' => array( 'type' => 'number', 'label' => __( 'Altura (px)', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
        ) );
    }

    /**
     * Registra bloques de módulos de Flavor
     */
    private function registrar_bloques_modulos() {
        // Verificar qué módulos están activos (preferido + legacy)
        $configuracion = get_option( 'flavor_chat_ia_settings', array() );
        $modulos_activos = $configuracion['active_modules'] ?? array();

        // Fusionar con opción legacy para compatibilidad
        $modulos_legacy = get_option( 'flavor_active_modules', array() );
        if ( ! empty( $modulos_legacy ) ) {
            $modulos_activos = array_unique( array_merge( $modulos_activos, $modulos_legacy ) );
        }

        // ============ MAPAS INTERACTIVOS ============
        // Campos comunes para todos los mapas
        $campos_mapa_comunes = array(
            'altura' => array(
                'type'    => 'number',
                'label'   => __( 'Altura (px)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => 400,
                'min'     => 200,
                'max'     => 800,
            ),
            'zoom' => array(
                'type'    => 'number',
                'label'   => __( 'Zoom inicial', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => 14,
                'min'     => 8,
                'max'     => 18,
            ),
            'mostrar_filtros' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => true,
            ),
            'mostrar_listado' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar listado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'default' => true,
            ),
        );

        if ( $this->modulo_activo( 'parkings', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mapa-parkings',
                'name'      => __( 'Mapa de Parkings', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'maps',
                'shortcode' => 'flavor_mapa_parkings',
                'module'    => 'parkings',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 9h4a2 2 0 012 2v0a2 2 0 01-2 2H9v-4z"/><path d="M9 13v4"/></svg>',
                'fields'    => array_merge( $campos_mapa_comunes, array(
                    'solo_disponibles' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Solo con plazas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => false,
                    ),
                ) ),
            ) );
        }

        if ( $this->modulo_activo( 'huertos-urbanos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mapa-huertos',
                'name'      => __( 'Mapa de Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'maps',
                'shortcode' => 'mapa_huertos',
                'module'    => 'huertos-urbanos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0110 10c0 5.52-10 12-10 12S2 17.52 2 12A10 10 0 0112 2z"/><circle cx="12" cy="10" r="3"/></svg>',
                'fields'    => array_merge( $campos_mapa_comunes, array(
                    'solo_activos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Solo activos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ) ),
            ) );
        }

        if ( $this->modulo_activo( 'compostaje', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mapa-composteras',
                'name'      => __( 'Mapa de Composteras', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'maps',
                'shortcode' => 'mapa_composteras',
                'module'    => 'compostaje',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19h16M5 19V8l7-5 7 5v11"/><path d="M9 19v-4h6v4"/></svg>',
                'fields'    => $campos_mapa_comunes,
            ) );
        }

        if ( $this->modulo_activo( 'biodiversidad-local', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mapa-biodiversidad',
                'name'      => __( 'Mapa de Biodiversidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'maps',
                'shortcode' => 'biodiversidad_mapa',
                'module'    => 'biodiversidad-local',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c4-4 8-7.5 8-12a8 8 0 10-16 0c0 4.5 4 8 8 12z"/><path d="M12 6v6l3 3"/></svg>',
                'fields'    => array_merge( $campos_mapa_comunes, array(
                    'tipo_especie' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo de especie', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'flora' => __( 'Flora', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'fauna' => __( 'Fauna', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                ) ),
            ) );
        }

        if ( $this->modulo_activo( 'incidencias', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mapa-incidencias',
                'name'      => __( 'Mapa de Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'maps',
                'shortcode' => 'mapa_incidencias',
                'module'    => 'incidencias',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                'fields'    => array_merge( $campos_mapa_comunes, array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'pendiente' => __( 'Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'en_proceso' => __( 'En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'resueltas' => __( 'Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'dias' => array(
                        'type'    => 'number',
                        'label'   => __( 'Últimos días', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 30,
                        'min'     => 7,
                        'max'     => 365,
                    ),
                ) ),
            ) );
        }

        // ============ ECONOMÍA SOCIAL ============
        if ( $this->modulo_activo( 'banco-tiempo', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'bt-dashboard',
                'name'      => __( 'Dashboard Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'bt_dashboard_sostenibilidad',
                'module'    => 'banco-tiempo',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>',
                'fields'    => array(
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'week' => __( 'Semana', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'month' => __( 'Mes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'year' => __( 'Año', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'month',
                    ),
                    'mostrar_ranking' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar ranking', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_estadisticas' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'bt-ranking',
                'name'      => __( 'Ranking Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'bt_ranking_comunidad',
                'module'    => 'banco-tiempo',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15l-2 5-3-1 2-4h3z"/><path d="M12 15l2 5 3-1-2-4h-3z"/><circle cx="12" cy="8" r="5"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Top usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 10,
                        'min'     => 3,
                        'max'     => 50,
                    ),
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'week' => __( 'Semana', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'month' => __( 'Mes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'year' => __( 'Año', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'all' => __( 'Todo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'month',
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'economia-don', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'economia-don',
                'name'      => __( 'Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'economia_don',
                'module'    => 'economia-don',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 48,
                    ),
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'mostrar_donante' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar donante', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'muro-gratitud',
                'name'      => __( 'Muro de Gratitud', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'muro_gratitud',
                'module'    => 'economia-don',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 20,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'grid' => __( 'Cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'masonry' => __( 'Masonry', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'carousel' => __( 'Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'masonry',
                    ),
                    'permitir_envio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir envío', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'grupos-consumo', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'gc-catalogo',
                'name'      => __( 'Catálogo Productos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'gc_catalogo',
                'module'    => 'grupos-consumo',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 48,
                    ),
                    'solo_disponibles' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Solo disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_productor' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar productor', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'gc-productores',
                'name'      => __( 'Productores Cercanos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'gc_productores_cercanos',
                'module'    => 'grupos-consumo',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 8,
                        'min'     => 1,
                        'max'     => 24,
                    ),
                    'radio_km' => array(
                        'type'    => 'number',
                        'label'   => __( 'Radio (km)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 50,
                        'min'     => 5,
                        'max'     => 200,
                    ),
                    'mostrar_mapa' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar mapa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // ============ COMUNIDAD ============
        if ( $this->modulo_activo( 'multimedia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'galeria-multimedia',
                'name'      => __( 'Galería Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'flavor_galeria',
                'module'    => 'multimedia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
                'fields'    => array(
                    'album' => array(
                        'type'    => 'select',
                        'label'   => __( 'Álbum', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 48,
                    ),
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
                        'default' => '4',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'grid' => __( 'Cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'masonry' => __( 'Masonry', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'grid',
                    ),
                    'lightbox' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Abrir en lightbox', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'carousel-imagenes',
                'name'      => __( 'Carrusel de Imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'flavor_carousel',
                'module'    => 'multimedia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><polyline points="6,10 2,12 6,14"/><polyline points="18,10 22,12 18,14"/></svg>',
                'fields'    => array(
                    'album' => array(
                        'type'    => 'select',
                        'label'   => __( 'Álbum', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'autoplay' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'intervalo' => array(
                        'type'    => 'number',
                        'label'   => __( 'Intervalo (seg)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 5,
                        'min'     => 2,
                        'max'     => 15,
                    ),
                    'mostrar_flechas' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar flechas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_puntos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar indicadores', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'radio', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'radio-player',
                'name'      => __( 'Player de Radio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'flavor_radio_player',
                'module'    => 'radio',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 010 8.49M7.76 16.24a6 6 0 010-8.49M19.07 4.93a10 10 0 010 14.14M4.93 19.07a10 10 0 010-14.14"/></svg>',
                'fields'    => array(
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'compacto' => __( 'Compacto', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'expandido' => __( 'Expandido', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'mini' => __( 'Mini', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'compacto',
                    ),
                    'autoplay' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => false,
                    ),
                    'mostrar_programa' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar programa actual', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_oyentes' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar oyentes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => false,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'radio-programacion',
                'name'      => __( 'Programación Radio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'flavor_radio_programacion',
                'module'    => 'radio',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'semana' => __( 'Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'dia' => __( 'Diaria', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'lista' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'semana',
                    ),
                    'mostrar_locutor' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar locutor', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'destacar_actual' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Destacar programa actual', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'avisos-municipales', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'avisos-activos',
                'name'      => __( 'Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'avisos_activos',
                'module'    => 'avisos-municipales',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'info' => __( 'Información', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'evento' => __( 'Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'servicio' => __( 'Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 5,
                        'min'     => 1,
                        'max'     => 20,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'lista' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'cards' => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'marquee' => __( 'Marquesina', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'lista',
                    ),
                    'mostrar_fecha' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar fecha', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'avisos-urgentes',
                'name'      => __( 'Avisos Urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'avisos_urgentes',
                'module'    => 'avisos-municipales',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 3,
                        'min'     => 1,
                        'max'     => 10,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'banner' => __( 'Banner', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'modal' => __( 'Modal', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'sticky' => __( 'Fijo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'banner',
                    ),
                    'auto_ocultar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Auto ocultar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => false,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'cursos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'cursos-catalogo',
                'name'      => __( 'Catálogo de Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'cursos_catalogo',
                'module'    => 'cursos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 50,
                    ),
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '2' => '2', '3' => '3', '4' => '4' ),
                        'default' => '3',
                    ),
                    'mostrar_precio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar precio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_duracion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar duración', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'eventos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'eventos-listado',
                'name'      => __( 'Listado de Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'module'    => 'eventos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'fields'    => array_merge(
                    $this->get_campos_header_seccion(),
                    array(
                        'categoria' => array(
                            'type'    => 'select',
                            'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'default' => '',
                        ),
                        'limite' => array(
                            'type'    => 'number',
                            'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'default' => 6,
                            'min'     => 1,
                            'max'     => 24,
                        ),
                        'columnas' => array(
                            'type'    => 'number',
                            'label'   => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'default' => 3,
                            'min'     => 1,
                            'max'     => 4,
                        ),
                        'vista' => array(
                            'type'    => 'select',
                            'label'   => __( 'Vista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'options' => array( 'grid' => __( 'Cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'list' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'default' => 'grid',
                        ),
                        'mostrar_fecha' => array(
                            'type'    => 'toggle',
                            'label'   => __( 'Mostrar fecha', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'default' => true,
                        ),
                        'mostrar_ubicacion' => array(
                            'type'    => 'toggle',
                            'label'   => __( 'Mostrar ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'default' => true,
                        ),
                        'mostrar_inscripcion' => array(
                            'type'    => 'toggle',
                            'label'   => __( 'Mostrar inscripción', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'default' => true,
                        ),
                        'solo_proximos' => array(
                            'type'    => 'toggle',
                            'label'   => __( 'Solo próximos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'default' => true,
                        ),
                        'mostrar_filtros' => array(
                            'type'    => 'toggle',
                            'label'   => __( 'Mostrar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'default' => false,
                        ),
                    )
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'eventos-calendario',
                'name'      => __( 'Calendario de Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'module'    => 'eventos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><rect x="7" y="14" width="3" height="3"/></svg>',
                'fields'    => array_merge(
                    $this->get_campos_header_seccion(),
                    array(
                        'vista_inicial' => array(
                            'type'    => 'select',
                            'label'   => __( 'Vista inicial', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'options' => array( 'month' => __( 'Mes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'week' => __( 'Semana', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'day' => __( 'Día', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                            'default' => 'month',
                        ),
                        'mostrar_controles' => array(
                            'type'    => 'toggle',
                            'label'   => __( 'Mostrar controles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'default' => true,
                        ),
                    )
                ),
            ) );
        }

        // ============ MÓDULOS GENERALES ============
        if ( $this->modulo_activo( 'transparencia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'transparencia-portal',
                'name'      => __( 'Portal Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'transparencia_portal',
                'module'    => 'transparencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                'fields'    => array(
                    'seccion' => array(
                        'type'    => 'select',
                        'label'   => __( 'Sección', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'contratos' => __( 'Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'subvenciones' => __( 'Subvenciones', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'personal' => __( 'Personal', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'anio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Año', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Actual', FLAVOR_PLATFORM_TEXT_DOMAIN ), '2024' => '2024', '2023' => '2023', '2022' => '2022' ),
                        'default' => '',
                    ),
                    'mostrar_buscador' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'transparencia-presupuesto',
                'name'      => __( 'Gráfico Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'transparencia_grafico_presupuesto',
                'module'    => 'transparencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 118 2.83"/><path d="M22 12A10 10 0 0012 2v10z"/></svg>',
                'fields'    => array(
                    'tipo_grafico' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo de gráfico', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'pie' => __( 'Circular', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'bar' => __( 'Barras', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'donut' => __( 'Donut', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'donut',
                    ),
                    'anio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Año', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Actual', FLAVOR_PLATFORM_TEXT_DOMAIN ), '2024' => '2024', '2023' => '2023' ),
                        'default' => '',
                    ),
                    'mostrar_leyenda' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar leyenda', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_valores' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar valores', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'presupuestos-participativos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'presupuestos-listado',
                'name'      => __( 'Proyectos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'presupuestos_listado',
                'module'    => 'presupuestos-participativos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17,11 19,13 23,9"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'votacion' => __( 'En votación', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'aprobados' => __( 'Aprobados', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'ejecucion' => __( 'En ejecución', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 50,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'votos' => __( 'Más votados', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'recientes' => __( 'Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'presupuesto' => __( 'Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'votos',
                    ),
                    'permitir_votar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir votar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'huella-ecologica', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'huella-ecologica',
                'name'      => __( 'Calculadora Huella', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'huella_ecologica_calculadora',
                'module'    => 'huella-ecologica',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c4-4 8-7.5 8-12a8 8 0 10-16 0c0 4.5 4 8 8 12z"/><circle cx="12" cy="10" r="3"/></svg>',
                'fields'    => array(
                    'modo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Modo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'completo' => __( 'Completo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'rapido' => __( 'Rápido', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'comparativo' => __( 'Comparativo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'completo',
                    ),
                    'mostrar_consejos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar consejos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'guardar_historial' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Guardar historial', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'sello-conciencia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'sello-conciencia',
                'name'      => __( 'Sello de Conciencia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'sello_conciencia',
                'module'    => 'sello-conciencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21,13.89 7,23 12,20 17,23 15.79,13.88"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'eco' => __( 'Ecológico', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'social' => __( 'Social', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'comercio_justo' => __( 'Comercio justo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'badge' => __( 'Insignia', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'card' => __( 'Tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'banner' => __( 'Banner', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'badge',
                    ),
                    'mostrar_criterios' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar criterios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Email marketing
        if ( $this->modulo_activo( 'email-marketing', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'newsletter-form',
                'name'      => __( 'Formulario Newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'flavor_suscripcion_newsletter',
                'module'    => 'email-marketing',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
                'fields'    => array(
                    'lista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'inline' => __( 'En línea', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'vertical' => __( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'card' => __( 'Tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'inline',
                    ),
                    'mostrar_nombre' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Pedir nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => false,
                    ),
                    'texto_boton' => array(
                        'type'    => 'text',
                        'label'   => __( 'Texto botón', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => __( 'Suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                ),
            ) );
        }

        // ============ LANDING PAGES ============
        // Bloque para insertar landing pages completas de módulos
        $this->registrar_bloque( array(
            'id'        => 'flavor-landing',
            'name'      => __( 'Landing de Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category'  => 'sections',
            'shortcode' => 'flavor_landing',
            'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>',
            'fields'    => array(
                'module' => array(
                    'type'    => 'select',
                    'label'   => __( 'Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => array(
                        ''                 => __( '-- Seleccionar módulo --', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'grupos-consumo'   => __( 'Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'banco-tiempo'     => __( 'Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'ayuntamiento'     => __( 'Ayuntamiento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'comunidades'      => __( 'Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'espacios-comunes' => __( 'Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'ayuda-vecinal'    => __( 'Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'huertos-urbanos'  => __( 'Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'biblioteca'       => __( 'Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'cursos'           => __( 'Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'eventos'          => __( 'Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'marketplace'      => __( 'Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'incidencias'      => __( 'Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'bicicletas'       => __( 'Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'reciclaje'        => __( 'Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'restaurante'      => __( 'Restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'peluqueria'       => __( 'Peluquería', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'gimnasio'         => __( 'Gimnasio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'clinica'          => __( 'Clínica', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'hotel'            => __( 'Hotel', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'inmobiliaria'     => __( 'Inmobiliaria', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'tienda'           => __( 'Tienda', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'podcast'          => __( 'Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'default' => '',
                ),
                'color' => array(
                    'type'    => 'color',
                    'label'   => __( 'Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => '',
                ),
            ),
        ) );

        // ============ NUEVOS MÓDULOS ============
        $this->registrar_nuevos_modulos( $modulos_activos );
    }

    /**
     * Registra bloques de módulos adicionales
     *
     * @param array $modulos_activos Lista de módulos activos.
     */
    private function registrar_nuevos_modulos( $modulos_activos ) {
        // Advertising
        if ( $this->modulo_activo( 'advertising', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'ad-banner',
                'name'      => __( 'Banner Publicitario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'flavor_ad',
                'module'    => 'advertising',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="10" rx="2"/><path d="M12 7v10"/></svg>',
                'fields'    => array(
                    'posicion' => array(
                        'type'    => 'select',
                        'label'   => __( 'Posición', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'header' => __( 'Cabecera', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'sidebar' => __( 'Lateral', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'content' => __( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'footer' => __( 'Pie', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'content',
                    ),
                    'formato' => array(
                        'type'    => 'select',
                        'label'   => __( 'Formato', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'horizontal' => __( 'Horizontal', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'vertical' => __( 'Vertical', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'cuadrado' => __( 'Cuadrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'horizontal',
                    ),
                    'rotacion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Rotar anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'ads-dashboard',
                'name'      => __( 'Dashboard Anunciante', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'flavor_ads_dashboard',
                'module'    => 'advertising',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'resumen' => __( 'Resumen', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'campanas' => __( 'Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'estadisticas' => __( 'Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'resumen',
                    ),
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '7d' => __( 'Últimos 7 días', FLAVOR_PLATFORM_TEXT_DOMAIN ), '30d' => __( 'Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN ), '90d' => __( 'Últimos 90 días', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '30d',
                    ),
                ),
            ) );
        }

        // Biblioteca
        if ( $this->modulo_activo( 'biblioteca', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'biblioteca-catalogo',
                'name'      => __( 'Catálogo Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'biblioteca_catalogo',
                'module'    => 'biblioteca',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
                'fields'    => array(
                    'genero' => array(
                        'type'    => 'select',
                        'label'   => __( 'Género', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 48,
                    ),
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '3' => '3', '4' => '4', '6' => '6' ),
                        'default' => '4',
                    ),
                    'mostrar_disponibilidad' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_autor' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar autor', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'biblioteca-mis-prestamos',
                'name'      => __( 'Mis Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'biblioteca_mis_prestamos',
                'module'    => 'biblioteca',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'activos' => __( 'Activos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'vencidos' => __( 'Vencidos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'historial' => __( 'Historial', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'activos',
                    ),
                    'mostrar_renovar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Botón renovar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Carpooling
        if ( $this->modulo_activo( 'carpooling', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'carpooling-buscar',
                'name'      => __( 'Buscar Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'carpooling_buscar_viaje',
                'module'    => 'carpooling',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><circle cx="5" cy="17" r="2"/><circle cx="12" cy="17" r="2"/><path d="M16 8h4l3 5v4h-7"/><circle cx="20" cy="17" r="2"/></svg>',
                'fields'    => array(
                    'mostrar_mapa' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar mapa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'radio_km' => array(
                        'type'    => 'number',
                        'label'   => __( 'Radio (km)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 50,
                        'min'     => 5,
                        'max'     => 200,
                    ),
                    'resultados' => array(
                        'type'    => 'number',
                        'label'   => __( 'Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'carpooling-publicar',
                'name'      => __( 'Publicar Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'carpooling_publicar_viaje',
                'module'    => 'carpooling',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
                'fields'    => array(
                    'modo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Modo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'completo' => __( 'Completo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'rapido' => __( 'Rápido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'completo',
                    ),
                    'recurrente' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir recurrentes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Chat grupos
        if ( $this->modulo_activo( 'chat-grupos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'chat-grupos-lista',
                'name'      => __( 'Lista de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'flavor_grupos_lista',
                'module'    => 'chat-grupos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Mostrar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'mis_grupos' => __( 'Mis grupos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'todos' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'recientes' => __( 'Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'mis_grupos',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'mostrar_miembros' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar miembros', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'chat-grupos-explorar',
                'name'      => __( 'Explorar Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'flavor_grupos_explorar',
                'module'    => 'chat-grupos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 6,
                        'max'     => 48,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'populares' => __( 'Más populares', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'recientes' => __( 'Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'activos' => __( 'Más activos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'populares',
                    ),
                ),
            ) );
        }

        // Chat interno
        if ( $this->modulo_activo( 'chat-interno', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'chat-inbox',
                'name'      => __( 'Bandeja de Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'flavor_chat_inbox',
                'module'    => 'chat-interno',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'lista' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'completa' => __( 'Completa', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'compacta' => __( 'Compacta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'lista',
                    ),
                    'mostrar_no_leidos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Destacar no leídos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'sonido' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Sonido notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Círculos de cuidados
        if ( $this->modulo_activo( 'circulos-cuidados', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'circulos-cuidados-lista',
                'name'      => __( 'Círculos de Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'circulos_cuidados',
                'module'    => 'circulos-cuidados',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>',
                'fields'    => array(
                    'zona' => array(
                        'type'    => 'select',
                        'label'   => __( 'Zona', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'cercanos' => __( 'Cercanos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 8,
                        'min'     => 4,
                        'max'     => 24,
                    ),
                    'mostrar_miembros' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar miembros', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'necesidades-cuidados',
                'name'      => __( 'Necesidades de Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'necesidades_cuidados',
                'module'    => 'circulos-cuidados',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'mayores' => __( 'Mayores', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'infancia' => __( 'Infancia', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'diversidad' => __( 'Diversidad funcional', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'urgencia' => array(
                        'type'    => 'select',
                        'label'   => __( 'Urgencia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'alta' => __( 'Alta', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'media' => __( 'Media', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'baja' => __( 'Baja', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'mostrar_voluntarios' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar voluntarios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Comunidades
        if ( $this->modulo_activo( 'comunidades', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'comunidades-listar',
                'name'      => __( 'Listar Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'comunidades_listar',
                'module'    => 'comunidades',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'barrio' => __( 'Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'interes' => __( 'Interés', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'proyecto' => __( 'Proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 48,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'miembros' => __( 'Más miembros', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'activas' => __( 'Más activas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'recientes' => __( 'Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'miembros',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'comunidades-actividad',
                'name'      => __( 'Feed de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'comunidades_actividad',
                'module'    => 'comunidades',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg>',
                'fields'    => array(
                    'comunidad' => array(
                        'type'    => 'select',
                        'label'   => __( 'Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 20,
                        'min'     => 5,
                        'max'     => 100,
                    ),
                    'permitir_publicar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir publicar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Economía suficiencia
        if ( $this->modulo_activo( 'economia-suficiencia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'suficiencia-intro',
                'name'      => __( 'Intro Suficiencia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'suficiencia_intro',
                'module'    => 'economia-suficiencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 6v6l4 2"/></svg>',
                'fields'    => array(
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'completo' => __( 'Completo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'resumido' => __( 'Resumido', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'visual' => __( 'Visual', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'completo',
                    ),
                    'mostrar_cta' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar CTA', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'suficiencia-evaluacion',
                'name'      => __( 'Evaluación Personal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'suficiencia_evaluacion',
                'module'    => 'economia-suficiencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>',
                'fields'    => array(
                    'modo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Modo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'completo' => __( 'Completo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'rapido' => __( 'Rápido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'completo',
                    ),
                    'guardar_resultados' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Guardar resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_recursos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar recursos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Empresarial
        if ( $this->modulo_activo( 'empresarial', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'empresarial-servicios',
                'name'      => __( 'Servicios Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'empresarial_servicios',
                'module'    => 'empresarial',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>',
                'fields'    => array(
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '2' => '2', '3' => '3', '4' => '4' ),
                        'default' => '3',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'cards' => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'lista' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'iconos' => __( 'Iconos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'cards',
                    ),
                    'mostrar_precio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar precio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'empresarial-equipo',
                'name'      => __( 'Equipo Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'empresarial_equipo',
                'module'    => 'empresarial',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
                'fields'    => array(
                    'departamento' => array(
                        'type'    => 'select',
                        'label'   => __( 'Departamento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '3' => '3', '4' => '4', '5' => '5' ),
                        'default' => '4',
                    ),
                    'mostrar_cargo' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar cargo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_redes' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar redes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'empresarial-portfolio',
                'name'      => __( 'Portfolio Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'empresarial_portfolio',
                'module'    => 'empresarial',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 9,
                        'min'     => 3,
                        'max'     => 24,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'grid' => __( 'Cuadrícula', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'masonry' => __( 'Masonry', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'carousel' => __( 'Carrusel', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'grid',
                    ),
                ),
            ) );
        }

        // Espacios comunes
        if ( $this->modulo_activo( 'espacios-comunes', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'espacios-listado',
                'name'      => __( 'Listado Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'espacios_listado',
                'module'    => 'espacios-comunes',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'sala' => __( 'Salas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'exterior' => __( 'Exteriores', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'deportivo' => __( 'Deportivos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 24,
                    ),
                    'mostrar_disponibilidad' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'espacios-calendario',
                'name'      => __( 'Calendario Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'espacios_calendario',
                'module'    => 'espacios-comunes',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'fields'    => array(
                    'espacio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Espacio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'mes' => __( 'Mes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'semana' => __( 'Semana', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'dia' => __( 'Día', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'semana',
                    ),
                    'permitir_reservar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir reservar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Facturas
        if ( $this->modulo_activo( 'facturas', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mis-facturas',
                'name'      => __( 'Mis Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'flavor_mis_facturas',
                'module'    => 'facturas',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'pendientes' => __( 'Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'pagadas' => __( 'Pagadas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'anio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Año', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), '2024' => '2024', '2023' => '2023' ),
                        'default' => '',
                    ),
                    'permitir_descarga' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir descarga', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'historial-pagos',
                'name'      => __( 'Historial Pagos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'flavor_historial_pagos',
                'module'    => 'facturas',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
                'fields'    => array(
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '30d' => __( 'Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN ), '90d' => __( 'Últimos 90 días', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'anio' => __( 'Este año', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'todo' => __( 'Todo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'anio',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 20,
                        'min'     => 10,
                        'max'     => 100,
                    ),
                ),
            ) );
        }

        // Justicia restaurativa
        if ( $this->modulo_activo( 'justicia-restaurativa', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'justicia-info',
                'name'      => __( 'Info Justicia Restaurativa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'justicia_restaurativa',
                'module'    => 'justicia-restaurativa',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
                'fields'    => array(
                    'seccion' => array(
                        'type'    => 'select',
                        'label'   => __( 'Sección', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'intro' => __( 'Introducción', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'proceso' => __( 'Proceso', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'faq' => __( 'Preguntas frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'intro',
                    ),
                    'mostrar_casos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar casos ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'solicitar-mediacion',
                'name'      => __( 'Solicitar Mediación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'solicitar_mediacion',
                'module'    => 'justicia-restaurativa',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'vecinal' => __( 'Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'familiar' => __( 'Familiar', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'comunitario' => __( 'Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'vecinal',
                    ),
                    'anonimo' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Marketplace
        if ( $this->modulo_activo( 'marketplace', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'marketplace-listado',
                'name'      => __( 'Listado Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'marketplace_listado',
                'module'    => 'marketplace',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'venta' => __( 'Venta', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'busco' => __( 'Busco', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'regalo' => __( 'Regalo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'intercambio' => __( 'Intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 48,
                    ),
                    'orden' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'recent' => __( 'Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'price_asc' => __( 'Precio menor', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'price_desc' => __( 'Precio mayor', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'recent',
                    ),
                    'mostrar_precio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar precio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'marketplace-formulario',
                'name'      => __( 'Publicar Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'marketplace_formulario',
                'module'    => 'marketplace',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
                'fields'    => array(
                    'tipo_default' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'venta' => __( 'Venta', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'busco' => __( 'Busco', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'regalo' => __( 'Regalo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'intercambio' => __( 'Intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'venta',
                    ),
                    'permitir_imagenes' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'max_imagenes' => array(
                        'type'    => 'number',
                        'label'   => __( 'Máx. imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 5,
                        'min'     => 1,
                        'max'     => 10,
                    ),
                ),
            ) );
        }

        // Participación
        if ( $this->modulo_activo( 'participacion', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'propuestas-activas',
                'name'      => __( 'Propuestas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'propuestas_activas',
                'module'    => 'participacion',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'activas' => __( 'Activas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'votacion' => __( 'En votación', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'aprobadas' => __( 'Aprobadas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'todas' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'activas',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'votos' => __( 'Más votadas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'recientes' => __( 'Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'comentarios' => __( 'Más comentadas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'recientes',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'votacion-activa',
                'name'      => __( 'Votación Activa', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'votacion_activa',
                'module'    => 'participacion',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>',
                'fields'    => array(
                    'mostrar_progreso' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar progreso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_resultados' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar resultados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => false,
                    ),
                    'permitir_comentarios' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Podcast
        if ( $this->modulo_activo( 'podcast', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'podcast-player',
                'name'      => __( 'Reproductor Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'podcast_player',
                'module'    => 'podcast',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path d="M19 10v2a7 7 0 01-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>',
                'fields'    => array(
                    'episodio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Episodio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'ultimo' => __( 'Último', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'ultimo',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'completo' => __( 'Completo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'mini' => __( 'Mini', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'card' => __( 'Tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'completo',
                    ),
                    'autoplay' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Autoplay', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => false,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'podcast-episodios',
                'name'      => __( 'Lista Episodios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'podcast_lista_episodios',
                'module'    => 'podcast',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'recientes' => __( 'Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'populares' => __( 'Más populares', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'recientes',
                    ),
                    'mostrar_duracion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar duración', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Red social
        if ( $this->modulo_activo( 'red-social', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'rs-feed',
                'name'      => __( 'Feed Social', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'rs_feed',
                'module'    => 'red-social',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'todos' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'siguiendo' => __( 'Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'populares' => __( 'Populares', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'todos',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 20,
                        'min'     => 5,
                        'max'     => 100,
                    ),
                    'permitir_publicar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir publicar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_reacciones' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar reacciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'rs-historias',
                'name'      => __( 'Historias', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'rs_historias',
                'module'    => 'red-social',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Mostrar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'todos' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'siguiendo' => __( 'Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'siguiendo',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 15,
                        'min'     => 5,
                        'max'     => 30,
                    ),
                    'permitir_crear' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir crear', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Reciclaje
        if ( $this->modulo_activo( 'reciclaje', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'reciclaje-puntos',
                'name'      => __( 'Puntos de Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'maps',
                'shortcode' => 'reciclaje_puntos_cercanos',
                'module'    => 'reciclaje',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,4 23,10 17,10"/><polyline points="1,20 1,14 7,14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'contenedor' => __( 'Contenedores', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'punto_limpio' => __( 'Puntos limpios', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'textil' => __( 'Textil', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'radio_km' => array(
                        'type'    => 'number',
                        'label'   => __( 'Radio (km)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 5,
                        'min'     => 1,
                        'max'     => 50,
                    ),
                    'mostrar_listado' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar listado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'reciclaje-guia',
                'name'      => __( 'Guía de Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'reciclaje_guia',
                'module'    => 'reciclaje',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'plasticos' => __( 'Plásticos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'vidrio' => __( 'Vidrio', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'papel' => __( 'Papel', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'organico' => __( 'Orgánico', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'visual' => __( 'Visual', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'lista' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'buscador' => __( 'Buscador', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'visual',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'reciclaje-ranking',
                'name'      => __( 'Ranking Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'reciclaje_ranking',
                'module'    => 'reciclaje',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15l-2 5-3-1 2-4h3z"/><path d="M12 15l2 5 3-1-2-4h-3z"/><circle cx="12" cy="8" r="5"/></svg>',
                'fields'    => array(
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'semana' => __( 'Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'mes' => __( 'Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'anio' => __( 'Este año', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'total' => __( 'Total', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'mes',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Top', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'mostrar_puntos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar puntos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Saberes ancestrales
        if ( $this->modulo_activo( 'saberes-ancestrales', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'saberes-catalogo',
                'name'      => __( 'Catálogo Saberes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'saberes_catalogo',
                'module'    => 'saberes-ancestrales',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'oficios' => __( 'Oficios', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'medicina' => __( 'Medicina tradicional', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'artesania' => __( 'Artesanía', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'cocina' => __( 'Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 48,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'cards' => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'lista' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'galeria' => __( 'Galería', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'cards',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'saberes-portadores',
                'name'      => __( 'Portadores de Saber', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'saberes_portadores',
                'module'    => 'saberes-ancestrales',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                'fields'    => array(
                    'especialidad' => array(
                        'type'    => 'select',
                        'label'   => __( 'Especialidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 8,
                        'min'     => 4,
                        'max'     => 24,
                    ),
                    'mostrar_bio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar biografía', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Socios
        if ( $this->modulo_activo( 'socios', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'socios-pagar-cuota',
                'name'      => __( 'Pagar Cuota', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'socios_pagar_cuota',
                'module'    => 'socios',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
                'fields'    => array(
                    'mostrar_historial' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar historial', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'metodos_pago' => array(
                        'type'    => 'select',
                        'label'   => __( 'Métodos de pago', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'todos' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'tarjeta' => __( 'Solo tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'transferencia' => __( 'Solo transferencia', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'todos',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'socios-perfil',
                'name'      => __( 'Mi Perfil Socio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'socios_mi_perfil',
                'module'    => 'socios',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                'fields'    => array(
                    'secciones' => array(
                        'type'    => 'select',
                        'label'   => __( 'Secciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'todas' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'basico' => __( 'Solo datos básicos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'todas',
                    ),
                    'editable' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir edición', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                    'mostrar_carnet' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar carnet', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Talleres
        if ( $this->modulo_activo( 'talleres', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'talleres-proximos',
                'name'      => __( 'Próximos Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'proximos_talleres',
                'module'    => 'talleres',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 6,
                        'min'     => 3,
                        'max'     => 24,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'cards' => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'lista' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'timeline' => __( 'Timeline', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'cards',
                    ),
                    'mostrar_plazas' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar plazas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'talleres-calendario',
                'name'      => __( 'Calendario Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'community',
                'shortcode' => 'calendario_talleres',
                'module'    => 'talleres',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'mes' => __( 'Mes', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'semana' => __( 'Semana', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'lista' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'mes',
                    ),
                    'mostrar_filtros' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Trabajo digno
        if ( $this->modulo_activo( 'trabajo-digno', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'trabajo-ofertas',
                'name'      => __( 'Ofertas de Trabajo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'trabajo_digno_ofertas',
                'module'    => 'trabajo-digno',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'empleo' => __( 'Empleo', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'colaboracion' => __( 'Colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'voluntariado' => __( 'Voluntariado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'sector' => array(
                        'type'    => 'select',
                        'label'   => __( 'Sector', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'mostrar_salario' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar salario', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'trabajo-formacion',
                'name'      => __( 'Formación Laboral', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'economy',
                'shortcode' => 'trabajo_digno_formacion',
                'module'    => 'trabajo-digno',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
                'fields'    => array(
                    'area' => array(
                        'type'    => 'select',
                        'label'   => __( 'Área', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 8,
                        'min'     => 4,
                        'max'     => 24,
                    ),
                    'solo_gratuitos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Solo gratuitos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => false,
                    ),
                ),
            ) );
        }

        // Trading IA
        if ( $this->modulo_activo( 'trading-ia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'trading-dashboard',
                'name'      => __( 'Dashboard Trading', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'trading_ia_dashboard',
                'module'    => 'trading-ia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,7 13.5,15.5 8.5,10.5 2,17"/><polyline points="16,7 22,7 22,13"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'resumen' => __( 'Resumen', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'detalle' => __( 'Detalle', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'grafico' => __( 'Gráfico', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'resumen',
                    ),
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '24h' => __( '24 horas', FLAVOR_PLATFORM_TEXT_DOMAIN ), '7d' => __( '7 días', FLAVOR_PLATFORM_TEXT_DOMAIN ), '30d' => __( '30 días', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '24h',
                    ),
                    'actualizar_auto' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Actualización automática', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'trading-widget',
                'name'      => __( 'Widget Precio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'trading_ia_widget_precio',
                'module'    => 'trading-ia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 100 4h4a2 2 0 110 4H8"/><path d="M12 6v2m0 8v2"/></svg>',
                'fields'    => array(
                    'moneda' => array(
                        'type'    => 'select',
                        'label'   => __( 'Moneda', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'btc' => 'Bitcoin (BTC)', 'eth' => 'Ethereum (ETH)', 'eur' => 'Euro (EUR)' ),
                        'default' => 'btc',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'mini' => __( 'Mini', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'normal' => __( 'Normal', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'completo' => __( 'Completo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'normal',
                    ),
                    'mostrar_variacion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar variación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Trámites
        if ( $this->modulo_activo( 'tramites', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'tramites-catalogo',
                'name'      => __( 'Catálogo Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'catalogo_tramites',
                'module'    => 'tramites',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todas', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( 'cards' => __( 'Tarjetas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'lista' => __( 'Lista', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'acordeon' => __( 'Acordeón', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => 'cards',
                    ),
                    'mostrar_buscador' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar buscador', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'mis-expedientes',
                'name'      => __( 'Mis Expedientes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'category'  => 'modules',
                'shortcode' => 'mis_expedientes',
                'module'    => 'tramites',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array( '' => __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'en_curso' => __( 'En curso', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'pendiente' => __( 'Pendiente doc.', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'finalizado' => __( 'Finalizados', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => 20,
                        'min'     => 5,
                        'max'     => 100,
                    ),
                    'mostrar_seguimiento' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar seguimiento', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => true,
                    ),
                ),
            ) );
        }
    }

    /**
     * Verifica si un módulo está activo
     *
     * En el contexto del editor VBP (admin), se muestran todos los bloques
     * para que el diseñador pueda trabajar con cualquier módulo.
     * En el frontend, se verifica realmente si el módulo está activo.
     *
     * @param string $modulo_id        ID del módulo.
     * @param array  $modulos_activos  Lista de módulos activos (legacy, no usado).
     * @return bool
     */
    private function modulo_activo( $modulo_id, $modulos_activos ) {
        // En el editor VBP (admin), mostrar todos los bloques para diseño
        if ( is_admin() && ! wp_doing_ajax() ) {
            return true;
        }

        // En el frontend, usar la función centralizada que verifica ambas fuentes
        if ( class_exists( 'Flavor_Chat_Module_Loader' ) ) {
            return Flavor_Chat_Module_Loader::is_module_active( $modulo_id );
        }

        // Fallback: verificar en el array proporcionado (legacy)
        $id_normalizado = str_replace( '-', '_', $modulo_id );
        return in_array( $modulo_id, $modulos_activos, true )
            || in_array( $id_normalizado, $modulos_activos, true );
    }

    /**
     * Registra un bloque
     *
     * @param array $args Argumentos del bloque.
     */
    public function registrar_bloque( $args ) {
        $defaults = array(
            'id'        => '',
            'name'      => '',
            'category'  => 'basic',
            'icon'      => '',
            'variants'  => array(),
            'fields'    => array(),
            'shortcode' => '',
            'module'    => '',
            'supports'  => array( 'styles', 'responsive', 'animation', 'ai' ),
            'presets'   => array(),
        );

        $bloque = wp_parse_args( $args, $defaults );

        // Añadir campos de colores para bloques de sección
        if ( 'sections' === $bloque['category'] ) {
            $bloque['fields'] = array_merge(
                $bloque['fields'],
                $this->get_campos_colores_seccion()
            );
        }

        // Añadir campos de estilo para bloques de módulos
        if ( ! empty( $bloque['module'] ) ) {
            $bloque['fields'] = array_merge(
                $bloque['fields'],
                $this->get_campos_estilo_comunes()
            );

            // Añadir campos de tarjeta/listado según la categoría
            $categorias_con_listado = array( 'economy', 'community', 'modules' );
            if ( in_array( $bloque['category'], $categorias_con_listado, true ) ) {
                $bloque['fields'] = array_merge(
                    $bloque['fields'],
                    $this->get_campos_tarjeta(),
                    $this->get_campos_cabecera()
                );
            }
        }

        // Añadir campos comunes automáticamente
        $bloque['fields'] = array_merge( $bloque['fields'], $this->get_campos_comunes() );

        if ( ! empty( $bloque['id'] ) ) {
            $this->bloques[ $bloque['id'] ] = $bloque;
        }
    }

    /**
     * Campos comunes para todos los bloques
     *
     * @return array
     */
    private function get_campos_comunes() {
        return array(
            // === ANIMACIONES ===
            '_animacion' => array(
                'type'    => 'select',
                'label'   => __( 'Animación de entrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group'   => 'animacion',
                'options' => array(
                    'none'       => __( 'Ninguna', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'fadeIn'     => 'Fade In',
                    'fadeInUp'   => 'Fade In Up',
                    'fadeInDown' => 'Fade In Down',
                    'fadeInLeft' => 'Fade In Left',
                    'fadeInRight'=> 'Fade In Right',
                    'slideInUp'  => 'Slide In Up',
                    'slideInDown'=> 'Slide In Down',
                    'slideInLeft'=> 'Slide In Left',
                    'slideInRight'=> 'Slide In Right',
                    'zoomIn'     => 'Zoom In',
                    'zoomInUp'   => 'Zoom In Up',
                    'bounceIn'   => 'Bounce In',
                    'flipInX'    => 'Flip In X',
                    'flipInY'    => 'Flip In Y',
                    'rotateIn'   => 'Rotate In',
                    'pulse'      => 'Pulse',
                    'shake'      => 'Shake',
                    'swing'      => 'Swing',
                    'tada'       => 'Tada',
                    'wobble'     => 'Wobble',
                    'jello'      => 'Jello',
                    'heartBeat'  => 'Heart Beat',
                ),
            ),
            '_animacion_duracion' => array(
                'type'    => 'select',
                'label'   => __( 'Duración', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group'   => 'animacion',
                'options' => array(
                    'faster' => '0.3s',
                    'fast'   => '0.5s',
                    'normal' => '0.8s',
                    'slow'   => '1s',
                    'slower' => '1.5s',
                ),
                'default' => 'normal',
            ),
            '_animacion_delay' => array(
                'type'    => 'select',
                'label'   => __( 'Retraso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group'   => 'animacion',
                'options' => array(
                    '0'    => __( 'Sin retraso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    '100'  => '0.1s',
                    '200'  => '0.2s',
                    '300'  => '0.3s',
                    '500'  => '0.5s',
                    '700'  => '0.7s',
                    '1000' => '1s',
                    '1500' => '1.5s',
                    '2000' => '2s',
                ),
                'default' => '0',
            ),
            '_animacion_trigger' => array(
                'type'    => 'select',
                'label'   => __( 'Disparador', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group'   => 'animacion',
                'options' => array(
                    'viewport' => __( 'Al entrar en viewport', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'load'     => __( 'Al cargar página', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'hover'    => __( 'Al pasar el mouse', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'click'    => __( 'Al hacer click', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'default' => 'viewport',
            ),

            // === RESPONSIVE ===
            '_ocultar_en' => array(
                'type'    => 'multiselect',
                'label'   => __( 'Ocultar en', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group'   => 'responsive',
                'options' => array(
                    'mobile'  => __( 'Móvil', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'tablet'  => __( 'Tablet', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'desktop' => __( 'Escritorio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            ),
            '_orden_mobile' => array(
                'type'    => 'number',
                'label'   => __( 'Orden en móvil', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group'   => 'responsive',
                'min'     => -10,
                'max'     => 100,
                'default' => 0,
            ),
            '_padding_mobile' => array(
                'type'    => 'spacing',
                'label'   => __( 'Padding en móvil', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group'   => 'responsive',
            ),
            '_margin_mobile' => array(
                'type'    => 'spacing',
                'label'   => __( 'Margen en móvil', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group'   => 'responsive',
            ),

            // === AVANZADO ===
            '_css_id' => array(
                'type'  => 'text',
                'label' => __( 'ID CSS', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group' => 'avanzado',
            ),
            '_css_classes' => array(
                'type'  => 'text',
                'label' => __( 'Clases CSS adicionales', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group' => 'avanzado',
            ),
            '_custom_css' => array(
                'type'     => 'code',
                'label'    => __( 'CSS personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'group'    => 'avanzado',
                'language' => 'css',
            ),
        );
    }

    /**
     * Obtiene todos los bloques
     *
     * @return array
     */
    public function get_bloques() {
        return $this->bloques;
    }

    /**
     * Obtiene bloques por categoría
     *
     * @param string $categoria ID de la categoría.
     * @return array
     */
    public function get_bloques_por_categoria( $categoria ) {
        return array_filter( $this->bloques, function ( $bloque ) use ( $categoria ) {
            return $bloque['category'] === $categoria;
        } );
    }

    /**
     * Obtiene todas las categorías con sus bloques
     *
     * @return array
     */
    public function get_categorias_con_bloques() {
        $resultado = array();
        $previews = $this->get_preview_data();

        foreach ( $this->categorias as $categoria ) {
            $bloques_categoria = $this->get_bloques_por_categoria( $categoria['id'] );

            if ( ! empty( $bloques_categoria ) ) {
                // Agregar preview_html a cada bloque de módulo
                $bloques_con_preview = array();
                foreach ( $bloques_categoria as $bloque_id => $bloque ) {
                    // Si es un bloque de módulo, agregar el preview HTML
                    if ( ! empty( $bloque['module'] ) || ! empty( $bloque['shortcode'] ) ) {
                        $preview_html = '';

                        // Buscar preview específico del bloque
                        if ( isset( $previews[ $bloque_id ] ) ) {
                            $preview_html = $previews[ $bloque_id ];
                        } elseif ( isset( $previews[ 'category_' . $categoria['id'] ] ) ) {
                            // Fallback a preview de categoría
                            $preview_html = $previews[ 'category_' . $categoria['id'] ];
                        } elseif ( isset( $previews['category_modules'] ) ) {
                            // Fallback genérico
                            $preview_html = $previews['category_modules'];
                        }

                        if ( ! empty( $preview_html ) ) {
                            $bloque['preview_html'] = $preview_html;
                        }
                    }
                    $bloques_con_preview[] = $bloque;
                }

                $resultado[] = array_merge( $categoria, array(
                    'blocks' => $bloques_con_preview,
                ) );
            }
        }

        // Ordenar por 'order'
        usort( $resultado, function ( $a, $b ) {
            return $a['order'] - $b['order'];
        } );

        return $resultado;
    }

    /**
     * Obtiene un bloque por ID
     *
     * @param string $bloque_id ID del bloque.
     * @return array|null
     */
    public function get_bloque( $bloque_id ) {
        return isset( $this->bloques[ $bloque_id ] ) ? $this->bloques[ $bloque_id ] : null;
    }

    /**
     * Renderiza un bloque
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    public function renderizar_bloque( $elemento ) {
        $bloque = $this->get_bloque( $elemento['type'] );

        if ( ! $bloque ) {
            return '<div class="vbp-element-error">Bloque no encontrado: ' . esc_html( $elemento['type'] ) . '</div>';
        }

        // Si tiene shortcode, verificar si está registrado
        if ( ! empty( $bloque['shortcode'] ) ) {
            // Verificar si el shortcode está registrado (módulo activo)
            if ( ! shortcode_exists( $bloque['shortcode'] ) ) {
                // Mostrar placeholder visual para módulos inactivos
                return $this->render_module_placeholder( $bloque, $elemento );
            }

            $atributos = isset( $elemento['data'] ) ? $elemento['data'] : array();
            return do_shortcode( $this->construir_shortcode( $bloque['shortcode'], $atributos ) );
        }

        // Renderizado personalizado según el tipo
        $metodo_render = 'render_' . str_replace( '-', '_', $elemento['type'] );
        if ( method_exists( $this, $metodo_render ) ) {
            return $this->$metodo_render( $elemento );
        }

        // Renderizado genérico
        return $this->render_generico( $elemento );
    }

    /**
     * Renderiza un preview con datos de ejemplo para módulos
     *
     * @param array $bloque   Datos del bloque.
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_module_placeholder( $bloque, $elemento ) {
        $block_id = isset( $bloque['id'] ) ? $bloque['id'] : $elemento['type'];
        $nombre_modulo = isset( $bloque['module'] ) ? $bloque['module'] : 'desconocido';
        $nombre_bloque = isset( $bloque['name'] ) ? $bloque['name'] : $elemento['type'];
        $data = isset( $elemento['data'] ) ? $elemento['data'] : array();

        // Generar estilos dinámicos basados en las opciones
        $estilos_css = $this->generar_estilos_preview( $data );

        // Intentar obtener preview con datos de ejemplo
        $preview_html = $this->get_module_preview( $block_id, $bloque, $elemento );

        if ( ! empty( $preview_html ) ) {
            $clases_preview = 'vbp-module-preview';
            if ( ! empty( $data['estilo_tarjeta'] ) ) {
                $clases_preview .= ' vbp-card-style-' . esc_attr( $data['estilo_tarjeta'] );
            }
            if ( ! empty( $data['hover_effect'] ) ) {
                $clases_preview .= ' vbp-hover-' . esc_attr( $data['hover_effect'] );
            }
            if ( ! empty( $data['animacion_entrada'] ) && 'none' !== $data['animacion_entrada'] ) {
                $clases_preview .= ' vbp-animate-' . esc_attr( $data['animacion_entrada'] );
            }

            return '<div class="' . esc_attr( $clases_preview ) . '" data-module="' . esc_attr( $nombre_modulo ) . '" data-block="' . esc_attr( $block_id ) . '" style="' . esc_attr( $estilos_css ) . '">' .
                   '<div class="vbp-module-preview-badge">' . esc_html__( 'Preview', FLAVOR_PLATFORM_TEXT_DOMAIN ) . ' - ' . esc_html( $nombre_bloque ) . '</div>' .
                   $preview_html .
                   '</div>';
        }

        // Fallback a placeholder básico
        return $this->render_basic_placeholder( $bloque, $elemento );
    }

    /**
     * Genera CSS inline para la preview basado en las opciones
     *
     * @param array $data Opciones del elemento.
     * @return string CSS inline.
     */
    private function generar_estilos_preview( $data ) {
        $estilos = array();

        // Esquema de colores
        $colores = array(
            'primary' => array( '#3b82f6', '#1d4ed8' ),
            'success' => array( '#22c55e', '#16a34a' ),
            'warning' => array( '#f59e0b', '#d97706' ),
            'danger'  => array( '#ef4444', '#dc2626' ),
            'purple'  => array( '#8b5cf6', '#7c3aed' ),
            'pink'    => array( '#ec4899', '#db2777' ),
            'dark'    => array( '#1e293b', '#0f172a' ),
        );

        $esquema = isset( $data['esquema_color'] ) ? $data['esquema_color'] : 'default';
        if ( isset( $colores[ $esquema ] ) ) {
            $estilos[] = '--vbp-color-primary: ' . $colores[ $esquema ][0];
            $estilos[] = '--vbp-color-primary-dark: ' . $colores[ $esquema ][1];
        } elseif ( 'custom' === $esquema ) {
            if ( ! empty( $data['color_primario'] ) ) {
                $estilos[] = '--vbp-color-primary: ' . $data['color_primario'];
            }
            if ( ! empty( $data['color_secundario'] ) ) {
                $estilos[] = '--vbp-color-secondary: ' . $data['color_secundario'];
            }
        }

        // Radio de bordes
        $radios = array(
            'none' => '0',
            'sm'   => '4px',
            'md'   => '8px',
            'lg'   => '12px',
            'xl'   => '16px',
            'full' => '9999px',
        );
        if ( ! empty( $data['radio_bordes'] ) && isset( $radios[ $data['radio_bordes'] ] ) ) {
            $estilos[] = '--vbp-radius: ' . $radios[ $data['radio_bordes'] ];
        }

        // Sombras
        $sombras = array(
            'none' => 'none',
            'sm'   => '0 1px 2px rgba(0,0,0,0.05)',
            'md'   => '0 4px 6px rgba(0,0,0,0.1)',
            'lg'   => '0 10px 15px rgba(0,0,0,0.1)',
            'xl'   => '0 20px 25px rgba(0,0,0,0.15)',
        );
        if ( ! empty( $data['sombra'] ) && isset( $sombras[ $data['sombra'] ] ) ) {
            $estilos[] = '--vbp-shadow: ' . $sombras[ $data['sombra'] ];
        }

        return implode( '; ', $estilos );
    }

    /**
     * Obtiene el HTML de preview con datos de ejemplo
     */
    private function get_module_preview( $block_id, $bloque, $elemento ) {
        $previews = $this->get_preview_data();

        if ( isset( $previews[ $block_id ] ) ) {
            return $previews[ $block_id ];
        }

        // Previews genéricos por categoría
        $category = isset( $bloque['category'] ) ? $bloque['category'] : '';
        if ( isset( $previews[ 'category_' . $category ] ) ) {
            return $previews[ 'category_' . $category ];
        }

        return '';
    }

    /**
     * Registra bloques de widgets del Dashboard Unificado
     *
     * Permite usar cualquier widget del dashboard como bloque
     * insertable en páginas públicas mediante shortcodes.
     *
     * @since 4.2.0
     */
    private function registrar_bloques_dashboard_widgets() {
        // Verificar que existe la clase del registro de widgets
        if ( ! class_exists( 'Flavor_Widget_Registry' ) ) {
            return;
        }

        // Obtener todos los widgets registrados
        $registry = Flavor_Widget_Registry::get_instance();
        $all_widgets = $registry->get_all( true );
        $categories = $registry->get_categories();

        if ( empty( $all_widgets ) ) {
            return;
        }

        // Bloque genérico para insertar cualquier widget
        $widget_options = array( '' => __( 'Seleccionar widget...', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        foreach ( $all_widgets as $widget_id => $widget_data ) {
            $config = $widget_data['config'];
            $cat_id = $config['category'] ?? 'sistema';
            $cat_label = isset( $categories[ $cat_id ]['label'] ) ? $categories[ $cat_id ]['label'] : $cat_id;
            $widget_options[ $widget_id ] = sprintf( '[%s] %s', $cat_label, $config['title'] ?? $widget_id );
        }

        // Bloque principal: Widget Individual
        $this->registrar_bloque( array(
            'id'        => 'dashboard-widget',
            'name'      => __( 'Widget Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category'  => 'dashboard',
            'shortcode' => 'flavor_widget',
            'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="4" height="6" fill="currentColor" opacity="0.2"/><rect x="13" y="7" width="4" height="10" fill="currentColor" opacity="0.2"/></svg>',
            'fields'    => array(
                'id' => array(
                    'type'    => 'select',
                    'label'   => __( 'Widget', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => $widget_options,
                    'default' => '',
                ),
                'titulo' => array(
                    'type'    => 'text',
                    'label'   => __( 'Título personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => '',
                    'placeholder' => __( 'Dejar vacío para usar el del widget', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'titulo_visible' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar título', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'estilo' => array(
                    'type'    => 'select',
                    'label'   => __( 'Estilo visual', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => array(
                        'elevated' => __( 'Elevado (sombra)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'outlined' => __( 'Con borde', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'flat'     => __( 'Plano', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'glass'    => __( 'Glassmorphism', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'default' => 'elevated',
                ),
                'animacion' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Animación hover', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
                'acciones' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar acciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
            ),
        ) );

        // Bloque: Grid de Múltiples Widgets
        $this->registrar_bloque( array(
            'id'        => 'dashboard-widgets-grid',
            'name'      => __( 'Grid de Widgets', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category'  => 'dashboard',
            'shortcode' => 'flavor_widgets',
            'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
            'fields'    => array(
                'ids' => array(
                    'type'    => 'text',
                    'label'   => __( 'IDs de widgets', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => '',
                    'placeholder' => __( 'eventos,reservas,socios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'description' => __( 'Separar IDs con comas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'columnas' => array(
                    'type'    => 'select',
                    'label'   => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => array(
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                    ),
                    'default' => '2',
                ),
                'gap' => array(
                    'type'    => 'select',
                    'label'   => __( 'Espaciado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => array(
                        'compact'     => __( 'Compacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'normal'      => __( 'Normal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'comfortable' => __( 'Espacioso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'default' => 'normal',
                ),
                'estilo' => array(
                    'type'    => 'select',
                    'label'   => __( 'Estilo visual', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => array(
                        'elevated' => __( 'Elevado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'outlined' => __( 'Con borde', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'flat'     => __( 'Plano', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'glass'    => __( 'Glass', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'default' => 'elevated',
                ),
            ),
        ) );

        // Bloque: Widgets por Categoría
        $categoria_options = array( '' => __( 'Seleccionar categoría...', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        foreach ( $categories as $cat_id => $cat_info ) {
            $categoria_options[ $cat_id ] = $cat_info['label'];
        }

        $this->registrar_bloque( array(
            'id'        => 'dashboard-widgets-category',
            'name'      => __( 'Widgets por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'category'  => 'dashboard',
            'shortcode' => 'flavor_widgets_categoria',
            'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3H6a2 2 0 00-2 2v14c0 1.1.9 2 2 2h12a2 2 0 002-2V9l-6-6z"/><path d="M14 3v6h6"/></svg>',
            'fields'    => array(
                'categoria' => array(
                    'type'    => 'select',
                    'label'   => __( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => $categoria_options,
                    'default' => '',
                ),
                'limite' => array(
                    'type'    => 'number',
                    'label'   => __( 'Límite', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => 4,
                    'min'     => 1,
                    'max'     => 12,
                ),
                'columnas' => array(
                    'type'    => 'select',
                    'label'   => __( 'Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
                    'default' => '2',
                ),
                'titulo' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar título de categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'default' => true,
                ),
            ),
        ) );

        // Registrar widgets individuales más populares como bloques directos
        $widgets_populares = array(
            'eventos'     => array( 'name' => __( 'Widget: Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'icon' => 'dashicons-calendar-alt' ),
            'reservas'    => array( 'name' => __( 'Widget: Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'icon' => 'dashicons-tickets-alt' ),
            'socios'      => array( 'name' => __( 'Widget: Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'icon' => 'dashicons-id-alt' ),
            'comunidades' => array( 'name' => __( 'Widget: Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'icon' => 'dashicons-groups' ),
            'foros'       => array( 'name' => __( 'Widget: Foros', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'icon' => 'dashicons-format-chat' ),
            'marketplace' => array( 'name' => __( 'Widget: Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN ), 'icon' => 'dashicons-store' ),
        );

        foreach ( $widgets_populares as $widget_id => $widget_info ) {
            // Solo registrar si el widget existe
            if ( ! isset( $all_widgets[ $widget_id ] ) ) {
                continue;
            }

            $config = $all_widgets[ $widget_id ]['config'];
            $icon_svg = $this->dashicon_to_svg( $config['icon'] ?? $widget_info['icon'] );

            $this->registrar_bloque( array(
                'id'        => 'widget-' . $widget_id,
                'name'      => $widget_info['name'],
                'category'  => 'dashboard',
                'shortcode' => 'flavor_widget',
                'shortcode_defaults' => array( 'id' => $widget_id ),
                'icon'      => $icon_svg,
                'fields'    => array(
                    'titulo' => array(
                        'type'    => 'text',
                        'label'   => __( 'Título personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'options' => array(
                            'elevated' => __( 'Elevado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'outlined' => __( 'Con borde', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'flat'     => __( 'Plano', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            'glass'    => __( 'Glass', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        ),
                        'default' => 'elevated',
                    ),
                ),
            ) );
        }
    }

    /**
     * Convierte un dashicon a SVG para usar en el editor
     *
     * @param string $dashicon Clase dashicon (ej: dashicons-calendar)
     * @return string SVG string
     */
    private function dashicon_to_svg( $dashicon ) {
        // Mapa básico de dashicons a SVG
        $svg_map = array(
            'dashicons-calendar-alt' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            'dashicons-tickets-alt'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9a3 3 0 013-3h14a3 3 0 013 3M2 9v6a3 3 0 003 3h14a3 3 0 003-3V9M2 9l3-3M22 9l-3-3M9 12h6"/></svg>',
            'dashicons-id-alt'       => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="11" r="2"/><path d="M14 11h4M14 15h2"/></svg>',
            'dashicons-groups'       => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
            'dashicons-format-chat'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
            'dashicons-store'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>',
        );

        return isset( $svg_map[ $dashicon ] )
            ? $svg_map[ $dashicon ]
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>';
    }

    /**
     * Datos de preview para cada tipo de widget
     */
    private function get_preview_data() {
        return array(
            // ========== MAPAS ==========
            'mapa-parkings' => $this->preview_mapa( 'Parkings disponibles', '12 plazas libres', '#3b82f6' ),
            'mapa-huertos' => $this->preview_mapa( 'Huertos urbanos', '8 parcelas disponibles', '#22c55e' ),
            'mapa-composteras' => $this->preview_mapa( 'Composteras', '5 puntos activos', '#84cc16' ),
            'mapa-biodiversidad' => $this->preview_mapa( 'Biodiversidad local', '24 avistamientos', '#10b981' ),
            'mapa-incidencias' => $this->preview_mapa( 'Incidencias', '3 reportes abiertos', '#f59e0b' ),

            // ========== ECONOMÍA SOCIAL ==========
            'bt-dashboard' => $this->preview_banco_tiempo(),
            'bt-ranking' => $this->preview_ranking(),
            'economia-don' => $this->preview_economia_don(),
            'muro-gratitud' => $this->preview_muro_gratitud(),
            'gc-catalogo' => $this->preview_catalogo_productos(),
            'gc-productores' => $this->preview_productores(),

            // ========== COMUNIDAD ==========
            'galeria-multimedia' => $this->preview_galeria(),
            'carousel-imagenes' => $this->preview_carousel(),
            'radio-player' => $this->preview_radio(),
            'radio-programacion' => $this->preview_radio(),
            'avisos-activos' => $this->preview_avisos(),
            'avisos-urgentes' => $this->preview_avisos_urgentes(),

            // ========== EVENTOS/CURSOS ==========
            'cursos-catalogo' => $this->preview_cursos(),
            'eventos-listado' => $this->preview_eventos(),
            'eventos-calendario' => $this->preview_calendario(),

            // ========== PARTICIPACIÓN ==========
            'transparencia-portal' => $this->preview_transparencia(),
            'transparencia-presupuesto' => $this->preview_presupuestos(),
            'presupuestos-listado' => $this->preview_presupuestos(),

            // ========== SOSTENIBILIDAD ==========
            'huella-ecologica' => $this->preview_huella_ecologica(),
            'sello-conciencia' => $this->preview_sello_conciencia(),
            'reciclaje-puntos' => $this->preview_reciclaje(),
            'reciclaje-guia' => $this->preview_reciclaje(),
            'reciclaje-ranking' => $this->preview_ranking(),

            // ========== COMUNICACIÓN ==========
            'newsletter-form' => $this->preview_newsletter(),
            'ad-banner' => $this->preview_ad_banner(),
            'ads-dashboard' => $this->preview_dashboard_generic( 'Publicidad', '💰' ),
            'podcast-player' => $this->preview_podcast(),
            'podcast-episodios' => $this->preview_podcast(),
            'rs-feed' => $this->preview_social_feed(),
            'rs-historias' => $this->preview_social_feed(),

            // ========== SERVICIOS COMUNITARIOS ==========
            'biblioteca-catalogo' => $this->preview_biblioteca(),
            'biblioteca-mis-prestamos' => $this->preview_biblioteca(),
            'carpooling-buscar' => $this->preview_carpooling(),
            'carpooling-publicar' => $this->preview_carpooling(),
            'espacios-listado' => $this->preview_espacios(),
            'espacios-calendario' => $this->preview_calendario(),

            // ========== CHAT Y COMUNIDADES ==========
            'chat-grupos-lista' => $this->preview_chat_grupos(),
            'chat-grupos-explorar' => $this->preview_chat_grupos(),
            'chat-inbox' => $this->preview_chat_inbox(),
            'circulos-cuidados-lista' => $this->preview_cuidados(),
            'necesidades-cuidados' => $this->preview_cuidados(),
            'comunidades-listar' => $this->preview_comunidades(),
            'comunidades-actividad' => $this->preview_social_feed(),

            // ========== ECONOMÍA Y TRABAJO ==========
            'suficiencia-intro' => $this->preview_suficiencia(),
            'suficiencia-evaluacion' => $this->preview_suficiencia(),
            'trabajo-ofertas' => $this->preview_trabajo(),
            'trabajo-formacion' => $this->preview_cursos(),
            'marketplace-listado' => $this->preview_catalogo_productos(),
            'marketplace-formulario' => $this->preview_marketplace_form(),
            'trading-dashboard' => $this->preview_trading(),
            'trading-widget' => $this->preview_trading_widget(),

            // ========== EMPRESARIAL ==========
            'empresarial-servicios' => $this->preview_empresarial(),
            'empresarial-equipo' => $this->preview_equipo(),
            'empresarial-portfolio' => $this->preview_galeria(),

            // ========== GESTIÓN Y TRÁMITES ==========
            'tramites-catalogo' => $this->preview_tramites(),
            'mis-expedientes' => $this->preview_expedientes(),
            'mis-facturas' => $this->preview_facturas(),
            'historial-pagos' => $this->preview_facturas(),
            'socios-pagar-cuota' => $this->preview_socios(),
            'socios-perfil' => $this->preview_perfil(),

            // ========== PARTICIPACIÓN CIUDADANA ==========
            'propuestas-activas' => $this->preview_propuestas(),
            'votacion-activa' => $this->preview_votacion(),
            'justicia-info' => $this->preview_justicia(),
            'solicitar-mediacion' => $this->preview_justicia(),

            // ========== TALLERES Y SABERES ==========
            'talleres-proximos' => $this->preview_eventos(),
            'talleres-calendario' => $this->preview_calendario(),
            'saberes-catalogo' => $this->preview_saberes(),
            'saberes-portadores' => $this->preview_productores(),

            // ========== CATEGORÍAS GENÉRICAS ==========
            'category_maps' => $this->preview_mapa( 'Mapa interactivo', 'Datos de ejemplo', '#6366f1' ),
            'category_economy' => $this->preview_economy_generic(),
            'category_community' => $this->preview_community_generic(),
            'category_modules' => $this->preview_module_generic(),
        );
    }

    /**
     * Preview de mapa genérico
     */
    private function preview_mapa( $titulo, $subtitulo, $color ) {
        return '
        <div class="vbp-preview-map" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border-radius: 12px; padding: 0; overflow: hidden; min-height: 300px; position: relative;">
            <div style="position: absolute; inset: 0; background: url(\'data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><rect fill=\"%23818cf8\" fill-opacity=\"0.1\" width=\"100\" height=\"100\"/><path d=\"M0 50h100M50 0v100\" stroke=\"%23818cf8\" stroke-opacity=\"0.2\" stroke-width=\"0.5\"/></svg>\') repeat; opacity: 0.5;"></div>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; z-index: 1;">
                <div style="width: 60px; height: 60px; background: ' . esc_attr( $color ) . '; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); margin: 0 auto 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <div style="width: 24px; height: 24px; background: white; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(45deg);"></div>
                </div>
                <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 600; color: #1e293b;">' . esc_html( $titulo ) . '</h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">' . esc_html( $subtitulo ) . '</p>
            </div>
            <div style="position: absolute; bottom: 16px; right: 16px; display: flex; gap: 8px;">
                <button style="width: 36px; height: 36px; background: white; border: none; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; font-size: 18px;">+</button>
                <button style="width: 36px; height: 36px; background: white; border: none; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; font-size: 18px;">−</button>
            </div>
        </div>';
    }

    /**
     * Preview banco de tiempo dashboard
     */
    private function preview_banco_tiempo() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1e293b;">Mi Banco de Tiempo</h3>
                    <p style="margin: 0; font-size: 14px; color: #64748b;">Balance actual</p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                <div style="text-align: center; padding: 16px; background: #f0fdf4; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: 700; color: #22c55e;">+12</div>
                    <div style="font-size: 12px; color: #16a34a;">Horas dadas</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #fef3c7; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: 700; color: #f59e0b;">8</div>
                    <div style="font-size: 12px; color: #d97706;">Horas recibidas</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #eff6ff; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: 700; color: #3b82f6;">+4</div>
                    <div style="font-size: 12px; color: #2563eb;">Balance</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview ranking comunitario
     */
    private function preview_ranking() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">🏆 Ranking Comunitario</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: linear-gradient(90deg, #fef3c7, transparent); border-radius: 8px;">
                    <span style="font-size: 24px;">🥇</span>
                    <div style="width: 40px; height: 40px; background: #e0e7ff; border-radius: 50%;"></div>
                    <div style="flex: 1;"><strong>María G.</strong><br><span style="font-size: 12px; color: #64748b;">45 horas compartidas</span></div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: linear-gradient(90deg, #f1f5f9, transparent); border-radius: 8px;">
                    <span style="font-size: 24px;">🥈</span>
                    <div style="width: 40px; height: 40px; background: #dbeafe; border-radius: 50%;"></div>
                    <div style="flex: 1;"><strong>Carlos R.</strong><br><span style="font-size: 12px; color: #64748b;">38 horas compartidas</span></div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: linear-gradient(90deg, #fef2f2, transparent); border-radius: 8px;">
                    <span style="font-size: 24px;">🥉</span>
                    <div style="width: 40px; height: 40px; background: #fce7f3; border-radius: 50%;"></div>
                    <div style="flex: 1;"><strong>Ana L.</strong><br><span style="font-size: 12px; color: #64748b;">32 horas compartidas</span></div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview economía del don
     */
    private function preview_economia_don() {
        return '
        <div style="background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%); border-radius: 12px; padding: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 8px;">💝</div>
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #1e293b;">Economía del Don</h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">Compartir sin esperar nada a cambio</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #ec4899;">156</div>
                    <div style="font-size: 12px; color: #64748b;">Regalos dados</div>
                </div>
                <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #8b5cf6;">89</div>
                    <div style="font-size: 12px; color: #64748b;">Personas ayudadas</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview muro de gratitud
     */
    private function preview_muro_gratitud() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">✨ Muro de Gratitud</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="padding: 16px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <p style="margin: 0 0 8px; font-style: italic; color: #1e293b;">"Gracias María por ayudarme con las clases de inglés"</p>
                    <span style="font-size: 12px; color: #64748b;">— Pedro M. hace 2 horas</span>
                </div>
                <div style="padding: 16px; background: #fce7f3; border-radius: 8px; border-left: 4px solid #ec4899;">
                    <p style="margin: 0 0 8px; font-style: italic; color: #1e293b;">"Increíble el taller de costura, ¡aprendí muchísimo!"</p>
                    <span style="font-size: 12px; color: #64748b;">— Laura S. hace 1 día</span>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview catálogo de productos
     */
    private function preview_catalogo_productos() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">🛒 Catálogo de Productos</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="height: 100px; background: linear-gradient(135deg, #dcfce7, #bbf7d0);"></div>
                    <div style="padding: 12px;">
                        <h4 style="margin: 0 0 4px; font-size: 14px;">Tomates ecológicos</h4>
                        <p style="margin: 0; font-size: 16px; font-weight: 600; color: #22c55e;">2,50€/kg</p>
                    </div>
                </div>
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="height: 100px; background: linear-gradient(135deg, #fef3c7, #fde68a);"></div>
                    <div style="padding: 12px;">
                        <h4 style="margin: 0 0 4px; font-size: 14px;">Miel artesanal</h4>
                        <p style="margin: 0; font-size: 16px; font-weight: 600; color: #22c55e;">8,00€</p>
                    </div>
                </div>
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="height: 100px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe);"></div>
                    <div style="padding: 12px;">
                        <h4 style="margin: 0 0 4px; font-size: 14px;">Queso de cabra</h4>
                        <p style="margin: 0; font-size: 16px; font-weight: 600; color: #22c55e;">12,00€</p>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview productores
     */
    private function preview_productores() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">🌱 Productores Cercanos</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">🥬</div>
                    <div style="flex: 1;">
                        <strong>Huerta La Verde</strong><br>
                        <span style="font-size: 12px; color: #64748b;">📍 2.3 km • Verduras ecológicas</span>
                    </div>
                    <span style="background: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Abierto</span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">🍯</div>
                    <div style="flex: 1;">
                        <strong>Apiario Natural</strong><br>
                        <span style="font-size: 12px; color: #64748b;">📍 4.1 km • Miel y derivados</span>
                    </div>
                    <span style="background: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Abierto</span>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview galería
     */
    private function preview_galeria() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📸 Galería Multimedia</h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                <div style="aspect-ratio: 1; background: linear-gradient(135deg, #fce7f3, #fbcfe8); border-radius: 8px;"></div>
                <div style="aspect-ratio: 1; background: linear-gradient(135deg, #dbeafe, #bfdbfe); border-radius: 8px;"></div>
                <div style="aspect-ratio: 1; background: linear-gradient(135deg, #dcfce7, #bbf7d0); border-radius: 8px;"></div>
                <div style="aspect-ratio: 1; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 8px;"></div>
                <div style="aspect-ratio: 1; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); border-radius: 8px;"></div>
                <div style="aspect-ratio: 1; background: linear-gradient(135deg, #fae8ff, #f5d0fe); border-radius: 8px;"></div>
                <div style="aspect-ratio: 1; background: linear-gradient(135deg, #ccfbf1, #99f6e4); border-radius: 8px;"></div>
                <div style="aspect-ratio: 1; background: linear-gradient(135deg, #fee2e2, #fecaca); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #64748b;">+12</div>
            </div>
        </div>';
    }

    /**
     * Preview carousel
     */
    private function preview_carousel() {
        return '
        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="height: 200px; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); display: flex; align-items: center; justify-content: center; position: relative;">
                <div style="text-align: center; color: white;">
                    <div style="font-size: 48px; margin-bottom: 8px;">🖼️</div>
                    <p style="margin: 0; font-size: 16px;">Carrusel de Imágenes</p>
                </div>
                <button style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; font-size: 18px;">‹</button>
                <button style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; font-size: 18px;">›</button>
            </div>
            <div style="display: flex; justify-content: center; gap: 8px; padding: 12px;">
                <span style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%;"></span>
                <span style="width: 8px; height: 8px; background: #e2e8f0; border-radius: 50%;"></span>
                <span style="width: 8px; height: 8px; background: #e2e8f0; border-radius: 50%;"></span>
            </div>
        </div>';
    }

    /**
     * Preview radio
     */
    private function preview_radio() {
        return '
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 12px; padding: 24px; color: white;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #ec4899, #8b5cf6); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 32px;">📻</div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 600;">Radio Comunitaria</h3>
                    <p style="margin: 0 0 8px; font-size: 14px; color: #94a3b8;">En directo ahora</p>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">♪ Programa de la mañana</p>
                </div>
                <button style="width: 56px; height: 56px; background: #22c55e; border: none; border-radius: 50%; cursor: pointer; font-size: 24px;">▶</button>
            </div>
            <div style="margin-top: 16px; height: 4px; background: #334155; border-radius: 2px;">
                <div style="width: 60%; height: 100%; background: linear-gradient(90deg, #22c55e, #16a34a); border-radius: 2px;"></div>
            </div>
        </div>';
    }

    /**
     * Preview avisos
     */
    private function preview_avisos() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📢 Avisos Municipales</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="padding: 12px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;">OBRAS</span>
                        <span style="font-size: 12px; color: #64748b;">Hace 2 horas</span>
                    </div>
                    <p style="margin: 0; font-size: 14px; color: #1e293b;">Corte de tráfico en Calle Mayor por obras</p>
                </div>
                <div style="padding: 12px; background: #eff6ff; border-radius: 8px; border-left: 4px solid #3b82f6;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="background: #3b82f6; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;">EVENTO</span>
                        <span style="font-size: 12px; color: #64748b;">Ayer</span>
                    </div>
                    <p style="margin: 0; font-size: 14px; color: #1e293b;">Fiestas patronales: programa completo</p>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview avisos urgentes
     */
    private function preview_avisos_urgentes() {
        return '
        <div style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #ef4444; border-radius: 12px; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 48px; height: 48px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: pulse 2s infinite;">
                    <span style="font-size: 24px;">⚠️</span>
                </div>
                <div>
                    <span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">URGENTE</span>
                    <h4 style="margin: 4px 0 0; font-size: 16px; color: #991b1b;">Corte de agua programado</h4>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #b91c1c;">Mañana de 8:00 a 14:00 en zona centro</p>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview cursos
     */
    private function preview_cursos() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📚 Catálogo de Cursos</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="height: 80px; background: linear-gradient(135deg, #3b82f6, #1d4ed8);"></div>
                    <div style="padding: 12px;">
                        <span style="background: #dbeafe; color: #1d4ed8; padding: 2px 6px; border-radius: 4px; font-size: 10px;">ONLINE</span>
                        <h4 style="margin: 8px 0 4px; font-size: 14px;">Introducción a la programación</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">12 plazas disponibles</p>
                    </div>
                </div>
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="height: 80px; background: linear-gradient(135deg, #22c55e, #16a34a);"></div>
                    <div style="padding: 12px;">
                        <span style="background: #dcfce7; color: #16a34a; padding: 2px 6px; border-radius: 4px; font-size: 10px;">PRESENCIAL</span>
                        <h4 style="margin: 8px 0 4px; font-size: 14px;">Taller de huerto urbano</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">8 plazas disponibles</p>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview eventos
     */
    private function preview_eventos() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📅 Próximos Eventos</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="text-align: center; background: #3b82f6; color: white; padding: 8px 12px; border-radius: 8px;">
                        <div style="font-size: 20px; font-weight: 700;">15</div>
                        <div style="font-size: 11px;">MAR</div>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 4px; font-size: 14px;">Mercadillo solidario</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">📍 Plaza Mayor • 10:00</p>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="text-align: center; background: #8b5cf6; color: white; padding: 8px 12px; border-radius: 8px;">
                        <div style="font-size: 20px; font-weight: 700;">22</div>
                        <div style="font-size: 11px;">MAR</div>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 4px; font-size: 14px;">Concierto benéfico</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">📍 Auditorio • 20:00</p>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview calendario
     */
    private function preview_calendario() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1e293b;">Marzo 2024</h3>
                <div style="display: flex; gap: 8px;">
                    <button style="width: 32px; height: 32px; background: #f1f5f9; border: none; border-radius: 6px; cursor: pointer;">‹</button>
                    <button style="width: 32px; height: 32px; background: #f1f5f9; border: none; border-radius: 6px; cursor: pointer;">›</button>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; font-size: 12px;">
                <div style="color: #94a3b8; padding: 8px;">L</div>
                <div style="color: #94a3b8; padding: 8px;">M</div>
                <div style="color: #94a3b8; padding: 8px;">X</div>
                <div style="color: #94a3b8; padding: 8px;">J</div>
                <div style="color: #94a3b8; padding: 8px;">V</div>
                <div style="color: #94a3b8; padding: 8px;">S</div>
                <div style="color: #94a3b8; padding: 8px;">D</div>
                <div style="padding: 8px; color: #cbd5e1;">26</div>
                <div style="padding: 8px; color: #cbd5e1;">27</div>
                <div style="padding: 8px; color: #cbd5e1;">28</div>
                <div style="padding: 8px; color: #cbd5e1;">29</div>
                <div style="padding: 8px;">1</div>
                <div style="padding: 8px;">2</div>
                <div style="padding: 8px;">3</div>
                <div style="padding: 8px;">4</div>
                <div style="padding: 8px;">5</div>
                <div style="padding: 8px;">6</div>
                <div style="padding: 8px;">7</div>
                <div style="padding: 8px;">8</div>
                <div style="padding: 8px;">9</div>
                <div style="padding: 8px;">10</div>
                <div style="padding: 8px; background: #3b82f6; color: white; border-radius: 6px;">11</div>
                <div style="padding: 8px;">12</div>
                <div style="padding: 8px;">13</div>
                <div style="padding: 8px;">14</div>
                <div style="padding: 8px; background: #22c55e; color: white; border-radius: 6px;">15</div>
                <div style="padding: 8px;">16</div>
                <div style="padding: 8px;">17</div>
            </div>
        </div>';
    }

    /**
     * Preview transparencia
     */
    private function preview_transparencia() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📊 Portal de Transparencia</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div style="padding: 16px; background: #f0fdf4; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 4px;">📋</div>
                    <div style="font-size: 14px; font-weight: 600; color: #1e293b;">Presupuestos</div>
                </div>
                <div style="padding: 16px; background: #eff6ff; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 4px;">👥</div>
                    <div style="font-size: 14px; font-weight: 600; color: #1e293b;">Organigrama</div>
                </div>
                <div style="padding: 16px; background: #fef3c7; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 4px;">📄</div>
                    <div style="font-size: 14px; font-weight: 600; color: #1e293b;">Contratos</div>
                </div>
                <div style="padding: 16px; background: #fce7f3; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 4px;">📈</div>
                    <div style="font-size: 14px; font-weight: 600; color: #1e293b;">Indicadores</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview presupuestos participativos
     */
    private function preview_presupuestos() {
        return '
        <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 12px; padding: 24px;">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">💰 Presupuestos Participativos</h3>
            <div style="background: white; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 600;">Parque infantil zona norte</span>
                    <span style="background: #22c55e; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">342 votos</span>
                </div>
                <div style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                    <div style="width: 78%; height: 100%; background: linear-gradient(90deg, #22c55e, #16a34a);"></div>
                </div>
            </div>
            <div style="background: white; border-radius: 8px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 600;">Carril bici centro</span>
                    <span style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">287 votos</span>
                </div>
                <div style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                    <div style="width: 65%; height: 100%; background: linear-gradient(90deg, #3b82f6, #1d4ed8);"></div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview huella ecológica
     */
    private function preview_huella_ecologica() {
        return '
        <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 12px; padding: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 8px;">🌍</div>
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #1e293b;">Tu Huella Ecológica</h3>
            </div>
            <div style="background: white; border-radius: 8px; padding: 20px; text-align: center;">
                <div style="font-size: 48px; font-weight: 700; color: #22c55e;">2.4</div>
                <div style="font-size: 14px; color: #64748b;">toneladas CO₂/año</div>
                <div style="margin-top: 12px; padding: 8px; background: #dcfce7; border-radius: 4px; color: #16a34a; font-size: 13px;">
                    🌱 ¡Estás por debajo de la media!
                </div>
            </div>
        </div>';
    }

    /**
     * Preview sello de conciencia
     */
    private function preview_sello_conciencia() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
            <div style="width: 100px; height: 100px; margin: 0 auto 16px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);">
                <span style="font-size: 48px;">✓</span>
            </div>
            <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #1e293b;">Sello de Conciencia</h3>
            <p style="margin: 0 0 16px; font-size: 14px; color: #64748b;">Nivel: Bronce • 250 puntos</p>
            <div style="display: flex; justify-content: center; gap: 16px;">
                <div style="text-align: center;">
                    <div style="font-size: 20px; font-weight: 600; color: #22c55e;">12</div>
                    <div style="font-size: 11px; color: #64748b;">Acciones</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 20px; font-weight: 600; color: #3b82f6;">5</div>
                    <div style="font-size: 11px; color: #64748b;">Badges</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 20px; font-weight: 600; color: #8b5cf6;">3</div>
                    <div style="font-size: 11px; color: #64748b;">Retos</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview economía genérico
     */
    private function preview_economy_generic() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 24px;">💱</span>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1e293b;">Economía Social</h3>
                    <p style="margin: 0; font-size: 14px; color: #64748b;">Widget de módulo</p>
                </div>
            </div>
            <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">Este widget mostrará contenido del módulo de economía social cuando esté activado.</p>
        </div>';
    }

    /**
     * Preview comunidad genérico
     */
    private function preview_community_generic() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 24px;">👥</span>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1e293b;">Comunidad</h3>
                    <p style="margin: 0; font-size: 14px; color: #64748b;">Widget de módulo</p>
                </div>
            </div>
            <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">Este widget mostrará contenido del módulo comunitario cuando esté activado.</p>
        </div>';
    }

    /**
     * Preview módulo genérico
     */
    private function preview_module_generic() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #6366f1, #4f46e5); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 24px;">⚙️</span>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1e293b;">Widget de Módulo</h3>
                    <p style="margin: 0; font-size: 14px; color: #64748b;">Vista previa</p>
                </div>
            </div>
            <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">Este widget mostrará contenido cuando el módulo esté activado.</p>
        </div>';
    }

    /**
     * Preview dashboard genérico
     */
    private function preview_dashboard_generic( $titulo, $icono ) {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 24px;">' . esc_html( $icono ) . '</span>
                </div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1e293b;">' . esc_html( $titulo ) . '</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 700; color: #3b82f6;">247</div>
                    <div style="font-size: 12px; color: #64748b;">Total</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f0fdf4; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 700; color: #22c55e;">+12%</div>
                    <div style="font-size: 12px; color: #64748b;">Crecimiento</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #fef3c7; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 700; color: #f59e0b;">85</div>
                    <div style="font-size: 12px; color: #64748b;">Activos</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview reciclaje
     */
    private function preview_reciclaje() {
        return '
        <div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 12px; padding: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 8px;">♻️</div>
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #1e293b;">Puntos de Reciclaje</h3>
            </div>
            <div style="background: white; border-radius: 8px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="font-size: 32px; font-weight: 700; color: #22c55e;">1,250</span>
                        <span style="font-size: 14px; color: #64748b; margin-left: 8px;">puntos</span>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 14px; color: #16a34a;">🌱 Nivel Eco-Champion</div>
                        <div style="font-size: 12px; color: #64748b;">Top 5% de la comunidad</div>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview newsletter
     */
    private function preview_newsletter() {
        return '
        <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 12px; padding: 32px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 16px;">📬</div>
            <h3 style="margin: 0 0 8px; font-size: 22px; font-weight: 600; color: #1e293b;">Suscríbete al Newsletter</h3>
            <p style="margin: 0 0 20px; font-size: 14px; color: #64748b;">Recibe las últimas novedades en tu email</p>
            <div style="display: flex; gap: 8px; max-width: 400px; margin: 0 auto;">
                <input type="text" placeholder="tu@email.com" style="flex: 1; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;" disabled>
                <button style="padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Suscribirse</button>
            </div>
        </div>';
    }

    /**
     * Preview banner publicitario
     */
    private function preview_ad_banner() {
        return '
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; padding: 24px; text-align: center; border: 2px dashed #f59e0b;">
            <div style="font-size: 36px; margin-bottom: 12px;">📢</div>
            <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 600; color: #92400e;">Espacio Publicitario</h3>
            <p style="margin: 0; font-size: 13px; color: #a16207;">728 x 90 px</p>
        </div>';
    }

    /**
     * Preview podcast
     */
    private function preview_podcast() {
        return '
        <div style="background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); border-radius: 12px; padding: 24px; color: white;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 32px;">🎙️</div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 600;">Podcast Comunitario</h3>
                    <p style="margin: 0 0 8px; font-size: 14px; opacity: 0.8;">Episodio 24: Sostenibilidad local</p>
                    <p style="margin: 0; font-size: 12px; opacity: 0.6;">45:32 • 12 feb 2024</p>
                </div>
                <button style="width: 56px; height: 56px; background: white; border: none; border-radius: 50%; cursor: pointer; font-size: 24px; color: #7c3aed;">▶</button>
            </div>
        </div>';
    }

    /**
     * Preview feed social
     */
    private function preview_social_feed() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📱 Actividad Reciente</h3>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; gap: 12px;">
                    <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #ec4899, #f472b6); border-radius: 50%;"></div>
                    <div style="flex: 1;">
                        <p style="margin: 0 0 4px; font-size: 14px;"><strong>María G.</strong> compartió una foto</p>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">Hace 2 horas • 💬 12 • ❤️ 45</p>
                    </div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #3b82f6, #60a5fa); border-radius: 50%;"></div>
                    <div style="flex: 1;">
                        <p style="margin: 0 0 4px; font-size: 14px;"><strong>Carlos R.</strong> publicó en el foro</p>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">Hace 5 horas • 💬 8 • ❤️ 23</p>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview biblioteca
     */
    private function preview_biblioteca() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📚 Catálogo de Libros</h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                <div style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); height: 120px; border-radius: 4px; box-shadow: 2px 2px 8px rgba(0,0,0,0.1);"></div>
                <div style="background: linear-gradient(135deg, #fce7f3, #fbcfe8); height: 120px; border-radius: 4px; box-shadow: 2px 2px 8px rgba(0,0,0,0.1);"></div>
                <div style="background: linear-gradient(135deg, #dcfce7, #bbf7d0); height: 120px; border-radius: 4px; box-shadow: 2px 2px 8px rgba(0,0,0,0.1);"></div>
                <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); height: 120px; border-radius: 4px; box-shadow: 2px 2px 8px rgba(0,0,0,0.1);"></div>
            </div>
            <p style="margin: 16px 0 0; font-size: 13px; color: #64748b; text-align: center;">124 libros disponibles</p>
        </div>';
    }

    /**
     * Preview carpooling
     */
    private function preview_carpooling() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">🚗 Viajes Compartidos</h3>
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span style="font-size: 20px;">📍</span>
                            <strong>Madrid</strong>
                            <span style="color: #64748b;">→</span>
                            <strong>Barcelona</strong>
                        </div>
                        <p style="margin: 0; font-size: 13px; color: #64748b;">Mañana, 8:00 AM • 3 plazas libres</p>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 20px; font-weight: 700; color: #22c55e;">15€</div>
                        <div style="font-size: 11px; color: #64748b;">por plaza</div>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview espacios comunes
     */
    private function preview_espacios() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">🏛️ Espacios Disponibles</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="height: 80px; background: linear-gradient(135deg, #3b82f6, #1d4ed8);"></div>
                    <div style="padding: 12px;">
                        <h4 style="margin: 0 0 4px; font-size: 14px;">Sala de Reuniones A</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">👥 12 personas • WiFi</p>
                    </div>
                </div>
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="height: 80px; background: linear-gradient(135deg, #22c55e, #16a34a);"></div>
                    <div style="padding: 12px;">
                        <h4 style="margin: 0 0 4px; font-size: 14px;">Coworking</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">👥 20 puestos • Proyector</p>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview chat grupos
     */
    private function preview_chat_grupos() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">💬 Grupos de Chat</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🌱</div>
                    <div style="flex: 1;">
                        <strong>Huertos Urbanos</strong><br>
                        <span style="font-size: 12px; color: #64748b;">45 miembros • 3 mensajes nuevos</span>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🚴</div>
                    <div style="flex: 1;">
                        <strong>Movilidad Sostenible</strong><br>
                        <span style="font-size: 12px; color: #64748b;">28 miembros • Activo ahora</span>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview chat inbox
     */
    private function preview_chat_inbox() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📥 Mensajes</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #eff6ff; border-radius: 8px;">
                    <div style="width: 44px; height: 44px; background: #3b82f6; border-radius: 50%;"></div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between;">
                            <strong>Ana López</strong>
                            <span style="font-size: 11px; color: #64748b;">10:30</span>
                        </div>
                        <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">¡Hola! ¿Quedamos mañana para...</p>
                    </div>
                    <span style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%;"></span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 44px; height: 44px; background: #64748b; border-radius: 50%;"></div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between;">
                            <strong>Pedro García</strong>
                            <span style="font-size: 11px; color: #64748b;">Ayer</span>
                        </div>
                        <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">Gracias por la ayuda con el taller</p>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview círculos de cuidados
     */
    private function preview_cuidados() {
        return '
        <div style="background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%); border-radius: 12px; padding: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 8px;">❤️</div>
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #1e293b;">Círculos de Cuidados</h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">Apoyo mutuo en la comunidad</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #ec4899;">12</div>
                    <div style="font-size: 12px; color: #64748b;">Personas ayudadas</div>
                </div>
                <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #8b5cf6;">5</div>
                    <div style="font-size: 12px; color: #64748b;">Círculos activos</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview comunidades
     */
    private function preview_comunidades() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">🌐 Comunidades</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div style="padding: 16px; background: #f0fdf4; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 8px;">🌱</div>
                    <h4 style="margin: 0 0 4px; font-size: 14px; font-weight: 600;">Ecología Local</h4>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">234 miembros</p>
                </div>
                <div style="padding: 16px; background: #eff6ff; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 8px;">🎨</div>
                    <h4 style="margin: 0 0 4px; font-size: 14px; font-weight: 600;">Arte y Cultura</h4>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">156 miembros</p>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview economía de suficiencia
     */
    private function preview_suficiencia() {
        return '
        <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 12px; padding: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 8px;">⚖️</div>
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #1e293b;">Economía de Suficiencia</h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">Vive con lo necesario, comparte el excedente</p>
            </div>
            <div style="background: white; border-radius: 8px; padding: 16px;">
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="font-size: 13px;">Tu nivel de suficiencia</span>
                        <span style="font-size: 13px; font-weight: 600; color: #22c55e;">72%</span>
                    </div>
                    <div style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                        <div style="width: 72%; height: 100%; background: linear-gradient(90deg, #22c55e, #16a34a);"></div>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview trabajo digno
     */
    private function preview_trabajo() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">💼 Ofertas de Trabajo</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #3b82f6;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4 style="margin: 0 0 4px; font-size: 15px; font-weight: 600;">Desarrollador Web</h4>
                            <p style="margin: 0; font-size: 13px; color: #64748b;">Cooperativa TechLocal • Remoto</p>
                        </div>
                        <span style="background: #dbeafe; color: #1d4ed8; padding: 4px 8px; border-radius: 4px; font-size: 11px;">NUEVO</span>
                    </div>
                </div>
                <div style="padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #22c55e;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4 style="margin: 0 0 4px; font-size: 15px; font-weight: 600;">Agricultor/a Ecológico</h4>
                            <p style="margin: 0; font-size: 13px; color: #64748b;">Huerta Comunitaria • Presencial</p>
                        </div>
                        <span style="background: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 4px; font-size: 11px;">URGENTE</span>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview formulario marketplace
     */
    private function preview_marketplace_form() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📦 Publicar Producto</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <input type="text" placeholder="Nombre del producto" style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;" disabled>
                <textarea placeholder="Descripción..." style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; height: 80px; resize: none;" disabled></textarea>
                <div style="display: flex; gap: 12px;">
                    <input type="text" placeholder="Precio" style="flex: 1; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;" disabled>
                    <select style="flex: 1; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;" disabled>
                        <option>Categoría</option>
                    </select>
                </div>
                <button style="padding: 12px; background: #22c55e; color: white; border: none; border-radius: 8px; font-weight: 600;">Publicar</button>
            </div>
        </div>';
    }

    /**
     * Preview trading
     */
    private function preview_trading() {
        return '
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 12px; padding: 24px; color: white;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 18px; font-weight: 600;">📈 Trading Dashboard</h3>
                <span style="background: #22c55e; padding: 4px 8px; border-radius: 4px; font-size: 12px;">En vivo</span>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                <div style="text-align: center; padding: 16px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 700; color: #22c55e;">+5.2%</div>
                    <div style="font-size: 12px; opacity: 0.7;">Hoy</div>
                </div>
                <div style="text-align: center; padding: 16px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 700;">$1,234</div>
                    <div style="font-size: 12px; opacity: 0.7;">Balance</div>
                </div>
                <div style="text-align: center; padding: 16px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 700; color: #f59e0b;">12</div>
                    <div style="font-size: 12px; opacity: 0.7;">Operaciones</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview widget trading
     */
    private function preview_trading_widget() {
        return '
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: #f7931a; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">₿</div>
                    <div>
                        <div style="font-size: 16px; font-weight: 600;">Bitcoin</div>
                        <div style="font-size: 12px; color: #64748b;">BTC/USD</div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 18px; font-weight: 700;">$43,250</div>
                    <div style="font-size: 13px; color: #22c55e;">+2.4%</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview empresarial
     */
    private function preview_empresarial() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">🏢 Servicios Empresariales</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                <div style="padding: 20px; background: #f8fafc; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 8px;">📊</div>
                    <h4 style="margin: 0 0 4px; font-size: 14px; font-weight: 600;">Consultoría</h4>
                </div>
                <div style="padding: 20px; background: #f8fafc; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 8px;">🎯</div>
                    <h4 style="margin: 0 0 4px; font-size: 14px; font-weight: 600;">Marketing</h4>
                </div>
                <div style="padding: 20px; background: #f8fafc; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 8px;">💻</div>
                    <h4 style="margin: 0 0 4px; font-size: 14px; font-weight: 600;">Desarrollo</h4>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview equipo
     */
    private function preview_equipo() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">👥 Nuestro Equipo</h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                <div style="text-align: center;">
                    <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 50%; margin: 0 auto 8px;"></div>
                    <h4 style="margin: 0; font-size: 13px; font-weight: 600;">Ana García</h4>
                    <p style="margin: 4px 0 0; font-size: 11px; color: #64748b;">CEO</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 50%; margin: 0 auto 8px;"></div>
                    <h4 style="margin: 0; font-size: 13px; font-weight: 600;">Luis Martín</h4>
                    <p style="margin: 4px 0 0; font-size: 11px; color: #64748b;">CTO</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 50%; margin: 0 auto 8px;"></div>
                    <h4 style="margin: 0; font-size: 13px; font-weight: 600;">Marta López</h4>
                    <p style="margin: 4px 0 0; font-size: 11px; color: #64748b;">Design</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #ec4899, #be185d); border-radius: 50%; margin: 0 auto 8px;"></div>
                    <h4 style="margin: 0; font-size: 13px; font-weight: 600;">Carlos Ruiz</h4>
                    <p style="margin: 4px 0 0; font-size: 11px; color: #64748b;">Marketing</p>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview trámites
     */
    private function preview_tramites() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📋 Catálogo de Trámites</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 44px; height: 44px; background: #3b82f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📄</div>
                    <div style="flex: 1;">
                        <strong>Certificado de Empadronamiento</strong><br>
                        <span style="font-size: 12px; color: #64748b;">Online • 24-48h</span>
                    </div>
                    <span style="color: #22c55e; font-size: 14px;">→</span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 44px; height: 44px; background: #22c55e; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🏠</div>
                    <div style="flex: 1;">
                        <strong>Licencia de Obras</strong><br>
                        <span style="font-size: 12px; color: #64748b;">Presencial • 15 días</span>
                    </div>
                    <span style="color: #22c55e; font-size: 14px;">→</span>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview expedientes
     */
    private function preview_expedientes() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📁 Mis Expedientes</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="padding: 12px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #22c55e;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong>EXP-2024-001</strong>
                        <span style="background: #dcfce7; color: #16a34a; padding: 2px 8px; border-radius: 4px; font-size: 11px;">RESUELTO</span>
                    </div>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">Certificado de Empadronamiento</p>
                </div>
                <div style="padding: 12px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong>EXP-2024-002</strong>
                        <span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-size: 11px;">EN PROCESO</span>
                    </div>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">Licencia de Obras Menores</p>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview facturas
     */
    private function preview_facturas() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">📑 Mis Facturas</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <th style="text-align: left; padding: 8px 0; color: #64748b; font-weight: 500;">Factura</th>
                        <th style="text-align: left; padding: 8px 0; color: #64748b; font-weight: 500;">Fecha</th>
                        <th style="text-align: right; padding: 8px 0; color: #64748b; font-weight: 500;">Importe</th>
                        <th style="text-align: right; padding: 8px 0; color: #64748b; font-weight: 500;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0;">FAC-001</td>
                        <td style="padding: 12px 0;">15/02/2024</td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 600;">125,00€</td>
                        <td style="padding: 12px 0; text-align: right;"><span style="background: #dcfce7; color: #16a34a; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Pagada</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0;">FAC-002</td>
                        <td style="padding: 12px 0;">01/03/2024</td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 600;">85,50€</td>
                        <td style="padding: 12px 0; text-align: right;"><span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Pendiente</span></td>
                    </tr>
                </tbody>
            </table>
        </div>';
    }

    /**
     * Preview socios
     */
    private function preview_socios() {
        return '
        <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 12px; padding: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 8px;">🎫</div>
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #1e293b;">Cuota de Socio</h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">Membresía anual 2024</p>
            </div>
            <div style="background: white; border-radius: 8px; padding: 20px; text-align: center;">
                <div style="font-size: 36px; font-weight: 700; color: #3b82f6; margin-bottom: 8px;">50€<span style="font-size: 14px; font-weight: 400; color: #64748b;">/año</span></div>
                <button style="width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Pagar Cuota</button>
            </div>
        </div>';
    }

    /**
     * Preview perfil
     */
    private function preview_perfil() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 50%;"></div>
                <div>
                    <h3 style="margin: 0 0 4px; font-size: 20px; font-weight: 600; color: #1e293b;">María García</h3>
                    <p style="margin: 0; font-size: 14px; color: #64748b;">Socio desde 2022 • #12345</p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 20px; font-weight: 700; color: #3b82f6;">24</div>
                    <div style="font-size: 11px; color: #64748b;">Actividades</div>
                </div>
                <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 20px; font-weight: 700; color: #22c55e;">12</div>
                    <div style="font-size: 11px; color: #64748b;">Eventos</div>
                </div>
                <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 20px; font-weight: 700; color: #f59e0b;">350</div>
                    <div style="font-size: 11px; color: #64748b;">Puntos</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview propuestas
     */
    private function preview_propuestas() {
        return '
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1e293b;">💡 Propuestas Activas</h3>
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                    <h4 style="margin: 0; font-size: 15px; font-weight: 600;">Nuevo parque infantil en zona norte</h4>
                    <span style="background: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 4px; font-size: 11px;">EN VOTACIÓN</span>
                </div>
                <p style="margin: 0 0 12px; font-size: 13px; color: #64748b;">Crear un espacio de juegos para niños de 3-12 años...</p>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; color: #64748b;">👍 127 apoyos</span>
                    <button style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 13px; cursor: pointer;">Apoyar</button>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview votación
     */
    private function preview_votacion() {
        return '
        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 12px; padding: 24px; color: white;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 8px;">🗳️</div>
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600;">Votación en Curso</h3>
                <p style="margin: 0; font-size: 14px; opacity: 0.8;">¿Qué proyecto priorizamos?</p>
            </div>
            <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 16px;">
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Opción A: Parque</span>
                        <span>65%</span>
                    </div>
                    <div style="height: 8px; background: rgba(255,255,255,0.2); border-radius: 4px; overflow: hidden;">
                        <div style="width: 65%; height: 100%; background: white;"></div>
                    </div>
                </div>
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Opción B: Carril bici</span>
                        <span>35%</span>
                    </div>
                    <div style="height: 8px; background: rgba(255,255,255,0.2); border-radius: 4px; overflow: hidden;">
                        <div style="width: 35%; height: 100%; background: white;"></div>
                    </div>
                </div>
            </div>
            <p style="margin: 16px 0 0; font-size: 13px; text-align: center; opacity: 0.7;">⏱️ Finaliza en 3 días</p>
        </div>';
    }

    /**
     * Preview justicia restaurativa
     */
    private function preview_justicia() {
        return '
        <div style="background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%); border-radius: 12px; padding: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 8px;">⚖️</div>
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #1e293b;">Justicia Restaurativa</h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">Mediación y resolución de conflictos</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #ec4899;">15</div>
                    <div style="font-size: 12px; color: #64748b;">Mediaciones exitosas</div>
                </div>
                <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #8b5cf6;">92%</div>
                    <div style="font-size: 12px; color: #64748b;">Tasa de resolución</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Preview saberes ancestrales
     */
    private function preview_saberes() {
        return '
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; padding: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 8px;">📜</div>
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #1e293b;">Saberes Ancestrales</h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">Conocimiento tradicional de la comunidad</p>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="background: white; padding: 12px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 24px;">🌿</span>
                    <div>
                        <strong>Plantas Medicinales</strong><br>
                        <span style="font-size: 12px; color: #64748b;">24 recetas tradicionales</span>
                    </div>
                </div>
                <div style="background: white; padding: 12px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 24px;">🍳</span>
                    <div>
                        <strong>Cocina Tradicional</strong><br>
                        <span style="font-size: 12px; color: #64748b;">56 recetas locales</span>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Placeholder básico para bloques sin preview
     */
    private function render_basic_placeholder( $bloque, $elemento ) {
        $nombre_modulo = isset( $bloque['module'] ) ? $bloque['module'] : 'desconocido';
        $nombre_bloque = isset( $bloque['name'] ) ? $bloque['name'] : $elemento['type'];
        $icono = isset( $bloque['icon'] ) ? $bloque['icon'] : '';

        return '
        <div class="vbp-module-placeholder" data-module="' . esc_attr( $nombre_modulo ) . '">
            <div class="vbp-module-placeholder-inner">
                ' . ( ! empty( $icono ) ? '<div class="vbp-module-placeholder-icon">' . $icono . '</div>' : '' ) . '
                <div class="vbp-module-placeholder-info">
                    <span class="vbp-module-placeholder-name">' . esc_html( $nombre_bloque ) . '</span>
                    <span class="vbp-module-placeholder-module">' . sprintf( esc_html__( 'Módulo "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN ), esc_html( $nombre_modulo ) ) . '</span>
                </div>
                <span class="vbp-module-placeholder-badge">' . esc_html__( 'Preview', FLAVOR_PLATFORM_TEXT_DOMAIN ) . '</span>
            </div>
        </div>';
    }

    /**
     * Construye un shortcode a partir de atributos
     *
     * @param string $tag       Tag del shortcode.
     * @param array  $atributos Atributos.
     * @return string
     */
    private function construir_shortcode( $tag, $atributos = array() ) {
        $shortcode = '[' . $tag;

        foreach ( $atributos as $key => $value ) {
            if ( is_string( $value ) || is_numeric( $value ) ) {
                $shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
            }
        }

        $shortcode .= ']';

        return $shortcode;
    }

    /**
     * Renderizado genérico
     *
     * @param array $elemento Datos del elemento.
     * @return string
     */
    private function render_generico( $elemento ) {
        $data  = isset( $elemento['data'] ) ? $elemento['data'] : array();
        $name  = isset( $elemento['name'] ) ? $elemento['name'] : $elemento['type'];

        return sprintf(
            '<div class="vbp-block vbp-block-%s"><div class="vbp-block-content">%s</div></div>',
            esc_attr( $elemento['type'] ),
            esc_html( $name )
        );
    }
}

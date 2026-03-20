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
                'label' => __( '🎨 Estilo Visual', 'flavor-chat-ia' ),
            ),
            'esquema_color' => array(
                'type'    => 'select',
                'label'   => __( 'Esquema de color', 'flavor-chat-ia' ),
                'options' => array(
                    'default'   => __( 'Por defecto', 'flavor-chat-ia' ),
                    'primary'   => __( 'Primario (azul)', 'flavor-chat-ia' ),
                    'success'   => __( 'Éxito (verde)', 'flavor-chat-ia' ),
                    'warning'   => __( 'Advertencia (amarillo)', 'flavor-chat-ia' ),
                    'danger'    => __( 'Peligro (rojo)', 'flavor-chat-ia' ),
                    'purple'    => __( 'Púrpura', 'flavor-chat-ia' ),
                    'pink'      => __( 'Rosa', 'flavor-chat-ia' ),
                    'dark'      => __( 'Oscuro', 'flavor-chat-ia' ),
                    'custom'    => __( 'Personalizado', 'flavor-chat-ia' ),
                ),
                'default' => 'default',
            ),
            'color_primario' => array(
                'type'      => 'color',
                'label'     => __( 'Color primario', 'flavor-chat-ia' ),
                'default'   => '#3b82f6',
                'condition' => array( 'esquema_color' => 'custom' ),
            ),
            'color_secundario' => array(
                'type'      => 'color',
                'label'     => __( 'Color secundario', 'flavor-chat-ia' ),
                'default'   => '#64748b',
                'condition' => array( 'esquema_color' => 'custom' ),
            ),
            'radio_bordes' => array(
                'type'    => 'select',
                'label'   => __( 'Bordes redondeados', 'flavor-chat-ia' ),
                'options' => array(
                    'none'   => __( 'Sin redondear', 'flavor-chat-ia' ),
                    'sm'     => __( 'Pequeño (4px)', 'flavor-chat-ia' ),
                    'md'     => __( 'Mediano (8px)', 'flavor-chat-ia' ),
                    'lg'     => __( 'Grande (12px)', 'flavor-chat-ia' ),
                    'xl'     => __( 'Extra grande (16px)', 'flavor-chat-ia' ),
                    'full'   => __( 'Completo (circular)', 'flavor-chat-ia' ),
                ),
                'default' => 'lg',
            ),
            'sombra' => array(
                'type'    => 'select',
                'label'   => __( 'Sombra', 'flavor-chat-ia' ),
                'options' => array(
                    'none' => __( 'Sin sombra', 'flavor-chat-ia' ),
                    'sm'   => __( 'Sutil', 'flavor-chat-ia' ),
                    'md'   => __( 'Media', 'flavor-chat-ia' ),
                    'lg'   => __( 'Pronunciada', 'flavor-chat-ia' ),
                    'xl'   => __( 'Muy pronunciada', 'flavor-chat-ia' ),
                ),
                'default' => 'md',
            ),
            'animacion_entrada' => array(
                'type'    => 'select',
                'label'   => __( 'Animación de entrada', 'flavor-chat-ia' ),
                'options' => array(
                    'none'      => __( 'Sin animación', 'flavor-chat-ia' ),
                    'fade'      => __( 'Aparecer', 'flavor-chat-ia' ),
                    'slide-up'  => __( 'Deslizar arriba', 'flavor-chat-ia' ),
                    'slide-down'=> __( 'Deslizar abajo', 'flavor-chat-ia' ),
                    'zoom'      => __( 'Zoom', 'flavor-chat-ia' ),
                    'bounce'    => __( 'Rebote', 'flavor-chat-ia' ),
                ),
                'default' => 'fade',
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
                'label' => __( '🎨 Colores de Texto', 'flavor-chat-ia' ),
            ),
            'titulo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color del título', 'flavor-chat-ia' ),
                'default' => '#1f2937',
            ),
            'subtitulo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color del subtítulo', 'flavor-chat-ia' ),
                'default' => '#6b7280',
            ),
            'texto_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color del texto', 'flavor-chat-ia' ),
                'default' => '#374151',
            ),

            // Colores de botón
            '_separator_colores_boton' => array(
                'type'  => 'separator',
                'label' => __( '🔘 Colores de Botón', 'flavor-chat-ia' ),
            ),
            'boton_color_fondo' => array(
                'type'    => 'color',
                'label'   => __( 'Fondo del botón', 'flavor-chat-ia' ),
                'default' => '#3b82f6',
            ),
            'boton_color_texto' => array(
                'type'    => 'color',
                'label'   => __( 'Texto del botón', 'flavor-chat-ia' ),
                'default' => '#ffffff',
            ),
            'boton_color_hover' => array(
                'type'    => 'color',
                'label'   => __( 'Fondo hover', 'flavor-chat-ia' ),
                'default' => '#2563eb',
            ),

            // Fondo de sección
            '_separator_fondo_seccion' => array(
                'type'  => 'separator',
                'label' => __( '🖼️ Fondo de Sección', 'flavor-chat-ia' ),
            ),
            'seccion_fondo_tipo' => array(
                'type'    => 'select',
                'label'   => __( 'Tipo de fondo', 'flavor-chat-ia' ),
                'options' => array(
                    'color'    => __( 'Color sólido', 'flavor-chat-ia' ),
                    'gradient' => __( 'Gradiente', 'flavor-chat-ia' ),
                    'image'    => __( 'Imagen', 'flavor-chat-ia' ),
                ),
                'default' => 'color',
            ),
            'seccion_fondo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color de fondo', 'flavor-chat-ia' ),
                'default' => '#ffffff',
            ),
            'seccion_fondo_gradiente_inicio' => array(
                'type'    => 'color',
                'label'   => __( 'Gradiente inicio', 'flavor-chat-ia' ),
                'default' => '#3b82f6',
            ),
            'seccion_fondo_gradiente_fin' => array(
                'type'    => 'color',
                'label'   => __( 'Gradiente fin', 'flavor-chat-ia' ),
                'default' => '#8b5cf6',
            ),
            'seccion_fondo_imagen' => array(
                'type'  => 'image',
                'label' => __( 'Imagen de fondo', 'flavor-chat-ia' ),
            ),
            'seccion_overlay_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color overlay', 'flavor-chat-ia' ),
                'default' => 'rgba(0,0,0,0.5)',
            ),

            // Tarjetas/Cards
            '_separator_tarjetas' => array(
                'type'  => 'separator',
                'label' => __( '🃏 Colores de Tarjetas', 'flavor-chat-ia' ),
            ),
            'card_fondo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Fondo de tarjeta', 'flavor-chat-ia' ),
                'default' => '#ffffff',
            ),
            'card_borde_color' => array(
                'type'    => 'color',
                'label'   => __( 'Borde de tarjeta', 'flavor-chat-ia' ),
                'default' => '#e5e7eb',
            ),
            'card_titulo_color' => array(
                'type'    => 'color',
                'label'   => __( 'Título de tarjeta', 'flavor-chat-ia' ),
                'default' => '#1f2937',
            ),
            'card_texto_color' => array(
                'type'    => 'color',
                'label'   => __( 'Texto de tarjeta', 'flavor-chat-ia' ),
                'default' => '#6b7280',
            ),
            'card_icono_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color de iconos', 'flavor-chat-ia' ),
                'default' => '#3b82f6',
            ),

            // Acentos
            '_separator_acentos' => array(
                'type'  => 'separator',
                'label' => __( '✨ Colores de Acento', 'flavor-chat-ia' ),
            ),
            'acento_color' => array(
                'type'    => 'color',
                'label'   => __( 'Color de acento', 'flavor-chat-ia' ),
                'default' => '#3b82f6',
            ),
            'destacado_fondo' => array(
                'type'    => 'color',
                'label'   => __( 'Fondo destacado', 'flavor-chat-ia' ),
                'default' => '#eff6ff',
            ),
            'destacado_borde' => array(
                'type'    => 'color',
                'label'   => __( 'Borde destacado', 'flavor-chat-ia' ),
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
                'label' => __( '📐 Disposición', 'flavor-chat-ia' ),
            ),
            'columnas' => array(
                'type'    => 'select',
                'label'   => __( 'Columnas', 'flavor-chat-ia' ),
                'options' => array(
                    '1' => '1 columna',
                    '2' => '2 columnas',
                    '3' => '3 columnas',
                    '4' => '4 columnas',
                    'auto' => __( 'Automático', 'flavor-chat-ia' ),
                ),
                'default' => '3',
            ),
            'columnas_tablet' => array(
                'type'    => 'select',
                'label'   => __( 'Columnas (tablet)', 'flavor-chat-ia' ),
                'options' => array(
                    '1' => '1 columna',
                    '2' => '2 columnas',
                    '3' => '3 columnas',
                ),
                'default' => '2',
            ),
            'columnas_movil' => array(
                'type'    => 'select',
                'label'   => __( 'Columnas (móvil)', 'flavor-chat-ia' ),
                'options' => array(
                    '1' => '1 columna',
                    '2' => '2 columnas',
                ),
                'default' => '1',
            ),
            'espacio_items' => array(
                'type'    => 'select',
                'label'   => __( 'Espacio entre items', 'flavor-chat-ia' ),
                'options' => array(
                    'xs'  => __( 'Muy pequeño (8px)', 'flavor-chat-ia' ),
                    'sm'  => __( 'Pequeño (12px)', 'flavor-chat-ia' ),
                    'md'  => __( 'Mediano (16px)', 'flavor-chat-ia' ),
                    'lg'  => __( 'Grande (24px)', 'flavor-chat-ia' ),
                    'xl'  => __( 'Extra grande (32px)', 'flavor-chat-ia' ),
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
                'label' => __( '🃏 Estilo de Tarjeta', 'flavor-chat-ia' ),
            ),
            'estilo_tarjeta' => array(
                'type'    => 'select',
                'label'   => __( 'Estilo de tarjeta', 'flavor-chat-ia' ),
                'options' => array(
                    'default'    => __( 'Por defecto', 'flavor-chat-ia' ),
                    'elevated'   => __( 'Elevada', 'flavor-chat-ia' ),
                    'outlined'   => __( 'Con borde', 'flavor-chat-ia' ),
                    'filled'     => __( 'Rellena', 'flavor-chat-ia' ),
                    'glass'      => __( 'Cristal (glassmorphism)', 'flavor-chat-ia' ),
                    'gradient'   => __( 'Gradiente', 'flavor-chat-ia' ),
                    'minimal'    => __( 'Minimalista', 'flavor-chat-ia' ),
                ),
                'default' => 'elevated',
            ),
            'mostrar_imagen' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar imagen destacada', 'flavor-chat-ia' ),
                'default' => true,
            ),
            'ratio_imagen' => array(
                'type'      => 'select',
                'label'     => __( 'Proporción imagen', 'flavor-chat-ia' ),
                'options'   => array(
                    '1:1'   => __( 'Cuadrada (1:1)', 'flavor-chat-ia' ),
                    '4:3'   => __( 'Estándar (4:3)', 'flavor-chat-ia' ),
                    '16:9'  => __( 'Panorámica (16:9)', 'flavor-chat-ia' ),
                    '3:2'   => __( 'Fotografía (3:2)', 'flavor-chat-ia' ),
                    '21:9'  => __( 'Cine (21:9)', 'flavor-chat-ia' ),
                ),
                'default'   => '16:9',
                'condition' => array( 'mostrar_imagen' => true ),
            ),
            'hover_effect' => array(
                'type'    => 'select',
                'label'   => __( 'Efecto hover', 'flavor-chat-ia' ),
                'options' => array(
                    'none'      => __( 'Ninguno', 'flavor-chat-ia' ),
                    'lift'      => __( 'Elevar', 'flavor-chat-ia' ),
                    'grow'      => __( 'Crecer', 'flavor-chat-ia' ),
                    'glow'      => __( 'Resplandor', 'flavor-chat-ia' ),
                    'border'    => __( 'Borde color', 'flavor-chat-ia' ),
                    'overlay'   => __( 'Superposición', 'flavor-chat-ia' ),
                ),
                'default' => 'lift',
            ),
            'mostrar_badges' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar badges/etiquetas', 'flavor-chat-ia' ),
                'default' => true,
            ),
            'mostrar_meta' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar meta info (fecha, autor)', 'flavor-chat-ia' ),
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
                'label' => __( '📝 Cabecera', 'flavor-chat-ia' ),
            ),
            'mostrar_titulo' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar título', 'flavor-chat-ia' ),
                'default' => true,
            ),
            'titulo_personalizado' => array(
                'type'      => 'text',
                'label'     => __( 'Título personalizado', 'flavor-chat-ia' ),
                'default'   => '',
                'placeholder' => __( 'Dejar vacío para usar título por defecto', 'flavor-chat-ia' ),
                'condition' => array( 'mostrar_titulo' => true ),
            ),
            'mostrar_descripcion' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar descripción', 'flavor-chat-ia' ),
                'default' => false,
            ),
            'descripcion' => array(
                'type'      => 'textarea',
                'label'     => __( 'Descripción', 'flavor-chat-ia' ),
                'default'   => '',
                'rows'      => 2,
                'condition' => array( 'mostrar_descripcion' => true ),
            ),
            'mostrar_icono' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar icono', 'flavor-chat-ia' ),
                'default' => true,
            ),
            'alineacion_cabecera' => array(
                'type'    => 'select',
                'label'   => __( 'Alineación cabecera', 'flavor-chat-ia' ),
                'options' => array(
                    'left'   => __( 'Izquierda', 'flavor-chat-ia' ),
                    'center' => __( 'Centro', 'flavor-chat-ia' ),
                    'right'  => __( 'Derecha', 'flavor-chat-ia' ),
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
                'label' => __( '📄 Paginación', 'flavor-chat-ia' ),
            ),
            'paginacion' => array(
                'type'    => 'select',
                'label'   => __( 'Tipo de paginación', 'flavor-chat-ia' ),
                'options' => array(
                    'none'     => __( 'Sin paginación', 'flavor-chat-ia' ),
                    'numbers'  => __( 'Números de página', 'flavor-chat-ia' ),
                    'loadmore' => __( 'Botón cargar más', 'flavor-chat-ia' ),
                    'infinite' => __( 'Scroll infinito', 'flavor-chat-ia' ),
                ),
                'default' => 'loadmore',
            ),
            'items_pagina' => array(
                'type'      => 'number',
                'label'     => __( 'Items por página', 'flavor-chat-ia' ),
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
                'name'  => __( 'Secciones', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
                'order' => 10,
            ),
            'basic' => array(
                'id'    => 'basic',
                'name'  => __( 'Básicos', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
                'order' => 20,
            ),
            'layout' => array(
                'id'    => 'layout',
                'name'  => __( 'Layout', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
                'order' => 30,
            ),
            'forms' => array(
                'id'    => 'forms',
                'name'  => __( 'Formularios', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
                'order' => 40,
            ),
            'media' => array(
                'id'    => 'media',
                'name'  => __( 'Media', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5,3 19,12 5,21"/></svg>',
                'order' => 50,
            ),
            'modules' => array(
                'id'    => 'modules',
                'name'  => __( 'Módulos', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
                'order' => 60,
            ),
            'maps' => array(
                'id'    => 'maps',
                'name'  => __( 'Mapas', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1,6 1,22 8,18 16,22 23,18 23,2 16,6 8,2"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
                'order' => 70,
            ),
            'economy' => array(
                'id'    => 'economy',
                'name'  => __( 'Economía Social', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 100 4h4a2 2 0 110 4H8"/><path d="M12 6v2m0 8v2"/></svg>',
                'order' => 80,
            ),
            'community' => array(
                'id'    => 'community',
                'name'  => __( 'Comunidad', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
                'order' => 90,
            ),
            'dashboard' => array(
                'id'    => 'dashboard',
                'name'  => __( 'Widgets Dashboard', 'flavor-chat-ia' ),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="3" height="5" fill="currentColor" opacity="0.3"/><rect x="14" y="7" width="3" height="9" fill="currentColor" opacity="0.3"/><path d="M7 16h3M14 16h3"/></svg>',
                'order' => 95,
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
            'name'     => __( 'Hero', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
            'variants' => array(
                'fullscreen'   => __( 'Pantalla completa', 'flavor-chat-ia' ),
                'split'        => __( 'Dividido', 'flavor-chat-ia' ),
                'centered'     => __( 'Centrado', 'flavor-chat-ia' ),
                'video'        => __( 'Con video', 'flavor-chat-ia' ),
                'slider'       => __( 'Slider', 'flavor-chat-ia' ),
                'glassmorphism'=> __( 'Glassmorphism', 'flavor-chat-ia' ),
                'gradient'     => __( 'Gradiente animado', 'flavor-chat-ia' ),
                'parallax'     => __( 'Parallax', 'flavor-chat-ia' ),
                'particles'    => __( 'Con partículas', 'flavor-chat-ia' ),
                'minimal'      => __( 'Minimalista', 'flavor-chat-ia' ),
                '3d'           => __( 'Efecto 3D', 'flavor-chat-ia' ),
                'typewriter'   => __( 'Efecto máquina de escribir', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                // Contenido
                '_separator_contenido' => array( 'type' => 'separator', 'label' => __( '📝 Contenido', 'flavor-chat-ia' ) ),
                'titulo'        => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo'     => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'boton_texto'   => array( 'type' => 'text', 'label' => __( 'Texto del botón', 'flavor-chat-ia' ) ),
                'boton_url'     => array( 'type' => 'url', 'label' => __( 'URL del botón', 'flavor-chat-ia' ) ),
                'boton_2_texto' => array( 'type' => 'text', 'label' => __( 'Segundo botón (texto)', 'flavor-chat-ia' ) ),
                'boton_2_url'   => array( 'type' => 'url', 'label' => __( 'Segundo botón (URL)', 'flavor-chat-ia' ) ),

                // Colores de texto
                '_separator_colores_texto' => array( 'type' => 'separator', 'label' => __( '🎨 Colores de Texto', 'flavor-chat-ia' ) ),
                'titulo_color'    => array( 'type' => 'color', 'label' => __( 'Color del título', 'flavor-chat-ia' ), 'default' => '#ffffff' ),
                'subtitulo_color' => array( 'type' => 'color', 'label' => __( 'Color del subtítulo', 'flavor-chat-ia' ), 'default' => '#e0e0e0' ),

                // Colores de botones
                '_separator_colores_botones' => array( 'type' => 'separator', 'label' => __( '🔘 Botón Principal', 'flavor-chat-ia' ) ),
                'boton_color_fondo' => array( 'type' => 'color', 'label' => __( 'Color de fondo', 'flavor-chat-ia' ), 'default' => '#3b82f6' ),
                'boton_color_texto' => array( 'type' => 'color', 'label' => __( 'Color del texto', 'flavor-chat-ia' ), 'default' => '#ffffff' ),
                'boton_border_radius' => array( 'type' => 'text', 'label' => __( 'Border radius', 'flavor-chat-ia' ), 'default' => '8px' ),

                '_separator_boton_secundario' => array( 'type' => 'separator', 'label' => __( '🔘 Botón Secundario', 'flavor-chat-ia' ) ),
                'boton_2_color_fondo' => array( 'type' => 'color', 'label' => __( 'Color de fondo', 'flavor-chat-ia' ), 'default' => 'transparent' ),
                'boton_2_color_texto' => array( 'type' => 'color', 'label' => __( 'Color del texto', 'flavor-chat-ia' ), 'default' => '#ffffff' ),
                'boton_2_color_borde' => array( 'type' => 'color', 'label' => __( 'Color del borde', 'flavor-chat-ia' ), 'default' => '#ffffff' ),

                // Fondo
                '_separator_fondo' => array( 'type' => 'separator', 'label' => __( '🖼️ Fondo', 'flavor-chat-ia' ) ),
                'imagen_fondo'  => array( 'type' => 'image', 'label' => __( 'Imagen de fondo', 'flavor-chat-ia' ) ),
                'video_url'     => array( 'type' => 'url', 'label' => __( 'URL del video (YouTube/Vimeo)', 'flavor-chat-ia' ) ),
                'color_fondo'   => array( 'type' => 'color', 'label' => __( 'Color de fondo', 'flavor-chat-ia' ), 'default' => '#1a1a2e' ),
                'overlay_color' => array( 'type' => 'color', 'label' => __( 'Color overlay', 'flavor-chat-ia' ) ),
                'overlay_opacity' => array( 'type' => 'range', 'label' => __( 'Opacidad overlay', 'flavor-chat-ia' ), 'min' => 0, 'max' => 100 ),

                // Layout
                '_separator_layout' => array( 'type' => 'separator', 'label' => __( '📐 Layout', 'flavor-chat-ia' ) ),
                'altura'        => array( 'type' => 'select', 'label' => __( 'Altura', 'flavor-chat-ia' ), 'options' => array( 'auto' => 'Auto', '50vh' => '50%', '75vh' => '75%', '100vh' => '100%' ) ),
                'alineacion'    => array( 'type' => 'select', 'label' => __( 'Alineación', 'flavor-chat-ia' ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha' ) ),
                'padding_vertical' => array( 'type' => 'text', 'label' => __( 'Padding vertical', 'flavor-chat-ia' ), 'default' => '80px' ),
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
            'name'     => __( 'Características', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
            'variants' => array(
                'grid'        => __( 'Cuadrícula', 'flavor-chat-ia' ),
                'list'        => __( 'Lista', 'flavor-chat-ia' ),
                'alternating' => __( 'Alternado', 'flavor-chat-ia' ),
                'cards'       => __( 'Tarjetas', 'flavor-chat-ia' ),
                'zigzag'      => __( 'Zigzag', 'flavor-chat-ia' ),
                'timeline'    => __( 'Línea de tiempo', 'flavor-chat-ia' ),
                'tabs'        => __( 'Pestañas', 'flavor-chat-ia' ),
                'accordion'   => __( 'Acordeón', 'flavor-chat-ia' ),
                'icons-only'  => __( 'Solo iconos', 'flavor-chat-ia' ),
                'bento'       => __( 'Bento Grid', 'flavor-chat-ia' ),
                'hover-cards' => __( 'Tarjetas con hover', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'    => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo' => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'columnas'  => array( 'type' => 'select', 'label' => __( 'Columnas', 'flavor-chat-ia' ), 'options' => array( '2' => '2', '3' => '3', '4' => '4' ) ),
                'items'     => array( 'type' => 'repeater', 'label' => __( 'Características', 'flavor-chat-ia' ), 'fields' => array(
                    'icono'       => array( 'type' => 'icon', 'label' => __( 'Icono', 'flavor-chat-ia' ) ),
                    'titulo'      => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ) ),
                    'descripcion' => array( 'type' => 'textarea', 'label' => __( 'Descripción', 'flavor-chat-ia' ) ),
                    'enlace'      => array( 'type' => 'url', 'label' => __( 'Enlace', 'flavor-chat-ia' ) ),
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
            'name'     => __( 'Testimonios', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
            'variants' => array(
                'carousel'    => __( 'Carrusel', 'flavor-chat-ia' ),
                'grid'        => __( 'Cuadrícula', 'flavor-chat-ia' ),
                'single'      => __( 'Único destacado', 'flavor-chat-ia' ),
                'masonry'     => __( 'Masonry', 'flavor-chat-ia' ),
                'video'       => __( 'Video testimonios', 'flavor-chat-ia' ),
                'rating'      => __( 'Con estrellas', 'flavor-chat-ia' ),
                'avatar-large'=> __( 'Avatar grande', 'flavor-chat-ia' ),
                'quote-card'  => __( 'Tarjeta con cita', 'flavor-chat-ia' ),
                'logos'       => __( 'Con logos de empresas', 'flavor-chat-ia' ),
                'twitter'     => __( 'Estilo Twitter/X', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo'    => array( 'type' => 'text', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'autoplay'     => array( 'type' => 'toggle', 'label' => __( 'Autoplay', 'flavor-chat-ia' ) ),
                'mostrar_rating' => array( 'type' => 'toggle', 'label' => __( 'Mostrar rating', 'flavor-chat-ia' ) ),
                'testimonios'  => array( 'type' => 'repeater', 'label' => __( 'Testimonios', 'flavor-chat-ia' ), 'fields' => array(
                    'texto'    => array( 'type' => 'textarea', 'label' => __( 'Testimonio', 'flavor-chat-ia' ) ),
                    'nombre'   => array( 'type' => 'text', 'label' => __( 'Nombre', 'flavor-chat-ia' ) ),
                    'cargo'    => array( 'type' => 'text', 'label' => __( 'Cargo', 'flavor-chat-ia' ) ),
                    'empresa'  => array( 'type' => 'text', 'label' => __( 'Empresa', 'flavor-chat-ia' ) ),
                    'avatar'   => array( 'type' => 'image', 'label' => __( 'Foto', 'flavor-chat-ia' ) ),
                    'logo'     => array( 'type' => 'image', 'label' => __( 'Logo empresa', 'flavor-chat-ia' ) ),
                    'rating'   => array( 'type' => 'select', 'label' => __( 'Rating', 'flavor-chat-ia' ), 'options' => array( '5' => '5 estrellas', '4' => '4 estrellas', '3' => '3 estrellas' ) ),
                    'video_url'=> array( 'type' => 'url', 'label' => __( 'Video (opcional)', 'flavor-chat-ia' ) ),
                ) ),
            ),
        ) );

        // Precios
        $this->registrar_bloque( array(
            'id'       => 'pricing',
            'name'     => __( 'Precios', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 100 4h4a2 2 0 110 4H8"/><path d="M12 6v2m0 8v2"/></svg>',
            'variants' => array(
                'cards'       => __( 'Tarjetas', 'flavor-chat-ia' ),
                'table'       => __( 'Tabla comparativa', 'flavor-chat-ia' ),
                'toggle'      => __( 'Toggle mensual/anual', 'flavor-chat-ia' ),
                'comparison'  => __( 'Comparación lado a lado', 'flavor-chat-ia' ),
                'slider'      => __( 'Slider de planes', 'flavor-chat-ia' ),
                'minimal'     => __( 'Minimalista', 'flavor-chat-ia' ),
                'gradient'    => __( 'Gradiente destacado', 'flavor-chat-ia' ),
                'enterprise'  => __( 'Enterprise con contacto', 'flavor-chat-ia' ),
                'freemium'    => __( 'Freemium destacado', 'flavor-chat-ia' ),
                'horizontal'  => __( 'Horizontal', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'      => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo'   => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'moneda'      => array( 'type' => 'text', 'label' => __( 'Moneda', 'flavor-chat-ia' ), 'default' => '€' ),
                'periodo'     => array( 'type' => 'select', 'label' => __( 'Período', 'flavor-chat-ia' ), 'options' => array( 'mes' => 'Mes', 'año' => 'Año', 'único' => 'Pago único' ) ),
                'mostrar_toggle' => array( 'type' => 'toggle', 'label' => __( 'Mostrar toggle mensual/anual', 'flavor-chat-ia' ) ),
                'descuento_anual' => array( 'type' => 'number', 'label' => __( 'Descuento anual (%)', 'flavor-chat-ia' ), 'default' => 20 ),
                'planes'      => array( 'type' => 'repeater', 'label' => __( 'Planes', 'flavor-chat-ia' ), 'fields' => array(
                    'nombre'        => array( 'type' => 'text', 'label' => __( 'Nombre del plan', 'flavor-chat-ia' ) ),
                    'descripcion'   => array( 'type' => 'textarea', 'label' => __( 'Descripción', 'flavor-chat-ia' ) ),
                    'precio'        => array( 'type' => 'number', 'label' => __( 'Precio', 'flavor-chat-ia' ) ),
                    'precio_anual'  => array( 'type' => 'number', 'label' => __( 'Precio anual', 'flavor-chat-ia' ) ),
                    'destacado'     => array( 'type' => 'toggle', 'label' => __( 'Plan destacado', 'flavor-chat-ia' ) ),
                    'etiqueta'      => array( 'type' => 'text', 'label' => __( 'Etiqueta (ej: Popular)', 'flavor-chat-ia' ) ),
                    'boton_texto'   => array( 'type' => 'text', 'label' => __( 'Texto del botón', 'flavor-chat-ia' ) ),
                    'boton_url'     => array( 'type' => 'url', 'label' => __( 'URL del botón', 'flavor-chat-ia' ) ),
                    'caracteristicas' => array( 'type' => 'textarea', 'label' => __( 'Características (una por línea)', 'flavor-chat-ia' ) ),
                    'icono'         => array( 'type' => 'icon', 'label' => __( 'Icono', 'flavor-chat-ia' ) ),
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
            'name'     => __( 'Llamada a acción', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>',
            'variants' => array(
                'simple'      => __( 'Simple', 'flavor-chat-ia' ),
                'banner'      => __( 'Banner', 'flavor-chat-ia' ),
                'split'       => __( 'Dividido', 'flavor-chat-ia' ),
                'gradient'    => __( 'Gradiente', 'flavor-chat-ia' ),
                'newsletter'  => __( 'Newsletter', 'flavor-chat-ia' ),
                'countdown'   => __( 'Con cuenta atrás', 'flavor-chat-ia' ),
                'floating'    => __( 'Flotante', 'flavor-chat-ia' ),
                'video'       => __( 'Con video', 'flavor-chat-ia' ),
                'testimonial' => __( 'Con testimonio', 'flavor-chat-ia' ),
                'stats'       => __( 'Con estadísticas', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'descripcion'  => array( 'type' => 'textarea', 'label' => __( 'Descripción', 'flavor-chat-ia' ), 'ai' => true ),
                'boton_texto'  => array( 'type' => 'text', 'label' => __( 'Texto del botón', 'flavor-chat-ia' ) ),
                'boton_url'    => array( 'type' => 'url', 'label' => __( 'URL del botón', 'flavor-chat-ia' ) ),
                'boton_estilo' => array( 'type' => 'select', 'label' => __( 'Estilo del botón', 'flavor-chat-ia' ), 'options' => array( 'primary' => 'Primario', 'secondary' => 'Secundario', 'white' => 'Blanco', 'outline' => 'Contorno' ) ),
                'boton_2_texto'=> array( 'type' => 'text', 'label' => __( 'Segundo botón (texto)', 'flavor-chat-ia' ) ),
                'boton_2_url'  => array( 'type' => 'url', 'label' => __( 'Segundo botón (URL)', 'flavor-chat-ia' ) ),
                'imagen_fondo' => array( 'type' => 'image', 'label' => __( 'Imagen de fondo', 'flavor-chat-ia' ) ),
                'video_url'    => array( 'type' => 'url', 'label' => __( 'Video de fondo (URL)', 'flavor-chat-ia' ) ),
                'overlay_color'=> array( 'type' => 'color', 'label' => __( 'Color overlay', 'flavor-chat-ia' ) ),
                'overlay_opacity' => array( 'type' => 'range', 'label' => __( 'Opacidad overlay', 'flavor-chat-ia' ), 'min' => 0, 'max' => 100 ),
                'formulario_email' => array( 'type' => 'toggle', 'label' => __( 'Mostrar campo email', 'flavor-chat-ia' ) ),
                'fecha_limite' => array( 'type' => 'datetime', 'label' => __( 'Fecha límite (countdown)', 'flavor-chat-ia' ) ),
                'alineacion'   => array( 'type' => 'select', 'label' => __( 'Alineación', 'flavor-chat-ia' ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha' ) ),
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
            'name'     => __( 'FAQ', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
            'variants' => array(
                'accordion'    => __( 'Acordeón', 'flavor-chat-ia' ),
                'two-columns'  => __( 'Dos columnas', 'flavor-chat-ia' ),
                'categories'   => __( 'Por categorías', 'flavor-chat-ia' ),
                'tabs'         => __( 'Con pestañas', 'flavor-chat-ia' ),
                'search'       => __( 'Con buscador', 'flavor-chat-ia' ),
                'sidebar'      => __( 'Con sidebar', 'flavor-chat-ia' ),
                'cards'        => __( 'Tarjetas', 'flavor-chat-ia' ),
                'minimal'      => __( 'Minimalista', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'    => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo' => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'mostrar_buscador' => array( 'type' => 'toggle', 'label' => __( 'Mostrar buscador', 'flavor-chat-ia' ) ),
                'abrir_primero'    => array( 'type' => 'toggle', 'label' => __( 'Abrir primera pregunta', 'flavor-chat-ia' ) ),
                'icono_expandir'   => array( 'type' => 'select', 'label' => __( 'Icono expandir', 'flavor-chat-ia' ), 'options' => array( 'plus' => 'Plus/Minus', 'chevron' => 'Chevron', 'arrow' => 'Flecha' ) ),
                'preguntas' => array( 'type' => 'repeater', 'label' => __( 'Preguntas', 'flavor-chat-ia' ), 'fields' => array(
                    'pregunta'  => array( 'type' => 'text', 'label' => __( 'Pregunta', 'flavor-chat-ia' ) ),
                    'respuesta' => array( 'type' => 'editor', 'label' => __( 'Respuesta', 'flavor-chat-ia' ) ),
                    'categoria' => array( 'type' => 'text', 'label' => __( 'Categoría', 'flavor-chat-ia' ) ),
                    'icono'     => array( 'type' => 'icon', 'label' => __( 'Icono', 'flavor-chat-ia' ) ),
                ) ),
                'texto_contacto' => array( 'type' => 'text', 'label' => __( 'Texto de contacto', 'flavor-chat-ia' ) ),
                'enlace_contacto' => array( 'type' => 'url', 'label' => __( 'Enlace de contacto', 'flavor-chat-ia' ) ),
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
            'name'     => __( 'Contacto', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
            'variants' => array(
                'form'          => __( 'Formulario', 'flavor-chat-ia' ),
                'split'         => __( 'Dividido', 'flavor-chat-ia' ),
                'map'           => __( 'Con mapa', 'flavor-chat-ia' ),
                'info'          => __( 'Solo información', 'flavor-chat-ia' ),
                'cards'         => __( 'Tarjetas de contacto', 'flavor-chat-ia' ),
                'chat'          => __( 'Estilo chat', 'flavor-chat-ia' ),
                'minimal'       => __( 'Minimalista', 'flavor-chat-ia' ),
                'fullwidth-map' => __( 'Mapa ancho completo', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'         => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo'      => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'email'          => array( 'type' => 'text', 'label' => __( 'Email', 'flavor-chat-ia' ) ),
                'telefono'       => array( 'type' => 'text', 'label' => __( 'Teléfono', 'flavor-chat-ia' ) ),
                'whatsapp'       => array( 'type' => 'text', 'label' => __( 'WhatsApp', 'flavor-chat-ia' ) ),
                'direccion'      => array( 'type' => 'textarea', 'label' => __( 'Dirección', 'flavor-chat-ia' ) ),
                'horario'        => array( 'type' => 'textarea', 'label' => __( 'Horario de atención', 'flavor-chat-ia' ) ),
                'mapa_lat'       => array( 'type' => 'number', 'label' => __( 'Latitud', 'flavor-chat-ia' ) ),
                'mapa_lng'       => array( 'type' => 'number', 'label' => __( 'Longitud', 'flavor-chat-ia' ) ),
                'mapa_zoom'      => array( 'type' => 'number', 'label' => __( 'Zoom del mapa', 'flavor-chat-ia' ), 'default' => 15 ),
                'redes_sociales' => array( 'type' => 'repeater', 'label' => __( 'Redes sociales', 'flavor-chat-ia' ), 'fields' => array(
                    'red'   => array( 'type' => 'select', 'label' => __( 'Red', 'flavor-chat-ia' ), 'options' => array( 'facebook' => 'Facebook', 'twitter' => 'Twitter/X', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'tiktok' => 'TikTok' ) ),
                    'url'   => array( 'type' => 'url', 'label' => __( 'URL', 'flavor-chat-ia' ) ),
                ) ),
                'formulario_campos' => array( 'type' => 'multiselect', 'label' => __( 'Campos del formulario', 'flavor-chat-ia' ), 'options' => array( 'nombre' => 'Nombre', 'email' => 'Email', 'telefono' => 'Teléfono', 'asunto' => 'Asunto', 'mensaje' => 'Mensaje' ) ),
                'boton_texto'    => array( 'type' => 'text', 'label' => __( 'Texto del botón', 'flavor-chat-ia' ), 'default' => 'Enviar mensaje' ),
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
            'name'     => __( 'Equipo', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
            'variants' => array(
                'grid'        => __( 'Cuadrícula', 'flavor-chat-ia' ),
                'carousel'    => __( 'Carrusel', 'flavor-chat-ia' ),
                'list'        => __( 'Lista', 'flavor-chat-ia' ),
                'cards'       => __( 'Tarjetas hover', 'flavor-chat-ia' ),
                'circular'    => __( 'Fotos circulares', 'flavor-chat-ia' ),
                'minimal'     => __( 'Minimalista', 'flavor-chat-ia' ),
                'detailed'    => __( 'Detallado con bio', 'flavor-chat-ia' ),
                'hierarchy'   => __( 'Organigrama', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'    => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo' => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'columnas'  => array( 'type' => 'select', 'label' => __( 'Columnas', 'flavor-chat-ia' ), 'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5' ) ),
                'miembros'  => array( 'type' => 'repeater', 'label' => __( 'Miembros', 'flavor-chat-ia' ), 'fields' => array(
                    'nombre'    => array( 'type' => 'text', 'label' => __( 'Nombre', 'flavor-chat-ia' ) ),
                    'cargo'     => array( 'type' => 'text', 'label' => __( 'Cargo', 'flavor-chat-ia' ) ),
                    'bio'       => array( 'type' => 'textarea', 'label' => __( 'Biografía', 'flavor-chat-ia' ) ),
                    'foto'      => array( 'type' => 'image', 'label' => __( 'Foto', 'flavor-chat-ia' ) ),
                    'email'     => array( 'type' => 'text', 'label' => __( 'Email', 'flavor-chat-ia' ) ),
                    'linkedin'  => array( 'type' => 'url', 'label' => __( 'LinkedIn', 'flavor-chat-ia' ) ),
                    'twitter'   => array( 'type' => 'url', 'label' => __( 'Twitter/X', 'flavor-chat-ia' ) ),
                    'departamento' => array( 'type' => 'text', 'label' => __( 'Departamento', 'flavor-chat-ia' ) ),
                ) ),
                'mostrar_redes' => array( 'type' => 'toggle', 'label' => __( 'Mostrar redes sociales', 'flavor-chat-ia' ) ),
                'mostrar_email' => array( 'type' => 'toggle', 'label' => __( 'Mostrar email', 'flavor-chat-ia' ) ),
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
            'name'     => __( 'Galería', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
            'variants' => array(
                'grid'        => __( 'Cuadrícula', 'flavor-chat-ia' ),
                'masonry'     => __( 'Masonry', 'flavor-chat-ia' ),
                'carousel'    => __( 'Carrusel', 'flavor-chat-ia' ),
                'lightbox'    => __( 'Con lightbox', 'flavor-chat-ia' ),
                'justified'   => __( 'Justificada', 'flavor-chat-ia' ),
                'slider'      => __( 'Slider fullwidth', 'flavor-chat-ia' ),
                'mosaic'      => __( 'Mosaico', 'flavor-chat-ia' ),
                'polaroid'    => __( 'Estilo Polaroid', 'flavor-chat-ia' ),
                'filterable'  => __( 'Con filtros', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'      => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo'   => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'imagenes'    => array( 'type' => 'gallery', 'label' => __( 'Imágenes', 'flavor-chat-ia' ) ),
                'columnas'    => array( 'type' => 'select', 'label' => __( 'Columnas', 'flavor-chat-ia' ), 'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ) ),
                'gap'         => array( 'type' => 'select', 'label' => __( 'Espaciado', 'flavor-chat-ia' ), 'options' => array( '0' => 'Sin espacio', '4' => 'Pequeño', '8' => 'Normal', '16' => 'Grande' ) ),
                'lightbox'    => array( 'type' => 'toggle', 'label' => __( 'Lightbox', 'flavor-chat-ia' ) ),
                'captions'    => array( 'type' => 'toggle', 'label' => __( 'Mostrar títulos', 'flavor-chat-ia' ) ),
                'hover_effect'=> array( 'type' => 'select', 'label' => __( 'Efecto hover', 'flavor-chat-ia' ), 'options' => array( 'none' => 'Ninguno', 'zoom' => 'Zoom', 'overlay' => 'Overlay', 'grayscale' => 'Escala de grises', 'blur' => 'Desenfoque' ) ),
                'aspect_ratio'=> array( 'type' => 'select', 'label' => __( 'Proporción', 'flavor-chat-ia' ), 'options' => array( 'auto' => 'Auto', 'square' => 'Cuadrado', '4:3' => '4:3', '16:9' => '16:9', '3:2' => '3:2' ) ),
                'autoplay'    => array( 'type' => 'toggle', 'label' => __( 'Autoplay (carrusel)', 'flavor-chat-ia' ) ),
                'categorias'  => array( 'type' => 'text', 'label' => __( 'Categorías (separadas por coma)', 'flavor-chat-ia' ) ),
            ),
        ) );

        // Blog
        $this->registrar_bloque( array(
            'id'       => 'blog',
            'name'     => __( 'Blog', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>',
            'variants' => array(
                'grid'          => __( 'Cuadrícula', 'flavor-chat-ia' ),
                'list'          => __( 'Lista', 'flavor-chat-ia' ),
                'featured'      => __( 'Destacado + grid', 'flavor-chat-ia' ),
                'masonry'       => __( 'Masonry', 'flavor-chat-ia' ),
                'carousel'      => __( 'Carrusel', 'flavor-chat-ia' ),
                'minimal'       => __( 'Minimalista', 'flavor-chat-ia' ),
                'cards-overlay' => __( 'Tarjetas con overlay', 'flavor-chat-ia' ),
                'timeline'      => __( 'Línea de tiempo', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo'    => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'cantidad'     => array( 'type' => 'number', 'label' => __( 'Cantidad de posts', 'flavor-chat-ia' ), 'default' => 6 ),
                'categoria'    => array( 'type' => 'taxonomy', 'label' => __( 'Categoría', 'flavor-chat-ia' ), 'taxonomy' => 'category' ),
                'etiquetas'    => array( 'type' => 'taxonomy', 'label' => __( 'Etiquetas', 'flavor-chat-ia' ), 'taxonomy' => 'post_tag' ),
                'columnas'     => array( 'type' => 'select', 'label' => __( 'Columnas', 'flavor-chat-ia' ), 'options' => array( '2' => '2', '3' => '3', '4' => '4' ) ),
                'orden'        => array( 'type' => 'select', 'label' => __( 'Ordenar por', 'flavor-chat-ia' ), 'options' => array( 'date' => 'Fecha', 'title' => 'Título', 'rand' => 'Aleatorio', 'comment_count' => 'Comentarios' ) ),
                'mostrar_extracto' => array( 'type' => 'toggle', 'label' => __( 'Mostrar extracto', 'flavor-chat-ia' ) ),
                'mostrar_fecha' => array( 'type' => 'toggle', 'label' => __( 'Mostrar fecha', 'flavor-chat-ia' ) ),
                'mostrar_autor' => array( 'type' => 'toggle', 'label' => __( 'Mostrar autor', 'flavor-chat-ia' ) ),
                'mostrar_categorias' => array( 'type' => 'toggle', 'label' => __( 'Mostrar categorías', 'flavor-chat-ia' ) ),
                'mostrar_imagen' => array( 'type' => 'toggle', 'label' => __( 'Mostrar imagen', 'flavor-chat-ia' ), 'default' => true ),
                'leer_mas_texto' => array( 'type' => 'text', 'label' => __( 'Texto "Leer más"', 'flavor-chat-ia' ), 'default' => 'Leer más' ),
                'ver_todos_url' => array( 'type' => 'url', 'label' => __( 'URL "Ver todos"', 'flavor-chat-ia' ) ),
                'ver_todos_texto' => array( 'type' => 'text', 'label' => __( 'Texto "Ver todos"', 'flavor-chat-ia' ) ),
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
            'name'     => __( 'Video', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5,3 19,12 5,21"/></svg>',
            'variants' => array(
                'embed'       => __( 'Embed simple', 'flavor-chat-ia' ),
                'background'  => __( 'Video de fondo', 'flavor-chat-ia' ),
                'lightbox'    => __( 'Con lightbox', 'flavor-chat-ia' ),
                'split'       => __( 'Dividido con texto', 'flavor-chat-ia' ),
                'fullscreen'  => __( 'Pantalla completa', 'flavor-chat-ia' ),
                'playlist'    => __( 'Playlist', 'flavor-chat-ia' ),
                'testimonial' => __( 'Video testimonio', 'flavor-chat-ia' ),
                'tutorial'    => __( 'Tutorial con pasos', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'descripcion'  => array( 'type' => 'textarea', 'label' => __( 'Descripción', 'flavor-chat-ia' ), 'ai' => true ),
                'video_url'    => array( 'type' => 'url', 'label' => __( 'URL del video (YouTube/Vimeo)', 'flavor-chat-ia' ) ),
                'video_file'   => array( 'type' => 'file', 'label' => __( 'Archivo de video', 'flavor-chat-ia' ), 'accept' => 'video/*' ),
                'thumbnail'    => array( 'type' => 'image', 'label' => __( 'Imagen de portada', 'flavor-chat-ia' ) ),
                'autoplay'     => array( 'type' => 'toggle', 'label' => __( 'Autoplay', 'flavor-chat-ia' ) ),
                'loop'         => array( 'type' => 'toggle', 'label' => __( 'Loop', 'flavor-chat-ia' ) ),
                'muted'        => array( 'type' => 'toggle', 'label' => __( 'Silenciado', 'flavor-chat-ia' ) ),
                'controls'     => array( 'type' => 'toggle', 'label' => __( 'Mostrar controles', 'flavor-chat-ia' ), 'default' => true ),
                'aspect_ratio' => array( 'type' => 'select', 'label' => __( 'Proporción', 'flavor-chat-ia' ), 'options' => array( '16:9' => '16:9', '4:3' => '4:3', '21:9' => '21:9 (Cine)', '1:1' => 'Cuadrado' ) ),
                'play_icon'    => array( 'type' => 'select', 'label' => __( 'Estilo icono play', 'flavor-chat-ia' ), 'options' => array( 'default' => 'Defecto', 'circle' => 'Círculo', 'minimal' => 'Minimalista', 'youtube' => 'Estilo YouTube' ) ),
                'overlay_color'=> array( 'type' => 'color', 'label' => __( 'Color overlay', 'flavor-chat-ia' ) ),
                'boton_texto'  => array( 'type' => 'text', 'label' => __( 'Texto del botón', 'flavor-chat-ia' ) ),
                'boton_url'    => array( 'type' => 'url', 'label' => __( 'URL del botón', 'flavor-chat-ia' ) ),
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
            'name'     => __( 'Estadísticas', 'flavor-chat-ia' ),
            'category' => 'sections',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>',
            'variants' => array(
                'counters'    => __( 'Contadores', 'flavor-chat-ia' ),
                'progress'    => __( 'Barras de progreso', 'flavor-chat-ia' ),
                'charts'      => __( 'Gráficos', 'flavor-chat-ia' ),
                'cards'       => __( 'Tarjetas', 'flavor-chat-ia' ),
                'icons'       => __( 'Con iconos', 'flavor-chat-ia' ),
                'radial'      => __( 'Progreso circular', 'flavor-chat-ia' ),
                'minimal'     => __( 'Minimalista', 'flavor-chat-ia' ),
                'comparison'  => __( 'Comparación antes/después', 'flavor-chat-ia' ),
            ),
            'fields'   => array(
                'titulo'       => array( 'type' => 'text', 'label' => __( 'Título', 'flavor-chat-ia' ), 'ai' => true ),
                'subtitulo'    => array( 'type' => 'textarea', 'label' => __( 'Subtítulo', 'flavor-chat-ia' ), 'ai' => true ),
                'columnas'     => array( 'type' => 'select', 'label' => __( 'Columnas', 'flavor-chat-ia' ), 'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5' ) ),
                'estadisticas' => array( 'type' => 'repeater', 'label' => __( 'Estadísticas', 'flavor-chat-ia' ), 'fields' => array(
                    'valor'       => array( 'type' => 'text', 'label' => __( 'Valor', 'flavor-chat-ia' ) ),
                    'etiqueta'    => array( 'type' => 'text', 'label' => __( 'Etiqueta', 'flavor-chat-ia' ) ),
                    'descripcion' => array( 'type' => 'text', 'label' => __( 'Descripción', 'flavor-chat-ia' ) ),
                    'icono'       => array( 'type' => 'icon', 'label' => __( 'Icono', 'flavor-chat-ia' ) ),
                    'prefijo'     => array( 'type' => 'text', 'label' => __( 'Prefijo (ej: $, €)', 'flavor-chat-ia' ) ),
                    'sufijo'      => array( 'type' => 'text', 'label' => __( 'Sufijo (ej: %, +)', 'flavor-chat-ia' ) ),
                    'porcentaje'  => array( 'type' => 'number', 'label' => __( 'Porcentaje (para barras)', 'flavor-chat-ia' ), 'min' => 0, 'max' => 100 ),
                    'color'       => array( 'type' => 'color', 'label' => __( 'Color', 'flavor-chat-ia' ) ),
                ) ),
                'animacion'    => array( 'type' => 'toggle', 'label' => __( 'Animación de conteo', 'flavor-chat-ia' ), 'default' => true ),
                'duracion'     => array( 'type' => 'number', 'label' => __( 'Duración animación (ms)', 'flavor-chat-ia' ), 'default' => 2000 ),
                'color_fondo'  => array( 'type' => 'color', 'label' => __( 'Color de fondo', 'flavor-chat-ia' ) ),
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
    }

    /**
     * Registra bloques básicos
     */
    private function registrar_bloques_basicos() {
        // Texto
        $this->registrar_bloque( array(
            'id'       => 'text',
            'name'     => __( 'Texto', 'flavor-chat-ia' ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4,7 4,4 20,4 20,7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
            'fields'   => array(
                'contenido'   => array( 'type' => 'textarea', 'label' => __( 'Contenido', 'flavor-chat-ia' ) ),
                'alineacion'  => array( 'type' => 'select', 'label' => __( 'Alineación', 'flavor-chat-ia' ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado' ) ),
                'tamano'      => array( 'type' => 'select', 'label' => __( 'Tamaño', 'flavor-chat-ia' ), 'options' => array( 'sm' => 'Pequeño', 'base' => 'Normal', 'lg' => 'Grande', 'xl' => 'Extra grande' ) ),
                'color'       => array( 'type' => 'color', 'label' => __( 'Color', 'flavor-chat-ia' ) ),
            ),
        ) );

        // Encabezado
        $this->registrar_bloque( array(
            'id'       => 'heading',
            'name'     => __( 'Encabezado', 'flavor-chat-ia' ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4v16M18 4v16M6 12h12"/></svg>',
            'fields'   => array(
                'texto'      => array( 'type' => 'text', 'label' => __( 'Texto', 'flavor-chat-ia' ) ),
                'nivel'      => array( 'type' => 'select', 'label' => __( 'Nivel', 'flavor-chat-ia' ), 'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6' ) ),
                'alineacion' => array( 'type' => 'select', 'label' => __( 'Alineación', 'flavor-chat-ia' ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha' ) ),
                'color'      => array( 'type' => 'color', 'label' => __( 'Color', 'flavor-chat-ia' ) ),
            ),
        ) );

        // Imagen
        $this->registrar_bloque( array(
            'id'       => 'image',
            'name'     => __( 'Imagen', 'flavor-chat-ia' ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
            'fields'   => array(
                'imagen'     => array( 'type' => 'image', 'label' => __( 'Imagen', 'flavor-chat-ia' ) ),
                'alt'        => array( 'type' => 'text', 'label' => __( 'Texto alternativo', 'flavor-chat-ia' ) ),
                'tamano'     => array( 'type' => 'select', 'label' => __( 'Tamaño', 'flavor-chat-ia' ), 'options' => array( 'auto' => 'Auto', 'full' => 'Completo', 'contain' => 'Contener', 'cover' => 'Cubrir' ) ),
                'alineacion' => array( 'type' => 'select', 'label' => __( 'Alineación', 'flavor-chat-ia' ), 'options' => array( 'left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha' ) ),
                'enlace'     => array( 'type' => 'url', 'label' => __( 'Enlace', 'flavor-chat-ia' ) ),
                'lightbox'   => array( 'type' => 'toggle', 'label' => __( 'Lightbox', 'flavor-chat-ia' ) ),
            ),
        ) );

        // Botón
        $this->registrar_bloque( array(
            'id'       => 'button',
            'name'     => __( 'Botón', 'flavor-chat-ia' ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"/></svg>',
            'fields'   => array(
                'texto'       => array( 'type' => 'text', 'label' => __( 'Texto', 'flavor-chat-ia' ) ),
                'url'         => array( 'type' => 'url', 'label' => __( 'URL', 'flavor-chat-ia' ) ),
                'estilo'      => array( 'type' => 'select', 'label' => __( 'Estilo', 'flavor-chat-ia' ), 'options' => array( 'primary' => 'Primario', 'secondary' => 'Secundario', 'outline' => 'Contorno', 'ghost' => 'Transparente' ) ),
                'tamano'      => array( 'type' => 'select', 'label' => __( 'Tamaño', 'flavor-chat-ia' ), 'options' => array( 'sm' => 'Pequeño', 'md' => 'Mediano', 'lg' => 'Grande' ) ),
                'icono'       => array( 'type' => 'icon', 'label' => __( 'Icono', 'flavor-chat-ia' ) ),
                'nueva_vent'  => array( 'type' => 'toggle', 'label' => __( 'Nueva ventana', 'flavor-chat-ia' ) ),
                'ancho_full'  => array( 'type' => 'toggle', 'label' => __( 'Ancho completo', 'flavor-chat-ia' ) ),
            ),
        ) );

        // Divisor
        $this->registrar_bloque( array(
            'id'       => 'divider',
            'name'     => __( 'Divisor', 'flavor-chat-ia' ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/></svg>',
            'fields'   => array(
                'estilo'  => array( 'type' => 'select', 'label' => __( 'Estilo', 'flavor-chat-ia' ), 'options' => array( 'solid' => 'Sólido', 'dashed' => 'Guiones', 'dotted' => 'Puntos', 'double' => 'Doble' ) ),
                'color'   => array( 'type' => 'color', 'label' => __( 'Color', 'flavor-chat-ia' ) ),
                'grosor'  => array( 'type' => 'select', 'label' => __( 'Grosor', 'flavor-chat-ia' ), 'options' => array( '1' => '1px', '2' => '2px', '3' => '3px', '4' => '4px' ) ),
                'ancho'   => array( 'type' => 'select', 'label' => __( 'Ancho', 'flavor-chat-ia' ), 'options' => array( '25' => '25%', '50' => '50%', '75' => '75%', '100' => '100%' ) ),
                'margen'  => array( 'type' => 'spacing', 'label' => __( 'Margen', 'flavor-chat-ia' ) ),
            ),
        ) );

        // Espaciador
        $this->registrar_bloque( array(
            'id'       => 'spacer',
            'name'     => __( 'Espaciador', 'flavor-chat-ia' ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
            'fields'   => array(
                'altura'         => array( 'type' => 'number', 'label' => __( 'Altura (px)', 'flavor-chat-ia' ) ),
                'altura_mobile'  => array( 'type' => 'number', 'label' => __( 'Altura móvil (px)', 'flavor-chat-ia' ) ),
            ),
        ) );

        // Icono
        $this->registrar_bloque( array(
            'id'       => 'icon',
            'name'     => __( 'Icono', 'flavor-chat-ia' ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>',
            'fields'   => array(
                'icono'       => array( 'type' => 'icon', 'label' => __( 'Icono', 'flavor-chat-ia' ) ),
                'tamano'      => array( 'type' => 'select', 'label' => __( 'Tamaño', 'flavor-chat-ia' ), 'options' => array( 'sm' => 'Pequeño', 'md' => 'Mediano', 'lg' => 'Grande', 'xl' => 'Extra grande' ) ),
                'color'       => array( 'type' => 'color', 'label' => __( 'Color', 'flavor-chat-ia' ) ),
                'fondo'       => array( 'type' => 'color', 'label' => __( 'Color de fondo', 'flavor-chat-ia' ) ),
                'borde'       => array( 'type' => 'toggle', 'label' => __( 'Borde', 'flavor-chat-ia' ) ),
                'redondeado'  => array( 'type' => 'toggle', 'label' => __( 'Redondeado', 'flavor-chat-ia' ) ),
                'enlace'      => array( 'type' => 'url', 'label' => __( 'Enlace', 'flavor-chat-ia' ) ),
            ),
        ) );

        // HTML
        $this->registrar_bloque( array(
            'id'       => 'html',
            'name'     => __( 'HTML', 'flavor-chat-ia' ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>',
            'fields'   => array(
                'codigo'      => array( 'type' => 'code', 'label' => __( 'Código HTML', 'flavor-chat-ia' ), 'language' => 'html' ),
                'contenedor'  => array( 'type' => 'toggle', 'label' => __( 'Contenedor', 'flavor-chat-ia' ) ),
            ),
        ) );

        // Shortcode
        $this->registrar_bloque( array(
            'id'       => 'shortcode',
            'name'     => __( 'Shortcode', 'flavor-chat-ia' ),
            'category' => 'basic',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 17l6-6-6-6M12 19h8"/></svg>',
            'fields'   => array(
                'shortcode'   => array( 'type' => 'text', 'label' => __( 'Shortcode', 'flavor-chat-ia' ), 'placeholder' => '[mi_shortcode]' ),
                'descripcion' => array( 'type' => 'text', 'label' => __( 'Descripción', 'flavor-chat-ia' ) ),
            ),
        ) );
    }

    /**
     * Registra bloques de layout
     */
    private function registrar_bloques_layout() {
        $this->registrar_bloque( array(
            'id'       => 'container',
            'name'     => __( 'Contenedor', 'flavor-chat-ia' ),
            'category' => 'layout',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
            'fields'   => array(
                'max_width'  => array( 'type' => 'select', 'label' => __( 'Ancho máximo', 'flavor-chat-ia' ), 'options' => array( 'full' => 'Completo', '1200px' => '1200px', '960px' => '960px', '720px' => '720px' ) ),
                'padding'    => array( 'type' => 'spacing', 'label' => __( 'Padding', 'flavor-chat-ia' ) ),
                'background' => array( 'type' => 'color', 'label' => __( 'Fondo', 'flavor-chat-ia' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'row',
            'name'     => __( 'Fila', 'flavor-chat-ia' ),
            'category' => 'layout',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="1"/></svg>',
            'fields'   => array(
                'align'   => array( 'type' => 'select', 'label' => __( 'Alineación', 'flavor-chat-ia' ), 'options' => array( 'start' => 'Inicio', 'center' => 'Centro', 'end' => 'Final', 'stretch' => 'Estirar' ) ),
                'gap'     => array( 'type' => 'number', 'label' => __( 'Espacio', 'flavor-chat-ia' ) ),
                'reverse' => array( 'type' => 'toggle', 'label' => __( 'Invertir', 'flavor-chat-ia' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'columns',
            'name'     => __( 'Columnas', 'flavor-chat-ia' ),
            'category' => 'layout',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="18" rx="1"/></svg>',
            'fields'   => array(
                'columnas' => array( 'type' => 'select', 'label' => __( 'Columnas', 'flavor-chat-ia' ), 'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ) ),
                'gap'      => array( 'type' => 'number', 'label' => __( 'Espacio', 'flavor-chat-ia' ) ),
                'stack_on' => array( 'type' => 'select', 'label' => __( 'Apilar en', 'flavor-chat-ia' ), 'options' => array( 'mobile' => 'Móvil', 'tablet' => 'Tablet', 'never' => 'Nunca' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'grid',
            'name'     => __( 'Grid', 'flavor-chat-ia' ),
            'category' => 'layout',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
            'fields'   => array(
                'columnas' => array( 'type' => 'number', 'label' => __( 'Columnas', 'flavor-chat-ia' ) ),
                'filas'    => array( 'type' => 'number', 'label' => __( 'Filas', 'flavor-chat-ia' ) ),
                'gap'      => array( 'type' => 'number', 'label' => __( 'Espacio', 'flavor-chat-ia' ) ),
            ),
        ) );
    }

    /**
     * Registra bloques de formularios
     */
    private function registrar_bloques_formularios() {
        $this->registrar_bloque( array(
            'id'       => 'form',
            'name'     => __( 'Formulario', 'flavor-chat-ia' ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/></svg>',
            'fields'   => array(
                'action'       => array( 'type' => 'url', 'label' => __( 'URL de envío', 'flavor-chat-ia' ) ),
                'method'       => array( 'type' => 'select', 'label' => __( 'Método', 'flavor-chat-ia' ), 'options' => array( 'POST' => 'POST', 'GET' => 'GET' ) ),
                'submit_text'  => array( 'type' => 'text', 'label' => __( 'Texto del botón', 'flavor-chat-ia' ) ),
                'success_msg'  => array( 'type' => 'text', 'label' => __( 'Mensaje de éxito', 'flavor-chat-ia' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'input',
            'name'     => __( 'Campo de texto', 'flavor-chat-ia' ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 12h.01"/></svg>',
            'fields'   => array(
                'label'       => array( 'type' => 'text', 'label' => __( 'Etiqueta', 'flavor-chat-ia' ) ),
                'placeholder' => array( 'type' => 'text', 'label' => __( 'Placeholder', 'flavor-chat-ia' ) ),
                'type'        => array( 'type' => 'select', 'label' => __( 'Tipo', 'flavor-chat-ia' ), 'options' => array( 'text' => 'Texto', 'email' => 'Email', 'tel' => 'Teléfono', 'number' => 'Número', 'password' => 'Contraseña' ) ),
                'required'    => array( 'type' => 'toggle', 'label' => __( 'Requerido', 'flavor-chat-ia' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'textarea',
            'name'     => __( 'Área de texto', 'flavor-chat-ia' ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="13" y2="16"/></svg>',
            'fields'   => array(
                'label'       => array( 'type' => 'text', 'label' => __( 'Etiqueta', 'flavor-chat-ia' ) ),
                'placeholder' => array( 'type' => 'text', 'label' => __( 'Placeholder', 'flavor-chat-ia' ) ),
                'rows'        => array( 'type' => 'number', 'label' => __( 'Filas', 'flavor-chat-ia' ) ),
                'required'    => array( 'type' => 'toggle', 'label' => __( 'Requerido', 'flavor-chat-ia' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'select',
            'name'     => __( 'Selector', 'flavor-chat-ia' ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><polyline points="8,10 12,14 16,10"/></svg>',
            'fields'   => array(
                'label'    => array( 'type' => 'text', 'label' => __( 'Etiqueta', 'flavor-chat-ia' ) ),
                'options'  => array( 'type' => 'repeater', 'label' => __( 'Opciones', 'flavor-chat-ia' ) ),
                'required' => array( 'type' => 'toggle', 'label' => __( 'Requerido', 'flavor-chat-ia' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'checkbox',
            'name'     => __( 'Checkbox', 'flavor-chat-ia' ),
            'category' => 'forms',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="9,12 11,14 15,10"/></svg>',
            'fields'   => array(
                'label'    => array( 'type' => 'text', 'label' => __( 'Etiqueta', 'flavor-chat-ia' ) ),
                'checked'  => array( 'type' => 'toggle', 'label' => __( 'Marcado', 'flavor-chat-ia' ) ),
                'required' => array( 'type' => 'toggle', 'label' => __( 'Requerido', 'flavor-chat-ia' ) ),
            ),
        ) );
    }

    /**
     * Registra bloques de media
     */
    private function registrar_bloques_media() {
        $this->registrar_bloque( array(
            'id'       => 'video-embed',
            'name'     => __( 'Video embed', 'flavor-chat-ia' ),
            'category' => 'media',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polygon points="10,8 16,12 10,16 10,8"/></svg>',
            'fields'   => array(
                'url'        => array( 'type' => 'url', 'label' => __( 'URL del video', 'flavor-chat-ia' ) ),
                'autoplay'   => array( 'type' => 'toggle', 'label' => __( 'Autoplay', 'flavor-chat-ia' ) ),
                'muted'      => array( 'type' => 'toggle', 'label' => __( 'Sin sonido', 'flavor-chat-ia' ) ),
                'loop'       => array( 'type' => 'toggle', 'label' => __( 'Bucle', 'flavor-chat-ia' ) ),
                'aspect'     => array( 'type' => 'select', 'label' => __( 'Aspecto', 'flavor-chat-ia' ), 'options' => array( '16:9' => '16:9', '4:3' => '4:3', '1:1' => '1:1' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'audio',
            'name'     => __( 'Audio', 'flavor-chat-ia' ),
            'category' => 'media',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
            'fields'   => array(
                'url'      => array( 'type' => 'url', 'label' => __( 'URL del audio', 'flavor-chat-ia' ) ),
                'autoplay' => array( 'type' => 'toggle', 'label' => __( 'Autoplay', 'flavor-chat-ia' ) ),
                'loop'     => array( 'type' => 'toggle', 'label' => __( 'Bucle', 'flavor-chat-ia' ) ),
                'controls' => array( 'type' => 'toggle', 'label' => __( 'Controles', 'flavor-chat-ia' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'map',
            'name'     => __( 'Mapa', 'flavor-chat-ia' ),
            'category' => 'media',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1,6 1,22 8,18 16,22 23,18 23,2 16,6 8,2"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
            'fields'   => array(
                'lat'     => array( 'type' => 'number', 'label' => __( 'Latitud', 'flavor-chat-ia' ) ),
                'lng'     => array( 'type' => 'number', 'label' => __( 'Longitud', 'flavor-chat-ia' ) ),
                'zoom'    => array( 'type' => 'number', 'label' => __( 'Zoom', 'flavor-chat-ia' ) ),
                'height'  => array( 'type' => 'number', 'label' => __( 'Altura (px)', 'flavor-chat-ia' ) ),
                'marker'  => array( 'type' => 'toggle', 'label' => __( 'Mostrar marcador', 'flavor-chat-ia' ) ),
            ),
        ) );

        $this->registrar_bloque( array(
            'id'       => 'embed',
            'name'     => __( 'Embed HTML', 'flavor-chat-ia' ),
            'category' => 'media',
            'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>',
            'fields'   => array(
                'code'   => array( 'type' => 'code', 'label' => __( 'Código HTML', 'flavor-chat-ia' ) ),
                'height' => array( 'type' => 'number', 'label' => __( 'Altura (px)', 'flavor-chat-ia' ) ),
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
                'label'   => __( 'Altura (px)', 'flavor-chat-ia' ),
                'default' => 400,
                'min'     => 200,
                'max'     => 800,
            ),
            'zoom' => array(
                'type'    => 'number',
                'label'   => __( 'Zoom inicial', 'flavor-chat-ia' ),
                'default' => 14,
                'min'     => 8,
                'max'     => 18,
            ),
            'mostrar_filtros' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar filtros', 'flavor-chat-ia' ),
                'default' => true,
            ),
            'mostrar_listado' => array(
                'type'    => 'toggle',
                'label'   => __( 'Mostrar listado', 'flavor-chat-ia' ),
                'default' => true,
            ),
        );

        if ( $this->modulo_activo( 'parkings', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mapa-parkings',
                'name'      => __( 'Mapa de Parkings', 'flavor-chat-ia' ),
                'category'  => 'maps',
                'shortcode' => 'flavor_mapa_parkings',
                'module'    => 'parkings',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 9h4a2 2 0 012 2v0a2 2 0 01-2 2H9v-4z"/><path d="M9 13v4"/></svg>',
                'fields'    => array_merge( $campos_mapa_comunes, array(
                    'solo_disponibles' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Solo con plazas', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                ) ),
            ) );
        }

        if ( $this->modulo_activo( 'huertos-urbanos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mapa-huertos',
                'name'      => __( 'Mapa de Huertos', 'flavor-chat-ia' ),
                'category'  => 'maps',
                'shortcode' => 'mapa_huertos',
                'module'    => 'huertos-urbanos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0110 10c0 5.52-10 12-10 12S2 17.52 2 12A10 10 0 0112 2z"/><circle cx="12" cy="10" r="3"/></svg>',
                'fields'    => array_merge( $campos_mapa_comunes, array(
                    'solo_activos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Solo activos', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ) ),
            ) );
        }

        if ( $this->modulo_activo( 'compostaje', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mapa-composteras',
                'name'      => __( 'Mapa de Composteras', 'flavor-chat-ia' ),
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
                'name'      => __( 'Mapa de Biodiversidad', 'flavor-chat-ia' ),
                'category'  => 'maps',
                'shortcode' => 'biodiversidad_mapa',
                'module'    => 'biodiversidad-local',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c4-4 8-7.5 8-12a8 8 0 10-16 0c0 4.5 4 8 8 12z"/><path d="M12 6v6l3 3"/></svg>',
                'fields'    => array_merge( $campos_mapa_comunes, array(
                    'tipo_especie' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo de especie', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'flora' => __( 'Flora', 'flavor-chat-ia' ), 'fauna' => __( 'Fauna', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                ) ),
            ) );
        }

        if ( $this->modulo_activo( 'incidencias', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mapa-incidencias',
                'name'      => __( 'Mapa de Incidencias', 'flavor-chat-ia' ),
                'category'  => 'maps',
                'shortcode' => 'mapa_incidencias',
                'module'    => 'incidencias',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                'fields'    => array_merge( $campos_mapa_comunes, array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'pendiente' => __( 'Pendientes', 'flavor-chat-ia' ), 'en_proceso' => __( 'En proceso', 'flavor-chat-ia' ), 'resueltas' => __( 'Resueltas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'dias' => array(
                        'type'    => 'number',
                        'label'   => __( 'Últimos días', 'flavor-chat-ia' ),
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
                'name'      => __( 'Dashboard Banco de Tiempo', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'bt_dashboard_sostenibilidad',
                'module'    => 'banco-tiempo',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>',
                'fields'    => array(
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', 'flavor-chat-ia' ),
                        'options' => array( 'week' => __( 'Semana', 'flavor-chat-ia' ), 'month' => __( 'Mes', 'flavor-chat-ia' ), 'year' => __( 'Año', 'flavor-chat-ia' ) ),
                        'default' => 'month',
                    ),
                    'mostrar_ranking' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar ranking', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_estadisticas' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar estadísticas', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'bt-ranking',
                'name'      => __( 'Ranking Comunitario', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'bt_ranking_comunidad',
                'module'    => 'banco-tiempo',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15l-2 5-3-1 2-4h3z"/><path d="M12 15l2 5 3-1-2-4h-3z"/><circle cx="12" cy="8" r="5"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Top usuarios', 'flavor-chat-ia' ),
                        'default' => 10,
                        'min'     => 3,
                        'max'     => 50,
                    ),
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', 'flavor-chat-ia' ),
                        'options' => array( 'week' => __( 'Semana', 'flavor-chat-ia' ), 'month' => __( 'Mes', 'flavor-chat-ia' ), 'year' => __( 'Año', 'flavor-chat-ia' ), 'all' => __( 'Todo', 'flavor-chat-ia' ) ),
                        'default' => 'month',
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'economia-don', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'economia-don',
                'name'      => __( 'Economía del Don', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'economia_don',
                'module'    => 'economia-don',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 48,
                    ),
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'mostrar_donante' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar donante', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'muro-gratitud',
                'name'      => __( 'Muro de Gratitud', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'muro_gratitud',
                'module'    => 'economia-don',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Mensajes', 'flavor-chat-ia' ),
                        'default' => 20,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'grid' => __( 'Cuadrícula', 'flavor-chat-ia' ), 'masonry' => __( 'Masonry', 'flavor-chat-ia' ), 'carousel' => __( 'Carrusel', 'flavor-chat-ia' ) ),
                        'default' => 'masonry',
                    ),
                    'permitir_envio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir envío', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'grupos-consumo', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'gc-catalogo',
                'name'      => __( 'Catálogo Productos', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'gc_catalogo',
                'module'    => 'grupos-consumo',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 48,
                    ),
                    'solo_disponibles' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Solo disponibles', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_productor' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar productor', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'gc-productores',
                'name'      => __( 'Productores Cercanos', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'gc_productores_cercanos',
                'module'    => 'grupos-consumo',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 8,
                        'min'     => 1,
                        'max'     => 24,
                    ),
                    'radio_km' => array(
                        'type'    => 'number',
                        'label'   => __( 'Radio (km)', 'flavor-chat-ia' ),
                        'default' => 50,
                        'min'     => 5,
                        'max'     => 200,
                    ),
                    'mostrar_mapa' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar mapa', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // ============ COMUNIDAD ============
        if ( $this->modulo_activo( 'multimedia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'galeria-multimedia',
                'name'      => __( 'Galería Multimedia', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'flavor_galeria',
                'module'    => 'multimedia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
                'fields'    => array(
                    'album' => array(
                        'type'    => 'select',
                        'label'   => __( 'Álbum', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 48,
                    ),
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', 'flavor-chat-ia' ),
                        'options' => array( '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
                        'default' => '4',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'grid' => __( 'Cuadrícula', 'flavor-chat-ia' ), 'masonry' => __( 'Masonry', 'flavor-chat-ia' ) ),
                        'default' => 'grid',
                    ),
                    'lightbox' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Abrir en lightbox', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'carousel-imagenes',
                'name'      => __( 'Carrusel de Imágenes', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'flavor_carousel',
                'module'    => 'multimedia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><polyline points="6,10 2,12 6,14"/><polyline points="18,10 22,12 18,14"/></svg>',
                'fields'    => array(
                    'album' => array(
                        'type'    => 'select',
                        'label'   => __( 'Álbum', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'autoplay' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Autoplay', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'intervalo' => array(
                        'type'    => 'number',
                        'label'   => __( 'Intervalo (seg)', 'flavor-chat-ia' ),
                        'default' => 5,
                        'min'     => 2,
                        'max'     => 15,
                    ),
                    'mostrar_flechas' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar flechas', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_puntos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar indicadores', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'radio', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'radio-player',
                'name'      => __( 'Player de Radio', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'flavor_radio_player',
                'module'    => 'radio',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 010 8.49M7.76 16.24a6 6 0 010-8.49M19.07 4.93a10 10 0 010 14.14M4.93 19.07a10 10 0 010-14.14"/></svg>',
                'fields'    => array(
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'compacto' => __( 'Compacto', 'flavor-chat-ia' ), 'expandido' => __( 'Expandido', 'flavor-chat-ia' ), 'mini' => __( 'Mini', 'flavor-chat-ia' ) ),
                        'default' => 'compacto',
                    ),
                    'autoplay' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Autoplay', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                    'mostrar_programa' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar programa actual', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_oyentes' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar oyentes', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'radio-programacion',
                'name'      => __( 'Programación Radio', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'flavor_radio_programacion',
                'module'    => 'radio',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', 'flavor-chat-ia' ),
                        'options' => array( 'semana' => __( 'Semanal', 'flavor-chat-ia' ), 'dia' => __( 'Diaria', 'flavor-chat-ia' ), 'lista' => __( 'Lista', 'flavor-chat-ia' ) ),
                        'default' => 'semana',
                    ),
                    'mostrar_locutor' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar locutor', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'destacar_actual' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Destacar programa actual', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'avisos-municipales', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'avisos-activos',
                'name'      => __( 'Avisos Municipales', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'avisos_activos',
                'module'    => 'avisos-municipales',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'info' => __( 'Información', 'flavor-chat-ia' ), 'evento' => __( 'Eventos', 'flavor-chat-ia' ), 'servicio' => __( 'Servicios', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 5,
                        'min'     => 1,
                        'max'     => 20,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'lista' => __( 'Lista', 'flavor-chat-ia' ), 'cards' => __( 'Tarjetas', 'flavor-chat-ia' ), 'marquee' => __( 'Marquesina', 'flavor-chat-ia' ) ),
                        'default' => 'lista',
                    ),
                    'mostrar_fecha' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar fecha', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'avisos-urgentes',
                'name'      => __( 'Avisos Urgentes', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'avisos_urgentes',
                'module'    => 'avisos-municipales',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 3,
                        'min'     => 1,
                        'max'     => 10,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'banner' => __( 'Banner', 'flavor-chat-ia' ), 'modal' => __( 'Modal', 'flavor-chat-ia' ), 'sticky' => __( 'Fijo', 'flavor-chat-ia' ) ),
                        'default' => 'banner',
                    ),
                    'auto_ocultar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Auto ocultar', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'cursos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'cursos-catalogo',
                'name'      => __( 'Catálogo de Cursos', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'cursos_catalogo',
                'module'    => 'cursos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 50,
                    ),
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', 'flavor-chat-ia' ),
                        'options' => array( '2' => '2', '3' => '3', '4' => '4' ),
                        'default' => '3',
                    ),
                    'mostrar_precio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar precio', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_duracion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar duración', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'eventos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'eventos-listado',
                'name'      => __( 'Listado de Eventos', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'eventos_listado',
                'module'    => 'eventos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 6,
                        'min'     => 1,
                        'max'     => 24,
                    ),
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', 'flavor-chat-ia' ),
                        'options' => array( 'grid' => __( 'Cuadrícula', 'flavor-chat-ia' ), 'list' => __( 'Lista', 'flavor-chat-ia' ) ),
                        'default' => 'grid',
                    ),
                    'mostrar_fecha' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar fecha', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'solo_proximos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Solo próximos', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'eventos-calendario',
                'name'      => __( 'Calendario de Eventos', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'eventos_calendario',
                'module'    => 'eventos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><rect x="7" y="14" width="3" height="3"/></svg>',
                'fields'    => array(
                    'vista_inicial' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista inicial', 'flavor-chat-ia' ),
                        'options' => array( 'month' => __( 'Mes', 'flavor-chat-ia' ), 'week' => __( 'Semana', 'flavor-chat-ia' ), 'day' => __( 'Día', 'flavor-chat-ia' ) ),
                        'default' => 'month',
                    ),
                    'mostrar_controles' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar controles', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // ============ MÓDULOS GENERALES ============
        if ( $this->modulo_activo( 'transparencia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'transparencia-portal',
                'name'      => __( 'Portal Transparencia', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'transparencia_portal',
                'module'    => 'transparencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                'fields'    => array(
                    'seccion' => array(
                        'type'    => 'select',
                        'label'   => __( 'Sección', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'contratos' => __( 'Contratos', 'flavor-chat-ia' ), 'subvenciones' => __( 'Subvenciones', 'flavor-chat-ia' ), 'personal' => __( 'Personal', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'anio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Año', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Actual', 'flavor-chat-ia' ), '2024' => '2024', '2023' => '2023', '2022' => '2022' ),
                        'default' => '',
                    ),
                    'mostrar_buscador' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar buscador', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'transparencia-presupuesto',
                'name'      => __( 'Gráfico Presupuesto', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'transparencia_grafico_presupuesto',
                'module'    => 'transparencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 118 2.83"/><path d="M22 12A10 10 0 0012 2v10z"/></svg>',
                'fields'    => array(
                    'tipo_grafico' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo de gráfico', 'flavor-chat-ia' ),
                        'options' => array( 'pie' => __( 'Circular', 'flavor-chat-ia' ), 'bar' => __( 'Barras', 'flavor-chat-ia' ), 'donut' => __( 'Donut', 'flavor-chat-ia' ) ),
                        'default' => 'donut',
                    ),
                    'anio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Año', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Actual', 'flavor-chat-ia' ), '2024' => '2024', '2023' => '2023' ),
                        'default' => '',
                    ),
                    'mostrar_leyenda' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar leyenda', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_valores' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar valores', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'presupuestos-participativos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'presupuestos-listado',
                'name'      => __( 'Proyectos Participativos', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'presupuestos_listado',
                'module'    => 'presupuestos-participativos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17,11 19,13 23,9"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ), 'votacion' => __( 'En votación', 'flavor-chat-ia' ), 'aprobados' => __( 'Aprobados', 'flavor-chat-ia' ), 'ejecucion' => __( 'En ejecución', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 50,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', 'flavor-chat-ia' ),
                        'options' => array( 'votos' => __( 'Más votados', 'flavor-chat-ia' ), 'recientes' => __( 'Más recientes', 'flavor-chat-ia' ), 'presupuesto' => __( 'Presupuesto', 'flavor-chat-ia' ) ),
                        'default' => 'votos',
                    ),
                    'permitir_votar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir votar', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'huella-ecologica', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'huella-ecologica',
                'name'      => __( 'Calculadora Huella', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'huella_ecologica_calculadora',
                'module'    => 'huella-ecologica',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c4-4 8-7.5 8-12a8 8 0 10-16 0c0 4.5 4 8 8 12z"/><circle cx="12" cy="10" r="3"/></svg>',
                'fields'    => array(
                    'modo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Modo', 'flavor-chat-ia' ),
                        'options' => array( 'completo' => __( 'Completo', 'flavor-chat-ia' ), 'rapido' => __( 'Rápido', 'flavor-chat-ia' ), 'comparativo' => __( 'Comparativo', 'flavor-chat-ia' ) ),
                        'default' => 'completo',
                    ),
                    'mostrar_consejos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar consejos', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'guardar_historial' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Guardar historial', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        if ( $this->modulo_activo( 'sello-conciencia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'sello-conciencia',
                'name'      => __( 'Sello de Conciencia', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'sello_conciencia',
                'module'    => 'sello-conciencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21,13.89 7,23 12,20 17,23 15.79,13.88"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'eco' => __( 'Ecológico', 'flavor-chat-ia' ), 'social' => __( 'Social', 'flavor-chat-ia' ), 'comercio_justo' => __( 'Comercio justo', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'badge' => __( 'Insignia', 'flavor-chat-ia' ), 'card' => __( 'Tarjeta', 'flavor-chat-ia' ), 'banner' => __( 'Banner', 'flavor-chat-ia' ) ),
                        'default' => 'badge',
                    ),
                    'mostrar_criterios' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar criterios', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Email marketing
        if ( $this->modulo_activo( 'email-marketing', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'newsletter-form',
                'name'      => __( 'Formulario Newsletter', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'flavor_suscripcion_newsletter',
                'module'    => 'email-marketing',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
                'fields'    => array(
                    'lista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Lista', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Por defecto', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'inline' => __( 'En línea', 'flavor-chat-ia' ), 'vertical' => __( 'Vertical', 'flavor-chat-ia' ), 'card' => __( 'Tarjeta', 'flavor-chat-ia' ) ),
                        'default' => 'inline',
                    ),
                    'mostrar_nombre' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Pedir nombre', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                    'texto_boton' => array(
                        'type'    => 'text',
                        'label'   => __( 'Texto botón', 'flavor-chat-ia' ),
                        'default' => __( 'Suscribirse', 'flavor-chat-ia' ),
                    ),
                ),
            ) );
        }

        // ============ LANDING PAGES ============
        // Bloque para insertar landing pages completas de módulos
        $this->registrar_bloque( array(
            'id'        => 'flavor-landing',
            'name'      => __( 'Landing de Módulo', 'flavor-chat-ia' ),
            'category'  => 'sections',
            'shortcode' => 'flavor_landing',
            'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>',
            'fields'    => array(
                'module' => array(
                    'type'    => 'select',
                    'label'   => __( 'Módulo', 'flavor-chat-ia' ),
                    'options' => array(
                        ''                 => __( '-- Seleccionar módulo --', 'flavor-chat-ia' ),
                        'grupos-consumo'   => __( 'Grupos de Consumo', 'flavor-chat-ia' ),
                        'banco-tiempo'     => __( 'Banco de Tiempo', 'flavor-chat-ia' ),
                        'ayuntamiento'     => __( 'Ayuntamiento', 'flavor-chat-ia' ),
                        'comunidades'      => __( 'Comunidades', 'flavor-chat-ia' ),
                        'espacios-comunes' => __( 'Espacios Comunes', 'flavor-chat-ia' ),
                        'ayuda-vecinal'    => __( 'Ayuda Vecinal', 'flavor-chat-ia' ),
                        'huertos-urbanos'  => __( 'Huertos Urbanos', 'flavor-chat-ia' ),
                        'biblioteca'       => __( 'Biblioteca', 'flavor-chat-ia' ),
                        'cursos'           => __( 'Cursos', 'flavor-chat-ia' ),
                        'eventos'          => __( 'Eventos', 'flavor-chat-ia' ),
                        'marketplace'      => __( 'Marketplace', 'flavor-chat-ia' ),
                        'incidencias'      => __( 'Incidencias', 'flavor-chat-ia' ),
                        'bicicletas'       => __( 'Bicicletas Compartidas', 'flavor-chat-ia' ),
                        'reciclaje'        => __( 'Reciclaje', 'flavor-chat-ia' ),
                        'restaurante'      => __( 'Restaurante', 'flavor-chat-ia' ),
                        'peluqueria'       => __( 'Peluquería', 'flavor-chat-ia' ),
                        'gimnasio'         => __( 'Gimnasio', 'flavor-chat-ia' ),
                        'clinica'          => __( 'Clínica', 'flavor-chat-ia' ),
                        'hotel'            => __( 'Hotel', 'flavor-chat-ia' ),
                        'inmobiliaria'     => __( 'Inmobiliaria', 'flavor-chat-ia' ),
                        'tienda'           => __( 'Tienda', 'flavor-chat-ia' ),
                        'podcast'          => __( 'Podcast', 'flavor-chat-ia' ),
                    ),
                    'default' => '',
                ),
                'color' => array(
                    'type'    => 'color',
                    'label'   => __( 'Color primario', 'flavor-chat-ia' ),
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
                'name'      => __( 'Banner Publicitario', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'flavor_ad',
                'module'    => 'advertising',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="10" rx="2"/><path d="M12 7v10"/></svg>',
                'fields'    => array(
                    'posicion' => array(
                        'type'    => 'select',
                        'label'   => __( 'Posición', 'flavor-chat-ia' ),
                        'options' => array( 'header' => __( 'Cabecera', 'flavor-chat-ia' ), 'sidebar' => __( 'Lateral', 'flavor-chat-ia' ), 'content' => __( 'Contenido', 'flavor-chat-ia' ), 'footer' => __( 'Pie', 'flavor-chat-ia' ) ),
                        'default' => 'content',
                    ),
                    'formato' => array(
                        'type'    => 'select',
                        'label'   => __( 'Formato', 'flavor-chat-ia' ),
                        'options' => array( 'horizontal' => __( 'Horizontal', 'flavor-chat-ia' ), 'vertical' => __( 'Vertical', 'flavor-chat-ia' ), 'cuadrado' => __( 'Cuadrado', 'flavor-chat-ia' ) ),
                        'default' => 'horizontal',
                    ),
                    'rotacion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Rotar anuncios', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'ads-dashboard',
                'name'      => __( 'Dashboard Anunciante', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'flavor_ads_dashboard',
                'module'    => 'advertising',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', 'flavor-chat-ia' ),
                        'options' => array( 'resumen' => __( 'Resumen', 'flavor-chat-ia' ), 'campanas' => __( 'Campañas', 'flavor-chat-ia' ), 'estadisticas' => __( 'Estadísticas', 'flavor-chat-ia' ) ),
                        'default' => 'resumen',
                    ),
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', 'flavor-chat-ia' ),
                        'options' => array( '7d' => __( 'Últimos 7 días', 'flavor-chat-ia' ), '30d' => __( 'Últimos 30 días', 'flavor-chat-ia' ), '90d' => __( 'Últimos 90 días', 'flavor-chat-ia' ) ),
                        'default' => '30d',
                    ),
                ),
            ) );
        }

        // Biblioteca
        if ( $this->modulo_activo( 'biblioteca', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'biblioteca-catalogo',
                'name'      => __( 'Catálogo Biblioteca', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'biblioteca_catalogo',
                'module'    => 'biblioteca',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
                'fields'    => array(
                    'genero' => array(
                        'type'    => 'select',
                        'label'   => __( 'Género', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 48,
                    ),
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', 'flavor-chat-ia' ),
                        'options' => array( '3' => '3', '4' => '4', '6' => '6' ),
                        'default' => '4',
                    ),
                    'mostrar_disponibilidad' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar disponibilidad', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_autor' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar autor', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'biblioteca-mis-prestamos',
                'name'      => __( 'Mis Préstamos', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'biblioteca_mis_prestamos',
                'module'    => 'biblioteca',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ), 'activos' => __( 'Activos', 'flavor-chat-ia' ), 'vencidos' => __( 'Vencidos', 'flavor-chat-ia' ), 'historial' => __( 'Historial', 'flavor-chat-ia' ) ),
                        'default' => 'activos',
                    ),
                    'mostrar_renovar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Botón renovar', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Carpooling
        if ( $this->modulo_activo( 'carpooling', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'carpooling-buscar',
                'name'      => __( 'Buscar Viaje', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'carpooling_buscar_viaje',
                'module'    => 'carpooling',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><circle cx="5" cy="17" r="2"/><circle cx="12" cy="17" r="2"/><path d="M16 8h4l3 5v4h-7"/><circle cx="20" cy="17" r="2"/></svg>',
                'fields'    => array(
                    'mostrar_mapa' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar mapa', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'radio_km' => array(
                        'type'    => 'number',
                        'label'   => __( 'Radio (km)', 'flavor-chat-ia' ),
                        'default' => 50,
                        'min'     => 5,
                        'max'     => 200,
                    ),
                    'resultados' => array(
                        'type'    => 'number',
                        'label'   => __( 'Resultados', 'flavor-chat-ia' ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'carpooling-publicar',
                'name'      => __( 'Publicar Viaje', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'carpooling_publicar_viaje',
                'module'    => 'carpooling',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
                'fields'    => array(
                    'modo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Modo', 'flavor-chat-ia' ),
                        'options' => array( 'completo' => __( 'Completo', 'flavor-chat-ia' ), 'rapido' => __( 'Rápido', 'flavor-chat-ia' ) ),
                        'default' => 'completo',
                    ),
                    'recurrente' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir recurrentes', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Chat grupos
        if ( $this->modulo_activo( 'chat-grupos', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'chat-grupos-lista',
                'name'      => __( 'Lista de Grupos', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'flavor_grupos_lista',
                'module'    => 'chat-grupos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Mostrar', 'flavor-chat-ia' ),
                        'options' => array( 'mis_grupos' => __( 'Mis grupos', 'flavor-chat-ia' ), 'todos' => __( 'Todos', 'flavor-chat-ia' ), 'recientes' => __( 'Recientes', 'flavor-chat-ia' ) ),
                        'default' => 'mis_grupos',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'mostrar_miembros' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar miembros', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'chat-grupos-explorar',
                'name'      => __( 'Explorar Grupos', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'flavor_grupos_explorar',
                'module'    => 'chat-grupos',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 6,
                        'max'     => 48,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', 'flavor-chat-ia' ),
                        'options' => array( 'populares' => __( 'Más populares', 'flavor-chat-ia' ), 'recientes' => __( 'Más recientes', 'flavor-chat-ia' ), 'activos' => __( 'Más activos', 'flavor-chat-ia' ) ),
                        'default' => 'populares',
                    ),
                ),
            ) );
        }

        // Chat interno
        if ( $this->modulo_activo( 'chat-interno', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'chat-inbox',
                'name'      => __( 'Bandeja de Mensajes', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'flavor_chat_inbox',
                'module'    => 'chat-interno',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', 'flavor-chat-ia' ),
                        'options' => array( 'lista' => __( 'Lista', 'flavor-chat-ia' ), 'completa' => __( 'Completa', 'flavor-chat-ia' ), 'compacta' => __( 'Compacta', 'flavor-chat-ia' ) ),
                        'default' => 'lista',
                    ),
                    'mostrar_no_leidos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Destacar no leídos', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'sonido' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Sonido notificaciones', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Círculos de cuidados
        if ( $this->modulo_activo( 'circulos-cuidados', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'circulos-cuidados-lista',
                'name'      => __( 'Círculos de Cuidados', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'circulos_cuidados',
                'module'    => 'circulos-cuidados',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>',
                'fields'    => array(
                    'zona' => array(
                        'type'    => 'select',
                        'label'   => __( 'Zona', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'cercanos' => __( 'Cercanos', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 8,
                        'min'     => 4,
                        'max'     => 24,
                    ),
                    'mostrar_miembros' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar miembros', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'necesidades-cuidados',
                'name'      => __( 'Necesidades de Cuidados', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'necesidades_cuidados',
                'module'    => 'circulos-cuidados',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ), 'mayores' => __( 'Mayores', 'flavor-chat-ia' ), 'infancia' => __( 'Infancia', 'flavor-chat-ia' ), 'diversidad' => __( 'Diversidad funcional', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'urgencia' => array(
                        'type'    => 'select',
                        'label'   => __( 'Urgencia', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'alta' => __( 'Alta', 'flavor-chat-ia' ), 'media' => __( 'Media', 'flavor-chat-ia' ), 'baja' => __( 'Baja', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'mostrar_voluntarios' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar voluntarios', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Comunidades
        if ( $this->modulo_activo( 'comunidades', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'comunidades-listar',
                'name'      => __( 'Listar Comunidades', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'comunidades_listar',
                'module'    => 'comunidades',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'barrio' => __( 'Barrio', 'flavor-chat-ia' ), 'interes' => __( 'Interés', 'flavor-chat-ia' ), 'proyecto' => __( 'Proyecto', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 48,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', 'flavor-chat-ia' ),
                        'options' => array( 'miembros' => __( 'Más miembros', 'flavor-chat-ia' ), 'activas' => __( 'Más activas', 'flavor-chat-ia' ), 'recientes' => __( 'Más recientes', 'flavor-chat-ia' ) ),
                        'default' => 'miembros',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'comunidades-actividad',
                'name'      => __( 'Feed de Actividad', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'comunidades_actividad',
                'module'    => 'comunidades',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg>',
                'fields'    => array(
                    'comunidad' => array(
                        'type'    => 'select',
                        'label'   => __( 'Comunidad', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Publicaciones', 'flavor-chat-ia' ),
                        'default' => 20,
                        'min'     => 5,
                        'max'     => 100,
                    ),
                    'permitir_publicar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir publicar', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Economía suficiencia
        if ( $this->modulo_activo( 'economia-suficiencia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'suficiencia-intro',
                'name'      => __( 'Intro Suficiencia', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'suficiencia_intro',
                'module'    => 'economia-suficiencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 6v6l4 2"/></svg>',
                'fields'    => array(
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'completo' => __( 'Completo', 'flavor-chat-ia' ), 'resumido' => __( 'Resumido', 'flavor-chat-ia' ), 'visual' => __( 'Visual', 'flavor-chat-ia' ) ),
                        'default' => 'completo',
                    ),
                    'mostrar_cta' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar CTA', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'suficiencia-evaluacion',
                'name'      => __( 'Evaluación Personal', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'suficiencia_evaluacion',
                'module'    => 'economia-suficiencia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>',
                'fields'    => array(
                    'modo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Modo', 'flavor-chat-ia' ),
                        'options' => array( 'completo' => __( 'Completo', 'flavor-chat-ia' ), 'rapido' => __( 'Rápido', 'flavor-chat-ia' ) ),
                        'default' => 'completo',
                    ),
                    'guardar_resultados' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Guardar resultados', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_recursos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar recursos', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Empresarial
        if ( $this->modulo_activo( 'empresarial', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'empresarial-servicios',
                'name'      => __( 'Servicios Empresa', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'empresarial_servicios',
                'module'    => 'empresarial',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>',
                'fields'    => array(
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', 'flavor-chat-ia' ),
                        'options' => array( '2' => '2', '3' => '3', '4' => '4' ),
                        'default' => '3',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'cards' => __( 'Tarjetas', 'flavor-chat-ia' ), 'lista' => __( 'Lista', 'flavor-chat-ia' ), 'iconos' => __( 'Iconos', 'flavor-chat-ia' ) ),
                        'default' => 'cards',
                    ),
                    'mostrar_precio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar precio', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'empresarial-equipo',
                'name'      => __( 'Equipo Empresa', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'empresarial_equipo',
                'module'    => 'empresarial',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
                'fields'    => array(
                    'departamento' => array(
                        'type'    => 'select',
                        'label'   => __( 'Departamento', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'columnas' => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', 'flavor-chat-ia' ),
                        'options' => array( '3' => '3', '4' => '4', '5' => '5' ),
                        'default' => '4',
                    ),
                    'mostrar_cargo' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar cargo', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_redes' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar redes', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'empresarial-portfolio',
                'name'      => __( 'Portfolio Empresa', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'empresarial_portfolio',
                'module'    => 'empresarial',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 9,
                        'min'     => 3,
                        'max'     => 24,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'grid' => __( 'Cuadrícula', 'flavor-chat-ia' ), 'masonry' => __( 'Masonry', 'flavor-chat-ia' ), 'carousel' => __( 'Carrusel', 'flavor-chat-ia' ) ),
                        'default' => 'grid',
                    ),
                ),
            ) );
        }

        // Espacios comunes
        if ( $this->modulo_activo( 'espacios-comunes', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'espacios-listado',
                'name'      => __( 'Listado Espacios', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'espacios_listado',
                'module'    => 'espacios-comunes',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ), 'sala' => __( 'Salas', 'flavor-chat-ia' ), 'exterior' => __( 'Exteriores', 'flavor-chat-ia' ), 'deportivo' => __( 'Deportivos', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 24,
                    ),
                    'mostrar_disponibilidad' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar disponibilidad', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'espacios-calendario',
                'name'      => __( 'Calendario Espacios', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'espacios_calendario',
                'module'    => 'espacios-comunes',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'fields'    => array(
                    'espacio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Espacio', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', 'flavor-chat-ia' ),
                        'options' => array( 'mes' => __( 'Mes', 'flavor-chat-ia' ), 'semana' => __( 'Semana', 'flavor-chat-ia' ), 'dia' => __( 'Día', 'flavor-chat-ia' ) ),
                        'default' => 'semana',
                    ),
                    'permitir_reservar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir reservar', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Facturas
        if ( $this->modulo_activo( 'facturas', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'mis-facturas',
                'name'      => __( 'Mis Facturas', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'flavor_mis_facturas',
                'module'    => 'facturas',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'pendientes' => __( 'Pendientes', 'flavor-chat-ia' ), 'pagadas' => __( 'Pagadas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'anio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Año', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ), '2024' => '2024', '2023' => '2023' ),
                        'default' => '',
                    ),
                    'permitir_descarga' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir descarga', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'historial-pagos',
                'name'      => __( 'Historial Pagos', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'flavor_historial_pagos',
                'module'    => 'facturas',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
                'fields'    => array(
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', 'flavor-chat-ia' ),
                        'options' => array( '30d' => __( 'Últimos 30 días', 'flavor-chat-ia' ), '90d' => __( 'Últimos 90 días', 'flavor-chat-ia' ), 'anio' => __( 'Este año', 'flavor-chat-ia' ), 'todo' => __( 'Todo', 'flavor-chat-ia' ) ),
                        'default' => 'anio',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
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
                'name'      => __( 'Info Justicia Restaurativa', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'justicia_restaurativa',
                'module'    => 'justicia-restaurativa',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
                'fields'    => array(
                    'seccion' => array(
                        'type'    => 'select',
                        'label'   => __( 'Sección', 'flavor-chat-ia' ),
                        'options' => array( 'intro' => __( 'Introducción', 'flavor-chat-ia' ), 'proceso' => __( 'Proceso', 'flavor-chat-ia' ), 'faq' => __( 'Preguntas frecuentes', 'flavor-chat-ia' ) ),
                        'default' => 'intro',
                    ),
                    'mostrar_casos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar casos ejemplo', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'solicitar-mediacion',
                'name'      => __( 'Solicitar Mediación', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'solicitar_mediacion',
                'module'    => 'justicia-restaurativa',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', 'flavor-chat-ia' ),
                        'options' => array( 'vecinal' => __( 'Vecinal', 'flavor-chat-ia' ), 'familiar' => __( 'Familiar', 'flavor-chat-ia' ), 'comunitario' => __( 'Comunitario', 'flavor-chat-ia' ) ),
                        'default' => 'vecinal',
                    ),
                    'anonimo' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir anónimo', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Marketplace
        if ( $this->modulo_activo( 'marketplace', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'marketplace-listado',
                'name'      => __( 'Listado Marketplace', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'marketplace_listado',
                'module'    => 'marketplace',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ), 'venta' => __( 'Venta', 'flavor-chat-ia' ), 'busco' => __( 'Busco', 'flavor-chat-ia' ), 'regalo' => __( 'Regalo', 'flavor-chat-ia' ), 'intercambio' => __( 'Intercambio', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 1,
                        'max'     => 48,
                    ),
                    'orden' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', 'flavor-chat-ia' ),
                        'options' => array( 'recent' => __( 'Más recientes', 'flavor-chat-ia' ), 'price_asc' => __( 'Precio menor', 'flavor-chat-ia' ), 'price_desc' => __( 'Precio mayor', 'flavor-chat-ia' ) ),
                        'default' => 'recent',
                    ),
                    'mostrar_precio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar precio', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'marketplace-formulario',
                'name'      => __( 'Publicar Anuncio', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'marketplace_formulario',
                'module'    => 'marketplace',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
                'fields'    => array(
                    'tipo_default' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo por defecto', 'flavor-chat-ia' ),
                        'options' => array( 'venta' => __( 'Venta', 'flavor-chat-ia' ), 'busco' => __( 'Busco', 'flavor-chat-ia' ), 'regalo' => __( 'Regalo', 'flavor-chat-ia' ), 'intercambio' => __( 'Intercambio', 'flavor-chat-ia' ) ),
                        'default' => 'venta',
                    ),
                    'permitir_imagenes' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir imágenes', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'max_imagenes' => array(
                        'type'    => 'number',
                        'label'   => __( 'Máx. imágenes', 'flavor-chat-ia' ),
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
                'name'      => __( 'Propuestas Activas', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'propuestas_activas',
                'module'    => 'participacion',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', 'flavor-chat-ia' ),
                        'options' => array( 'activas' => __( 'Activas', 'flavor-chat-ia' ), 'votacion' => __( 'En votación', 'flavor-chat-ia' ), 'aprobadas' => __( 'Aprobadas', 'flavor-chat-ia' ), 'todas' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => 'activas',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', 'flavor-chat-ia' ),
                        'options' => array( 'votos' => __( 'Más votadas', 'flavor-chat-ia' ), 'recientes' => __( 'Más recientes', 'flavor-chat-ia' ), 'comentarios' => __( 'Más comentadas', 'flavor-chat-ia' ) ),
                        'default' => 'recientes',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'votacion-activa',
                'name'      => __( 'Votación Activa', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'votacion_activa',
                'module'    => 'participacion',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>',
                'fields'    => array(
                    'mostrar_progreso' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar progreso', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_resultados' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar resultados', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                    'permitir_comentarios' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir comentarios', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Podcast
        if ( $this->modulo_activo( 'podcast', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'podcast-player',
                'name'      => __( 'Reproductor Podcast', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'podcast_player',
                'module'    => 'podcast',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path d="M19 10v2a7 7 0 01-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>',
                'fields'    => array(
                    'episodio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Episodio', 'flavor-chat-ia' ),
                        'options' => array( 'ultimo' => __( 'Último', 'flavor-chat-ia' ) ),
                        'default' => 'ultimo',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'completo' => __( 'Completo', 'flavor-chat-ia' ), 'mini' => __( 'Mini', 'flavor-chat-ia' ), 'card' => __( 'Tarjeta', 'flavor-chat-ia' ) ),
                        'default' => 'completo',
                    ),
                    'autoplay' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Autoplay', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'podcast-episodios',
                'name'      => __( 'Lista Episodios', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'podcast_lista_episodios',
                'module'    => 'podcast',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
                'fields'    => array(
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'ordenar' => array(
                        'type'    => 'select',
                        'label'   => __( 'Ordenar por', 'flavor-chat-ia' ),
                        'options' => array( 'recientes' => __( 'Más recientes', 'flavor-chat-ia' ), 'populares' => __( 'Más populares', 'flavor-chat-ia' ) ),
                        'default' => 'recientes',
                    ),
                    'mostrar_duracion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar duración', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Red social
        if ( $this->modulo_activo( 'red-social', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'rs-feed',
                'name'      => __( 'Feed Social', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'rs_feed',
                'module'    => 'red-social',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', 'flavor-chat-ia' ),
                        'options' => array( 'todos' => __( 'Todos', 'flavor-chat-ia' ), 'siguiendo' => __( 'Siguiendo', 'flavor-chat-ia' ), 'populares' => __( 'Populares', 'flavor-chat-ia' ) ),
                        'default' => 'todos',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Publicaciones', 'flavor-chat-ia' ),
                        'default' => 20,
                        'min'     => 5,
                        'max'     => 100,
                    ),
                    'permitir_publicar' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir publicar', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_reacciones' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar reacciones', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'rs-historias',
                'name'      => __( 'Historias', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'rs_historias',
                'module'    => 'red-social',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Mostrar', 'flavor-chat-ia' ),
                        'options' => array( 'todos' => __( 'Todas', 'flavor-chat-ia' ), 'siguiendo' => __( 'Siguiendo', 'flavor-chat-ia' ) ),
                        'default' => 'siguiendo',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 15,
                        'min'     => 5,
                        'max'     => 30,
                    ),
                    'permitir_crear' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir crear', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Reciclaje
        if ( $this->modulo_activo( 'reciclaje', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'reciclaje-puntos',
                'name'      => __( 'Puntos de Reciclaje', 'flavor-chat-ia' ),
                'category'  => 'maps',
                'shortcode' => 'reciclaje_puntos_cercanos',
                'module'    => 'reciclaje',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,4 23,10 17,10"/><polyline points="1,20 1,14 7,14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ), 'contenedor' => __( 'Contenedores', 'flavor-chat-ia' ), 'punto_limpio' => __( 'Puntos limpios', 'flavor-chat-ia' ), 'textil' => __( 'Textil', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'radio_km' => array(
                        'type'    => 'number',
                        'label'   => __( 'Radio (km)', 'flavor-chat-ia' ),
                        'default' => 5,
                        'min'     => 1,
                        'max'     => 50,
                    ),
                    'mostrar_listado' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar listado', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'reciclaje-guia',
                'name'      => __( 'Guía de Reciclaje', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'reciclaje_guia',
                'module'    => 'reciclaje',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'plasticos' => __( 'Plásticos', 'flavor-chat-ia' ), 'vidrio' => __( 'Vidrio', 'flavor-chat-ia' ), 'papel' => __( 'Papel', 'flavor-chat-ia' ), 'organico' => __( 'Orgánico', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'visual' => __( 'Visual', 'flavor-chat-ia' ), 'lista' => __( 'Lista', 'flavor-chat-ia' ), 'buscador' => __( 'Buscador', 'flavor-chat-ia' ) ),
                        'default' => 'visual',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'reciclaje-ranking',
                'name'      => __( 'Ranking Reciclaje', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'reciclaje_ranking',
                'module'    => 'reciclaje',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15l-2 5-3-1 2-4h3z"/><path d="M12 15l2 5 3-1-2-4h-3z"/><circle cx="12" cy="8" r="5"/></svg>',
                'fields'    => array(
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', 'flavor-chat-ia' ),
                        'options' => array( 'semana' => __( 'Esta semana', 'flavor-chat-ia' ), 'mes' => __( 'Este mes', 'flavor-chat-ia' ), 'anio' => __( 'Este año', 'flavor-chat-ia' ), 'total' => __( 'Total', 'flavor-chat-ia' ) ),
                        'default' => 'mes',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Top', 'flavor-chat-ia' ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'mostrar_puntos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar puntos', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Saberes ancestrales
        if ( $this->modulo_activo( 'saberes-ancestrales', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'saberes-catalogo',
                'name'      => __( 'Catálogo Saberes', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'saberes_catalogo',
                'module'    => 'saberes-ancestrales',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ), 'oficios' => __( 'Oficios', 'flavor-chat-ia' ), 'medicina' => __( 'Medicina tradicional', 'flavor-chat-ia' ), 'artesania' => __( 'Artesanía', 'flavor-chat-ia' ), 'cocina' => __( 'Cocina', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 12,
                        'min'     => 4,
                        'max'     => 48,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'cards' => __( 'Tarjetas', 'flavor-chat-ia' ), 'lista' => __( 'Lista', 'flavor-chat-ia' ), 'galeria' => __( 'Galería', 'flavor-chat-ia' ) ),
                        'default' => 'cards',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'saberes-portadores',
                'name'      => __( 'Portadores de Saber', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'saberes_portadores',
                'module'    => 'saberes-ancestrales',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                'fields'    => array(
                    'especialidad' => array(
                        'type'    => 'select',
                        'label'   => __( 'Especialidad', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 8,
                        'min'     => 4,
                        'max'     => 24,
                    ),
                    'mostrar_bio' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar biografía', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Socios
        if ( $this->modulo_activo( 'socios', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'socios-pagar-cuota',
                'name'      => __( 'Pagar Cuota', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'socios_pagar_cuota',
                'module'    => 'socios',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
                'fields'    => array(
                    'mostrar_historial' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar historial', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'metodos_pago' => array(
                        'type'    => 'select',
                        'label'   => __( 'Métodos de pago', 'flavor-chat-ia' ),
                        'options' => array( 'todos' => __( 'Todos', 'flavor-chat-ia' ), 'tarjeta' => __( 'Solo tarjeta', 'flavor-chat-ia' ), 'transferencia' => __( 'Solo transferencia', 'flavor-chat-ia' ) ),
                        'default' => 'todos',
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'socios-perfil',
                'name'      => __( 'Mi Perfil Socio', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'socios_mi_perfil',
                'module'    => 'socios',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                'fields'    => array(
                    'secciones' => array(
                        'type'    => 'select',
                        'label'   => __( 'Secciones', 'flavor-chat-ia' ),
                        'options' => array( 'todas' => __( 'Todas', 'flavor-chat-ia' ), 'basico' => __( 'Solo datos básicos', 'flavor-chat-ia' ) ),
                        'default' => 'todas',
                    ),
                    'editable' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Permitir edición', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'mostrar_carnet' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar carnet', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Talleres
        if ( $this->modulo_activo( 'talleres', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'talleres-proximos',
                'name'      => __( 'Próximos Talleres', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'proximos_talleres',
                'module'    => 'talleres',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 6,
                        'min'     => 3,
                        'max'     => 24,
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'cards' => __( 'Tarjetas', 'flavor-chat-ia' ), 'lista' => __( 'Lista', 'flavor-chat-ia' ), 'timeline' => __( 'Timeline', 'flavor-chat-ia' ) ),
                        'default' => 'cards',
                    ),
                    'mostrar_plazas' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar plazas', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'talleres-calendario',
                'name'      => __( 'Calendario Talleres', 'flavor-chat-ia' ),
                'category'  => 'community',
                'shortcode' => 'calendario_talleres',
                'module'    => 'talleres',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', 'flavor-chat-ia' ),
                        'options' => array( 'mes' => __( 'Mes', 'flavor-chat-ia' ), 'semana' => __( 'Semana', 'flavor-chat-ia' ), 'lista' => __( 'Lista', 'flavor-chat-ia' ) ),
                        'default' => 'mes',
                    ),
                    'mostrar_filtros' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar filtros', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Trabajo digno
        if ( $this->modulo_activo( 'trabajo-digno', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'trabajo-ofertas',
                'name'      => __( 'Ofertas de Trabajo', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'trabajo_digno_ofertas',
                'module'    => 'trabajo-digno',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>',
                'fields'    => array(
                    'tipo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tipo', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ), 'empleo' => __( 'Empleo', 'flavor-chat-ia' ), 'colaboracion' => __( 'Colaboración', 'flavor-chat-ia' ), 'voluntariado' => __( 'Voluntariado', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'sector' => array(
                        'type'    => 'select',
                        'label'   => __( 'Sector', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 10,
                        'min'     => 5,
                        'max'     => 50,
                    ),
                    'mostrar_salario' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar salario', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'trabajo-formacion',
                'name'      => __( 'Formación Laboral', 'flavor-chat-ia' ),
                'category'  => 'economy',
                'shortcode' => 'trabajo_digno_formacion',
                'module'    => 'trabajo-digno',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
                'fields'    => array(
                    'area' => array(
                        'type'    => 'select',
                        'label'   => __( 'Área', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 8,
                        'min'     => 4,
                        'max'     => 24,
                    ),
                    'solo_gratuitos' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Solo gratuitos', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                ),
            ) );
        }

        // Trading IA
        if ( $this->modulo_activo( 'trading-ia', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'trading-dashboard',
                'name'      => __( 'Dashboard Trading', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'trading_ia_dashboard',
                'module'    => 'trading-ia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,7 13.5,15.5 8.5,10.5 2,17"/><polyline points="16,7 22,7 22,13"/></svg>',
                'fields'    => array(
                    'vista' => array(
                        'type'    => 'select',
                        'label'   => __( 'Vista', 'flavor-chat-ia' ),
                        'options' => array( 'resumen' => __( 'Resumen', 'flavor-chat-ia' ), 'detalle' => __( 'Detalle', 'flavor-chat-ia' ), 'grafico' => __( 'Gráfico', 'flavor-chat-ia' ) ),
                        'default' => 'resumen',
                    ),
                    'periodo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Período', 'flavor-chat-ia' ),
                        'options' => array( '24h' => __( '24 horas', 'flavor-chat-ia' ), '7d' => __( '7 días', 'flavor-chat-ia' ), '30d' => __( '30 días', 'flavor-chat-ia' ) ),
                        'default' => '24h',
                    ),
                    'actualizar_auto' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Actualización automática', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'trading-widget',
                'name'      => __( 'Widget Precio', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'trading_ia_widget_precio',
                'module'    => 'trading-ia',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 100 4h4a2 2 0 110 4H8"/><path d="M12 6v2m0 8v2"/></svg>',
                'fields'    => array(
                    'moneda' => array(
                        'type'    => 'select',
                        'label'   => __( 'Moneda', 'flavor-chat-ia' ),
                        'options' => array( 'btc' => 'Bitcoin (BTC)', 'eth' => 'Ethereum (ETH)', 'eur' => 'Euro (EUR)' ),
                        'default' => 'btc',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'mini' => __( 'Mini', 'flavor-chat-ia' ), 'normal' => __( 'Normal', 'flavor-chat-ia' ), 'completo' => __( 'Completo', 'flavor-chat-ia' ) ),
                        'default' => 'normal',
                    ),
                    'mostrar_variacion' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar variación', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
        }

        // Trámites
        if ( $this->modulo_activo( 'tramites', $modulos_activos ) ) {
            $this->registrar_bloque( array(
                'id'        => 'tramites-catalogo',
                'name'      => __( 'Catálogo Trámites', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'catalogo_tramites',
                'module'    => 'tramites',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                'fields'    => array(
                    'categoria' => array(
                        'type'    => 'select',
                        'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todas', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array( 'cards' => __( 'Tarjetas', 'flavor-chat-ia' ), 'lista' => __( 'Lista', 'flavor-chat-ia' ), 'acordeon' => __( 'Acordeón', 'flavor-chat-ia' ) ),
                        'default' => 'cards',
                    ),
                    'mostrar_buscador' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar buscador', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                ),
            ) );
            $this->registrar_bloque( array(
                'id'        => 'mis-expedientes',
                'name'      => __( 'Mis Expedientes', 'flavor-chat-ia' ),
                'category'  => 'modules',
                'shortcode' => 'mis_expedientes',
                'module'    => 'tramites',
                'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>',
                'fields'    => array(
                    'estado' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estado', 'flavor-chat-ia' ),
                        'options' => array( '' => __( 'Todos', 'flavor-chat-ia' ), 'en_curso' => __( 'En curso', 'flavor-chat-ia' ), 'pendiente' => __( 'Pendiente doc.', 'flavor-chat-ia' ), 'finalizado' => __( 'Finalizados', 'flavor-chat-ia' ) ),
                        'default' => '',
                    ),
                    'limite' => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 20,
                        'min'     => 5,
                        'max'     => 100,
                    ),
                    'mostrar_seguimiento' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar seguimiento', 'flavor-chat-ia' ),
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
                'label'   => __( 'Animación de entrada', 'flavor-chat-ia' ),
                'group'   => 'animacion',
                'options' => array(
                    'none'       => __( 'Ninguna', 'flavor-chat-ia' ),
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
                'label'   => __( 'Duración', 'flavor-chat-ia' ),
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
                'label'   => __( 'Retraso', 'flavor-chat-ia' ),
                'group'   => 'animacion',
                'options' => array(
                    '0'    => __( 'Sin retraso', 'flavor-chat-ia' ),
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
                'label'   => __( 'Disparador', 'flavor-chat-ia' ),
                'group'   => 'animacion',
                'options' => array(
                    'viewport' => __( 'Al entrar en viewport', 'flavor-chat-ia' ),
                    'load'     => __( 'Al cargar página', 'flavor-chat-ia' ),
                    'hover'    => __( 'Al pasar el mouse', 'flavor-chat-ia' ),
                    'click'    => __( 'Al hacer click', 'flavor-chat-ia' ),
                ),
                'default' => 'viewport',
            ),

            // === RESPONSIVE ===
            '_ocultar_en' => array(
                'type'    => 'multiselect',
                'label'   => __( 'Ocultar en', 'flavor-chat-ia' ),
                'group'   => 'responsive',
                'options' => array(
                    'mobile'  => __( 'Móvil', 'flavor-chat-ia' ),
                    'tablet'  => __( 'Tablet', 'flavor-chat-ia' ),
                    'desktop' => __( 'Escritorio', 'flavor-chat-ia' ),
                ),
            ),
            '_orden_mobile' => array(
                'type'    => 'number',
                'label'   => __( 'Orden en móvil', 'flavor-chat-ia' ),
                'group'   => 'responsive',
                'min'     => -10,
                'max'     => 100,
                'default' => 0,
            ),
            '_padding_mobile' => array(
                'type'    => 'spacing',
                'label'   => __( 'Padding en móvil', 'flavor-chat-ia' ),
                'group'   => 'responsive',
            ),
            '_margin_mobile' => array(
                'type'    => 'spacing',
                'label'   => __( 'Margen en móvil', 'flavor-chat-ia' ),
                'group'   => 'responsive',
            ),

            // === AVANZADO ===
            '_css_id' => array(
                'type'  => 'text',
                'label' => __( 'ID CSS', 'flavor-chat-ia' ),
                'group' => 'avanzado',
            ),
            '_css_classes' => array(
                'type'  => 'text',
                'label' => __( 'Clases CSS adicionales', 'flavor-chat-ia' ),
                'group' => 'avanzado',
            ),
            '_custom_css' => array(
                'type'     => 'code',
                'label'    => __( 'CSS personalizado', 'flavor-chat-ia' ),
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
                   '<div class="vbp-module-preview-badge">' . esc_html__( 'Preview', 'flavor-chat-ia' ) . ' - ' . esc_html( $nombre_bloque ) . '</div>' .
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
        $widget_options = array( '' => __( 'Seleccionar widget...', 'flavor-chat-ia' ) );
        foreach ( $all_widgets as $widget_id => $widget_data ) {
            $config = $widget_data['config'];
            $cat_id = $config['category'] ?? 'sistema';
            $cat_label = isset( $categories[ $cat_id ]['label'] ) ? $categories[ $cat_id ]['label'] : $cat_id;
            $widget_options[ $widget_id ] = sprintf( '[%s] %s', $cat_label, $config['title'] ?? $widget_id );
        }

        // Bloque principal: Widget Individual
        $this->registrar_bloque( array(
            'id'        => 'dashboard-widget',
            'name'      => __( 'Widget Dashboard', 'flavor-chat-ia' ),
            'category'  => 'dashboard',
            'shortcode' => 'flavor_widget',
            'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="4" height="6" fill="currentColor" opacity="0.2"/><rect x="13" y="7" width="4" height="10" fill="currentColor" opacity="0.2"/></svg>',
            'fields'    => array(
                'id' => array(
                    'type'    => 'select',
                    'label'   => __( 'Widget', 'flavor-chat-ia' ),
                    'options' => $widget_options,
                    'default' => '',
                ),
                'titulo' => array(
                    'type'    => 'text',
                    'label'   => __( 'Título personalizado', 'flavor-chat-ia' ),
                    'default' => '',
                    'placeholder' => __( 'Dejar vacío para usar el del widget', 'flavor-chat-ia' ),
                ),
                'titulo_visible' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar título', 'flavor-chat-ia' ),
                    'default' => true,
                ),
                'estilo' => array(
                    'type'    => 'select',
                    'label'   => __( 'Estilo visual', 'flavor-chat-ia' ),
                    'options' => array(
                        'elevated' => __( 'Elevado (sombra)', 'flavor-chat-ia' ),
                        'outlined' => __( 'Con borde', 'flavor-chat-ia' ),
                        'flat'     => __( 'Plano', 'flavor-chat-ia' ),
                        'glass'    => __( 'Glassmorphism', 'flavor-chat-ia' ),
                    ),
                    'default' => 'elevated',
                ),
                'animacion' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Animación hover', 'flavor-chat-ia' ),
                    'default' => true,
                ),
                'acciones' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar acciones', 'flavor-chat-ia' ),
                    'default' => true,
                ),
            ),
        ) );

        // Bloque: Grid de Múltiples Widgets
        $this->registrar_bloque( array(
            'id'        => 'dashboard-widgets-grid',
            'name'      => __( 'Grid de Widgets', 'flavor-chat-ia' ),
            'category'  => 'dashboard',
            'shortcode' => 'flavor_widgets',
            'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
            'fields'    => array(
                'ids' => array(
                    'type'    => 'text',
                    'label'   => __( 'IDs de widgets', 'flavor-chat-ia' ),
                    'default' => '',
                    'placeholder' => __( 'eventos,reservas,socios', 'flavor-chat-ia' ),
                    'description' => __( 'Separar IDs con comas', 'flavor-chat-ia' ),
                ),
                'columnas' => array(
                    'type'    => 'select',
                    'label'   => __( 'Columnas', 'flavor-chat-ia' ),
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
                    'label'   => __( 'Espaciado', 'flavor-chat-ia' ),
                    'options' => array(
                        'compact'     => __( 'Compacto', 'flavor-chat-ia' ),
                        'normal'      => __( 'Normal', 'flavor-chat-ia' ),
                        'comfortable' => __( 'Espacioso', 'flavor-chat-ia' ),
                    ),
                    'default' => 'normal',
                ),
                'estilo' => array(
                    'type'    => 'select',
                    'label'   => __( 'Estilo visual', 'flavor-chat-ia' ),
                    'options' => array(
                        'elevated' => __( 'Elevado', 'flavor-chat-ia' ),
                        'outlined' => __( 'Con borde', 'flavor-chat-ia' ),
                        'flat'     => __( 'Plano', 'flavor-chat-ia' ),
                        'glass'    => __( 'Glass', 'flavor-chat-ia' ),
                    ),
                    'default' => 'elevated',
                ),
            ),
        ) );

        // Bloque: Widgets por Categoría
        $categoria_options = array( '' => __( 'Seleccionar categoría...', 'flavor-chat-ia' ) );
        foreach ( $categories as $cat_id => $cat_info ) {
            $categoria_options[ $cat_id ] = $cat_info['label'];
        }

        $this->registrar_bloque( array(
            'id'        => 'dashboard-widgets-category',
            'name'      => __( 'Widgets por Categoría', 'flavor-chat-ia' ),
            'category'  => 'dashboard',
            'shortcode' => 'flavor_widgets_categoria',
            'icon'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3H6a2 2 0 00-2 2v14c0 1.1.9 2 2 2h12a2 2 0 002-2V9l-6-6z"/><path d="M14 3v6h6"/></svg>',
            'fields'    => array(
                'categoria' => array(
                    'type'    => 'select',
                    'label'   => __( 'Categoría', 'flavor-chat-ia' ),
                    'options' => $categoria_options,
                    'default' => '',
                ),
                'limite' => array(
                    'type'    => 'number',
                    'label'   => __( 'Límite', 'flavor-chat-ia' ),
                    'default' => 4,
                    'min'     => 1,
                    'max'     => 12,
                ),
                'columnas' => array(
                    'type'    => 'select',
                    'label'   => __( 'Columnas', 'flavor-chat-ia' ),
                    'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
                    'default' => '2',
                ),
                'titulo' => array(
                    'type'    => 'toggle',
                    'label'   => __( 'Mostrar título de categoría', 'flavor-chat-ia' ),
                    'default' => true,
                ),
            ),
        ) );

        // Registrar widgets individuales más populares como bloques directos
        $widgets_populares = array(
            'eventos'     => array( 'name' => __( 'Widget: Eventos', 'flavor-chat-ia' ), 'icon' => 'dashicons-calendar-alt' ),
            'reservas'    => array( 'name' => __( 'Widget: Reservas', 'flavor-chat-ia' ), 'icon' => 'dashicons-tickets-alt' ),
            'socios'      => array( 'name' => __( 'Widget: Miembros', 'flavor-chat-ia' ), 'icon' => 'dashicons-id-alt' ),
            'comunidades' => array( 'name' => __( 'Widget: Comunidades', 'flavor-chat-ia' ), 'icon' => 'dashicons-groups' ),
            'foros'       => array( 'name' => __( 'Widget: Foros', 'flavor-chat-ia' ), 'icon' => 'dashicons-format-chat' ),
            'marketplace' => array( 'name' => __( 'Widget: Marketplace', 'flavor-chat-ia' ), 'icon' => 'dashicons-store' ),
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
                        'label'   => __( 'Título personalizado', 'flavor-chat-ia' ),
                        'default' => '',
                    ),
                    'estilo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array(
                            'elevated' => __( 'Elevado', 'flavor-chat-ia' ),
                            'outlined' => __( 'Con borde', 'flavor-chat-ia' ),
                            'flat'     => __( 'Plano', 'flavor-chat-ia' ),
                            'glass'    => __( 'Glass', 'flavor-chat-ia' ),
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
                    <span class="vbp-module-placeholder-module">' . sprintf( esc_html__( 'Módulo "%s"', 'flavor-chat-ia' ), esc_html( $nombre_modulo ) ) . '</span>
                </div>
                <span class="vbp-module-placeholder-badge">' . esc_html__( 'Preview', 'flavor-chat-ia' ) . '</span>
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

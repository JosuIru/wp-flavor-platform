<?php
/**
 * Visual Builder Pro - Asset Manager
 *
 * Gestor de medios centralizado para VBP.
 * Integra WordPress Media Library, favoritos, colecciones y Unsplash.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestión de assets en VBP
 *
 * @since 2.3.0
 */
class Flavor_VBP_Asset_Manager {

    /**
     * Namespace de la API
     *
     * @var string
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * Meta key para favoritos de usuario
     *
     * @var string
     */
    const META_FAVORITES = '_vbp_asset_favorites';

    /**
     * Option key para colecciones globales
     *
     * @var string
     */
    const OPTION_COLLECTIONS = 'vbp_asset_collections';

    /**
     * Tipos de asset soportados
     *
     * @var array
     */
    const ASSET_TYPES = array(
        'images' => array(
            'mime_types' => array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ),
            'label'      => 'Imagenes',
            'icon'       => 'format-image',
        ),
        'svgs'   => array(
            'mime_types' => array( 'image/svg+xml' ),
            'label'      => 'SVGs',
            'icon'       => 'editor-code',
        ),
        'videos' => array(
            'mime_types' => array( 'video/mp4', 'video/webm', 'video/ogg' ),
            'label'      => 'Videos',
            'icon'       => 'video-alt3',
        ),
        'icons'  => array(
            'type'  => 'builtin',
            'label' => 'Iconos',
            'icon'  => 'star-filled',
        ),
    );

    /**
     * Iconos integrados organizados por categoria
     *
     * @var array
     */
    private $builtin_icons = array();

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Asset_Manager|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Asset_Manager
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
        $this->cargar_iconos_integrados();
        add_action( 'rest_api_init', array( $this, 'registrar_rutas_rest' ) );
    }

    /**
     * Carga los iconos integrados
     */
    private function cargar_iconos_integrados() {
        $this->builtin_icons = array(
            'arrows' => array(
                'label' => __( 'Flechas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icons' => array(
                    'arrow-up'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>',
                    'arrow-down'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>',
                    'arrow-left'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>',
                    'arrow-right'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
                    'chevron-up'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 15l-6-6-6 6"/></svg>',
                    'chevron-down'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>',
                    'chevron-left'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>',
                    'chevron-right'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>',
                    'double-arrow-up'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 11l-6-6-6 6M18 17l-6-6-6 6"/></svg>',
                    'double-arrow-down' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 7l6 6 6-6M6 13l6 6 6-6"/></svg>',
                ),
            ),
            'social' => array(
                'label' => __( 'Redes Sociales', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icons' => array(
                    'facebook'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                    'twitter'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
                    'instagram'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
                    'linkedin'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                    'youtube'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
                    'tiktok'     => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
                    'whatsapp'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
                    'telegram'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
                    'pinterest'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0a12 12 0 0 0-4.373 23.178c-.07-.633-.134-1.606.028-2.298.146-.625.938-3.977.938-3.977s-.239-.479-.239-1.187c0-1.113.645-1.943 1.448-1.943.683 0 1.012.512 1.012 1.127 0 .686-.437 1.713-.663 2.664-.189.796.4 1.446 1.185 1.446 1.422 0 2.515-1.5 2.515-3.664 0-1.915-1.377-3.254-3.342-3.254-2.276 0-3.612 1.707-3.612 3.471 0 .688.265 1.425.595 1.826a.24.24 0 0 1 .056.23c-.061.252-.196.796-.222.907-.035.146-.116.177-.268.107-1-.465-1.624-1.926-1.624-3.1 0-2.523 1.834-4.84 5.286-4.84 2.775 0 4.932 1.977 4.932 4.62 0 2.757-1.739 4.976-4.151 4.976-.811 0-1.573-.421-1.834-.919l-.498 1.902c-.181.695-.669 1.566-.995 2.097A12 12 0 1 0 12 0z"/></svg>',
                ),
            ),
            'ui' => array(
                'label' => __( 'Interfaz', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icons' => array(
                    'menu'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>',
                    'close'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
                    'check'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
                    'plus'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
                    'minus'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/></svg>',
                    'search'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>',
                    'settings'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
                    'user'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                    'heart'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
                    'heart-filled' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
                    'star'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                    'star-filled'  => '<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                    'mail'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
                    'phone'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
                    'location'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
                    'calendar'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                    'clock'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
                    'download'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
                    'upload'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
                    'share'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
                    'link'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
                    'external-link' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
                    'copy'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
                    'trash'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
                    'edit'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
                    'eye'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
                    'eye-off'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
                    'lock'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
                    'unlock'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>',
                    'info'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
                    'alert-circle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
                    'alert-triangle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                    'check-circle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
                    'x-circle'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
                ),
            ),
            'commerce' => array(
                'label' => __( 'Comercio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icons' => array(
                    'shopping-cart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
                    'shopping-bag'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
                    'credit-card'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
                    'tag'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
                    'percent'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
                    'gift'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
                    'truck'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
                    'package'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
                ),
            ),
            'media' => array(
                'label' => __( 'Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icons' => array(
                    'image'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
                    'video'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>',
                    'camera'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
                    'play'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
                    'pause'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>',
                    'volume'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>',
                    'volume-x' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>',
                    'mic'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>',
                    'headphones' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>',
                ),
            ),
            'nature' => array(
                'label' => __( 'Naturaleza', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icons' => array(
                    'sun'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
                    'moon'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
                    'cloud'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
                    'droplet'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>',
                    'wind'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"/></svg>',
                    'leaf'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>',
                    'tree'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V2"/><path d="M5 9l7-7 7 7"/><path d="M7 14l5-5 5 5"/><path d="M9 19l3-3 3 3"/></svg>',
                    'flower'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 7.5a4.5 4.5 0 1 1 4.5 4.5M12 7.5A4.5 4.5 0 1 0 7.5 12M12 7.5V9m-4.5 3a4.5 4.5 0 1 0 4.5 4.5M7.5 12H9m7.5 0a4.5 4.5 0 1 1-4.5 4.5m4.5-4.5H15m-3 4.5V15"/><circle cx="12" cy="12" r="3"/><path d="m8 22 4-10 4 10"/></svg>',
                ),
            ),
        );
    }

    /**
     * Registra las rutas REST API
     */
    public function registrar_rutas_rest() {
        // Listar assets
        register_rest_route(
            self::NAMESPACE,
            '/assets',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'listar_assets' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
                'args'                => array(
                    'type'    => array(
                        'default'           => 'images',
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'page'    => array(
                        'default'           => 1,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default'           => 24,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'search'  => array(
                        'default'           => '',
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'collection' => array(
                        'default'           => '',
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'favorites' => array(
                        'default'           => false,
                        'type'              => 'boolean',
                    ),
                ),
            )
        );

        // Subir asset
        register_rest_route(
            self::NAMESPACE,
            '/assets/upload',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'subir_asset' ),
                'permission_callback' => array( $this, 'verificar_permiso_upload' ),
            )
        );

        // Obtener favoritos
        register_rest_route(
            self::NAMESPACE,
            '/assets/favorites',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'obtener_favoritos' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'toggle_favorito' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                    'args'                => array(
                        'asset_id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );

        // Colecciones
        register_rest_route(
            self::NAMESPACE,
            '/assets/collections',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'listar_colecciones' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'crear_coleccion' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                    'args'                => array(
                        'name' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'description' => array(
                            'default'           => '',
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_textarea_field',
                        ),
                    ),
                ),
            )
        );

        // Operaciones en coleccion especifica
        register_rest_route(
            self::NAMESPACE,
            '/assets/collections/(?P<id>[a-zA-Z0-9_-]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'obtener_coleccion' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'actualizar_coleccion' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'eliminar_coleccion' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
            )
        );

        // Agregar/quitar asset de coleccion
        register_rest_route(
            self::NAMESPACE,
            '/assets/collections/(?P<id>[a-zA-Z0-9_-]+)/assets',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'toggle_asset_coleccion' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
                'args'                => array(
                    'asset_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Iconos integrados
        register_rest_route(
            self::NAMESPACE,
            '/assets/icons',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_iconos' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
                'args'                => array(
                    'category' => array(
                        'default'           => '',
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'search'   => array(
                        'default'           => '',
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );
    }

    /**
     * Verifica permiso de acceso
     *
     * @return bool
     */
    public function verificar_permiso() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verifica permiso de upload
     *
     * @return bool
     */
    public function verificar_permiso_upload() {
        return current_user_can( 'upload_files' );
    }

    /**
     * Lista assets de la Media Library
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function listar_assets( $request ) {
        $tipo         = $request->get_param( 'type' );
        $pagina       = $request->get_param( 'page' );
        $por_pagina   = min( 50, $request->get_param( 'per_page' ) );
        $busqueda     = $request->get_param( 'search' );
        $coleccion_id = $request->get_param( 'collection' );
        $solo_favoritos = $request->get_param( 'favorites' );

        // Obtener config del tipo
        $tipo_config = isset( self::ASSET_TYPES[ $tipo ] ) ? self::ASSET_TYPES[ $tipo ] : null;

        if ( ! $tipo_config ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Tipo de asset no valido', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                400
            );
        }

        // Si es tipo builtin (iconos), devolver iconos
        if ( isset( $tipo_config['type'] ) && 'builtin' === $tipo_config['type'] ) {
            return $this->obtener_iconos( $request );
        }

        // Construir query para Media Library
        $argumentos_query = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $por_pagina,
            'paged'          => $pagina,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        // Filtrar por tipo MIME
        if ( ! empty( $tipo_config['mime_types'] ) ) {
            $argumentos_query['post_mime_type'] = $tipo_config['mime_types'];
        }

        // Busqueda
        if ( ! empty( $busqueda ) ) {
            $argumentos_query['s'] = $busqueda;
        }

        // Filtrar por coleccion
        if ( ! empty( $coleccion_id ) ) {
            $colecciones = $this->obtener_colecciones_usuario();
            if ( isset( $colecciones[ $coleccion_id ] ) ) {
                $asset_ids = $colecciones[ $coleccion_id ]['assets'] ?? array();
                if ( ! empty( $asset_ids ) ) {
                    $argumentos_query['post__in'] = $asset_ids;
                } else {
                    // Coleccion vacia
                    return new WP_REST_Response(
                        array(
                            'assets'     => array(),
                            'total'      => 0,
                            'totalPages' => 0,
                            'page'       => $pagina,
                        ),
                        200
                    );
                }
            }
        }

        // Solo favoritos
        if ( $solo_favoritos ) {
            $favoritos = $this->obtener_favoritos_usuario();
            if ( ! empty( $favoritos ) ) {
                if ( isset( $argumentos_query['post__in'] ) ) {
                    $argumentos_query['post__in'] = array_intersect( $argumentos_query['post__in'], $favoritos );
                } else {
                    $argumentos_query['post__in'] = $favoritos;
                }

                if ( empty( $argumentos_query['post__in'] ) ) {
                    return new WP_REST_Response(
                        array(
                            'assets'     => array(),
                            'total'      => 0,
                            'totalPages' => 0,
                            'page'       => $pagina,
                        ),
                        200
                    );
                }
            } else {
                return new WP_REST_Response(
                    array(
                        'assets'     => array(),
                        'total'      => 0,
                        'totalPages' => 0,
                        'page'       => $pagina,
                    ),
                    200
                );
            }
        }

        $query = new WP_Query( $argumentos_query );
        $assets = array();
        $favoritos = $this->obtener_favoritos_usuario();

        foreach ( $query->posts as $attachment ) {
            $assets[] = $this->formatear_asset( $attachment, $favoritos );
        }

        return new WP_REST_Response(
            array(
                'assets'     => $assets,
                'total'      => $query->found_posts,
                'totalPages' => $query->max_num_pages,
                'page'       => $pagina,
            ),
            200
        );
    }

    /**
     * Formatea un attachment para la respuesta
     *
     * @param WP_Post $attachment   Objeto attachment.
     * @param array   $favoritos    IDs de favoritos del usuario.
     * @return array
     */
    private function formatear_asset( $attachment, $favoritos = array() ) {
        $url = wp_get_attachment_url( $attachment->ID );
        $metadata = wp_get_attachment_metadata( $attachment->ID );

        $asset = array(
            'id'          => $attachment->ID,
            'title'       => $attachment->post_title,
            'filename'    => basename( get_attached_file( $attachment->ID ) ),
            'url'         => $url,
            'mime'        => $attachment->post_mime_type,
            'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
            'caption'     => $attachment->post_excerpt,
            'date'        => $attachment->post_date,
            'isFavorite'  => in_array( $attachment->ID, $favoritos, true ),
        );

        // Dimensiones para imagenes
        if ( ! empty( $metadata['width'] ) && ! empty( $metadata['height'] ) ) {
            $asset['width']  = $metadata['width'];
            $asset['height'] = $metadata['height'];
        }

        // Thumbnail para imagenes
        if ( strpos( $attachment->post_mime_type, 'image/' ) === 0 ) {
            $thumbnail = wp_get_attachment_image_src( $attachment->ID, 'thumbnail' );
            if ( $thumbnail ) {
                $asset['thumbnail'] = $thumbnail[0];
            }

            $medium = wp_get_attachment_image_src( $attachment->ID, 'medium' );
            if ( $medium ) {
                $asset['medium'] = $medium[0];
            }
        }

        // Duracion para videos
        if ( strpos( $attachment->post_mime_type, 'video/' ) === 0 && ! empty( $metadata['length_formatted'] ) ) {
            $asset['duration'] = $metadata['length_formatted'];
        }

        // Tamano de archivo
        $file_path = get_attached_file( $attachment->ID );
        if ( file_exists( $file_path ) ) {
            $asset['filesize'] = size_format( filesize( $file_path ) );
        }

        return $asset;
    }

    /**
     * Sube un archivo a la Media Library
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function subir_asset( $request ) {
        // Verificar que hay archivos
        $archivos = $request->get_file_params();

        if ( empty( $archivos['file'] ) ) {
            return new WP_REST_Response(
                array( 'error' => __( 'No se proporciono archivo', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                400
            );
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Intentar subir el archivo
        $attachment_id = media_handle_upload( 'file', 0 );

        if ( is_wp_error( $attachment_id ) ) {
            return new WP_REST_Response(
                array( 'error' => $attachment_id->get_error_message() ),
                500
            );
        }

        $attachment = get_post( $attachment_id );

        return new WP_REST_Response(
            array(
                'success' => true,
                'asset'   => $this->formatear_asset( $attachment ),
            ),
            200
        );
    }

    /**
     * Obtiene los favoritos del usuario
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function obtener_favoritos( $request ) {
        $favoritos_ids = $this->obtener_favoritos_usuario();

        if ( empty( $favoritos_ids ) ) {
            return new WP_REST_Response(
                array( 'favorites' => array() ),
                200
            );
        }

        $argumentos_query = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'post__in'       => $favoritos_ids,
            'orderby'        => 'post__in',
        );

        $query = new WP_Query( $argumentos_query );
        $assets = array();

        foreach ( $query->posts as $attachment ) {
            $assets[] = $this->formatear_asset( $attachment, $favoritos_ids );
        }

        return new WP_REST_Response(
            array( 'favorites' => $assets ),
            200
        );
    }

    /**
     * Alterna el estado de favorito de un asset
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function toggle_favorito( $request ) {
        $asset_id = $request->get_param( 'asset_id' );
        $user_id = get_current_user_id();

        // Verificar que el attachment existe
        $attachment = get_post( $asset_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Asset no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        $favoritos = $this->obtener_favoritos_usuario();
        $es_favorito = in_array( $asset_id, $favoritos, true );

        if ( $es_favorito ) {
            // Quitar de favoritos
            $favoritos = array_diff( $favoritos, array( $asset_id ) );
            $es_favorito = false;
        } else {
            // Agregar a favoritos
            $favoritos[] = $asset_id;
            $es_favorito = true;
        }

        update_user_meta( $user_id, self::META_FAVORITES, array_values( $favoritos ) );

        return new WP_REST_Response(
            array(
                'success'    => true,
                'isFavorite' => $es_favorito,
                'asset_id'   => $asset_id,
            ),
            200
        );
    }

    /**
     * Obtiene los IDs de favoritos del usuario actual
     *
     * @return array
     */
    private function obtener_favoritos_usuario() {
        $user_id = get_current_user_id();
        $favoritos = get_user_meta( $user_id, self::META_FAVORITES, true );

        return is_array( $favoritos ) ? $favoritos : array();
    }

    /**
     * Lista las colecciones del usuario
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function listar_colecciones( $request ) {
        $colecciones = $this->obtener_colecciones_usuario();

        $resultado = array();
        foreach ( $colecciones as $id => $coleccion ) {
            $resultado[] = array(
                'id'          => $id,
                'name'        => $coleccion['name'],
                'description' => $coleccion['description'] ?? '',
                'assetCount'  => count( $coleccion['assets'] ?? array() ),
                'created'     => $coleccion['created'] ?? '',
            );
        }

        return new WP_REST_Response(
            array( 'collections' => $resultado ),
            200
        );
    }

    /**
     * Crea una nueva coleccion
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function crear_coleccion( $request ) {
        $nombre = $request->get_param( 'name' );
        $descripcion = $request->get_param( 'description' );

        $colecciones = $this->obtener_colecciones_usuario();

        // Generar ID unico
        $id = sanitize_title( $nombre ) . '_' . wp_generate_password( 6, false );

        $colecciones[ $id ] = array(
            'name'        => $nombre,
            'description' => $descripcion,
            'assets'      => array(),
            'created'     => current_time( 'mysql' ),
        );

        $this->guardar_colecciones_usuario( $colecciones );

        return new WP_REST_Response(
            array(
                'success'    => true,
                'collection' => array(
                    'id'          => $id,
                    'name'        => $nombre,
                    'description' => $descripcion,
                    'assetCount'  => 0,
                    'created'     => $colecciones[ $id ]['created'],
                ),
            ),
            200
        );
    }

    /**
     * Obtiene una coleccion especifica
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function obtener_coleccion( $request ) {
        $id = $request->get_param( 'id' );
        $colecciones = $this->obtener_colecciones_usuario();

        if ( ! isset( $colecciones[ $id ] ) ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Coleccion no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        $coleccion = $colecciones[ $id ];

        // Obtener los assets de la coleccion
        $assets = array();
        if ( ! empty( $coleccion['assets'] ) ) {
            $argumentos_query = array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => -1,
                'post__in'       => $coleccion['assets'],
                'orderby'        => 'post__in',
            );

            $query = new WP_Query( $argumentos_query );
            $favoritos = $this->obtener_favoritos_usuario();

            foreach ( $query->posts as $attachment ) {
                $assets[] = $this->formatear_asset( $attachment, $favoritos );
            }
        }

        return new WP_REST_Response(
            array(
                'collection' => array(
                    'id'          => $id,
                    'name'        => $coleccion['name'],
                    'description' => $coleccion['description'] ?? '',
                    'assetCount'  => count( $coleccion['assets'] ?? array() ),
                    'created'     => $coleccion['created'] ?? '',
                    'assets'      => $assets,
                ),
            ),
            200
        );
    }

    /**
     * Actualiza una coleccion
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function actualizar_coleccion( $request ) {
        $id = $request->get_param( 'id' );
        $colecciones = $this->obtener_colecciones_usuario();

        if ( ! isset( $colecciones[ $id ] ) ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Coleccion no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        $body = $request->get_json_params();

        if ( isset( $body['name'] ) ) {
            $colecciones[ $id ]['name'] = sanitize_text_field( $body['name'] );
        }

        if ( isset( $body['description'] ) ) {
            $colecciones[ $id ]['description'] = sanitize_textarea_field( $body['description'] );
        }

        $this->guardar_colecciones_usuario( $colecciones );

        return new WP_REST_Response(
            array(
                'success'    => true,
                'collection' => array(
                    'id'          => $id,
                    'name'        => $colecciones[ $id ]['name'],
                    'description' => $colecciones[ $id ]['description'] ?? '',
                    'assetCount'  => count( $colecciones[ $id ]['assets'] ?? array() ),
                ),
            ),
            200
        );
    }

    /**
     * Elimina una coleccion
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function eliminar_coleccion( $request ) {
        $id = $request->get_param( 'id' );
        $colecciones = $this->obtener_colecciones_usuario();

        if ( ! isset( $colecciones[ $id ] ) ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Coleccion no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        unset( $colecciones[ $id ] );
        $this->guardar_colecciones_usuario( $colecciones );

        return new WP_REST_Response(
            array( 'success' => true ),
            200
        );
    }

    /**
     * Alterna un asset en una coleccion
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function toggle_asset_coleccion( $request ) {
        $coleccion_id = $request->get_param( 'id' );
        $asset_id = $request->get_param( 'asset_id' );

        $colecciones = $this->obtener_colecciones_usuario();

        if ( ! isset( $colecciones[ $coleccion_id ] ) ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Coleccion no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        // Verificar que el asset existe
        $attachment = get_post( $asset_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Asset no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        if ( ! isset( $colecciones[ $coleccion_id ]['assets'] ) ) {
            $colecciones[ $coleccion_id ]['assets'] = array();
        }

        $assets = &$colecciones[ $coleccion_id ]['assets'];
        $esta_en_coleccion = in_array( $asset_id, $assets, true );

        if ( $esta_en_coleccion ) {
            // Quitar de coleccion
            $assets = array_diff( $assets, array( $asset_id ) );
            $assets = array_values( $assets );
            $esta_en_coleccion = false;
        } else {
            // Agregar a coleccion
            $assets[] = $asset_id;
            $esta_en_coleccion = true;
        }

        $this->guardar_colecciones_usuario( $colecciones );

        return new WP_REST_Response(
            array(
                'success'        => true,
                'inCollection'   => $esta_en_coleccion,
                'asset_id'       => $asset_id,
                'collection_id'  => $coleccion_id,
            ),
            200
        );
    }

    /**
     * Obtiene las colecciones del usuario actual
     *
     * @return array
     */
    private function obtener_colecciones_usuario() {
        $user_id = get_current_user_id();
        $colecciones = get_user_meta( $user_id, self::OPTION_COLLECTIONS, true );

        return is_array( $colecciones ) ? $colecciones : array();
    }

    /**
     * Guarda las colecciones del usuario actual
     *
     * @param array $colecciones Colecciones a guardar.
     */
    private function guardar_colecciones_usuario( $colecciones ) {
        $user_id = get_current_user_id();
        update_user_meta( $user_id, self::OPTION_COLLECTIONS, $colecciones );
    }

    /**
     * Obtiene los iconos integrados
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function obtener_iconos( $request ) {
        $categoria = $request->get_param( 'category' );
        $busqueda = strtolower( $request->get_param( 'search' ) );

        $resultado = array();

        foreach ( $this->builtin_icons as $categoria_id => $categoria_data ) {
            // Filtrar por categoria
            if ( ! empty( $categoria ) && $categoria !== $categoria_id ) {
                continue;
            }

            $iconos_filtrados = array();

            foreach ( $categoria_data['icons'] as $icon_id => $icon_svg ) {
                // Filtrar por busqueda
                if ( ! empty( $busqueda ) && strpos( strtolower( $icon_id ), $busqueda ) === false ) {
                    continue;
                }

                $iconos_filtrados[] = array(
                    'id'       => $icon_id,
                    'name'     => ucwords( str_replace( '-', ' ', $icon_id ) ),
                    'svg'      => $icon_svg,
                    'category' => $categoria_id,
                );
            }

            if ( ! empty( $iconos_filtrados ) ) {
                $resultado[] = array(
                    'id'    => $categoria_id,
                    'label' => $categoria_data['label'],
                    'icons' => $iconos_filtrados,
                );
            }
        }

        return new WP_REST_Response(
            array( 'categories' => $resultado ),
            200
        );
    }

    /**
     * Obtiene las categorias de iconos disponibles
     *
     * @return array
     */
    public function get_icon_categories() {
        $categorias = array();

        foreach ( $this->builtin_icons as $id => $data ) {
            $categorias[ $id ] = array(
                'label' => $data['label'],
                'count' => count( $data['icons'] ),
            );
        }

        return $categorias;
    }

    /**
     * Obtiene un icono especifico por ID
     *
     * @param string $icon_id ID del icono.
     * @return string|null SVG del icono o null si no existe.
     */
    public function get_icon( $icon_id ) {
        foreach ( $this->builtin_icons as $categoria_data ) {
            if ( isset( $categoria_data['icons'][ $icon_id ] ) ) {
                return $categoria_data['icons'][ $icon_id ];
            }
        }

        return null;
    }
}

<?php
/**
 * CPT Flavor Product: vitrina extensible del ecosistema Flavor
 * (plugins, apps móviles, servicios). Cada producto es un post con
 * meta de URL, repo, docs, versión y estado de madurez.
 *
 * @package FlavorPlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Flavor_Product_CPT {

	const POST_TYPE       = 'flavor_product';
	const TAXONOMY_TIPO   = 'flavor_product_type';
	const META_URL        = '_fp_product_url';
	const META_REPO       = '_fp_product_repo';
	const META_DOCS       = '_fp_product_docs';
	const META_VERSION    = '_fp_product_version';
	const META_STATUS     = '_fp_product_status';
	const META_ICON       = '_fp_product_icon';
	const META_BADGE      = '_fp_product_badge_color';
	const META_ORDER      = '_fp_product_order';

	private static ?Flavor_Product_CPT $instancia_singleton = null;

	public static function get_instance(): Flavor_Product_CPT {
		if ( null === self::$instancia_singleton ) {
			self::$instancia_singleton = new self();
		}
		return self::$instancia_singleton;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'registrar_post_type' ) );
		add_action( 'init', array( $this, 'registrar_taxonomia' ) );
		add_action( 'init', array( $this, 'registrar_campos_meta_rest' ) );
		add_action( 'add_meta_boxes', array( $this, 'registrar_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'guardar_meta_boxes' ), 10, 2 );
		add_shortcode( 'flavor_ecosystem', array( $this, 'render_shortcode_ecosystem' ) );
	}

	/**
	 * Shortcode [flavor_ecosystem] — renderiza una rejilla con todos los
	 * productos publicados. Se usa para vincularlo desde páginas hechas con
	 * VBP (bloque "shortcode") de forma que añadir un producto = publicar
	 * un CPT, sin tocar la landing.
	 *
	 * Atributos:
	 *   status   — filtra por estado concreto (stable|beta|alpha|coming|paused)
	 *   columns  — 2|3|4 (default 3)
	 *   limit    — máximo de productos (default -1 = todos)
	 *
	 * @param array<string, string> $atributos_shortcode Atributos pasados al shortcode.
	 * @return string HTML renderizado.
	 */
	public function render_shortcode_ecosystem( $atributos_shortcode = array() ) {
		$atributos_shortcode = shortcode_atts(
			array(
				'status'  => '',
				'columns' => '3',
				'limit'   => '-1',
			),
			$atributos_shortcode,
			'flavor_ecosystem'
		);

		$argumentos_consulta = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atributos_shortcode['limit'],
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		);

		if ( '' !== $atributos_shortcode['status'] ) {
			$argumentos_consulta['meta_query'] = array(
				array(
					'key'     => self::META_STATUS,
					'value'   => sanitize_key( $atributos_shortcode['status'] ),
					'compare' => '=',
				),
			);
		}

		$consulta_productos = new WP_Query( $argumentos_consulta );
		if ( ! $consulta_productos->have_posts() ) {
			return '<p class="flavor-ecosystem-empty">' . esc_html__( 'Aún no hay productos publicados.', 'flavor-platform' ) . '</p>';
		}

		$etiquetas_estado = array(
			'stable' => __( 'Estable', 'flavor-platform' ),
			'beta'   => __( 'Beta', 'flavor-platform' ),
			'alpha'  => __( 'Alpha', 'flavor-platform' ),
			'coming' => __( 'Próximamente', 'flavor-platform' ),
			'paused' => __( 'En pausa', 'flavor-platform' ),
		);

		$colores_estado = array(
			'stable' => '#22c55e',
			'beta'   => '#f97316',
			'alpha'  => '#eab308',
			'coming' => '#8b5cf6',
			'paused' => '#94a3b8',
		);

		$columnas_grid = max( 1, min( 4, (int) $atributos_shortcode['columns'] ) );

		ob_start();
		?>
		<div class="flavor-ecosystem-grid" style="display:grid;grid-template-columns:repeat(<?php echo esc_attr( (string) $columnas_grid ); ?>,minmax(0,1fr));gap:24px;margin:32px 0;">
			<?php
			while ( $consulta_productos->have_posts() ) :
				$consulta_productos->the_post();
				$id_producto   = get_the_ID();
				$url_producto  = get_post_meta( $id_producto, self::META_URL, true );
				$repo_producto = get_post_meta( $id_producto, self::META_REPO, true );
				$docs_producto = get_post_meta( $id_producto, self::META_DOCS, true );
				$version_prod  = get_post_meta( $id_producto, self::META_VERSION, true );
				$estado_prod   = get_post_meta( $id_producto, self::META_STATUS, true );
				$icono_prod    = get_post_meta( $id_producto, self::META_ICON, true );
				$badge_prod    = get_post_meta( $id_producto, self::META_BADGE, true );
				if ( empty( $badge_prod ) ) {
					$badge_prod = $colores_estado[ $estado_prod ] ?? '#6366f1';
				}
				$etiqueta_estado = $etiquetas_estado[ $estado_prod ] ?? '';
				?>
				<article class="flavor-ecosystem-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.04);display:flex;flex-direction:column;gap:12px;">
					<header style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
						<div style="display:flex;align-items:center;gap:12px;">
							<?php if ( ! empty( $icono_prod ) ) : ?>
								<?php if ( 0 === strpos( $icono_prod, 'dashicons-' ) ) : ?>
									<span class="dashicons <?php echo esc_attr( $icono_prod ); ?>" style="font-size:28px;width:28px;height:28px;color:<?php echo esc_attr( $badge_prod ); ?>;"></span>
								<?php else : ?>
									<span style="font-size:28px;line-height:1;"><?php echo esc_html( $icono_prod ); ?></span>
								<?php endif; ?>
							<?php endif; ?>
							<h3 style="margin:0;font-size:18px;font-weight:600;color:#111827;"><?php echo esc_html( get_the_title() ); ?></h3>
						</div>
						<?php if ( '' !== $etiqueta_estado ) : ?>
							<span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:<?php echo esc_attr( $badge_prod ); ?>20;color:<?php echo esc_attr( $badge_prod ); ?>;text-transform:uppercase;letter-spacing:.5px;">
								<?php echo esc_html( $etiqueta_estado ); ?>
							</span>
						<?php endif; ?>
					</header>

					<p style="margin:0;color:#4b5563;font-size:14px;line-height:1.5;"><?php echo esc_html( get_the_excerpt() ); ?></p>

					<?php if ( ! empty( $version_prod ) ) : ?>
						<div style="color:#6b7280;font-size:12px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">v<?php echo esc_html( $version_prod ); ?></div>
					<?php endif; ?>

					<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:auto;padding-top:12px;">
						<?php if ( ! empty( $url_producto ) ) : ?>
							<a href="<?php echo esc_url( $url_producto ); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;background:<?php echo esc_attr( $badge_prod ); ?>;color:#fff;text-decoration:none;font-size:13px;font-weight:500;">
								<?php esc_html_e( 'Saber más', 'flavor-platform' ); ?> →
							</a>
						<?php endif; ?>
						<?php if ( ! empty( $repo_producto ) ) : ?>
							<a href="<?php echo esc_url( $repo_producto ); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;background:#f3f4f6;color:#374151;text-decoration:none;font-size:13px;font-weight:500;">
								GitHub
							</a>
						<?php endif; ?>
						<?php if ( ! empty( $docs_producto ) ) : ?>
							<a href="<?php echo esc_url( $docs_producto ); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;background:#f3f4f6;color:#374151;text-decoration:none;font-size:13px;font-weight:500;">
								<?php esc_html_e( 'Docs', 'flavor-platform' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public function registrar_post_type(): void {
		$etiquetas_post_type = array(
			'name'               => __( 'Productos Flavor', 'flavor-platform' ),
			'singular_name'      => __( 'Producto Flavor', 'flavor-platform' ),
			'menu_name'          => __( 'Ecosistema', 'flavor-platform' ),
			'add_new'            => __( 'Añadir producto', 'flavor-platform' ),
			'add_new_item'       => __( 'Nuevo producto', 'flavor-platform' ),
			'edit_item'          => __( 'Editar producto', 'flavor-platform' ),
			'new_item'           => __( 'Nuevo producto', 'flavor-platform' ),
			'view_item'          => __( 'Ver producto', 'flavor-platform' ),
			'search_items'       => __( 'Buscar productos', 'flavor-platform' ),
			'not_found'          => __( 'No hay productos', 'flavor-platform' ),
			'not_found_in_trash' => __( 'No hay productos en la papelera', 'flavor-platform' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $etiquetas_post_type,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'rest_base'          => 'flavor-products',
				'menu_position'      => 22,
				'menu_icon'          => 'dashicons-screenoptions',
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
				'rewrite'            => array( 'slug' => 'flavor-product' ),
			)
		);
	}

	public function registrar_taxonomia(): void {
		register_taxonomy(
			self::TAXONOMY_TIPO,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Tipos de producto', 'flavor-platform' ),
					'singular_name' => __( 'Tipo de producto', 'flavor-platform' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);
	}

	public function registrar_campos_meta_rest(): void {
		$campos_meta_rest = array(
			self::META_URL     => 'string',
			self::META_REPO    => 'string',
			self::META_DOCS    => 'string',
			self::META_VERSION => 'string',
			self::META_STATUS  => 'string',
			self::META_ICON    => 'string',
			self::META_BADGE   => 'string',
			self::META_ORDER   => 'integer',
		);

		foreach ( $campos_meta_rest as $clave_meta => $tipo_dato ) {
			register_post_meta(
				self::POST_TYPE,
				$clave_meta,
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => $tipo_dato,
					'auth_callback' => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	public function registrar_meta_boxes(): void {
		add_meta_box(
			'flavor_product_meta',
			__( 'Datos del producto', 'flavor-platform' ),
			array( $this, 'renderizar_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function renderizar_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'flavor_product_meta', 'flavor_product_meta_nonce' );

		$campos_producto = array(
			self::META_URL     => array( 'label' => __( 'URL principal del producto', 'flavor-platform' ), 'type' => 'url', 'placeholder' => 'https://…' ),
			self::META_REPO    => array( 'label' => __( 'Repositorio (GitHub)', 'flavor-platform' ), 'type' => 'url', 'placeholder' => 'https://github.com/owner/repo' ),
			self::META_DOCS    => array( 'label' => __( 'Documentación', 'flavor-platform' ), 'type' => 'url', 'placeholder' => 'https://…/docs' ),
			self::META_VERSION => array( 'label' => __( 'Versión actual', 'flavor-platform' ), 'type' => 'text', 'placeholder' => '1.0.0' ),
			self::META_STATUS  => array( 'label' => __( 'Estado', 'flavor-platform' ), 'type' => 'select', 'options' => array(
				'stable'  => 'Estable',
				'beta'    => 'Beta',
				'alpha'   => 'Alpha',
				'coming'  => 'Próximamente',
				'paused'  => 'En pausa',
			) ),
			self::META_ICON    => array( 'label' => __( 'Icono (dashicons-* o emoji)', 'flavor-platform' ), 'type' => 'text', 'placeholder' => 'dashicons-admin-plugins ó 🧩' ),
			self::META_BADGE   => array( 'label' => __( 'Color de badge (hex)', 'flavor-platform' ), 'type' => 'text', 'placeholder' => '#22c55e' ),
			self::META_ORDER   => array( 'label' => __( 'Orden', 'flavor-platform' ), 'type' => 'number', 'placeholder' => '10' ),
		);

		echo '<table class="form-table">';
		foreach ( $campos_producto as $clave_campo => $definicion_campo ) {
			$valor_almacenado = get_post_meta( $post->ID, $clave_campo, true );
			printf( '<tr><th><label for="%s">%s</label></th><td>', esc_attr( $clave_campo ), esc_html( $definicion_campo['label'] ) );

			if ( 'select' === $definicion_campo['type'] ) {
				printf( '<select name="%s" id="%s">', esc_attr( $clave_campo ), esc_attr( $clave_campo ) );
				echo '<option value="">—</option>';
				foreach ( $definicion_campo['options'] as $valor_opcion => $etiqueta_opcion ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $valor_opcion ), selected( $valor_almacenado, $valor_opcion, false ), esc_html( $etiqueta_opcion ) );
				}
				echo '</select>';
			} else {
				printf(
					'<input type="%s" name="%s" id="%s" value="%s" placeholder="%s" class="regular-text" />',
					esc_attr( $definicion_campo['type'] ),
					esc_attr( $clave_campo ),
					esc_attr( $clave_campo ),
					esc_attr( (string) $valor_almacenado ),
					esc_attr( $definicion_campo['placeholder'] ?? '' )
				);
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}

	public function guardar_meta_boxes( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['flavor_product_meta_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['flavor_product_meta_nonce'] ) ), 'flavor_product_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$mapa_sanitizadores = array(
			self::META_URL     => 'esc_url_raw',
			self::META_REPO    => 'esc_url_raw',
			self::META_DOCS    => 'esc_url_raw',
			self::META_VERSION => 'sanitize_text_field',
			self::META_STATUS  => 'sanitize_key',
			self::META_ICON    => 'sanitize_text_field',
			self::META_BADGE   => 'sanitize_hex_color',
			self::META_ORDER   => 'absint',
		);

		foreach ( $mapa_sanitizadores as $clave_meta => $funcion_sanitizar ) {
			if ( ! isset( $_POST[ $clave_meta ] ) ) {
				continue;
			}
			$valor_entrada  = wp_unslash( $_POST[ $clave_meta ] );
			$valor_limpio   = call_user_func( $funcion_sanitizar, $valor_entrada );
			if ( null === $valor_limpio || '' === $valor_limpio ) {
				delete_post_meta( $post_id, $clave_meta );
			} else {
				update_post_meta( $post_id, $clave_meta, $valor_limpio );
			}
		}
	}
}

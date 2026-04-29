<?php
/**
 * Template HTML para exportar documentos VBP.
 *
 * Variables esperadas:
 * - $titulo_documento (string)
 * - $idioma_blog (string)
 * - $color_primario (string hex)
 * - $color_secundario (string hex)
 * - $color_texto (string hex)
 * - $color_fondo (string hex)
 * - $elementos_documento (array)
 *
 * @package Flavor_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $idioma_blog ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $titulo_documento ); ?></title>
	<style>
		:root {
			--primary-color: <?php echo esc_attr( $color_primario ); ?>;
			--secondary-color: <?php echo esc_attr( $color_secundario ); ?>;
			--text-color: <?php echo esc_attr( $color_texto ); ?>;
			--background-color: <?php echo esc_attr( $color_fondo ); ?>;
		}
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
			color: var(--text-color);
			background: var(--background-color);
			line-height: 1.6;
		}
		.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
		h1, h2, h3, h4, h5, h6 { line-height: 1.2; margin-bottom: 1rem; }
		p { margin-bottom: 1rem; }
		img { max-width: 100%; height: auto; }
		.button {
			display: inline-block;
			padding: 12px 24px;
			background: var(--primary-color);
			color: white;
			text-decoration: none;
			border-radius: 8px;
			font-weight: 600;
			transition: opacity 0.2s;
		}
		.button:hover { opacity: 0.9; }
		section { padding: 80px 40px; }
		.card {
			background: white;
			border-radius: 12px;
			padding: 24px;
			box-shadow: 0 4px 6px rgba(0,0,0,0.1);
		}
		.grid { display: grid; gap: 24px; }
		@media (min-width: 768px) {
			.grid-2 { grid-template-columns: repeat(2, 1fr); }
			.grid-3 { grid-template-columns: repeat(3, 1fr); }
			.grid-4 { grid-template-columns: repeat(4, 1fr); }
		}
	</style>
</head>
<body>
<?php
if ( class_exists( 'Flavor_VBP_Canvas' ) ) {
	$instancia_canvas = Flavor_VBP_Canvas::get_instance();
	foreach ( $elementos_documento as $elemento ) {
		echo $instancia_canvas->renderizar_elemento( $elemento ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
?>
</body>
</html>

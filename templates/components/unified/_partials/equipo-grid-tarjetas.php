<?php
/**
 * Partial: Equipo - Grid de Tarjetas
 * Grid of team member cards with photo.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Miembros: nombre, cargo, foto, bio, redes
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_miembros    = $items ?? [];
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-6xl mx-auto">
        <?php if ( $titulo_seccion || $subtitulo_seccion ) : ?>
            <div class="text-center mb-12">
                <?php if ( $titulo_seccion ) : ?>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        <?php echo esc_html( $titulo_seccion ); ?>
                    </h2>
                <?php endif; ?>
                <?php if ( $subtitulo_seccion ) : ?>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        <?php echo esc_html( $subtitulo_seccion ); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach ( $lista_miembros as $miembro ) :
                $nombre_miembro = $miembro['nombre'] ?? '';
                $cargo_miembro  = $miembro['cargo'] ?? '';
                $foto_miembro   = $miembro['foto'] ?? '';
                $bio_miembro    = $miembro['bio'] ?? '';
                $redes_miembro  = $miembro['redes'] ?? [];
            ?>
                <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden group">
                    <!-- Photo -->
                    <div class="aspect-square overflow-hidden bg-gray-100">
                        <?php if ( $foto_miembro ) : ?>
                            <img
                                src="<?php echo esc_url( $foto_miembro ); ?>"
                                alt="<?php echo esc_attr( $nombre_miembro ); ?>"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />
                        <?php else : ?>
                            <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                <svg class="w-20 h-20 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="p-6 text-center">
                        <?php if ( $nombre_miembro ) : ?>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">
                                <?php echo esc_html( $nombre_miembro ); ?>
                            </h3>
                        <?php endif; ?>

                        <?php if ( $cargo_miembro ) : ?>
                            <p class="text-sm font-medium mb-3" style="color: <?php echo esc_attr( $color_principal ); ?>;">
                                <?php echo esc_html( $cargo_miembro ); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ( $bio_miembro ) : ?>
                            <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                                <?php echo esc_html( $bio_miembro ); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ( ! empty( $redes_miembro ) ) : ?>
                            <div class="flex items-center justify-center gap-3">
                                <?php foreach ( $redes_miembro as $nombre_red => $url_red ) : ?>
                                    <a
                                        href="<?php echo esc_url( $url_red ); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-white transition-colors"
                                        style="--hover-bg: <?php echo esc_attr( $color_principal ); ?>;"
                                        onmouseover="this.style.backgroundColor='<?php echo esc_attr( $color_principal ); ?>'"
                                        onmouseout="this.style.backgroundColor='transparent'"
                                        aria-label="<?php echo esc_attr( $nombre_red ); ?>"
                                    >
                                        <span class="text-xs font-bold uppercase"><?php echo esc_html( mb_substr( $nombre_red, 0, 2 ) ); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

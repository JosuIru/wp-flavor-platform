<?php
/**
 * Themacle Component: Post Content
 *
 * Full article layout with optional featured image, meta info bar,
 * post body, and social share buttons.
 *
 * @package FlavorChatIA
 *
 * @var bool   $mostrar_imagen_destacada Whether to display the featured image.
 * @var bool   $mostrar_fecha            Whether to display the publication date.
 * @var bool   $mostrar_autor            Whether to display the author name.
 * @var bool   $mostrar_compartir        Whether to display share buttons.
 * @var string $component_classes        Additional CSS classes passed from the builder.
 */

defined( 'ABSPATH' ) || exit;

$mostrar_imagen_destacada = ! empty( $mostrar_imagen_destacada );
$mostrar_fecha            = ! empty( $mostrar_fecha );
$mostrar_autor            = ! empty( $mostrar_autor );
$mostrar_compartir        = ! empty( $mostrar_compartir );
$component_classes        = $component_classes ?? '';

$clases_seccion = sprintf(
    'flavor-post-content w-full py-12 md:py-20 %s',
    esc_attr( $component_classes )
);

$url_entrada_actual   = esc_url( get_permalink() );
$titulo_entrada_actual = get_the_title();
$titulo_codificado    = rawurlencode( $titulo_entrada_actual );
$url_codificada       = rawurlencode( $url_entrada_actual );

$tiene_meta_visible = $mostrar_fecha || $mostrar_autor;
?>

<article class="<?php echo esc_attr( trim( $clases_seccion ) ); ?>"
         style="font-family: var(--flavor-font-family, inherit);">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php /* ── Featured Image Header ───────────────────────── */ ?>
        <?php if ( $mostrar_imagen_destacada && has_post_thumbnail() ) : ?>
            <div class="mb-8 md:mb-12 rounded-2xl overflow-hidden shadow-lg -mx-4 sm:mx-0">
                <?php
                echo get_the_post_thumbnail(
                    null,
                    'full',
                    array(
                        'class'   => 'w-full h-auto object-cover max-h-[560px]',
                        'loading' => 'eager',
                    )
                );
                ?>
            </div>
        <?php endif; ?>

        <?php /* ── Post Title ──────────────────────────────────── */ ?>
        <header class="mb-6">
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight"
                style="color: var(--flavor-heading-color, #111827);">
                <?php echo esc_html( $titulo_entrada_actual ); ?>
            </h1>
        </header>

        <?php /* ── Meta Info Bar ───────────────────────────────── */ ?>
        <?php if ( $tiene_meta_visible ) : ?>
            <div class="flex flex-wrap items-center gap-4 mb-8 pb-6 border-b text-sm"
                 style="border-color: var(--flavor-border-color, #e5e7eb);
                        color: var(--flavor-text-color, #6b7280);">

                <?php if ( $mostrar_fecha ) : ?>
                    <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"
                          class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <?php echo esc_html( get_the_date() ); ?>
                    </time>
                <?php endif; ?>

                <?php if ( $mostrar_fecha && $mostrar_autor ) : ?>
                    <span class="text-gray-300" aria-hidden="true">&middot;</span>
                <?php endif; ?>

                <?php if ( $mostrar_autor ) : ?>
                    <span class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <?php echo esc_html( get_the_author() ); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php /* ── Post Body ───────────────────────────────────── */ ?>
        <div class="flavor-post-body prose prose-lg max-w-none
                    prose-headings:font-bold
                    prose-a:underline prose-a:decoration-1 prose-a:underline-offset-2
                    prose-img:rounded-xl prose-img:shadow-md
                    prose-blockquote:border-l-4 prose-blockquote:not-italic
                    leading-relaxed"
             style="color: var(--flavor-text-color, #374151);
                    --tw-prose-headings: var(--flavor-heading-color, #111827);
                    --tw-prose-links: var(--flavor-primary, #3b82f6);
                    --tw-prose-quote-borders: var(--flavor-primary, #3b82f6);">
            <?php the_content(); ?>
        </div>

        <?php /* ── Share Buttons ───────────────────────────────── */ ?>
        <?php if ( $mostrar_compartir ) : ?>
            <div class="mt-10 pt-8 border-t"
                 style="border-color: var(--flavor-border-color, #e5e7eb);">

                <p class="text-sm font-semibold mb-4"
                   style="color: var(--flavor-heading-color, #111827);">
                    <?php echo esc_html__( 'Share this article', 'flavor-chat-ia' ); ?>
                </p>

                <div class="flex flex-wrap gap-3">

                    <?php /* ── Twitter / X ────────────────────── */ ?>
                    <a href="<?php echo esc_url( 'https://twitter.com/intent/tweet?text=' . $titulo_codificado . '&url=' . $url_codificada ); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm
                              font-medium transition-colors duration-200 hover:bg-gray-50"
                       style="border-color: var(--flavor-border-color, #d1d5db);
                              color: var(--flavor-text-color, #374151);"
                       aria-label="<?php echo esc_attr__( 'Share on Twitter', 'flavor-chat-ia' ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                             fill="currentColor">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        <?php echo esc_html__( 'Twitter', 'flavor-chat-ia' ); ?>
                    </a>

                    <?php /* ── Facebook ────────────────────────── */ ?>
                    <a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . $url_codificada ); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm
                              font-medium transition-colors duration-200 hover:bg-gray-50"
                       style="border-color: var(--flavor-border-color, #d1d5db);
                              color: var(--flavor-text-color, #374151);"
                       aria-label="<?php echo esc_attr__( 'Share on Facebook', 'flavor-chat-ia' ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                             fill="currentColor">
                            <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                        </svg>
                        <?php echo esc_html__( 'Facebook', 'flavor-chat-ia' ); ?>
                    </a>

                    <?php /* ── LinkedIn ────────────────────────── */ ?>
                    <a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url_codificada ); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm
                              font-medium transition-colors duration-200 hover:bg-gray-50"
                       style="border-color: var(--flavor-border-color, #d1d5db);
                              color: var(--flavor-text-color, #374151);"
                       aria-label="<?php echo esc_attr__( 'Share on LinkedIn', 'flavor-chat-ia' ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                             fill="currentColor">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                        <?php echo esc_html__( 'LinkedIn', 'flavor-chat-ia' ); ?>
                    </a>

                </div>
            </div>
        <?php endif; ?>

    </div>
</article>

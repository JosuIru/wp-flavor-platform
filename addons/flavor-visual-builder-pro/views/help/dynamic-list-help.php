<?php
/**
 * Panel de ayuda in-app para el bloque Lista Dinámica.
 *
 * Se monta oculto en el DOM del editor y Alpine lo muestra/oculta vía
 * store('vbpHelp').panel === 'dynamic-list'. Contenido traducible con __()
 * y snippets con botón "Copiar" que usan la Clipboard API.
 *
 * La URL del video viene del filtro 'flavor_vbp_dynamic_list_help_video_url'
 * (default vacío → sección oculta). Se espera un iframe embeddable
 * (YouTube/Vimeo/self-hosted).
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Views\Help
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$url_video_ayuda = (string) apply_filters( 'flavor_vbp_dynamic_list_help_video_url', '' );
?>
<div class="vbp-help-panel"
     x-data="vbpHelpPanel()"
     x-show="isOpen('dynamic-list')"
     x-cloak
     @keydown.escape.window="close()"
     style="display:none;position:fixed;top:0;right:0;bottom:0;width:min(560px,90vw);background:#fff;box-shadow:-4px 0 24px rgba(0,0,0,0.15);z-index:100000;overflow-y:auto;"
     role="dialog"
     aria-modal="true"
     aria-labelledby="vbp-help-title">

    <!-- Backdrop con click-para-cerrar -->
    <div @click="close()"
         style="position:fixed;top:0;left:0;right:560px;bottom:0;background:rgba(0,0,0,0.35);z-index:-1;"></div>

    <!-- Header -->
    <header style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e7eb;position:sticky;top:0;background:#fff;z-index:1;">
        <h2 id="vbp-help-title" style="margin:0;font-size:1.1em;color:#111827;">
            <?php esc_html_e( 'Ayuda: Lista Dinámica', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
        </h2>
        <button type="button"
                @click="close()"
                aria-label="<?php esc_attr_e( 'Cerrar panel de ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                style="background:none;border:0;font-size:1.5em;color:#6b7280;cursor:pointer;line-height:1;">×</button>
    </header>

    <article style="padding:20px;color:#374151;line-height:1.6;font-size:0.9375em;">

        <!-- Intro -->
        <section>
            <p style="margin-top:0;">
                <?php esc_html_e( 'Lista Dinámica muestra contenido en vivo de la plataforma (eventos, socios, libros, foros, etc.) en cualquier página que construyas con VBP. Los datos se cargan desde la base de datos cada vez que alguien visita la página, no se hardcodean.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
        </section>

        <?php if ( $url_video_ayuda !== '' ) : ?>
        <!-- Video walkthrough -->
        <section style="margin:24px 0;">
            <h3 style="font-size:1em;color:#111827;margin:0 0 12px;">🎥 <?php esc_html_e( 'Video guía', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
            <div style="position:relative;padding-bottom:56.25%;height:0;border-radius:8px;overflow:hidden;background:#000;">
                <iframe src="<?php echo esc_url( $url_video_ayuda ); ?>"
                        style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                        allowfullscreen
                        title="<?php esc_attr_e( 'Video guía de Lista Dinámica', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"></iframe>
            </div>
        </section>
        <?php endif; ?>

        <!-- Cómo añadirlo -->
        <section style="margin:24px 0;">
            <h3 style="font-size:1em;color:#111827;margin:0 0 8px;">1. <?php esc_html_e( 'Cómo añadirlo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
            <p>
                <?php esc_html_e( 'Abre la paleta de bloques (icono + a la izquierda), busca la categoría "Campos Dinámicos" y arrastra "Lista Dinámica" al lienzo.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
        </section>

        <!-- Elegir colección -->
        <section style="margin:24px 0;">
            <h3 style="font-size:1em;color:#111827;margin:0 0 8px;">2. <?php esc_html_e( 'Colecciones disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
            <ul style="margin:0;padding-left:20px;">
                <li><strong>Eventos</strong> — <?php esc_html_e( 'actividades de la plataforma con fechas de inicio/fin', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><strong>Socios</strong> — <?php esc_html_e( 'miembros registrados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><strong>Biblioteca</strong> — <?php esc_html_e( 'libros del catálogo con portada, autor y género', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><strong>Hilos de foros</strong> — <?php esc_html_e( 'conversaciones activas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><strong>Grupos de consumo</strong> — <?php esc_html_e( 'grupos locales de compra colectiva', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><strong>Marketplace</strong> — <?php esc_html_e( 'anuncios publicados', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
            </ul>
            <p style="margin-top:8px;color:#6b7280;font-size:0.875em;">
                <?php esc_html_e( 'Cuando elijas una colección, los filtros se adaptan automáticamente. Los que aparezcan en la vista previa en tiempo real arriba.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
        </section>

        <!-- Tokens de fecha -->
        <section style="margin:24px 0;">
            <h3 style="font-size:1em;color:#111827;margin:0 0 8px;">3. <?php esc_html_e( 'Fechas relativas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
            <p>
                <?php esc_html_e( 'Para que los filtros de fecha no envejezcan (ej: "eventos próxima semana"), escribe tokens en vez de fechas fijas:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
            <div class="vbp-help-snippet" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:12px;margin:8px 0;position:relative;font-family:monospace;font-size:0.85em;">
                <button type="button"
                        class="vbp-help-copy-btn"
                        data-copy-target="snippet-tokens"
                        @click="copyToClipboard($event.currentTarget)"
                        style="position:absolute;top:6px;right:6px;padding:3px 8px;background:#3b82f6;color:#fff;border:0;border-radius:4px;font-size:0.75em;cursor:pointer;">
                    📋 <?php esc_html_e( 'Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <pre id="snippet-tokens" style="margin:0;white-space:pre-wrap;">@today                    <?php esc_html_e( 'hoy', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
@today+7d                 <?php esc_html_e( 'dentro de 7 días', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
@today-30d                <?php esc_html_e( 'hace 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
@today+2w                 <?php esc_html_e( 'dentro de 2 semanas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
@today+1m                 <?php esc_html_e( 'dentro de 1 mes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
@start_of_week            <?php esc_html_e( 'lunes de esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
@end_of_week              <?php esc_html_e( 'domingo de esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
@start_of_month           <?php esc_html_e( 'primer día del mes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
@end_of_month             <?php esc_html_e( 'último día del mes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></pre>
            </div>
            <p style="margin-top:12px;color:#6b7280;font-size:0.875em;">
                <strong><?php esc_html_e( 'Ejemplo:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></strong>
                <?php esc_html_e( 'Para "Eventos de esta semana": fecha_desde =', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                <code>@start_of_week</code>,
                <?php esc_html_e( 'fecha_hasta =', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                <code>@end_of_week</code>.
            </p>
        </section>

        <!-- Variantes -->
        <section style="margin:24px 0;">
            <h3 style="font-size:1em;color:#111827;margin:0 0 8px;">4. <?php esc_html_e( 'Variantes visuales', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
            <ul style="margin:0;padding-left:20px;">
                <li><strong><?php esc_html_e( 'Tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></strong> — <?php esc_html_e( 'grid responsivo con imagen 16:9. Recomendada para catálogos.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><strong><?php esc_html_e( 'Listado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></strong> — <?php esc_html_e( 'stack vertical compacto. Para barras laterales.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><strong><?php esc_html_e( 'Minimal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></strong> — <?php esc_html_e( 'solo títulos con enlace. Para "ver más" compactos.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><strong><?php esc_html_e( 'Plantilla personalizada', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></strong> — <?php esc_html_e( 'tu propio HTML con placeholders.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
            </ul>
            <p style="margin-top:8px;color:#6b7280;font-size:0.875em;">
                <?php esc_html_e( 'Cambia la variante desde la toolbar que aparece al seleccionar el bloque.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
        </section>

        <!-- Plantilla custom -->
        <section style="margin:24px 0;">
            <h3 style="font-size:1em;color:#111827;margin:0 0 8px;">5. <?php esc_html_e( 'Plantilla personalizada: placeholders', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
            <p>
                <?php esc_html_e( 'Al elegir variante "Plantilla personalizada" puedes escribir tu propio HTML. Los placeholders se sustituyen por los datos del item:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
            <div class="vbp-help-snippet" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:12px;margin:8px 0;position:relative;font-family:monospace;font-size:0.85em;">
                <button type="button"
                        class="vbp-help-copy-btn"
                        data-copy-target="snippet-template"
                        @click="copyToClipboard($event.currentTarget)"
                        style="position:absolute;top:6px;right:6px;padding:3px 8px;background:#3b82f6;color:#fff;border:0;border-radius:4px;font-size:0.75em;cursor:pointer;">
                    📋 <?php esc_html_e( 'Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <pre id="snippet-template" style="margin:0;white-space:pre-wrap;">&lt;article class="libro"&gt;
  &lt;img src="{{image}}" alt="{{title}}"&gt;
  &lt;h3&gt;&lt;a href="{{url}}"&gt;{{title}}&lt;/a&gt;&lt;/h3&gt;
  &lt;p class="autor"&gt;{{meta.autor}}&lt;/p&gt;
  &lt;p&gt;{{excerpt}}&lt;/p&gt;
&lt;/article&gt;</pre>
            </div>
            <p style="margin-top:12px;color:#6b7280;font-size:0.875em;">
                <strong><?php esc_html_e( 'Placeholders disponibles:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></strong>
                <code>{{title}}</code>, <code>{{excerpt}}</code>, <code>{{image}}</code>, <code>{{url}}</code>, <code>{{date}}</code>,
                <code>{{meta.CAMPO}}</code> (<?php esc_html_e( 'ej: meta.autor, meta.genero, meta.ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>).
            </p>
            <p style="margin-top:8px;color:#991b1b;font-size:0.875em;background:#fef2f2;padding:8px 10px;border-radius:6px;border:1px solid #fecaca;">
                ⚠ <?php esc_html_e( 'Por seguridad se eliminan <script>, <iframe> y atributos on* (onclick, onload). URLs javascript: se descartan. Para permitir iframes de hosts concretos (YouTube, Vimeo) el administrador del site debe añadir el filtro en código:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
            <div class="vbp-help-snippet" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:12px;margin:8px 0;position:relative;font-family:monospace;font-size:0.85em;">
                <button type="button"
                        class="vbp-help-copy-btn"
                        data-copy-target="snippet-iframe-allowlist"
                        @click="copyToClipboard($event.currentTarget)"
                        style="position:absolute;top:6px;right:6px;padding:3px 8px;background:#3b82f6;color:#fff;border:0;border-radius:4px;font-size:0.75em;cursor:pointer;">
                    📋 <?php esc_html_e( 'Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <pre id="snippet-iframe-allowlist" style="margin:0;white-space:pre-wrap;">add_filter('flavor_vbp_custom_template_iframe_hosts', function () {
    return ['youtube.com', 'vimeo.com', 'openstreetmap.org'];
});</pre>
            </div>
        </section>

        <!-- Filtros visitante -->
        <section style="margin:24px 0;">
            <h3 style="font-size:1em;color:#111827;margin:0 0 8px;">6. <?php esc_html_e( 'Filtros editables por el visitante', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
            <p>
                <?php esc_html_e( 'En el inspector, marca qué filtros quieres que el visitante pueda cambiar. Aparecerá un formulario arriba del listado con esos filtros. Al cambiar cualquier filtro:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
            <ul style="margin:0;padding-left:20px;">
                <li><?php esc_html_e( 'El listado se recarga automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><?php esc_html_e( 'La URL se actualiza con los filtros: los visitantes pueden compartir enlaces directos a resultados específicos.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
                <li><?php esc_html_e( 'Los filtros de búsqueda tienen un retraso de 400ms para no recargar al teclear cada letra.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></li>
            </ul>
        </section>

        <!-- Recetas -->
        <section style="margin:24px 0;">
            <h3 style="font-size:1em;color:#111827;margin:0 0 8px;">7. <?php esc_html_e( 'Recetas comunes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
            <dl style="margin:0;">
                <dt style="font-weight:600;margin-top:12px;"><?php esc_html_e( 'Próximos eventos (esta semana)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></dt>
                <dd style="margin:4px 0 0 0;color:#4b5563;font-size:0.9em;">
                    <?php esc_html_e( 'Colección: Eventos · estado: publicado · fecha_desde: @today · fecha_hasta: @today+7d · orden: próximos.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </dd>

                <dt style="font-weight:600;margin-top:12px;"><?php esc_html_e( 'Últimos libros añadidos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></dt>
                <dd style="margin:4px 0 0 0;color:#4b5563;font-size:0.9em;">
                    <?php esc_html_e( 'Colección: Biblioteca · estado: disponible · orden: recientes · límite: 8.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </dd>

                <dt style="font-weight:600;margin-top:12px;"><?php esc_html_e( 'Anuncios gratis del marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></dt>
                <dd style="margin:4px 0 0 0;color:#4b5563;font-size:0.9em;">
                    <?php esc_html_e( 'Colección: Marketplace · estado: publicado · solo_gratuitos: activo · orden: recientes.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </dd>
            </dl>
        </section>

        <!-- FAQ -->
        <section style="margin:24px 0;">
            <h3 style="font-size:1em;color:#111827;margin:0 0 8px;">8. <?php esc_html_e( 'Preguntas frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>

            <details style="margin:8px 0;padding:10px 12px;background:#f9fafb;border-radius:6px;border:1px solid #e5e7eb;">
                <summary style="font-weight:600;cursor:pointer;"><?php esc_html_e( '¿Por qué no veo ningún item?', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></summary>
                <p style="margin:8px 0 0;color:#4b5563;font-size:0.9em;">
                    <?php esc_html_e( 'Revisa el estado (por defecto filtra por "publicado"/"activo"/"disponible"), las fechas (no están en el rango), o la búsqueda (no coincide con ningún título). La vista previa en el inspector muestra el total actual.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </p>
            </details>

            <details style="margin:8px 0;padding:10px 12px;background:#f9fafb;border-radius:6px;border:1px solid #e5e7eb;">
                <summary style="font-weight:600;cursor:pointer;"><?php esc_html_e( '¿Cómo comparto una URL con filtros aplicados?', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></summary>
                <p style="margin:8px 0 0;color:#4b5563;font-size:0.9em;">
                    <?php esc_html_e( 'Si el visitante cambia un filtro, la URL del navegador se actualiza con f_estado=, f_busqueda=, etc. Basta con copiar esa URL. Al abrirla, los filtros se aplican automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </p>
            </details>

            <details style="margin:8px 0;padding:10px 12px;background:#f9fafb;border-radius:6px;border:1px solid #e5e7eb;">
                <summary style="font-weight:600;cursor:pointer;"><?php esc_html_e( '¿Los datos están cacheados? ¿Cuánto tardan en actualizarse?', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></summary>
                <p style="margin:8px 0 0;color:#4b5563;font-size:0.9em;">
                    <?php esc_html_e( 'Sí, el botón "Cargar más" en frontend cachea respuestas 2 minutos. Pero cuando se crea/elimina un evento, libro, anuncio, socio, etc., el cache se limpia automáticamente para esa colección, así que los cambios aparecen al momento.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </p>
            </details>
        </section>
    </article>
</div>

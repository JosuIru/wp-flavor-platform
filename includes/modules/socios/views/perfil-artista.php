<?php
/**
 * Vista de Perfil Público de Artista (Kulturaka)
 *
 * @package FlavorChatIA
 * @subpackage Modules\Socios\Artistas
 *
 * Variables disponibles:
 * @var object $artista Datos del artista
 * @var bool $esta_siguiendo Si el usuario actual sigue al artista
 * @var bool $es_propio Si es el perfil del usuario actual
 */

if (!defined('ABSPATH')) {
    exit;
}

$nivel_clases = [
    'emergente' => 'flavor-artista-nivel--emergente',
    'establecido' => 'flavor-artista-nivel--establecido',
    'profesional' => 'flavor-artista-nivel--profesional',
    'consagrado' => 'flavor-artista-nivel--consagrado',
];

$nivel_clase = $nivel_clases[$artista->nivel_artista] ?? '';
?>

<div class="flavor-artista-perfil" data-artista-id="<?php echo esc_attr($artista->id); ?>">

    <!-- Cabecera del perfil -->
    <div class="flavor-artista-header">
        <div class="flavor-artista-header__bg">
            <?php if (!empty($artista->portfolio_imagenes[0])): ?>
                <img src="<?php echo esc_url($artista->portfolio_imagenes[0]); ?>" alt="">
            <?php endif; ?>
        </div>

        <div class="flavor-artista-header__content">
            <div class="flavor-artista-avatar">
                <img src="<?php echo esc_url($artista->avatar); ?>" alt="<?php echo esc_attr($artista->nombre_artistico); ?>">
                <?php if ($artista->verificado): ?>
                    <span class="flavor-artista-verificado" title="<?php esc_attr_e('Artista verificado', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </span>
                <?php endif; ?>
            </div>

            <div class="flavor-artista-info">
                <h1 class="flavor-artista-nombre">
                    <?php echo esc_html($artista->nombre_artistico); ?>
                </h1>

                <div class="flavor-artista-meta">
                    <span class="flavor-artista-nivel <?php echo esc_attr($nivel_clase); ?>">
                        <?php echo esc_html($artista->nivel_label); ?>
                    </span>

                    <?php if (!empty($artista->disciplinas_detalle)): ?>
                        <span class="flavor-artista-disciplinas">
                            <?php foreach (array_slice($artista->disciplinas_detalle, 0, 3) as $disciplina): ?>
                                <span class="flavor-artista-disciplina" style="--disciplina-color: <?php echo esc_attr($disciplina->color); ?>">
                                    <?php echo esc_html($disciplina->nombre); ?>
                                </span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($artista->bio_corta)): ?>
                    <p class="flavor-artista-bio-corta"><?php echo esc_html($artista->bio_corta); ?></p>
                <?php endif; ?>
            </div>

            <div class="flavor-artista-acciones">
                <?php if (!$es_propio): ?>
                    <button type="button"
                            class="flavor-btn flavor-btn--primary flavor-artista-seguir"
                            data-artista="<?php echo esc_attr($artista->id); ?>"
                            data-siguiendo="<?php echo $esta_siguiendo ? '1' : '0'; ?>">
                        <span class="dashicons <?php echo $esta_siguiendo ? 'dashicons-yes' : 'dashicons-plus-alt2'; ?>"></span>
                        <span class="texto">
                            <?php echo $esta_siguiendo ? esc_html__('Siguiendo', 'flavor-chat-ia') : esc_html__('Seguir', 'flavor-chat-ia'); ?>
                        </span>
                    </button>

                    <button type="button" class="flavor-btn flavor-btn--secondary flavor-artista-contactar">
                        <span class="dashicons dashicons-email"></span>
                        <?php esc_html_e('Contactar', 'flavor-chat-ia'); ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo esc_url(home_url('/mi-perfil-artista/editar/')); ?>" class="flavor-btn flavor-btn--secondary">
                        <span class="dashicons dashicons-edit"></span>
                        <?php esc_html_e('Editar perfil', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="flavor-artista-stats">
        <div class="flavor-artista-stat">
            <span class="flavor-artista-stat__valor"><?php echo number_format_i18n($artista->eventos_realizados); ?></span>
            <span class="flavor-artista-stat__label"><?php esc_html_e('Eventos', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="flavor-artista-stat">
            <span class="flavor-artista-stat__valor"><?php echo number_format_i18n($artista->seguidores_count); ?></span>
            <span class="flavor-artista-stat__label"><?php esc_html_e('Seguidores', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="flavor-artista-stat">
            <span class="flavor-artista-stat__valor"><?php echo number_format_i18n($artista->audiencia_total); ?></span>
            <span class="flavor-artista-stat__label"><?php esc_html_e('Audiencia total', 'flavor-chat-ia'); ?></span>
        </div>
        <?php if ($artista->rating): ?>
        <div class="flavor-artista-stat">
            <span class="flavor-artista-stat__valor">
                <span class="dashicons dashicons-star-filled"></span>
                <?php echo number_format_i18n($artista->rating, 1); ?>
            </span>
            <span class="flavor-artista-stat__label">
                <?php printf(esc_html__('(%s valoraciones)', 'flavor-chat-ia'), number_format_i18n($artista->total_valoraciones)); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Contenido principal -->
    <div class="flavor-artista-contenido">

        <!-- Columna principal -->
        <div class="flavor-artista-main">

            <!-- Biografía -->
            <?php if (!empty($artista->bio_artistica)): ?>
            <section class="flavor-artista-seccion">
                <h2 class="flavor-artista-seccion__titulo">
                    <span class="dashicons dashicons-id-alt"></span>
                    <?php esc_html_e('Sobre el artista', 'flavor-chat-ia'); ?>
                </h2>
                <div class="flavor-artista-bio">
                    <?php echo wp_kses_post(wpautop($artista->bio_artistica)); ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Video destacado -->
            <?php if (!empty($artista->video_destacado)): ?>
            <section class="flavor-artista-seccion">
                <h2 class="flavor-artista-seccion__titulo">
                    <span class="dashicons dashicons-video-alt3"></span>
                    <?php esc_html_e('Video destacado', 'flavor-chat-ia'); ?>
                </h2>
                <div class="flavor-artista-video-destacado">
                    <?php echo wp_oembed_get($artista->video_destacado); ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Portfolio de videos -->
            <?php if (!empty($artista->portfolio_videos)): ?>
            <section class="flavor-artista-seccion">
                <h2 class="flavor-artista-seccion__titulo">
                    <span class="dashicons dashicons-playlist-video"></span>
                    <?php esc_html_e('Videos', 'flavor-chat-ia'); ?>
                </h2>
                <div class="flavor-artista-portfolio-grid">
                    <?php foreach ($artista->portfolio_videos as $video): ?>
                        <div class="flavor-artista-portfolio-item flavor-artista-portfolio-item--video">
                            <?php echo wp_oembed_get($video); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Audio destacado -->
            <?php if (!empty($artista->audio_destacado) || !empty($artista->portfolio_audio)): ?>
            <section class="flavor-artista-seccion">
                <h2 class="flavor-artista-seccion__titulo">
                    <span class="dashicons dashicons-format-audio"></span>
                    <?php esc_html_e('Música', 'flavor-chat-ia'); ?>
                </h2>
                <div class="flavor-artista-audio">
                    <?php if (!empty($artista->audio_destacado)): ?>
                        <?php echo wp_oembed_get($artista->audio_destacado); ?>
                    <?php elseif (!empty($artista->portfolio_audio[0])): ?>
                        <?php echo wp_oembed_get($artista->portfolio_audio[0]); ?>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Galería de imágenes -->
            <?php if (!empty($artista->portfolio_imagenes)): ?>
            <section class="flavor-artista-seccion">
                <h2 class="flavor-artista-seccion__titulo">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <?php esc_html_e('Galería', 'flavor-chat-ia'); ?>
                </h2>
                <div class="flavor-artista-galeria">
                    <?php foreach ($artista->portfolio_imagenes as $imagen): ?>
                        <a href="<?php echo esc_url($imagen); ?>" class="flavor-artista-galeria__item" data-lightbox="galeria-artista">
                            <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($artista->nombre_artistico); ?>">
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </div>

        <!-- Sidebar -->
        <aside class="flavor-artista-sidebar">

            <!-- Géneros -->
            <?php if (!empty($artista->generos)): ?>
            <div class="flavor-artista-card">
                <h3 class="flavor-artista-card__titulo">
                    <span class="dashicons dashicons-tag"></span>
                    <?php esc_html_e('Géneros', 'flavor-chat-ia'); ?>
                </h3>
                <div class="flavor-artista-tags">
                    <?php foreach ($artista->generos as $genero): ?>
                        <span class="flavor-artista-tag"><?php echo esc_html($genero); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Idiomas -->
            <?php if (!empty($artista->idiomas)): ?>
            <div class="flavor-artista-card">
                <h3 class="flavor-artista-card__titulo">
                    <span class="dashicons dashicons-translation"></span>
                    <?php esc_html_e('Idiomas', 'flavor-chat-ia'); ?>
                </h3>
                <div class="flavor-artista-idiomas">
                    <?php
                    $idiomas_labels = [
                        'eu' => 'Euskara',
                        'es' => 'Castellano',
                        'en' => 'English',
                        'fr' => 'Français',
                        'pt' => 'Português',
                    ];
                    foreach ($artista->idiomas as $idioma):
                        $label = $idiomas_labels[$idioma] ?? $idioma;
                    ?>
                        <span class="flavor-artista-idioma"><?php echo esc_html($label); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Disponibilidad -->
            <?php if ($artista->disponible_giras): ?>
            <div class="flavor-artista-card flavor-artista-card--disponible">
                <h3 class="flavor-artista-card__titulo">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Disponible para eventos', 'flavor-chat-ia'); ?>
                </h3>
                <ul class="flavor-artista-disponibilidad">
                    <?php if ($artista->radio_actuacion_km): ?>
                        <li>
                            <span class="dashicons dashicons-location"></span>
                            <?php printf(esc_html__('Radio: %d km', 'flavor-chat-ia'), $artista->radio_actuacion_km); ?>
                        </li>
                    <?php endif; ?>
                    <?php if ($artista->duracion_tipica_min): ?>
                        <li>
                            <span class="dashicons dashicons-clock"></span>
                            <?php printf(esc_html__('Duración: %d min', 'flavor-chat-ia'), $artista->duracion_tipica_min); ?>
                        </li>
                    <?php endif; ?>
                    <?php if ($artista->acepta_semilla): ?>
                        <li>
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php esc_html_e('Acepta SEMILLA', 'flavor-chat-ia'); ?>
                        </li>
                    <?php endif; ?>
                    <?php if ($artista->acepta_hours): ?>
                        <li>
                            <span class="dashicons dashicons-backup"></span>
                            <?php esc_html_e('Acepta HOURS', 'flavor-chat-ia'); ?>
                        </li>
                    <?php endif; ?>
                </ul>

                <?php if (!$es_propio): ?>
                <a href="<?php echo esc_url(home_url('/proponer-evento/?artista=' . $artista->id)); ?>" class="flavor-btn flavor-btn--success flavor-btn--block">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Proponer evento', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Redes sociales -->
            <?php
            $redes = [
                'website' => ['icon' => 'admin-site-alt3', 'label' => 'Web'],
                'instagram' => ['icon' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/'],
                'bandcamp' => ['icon' => 'format-audio', 'label' => 'Bandcamp', 'url' => 'https://bandcamp.com/'],
                'spotify' => ['icon' => 'spotify', 'label' => 'Spotify'],
                'youtube' => ['icon' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/'],
                'soundcloud' => ['icon' => 'format-audio', 'label' => 'SoundCloud', 'url' => 'https://soundcloud.com/'],
                'tiktok' => ['icon' => 'video-alt3', 'label' => 'TikTok', 'url' => 'https://tiktok.com/@'],
            ];

            $tiene_redes = false;
            foreach ($redes as $red => $config) {
                if (!empty($artista->$red)) {
                    $tiene_redes = true;
                    break;
                }
            }

            if ($tiene_redes):
            ?>
            <div class="flavor-artista-card">
                <h3 class="flavor-artista-card__titulo">
                    <span class="dashicons dashicons-share"></span>
                    <?php esc_html_e('Enlaces', 'flavor-chat-ia'); ?>
                </h3>
                <div class="flavor-artista-redes">
                    <?php foreach ($redes as $red => $config):
                        if (empty($artista->$red)) continue;

                        $url = $artista->$red;
                        if (!filter_var($url, FILTER_VALIDATE_URL) && isset($config['url'])) {
                            $url = $config['url'] . $url;
                        }
                    ?>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="flavor-artista-red flavor-artista-red--<?php echo esc_attr($red); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($config['icon']); ?>"></span>
                            <span><?php echo esc_html($config['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prensa -->
            <?php if (!empty($artista->portfolio_prensa)): ?>
            <div class="flavor-artista-card">
                <h3 class="flavor-artista-card__titulo">
                    <span class="dashicons dashicons-media-document"></span>
                    <?php esc_html_e('Prensa', 'flavor-chat-ia'); ?>
                </h3>
                <ul class="flavor-artista-prensa">
                    <?php foreach ($artista->portfolio_prensa as $prensa): ?>
                        <li>
                            <a href="<?php echo esc_url($prensa['url'] ?? $prensa); ?>" target="_blank">
                                <?php echo esc_html($prensa['titulo'] ?? __('Ver artículo', 'flavor-chat-ia')); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

        </aside>

    </div>

</div>

<style>
.flavor-artista-perfil {
    --artista-primary: #ec4899;
    --artista-bg: #fdf2f8;
    max-width: 1200px;
    margin: 0 auto;
}

.flavor-artista-header {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.flavor-artista-header__bg {
    height: 200px;
    background: linear-gradient(135deg, var(--artista-primary) 0%, #8b5cf6 100%);
}

.flavor-artista-header__bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.3;
}

.flavor-artista-header__content {
    display: flex;
    align-items: flex-end;
    gap: 1.5rem;
    padding: 0 1.5rem 1.5rem;
    margin-top: -60px;
    position: relative;
    flex-wrap: wrap;
}

.flavor-artista-avatar {
    position: relative;
    flex-shrink: 0;
}

.flavor-artista-avatar img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    object-fit: cover;
}

.flavor-artista-verificado {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

.flavor-artista-info {
    flex: 1;
    min-width: 200px;
}

.flavor-artista-nombre {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    color: #1f2937;
}

.flavor-artista-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.flavor-artista-nivel {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.flavor-artista-nivel--emergente { background: #dbeafe; color: #1e40af; }
.flavor-artista-nivel--establecido { background: #d1fae5; color: #065f46; }
.flavor-artista-nivel--profesional { background: #fef3c7; color: #92400e; }
.flavor-artista-nivel--consagrado { background: #fce7f3; color: #9d174d; }

.flavor-artista-disciplina {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    background: var(--disciplina-color, #6b7280);
    color: white;
}

.flavor-artista-bio-corta {
    color: #6b7280;
    margin: 0;
    font-size: 0.9rem;
}

.flavor-artista-acciones {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.flavor-artista-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.flavor-artista-stat {
    text-align: center;
}

.flavor-artista-stat__valor {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.flavor-artista-stat__valor .dashicons {
    color: #f59e0b;
    font-size: 1.25rem;
    vertical-align: middle;
}

.flavor-artista-stat__label {
    font-size: 0.8rem;
    color: #6b7280;
}

.flavor-artista-contenido {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.5rem;
}

@media (max-width: 900px) {
    .flavor-artista-contenido {
        grid-template-columns: 1fr;
    }
}

.flavor-artista-main {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.flavor-artista-seccion {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.flavor-artista-seccion__titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: #1f2937;
}

.flavor-artista-seccion__titulo .dashicons {
    color: var(--artista-primary);
}

.flavor-artista-bio {
    line-height: 1.7;
    color: #374151;
}

.flavor-artista-portfolio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.flavor-artista-galeria {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.5rem;
}

.flavor-artista-galeria__item {
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
}

.flavor-artista-galeria__item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.flavor-artista-galeria__item:hover img {
    transform: scale(1.05);
}

.flavor-artista-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.flavor-artista-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.flavor-artista-card--disponible {
    border: 2px solid #10b981;
    background: #f0fdf4;
}

.flavor-artista-card__titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 0.75rem;
    color: #374151;
}

.flavor-artista-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.flavor-artista-tag {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #4b5563;
}

.flavor-artista-disponibilidad {
    list-style: none;
    padding: 0;
    margin: 0 0 1rem;
}

.flavor-artista-disponibilidad li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.9rem;
    color: #374151;
}

.flavor-artista-disponibilidad li:last-child {
    border-bottom: none;
}

.flavor-artista-disponibilidad .dashicons {
    color: #10b981;
    font-size: 1rem;
}

.flavor-artista-redes {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.flavor-artista-red {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: #f3f4f6;
    border-radius: 6px;
    color: #374151;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background 0.2s;
}

.flavor-artista-red:hover {
    background: #e5e7eb;
}

.flavor-artista-red--instagram .dashicons { color: #e4405f; }
.flavor-artista-red--youtube .dashicons { color: #ff0000; }
.flavor-artista-red--spotify .dashicons { color: #1db954; }

.flavor-artista-prensa {
    list-style: none;
    padding: 0;
    margin: 0;
}

.flavor-artista-prensa li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.flavor-artista-prensa li:last-child {
    border-bottom: none;
}

.flavor-artista-prensa a {
    color: var(--artista-primary);
    text-decoration: none;
}

.flavor-artista-prensa a:hover {
    text-decoration: underline;
}

/* Botones */
.flavor-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.flavor-btn--primary {
    background: var(--artista-primary);
    color: white;
}

.flavor-btn--primary:hover {
    background: #db2777;
}

.flavor-btn--secondary {
    background: #f3f4f6;
    color: #374151;
}

.flavor-btn--secondary:hover {
    background: #e5e7eb;
}

.flavor-btn--success {
    background: #10b981;
    color: white;
}

.flavor-btn--success:hover {
    background: #059669;
}

.flavor-btn--block {
    width: 100%;
    justify-content: center;
}

.flavor-artista-seguir[data-siguiendo="1"] {
    background: #d1fae5;
    color: #065f46;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botón seguir/dejar de seguir
    const botonSeguir = document.querySelector('.flavor-artista-seguir');
    if (botonSeguir) {
        botonSeguir.addEventListener('click', async function() {
            const artistaId = this.dataset.artista;
            const siguiendo = this.dataset.siguiendo === '1';

            this.disabled = true;

            try {
                const response = await fetch(`/wp-json/flavor/v1/artistas/${artistaId}/seguir`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.dataset.siguiendo = data.siguiendo ? '1' : '0';
                    const icono = this.querySelector('.dashicons');
                    const texto = this.querySelector('.texto');

                    if (data.siguiendo) {
                        icono.className = 'dashicons dashicons-yes';
                        texto.textContent = '<?php esc_html_e('Siguiendo', 'flavor-chat-ia'); ?>';
                    } else {
                        icono.className = 'dashicons dashicons-plus-alt2';
                        texto.textContent = '<?php esc_html_e('Seguir', 'flavor-chat-ia'); ?>';
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }

            this.disabled = false;
        });
    }
});
</script>

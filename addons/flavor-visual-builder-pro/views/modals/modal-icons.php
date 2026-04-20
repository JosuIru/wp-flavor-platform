<?php
/**
 * Visual Builder Pro - Modal Selector de Iconos Mejorado
 *
 * Incluye Material Icons, Font Awesome 6 y SVG personalizado
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Lista de Material Icons disponibles (expandida)
$material_icons = array(
    // Navegación
    'home', 'menu', 'arrow_back', 'arrow_forward', 'close', 'expand_more', 'expand_less',
    'chevron_left', 'chevron_right', 'apps', 'more_vert', 'more_horiz', 'refresh',
    // Comunicación
    'chat', 'chat_bubble', 'email', 'phone', 'call', 'message', 'forum', 'comment',
    'notifications', 'announcement', 'campaign', 'contact_mail', 'contact_phone',
    // Social
    'people', 'person', 'group', 'groups', 'person_add', 'share', 'thumb_up',
    'thumb_down', 'favorite', 'favorite_border', 'public', 'language',
    // Comercio
    'shopping_cart', 'shopping_bag', 'store', 'storefront', 'local_offer',
    'attach_money', 'payments', 'credit_card', 'receipt', 'sell', 'inventory',
    // Contenido
    'edit', 'delete', 'add', 'remove', 'save', 'content_copy', 'content_paste',
    'create', 'mode_edit', 'add_circle', 'remove_circle', 'check_circle',
    // Archivos
    'folder', 'folder_open', 'file_copy', 'description', 'upload_file', 'download',
    'cloud', 'cloud_upload', 'cloud_download', 'attachment', 'link',
    // Media
    'image', 'photo_camera', 'videocam', 'play_arrow', 'pause', 'stop',
    'volume_up', 'volume_off', 'mic', 'music_note', 'movie',
    // Mapas y ubicación
    'location_on', 'place', 'map', 'directions', 'navigation', 'my_location',
    'explore', 'local_shipping', 'flight', 'directions_car', 'directions_bike',
    // Tiempo y calendario
    'schedule', 'access_time', 'today', 'event', 'calendar_today', 'date_range',
    'alarm', 'timer', 'history', 'update',
    // Herramientas
    'settings', 'build', 'construction', 'handyman', 'engineering', 'tune',
    'admin_panel_settings', 'manage_accounts', 'security', 'lock', 'vpn_key',
    // Educación
    'school', 'menu_book', 'auto_stories', 'library_books', 'class',
    'science', 'biotech', 'psychology', 'architecture',
    // Salud
    'local_hospital', 'medical_services', 'health_and_safety', 'healing',
    'spa', 'self_improvement', 'fitness_center', 'sports',
    // Negocios
    'work', 'business', 'domain', 'corporate_fare', 'account_balance',
    'analytics', 'trending_up', 'trending_down', 'insights', 'bar_chart',
    // Naturaleza
    'eco', 'nature', 'park', 'grass', 'forest', 'water_drop', 'air',
    'wb_sunny', 'nights_stay', 'cloud', 'thunderstorm',
    // Misceláneo
    'star', 'star_border', 'grade', 'verified', 'workspace_premium',
    'emoji_events', 'military_tech', 'diamond', 'rocket_launch', 'lightbulb',
    'info', 'help', 'warning', 'error', 'priority_high', 'report_problem',
    'pets', 'restaurant', 'local_cafe', 'local_dining', 'cake', 'local_bar',
);

// Font Awesome 6 Free Icons organizados por categoría
$fontawesome_categories = array(
    'solid' => array(
        'label' => __( 'Sólidos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        'icons' => array(
            // Flechas
            'fa-arrow-up', 'fa-arrow-down', 'fa-arrow-left', 'fa-arrow-right',
            'fa-angles-up', 'fa-angles-down', 'fa-chevron-up', 'fa-chevron-down',
            'fa-chevron-left', 'fa-chevron-right', 'fa-circle-arrow-up', 'fa-circle-arrow-down',
            // Social/Comunicación
            'fa-user', 'fa-users', 'fa-user-plus', 'fa-user-group', 'fa-people-group',
            'fa-comment', 'fa-comments', 'fa-message', 'fa-envelope', 'fa-phone',
            'fa-phone-volume', 'fa-paper-plane', 'fa-at', 'fa-share', 'fa-share-nodes',
            // Comercio
            'fa-cart-shopping', 'fa-bag-shopping', 'fa-basket-shopping', 'fa-store',
            'fa-shop', 'fa-credit-card', 'fa-money-bill', 'fa-wallet', 'fa-coins',
            'fa-cash-register', 'fa-receipt', 'fa-tag', 'fa-tags', 'fa-percent',
            // UI/Acciones
            'fa-house', 'fa-gear', 'fa-sliders', 'fa-bars', 'fa-xmark', 'fa-check',
            'fa-plus', 'fa-minus', 'fa-magnifying-glass', 'fa-pen', 'fa-pencil',
            'fa-trash', 'fa-copy', 'fa-paste', 'fa-download', 'fa-upload',
            'fa-link', 'fa-unlink', 'fa-lock', 'fa-unlock', 'fa-key',
            // Media
            'fa-image', 'fa-images', 'fa-camera', 'fa-video', 'fa-play',
            'fa-pause', 'fa-stop', 'fa-forward', 'fa-backward', 'fa-volume-high',
            'fa-volume-low', 'fa-volume-xmark', 'fa-microphone', 'fa-headphones', 'fa-music',
            // Archivos
            'fa-file', 'fa-folder', 'fa-folder-open', 'fa-file-pdf', 'fa-file-word',
            'fa-file-excel', 'fa-file-powerpoint', 'fa-file-image', 'fa-file-video', 'fa-file-audio',
            'fa-file-code', 'fa-file-zipper', 'fa-cloud', 'fa-cloud-arrow-up', 'fa-cloud-arrow-down',
            // Ubicación
            'fa-location-dot', 'fa-map', 'fa-map-pin', 'fa-compass', 'fa-globe',
            'fa-earth-americas', 'fa-earth-europe', 'fa-earth-asia', 'fa-building', 'fa-city',
            'fa-car', 'fa-bus', 'fa-train', 'fa-plane', 'fa-ship',
            // Tiempo
            'fa-clock', 'fa-calendar', 'fa-calendar-days', 'fa-calendar-check', 'fa-hourglass',
            'fa-stopwatch', 'fa-bell', 'fa-bell-slash', 'fa-history',
            // Símbolos
            'fa-heart', 'fa-star', 'fa-fire', 'fa-bolt', 'fa-sun',
            'fa-moon', 'fa-cloud-sun', 'fa-snowflake', 'fa-umbrella', 'fa-leaf',
            'fa-seedling', 'fa-tree', 'fa-water', 'fa-mountain', 'fa-paw',
            // Objetos
            'fa-gift', 'fa-trophy', 'fa-medal', 'fa-crown', 'fa-gem',
            'fa-lightbulb', 'fa-rocket', 'fa-flag', 'fa-bookmark', 'fa-thumbtack',
            // Educación/Trabajo
            'fa-graduation-cap', 'fa-book', 'fa-book-open', 'fa-pencil', 'fa-pen-ruler',
            'fa-briefcase', 'fa-laptop', 'fa-desktop', 'fa-mobile', 'fa-tablet',
            // Salud
            'fa-heart-pulse', 'fa-stethoscope', 'fa-hospital', 'fa-pills', 'fa-syringe',
            'fa-dumbbell', 'fa-spa', 'fa-brain', 'fa-eye', 'fa-hand',
            // Comida
            'fa-utensils', 'fa-plate-wheat', 'fa-mug-hot', 'fa-wine-glass', 'fa-martini-glass',
            'fa-pizza-slice', 'fa-burger', 'fa-ice-cream', 'fa-cookie', 'fa-apple-whole',
            // Seguridad
            'fa-shield', 'fa-shield-halved', 'fa-user-shield', 'fa-fingerprint', 'fa-id-card',
            'fa-circle-check', 'fa-circle-xmark', 'fa-circle-info', 'fa-circle-exclamation', 'fa-triangle-exclamation',
        ),
    ),
    'brands' => array(
        'label' => __( 'Marcas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        'icons' => array(
            // Redes Sociales
            'fa-facebook', 'fa-facebook-f', 'fa-twitter', 'fa-x-twitter', 'fa-instagram',
            'fa-linkedin', 'fa-linkedin-in', 'fa-youtube', 'fa-tiktok', 'fa-pinterest',
            'fa-whatsapp', 'fa-telegram', 'fa-discord', 'fa-slack', 'fa-reddit',
            'fa-snapchat', 'fa-twitch', 'fa-mastodon', 'fa-threads',
            // Tech
            'fa-google', 'fa-apple', 'fa-microsoft', 'fa-amazon', 'fa-github',
            'fa-gitlab', 'fa-bitbucket', 'fa-docker', 'fa-linux', 'fa-windows',
            'fa-android', 'fa-chrome', 'fa-firefox', 'fa-safari', 'fa-edge',
            'fa-wordpress', 'fa-wix', 'fa-shopify', 'fa-stripe', 'fa-paypal',
            // Desarrollo
            'fa-html5', 'fa-css3-alt', 'fa-js', 'fa-php', 'fa-python',
            'fa-react', 'fa-vuejs', 'fa-angular', 'fa-node-js', 'fa-npm',
            'fa-laravel', 'fa-symfony', 'fa-drupal', 'fa-joomla', 'fa-magento',
            // Servicios
            'fa-dropbox', 'fa-google-drive', 'fa-spotify', 'fa-soundcloud', 'fa-airbnb',
            'fa-uber', 'fa-lyft', 'fa-bitcoin', 'fa-ethereum', 'fa-cc-visa',
            'fa-cc-mastercard', 'fa-cc-amex', 'fa-cc-paypal', 'fa-cc-stripe', 'fa-cc-apple-pay',
        ),
    ),
);
?>
<div id="vbp-icon-modal" class="vbp-modal-overlay" x-data="vbpIconSelector()" x-show="$store.vbpModals.iconSelector.open" x-cloak @keydown.escape.window="closeModal()">
    <div class="vbp-modal vbp-modal-large" @click.outside="closeModal()">
        <div class="vbp-modal-header">
            <h3 class="vbp-modal-title"><?php esc_html_e( 'Seleccionar icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
            <button type="button" @click="closeModal()" class="vbp-modal-close">&times;</button>
        </div>

        <div class="vbp-modal-tabs vbp-modal-tabs-scrollable">
            <button type="button"
                    @click="activeTab = 'material'"
                    :class="{ 'active': activeTab === 'material' }"
                    class="vbp-modal-tab">
                <span class="material-icons">category</span>
                <?php esc_html_e( 'Material', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </button>
            <button type="button"
                    @click="activeTab = 'fontawesome'"
                    :class="{ 'active': activeTab === 'fontawesome' }"
                    class="vbp-modal-tab">
                <i class="fa-solid fa-font-awesome"></i>
                <?php esc_html_e( 'Font Awesome', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </button>
            <button type="button"
                    @click="activeTab = 'brands'"
                    :class="{ 'active': activeTab === 'brands' }"
                    class="vbp-modal-tab">
                <i class="fa-brands fa-font-awesome"></i>
                <?php esc_html_e( 'Marcas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </button>
            <button type="button"
                    @click="activeTab = 'svg'"
                    :class="{ 'active': activeTab === 'svg' }"
                    class="vbp-modal-tab">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                <?php esc_html_e( 'SVG', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <!-- Barra de búsqueda común -->
        <div class="vbp-modal-search-bar">
            <div class="vbp-icon-search">
                <svg class="vbp-icon-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text"
                       x-model="searchQuery"
                       @input="filterIcons()"
                       :placeholder="activeTab === 'svg' ? '<?php esc_attr_e( 'Buscar en biblioteca...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>' : '<?php esc_attr_e( 'Buscar icono...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>'"
                       class="vbp-icon-search-input">
                <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="vbp-search-clear">&times;</button>
            </div>
        </div>

        <!-- Tab: Material Icons -->
        <div x-show="activeTab === 'material'" class="vbp-modal-content vbp-modal-content-icons">
            <div class="vbp-icon-grid vbp-icon-grid-large">
                <?php foreach ( $material_icons as $icon_name ) : ?>
                <button type="button"
                        class="vbp-icon-option"
                        data-icon="<?php echo esc_attr( $icon_name ); ?>"
                        data-type="material"
                        @click="selectIcon('material', '<?php echo esc_attr( $icon_name ); ?>')"
                        x-show="isIconVisible('<?php echo esc_attr( $icon_name ); ?>')"
                        :class="{ 'selected': selectedIcon === '<?php echo esc_attr( $icon_name ); ?>' && selectedType === 'material' }"
                        title="<?php echo esc_attr( $icon_name ); ?>">
                    <span class="material-icons"><?php echo esc_html( $icon_name ); ?></span>
                    <span class="vbp-icon-name"><?php echo esc_html( $icon_name ); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab: Font Awesome Solid -->
        <div x-show="activeTab === 'fontawesome'" class="vbp-modal-content vbp-modal-content-icons">
            <div class="vbp-icon-grid vbp-icon-grid-large">
                <?php foreach ( $fontawesome_categories['solid']['icons'] as $icon_name ) :
                    $icon_display = str_replace( 'fa-', '', $icon_name );
                ?>
                <button type="button"
                        class="vbp-icon-option"
                        data-icon="<?php echo esc_attr( $icon_name ); ?>"
                        data-type="fontawesome"
                        @click="selectIcon('fontawesome', 'fa-solid <?php echo esc_attr( $icon_name ); ?>')"
                        x-show="isIconVisible('<?php echo esc_attr( $icon_display ); ?>')"
                        :class="{ 'selected': selectedIcon === 'fa-solid <?php echo esc_attr( $icon_name ); ?>' && selectedType === 'fontawesome' }"
                        title="<?php echo esc_attr( $icon_display ); ?>">
                    <i class="fa-solid <?php echo esc_attr( $icon_name ); ?>"></i>
                    <span class="vbp-icon-name"><?php echo esc_html( $icon_display ); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab: Font Awesome Brands -->
        <div x-show="activeTab === 'brands'" class="vbp-modal-content vbp-modal-content-icons">
            <div class="vbp-icon-grid vbp-icon-grid-large">
                <?php foreach ( $fontawesome_categories['brands']['icons'] as $icon_name ) :
                    $icon_display = str_replace( 'fa-', '', $icon_name );
                ?>
                <button type="button"
                        class="vbp-icon-option"
                        data-icon="<?php echo esc_attr( $icon_name ); ?>"
                        data-type="fontawesome-brand"
                        @click="selectIcon('fontawesome', 'fa-brands <?php echo esc_attr( $icon_name ); ?>')"
                        x-show="isIconVisible('<?php echo esc_attr( $icon_display ); ?>')"
                        :class="{ 'selected': selectedIcon === 'fa-brands <?php echo esc_attr( $icon_name ); ?>' && selectedType === 'fontawesome' }"
                        title="<?php echo esc_attr( $icon_display ); ?>">
                    <i class="fa-brands <?php echo esc_attr( $icon_name ); ?>"></i>
                    <span class="vbp-icon-name"><?php echo esc_html( $icon_display ); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab: SVG Personalizado -->
        <div x-show="activeTab === 'svg'" class="vbp-modal-content">
            <div class="vbp-svg-upload-area">
                <div class="vbp-svg-preview" x-show="customSvgUrl">
                    <img :src="customSvgUrl" alt="SVG personalizado" class="vbp-svg-preview-img">
                    <button type="button" @click="clearCustomSvg()" class="vbp-svg-remove">&times;</button>
                </div>

                <div class="vbp-svg-upload-box" x-show="!customSvgUrl">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p><?php esc_html_e( 'Arrastra un SVG aquí o', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                    <button type="button" @click="openMediaLibrarySvg()" class="vbp-btn vbp-btn-secondary">
                        <?php esc_html_e( 'Seleccionar de la biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>

            <div class="vbp-svg-info">
                <p class="vbp-info-text">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                    <?php esc_html_e( 'Sube archivos SVG para usar como iconos personalizados.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </p>
            </div>
        </div>

        <div class="vbp-modal-footer">
            <div class="vbp-selected-preview" x-show="selectedIcon || customSvgUrl">
                <span class="vbp-preview-label"><?php esc_html_e( 'Seleccionado:', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                <template x-if="selectedType === 'material'">
                    <span class="material-icons" x-text="selectedIcon"></span>
                </template>
                <template x-if="selectedType === 'fontawesome'">
                    <i :class="selectedIcon"></i>
                </template>
                <template x-if="selectedType === 'svg'">
                    <img :src="customSvgUrl" class="vbp-preview-svg">
                </template>
                <span class="vbp-preview-name" x-text="selectedType === 'svg' ? 'SVG personalizado' : selectedIcon"></span>
            </div>
            <div class="vbp-modal-actions">
                <button type="button" @click="closeModal()" class="vbp-btn vbp-btn-ghost">
                    <?php esc_html_e( 'Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
                <button type="button"
                        @click="confirmSelection()"
                        class="vbp-btn vbp-btn-primary"
                        :disabled="!selectedIcon && !customSvgUrl">
                    <?php esc_html_e( 'Aplicar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos adicionales para el modal de iconos mejorado */
.vbp-modal-large {
    max-width: 900px;
    width: 95%;
}

.vbp-modal-tabs-scrollable {
    display: flex;
    gap: 0.25rem;
    overflow-x: auto;
    padding: 0.75rem 1rem;
    scrollbar-width: thin;
}

.vbp-modal-search-bar {
    padding: 0 1rem 0.75rem;
    border-bottom: 1px solid var(--vbp-border);
}

.vbp-icon-search {
    position: relative;
    display: flex;
    align-items: center;
}

.vbp-search-clear {
    position: absolute;
    right: 8px;
    background: none;
    border: none;
    color: var(--vbp-text-muted);
    cursor: pointer;
    font-size: 1.25rem;
    padding: 0.25rem;
    line-height: 1;
}

.vbp-search-clear:hover {
    color: var(--vbp-text);
}

.vbp-modal-content-icons {
    max-height: 400px;
    overflow-y: auto;
    padding: 1rem;
}

.vbp-icon-grid-large {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 0.5rem;
}

.vbp-icon-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 0.5rem;
    border: 1px solid var(--vbp-border);
    border-radius: 8px;
    background: var(--vbp-bg);
    cursor: pointer;
    transition: all 0.15s ease;
    gap: 0.35rem;
    min-height: 70px;
}

.vbp-icon-option:hover {
    border-color: var(--vbp-primary);
    background: var(--vbp-bg-hover);
}

.vbp-icon-option.selected {
    border-color: var(--vbp-primary);
    background: var(--vbp-primary-light);
    box-shadow: 0 0 0 2px var(--vbp-primary-alpha);
}

.vbp-icon-option .material-icons,
.vbp-icon-option i {
    font-size: 24px;
    color: var(--vbp-text);
}

.vbp-icon-option.selected .material-icons,
.vbp-icon-option.selected i {
    color: var(--vbp-primary);
}

.vbp-icon-name {
    font-size: 0.65rem;
    color: var(--vbp-text-muted);
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

.vbp-selected-preview {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: var(--vbp-bg-elevated);
    border-radius: 6px;
}

.vbp-preview-label {
    font-size: 0.8rem;
    color: var(--vbp-text-muted);
}

.vbp-preview-name {
    font-size: 0.85rem;
    font-family: monospace;
    color: var(--vbp-text);
}

.vbp-selected-preview .material-icons,
.vbp-selected-preview i {
    font-size: 28px;
    color: var(--vbp-primary);
}

.vbp-preview-svg {
    width: 28px;
    height: 28px;
    object-fit: contain;
}

.vbp-modal-tab i {
    font-size: 14px;
}

/* Scrollbar para el grid de iconos */
.vbp-modal-content-icons::-webkit-scrollbar {
    width: 8px;
}

.vbp-modal-content-icons::-webkit-scrollbar-track {
    background: var(--vbp-bg);
    border-radius: 4px;
}

.vbp-modal-content-icons::-webkit-scrollbar-thumb {
    background: var(--vbp-border);
    border-radius: 4px;
}

.vbp-modal-content-icons::-webkit-scrollbar-thumb:hover {
    background: var(--vbp-text-muted);
}
</style>

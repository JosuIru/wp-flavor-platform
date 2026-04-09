<?php
/**
 * Themacle Filters Bar Component
 *
 * Renders a taxonomy-based filter bar in one of three visual styles:
 *   - underline : horizontal text links with underline on active/hover
 *   - pills     : rounded pill buttons, active one filled with --flavor-primary
 *   - dropdown  : <select> element for mobile-friendly filtering
 *
 * Each filter element carries a data-term-slug attribute so JavaScript
 * can bind filtering behaviour independently.
 *
 * @package FlavorChatIA
 *
 * @var string $taxonomia         Taxonomy slug to pull terms from.
 * @var string $estilo            Display style: 'underline', 'pills', or 'dropdown'.
 * @var string $component_classes Additional CSS classes for the wrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$taxonomia         = isset( $taxonomia ) ? $taxonomia : '';
$estilo            = isset( $estilo ) ? $estilo : 'underline';
$component_classes = isset( $component_classes ) ? $component_classes : '';

if ( ! $taxonomia || ! taxonomy_exists( $taxonomia ) ) {
    return;
}

$terminos = get_terms(
    array(
        'taxonomy'   => $taxonomia,
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    )
);

if ( is_wp_error( $terminos ) || empty( $terminos ) ) {
    return;
}

$texto_todos        = __( 'Todos', FLAVOR_PLATFORM_TEXT_DOMAIN );
$estilos_permitidos = array( 'underline', 'pills', 'dropdown' );
if ( ! in_array( $estilo, $estilos_permitidos, true ) ) {
    $estilo = 'underline';
}
?>

<nav
    class="flavor-filters-bar w-full <?php echo esc_attr( $component_classes ); ?>"
    aria-label="<?php echo esc_attr__( 'Filtros', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
    data-taxonomy="<?php echo esc_attr( $taxonomia ); ?>"
    data-style="<?php echo esc_attr( $estilo ); ?>"
>
    <?php if ( 'underline' === $estilo ) : ?>
        <ul class="flavor-filters-bar__list flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-gray-200 pb-2">
            <li>
                <button
                    type="button"
                    class="flavor-filters-bar__item flavor-filters-bar__item--active relative pb-2 text-sm font-medium transition-colors duration-200 sm:text-base"
                    style="color: var(--flavor-primary, #2563eb);"
                    data-term-slug="all"
                    aria-pressed="true"
                >
                    <?php echo esc_html( $texto_todos ); ?>
                    <span
                        class="flavor-filters-bar__indicator absolute bottom-0 left-0 h-0.5 w-full"
                        style="background-color: var(--flavor-primary, #2563eb);"
                    ></span>
                </button>
            </li>
            <?php foreach ( $terminos as $termino ) : ?>
                <li>
                    <button
                        type="button"
                        class="flavor-filters-bar__item relative pb-2 text-sm font-medium text-gray-500 transition-colors duration-200 hover:text-gray-900 sm:text-base"
                        data-term-slug="<?php echo esc_attr( $termino->slug ); ?>"
                        aria-pressed="false"
                    >
                        <?php echo esc_html( $termino->name ); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

    <?php elseif ( 'pills' === $estilo ) : ?>
        <div class="flavor-filters-bar__pills flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="flavor-filters-bar__pill flavor-filters-bar__pill--active inline-block rounded-full px-4 py-1.5 text-sm font-medium text-white transition-colors duration-200 sm:text-base"
                style="background-color: var(--flavor-primary, #2563eb);"
                data-term-slug="all"
                aria-pressed="true"
            >
                <?php echo esc_html( $texto_todos ); ?>
            </button>
            <?php foreach ( $terminos as $termino ) : ?>
                <button
                    type="button"
                    class="flavor-filters-bar__pill inline-block rounded-full border px-4 py-1.5 text-sm font-medium transition-colors duration-200 sm:text-base"
                    style="border-color: var(--flavor-primary, #2563eb); color: var(--flavor-primary, #2563eb); background-color: transparent;"
                    data-term-slug="<?php echo esc_attr( $termino->slug ); ?>"
                    aria-pressed="false"
                >
                    <?php echo esc_html( $termino->name ); ?>
                </button>
            <?php endforeach; ?>
        </div>

    <?php elseif ( 'dropdown' === $estilo ) : ?>
        <?php
        $identificador_select = 'flavor-filters-dropdown-' . wp_unique_id();
        ?>
        <div class="flavor-filters-bar__dropdown inline-block w-full max-w-xs">
            <label for="<?php echo esc_attr( $identificador_select ); ?>" class="sr-only">
                <?php echo esc_html__( 'Filtrar por', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </label>
            <select
                id="<?php echo esc_attr( $identificador_select ); ?>"
                class="flavor-filters-bar__select w-full appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-10 text-sm text-gray-900 transition-colors duration-200 focus:outline-none focus:ring-2 sm:text-base"
                style="--tw-ring-color: var(--flavor-primary, #2563eb);"
                data-term-slug="all"
            >
                <option value="all" data-term-slug="all" selected>
                    <?php echo esc_html( $texto_todos ); ?>
                </option>
                <?php foreach ( $terminos as $termino ) : ?>
                    <option
                        value="<?php echo esc_attr( $termino->slug ); ?>"
                        data-term-slug="<?php echo esc_attr( $termino->slug ); ?>"
                    >
                        <?php echo esc_html( $termino->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
</nav>

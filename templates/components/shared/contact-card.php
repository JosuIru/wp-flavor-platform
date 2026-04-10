<?php
/**
 * Componente: Contact Card
 *
 * Tarjeta de contacto con información y acciones.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param string $name        Nombre completo
 * @param string $role        Rol/cargo
 * @param string $avatar      URL del avatar
 * @param string $email       Email
 * @param string $phone       Teléfono
 * @param string $whatsapp    WhatsApp
 * @param string $address     Dirección
 * @param array  $social      Redes sociales: ['twitter' => '', 'linkedin' => '', 'website' => '']
 * @param string $bio         Biografía corta
 * @param string $variant     Variante: default, compact, horizontal, minimal
 * @param bool   $show_actions Mostrar botones de acción
 * @param string $profile_url URL del perfil completo
 */

if (!defined('ABSPATH')) {
    exit;
}

$name = $name ?? '';
$role = $role ?? '';
$avatar = $avatar ?? '';
$email = $email ?? '';
$phone = $phone ?? '';
$whatsapp = $whatsapp ?? '';
$address = $address ?? '';
$social = $social ?? [];
$bio = $bio ?? '';
$variant = $variant ?? 'default';
$show_actions = $show_actions ?? true;
$profile_url = $profile_url ?? '';

// Generar iniciales si no hay avatar
$initials = '';
if (!$avatar && $name) {
    $parts = explode(' ', trim($name));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr(end($parts), 0, 1));
    }
}

// Colores para avatar con iniciales
$avatar_colors = [
    'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500',
    'bg-pink-500', 'bg-teal-500', 'bg-indigo-500', 'bg-red-500'
];
$avatar_color = $avatar_colors[crc32($name) % count($avatar_colors)];
?>

<?php if ($variant === 'minimal'): ?>
    <!-- Variante Minimal -->
    <div class="flavor-contact-card flex items-center gap-3">
        <?php if ($avatar): ?>
            <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($name); ?>" class="w-10 h-10 rounded-full object-cover">
        <?php else: ?>
            <div class="w-10 h-10 rounded-full <?php echo esc_attr($avatar_color); ?> flex items-center justify-center text-white font-semibold">
                <?php echo esc_html($initials); ?>
            </div>
        <?php endif; ?>
        <div class="min-w-0">
            <p class="font-medium text-gray-900 truncate"><?php echo esc_html($name); ?></p>
            <?php if ($role): ?>
                <p class="text-sm text-gray-500 truncate"><?php echo esc_html($role); ?></p>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($variant === 'compact'): ?>
    <!-- Variante Compact -->
    <div class="flavor-contact-card bg-white rounded-xl shadow-sm border p-4">
        <div class="flex items-center gap-4">
            <?php if ($avatar): ?>
                <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($name); ?>" class="w-14 h-14 rounded-full object-cover">
            <?php else: ?>
                <div class="w-14 h-14 rounded-full <?php echo esc_attr($avatar_color); ?> flex items-center justify-center text-white text-lg font-bold">
                    <?php echo esc_html($initials); ?>
                </div>
            <?php endif; ?>

            <div class="flex-1 min-w-0">
                <h4 class="font-semibold text-gray-900 truncate"><?php echo esc_html($name); ?></h4>
                <?php if ($role): ?>
                    <p class="text-sm text-gray-500"><?php echo esc_html($role); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($show_actions): ?>
                <div class="flex items-center gap-2">
                    <?php if ($email): ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="p-2 rounded-lg bg-gray-100 hover:bg-blue-100 text-gray-600 hover:text-blue-600 transition-colors" title="Email">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($phone || $whatsapp): ?>
                        <a href="<?php echo $whatsapp ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) : 'tel:' . esc_attr($phone); ?>" class="p-2 rounded-lg bg-gray-100 hover:bg-green-100 text-gray-600 hover:text-green-600 transition-colors" title="<?php echo $whatsapp ? 'WhatsApp' : 'Llamar'; ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($variant === 'horizontal'): ?>
    <!-- Variante Horizontal -->
    <div class="flavor-contact-card bg-white rounded-xl shadow-md overflow-hidden">
        <div class="flex flex-col sm:flex-row">
            <!-- Avatar lado izquierdo -->
            <div class="sm:w-1/3 bg-gradient-to-br from-gray-100 to-gray-200 p-6 flex items-center justify-center">
                <?php if ($avatar): ?>
                    <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($name); ?>" class="w-24 h-24 sm:w-32 sm:h-32 rounded-full object-cover border-4 border-white shadow-lg">
                <?php else: ?>
                    <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-full <?php echo esc_attr($avatar_color); ?> flex items-center justify-center text-white text-3xl font-bold border-4 border-white shadow-lg">
                        <?php echo esc_html($initials); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info lado derecho -->
            <div class="flex-1 p-6">
                <h3 class="text-xl font-bold text-gray-900"><?php echo esc_html($name); ?></h3>
                <?php if ($role): ?>
                    <p class="text-blue-600 font-medium"><?php echo esc_html($role); ?></p>
                <?php endif; ?>

                <?php if ($bio): ?>
                    <p class="mt-3 text-gray-600 text-sm"><?php echo esc_html($bio); ?></p>
                <?php endif; ?>

                <!-- Datos de contacto -->
                <div class="mt-4 space-y-2">
                    <?php if ($email): ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <?php echo esc_html($email); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($phone): ?>
                        <a href="tel:<?php echo esc_attr($phone); ?>" class="flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <?php echo esc_html($phone); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($address): ?>
                        <p class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <?php echo esc_html($address); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Redes sociales -->
                <?php if (!empty($social)): ?>
                    <div class="mt-4 flex items-center gap-2">
                        <?php if (!empty($social['twitter'])): ?>
                            <a href="<?php echo esc_url($social['twitter']); ?>" target="_blank" rel="noopener" class="p-2 bg-gray-100 rounded-lg hover:bg-blue-100 text-gray-600 hover:text-blue-500 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($social['linkedin'])): ?>
                            <a href="<?php echo esc_url($social['linkedin']); ?>" target="_blank" rel="noopener" class="p-2 bg-gray-100 rounded-lg hover:bg-blue-100 text-gray-600 hover:text-blue-700 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($social['website'])): ?>
                            <a href="<?php echo esc_url($social['website']); ?>" target="_blank" rel="noopener" class="p-2 bg-gray-100 rounded-lg hover:bg-blue-100 text-gray-600 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Variante Default (card vertical) -->
    <div class="flavor-contact-card bg-white rounded-xl shadow-md overflow-hidden text-center">
        <!-- Header con gradiente -->
        <div class="h-20 bg-gradient-to-r from-blue-500 to-purple-500"></div>

        <!-- Avatar -->
        <div class="-mt-12 relative z-10">
            <?php if ($avatar): ?>
                <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($name); ?>" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg mx-auto">
            <?php else: ?>
                <div class="w-24 h-24 rounded-full <?php echo esc_attr($avatar_color); ?> flex items-center justify-center text-white text-2xl font-bold border-4 border-white shadow-lg mx-auto">
                    <?php echo esc_html($initials); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="px-6 pt-4 pb-6">
            <h3 class="text-xl font-bold text-gray-900"><?php echo esc_html($name); ?></h3>
            <?php if ($role): ?>
                <p class="text-blue-600 font-medium"><?php echo esc_html($role); ?></p>
            <?php endif; ?>

            <?php if ($bio): ?>
                <p class="mt-3 text-gray-600 text-sm"><?php echo esc_html($bio); ?></p>
            <?php endif; ?>

            <!-- Datos de contacto -->
            <div class="mt-4 space-y-2 text-sm">
                <?php if ($email): ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="flex items-center justify-center gap-2 text-gray-600 hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <?php echo esc_html($email); ?>
                    </a>
                <?php endif; ?>
                <?php if ($phone): ?>
                    <a href="tel:<?php echo esc_attr($phone); ?>" class="flex items-center justify-center gap-2 text-gray-600 hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <?php echo esc_html($phone); ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Acciones -->
            <?php if ($show_actions && ($email || $whatsapp || $profile_url)): ?>
                <div class="mt-5 flex items-center justify-center gap-3">
                    <?php if ($email): ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <?php esc_html_e('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($profile_url): ?>
                        <a href="<?php echo esc_url($profile_url); ?>" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            <?php esc_html_e('Ver perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Redes sociales -->
            <?php if (!empty($social)): ?>
                <div class="mt-4 flex items-center justify-center gap-3">
                    <?php foreach ($social as $network => $url): ?>
                        <?php if ($url): ?>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <?php if ($network === 'twitter'): ?>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                <?php elseif ($network === 'linkedin'): ?>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                <?php elseif ($network === 'website'): ?>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

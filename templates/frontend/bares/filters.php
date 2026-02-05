<?php
/**
 * Frontend: Filtros de Bares y Restaurantes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-bares-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-orange-600 hover:text-orange-700 font-medium">Limpiar</a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Tipo de cocina -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Tipo de cocina</h4>
                <div class="space-y-2">
                    <?php
                    $tipos_cocina = [
                        'espanola' => '🇪🇸 Espanola',
                        'italiana' => '🇮🇹 Italiana',
                        'mexicana' => '🇲🇽 Mexicana',
                        'japonesa' => '🇯🇵 Japonesa',
                        'china' => '🇨🇳 China',
                        'americana' => '🇺🇸 Americana',
                        'mediterranea' => '🫒 Mediterranea',
                        'fusion' => '🍴 Fusion',
                    ];
                    foreach ($tipos_cocina as $valor_cocina => $etiqueta_cocina):
                        $marcado_cocina = in_array($valor_cocina, $filtros_activos['tipo_cocina'] ?? []) ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="tipo_cocina[]" value="<?php echo esc_attr($valor_cocina); ?>"
                               <?php echo $marcado_cocina; ?>
                               class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-sm text-gray-700 group-hover:text-orange-600 transition-colors">
                            <?php echo esc_html($etiqueta_cocina); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Rango de precio -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Rango de precio</h4>
                <select name="precio" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-orange-500">
                    <option value="">Cualquier precio</option>
                    <option value="1" <?php echo ($filtros_activos['precio'] ?? '') === '1' ? 'selected' : ''; ?>>€ - Economico</option>
                    <option value="2" <?php echo ($filtros_activos['precio'] ?? '') === '2' ? 'selected' : ''; ?>>€€ - Moderado</option>
                    <option value="3" <?php echo ($filtros_activos['precio'] ?? '') === '3' ? 'selected' : ''; ?>>€€€ - Premium</option>
                </select>
            </div>

            <!-- Distancia -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Distancia</h4>
                <select name="distancia" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-orange-500">
                    <option value="">Cualquier distancia</option>
                    <option value="500" <?php echo ($filtros_activos['distancia'] ?? '') === '500' ? 'selected' : ''; ?>>Menos de 500m</option>
                    <option value="1000" <?php echo ($filtros_activos['distancia'] ?? '') === '1000' ? 'selected' : ''; ?>>Menos de 1 km</option>
                    <option value="3000" <?php echo ($filtros_activos['distancia'] ?? '') === '3000' ? 'selected' : ''; ?>>Menos de 3 km</option>
                    <option value="5000" <?php echo ($filtros_activos['distancia'] ?? '') === '5000' ? 'selected' : ''; ?>>Menos de 5 km</option>
                </select>
            </div>

            <!-- Servicios -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Servicios</h4>
                <div class="space-y-2">
                    <?php
                    $servicios_local = [
                        'terraza' => '☀️ Terraza',
                        'wifi' => '📶 WiFi gratis',
                        'reservas' => '📅 Reservas online',
                        'musica' => '🎵 Musica en vivo',
                        'delivery' => '🛵 Entrega a domicilio',
                        'accesible' => '♿ Accesible',
                    ];
                    foreach ($servicios_local as $valor_servicio => $etiqueta_servicio):
                        $marcado_servicio = in_array($valor_servicio, $filtros_activos['servicios'] ?? []) ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="servicios[]" value="<?php echo esc_attr($valor_servicio); ?>"
                               <?php echo $marcado_servicio; ?>
                               class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-sm text-gray-700 group-hover:text-orange-600 transition-colors">
                            <?php echo esc_html($etiqueta_servicio); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Valoracion minima -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Valoracion minima</h4>
                <select name="valoracion" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-orange-500">
                    <option value="">Cualquiera</option>
                    <option value="3" <?php echo ($filtros_activos['valoracion'] ?? '') === '3' ? 'selected' : ''; ?>>⭐⭐⭐ o mas</option>
                    <option value="4" <?php echo ($filtros_activos['valoracion'] ?? '') === '4' ? 'selected' : ''; ?>>⭐⭐⭐⭐ o mas</option>
                    <option value="5" <?php echo ($filtros_activos['valoracion'] ?? '') === '5' ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐</option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-amber-500 to-orange-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-amber-600 hover:to-orange-700 transition-all shadow-md">
                Aplicar Filtros
            </button>
        </form>
    </div>
</div>

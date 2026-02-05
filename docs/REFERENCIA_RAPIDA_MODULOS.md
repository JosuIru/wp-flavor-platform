# Referencia Rápida de Módulos

## Módulos Nativos

| ID | Nombre | Ruta | Clase Principal |
|----|--------|------|-----------------|
| `woocommerce` | WooCommerce | `/includes/modules/woocommerce/` | `Flavor_Chat_Woocommerce_Module` |
| `socios` | Socios | `/includes/modules/socios/` | `Flavor_Chat_Socios_Module` |
| `eventos` | Eventos | `/includes/modules/eventos/` | `Flavor_Chat_Eventos_Module` |
| `grupos_consumo` | Grupos de Consumo | `/includes/modules/grupos-consumo/` | `Flavor_Chat_Grupos_Consumo_Module` |
| `marketplace` | Marketplace | `/includes/modules/marketplace/` | `Flavor_Chat_Marketplace_Module` |
| `banco_tiempo` | Banco de Tiempo | `/includes/modules/banco-tiempo/` | `Flavor_Chat_Banco_Tiempo_Module` |
| `facturas` | Facturas | `/includes/modules/facturas/` | `Flavor_Chat_Facturas_Module` |
| `fichaje_empleados` | Fichaje | `/includes/modules/fichaje-empleados/` | `Flavor_Chat_Fichaje_Empleados_Module` |
| `incidencias` | Incidencias | `/includes/modules/incidencias/` | `Flavor_Chat_Incidencias_Module` |
| `participacion` | Participación | `/includes/modules/participacion/` | `Flavor_Chat_Participacion_Module` |
| `presupuestos_participativos` | Presupuestos | `/includes/modules/presupuestos-participativos/` | `Flavor_Chat_Presupuestos_Participativos_Module` |
| `avisos_municipales` | Avisos | `/includes/modules/avisos-municipales/` | `Flavor_Chat_Avisos_Municipales_Module` |
| `advertising` | Publicidad | `/includes/modules/advertising/` | `Flavor_Chat_Advertising_Module` |
| `ayuda_vecinal` | Ayuda Vecinal | `/includes/modules/ayuda-vecinal/` | `Flavor_Chat_Ayuda_Vecinal_Module` |
| `biblioteca` | Biblioteca | `/includes/modules/biblioteca/` | `Flavor_Chat_Biblioteca_Module` |
| `bicicletas_compartidas` | Bicicletas | `/includes/modules/bicicletas-compartidas/` | `Flavor_Chat_Bicicletas_Compartidas_Module` |
| `carpooling` | Carpooling | `/includes/modules/carpooling/` | `Flavor_Chat_Carpooling_Module` |
| `chat_grupos` | Chat Grupos | `/includes/modules/chat-grupos/` | `Flavor_Chat_Chat_Grupos_Module` |
| `chat_interno` | Chat Interno | `/includes/modules/chat-interno/` | `Flavor_Chat_Chat_Interno_Module` |
| `compostaje` | Compostaje | `/includes/modules/compostaje/` | `Flavor_Chat_Compostaje_Module` |
| `cursos` | Cursos | `/includes/modules/cursos/` | `Flavor_Chat_Cursos_Module` |
| `empresarial` | Empresarial | `/includes/modules/empresarial/` | `Flavor_Chat_Empresarial_Module` |
| `espacios_comunes` | Espacios | `/includes/modules/espacios-comunes/` | `Flavor_Chat_Espacios_Comunes_Module` |
| `huertos_urbanos` | Huertos | `/includes/modules/huertos-urbanos/` | `Flavor_Chat_Huertos_Urbanos_Module` |
| `multimedia` | Multimedia | `/includes/modules/multimedia/` | `Flavor_Chat_Multimedia_Module` |
| `parkings` | Parkings | `/includes/modules/parkings/` | `Flavor_Chat_Parkings_Module` |
| `podcast` | Podcast | `/includes/modules/podcast/` | `Flavor_Chat_Podcast_Module` |
| `radio` | Radio | `/includes/modules/radio/` | `Flavor_Chat_Radio_Module` |
| `reciclaje` | Reciclaje | `/includes/modules/reciclaje/` | `Flavor_Chat_Reciclaje_Module` |
| `red_social` | Red Social | `/includes/modules/red-social/` | `Flavor_Chat_Red_Social_Module` |
| `talleres` | Talleres | `/includes/modules/talleres/` | `Flavor_Chat_Talleres_Module` |
| `tramites` | Trámites | `/includes/modules/tramites/` | `Flavor_Chat_Tramites_Module` |
| `transparencia` | Transparencia | `/includes/modules/transparencia/` | `Flavor_Chat_Transparencia_Module` |
| `colectivos` | Colectivos | `/includes/modules/colectivos/` | `Flavor_Chat_Colectivos_Module` |
| `foros` | Foros | `/includes/modules/foros/` | `Flavor_Chat_Foros_Module` |
| `clientes` | Clientes | `/includes/modules/clientes/` | `Flavor_Chat_Clientes_Module` |
| `comunidades` | Comunidades | `/includes/modules/comunidades/` | `Flavor_Chat_Comunidades_Module` |
| `bares` | Bares | `/includes/modules/bares/` | `Flavor_Chat_Bares_Module` |
| `reservas` | Reservas | `/includes/modules/reservas/` | `Flavor_Chat_Reservas_Module` |
| `trading_ia` | Trading IA | `/includes/modules/trading-ia/` | `Flavor_Chat_Trading_Ia_Module` |
| `dex_solana` | DEX Solana | `/includes/modules/dex-solana/` | `Flavor_Chat_Dex_Solana_Module` |
| `themacle` | Themacle | `/includes/modules/themacle/` | `Flavor_Chat_Themacle_Module` |

---

## Addons

| ID | Nombre | Ruta | Archivo Principal |
|----|--------|------|-------------------|
| `web-builder-pro` | Web Builder Pro | `/addons/flavor-web-builder-pro/` | `flavor-web-builder-pro.php` |
| `network-communities` | Network Communities | `/addons/flavor-network-communities/` | `flavor-network-communities.php` |
| `advertising-pro` | Advertising Pro | `/addons/flavor-advertising-pro/` | `flavor-advertising-pro.php` |
| `admin-assistant` | Admin Assistant | `/addons/flavor-admin-assistant/` | `flavor-admin-assistant.php` |
| `restaurant-ordering` | Restaurant Ordering | `/addons/flavor-restaurant-ordering/` | `flavor-restaurant-ordering.php` |

---

## Archivos Core del Sistema de Módulos

| Archivo | Descripción |
|---------|-------------|
| `/includes/class-module-loader.php` | Carga y gestiona módulos |
| `/includes/interface-chat-module.php` | Interfaz que deben implementar los módulos |
| `/includes/class-addon-manager.php` | Gestiona addons |
| `/includes/class-dependency-checker.php` | Verifica dependencias |
| `/includes/class-autoloader.php` | Autoloader de clases |

---

## Shortcodes Comunes por Módulo

### Socios
```
[flavor_socios_registro]
[flavor_socios_login]
[flavor_socios_dashboard]
[flavor_socios_lista]
```

### Eventos
```
[flavor_eventos]
[flavor_eventos_calendario]
[flavor_eventos_proximos limit="5"]
[flavor_evento id="123"]
```

### Marketplace
```
[flavor_marketplace]
[flavor_marketplace_categorias]
[flavor_producto id="123"]
[flavor_mi_tienda]
```

### Grupos de Consumo
```
[flavor_grupos_consumo]
[flavor_pedidos_abiertos]
[flavor_mi_grupo]
[flavor_catalogo]
```

### Espacios Comunes
```
[flavor_espacios]
[flavor_espacios_calendario]
[flavor_reservar_espacio]
[flavor_mis_reservas]
```

### Chat
```
[flavor_chat]
[flavor_chat_grupos]
[flavor_mensajes]
```

### Incidencias
```
[flavor_incidencias_form]
[flavor_incidencias_lista]
[flavor_mis_incidencias]
```

---

## Opciones de Base de Datos

### Configuración Global
```
wp_options key: 'flavor_chat_ia_settings'
├── active_modules: array
├── api_key: string
├── default_engine: string
└── ...
```

### Por Módulo
```
wp_options key: 'flavor_chat_ia_module_{module_id}'
└── Configuración específica del módulo
```

### Addons Activos
```
wp_options key: 'flavor_active_addons'
└── array de IDs de addons activos
```

---

## Hooks Principales

### Acciones
```php
do_action('flavor_module_activated', $module_id);
do_action('flavor_module_deactivated', $module_id);
do_action('flavor_before_load_modules');
do_action('flavor_after_load_modules', $loaded_modules);
do_action('flavor_register_addons');
```

### Filtros
```php
apply_filters('flavor_active_modules', $modules);
apply_filters('flavor_chat_ia_modules', $modules);
apply_filters("flavor_module_settings_{$module_id}", $settings);
apply_filters("flavor_module_actions_{$module_id}", $actions);
```

---

## Comandos Útiles

### Verificar módulos activos
```php
$loader = Flavor_Chat_Module_Loader::get_instance();
$activos = $loader->get_loaded_modules();
print_r(array_keys($activos));
```

### Verificar si un módulo está activo
```php
$loader = Flavor_Chat_Module_Loader::get_instance();
if ($loader->is_module_active('eventos')) {
    // Módulo eventos está activo
}
```

### Obtener instancia de un módulo
```php
$eventos = Flavor_Chat_Module_Loader::get_instance()->get_module('eventos');
if ($eventos) {
    $eventos->mi_metodo();
}
```

### Activar módulo programáticamente
```php
$settings = get_option('flavor_chat_ia_settings', []);
$settings['active_modules'][] = 'nuevo_modulo';
update_option('flavor_chat_ia_settings', $settings);
```

---

*Referencia rápida - Flavor Chat IA v3.1.0*

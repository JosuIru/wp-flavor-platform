# Flavor Multilingual

Sistema de traducción multiidioma profesional para Flavor Platform con integración de IA.

## Versión: 1.4.0

## Características

### Experiencia de Usuario
- **Selector de idioma configurable**: 7 estilos (dropdown, horizontal, vertical, banderas, minimal, globo, select nativo)
- **Widget y shortcode**: Totalmente personalizables con opciones para ocultar banderas
- **Editor lado a lado**: Comparar original vs traducción en tiempo real
- **Auto-guardado**: Guarda campos individuales mientras escribes
- **Sugerencias de TM**: Muestra coincidencias de la memoria de traducción

### Notificaciones
- **Email al asignar**: Notifica al traductor cuando se le asigna contenido
- **Email de revisión**: Avisa a revisores cuando hay traducciones pendientes
- **Estados de traducción**: Notifica aprobaciones, rechazos y publicaciones
- **Plantillas HTML**: Emails profesionales con diseño responsive

### Core
- 23 idiomas soportados (español, inglés, euskera, catalán, gallego, y más)
- Traducción de posts, páginas, taxonomías, menús y strings
- URLs traducidas (parámetro, directorio o subdominio)
- Slugs traducidos automáticamente
- Compatibilidad WPML (funciones icl_*)

### Traducción IA
- Integración con Claude, OpenAI, DeepSeek, Mistral
- Traducción automática de contenido nuevo
- Revisión humana de traducciones automáticas
- Detección automática de contexto para mejor calidad

### Integraciones
- **ACF**: Traducción de campos personalizados
- **WooCommerce**: Productos, categorías, atributos, emails de pedido
- **Visual Builder Pro**: Bloques y páginas VBP
- **Media Library**: Alt text, caption, description de imágenes
- **Sitemaps SEO**: Yoast, RankMath, AIOSEO, sitemap propio con hreflang

### Rendimiento
- **Caché multinivel**: Memoria → Object Cache → Transients
- **Memoria de traducción**: Reutilización de traducciones previas
- **Fuzzy matching**: Encuentra traducciones similares
- **Glosario**: Terminología consistente

### Flujo de trabajo
- **Roles**: Translation Manager, Translator, Reviewer
- **Estados**: pending → in_progress → needs_review → approved → published
- **Asignaciones**: Asignar contenido a traductores específicos
- **Notificaciones**: Email automático al asignar traducción

### Intercambio
- **XLIFF 1.2**: Import/export estándar de la industria
- **PO/MO**: Compatibilidad con archivos gettext

### Detección automática
- **Geolocalización IP**: ip-api, ipinfo, ipgeolocation
- **Accept-Language**: Headers del navegador
- **Cookies**: Preferencia guardada del usuario

## Estructura de archivos

```
flavor-multilingual/
├── flavor-multilingual.php          # Archivo principal
├── phpunit.xml                      # Configuración PHPUnit
├── README.md                        # Esta documentación
│
├── admin/
│   ├── class-admin-settings.php     # Página de configuración
│   ├── class-metabox-translations.php
│   ├── class-string-manager.php     # Gestor de strings
│   ├── class-taxonomy-translations.php
│   ├── class-menu-translations.php
│   └── class-translation-dashboard.php # Dashboard de estadísticas
│
├── api/
│   └── class-translation-api.php    # REST API
│
├── assets/
│   ├── css/
│   └── js/
│       └── vbp-integration.js
│
├── frontend/
│   ├── class-frontend-controller.php
│   └── class-language-switcher.php
│
├── includes/
│   ├── class-multilingual-core.php  # Núcleo
│   ├── class-language-manager.php
│   ├── class-translation-storage.php
│   ├── class-ai-translator.php      # Motor IA
│   ├── class-url-manager.php        # URLs y hreflang
│   ├── class-slug-translator.php
│   ├── class-content-duplicator.php
│   ├── class-po-mo-handler.php
│   ├── class-wpml-compatibility.php
│   ├── class-translation-cache.php  # Caché multinivel
│   ├── class-translation-memory.php # TM + Glosario
│   ├── class-translation-roles.php  # Roles y permisos
│   ├── class-xliff-handler.php      # XLIFF import/export
│   └── class-geolocation.php        # Detección por IP
│
├── integrations/
│   ├── class-acf-integration.php
│   ├── class-woocommerce-integration.php
│   ├── class-vbp-integration.php
│   ├── class-media-integration.php
│   └── class-sitemap-integration.php
│
└── tests/
    ├── bootstrap.php
    ├── mocks/
    │   └── wordpress-mocks.php
    └── unit/
        ├── TranslationCacheTest.php
        ├── XliffHandlerTest.php
        ├── TranslationRolesTest.php
        └── GeolocationTest.php
```

## API REST

### Endpoints públicos
```
GET  /wp-json/flavor-multilingual/v1/languages
GET  /wp-json/flavor-multilingual/v1/languages/{code}
GET  /wp-json/flavor-multilingual/v1/current-language
```

### Endpoints de traducción
```
GET  /wp-json/flavor-multilingual/v1/posts/{id}/translations
POST /wp-json/flavor-multilingual/v1/posts/{id}/translations
PUT  /wp-json/flavor-multilingual/v1/posts/{id}/translations/{lang}
POST /wp-json/flavor-multilingual/v1/translate
POST /wp-json/flavor-multilingual/v1/translate/post/{id}
```

### Endpoints XLIFF
```
POST /wp-json/flavor-multilingual/v1/xliff/export
POST /wp-json/flavor-multilingual/v1/xliff/import
```

## Roles y capacidades

### Translation Manager
- Todas las capacidades de traducción
- Gestionar idiomas
- Asignar traducciones
- Gestionar glosario y TM
- Import/export XLIFF

### Translator
- Traducir contenido asignado
- Usar traducción IA

### Translation Reviewer
- Revisar traducciones
- Aprobar/rechazar traducciones
- Ver estadísticas

## Configuración

### Opciones disponibles
- `url_mode`: parameter | directory | subdomain
- `auto_detect_browser`: true | false
- `remember_user_lang`: true | false
- `auto_redirect`: true | false
- `ai_engine`: claude | openai | deepseek | mistral
- `auto_translate_new`: true | false
- `mark_auto_for_review`: true | false
- `hide_untranslated`: true | false

### Geolocalización
- `geo_api`: ip-api | ipinfo | ipgeolocation
- `geo_api_key`: (para APIs que lo requieren)

## Uso programático

```php
// Obtener traducción
$core = Flavor_Multilingual_Core::get_instance();
$translation = $core->get_translation('post', $post_id, 'eu', 'title');

// Guardar traducción
$storage = Flavor_Translation_Storage::get_instance();
$storage->save_translation('post', $post_id, 'eu', 'title', 'Titulua', array(
    'status' => 'published',
    'auto'   => false,
));

// Traducir con IA
$translator = Flavor_AI_Translator::get_instance();
$translated = $translator->translate('Hello World', 'en', 'eu');

// Verificar permisos
$roles = Flavor_Translation_Roles::get_instance();
$can_translate = apply_filters('flavor_ml_can_translate', false, $user_id, $post_id);

// Caché
$cache = Flavor_Translation_Cache::get_instance();
$cached = $cache->get_translation('post', $post_id, 'eu', 'title');

// Memoria de traducción
$tm = Flavor_Translation_Memory::get_instance();
$similar = $tm->find_similar('Texto original', 'es', 'eu', 0.7);
```

## Tests

```bash
# Ejecutar todos los tests
cd addons/flavor-multilingual
./vendor/bin/phpunit

# Ejecutar un test específico
./vendor/bin/phpunit tests/unit/TranslationCacheTest.php
```

## Changelog

### 1.3.0
- Sistema de roles y permisos (Translation Manager, Translator, Reviewer)
- XLIFF 1.2 import/export
- Geolocalización por IP
- Dashboard de estadísticas
- Tests PHPUnit

### 1.2.0
- Caché multinivel
- Memoria de traducción
- Glosario
- Integración Media Library
- Integración Sitemaps SEO

### 1.1.0
- Integración ACF
- Integración WooCommerce
- Integración Visual Builder Pro

### 1.0.0
- Versión inicial
- 23 idiomas soportados
- Traducción IA con Claude/OpenAI
- URLs traducidas
- Compatibilidad WPML

## Licencia

GPL-2.0+

## Autor

Gailu Labs - https://gailu.net

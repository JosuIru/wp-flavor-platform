# Flavor Multilingual - Documento de Diseño

## Visión General

Addon de traducción multiidioma para Flavor Platform con integración de IA para traducciones automáticas. Diseñado para ser ligero, autónomo y compatible con WPML/Polylang cuando estén presentes.

## Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLAVOR MULTILINGUAL                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │  Language   │  │ Translation │  │   AI Translation        │ │
│  │  Manager    │  │   Storage   │  │      Engine             │ │
│  │             │  │             │  │  (usa Flavor Engines)   │ │
│  └──────┬──────┘  └──────┬──────┘  └───────────┬─────────────┘ │
│         │                │                     │               │
│         ▼                ▼                     ▼               │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   Core Controller                        │   │
│  │  - Detecta idioma actual                                │   │
│  │  - Filtra contenido según idioma                        │   │
│  │  - Gestiona URLs multiidioma                            │   │
│  └─────────────────────────────────────────────────────────┘   │
│         │                │                     │               │
│         ▼                ▼                     ▼               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │   Admin UI  │  │  Frontend   │  │      REST API           │ │
│  │  (Metabox)  │  │  Selector   │  │   /flavor/v1/translate  │ │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘ │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Estructura de Archivos

```
addons/flavor-multilingual/
├── flavor-multilingual.php          # Archivo principal del addon
├── DESIGN.md                         # Este documento
├── README.md                         # Documentación de uso
│
├── includes/
│   ├── class-multilingual-core.php       # Controlador principal
│   ├── class-language-manager.php        # Gestión de idiomas
│   ├── class-translation-storage.php     # Almacenamiento de traducciones
│   ├── class-ai-translator.php           # Motor de traducción con IA
│   ├── class-url-manager.php             # Gestión de URLs multiidioma
│   ├── class-menu-translator.php         # Traducción de menús
│   ├── class-string-translator.php       # Traducción de strings PHP
│   └── class-compatibility.php           # Compatibilidad WPML/Polylang
│
├── admin/
│   ├── class-admin-settings.php          # Página de configuración
│   ├── class-metabox-translations.php    # Metabox en editor
│   ├── views/
│   │   ├── settings-page.php
│   │   ├── metabox-translations.php
│   │   └── language-switcher-admin.php
│   ├── css/
│   │   └── admin-multilingual.css
│   └── js/
│       └── admin-multilingual.js
│
├── frontend/
│   ├── class-frontend-controller.php     # Controlador frontend
│   ├── class-language-switcher.php       # Widget selector de idioma
│   ├── css/
│   │   └── language-switcher.css
│   └── js/
│       └── language-switcher.js
│
├── api/
│   └── class-translation-api.php         # Endpoints REST
│
└── assets/
    └── flags/                            # Iconos de banderas (SVG)
        ├── es.svg
        ├── en.svg
        ├── eu.svg
        ├── fr.svg
        └── ...
```

## Base de Datos

### Tabla: `{prefix}flavor_languages`

```sql
CREATE TABLE {prefix}flavor_languages (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(10) NOT NULL,           -- 'es', 'en', 'eu', 'fr'
    locale VARCHAR(20) NOT NULL,         -- 'es_ES', 'en_US', 'eu'
    name VARCHAR(100) NOT NULL,          -- 'Español', 'English'
    native_name VARCHAR(100) NOT NULL,   -- 'Español', 'English'
    flag VARCHAR(10) DEFAULT NULL,       -- Código de bandera
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tabla: `{prefix}flavor_translations`

```sql
CREATE TABLE {prefix}flavor_translations (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    object_type VARCHAR(50) NOT NULL,    -- 'post', 'term', 'string', 'menu_item', 'option'
    object_id BIGINT(20) UNSIGNED NOT NULL,
    language_code VARCHAR(10) NOT NULL,
    field_name VARCHAR(100) NOT NULL,    -- 'title', 'content', 'excerpt', 'meta_description'
    translation LONGTEXT,
    is_auto_translated TINYINT(1) DEFAULT 0,
    translator VARCHAR(50) DEFAULT NULL, -- 'claude', 'openai', 'manual'
    status VARCHAR(20) DEFAULT 'draft',  -- 'draft', 'published', 'needs_review'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY object_translation (object_type, object_id, language_code, field_name),
    KEY language_code (language_code),
    KEY object_type_id (object_type, object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tabla: `{prefix}flavor_string_translations`

```sql
CREATE TABLE {prefix}flavor_string_translations (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    string_key VARCHAR(255) NOT NULL,    -- Hash MD5 del string original
    original_string TEXT NOT NULL,
    domain VARCHAR(100) DEFAULT 'flavor-chat-ia',
    context VARCHAR(255) DEFAULT NULL,
    language_code VARCHAR(10) NOT NULL,
    translation TEXT,
    is_auto_translated TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY string_lang (string_key, language_code),
    KEY domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Clases Principales

### 1. Multilingual_Core (Singleton)

```php
class Flavor_Multilingual_Core {

    private static $instance = null;
    private $current_language;
    private $default_language;
    private $active_languages = [];

    // Métodos principales
    public function get_current_language(): string;
    public function set_current_language(string $code): void;
    public function get_default_language(): string;
    public function get_active_languages(): array;
    public function is_default_language(): bool;

    // Traducción de contenido
    public function translate_post(int $post_id, string $lang = null): ?WP_Post;
    public function get_post_translations(int $post_id): array;
    public function translate_string(string $text, string $lang = null): string;

    // URLs
    public function get_language_url(string $lang, string $url = null): string;
    public function get_current_page_translations(): array;
}
```

### 2. Language_Manager

```php
class Flavor_Language_Manager {

    // Gestión de idiomas
    public function add_language(array $data): int|WP_Error;
    public function update_language(int $id, array $data): bool|WP_Error;
    public function delete_language(int $id): bool|WP_Error;
    public function get_language(string $code): ?object;
    public function get_all_languages(bool $active_only = true): array;
    public function set_default_language(string $code): bool;

    // Idiomas predefinidos
    public function get_available_languages(): array;
    public function install_language_pack(string $code): bool|WP_Error;
}
```

### 3. Translation_Storage

```php
class Flavor_Translation_Storage {

    // Guardar/obtener traducciones
    public function save_translation(string $type, int $id, string $lang, string $field, string $value, array $meta = []): bool;
    public function get_translation(string $type, int $id, string $lang, string $field): ?string;
    public function get_all_translations(string $type, int $id): array;
    public function delete_translations(string $type, int $id, string $lang = null): bool;

    // Traducción de strings
    public function save_string_translation(string $original, string $lang, string $translation, string $domain = 'flavor-chat-ia'): bool;
    public function get_string_translation(string $original, string $lang, string $domain = 'flavor-chat-ia'): ?string;

    // Estadísticas
    public function get_translation_stats(): array;
    public function get_untranslated_content(string $lang): array;
}
```

### 4. AI_Translator

```php
class Flavor_AI_Translator {

    private $engine; // Reutiliza Flavor_Engine_Manager

    // Traducción con IA
    public function translate_text(string $text, string $from_lang, string $to_lang): string|WP_Error;
    public function translate_html(string $html, string $from_lang, string $to_lang): string|WP_Error;
    public function translate_post(int $post_id, string $to_lang): array|WP_Error;
    public function translate_batch(array $texts, string $from_lang, string $to_lang): array|WP_Error;

    // Configuración
    public function set_engine(string $engine): void; // 'claude', 'openai', 'deepseek'
    public function get_supported_languages(): array;

    // Prompt para traducción
    private function build_translation_prompt(string $text, string $from, string $to, string $context = ''): string;
}
```

### 5. URL_Manager

```php
class Flavor_URL_Manager {

    const MODE_PARAMETER = 'parameter';  // ?lang=eu
    const MODE_DIRECTORY = 'directory';  // /eu/pagina/
    const MODE_SUBDOMAIN = 'subdomain';  // eu.dominio.com

    private $mode;

    // Gestión de URLs
    public function get_translated_url(string $url, string $lang): string;
    public function get_current_language_from_url(): ?string;
    public function add_language_to_url(string $url, string $lang): string;
    public function remove_language_from_url(string $url): string;

    // Rewrite rules
    public function register_rewrite_rules(): void;
    public function filter_permalink(string $permalink, WP_Post $post): string;

    // hreflang
    public function output_hreflang_tags(): void;
}
```

## REST API Endpoints

### Configuración

```
GET    /wp-json/flavor/v1/multilingual/languages
POST   /wp-json/flavor/v1/multilingual/languages
PUT    /wp-json/flavor/v1/multilingual/languages/{code}
DELETE /wp-json/flavor/v1/multilingual/languages/{code}
```

### Traducciones

```
GET    /wp-json/flavor/v1/multilingual/translations/{type}/{id}
POST   /wp-json/flavor/v1/multilingual/translations/{type}/{id}
DELETE /wp-json/flavor/v1/multilingual/translations/{type}/{id}/{lang}

# Traducción con IA
POST   /wp-json/flavor/v1/multilingual/translate
       Body: { "text": "...", "from": "es", "to": "en", "context": "..." }

POST   /wp-json/flavor/v1/multilingual/translate-post
       Body: { "post_id": 123, "to_languages": ["en", "eu", "fr"] }

POST   /wp-json/flavor/v1/multilingual/translate-batch
       Body: { "items": [...], "from": "es", "to": "en" }
```

### Strings

```
GET    /wp-json/flavor/v1/multilingual/strings
POST   /wp-json/flavor/v1/multilingual/strings
GET    /wp-json/flavor/v1/multilingual/strings/untranslated/{lang}
```

## Hooks y Filtros

### Acciones

```php
// Cuando cambia el idioma actual
do_action('flavor_multilingual_language_changed', $new_lang, $old_lang);

// Antes/después de traducir con IA
do_action('flavor_multilingual_before_ai_translate', $text, $from, $to);
do_action('flavor_multilingual_after_ai_translate', $text, $translation, $from, $to);

// Cuando se guarda una traducción
do_action('flavor_multilingual_translation_saved', $type, $id, $lang, $field, $value);

// Cuando se añade/elimina un idioma
do_action('flavor_multilingual_language_added', $code, $data);
do_action('flavor_multilingual_language_deleted', $code);
```

### Filtros

```php
// Modificar el idioma detectado
$lang = apply_filters('flavor_multilingual_detected_language', $lang, $request);

// Modificar traducción antes de guardar
$translation = apply_filters('flavor_multilingual_translation_before_save', $translation, $original, $lang);

// Modificar prompt de IA
$prompt = apply_filters('flavor_multilingual_ai_prompt', $prompt, $text, $from, $to);

// Idiomas soportados
$languages = apply_filters('flavor_multilingual_supported_languages', $languages);

// URL traducida
$url = apply_filters('flavor_multilingual_translated_url', $url, $lang, $original_url);

// Contenido traducido de post
$content = apply_filters('flavor_multilingual_post_content', $content, $post_id, $lang);
```

## Interfaz de Usuario

### 1. Metabox en Editor de Posts

```
┌─────────────────────────────────────────────────────────┐
│ 🌐 Traducciones                                    [−] │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Idioma actual: 🇪🇸 Español (predeterminado)           │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 🇬🇧 English                                      │   │
│  │ ├─ Título: [My page title          ] ✏️ 🤖      │   │
│  │ ├─ Contenido: [Traducido] ✅        ✏️ 🤖       │   │
│  │ └─ Estado: ● Publicado  ○ Borrador  ○ Revisar  │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 🇪🇺 Euskara                                      │   │
│  │ ├─ Título: [                        ] ✏️ 🤖      │   │
│  │ ├─ Contenido: [Sin traducir] ⚠️     ✏️ 🤖       │   │
│  │ └─ Estado: ○ Publicado  ● Borrador  ○ Revisar  │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  [🤖 Traducir todo con IA]  [💾 Guardar traducciones]  │
│                                                         │
└─────────────────────────────────────────────────────────┘

🤖 = Botón traducir con IA
✏️ = Editar manualmente
```

### 2. Selector de Idioma Frontend

```
┌──────────────────┐
│ 🇪🇸 Español    ▼ │
├──────────────────┤
│ 🇪🇸 Español  ✓  │
│ 🇬🇧 English     │
│ 🇪🇺 Euskara     │
│ 🇫🇷 Français    │
└──────────────────┘
```

### 3. Página de Configuración Admin

```
Flavor → Multiidioma

┌─────────────────────────────────────────────────────────────┐
│ Configuración de Idiomas                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Idiomas Activos:                                            │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ ☰ 🇪🇸 Español     es_ES   [Predeterminado] [Editar] [−]│ │
│ │ ☰ 🇬🇧 English     en_US                   [Editar] [−]│ │
│ │ ☰ 🇪🇺 Euskara     eu                      [Editar] [−]│ │
│ └─────────────────────────────────────────────────────────┘ │
│ [+ Añadir idioma]                                           │
│                                                             │
│ ─────────────────────────────────────────────────────────── │
│                                                             │
│ Formato de URL:                                             │
│ ○ Parámetro: ejemplo.com/pagina/?lang=eu                   │
│ ● Directorio: ejemplo.com/eu/pagina/                       │
│ ○ Subdominio: eu.ejemplo.com/pagina/                       │
│                                                             │
│ ─────────────────────────────────────────────────────────── │
│                                                             │
│ Traducción Automática (IA):                                 │
│ Motor: [Claude ▼]                                           │
│ ☑ Traducir automáticamente al crear contenido              │
│ ☑ Marcar traducciones automáticas para revisión            │
│                                                             │
│ ─────────────────────────────────────────────────────────── │
│                                                             │
│ Detección de Idioma:                                        │
│ ☑ Detectar idioma del navegador                            │
│ ☑ Recordar preferencia del usuario                         │
│ ☐ Redireccionar automáticamente                            │
│                                                             │
│                              [Guardar Configuración]        │
└─────────────────────────────────────────────────────────────┘
```

## Flujo de Traducción con IA

```
┌──────────────────┐
│ Usuario hace     │
│ clic en 🤖       │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ JavaScript envía │
│ POST a REST API  │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐     ┌──────────────────┐
│ AI_Translator    │────▶│ Flavor Engine    │
│ prepara prompt   │     │ Manager          │
└────────┬─────────┘     └────────┬─────────┘
         │                        │
         │                        ▼
         │               ┌──────────────────┐
         │               │ API Externa      │
         │               │ (Claude/OpenAI)  │
         │               └────────┬─────────┘
         │                        │
         ▼                        ▼
┌──────────────────┐     ┌──────────────────┐
│ Recibe           │◀────│ Respuesta IA     │
│ traducción       │     └──────────────────┘
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Translation      │
│ Storage guarda   │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ UI actualiza     │
│ con traducción   │
└──────────────────┘
```

## Prompt de Traducción IA

```
Eres un traductor profesional especializado en contenido web.

TAREA: Traducir el siguiente contenido de {idioma_origen} a {idioma_destino}.

REGLAS:
1. Mantén el formato HTML/Markdown intacto
2. No traduzcas nombres propios, marcas o términos técnicos específicos
3. Adapta expresiones idiomáticas al idioma destino
4. Mantén el tono y estilo del original
5. Si hay shortcodes de WordPress [ejemplo], NO los traduzcas

CONTEXTO: {contexto_opcional}

CONTENIDO A TRADUCIR:
---
{contenido}
---

Responde SOLO con la traducción, sin explicaciones adicionales.
```

## Compatibilidad con WPML/Polylang

```php
class Flavor_Multilingual_Compatibility {

    public function __construct() {
        // Detectar plugins instalados
        $this->detect_translation_plugins();
    }

    public function detect_translation_plugins(): ?string {
        if (defined('ICL_SITEPRESS_VERSION')) {
            return 'wpml';
        }
        if (defined('POLYLANG_VERSION')) {
            return 'polylang';
        }
        return null; // Usar sistema propio
    }

    public function get_current_language(): string {
        $plugin = $this->detect_translation_plugins();

        switch ($plugin) {
            case 'wpml':
                return apply_filters('wpml_current_language', null);
            case 'polylang':
                return pll_current_language();
            default:
                return Flavor_Multilingual_Core::get_instance()->get_current_language();
        }
    }

    // Registrar CPTs de Flavor como traducibles en WPML/Polylang
    public function register_flavor_post_types(): void {
        $flavor_cpts = ['flavor_landing', 'flavor_template', ...];

        foreach ($flavor_cpts as $cpt) {
            // WPML
            if (function_exists('wpml_register_single_string')) {
                // Registrar...
            }

            // Polylang
            if (function_exists('pll_register_string')) {
                // Registrar...
            }
        }
    }
}
```

## Fases de Implementación

### Fase 1: MVP (Semana 1-2)
- [ ] Estructura básica del addon
- [ ] Gestión de idiomas (CRUD)
- [ ] Storage de traducciones en meta
- [ ] Metabox básico en editor
- [ ] Traducción manual de posts

### Fase 2: IA (Semana 2-3)
- [ ] Integración con Flavor Engine Manager
- [ ] Traducción con IA (Claude/OpenAI)
- [ ] Botón "Traducir con IA" en metabox
- [ ] Traducción batch de múltiples campos

### Fase 3: Frontend (Semana 3-4)
- [ ] Selector de idioma (widget/shortcode)
- [ ] URLs multiidioma (parámetro/directorio)
- [ ] Detección de idioma del navegador
- [ ] hreflang tags para SEO

### Fase 4: Avanzado (Semana 4-5)
- [ ] Traducción de menús
- [ ] Traducción de strings PHP
- [ ] Página de gestión de traducciones
- [ ] Estadísticas y reportes

### Fase 5: Compatibilidad (Semana 5-6)
- [ ] Bridge con WPML
- [ ] Bridge con Polylang
- [ ] Documentación completa
- [ ] Tests y optimización

## Consideraciones de Rendimiento

1. **Cache de traducciones**: Usar object cache (Redis/Memcached) si está disponible
2. **Lazy loading**: Cargar traducciones solo cuando se necesitan
3. **Batch API calls**: Agrupar llamadas a IA para traducción masiva
4. **Índices DB**: Asegurar índices en tablas de traducciones
5. **Transients**: Cache de traducciones de strings frecuentes

## Seguridad

1. **Capabilities**: Crear `manage_translations` capability
2. **Nonces**: En todas las acciones AJAX/REST
3. **Sanitización**: Escapar todo contenido traducido
4. **Rate limiting**: Limitar llamadas a API de IA
5. **Validación**: Verificar códigos de idioma válidos

---

**Autor**: Flavor Platform Team
**Versión**: 1.0.0-design
**Fecha**: 2026-03-16

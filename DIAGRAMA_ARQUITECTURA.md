# 🏗️ Diagrama de Arquitectura - Flavor Chat IA

## 📊 Arquitectura General

```
┌─────────────────────────────────────────────────────────────────┐
│                      FLAVOR CHAT IA PLUGIN                       │
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              SISTEMA DE PERFILES (Capa 1)              │    │
│  │                                                         │    │
│  │  Tienda    Grupo      Restaurante   Banco    Coworking │    │
│  │  Online    Consumo                  Tiempo             │    │
│  │    🏪        🥕          🍽️          ⏰        🏢        │    │
│  │                                                         │    │
│  │  Comunidad  Marketplace  Personalizado                 │    │
│  │    👥         📢            ⚙️                          │    │
│  └────────────────────────────────────────────────────────┘    │
│                              ↓                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │          GESTOR DE MÓDULOS (Capa 2)                    │    │
│  │                                                         │    │
│  │  • Module Loader                                       │    │
│  │  • Module Interface                                    │    │
│  │  • Module Base Class                                   │    │
│  └────────────────────────────────────────────────────────┘    │
│                              ↓                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │               MÓDULOS FUNCIONALES (Capa 3)             │    │
│  │                                                         │    │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐            │    │
│  │  │ WooComm  │  │  Banco   │  │Marketplace│  [+Más]    │    │
│  │  │          │  │  Tiempo  │  │          │            │    │
│  │  │  • CPT   │  │  • Tablas│  │  • CPT   │            │    │
│  │  │  • Tools │  │  • Tools │  │  • Taxons│            │    │
│  │  │  • KB    │  │  • KB    │  │  • Metas │            │    │
│  │  └──────────┘  └──────────┘  └──────────┘            │    │
│  └────────────────────────────────────────────────────────┘    │
│                              ↓                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              MOTOR DE IA (Capa 4)                      │    │
│  │                                                         │    │
│  │  Claude API    OpenAI    DeepSeek    Mistral          │    │
│  │      🤖          🤖         🤖          🤖              │    │
│  └────────────────────────────────────────────────────────┘    │
│                              ↓                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │                 INTERFACES (Capa 5)                    │    │
│  │                                                         │    │
│  │  Chat Widget   REST API   Admin Panel   Shortcodes    │    │
│  │      💬          🔌          ⚙️           📝            │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Flujo de Datos

### 1. Usuario solicita acción en el Chat

```
Usuario
  │
  ├─> "Buscar anuncios de electrónica"
  │
  ↓
Chat Widget (Frontend)
  │
  ├─> AJAX Request
  │
  ↓
Chat Core (Backend)
  │
  ├─> Construye prompt con:
  │   • Context del usuario
  │   • Knowledge Base de módulos activos
  │   • Tool Definitions de módulos
  │
  ↓
Engine Manager
  │
  ├─> Selecciona proveedor activo (ej: Claude)
  │
  ↓
Claude API
  │
  ├─> Procesa y decide usar tool: "marketplace_buscar"
  │
  ↓
Chat Core
  │
  ├─> Detecta tool_use
  │
  ↓
Module Loader
  │
  ├─> Encuentra módulo "marketplace"
  │
  ↓
Marketplace Module
  │
  ├─> execute_action('buscar', params)
  │   • Consulta BD / CPT
  │   • Formatea resultados
  │
  ↓
Chat Core
  │
  ├─> Devuelve resultados a Claude
  │
  ↓
Claude API
  │
  ├─> Genera respuesta natural con los datos
  │
  ↓
Chat Widget
  │
  └─> Muestra respuesta al usuario
```

---

## 📁 Estructura de Archivos

```
flavor-chat-ia/
│
├── 📄 flavor-chat-ia.php              # Plugin principal
├── 📄 README.md                        # Documentación principal
├── 📄 ARQUITECTURA_MODULOS.md          # Guía técnica
├── 📄 RESUMEN_IMPLEMENTACION.md        # Guía de uso
├── 📄 DIAGRAMA_ARQUITECTURA.md         # Este archivo
│
├── 📁 includes/
│   │
│   ├── 📄 class-app-profiles.php       # ⭐ Sistema de perfiles
│   │
│   ├── 📁 engines/                     # Motores de IA
│   │   ├── interface-ai-engine.php
│   │   ├── class-engine-claude.php
│   │   ├── class-engine-openai.php
│   │   ├── class-engine-deepseek.php
│   │   ├── class-engine-mistral.php
│   │   └── class-engine-manager.php
│   │
│   ├── 📁 core/                        # Core del chat
│   │   ├── class-chat-session.php
│   │   ├── class-chat-core.php
│   │   ├── class-chat-ajax.php
│   │   └── class-chat-assets.php
│   │
│   ├── 📁 modules/                     # ⭐ Módulos funcionales
│   │   │
│   │   ├── 📄 interface-chat-module.php    # Interface
│   │   ├── 📄 class-module-loader.php      # Cargador
│   │   ├── 📄 PLANTILLA_MODULO.php         # Plantilla
│   │   │
│   │   ├── 📁 woocommerce/
│   │   │   └── class-woocommerce-module.php
│   │   │
│   │   ├── 📁 banco-tiempo/            # ⭐ NUEVO
│   │   │   ├── class-banco-tiempo-module.php
│   │   │   └── install.php
│   │   │
│   │   └── 📁 marketplace/             # ⭐ NUEVO
│   │       └── class-marketplace-module.php
│   │
│   └── 📁 admin-assistant/
│       └── ...
│
├── 📁 admin/                           # Admin de WordPress
│   │
│   ├── 📄 class-chat-settings.php      # Ajustes generales
│   ├── 📄 class-chat-analytics.php     # Analytics
│   ├── 📄 class-app-profile-admin.php  # ⭐ Admin de perfiles
│   │
│   ├── 📁 css/
│   └── 📁 js/
│
├── 📁 assets/                          # Assets frontend
│   ├── 📁 css/
│   └── 📁 js/
│
└── 📁 languages/                       # Traducciones
    └── ...
```

---

## 🔌 Arquitectura de un Módulo

```
┌───────────────────────────────────────────────────────────┐
│              MÓDULO (Ej: Marketplace)                     │
│                                                           │
│  ┌─────────────────────────────────────────────────┐    │
│  │         class-marketplace-module.php            │    │
│  │                                                  │    │
│  │  extends Flavor_Chat_Module_Base                │    │
│  │  implements Flavor_Chat_Module_Interface        │    │
│  │                                                  │    │
│  │  ┌────────────────────────────────────────┐    │    │
│  │  │  MÉTODOS OBLIGATORIOS                  │    │    │
│  │  │                                         │    │    │
│  │  │  • get_id()                            │    │    │
│  │  │  • get_name()                          │    │    │
│  │  │  • get_description()                   │    │    │
│  │  │  • can_activate()                      │    │    │
│  │  │  • init()                              │    │    │
│  │  │  • get_actions()                       │    │    │
│  │  │  • execute_action()                    │    │    │
│  │  │  • get_tool_definitions()             │    │    │
│  │  │  • get_knowledge_base()               │    │    │
│  │  │  • get_faqs()                          │    │    │
│  │  └────────────────────────────────────────┘    │    │
│  └─────────────────────────────────────────────────┘    │
│                         ↓                                │
│  ┌─────────────────────────────────────────────────┐    │
│  │         COMPONENTES DEL MÓDULO                  │    │
│  │                                                  │    │
│  │  • Custom Post Types                           │    │
│  │    └─> register_post_type()                    │    │
│  │                                                  │    │
│  │  • Taxonomías                                   │    │
│  │    └─> register_taxonomy()                     │    │
│  │                                                  │    │
│  │  • Meta Boxes (Custom Fields)                  │    │
│  │    ├─> add_meta_box()                          │    │
│  │    └─> save_post action                        │    │
│  │                                                  │    │
│  │  • Tablas Personalizadas (opcional)           │    │
│  │    └─> install.php                             │    │
│  │                                                  │    │
│  │  • Shortcodes                                   │    │
│  │    └─> add_shortcode()                         │    │
│  │                                                  │    │
│  │  • AJAX Handlers                               │    │
│  │    └─> wp_ajax_* actions                       │    │
│  │                                                  │    │
│  │  • Hooks propios                               │    │
│  │    └─> do_action('modulo_evento')             │    │
│  └─────────────────────────────────────────────────┘    │
└───────────────────────────────────────────────────────────┘
```

---

## 🎯 Flujo de Activación de Perfil

```
Admin selecciona perfil "Banco de Tiempo"
  │
  ↓
class-app-profile-admin.php
  │
  ├─> procesar_cambio_perfil()
  │
  ↓
class-app-profiles.php
  │
  ├─> establecer_perfil('banco_tiempo')
  │   • Lee módulos requeridos: ['banco_tiempo', 'chat', 'membresias']
  │   • Actualiza option 'flavor_chat_ia_settings'
  │   • Guarda módulos activos
  │
  ↓
WordPress recarga
  │
  ↓
flavor-chat-ia.php
  │
  ├─> init()
  │   • Carga Flavor_App_Profiles
  │   • Carga Module_Loader
  │
  ↓
class-module-loader.php
  │
  ├─> load_active_modules()
  │   • Lee módulos de settings
  │   • Para cada módulo:
  │     ├─> require_once module file
  │     ├─> Instancia clase
  │     ├─> Verifica can_activate()
  │     └─> Llama a init()
  │
  ↓
Cada módulo ejecuta su init()
  │
  ├─> Registra CPT, taxonomías, meta boxes
  ├─> Añade hooks y filters
  ├─> Registra shortcodes
  └─> Configura AJAX handlers
  │
  ↓
Sistema listo con módulos activos
```

---

## 💾 Esquema de Base de Datos

### Tablas Core

```sql
wp_flavor_chat_conversations
├── id (PK)
├── session_id
├── language
├── started_at
├── ended_at
├── message_count
├── escalated
├── user_id (FK)
└── ...

wp_flavor_chat_messages
├── id (PK)
├── conversation_id (FK)
├── role (user|assistant|system)
├── content
├── tool_calls
├── tokens_used
└── created_at

wp_flavor_chat_escalations
├── id (PK)
├── conversation_id (FK)
├── reason
├── summary
├── status
└── ...
```

### Tablas de Módulos

#### Banco de Tiempo
```sql
wp_flavor_banco_tiempo_servicios
├── id (PK)
├── usuario_id (FK)
├── titulo
├── descripcion
├── categoria
├── horas_estimadas
├── estado
└── fecha_publicacion

wp_flavor_banco_tiempo_transacciones
├── id (PK)
├── servicio_id (FK)
├── usuario_solicitante_id (FK)
├── usuario_receptor_id (FK)
├── horas
├── estado
├── valoracion_solicitante
├── valoracion_receptor
└── ...
```

#### Marketplace (usa CPT)
```sql
wp_posts
├── ID (PK)
├── post_type = 'marketplace_item'
├── post_title
├── post_content
└── ...

wp_term_relationships (taxonomías)
├── marketplace_tipo (regalo, venta, cambio, alquiler)
└── marketplace_categoria (Electrónica, Muebles, etc.)

wp_postmeta (custom fields)
├── _marketplace_precio
├── _marketplace_estado
├── _marketplace_ubicacion
├── _marketplace_contacto
└── _marketplace_fecha_expiracion
```

---

## 🔐 Flujo de Seguridad

```
Usuario envía petición
  │
  ↓
Frontend valida entrada
  │
  ├─> Sanitiza datos (sanitize_text_field, etc.)
  ├─> Verifica nonce (wp_verify_nonce)
  │
  ↓
Backend recibe petición
  │
  ├─> Verifica capacidades (current_user_can)
  ├─> Valida parámetros
  ├─> Verifica rate limiting
  │
  ↓
Módulo ejecuta acción
  │
  ├─> Prepared statements (wpdb->prepare)
  ├─> Sanitización adicional
  ├─> Validación de negocio
  │
  ↓
Respuesta al usuario
  │
  └─> Escape de salida (esc_html, esc_attr, etc.)
```

---

## 🌐 Integración REST API

```
GET  /wp-json/flavor-chat-ia/v1/chat
POST /wp-json/flavor-chat-ia/v1/chat/message

GET  /wp-json/flavor-chat-ia/v1/modules
GET  /wp-json/flavor-chat-ia/v1/modules/{module}/actions
POST /wp-json/flavor-chat-ia/v1/modules/{module}/action

GET  /wp-json/flavor-chat-ia/v1/profiles
GET  /wp-json/flavor-chat-ia/v1/profiles/{profile}
```

---

## 🎨 Personalización con Hooks

### Filtros Principales

```php
// Modificar perfiles
apply_filters('flavor_chat_ia_app_profiles', $perfiles)

// Registrar módulos externos
apply_filters('flavor_chat_ia_modules', $modulos)

// Modificar tool definitions
apply_filters('flavor_chat_ia_tool_definitions', $tools)

// Modificar knowledge base
apply_filters('flavor_chat_ia_knowledge_base', $knowledge)
```

### Acciones Principales

```php
// Al activar plugin
do_action('flavor_chat_ia_activated')

// Al instalar módulos
do_action('flavor_chat_ia_install_modules')

// Al cambiar perfil
do_action('flavor_chat_ia_profile_changed', $perfil_id)

// Al cargar módulo
do_action('flavor_chat_ia_module_loaded', $modulo_id, $modulo)

// Al crear anuncio marketplace
do_action('marketplace_anuncio_creado', $post_id, $datos)

// Al completar servicio banco tiempo
do_action('flavor_banco_tiempo_servicio_completado', $intercambio_id, $horas)
```

---

## 📱 Integración Apps Móviles

```
┌─────────────┐
│   App iOS   │
│             │
│  React      │ ──┐
│  Native     │   │
└─────────────┘   │
                  │    REST API
┌─────────────┐   │  ← → WordPress
│ App Android │   │    + Plugin
│             │   │
│  React      │ ──┘
│  Native     │
└─────────────┘

Endpoints disponibles:
• /chat - Chat con IA
• /modules/{module}/action - Acciones de módulos
• /marketplace - CRUD anuncios
• /banco-tiempo - Gestión servicios
• /auth - Autenticación JWT
```

---

**¿Preguntas? Consulta la documentación completa en los archivos .md** 📖

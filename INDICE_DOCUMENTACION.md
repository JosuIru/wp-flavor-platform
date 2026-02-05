# 📚 Índice General de Documentación

## 🗺️ Guía de Navegación

Esta es la documentación completa del plugin **Flavor Chat IA**. Dependiendo de lo que necesites, empieza por el documento adecuado:

---

## 👥 **Para Usuarios Finales**

### 📖 [README.md](./README.md)
**Empieza aquí si es la primera vez**
- Qué es el plugin
- Requisitos del sistema
- Instalación paso a paso
- Características principales
- Módulos disponibles
- Licencia y créditos

### 📝 [RESUMEN_IMPLEMENTACION.md](./RESUMEN_IMPLEMENTACION.md)
**Guía práctica de uso**
- Cómo activar el plugin
- Seleccionar perfil de aplicación
- Gestionar módulos
- Usar el chat IA
- Crear anuncios en Marketplace
- Usar el Banco de Tiempo
- Probar funcionalidades

---

## 👨‍💻 **Para Desarrolladores**

### 🏗️ [ARQUITECTURA_MODULOS.md](./ARQUITECTURA_MODULOS.md)
**Guía técnica completa**
- Concepto de la arquitectura modular
- Estructura de un módulo
- Crear módulos paso a paso
- Custom Post Types y Fields
- Integración con el chat IA
- Ejemplos de código
- Best practices

### 📊 [DIAGRAMA_ARQUITECTURA.md](./DIAGRAMA_ARQUITECTURA.md)
**Diagramas visuales**
- Arquitectura general del sistema
- Flujo de datos
- Estructura de archivos
- Esquema de base de datos
- Flujo de activación de perfiles
- Integración con REST API
- Personalización con hooks

### 📄 [PLANTILLA_MODULO.php](./includes/modules/PLANTILLA_MODULO.php)
**Template listo para copiar**
- Código base para nuevo módulo
- Todos los métodos necesarios
- Ejemplos de CPT, taxonomías, meta boxes
- Comentarios explicativos
- Checklist de implementación

### ⭐ [NUEVAS_FUNCIONALIDADES.md](./NUEVAS_FUNCIONALIDADES.md)
**Últimas actualizaciones**
- Módulo Grupos de Consumo (nuevo)
- Clase Helpers/Utilidades (nuevo)
- Templates frontend (nuevo)
- Cómo usar todo lo nuevo
- Tips de desarrollo

---

## 📦 **Por Módulo (41+ módulos)**

### Comercio/Economía
| Módulo | Descripción | Components | Frontend |
|--------|-------------|------------|----------|
| carpooling | Compartir viajes | 5 templates | 4 vistas |
| tienda-local | Comercio de proximidad | 2 templates | 4 vistas |
| marketplace | Compra/venta/intercambio | 4 templates | 4 vistas |
| banco-tiempo | Intercambio de servicios | - | 4 vistas |
| bares | Bares y restaurantes | 3 templates | 4 vistas |
| woocommerce | Integración WooCommerce | 4 templates | nativo WC |

### Gobernanza/Gestión
| Módulo | Descripción | Components | Frontend |
|--------|-------------|------------|----------|
| participacion | Participación ciudadana | 4 templates | 4 vistas |
| presupuestos-participativos | Presupuestos participativos | 4 templates | 4 vistas |
| transparencia | Portal de transparencia | 4 templates | 4 vistas |
| tramites | Trámites online | 4 templates | 4 vistas |
| avisos-municipales | Avisos del municipio | - | 4 vistas |
| incidencias | Gestión de incidencias | 2 templates | 4 vistas |

### Comunicación/Social
| Módulo | Descripción | Components | Frontend |
|--------|-------------|------------|----------|
| red-social | Red social comunitaria | 2 templates | 4 vistas |
| foros | Foros de discusión | 3 templates | 4 vistas |
| chat-grupos | Grupos de chat | 4 templates | app RT |
| chat-interno | Mensajería interna | 3 templates | app RT |
| podcast | Podcasts comunitarios | 4 templates | 4 vistas |
| radio | Radio comunitaria | 4 templates | 4 vistas |
| multimedia | Contenido multimedia | 4 templates | 4 vistas |

### Comunidad
| Módulo | Descripción | Components | Frontend |
|--------|-------------|------------|----------|
| colectivos | Asociaciones y colectivos | 3 templates | 4 vistas |
| eventos | Eventos comunitarios | 3 templates | 4 vistas |
| espacios-comunes | Reserva de espacios | 3 templates | 4 vistas |
| ayuda-vecinal | Ayuda entre vecinos | 4 templates | 4 vistas |
| comunidades | Red de comunidades | 3 templates | 4 vistas |

### Educación
| Módulo | Descripción | Components | Frontend |
|--------|-------------|------------|----------|
| cursos | Formación online | 4 templates | 4 vistas |
| biblioteca | Biblioteca comunitaria | 4 templates | 4 vistas |
| talleres | Talleres prácticos | 2 templates | 4 vistas |

### Medio Ambiente
| Módulo | Descripción | Components | Frontend |
|--------|-------------|------------|----------|
| huertos-urbanos | Huertos comunitarios | 4 templates | 4 vistas |
| reciclaje | Puntos de reciclaje | 4 templates | 4 vistas |
| compostaje | Compostaje comunitario | 4 templates | 4 vistas |

### Empresarial/Finanzas
| Módulo | Descripción | Components | Frontend |
|--------|-------------|------------|----------|
| empresarial | Directorio empresarial | 8 templates | 4 vistas |
| clientes | Gestión de clientes | 3 templates | 4 vistas |
| socios | Membresías | 4 templates | 4 vistas |
| facturas | Facturación | 3 templates | dashboard |
| fichaje-empleados | Control horario | 3 templates | dashboard |

### Especializado
| Módulo | Descripción | Components | Frontend |
|--------|-------------|------------|----------|
| trading-ia | Trading con IA | 3 templates | dashboard |
| dex-solana | DEX en Solana | 3 templates | dashboard |
| advertising | Publicidad ética | 4 templates | interno |
| themacle | Motor de temas | 16 templates | interno |

---

## 🛠️ **Utilidades y Helpers**

### 🔧 [class-helpers.php](./includes/class-helpers.php)
**Funciones comunes para todos los módulos**
- Formateo de precios y fechas
- Envío de emails y notificaciones
- Validaciones
- Exportación CSV
- Gestión de configuración
- Logging
- Y mucho más

**Documentación**: Ver [NUEVAS_FUNCIONALIDADES.md](./NUEVAS_FUNCIONALIDADES.md) sección "Clase de Helpers"

---

## 🎨 **Templates y Frontend**

### 📄 Template Marketplace
- **Archivo**: `templates/marketplace/single-marketplace_item.php`
- **Descripción**: Template completo para anuncios
- **Personalizable**: Copiar a tu tema
- **Documentación**: Comentarios en el archivo + [NUEVAS_FUNCIONALIDADES.md](./NUEVAS_FUNCIONALIDADES.md)

### 🎨 Personalización
Para personalizar templates, cópialos a tu tema:
```
tu-tema/flavor-chat-ia/
├── marketplace/
│   └── single-marketplace_item.php
├── grupos-consumo/
│   └── ...
└── banco-tiempo/
    └── ...
```

---

## 📋 **Checklist de Inicio Rápido**

### Para Usuarios:
- [ ] Leer [README.md](./README.md)
- [ ] Seguir instalación en [RESUMEN_IMPLEMENTACION.md](./RESUMEN_IMPLEMENTACION.md)
- [ ] Seleccionar perfil de aplicación
- [ ] Probar funcionalidades básicas
- [ ] Configurar módulos activos

### Para Desarrolladores:
- [ ] Leer [ARQUITECTURA_MODULOS.md](./ARQUITECTURA_MODULOS.md)
- [ ] Revisar [DIAGRAMA_ARQUITECTURA.md](./DIAGRAMA_ARQUITECTURA.md)
- [ ] Estudiar código de módulos existentes
- [ ] Revisar [PLANTILLA_MODULO.php](./includes/PLANTILLA_MODULO.php)
- [ ] Conocer [class-helpers.php](./includes/class-helpers.php)
- [ ] Crear tu primer módulo

---

## 🗂️ **Estructura de Archivos Documentados**

```
flavor-chat-ia/
│
├── 📄 README.md                         # Documentación principal
├── 📄 RESUMEN_IMPLEMENTACION.md         # Guía de uso
├── 📄 ARQUITECTURA_MODULOS.md           # Guía técnica
├── 📄 DIAGRAMA_ARQUITECTURA.md          # Diagramas
├── 📄 NUEVAS_FUNCIONALIDADES.md         # Últimas actualizaciones
├── 📄 ESTADO-PROYECTO.md                # Estado del proyecto
├── 📄 LANDING-TEMPLATES-STATUS.md       # Estado de plantillas
├── 📄 VERIFICACION-SISTEMA.md           # Checklist de verificación
├── 📄 GUIA-SETTINGS-DISENO.md           # Guía de diseño
├── 📄 WEB-BUILDER-README.md             # Guía del web builder
├── 📄 SISTEMA-PUBLICIDAD-ETICA.md       # Sistema de publicidad
├── 📄 INDICE_DOCUMENTACION.md           # Este archivo
│
├── 📁 includes/
│   ├── 📄 class-helpers.php             # Utilidades comunes
│   ├── 📄 class-app-profiles.php        # Sistema de perfiles
│   │
│   ├── 📁 web-builder/
│   │   ├── 📄 class-component-registry.php  # Registro de componentes
│   │   └── 📄 class-page-builder.php        # Page Builder
│   │
│   ├── 📁 modules/                      # 41+ módulos
│   │   ├── 📄 PLANTILLA_MODULO.php      # Template base
│   │   ├── 📄 interface-chat-module.php # Interface
│   │   ├── 📄 class-module-loader.php   # Cargador
│   │   ├── 📁 carpooling/              # ... y 40+ módulos más
│   │   └── ...
│   │
│   └── 📁 engines/                      # Motores de IA
│
├── 📁 admin/
│   ├── 📄 class-design-settings.php     # Settings de diseño
│   ├── 📄 class-app-profile-admin.php   # Admin de perfiles
│   └── 📄 class-chat-settings.php       # Ajustes generales
│
├── 📁 assets/
│   ├── 📁 js/                           # 16+ archivos JS
│   │   ├── 📄 page-builder.js
│   │   ├── 📄 flavor-frontend.js        # JS frontend compartido
│   │   └── ...
│   └── 📁 css/                          # 15+ archivos CSS
│
└── 📁 templates/
    ├── 📁 components/                   # 170+ templates de componentes
    │   ├── 📁 _shared/                  # Componentes compartidos
    │   │   ├── _pagination.php
    │   │   ├── _breadcrumb.php
    │   │   ├── _empty-state.php
    │   │   ├── _sort-bar.php
    │   │   └── _rating-stars.php
    │   ├── 📁 carpooling/              # hero, grid, features, cta
    │   ├── 📁 empresarial/             # 8 templates completos
    │   ├── 📁 participacion/           # hero, grid, proceso, cta
    │   ├── 📁 marketplace/             # hero, grid, categorias, cta
    │   └── ... (41+ módulos)
    │
    └── 📁 frontend/                     # 132 vistas frontend
        ├── 📁 banco-tiempo/            # archive, single, search, filters
        ├── 📁 marketplace/             # archive, single, search, filters
        ├── 📁 participacion/           # archive, single, search, filters
        └── ... (33 módulos × 4 archivos)
```

---

## 🔍 **Búsqueda Rápida**

### Busco información sobre...

#### Instalación
→ [README.md](./README.md) sección "Instalación"
→ [RESUMEN_IMPLEMENTACION.md](./RESUMEN_IMPLEMENTACION.md) sección "Cómo empezar"

#### Perfiles de Aplicación
→ [README.md](./README.md) sección "Perfiles de Aplicación"
→ [ARQUITECTURA_MODULOS.md](./ARQUITECTURA_MODULOS.md) sección "Perfiles de Aplicación"

#### Crear un módulo
→ [ARQUITECTURA_MODULOS.md](./ARQUITECTURA_MODULOS.md) sección "Crear un Nuevo Módulo"
→ [PLANTILLA_MODULO.php](./includes/modules/PLANTILLA_MODULO.php)

#### Custom Post Types
→ [ARQUITECTURA_MODULOS.md](./ARQUITECTURA_MODULOS.md) sección "Custom Post Types y Fields"
→ Ejemplos en `includes/modules/marketplace/` y `includes/modules/grupos-consumo/`

#### Tablas personalizadas
→ Ver archivos `install.php` en:
  - `includes/modules/banco-tiempo/install.php`
  - `includes/modules/grupos-consumo/install.php`

#### Integración con Chat IA
→ [ARQUITECTURA_MODULOS.md](./ARQUITECTURA_MODULOS.md) sección "Integración con el Chat IA"
→ Método `get_tool_definitions()` en cualquier módulo

#### Helpers/Utilidades
→ [NUEVAS_FUNCIONALIDADES.md](./NUEVAS_FUNCIONALIDADES.md) sección "Clase de Helpers"
→ [class-helpers.php](./includes/class-helpers.php)

#### Templates Frontend
→ [NUEVAS_FUNCIONALIDADES.md](./NUEVAS_FUNCIONALIDADES.md) sección "Template Frontend"
→ `templates/marketplace/single-marketplace_item.php`

#### Base de Datos
→ [DIAGRAMA_ARQUITECTURA.md](./DIAGRAMA_ARQUITECTURA.md) sección "Esquema de Base de Datos"
→ [NUEVAS_FUNCIONALIDADES.md](./NUEVAS_FUNCIONALIDADES.md) sección "Base de Datos Actualizada"

#### Módulo Grupos de Consumo
→ [NUEVAS_FUNCIONALIDADES.md](./NUEVAS_FUNCIONALIDADES.md) sección "Nuevo Módulo"

---

## 💡 **Casos de Uso**

### "Quiero crear una tienda online"
1. Lee [README.md](./README.md)
2. Instala según [RESUMEN_IMPLEMENTACION.md](./RESUMEN_IMPLEMENTACION.md)
3. Selecciona perfil "Tienda Online"
4. Configura WooCommerce
5. ¡Listo!

### "Quiero crear un grupo de consumo"
1. Lee [README.md](./README.md)
2. Instala según [RESUMEN_IMPLEMENTACION.md](./RESUMEN_IMPLEMENTACION.md)
3. Selecciona perfil "Grupo de Consumo"
4. Lee la guía en [NUEVAS_FUNCIONALIDADES.md](./NUEVAS_FUNCIONALIDADES.md)
5. Crea productores y productos
6. Inicia tu primer ciclo

### "Quiero crear un módulo personalizado"
1. Lee [ARQUITECTURA_MODULOS.md](./ARQUITECTURA_MODULOS.md)
2. Estudia [DIAGRAMA_ARQUITECTURA.md](./DIAGRAMA_ARQUITECTURA.md)
3. Copia [PLANTILLA_MODULO.php](./includes/modules/PLANTILLA_MODULO.php)
4. Revisa módulos existentes como ejemplo
5. Usa [class-helpers.php](./includes/class-helpers.php)
6. Sigue el checklist de la plantilla

### "Quiero personalizar el diseño"
1. Copia templates de `templates/` a tu tema
2. Modifica HTML y CSS
3. Usa helpers para formateo
4. Ver ejemplo en `templates/marketplace/single-marketplace_item.php`

---

## 🆘 **Ayuda y Soporte**

### Documentación no responde tu pregunta?

1. **Revisa los ejemplos de código** en:
   - `includes/modules/marketplace/`
   - `includes/modules/grupos-consumo/`

2. **Lee los comentarios** en:
   - [PLANTILLA_MODULO.php](./includes/modules/PLANTILLA_MODULO.php)
   - [class-helpers.php](./includes/class-helpers.php)

3. **Busca en el código** palabras clave relacionadas

4. **Consulta la documentación de WordPress**:
   - [Custom Post Types](https://developer.wordpress.org/plugins/post-types/)
   - [Taxonomies](https://developer.wordpress.org/plugins/taxonomies/)
   - [Meta Boxes](https://developer.wordpress.org/plugins/metadata/custom-meta-boxes/)

---

## 📝 **Changelog de Documentación**

### v2.0 (Actual)
- ✅ README.md completo
- ✅ RESUMEN_IMPLEMENTACION.md
- ✅ ARQUITECTURA_MODULOS.md
- ✅ DIAGRAMA_ARQUITECTURA.md
- ✅ PLANTILLA_MODULO.php
- ✅ NUEVAS_FUNCIONALIDADES.md
- ✅ INDICE_DOCUMENTACION.md (este archivo)
- ✅ Documentación inline en todos los módulos
- ✅ Templates con comentarios explicativos

---

## 🎯 **Próximos Pasos Recomendados**

1. **Si eres usuario**: Empieza por [RESUMEN_IMPLEMENTACION.md](./RESUMEN_IMPLEMENTACION.md)

2. **Si eres desarrollador**: Lee [ARQUITECTURA_MODULOS.md](./ARQUITECTURA_MODULOS.md)

3. **Si quieres crear módulos**: Revisa [PLANTILLA_MODULO.php](./includes/modules/PLANTILLA_MODULO.php)

4. **Si quieres personalizar**: Ver [NUEVAS_FUNCIONALIDADES.md](./NUEVAS_FUNCIONALIDADES.md) sección Templates

---

**¡Toda la información que necesitas está aquí! 📚✨**

Navega por los documentos según tus necesidades y no dudes en revisar el código de ejemplo.

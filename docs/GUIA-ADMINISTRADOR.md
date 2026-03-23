# Guía de Administración - Flavor Platform

Manual completo para administradores de sitios WordPress con Flavor Platform.

## Índice

1. [Requisitos Previos](#1-requisitos-previos)
2. [Instalación y Activación](#2-instalación-y-activación)
3. [Asistente de Configuración Inicial](#3-asistente-de-configuración-inicial)
4. [Panel de Administración](#4-panel-de-administración)
5. [Gestión de Módulos](#5-gestión-de-módulos)
6. [Configuración de Diseño](#6-configuración-de-diseño)
7. [Menús y Navegación](#7-menús-y-navegación)
8. [Chat IA y Proveedores](#8-chat-ia-y-proveedores)
9. [Usuarios y Roles](#9-usuarios-y-roles)
10. [Datos de Demostración](#10-datos-de-demostración)
11. [Exportación e Importación](#11-exportación-e-importación)
12. [Aplicaciones Móviles](#12-aplicaciones-móviles)
13. [Mantenimiento y Diagnóstico](#13-mantenimiento-y-diagnóstico)
14. [Solución de Problemas](#14-solución-de-problemas)

---

## 1. Requisitos Previos

### Requisitos del Sistema

| Componente | Versión Mínima | Recomendada |
|------------|----------------|-------------|
| PHP | 7.4 | 8.1+ |
| WordPress | 5.8 | 6.4+ |
| MySQL | 5.7 | 8.0+ |
| Memoria PHP | 128MB | 256MB+ |

### Extensiones PHP Requeridas

- `curl` - Para comunicación con APIs de IA
- `json` - Procesamiento de datos
- `mbstring` - Soporte de caracteres multibyte
- `openssl` - Encriptación de API keys

### Permisos de Archivos

```
wp-content/plugins/flavor-chat-ia/  → 755
wp-content/uploads/                  → 755
```

---

## 2. Instalación y Activación

### Instalación Manual

1. Descomprimir el plugin en `wp-content/plugins/`
2. Ir a **Plugins → Plugins instalados**
3. Buscar "Flavor Platform" y hacer clic en **Activar**

### Primera Activación

Al activar el plugin por primera vez:

1. Se crean las tablas de base de datos necesarias
2. Se registran los roles personalizados
3. Se redirige automáticamente al Asistente de Configuración

> **Nota**: Si se omite el asistente, puedes acceder después desde **Flavor Platform → Configuración → Asistente**.

---

## 3. Asistente de Configuración Inicial

El Setup Wizard guía la configuración inicial en 7 pasos.

### Paso 1: Bienvenida

Selecciona el tipo de organización:

| Perfil | Descripción | Módulos Incluidos |
|--------|-------------|-------------------|
| **Comunidad Vecinal** | Asociaciones de vecinos, comunidades | Socios, Eventos, Espacios, Incidencias |
| **Cooperativa** | Cooperativas de consumo, trabajo | Grupos Consumo, Marketplace, Banco Tiempo |
| **Ayuntamiento** | Administración municipal | Participación, Transparencia, Trámites |
| **Asociación Cultural** | Centros culturales, colectivos | Eventos, Talleres, Biblioteca, Foros |
| **Empresa Social** | Economía social, emprendimiento | Marketplace, Socios, Facturación |
| **Personalizado** | Configuración manual | Selección libre |

### Paso 2: Información Básica

- **Nombre del sitio**: Aparece en cabeceras y emails
- **Logo**: Subir imagen (recomendado: 200x60px PNG)
- **Color primario**: Color principal de la interfaz
- **Color secundario**: Color de acentos

### Paso 3: Módulos

Activa los módulos que necesites. Se preseleccionan según el perfil elegido.

**Categorías de módulos:**

- **Gestión de Personas**: Socios, Fichaje, Clientes
- **Economía Local**: Marketplace, Banco Tiempo, Grupos Consumo
- **Participación**: Encuestas, Presupuestos Participativos, Foros
- **Servicios**: Eventos, Talleres, Cursos, Reservas
- **Comunicación**: Chat, Avisos, Newsletter

### Paso 4: Diseño

Selecciona un tema visual:

| Tema | Colores | Ideal para |
|------|---------|------------|
| **Clásico** | Azul / Gris | Uso general |
| **Púrpura Moderno** | Púrpura / Slate | Creativos, culturales |
| **Océano** | Cyan / Slate | Medio ambiente, cooperativas |
| **Bosque** | Verde / Slate | Ecología, huertos |
| **Atardecer** | Naranja / Stone | Cálido, acogedor |
| **Rosa** | Rosa / Slate | Comunidades femeninas |
| **Modo Oscuro** | Azul claro / Slate oscuro | Preferencia oscura |
| **Minimalista** | Negro / Gris | Empresarial, formal |

### Paso 5: Datos de Demostración

Opción para importar contenido de ejemplo:

- Usuarios ficticios con diferentes roles
- Productos en marketplace
- Eventos de ejemplo
- Contenido en módulos activos

> **Recomendación**: Importar demo para evaluar funcionalidades, luego limpiar antes de producción.

### Paso 6: Notificaciones

Configura el sistema de notificaciones:

- **Email de origen**: Dirección para envío de correos
- **Nombre del remitente**: Nombre que aparece en emails
- **Notificaciones push**: Habilitar para apps móviles (requiere Firebase)

### Paso 7: Resumen

Revisa la configuración y finaliza. El sistema:

1. Guarda todas las opciones
2. Activa los módulos seleccionados
3. Aplica el tema de diseño
4. Importa datos demo (si se seleccionó)
5. Redirige al Dashboard principal

---

## 4. Panel de Administración

### Estructura del Menú

El menú principal **Flavor Platform** se organiza en secciones:

```
Flavor Platform
├── PRINCIPAL
│   ├── Dashboard
│   ├── Dashboard Unificado
│   └── Índice de Dashboards
│
├── MI APP
│   ├── Compositor de Módulos
│   ├── Diseño y Apariencia
│   ├── Layouts (Menús y Footers)
│   ├── Crear Páginas
│   └── Permisos
│
├── CHAT IA
│   ├── Configuración IA
│   └── Escalados
│
├── APPS Y CONECTIVIDAD
│   ├── Apps Móviles
│   ├── Deep Links
│   └── Red de Nodos
│
├── EXTENSIONES
│   ├── Addons
│   └── Marketplace
│
├── HERRAMIENTAS
│   ├── Export / Import
│   ├── Diagnóstico
│   ├── Registro de Actividad
│   └── Analytics
│
└── AYUDA
    ├── Documentación
    └── Tours Guiados
```

### Dashboard Principal

El dashboard muestra:

- **Estadísticas en tiempo real**: Usuarios, contenido, actividad
- **Gráficos**: Evolución de métricas principales
- **Acciones rápidas**: Accesos directos a tareas frecuentes
- **Estado de módulos**: Resumen de módulos activos
- **Alertas del sistema**: Avisos y notificaciones importantes

> **Actualización automática**: Los datos se refrescan cada 60 segundos.

### Cambio de Vista

El sistema soporta diferentes vistas según el rol:

1. **Vista Admin**: Acceso completo a todos los menús
2. **Vista Gestor**: Menú reducido para gestores de contenido

Para cambiar de vista: Clic en el selector de vista en la cabecera del admin.

---

## 5. Gestión de Módulos

### Compositor de Módulos

Accede desde **Flavor Platform → Compositor de Módulos**.

#### Activar/Desactivar Módulos

1. Localiza el módulo en la lista
2. Usa el interruptor para activar/desactivar
3. Los cambios se aplican inmediatamente

#### Configuración por Módulo

Cada módulo tiene su propia configuración:

1. Clic en el icono de engranaje del módulo
2. Ajusta las opciones específicas
3. Guarda los cambios

### Módulos Disponibles (60+)

#### Gestión de Personas
| Módulo | Descripción |
|--------|-------------|
| Socios | Gestión de membresías, cuotas, carnets |
| Clientes | CRM básico para clientes |
| Fichaje Empleados | Control horario con geolocalización |

#### Economía Local
| Módulo | Descripción |
|--------|-------------|
| Marketplace | Tienda de productos y servicios locales |
| Banco de Tiempo | Intercambio de servicios por horas |
| Grupos de Consumo | Pedidos colectivos a productores |
| Crowdfunding | Financiación colectiva de proyectos |

#### Participación
| Módulo | Descripción |
|--------|-------------|
| Encuestas | Crear y gestionar encuestas |
| Presupuestos Participativos | Votación de proyectos |
| Foros | Debates y discusiones |
| Transparencia | Portal de datos públicos |

#### Servicios
| Módulo | Descripción |
|--------|-------------|
| Eventos | Calendario y gestión de eventos |
| Talleres | Formación con inscripciones |
| Cursos | Formación online con aulas virtuales |
| Reservas | Reserva de espacios y recursos |
| Biblioteca | Préstamo de libros y materiales |

#### Comunicación
| Módulo | Descripción |
|--------|-------------|
| Avisos | Tablón de anuncios |
| Chat Interno | Mensajería entre usuarios |
| Chat Grupos | Canales de grupo |
| Newsletter | Campañas de email |

### Dependencias entre Módulos

Algunos módulos requieren otros para funcionar:

```
Grupos de Consumo → requiere → Socios
Aulas Virtuales  → requiere → Cursos
Facturación      → requiere → Socios o Clientes
```

El sistema avisa automáticamente si faltan dependencias.

---

## 6. Configuración de Diseño

### Acceso

**Flavor Platform → Diseño y Apariencia**

### Secciones de Configuración

#### Colores

| Opción | Descripción | Ejemplo |
|--------|-------------|---------|
| Color primario | Botones, enlaces, acentos | `#3b82f6` |
| Color secundario | Fondos sutiles, bordes | `#6b7280` |
| Color de fondo | Fondo general | `#ffffff` |
| Color de texto | Texto principal | `#1f2937` |
| Color de éxito | Mensajes positivos | `#16a34a` |
| Color de error | Mensajes de error | `#dc2626` |

#### Tipografía

| Opción | Descripción |
|--------|-------------|
| Fuente principal | Familia tipográfica para textos |
| Fuente de títulos | Familia para encabezados |
| Tamaño base | Tamaño de texto base (14-18px) |
| Altura de línea | Espaciado entre líneas |

#### Espaciados

| Opción | Descripción |
|--------|-------------|
| Radio de bordes | Redondeo de esquinas |
| Espaciado de secciones | Margen entre bloques |
| Padding de componentes | Relleno interno |

#### Componentes

| Opción | Descripción |
|--------|-------------|
| Estilo de botones | Sólido, outline, ghost |
| Estilo de tarjetas | Sombra, borde, flat |
| Estilo de inputs | Relleno, underline, outline |

### Aplicar un Tema Predefinido

1. Ir a **Diseño y Apariencia**
2. Sección **Temas**
3. Clic en **Aplicar** en el tema deseado
4. Todos los colores se actualizan automáticamente

### Previsualización

Usa el botón **Previsualizar** para ver cambios antes de guardar.

---

## 7. Menús y Navegación

### Gestión de Layouts

Accede desde **Flavor Platform → Layouts (Menús y Footers)**.

#### Tipos de Menú

| Tipo | Ubicación | Uso |
|------|-----------|-----|
| Header principal | Cabecera del sitio | Navegación principal |
| Header móvil | Menú hamburguesa | Navegación en móviles |
| Footer | Pie de página | Enlaces secundarios |
| Sidebar | Lateral | Navegación contextual |
| Dashboard | Panel de usuario | Menú del área privada |

#### Crear un Menú

1. Ir a **Layouts**
2. Clic en **Nuevo Menú**
3. Asignar nombre y ubicación
4. Añadir elementos:
   - Páginas
   - Enlaces personalizados
   - Módulos (generan enlace automático)
   - Categorías
5. Ordenar arrastrando
6. Guardar

#### Menús por Vista

Puedes configurar menús diferentes según el rol del usuario:

1. Ir a **Configuración de Vistas**
2. Seleccionar rol/vista
3. Asignar menú específico
4. Los usuarios verán el menú correspondiente a su rol

### Visibilidad de Elementos

Cada elemento del menú puede configurarse:

- **Visible para todos**: Público
- **Solo usuarios registrados**: Requiere login
- **Roles específicos**: Solo ciertos roles
- **Módulo activo**: Solo si el módulo está habilitado

---

## 8. Chat IA y Proveedores

### Configuración del Chat

Accede desde **Flavor Platform → Configuración IA**.

#### Pestaña General

| Opción | Descripción |
|--------|-------------|
| Chat habilitado | Activa/desactiva el chat |
| Solo administradores | Restringe uso a admins |
| Widget flotante | Mostrar burbuja de chat |
| Nombre del asistente | Nombre que ve el usuario |
| Rol del asistente | Descripción del rol |
| Tono | Formal, amigable, técnico |

#### Pestaña Proveedores

Configura las API keys de los proveedores de IA:

| Proveedor | Modelos Disponibles |
|-----------|---------------------|
| **Claude (Anthropic)** | claude-3-opus, claude-3-sonnet, claude-3-haiku |
| **OpenAI** | gpt-4-turbo, gpt-4, gpt-3.5-turbo |
| **DeepSeek** | deepseek-chat, deepseek-coder |
| **Mistral** | mistral-large, mistral-medium, mistral-small |

**Configurar un proveedor:**

1. Seleccionar proveedor activo
2. Introducir API Key
3. Seleccionar modelo
4. Configurar tokens máximos
5. Guardar

> **Seguridad**: Las API keys se almacenan encriptadas en la base de datos.

#### Pestaña Límites

| Opción | Descripción |
|--------|-------------|
| Mensajes por sesión | Límite de mensajes por conversación |
| Tokens por mensaje | Límite de tokens en respuestas |
| Timeout | Tiempo máximo de espera |

#### Pestaña Contexto

Configura el conocimiento base del asistente:

- **Contexto del sitio**: Información sobre la organización
- **Instrucciones**: Comportamiento del asistente
- **Restricciones**: Temas a evitar
- **FAQs**: Preguntas frecuentes predefinidas

### Escalados

Cuando el chat no puede resolver una consulta:

1. Ir a **Flavor Platform → Escalados**
2. Ver conversaciones escaladas
3. Responder manualmente
4. Marcar como resuelto

---

## 9. Usuarios y Roles

### Roles Predefinidos

| Rol | Slug | Descripción |
|-----|------|-------------|
| Visitante | `flavor_visitante` | Acceso de solo lectura |
| Socio/Miembro | `flavor_socio` | Usuario registrado básico |
| Gestor | `flavor_gestor` | Administrador de contenido |
| Líder de Comunidad | `flavor_community_leader` | Gestión de grupos |
| Administrador | `flavor_admin` | Control total |

### Capacidades por Rol

#### Visitante
- Ver contenido público
- Ver productos y eventos
- Leer foros

#### Socio/Miembro
- Todo lo de Visitante
- Acceder al dashboard personal
- Crear pedidos y reservas
- Participar en foros
- Inscribirse en eventos

#### Gestor
- Todo lo de Socio
- Gestionar eventos y talleres
- Moderar foros
- Gestionar reservas
- Ver reportes

#### Administrador
- Acceso completo
- Configuración del sistema
- Gestión de usuarios
- Configuración de módulos

### Gestión de Permisos

Accede desde **Flavor Platform → Permisos**.

#### Pestaña Roles

1. Ver roles existentes
2. Crear nuevos roles personalizados
3. Asignar capacidades a roles
4. Eliminar roles (excepto predefinidos)

#### Pestaña Usuarios

1. Buscar usuario
2. Ver rol actual
3. Cambiar rol
4. Asignar permisos adicionales

#### Pestaña Módulos

Control de acceso granular por módulo:

1. Seleccionar módulo
2. Definir qué roles pueden:
   - Ver el módulo
   - Crear contenido
   - Editar contenido
   - Eliminar contenido
   - Administrar

---

## 10. Datos de Demostración

### Generar Datos Demo

1. Ir a **Flavor Platform → Herramientas → Demo Datos**
2. Clic en **Generar Datos de Demo**

Se crean:
- 5 usuarios de ejemplo con diferentes perfiles
- Productos en marketplace
- Servicios en banco de tiempo
- Eventos de ejemplo
- Contenido en módulos activos

### Usuarios de Demo

| Usuario | Negocio | Ubicación |
|---------|---------|-----------|
| `maria_cosmetica` | Cosmética Natural | Estella |
| `carlos_carpintero` | Carpintería Artesana | Ayegui |
| `ana_diseno` | Diseño Gráfico | Estella |
| `pedro_huerta` | Huerta Ecológica | Villatuerta |
| `laura_asesora` | Asesoría Fiscal | Estella |

**Contraseña**: `demo2026` (para todos)

### Limpiar Datos Demo

1. Ir a **Demo Datos**
2. Clic en **Limpiar datos de demo**
3. Confirmar eliminación

Se eliminan:
- Usuarios con marca `is_demo_user`
- Posts con meta `is_demo`
- Registros en tablas con `is_demo = 1`

> **Importante**: Limpiar datos demo antes de pasar a producción.

---

## 11. Exportación e Importación

### Acceso

**Flavor Platform → Export / Import**

### Exportar Configuración

1. Seleccionar qué exportar:
   - ☑️ Configuración general
   - ☑️ Módulos activos
   - ☑️ Diseño y colores
   - ☑️ Menús
   - ☐ Contenido (opcional)
2. Clic en **Exportar**
3. Se descarga archivo `.json`

### Importar Configuración

1. Seleccionar archivo `.json`
2. Vista previa de cambios
3. Opciones:
   - ☑️ Sobrescribir configuración existente
   - ☐ Modo prueba (no aplica cambios)
4. Clic en **Importar**

### Migración entre Sitios

Para migrar a otro sitio:

1. **Sitio origen**: Exportar configuración completa
2. **Sitio destino**: Instalar y activar plugin
3. **Sitio destino**: Importar configuración
4. Verificar que todo funciona
5. Ajustar URLs si es necesario

### API de Exportación

También disponible vía REST API:

```bash
# Exportar
GET /wp-json/flavor-site-builder/v1/site/export

# Importar
POST /wp-json/flavor-site-builder/v1/site/import
Content-Type: application/json
X-VBP-Key: tu-api-key

{...configuración...}
```

---

## 12. Aplicaciones Móviles

### Configuración de Apps

Accede desde **Flavor Platform → Apps Móviles**.

#### Información Básica

| Campo | Descripción |
|-------|-------------|
| Nombre de la app | Nombre que aparece en el dispositivo |
| ID de paquete | Identificador único (ej: `com.miorg.app`) |
| Versión | Número de versión (ej: `1.0.0`) |
| Descripción | Descripción para stores |

#### Diseño de la App

- **Icono**: Imagen 1024x1024px
- **Splash screen**: Imagen de carga
- **Colores**: Heredados del sitio o personalizados
- **Tema**: Claro, oscuro, automático

#### Módulos en App

Selecciona qué módulos aparecen en la app móvil:

1. Lista de módulos disponibles
2. Arrastrar para ordenar
3. Mostrar/ocultar cada módulo
4. Configurar icono y nombre en app

#### Firebase (Push Notifications)

1. Crear proyecto en Firebase Console
2. Obtener configuración:
   - `apiKey`
   - `projectId`
   - `messagingSenderId`
   - `appId`
3. Introducir en configuración
4. Guardar

### Compilar la App

El plugin genera código Flutter listo para compilar:

1. Ir a **Apps Móviles → Compilar**
2. Descargar proyecto Flutter
3. Compilar con Flutter SDK:
   ```bash
   flutter build apk  # Android
   flutter build ios  # iOS
   ```

---

## 13. Mantenimiento y Diagnóstico

### Health Check

Accede desde **Flavor Platform → Diagnóstico**.

El sistema verifica:

| Componente | Verifica |
|------------|----------|
| Base de datos | Conexión y tablas |
| Módulos | Estado de carga |
| APIs | Conectividad con proveedores |
| Archivos | Permisos de escritura |
| Caché | Estado y tamaño |
| Cron | Tareas programadas |

#### Estados

- ✅ **OK**: Funcionando correctamente
- ⚠️ **Advertencia**: Funciona pero hay problemas menores
- ❌ **Error**: No funciona, requiere atención

### Registro de Actividad

**Flavor Platform → Registro de Actividad**

Muestra:
- Acciones de usuarios
- Cambios de configuración
- Errores del sistema
- Accesos a la API

Filtros disponibles:
- Por usuario
- Por tipo de acción
- Por fecha
- Por módulo

### Limpieza de Caché

1. Ir a **Diagnóstico**
2. Sección **Caché**
3. Clic en **Limpiar caché**

Se limpia:
- Transients de WordPress
- Caché de configuración
- Caché de consultas

### Tareas Programadas (Cron)

El plugin registra estas tareas:

| Tarea | Frecuencia | Descripción |
|-------|------------|-------------|
| `flavor_daily_cleanup` | Diaria | Limpieza de datos antiguos |
| `flavor_sync_nodes` | Cada 6h | Sincronización de red |
| `flavor_send_notifications` | Cada 15min | Envío de notificaciones |
| `flavor_generate_reports` | Semanal | Generación de informes |

### Logs de Debug

Para activar logs detallados:

1. Editar `wp-config.php`
2. Añadir:
   ```php
   define('FLAVOR_CHAT_IA_DEBUG', true);
   ```
3. Los logs aparecen en `wp-content/debug.log`

---

## 14. Solución de Problemas

### Problemas Comunes

#### El chat IA no responde

**Causas posibles:**
1. API Key inválida o expirada
2. Proveedor no seleccionado
3. Límite de tokens alcanzado

**Solución:**
1. Verificar API Key en configuración
2. Probar con otro proveedor
3. Revisar logs de error

#### Los módulos no aparecen

**Causas posibles:**
1. Módulo no activado
2. Usuario sin permisos
3. Error de carga

**Solución:**
1. Verificar en Compositor de Módulos
2. Revisar permisos del usuario
3. Ir a Diagnóstico y verificar estado

#### Error de permisos al guardar

**Causas posibles:**
1. Sesión expirada
2. Nonce inválido
3. Permisos insuficientes

**Solución:**
1. Cerrar sesión y volver a entrar
2. Limpiar caché del navegador
3. Verificar rol del usuario

#### Las notificaciones no llegan

**Causas posibles:**
1. Email mal configurado
2. Firebase no configurado
3. Cola de emails bloqueada

**Solución:**
1. Verificar configuración de email
2. Configurar Firebase para push
3. Instalar plugin de SMTP

#### La app móvil no conecta

**Causas posibles:**
1. URL del sitio incorrecta
2. SSL no configurado
3. API bloqueada

**Solución:**
1. Verificar URL en configuración de app
2. Asegurar que el sitio usa HTTPS
3. Verificar que REST API está accesible

### Restablecer Configuración

Si necesitas empezar de cero:

1. Ir a **Diagnóstico**
2. Sección **Reset**
3. Seleccionar qué restablecer:
   - ☐ Configuración general
   - ☐ Módulos
   - ☐ Diseño
   - ☐ Permisos
4. Confirmar acción

> **Advertencia**: Esta acción no se puede deshacer.

### Soporte

Si los problemas persisten:

1. Revisar la documentación en **Flavor Platform → Documentación**
2. Usar los tours guiados en **Flavor Platform → Tours**
3. Exportar configuración y logs para diagnóstico

---

## Apéndice A: Referencia de Opciones

### Opciones de Configuración

| Opción | Descripción |
|--------|-------------|
| `flavor_setup_completed` | Si se completó el setup |
| `flavor_selected_profile` | Perfil seleccionado |
| `flavor_design_settings` | Configuración de diseño |
| `flavor_active_theme` | Tema visual activo |
| `flavor_chat_ia_settings` | Configuración del chat |
| `flavor_modules_visibility` | Visibilidad de módulos |
| `flavor_apps_config` | Configuración de apps |
| `flavor_role_permissions` | Permisos personalizados |

### Opciones por Módulo

Patrón: `flavor_chat_ia_module_{nombre_modulo}`

Ejemplo:
```php
get_option('flavor_chat_ia_module_socios');
// Devuelve: ['enabled' => true, 'settings' => [...]]
```

---

## Apéndice B: Hooks para Desarrolladores

### Acciones

```php
// Después de activar un módulo
do_action('flavor_module_activated', $module_id);

// Después de desactivar un módulo
do_action('flavor_module_deactivated', $module_id);

// Al guardar configuración
do_action('flavor_settings_saved', $settings);

// Al completar setup
do_action('flavor_setup_completed', $profile);
```

### Filtros

```php
// Modificar módulos disponibles
apply_filters('flavor_available_modules', $modules);

// Modificar temas disponibles
apply_filters('flavor_available_themes', $themes);

// Modificar capacidades de rol
apply_filters('flavor_role_capabilities', $caps, $role);

// Modificar menú de admin
apply_filters('flavor_admin_menu_items', $items);
```

---

## Apéndice C: Atajos de Teclado

| Atajo | Acción |
|-------|--------|
| `Ctrl + S` | Guardar cambios |
| `Ctrl + Z` | Deshacer |
| `Ctrl + Shift + Z` | Rehacer |
| `Esc` | Cerrar modal |
| `?` | Mostrar ayuda |

---

## Apéndice D: Glosario

| Término | Definición |
|---------|------------|
| **Módulo** | Funcionalidad específica activable/desactivable |
| **Perfil** | Conjunto predefinido de módulos para un tipo de organización |
| **Shortcode** | Código corto que inserta funcionalidad en páginas |
| **Layout** | Combinación de menú y footer |
| **VBP** | Visual Builder Pro, constructor de páginas |
| **Socio** | Usuario registrado con membresía |
| **Dashboard** | Panel de control con estadísticas |
| **Transient** | Caché temporal de WordPress |

---

## Historial de Versiones

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 3.0 | 2026-03 | Versión completa con todos los módulos |
| 2.2 | 2026-02 | Añadidos proveedores IA adicionales |
| 2.0 | 2026-01 | Rediseño del panel de administración |

---

*Guía de Administración - Flavor Platform v3.0*
*Última actualización: Marzo 2026*

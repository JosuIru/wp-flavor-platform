# ⚠️ P0 #6: TABLAS BD - ANÁLISIS DE SISTEMA DUAL

**Fecha**: 2026-03-23
**Estado**: 🔴 PROBLEMA CONFIRMADO - Requiere Resolución
**Criticidad**: 🔥🔥🔥 ALTA
**Tiempo estimado**: 0.5 día

---

## 📋 Resumen Ejecutivo

El reporte identificó correctamente un problema de creación de tablas, pero la **causa raíz es más compleja** de lo reportado inicialmente.

**Hallazgo Principal**: Existen **DOS sistemas incompatibles** de creación de tablas operando simultáneamente:
1. **Sistema antiguo**: `Flavor_Database_Installer` con esquemas básicos (15-20 campos por tabla)
2. **Sistema nuevo**: Archivos `install.php` por módulo con esquemas completos (40-50 campos por tabla)

**Impacto**:
- ✅ **Socios, Eventos, Reservas**: Tablas SE crean, pero con esquema ANTIGUO/INCOMPLETO
- ❌ **Clientes**: NO se crean tablas (missing completamente)
- ❌ **Facturas**: Arquitectura compleja con gateways de pago, sin install.php

---

## 🔍 Análisis Detallado

### 1. Sistema de Creación de Tablas

#### A. Database Installer (Sistema Antiguo)

**Ubicación**: `includes/class-database-installer.php` (6,369 líneas)

**Función**: `Flavor_Database_Installer::install_tables()`

**Método de creación**:
```php
public static function install_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix . 'flavor_';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Array de todas las tablas a crear
    $tables = self::get_tables_sql($prefix, $charset_collate);

    foreach ($tables as $sql) {
        dbDelta($sql);  // Crea TODAS las tablas de golpe
    }

    update_option('flavor_db_version', '2.1.0');
}
```

**Tablas creadas**: 309 CREATE TABLE statements (todo el sistema)

**Momento de ejecución**:
- Durante activación del plugin (`class-database-setup.php::install()`)
- Llamada desde `Flavor_Database_Setup::install_via_database_installer()`

#### B. Install.php por Módulo (Sistema Nuevo)

**Ubicación**: `includes/modules/{modulo}/install.php`

**Archivos existentes**:
| Módulo | Archivo | Tamaño | Tablas |
|--------|---------|--------|--------|
| ✅ Socios | `modules/socios/install.php` | 9.7K | 4 |
| ✅ Eventos | `modules/eventos/install.php` | 8.3K | 3 |
| ✅ Reservas | `modules/reservas/install.php` | 3.4K | 2 |
| ❌ **Clientes** | **NO EXISTE** | - | - |
| ❌ **Facturas** | **NO EXISTE** | - | - |

**Método de creación** (ejemplo Socios):
```php
function flavor_socios_crear_tablas() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql_socios = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_socios (
        // ... 60+ campos completos ...
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_socios);
    // ... más tablas ...

    update_option('flavor_socios_db_version', '1.0.0');
}
```

**Momento de ejecución**:
- **Socios/Eventos**: Se esperaba `maybe_create_tables()` en módulo → **NO IMPLEMENTADO**
- **Reservas**: Llamada desde `Flavor_Database_Setup::create_module_tables()` → ✅ FUNCIONA

---

### 2. Comparativa de Esquemas: Socios

#### Esquema Database Installer (Antiguo)

**Ubicación**: `includes/class-database-installer.php:453`

```sql
CREATE TABLE flavor_socios (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    telefono varchar(50),
    direccion varchar(500),
    ciudad varchar(100),
    codigo_postal varchar(10),
    fecha_alta date,
    fecha_baja date,
    estado varchar(50) DEFAULT 'activo',
    tipo_socio varchar(50) DEFAULT 'ordinario',
    cuota_mensual decimal(10,2) DEFAULT 0,
    notas text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY email (email),
    KEY estado (estado)
)
```

**Total**: ~17 campos

#### Esquema install.php (Nuevo)

**Ubicación**: `includes/modules/socios/install.php:25`

```sql
CREATE TABLE IF NOT EXISTS flavor_socios (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    numero_socio varchar(50) NOT NULL,
    usuario_id bigint(20) unsigned DEFAULT NULL,
    nombre varchar(100) NOT NULL,
    apellidos varchar(150) DEFAULT NULL,
    email varchar(200) NOT NULL,
    telefono varchar(50) DEFAULT NULL,
    telefono_secundario varchar(50) DEFAULT NULL,
    dni_nif varchar(20) DEFAULT NULL,
    fecha_nacimiento date DEFAULT NULL,
    genero enum('masculino','femenino','otro','no_especificado') DEFAULT 'no_especificado',
    direccion varchar(500) DEFAULT NULL,
    codigo_postal varchar(10) DEFAULT NULL,
    ciudad varchar(100) DEFAULT NULL,
    provincia varchar(100) DEFAULT NULL,
    pais varchar(100) DEFAULT 'Espana',
    imagen_perfil varchar(500) DEFAULT NULL,
    tipo_socio varchar(50) NOT NULL DEFAULT 'ordinario',
    categoria varchar(50) DEFAULT NULL,
    fecha_alta date NOT NULL,
    fecha_baja date DEFAULT NULL,
    motivo_baja text DEFAULT NULL,
    estado enum('pendiente','activo','suspendido','baja','moroso') NOT NULL DEFAULT 'pendiente',
    cuota_tipo varchar(50) DEFAULT 'mensual',
    cuota_importe decimal(10,2) DEFAULT 0.00,
    cuota_reducida tinyint(1) NOT NULL DEFAULT 0,
    motivo_reduccion text DEFAULT NULL,
    forma_pago enum('domiciliacion','transferencia','efectivo','tarjeta') DEFAULT 'transferencia',
    iban varchar(34) DEFAULT NULL,
    mandato_sepa varchar(50) DEFAULT NULL,
    comunicaciones_email tinyint(1) NOT NULL DEFAULT 1,
    comunicaciones_sms tinyint(1) NOT NULL DEFAULT 0,
    comunicaciones_postal tinyint(1) NOT NULL DEFAULT 0,
    intereses text DEFAULT NULL,
    notas text DEFAULT NULL,
    referido_por bigint(20) unsigned DEFAULT NULL,
    carnet_emitido tinyint(1) NOT NULL DEFAULT 0,
    carnet_fecha_emision date DEFAULT NULL,
    ultimo_acceso datetime DEFAULT NULL,
    metadata json DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY numero_socio (numero_socio),
    UNIQUE KEY email (email),
    KEY usuario_id (usuario_id),
    KEY tipo_socio (tipo_socio),
    KEY estado (estado),
    KEY fecha_alta (fecha_alta),
    KEY ciudad (ciudad),
    KEY dni_nif (dni_nif),
    FULLTEXT KEY busqueda (nombre, apellidos, email)
)
```

**Total**: ~42 campos + FULLTEXT search + más índices

**Campos CRÍTICOS faltantes en esquema antiguo**:
- ❌ `numero_socio` (UNIQUE) - Identificador visible del socio
- ❌ `usuario_id` - Relación con usuario WordPress
- ❌ `apellidos` - Nombre completo split
- ❌ `dni_nif` - Documento identificación
- ❌ `cuota_tipo` - Periodicidad de cuota
- ❌ `cuota_importe` - Importe variable
- ❌ `forma_pago`, `iban`, `mandato_sepa` - Domiciliación bancaria
- ❌ `metadata` (JSON) - Campos extensibles
- ❌ `carnet_emitido`, `carnet_fecha_emision` - Sistema de carnets

---

### 3. Comparativa de Esquemas: Eventos

#### Esquema Database Installer (Antiguo)

```sql
CREATE TABLE flavor_eventos (
    id, titulo, descripcion, fecha_inicio, fecha_fin, lugar,
    ubicacion, latitud, longitud, capacidad, precio, imagen,
    estado, created_at, updated_at
)
```

**Total**: ~15 campos

#### Esquema install.php (Nuevo)

**Total**: ~46 campos incluyen:
- `slug`, `contenido`, `extracto`, `imagen_destacada`, `galeria_imagenes`
- `tipo`, `categoria`, `etiquetas`
- `es_recurrente`, `recurrencia_tipo`, `recurrencia_config` (JSON)
- `ubicacion_tipo` (presencial/online/híbrido)
- `url_online`, `plataforma_online`
- `organizador_id`, `comunidad_id`, `organizador_nombre/email/telefono`
- `aforo_maximo`, `inscritos_count`, `lista_espera_count`
- `requiere_inscripcion`, `fecha_limite_inscripcion`
- `precio`, `precio_socios`, `moneda`, `es_gratuito`
- `visibilidad` (público/privado/socios)
- `es_destacado`, `visualizaciones`
- `metadata` (JSON)
- **FULLTEXT search** en título, descripción, ubicación

**Impacto**: Sin esquema completo, el módulo NO puede:
- Gestionar eventos recurrentes
- Diferencia presencial/online
- Sistema de inscripción con aforo
- Precios diferenciados para socios
- Visibilidad por roles

---

### 4. Estado por Módulo

#### A. Socios (❌ PROBLEMA)

**Código del módulo**:
```php
// includes/modules/socios/class-socios-module.php:122
public function init() {
    add_action('init', [$this, 'maybe_create_tables']);  // Llama a método
    // ...
}

public function can_activate() {
    global $wpdb;
    $tabla_socios = $wpdb->prefix . 'flavor_socios';
    return Flavor_Chat_Helpers::tabla_existe($tabla_socios);
}
```

**Problemas**:
1. ❌ Llama a `maybe_create_tables()` que **NO ESTÁ IMPLEMENTADO** (grep sin resultados)
2. ✅ `can_activate()` verifica tabla existe
3. ⚠️ Tabla SE crea via Database Installer con esquema antiguo
4. ❌ Módulo usa campos que NO existen en esquema antiguo

**Resultado**: Módulo puede activarse pero funciones avanzadas FALLAN silenciosamente

#### B. Eventos (❌ PROBLEMA SIMILAR)

**Código**: Similar a Socios

**Problemas**:
1. ❌ `maybe_create_tables()` NO implementado
2. ⚠️ Tabla creada con esquema antiguo (15 campos vs 46)
3. ❌ Funcionalidades como eventos recurrentes, online, inscripción con aforo NO funcionan

#### C. Reservas (✅ FUNCIONAL)

**Código**:
```php
// includes/modules/reservas/class-reservas-module.php
// NO llama a maybe_create_tables()
```

**Creación de tablas**:
```php
// includes/bootstrap/class-database-setup.php:181-186
if (file_exists(FLAVOR_CHAT_IA_PATH . 'includes/modules/reservas/install.php')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/reservas/install.php';
    if (function_exists('flavor_reservas_crear_tabla')) {
        flavor_reservas_crear_tabla();  // ✅ SE LLAMA
    }
}
```

**Estado**: ✅ FUNCIONA - Las tablas se crean correctamente con esquema completo

#### D. Clientes (🔴 CRÍTICO)

**Código**:
```php
// includes/modules/clientes/class-clientes-module.php:78
public function init() {
    add_action('init', [$this, 'maybe_create_tables']);  // Método NO existe
}

public function can_activate() {
    global $wpdb;
    $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
    return Flavor_Chat_Helpers::tabla_existe($tabla_clientes);  // NUNCA true
}
```

**Problemas**:
1. ❌ NO tiene `install.php`
2. ❌ `maybe_create_tables()` NO implementado
3. ❌ Database Installer NO crea tabla `flavor_clientes` (grep sin resultados)
4. ❌ `can_activate()` SIEMPRE devuelve `false`

**Resultado**: **Módulo NO puede activarse NUNCA**

#### E. Facturas (🔴 CRÍTICO + COMPLEJO)

**Arquitectura especial**: Sistema de payment gateways

Archivos encontrados:
- `class-payment-gateway-manager.php` (391 líneas)
- `gateways/class-redsys-gateway.php` (330 líneas)
- `gateways/class-paypal-gateway.php` (318 líneas)
- `gateways/class-stripe-gateway.php` (probablemente existe)

**Problemas**:
1. ❌ NO tiene `install.php`
2. ❌ Sistema requiere múltiples tablas:
   - `flavor_facturas` - Facturas
   - `flavor_facturas_lineas` - Líneas de factura
   - `flavor_facturas_pagos` - Registro de pagos
   - `flavor_facturas_series` - Series de numeración
3. ❌ Funcionalidad de pago online implementada pero **SIN TABLAS**

**Evidencia de tablas requeridas**:
```php
// class-redsys-gateway.php:231
$factura_id = $wpdb->get_var($wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_redsys_order_id' AND meta_value = %s",
    $order_id
));
```

Usa `postmeta` como workaround, pero debería tener tabla propia.

---

### 5. Tablas Creadas por Database Installer

**Socios** (7 tablas):
1. `flavor_socios`
2. `flavor_socios_cuotas`
3. `flavor_socios_pagos`
4. `flavor_socios_historial`
5. `flavor_socios_beneficios`
6. `flavor_socios_tipos`
7. `flavor_socios_transacciones`

**Socios install.php** (4 tablas):
1. `flavor_socios`
2. `flavor_socios_cuotas`
3. `flavor_socios_tipos`
4. `flavor_socios_historial`

**Conflicto**: Database Installer crea 3 tablas EXTRA que install.php NO maneja:
- `flavor_socios_pagos` (duplica `cuotas`?)
- `flavor_socios_beneficios`
- `flavor_socios_transacciones`

---

## 🎯 Soluciones Propuestas

### Opción 1: Migrar a Sistema Nuevo (RECOMENDADO)

**Acción**: Hacer que todos los módulos usen sus `install.php` propios

**Pasos**:

1. **Crear install.php faltantes**:
   ```bash
   # Clientes
   includes/modules/clientes/install.php

   # Facturas
   includes/modules/facturas/install.php
   ```

2. **Modificar Database Setup** para llamar todos los install.php:
   ```php
   // includes/bootstrap/class-database-setup.php:create_module_tables()

   $modules_con_install = [
       'socios', 'eventos', 'reservas', 'clientes', 'facturas',
       // ... otros
   ];

   foreach ($modules_con_install as $module_slug) {
       $install_path = FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/install.php";
       if (file_exists($install_path)) {
           require_once $install_path;
           $function_name = "flavor_{$module_slug}_crear_tablas";
           if (function_exists($function_name)) {
               call_user_func($function_name);
           }
       }
   }
   ```

3. **DEPRECAR Database Installer** para tablas de módulos:
   - Mantener solo tablas core (chat_conversations, chat_messages, etc.)
   - Eliminar CREATE TABLE de módulos (socios, eventos, etc.)
   - Añadir migración para actualizar tablas existentes

4. **Implementar `maybe_create_tables()` en módulos**:
   ```php
   // Patrón para todos los módulos
   public function maybe_create_tables() {
       $db_version = get_option('flavor_socios_db_version', '');
       if ($db_version === '1.0.0') {
           return; // Ya instaladas
       }

       $install_path = dirname(__FILE__) . '/install.php';
       if (file_exists($install_path)) {
           require_once $install_path;
           flavor_socios_crear_tablas();
       }
   }
   ```

**Ventajas**:
- ✅ Esquemas completos y actualizados
- ✅ Mantenimiento descentralizado por módulo
- ✅ Versionado independiente de BD por módulo
- ✅ Facilita desarrollo de nuevos módulos

**Tiempo estimado**: 4-6 horas

### Opción 2: Actualizar Database Installer (NO RECOMENDADO)

**Acción**: Sincronizar esquemas de Database Installer con install.php

**Problemas**:
- ❌ Archivo ya tiene 6,369 líneas
- ❌ Difícil de mantener (309 tablas)
- ❌ Cambios en módulo requieren editar otro archivo
- ❌ No escala para futuro desarrollo

**Tiempo estimado**: 8-10 horas + mantenimiento continuo

---

## 📊 Plan de Implementación Recomendado

### Fase 1: Crear Install.php Faltantes (2 horas)

#### A. Clientes

```php
<?php
// includes/modules/clientes/install.php

if (!defined('ABSPATH')) {
    exit;
}

function flavor_clientes_crear_tablas() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
    $sql_clientes = "CREATE TABLE IF NOT EXISTS $tabla_clientes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        telefono varchar(50) DEFAULT NULL,
        empresa varchar(255) DEFAULT NULL,
        nif_cif varchar(20) DEFAULT NULL,
        direccion varchar(500) DEFAULT NULL,
        ciudad varchar(100) DEFAULT NULL,
        codigo_postal varchar(10) DEFAULT NULL,
        pais varchar(100) DEFAULT 'España',
        tipo_cliente enum('particular','empresa','autonomo') DEFAULT 'particular',
        estado enum('activo','inactivo','moroso') DEFAULT 'activo',
        fecha_alta date DEFAULT NULL,
        notas text DEFAULT NULL,
        usuario_id bigint(20) unsigned DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY email (email),
        KEY estado (estado),
        KEY tipo_cliente (tipo_cliente),
        KEY usuario_id (usuario_id),
        FULLTEXT KEY busqueda (nombre, email, empresa)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_clientes);

    update_option('flavor_clientes_db_version', '1.0.0');
}
```

#### B. Facturas

```php
<?php
// includes/modules/facturas/install.php

function flavor_facturas_crear_tablas() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // 1. Facturas
    $tabla_facturas = $wpdb->prefix . 'flavor_facturas';
    $sql_facturas = "CREATE TABLE IF NOT EXISTS $tabla_facturas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        numero_factura varchar(50) NOT NULL,
        serie varchar(10) DEFAULT 'A',
        cliente_id bigint(20) unsigned NOT NULL,
        fecha_emision date NOT NULL,
        fecha_vencimiento date DEFAULT NULL,
        base_imponible decimal(10,2) NOT NULL DEFAULT 0.00,
        iva decimal(10,2) NOT NULL DEFAULT 0.00,
        total decimal(10,2) NOT NULL DEFAULT 0.00,
        estado enum('borrador','emitida','enviada','pagada','vencida','cancelada') NOT NULL DEFAULT 'borrador',
        metodo_pago varchar(50) DEFAULT NULL,
        notas text DEFAULT NULL,
        pdf_url varchar(500) DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY numero_factura (numero_factura, serie),
        KEY cliente_id (cliente_id),
        KEY estado (estado),
        KEY fecha_emision (fecha_emision)
    ) $charset_collate;";

    // 2. Líneas de factura
    $tabla_lineas = $wpdb->prefix . 'flavor_facturas_lineas';
    $sql_lineas = "CREATE TABLE IF NOT EXISTS $tabla_lineas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        factura_id bigint(20) unsigned NOT NULL,
        concepto varchar(500) NOT NULL,
        cantidad decimal(10,2) NOT NULL DEFAULT 1.00,
        precio_unitario decimal(10,2) NOT NULL DEFAULT 0.00,
        iva_porcentaje decimal(5,2) NOT NULL DEFAULT 21.00,
        subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
        orden int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY factura_id (factura_id)
    ) $charset_collate;";

    // 3. Pagos
    $tabla_pagos = $wpdb->prefix . 'flavor_facturas_pagos';
    $sql_pagos = "CREATE TABLE IF NOT EXISTS $tabla_pagos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        factura_id bigint(20) unsigned NOT NULL,
        importe decimal(10,2) NOT NULL,
        fecha_pago date NOT NULL,
        metodo_pago varchar(50) NOT NULL,
        referencia varchar(100) DEFAULT NULL,
        notas text DEFAULT NULL,
        gateway varchar(50) DEFAULT NULL,
        gateway_transaction_id varchar(100) DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY factura_id (factura_id),
        KEY metodo_pago (metodo_pago)
    ) $charset_collate;";

    // 4. Series
    $tabla_series = $wpdb->prefix . 'flavor_facturas_series';
    $sql_series = "CREATE TABLE IF NOT EXISTS $tabla_series (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        serie varchar(10) NOT NULL,
        nombre varchar(100) NOT NULL,
        prefijo varchar(20) DEFAULT NULL,
        siguiente_numero int(11) NOT NULL DEFAULT 1,
        digitos int(11) NOT NULL DEFAULT 4,
        activa tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE KEY serie (serie)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_facturas);
    dbDelta($sql_lineas);
    dbDelta($sql_pagos);
    dbDelta($sql_series);

    // Serie por defecto
    $wpdb->insert($tabla_series, [
        'serie' => 'A',
        'nombre' => 'Serie General',
        'prefijo' => 'FAC',
        'siguiente_numero' => 1,
        'digitos' => 6,
        'activa' => 1
    ], ['%s', '%s', '%s', '%d', '%d', '%d']);

    update_option('flavor_facturas_db_version', '1.0.0');
}
```

### Fase 2: Implementar maybe_create_tables() en Módulos (1 hora)

```php
// Patrón para Socios, Eventos, Clientes, Facturas

public function maybe_create_tables() {
    $module_id = $this->id; // 'socios', 'eventos', etc.
    $db_version_key = "flavor_{$module_id}_db_version";
    $db_version = get_option($db_version_key, '');

    if ($db_version === '1.0.0') {
        return; // Ya instaladas
    }

    $install_path = dirname(__FILE__) . '/install.php';
    if (file_exists($install_path)) {
        require_once $install_path;

        $function_name = "flavor_{$module_id}_crear_tablas";
        if (function_exists($function_name)) {
            call_user_func($function_name);
        }
    }
}
```

### Fase 3: Modificar Database Setup (30 min)

```php
// includes/bootstrap/class-database-setup.php

public function create_module_tables() {
    // Lista de módulos con install.php
    $modules = [
        'socios',
        'eventos',
        'reservas',
        'clientes',
        'facturas',
        // Añadir más según se creen
    ];

    foreach ($modules as $module_slug) {
        $this->maybe_run_module_installer(
            $module_slug,
            "flavor_{$module_slug}_crear_tablas"
        );
    }

    // Sistemas especiales (mantener)
    if (class_exists('Flavor_Deep_Link_Manager')) {
        Flavor_Deep_Link_Manager::create_tables();
    }
    // ... resto de sistemas ...
}
```

### Fase 4: Crear Migración para Actualizar Tablas Existentes (1-2 horas)

```php
<?php
// includes/migrations/class-migration-update-module-tables.php

class Flavor_Migration_Update_Module_Tables extends Flavor_Migration_Base {

    public function up() {
        // Actualizar tablas creadas con esquema antiguo
        $this->update_socios_table();
        $this->update_eventos_table();
    }

    private function update_socios_table() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios';

        // Añadir campos faltantes
        $nuevos_campos = [
            "numero_socio varchar(50) DEFAULT NULL",
            "usuario_id bigint(20) unsigned DEFAULT NULL",
            "apellidos varchar(150) DEFAULT NULL",
            "dni_nif varchar(20) DEFAULT NULL",
            // ... más campos ...
        ];

        foreach ($nuevos_campos as $campo) {
            $nombre_campo = explode(' ', $campo)[0];

            // Verificar si existe
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $tabla, $nombre_campo
            ));

            if (!$existe) {
                $wpdb->query("ALTER TABLE $tabla ADD COLUMN $campo");
            }
        }

        // Añadir índices
        $wpdb->query("ALTER TABLE $tabla ADD UNIQUE KEY numero_socio (numero_socio)");
        $wpdb->query("ALTER TABLE $tabla ADD FULLTEXT KEY busqueda (nombre, apellidos, email)");
    }

    // Similar para eventos...
}
```

---

## ✅ Verificación Post-Implementación

```bash
# 1. Verificar que install.php se ejecutan
wp shell
php> flavor_clientes_crear_tablas();
php> flavor_facturas_crear_tablas();

# 2. Verificar tablas creadas
wp db query "SHOW TABLES LIKE 'wp_flavor_clientes'" --skip-column-names
wp db query "SHOW TABLES LIKE 'wp_flavor_facturas%'" --skip-column-names

# 3. Verificar esquemas completos
wp db query "SHOW CREATE TABLE wp_flavor_socios\G"
wp db query "SHOW CREATE TABLE wp_flavor_eventos\G"

# 4. Verificar campos críticos
wp db query "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'wp_flavor_socios' AND COLUMN_NAME IN ('numero_socio', 'dni_nif', 'metadata')"

# 5. Test activación de módulos
wp shell
php> $modulo_clientes = new Flavor_Chat_Clientes_Module();
php> echo $modulo_clientes->can_activate() ? 'OK' : 'FAIL';
```

---

## 📈 Impacto de la Solución

### Módulos Desbloqueados

**Antes**:
- 🔴 Clientes: NO activable
- 🔴 Facturas: NO activable
- ⚠️ Socios: Activable pero funciones limitadas
- ⚠️ Eventos: Activable pero funciones limitadas

**Después**:
- ✅ Clientes: Totalmente funcional
- ✅ Facturas: Pago online + gestión completa
- ✅ Socios: Todas las funciones (carnets, SEPA, metadata)
- ✅ Eventos: Recurrencia, online, aforo, inscripciones

### Funcionalidades Desbloqueadas

**Socios**:
- ✅ Sistema de carnets digitales
- ✅ Domiciliación SEPA (IBAN, mandato)
- ✅ Cuotas variables por socio
- ✅ Metadata extensible
- ✅ Búsqueda FULLTEXT

**Eventos**:
- ✅ Eventos recurrentes (diarios, semanales, mensuales)
- ✅ Eventos online/híbridos (Zoom, Meet)
- ✅ Sistema de aforo con lista de espera
- ✅ Inscripción limitada por fecha
- ✅ Precios diferenciados socios/no socios
- ✅ Visibilidad por roles

**Facturas**:
- ✅ Numeración automática por series
- ✅ Gestión de líneas de factura
- ✅ Registro de pagos parciales
- ✅ Integración payment gateways (Stripe, PayPal, Redsys)

---

## 🎯 Conclusión

**Estado actual**: 🔴 Sistema dual conflictivo
- Database Installer crea esquemas antiguos
- install.php con esquemas completos NO se ejecutan
- Módulos Clientes y Facturas sin tablas

**Solución recomendada**: ✅ Migrar completamente a sistema install.php
- Crear install.php faltantes (Clientes, Facturas)
- Implementar `maybe_create_tables()` en todos los módulos
- Modificar Database Setup para ejecutar todos los install.php
- Crear migración para actualizar tablas existentes

**Tiempo estimado total**: 4-6 horas

**Beneficio**: Desbloquea 4 módulos críticos + funciones avanzadas en módulos existentes

**Prioridad**: 🔥🔥🔥 ALTA - Bloquea funcionalidad core de gestión

---

**Generado**: 2026-03-23
**Por**: Claude Code (Análisis automatizado)
**Próximo paso**: Implementar Opción 1 - Sistema de install.php unificado


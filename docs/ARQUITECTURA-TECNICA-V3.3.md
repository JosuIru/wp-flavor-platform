# Arquitectura Técnica v3.3

## Resumen de Cambios

La versión 3.3 introduce mejoras significativas en la arquitectura técnica del plugin:

1. **Bootstrap Modular** - Código principal dividido en clases especializadas
2. **Sistema de Migrations** - Gestión versionada de base de datos
3. **CSS Consolidado** - Build system con PostCSS

---

## 1. Bootstrap Modular

### Estructura

```
includes/bootstrap/
├── class-bootstrap-dependencies.php   # Carga de dependencias
├── class-starter-theme-manager.php    # Gestión del tema companion
├── class-database-setup.php           # Instalación de BD
├── class-cron-manager.php             # Tareas programadas
└── class-system-initializer.php       # Inicialización de singletons
```

### Archivo Principal

`flavor-chat-ia.php` ahora delega a las clases de bootstrap:

```php
class Flavor_Chat_IA {
    private $theme_manager;
    private $cron_manager;
    private $db_setup;
    private $system_initializer;

    private function __construct() {
        $this->theme_manager = Flavor_Starter_Theme_Manager::get_instance();
        $this->cron_manager = Flavor_Cron_Manager::get_instance();
        $this->db_setup = Flavor_Database_Setup::get_instance();
        $this->system_initializer = Flavor_System_Initializer::get_instance();
        // ...
    }
}
```

### Beneficios

- Archivo principal reducido de ~2,200 a ~720 líneas
- Mejor separación de responsabilidades
- Más fácil de mantener y testear

---

## 2. Sistema de Migrations

### Estructura

```
includes/database/
├── class-migration-base.php        # Clase abstracta base
├── class-migration-runner.php      # Motor de ejecución
└── migrations/
    ├── 2024_01_01_000001_create_chat_tables.php
    ├── 2024_01_01_000002_create_eventos_tables.php
    └── 2024_01_01_000003_create_reservas_tables.php

includes/cli/
└── class-migration-command.php     # Comandos WP-CLI
```

### Crear una Migration

```php
class Migration_2024_03_09_Create_Example_Table extends Flavor_Migration_Base {

    protected $migration_name = 'create_example_table';
    protected $description = 'Crear tabla de ejemplo';

    public function up() {
        $columns = [
            $this->column_id(),
            'nombre varchar(255) NOT NULL',
            $this->column_user_id(true),
            $this->column_status(['active', 'inactive'], 'active'),
            $this->column_created_at(),
        ];

        $keys = [
            $this->key_primary(),
            $this->key_index('user_id'),
        ];

        return $this->create_table('example', $columns, $keys);
    }

    public function down() {
        return $this->drop_table('example');
    }
}
```

### Comandos WP-CLI

```bash
# Ver estado de migrations
wp flavor migrate:status

# Ejecutar migrations pendientes
wp flavor migrate

# Crear nueva migration
wp flavor migrate:make create_notifications_table --table=notifications

# Revertir última batch
wp flavor migrate:rollback

# Revertir todas
wp flavor migrate:reset

# Reset + ejecutar todo
wp flavor migrate:refresh
```

### Helpers Disponibles

**Columnas:**
- `column_id()` - ID autoincremental
- `column_user_id($nullable)` - FK a users
- `column_status($values, $default)` - Enum de estado
- `column_created_at()` - Timestamp creación
- `column_updated_at()` - Timestamp actualización

**Keys:**
- `key_primary($column)` - Primary key
- `key_index($columns, $name)` - Índice simple
- `key_unique($columns, $name)` - Índice único

**Operaciones:**
- `create_table($name, $columns, $keys)`
- `drop_table($name)`
- `add_column($table, $column, $definition)`
- `drop_column($table, $column)`
- `add_index($table, $name, $columns)`
- `drop_index($table, $name)`
- `table_exists($name)`
- `column_exists($table, $column)`

---

## 3. Sistema CSS

### Estructura

```
assets/css/
├── core/              # Variables, reset (futuro)
├── components/        # Componentes UI (futuro)
├── layouts/           # Estructuras de página (futuro)
├── modules/           # Estilos de módulos (futuro)
├── dist/              # CSS compilado
│   ├── flavor-core.bundle.css
│   └── flavor-core.min.css
├── flavor-core.css    # Punto de entrada
└── *.css              # Archivos individuales
```

### Build System

**Configuración:** `postcss.config.js`

```javascript
module.exports = (ctx) => ({
    plugins: {
        'postcss-import': {},
        'autoprefixer': {},
        ...(ctx.env === 'production' ? { 'cssnano': {} } : {})
    }
});
```

**Comandos:**

```bash
# Compilar todo (bundle + minify)
npm run css:build

# Solo bundle (desarrollo)
npm run css:bundle

# Watch mode
npm run css:watch
```

### Uso en WordPress

```php
// Bundle completo (producción)
wp_enqueue_style(
    'flavor-core',
    FLAVOR_CHAT_IA_URL . 'assets/css/dist/flavor-core.min.css',
    [],
    FLAVOR_CHAT_IA_VERSION
);
```

---

## 4. Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    flavor-chat-ia.php                        │
│                    (Punto de entrada)                        │
└─────────────────────────┬───────────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          │               │               │
          ▼               ▼               ▼
┌─────────────────┐ ┌───────────┐ ┌─────────────────┐
│   Bootstrap     │ │  Database │ │      CLI        │
│  Dependencies   │ │   Setup   │ │   Commands      │
└─────────────────┘ └─────┬─────┘ └─────────────────┘
                          │
                          ▼
                 ┌─────────────────┐
                 │    Migration    │
                 │     Runner      │
                 └─────────────────┘
                          │
          ┌───────────────┼───────────────┐
          ▼               ▼               ▼
    ┌──────────┐   ┌──────────┐   ┌──────────┐
    │Migration │   │Migration │   │Migration │
    │  Chat    │   │ Eventos  │   │ Reservas │
    └──────────┘   └──────────┘   └──────────┘
```

---

## 5. Convenciones

### Nombres de Archivos

- **Migrations:** `YYYY_MM_DD_HHMMSS_description.php`
- **Clases:** `class-{nombre}.php`
- **CSS:** Nombres descriptivos en kebab-case

### Nombres de Clases

- **Migrations:** `Migration_YYYY_MM_DD_HHMMSS_Description`
- **Singletons:** `Flavor_{Nombre}` con `get_instance()`

### Tablas de BD

- Prefijo: `{wp_prefix}flavor_`
- Nombres en snake_case plural: `flavor_eventos`, `flavor_reservas`

---

## 6. Testing

### Verificar Migrations

```bash
# Ver estado
wp flavor migrate:status

# Ejecutar en sitio de prueba
wp flavor migrate

# Verificar tablas creadas
wp db query "SHOW TABLES LIKE 'wp_flavor_%'"
```

### Verificar CSS Build

```bash
# Instalar dependencias
npm install

# Compilar
npm run css:build

# Verificar archivo generado
ls -la assets/css/dist/
```

---

## 7. Roadmap

- [ ] Mover archivos CSS existentes a subdirectorios
- [ ] Crear más migrations para módulos restantes
- [ ] Añadir tests unitarios para migrations
- [ ] Documentar API de cada módulo

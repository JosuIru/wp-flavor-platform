# Modulo: Trabajo Digno

> Bolsa de empleo etico, emprendimiento local y formacion profesional

## Descripcion

Plataforma integral de empleo que promueve condiciones laborales justas basadas en criterios de la OIT (Organizacion Internacional del Trabajo) y principios de economia solidaria. Conecta ofertas de trabajo con candidatos priorizando la dignidad laboral, el cooperativismo y el desarrollo profesional local.

## Archivos Principales

```
includes/modules/trabajo-digno/
├── class-trabajo-digno-module.php          # Clase principal del modulo
├── class-trabajo-digno-dashboard-tab.php   # Tabs del dashboard de usuario
├── class-trabajo-digno-widget.php          # Widget de dashboard
├── frontend/
│   └── class-trabajo-digno-frontend-controller.php
├── templates/
│   ├── ofertas.php
│   ├── formacion.php
│   ├── emprendimientos.php
│   ├── mi-perfil.php
│   └── publicar.php
├── views/
│   └── dashboard.php                       # Dashboard administrativo
└── assets/
    ├── css/trabajo-digno.css
    └── js/trabajo-digno.js
```

## Tablas de Base de Datos

### wp_flavor_trabajo_ofertas
Ofertas de empleo publicadas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| titulo | varchar(255) | Titulo del puesto |
| descripcion | text | Descripcion del trabajo |
| requisitos | text | Requisitos del puesto |
| beneficios | text | Beneficios ofrecidos |
| condiciones_dignas | text | Compromiso trabajo digno |
| empresa | varchar(255) | Nombre empresa/organizacion |
| empresa_descripcion | text | Descripcion de la empresa |
| empresa_web | varchar(255) | Web de la empresa |
| ubicacion | varchar(255) | Ubicacion del trabajo |
| salario | varchar(100) | Rango salarial |
| salario_min | decimal(10,2) | Salario minimo |
| salario_max | decimal(10,2) | Salario maximo |
| categoria | varchar(100) | Categoria laboral |
| tipo_contrato | varchar(50) | Tipo de contrato |
| jornada | varchar(50) | Tipo de jornada |
| es_cooperativa | tinyint(1) | Es cooperativa/ESS |
| verificada | tinyint(1) | Empresa verificada |
| usuario_id | bigint(20) | FK autor |
| estado | enum | borrador/pendiente/publicada/activa/cerrada/cubierta |
| fecha_publicacion | datetime | Fecha publicacion |
| fecha_limite | datetime | Fecha limite candidaturas |
| fecha_creacion | datetime | Fecha creacion |
| fecha_actualizacion | datetime | Ultima actualizacion |

**Indices:** estado, categoria, tipo_contrato, usuario_id, fecha_publicacion

### wp_flavor_trabajo_candidaturas
Candidaturas enviadas a ofertas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| oferta_id | bigint(20) | FK oferta |
| usuario_id | bigint(20) | FK candidato |
| mensaje | text | Mensaje de presentacion |
| cv_url | varchar(500) | URL del CV |
| estado | enum | enviada/en_proceso/seleccionado/descartado |
| notas_empresa | text | Notas internas |
| fecha | datetime | Fecha candidatura |
| fecha_creacion | datetime | Fecha creacion |

**Indices:** oferta_id, usuario_id, estado
**Unique:** oferta_id + usuario_id

### wp_flavor_trabajo_empresas
Empresas/organizaciones registradas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(255) | Nombre |
| descripcion | text | Descripcion |
| sector | varchar(100) | Sector actividad |
| tipo_organizacion | varchar(100) | Tipo (cooperativa, S.L., etc) |
| logo | varchar(500) | Logo |
| web | varchar(255) | Sitio web |
| email | varchar(100) | Email contacto |
| telefono | varchar(20) | Telefono |
| direccion | text | Direccion |
| verificada | tinyint(1) | Verificada |
| usuario_id | bigint(20) | FK administrador |
| estado | enum | pendiente/activa/inactiva |
| created_at | datetime | Fecha creacion |

### wp_flavor_cooperativas
Cooperativas y empresas de economia social.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(255) | Nombre cooperativa |
| descripcion | text | Descripcion |
| sector | varchar(100) | Sector |
| empleos | int | Numero empleos |
| logo | varchar(500) | Logo |
| web | varchar(255) | Web |
| estado | enum | pendiente/activa/inactiva |
| created_at | datetime | Fecha creacion |

## Custom Post Types

### td_oferta
Ofertas de trabajo (alternativo a tabla).

| Metadato | Descripcion |
|----------|-------------|
| _td_tipo | Tipo: empleo/cooperativa/autonomo/practicas/voluntariado |
| _td_jornada | Jornada: completa/parcial/flexible/remoto/hibrido/temporal |
| _td_ubicacion | Ubicacion del puesto |
| _td_salario | Informacion salarial |
| _td_criterios_dignidad | Array criterios cumplidos |
| _td_postulaciones | Array de postulaciones |

### td_perfil
Perfiles profesionales de candidatos.

| Metadato | Descripcion |
|----------|-------------|
| _td_experiencia | Experiencia laboral |
| _td_formacion | Formacion academica |
| _td_disponibilidad | Disponibilidad horaria |

### td_formacion
Cursos y talleres de formacion.

| Metadato | Descripcion |
|----------|-------------|
| _td_plazas | Numero plazas |
| _td_inscritos | Array usuarios inscritos |

### td_emprendimiento
Emprendimientos locales.

| Metadato | Descripcion |
|----------|-------------|
| _td_tipo_organizacion | Tipo de organizacion |
| _td_web | Sitio web |
| _td_contacto | Email contacto |

## Taxonomias

### td_sector
Sectores de actividad economica.

Sectores predefinidos:
- Agroecologia
- Artesania
- Comercio Justo
- Construccion Sostenible
- Cuidados
- Educacion
- Energias Renovables
- Hosteleria
- Tecnologia
- Cultura y Arte
- Salud
- Transporte Sostenible
- Reciclaje/Economia Circular
- Servicios Locales

### td_habilidad
Habilidades y competencias profesionales.

## Criterios de Trabajo Digno

El modulo evalua ofertas segun 8 criterios basados en la OIT:

| Criterio | Descripcion |
|----------|-------------|
| salario_justo | Remuneracion suficiente para vida digna |
| seguridad_social | Cobertura de proteccion social |
| conciliacion | Equilibrio vida laboral y personal |
| igualdad | Sin discriminacion de ningun tipo |
| formacion | Desarrollo profesional permanente |
| participacion | Voz en decisiones de la organizacion |
| sostenibilidad | Impacto ambiental responsable |
| impacto_local | Contribucion a la comunidad |

El **Indice de Dignidad** se calcula como porcentaje de criterios cumplidos.

## Shortcodes

### Listados y Busqueda

```php
[trabajo_digno_ofertas]
// Catalogo de ofertas de empleo
// - tipo: empleo|cooperativa|autonomo|practicas|voluntariado
// - sector: slug del sector
// - jornada: completa|parcial|flexible|remoto
// - limite: numero

[flavor_trabajo_ofertas]
// Bolsa de trabajo con filtros
// - categoria: slug categoria
// - tipo: tipo contrato
// - limite: numero

[flavor_trabajo_buscar]
// Buscador avanzado
```

### Detalle y Publicacion

```php
[trabajo_digno_publicar]
// Formulario publicar oferta

[flavor_trabajo_oferta id="123"]
// Detalle de oferta
// - id: ID oferta (o auto desde URL)

[flavor_trabajo_publicar]
// Formulario publicacion
```

### Gestion Personal

```php
[trabajo_digno_mi_perfil]
// Perfil profesional del usuario

[flavor_trabajo_mis_ofertas]
// Ofertas publicadas por el usuario

[flavor_trabajo_mis_candidaturas]
// Candidaturas enviadas
```

### Contenidos Complementarios

```php
[trabajo_digno_formacion]
// Catalogo de formacion disponible

[trabajo_digno_emprendimientos]
// Directorio de emprendimientos locales

[flavor_trabajo_cooperativas]
// Listado de cooperativas y ESS

[flavor_trabajo_formacion]
// Cursos para el empleo

[flavor_trabajo_estadisticas]
// Impacto de la bolsa de trabajo
```

## Dashboard Tab

**Clase:** `Flavor_Trabajo_Digno_Dashboard_Tab`

**Tabs disponibles:**
- `trabajo-digno-resumen` - Panel resumen con KPIs
- `trabajo-digno-ofertas` - Bolsa de empleo

**Clase:** `Flavor_Trabajo_Digno_Frontend_Controller`

**Tabs registrados:**
- `trabajo-digno` - Dashboard completo

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/trabajo-digno/` | index | Bolsa de empleo |
| `/trabajo-digno/ofertas/` | ofertas | Listado ofertas |
| `/trabajo-digno/formacion/` | formacion | Catalogo formacion |
| `/trabajo-digno/emprendimientos/` | emprendimientos | Directorio |
| `/trabajo-digno/publicar/` | publicar | Nueva oferta |
| `/trabajo-digno/oferta/{id}/` | ver | Detalle oferta |
| `/mi-portal/trabajo-digno/` | mi-perfil | Perfil profesional |
| `/mi-portal/trabajo-digno/ofertas/` | mis-ofertas | Mis publicaciones |
| `/mi-portal/trabajo-digno/candidaturas/` | mis-candidaturas | Mis candidaturas |
| `/mi-portal/trabajo-digno/cooperativas/` | cooperativas | Cooperativas |

## REST API

### Endpoints Publicos

```
GET /wp-json/flavor/v1/trabajo-digno/ofertas
    ?tipo=empleo|cooperativa|autonomo
    ?sector=slug
    ?per_page=20
    ?page=1
Listar ofertas activas

GET /wp-json/flavor/v1/trabajo-digno/ofertas/{id}
Obtener detalle de oferta

GET /wp-json/flavor/v1/trabajo-digno/formacion
    ?per_page=20
Listar formaciones disponibles

GET /wp-json/flavor/v1/trabajo-digno/emprendimientos
    ?per_page=20
Listar emprendimientos locales
```

### Endpoints Autenticados

```
GET /wp-json/flavor/v1/trabajo-digno/mi-perfil
Obtener perfil profesional del usuario

GET /wp-json/flavor/v1/trabajo-digno/mis-postulaciones
Listar postulaciones del usuario
```

## AJAX Handlers

| Accion | Descripcion |
|--------|-------------|
| `td_publicar_oferta` | Publicar nueva oferta |
| `td_postular` | Enviar candidatura |
| `td_guardar_perfil` | Guardar perfil profesional |
| `td_registrar_emprendimiento` | Registrar emprendimiento |
| `td_inscribir_formacion` | Inscribirse en formacion |
| `flavor_trabajo_publicar` | Publicar oferta (alt) |
| `flavor_trabajo_candidatura` | Enviar candidatura (alt) |
| `flavor_trabajo_guardar` | Guardar oferta favorita |
| `flavor_trabajo_actualizar_estado` | Cambiar estado oferta |
| `flavor_trabajo_buscar` | Buscar ofertas (publico) |

## Tipos de Oferta

| Tipo | Descripcion | Color |
|------|-------------|-------|
| empleo | Empleo tradicional | #3b82f6 |
| cooperativa | Puesto en cooperativa | #22c55e |
| autonomo | Autonomo/Freelance | #f59e0b |
| practicas | Practicas profesionales | #8b5cf6 |
| voluntariado | Voluntariado | #ec4899 |

## Tipos de Jornada

| Jornada | Descripcion |
|---------|-------------|
| completa | Jornada completa |
| parcial | Media jornada |
| flexible | Horario flexible |
| remoto | Trabajo remoto |
| hibrido | Presencial + remoto |
| temporal | Temporal/Por proyecto |

## Vinculaciones

| Modulo | Integracion |
|--------|-------------|
| comunidades | Ofertas por comunidad |
| socios | Ofertas exclusivas socios |
| cursos | Formacion vinculada |
| foros | Foro por oferta |
| chat-grupos | Chat colaborativo |
| multimedia | Recursos por oferta |
| red-social | Feed de actividad |

## Configuracion

```php
'trabajo_digno' => [
    'enabled' => true,
    'tipos_permitidos' => ['empleo', 'cooperativa', 'autonomo', 'practicas', 'voluntariado'],
    'requiere_aprobacion' => true,
    'mostrar_salario' => true,
    'verificar_empresas' => true,
    'criterios_minimos' => 3,           // Criterios dignidad minimos
    'notificar_postulaciones' => true,
    'permitir_cv' => true,
    'mostrar_en_dashboard' => true,
    'integraciones' => [
        'foros' => true,
        'chat' => true,
        'multimedia' => true,
        'red_social' => true,
    ],
]
```

## Permisos

| Capability | Descripcion |
|------------|-------------|
| `td_publicar_oferta` | Publicar ofertas |
| `td_editar_oferta` | Editar ofertas propias |
| `td_gestionar_ofertas` | Gestionar todas las ofertas |
| `td_postular` | Enviar candidaturas |
| `td_ver_candidaturas` | Ver candidaturas recibidas |
| `td_verificar_empresa` | Verificar empresas |
| `td_gestionar_cooperativas` | Gestionar cooperativas |

## Valoracion de Conciencia

Puntuacion total: **85/100**

| Premisa | Puntuacion | Descripcion |
|---------|------------|-------------|
| Conciencia Fundamental | 18 | Reconoce la dignidad inherente del trabajador |
| Abundancia Organizable | 17 | Organiza oportunidades como recurso comunitario |
| Interdependencia Radical | 17 | Conecta empleadores y trabajadores en corresponsabilidad |
| Madurez Ciclica | 16 | Respeta ritmos de vida y conciliacion |
| Valor Intrinseco | 17 | Valora trabajo por aporte social |

**Fortalezas:**
- Criterios explicitos de trabajo digno basados en OIT
- Promocion de economia cooperativa y solidaria
- Conexion empleo-formacion-emprendimiento

**Areas de mejora:**
- Incorporar metricas de impacto social del empleo
- Desarrollar sistema de verificacion de condiciones laborales

## Panel Administracion

### Dashboard Admin

Vista general con KPIs:
- Ofertas activas
- Total candidaturas
- Candidatos unicos
- Empresas registradas
- Candidaturas pendientes de revision
- Grafico actividad semanal
- Ofertas por tipo de contrato
- Ofertas mas solicitadas
- Ofertas recientes

### Subpaginas Admin

| Pagina | Descripcion |
|--------|-------------|
| Trabajo Digno | Dashboard principal |
| Ofertas | Gestion de ofertas |
| Formacion | Cursos y talleres |
| Emprendimientos | Directorio local |

## Renderer Config

El modulo expone configuracion para el Module Renderer:

```php
[
    'module' => 'trabajo-digno',
    'title' => 'Trabajo Digno',
    'icon' => 'dashicons-businessman',
    'color' => 'accent',

    'stats' => [
        'ofertas_activas',
        'empresas',
        'emprendimientos',
        'contrataciones',
    ],

    'tabs' => [
        'ofertas',
        'emprendimientos',
        'publicar',
        'mis-postulaciones',
        'mi-cv',
        'foro',
        'chat',
        'multimedia',
        'red-social',
    ],

    'features' => [
        'indice_dignidad' => true,
        'postulaciones' => true,
        'cv_online' => true,
        'emprendimientos' => true,
        'alertas' => true,
    ],
]
```

## FAQs del Modulo

**Que es el indice de dignidad?**
Indicador que muestra cuantos criterios de trabajo digno cumple una oferta (salario justo, conciliacion, igualdad, etc.).

**Como publico una oferta de empleo?**
Ve a Trabajo Digno > Publicar Oferta. Completa el formulario incluyendo los criterios de dignidad que ofreces.

**Como postulo a una oferta?**
Desde la ficha de la oferta, haz clic en "Postular". Puedes adjuntar un mensaje de presentacion.

**Puedo registrar mi emprendimiento?**
Si, en la seccion de Emprendimientos puedes registrar tu proyecto o empresa local.

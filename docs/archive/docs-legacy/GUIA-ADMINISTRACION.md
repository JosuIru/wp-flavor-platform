# Guia de Administracion

## Objetivo

Esta guia resume donde esta cada cosa dentro del panel del plugin y como recorrerlo sin perder tiempo.

## Pantallas administrativas principales

La instalacion incluye varias clases de administracion bajo `admin/` e `includes/admin/`. El menu exacto visible depende de permisos y configuracion, pero funcionalmente hay estas zonas:

| Zona | Uso principal |
|---|---|
| Dashboard principal | Resumen del estado del sistema y accesos rapidos |
| Dashboard unificado | Vista agregada por ecosistemas, contextos y modulos |
| Modulos | Activacion, visibilidad, capacidad y acceso a documentacion por modulo |
| Paginas | Gestion de paginas generadas o administradas por el plugin |
| Diseno | Tokens, layouts, landing editor y ajustes visuales |
| Permisos | Roles, capabilities y asignaciones por modulo |
| Addons | Gestion de extensiones fuera del core |
| Setup Wizard | Arranque inicial guiado |
| Tours | Ayuda contextual para onboarding |
| Export / Import | Traslado de configuracion o datos soportados |
| Analytics | Metricas y paneles internos |
| Health Check | Diagnostico tecnico y enlaces de soporte |
| API Docs | Referencia REST integrada |
| Documentacion | Documentacion funcional y tecnica del plugin |

## Flujo recomendado para una instalacion nueva

### Paso 1

Entrar en el asistente o revisar la configuracion base del plugin.

### Paso 2

Definir el perfil funcional o, si no existe uno adecuado, activar solo los modulos necesarios.

### Paso 3

Revisar paginas, rutas y dashboards generados.

### Paso 4

Validar permisos y roles antes de abrir el sistema a usuarios finales.

### Paso 5

Comprobar, modulo por modulo, que:

- se activa sin errores
- tiene paginas o tabs accesibles
- carga sus assets
- respeta permisos

## Como leer la pantalla de modulos

La vista unificada de modulos es especialmente importante porque concentra:

- estado de activacion
- contexto
- visibilidad
- documentacion resumida por modulo

Es la mejor puerta de entrada cuando no sabes si una capacidad existe ya o si un modulo esta listo para usarse.

## Buenas practicas de administracion

- No actives decenas de modulos a la vez sin validacion intermedia.
- Configura primero un flujo principal de negocio.
- Revisa permisos antes de dar acceso a gestores o coordinadores.
- Usa la documentacion integrada como referencia diaria.
- Trata los addons como extensiones separadas, no como parte obligatoria del core.

## Senales de alerta

Conviene hacer una pausa si observas:

- diferencias entre lo que promete un documento antiguo y lo que ves en el menu actual
- modulos con pantallas parciales o sin dashboard claro
- errores de nonce, permisos o assets en runtime
- necesidad de crear demasiadas excepciones manuales para un mismo flujo

## Documentos que conviene tener a mano

- `GUIA_MODULOS.md`
- `FUNCIONALIDADES-COMPARTIDAS.md`
- `PERMISSIONS-USAGE.md`
- `ESTADO-REAL-PLUGIN.md`

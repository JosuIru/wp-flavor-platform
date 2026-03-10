# Guia de Inicio Rapido

## Objetivo

Poner una instalacion en un estado util sin asumir que todos los modulos o pantallas deben activarse desde el primer dia.

## Recorrido recomendado

### 1. Confirmar el alcance

Antes de tocar menus, decide que necesita realmente el proyecto:

- comunidad y socios
- marketplace o grupos de consumo
- reservas o tramites
- contenidos y difusion
- panel interno para gestores

### 2. Entrar al panel del plugin

Revisa primero estas zonas:

- dashboard
- modulos
- paginas
- permisos
- documentacion

### 3. Activar un conjunto pequeno de modulos

Empieza por el flujo principal. Ejemplos:

- `socios`, `eventos`, `comunidades`
- `grupos_consumo`, `marketplace`, `reservas`
- `tramites`, `transparencia`, `documentacion_legal`

### 4. Revisar paginas y dashboards

Tras activar modulos, valida:

- que existan rutas o shortcodes usables
- que el dashboard o tab del modulo aparezca
- que no haya errores visibles de permisos o carga

### 5. Ajustar permisos

No des por hecho que el rol WordPress basico cubre el flujo. Revisa capabilities y roles funcionales del plugin.

### 6. Validar con usuarios y contexto real

Haz una prueba minima con:

- un administrador
- un gestor intermedio
- un usuario final

## Que no conviene hacer al empezar

- activar casi todo el inventario de modulos
- confiar en documentos antiguos sin contraste
- asumir que todos los modulos tienen la misma madurez
- prometer integraciones no validadas en la instalacion actual

## Checklist minimo

- El plugin esta activo y accesible en admin.
- Los modulos elegidos se cargan sin error visible.
- Las paginas o dashboards clave existen.
- Los permisos estan revisados.
- La documentacion integrada ya apunta a estos documentos canonicos.

## Siguiente lectura recomendada

- `GUIA-ADMINISTRACION.md`
- `GUIA_MODULOS.md`
- `ESTADO-REAL-PLUGIN.md`

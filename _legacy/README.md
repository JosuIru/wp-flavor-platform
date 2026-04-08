# Archivos Legacy

Esta carpeta contiene scripts y herramientas de diagnóstico que fueron movidos fuera del directorio principal por las siguientes razones:

## /diagnosticos/
Scripts de diagnóstico que no son cargados por el plugin pero estaban en la raíz:
- `diagnostico-completo.php` - Diagnóstico general del sistema
- `diagnostico-completo-modulos.php` - Diagnóstico de módulos
- `diagnostico-tabs-modulos.php` - Diagnóstico con interfaz de tabs
- `diagnostico-modulos.php` - Diagnóstico de módulos v2
- `diagnostico-ia.php` - Diagnóstico de funcionalidades IA
- `diagnostico-pp.php` - Diagnóstico de Presupuestos Participativos
- `diagnostico-performance.php` - Diagnóstico de rendimiento

**Uso**: Si necesitas ejecutar alguno, cópialo temporalmente a la raíz del plugin.

## /scripts-cliente/
Scripts específicos de cliente o de mantenimiento puntual:
- `setup-herri-antifaxistak.php` - Setup para cliente específico
- `setup-pp-demo.php` - Setup de datos demo
- `activar-reputacion.php` - Script de activación de reputación
- `actualizar-comunidades-crosspost.php` - Script de mantenimiento
- `check-renderer-config.php` - Validación de configuración
- `crear-datos-marketplace.php` - Generador de datos demo

**IMPORTANTE**: Estos scripts NO deben incluirse en distribuciones de producción.

---
Movidos el: 2026-04-08
Motivo: Limpieza de código muerto y organización del repositorio

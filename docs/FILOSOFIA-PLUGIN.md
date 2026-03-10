# Filosofia del Plugin

## Que es Flavor Platform

`flavor-chat-ia` es un plugin WordPress de arquitectura modular pensado para construir portales, paneles y experiencias de gestion alrededor de comunidades, organizaciones, redes locales y verticales sectoriales.

No es un unico producto cerrado. Es una plataforma compuesta por:

- un nucleo comun
- un sistema de modulos activables
- paneles administrativos y de usuario
- utilidades compartidas
- addons especializados

## Problema que intenta resolver

Muchos proyectos necesitan mezclar varias capas a la vez:

- gestion interna
- portal publico
- comunidad o socios
- tramites o reservas
- contenido y difusion
- integraciones entre servicios

En WordPress esto suele acabar repartido entre muchos plugins sin un lenguaje comun. Flavor intenta concentrar esas piezas bajo una misma estructura.

## Principios de diseno

### Modularidad antes que carga total

La idea central es activar solo lo necesario para cada instalacion. El plugin incluye un inventario amplio de modulos, pero la intencion no es usar todo a la vez.

### El modulo es una capacidad de negocio

Un modulo no es solo una pantalla. Normalmente concentra:

- modelo de datos
- logica de negocio
- vistas o templates
- endpoints o AJAX
- integracion con dashboards
- permisos

### El ecosistema importa mas que la pantalla aislada

El valor del plugin esta en conectar capacidades. Ejemplos:

- un modulo consume contenido de otro
- un dashboard agrupa modulos relacionados
- permisos y widgets se reparten por contexto

### WordPress como base, no como limite

El plugin se apoya en:

- roles y capabilities
- CPTs y taxonomias
- admin pages
- REST API
- shortcodes

Pero añade capas propias para resolver casos mas complejos:

- cargador de modulos
- integraciones provider/consumer
- dashboards unificados
- documentacion y herramientas internas

### Accesibilidad operativa

La documentacion y la arquitectura deben permitir que una persona nueva entienda:

- que activar
- donde configurar
- que puede romperse
- como validar el estado real

No basta con tener mucho codigo. Hace falta que el sistema sea legible.

## Que no es este plugin

No debe entenderse como:

- un producto monolitico simple de instalar y olvidar
- una garantia de que todos los modulos tienen la misma madurez
- una base de codigo completamente homogenea
- un sustituto automatico de analisis funcional o QA

## Criterio practico de uso

La filosofia correcta para trabajar con el plugin es:

- elegir un caso de uso principal
- activar pocos modulos bien conectados
- validar permisos y paginas
- revisar el estado real antes de prometer alcance
- tratar los modulos experimentales como tales

## Regla de mantenimiento

Cuando haya conflicto entre documentacion y codigo, el codigo actual manda. Cuando haya conflicto entre documentos viejos y auditoria vigente, prevalece la auditoria.

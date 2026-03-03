# Auditoría de Arquitectura entre Módulos

Fecha: 2026-03-03

## Objetivo

Evaluar si el desarrollo actual de los módulos y de los módulos que “cuelgan” de otros está bien resuelto a nivel arquitectónico.

La pregunta no es solo si cada módulo funciona, sino si la relación entre:

- módulos base
- módulos verticales
- módulos transversales

está bien separada, es consistente y se puede escalar sin volver el sistema inmantenible.

## Criterio de evaluación

Se usa esta tipología:

### 1. Módulo base

Aporta contexto estructural o identidad compartida.

Ejemplos:

- comunidad
- socio
- nodo
- colectivo

No debería cargar demasiada lógica operativa específica.

### 2. Módulo vertical

Resuelve una operación concreta del dominio.

Ejemplos:

- grupos de consumo
- energía comunitaria
- banco de tiempo
- reservas

Debe poder apoyarse en un módulo base sin ser absorbido por él.

### 3. Módulo transversal

Aporta métricas, cultura, impacto, conocimiento o gobernanza a varios verticales.

Ejemplos:

- huella ecológica
- economía de suficiencia
- saberes ancestrales
- transparencia

## Veredicto general

La arquitectura de módulos de Flavor está `bien encaminada` y, en varios casos, `bien resuelta`.

El problema principal no es la calidad de los módulos individuales, sino la ausencia de una `arquitectura superior explícita` que nombre y gobierne las relaciones entre ellos.

En corto:

- los módulos importantes están razonablemente bien separados
- varios módulos ya usan bien integraciones consumer/provider
- los módulos base no siempre están formalizados como base
- faltan contratos de dependencia y extensión más claros

## Mapa actual de módulos por rol

## A. Módulos base

### `comunidades`

Archivo: `includes/modules/comunidades/class-comunidades-module.php`

Rol actual:

- contenedor social
- membresía
- actividad
- anuncios
- recursos
- coordinación

Juicio:

- `bien resuelto` como base social
- no conviene meter dentro toda la lógica vertical
- es correcto usarlo como soporte para energía, eventos, participación, ayuda o recursos compartidos

Estado arquitectónico:

- bueno

### `socios`

Rol actual:

- identidad organizativa
- membresías
- cuotas
- control de pertenencia

Juicio:

- buen base para organizaciones formales
- útil para cooperativas, asociaciones y estructuras con membresía explícita

Estado arquitectónico:

- bueno

### `colectivos`

Rol actual:

- agrupación organizativa
- capa intermedia entre comunidad y estructura formal

Juicio:

- razonable como base secundaria
- hoy convive con `comunidades` y `socios`; hace falta delimitar mejor cuándo usar cada uno

Estado arquitectónico:

- correcto, pero con riesgo de solapamiento semántico

## B. Módulos verticales

### `grupos_consumo`

Archivo: `includes/modules/grupos-consumo/class-grupos-consumo-module.php`

Rol:

- pedidos colectivos
- productores
- ciclos
- pagos
- excedentes
- transparencia

Juicio:

- `muy bien resuelto`
- tiene suficiente entidad propia
- cuelga bien de comunidad/membresía sin diluir su dominio

Estado arquitectónico:

- muy bueno

### `banco_tiempo`

Archivo: `includes/modules/banco-tiempo/class-banco-tiempo-module.php`

Rol:

- servicios
- intercambios
- reputación
- horas
- fondo solidario

Juicio:

- `muy buen vertical`
- su propia base de valor está clara
- se apoya bien en usuarios/comunidad, sin confundir su lógica con el módulo base

Estado arquitectónico:

- muy bueno

### `energia_comunitaria`

Archivo: `includes/modules/energia-comunitaria/class-energia-comunitaria-module.php`

Rol:

- instalaciones
- lecturas
- participantes
- reparto
- cierres
- liquidaciones

Juicio:

- `bien planteado`
- la decisión correcta fue no incrustarlo dentro de `comunidades`
- usar `comunidad_id` como vínculo es la solución arquitectónicamente sana

Estado arquitectónico:

- bueno

### `reservas`, `espacios_comunes`, `incidencias`, `tramites`

Juicio conjunto:

- buenos verticales operativos
- bastante independientes
- encajan bien con módulos base por contexto organizativo o territorial

Estado arquitectónico:

- bueno

## C. Módulos transversales

### `huella_ecologica`

Archivo: `includes/modules/huella-ecologica/class-huella-ecologica-module.php`

Rol:

- medir impacto
- registrar acciones
- proyectos de compensación
- métricas comunitarias

Juicio:

- `muy buen módulo transversal`
- debería conectarse aún más explícitamente con grupos de consumo, energía, movilidad y residuos

Estado arquitectónico:

- bueno, con margen de integración adicional

### `economia_suficiencia`

Archivo: `includes/modules/economia-suficiencia/class-economia-suficiencia-module.php`

Rol:

- compromisos
- prácticas
- biblioteca
- itinerario personal

Juicio:

- transversal cultural muy valioso
- no debe competir con los verticales operativos
- debe actuar como capa de cambio de hábitos y cultura

Estado arquitectónico:

- bueno

### `saberes_ancestrales`

Archivo: `includes/modules/saberes-ancestrales/class-saberes-ancestrales-module.php`

Rol:

- catálogo de saberes
- portadores/guardianes
- talleres
- transmisión

Juicio:

- bien definido como capa cultural y de memoria colectiva
- encaja bien con Gailu en la parte de cultura y conciencia

Estado arquitectónico:

- bueno

### `participacion`, `presupuestos_participativos`, `transparencia`

Juicio conjunto:

- forman una capa transversal de gobernanza
- buena separación respecto a los verticales económicos y operativos

Estado arquitectónico:

- bueno

## D. Relación “módulo base -> módulo que cuelga”

## Relaciones que hoy están bien resueltas

### `comunidades -> energia_comunitaria`

Patrón:

- `comunidades` aporta pertenencia y contexto
- `energia_comunitaria` aporta operación energética

Juicio:

- `correcto`
- este es uno de los mejores ejemplos del proyecto

### `comunidades -> eventos / ayuda_vecinal / foros / recursos`

Patrón:

- la comunidad actúa como marco común
- cada módulo mantiene su dominio

Juicio:

- correcto

### `socios -> cooperativa / cuotas / participación`

Patrón:

- membresía formal como base
- módulos de gestión cuelgan de identidad y rol

Juicio:

- correcto

## Relaciones que hoy están bien a nivel práctico, pero mal formalizadas

### `grupos_consumo <-> huella_ecologica`

Existe:

- métricas ecológicas propias
- afinidad de dominio evidente

Falta:

- un contrato explícito que diga qué datos exporta grupos de consumo a huella
- métricas comunes reutilizables

### `energia_comunitaria <-> huella_ecologica`

Existe:

- afinidad conceptual
- aceptación de integración

Falta:

- contrato fuerte de contribución de ahorro/co2
- tablero compartido

### `saberes_ancestrales <-> talleres / cursos / comunidades`

Existe:

- compatibilidad
- sentido funcional

Falta:

- un estándar de “transmisión de conocimiento” reutilizable entre módulos

## Solapamientos y riesgos

## 1. `comunidades`, `colectivos`, `socios`

Riesgo:

- las tres piezas pueden representar agrupación humana

Problema:

- si no se define su rol exacto, el sistema acaba con tres formas de modelar casi lo mismo

Regla recomendada:

- `comunidades` = tejido social y actividad
- `socios` = membresía formal y derechos/obligaciones
- `colectivos` = agrupación política/organizativa o asociativa de segundo nivel

## 2. `economia_suficiencia` frente a módulos operativos

Riesgo:

- que se le pida resolver operación económica real

Regla recomendada:

- `economia_suficiencia` no debe gestionar mercado, energía ni pagos
- debe medir, acompañar y transformar hábitos

## 3. `huella_ecologica` como módulo aislado

Riesgo:

- quedarse como calculadora bonita en vez de capa transversal de impacto

Regla recomendada:

- debe consumir datos de verticales y devolver indicadores compartidos al ecosistema

## Qué está bien y debe mantenerse

- separar módulos base y verticales
- no meter toda la lógica dentro de `comunidades`
- mantener verticales con tablas y reglas propias
- usar módulos transversales para impacto, cultura y gobernanza
- aprovechar integraciones dinámicas consumer/provider

## Qué falta formalizar

## 1. Tipo de relación entre módulos

Hoy una relación existe, pero no siempre se nombra.

Conviene formalizar:

- `base_for`
- `extends`
- `measures`
- `governs`
- `teaches`
- `federates`

Ejemplo:

- `comunidades base_for energia_comunitaria`
- `huella_ecologica measures energia_comunitaria`
- `saberes_ancestrales teaches comunidades`

## 2. Módulos raíz del ecosistema

Conviene declarar explícitamente qué módulos son estructurales:

- `comunidades`
- `socios`
- `red_de_nodos` o futura entidad `nodo`

## 3. Contratos de datos entre verticales y transversales

Conviene definir para cada integración:

- qué produce el módulo origen
- qué consume el módulo transversal
- qué KPIs comunes salen de ahí

## Recomendación técnica

La arquitectura actual permite crecer, pero necesita una capa de diseño formal.

La recomendación concreta es:

### 1. Declarar tres clases de módulo

- `base`
- `vertical`
- `transversal`

### 2. Añadir metadatos de relación

En registro o metadata del módulo:

- `module_role`
- `depends_on`
- `extends_modules`
- `measures_modules`
- `supports_modules`

### 3. Crear una matriz de arquitectura del ecosistema

Con forma:

| módulo | rol | base de | cuelga de | mide a | cultura para |
|--------|-----|----------|-----------|--------|--------------|

### 4. Preparar la futura entidad `nodo`

Porque es la pieza que mejor puede unificar:

- comunidades
- red federada
- perfiles
- métricas de transición

## Veredicto final

Sí: `el desarrollo de los módulos y de los módulos que cuelgan de ellos está globalmente bien`.

Pero el estado correcto de esa frase es:

- bien a nivel de implementación
- razonablemente bien a nivel de separación de dominios
- todavía insuficientemente formalizado a nivel de arquitectura global

No hace falta rehacer los módulos.

Hace falta:

- nombrar mejor los roles
- fijar relaciones explícitas
- evitar solapamientos semánticos
- elevar el nodo como pieza central del ecosistema

## Archivos revisados

- `includes/class-app-profiles.php`
- `admin/class-unified-modules-view.php`
- `docs/ARQUITECTURA-MODULOS.md`
- `docs/INTEGRACIONES.md`
- `includes/modules/comunidades/class-comunidades-module.php`
- `includes/modules/grupos-consumo/class-grupos-consumo-module.php`
- `includes/modules/banco-tiempo/class-banco-tiempo-module.php`
- `includes/modules/energia-comunitaria/class-energia-comunitaria-module.php`
- `includes/modules/huella-ecologica/class-huella-ecologica-module.php`
- `includes/modules/economia-suficiencia/class-economia-suficiencia-module.php`
- `includes/modules/saberes-ancestrales/class-saberes-ancestrales-module.php`

# Matriz de Compatibilidad por Contextos y Composiciones Modulares

**Fecha:** 2026-03-22
**Objetivo:** Modelar el plugin no como una lista plana de modulos ni como parejas fijas entre modulos, sino como un sistema de composiciones modulares dependientes del contexto.

---

## 1. Principio Base

Un mismo modulo puede vivir en contextos distintos y, segun el contexto, activar relaciones UX diferentes.

Ejemplo:

- `grupos-consumo` en una comunidad local
- `grupos-consumo` dentro de un colectivo
- `grupos-consumo` vinculado a un ayuntamiento
- `grupos-consumo` dentro de una red de nodos

Por tanto, las relaciones no deben modelarse solo como:

- `modulo A -> modulo B`

Sino como:

- `contexto -> modulo base -> modulos transversales y de soporte -> alcance`

---

## 2. Modelo de Composicion

Cada experiencia frontend deberia poder describirse con estas capas:

### 1. Contexto contenedor

Puede ser:

- comunidad
- colectivo
- ayuntamiento
- nodo/red
- empresa
- coworking
- barrio
- escuela o academia
- cooperativa

### 2. Modulo base

Es el modulo que organiza la accion principal:

- grupos-consumo
- ayuda-vecinal
- eventos
- participacion
- marketplace
- tramites
- cursos
- etc.

### 3. Modulos transversales activados

Son modulos reutilizables que pueden aparecer en muchos contextos:

- `red-social`
- `chat-grupos`
- `foros`
- `podcast`
- `radio`
- `multimedia`
- `documentacion-legal`
- `facturas`
- `contabilidad`
- `reservas`
- `espacios-comunes`
- `email-marketing`
- `mapa-actores`
- `avisos-municipales`

### 4. Alcance

- local
- federado / red

---

## 3. Tipos de Compatibilidad

Para cada contexto y modulo base conviene clasificar modulos adicionales como:

- `nucleares`: sin ellos la experiencia queda incompleta
- `recomendados`: mejoran mucho la experiencia
- `opcionales`: dependen del caso
- `sensibles al contexto`: utiles solo en determinados despliegues
- `no prioritarios`: no deberian mostrarse como primera capa

---

## 4. Matriz por Contexto

## A. Grupo de Consumo

### Modulo base

- `grupos-consumo`

### Modulos nucleares

- `socios`
- `eventos`
- `chat-grupos`

### Modulos recomendados

- `participacion`
- `marketplace`
- `facturas`
- `documentacion-legal`
- `reservas`
- `espacios-comunes`

### Modulos opcionales

- `red-social`
- `foros`
- `podcast`
- `radio`
- `multimedia`
- `recetas`
- `economia-suficiencia`
- `huella-ecologica`

### Modulos sensibles al contexto

- `tramites` si hay relacion con administracion publica
- `avisos-municipales` si depende de entorno municipal
- `network-communities` si participa en federacion de nodos
- `mapa-actores` si trabaja con productores o alianzas externas

### Bloques UX recomendados

- `Mi ciclo actual`
- `Mi grupo`
- `Coordinar pedido`
- `Pertenencia y cuotas`
- `Documentos y facturacion`
- `En la red`

---

## B. Grupo de Cuidados

### Modulo base

- `ayuda-vecinal` o `circulos-cuidados`

### Modulos nucleares

- `banco-tiempo`
- `chat-grupos`
- `comunidades`

### Modulos recomendados

- `socios`
- `eventos`
- `mapa-actores`
- `incidencias`

### Modulos opcionales

- `foros`
- `red-social`
- `podcast`
- `saberes-ancestrales`
- `talleres`

### Modulos sensibles al contexto

- `tramites` si hay acompañamiento institucional
- `documentacion-legal` si se gestionan protocolos o derivaciones
- `network-communities` si coopera con otras comunidades de cuidados

### Bloques UX recomendados

- `Pedir ayuda`
- `Ofrecer ayuda`
- `Mi circulo`
- `Conversar`
- `Recursos y saberes`
- `Derivar o escalar`

---

## C. Ayuntamiento o Entorno Municipal

### Modulos base posibles

- `tramites`
- `incidencias`
- `participacion`
- `transparencia`

### Modulos nucleares

- `avisos-municipales`
- `documentacion-legal`
- `eventos`

### Modulos recomendados

- `encuestas`
- `presupuestos-participativos`
- `foros`
- `red-social`
- `mapa-actores`

### Modulos opcionales

- `podcast`
- `radio`
- `marketplace`
- `trabajo-digno`
- `biodiversidad-local`
- `energia-comunitaria`

### Modulos sensibles al contexto

- `grupos-consumo` si hay politica alimentaria local
- `ayuda-vecinal` si se articula tejido comunitario
- `network-communities` si forma parte de red de municipios

### Bloques UX recomendados

- `Que requiere accion`
- `Mis tramites`
- `Participar`
- `Informacion publica`
- `Alertas`
- `Red de municipios`

---

## D. Comunidad General / Asociacion

### Modulo base

- `comunidades`

### Modulos nucleares

- `socios`
- `eventos`
- `red-social`
- `chat-grupos`

### Modulos recomendados

- `foros`
- `participacion`
- `marketplace`
- `colectivos`
- `biblioteca`
- `cursos`

### Modulos opcionales

- `podcast`
- `radio`
- `multimedia`
- `talleres`
- `documentacion-legal`
- `reservas`
- `espacios-comunes`

### Modulos sensibles al contexto

- `grupos-consumo`
- `ayuda-vecinal`
- `energia-comunitaria`
- `transparencia`
- `network-communities`

### Bloques UX recomendados

- `Mis espacios`
- `Conversar`
- `Participar`
- `Intercambiar`
- `Aprender`
- `En la red`

---

## E. Colectivo Organizado / Plataforma

### Modulo base

- `colectivos`

### Modulos nucleares

- `eventos`
- `participacion`
- `foros`

### Modulos recomendados

- `chat-grupos`
- `espacios-comunes`
- `marketplace`
- `crowdfunding`
- `campanias`

### Modulos opcionales

- `red-social`
- `multimedia`
- `podcast`
- `documentacion-legal`

### Modulos sensibles al contexto

- `socios` si hay membresia formal
- `tramites` si se relaciona con administracion
- `network-communities` si coopera externamente

### Bloques UX recomendados

- `Mi colectivo`
- `Asambleas y decisiones`
- `Proyectos`
- `Conversar`
- `Recursos y espacios`

---

## F. Academia / Escuela / Comunidad de Aprendizaje

### Modulo base

- `cursos`

### Modulos nucleares

- `biblioteca`
- `eventos`
- `multimedia`

### Modulos recomendados

- `talleres`
- `saberes-ancestrales`
- `foros`
- `red-social`

### Modulos opcionales

- `podcast`
- `radio`
- `documentacion-legal`
- `marketplace`

### Modulos sensibles al contexto

- `socios` si la membresia habilita acceso
- `network-communities` si hay federacion educativa

### Bloques UX recomendados

- `Continuar aprendiendo`
- `Recursos relacionados`
- `Sesiones y eventos`
- `Conversar`
- `Certificados y avance`

---

## G. Coworking / Espacios Compartidos

### Modulo base

- `espacios-comunes` o `reservas`

### Modulos nucleares

- `reservas`
- `socios`
- `eventos`

### Modulos recomendados

- `marketplace`
- `facturas`
- `contabilidad`
- `chat-grupos`

### Modulos opcionales

- `red-social`
- `foros`
- `clientes`
- `empresas`

### Modulos sensibles al contexto

- `fichaje-empleados` si hay operacion empresarial
- `tramites` si hay gestiones administrativas

### Bloques UX recomendados

- `Mis reservas`
- `Disponibilidad`
- `Facturacion`
- `Comunidad`
- `Servicios adicionales`

---

## H. Red Cultural / Medio Comunitario

### Modulo base

- `kulturaka`, `eventos`, `podcast` o `radio`

### Modulos nucleares

- `multimedia`
- `eventos`
- `red-social`

### Modulos recomendados

- `podcast`
- `radio`
- `crowdfunding`
- `colectivos`
- `comunidades`

### Modulos opcionales

- `marketplace`
- `talleres`
- `cursos`
- `biblioteca`

### Modulos sensibles al contexto

- `network-communities` si hay red de espacios o nodos culturales
- `socios` si hay membresia cultural

### Bloques UX recomendados

- `Programacion`
- `Escuchar y ver`
- `Participar`
- `Apoyar`
- `Red cultural`

---

## I. Economia Local / Mercado Comunitario

### Modulo base

- `marketplace`

### Modulos nucleares

- `comunidades`
- `chat-grupos`
- `red-social`

### Modulos recomendados

- `woocommerce`
- `facturas`
- `empresas`
- `clientes`
- `grupos-consumo`

### Modulos opcionales

- `socios`
- `sello-conciencia`
- `trabajo-digno`
- `economia-don`

### Modulos sensibles al contexto

- `network-communities` si vende o comparte fuera del nodo
- `mapa-actores` si quiere mostrar tejido productivo

### Bloques UX recomendados

- `Explorar`
- `Publicado por`
- `Conversar o negociar`
- `Mi comunidad`
- `En la red`

---

## J. Red Territorial / Barrio

### Modulos base posibles

- `comunidades`
- `incidencias`
- `ayuda-vecinal`

### Modulos nucleares

- `avisos-municipales`
- `mapa-actores`
- `eventos`

### Modulos recomendados

- `banco-tiempo`
- `bicicletas-compartidas`
- `carpooling`
- `huertos-urbanos`
- `compostaje`
- `reciclaje`

### Modulos opcionales

- `participacion`
- `foros`
- `red-social`

### Modulos sensibles al contexto

- `tramites` en despliegues municipales
- `network-communities` si se conecta con otros barrios o municipios

### Bloques UX recomendados

- `Que pasa en mi zona`
- `Pedir / ofrecer`
- `Movilidad`
- `Recursos compartidos`
- `Alertas`

---

## K. Empresa / Negocio / Operacion Interna

### Modulo base

- `empresas`, `clientes` o `empresarial`

### Modulos nucleares

- `facturas`
- `contabilidad`
- `chat-interno`

### Modulos recomendados

- `marketplace`
- `woocommerce`
- `fichaje-empleados`
- `email-marketing`

### Modulos opcionales

- `reservas`
- `eventos`
- `clientes`

### Modulos sensibles al contexto

- `socios` si mezcla membresia y negocio
- `network-communities` si coopera en red de empresas

### Bloques UX recomendados

- `Operacion diaria`
- `Ventas y cobros`
- `Equipo`
- `Clientes`
- `Canales`

---

## L. Red de Nodos / Federacion

### Modulo base

- `network-communities`

### Modulos nucleares

- `comunidades`
- `eventos`
- `marketplace`

### Modulos recomendados

- `grupos-consumo`
- `mapa-actores`
- `podcast`
- `radio`
- `banco-tiempo`
- `saberes-ancestrales`

### Modulos opcionales

- `transparencia`
- `participacion`
- `avisos-municipales`

### Modulos sensibles al contexto

- casi todos los modulos pueden entrar, pero no todos deberian entrar como primera capa

### Bloques UX recomendados

- `Tu nodo`
- `Otros nodos`
- `Eventos de la red`
- `Catalogo de la red`
- `Colaboraciones abiertas`

---

## 5. Reglas UX Derivadas

### Regla 1

No existe una unica jerarquia de modulos valida para todos los despliegues.

### Regla 2

Los modulos transversales deben activarse por contexto, no por presencia en el arbol.

### Regla 3

En frontend siempre deberia quedar claro:

- cual es el contenedor
- cual es el modulo base
- que herramientas complementarias estan activas aqui
- si el alcance es local o de red

### Regla 4

Un modulo no debe mostrar todos los modulos compatibles a la vez.

Debe priorizar:

- nucleares
- recomendados
- opcionales

### Regla 5

Los bloques `Relacionado`, `Conversar`, `Gestionar`, `En la red` deben poblarse segun contexto y no solo segun modulo.

---

## 6. Implicacion para el Plan UX

El plan de mejoras no deberia limitarse a:

- arreglar dashboards
- embellecer componentes

Sino a:

- soportar composiciones dinamicas por contexto
- definir bundles UX segun tipo de despliegue
- hacer visibles las herramientas activas de cada espacio

---

## 7. Conclusion

La idea correcta no es:

- "este modulo se relaciona con estos tres modulos"

Sino:

- "en este contexto, este modulo base se compone con estas herramientas y estas relaciones"

Eso permite representar bien casos como:

- un grupo de consumo dentro de una comunidad
- un grupo de consumo ligado a un ayuntamiento
- un grupo de consumo federado en una red
- un grupo de cuidados que usa chat, podcast, facturas y documentos
- una comunidad cultural que combina eventos, multimedia, crowdfunding y radio

Esta matriz debe usarse como base para definir:

- bundles de activacion
- perfiles de portal
- bloques contextuales en frontend
- reglas de compatibilidad visibles para el usuario

---

## 8. Prioridad de Implementacion por Contexto

### Primera ola

- `Comunidad General / Asociacion`
- `Grupo de Consumo`
- `Ayuntamiento o Entorno Municipal`

Motivo:

- concentran mas modulos ya desarrollados
- permiten validar hubs, relaciones y rutas canonicas

### Segunda ola

- `Grupo de Cuidados`
- `Academia / Escuela / Comunidad de Aprendizaje`
- `Coworking / Espacios Compartidos`

Motivo:

- reutilizan bastante infraestructura comun
- requieren menos arquitectura nueva que la red federada

### Tercera ola

- `Red Cultural / Medio Comunitario`
- `Economia Local / Mercado Comunitario` en modo expandido
- `Red Territorial / Barrio`
- `Empresa / Operacion`
- `Red de Nodos / Federacion`

Motivo:

- implican mas sensibilidad contextual
- mezclan modulos transversales y federados
- exigen una narrativa UX mas madura

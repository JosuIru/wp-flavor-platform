# Matriz Maestra de Jerarquias e Interconexiones de Modulos

**Fecha:** 2026-03-22
**Alcance:** Todos los modulos detectados en `includes/modules`
**Objetivo:** Disponer de un mapa UX maestro para ordenar jerarquias, familias funcionales e interconexiones potenciales entre modulos.

---

## 1. Capas Jerarquicas

### Nivel 0: Portal

- `mi-portal`

### Nivel 1: Hubs y contenedores

- `comunidades`
- `colectivos`
- `socios`
- `network-communities` / red de nodos

### Nivel 2: Ecosistemas funcionales

- gobernanza
- conocimiento
- economia
- territorio e infraestructura
- conversacion y pulso
- servicios y gestion
- laboratorio o nicho

### Nivel 3: Modulos operativos

Los modulos concretos de cada ecosistema.

---

## 2. Familias Funcionales

### Gobernanza

- `participacion`
- `transparencia`
- `presupuestos-participativos`
- `encuestas`
- `campanias`
- `seguimiento-denuncias`
- `justicia-restaurativa`
- `documentacion-legal`

### Conocimiento y cultura

- `biblioteca`
- `cursos`
- `talleres`
- `saberes-ancestrales`
- `multimedia`
- `podcast`
- `radio`
- `agregador-contenido`
- `recetas`
- `kulturaka`

### Economia e intercambio

- `marketplace`
- `grupos-consumo`
- `banco-tiempo`
- `economia-don`
- `economia-suficiencia`
- `crowdfunding`
- `trabajo-digno`
- `empresas`
- `clientes`
- `contabilidad`
- `facturas`
- `woocommerce`
- `advertising`
- `empresarial`

### Territorio, recursos e infraestructura

- `espacios-comunes`
- `reservas`
- `parkings`
- `bicicletas-compartidas`
- `carpooling`
- `mapa-actores`
- `biodiversidad-local`
- `huertos-urbanos`
- `compostaje`
- `reciclaje`
- `energia-comunitaria`
- `huella-ecologica`
- `incidencias`
- `avisos-municipales`
- `ayuda-vecinal`
- `circulos-cuidados`

### Conversacion y pulso

- `red-social`
- `chat-grupos`
- `chat-interno`
- `chat-estados`
- `foros`
- `email-marketing`

### Servicios y gestion

- `tramites`
- `fichaje-empleados`
- `bug-tracker`
- `sello-conciencia`

### Especializados o laboratorio

- `dex-solana`
- `trading-ia`
- `themacle`
- `bares`

---

## 3. Tipos de Relacion UX

Cada relacion entre modulos deberia clasificarse en una o varias de estas categorias:

- `contexto`: donde ocurre algo
- `pertenencia`: quien puede hacerlo o verlo
- `siguiente_paso`: accion natural posterior
- `conversacion`: donde se comenta o coordina
- `recurso`: documento, espacio, contenido o infraestructura usada
- `escala_red`: salto fuera del contexto local

---

## 4. Matriz Exhaustiva por Modulo

Formato:

- `nivel`: jerarquia UX recomendada
- `familia`: ecosistema principal
- `relaciones_fuertes`: conexiones que deberian ser visibles en frontend
- `relaciones_medias`: conexiones utiles, pero no siempre principales
- `riesgo`: riesgo UX si se presenta mal

### advertising

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `marketplace`, `empresas`, `clientes`
- relaciones_medias: `eventos`, `email-marketing`
- riesgo: parecer una isla de promocion sin contexto comercial claro

### agregador-contenido

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `podcast`, `radio`, `multimedia`
- relaciones_medias: `biblioteca`, `documentacion-legal`, `red-social`
- riesgo: mostrarse como backend tecnico en vez de hub de contenido

### avisos-municipales

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `incidencias`, `tramites`, `comunidades`
- relaciones_medias: `red-social`, `email-marketing`
- riesgo: duplicar notificaciones sin distinguir urgencia publica vs actividad social

### ayuda-vecinal

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `banco-tiempo`, `circulos-cuidados`, `comunidades`
- relaciones_medias: `incidencias`, `mapa-actores`
- riesgo: mezclar ayuda mutua con intercambio general sin etiquetas claras

### banco-tiempo

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `ayuda-vecinal`, `comunidades`, `trabajo-digno`
- relaciones_medias: `marketplace`, `circulos-cuidados`
- riesgo: confundirse con marketplace o voluntariado

### bares

- nivel: 3
- familia: especializados o laboratorio
- relaciones_fuertes: `reservas`, `eventos`
- relaciones_medias: `marketplace`, `comunidades`
- riesgo: quedar fuera del ecosistema si no se contextualiza como ocio/servicio local

### biblioteca

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `cursos`, `talleres`, `saberes-ancestrales`
- relaciones_medias: `eventos`, `comunidades`, `documentacion-legal`, `foros`
- riesgo: parecer catalogo aislado y no biblioteca contextual

### bicicletas-compartidas

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `comunidades`, `carpooling`
- relaciones_medias: `incidencias`, `energia-comunitaria`, `parkings`
- riesgo: aislar movilidad ligera del resto de infraestructura comunitaria

### biodiversidad-local

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `huertos-urbanos`, `compostaje`, `reciclaje`
- relaciones_medias: `eventos`, `comunidades`
- riesgo: mostrarse como contenido pasivo en vez de proyecto territorial

### bug-tracker

- nivel: 3
- familia: servicios y gestion
- relaciones_fuertes: `themacle`, `trading-ia`, `dex-solana`
- relaciones_medias: `chat-interno`
- riesgo: meterlo en el portal general de usuarios cuando es mas de operacion tecnica

### campanias

- nivel: 3
- familia: gobernanza
- relaciones_fuertes: `participacion`, `eventos`, `red-social`
- relaciones_medias: `crowdfunding`, `email-marketing`
- riesgo: superponerse con participacion y social sin diferenciar movilizacion

### carpooling

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `eventos`, `comunidades`, `parkings`
- relaciones_medias: `bicicletas-compartidas`
- riesgo: quedar desconectado de movilidad comunitaria y asistencia a eventos

### chat-estados

- nivel: 4
- familia: conversacion y pulso
- relaciones_fuertes: `red-social`, `chat-grupos`
- relaciones_medias: `chat-interno`
- riesgo: no distinguirse de feed social o mensajeria

### chat-grupos

- nivel: 4
- familia: conversacion y pulso
- relaciones_fuertes: `comunidades`, `colectivos`, `eventos`, `participacion`, `grupos-consumo`
- relaciones_medias: `incidencias`, `marketplace`
- riesgo: chats sin contexto de origen

### chat-interno

- nivel: 4
- familia: conversacion y pulso
- relaciones_fuertes: `clientes`, `empresas`, `fichaje-empleados`
- relaciones_medias: `bug-tracker`
- riesgo: mezclar mensajeria interna con canales comunitarios

### circulos-cuidados

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `ayuda-vecinal`, `banco-tiempo`, `comunidades`
- relaciones_medias: `socios`
- riesgo: competir con ayuda vecinal en vez de complementarla

### clientes

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `empresas`, `contabilidad`, `facturas`
- relaciones_medias: `chat-interno`, `email-marketing`
- riesgo: aparecer como CRM suelto sin recorrido funcional

### colectivos

- nivel: 1
- familia: hub y contenedor
- relaciones_fuertes: `comunidades`, `eventos`, `participacion`, `foros`, `espacios-comunes`
- relaciones_medias: `marketplace`, `socios`, `chat-grupos`
- riesgo: no verse como subestructura entre comunidad y accion organizada

### compostaje

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `reciclaje`, `huertos-urbanos`, `biodiversidad-local`
- relaciones_medias: `eventos`, `comunidades`
- riesgo: quedar como modulo ecologico aislado

### comunidades

- nivel: 1
- familia: hub y contenedor
- relaciones_fuertes: `socios`, `colectivos`, `eventos`, `participacion`, `marketplace`, `grupos-consumo`, `red-social`, `chat-grupos`, `foros`
- relaciones_medias: `incidencias`, `cursos`, `biblioteca`, `espacios-comunes`, `ayuda-vecinal`, `energia-comunitaria`
- riesgo: no presentarse como hub del ecosistema local

### contabilidad

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `facturas`, `clientes`, `empresas`
- relaciones_medias: `woocommerce`, `marketplace`
- riesgo: capa financiera desconectada de actividad comercial

### crowdfunding

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `campanias`, `colectivos`, `comunidades`
- relaciones_medias: `eventos`, `socios`, `kulturaka`
- riesgo: confundirse con donaciones o con marketplace

### cursos

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `biblioteca`, `talleres`, `saberes-ancestrales`
- relaciones_medias: `eventos`, `multimedia`, `podcast`, `comunidades`
- riesgo: parecer LMS cerrado y no nodo de aprendizaje

### dex-solana

- nivel: 3
- familia: especializados o laboratorio
- relaciones_fuertes: `trading-ia`, `themacle`
- relaciones_medias: `bug-tracker`
- riesgo: contaminar el portal general si no se separa como vertical tecnico

### documentacion-legal

- nivel: 3
- familia: gobernanza
- relaciones_fuertes: `tramites`, `transparencia`, `participacion`
- relaciones_medias: `biblioteca`, `foros`, `seguimiento-denuncias`
- riesgo: parecer un repositorio muerto sin flujos asociados

### economia-don

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `marketplace`, `banco-tiempo`, `comunidades`
- relaciones_medias: `socios`
- riesgo: confundirse con crowdfunding o marketplace

### economia-suficiencia

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `grupos-consumo`, `marketplace`, `comunidades`
- relaciones_medias: `biblioteca`, `cursos`
- riesgo: quedar solo como contenido teorico

### email-marketing

- nivel: 4
- familia: conversacion y pulso
- relaciones_fuertes: `campanias`, `eventos`, `clientes`
- relaciones_medias: `avisos-municipales`, `socios`
- riesgo: duplicar canales de comunicacion sin rol claro

### empresarial

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `empresas`, `clientes`, `facturas`
- relaciones_medias: `contabilidad`, `marketplace`
- riesgo: ser redundante si no se diferencia de empresas/CRM

### empresas

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `clientes`, `facturas`, `marketplace`
- relaciones_medias: `trabajo-digno`, `empresarial`
- riesgo: no quedar claro si es directorio, ERP o portal comercial

### encuestas

- nivel: 3
- familia: gobernanza
- relaciones_fuertes: `participacion`, `comunidades`
- relaciones_medias: `eventos`, `email-marketing`
- riesgo: duplicar mecanismos de decision sin explicar diferencia con votaciones

### energia-comunitaria

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `comunidades`, `participacion`, `presupuestos-participativos`
- relaciones_medias: `incidencias`, `huella-ecologica`
- riesgo: separarse de gobernanza y sostenibilidad

### espacios-comunes

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `reservas`, `eventos`, `colectivos`, `comunidades`
- relaciones_medias: `cursos`, `parkings`
- riesgo: verse como catalogo de espacios y no como infraestructura compartida

### eventos

- nivel: 3
- familia: actividad/comunidad
- relaciones_fuertes: `comunidades`, `participacion`, `foros`, `red-social`, `chat-grupos`, `espacios-comunes`
- relaciones_medias: `socios`, `reservas`, `cursos`, `biblioteca`
- riesgo: no conectar convocatoria, conversacion y decision

### facturas

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `contabilidad`, `clientes`, `empresas`
- relaciones_medias: `woocommerce`, `socios`
- riesgo: quedar como backend administrativo

### fichaje-empleados

- nivel: 3
- familia: servicios y gestion
- relaciones_fuertes: `empresas`, `chat-interno`
- relaciones_medias: `facturas`, `clientes`
- riesgo: meterlo en experiencia ciudadana general

### foros

- nivel: 4
- familia: conversacion y pulso
- relaciones_fuertes: `comunidades`, `colectivos`, `participacion`
- relaciones_medias: `eventos`, `cursos`, `biblioteca`, `seguimiento-denuncias`
- riesgo: no distinguir debate estructurado de chat y red social

### grupos-consumo

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `comunidades`, `socios`, `eventos`, `participacion`, `chat-grupos`
- relaciones_medias: `marketplace`, `economia-suficiencia`, `recetas`, `red-social`
- riesgo: mezcla de rutas y circuitos legacy

### huella-ecologica

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `energia-comunitaria`, `grupos-consumo`, `reciclaje`
- relaciones_medias: `carpooling`, `bicicletas-compartidas`
- riesgo: mostrarse como analitica aislada

### huertos-urbanos

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `compostaje`, `biodiversidad-local`, `saberes-ancestrales`
- relaciones_medias: `eventos`, `comunidades`
- riesgo: separarse de sostenibilidad y aprendizaje practico

### incidencias

- nivel: 3
- familia: servicios y gestion
- relaciones_fuertes: `avisos-municipales`, `comunidades`, `participacion`
- relaciones_medias: `eventos`, `mapa-actores`, `red-social`
- riesgo: tratarlo como simple ticketing y no como tema territorial

### justicia-restaurativa

- nivel: 3
- familia: gobernanza
- relaciones_fuertes: `seguimiento-denuncias`, `comunidades`, `documentacion-legal`
- relaciones_medias: `foros`
- riesgo: no diferenciar mediacion de denuncia o proceso formal

### kulturaka

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `eventos`, `crowdfunding`, `comunidades`, `colectivos`
- relaciones_medias: `multimedia`, `banco-tiempo`
- riesgo: quedar como vertical cultural aislada sin conexiones operativas

### mapa-actores

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `comunidades`, `ayuda-vecinal`, `trabajo-digno`
- relaciones_medias: `incidencias`, `biodiversidad-local`
- riesgo: grafo bonito pero poca accion posterior

### marketplace

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `comunidades`, `red-social`, `chat-grupos`
- relaciones_medias: `grupos-consumo`, `woocommerce`, `empresas`, `socios`
- riesgo: no mostrar procedencia comunitaria ni contexto

### multimedia

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `eventos`, `cursos`, `podcast`, `radio`
- relaciones_medias: `red-social`, `documentacion-legal`
- riesgo: galeria sin relacion con aprendizaje o memoria comunitaria

### parkings

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `reservas`, `carpooling`
- relaciones_medias: `eventos`, `espacios-comunes`
- riesgo: quedar como servicio autonomo sin ecosistema de movilidad

### participacion

- nivel: 3
- familia: gobernanza
- relaciones_fuertes: `comunidades`, `eventos`, `foros`, `chat-grupos`, `transparencia`
- relaciones_medias: `presupuestos-participativos`, `encuestas`, `incidencias`
- riesgo: no explicar contexto, debate y trazabilidad

### podcast

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `radio`, `multimedia`, `agregador-contenido`
- relaciones_medias: `cursos`, `saberes-ancestrales`
- riesgo: no integrarse en el ecosistema de contenidos

### presupuestos-participativos

- nivel: 3
- familia: gobernanza
- relaciones_fuertes: `participacion`, `transparencia`, `comunidades`
- relaciones_medias: `energia-comunitaria`
- riesgo: duplicar decision y presupuesto sin trazabilidad documental

### radio

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `podcast`, `multimedia`, `agregador-contenido`
- relaciones_medias: `eventos`, `comunidades`
- riesgo: no diferenciar emision en vivo de archivo de contenido

### recetas

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `grupos-consumo`, `saberes-ancestrales`
- relaciones_medias: `biblioteca`, `multimedia`
- riesgo: quedar como microvertical sin entrada natural

### reciclaje

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `compostaje`, `huella-ecologica`, `biodiversidad-local`
- relaciones_medias: `comunidades`, `eventos`
- riesgo: no conectar accion cotidiana con impacto

### red-social

- nivel: 4
- familia: conversacion y pulso
- relaciones_fuertes: `comunidades`, `eventos`, `marketplace`, `participacion`
- relaciones_medias: `colectivos`, `grupos-consumo`, `incidencias`, `cursos`
- riesgo: feed descontextualizado

### reservas

- nivel: 3
- familia: territorio, recursos e infraestructura
- relaciones_fuertes: `espacios-comunes`, `eventos`, `parkings`
- relaciones_medias: `bares`, `socios`
- riesgo: existir como modulo flotante en vez de capa de infraestructura

### saberes-ancestrales

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `talleres`, `cursos`, `comunidades`
- relaciones_medias: `biblioteca`, `eventos`, `huertos-urbanos`, `recetas`
- riesgo: aislar memoria cultural de formacion y territorio

### seguimiento-denuncias

- nivel: 3
- familia: gobernanza
- relaciones_fuertes: `justicia-restaurativa`, `documentacion-legal`
- relaciones_medias: `foros`, `comunidades`
- riesgo: no distinguirse de incidencias o tramites

### sello-conciencia

- nivel: 3
- familia: servicios y gestion
- relaciones_fuertes: `socios`, `comunidades`
- relaciones_medias: `marketplace`, `trabajo-digno`
- riesgo: no quedar claro si es credencial, reputacion o certificacion

### socios

- nivel: 1
- familia: hub y contenedor
- relaciones_fuertes: `comunidades`, `participacion`, `eventos`, `grupos-consumo`
- relaciones_medias: `transparencia`, `reservas`, `sello-conciencia`
- riesgo: verse solo como cuotas y no como identidad y pertenencia

### talleres

- nivel: 3
- familia: conocimiento y cultura
- relaciones_fuertes: `cursos`, `saberes-ancestrales`, `eventos`
- relaciones_medias: `biblioteca`, `multimedia`
- riesgo: duplicarse con cursos si no se distingue practica/presencial

### themacle

- nivel: 3
- familia: especializados o laboratorio
- relaciones_fuertes: `trading-ia`, `dex-solana`
- relaciones_medias: `bug-tracker`
- riesgo: modulo experimental sin sitio claro en UX general

### trabajo-digno

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `empresas`, `marketplace`, `clientes`
- relaciones_medias: `banco-tiempo`, `mapa-actores`, `sello-conciencia`
- riesgo: mezclar empleo, emprendimiento y colaboracion sin jerarquia

### trading-ia

- nivel: 3
- familia: especializados o laboratorio
- relaciones_fuertes: `dex-solana`, `themacle`
- relaciones_medias: `bug-tracker`
- riesgo: mezclarlo con modulos comunitarios generales

### tramites

- nivel: 3
- familia: servicios y gestion
- relaciones_fuertes: `comunidades`, `avisos-municipales`, `documentacion-legal`
- relaciones_medias: `incidencias`, `transparencia`
- riesgo: servicio correcto pero sin continuidad visible

### transparencia

- nivel: 3
- familia: gobernanza
- relaciones_fuertes: `participacion`, `presupuestos-participativos`, `tramites`, `documentacion-legal`
- relaciones_medias: `socios`, `comunidades`
- riesgo: portal documental desconectado de decisiones

### woocommerce

- nivel: 3
- familia: economia e intercambio
- relaciones_fuertes: `marketplace`, `facturas`, `contabilidad`
- relaciones_medias: `empresas`, `clientes`
- riesgo: duplicidad con marketplace si no se diferencia canal o backend

---

## 5. Reglas de Presentacion UX

### Regla 1: no todos los modulos deben verse al mismo nivel

Los hubs y contenedores deben ordenar la experiencia:

- `mi-portal`
- `comunidades`
- `colectivos`
- `socios`

### Regla 2: cada pantalla debe responder a 5 preguntas

1. donde estoy
2. que puedo hacer ahora
3. que se relaciona con esto
4. donde se conversa sobre esto
5. si esto afecta solo a mi contexto o a la red

### Regla 3: no todas las relaciones son simetricas

Ejemplos:

- `biblioteca -> cursos` es fuerte
- `cursos -> biblioteca` es fuerte
- `socios -> eventos` es fuerte
- `eventos -> socios` solo cuando membresia afecte acceso
- `tramites -> red-social` es debil
- `red-social -> tramites` rara vez debe ser CTA principal

### Regla 4: modulos transversales deben heredar contexto

Modulos como:

- `red-social`
- `foros`
- `chat-grupos`
- `biblioteca`
- `multimedia`

deberian poder heredar:

- `comunidad_id`
- `evento_id`
- `propuesta_id`
- `colectivo_id`
- `nodo_id`

---

## 6. Ecosistemas UX Recomendados

### Ecosistema Comunidad

- `comunidades`
- `socios`
- `colectivos`
- `eventos`
- `red-social`
- `chat-grupos`
- `foros`

### Ecosistema Gobernanza

- `participacion`
- `transparencia`
- `presupuestos-participativos`
- `encuestas`
- `campanias`
- `documentacion-legal`
- `seguimiento-denuncias`
- `justicia-restaurativa`

### Ecosistema Conocimiento

- `biblioteca`
- `cursos`
- `talleres`
- `saberes-ancestrales`
- `multimedia`
- `podcast`
- `radio`
- `agregador-contenido`
- `recetas`

### Ecosistema Economia

- `marketplace`
- `grupos-consumo`
- `banco-tiempo`
- `economia-don`
- `economia-suficiencia`
- `crowdfunding`
- `trabajo-digno`
- `empresas`
- `clientes`
- `contabilidad`
- `facturas`
- `woocommerce`

### Ecosistema Territorio

- `incidencias`
- `avisos-municipales`
- `energia-comunitaria`
- `reciclaje`
- `compostaje`
- `huertos-urbanos`
- `biodiversidad-local`
- `mapa-actores`
- `espacios-comunes`
- `reservas`
- `parkings`
- `bicicletas-compartidas`
- `carpooling`
- `ayuda-vecinal`
- `circulos-cuidados`

---

## 7. Conclusion

El plugin no debe pensarse como un listado plano de 66 modulos, sino como una superposicion de ecosistemas:

- comunidad
- gobernanza
- conocimiento
- economia
- territorio
- conversacion
- red federada

La deuda UX principal no es solo visual. Es jerarquica:

- que modulos ordenan
- cuales habilitan pertenencia
- cuales ejecutan acciones
- cuales aportan recursos
- cuales canalizan la conversacion
- cuales extienden la experiencia a la red

Esta matriz puede usarse como base para:

- priorizar activaciones
- definir bloques `Relacionado`
- definir bloques `Conversar`
- definir bloques `En tu comunidad`
- definir bloques `En la red`
- reducir menus planos y dashboards redundantes

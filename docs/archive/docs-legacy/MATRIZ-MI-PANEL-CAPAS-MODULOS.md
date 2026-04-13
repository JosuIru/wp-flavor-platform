# Matriz de Capas para Mi-Panel

## Objetivo

Esta matriz fija como deben leerse los modulos actuales dentro de `mi-panel`.

La home no debe responder primero a `que modulos existen`, sino a:

- `que requiere atencion`
- `que toca hacer`
- `en que ecosistemas participo`
- `que se esta moviendo`

## Capas recomendadas

Orden recomendado para `mi-panel`:

1. `Señales del nodo`
2. `Que hacer ahora`
3. `Ecosistemas principales`
4. `Ecosistemas coordinados`
5. `Otros espacios activos`
6. `Pulso social`

## Regla base

Cada modulo debe tener una funcion dominante en portada:

- `signal`: alerta, aviso, anuncio, urgencia, notificacion
- `action`: siguiente paso, tarea, evento, reserva, proceso pendiente
- `ecosystem`: estructura base o satelite claro de un ecosistema
- `pulse`: actividad social, posts, conversaciones, memoria viva
- `service`: modulo util, pero no prioritario para portada
- `transversal`: capa de soporte, medicion, gobernanza o aprendizaje

Puede participar en varias capas, pero en `mi-panel` debe tener una prioridad dominante.

## Matriz de modulos

| Modulo | Capa dominante | Capa secundaria | Debe subir a portada | Nota |
| --- | --- | --- | --- | --- |
| `avisos_municipales` | `signal` | `action` | si | aviso directo del nodo |
| `anuncios` | `signal` | `pulse` | si | mejor como señal corta que como widget grande |
| `notificaciones` | `signal` | `pulse` | si | urgentes arriba, sociales menores abajo |
| `incidencias` | `signal` | `action` | si | muy buena prioridad nativa |
| `eventos` | `action` | `ecosystem` | si | debe alimentar `Que hacer ahora` |
| `reservas` | `action` | `ecosystem` | si | mejor como proximo paso que como bloque aislado |
| `participacion` | `action` | `transversal` | si | decisiones activas, votaciones o procesos abiertos |
| `comunidades` | `ecosystem` | `pulse` | si | base principal de estructura compartida |
| `energia_comunitaria` | `ecosystem` | `signal` | si | satelite operativo fuerte |
| `grupos_consumo` | `ecosystem` | `action` | si | satelite operativo fuerte |
| `banco_tiempo` | `ecosystem` | `action` | si | satelite operativo fuerte |
| `ayuda_vecinal` | `ecosystem` | `signal` | si | satelite operativo fuerte |
| `socios` | `ecosystem` o `service` | `action` | depende | solo debe subir como ecosistema si sostiene modulos activos reales |
| `colectivos` | `ecosystem` o `service` | `pulse` | depende | hoy encaja mejor como base local |
| `transparencia` | `transversal` | `action` | si, pero discreto | no debe competir con bases |
| `huella_ecologica` | `transversal` | `service` | si, pero discreto | mejor como capa de lectura o impacto |
| `economia_suficiencia` | `transversal` | `service` | opcional | buena como recomendacion, no como eje |
| `saberes_ancestrales` | `transversal` | `pulse` | opcional | mejor como capa cultural |
| `red_social` | `pulse` | `signal` | si | alimenta `Pulso social` |
| `chat_grupos` | `pulse` | `signal` | si | muy util para no leidos y actividad |
| `foros` | `pulse` | `service` | si, pero abajo | conversacion de continuidad, no urgencia |
| `podcast` | `service` | `pulse` | no | mejor en espacios activos o pulso cultural |
| `multimedia` | `service` | `pulse` | no | no deberia competir con ecosistemas |
| `radio` | `service` | `pulse` | no | mejor como contenido o memoria viva |
| `biblioteca` | `service` | `transversal` | no | util, pero no prioritaria en home |
| `marketplace` | `service` | `action` | depende | solo subir si hay actividad comercial real del usuario |
| `tramites` | `service` | `action` | depende | subir solo con tramite pendiente o cita |
| `clientes` | `service` | `action` | no | mas propio de panel profesional |
| `empresarial` | `service` | `action` | no | mas propio de otra app o contexto |

## Lectura por bloque

### 1. Señales del nodo

Aqui deben entrar:

- avisos
- anuncios prioritarios
- notificaciones urgentes
- incidencias abiertas o relevantes
- alertas de energia, comunidad o reservas

Pregunta que responde:

`que requiere atencion ahora`

### 2. Que hacer ahora

Aqui deben entrar:

- eventos cercanos
- reservas proximas
- decisiones abiertas
- tareas o acciones pendientes
- herramientas recomendadas

Pregunta que responde:

`que toca hacer`

### 3. Ecosistemas principales

Solo deben subir los ecosistemas con estructura clara y vida operativa real.

Hoy, los candidatos mas fuertes son:

- `comunidades`
- `energia_comunitaria` cuando cuelga de comunidad activa
- `grupos_consumo`
- `banco_tiempo`
- `ayuda_vecinal`

`socios` solo deberia subir aqui si realmente sostiene `reservas`, `participacion` o `transparencia` activas para ese usuario.

### 4. Ecosistemas coordinados

Aqui va el detalle operativo:

- base activa
- satelites operativos
- capas transversales relacionadas

Este bloque debe explicar la estructura, no competir con la urgencia.

### 5. Otros espacios activos

Aqui deben caer los modulos utiles pero no estructurales:

- podcast
- multimedia
- radio
- biblioteca
- marketplace
- tramites
- servicios sueltos

### 6. Pulso social

Aqui deben vivir:

- ultimos posts
- nodos y grupos
- conversaciones abiertas
- actividad reciente

Pregunta que responde:

`que se esta moviendo`

## Riesgos si no se respeta esta matriz

- demasiados modulos parecen igual de importantes
- los ecosistemas pierden claridad
- avisos y notificaciones se mezclan con servicios secundarios
- el usuario no distingue urgencia, accion y estructura
- modulos culturales o standalone inflan la portada

## Criterio practico para subir o bajar un modulo

Un modulo debe subir a portada si cumple al menos una:

- tiene urgencia
- tiene accion pendiente
- sostiene un ecosistema real
- aporta actividad social relevante

Si no cumple ninguna, debe ir a `Otros espacios activos`.

## Conclusion

La estructura correcta para Flavor, con lo que ya tiene desarrollado, es:

- `Señal`
- `Accion`
- `Estructura`
- `Pulso`
- `Servicio`

Eso mantiene coherencia con la filosofia nueva y con la realidad del catalogo actual.

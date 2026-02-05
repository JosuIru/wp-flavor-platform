=== Flavor Network Communities ===
Contributors: gailulabs
Tags: network, communities, multi-site, federation, collaboration
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema de red multi-sitio para conectar comunidades, compartir contenido y colaborar globalmente.

== Description ==

**Flavor Network Communities** permite crear una red federada de sitios WordPress que pueden compartir contenido, eventos, recursos y colaborar entre sí.

Ideal para:
* **Redes de municipios** - Conecta pueblos y ciudades de una comarca o región
* **Franquicias** - Vincula todas las sedes de tu negocio
* **Federaciones** - Une asociaciones, cooperativas o grupos de trabajo
* **Comunidades temáticas** - Conecta grupos con intereses comunes
* **Redes sociales** - Crea tu propia red social federada

= Características Principales =

**Conexión de Nodos**
* Conecta múltiples instalaciones de Flavor Platform
* Comunicación segura mediante API REST
* Autenticación y verificación de nodos
* Sincronización bidireccional de contenido

**Directorio Global**
* Listado de todos los nodos de la red
* Búsqueda y filtrado de comunidades
* Perfiles públicos de cada nodo
* Mapa interactivo de la red

**Contenido Compartido**
* **Eventos** - Calendario global de eventos de todos los nodos
* **Catálogo** - Productos y servicios compartidos
* **Tablón** - Avisos y noticias de la red
* **Alertas** - Notificaciones importantes
* **Colaboraciones** - Proyectos conjuntos
* **Ofertas de Tiempo** - Banco de tiempo compartido
* **Preguntas** - Foro de consultas de la red

**Módulos de Red**
* Gestión modular de funcionalidades
* Activa solo lo que necesites
* Cada módulo es independiente
* Configuración granular

**API REST Completa**
* Endpoints para todos los recursos
* Documentación automática
* Webhooks para eventos
* Rate limiting integrado

= Requisitos =

* Flavor Platform 3.0 o superior
* WordPress 5.8+
* PHP 7.4+
* Extensión PHP cURL (para comunicación entre nodos)
* Extensión PHP JSON

= Casos de Uso =

**Red de Municipios**
Conecta todos los pueblos de tu comarca:
* Calendario de eventos comarcal
* Tablón de noticias regional
* Catálogo de comercio local
* Directorio de asociaciones

**Franquicia Multi-sede**
Vincula todas tus tiendas:
* Catálogo de productos compartido
* Eventos de todas las sedes
* Sistema de colaboración interno
* Mapa de ubicaciones

**Federación de Asociaciones**
Une grupos de trabajo:
* Proyectos colaborativos
* Recursos compartidos
* Comunicación interna
* Eventos federados

== Installation ==

1. Asegúrate de tener **Flavor Platform 3.0** instalado y activado
2. Sube la carpeta `flavor-network-communities` a `/wp-content/plugins/`
3. Activa el plugin desde el menú 'Plugins' en WordPress
4. El addon se activará automáticamente en Flavor Platform > Addons
5. Ve a "Red de Comunidades" en el menú lateral para configurar tu nodo

== Configuración Inicial ==

**Para el Primer Nodo (Nodo Raíz)**

1. Ve a **Red de Comunidades > Configuración**
2. Marca "Este es el nodo raíz de la red"
3. Configura el nombre de tu red
4. Activa los módulos que necesites
5. Guarda los cambios

**Para Nodos Secundarios**

1. Ve a **Red de Comunidades > Configuración**
2. Introduce la URL del nodo raíz
3. Genera una clave de conexión
4. Solicita que el nodo raíz apruebe tu conexión
5. Una vez aprobado, ya formas parte de la red

== Frequently Asked Questions ==

= ¿Necesito WordPress Multisite? =

No, Network Communities NO es WordPress Multisite. Cada nodo es una instalación independiente de WordPress con Flavor Platform.

= ¿Cuántos nodos puedo conectar? =

No hay límite técnico. Puedes conectar tantos nodos como necesites.

= ¿Los nodos deben estar en el mismo servidor? =

No, cada nodo puede estar en servidores diferentes, incluso en países distintos.

= ¿Es segura la comunicación entre nodos? =

Sí, toda la comunicación se realiza mediante API REST con autenticación, claves secretas y verificación de firmas.

= ¿Qué datos se comparten entre nodos? =

Solo los datos que configures. Por defecto:
* Eventos públicos
* Catálogo de productos/servicios
* Tablón de avisos
* Perfil público del nodo

Nunca se comparten usuarios, contraseñas o datos privados.

= ¿Puedo desconectar un nodo de la red? =

Sí, en cualquier momento desde la configuración. Los datos locales se mantienen.

= ¿Funciona con otros plugins de WordPress? =

Sí, Network Communities es compatible con la mayoría de plugins. Requiere Flavor Platform como base.

= ¿Puedo personalizar qué módulos usar? =

Sí, cada módulo se activa/desactiva independientemente desde la configuración.

== Screenshots ==

1. Panel de administración de la red
2. Directorio global de nodos
3. Mapa interactivo de la red
4. Configuración de módulos
5. API REST y webhooks
6. Gestión de conexiones

== Changelog ==

= 1.0.0 - 2025-02-04 =
* Lanzamiento inicial del addon
* Extraído del core de Flavor Platform
* Sistema de nodos y conexiones
* 10 módulos de red disponibles
* API REST completa
* Shortcodes para frontend
* Panel de administración
* Templates responsive

== Upgrade Notice ==

= 1.0.0 =
Primera versión del addon independiente. Si actualizas desde Flavor Platform 2.x, Network Communities ahora es un addon separado que debes activar.

== Módulos Disponibles ==

* **Perfil Público** - Información básica del nodo
* **Directorio** - Listado de todos los nodos
* **Mapa** - Visualización geográfica
* **Tablón** - Avisos y noticias
* **Eventos** - Calendario global
* **Alertas** - Notificaciones importantes
* **Catálogo** - Productos y servicios
* **Colaboraciones** - Proyectos conjuntos
* **Ofertas de Tiempo** - Banco de tiempo
* **Preguntas** - Foro de consultas

Cada módulo se activa/desactiva de forma independiente.

== Shortcodes ==

```
[flavor_network_directory]          - Directorio de nodos
[flavor_network_map]                 - Mapa de la red
[flavor_network_board]               - Tablón de avisos
[flavor_network_events]              - Calendario de eventos
[flavor_network_alerts]              - Alertas importantes
[flavor_network_catalog]             - Catálogo global
[flavor_network_collaborations]      - Proyectos colaborativos
[flavor_network_time_offers]         - Ofertas de banco de tiempo
[flavor_network_node_profile id=""]  - Perfil de un nodo
[flavor_network_questions]           - Preguntas de la red
```

== API REST ==

Endpoints disponibles en `/wp-json/flavor-network/v1/`:

* `/nodes` - Lista de nodos
* `/events` - Eventos globales
* `/catalog` - Catálogo compartido
* `/board` - Tablón de avisos
* `/alerts` - Alertas
* `/collaborations` - Colaboraciones
* `/time-offers` - Ofertas de tiempo
* `/questions` - Preguntas

Documentación completa: https://gailu.net/docs/network-api

== Soporte ==

Para soporte y documentación:

* Documentación: https://gailu.net/docs/network-communities
* Issues: https://github.com/gailu-labs/flavor-platform/issues
* Comunidad: https://community.gailu.net

== Créditos ==

Desarrollado por Gailu Labs - https://gailu.net

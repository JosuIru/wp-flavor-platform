# Flavor Platform
## Plataforma Digital para Ecosistemas de Emprendimiento Local

---

**Presentación para la organización**
*Proyecto EDLP - Emprendimiento en la comarca*

---

## Quiénes Somos

Desarrollamos **Flavor Platform**, una plataforma integral diseñada para digitalizar y potenciar comunidades locales, cooperativas y territorios rurales.

Nuestro enfoque combina:
- **Tecnología accesible** (WordPress, apps móviles)
- **Módulos especializados** para economía local
- **Filosofía de código abierto** y soberanía digital

---

## El Reto del Emprendimiento Rural

| Problema | Impacto |
|----------|---------|
| Dispersión geográfica | Difícil conectar emprendedores entre sí |
| Falta de visibilidad | Negocios locales invisibles online |
| Acceso a recursos | Formación y financiación fragmentadas |
| Economía extractiva | El dinero sale del territorio |

**Flavor ofrece herramientas digitales para construir un ecosistema de emprendimiento conectado y visible.**

---

## Solución: Módulos para Emprendimiento

### 1. Directorio de Emprendedores y Negocios

- **Perfiles completos** de cada emprendedor/negocio
- **Categorización** por sector, ubicación, servicios
- **Buscador avanzado** para encontrar proveedores locales
- **Mapa interactivo** del tejido empresarial de la comarca

> *"Quiero encontrar un carpintero en Estella" → Búsqueda instantánea*

---

### 2. Marketplace Local

- **Compra-venta** entre emprendedores y ciudadanos
- **Tipos**: Productos, servicios, segunda mano, alquiler
- **Categorías personalizables** según el territorio
- **Sistema de valoraciones** y confianza

> *Alternativa local a Wallapop/Amazon que mantiene el dinero en el territorio*

---

### 3. Banco de Tiempo

- **Intercambio de servicios** sin dinero
- **1 hora dada = 1 hora recibida**
- **Fomenta colaboración** entre emprendedores
- **Ideal para startups** con poco capital

> *Un diseñador hace un logo a cambio de asesoría fiscal*

---

### 4. Grupos de Consumo

- **Pedidos colectivos** a productores locales
- **Ciclos de compra** organizados
- **Reduce intermediarios**
- **Conecta productores** con consumidores

> *Perfecto para productores agroalimentarios de la zona*

---

### 5. Formación y Talleres

- **Catálogo de cursos** y talleres
- **Inscripciones online** con aforo
- **Certificados digitales**
- **Formadores locales** pueden ofrecer sus cursos

> *Talleres de marketing digital, contabilidad, oficios...*

---

### 6. Espacio de Networking

- **Red social interna** para emprendedores
- **Grupos temáticos** (sector, intereses)
- **Chat privado** y grupal
- **Tablón de oportunidades** y colaboraciones

> *LinkedIn local y cercano*

---

### 7. Recursos Compartidos

- **Reserva de espacios** (coworking, salas)
- **Préstamo de equipamiento**
- **Vehículos compartidos** (carpooling)
- **Optimiza recursos** de la comarca

---

### 8. Financiación Colectiva

- **Microcréditos comunitarios** (en desarrollo)
- **Crowdfunding local** para proyectos
- **Fondo solidario** entre emprendedores

---

### 9. Contabilidad y Gestión Económica

- **Libro contable** integrado con todos los módulos
- **Desglose fiscal** automático
- **Comparativas** mensuales y anuales
- **Integración nativa** con facturación, cuotas de socios, marketplace

> *Gestión económica transparente sin herramientas externas*

---

## Impacto Social y Sostenibilidad

### Sello de Conciencia - Medición de Impacto

Flavor Platform incluye un **sistema de evaluación automática** que mide el impacto social y ético de cada implementación según las **5 premisas de una economía consciente**:

| Premisa | Descripción | Indicadores |
|---------|-------------|-------------|
| **La conciencia es fundamental** | Reconocer la dignidad de todas las personas | Participación activa, autonomía, bienestar |
| **La abundancia es organizable** | Distribución equitativa de recursos | Acceso a recursos, economía colaborativa |
| **La interdependencia es radical** | Cooperación como valor central | Redes de apoyo, acción colectiva |
| **La madurez es cíclica** | Respetar límites y ciclos naturales | Sostenibilidad, suficiencia, largo plazo |
| **El valor es intrínseco** | Las cosas valen por lo que son | Intercambios no monetarios, reconocimiento |

### Niveles de Certificación

- **Básico** (1-25 puntos): Cumple requisitos mínimos
- **En Transición** (26-50): Integración progresiva
- **Consciente** (51-75): Aplicación ética activa
- **Referente** (76-100): Ejemplo de economía consciente

### Alineación con Estándares RSC/ESG

El Sello de Conciencia está diseñado para alinearse con marcos internacionales:

| Estándar | Alineación con Flavor |
|----------|----------------------|
| **ODS (Agenda 2030)** | Contribución directa a ODS 8, 10, 11, 12, 13, 17 |
| **ESG** (Environmental, Social, Governance) | Evaluación automática de las 3 dimensiones |
| **RSC** (Responsabilidad Social Corporativa) | Métricas de impacto social integradas |
| **Economía del Bien Común** | Puntuación alineada con la Matriz del Bien Común |
| **B Corp** | Indicadores comparables para certificación |

### Informes de Impacto

La plataforma genera automáticamente:
- **Dashboard de impacto** con métricas en tiempo real
- **Informes periódicos** de contribución social
- **Certificado digital** del nivel de conciencia
- **Datos exportables** para memorias de sostenibilidad

> *Ideal para organizaciones que necesitan reportar impacto ante financiadores, socios o administraciones públicas.*

---

## Arquitectura Técnica

```
┌─────────────────────────────────────────────────────┐
│                  FLAVOR PLATFORM                     │
├─────────────────────────────────────────────────────┤
│                                                      │
│   ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ │
│   │   Portal    │  │    Admin    │  │  App Móvil  │ │
│   │    Web      │  │   Panel     │  │   Flutter   │ │
│   └─────────────┘  └─────────────┘  └─────────────┘ │
│                                                      │
│   ┌─────────────────────────────────────────────┐   │
│   │              MÓDULOS ACTIVOS                 │   │
│   │  Marketplace │ Banco Tiempo │ Formación     │   │
│   │  Directorio  │ Red Social   │ Recursos      │   │
│   │  Grupos      │ Chat         │ Eventos       │   │
│   └─────────────────────────────────────────────┘   │
│                                                      │
│   ┌─────────────────────────────────────────────┐   │
│   │              WORDPRESS + API                 │   │
│   └─────────────────────────────────────────────┘   │
│                                                      │
└─────────────────────────────────────────────────────┘
```

**Ventajas técnicas:**
- Basado en **WordPress** (fácil mantenimiento)
- **Código abierto** (sin dependencia de proveedor)
- **App móvil** nativa para iOS y Android
- **Escalable** (de 100 a 10.000 usuarios)
- **RGPD compliant** (datos en servidor propio)

---

## Caso de Uso: Emprendimiento en la comarca

### Escenario 1: Nuevo Emprendedor

1. **María** abre una tienda de cosmética natural en Estella
2. Se registra en la plataforma → aparece en el **directorio**
3. Publica sus productos en el **marketplace**
4. Ofrece talleres de cosmética → **módulo formación**
5. Intercambia servicios con un diseñador → **banco de tiempo**
6. Conecta con productores de aceite local → **grupos de consumo**

### Escenario 2: Colaboración Territorial

1. **5 productores** agroalimentarios se organizan
2. Crean un **grupo de consumo** conjunto
3. Comparten **transporte** para entregas (carpooling)
4. Organizan **mercados** mensuales (eventos)
5. Ofrecen **formación** sobre agricultura ecológica

---

## Panel de Administración

la organización tendría acceso a:

- **Dashboard** con métricas del ecosistema
- **Gestión de usuarios** y emprendedores
- **Moderación** de contenido
- **Informes** de actividad (útil para justificación EDLP)
- **Configuración** de módulos activos

---

## Propuesta Económica Orientativa

### Opción A: Implementación Completa

| Concepto | Inversión |
|----------|-----------|
| Licencia Flavor Platform | 8.000 € |
| Personalización y branding | 4.000 € |
| Configuración de módulos | 3.000 € |
| Formación administradores (8h) | 1.200 € |
| Migración/importación datos | 1.500 € |
| **Total implementación** | **17.700 €** |

| Mantenimiento anual | |
|---------------------|-----------|
| Soporte técnico + actualizaciones | 2.400 €/año |
| Hosting gestionado (opcional) | 1.200 €/año |

---

### Opción B: Implementación Básica

| Concepto | Inversión |
|----------|-----------|
| Licencia Flavor Platform | 8.000 € |
| Configuración básica (4 módulos) | 2.000 € |
| Formación (4h) | 600 € |
| **Total** | **10.600 €** |

---

### Opción C: Desarrollo a Medida Ampliado

| Concepto | Inversión |
|----------|-----------|
| Todo lo de Opción A | 17.700 € |
| App móvil personalizada | 8.000 € |
| Módulos adicionales específicos | 5.000 - 15.000 € |
| Integraciones (CRM, email, etc.) | 2.000 - 5.000 € |
| **Total** | **32.700 - 45.700 €** |

---

## Compatibilidad con EDLP

Este proyecto encaja en las líneas de financiación EDLP:

✅ **Digitalización** del territorio rural
✅ **Emprendimiento** y creación de empresas
✅ **Economía circular** y local
✅ **Innovación social**
✅ **Vertebración territorial**

*La plataforma genera métricas de impacto fáciles de reportar.*

---

## Cronograma Estimado

| Fase | Duración | Entregables |
|------|----------|-------------|
| **1. Análisis** | 2 semanas | Requisitos detallados |
| **2. Configuración** | 3 semanas | Plataforma operativa |
| **3. Personalización** | 2 semanas | Branding, ajustes |
| **4. Formación** | 1 semana | Admins capacitados |
| **5. Piloto** | 4 semanas | Prueba con usuarios reales |
| **6. Lanzamiento** | 1 semana | Apertura pública |

**Total: 3 meses aproximadamente**

---

## Próximos Pasos

1. **Reunión de análisis** - Detallar necesidades específicas
2. **Demo personalizada** - Mostrar módulos relevantes
3. **Propuesta detallada** - Presupuesto ajustado
4. **Planificación** - Calendario y fases

---

## Contacto

**Josu Irurtzun - Gailu Microcoop**

📧 info@gailu.net
📱 690 390 018
🌐 www.gailu.net

---

*Documento preparado para la organización - Marzo 2026*

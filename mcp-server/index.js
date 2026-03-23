#!/usr/bin/env node
/**
 * Flavor VBP MCP Server v2.0
 *
 * Servidor MCP mejorado para integrar Visual Builder Pro con Claude Code.
 * Usa HTTP para comunicarse con WordPress (más fiable que WP-CLI en Local Sites).
 *
 * @version 2.0.0
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
  ListResourcesRequestSchema,
  ReadResourceRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
import fs from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Configuración
const CONFIG = {
  siteUrl: process.env.SITE_URL || 'http://sitio-prueba.local',
  apiKey: process.env.VBP_API_KEY || 'flavor-vbp-2024',
  pluginPath: path.resolve(__dirname, '..'),
  schemaPath: path.resolve(__dirname, '..', 'vbp-schema.json'),
};

/**
 * Cliente HTTP para la API REST de WordPress
 */
class VBPClient {
  constructor(baseUrl, apiKey) {
    this.baseUrl = baseUrl.replace(/\/$/, '');
    this.apiKey = apiKey;
    this.apiEndpoint = `${this.baseUrl}/wp-json/flavor-vbp/v1/claude`;
  }

  async request(endpoint, options = {}) {
    const url = `${this.apiEndpoint}${endpoint}`;
    const headers = {
      'Content-Type': 'application/json',
      'X-VBP-Key': this.apiKey,
      ...options.headers,
    };

    try {
      const response = await fetch(url, {
        ...options,
        headers,
      });

      const data = await response.json();

      if (!response.ok) {
        return { success: false, error: data.message || data.error || 'Error desconocido', status: response.status };
      }

      return { success: true, data };
    } catch (error) {
      return { success: false, error: error.message };
    }
  }

  async getSchema() {
    return this.request('/schema');
  }

  async listBlocks(category = '') {
    const params = category ? `?category=${category}` : '';
    return this.request(`/blocks${params}`);
  }

  async listModules() {
    return this.request('/modules');
  }

  async createPage(title, elements = [], options = {}) {
    return this.request('/pages', {
      method: 'POST',
      body: JSON.stringify({
        title,
        elements,
        template: options.template || '',
        context: options.context || {},
        status: options.status || 'draft',
      }),
    });
  }

  async getPage(postId) {
    return this.request(`/pages/${postId}`);
  }

  async updatePage(postId, updates) {
    return this.request(`/pages/${postId}`, {
      method: 'PUT',
      body: JSON.stringify(updates),
    });
  }

  async addBlock(postId, blockType, data = {}, position = 'end') {
    return this.request(`/pages/${postId}/blocks`, {
      method: 'POST',
      body: JSON.stringify({ type: blockType, data, position }),
    });
  }

  async listPages(status = 'any') {
    return this.request(`/pages?status=${status}`);
  }

  async generateSection(type, context = {}) {
    return this.request('/generate-section', {
      method: 'POST',
      body: JSON.stringify({ type, context }),
    });
  }

  async listTemplates() {
    return this.request('/templates');
  }

  async listSectionTypes() {
    return this.request('/section-types');
  }

  async getBlockPresets(blockType) {
    return this.request(`/blocks/${blockType}/presets`);
  }

  async duplicatePage(postId, newTitle = '') {
    return this.request(`/pages/${postId}/duplicate`, {
      method: 'POST',
      body: JSON.stringify({ title: newTitle }),
    });
  }
}

// Cliente global
const client = new VBPClient(CONFIG.siteUrl, CONFIG.apiKey);

/**
 * Lee el schema de bloques desde archivo local o API
 */
async function getBlocksSchema() {
  // Primero intentar leer archivo local (más rápido)
  try {
    const content = await fs.readFile(CONFIG.schemaPath, 'utf-8');
    return JSON.parse(content);
  } catch {
    // Si no existe, intentar desde API
    const result = await client.getSchema();
    if (result.success) {
      // Guardar para cache
      try {
        await fs.writeFile(CONFIG.schemaPath, JSON.stringify(result.data, null, 2));
      } catch {}
      return result.data;
    }
    return null;
  }
}

/**
 * Genera un ID único para elementos
 */
function generateElementId() {
  return 'el_' + Math.random().toString(36).substring(2, 14);
}

/**
 * Estilos por defecto para elementos VBP
 */
function getDefaultStyles() {
  return {
    spacing: {
      margin: { top: '', right: '', bottom: '', left: '' },
      padding: { top: '', right: '', bottom: '', left: '' },
    },
    colors: { background: '', text: '' },
    typography: {},
    borders: {},
    shadows: {},
    layout: {},
    advanced: { cssId: '', cssClasses: '', customCss: '' },
  };
}

/**
 * Plantillas de secciones predefinidas
 */
const SECTION_TEMPLATES = {
  hero: (context = {}) => ({
    id: generateElementId(),
    type: 'hero',
    name: 'Hero',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || context.topic || 'Título Principal',
      subtitulo: context.subtitulo || `La mejor solución en ${context.industry || 'tu sector'}`,
      boton_texto: context.boton_texto || 'Comenzar ahora',
      boton_url: context.boton_url || '#contacto',
      variante: context.variante || 'centered',
      imagen: context.imagen || '',
      video_url: context.video_url || '',
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  features: (context = {}) => ({
    id: generateElementId(),
    type: 'features',
    name: 'Características',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Por qué elegirnos',
      subtitulo: context.subtitulo || 'Características que nos diferencian',
      columnas: context.columnas || 3,
      variante: context.variante || 'cards',
      features: context.features || [
        { icono: '⚡', titulo: 'Rápido', descripcion: 'Implementación en minutos' },
        { icono: '🔒', titulo: 'Seguro', descripcion: 'Protección de nivel empresarial' },
        { icono: '📱', titulo: 'Accesible', descripcion: 'Desde cualquier dispositivo' },
        { icono: '🎯', titulo: 'Preciso', descripcion: 'Resultados garantizados' },
        { icono: '💡', titulo: 'Innovador', descripcion: 'Tecnología de vanguardia' },
        { icono: '🤝', titulo: 'Soporte', descripcion: 'Ayuda cuando la necesites' },
      ],
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  cta: (context = {}) => ({
    id: generateElementId(),
    type: 'cta',
    name: 'Call to Action',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || '¿Listo para empezar?',
      subtitulo: context.subtitulo || 'Únete a miles de usuarios satisfechos',
      boton_texto: context.boton_texto || 'Contactar ahora',
      boton_url: context.boton_url || '#contacto',
      boton_secundario_texto: context.boton_secundario_texto || '',
      boton_secundario_url: context.boton_secundario_url || '',
      variante: context.variante || 'centered',
      fondo: context.fondo || 'gradient',
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  testimonials: (context = {}) => ({
    id: generateElementId(),
    type: 'testimonials',
    name: 'Testimonios',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Lo que dicen nuestros clientes',
      subtitulo: context.subtitulo || '',
      variante: context.variante || 'cards',
      testimonios: context.testimonios || [
        {
          texto: 'Excelente servicio, ha transformado nuestra forma de trabajar.',
          autor: 'María García',
          cargo: 'CEO, TechCorp',
          avatar: '',
          rating: 5,
        },
        {
          texto: 'La mejor inversión que hemos hecho este año.',
          autor: 'Carlos López',
          cargo: 'Director, InnovaCo',
          avatar: '',
          rating: 5,
        },
        {
          texto: 'Soporte increíble y resultados inmediatos.',
          autor: 'Ana Martínez',
          cargo: 'Fundadora, StartupXYZ',
          avatar: '',
          rating: 5,
        },
      ],
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  faq: (context = {}) => ({
    id: generateElementId(),
    type: 'faq',
    name: 'FAQ',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Preguntas frecuentes',
      subtitulo: context.subtitulo || 'Resolvemos tus dudas',
      variante: context.variante || 'accordion',
      faqs: context.faqs || [
        { pregunta: '¿Cómo empiezo?', respuesta: 'Es muy sencillo, solo necesitas registrarte y seguir el asistente de configuración.' },
        { pregunta: '¿Tiene soporte técnico?', respuesta: 'Sí, ofrecemos soporte 24/7 por chat, email y teléfono.' },
        { pregunta: '¿Puedo cancelar en cualquier momento?', respuesta: 'Por supuesto, no hay permanencia. Puedes cancelar cuando quieras.' },
        { pregunta: '¿Hay periodo de prueba?', respuesta: 'Sí, ofrecemos 14 días de prueba gratuita sin compromiso.' },
      ],
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  pricing: (context = {}) => ({
    id: generateElementId(),
    type: 'pricing',
    name: 'Precios',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Planes y precios',
      subtitulo: context.subtitulo || 'Elige el plan que mejor se adapte a tus necesidades',
      variante: context.variante || 'cards',
      mostrar_anual: context.mostrar_anual !== false,
      planes: context.planes || [
        {
          nombre: 'Básico',
          precio: '9',
          periodo: 'mes',
          descripcion: 'Para empezar',
          caracteristicas: ['5 usuarios', '10GB almacenamiento', 'Soporte email'],
          destacado: false,
          boton_texto: 'Empezar',
        },
        {
          nombre: 'Pro',
          precio: '29',
          periodo: 'mes',
          descripcion: 'Para equipos',
          caracteristicas: ['25 usuarios', '100GB almacenamiento', 'Soporte prioritario', 'Integraciones'],
          destacado: true,
          boton_texto: 'Elegir Pro',
        },
        {
          nombre: 'Enterprise',
          precio: '99',
          periodo: 'mes',
          descripcion: 'Para empresas',
          caracteristicas: ['Usuarios ilimitados', 'Almacenamiento ilimitado', 'Soporte 24/7', 'API completa', 'SLA garantizado'],
          destacado: false,
          boton_texto: 'Contactar',
        },
      ],
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  team: (context = {}) => ({
    id: generateElementId(),
    type: 'team',
    name: 'Equipo',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Nuestro equipo',
      subtitulo: context.subtitulo || 'Las personas detrás del proyecto',
      columnas: context.columnas || 4,
      miembros: context.miembros || [
        { nombre: 'Ana Martínez', cargo: 'CEO & Fundadora', foto: '', bio: '', linkedin: '', twitter: '' },
        { nombre: 'Carlos López', cargo: 'CTO', foto: '', bio: '', linkedin: '', twitter: '' },
        { nombre: 'María García', cargo: 'Directora de Producto', foto: '', bio: '', linkedin: '', twitter: '' },
        { nombre: 'Pedro Sánchez', cargo: 'Lead Developer', foto: '', bio: '', linkedin: '', twitter: '' },
      ],
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  contact: (context = {}) => ({
    id: generateElementId(),
    type: 'contact',
    name: 'Contacto',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Contacta con nosotros',
      subtitulo: context.subtitulo || 'Estamos aquí para ayudarte',
      mostrar_formulario: context.mostrar_formulario !== false,
      mostrar_mapa: context.mostrar_mapa || false,
      mostrar_info: context.mostrar_info !== false,
      email: context.email || 'info@ejemplo.com',
      telefono: context.telefono || '+34 600 000 000',
      direccion: context.direccion || '',
      horario: context.horario || 'Lun-Vie: 9:00 - 18:00',
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  stats: (context = {}) => ({
    id: generateElementId(),
    type: 'stats',
    name: 'Estadísticas',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Números que hablan',
      subtitulo: context.subtitulo || '',
      variante: context.variante || 'horizontal',
      stats: context.stats || [
        { numero: '10K+', label: 'Usuarios activos', icono: '👥' },
        { numero: '99.9%', label: 'Uptime', icono: '⚡' },
        { numero: '24/7', label: 'Soporte', icono: '🎧' },
        { numero: '50+', label: 'Países', icono: '🌍' },
      ],
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  gallery: (context = {}) => ({
    id: generateElementId(),
    type: 'gallery',
    name: 'Galería',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Galería',
      subtitulo: context.subtitulo || '',
      columnas: context.columnas || 3,
      imagenes: context.imagenes || [],
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  text: (context = {}) => ({
    id: generateElementId(),
    type: 'text',
    name: 'Texto',
    visible: true,
    locked: false,
    data: {
      contenido: context.contenido || '<p>Tu contenido aquí...</p>',
      alineacion: context.alineacion || 'left',
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  // Secciones especiales para módulos Flavor
  module_grupos_consumo: (context = {}) => ({
    id: generateElementId(),
    type: 'module_grupos_consumo_listado',
    name: 'Listado Grupos de Consumo',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Grupos de Consumo',
      mostrar_mapa: context.mostrar_mapa || true,
      limite: context.limite || 12,
      columnas: context.columnas || 3,
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  module_eventos: (context = {}) => ({
    id: generateElementId(),
    type: 'module_eventos_proximos',
    name: 'Próximos Eventos',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Próximos Eventos',
      limite: context.limite || 6,
      mostrar_calendario: context.mostrar_calendario || false,
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  module_marketplace: (context = {}) => ({
    id: generateElementId(),
    type: 'module_marketplace_productos',
    name: 'Productos Marketplace',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Productos Destacados',
      limite: context.limite || 8,
      columnas: context.columnas || 4,
      mostrar_filtros: context.mostrar_filtros || true,
    },
    styles: getDefaultStyles(),
    children: [],
  }),

  module_cursos: (context = {}) => ({
    id: generateElementId(),
    type: 'module_cursos_catalogo',
    name: 'Catálogo de Cursos',
    visible: true,
    locked: false,
    data: {
      titulo: context.titulo || 'Nuestros Cursos',
      limite: context.limite || 6,
      mostrar_categorias: context.mostrar_categorias || true,
    },
    styles: getDefaultStyles(),
    children: [],
  }),
};

/**
 * Plantillas de página completas
 */
const PAGE_TEMPLATES = {
  'landing-basica': {
    name: 'Landing Básica',
    description: 'Hero + Características + CTA',
    sections: ['hero', 'features', 'cta'],
  },
  'landing-completa': {
    name: 'Landing Completa',
    description: 'Landing con todas las secciones típicas',
    sections: ['hero', 'features', 'stats', 'testimonials', 'pricing', 'faq', 'cta'],
  },
  'landing-producto': {
    name: 'Landing de Producto',
    description: 'Para presentar un producto o servicio',
    sections: ['hero', 'features', 'gallery', 'testimonials', 'pricing', 'cta'],
  },
  'landing-startup': {
    name: 'Landing Startup',
    description: 'Para startups y nuevos proyectos',
    sections: ['hero', 'features', 'stats', 'team', 'testimonials', 'cta'],
  },
  'grupos-consumo': {
    name: 'Grupos de Consumo',
    description: 'Landing para módulo de grupos de consumo',
    sections: ['hero', 'module_grupos_consumo', 'features', 'testimonials', 'cta'],
  },
  'eventos': {
    name: 'Eventos',
    description: 'Landing para módulo de eventos',
    sections: ['hero', 'module_eventos', 'features', 'cta'],
  },
  'marketplace': {
    name: 'Marketplace',
    description: 'Landing para marketplace',
    sections: ['hero', 'module_marketplace', 'features', 'testimonials', 'cta'],
  },
  'cursos': {
    name: 'Plataforma de Cursos',
    description: 'Landing para módulo de cursos',
    sections: ['hero', 'module_cursos', 'features', 'testimonials', 'pricing', 'cta'],
  },
  'comunidad': {
    name: 'Comunidad',
    description: 'Para comunidades y colectivos',
    sections: ['hero', 'features', 'stats', 'team', 'faq', 'contact'],
  },
};

/**
 * Genera elementos para una plantilla
 */
function generateTemplateElements(templateName, context = {}) {
  const template = PAGE_TEMPLATES[templateName];
  if (!template) return [];

  return template.sections.map(sectionType => {
    const generator = SECTION_TEMPLATES[sectionType];
    if (generator) {
      return generator(context);
    }
    return null;
  }).filter(Boolean);
}

// Crear servidor MCP
const server = new Server(
  {
    name: 'flavor-vbp',
    version: '2.0.0',
  },
  {
    capabilities: {
      tools: {},
      resources: {},
    },
  }
);

// Definir herramientas
const TOOLS = [
  {
    name: 'vbp_list_blocks',
    description: 'Lista todos los bloques/secciones disponibles en Visual Builder Pro, organizados por categoría.',
    inputSchema: {
      type: 'object',
      properties: {
        category: {
          type: 'string',
          description: 'Filtrar por categoría: sections, basic, layout, forms, media, modules, widgets',
        },
      },
    },
  },
  {
    name: 'vbp_get_block_schema',
    description: 'Obtiene el schema detallado de un tipo de bloque específico, con todos sus campos y opciones.',
    inputSchema: {
      type: 'object',
      properties: {
        blockType: {
          type: 'string',
          description: 'Tipo de bloque (ej: hero, features, cta, pricing, module_grupos_consumo_listado)',
        },
      },
      required: ['blockType'],
    },
  },
  {
    name: 'vbp_create_page',
    description: 'Crea una nueva página VBP. Puedes usar una plantilla predefinida o proporcionar elementos personalizados.',
    inputSchema: {
      type: 'object',
      properties: {
        title: {
          type: 'string',
          description: 'Título de la página',
        },
        template: {
          type: 'string',
          description: 'Plantilla predefinida: landing-basica, landing-completa, landing-producto, landing-startup, grupos-consumo, eventos, marketplace, cursos, comunidad',
        },
        elements: {
          type: 'array',
          description: 'Array de elementos VBP personalizados (si no usas template)',
        },
        context: {
          type: 'object',
          description: 'Contexto para personalizar la plantilla: { topic, industry, titulo, subtitulo, ... }',
        },
        status: {
          type: 'string',
          enum: ['draft', 'publish'],
          default: 'draft',
        },
      },
      required: ['title'],
    },
  },
  {
    name: 'vbp_generate_section',
    description: 'Genera una sección VBP con contenido predefinido personalizable.',
    inputSchema: {
      type: 'object',
      properties: {
        sectionType: {
          type: 'string',
          enum: ['hero', 'features', 'cta', 'testimonials', 'faq', 'pricing', 'team', 'contact', 'stats', 'gallery', 'text', 'module_grupos_consumo', 'module_eventos', 'module_marketplace', 'module_cursos'],
          description: 'Tipo de sección a generar',
        },
        context: {
          type: 'object',
          description: 'Contexto para personalizar: { titulo, subtitulo, topic, industry, features, testimonios, faqs, planes, ... }',
        },
      },
      required: ['sectionType'],
    },
  },
  {
    name: 'vbp_add_block',
    description: 'Añade un bloque/sección a una página VBP existente.',
    inputSchema: {
      type: 'object',
      properties: {
        postId: {
          type: 'number',
          description: 'ID de la página VBP',
        },
        blockType: {
          type: 'string',
          description: 'Tipo de bloque a añadir',
        },
        data: {
          type: 'object',
          description: 'Datos de configuración del bloque',
        },
        position: {
          type: 'string',
          description: 'Posición: start, end, o número de índice',
          default: 'end',
        },
      },
      required: ['postId', 'blockType'],
    },
  },
  {
    name: 'vbp_get_page',
    description: 'Obtiene la información completa de una página VBP, incluyendo todos sus elementos.',
    inputSchema: {
      type: 'object',
      properties: {
        postId: {
          type: 'number',
          description: 'ID de la página VBP',
        },
      },
      required: ['postId'],
    },
  },
  {
    name: 'vbp_update_page',
    description: 'Actualiza una página VBP existente (título, elementos, estado).',
    inputSchema: {
      type: 'object',
      properties: {
        postId: {
          type: 'number',
          description: 'ID de la página VBP',
        },
        title: {
          type: 'string',
          description: 'Nuevo título',
        },
        elements: {
          type: 'array',
          description: 'Nuevos elementos (reemplaza los existentes)',
        },
        status: {
          type: 'string',
          enum: ['draft', 'publish'],
        },
      },
      required: ['postId'],
    },
  },
  {
    name: 'vbp_list_pages',
    description: 'Lista todas las páginas VBP existentes.',
    inputSchema: {
      type: 'object',
      properties: {
        status: {
          type: 'string',
          enum: ['any', 'draft', 'publish'],
          default: 'any',
        },
      },
    },
  },
  {
    name: 'vbp_list_modules',
    description: 'Lista los módulos Flavor activos y sus widgets disponibles para insertar en páginas.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'vbp_list_templates',
    description: 'Lista las plantillas de página predefinidas disponibles con sus secciones e industrias.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'vbp_list_section_types',
    description: 'Lista todos los tipos de sección que se pueden generar (hero, features, pricing, etc.).',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'vbp_get_block_presets',
    description: 'Obtiene los presets predefinidos de un bloque (diseños preconfiguridos).',
    inputSchema: {
      type: 'object',
      properties: {
        blockType: {
          type: 'string',
          description: 'Tipo de bloque (ej: hero, pricing, features)',
        },
      },
      required: ['blockType'],
    },
  },
  {
    name: 'vbp_duplicate_page',
    description: 'Duplica una página VBP existente.',
    inputSchema: {
      type: 'object',
      properties: {
        postId: {
          type: 'number',
          description: 'ID de la página a duplicar',
        },
        title: {
          type: 'string',
          description: 'Título para la nueva página (opcional, por defecto añade "(copia)")',
        },
      },
      required: ['postId'],
    },
  },
];

// Handler para listar herramientas
server.setRequestHandler(ListToolsRequestSchema, async () => {
  return { tools: TOOLS };
});

// Handler para ejecutar herramientas
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case 'vbp_list_blocks': {
        const result = await client.listBlocks(args?.category || '');
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        // Fallback: usar schema local
        const schema = await getBlocksSchema();
        if (schema?.blocks) {
          const blocks = Object.values(schema.blocks).map(b => ({
            type: b.type,
            name: b.name,
            category: b.category,
            description: b.description,
          }));
          return { content: [{ type: 'text', text: JSON.stringify(blocks, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Error: ${result.error}` }], isError: true };
      }

      case 'vbp_get_block_schema': {
        const schema = await getBlocksSchema();
        if (schema?.blocks?.[args.blockType]) {
          return { content: [{ type: 'text', text: JSON.stringify(schema.blocks[args.blockType], null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Bloque no encontrado: ${args.blockType}` }], isError: true };
      }

      case 'vbp_create_page': {
        let elements = args.elements || [];
        const context = args.context || {};

        // Usar título como topic si no se especifica
        if (!context.topic && args.title) {
          context.topic = args.title;
        }

        // Si hay plantilla, generar elementos localmente O usar la API
        if (args.template && elements.length === 0) {
          // Preferir usar la API que tiene más contenido por industria
          const result = await client.createPage(args.title, [], {
            template: args.template,
            context: context,
            status: args.status || 'draft',
          });

          if (result.success) {
            return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
          }

          // Fallback: generar elementos localmente
          elements = generateTemplateElements(args.template, context);
        }

        const result = await client.createPage(args.title, elements, {
          context: context,
          status: args.status || 'draft',
        });

        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Error: ${result.error}` }], isError: true };
      }

      case 'vbp_generate_section': {
        const generator = SECTION_TEMPLATES[args.sectionType];
        if (generator) {
          const section = generator(args.context || {});
          return { content: [{ type: 'text', text: JSON.stringify(section, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Tipo de sección no válido: ${args.sectionType}` }], isError: true };
      }

      case 'vbp_add_block': {
        const result = await client.addBlock(args.postId, args.blockType, args.data || {}, args.position || 'end');
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Error: ${result.error}` }], isError: true };
      }

      case 'vbp_get_page': {
        const result = await client.getPage(args.postId);
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Error: ${result.error}` }], isError: true };
      }

      case 'vbp_update_page': {
        const updates = {};
        if (args.title) updates.title = args.title;
        if (args.elements) updates.elements = args.elements;
        if (args.status) updates.status = args.status;

        const result = await client.updatePage(args.postId, updates);
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Error: ${result.error}` }], isError: true };
      }

      case 'vbp_list_pages': {
        const result = await client.listPages(args?.status || 'any');
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Error: ${result.error}` }], isError: true };
      }

      case 'vbp_list_modules': {
        const result = await client.listModules();
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Error: ${result.error}` }], isError: true };
      }

      case 'vbp_list_templates': {
        // Intentar obtener desde API (más completo)
        const result = await client.listTemplates();
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        // Fallback a templates locales
        return { content: [{ type: 'text', text: JSON.stringify(PAGE_TEMPLATES, null, 2) }] };
      }

      case 'vbp_list_section_types': {
        const result = await client.listSectionTypes();
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        // Fallback a tipos locales
        const localTypes = Object.keys(SECTION_TEMPLATES).map(type => ({
          type,
          name: type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' '),
          category: type.startsWith('module_') ? 'modules' : 'sections',
        }));
        return { content: [{ type: 'text', text: JSON.stringify(localTypes, null, 2) }] };
      }

      case 'vbp_get_block_presets': {
        const result = await client.getBlockPresets(args.blockType);
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Error: ${result.error}` }], isError: true };
      }

      case 'vbp_duplicate_page': {
        const result = await client.duplicatePage(args.postId, args.title || '');
        if (result.success) {
          return { content: [{ type: 'text', text: JSON.stringify(result.data, null, 2) }] };
        }
        return { content: [{ type: 'text', text: `Error: ${result.error}` }], isError: true };
      }

      default:
        return { content: [{ type: 'text', text: `Herramienta desconocida: ${name}` }], isError: true };
    }
  } catch (error) {
    return { content: [{ type: 'text', text: `Error: ${error.message}` }], isError: true };
  }
});

// Handler para listar recursos
server.setRequestHandler(ListResourcesRequestSchema, async () => {
  return {
    resources: [
      {
        uri: 'vbp://schema/blocks',
        name: 'VBP Blocks Schema',
        description: 'Schema completo de todos los bloques disponibles',
        mimeType: 'application/json',
      },
      {
        uri: 'vbp://templates/pages',
        name: 'VBP Page Templates',
        description: 'Plantillas de página predefinidas',
        mimeType: 'application/json',
      },
      {
        uri: 'vbp://templates/sections',
        name: 'VBP Section Templates',
        description: 'Tipos de secciones disponibles',
        mimeType: 'application/json',
      },
    ],
  };
});

// Handler para leer recursos
server.setRequestHandler(ReadResourceRequestSchema, async (request) => {
  const { uri } = request.params;

  if (uri === 'vbp://schema/blocks') {
    const schema = await getBlocksSchema();
    return {
      contents: [{
        uri,
        mimeType: 'application/json',
        text: JSON.stringify(schema, null, 2),
      }],
    };
  }

  if (uri === 'vbp://templates/pages') {
    return {
      contents: [{
        uri,
        mimeType: 'application/json',
        text: JSON.stringify(PAGE_TEMPLATES, null, 2),
      }],
    };
  }

  if (uri === 'vbp://templates/sections') {
    const sectionTypes = Object.keys(SECTION_TEMPLATES);
    return {
      contents: [{
        uri,
        mimeType: 'application/json',
        text: JSON.stringify(sectionTypes, null, 2),
      }],
    };
  }

  throw new Error(`Recurso no encontrado: ${uri}`);
});

// Iniciar servidor
async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error('Flavor VBP MCP Server v2.0 iniciado');
  console.error(`Conectando a: ${CONFIG.siteUrl}`);
}

main().catch(console.error);

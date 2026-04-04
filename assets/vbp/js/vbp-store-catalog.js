/**
 * Visual Builder Pro - Store Catalog
 *
 * Catálogos puros para nombres, defaults, estilos y variantes.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPStoreCatalog = {
    getDefaultName: function(type) {
        var nombres = {
            hero: 'Hero',
            features: 'Características',
            testimonials: 'Testimonios',
            pricing: 'Precios',
            cta: 'CTA',
            faq: 'FAQ',
            contact: 'Contacto',
            team: 'Equipo',
            stats: 'Estadísticas',
            gallery: 'Galería',
            blog: 'Blog',
            'video-section': 'Video',
            heading: 'Encabezado',
            text: 'Texto',
            image: 'Imagen',
            button: 'Botón',
            divider: 'Separador',
            spacer: 'Espaciador',
            icon: 'Icono',
            html: 'HTML',
            shortcode: 'Shortcode',
            container: 'Contenedor',
            columns: 'Columnas',
            row: 'Fila',
            grid: 'Grid',
            form: 'Formulario',
            input: 'Campo de texto',
            textarea: 'Área de texto',
            select: 'Selector',
            checkbox: 'Checkbox',
            'video-embed': 'Video Embed',
            audio: 'Audio',
            map: 'Mapa',
            mapa: 'Mapa',
            embed: 'Embed',
            countdown: 'Cuenta Regresiva',
            'social-icons': 'Iconos Sociales',
            newsletter: 'Newsletter',
            'logo-grid': 'Grid de Logos',
            'icon-box': 'Caja de Icono',
            accordion: 'Acordeón',
            tabs: 'Pestañas',
            'progress-bar': 'Barra de Progreso',
            alert: 'Alerta',
            'before-after': 'Antes/Después'
        };
        return nombres[type] || type;
    },

    getDefaultData: function(type) {
        var defaults = {
            hero: {
                titulo: 'Título Principal',
                subtitulo: 'Subtítulo descriptivo que explica el valor de tu propuesta',
                boton_texto: 'Comenzar ahora',
                boton_url: '#',
                imagen_fondo: ''
            },
            features: {
                titulo: 'Nuestras Características',
                items: [
                    { icono: '⚡', titulo: 'Rápido', descripcion: 'Implementación en minutos' },
                    { icono: '🔒', titulo: 'Seguro', descripcion: 'Protección de datos garantizada' },
                    { icono: '📱', titulo: 'Responsive', descripcion: 'Funciona en todos los dispositivos' }
                ]
            },
            testimonials: {
                titulo: 'Lo que dicen nuestros clientes',
                items: [
                    { texto: 'Excelente servicio, muy recomendado. Ha superado todas nuestras expectativas.', autor: 'María García', cargo: 'CEO, Empresa X' }
                ]
            },
            pricing: {
                titulo: 'Planes y Precios',
                subtitulo: 'Elige el plan que mejor se adapte a tus necesidades',
                items: [
                    { nombre: 'Básico', precio: '9', periodo: '/mes', caracteristicas: ['5 usuarios', '10GB almacenamiento', 'Soporte email'], destacado: false },
                    { nombre: 'Pro', precio: '29', periodo: '/mes', caracteristicas: ['25 usuarios', '100GB almacenamiento', 'Soporte prioritario'], destacado: true },
                    { nombre: 'Enterprise', precio: '99', periodo: '/mes', caracteristicas: ['Usuarios ilimitados', '1TB almacenamiento', 'Soporte 24/7'], destacado: false }
                ]
            },
            cta: {
                titulo: '¿Listo para empezar?',
                subtitulo: 'Únete a miles de usuarios que ya confían en nosotros',
                boton_texto: 'Empezar gratis',
                boton_url: '#'
            },
            faq: {
                titulo: 'Preguntas Frecuentes',
                items: [
                    { pregunta: '¿Cómo funciona?', respuesta: 'Es muy sencillo, solo tienes que registrarte y empezar a usar la plataforma.' },
                    { pregunta: '¿Puedo cancelar en cualquier momento?', respuesta: 'Sí, puedes cancelar tu suscripción cuando quieras sin penalizaciones.' },
                    { pregunta: '¿Ofrecen soporte técnico?', respuesta: 'Sí, ofrecemos soporte técnico 24/7 para todos nuestros usuarios.' }
                ]
            },
            contact: {
                titulo: 'Contáctanos',
                subtitulo: 'Estaremos encantados de ayudarte'
            },
            team: {
                titulo: 'Nuestro Equipo',
                items: [
                    { nombre: 'Ana García', cargo: 'CEO', bio: 'Fundadora con más de 10 años de experiencia.' },
                    { nombre: 'Carlos López', cargo: 'CTO', bio: 'Experto en tecnología e innovación.' },
                    { nombre: 'María Rodríguez', cargo: 'CMO', bio: 'Especialista en marketing digital.' }
                ]
            },
            stats: {
                items: [
                    { numero: '10K+', label: 'Usuarios activos' },
                    { numero: '99%', label: 'Satisfacción' },
                    { numero: '24/7', label: 'Soporte' },
                    { numero: '50+', label: 'Países' }
                ]
            },
            gallery: { titulo: 'Galería', items: [] },
            blog: { titulo: 'Últimas Noticias' },
            'video-section': {
                titulo: 'Mira cómo funciona',
                descripcion: 'Descripción del video',
                video_url: ''
            },
            heading: { text: 'Escribe tu encabezado aquí', level: 'h2' },
            text: { text: '<p>Escribe tu texto aquí. Puedes usar <strong>negrita</strong>, <em>cursiva</em> y más formatos usando la barra de herramientas flotante.</p>' },
            image: { src: '', alt: '', caption: '' },
            button: { text: 'Botón', url: '#', target: '_self', style: 'filled', align: 'left' },
            divider: { style: 'solid', width: '1px', color: '#e0e0e0' },
            spacer: { height: '60px' },
            icon: { icon: '⭐', size: '48px' },
            html: { code: '<!-- Tu código HTML aquí -->' },
            shortcode: { shortcode: 'tu_shortcode' },
            container: { maxWidth: '1200px' },
            columns: { columns: 2 },
            row: { columns: 2 },
            grid: {},
            form: {
                titulo: 'Formulario',
                boton_texto: 'Enviar',
                boton_url: '',
                mensaje_exito: '¡Gracias! Tu mensaje ha sido enviado.',
                campos: [
                    { tipo: 'text', label: 'Nombre', placeholder: 'Tu nombre', requerido: true },
                    { tipo: 'email', label: 'Email', placeholder: 'tu@email.com', requerido: true },
                    { tipo: 'textarea', label: 'Mensaje', placeholder: 'Escribe tu mensaje...', requerido: false }
                ]
            },
            input: { label: 'Campo', inputType: 'text', placeholder: 'Escribe aquí...' },
            textarea: { label: 'Mensaje', placeholder: 'Escribe tu mensaje...' },
            select: { label: 'Selecciona' },
            checkbox: { label: 'Acepto los términos y condiciones' },
            'video-embed': { url: '' },
            audio: { src: '' },
            map: { lat: '', lng: '', zoom: 14 },
            mapa: { lat: '', lng: '', zoom: 14 },
            embed: { code: '' },
            countdown: {
                titulo: 'La oferta termina en',
                fecha: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                hora: '23:59',
                mensaje_fin: '¡La oferta ha terminado!',
                mostrar_dias: true,
                mostrar_horas: true,
                mostrar_minutos: true,
                mostrar_segundos: true
            },
            'social-icons': {
                titulo: 'Síguenos',
                redes: [
                    { red: 'facebook', url: '#', icono: '📘' },
                    { red: 'twitter', url: '#', icono: '🐦' },
                    { red: 'instagram', url: '#', icono: '📸' },
                    { red: 'linkedin', url: '#', icono: '💼' }
                ],
                estilo: 'circle',
                tamano: 'medium',
                alineacion: 'center'
            },
            newsletter: {
                titulo: 'Suscríbete a nuestro newsletter',
                subtitulo: 'Recibe las últimas novedades directamente en tu email',
                placeholder_email: 'tu@email.com',
                boton_texto: 'Suscribirse',
                mostrar_nombre: false,
                mensaje_exito: '¡Gracias por suscribirte!'
            },
            'logo-grid': {
                titulo: 'Confían en nosotros',
                logos: [],
                columnas: 4,
                escala_grises: true,
                hover_color: true
            },
            'icon-box': {
                icono: '🚀',
                titulo: 'Título',
                descripcion: 'Descripción del servicio o característica',
                enlace_url: '',
                enlace_texto: 'Saber más',
                alineacion: 'center'
            },
            accordion: {
                titulo: 'Acordeón',
                items: [
                    { titulo: 'Elemento 1', contenido: 'Contenido del elemento 1', abierto: true },
                    { titulo: 'Elemento 2', contenido: 'Contenido del elemento 2', abierto: false },
                    { titulo: 'Elemento 3', contenido: 'Contenido del elemento 3', abierto: false }
                ],
                multiples_abiertos: false
            },
            tabs: {
                items: [
                    { titulo: 'Tab 1', contenido: 'Contenido de la pestaña 1' },
                    { titulo: 'Tab 2', contenido: 'Contenido de la pestaña 2' },
                    { titulo: 'Tab 3', contenido: 'Contenido de la pestaña 3' }
                ],
                tab_activa: 0,
                estilo: 'horizontal'
            },
            'progress-bar': {
                items: [
                    { label: 'Diseño UI/UX', porcentaje: 90 },
                    { label: 'Desarrollo Web', porcentaje: 85 },
                    { label: 'Marketing Digital', porcentaje: 75 }
                ],
                mostrar_porcentaje: true,
                animado: true
            },
            alert: {
                tipo: 'info',
                titulo: 'Información importante',
                mensaje: 'Este es un mensaje de alerta para el usuario.',
                dismissible: true,
                icono: true
            },
            'before-after': {
                imagen_antes: '',
                imagen_despues: '',
                label_antes: 'Antes',
                label_despues: 'Después',
                posicion_inicial: 50,
                orientacion: 'horizontal'
            },
            timeline: {
                titulo: 'Línea de Tiempo',
                subtitulo: '',
                titulo_color: '#ffffff',
                subtitulo_color: '#9CA3AF',
                color_fondo: '#0f0f0f',
                linea_color: '#3b82f6',
                linea_posicion: 'center',
                eventos: [
                    { fecha: '2020', titulo: 'Primer evento', descripcion: 'Descripción del primer evento', icono: '🚀' },
                    { fecha: '2022', titulo: 'Segundo evento', descripcion: 'Descripción del segundo evento', icono: '📈' },
                    { fecha: '2024', titulo: 'Tercer evento', descripcion: 'Descripción del tercer evento', icono: '🎯' }
                ]
            },
            carousel: {
                titulo: '',
                subtitulo: '',
                titulo_color: '#ffffff',
                subtitulo_color: '#9CA3AF',
                color_fondo: '#0f0f0f',
                autoplay: true,
                intervalo: 5,
                mostrar_flechas: true,
                mostrar_dots: true,
                loop: true,
                slides_visibles: 1,
                items: [
                    { imagen: '', titulo: 'Slide 1', descripcion: 'Descripción del primer slide', enlace_url: '', enlace_texto: 'Ver más' },
                    { imagen: '', titulo: 'Slide 2', descripcion: 'Descripción del segundo slide', enlace_url: '', enlace_texto: 'Ver más' }
                ]
            }
        };
        return defaults[type] || {};
    },

    getDefaultStyles: function() {
        return {
            spacing: {
                margin: { top: '', right: '', bottom: '', left: '' },
                padding: { top: '', right: '', bottom: '', left: '' }
            },
            colors: { background: '', text: '' },
            background: {
                type: '',
                gradientDirection: 'to bottom',
                gradientStart: '#3b82f6',
                gradientEnd: '#8b5cf6',
                image: '',
                size: 'cover',
                position: 'center',
                repeat: 'no-repeat',
                fixed: false,
                overlayOpacity: 0
            },
            typography: { fontSize: '', fontWeight: '', lineHeight: '', textAlign: '' },
            borders: { radius: '', width: '', color: '', style: '' },
            shadows: { boxShadow: '' },
            layout: { display: '', flexDirection: '', justifyContent: '', alignItems: '', flexWrap: '', gap: '', gridTemplateColumns: '', gridTemplateRows: '' },
            dimensions: { width: '', height: '', minHeight: '', maxWidth: '' },
            position: { position: '', top: '', right: '', bottom: '', left: '', zIndex: '' },
            transform: { rotate: '', scale: '', translateX: '', translateY: '', skewX: '', skewY: '' },
            overflow: '',
            opacity: '',
            states: {
                hover: { enabled: false, background: '', color: '', borderColor: '', boxShadow: '', transform: '', opacity: '' },
                active: { enabled: false, background: '', color: '', borderColor: '', boxShadow: '', transform: '', opacity: '' },
                focus: { enabled: false, background: '', color: '', borderColor: '', boxShadow: '', outline: '', outlineOffset: '' }
            },
            transition: {
                enabled: false,
                property: 'all',
                duration: '0.3s',
                timing: 'ease',
                delay: ''
            },
            advanced: {
                cssId: '',
                cssClasses: '',
                customCss: '',
                entranceAnimation: '',
                hoverAnimation: '',
                loopAnimation: '',
                parallaxEnabled: false,
                parallaxSpeed: 0.5
            }
        };
    },

    getDefaultVariant: function(type) {
        var defaults = {
            hero: 'centered',
            features: 'grid',
            testimonials: 'cards',
            pricing: 'columns',
            cta: 'centered',
            faq: 'simple',
            contact: 'simple',
            team: 'grid',
            button: 'filled',
            divider: 'solid',
            'icon-box': 'vertical',
            accordion: 'simple',
            tabs: 'horizontal',
            alert: 'info',
            newsletter: 'inline'
        };
        return defaults[type] || 'default';
    },

    getVariantsForType: function(type) {
        var variants = {
            hero: [
                { id: 'centered', name: 'Centrado', icon: '⊡' },
                { id: 'left', name: 'Izquierda', icon: '⊟' },
                { id: 'split', name: 'Dividido', icon: '⊞' },
                { id: 'video-bg', name: 'Video fondo', icon: '▶' },
                { id: 'minimal', name: 'Minimalista', icon: '―' }
            ],
            features: [
                { id: 'grid', name: 'Grid', icon: '⊞' },
                { id: 'list', name: 'Lista', icon: '≡' },
                { id: 'icons', name: 'Iconos', icon: '◎' },
                { id: 'cards', name: 'Tarjetas', icon: '▢' }
            ],
            testimonials: [
                { id: 'cards', name: 'Tarjetas', icon: '▢' },
                { id: 'carousel', name: 'Carrusel', icon: '↔' },
                { id: 'quotes', name: 'Citas', icon: '❝' },
                { id: 'minimal', name: 'Mínimo', icon: '―' }
            ],
            pricing: [
                { id: 'columns', name: 'Columnas', icon: '▥' },
                { id: 'cards', name: 'Tarjetas', icon: '▢' },
                { id: 'toggle', name: 'Toggle', icon: '⇄' }
            ],
            cta: [
                { id: 'centered', name: 'Centrado', icon: '⊡' },
                { id: 'split', name: 'Dividido', icon: '⊞' },
                { id: 'banner', name: 'Banner', icon: '▭' }
            ],
            faq: [
                { id: 'simple', name: 'Simple', icon: '≡' },
                { id: 'accordion', name: 'Acordeón', icon: '▼' },
                { id: 'tabs', name: 'Pestañas', icon: '⊟' }
            ],
            contact: [
                { id: 'simple', name: 'Simple', icon: '▢' },
                { id: 'split', name: 'Dividido', icon: '⊞' },
                { id: 'minimal', name: 'Mínimo', icon: '―' }
            ],
            team: [
                { id: 'grid', name: 'Grid', icon: '⊞' },
                { id: 'cards', name: 'Tarjetas', icon: '▢' },
                { id: 'list', name: 'Lista', icon: '≡' }
            ],
            button: [
                { id: 'filled', name: 'Relleno', icon: '▮' },
                { id: 'outline', name: 'Contorno', icon: '▯' },
                { id: 'ghost', name: 'Ghost', icon: '◇' },
                { id: 'link', name: 'Enlace', icon: '―' }
            ],
            divider: [
                { id: 'solid', name: 'Sólido', icon: '―' },
                { id: 'dashed', name: 'Guiones', icon: '- -' },
                { id: 'dotted', name: 'Puntos', icon: '···' },
                { id: 'gradient', name: 'Gradiente', icon: '▬' }
            ],
            'icon-box': [
                { id: 'vertical', name: 'Vertical', icon: '⊡' },
                { id: 'horizontal', name: 'Horizontal', icon: '⊟' },
                { id: 'left', name: 'Izquierda', icon: '◀' }
            ],
            accordion: [
                { id: 'simple', name: 'Simple', icon: '≡' },
                { id: 'bordered', name: 'Bordeado', icon: '▢' },
                { id: 'filled', name: 'Relleno', icon: '▮' }
            ],
            tabs: [
                { id: 'horizontal', name: 'Horizontal', icon: '⊟' },
                { id: 'vertical', name: 'Vertical', icon: '⊡' },
                { id: 'pills', name: 'Pills', icon: '◯' }
            ],
            alert: [
                { id: 'info', name: 'Info', icon: 'ℹ' },
                { id: 'success', name: 'Éxito', icon: '✓' },
                { id: 'warning', name: 'Aviso', icon: '⚠' },
                { id: 'error', name: 'Error', icon: '✗' }
            ],
            newsletter: [
                { id: 'inline', name: 'En línea', icon: '⊟' },
                { id: 'stacked', name: 'Apilado', icon: '⊡' },
                { id: 'minimal', name: 'Mínimo', icon: '―' }
            ]
        };
        return variants[type] || [];
    }
};

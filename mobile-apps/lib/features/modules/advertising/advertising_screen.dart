import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/utils/flavor_contact_launcher.dart';
import '../../../core/utils/flavor_url_launcher.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class AdvertisingScreen extends ConsumerStatefulWidget {
  const AdvertisingScreen({super.key});

  @override
  ConsumerState<AdvertisingScreen> createState() => _AdvertisingScreenState();
}

class _AdvertisingScreenState extends ConsumerState<AdvertisingScreen> {
  List<dynamic> _anunciosEticos = [];
  bool _cargandoDatos = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarAnuncios();
  }

  Future<void> _cargarAnuncios() async {
    setState(() {
      _cargandoDatos = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/advertising');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _anunciosEticos = respuesta.data!['anuncios'] ??
              respuesta.data!['items'] ??
              respuesta.data!['data'] ??
              [];
          _cargandoDatos = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar publicidad';
          _cargandoDatos = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargandoDatos = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Publicidad Ética'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarAnuncios,
          ),
        ],
      ),
      body: _cargandoDatos
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarAnuncios,
                  icon: Icons.campaign,
                )
              : _anunciosEticos.isEmpty
                  ? const FlavorEmptyState(
                      icon: Icons.campaign,
                      title: 'No hay anuncios disponibles',
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarAnuncios,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _anunciosEticos.length,
                        itemBuilder: (context, indice) =>
                            _construirTarjetaAnuncio(_anunciosEticos[indice]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaAnuncio(dynamic anuncio) {
    final datosAnuncio = anuncio as Map<String, dynamic>;
    final tituloAnuncio = datosAnuncio['titulo'] ??
        datosAnuncio['nombre'] ??
        datosAnuncio['title'] ??
        'Sin título';
    final descripcionAnuncio = datosAnuncio['descripcion'] ??
        datosAnuncio['description'] ??
        '';
    final tipoAnuncio = datosAnuncio['tipo'] ?? datosAnuncio['type'] ?? '';
    final estadoAnuncio = datosAnuncio['estado'] ?? datosAnuncio['status'] ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.amber.shade100,
          child: const Icon(Icons.campaign, color: Colors.amber),
        ),
        title: Text(tituloAnuncio),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (descripcionAnuncio.isNotEmpty)
              Text(
                descripcionAnuncio,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            if (tipoAnuncio.isNotEmpty || estadoAnuncio.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Row(
                  children: [
                    if (tipoAnuncio.isNotEmpty)
                      Chip(
                        label: Text(tipoAnuncio, style: const TextStyle(fontSize: 10)),
                        padding: EdgeInsets.zero,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                    if (estadoAnuncio.isNotEmpty) ...[
                      const SizedBox(width: 4),
                      Chip(
                        label: Text(estadoAnuncio, style: const TextStyle(fontSize: 10)),
                        padding: EdgeInsets.zero,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                    ],
                  ],
                ),
              ),
          ],
        ),
        isThreeLine: descripcionAnuncio.isNotEmpty,
        trailing: const Icon(Icons.chevron_right),
        onTap: () {
          final anuncioId = datosAnuncio['id'];
          if (anuncioId != null) {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => AnuncioDetalleScreen(
                  anuncioId: anuncioId,
                  anuncioData: datosAnuncio,
                ),
              ),
            );
          }
        },
      ),
    );
  }
}

/// Pantalla de detalle de anuncio ético
class AnuncioDetalleScreen extends ConsumerStatefulWidget {
  final dynamic anuncioId;
  final Map<String, dynamic> anuncioData;

  const AnuncioDetalleScreen({
    super.key,
    required this.anuncioId,
    required this.anuncioData,
  });

  @override
  ConsumerState<AnuncioDetalleScreen> createState() => _AnuncioDetalleScreenState();
}

class _AnuncioDetalleScreenState extends ConsumerState<AnuncioDetalleScreen> {
  Map<String, dynamic>? _anuncioDetalle;
  bool _cargandoDetalle = true;
  bool _enviandoContacto = false;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() {
      _cargandoDetalle = true;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/advertising/${widget.anuncioId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _anuncioDetalle = respuesta.data!['anuncio'] ?? respuesta.data!['data'] ?? respuesta.data!;
          _cargandoDetalle = false;
        });
      } else {
        setState(() {
          _anuncioDetalle = widget.anuncioData;
          _cargandoDetalle = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _anuncioDetalle = widget.anuncioData;
        _cargandoDetalle = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final datos = _anuncioDetalle ?? widget.anuncioData;
    final tituloAnuncio = datos['titulo'] ?? datos['nombre'] ?? datos['title'] ?? 'Sin título';
    final descripcionAnuncio = datos['descripcion'] ?? datos['description'] ?? '';
    final contenidoAnuncio = datos['contenido'] ?? datos['content'] ?? '';
    final tipoAnuncio = datos['tipo'] ?? datos['type'] ?? '';
    final estadoAnuncio = datos['estado'] ?? datos['status'] ?? '';
    final imagenAnuncio = datos['imagen'] ?? datos['image'] ?? '';
    final anuncianteNombre = datos['anunciante'] ?? datos['empresa'] ?? datos['advertiser'] ?? '';
    final fechaInicio = datos['fecha_inicio'] ?? datos['start_date'] ?? '';
    final fechaFin = datos['fecha_fin'] ?? datos['end_date'] ?? '';
    final enlaceAnuncio = datos['enlace'] ?? datos['url'] ?? datos['link'] ?? '';
    final emailContacto = datos['email'] ?? datos['contact_email'] ?? '';
    final telefonoContacto = datos['telefono'] ?? datos['phone'] ?? '';
    final etiquetasEticas = (datos['etiquetas_eticas'] ?? datos['ethical_tags'] ?? []) as List<dynamic>;
    final criteriosEticos = (datos['criterios_eticos'] ?? datos['ethical_criteria'] ?? []) as List<dynamic>;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle del Anuncio'),
        actions: [
          if (enlaceAnuncio.isNotEmpty)
            IconButton(
              icon: const Icon(Icons.open_in_new),
              onPressed: () => _abrirEnlace(enlaceAnuncio),
              tooltip: 'Abrir enlace',
            ),
        ],
      ),
      body: _cargandoDetalle
          ? const FlavorLoadingState()
          : RefreshIndicator(
              onRefresh: _cargarDetalle,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Imagen del anuncio
                    if (imagenAnuncio.isNotEmpty)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Image.network(
                          imagenAnuncio,
                          width: double.infinity,
                          height: 200,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                            height: 200,
                            color: Colors.amber.shade50,
                            child: const Center(
                              child: Icon(Icons.campaign, size: 64, color: Colors.amber),
                            ),
                          ),
                        ),
                      )
                    else
                      Container(
                        height: 150,
                        decoration: BoxDecoration(
                          color: Colors.amber.shade50,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Center(
                          child: Icon(Icons.campaign, size: 64, color: Colors.amber),
                        ),
                      ),
                    const SizedBox(height: 16),

                    // Título
                    Text(
                      tituloAnuncio,
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 8),

                    // Chips de tipo y estado
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        if (tipoAnuncio.isNotEmpty)
                          Chip(
                            avatar: const Icon(Icons.category, size: 16),
                            label: Text(tipoAnuncio),
                            backgroundColor: Colors.amber.shade100,
                          ),
                        if (estadoAnuncio.isNotEmpty)
                          Chip(
                            avatar: Icon(
                              estadoAnuncio == 'activo' ? Icons.check_circle : Icons.schedule,
                              size: 16,
                            ),
                            label: Text(estadoAnuncio),
                            backgroundColor: estadoAnuncio == 'activo'
                                ? Colors.green.shade100
                                : Colors.grey.shade200,
                          ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Anunciante
                    if (anuncianteNombre.isNotEmpty) ...[
                      Card(
                        child: ListTile(
                          leading: const CircleAvatar(
                            child: Icon(Icons.business),
                          ),
                          title: const Text('Anunciante'),
                          subtitle: Text(anuncianteNombre),
                        ),
                      ),
                      const SizedBox(height: 12),
                    ],

                    // Fechas
                    if (fechaInicio.isNotEmpty || fechaFin.isNotEmpty)
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Row(
                            children: [
                              const Icon(Icons.date_range, color: Colors.grey),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    if (fechaInicio.isNotEmpty)
                                      Text('Inicio: $fechaInicio'),
                                    if (fechaFin.isNotEmpty)
                                      Text('Fin: $fechaFin'),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    const SizedBox(height: 16),

                    // Descripción
                    if (descripcionAnuncio.isNotEmpty) ...[
                      Text(
                        'Descripción',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 8),
                      Text(descripcionAnuncio),
                      const SizedBox(height: 16),
                    ],

                    // Contenido completo
                    if (contenidoAnuncio.isNotEmpty) ...[
                      Text(
                        'Contenido',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 8),
                      Text(contenidoAnuncio),
                      const SizedBox(height: 16),
                    ],

                    // Etiquetas éticas
                    if (etiquetasEticas.isNotEmpty) ...[
                      Text(
                        'Etiquetas Éticas',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: etiquetasEticas
                            .map((etiqueta) => Chip(
                                  avatar: const Icon(Icons.eco, size: 16, color: Colors.green),
                                  label: Text(etiqueta.toString()),
                                  backgroundColor: Colors.green.shade50,
                                ))
                            .toList(),
                      ),
                      const SizedBox(height: 16),
                    ],

                    // Criterios éticos
                    if (criteriosEticos.isNotEmpty) ...[
                      Text(
                        'Criterios Éticos Cumplidos',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 8),
                      ...criteriosEticos.map((criterio) => Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Icon(Icons.check_circle, color: Colors.green, size: 20),
                                const SizedBox(width: 8),
                                Expanded(child: Text(criterio.toString())),
                              ],
                            ),
                          )),
                      const SizedBox(height: 16),
                    ],

                    // Información de contacto
                    if (emailContacto.isNotEmpty || telefonoContacto.isNotEmpty) ...[
                      Text(
                        'Contacto',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 8),
                      if (emailContacto.isNotEmpty)
                        ListTile(
                          leading: const Icon(Icons.email),
                          title: Text(emailContacto),
                          onTap: () => _abrirEmail(emailContacto),
                          contentPadding: EdgeInsets.zero,
                        ),
                      if (telefonoContacto.isNotEmpty)
                        ListTile(
                          leading: const Icon(Icons.phone),
                          title: Text(telefonoContacto),
                          onTap: () => _llamarTelefono(telefonoContacto),
                          contentPadding: EdgeInsets.zero,
                        ),
                      const SizedBox(height: 16),
                    ],

                    // Botones de acción
                    Row(
                      children: [
                        if (enlaceAnuncio.isNotEmpty)
                          Expanded(
                            child: FilledButton.icon(
                              onPressed: () => _abrirEnlace(enlaceAnuncio),
                              icon: const Icon(Icons.open_in_new),
                              label: const Text('Ver más'),
                            ),
                          ),
                        if (enlaceAnuncio.isNotEmpty && (emailContacto.isNotEmpty || telefonoContacto.isNotEmpty))
                          const SizedBox(width: 12),
                        if (emailContacto.isNotEmpty || telefonoContacto.isNotEmpty)
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: () => _mostrarFormularioContacto(
                                emailContacto: emailContacto,
                                tituloAnuncio: tituloAnuncio,
                              ),
                              icon: const Icon(Icons.contact_mail),
                              label: const Text('Contactar'),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
    );
  }

  Future<void> _abrirEnlace(String url) async {
    await FlavorUrlLauncher.openExternal(
      context,
      url,
      emptyMessage: 'No hay enlace disponible.',
      errorMessage: 'No se puede abrir el enlace',
      normalizeHttpScheme: true,
    );
  }

  Future<void> _abrirEmail(String email) async {
    await FlavorContactLauncher.email(
      context,
      email,
      errorMessage: 'No se puede abrir el cliente de email',
    );
  }

  Future<void> _llamarTelefono(String telefono) async {
    await FlavorContactLauncher.call(context, telefono);
  }

  void _mostrarFormularioContacto({
    required String emailContacto,
    required String tituloAnuncio,
  }) {
    final nombreController = TextEditingController();
    final emailController = TextEditingController();
    final mensajeController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
          left: 20,
          right: 20,
          top: 20,
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.contact_mail, color: Colors.amber),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Contactar anunciante',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                'Sobre: $tituloAnuncio',
                style: TextStyle(color: Colors.grey.shade600),
              ),
              const SizedBox(height: 20),
              TextFormField(
                controller: nombreController,
                decoration: const InputDecoration(
                  labelText: 'Tu nombre',
                  prefixIcon: Icon(Icons.person),
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: emailController,
                decoration: const InputDecoration(
                  labelText: 'Tu email',
                  prefixIcon: Icon(Icons.email),
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.emailAddress,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: mensajeController,
                decoration: const InputDecoration(
                  labelText: 'Mensaje',
                  prefixIcon: Icon(Icons.message),
                  border: OutlineInputBorder(),
                  hintText: 'Escribe tu mensaje...',
                ),
                maxLines: 4,
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _enviandoContacto
                      ? null
                      : () async {
                          if (nombreController.text.isEmpty ||
                              emailController.text.isEmpty ||
                              mensajeController.text.isEmpty) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Completa todos los campos')),
                            );
                            return;
                          }
                          await _enviarMensajeContacto(
                            nombre: nombreController.text,
                            email: emailController.text,
                            mensaje: mensajeController.text,
                          );
                          if (!context.mounted) return;
                          Navigator.pop(context);
                        },
                  icon: _enviandoContacto
                      ? const FlavorInlineSpinner()
                      : const Icon(Icons.send),
                  label: Text(_enviandoContacto ? 'Enviando...' : 'Enviar mensaje'),
                ),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _enviarMensajeContacto({
    required String nombre,
    required String email,
    required String mensaje,
  }) async {
    setState(() => _enviandoContacto = true);
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post(
        '/advertising/${widget.anuncioId}/contactar',
        data: {
          'nombre': nombre,
          'email': email,
          'mensaje': mensaje,
        },
      );
      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Mensaje enviado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al enviar mensaje'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _enviandoContacto = false);
    }
  }
}

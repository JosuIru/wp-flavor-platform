import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class ForosScreen extends ConsumerStatefulWidget {
  const ForosScreen({super.key});

  @override
  ConsumerState<ForosScreen> createState() => _ForosScreenState();
}

class _ForosScreenState extends ConsumerState<ForosScreen> {
  List<dynamic> _listaForos = [];
  bool _cargando = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarDatos();
  }

  Future<void> _cargarDatos() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/foros');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaForos = respuesta.data!['items'] ?? respuesta.data!['data'] ?? respuesta.data!['foros'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar foros';
          _cargando = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Foros'),
        actions: [
          IconButton(icon: const Icon(Icons.search), onPressed: _mostrarBusqueda),
          IconButton(icon: const Icon(Icons.refresh), onPressed: _cargarDatos),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _crearNuevoForo(context),
        child: const Icon(Icons.add),
      ),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.forum, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_mensajeError!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _cargarDatos,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _listaForos.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.forum, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay foros disponibles'),
                          const SizedBox(height: 8),
                          const Text(
                            'Se el primero en iniciar una discusion',
                            style: TextStyle(color: Colors.grey),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDatos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listaForos.length,
                        itemBuilder: (context, indice) => _construirTarjetaForo(_listaForos[indice]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaForo(dynamic elemento) {
    final mapaDatos = elemento as Map<String, dynamic>;
    final tituloForo = mapaDatos['titulo'] ?? mapaDatos['nombre'] ?? mapaDatos['title'] ?? 'Sin titulo';
    final descripcionForo = mapaDatos['descripcion'] ?? mapaDatos['description'] ?? '';
    final autorForo = mapaDatos['autor'] ?? mapaDatos['author'] ?? mapaDatos['usuario'] ?? '';
    final fechaCreacion = mapaDatos['fecha'] ?? mapaDatos['created_at'] ?? mapaDatos['fecha_creacion'] ?? '';
    final totalRespuestas = mapaDatos['respuestas'] ?? mapaDatos['replies'] ?? mapaDatos['comentarios'] ?? 0;
    final totalVistas = mapaDatos['vistas'] ?? mapaDatos['views'] ?? 0;
    final categoriaForo = mapaDatos['categoria'] ?? mapaDatos['category'] ?? '';
    final esFijado = mapaDatos['fijado'] ?? mapaDatos['pinned'] ?? false;
    final estaCerrado = mapaDatos['cerrado'] ?? mapaDatos['closed'] ?? false;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () {
          final idForo = mapaDatos['id'];
          if (idForo != null) {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => ForoDetalleScreen(foroId: idForo),
              ),
            );
          }
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  backgroundColor: Colors.purple.shade100,
                  child: Icon(
                    esFijado ? Icons.push_pin : Icons.forum,
                    color: Colors.purple.shade700,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          if (esFijado)
                            Container(
                              margin: const EdgeInsets.only(right: 8),
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: Colors.orange.shade100,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                'FIJADO',
                                style: TextStyle(fontSize: 10, color: Colors.orange.shade800, fontWeight: FontWeight.bold),
                              ),
                            ),
                          if (estaCerrado)
                            Container(
                              margin: const EdgeInsets.only(right: 8),
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: Colors.red.shade100,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                'CERRADO',
                                style: TextStyle(fontSize: 10, color: Colors.red.shade800, fontWeight: FontWeight.bold),
                              ),
                            ),
                          if (categoriaForo.isNotEmpty)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: Colors.purple.shade50,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                categoriaForo,
                                style: TextStyle(fontSize: 10, color: Colors.purple.shade700),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        tituloForo,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      if (descripcionForo.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          descripcionForo,
                          style: TextStyle(color: Colors.grey.shade600),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          if (autorForo.isNotEmpty) ...[
                            Icon(Icons.person, size: 14, color: Colors.grey.shade500),
                            const SizedBox(width: 4),
                            Text(
                              autorForo,
                              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                            ),
                            const SizedBox(width: 12),
                          ],
                          if (fechaCreacion.isNotEmpty) ...[
                            Icon(Icons.access_time, size: 14, color: Colors.grey.shade500),
                            const SizedBox(width: 4),
                            Text(
                              fechaCreacion,
                              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _construirEstadisticaForo(Icons.reply, totalRespuestas.toString(), 'Respuestas'),
                _construirEstadisticaForo(Icons.visibility, totalVistas.toString(), 'Vistas'),
              ],
            ),
          ],
          ),
        ),
      ),
    );
  }

  Future<void> _crearNuevoForo(BuildContext context) async {
    final tituloController = TextEditingController();
    final descripcionController = TextEditingController();
    String categoriaSeleccionada = 'general';

    final categorias = ['general', 'anuncios', 'debates', 'ayuda', 'sugerencias'];

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
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
                    Icon(Icons.forum, color: Colors.purple.shade400),
                    const SizedBox(width: 12),
                    const Text(
                      'Nuevo Debate',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context, false),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: tituloController,
                  decoration: const InputDecoration(
                    labelText: 'Titulo del debate',
                    prefixIcon: Icon(Icons.title),
                    border: OutlineInputBorder(),
                    hintText: 'Escribe un titulo descriptivo',
                  ),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: categoriaSeleccionada,
                  decoration: const InputDecoration(
                    labelText: 'Categoria',
                    prefixIcon: Icon(Icons.category),
                    border: OutlineInputBorder(),
                  ),
                  items: categorias.map((categoria) {
                    return DropdownMenuItem<String>(
                      value: categoria,
                      child: Text(categoria[0].toUpperCase() + categoria.substring(1)),
                    );
                  }).toList(),
                  onChanged: (value) {
                    if (value != null) {
                      setModalState(() => categoriaSeleccionada = value);
                    }
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: descripcionController,
                  decoration: const InputDecoration(
                    labelText: 'Contenido',
                    prefixIcon: Icon(Icons.description),
                    border: OutlineInputBorder(),
                    hintText: 'Describe el tema que quieres discutir...',
                  ),
                  maxLines: 4,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () {
                      if (tituloController.text.trim().isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('El titulo es obligatorio')),
                        );
                        return;
                      }
                      Navigator.pop(context, true);
                    },
                    icon: const Icon(Icons.send),
                    label: const Text('Publicar debate'),
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );

    if (result == true && mounted) {
      try {
        final clienteApi = ref.read(apiClientProvider);
        final respuesta = await clienteApi.post('/foros', data: {
          'titulo': tituloController.text.trim(),
          'descripcion': descripcionController.text.trim(),
          'categoria': categoriaSeleccionada,
        });

        if (!mounted) return;

        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Debate creado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDatos();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al crear el debate'),
              backgroundColor: Colors.red,
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
          );
        }
      }
    }

    tituloController.dispose();
    descripcionController.dispose();
  }

  Widget _construirEstadisticaForo(IconData icono, String valor, String etiqueta) {
    return Row(
      children: [
        Icon(icono, size: 18, color: Colors.grey.shade600),
        const SizedBox(width: 6),
        Text(
          valor,
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        const SizedBox(width: 4),
        Text(
          etiqueta,
          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
        ),
      ],
    );
  }

  void _mostrarBusqueda() {
    final controladorBusqueda = TextEditingController();
    List<dynamic> resultadosFiltrados = List.from(_listaForos);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => DraggableScrollableSheet(
          initialChildSize: 0.85,
          minChildSize: 0.5,
          maxChildSize: 0.95,
          expand: false,
          builder: (context, scrollController) => Column(
            children: [
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Icon(Icons.search, color: Colors.purple.shade400),
                        const SizedBox(width: 12),
                        const Text(
                          'Buscar en foros',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const Spacer(),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(context),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: controladorBusqueda,
                      autofocus: true,
                      decoration: InputDecoration(
                        hintText: 'Escribe para buscar...',
                        prefixIcon: const Icon(Icons.search),
                        suffixIcon: controladorBusqueda.text.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () {
                                  controladorBusqueda.clear();
                                  setModalState(() {
                                    resultadosFiltrados = List.from(_listaForos);
                                  });
                                },
                              )
                            : null,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      onChanged: (textoBusqueda) {
                        final busquedaMinusculas = textoBusqueda.toLowerCase();
                        setModalState(() {
                          if (textoBusqueda.isEmpty) {
                            resultadosFiltrados = List.from(_listaForos);
                          } else {
                            resultadosFiltrados = _listaForos.where((foro) {
                              final mapaForo = foro as Map<String, dynamic>;
                              final titulo = (mapaForo['titulo'] ?? mapaForo['nombre'] ?? '').toString().toLowerCase();
                              final descripcion = (mapaForo['descripcion'] ?? '').toString().toLowerCase();
                              final autor = (mapaForo['autor'] ?? '').toString().toLowerCase();
                              final categoria = (mapaForo['categoria'] ?? '').toString().toLowerCase();
                              return titulo.contains(busquedaMinusculas) ||
                                  descripcion.contains(busquedaMinusculas) ||
                                  autor.contains(busquedaMinusculas) ||
                                  categoria.contains(busquedaMinusculas);
                            }).toList();
                          }
                        });
                      },
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              Expanded(
                child: resultadosFiltrados.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.search_off, size: 48, color: Colors.grey.shade400),
                            const SizedBox(height: 12),
                            Text(
                              controladorBusqueda.text.isEmpty
                                  ? 'No hay foros'
                                  : 'Sin resultados para "${controladorBusqueda.text}"',
                              style: TextStyle(color: Colors.grey.shade600),
                            ),
                          ],
                        ),
                      )
                    : ListView.separated(
                        controller: scrollController,
                        padding: const EdgeInsets.all(16),
                        itemCount: resultadosFiltrados.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 8),
                        itemBuilder: (context, indice) {
                          final foro = resultadosFiltrados[indice] as Map<String, dynamic>;
                          final tituloForo = foro['titulo'] ?? foro['nombre'] ?? 'Sin titulo';
                          final categoriaForo = foro['categoria'] ?? '';
                          final autorForo = foro['autor'] ?? '';
                          final respuestasForo = foro['respuestas'] ?? 0;

                          return ListTile(
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            tileColor: Colors.grey.shade100,
                            leading: CircleAvatar(
                              backgroundColor: Colors.purple.shade100,
                              child: Icon(Icons.forum, color: Colors.purple.shade700, size: 20),
                            ),
                            title: Text(
                              tituloForo,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            subtitle: Row(
                              children: [
                                if (categoriaForo.isNotEmpty) ...[
                                  Text(categoriaForo, style: const TextStyle(fontSize: 12)),
                                  const Text(' • ', style: TextStyle(fontSize: 12)),
                                ],
                                if (autorForo.isNotEmpty)
                                  Text(autorForo, style: const TextStyle(fontSize: 12)),
                                if (respuestasForo > 0) ...[
                                  const Text(' • ', style: TextStyle(fontSize: 12)),
                                  Text('$respuestasForo resp.', style: const TextStyle(fontSize: 12)),
                                ],
                              ],
                            ),
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () {
                              Navigator.pop(context);
                              final idForo = foro['id'];
                              if (idForo != null) {
                                Navigator.of(this.context).push(
                                  MaterialPageRoute(
                                    builder: (_) => ForoDetalleScreen(foroId: idForo),
                                  ),
                                );
                              }
                            },
                          );
                        },
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class ForoDetalleScreen extends ConsumerStatefulWidget {
  final dynamic foroId;
  const ForoDetalleScreen({super.key, required this.foroId});

  @override
  ConsumerState<ForoDetalleScreen> createState() => _ForoDetalleScreenState();
}

class _ForoDetalleScreenState extends ConsumerState<ForoDetalleScreen> {
  Map<String, dynamic>? _datosForo;
  List<dynamic> _listaRespuestas = [];
  bool _cargando = true;
  String? _mensajeError;
  final TextEditingController _controladorRespuesta = TextEditingController();

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  @override
  void dispose() {
    _controladorRespuesta.dispose();
    super.dispose();
  }

  Future<void> _cargarDetalle() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/foros/${widget.foroId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosForo = respuesta.data!['data'] ?? respuesta.data!;
          _listaRespuestas = _datosForo?['respuestas'] ?? _datosForo?['replies'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar foro';
          _cargando = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargando = false;
      });
    }
  }

  Future<void> _enviarRespuesta() async {
    if (_controladorRespuesta.text.trim().isEmpty) return;

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post(
        '/foros/${widget.foroId}/respuestas',
        data: {'contenido': _controladorRespuesta.text.trim()},
      );
      if (!mounted) return;
      if (respuesta.success) {
        _controladorRespuesta.clear();
        _cargarDetalle();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Respuesta enviada'), backgroundColor: Colors.green),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(respuesta.error ?? 'Error al enviar'), backgroundColor: Colors.red),
        );
      }
    } catch (excepcion) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(excepcion.toString()), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Debate'),
        actions: [
          IconButton(icon: const Icon(Icons.share), onPressed: _compartirForo),
        ],
      ),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(child: Text(_mensajeError!))
              : _datosForo == null
                  ? const Center(child: Text('No se encontraron datos'))
                  : Column(
                      children: [
                        Expanded(
                          child: ListView(
                            padding: const EdgeInsets.all(16),
                            children: [
                              Text(
                                _datosForo!['titulo'] ?? _datosForo!['nombre'] ?? 'Debate',
                                style: Theme.of(context).textTheme.titleLarge,
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  CircleAvatar(
                                    radius: 16,
                                    child: Text(
                                      (_datosForo!['autor'] ?? 'U').toString().substring(0, 1).toUpperCase(),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Text(_datosForo!['autor'] ?? 'Usuario'),
                                  const Spacer(),
                                  Text(
                                    _datosForo!['fecha'] ?? '',
                                    style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 16),
                              if (_datosForo!['contenido'] != null || _datosForo!['descripcion'] != null)
                                Text(_datosForo!['contenido'] ?? _datosForo!['descripcion']),
                              const Divider(height: 32),
                              Text(
                                'Respuestas (${_listaRespuestas.length})',
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 12),
                              if (_listaRespuestas.isEmpty)
                                Center(
                                  child: Padding(
                                    padding: const EdgeInsets.all(24),
                                    child: Text(
                                      'Se el primero en responder',
                                      style: TextStyle(color: Colors.grey.shade600),
                                    ),
                                  ),
                                )
                              else
                                ..._listaRespuestas.map((respuesta) => _construirTarjetaRespuesta(respuesta)),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Theme.of(context).cardColor,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.05),
                                blurRadius: 10,
                                offset: const Offset(0, -2),
                              ),
                            ],
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: TextField(
                                  controller: _controladorRespuesta,
                                  decoration: InputDecoration(
                                    hintText: 'Escribe tu respuesta...',
                                    border: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(24),
                                    ),
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                  ),
                                  maxLines: null,
                                ),
                              ),
                              const SizedBox(width: 8),
                              IconButton.filled(
                                onPressed: _enviarRespuesta,
                                icon: const Icon(Icons.send),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
    );
  }

  void _compartirForo() {
    if (_datosForo == null) return;

    final tituloForo = _datosForo!['titulo'] ?? _datosForo!['nombre'] ?? 'Debate';
    final autorForo = _datosForo!['autor'] ?? 'Usuario';
    final contenidoForo = _datosForo!['contenido'] ?? _datosForo!['descripcion'] ?? '';
    final totalRespuestas = _listaRespuestas.length;

    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.share, color: Colors.purple.shade400),
                const SizedBox(width: 12),
                const Text(
                  'Compartir debate',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    tituloForo,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Por $autorForo • $totalRespuestas respuestas',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Compartir mediante:',
              style: TextStyle(fontWeight: FontWeight.w500),
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _buildOpcionCompartir(
                  Icons.copy,
                  'Copiar enlace',
                  Colors.blue,
                  () {
                    Navigator.pop(context);
                    ScaffoldMessenger.of(this.context).showSnackBar(
                      const SnackBar(
                        content: Text('Enlace copiado al portapapeles'),
                        backgroundColor: Colors.green,
                      ),
                    );
                  },
                ),
                _buildOpcionCompartir(
                  Icons.message,
                  'Chat interno',
                  Colors.purple,
                  () {
                    Navigator.pop(context);
                    _compartirEnChat(tituloForo, contenidoForo);
                  },
                ),
                _buildOpcionCompartir(
                  Icons.email,
                  'Email',
                  Colors.orange,
                  () {
                    Navigator.pop(context);
                    ScaffoldMessenger.of(this.context).showSnackBar(
                      const SnackBar(
                        content: Text('Abriendo cliente de email...'),
                      ),
                    );
                  },
                ),
                _buildOpcionCompartir(
                  Icons.more_horiz,
                  'Mas',
                  Colors.grey,
                  () {
                    Navigator.pop(context);
                    ScaffoldMessenger.of(this.context).showSnackBar(
                      const SnackBar(
                        content: Text('Opciones adicionales de compartir'),
                      ),
                    );
                  },
                ),
              ],
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildOpcionCompartir(IconData icono, String etiqueta, Color color, VoidCallback alPresionar) {
    return InkWell(
      onTap: alPresionar,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.all(8),
        child: Column(
          children: [
            CircleAvatar(
              backgroundColor: color.withOpacity(0.1),
              radius: 24,
              child: Icon(icono, color: color),
            ),
            const SizedBox(height: 6),
            Text(
              etiqueta,
              style: const TextStyle(fontSize: 11),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _compartirEnChat(String titulo, String contenido) async {
    final controladorMensaje = TextEditingController(
      text: '📢 Te comparto este debate:\n\n"$titulo"\n\n${contenido.length > 100 ? '${contenido.substring(0, 100)}...' : contenido}',
    );

    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Compartir en chat'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Escribe un mensaje para acompañar el enlace:'),
            const SizedBox(height: 12),
            TextField(
              controller: controladorMensaje,
              maxLines: 4,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                hintText: 'Mensaje...',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Compartir'),
          ),
        ],
      ),
    );

    controladorMensaje.dispose();

    if (confirmar == true && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Debate compartido en el chat'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  Widget _construirTarjetaRespuesta(dynamic elemento) {
    final mapaDatos = elemento as Map<String, dynamic>;
    final autorRespuesta = mapaDatos['autor'] ?? mapaDatos['author'] ?? mapaDatos['usuario'] ?? 'Usuario';
    final contenidoRespuesta = mapaDatos['contenido'] ?? mapaDatos['content'] ?? mapaDatos['texto'] ?? '';
    final fechaRespuesta = mapaDatos['fecha'] ?? mapaDatos['created_at'] ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.shade100,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 14,
                child: Text(
                  autorRespuesta.toString().substring(0, 1).toUpperCase(),
                  style: const TextStyle(fontSize: 12),
                ),
              ),
              const SizedBox(width: 8),
              Text(autorRespuesta, style: const TextStyle(fontWeight: FontWeight.bold)),
              const Spacer(),
              Text(
                fechaRespuesta,
                style: TextStyle(color: Colors.grey.shade600, fontSize: 11),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(contenidoRespuesta),
        ],
      ),
    );
  }
}

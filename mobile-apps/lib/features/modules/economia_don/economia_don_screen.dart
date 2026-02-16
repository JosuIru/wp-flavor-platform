import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class EconomiaDonScreen extends ConsumerStatefulWidget {
  const EconomiaDonScreen({super.key});

  @override
  ConsumerState<EconomiaDonScreen> createState() => _EconomiaDonScreenState();
}

class _EconomiaDonScreenState extends ConsumerState<EconomiaDonScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _ofertas = [];
  List<Map<String, dynamic>> _necesidades = [];
  Map<String, dynamic>? _miPerfil;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/economia-don/tablero');
      if (response.success && response.data != null) {
        setState(() {
          _ofertas = (response.data!['ofertas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _necesidades = (response.data!['necesidades'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _miPerfil = response.data!['mi_perfil'];
        });
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Economia del Don'),
          actions: [
            IconButton(
              icon: const Icon(Icons.info_outline),
              onPressed: _showInfo,
            ),
          ],
          bottom: const TabBar(
            tabs: [
              Tab(icon: Icon(Icons.volunteer_activism), text: 'Ofertas'),
              Tab(icon: Icon(Icons.pan_tool), text: 'Necesidades'),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildListaOfertas(),
            _buildListaNecesidades(),
          ],
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _publicar,
          icon: const Icon(Icons.add),
          label: const Text('Publicar'),
        ),
      ),
    );
  }

  Widget _buildListaOfertas() {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    if (_ofertas.isEmpty) {
      return _buildEmptyState(
        icon: Icons.volunteer_activism,
        message: 'No hay ofertas publicadas',
        action: 'Se el primero en ofrecer algo',
      );
    }

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _ofertas.length,
        itemBuilder: (context, index) => _buildItemCard(_ofertas[index], esOferta: true),
      ),
    );
  }

  Widget _buildListaNecesidades() {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    if (_necesidades.isEmpty) {
      return _buildEmptyState(
        icon: Icons.pan_tool,
        message: 'No hay necesidades publicadas',
        action: 'Publica lo que necesitas',
      );
    }

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _necesidades.length,
        itemBuilder: (context, index) => _buildItemCard(_necesidades[index], esOferta: false),
      ),
    );
  }

  Widget _buildItemCard(Map<String, dynamic> item, {required bool esOferta}) {
    final titulo = item['titulo'] ?? '';
    final descripcion = item['descripcion'] ?? '';
    final autor = item['autor'] ?? '';
    final fecha = item['fecha'] ?? '';
    final categoria = item['categoria'] ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: esOferta ? Colors.green.shade100 : Colors.orange.shade100,
                  child: Icon(
                    esOferta ? Icons.volunteer_activism : Icons.pan_tool,
                    color: esOferta ? Colors.green.shade600 : Colors.orange.shade600,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(titulo, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text(autor, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                    ],
                  ),
                ),
                if (categoria.isNotEmpty)
                  Chip(
                    label: Text(categoria),
                    labelStyle: const TextStyle(fontSize: 11),
                    padding: EdgeInsets.zero,
                    visualDensity: VisualDensity.compact,
                  ),
              ],
            ),
            if (descripcion.isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(descripcion, maxLines: 3, overflow: TextOverflow.ellipsis),
            ],
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(Icons.access_time, size: 14, color: Colors.grey.shade500),
                const SizedBox(width: 4),
                Text(fecha, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                const Spacer(),
                TextButton.icon(
                  onPressed: () => _contactar(item),
                  icon: const Icon(Icons.chat_bubble_outline, size: 18),
                  label: const Text('Contactar'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState({required IconData icon, required String message, required String action}) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 64, color: Colors.grey.shade400),
          const SizedBox(height: 16),
          Text(message, style: TextStyle(color: Colors.grey.shade600)),
          const SizedBox(height: 8),
          Text(action, style: TextStyle(color: Colors.grey.shade500, fontSize: 13)),
        ],
      ),
    );
  }

  void _showInfo() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Economia del Don'),
        content: const Text(
          'La economia del don se basa en dar sin esperar nada a cambio. '
          'Aqui puedes ofrecer lo que tienes o pedir lo que necesitas, '
          'confiando en la generosidad de la comunidad.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Entendido')),
        ],
      ),
    );
  }

  void _publicar() {
    showModalBottomSheet(
      context: context,
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: Icon(Icons.volunteer_activism, color: Colors.green.shade600),
            title: const Text('Ofrecer algo'),
            subtitle: const Text('Comparte lo que puedes dar'),
            onTap: () {
              Navigator.pop(context);
              _mostrarFormulario(esOferta: true);
            },
          ),
          ListTile(
            leading: Icon(Icons.pan_tool, color: Colors.orange.shade600),
            title: const Text('Pedir algo'),
            subtitle: const Text('Expresa lo que necesitas'),
            onTap: () {
              Navigator.pop(context);
              _mostrarFormulario(esOferta: false);
            },
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  void _mostrarFormulario({required bool esOferta}) {
    final tituloController = TextEditingController();
    final descripcionController = TextEditingController();
    String categoriaSeleccionada = 'servicios';

    final categorias = [
      {'id': 'servicios', 'nombre': 'Servicios'},
      {'id': 'objetos', 'nombre': 'Objetos'},
      {'id': 'conocimientos', 'nombre': 'Conocimientos'},
      {'id': 'tiempo', 'nombre': 'Tiempo'},
      {'id': 'otros', 'nombre': 'Otros'},
    ];

    showModalBottomSheet(
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
                    Icon(
                      esOferta ? Icons.volunteer_activism : Icons.pan_tool,
                      color: esOferta ? Colors.green.shade600 : Colors.orange.shade600,
                    ),
                    const SizedBox(width: 12),
                    Text(
                      esOferta ? 'Ofrecer algo' : 'Pedir algo',
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  esOferta
                      ? 'Comparte lo que puedes dar a la comunidad'
                      : 'Expresa lo que necesitas de la comunidad',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: tituloController,
                  decoration: InputDecoration(
                    labelText: esOferta ? 'Que ofreces?' : 'Que necesitas?',
                    prefixIcon: const Icon(Icons.title),
                    border: const OutlineInputBorder(),
                    hintText: esOferta
                        ? 'Ej: Clases de guitarra, bicicleta, etc.'
                        : 'Ej: Ayuda con mudanza, herramientas, etc.',
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
                  items: categorias.map((cat) {
                    return DropdownMenuItem<String>(
                      value: cat['id'],
                      child: Text(cat['nombre']!),
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
                    labelText: 'Descripcion',
                    prefixIcon: Icon(Icons.description),
                    border: OutlineInputBorder(),
                    hintText: 'Describe con mas detalle...',
                  ),
                  maxLines: 4,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(
                      backgroundColor: esOferta ? Colors.green : Colors.orange,
                    ),
                    onPressed: () async {
                      if (tituloController.text.isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('El titulo es obligatorio')),
                        );
                        return;
                      }
                      Navigator.pop(context);
                      await _enviarPublicacion(
                        esOferta: esOferta,
                        titulo: tituloController.text,
                        categoria: categoriaSeleccionada,
                        descripcion: descripcionController.text,
                      );
                    },
                    icon: const Icon(Icons.send),
                    label: Text(esOferta ? 'Publicar oferta' : 'Publicar necesidad'),
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _enviarPublicacion({
    required bool esOferta,
    required String titulo,
    required String categoria,
    required String descripcion,
  }) async {
    final api = ref.read(apiClientProvider);
    final endpoint = esOferta ? '/economia-don/ofertas' : '/economia-don/necesidades';

    try {
      final response = await api.post(endpoint, data: {
        'titulo': titulo,
        'categoria': categoria,
        'descripcion': descripcion,
      });

      if (response.success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(esOferta ? 'Oferta publicada' : 'Necesidad publicada'),
              backgroundColor: Colors.green,
            ),
          );
          _loadData();
        }
      } else {
        throw Exception(response.error ?? 'Error al publicar');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _contactar(Map<String, dynamic> item) {
    final mensajeController = TextEditingController();
    final autorNombre = item['autor'] ?? 'Usuario';
    final itemTitulo = item['titulo'] ?? '';

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
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.chat_bubble, color: Colors.blue),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Contactar a $autorNombre',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
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
              'Sobre: $itemTitulo',
              style: TextStyle(color: Colors.grey.shade600),
            ),
            const SizedBox(height: 20),
            TextFormField(
              controller: mensajeController,
              decoration: const InputDecoration(
                labelText: 'Tu mensaje',
                prefixIcon: Icon(Icons.message),
                border: OutlineInputBorder(),
                hintText: 'Hola, me interesa...',
              ),
              maxLines: 4,
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: () async {
                  if (mensajeController.text.isEmpty) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Escribe un mensaje')),
                    );
                    return;
                  }
                  Navigator.pop(context);
                  await _enviarMensaje(
                    itemId: item['id']?.toString() ?? '',
                    autorId: item['autor_id']?.toString() ?? '',
                    mensaje: mensajeController.text,
                  );
                },
                icon: const Icon(Icons.send),
                label: const Text('Enviar mensaje'),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Future<void> _enviarMensaje({
    required String itemId,
    required String autorId,
    required String mensaje,
  }) async {
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/economia-don/contactar', data: {
        'item_id': itemId,
        'destinatario_id': autorId,
        'mensaje': mensaje,
      });

      if (response.success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Mensaje enviado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        throw Exception(response.error ?? 'Error al enviar');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}

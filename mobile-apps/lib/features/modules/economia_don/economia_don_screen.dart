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
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Formulario ${esOferta ? 'oferta' : 'necesidad'} - proximamente')),
    );
  }

  void _contactar(Map<String, dynamic> item) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Contactar a ${item['autor']} - proximamente')),
    );
  }
}

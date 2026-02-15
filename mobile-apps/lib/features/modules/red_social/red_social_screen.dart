import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class RedSocialScreen extends ConsumerStatefulWidget {
  const RedSocialScreen({super.key});

  @override
  ConsumerState<RedSocialScreen> createState() => _RedSocialScreenState();
}

class _RedSocialScreenState extends ConsumerState<RedSocialScreen> {
  List<dynamic> _publicaciones = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/red-social/feed');
      if (response.success && response.data != null) {
        setState(() {
          _publicaciones = response.data!['publicaciones'] ??
              response.data!['posts'] ??
              response.data!['items'] ??
              response.data!['data'] ??
              [];
          _loading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar el feed';
          _loading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Red Social'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.public, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_error!, textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadData,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _publicaciones.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.public,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay publicaciones disponibles'),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadData,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _publicaciones.length,
                        itemBuilder: (context, index) =>
                            _buildPublicacionCard(_publicaciones[index]),
                      ),
                    ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          // TODO: Implementar crear publicacion
        },
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _buildPublicacionCard(dynamic item) {
    final publicacionMap = item as Map<String, dynamic>;
    final autor = publicacionMap['autor'] ??
        publicacionMap['author'] ??
        publicacionMap['usuario'] ??
        'Usuario';
    final contenido = publicacionMap['contenido'] ??
        publicacionMap['content'] ??
        publicacionMap['texto'] ??
        '';
    final fecha = publicacionMap['fecha'] ??
        publicacionMap['date'] ??
        publicacionMap['created_at'] ??
        '';
    final likes = publicacionMap['likes'] ?? publicacionMap['me_gusta'] ?? 0;
    final comentarios =
        publicacionMap['comentarios'] ?? publicacionMap['comments'] ?? 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const CircleAvatar(child: Icon(Icons.person)),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        autor.toString(),
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      if (fecha.toString().isNotEmpty)
                        Text(
                          fecha.toString(),
                          style:
                              TextStyle(fontSize: 12, color: Colors.grey[600]),
                        ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.more_vert),
                  onPressed: () {},
                ),
              ],
            ),
            if (contenido.toString().isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(contenido.toString()),
            ],
            const SizedBox(height: 12),
            Row(
              children: [
                TextButton.icon(
                  onPressed: () {},
                  icon: const Icon(Icons.thumb_up_outlined, size: 20),
                  label: Text('$likes'),
                ),
                TextButton.icon(
                  onPressed: () {},
                  icon: const Icon(Icons.comment_outlined, size: 20),
                  label: Text('$comentarios'),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.share_outlined, size: 20),
                  onPressed: () {},
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

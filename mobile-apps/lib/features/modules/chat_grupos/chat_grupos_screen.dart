import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class ChatGruposScreen extends ConsumerStatefulWidget {
  const ChatGruposScreen({super.key});

  @override
  ConsumerState<ChatGruposScreen> createState() => _ChatGruposScreenState();
}

class _ChatGruposScreenState extends ConsumerState<ChatGruposScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _misFuture;
  late Future<ApiResponse<Map<String, dynamic>>> _explorarFuture;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _misFuture = api.getChatGrupos();
    _explorarFuture = api.explorarChatGrupos();
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Chat de Grupos'),
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Mis grupos'),
              Tab(text: 'Explorar'),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildListado(_misFuture, 'grupos'),
            _buildListado(_explorarFuture, 'grupos'),
          ],
        ),
      ),
    );
  }

  Widget _buildListado(Future<ApiResponse<Map<String, dynamic>>> future, String key) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: future,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final res = snapshot.data!;
        if (!res.success || res.data == null) {
          return Center(child: Text(res.error ?? 'Error al cargar grupos'));
        }
        final grupos = (res.data![key] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (grupos.isEmpty) {
          return const Center(child: Text('No hay grupos disponibles'));
        }
        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: grupos.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final g = grupos[index];
            final id = (g['id'] as num?)?.toInt() ?? 0;
            final nombre = g['nombre']?.toString() ?? 'Grupo';
            final desc = g['descripcion']?.toString() ?? '';
            return Card(
              elevation: 1,
              child: ListTile(
                leading: const Icon(Icons.forum),
                title: Text(nombre),
                subtitle: Text(desc, maxLines: 2, overflow: TextOverflow.ellipsis),
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => ChatGrupoMensajesScreen(grupoId: id, titulo: nombre),
                    ),
                  );
                },
              ),
            );
          },
        );
      },
    );
  }
}

class ChatGrupoMensajesScreen extends ConsumerStatefulWidget {
  final int grupoId;
  final String titulo;
  const ChatGrupoMensajesScreen({super.key, required this.grupoId, required this.titulo});

  @override
  ConsumerState<ChatGrupoMensajesScreen> createState() => _ChatGrupoMensajesScreenState();
}

class _ChatGrupoMensajesScreenState extends ConsumerState<ChatGrupoMensajesScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  final TextEditingController _controller = TextEditingController();

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getChatGrupoMensajes(grupoId: widget.grupoId);
  }

  Future<void> _enviar() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    _controller.clear();
    await ref.read(apiClientProvider).sendChatGrupoMensaje(
          grupoId: widget.grupoId,
          mensaje: text,
        );
    setState(() {
      _future = ref.read(apiClientProvider).getChatGrupoMensajes(grupoId: widget.grupoId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.titulo)),
      body: Column(
        children: [
          Expanded(
            child: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
              future: _future,
              builder: (context, snapshot) {
                if (!snapshot.hasData) {
                  return const Center(child: CircularProgressIndicator());
                }
                final res = snapshot.data!;
                if (!res.success || res.data == null) {
                  return Center(child: Text(res.error ?? 'Error al cargar mensajes'));
                }
                final mensajes = (res.data!['mensajes'] as List<dynamic>? ?? [])
                    .whereType<Map<String, dynamic>>()
                    .toList();
                if (mensajes.isEmpty) {
                  return const Center(child: Text('No hay mensajes'));
                }
                return ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: mensajes.length,
                  itemBuilder: (context, index) {
                    final m = mensajes[index];
                    final texto = m['mensaje']?.toString() ?? '';
                    final autor = m['autor_nombre']?.toString() ?? '';
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(autor, style: Theme.of(context).textTheme.labelSmall),
                          const SizedBox(height: 2),
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Theme.of(context).colorScheme.surfaceContainerHighest,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(texto),
                          ),
                        ],
                      ),
                    );
                  },
                );
              },
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    decoration: const InputDecoration(
                      hintText: 'Escribe un mensaje',
                      border: OutlineInputBorder(),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  onPressed: _enviar,
                  icon: const Icon(Icons.send),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

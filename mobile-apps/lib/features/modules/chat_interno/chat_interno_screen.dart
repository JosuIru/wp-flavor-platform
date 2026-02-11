import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class ChatInternoScreen extends ConsumerStatefulWidget {
  const ChatInternoScreen({super.key});

  @override
  ConsumerState<ChatInternoScreen> createState() => _ChatInternoScreenState();
}

class _ChatInternoScreenState extends ConsumerState<ChatInternoScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getChatInternoConversaciones();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Chat Interno')),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return Center(child: Text(res.error ?? 'Error al cargar conversaciones'));
          }
          final convs = (res.data!['conversaciones'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          if (convs.isEmpty) {
            return const Center(child: Text('No hay conversaciones'));
          }
          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: convs.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final c = convs[index];
              final id = (c['id'] as num?)?.toInt() ?? 0;
              final otro = (c['con_usuario'] as Map?) ?? {};
              final nombre = otro['nombre']?.toString() ?? 'Usuario';
              final preview = c['ultimo_mensaje']?.toString() ?? '';
              return Card(
                elevation: 1,
                child: ListTile(
                  leading: const Icon(Icons.person),
                  title: Text(nombre),
                  subtitle: Text(preview),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => ChatInternoMensajesScreen(
                          conversacionId: id,
                          titulo: nombre,
                        ),
                      ),
                    );
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class ChatInternoMensajesScreen extends ConsumerStatefulWidget {
  final int conversacionId;
  final String titulo;
  const ChatInternoMensajesScreen({super.key, required this.conversacionId, required this.titulo});

  @override
  ConsumerState<ChatInternoMensajesScreen> createState() => _ChatInternoMensajesScreenState();
}

class _ChatInternoMensajesScreenState extends ConsumerState<ChatInternoMensajesScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  final TextEditingController _controller = TextEditingController();

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getChatInternoMensajes(conversacionId: widget.conversacionId);
  }

  Future<void> _enviar() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    _controller.clear();
    await ref.read(apiClientProvider).sendChatInternoMensaje(
          conversacionId: widget.conversacionId,
          mensaje: text,
        );
    setState(() {
      _future = ref.read(apiClientProvider).getChatInternoMensajes(conversacionId: widget.conversacionId);
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
                    final autor = m['remitente_nombre']?.toString() ?? '';
                    final esMio = m['es_mio'] == true;
                    return Align(
                      alignment: esMio ? Alignment.centerRight : Alignment.centerLeft,
                      child: Container(
                        margin: const EdgeInsets.symmetric(vertical: 4),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: esMio
                              ? Theme.of(context).colorScheme.primary.withOpacity(0.15)
                              : Theme.of(context).colorScheme.surfaceContainerHighest,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (!esMio)
                              Text(autor, style: Theme.of(context).textTheme.labelSmall),
                            Text(texto),
                          ],
                        ),
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

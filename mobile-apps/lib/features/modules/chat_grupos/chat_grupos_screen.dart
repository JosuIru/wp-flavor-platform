import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_state_widgets.dart';
import '../chat/chat_conversations_screen.dart';

class ChatGruposScreen extends StatelessWidget {
  const ChatGruposScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const ChatConversationsScreen(
      title: 'Chat de Grupos',
      legacyNotice:
          'La entrada de chat de grupos ahora utiliza el chat unificado. Los grupos y conversaciones se gestionan desde la pestaña principal de chats.',
    );
  }
}

class LegacyChatGrupoMensajesScreen extends ConsumerStatefulWidget {
  final int grupoId;
  final String titulo;

  const LegacyChatGrupoMensajesScreen({
    super.key,
    required this.grupoId,
    required this.titulo,
  });

  @override
  ConsumerState<LegacyChatGrupoMensajesScreen> createState() =>
      _LegacyChatGrupoMensajesScreenState();
}

class _LegacyChatGrupoMensajesScreenState
    extends ConsumerState<LegacyChatGrupoMensajesScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  final TextEditingController _controller = TextEditingController();

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getChatGrupoMensajes(
          grupoId: widget.grupoId,
        );
  }

  Future<void> _refresh() async {
    setState(() {
      _future = ref.read(apiClientProvider).getChatGrupoMensajes(
            grupoId: widget.grupoId,
          );
    });
  }

  Future<void> _enviar() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    _controller.clear();
    await ref.read(apiClientProvider).sendChatGrupoMensaje(
          grupoId: widget.grupoId,
          mensaje: text,
        );
    await _refresh();
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
                  return const FlavorLoadingState();
                }
                final res = snapshot.data!;
                if (!res.success || res.data == null) {
                  return FlavorErrorState(
                    message: res.error ?? 'Error al cargar mensajes',
                    onRetry: _refresh,
                    icon: Icons.forum_outlined,
                  );
                }
                final mensajes = (res.data!['mensajes'] as List<dynamic>? ?? [])
                    .whereType<Map<String, dynamic>>()
                    .toList();
                if (mensajes.isEmpty) {
                  return const FlavorEmptyState(
                    icon: Icons.forum_outlined,
                    title: 'No hay mensajes',
                  );
                }
                return RefreshIndicator(
                  onRefresh: _refresh,
                  child: ListView.builder(
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
                            Text(
                              autor,
                              style: Theme.of(context).textTheme.labelSmall,
                            ),
                            const SizedBox(height: 2),
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Theme.of(context)
                                    .colorScheme
                                    .surfaceContainerHighest,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(texto),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
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

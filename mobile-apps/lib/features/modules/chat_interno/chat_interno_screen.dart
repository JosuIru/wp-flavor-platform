import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_state_widgets.dart';
import '../chat/chat_conversations_screen.dart';

class ChatInternoScreen extends StatelessWidget {
  const ChatInternoScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const ChatConversationsScreen(
      title: 'Chat Interno',
      legacyNotice:
          'La entrada de chat interno ahora utiliza el chat unificado. Las conversaciones privadas y de grupo se gestionan desde la misma bandeja.',
    );
  }
}

class LegacyChatInternoMensajesScreen extends ConsumerStatefulWidget {
  final int conversacionId;
  final int otroUsuarioId;
  final String titulo;

  const LegacyChatInternoMensajesScreen({
    super.key,
    required this.conversacionId,
    required this.otroUsuarioId,
    required this.titulo,
  });

  @override
  ConsumerState<LegacyChatInternoMensajesScreen> createState() =>
      _LegacyChatInternoMensajesScreenState();
}

class _LegacyChatInternoMensajesScreenState
    extends ConsumerState<LegacyChatInternoMensajesScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  final TextEditingController _controller = TextEditingController();

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getChatInternoMensajes(
          conversacionId: widget.conversacionId,
        );
  }

  Future<void> _refresh() async {
    setState(() {
      _future = ref.read(apiClientProvider).getChatInternoMensajes(
            conversacionId: widget.conversacionId,
          );
    });
  }

  Future<void> _enviar() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    _controller.clear();
    await ref.read(apiClientProvider).sendChatInternoMensaje(
          conversacionId: widget.conversacionId,
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
                    icon: Icons.chat_bubble_outline,
                  );
                }
                final mensajes = (res.data!['mensajes'] as List<dynamic>? ?? [])
                    .whereType<Map<String, dynamic>>()
                    .toList();
                if (mensajes.isEmpty) {
                  return const FlavorEmptyState(
                    icon: Icons.chat_bubble_outline,
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
                      final autor = m['remitente_nombre']?.toString() ?? '';
                      final esMio = m['es_mio'] == true;
                      return Align(
                        alignment: esMio
                            ? Alignment.centerRight
                            : Alignment.centerLeft,
                        child: Container(
                          margin: const EdgeInsets.symmetric(vertical: 4),
                          padding: const EdgeInsets.all(12),
                          constraints: BoxConstraints(
                            maxWidth: MediaQuery.of(context).size.width * 0.75,
                          ),
                          decoration: BoxDecoration(
                            color: esMio
                                ? Theme.of(context)
                                    .colorScheme
                                    .primary
                                    .withOpacity(0.15)
                                : Theme.of(context)
                                    .colorScheme
                                    .surfaceContainerHighest,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (!esMio)
                                Padding(
                                  padding: const EdgeInsets.only(bottom: 4),
                                  child: Text(
                                    autor,
                                    style: Theme.of(context).textTheme.labelSmall,
                                  ),
                                ),
                              Text(texto),
                            ],
                          ),
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

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/widgets/chat_widgets.dart';

/// Pantalla de chat para clientes
class ChatScreen extends ConsumerStatefulWidget {
  const ChatScreen({super.key});

  @override
  ConsumerState<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends ConsumerState<ChatScreen> {
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    // Iniciar sesión de chat al entrar
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(chatProvider.notifier).initSession();
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        0, // Porque la lista está invertida
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final chatState = ref.watch(chatProvider);

    // Scroll cuando hay nuevos mensajes
    ref.listen(chatProvider, (previous, next) {
      if (previous?.messages.length != next.messages.length) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          _scrollToBottom();
        });
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: const Text('Chat'),
        actions: [
          if (chatState.messages.isNotEmpty)
            IconButton(
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: const Text('Limpiar chat'),
                    content: const Text('¿Seguro que quieres borrar el historial de mensajes?'),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: const Text('Cancelar'),
                      ),
                      FilledButton(
                        onPressed: () {
                          Navigator.pop(context);
                          ref.read(chatProvider.notifier).clearChat();
                        },
                        child: const Text('Borrar'),
                      ),
                    ],
                  ),
                );
              },
              icon: const Icon(Icons.delete_outline),
              tooltip: 'Limpiar chat',
            ),
        ],
      ),
      body: Column(
        children: [
          // Sugerencias rápidas cuando no hay mensajes
          if (chatState.messages.isEmpty)
            _QuickSuggestions(
              onSuggestionTap: (suggestion) {
                ref.read(chatProvider.notifier).sendMessage(suggestion);
              },
            ),

          // Lista de mensajes
          Expanded(
            child: ChatMessageList(
              messages: chatState.messages,
              scrollController: _scrollController,
            ),
          ),

          // Campo de entrada
          ChatInputField(
            onSend: (message) {
              ref.read(chatProvider.notifier).sendMessage(message);
            },
            enabled: !chatState.isLoading,
            hintText: chatState.sessionId == null
                ? 'Conectando...'
                : 'Escribe tu mensaje...',
          ),
        ],
      ),
    );
  }
}

/// Sugerencias rápidas para iniciar conversación
class _QuickSuggestions extends StatelessWidget {
  final Function(String) onSuggestionTap;

  const _QuickSuggestions({required this.onSuggestionTap});

  @override
  Widget build(BuildContext context) {
    final suggestions = [
      '¿Qué horarios tienen disponibles?',
      '¿Cuánto cuestan las entradas?',
      '¿Tienen disponibilidad para este fin de semana?',
      '¿Cómo puedo hacer una reserva?',
    ];

    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Preguntas frecuentes',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                ),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: suggestions.map((suggestion) {
              return ActionChip(
                label: Text(
                  suggestion,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                onPressed: () => onSuggestionTap(suggestion),
                avatar: const Icon(Icons.chat_bubble_outline, size: 16),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }
}

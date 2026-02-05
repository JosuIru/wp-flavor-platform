import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/widgets/chat_widgets.dart';

/// Pantalla de chat IA para administradores
class AdminChatScreen extends ConsumerStatefulWidget {
  const AdminChatScreen({super.key});

  @override
  ConsumerState<AdminChatScreen> createState() => _AdminChatScreenState();
}

class _AdminChatScreenState extends ConsumerState<AdminChatScreen> {
  final _scrollController = ScrollController();

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        0, // Lista invertida
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final chatState = ref.watch(adminChatProvider);

    // Scroll automático con nuevos mensajes
    ref.listen(adminChatProvider, (previous, next) {
      if (previous?.messages.length != next.messages.length) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          _scrollToBottom();
        });
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: const Text('Chat IA Admin'),
        actions: [
          if (chatState.messages.isNotEmpty)
            IconButton(
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: const Text('Limpiar chat'),
                    content: const Text('¿Seguro que quieres borrar el historial?'),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: const Text('Cancelar'),
                      ),
                      FilledButton(
                        onPressed: () {
                          Navigator.pop(context);
                          ref.read(adminChatProvider.notifier).clearChat();
                        },
                        child: const Text('Borrar'),
                      ),
                    ],
                  ),
                );
              },
              icon: const Icon(Icons.delete_outline),
            ),
        ],
      ),
      body: Column(
        children: [
          // Sugerencias cuando está vacío
          if (chatState.messages.isEmpty)
            _AdminSuggestions(
              onSuggestionTap: (suggestion) {
                ref.read(adminChatProvider.notifier).sendMessage(suggestion);
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
              ref.read(adminChatProvider.notifier).sendMessage(message);
            },
            enabled: !chatState.isLoading,
            hintText: 'Pregunta sobre reservas, clientes, estadísticas...',
          ),
        ],
      ),
    );
  }
}

/// Sugerencias para administradores
class _AdminSuggestions extends StatelessWidget {
  final Function(String) onSuggestionTap;

  const _AdminSuggestions({required this.onSuggestionTap});

  @override
  Widget build(BuildContext context) {
    final suggestions = [
      (
        category: 'Reservas',
        icon: Icons.calendar_today,
        items: [
          '¿Cuántas reservas hay para hoy?',
          'Resumen de reservas de esta semana',
          'Lista de reservas pendientes',
        ]
      ),
      (
        category: 'Clientes',
        icon: Icons.people,
        items: [
          '¿Cuántos clientes diferentes tenemos?',
          'Clientes con más reservas',
          'Buscar cliente por email',
        ]
      ),
      (
        category: 'Estadísticas',
        icon: Icons.analytics,
        items: [
          'Resumen de ingresos del mes',
          'Tickets más vendidos',
          'Comparativa con el mes anterior',
        ]
      ),
      (
        category: 'Exportar',
        icon: Icons.file_download,
        items: [
          'Exportar reservas de hoy a CSV',
          'Lista de clientes en CSV',
          'Informe de ingresos mensual',
        ]
      ),
    ];

    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                Icons.smart_toy,
                color: Theme.of(context).colorScheme.primary,
              ),
              const SizedBox(width: 8),
              Text(
                'Asistente IA para gestión',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'Puedo ayudarte con reservas, clientes, estadísticas y más. '
            'También puedo generar informes CSV.',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                ),
          ),
          const SizedBox(height: 16),
          ...suggestions.map((category) => Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        category.icon,
                        size: 16,
                        color: Theme.of(context).colorScheme.primary.withOpacity(0.7),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        category.category,
                        style: Theme.of(context).textTheme.labelMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: category.items.map((item) {
                      return ActionChip(
                        label: Text(
                          item,
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                        onPressed: () => onSuggestionTap(item),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 16),
                ],
              )),
        ],
      ),
    );
  }
}

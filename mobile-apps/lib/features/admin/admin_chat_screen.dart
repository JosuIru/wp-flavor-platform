import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
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
  AppLocalizations get i18n => AppLocalizations.of(context)!;
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
    final i18n = AppLocalizations.of(context)!;
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
        title: Text(i18n.chatIaAdminAffb65),
        actions: [
          if (chatState.messages.isNotEmpty)
            IconButton(
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: Text(i18n.limpiarChatE7dfdf),
                    content: Text(i18n.seguroQueQuieresBorrarElHistorialFdb76f),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: Text(i18n.commonCancel),
                      ),
                      FilledButton(
                        onPressed: () {
                          Navigator.pop(context);
                          ref.read(adminChatProvider.notifier).clearChat();
                        },
                        child: Text(i18n.borrarA96f30),
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
            hintText: i18n.preguntaSobreReservasClientesEstadSD68421,
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
    final i18n = AppLocalizations.of(context)!;
    final suggestions = [
      (
        category: i18n.adminChatCategoryReservations,
        icon: Icons.calendar_today,
        items: [
          i18n.adminChatSuggestionReservationsToday,
          i18n.adminChatSuggestionReservationsWeek,
          i18n.adminChatSuggestionReservationsPending,
        ]
      ),
      (
        category: i18n.adminChatCategoryClients,
        icon: Icons.people,
        items: [
          i18n.adminChatSuggestionClientsCount,
          i18n.adminChatSuggestionTopClients,
          i18n.adminChatSuggestionFindClientByEmail,
        ]
      ),
      (
        category: i18n.adminChatCategoryStats,
        icon: Icons.analytics,
        items: [
          i18n.adminChatSuggestionMonthlyRevenue,
          i18n.adminChatSuggestionTopTickets,
          i18n.adminChatSuggestionMonthComparison,
        ]
      ),
      (
        category: i18n.adminChatCategoryExport,
        icon: Icons.file_download,
        items: [
          i18n.adminChatSuggestionExportTodayCsv,
          i18n.adminChatSuggestionExportClientsCsv,
          i18n.adminChatSuggestionExportMonthlyRevenue,
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
              Text(AppLocalizations.of(context)!.asistenteIaParaGestion,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            i18n.adminChatIntro,
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

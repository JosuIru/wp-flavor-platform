import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';
import 'escalated_chat_detail_screen.dart';

/// Pantalla de chats escalados para admin
class EscalatedChatsScreen extends ConsumerStatefulWidget {
  const EscalatedChatsScreen({super.key});

  @override
  ConsumerState<EscalatedChatsScreen> createState() => _EscalatedChatsScreenState();
}

class _EscalatedChatsScreenState extends ConsumerState<EscalatedChatsScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  String? _selectedStatus;
  List<EscalatedChat> _chats = [];
  bool _isLoading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadChats();
  }

  Future<void> _loadChats() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.getEscalatedChats(status: _selectedStatus);

      if (response.success && response.data != null) {
        final chats = (response.data!['chats'] as List?)
                ?.map((c) => EscalatedChat.fromJson(c))
                .toList() ??
            [];

        setState(() {
          _chats = chats;
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar chats';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Error de conexión: $e';
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;

    // Contar por estado
    final pendingCount = _chats.where((c) => c.isPending).length;
    final contactedCount = _chats.where((c) => c.isContacted).length;
    final resolvedCount = _chats.where((c) => c.isResolved).length;

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.chatsEscalados032c6e),
        actions: [
          IconButton(
            onPressed: _loadChats,
            icon: const Icon(Icons.refresh),
            tooltip: i18n.actualizar2e7be1,
          ),
        ],
      ),
      body: Column(
        children: [
          // Resumen
          Container(
            padding: const EdgeInsets.all(16),
            color: colorScheme.surfaceContainerHighest,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _StatBadge(
                  label: 'Pendientes',
                  count: pendingCount,
                  color: Colors.orange,
                  onTap: () {
                    setState(() {
                      _selectedStatus = _selectedStatus == 'pending' ? null : 'pending';
                    });
                    _loadChats();
                  },
                  isSelected: _selectedStatus == 'pending',
                ),
                _StatBadge(
                  label: 'Contactados',
                  count: contactedCount,
                  color: Colors.blue,
                  onTap: () {
                    setState(() {
                      _selectedStatus = _selectedStatus == 'contacted' ? null : 'contacted';
                    });
                    _loadChats();
                  },
                  isSelected: _selectedStatus == 'contacted',
                ),
                _StatBadge(
                  label: 'Resueltos',
                  count: resolvedCount,
                  color: Colors.green,
                  onTap: () {
                    setState(() {
                      _selectedStatus = _selectedStatus == 'resolved' ? null : 'resolved';
                    });
                    _loadChats();
                  },
                  isSelected: _selectedStatus == 'resolved',
                ),
              ],
            ),
          ),

          // Filtro activo
          if (_selectedStatus != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                children: [
                  Chip(
                    label: Text(i18n.escalatedFilterLabel(_getStatusLabel(_selectedStatus!))),
                    onDeleted: () {
                      setState(() {
                        _selectedStatus = null;
                      });
                      _loadChats();
                    },
                  ),
                ],
              ),
            ),

          // Lista de chats
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.error_outline,
                              size: 48,
                              color: colorScheme.error,
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _error!,
                              style: TextStyle(color: colorScheme.error),
                              textAlign: TextAlign.center,
                            ),
                            const SizedBox(height: 16),
                            FilledButton.icon(
                              onPressed: _loadChats,
                              icon: const Icon(Icons.refresh),
                              label: Text(i18n.reintentar179654),
                            ),
                          ],
                        ),
                      )
                    : _chats.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  Icons.check_circle_outline,
                                  size: 48,
                                  color: colorScheme.outline,
                                ),
                                const SizedBox(height: 16),
                                Text(
                                  'No hay chats escalados',
                                  style: TextStyle(color: colorScheme.outline),
                                ),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _loadChats,
                            child: ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: _chats.length,
                              itemBuilder: (context, index) {
                                final chat = _chats[index];
                                return _ChatCard(
                                  chat: chat,
                                  onTap: () => _navigateToDetail(chat),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }

  String _getStatusLabel(String status) {
    switch (status) {
      case 'pending':
        return 'Pendientes';
      case 'contacted':
        return 'Contactados';
      case 'resolved':
        return 'Resueltos';
      default:
        return status;
    }
  }

  void _navigateToDetail(EscalatedChat chat) async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => EscalatedChatDetailScreen(
          sessionId: chat.sessionId,
        ),
      ),
    );

    // Si hubo cambios, recargar lista
    if (result == true) {
      _loadChats();
    }
  }
}

/// Badge de estadística con contador
class _StatBadge extends StatelessWidget {
  final String label;
  final int count;
  final Color color;
  final VoidCallback onTap;
  final bool isSelected;

  const _StatBadge({
    required this.label,
    required this.count,
    required this.color,
    required this.onTap,
    this.isSelected = false,
  });

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? color.withOpacity(0.2) : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? color : Colors.transparent,
            width: 2,
          ),
        ),
        child: Column(
          children: [
            Text(
              count.toString(),
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }
}

/// Card de chat escalado
class _ChatCard extends StatelessWidget {
  final EscalatedChat chat;
  final VoidCallback onTap;

  const _ChatCard({
    required this.chat,
    required this.onTap,
  });

  Color _getStatusColor() {
    switch (chat.status) {
      case 'pending':
        return Colors.orange;
      case 'contacted':
        return Colors.blue;
      case 'resolved':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header: Estado + Fecha
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: _getStatusColor().withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 8,
                          height: 8,
                          decoration: BoxDecoration(
                            color: _getStatusColor(),
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          chat.statusDisplay,
                          style: TextStyle(
                            color: _getStatusColor(),
                            fontWeight: FontWeight.bold,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Text(
                    chat.createdAtFormatted,
                    style: TextStyle(
                      color: colorScheme.outline,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              // Motivo
              Text(
                chat.reason,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),

              const SizedBox(height: 8),

              // Resumen
              Text(
                chat.summary,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: colorScheme.onSurfaceVariant,
                  fontSize: 14,
                ),
              ),

              const SizedBox(height: 12),

              // Footer: Idioma + Mensajes
              Row(
                children: [
                  Icon(
                    Icons.language,
                    size: 16,
                    color: colorScheme.outline,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    chat.language.toUpperCase(),
                    style: TextStyle(
                      color: colorScheme.outline,
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Icon(
                    Icons.chat_bubble_outline,
                    size: 16,
                    color: colorScheme.outline,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    '${chat.messageCount} mensajes',
                    style: TextStyle(
                      color: colorScheme.outline,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),

              // Notas (si las hay)
              if (chat.notes != null && chat.notes!.isNotEmpty) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: colorScheme.surfaceContainerHighest,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        Icons.note,
                        size: 16,
                        color: colorScheme.outline,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          chat.notes!,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: colorScheme.onSurfaceVariant,
                            fontSize: 12,
                            fontStyle: FontStyle.italic,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

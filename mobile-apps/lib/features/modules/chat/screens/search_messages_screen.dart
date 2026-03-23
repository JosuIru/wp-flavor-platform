import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../../core/services/chat_service.dart';

/// Pantalla de búsqueda global de mensajes, usuarios y grupos
class SearchScreen extends ConsumerStatefulWidget {
  final String? conversationId;

  const SearchScreen({super.key, this.conversationId});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen>
    with SingleTickerProviderStateMixin {
  final ChatService _chatService = ChatService();
  final TextEditingController _searchController = TextEditingController();
  final FocusNode _focusNode = FocusNode();

  late TabController _tabController;
  Timer? _debounce;

  List<ChatMessage> _messages = [];
  List<ChatUser> _users = [];
  List<ChatGroup> _groups = [];
  List<String> _recentSearches = [];

  bool _isLoading = false;
  String _query = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(
      length: widget.conversationId != null ? 1 : 3,
      vsync: this,
    );
    _loadRecentSearches();

    // Auto-focus
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _focusNode.requestFocus();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _focusNode.dispose();
    _tabController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _loadRecentSearches() async {
    // TODO: Cargar de SharedPreferences
    setState(() {
      _recentSearches = ['compras', 'reunión', 'foto', 'evento'];
    });
  }

  void _onSearchChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () {
      if (value.trim().length >= 2) {
        _performSearch(value.trim());
      } else {
        setState(() {
          _messages = [];
          _users = [];
          _groups = [];
          _query = '';
        });
      }
    });
  }

  Future<void> _performSearch(String query) async {
    setState(() {
      _isLoading = true;
      _query = query;
    });

    try {
      if (widget.conversationId != null) {
        // Búsqueda solo en conversación específica
        final messages = await _chatService.searchMessages(
          query,
          conversationId: widget.conversationId,
        );
        setState(() {
          _messages = messages;
          _isLoading = false;
        });
      } else {
        // Búsqueda global
        final results = await Future.wait([
          _chatService.searchMessages(query),
          _chatService.searchUsers(query),
          _chatService.searchGroups(query),
        ]);

        setState(() {
          _messages = results[0] as List<ChatMessage>;
          _users = results[1] as List<ChatUser>;
          _groups = results[2] as List<ChatGroup>;
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error en búsqueda: $e')),
        );
      }
    }
  }

  void _saveRecentSearch(String query) {
    if (!_recentSearches.contains(query)) {
      setState(() {
        _recentSearches.insert(0, query);
        if (_recentSearches.length > 10) {
          _recentSearches.removeLast();
        }
      });
      // TODO: Guardar en SharedPreferences
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: TextField(
          controller: _searchController,
          focusNode: _focusNode,
          onChanged: _onSearchChanged,
          onSubmitted: (value) {
            if (value.trim().isNotEmpty) {
              _saveRecentSearch(value.trim());
            }
          },
          decoration: InputDecoration(
            hintText: widget.conversationId != null
                ? 'Buscar en esta conversación...'
                : 'Buscar mensajes, personas, grupos...',
            border: InputBorder.none,
            suffixIcon: _searchController.text.isNotEmpty
                ? IconButton(
                    icon: const Icon(Icons.clear),
                    onPressed: () {
                      _searchController.clear();
                      _onSearchChanged('');
                    },
                  )
                : null,
          ),
          style: const TextStyle(fontSize: 16),
        ),
        bottom: widget.conversationId == null
            ? TabBar(
                controller: _tabController,
                tabs: [
                  Tab(
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.message, size: 18),
                        const SizedBox(width: 4),
                        Text('Mensajes${_messages.isNotEmpty ? ' (${_messages.length})' : ''}'),
                      ],
                    ),
                  ),
                  Tab(
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.person, size: 18),
                        const SizedBox(width: 4),
                        Text('Personas${_users.isNotEmpty ? ' (${_users.length})' : ''}'),
                      ],
                    ),
                  ),
                  Tab(
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.group, size: 18),
                        const SizedBox(width: 4),
                        Text('Grupos${_groups.isNotEmpty ? ' (${_groups.length})' : ''}'),
                      ],
                    ),
                  ),
                ],
              )
            : null,
      ),
      body: _query.isEmpty
          ? _buildRecentSearches()
          : _isLoading
              ? const Center(child: CircularProgressIndicator())
              : widget.conversationId != null
                  ? _buildMessagesList()
                  : TabBarView(
                      controller: _tabController,
                      children: [
                        _buildMessagesList(),
                        _buildUsersList(),
                        _buildGroupsList(),
                      ],
                    ),
    );
  }

  Widget _buildRecentSearches() {
    if (_recentSearches.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.search,
              size: 64,
              color: Theme.of(context).colorScheme.outline,
            ),
            const SizedBox(height: 16),
            Text(
              'Busca mensajes, personas o grupos',
              style: TextStyle(
                color: Theme.of(context).colorScheme.outline,
              ),
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Text(
                'Búsquedas recientes',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: Theme.of(context).colorScheme.outline,
                ),
              ),
              const Spacer(),
              TextButton(
                onPressed: () {
                  setState(() => _recentSearches.clear());
                  // TODO: Limpiar SharedPreferences
                },
                child: const Text('Limpiar'),
              ),
            ],
          ),
        ),
        Expanded(
          child: ListView.builder(
            itemCount: _recentSearches.length,
            itemBuilder: (context, index) {
              final search = _recentSearches[index];
              return ListTile(
                leading: const Icon(Icons.history),
                title: Text(search),
                trailing: IconButton(
                  icon: const Icon(Icons.close, size: 18),
                  onPressed: () {
                    setState(() => _recentSearches.removeAt(index));
                  },
                ),
                onTap: () {
                  _searchController.text = search;
                  _performSearch(search);
                },
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildMessagesList() {
    if (_messages.isEmpty) {
      return _buildEmptyState(
        icon: Icons.message_outlined,
        message: 'No se encontraron mensajes',
      );
    }

    return ListView.builder(
      itemCount: _messages.length,
      itemBuilder: (context, index) {
        final message = _messages[index];
        return _MessageSearchResult(
          message: message,
          query: _query,
          onTap: () => _navigateToMessage(message),
        );
      },
    );
  }

  Widget _buildUsersList() {
    if (_users.isEmpty) {
      return _buildEmptyState(
        icon: Icons.person_outlined,
        message: 'No se encontraron personas',
      );
    }

    return ListView.builder(
      itemCount: _users.length,
      itemBuilder: (context, index) {
        final user = _users[index];
        return _UserSearchResult(
          user: user,
          query: _query,
          onTap: () => _navigateToUser(user),
        );
      },
    );
  }

  Widget _buildGroupsList() {
    if (_groups.isEmpty) {
      return _buildEmptyState(
        icon: Icons.group_outlined,
        message: 'No se encontraron grupos',
      );
    }

    return ListView.builder(
      itemCount: _groups.length,
      itemBuilder: (context, index) {
        final group = _groups[index];
        return _GroupSearchResult(
          group: group,
          query: _query,
          onTap: () => _navigateToGroup(group),
        );
      },
    );
  }

  Widget _buildEmptyState({required IconData icon, required String message}) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 64, color: Theme.of(context).colorScheme.outline),
          const SizedBox(height: 16),
          Text(
            message,
            style: TextStyle(color: Theme.of(context).colorScheme.outline),
          ),
        ],
      ),
    );
  }

  void _navigateToMessage(ChatMessage message) {
    _saveRecentSearch(_query);
    // TODO: Navegar al chat y saltar al mensaje específico
    Navigator.pop(context, message);
  }

  void _navigateToUser(ChatUser user) {
    _saveRecentSearch(_query);
    // TODO: Navegar al perfil o chat
    Navigator.pop(context, user);
  }

  void _navigateToGroup(ChatGroup group) {
    _saveRecentSearch(_query);
    // TODO: Navegar al grupo
    Navigator.pop(context, group);
  }
}

/// Resultado de búsqueda de mensaje
class _MessageSearchResult extends StatelessWidget {
  final ChatMessage message;
  final String query;
  final VoidCallback onTap;

  const _MessageSearchResult({
    required this.message,
    required this.query,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: CircleAvatar(
        backgroundImage: message.senderAvatar != null
            ? CachedNetworkImageProvider(message.senderAvatar!)
            : null,
        child: message.senderAvatar == null
            ? Text(message.senderName[0].toUpperCase())
            : null,
      ),
      title: Row(
        children: [
          Expanded(
            child: Text(
              message.senderName,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          Text(
            _formatDate(message.timestamp),
            style: TextStyle(
              fontSize: 12,
              color: Theme.of(context).colorScheme.outline,
            ),
          ),
        ],
      ),
      subtitle: _buildHighlightedText(context),
      onTap: onTap,
    );
  }

  Widget _buildHighlightedText(BuildContext context) {
    final text = message.content;
    final lowerText = text.toLowerCase();
    final lowerQuery = query.toLowerCase();
    final index = lowerText.indexOf(lowerQuery);

    if (index == -1) {
      return Text(
        text,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
      );
    }

    // Mostrar contexto alrededor del match
    final start = (index - 20).clamp(0, text.length);
    final end = (index + query.length + 30).clamp(0, text.length);
    final snippet = (start > 0 ? '...' : '') +
        text.substring(start, end) +
        (end < text.length ? '...' : '');

    final snippetLower = snippet.toLowerCase();
    final matchIndex = snippetLower.indexOf(lowerQuery);

    return RichText(
      maxLines: 2,
      overflow: TextOverflow.ellipsis,
      text: TextSpan(
        style: DefaultTextStyle.of(context).style.copyWith(
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
        children: [
          TextSpan(text: snippet.substring(0, matchIndex)),
          TextSpan(
            text: snippet.substring(matchIndex, matchIndex + query.length),
            style: TextStyle(
              backgroundColor: Theme.of(context).colorScheme.primaryContainer,
              fontWeight: FontWeight.bold,
            ),
          ),
          TextSpan(text: snippet.substring(matchIndex + query.length)),
        ],
      ),
    );
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    final diff = now.difference(date);

    if (diff.inDays == 0) {
      return '${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
    } else if (diff.inDays < 7) {
      const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
      return days[date.weekday % 7];
    } else {
      return '${date.day}/${date.month}/${date.year}';
    }
  }
}

/// Resultado de búsqueda de usuario
class _UserSearchResult extends StatelessWidget {
  final ChatUser user;
  final String query;
  final VoidCallback onTap;

  const _UserSearchResult({
    required this.user,
    required this.query,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Stack(
        children: [
          CircleAvatar(
            backgroundImage: user.avatarUrl != null
                ? CachedNetworkImageProvider(user.avatarUrl!)
                : null,
            child: user.avatarUrl == null
                ? Text(user.name[0].toUpperCase())
                : null,
          ),
          if (user.isOnline)
            Positioned(
              bottom: 0,
              right: 0,
              child: Container(
                width: 14,
                height: 14,
                decoration: BoxDecoration(
                  color: Colors.green,
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: Theme.of(context).scaffoldBackgroundColor,
                    width: 2,
                  ),
                ),
              ),
            ),
        ],
      ),
      title: _buildHighlightedName(context),
      subtitle: user.bio != null
          ? Text(
              user.bio!,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            )
          : user.isOnline
              ? const Text('En línea', style: TextStyle(color: Colors.green))
              : null,
      onTap: onTap,
    );
  }

  Widget _buildHighlightedName(BuildContext context) {
    final name = user.name;
    final lowerName = name.toLowerCase();
    final lowerQuery = query.toLowerCase();
    final index = lowerName.indexOf(lowerQuery);

    if (index == -1) {
      return Text(name);
    }

    return RichText(
      text: TextSpan(
        style: DefaultTextStyle.of(context).style.copyWith(
          fontWeight: FontWeight.w500,
        ),
        children: [
          TextSpan(text: name.substring(0, index)),
          TextSpan(
            text: name.substring(index, index + query.length),
            style: TextStyle(
              backgroundColor: Theme.of(context).colorScheme.primaryContainer,
            ),
          ),
          TextSpan(text: name.substring(index + query.length)),
        ],
      ),
    );
  }
}

/// Resultado de búsqueda de grupo
class _GroupSearchResult extends StatelessWidget {
  final ChatGroup group;
  final String query;
  final VoidCallback onTap;

  const _GroupSearchResult({
    required this.group,
    required this.query,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: CircleAvatar(
        backgroundImage: group.avatarUrl != null
            ? CachedNetworkImageProvider(group.avatarUrl!)
            : null,
        child: group.avatarUrl == null
            ? const Icon(Icons.group)
            : null,
      ),
      title: _buildHighlightedName(context),
      subtitle: Row(
        children: [
          Icon(
            _getPrivacyIcon(group.privacy),
            size: 14,
            color: Theme.of(context).colorScheme.outline,
          ),
          const SizedBox(width: 4),
          Text(
            '${group.memberCount} miembros',
            style: TextStyle(color: Theme.of(context).colorScheme.outline),
          ),
        ],
      ),
      trailing: group.privacy == GroupPrivacy.public
          ? FilledButton.tonal(
              onPressed: () {
                // TODO: Unirse al grupo
              },
              child: const Text('Unirse'),
            )
          : null,
      onTap: onTap,
    );
  }

  Widget _buildHighlightedName(BuildContext context) {
    final name = group.name;
    final lowerName = name.toLowerCase();
    final lowerQuery = query.toLowerCase();
    final index = lowerName.indexOf(lowerQuery);

    if (index == -1) {
      return Text(name, style: const TextStyle(fontWeight: FontWeight.w500));
    }

    return RichText(
      text: TextSpan(
        style: DefaultTextStyle.of(context).style.copyWith(
          fontWeight: FontWeight.w500,
        ),
        children: [
          TextSpan(text: name.substring(0, index)),
          TextSpan(
            text: name.substring(index, index + query.length),
            style: TextStyle(
              backgroundColor: Theme.of(context).colorScheme.primaryContainer,
            ),
          ),
          TextSpan(text: name.substring(index + query.length)),
        ],
      ),
    );
  }

  IconData _getPrivacyIcon(GroupPrivacy privacy) {
    switch (privacy) {
      case GroupPrivacy.public:
        return Icons.public;
      case GroupPrivacy.private:
        return Icons.lock_outline;
      case GroupPrivacy.secret:
        return Icons.visibility_off;
    }
  }
}

/// Filtros avanzados de búsqueda
class SearchFiltersSheet extends StatefulWidget {
  final SearchFilters? initialFilters;
  final ValueChanged<SearchFilters> onApply;

  const SearchFiltersSheet({
    super.key,
    this.initialFilters,
    required this.onApply,
  });

  @override
  State<SearchFiltersSheet> createState() => _SearchFiltersSheetState();
}

class _SearchFiltersSheetState extends State<SearchFiltersSheet> {
  late SearchFilters _filters;

  @override
  void initState() {
    super.initState();
    _filters = widget.initialFilters ?? SearchFilters();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text(
                'Filtros de búsqueda',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const Spacer(),
              TextButton(
                onPressed: () {
                  setState(() => _filters = SearchFilters());
                },
                child: const Text('Limpiar'),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Tipo de contenido
          const Text('Tipo de contenido', style: TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              _buildFilterChip('Texto', MessageType.text),
              _buildFilterChip('Fotos', MessageType.image),
              _buildFilterChip('Videos', MessageType.video),
              _buildFilterChip('Archivos', MessageType.file),
              _buildFilterChip('Audio', MessageType.audio),
              _buildFilterChip('Enlaces', MessageType.text), // TODO: Tipo link
            ],
          ),
          const SizedBox(height: 16),

          // Rango de fechas
          const Text('Fecha', style: TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () async {
                    final date = await showDatePicker(
                      context: context,
                      initialDate: _filters.fromDate ?? DateTime.now(),
                      firstDate: DateTime(2020),
                      lastDate: DateTime.now(),
                    );
                    if (date != null) {
                      setState(() => _filters = _filters.copyWith(fromDate: date));
                    }
                  },
                  icon: const Icon(Icons.calendar_today, size: 18),
                  label: Text(_filters.fromDate != null
                      ? '${_filters.fromDate!.day}/${_filters.fromDate!.month}/${_filters.fromDate!.year}'
                      : 'Desde'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () async {
                    final date = await showDatePicker(
                      context: context,
                      initialDate: _filters.toDate ?? DateTime.now(),
                      firstDate: DateTime(2020),
                      lastDate: DateTime.now(),
                    );
                    if (date != null) {
                      setState(() => _filters = _filters.copyWith(toDate: date));
                    }
                  },
                  icon: const Icon(Icons.calendar_today, size: 18),
                  label: Text(_filters.toDate != null
                      ? '${_filters.toDate!.day}/${_filters.toDate!.month}/${_filters.toDate!.year}'
                      : 'Hasta'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // De usuario específico
          const Text('De usuario', style: TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          TextField(
            decoration: const InputDecoration(
              hintText: 'Nombre de usuario...',
              border: OutlineInputBorder(),
              prefixIcon: Icon(Icons.person),
            ),
            onChanged: (value) {
              setState(() => _filters = _filters.copyWith(fromUser: value.isEmpty ? null : value));
            },
          ),
          const SizedBox(height: 24),

          // Botón aplicar
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: () {
                widget.onApply(_filters);
                Navigator.pop(context);
              },
              child: const Text('Aplicar filtros'),
            ),
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, MessageType type) {
    final isSelected = _filters.types?.contains(type) ?? false;

    return FilterChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        setState(() {
          final currentTypes = _filters.types ?? [];
          if (selected) {
            _filters = _filters.copyWith(types: [...currentTypes, type]);
          } else {
            _filters = _filters.copyWith(
              types: currentTypes.where((t) => t != type).toList(),
            );
          }
        });
      },
    );
  }
}

/// Modelo de filtros de búsqueda
class SearchFilters {
  final List<MessageType>? types;
  final DateTime? fromDate;
  final DateTime? toDate;
  final String? fromUser;
  final bool? onlyWithMedia;

  SearchFilters({
    this.types,
    this.fromDate,
    this.toDate,
    this.fromUser,
    this.onlyWithMedia,
  });

  SearchFilters copyWith({
    List<MessageType>? types,
    DateTime? fromDate,
    DateTime? toDate,
    String? fromUser,
    bool? onlyWithMedia,
  }) {
    return SearchFilters(
      types: types ?? this.types,
      fromDate: fromDate ?? this.fromDate,
      toDate: toDate ?? this.toDate,
      fromUser: fromUser ?? this.fromUser,
      onlyWithMedia: onlyWithMedia ?? this.onlyWithMedia,
    );
  }

  bool get hasFilters =>
      (types?.isNotEmpty ?? false) ||
      fromDate != null ||
      toDate != null ||
      fromUser != null ||
      onlyWithMedia == true;
}

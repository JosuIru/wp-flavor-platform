import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:image_picker/image_picker.dart';
import '../../../../core/widgets/flavor_initials_avatar.dart';
import '../../../../core/services/chat_service.dart';
import '../../../../core/widgets/flavor_state_widgets.dart';

/// Pantalla para crear un nuevo grupo
class CreateGroupScreen extends ConsumerStatefulWidget {
  final List<ChatUser>? preselectedUsers;

  const CreateGroupScreen({super.key, this.preselectedUsers});

  @override
  ConsumerState<CreateGroupScreen> createState() => _CreateGroupScreenState();
}

class _CreateGroupScreenState extends ConsumerState<CreateGroupScreen> {
  final ChatService _chatService = ChatService();
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();
  final TextEditingController _searchController = TextEditingController();

  GroupPrivacy _privacy = GroupPrivacy.private;
  String? _avatarPath;
  List<ChatUser> _selectedUsers = [];
  List<ChatUser> _searchResults = [];
  List<ChatUser> _contacts = [];
  bool _isLoading = false;

  int _currentStep = 0;

  @override
  void initState() {
    super.initState();
    if (widget.preselectedUsers != null) {
      _selectedUsers = List.from(widget.preselectedUsers!);
    }
    _loadContacts();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadContacts() async {
    setState(() => _isLoading = true);

    try {
      final contacts = await _chatService.getContacts();
      setState(() {
        _contacts = contacts;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _searchUsers(String query) async {
    if (query.isEmpty) {
      setState(() {
        _searchResults = [];
      });
      return;
    }

    try {
      final results = await _chatService.searchUsers(query);
      setState(() {
        _searchResults = results;
      });
    } catch (e) {
      debugPrint('Search users error: $e');
    }
  }

  void _toggleUser(ChatUser user) {
    setState(() {
      if (_selectedUsers.any((u) => u.id == user.id)) {
        _selectedUsers.removeWhere((u) => u.id == user.id);
      } else {
        _selectedUsers.add(user);
      }
    });
  }

  Future<void> _pickAvatar() async {
    final picker = ImagePicker();
    final image = await picker.pickImage(source: ImageSource.gallery);

    if (image != null) {
      setState(() => _avatarPath = image.path);
    }
  }

  Future<void> _createGroup() async {
    if (_nameController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('El nombre del grupo es obligatorio')),
      );
      return;
    }

    if (_selectedUsers.length < 2) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Selecciona al menos 2 participantes')),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      final group = await _chatService.createGroup(
        name: _nameController.text.trim(),
        description: _descriptionController.text.trim(),
        members: _selectedUsers.map((u) => u.id).toList(),
        privacy: _privacy,
        avatarPath: _avatarPath,
      );

      if (mounted) {
        Navigator.pop(context, group);
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al crear grupo: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_currentStep == 0 ? 'Nuevo grupo' : 'Añadir participantes'),
        actions: [
          if (_currentStep == 0)
            TextButton(
              onPressed: _selectedUsers.length >= 2
                  ? () => setState(() => _currentStep = 1)
                  : null,
              child: const Text('Siguiente'),
            ),
          if (_currentStep == 1)
            TextButton(
              onPressed: _nameController.text.trim().isNotEmpty && !_isLoading
                  ? _createGroup
                  : null,
              child: _isLoading
                  ? const FlavorInlineSpinner()
                  : const Text('Crear'),
            ),
        ],
      ),
      body: _currentStep == 0
          ? _buildMemberSelection()
          : _buildGroupDetails(),
    );
  }

  Widget _buildMemberSelection() {
    final colorScheme = Theme.of(context).colorScheme;

    return Column(
      children: [
        // Usuarios seleccionados
        if (_selectedUsers.isNotEmpty)
          Container(
            height: 100,
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: _selectedUsers.length,
              itemBuilder: (context, index) {
                final user = _selectedUsers[index];
                return _buildSelectedUserChip(user);
              },
            ),
          ),

        // Barra de búsqueda
        Padding(
          padding: const EdgeInsets.all(16),
          child: TextField(
            controller: _searchController,
            onChanged: _searchUsers,
            decoration: InputDecoration(
              hintText: 'Buscar contactos...',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: _searchController.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () {
                        _searchController.clear();
                        _searchUsers('');
                      },
                    )
                  : null,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              filled: true,
              fillColor: colorScheme.surfaceContainerHighest,
            ),
          ),
        ),

        // Contador
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            children: [
              Text(
                'Participantes: ${_selectedUsers.length}',
                style: TextStyle(
                  color: colorScheme.outline,
                  fontWeight: FontWeight.w500,
                ),
              ),
              const Spacer(),
              if (_selectedUsers.length < 2)
                Text(
                  'Mínimo 2 participantes',
                  style: TextStyle(
                    color: colorScheme.error,
                    fontSize: 12,
                  ),
                ),
            ],
          ),
        ),

        const Divider(),

        // Lista de contactos
        Expanded(
          child: _isLoading
              ? const FlavorLoadingState()
              : _buildContactsList(),
        ),
      ],
    );
  }

  Widget _buildSelectedUserChip(ChatUser user) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Stack(
            children: [
              CircleAvatar(
                radius: 28,
                backgroundImage: user.avatarUrl != null
                    ? CachedNetworkImageProvider(user.avatarUrl!)
                    : null,
                child: user.avatarUrl == null
                    ? Text(FlavorInitialsAvatar.initialsFor(user.name))
                    : null,
              ),
              Positioned(
                top: -4,
                right: -4,
                child: GestureDetector(
                  onTap: () => _toggleUser(user),
                  child: Container(
                    padding: const EdgeInsets.all(2),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.error,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.close, size: 14, color: Colors.white),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          SizedBox(
            width: 60,
            child: Text(
              user.name,
              style: const TextStyle(fontSize: 12),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildContactsList() {
    final displayList = _searchController.text.isNotEmpty
        ? _searchResults
        : _contacts;

    if (displayList.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.person_search,
              size: 64,
              color: Theme.of(context).colorScheme.outline,
            ),
            const SizedBox(height: 16),
            Text(
              _searchController.text.isNotEmpty
                  ? 'No se encontraron resultados'
                  : 'No tienes contactos',
              style: TextStyle(
                color: Theme.of(context).colorScheme.outline,
              ),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      itemCount: displayList.length,
      itemBuilder: (context, index) {
        final user = displayList[index];
        final isSelected = _selectedUsers.any((u) => u.id == user.id);

        return ListTile(
          leading: Stack(
            children: [
              CircleAvatar(
                backgroundImage: user.avatarUrl != null
                    ? CachedNetworkImageProvider(user.avatarUrl!)
                    : null,
                child: user.avatarUrl == null
                    ? Text(FlavorInitialsAvatar.initialsFor(user.name))
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
          title: Text(user.name),
          subtitle: user.bio != null ? Text(user.bio!) : null,
          trailing: Checkbox(
            value: isSelected,
            onChanged: (_) => _toggleUser(user),
            shape: const CircleBorder(),
          ),
          onTap: () => _toggleUser(user),
        );
      },
    );
  }

  Widget _buildGroupDetails() {
    final colorScheme = Theme.of(context).colorScheme;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Avatar del grupo
          Center(
            child: GestureDetector(
              onTap: _pickAvatar,
              child: Stack(
                children: [
                  CircleAvatar(
                    radius: 50,
                    backgroundColor: colorScheme.primaryContainer,
                    backgroundImage: _avatarPath != null
                        ? FileImage(File(_avatarPath!))
                        : null,
                    child: _avatarPath == null
                        ? Icon(
                            Icons.group,
                            size: 40,
                            color: colorScheme.onPrimaryContainer,
                          )
                        : null,
                  ),
                  Positioned(
                    bottom: 0,
                    right: 0,
                    child: CircleAvatar(
                      radius: 16,
                      backgroundColor: colorScheme.primary,
                      child: const Icon(
                        Icons.camera_alt,
                        size: 18,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Nombre del grupo
          TextField(
            controller: _nameController,
            decoration: const InputDecoration(
              labelText: 'Nombre del grupo *',
              hintText: 'Ej: Grupo de trabajo',
              border: OutlineInputBorder(),
            ),
            maxLength: 50,
            onChanged: (_) => setState(() {}),
          ),
          const SizedBox(height: 16),

          // Descripción
          TextField(
            controller: _descriptionController,
            decoration: const InputDecoration(
              labelText: 'Descripción (opcional)',
              hintText: 'De qué trata este grupo...',
              border: OutlineInputBorder(),
            ),
            maxLines: 3,
            maxLength: 500,
          ),
          const SizedBox(height: 24),

          // Privacidad
          Text(
            'Privacidad',
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: colorScheme.primary,
            ),
          ),
          const SizedBox(height: 8),
          _buildPrivacyOption(
            GroupPrivacy.public,
            'Público',
            'Cualquiera puede encontrar y unirse',
            Icons.public,
          ),
          _buildPrivacyOption(
            GroupPrivacy.private,
            'Privado',
            'Solo con invitación o enlace',
            Icons.lock_outline,
          ),
          _buildPrivacyOption(
            GroupPrivacy.secret,
            'Secreto',
            'Solo invitación directa, no aparece en búsquedas',
            Icons.visibility_off,
          ),
          const SizedBox(height: 24),

          // Participantes
          Text(
            'Participantes (${_selectedUsers.length})',
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: colorScheme.primary,
            ),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              ..._selectedUsers.map((user) => Chip(
                avatar: CircleAvatar(
                  backgroundImage: user.avatarUrl != null
                      ? CachedNetworkImageProvider(user.avatarUrl!)
                      : null,
                  child: user.avatarUrl == null
                      ? Text(user.name[0])
                      : null,
                ),
                label: Text(user.name),
                onDeleted: () => _toggleUser(user),
              )),
              ActionChip(
                avatar: const Icon(Icons.add),
                label: const Text('Añadir'),
                onPressed: () => setState(() => _currentStep = 0),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPrivacyOption(
    GroupPrivacy value,
    String title,
    String subtitle,
    IconData icon,
  ) {
    final isSelected = _privacy == value;
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      color: isSelected ? colorScheme.primaryContainer : null,
      child: ListTile(
        leading: Icon(
          icon,
          color: isSelected ? colorScheme.primary : null,
        ),
        title: Text(
          title,
          style: TextStyle(
            fontWeight: isSelected ? FontWeight.bold : null,
          ),
        ),
        subtitle: Text(subtitle),
        trailing: isSelected
            ? Icon(Icons.check_circle, color: colorScheme.primary)
            : null,
        onTap: () => setState(() => _privacy = value),
      ),
    );
  }
}

/// Pantalla para seleccionar contactos (reutilizable)
class ContactPickerScreen extends ConsumerStatefulWidget {
  final String title;
  final List<ChatUser>? excludeUsers;
  final bool multiSelect;
  final int? maxSelection;

  const ContactPickerScreen({
    super.key,
    this.title = 'Seleccionar contactos',
    this.excludeUsers,
    this.multiSelect = true,
    this.maxSelection,
  });

  @override
  ConsumerState<ContactPickerScreen> createState() => _ContactPickerScreenState();
}

class _ContactPickerScreenState extends ConsumerState<ContactPickerScreen> {
  final ChatService _chatService = ChatService();
  final TextEditingController _searchController = TextEditingController();

  List<ChatUser> _contacts = [];
  List<ChatUser> _searchResults = [];
  final List<ChatUser> _selectedUsers = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadContacts();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadContacts() async {
    setState(() => _isLoading = true);

    try {
      final contacts = await _chatService.getContacts();
      final excludeIds = widget.excludeUsers?.map((u) => u.id).toSet() ?? {};

      setState(() {
        _contacts = contacts.where((c) => !excludeIds.contains(c.id)).toList();
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _searchUsers(String query) async {
    if (query.isEmpty) {
      setState(() => _searchResults = []);
      return;
    }

    final excludeIds = widget.excludeUsers?.map((u) => u.id).toSet() ?? {};
    final results = await _chatService.searchUsers(query);

    setState(() {
      _searchResults = results.where((c) => !excludeIds.contains(c.id)).toList();
    });
  }

  void _toggleUser(ChatUser user) {
    if (!widget.multiSelect) {
      Navigator.pop(context, [user]);
      return;
    }

    setState(() {
      if (_selectedUsers.any((u) => u.id == user.id)) {
        _selectedUsers.removeWhere((u) => u.id == user.id);
      } else {
        if (widget.maxSelection != null &&
            _selectedUsers.length >= widget.maxSelection!) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Máximo ${widget.maxSelection} usuarios'),
            ),
          );
          return;
        }
        _selectedUsers.add(user);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final displayList = _searchController.text.isNotEmpty
        ? _searchResults
        : _contacts;

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        actions: [
          if (widget.multiSelect && _selectedUsers.isNotEmpty)
            TextButton.icon(
              onPressed: () => Navigator.pop(context, _selectedUsers),
              icon: const Icon(Icons.check),
              label: Text('(${_selectedUsers.length})'),
            ),
        ],
      ),
      body: Column(
        children: [
          // Búsqueda
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _searchController,
              onChanged: _searchUsers,
              decoration: InputDecoration(
                hintText: 'Buscar...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),

          // Lista
          Expanded(
            child: _isLoading
                ? const FlavorLoadingState()
                : displayList.isEmpty
                    ? const FlavorEmptyState(
                        icon: Icons.person_search_outlined,
                        title: 'No se encontraron contactos',
                      )
                    : ListView.builder(
                        itemCount: displayList.length,
                        itemBuilder: (context, index) {
                          final user = displayList[index];
                          final isSelected = _selectedUsers.any((u) => u.id == user.id);

                          return ListTile(
                            leading: CircleAvatar(
                              backgroundImage: user.avatarUrl != null
                                  ? CachedNetworkImageProvider(user.avatarUrl!)
                                  : null,
                              child: user.avatarUrl == null
                                  ? Text(FlavorInitialsAvatar.initialsFor(user.name))
                                  : null,
                            ),
                            title: Text(user.name),
                            subtitle: user.bio != null ? Text(user.bio!) : null,
                            trailing: widget.multiSelect
                                ? Checkbox(
                                    value: isSelected,
                                    onChanged: (_) => _toggleUser(user),
                                  )
                                : const Icon(Icons.chevron_right),
                            onTap: () => _toggleUser(user),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}

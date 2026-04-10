import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:image_picker/image_picker.dart';
import 'package:share_plus/share_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../../core/widgets/flavor_initials_avatar.dart';
import '../../../../core/services/chat_service.dart';
import '../../../../core/services/media_upload_service.dart';
import '../../../../core/widgets/flavor_state_widgets.dart';
import '../chat_main_screen.dart';
import 'search_messages_screen.dart';
import 'call_screen.dart';
import 'create_group_screen.dart';

/// Pantalla de información y configuración de grupo
class GroupInfoScreen extends ConsumerStatefulWidget {
  final String groupId;

  const GroupInfoScreen({super.key, required this.groupId});

  @override
  ConsumerState<GroupInfoScreen> createState() => _GroupInfoScreenState();
}

class _GroupInfoScreenState extends ConsumerState<GroupInfoScreen> {
  final ChatService _chatService = ChatService();
  ChatGroup? _group;
  List<GroupMember> _members = [];
  bool _isLoading = true;
  bool _isAdmin = false;
  String? _currentUserId;
  bool _notificationsEnabled = true;

  String get _notificationsPrefKey => 'chat_group_notifications_${widget.groupId}';

  @override
  void initState() {
    super.initState();
    _loadGroupInfo();
    _loadNotificationPreference();
  }

  Future<void> _loadNotificationPreference() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final enabled = prefs.getBool(_notificationsPrefKey) ?? true;
      if (mounted) {
        setState(() => _notificationsEnabled = enabled);
      }
    } catch (e) {
      // Usar valor por defecto
    }
  }

  Future<void> _setNotificationPreference(bool enabled) async {
    setState(() => _notificationsEnabled = enabled);
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool(_notificationsPrefKey, enabled);
    } catch (e) {
      // Ignorar error
    }
  }

  Future<void> _loadGroupInfo() async {
    setState(() => _isLoading = true);

    try {
      final group = await _chatService.getGroupInfo(widget.groupId);
      final members = await _chatService.getGroupMembers(widget.groupId);
      final currentUser = _chatService.currentUser;

      setState(() {
        _group = group;
        _members = members;
        _currentUserId = currentUser?.id;
        _isAdmin = members.any((m) =>
          m.userId == currentUser?.id &&
          (m.role == GroupRole.admin || m.role == GroupRole.owner)
        );
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al cargar información: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(),
        body: const FlavorLoadingState(),
      );
    }

    if (_group == null) {
      return Scaffold(
        appBar: AppBar(),
        body: const FlavorEmptyState(
          icon: Icons.group_off_outlined,
          title: 'Grupo no encontrado',
        ),
      );
    }

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // Header con imagen del grupo
          SliverAppBar(
            expandedHeight: 200,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  if (_group!.avatarUrl != null)
                    CachedNetworkImage(
                      imageUrl: _group!.avatarUrl!,
                      fit: BoxFit.cover,
                    )
                  else
                    Container(
                      color: colorScheme.primaryContainer,
                      child: Icon(
                        Icons.group,
                        size: 80,
                        color: colorScheme.onPrimaryContainer,
                      ),
                    ),
                  // Gradiente oscuro
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.transparent,
                          Colors.black.withOpacity(0.7),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              title: Text(_group!.name),
            ),
            actions: [
              if (_isAdmin)
                IconButton(
                  icon: const Icon(Icons.edit),
                  onPressed: _editGroup,
                ),
              PopupMenuButton<String>(
                onSelected: _handleMenuAction,
                itemBuilder: (context) => [
                  const PopupMenuItem(
                    value: 'search',
                    child: ListTile(
                      leading: Icon(Icons.search),
                      title: Text('Buscar mensajes'),
                      contentPadding: EdgeInsets.zero,
                    ),
                  ),
                  const PopupMenuItem(
                    value: 'media',
                    child: ListTile(
                      leading: Icon(Icons.photo_library),
                      title: Text('Multimedia'),
                      contentPadding: EdgeInsets.zero,
                    ),
                  ),
                  if (_isAdmin)
                    const PopupMenuItem(
                      value: 'settings',
                      child: ListTile(
                        leading: Icon(Icons.settings),
                        title: Text('Configuración'),
                        contentPadding: EdgeInsets.zero,
                      ),
                    ),
                  const PopupMenuItem(
                    value: 'leave',
                    child: ListTile(
                      leading: Icon(Icons.exit_to_app, color: Colors.red),
                      title: Text('Salir del grupo', style: TextStyle(color: Colors.red)),
                      contentPadding: EdgeInsets.zero,
                    ),
                  ),
                ],
              ),
            ],
          ),

          // Información del grupo
          SliverToBoxAdapter(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Descripción
                if (_group!.description != null && _group!.description!.isNotEmpty)
                  _buildSection(
                    title: 'Descripción',
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: Text(_group!.description!),
                    ),
                  ),

                // Privacidad
                _buildSection(
                  title: 'Privacidad',
                  child: ListTile(
                    leading: Icon(_getPrivacyIcon(_group!.privacy)),
                    title: Text(_getPrivacyLabel(_group!.privacy)),
                    subtitle: Text(_getPrivacyDescription(_group!.privacy)),
                  ),
                ),

                // Enlace de invitación
                if (_isAdmin && _group!.inviteLink != null)
                  _buildSection(
                    title: 'Enlace de invitación',
                    child: ListTile(
                      leading: const Icon(Icons.link),
                      title: Text(
                        _group!.inviteLink!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      trailing: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          IconButton(
                            icon: const Icon(Icons.copy),
                            onPressed: () {
                              Clipboard.setData(ClipboardData(text: _group!.inviteLink!));
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Enlace copiado')),
                              );
                            },
                          ),
                          IconButton(
                            icon: const Icon(Icons.share),
                            onPressed: () {
                              Share.share(
                                'Únete a ${_group!.name}: ${_group!.inviteLink}',
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                  ),

                // Notificaciones
                _buildSection(
                  title: 'Notificaciones',
                  child: SwitchListTile(
                    secondary: const Icon(Icons.notifications),
                    title: const Text('Notificaciones'),
                    subtitle: const Text('Recibir notificaciones de este grupo'),
                    value: _notificationsEnabled,
                    onChanged: _setNotificationPreference,
                  ),
                ),

                // Miembros
                _buildMembersSection(),

                // Multimedia compartida
                _buildMediaSection(),

                // Acciones peligrosas
                _buildDangerSection(),

                const SizedBox(height: 32),
              ],
            ),
          ),
        ],
      ),
      floatingActionButton: _isAdmin
          ? FloatingActionButton.extended(
              onPressed: _addMember,
              icon: const Icon(Icons.person_add),
              label: const Text('Añadir miembro'),
            )
          : null,
    );
  }

  Widget _buildSection({required String title, required Widget child}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Text(
            title,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: Theme.of(context).colorScheme.primary,
            ),
          ),
        ),
        child,
        const Divider(),
      ],
    );
  }

  Widget _buildMembersSection() {
    final colorScheme = Theme.of(context).colorScheme;

    return _buildSection(
      title: 'Miembros (${_members.length})',
      child: Column(
        children: [
          // Lista de miembros (máximo 5 visibles)
          ...(_members.take(5).map((member) => _buildMemberTile(member))),

          // Ver todos
          if (_members.length > 5)
            ListTile(
              leading: CircleAvatar(
                backgroundColor: colorScheme.primaryContainer,
                child: Text(
                  '+${_members.length - 5}',
                  style: TextStyle(color: colorScheme.onPrimaryContainer),
                ),
              ),
              title: const Text('Ver todos los miembros'),
              trailing: const Icon(Icons.chevron_right),
              onTap: _showAllMembers,
            ),
        ],
      ),
    );
  }

  Widget _buildMemberTile(GroupMember member) {
    final isCurrentUser = member.userId == _currentUserId;
    final canManage = _isAdmin && !isCurrentUser && member.role != GroupRole.owner;

    return ListTile(
      leading: CircleAvatar(
        backgroundImage: member.avatarUrl != null
            ? CachedNetworkImageProvider(member.avatarUrl!)
            : null,
        child: member.avatarUrl == null
            ? Text(FlavorInitialsAvatar.initialsFor(member.name))
            : null,
      ),
      title: Row(
        children: [
          Text(member.name),
          if (isCurrentUser)
            const Text(' (Tú)', style: TextStyle(color: Colors.grey)),
        ],
      ),
      subtitle: Text(_getRoleLabel(member.role)),
      trailing: canManage
          ? PopupMenuButton<String>(
              onSelected: (action) => _handleMemberAction(action, member),
              itemBuilder: (context) => [
                if (member.role != GroupRole.admin)
                  const PopupMenuItem(
                    value: 'make_admin',
                    child: Text('Hacer administrador'),
                  ),
                if (member.role == GroupRole.admin)
                  const PopupMenuItem(
                    value: 'remove_admin',
                    child: Text('Quitar de administrador'),
                  ),
                const PopupMenuItem(
                  value: 'remove',
                  child: Text('Expulsar del grupo', style: TextStyle(color: Colors.red)),
                ),
              ],
            )
          : null,
      onTap: () => _showUserProfile(member),
    );
  }

  Widget _buildMediaSection() {
    return _buildSection(
      title: 'Multimedia compartida',
      child: SizedBox(
        height: 100,
        child: ListView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          children: [
            _buildMediaPreview(Icons.photo, 'Fotos', '23'),
            _buildMediaPreview(Icons.videocam, 'Videos', '5'),
            _buildMediaPreview(Icons.insert_drive_file, 'Archivos', '12'),
            _buildMediaPreview(Icons.link, 'Enlaces', '8'),
          ],
        ),
      ),
    );
  }

  Widget _buildMediaPreview(IconData icon, String label, String count) {
    final colorScheme = Theme.of(context).colorScheme;

    return GestureDetector(
      onTap: () {
        // Navegar a búsqueda filtrada por tipo de media
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => SearchScreen(conversationId: widget.groupId),
          ),
        );
      },
      child: Container(
        width: 80,
        margin: const EdgeInsets.only(right: 12),
        decoration: BoxDecoration(
          color: colorScheme.surfaceContainerHighest,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 28, color: colorScheme.primary),
            const SizedBox(height: 4),
            Text(label, style: const TextStyle(fontSize: 12)),
            Text(
              count,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: colorScheme.primary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDangerSection() {
    final isOwner = _members.any((m) =>
      m.userId == _currentUserId && m.role == GroupRole.owner
    );

    return _buildSection(
      title: 'Zona peligrosa',
      child: Column(
        children: [
          ListTile(
            leading: const Icon(Icons.exit_to_app, color: Colors.orange),
            title: const Text('Salir del grupo'),
            subtitle: const Text('Dejarás de recibir mensajes'),
            onTap: _confirmLeaveGroup,
          ),
          if (isOwner)
            ListTile(
              leading: const Icon(Icons.delete_forever, color: Colors.red),
              title: const Text('Eliminar grupo', style: TextStyle(color: Colors.red)),
              subtitle: const Text('Esta acción no se puede deshacer'),
              onTap: _confirmDeleteGroup,
            ),
        ],
      ),
    );
  }

  // Handlers
  void _handleMenuAction(String action) {
    switch (action) {
      case 'search':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => SearchScreen(conversationId: widget.groupId),
          ),
        );
        break;
      case 'media':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => SearchScreen(conversationId: widget.groupId),
          ),
        );
        break;
      case 'settings':
        _editGroup();
        break;
      case 'leave':
        _confirmLeaveGroup();
        break;
    }
  }

  void _handleMemberAction(String action, GroupMember member) async {
    switch (action) {
      case 'make_admin':
        await _chatService.makeAdmin(widget.groupId, member.userId);
        _loadGroupInfo();
        break;
      case 'remove_admin':
        await _chatService.removeAdmin(widget.groupId, member.userId);
        _loadGroupInfo();
        break;
      case 'remove':
        _confirmRemoveMember(member);
        break;
    }
  }

  Future<void> _editGroup() async {
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (context) => _EditGroupDialog(group: _group!),
    );

    if (result != null) {
      await _chatService.updateGroup(
        widget.groupId,
        name: result['name'],
        description: result['description'],
        avatarUrl: result['avatarUrl'],
      );
      _loadGroupInfo();
    }
  }

  Future<void> _addMember() async {
    // Excluir usuarios que ya son miembros
    final existingMembers = _members.map((m) => ChatUser(
      id: m.userId,
      name: m.name,
      avatarUrl: m.avatarUrl,
    )).toList();

    final result = await Navigator.push<List<ChatUser>>(
      context,
      MaterialPageRoute(
        builder: (context) => ContactPickerScreen(
          title: 'Añadir miembros',
          excludeUsers: existingMembers,
          multiSelect: true,
        ),
      ),
    );

    if (result != null && result.isNotEmpty) {
      // Añadir cada usuario seleccionado al grupo
      for (final user in result) {
        await _chatService.addMember(widget.groupId, user.id);
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('${result.length} miembro(s) añadido(s)'),
          ),
        );
      }
      _loadGroupInfo();
    }
  }

  void _showAllMembers() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Text(
                    'Miembros (${_members.length})',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView.builder(
                controller: scrollController,
                itemCount: _members.length,
                itemBuilder: (context, index) => _buildMemberTile(_members[index]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showUserProfile(GroupMember member) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => UserProfileScreen(userId: member.userId),
      ),
    );
  }

  void _confirmLeaveGroup() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Salir del grupo'),
        content: Text('¿Estás seguro de que quieres salir de "${_group!.name}"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () async {
              final navigator = Navigator.of(context);
              Navigator.pop(context);
              await _chatService.leaveGroup(widget.groupId);
              navigator.pop();
              navigator.pop(); // Volver al listado de chats
            },
            child: const Text('Salir', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  void _confirmDeleteGroup() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar grupo'),
        content: const Text(
          'Esta acción eliminará el grupo permanentemente para todos los miembros. '
          'No se puede deshacer.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () async {
              final navigator = Navigator.of(context);
              Navigator.pop(context);
              await _chatService.deleteGroup(widget.groupId);
              navigator.pop();
              navigator.pop();
            },
            child: const Text('Eliminar', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  void _confirmRemoveMember(GroupMember member) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Expulsar miembro'),
        content: Text('¿Expulsar a ${member.name} del grupo?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () async {
              Navigator.pop(context);
              await _chatService.removeMember(widget.groupId, member.userId);
              _loadGroupInfo();
            },
            child: const Text('Expulsar', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  // Helpers
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

  String _getPrivacyLabel(GroupPrivacy privacy) {
    switch (privacy) {
      case GroupPrivacy.public:
        return 'Público';
      case GroupPrivacy.private:
        return 'Privado';
      case GroupPrivacy.secret:
        return 'Secreto';
    }
  }

  String _getPrivacyDescription(GroupPrivacy privacy) {
    switch (privacy) {
      case GroupPrivacy.public:
        return 'Cualquiera puede encontrar y unirse';
      case GroupPrivacy.private:
        return 'Solo con invitación';
      case GroupPrivacy.secret:
        return 'No aparece en búsquedas';
    }
  }

  String _getRoleLabel(GroupRole role) {
    switch (role) {
      case GroupRole.owner:
        return 'Propietario';
      case GroupRole.admin:
        return 'Administrador';
      case GroupRole.member:
        return 'Miembro';
    }
  }
}

/// Diálogo para editar grupo
class _EditGroupDialog extends StatefulWidget {
  final ChatGroup group;

  const _EditGroupDialog({required this.group});

  @override
  State<_EditGroupDialog> createState() => _EditGroupDialogState();
}

class _EditGroupDialogState extends State<_EditGroupDialog> {
  late TextEditingController _nameController;
  late TextEditingController _descriptionController;
  String? _newAvatarUrl;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.group.name);
    _descriptionController = TextEditingController(text: widget.group.description);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Editar grupo'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Avatar
            GestureDetector(
              onTap: _pickImage,
              child: Stack(
                children: [
                  CircleAvatar(
                    radius: 40,
                    backgroundImage: _newAvatarUrl != null || widget.group.avatarUrl != null
                        ? CachedNetworkImageProvider(_newAvatarUrl ?? widget.group.avatarUrl!)
                        : null,
                    child: _newAvatarUrl == null && widget.group.avatarUrl == null
                        ? const Icon(Icons.group, size: 40)
                        : null,
                  ),
                  Positioned(
                    bottom: 0,
                    right: 0,
                    child: CircleAvatar(
                      radius: 14,
                      backgroundColor: Theme.of(context).colorScheme.primary,
                      child: const Icon(Icons.camera_alt, size: 14, color: Colors.white),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Nombre
            TextField(
              controller: _nameController,
              decoration: const InputDecoration(
                labelText: 'Nombre del grupo',
                border: OutlineInputBorder(),
              ),
              maxLength: 50,
            ),
            const SizedBox(height: 16),

            // Descripción
            TextField(
              controller: _descriptionController,
              decoration: const InputDecoration(
                labelText: 'Descripción',
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
              maxLength: 500,
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cancelar'),
        ),
        FilledButton(
          onPressed: () {
            Navigator.pop(context, {
              'name': _nameController.text.trim(),
              'description': _descriptionController.text.trim(),
              'avatarUrl': _newAvatarUrl,
            });
          },
          child: const Text('Guardar'),
        ),
      ],
    );
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final image = await picker.pickImage(source: ImageSource.gallery);

    if (image != null) {
      // Subir imagen al servidor
      final uploadService = MediaUploadService();
      final uploadResult = await uploadService.uploadImage(
        File(image.path),
        maxWidth: 512,
        maxHeight: 512,
        quality: 90,
      );

      if (uploadResult.success && uploadResult.url != null) {
        setState(() {
          _newAvatarUrl = uploadResult.url;
        });
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(uploadResult.error ?? 'Error al subir imagen')),
          );
        }
      }
    }
  }
}

/// Pantalla de perfil de usuario
class UserProfileScreen extends ConsumerStatefulWidget {
  final String userId;

  const UserProfileScreen({super.key, required this.userId});

  @override
  ConsumerState<UserProfileScreen> createState() => _UserProfileScreenState();
}

class _UserProfileScreenState extends ConsumerState<UserProfileScreen> {
  final ChatService _chatService = ChatService();
  ChatUser? _user;
  bool _isLoading = true;
  bool _isBlocked = false;
  bool _userNotificationsEnabled = true;
  List<ChatGroup> _commonGroups = [];

  String get _userNotificationsPrefKey => 'chat_user_notifications_${widget.userId}';

  @override
  void initState() {
    super.initState();
    _loadUser();
    _loadUserNotificationPreference();
  }

  Future<void> _loadUserNotificationPreference() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final enabled = prefs.getBool(_userNotificationsPrefKey) ?? true;
      if (mounted) {
        setState(() => _userNotificationsEnabled = enabled);
      }
    } catch (e) {
      // Usar valor por defecto
    }
  }

  Future<void> _setUserNotificationPreference(bool enabled) async {
    setState(() => _userNotificationsEnabled = enabled);
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool(_userNotificationsPrefKey, enabled);
    } catch (e) {
      // Ignorar error
    }
  }

  Future<void> _loadUser() async {
    setState(() => _isLoading = true);

    try {
      final user = await _chatService.getUserInfo(widget.userId);
      final isBlocked = await _chatService.isUserBlocked(widget.userId);
      final commonGroups = await _chatService.getCommonGroups(widget.userId);

      setState(() {
        _user = user;
        _isBlocked = isBlocked;
        _commonGroups = commonGroups;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(),
        body: const FlavorLoadingState(),
      );
    }

    if (_user == null) {
      return Scaffold(
        appBar: AppBar(),
        body: const FlavorEmptyState(
          icon: Icons.person_off_outlined,
          title: 'Usuario no encontrado',
        ),
      );
    }

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // Header con avatar
          SliverAppBar(
            expandedHeight: 250,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  if (_user!.avatarUrl != null)
                    CachedNetworkImage(
                      imageUrl: _user!.avatarUrl!,
                      fit: BoxFit.cover,
                    )
                  else
                    Container(
                      color: colorScheme.primaryContainer,
                      child: Icon(
                        Icons.person,
                        size: 100,
                        color: colorScheme.onPrimaryContainer,
                      ),
                    ),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.transparent,
                          Colors.black.withOpacity(0.7),
                        ],
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: 16,
                    left: 16,
                    right: 16,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _user!.name,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        if (_user!.isOnline)
                          const Text(
                            'En línea',
                            style: TextStyle(color: Colors.green),
                          )
                        else if (_user!.lastSeen != null)
                          Text(
                            'Últ. vez ${_formatLastSeen(_user!.lastSeen!)}',
                            style: const TextStyle(color: Colors.white70),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              PopupMenuButton<String>(
                onSelected: _handleMenuAction,
                itemBuilder: (context) => [
                  PopupMenuItem(
                    value: 'block',
                    child: ListTile(
                      leading: Icon(
                        _isBlocked ? Icons.check_circle : Icons.block,
                        color: _isBlocked ? Colors.green : Colors.red,
                      ),
                      title: Text(_isBlocked ? 'Desbloquear' : 'Bloquear'),
                      contentPadding: EdgeInsets.zero,
                    ),
                  ),
                  const PopupMenuItem(
                    value: 'report',
                    child: ListTile(
                      leading: Icon(Icons.flag, color: Colors.orange),
                      title: Text('Reportar'),
                      contentPadding: EdgeInsets.zero,
                    ),
                  ),
                ],
              ),
            ],
          ),

          // Información del usuario
          SliverToBoxAdapter(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Acciones principales
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      _buildActionButton(
                        icon: Icons.chat,
                        label: 'Mensaje',
                        onTap: _startChat,
                      ),
                      _buildActionButton(
                        icon: Icons.call,
                        label: 'Llamar',
                        onTap: _startVoiceCall,
                      ),
                      _buildActionButton(
                        icon: Icons.videocam,
                        label: 'Video',
                        onTap: _startVideoCall,
                      ),
                    ],
                  ),
                ),

                const Divider(),

                // Bio/Estado
                if (_user!.bio != null && _user!.bio!.isNotEmpty)
                  ListTile(
                    leading: const Icon(Icons.info_outline),
                    title: const Text('Info'),
                    subtitle: Text(_user!.bio!),
                  ),

                // Teléfono
                if (_user!.phone != null)
                  ListTile(
                    leading: const Icon(Icons.phone),
                    title: const Text('Teléfono'),
                    subtitle: Text(_user!.phone!),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          icon: const Icon(Icons.call),
                          onPressed: () => _callPhone(_user!.phone!),
                        ),
                        IconButton(
                          icon: const Icon(Icons.chat),
                          onPressed: _startChat,
                        ),
                      ],
                    ),
                  ),

                // Email
                if (_user!.email != null)
                  ListTile(
                    leading: const Icon(Icons.email),
                    title: const Text('Email'),
                    subtitle: Text(_user!.email!),
                  ),

                const Divider(),

                // Multimedia compartida
                ListTile(
                  leading: const Icon(Icons.photo_library),
                  title: const Text('Multimedia compartida'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => _showSharedMedia(),
                ),

                // Grupos en común
                ListTile(
                  leading: const Icon(Icons.group),
                  title: const Text('Grupos en común'),
                  subtitle: Text('${_commonGroups.length} grupos'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: _showCommonGroups,
                ),

                const Divider(),

                // Notificaciones
                SwitchListTile(
                  secondary: const Icon(Icons.notifications),
                  title: const Text('Notificaciones'),
                  value: _userNotificationsEnabled,
                  onChanged: _setUserNotificationPreference,
                ),

                const SizedBox(height: 32),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButton({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    final colorScheme = Theme.of(context).colorScheme;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
        child: Column(
          children: [
            CircleAvatar(
              backgroundColor: colorScheme.primaryContainer,
              child: Icon(icon, color: colorScheme.onPrimaryContainer),
            ),
            const SizedBox(height: 8),
            Text(label),
          ],
        ),
      ),
    );
  }

  void _handleMenuAction(String action) async {
    switch (action) {
      case 'block':
        if (_isBlocked) {
          await _chatService.unblockUser(widget.userId);
        } else {
          await _chatService.blockUser(widget.userId);
        }
        _loadUser();
        break;
      case 'report':
        _showReportDialog();
        break;
    }
  }

  void _startChat() {
    // Navegar a chat con usuario
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (context) => ChatMainScreen(
          conversationId: 'user_${widget.userId}',
          name: _user?.name ?? 'Usuario',
          avatarUrl: _user?.avatarUrl,
          isGroup: false,
        ),
      ),
    );
  }

  void _startVoiceCall() {
    _chatService.startCall(widget.userId, isVideo: false);
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CallScreen(
          recipientId: widget.userId,
          recipientName: _user?.name ?? 'Usuario',
          recipientAvatar: _user?.avatarUrl,
          isVideo: false,
          isIncoming: false,
        ),
      ),
    );
  }

  void _startVideoCall() {
    _chatService.startCall(widget.userId, isVideo: true);
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CallScreen(
          recipientId: widget.userId,
          recipientName: _user?.name ?? 'Usuario',
          recipientAvatar: _user?.avatarUrl,
          isVideo: true,
          isIncoming: false,
        ),
      ),
    );
  }

  Future<void> _callPhone(String phoneNumber) async {
    final phoneUri = Uri(scheme: 'tel', path: phoneNumber);
    if (await canLaunchUrl(phoneUri)) {
      await launchUrl(phoneUri);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No se puede realizar la llamada')),
        );
      }
    }
  }

  void _showSharedMedia() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => SearchScreen(conversationId: 'user_${widget.userId}'),
      ),
    );
  }

  void _showCommonGroups() {
    if (_commonGroups.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No hay grupos en común')),
      );
      return;
    }

    showModalBottomSheet(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Text(
                    'Grupos en común (${_commonGroups.length})',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Flexible(
              child: ListView.builder(
                shrinkWrap: true,
                itemCount: _commonGroups.length,
                itemBuilder: (context, index) {
                  final group = _commonGroups[index];
                  return ListTile(
                    leading: CircleAvatar(
                      backgroundImage: group.avatarUrl != null
                          ? CachedNetworkImageProvider(group.avatarUrl!)
                          : null,
                      child: group.avatarUrl == null
                          ? const Icon(Icons.group)
                          : null,
                    ),
                    title: Text(group.name),
                    subtitle: Text('${group.memberCount} miembros'),
                    onTap: () {
                      Navigator.pop(context);
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => GroupInfoScreen(groupId: group.id),
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showReportDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Reportar usuario'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('¿Por qué quieres reportar a este usuario?'),
            const SizedBox(height: 16),
            _buildReportOption('Spam'),
            _buildReportOption('Contenido inapropiado'),
            _buildReportOption('Acoso'),
            _buildReportOption('Suplantación de identidad'),
            _buildReportOption('Otro'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
        ],
      ),
    );
  }

  Widget _buildReportOption(String reason) {
    return ListTile(
      title: Text(reason),
      onTap: () {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Reporte enviado. Gracias.')),
        );
      },
    );
  }

  String _formatLastSeen(DateTime lastSeen) {
    final now = DateTime.now();
    final diff = now.difference(lastSeen);

    if (diff.inMinutes < 1) return 'hace un momento';
    if (diff.inMinutes < 60) return 'hace ${diff.inMinutes} min';
    if (diff.inHours < 24) return 'hace ${diff.inHours} h';
    if (diff.inDays < 7) return 'hace ${diff.inDays} días';

    return '${lastSeen.day}/${lastSeen.month}/${lastSeen.year}';
  }
}

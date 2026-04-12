import 'package:flutter/material.dart';
import '../../../../core/services/chat_service.dart';

/// Pantalla de selección de contactos para llamadas grupales
class ContactPickerScreen extends StatefulWidget {
  final String? title;
  final bool multiSelect;
  final List<String> excludeUserIds;
  final int maxSelections;

  const ContactPickerScreen({
    super.key,
    this.title,
    this.multiSelect = false,
    this.excludeUserIds = const [],
    this.maxSelections = 10,
  });

  @override
  State<ContactPickerScreen> createState() => _ContactPickerScreenState();
}

class _ContactPickerScreenState extends State<ContactPickerScreen> {
  final List<ChatUser> _selectedUsers = [];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title ?? 'Seleccionar contactos'),
        actions: [
          if (widget.multiSelect && _selectedUsers.isNotEmpty)
            TextButton(
              onPressed: () => Navigator.pop(context, _selectedUsers),
              child: const Text('Listo'),
            ),
        ],
      ),
      body: const Center(
        child: Text('Selector de contactos (en desarrollo)'),
      ),
    );
  }
}

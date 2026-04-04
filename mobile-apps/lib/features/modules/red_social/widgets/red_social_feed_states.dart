import 'package:flutter/material.dart';
import '../../../../core/widgets/flavor_state_widgets.dart';

class RedSocialLoadingState extends StatelessWidget {
  const RedSocialLoadingState({super.key});

  @override
  Widget build(BuildContext context) {
    return const FlavorLoadingState();
  }
}

class RedSocialErrorState extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const RedSocialErrorState({
    super.key,
    required this.message,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return FlavorErrorState(
      message: message,
      onRetry: onRetry,
      icon: Icons.public,
    );
  }
}

class RedSocialEmptyState extends StatelessWidget {
  final VoidCallback onCreate;

  const RedSocialEmptyState({
    super.key,
    required this.onCreate,
  });

  @override
  Widget build(BuildContext context) {
    return FlavorEmptyState(
      icon: Icons.public,
      title: 'No hay publicaciones disponibles',
      action: FilledButton.icon(
        onPressed: onCreate,
        icon: const Icon(Icons.add),
        label: const Text('Crear publicacion'),
      ),
    );
  }
}

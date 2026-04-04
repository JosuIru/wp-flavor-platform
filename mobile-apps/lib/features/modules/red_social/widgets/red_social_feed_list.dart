import 'package:flutter/material.dart';

class RedSocialFeedList extends StatelessWidget {
  final List<dynamic> publicaciones;
  final Future<void> Function() onRefresh;
  final Widget Function(dynamic item, int index) itemBuilder;

  const RedSocialFeedList({
    super.key,
    required this.publicaciones,
    required this.onRefresh,
    required this.itemBuilder,
  });

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: publicaciones.length,
        itemBuilder: (context, index) =>
            itemBuilder(publicaciones[index], index),
      ),
    );
  }
}

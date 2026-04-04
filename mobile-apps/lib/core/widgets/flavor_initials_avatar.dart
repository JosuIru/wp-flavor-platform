import 'package:flutter/material.dart';

class FlavorInitialsAvatar extends StatelessWidget {
  final String name;
  final double? radius;
  final Color? backgroundColor;
  final TextStyle? textStyle;
  final String fallback;

  const FlavorInitialsAvatar({
    super.key,
    required this.name,
    this.radius,
    this.backgroundColor,
    this.textStyle,
    this.fallback = 'U',
  });

  static String initialsFor(String name, {String fallback = 'U'}) {
    final normalized = name.trim();
    if (normalized.isEmpty) {
      return fallback;
    }

    final parts = normalized
        .split(RegExp(r'\s+'))
        .where((part) => part.isNotEmpty)
        .toList();

    if (parts.length >= 2) {
      return '${parts.first[0]}${parts.last[0]}'.toUpperCase();
    }

    return parts.first[0].toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    return CircleAvatar(
      radius: radius,
      backgroundColor: backgroundColor,
      child: Text(
        initialsFor(name, fallback: fallback),
        style: textStyle,
      ),
    );
  }
}

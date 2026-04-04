import 'package:flutter/material.dart';

class FlavorSearchField extends StatelessWidget {
  final String hintText;
  final String value;
  final ValueChanged<String> onChanged;

  const FlavorSearchField({
    super.key,
    required this.hintText,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return TextField(
      onChanged: onChanged,
      decoration: InputDecoration(
        hintText: hintText,
        prefixIcon: const Icon(Icons.search),
        suffixIcon: value.isEmpty
            ? null
            : IconButton(
                icon: const Icon(Icons.close),
                onPressed: () => onChanged(''),
              ),
        filled: true,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide.none,
        ),
      ),
    );
  }
}

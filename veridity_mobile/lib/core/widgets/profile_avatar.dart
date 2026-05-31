import 'package:flutter/material.dart';

class ProfileAvatar extends StatelessWidget {
  const ProfileAvatar({super.key, this.photoUrl, this.radius = 30, this.onTap});

  final String? photoUrl;
  final double radius;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final imageUrl = photoUrl;
    final size = radius * 2;

    final child = Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: const Color(0xFF1D143E),
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white12),
      ),
      clipBehavior: Clip.antiAlias,
      child: imageUrl == null || imageUrl.isEmpty
          ? Icon(Icons.person, color: Colors.white70, size: radius)
          : Image.network(
              imageUrl,
              fit: BoxFit.cover,
              errorBuilder: (context, error, stackTrace) =>
                  Icon(Icons.person, color: Colors.white70, size: radius),
            ),
    );

    if (onTap == null) {
      return child;
    }

    return InkWell(
      onTap: onTap,
      customBorder: const CircleBorder(),
      child: child,
    );
  }
}

import 'package:flutter/material.dart';

enum AppSnackType { success, error, info }

class AppTopSnackBar {
  static void success(BuildContext context, String message) {
    show(context, message, type: AppSnackType.success);
  }

  static void error(BuildContext context, String message) {
    show(context, message, type: AppSnackType.error);
  }

  static void info(BuildContext context, String message) {
    show(context, message, type: AppSnackType.info);
  }

  static void show(
    BuildContext context,
    String message, {
    AppSnackType type = AppSnackType.info,
  }) {
    final color = switch (type) {
      AppSnackType.success => const Color(0xFF16A34A),
      AppSnackType.error => const Color(0xFFDC2626),
      AppSnackType.info => const Color(0xFF2563EB),
    };
    final icon = switch (type) {
      AppSnackType.success => Icons.check_circle_outline,
      AppSnackType.error => Icons.error_outline,
      AppSnackType.info => Icons.info_outline,
    };

    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          behavior: SnackBarBehavior.floating,
          margin: EdgeInsets.only(
            left: 18,
            right: 18,
            bottom: MediaQuery.sizeOf(context).height -
                MediaQuery.paddingOf(context).top -
                112,
          ),
          elevation: 0,
          backgroundColor: color,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          content: Row(
            children: [
              Icon(icon, color: Colors.white, size: 22),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  message,
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    height: 1.35,
                  ),
                ),
              ),
            ],
          ),
        ),
      );
  }
}

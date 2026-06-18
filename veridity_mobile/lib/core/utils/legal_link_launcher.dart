import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class LegalLinkLauncher {
  const LegalLinkLauncher._();

  static Future<void> open(BuildContext context, Uri uri) async {
    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Tautan belum bisa dibuka.')),
      );
    }
  }
}

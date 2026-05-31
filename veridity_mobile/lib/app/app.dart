import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import 'routes.dart';

class VeridityApp extends StatelessWidget {
  const VeridityApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'VeriDity',
      theme: AppTheme.dark(),
      initialRoute: AppRoutes.splash,
      routes: AppRoutes.routes,
    );
  }
}

import 'package:flutter/material.dart';

import '../features/audit/domain/entities/audit_entity.dart';
import '../features/audit/presentation/pages/audit_detail_page.dart';
import '../features/audit/presentation/pages/history_page.dart';
import '../features/audit/presentation/pages/home_page.dart';
import '../features/audit/presentation/pages/upload_file_page.dart';
import '../features/auth/presentation/pages/login_page.dart';
import '../features/auth/presentation/pages/onboarding_page.dart';
import '../features/auth/presentation/pages/signup_page.dart';
import '../features/auth/presentation/pages/splash_screen.dart';
import '../features/help/presentation/pages/help_page.dart';
import '../features/profile/presentation/pages/profile_page.dart';

class AppRoutes {
  const AppRoutes._();

  static const splash = '/SplashScreen';
  static const onboarding = '/SplashScreen2';
  static const login = '/Login';
  static const signUp = '/SignUp';
  static const home = '/Home';
  static const history = '/History';
  static const help = '/Help';
  static const profile = '/Profil';
  static const upload = '/UploadFoto';
  static const auditDetail = '/AuditDetail';

  static Map<String, WidgetBuilder> get routes {
    return {
      splash: (context) => const SplashScreen(),
      onboarding: (context) => const SplashScreen2(),
      login: (context) => const Login(),
      signUp: (context) => const SignUp(),
      home: (context) => Home(userData: _mapArguments(context)),
      history: (context) => History(userData: _mapArguments(context)),
      help: (context) => Help(userData: _mapArguments(context)),
      profile: (context) => Profil(userData: _mapArguments(context)),
      upload: (context) => const UploadFoto(),
      auditDetail: (context) => AuditDetail(audit: _auditArgument(context)),
    };
  }

  static Map<String, dynamic>? _mapArguments(BuildContext context) {
    return ModalRoute.of(context)?.settings.arguments as Map<String, dynamic>?;
  }

  static AuditEntity _auditArgument(BuildContext context) {
    return ModalRoute.of(context)?.settings.arguments as AuditEntity;
  }
}

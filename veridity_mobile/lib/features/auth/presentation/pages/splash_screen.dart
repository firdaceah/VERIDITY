import 'dart:async';
import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});
  @override
  SplashScreenState createState() => SplashScreenState();
}

class SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    Timer(const Duration(seconds: 2), () {
      if (!mounted) {
        return;
      }

      final sessionStore = AppDependencies.sessionStore;
      if (!sessionStore.onboardingCompleted) {
        Navigator.pushReplacementNamed(context, '/SplashScreen2');
        return;
      }

      final session = sessionStore.session;
      if (session != null && session.token.isNotEmpty) {
        Navigator.pushReplacementNamed(
          context,
          '/Home',
          arguments: session.asRouteArguments(),
        );
        return;
      }

      Navigator.pushReplacementNamed(context, '/Login');
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0A1A),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Image.asset("assets/images/logo.png", width: 200),
            const SizedBox(height: 20),
            const Text(
              "VERIDITY",
              style: TextStyle(
                color: Colors.white,
                fontSize: 40,
                fontWeight: FontWeight.bold,
              ),
            ),
            const Text(
              "A I  F i l e  F o r e n s i c s",
              style: TextStyle(color: Colors.white70, fontSize: 16),
            ),
          ],
        ),
      ),
    );
  }
}

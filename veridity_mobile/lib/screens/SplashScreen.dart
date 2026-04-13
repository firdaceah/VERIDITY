import 'dart:async';
import 'package:flutter/material.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});
  @override
  SplashScreenState createState() => SplashScreenState();
}

class SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    Timer(const Duration(seconds: 3), () {
      Navigator.pushReplacementNamed(context, '/SplashScreen2');
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
            Image.network(
              "https://storage.googleapis.com/tagjs-prod.appspot.com/v1/SmbShTn1dS/kezvh6k4_expires_30_days.png",
              width: 200,
            ),
            const SizedBox(height: 20),
            const Text("VERIDITY", 
              style: TextStyle(color: Colors.white, fontSize: 40, fontWeight: FontWeight.bold)),
            const Text("A I  P h o t o  F o r e n s i c s", 
              style: TextStyle(color: Colors.white70, fontSize: 16)),
          ],
        ),
      ),
    );
  }
}
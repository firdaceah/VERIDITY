import 'package:flutter/material.dart';

class SplashScreen2 extends StatelessWidget {
  const SplashScreen2({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0A1A),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: Center(
                child: Image.network(
                  "https://storage.googleapis.com/tagjs-prod.appspot.com/v1/SmbShTn1dS/vn19is93_expires_30_days.png",
                  width: 300,
                ),
              ),
            ),
            const Text("VERIDITY", 
                style: TextStyle(color: Colors.white, fontSize: 40, fontWeight: FontWeight.bold)),
            const SizedBox(height: 40),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 40),
              child: InkWell(
                onTap: () => Navigator.pushReplacementNamed(context, '/SignUp'),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 15),
                  decoration: BoxDecoration(
                    color: const Color(0xFF5A38CA),
                    borderRadius: BorderRadius.circular(15),
                  ),
                  child: const Center(
                    child: Text("Mulai", 
                        style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
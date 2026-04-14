import 'package:flutter/material.dart';
import 'screens/SignUp.dart';
import 'screens/Login.dart';
import 'screens/SplashScreen.dart';
import 'screens/SplashScreen2.dart';
import 'screens/Home.dart';
import 'screens/History.dart';
import 'screens/Help.dart';
import 'screens/Profil.dart';
import 'screens/UploadFoto.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'VeriDity',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF4338CA)),
        useMaterial3: true,
      ),
      // Jalankan Splash dulu
      initialRoute: '/SplashScreen',
      routes: {
        '/SplashScreen': (context) => const SplashScreen(),
        '/SplashScreen2': (context) => const SplashScreen2(),
        '/Login': (context) => const Login(),
        '/SignUp': (context) => const SignUp(),
        '/Home': (context) => Home(
          userData:
              ModalRoute.of(context)?.settings.arguments
                  as Map<String, dynamic>?,
        ),
        '/History': (context) => History(
          userData:
              ModalRoute.of(context)?.settings.arguments
                  as Map<String, dynamic>?,
        ),
        '/Help': (context) => Help(
          userData:
              ModalRoute.of(context)?.settings.arguments
                  as Map<String, dynamic>?,
        ),
        '/Profil': (context) => Profil(
          userData:
              ModalRoute.of(context)?.settings.arguments
                  as Map<String, dynamic>?,
        ),
        '/UploadFoto': (context) => const UploadFoto(),
      },
    );
  }
}

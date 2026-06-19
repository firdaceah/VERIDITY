import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';

class SplashScreen2 extends StatefulWidget {
  const SplashScreen2({super.key});

  @override
  SplashScreen2State createState() => SplashScreen2State();
}

class SplashScreen2State extends State<SplashScreen2> {
  final PageController _controller = PageController();
  int _index = 0;

  final List<_OnboardingItem> _items = const [
    _OnboardingItem(
      icon: Icons.verified_user_rounded,
      titleEn: 'File Authenticity Detection',
      titleId: 'Deteksi Keaslian File',
      subtitleEn:
          'Check photos and documents with digital forensic methods to spot original, edited, or AI-assisted content.',
      subtitleId:
          'Periksa foto dan dokumen dengan metode forensik digital untuk melihat indikasi asli, editan, atau campur tangan AI.',
    ),
    _OnboardingItem(
      icon: Icons.analytics_rounded,
      titleEn: 'Layered Analysis',
      titleId: 'Metode Berlapis',
      subtitleEn:
          'Images are analyzed through ELA, noise, metadata, and AI detection. Documents are checked through language patterns and sentence mapping.',
      subtitleId:
          'Foto dianalisis melalui ELA, noise, metadata, dan deteksi AI. Dokumen dianalisis melalui pola bahasa dan pemetaan kalimat.',
    ),
    _OnboardingItem(
      icon: Icons.picture_as_pdf_rounded,
      titleEn: 'History & PDF Reports',
      titleId: 'Riwayat & Laporan PDF',
      subtitleEn:
          'Analysis results are saved in history and can be downloaded as investigation reports for review or presentation.',
      subtitleId:
          'Hasil analisis tersimpan di riwayat dan dapat diunduh sebagai laporan investigasi untuk kebutuhan review atau presentasi.',
    ),
  ];

  Future<void> _finish() async {
    await AppDependencies.sessionStore.completeOnboarding();
    if (!mounted) {
      return;
    }
    Navigator.pushReplacementNamed(context, '/Login');
  }

  void _next() {
    if (_index == _items.length - 1) {
      _finish();
      return;
    }
    _controller.nextPage(
      duration: const Duration(milliseconds: 280),
      curve: Curves.easeOut,
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;

    return Scaffold(
      backgroundColor: const Color(0xFF0A0A1A),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(28, 28, 28, 34),
          child: Column(
            children: [
              Row(
                children: [
                  Image.asset('assets/images/logo.png', width: 40, height: 40),
                  const SizedBox(width: 10),
                  const Text(
                    'VERIDITY',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 0.5,
                    ),
                  ),
                  const Spacer(),
                  SizedBox(
                    width: 76,
                    child: _index == _items.length - 1
                        ? const SizedBox.shrink()
                        : TextButton(
                            onPressed: _finish,
                            child: Text(
                              lang.text('Skip', 'Lewati'),
                              style: const TextStyle(color: Colors.white60),
                            ),
                          ),
                  ),
                ],
              ),
              Expanded(
                child: PageView.builder(
                  controller: _controller,
                  itemCount: _items.length,
                  onPageChanged: (value) => setState(() => _index = value),
                  itemBuilder: (context, index) {
                    final item = _items[index];
                    return Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          width: 150,
                          height: 150,
                          decoration: BoxDecoration(
                            color: const Color(0xFF1D143E),
                            borderRadius: BorderRadius.circular(32),
                            border: Border.all(
                              color: const Color(0xFF39D2DD),
                              width: 1.5,
                            ),
                          ),
                          child: Icon(
                            item.icon,
                            color: const Color(0xFF39D2DD),
                            size: 72,
                          ),
                        ),
                        const SizedBox(height: 34),
                        Text(
                          lang.text(item.titleEn, item.titleId),
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 30,
                            fontWeight: FontWeight.bold,
                            height: 1.15,
                          ),
                        ),
                        const SizedBox(height: 18),
                        Text(
                          lang.text(item.subtitleEn, item.subtitleId),
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 15,
                            height: 1.6,
                          ),
                        ),
                      ],
                    );
                  },
                ),
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(
                  _items.length,
                  (index) => AnimatedContainer(
                    duration: const Duration(milliseconds: 180),
                    width: _index == index ? 26 : 9,
                    height: 9,
                    margin: const EdgeInsets.symmetric(horizontal: 4),
                    decoration: BoxDecoration(
                      color: _index == index
                          ? const Color(0xFF39D2DD)
                          : Colors.white24,
                      borderRadius: BorderRadius.circular(99),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 28),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _next,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF4338CA),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                  child: Text(
                    _index == _items.length - 1
                        ? lang.text('Start', 'Mulai')
                        : lang.text('Continue', 'Lanjut'),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _OnboardingItem {
  const _OnboardingItem({
    required this.icon,
    required this.titleEn,
    required this.titleId,
    required this.subtitleEn,
    required this.subtitleId,
  });

  final IconData icon;
  final String titleEn;
  final String titleId;
  final String subtitleEn;
  final String subtitleId;
}

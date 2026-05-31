import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../domain/entities/audit_entity.dart';

class History extends StatefulWidget {
  final Map<String, dynamic>? userData;
  const History({super.key, this.userData});
  @override
  HistoryState createState() => HistoryState();
}

class HistoryState extends State<History> {
  final int _selectedIndex = 1; // Index 1 untuk History
  List<AuditEntity> _historyData = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  // Fungsi untuk mengambil data dari kolom forensic_analyses di Laravel
  Future<void> _fetchHistory() async {
    try {
      final history = await AppDependencies.auditRepository.history();
      if (!mounted) {
        return;
      }
      setState(() {
        _historyData = history;
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) {
        return;
      }
      setState(() {
        _isLoading = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Gagal mengambil riwayat analisis")),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: Stack(
        children: [
          Positioned.fill(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 25, vertical: 40),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    "Riwayat Analisis",
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    "${_historyData.length} foto telah dianalisis",
                    style: TextStyle(color: Colors.white70, fontSize: 15),
                  ),
                  const SizedBox(height: 25),

                  // Search Bar
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 15),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0E0E20),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.white10),
                    ),
                    child: const TextField(
                      style: TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        icon: Icon(Icons.search, color: Colors.white54),
                        hintText: "Cari riwayat...",
                        hintStyle: TextStyle(color: Colors.white24),
                        border: InputBorder.none,
                      ),
                    ),
                  ),
                  const SizedBox(height: 25),

                  // Filter Buttons
                  Row(
                    children: [
                      _buildFilterChip("Semua", true),
                      const SizedBox(width: 10),
                      _buildFilterChip("Aman", false),
                      const SizedBox(width: 10),
                      _buildFilterChip("Beresiko", false),
                    ],
                  ),
                  const SizedBox(height: 30),

                  _isLoading
                      ? const Center(
                          child: CircularProgressIndicator(
                            color: Color(0xFF39D2DD),
                          ),
                        )
                      : _historyData.isEmpty
                      ? const Center(
                          child: Text(
                            "Belum ada riwayat",
                            style: TextStyle(color: Colors.white54),
                          ),
                        )
                      : Column(
                          children: _historyData.map((item) {
                            final formattedDate = item.createdAt.length >= 10
                                ? item.createdAt.substring(0, 10)
                                : "Unknown Date";

                            return _buildHistoryItem(item, formattedDate);
                          }).toList(),
                        ),

                  // const SizedBox(height: 120), // Biar nggak ketutup nav
                ],
              ),
            ),
          ),
          _buildBottomNav(),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, bool isActive) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
      decoration: BoxDecoration(
        color: isActive ? const Color(0xFF4338CA) : const Color(0xFF0E0E20),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: const TextStyle(color: Colors.white, fontSize: 13),
      ),
    );
  }

  Widget _buildHistoryItem(AuditEntity item, String date) {
    final isSafe = item.isSafe;
    return InkWell(
      onTap: () =>
          Navigator.pushNamed(context, '/AuditDetail', arguments: item),
      borderRadius: BorderRadius.circular(15),
      child: Container(
        margin: const EdgeInsets.only(bottom: 15),
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: const Color(0xFF1D143E),
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: Colors.white10),
        ),
        child: Row(
          children: [
            const Icon(Icons.image, color: Colors.white24, size: 30),
            const SizedBox(width: 15),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.fileName,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    date,
                    style: const TextStyle(color: Colors.white54, fontSize: 12),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: isSafe
                              ? Colors.green.withValues(alpha: 0.2)
                              : Colors.red.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          item.summaryLabel,
                          style: TextStyle(
                            color: isSafe ? Colors.green : Colors.red,
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      const Text(
                        "Detail",
                        style: TextStyle(
                          color: Color(0xFF7C3AED),
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomNav() {
    return Positioned(
      bottom: 0,
      left: 0,
      right: 0,
      child: Stack(
        alignment: Alignment.topCenter,
        clipBehavior: Clip.none,
        children: [
          Container(
            height: 80,
            decoration: const BoxDecoration(
              color: Color(0xFF0E0E20),
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(20),
                topRight: Radius.circular(20),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _navItem(Icons.home, "Home", 0, '/Home'),
                _navItem(Icons.history, "History", 1, '/History'),
                const SizedBox(width: 60),
                _navItem(Icons.help_outline, "Help", 2, '/Help'),
                _navItem(Icons.person_outline, "Profile", 3, '/Profil'),
              ],
            ),
          ),
          Positioned(
            top: -18,
            child: SizedBox(
              width: 75,
              height: 75,
              child: FloatingActionButton(
                heroTag: null,
                onPressed: () => Navigator.pushNamed(context, '/UploadFoto'),
                backgroundColor: const Color(0xFF39D2DD),
                shape: const CircleBorder(),
                child: const Icon(
                  Icons.qr_code_scanner,
                  color: Colors.white,
                  size: 45,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _navItem(IconData icon, String label, int index, String route) {
    bool isActive = _selectedIndex == index;
    return GestureDetector(
      onTap: () {
        if (!isActive) {
          Future.delayed(Duration.zero, () {
            if (mounted) {
              Navigator.pushNamedAndRemoveUntil(
                context,
                route,
                (route) => false,
                arguments: widget.userData,
              );
            }
          });
        }
      },
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            icon,
            color: isActive ? const Color(0xFF7C3AED) : Colors.white54,
          ),
          Text(
            label,
            style: TextStyle(
              color: isActive ? const Color(0xFF7C3AED) : Colors.white54,
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }
}

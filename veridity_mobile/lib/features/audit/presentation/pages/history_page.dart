import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/widgets/app_bottom_nav.dart';
import '../../domain/entities/audit_entity.dart';

class History extends StatefulWidget {
  final Map<String, dynamic>? userData;
  const History({super.key, this.userData});
  @override
  HistoryState createState() => HistoryState();
}

class HistoryState extends State<History> {
  final int _selectedIndex = 1; // Index 1 untuk History
  final TextEditingController _searchController = TextEditingController();
  final FocusNode _searchFocusNode = FocusNode();
  List<AuditEntity> _historyData = [];
  bool _isLoading = true;
  String _activeFilter = 'all';

  List<AuditEntity> get _filteredHistory {
    final query = _searchController.text.trim().toLowerCase();
    return _historyData.where((item) {
      final matchesFilter =
          _activeFilter == 'all' ||
          (_activeFilter == 'photo' && item.isImage) ||
          (_activeFilter == 'document' && item.isDocument);
      final matchesSearch =
          query.isEmpty ||
          item.fileName.toLowerCase().contains(query) ||
          item.summaryLabel.toLowerCase().contains(query);
      return matchesFilter && matchesSearch;
    }).toList();
  }

  @override
  void initState() {
    super.initState();
    _searchController.addListener(() => setState(() {}));
    _fetchHistory();
  }

  @override
  void dispose() {
    _searchFocusNode.dispose();
    _searchController.dispose();
    super.dispose();
  }

  // Fungsi untuk mengambil data dari kolom forensic_analyses di Laravel
  Future<void> _fetchHistory({bool showLoading = false}) async {
    _dismissSearchKeyboard();
    if (showLoading && mounted) {
      setState(() => _isLoading = true);
    }

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
        SnackBar(
          content: Text(
            AppDependencies.language.text(
              "Failed to load analysis history",
              "Gagal mengambil riwayat analisis",
            ),
          ),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: Stack(
        children: [
          Positioned.fill(
            child: SingleChildScrollView(
              padding: EdgeInsets.fromLTRB(
                25,
                40,
                25,
                AppBottomNav.contentBottomPadding(context),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    lang.text("Analysis History", "Riwayat Analisis"),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    lang.text(
                      "${_historyData.length} files analyzed",
                      "${_historyData.length} file telah dianalisis",
                    ),
                    style: const TextStyle(color: Colors.white70, fontSize: 15),
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
                    child: TextField(
                      controller: _searchController,
                      focusNode: _searchFocusNode,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        icon: const Icon(Icons.search, color: Colors.white54),
                        hintText: lang.text("Search history...", "Cari riwayat..."),
                        hintStyle: const TextStyle(color: Colors.white24),
                        border: InputBorder.none,
                      ),
                    ),
                  ),
                  const SizedBox(height: 25),

                  // Filter Buttons
                  Wrap(
                    spacing: 10,
                    runSpacing: 10,
                    children: [
                      _buildFilterChip(
                        value: "all",
                        label: lang.text("All", "Semua"),
                      ),
                      _buildFilterChip(
                        value: "photo",
                        label: lang.text("Photo", "Foto"),
                      ),
                      _buildFilterChip(
                        value: "document",
                        label: lang.text("Document", "Dokumen"),
                      ),
                    ],
                  ),
                  const SizedBox(height: 30),

                  _isLoading
                      ? Padding(
                          padding: EdgeInsets.symmetric(vertical: 34),
                          child: Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                CircularProgressIndicator(
                                  color: Color(0xFF39D2DD),
                                ),
                                SizedBox(height: 14),
                                Text(
                                  lang.text(
                                    "Loading analysis history...",
                                    "Proses mengambil data riwayat...",
                                  ),
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    color: Colors.white60,
                                    fontSize: 13,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        )
                      : _filteredHistory.isEmpty
                      ? Center(
                          child: Text(
                            lang.text(
                              "No history found",
                              "Riwayat tidak ditemukan",
                            ),
                            style: const TextStyle(color: Colors.white54),
                          ),
                        )
                      : Column(
                          children: _filteredHistory.map((item) {
                            final formattedDate = item.createdAt.length >= 10
                                ? item.createdAt.substring(0, 10)
                                : lang.text("Unknown Date", "Tanggal tidak diketahui");

                            return _buildHistoryItem(item, formattedDate);
                          }).toList(),
                        ),

                  // const SizedBox(height: 120), // Biar nggak ketutup nav
                ],
              ),
            ),
          ),
          AppBottomNav(activeIndex: _selectedIndex, userData: widget.userData),
        ],
      ),
    );
  }

  Widget _buildFilterChip({required String value, required String label}) {
    final isActive = _activeFilter == value;
    return InkWell(
      onTap: () => setState(() => _activeFilter = value),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
        decoration: BoxDecoration(
          color: isActive ? const Color(0xFF4338CA) : const Color(0xFF0E0E20),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isActive ? Colors.transparent : Colors.white10,
          ),
        ),
        child: Text(
          label,
          style: const TextStyle(color: Colors.white, fontSize: 13),
        ),
      ),
    );
  }

  IconData _historyIcon(AuditEntity item) {
    return switch (item.iconType) {
      IconType.pdf => Icons.picture_as_pdf_outlined,
      IconType.docx => Icons.description_outlined,
      IconType.image => Icons.image_outlined,
    };
  }

  Color _historyIconColor(AuditEntity item) {
    return switch (item.iconType) {
      IconType.pdf => Colors.redAccent,
      IconType.docx => const Color(0xFF39D2DD),
      IconType.image => Colors.white70,
    };
  }

  Widget _fileTypeBadge(AuditEntity item) {
    final lang = AppDependencies.language;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: Colors.white10,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        lang.fileTypeLabel(isDocument: item.isDocument),
        style: const TextStyle(color: Colors.white54, fontSize: 10),
      ),
    );
  }

  Widget _statusBadge(AuditEntity item) {
    final isSafe = item.isSafe;
    final color = isSafe
        ? Colors.green
        : (item.isWarning ? Colors.orange : Colors.red);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        AppDependencies.language.auditLabel(item.summaryLabel),
        softWrap: true,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.bold,
          height: 1.25,
        ),
      ),
    );
  }

  Future<void> _deleteAudit(AuditEntity item) async {
    await AppDependencies.auditRepository.delete(item.id);
    if (!mounted) {
      return;
    }
    setState(() {
      _historyData = _historyData
          .where((audit) => audit.id != item.id)
          .toList();
    });
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(
      SnackBar(
        content: Text(
          AppDependencies.language.text(
            "History deleted successfully",
            "Riwayat berhasil dihapus",
          ),
        ),
      ),
    );
  }

  Future<void> _openDetail(AuditEntity item) async {
    _dismissSearchKeyboard();
    await Navigator.pushNamed(context, '/AuditDetail', arguments: item);
    if (!mounted) {
      return;
    }
    _dismissSearchKeyboard();
    await _fetchHistory(showLoading: true);
  }

  void _dismissSearchKeyboard() {
    _searchFocusNode.unfocus();
    FocusManager.instance.primaryFocus?.unfocus();
  }

  Widget _buildHistoryItem(AuditEntity item, String date) {
    final lang = AppDependencies.language;

    return InkWell(
      onTap: () => _openDetail(item),
      borderRadius: BorderRadius.circular(15),
      child: Container(
        margin: const EdgeInsets.only(bottom: 15),
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: const Color(0xFF1D143E),
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: Colors.white10),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  _historyIcon(item),
                  color: _historyIconColor(item),
                  size: 34,
                ),
                const SizedBox(width: 15),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.fileName,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        date,
                        style: const TextStyle(
                          color: Colors.white54,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                PopupMenuButton<String>(
                  color: const Color(0xFF1D143E),
                  icon: const Icon(Icons.more_vert, color: Colors.white54),
                  onSelected: (value) {
                    if (value == 'delete') {
                      _deleteAudit(item);
                    }
                  },
                  itemBuilder: (context) => [
                    PopupMenuItem(
                      value: 'delete',
                      child: Text(
                        lang.text('Delete History', 'Hapus Riwayat'),
                        style: const TextStyle(color: Colors.redAccent),
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 10),
            _fileTypeBadge(item),
            const SizedBox(height: 8),
            _statusBadge(item),
            const SizedBox(height: 10),
            Text(
              lang.text("View Details", "Lihat Detail"),
              style: const TextStyle(
                color: Color(0xFF7C3AED),
                fontSize: 12,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

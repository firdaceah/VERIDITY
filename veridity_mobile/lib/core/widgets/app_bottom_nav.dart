import 'package:flutter/material.dart';

class AppBottomNav extends StatelessWidget {
  const AppBottomNav({
    super.key,
    required this.activeIndex,
    required this.userData,
  });

  static double contentBottomPadding(BuildContext context) {
    return 124 + MediaQuery.viewPaddingOf(context).bottom;
  }

  final int activeIndex;
  final Map<String, dynamic>? userData;

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.viewPaddingOf(context).bottom;
    final navHeight = 80.0 + bottomInset;

    return Positioned(
      bottom: 0,
      left: 0,
      right: 0,
      child: Stack(
        alignment: Alignment.topCenter,
        clipBehavior: Clip.none,
        children: [
          Container(
            height: navHeight,
            padding: EdgeInsets.only(bottom: bottomInset),
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
                _NavItem(
                  icon: Icons.home,
                  label: 'Home',
                  index: 0,
                  route: '/Home',
                  activeIndex: activeIndex,
                  userData: userData,
                ),
                _NavItem(
                  icon: Icons.history,
                  label: 'History',
                  index: 1,
                  route: '/History',
                  activeIndex: activeIndex,
                  userData: userData,
                ),
                const SizedBox(width: 60),
                _NavItem(
                  icon: Icons.help_outline,
                  label: 'Help',
                  index: 2,
                  route: '/Help',
                  activeIndex: activeIndex,
                  userData: userData,
                ),
                _NavItem(
                  icon: Icons.person_outline,
                  label: 'Profile',
                  index: 3,
                  route: '/Profil',
                  activeIndex: activeIndex,
                  userData: userData,
                ),
              ],
            ),
          ),
          Positioned(
            top: -18,
            child: SizedBox(
              width: 75,
              height: 75,
              child: FloatingActionButton(
                heroTag: 'app-upload-action',
                onPressed: () => Navigator.pushNamed(context, '/UploadFoto'),
                backgroundColor: const Color(0xFF39D2DD),
                shape: const CircleBorder(),
                child: const Icon(
                  Icons.add_to_photos_outlined,
                  color: Colors.white,
                  size: 38,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  const _NavItem({
    required this.icon,
    required this.label,
    required this.index,
    required this.route,
    required this.activeIndex,
    required this.userData,
  });

  final IconData icon;
  final String label;
  final int index;
  final String route;
  final int activeIndex;
  final Map<String, dynamic>? userData;

  @override
  Widget build(BuildContext context) {
    final isActive = activeIndex == index;

    return GestureDetector(
      onTap: isActive
          ? null
          : () => Navigator.pushNamedAndRemoveUntil(
              context,
              route,
              (route) => false,
              arguments: userData,
            ),
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

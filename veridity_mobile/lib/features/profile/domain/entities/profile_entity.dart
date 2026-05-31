import '../../../auth/domain/entities/user_entity.dart';

class ProfileEntity {
  const ProfileEntity({required this.user});

  final UserEntity user;

  String get name => user.name;
  String get email => user.email;
}

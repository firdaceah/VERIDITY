import 'user_entity.dart';

class AuthSession {
  const AuthSession({required this.token, required this.user});

  final String token;
  final UserEntity user;

  factory AuthSession.fromJson(Map<String, dynamic> json) {
    final userJson = json['data'] is Map<String, dynamic>
        ? json['data'] as Map<String, dynamic>
        : json['user'] as Map<String, dynamic>? ?? <String, dynamic>{};

    return AuthSession(
      token:
          json['access_token']?.toString() ?? json['token']?.toString() ?? '',
      user: UserEntity.fromJson(userJson),
    );
  }

  factory AuthSession.fromStoredJson(Map<String, dynamic> json) {
    return AuthSession(
      token: json['token']?.toString() ?? '',
      user: UserEntity.fromJson(
        json['user'] is Map<String, dynamic>
            ? json['user'] as Map<String, dynamic>
            : <String, dynamic>{},
      ),
    );
  }

  Map<String, dynamic> toStoredJson() {
    return {
      'token': token,
      'user': user.toJson(),
    };
  }

  Map<String, dynamic> asRouteArguments() {
    return {
      'token': token,
      'name': user.name,
      'email': user.email,
      'id': user.id,
      'profile_photo_url': user.profilePhotoUrl,
    };
  }

  AuthSession copyWith({UserEntity? user}) {
    return AuthSession(token: token, user: user ?? this.user);
  }
}

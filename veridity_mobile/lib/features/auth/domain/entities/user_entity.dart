class UserEntity {
  const UserEntity({
    required this.id,
    required this.name,
    required this.email,
    this.profilePhotoUrl,
  });

  final int id;
  final String name;
  final String email;
  final String? profilePhotoUrl;

  factory UserEntity.fromJson(Map<String, dynamic> json) {
    return UserEntity(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? 'User',
      email: json['email']?.toString() ?? '',
      profilePhotoUrl: json['profile_photo_url']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'profile_photo_url': profilePhotoUrl,
    };
  }

  UserEntity copyWith({String? name, String? email, String? profilePhotoUrl}) {
    return UserEntity(
      id: id,
      name: name ?? this.name,
      email: email ?? this.email,
      profilePhotoUrl: profilePhotoUrl ?? this.profilePhotoUrl,
    );
  }
}

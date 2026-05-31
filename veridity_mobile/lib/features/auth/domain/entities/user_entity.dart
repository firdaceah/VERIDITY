class UserEntity {
  const UserEntity({required this.id, required this.name, required this.email});

  final int id;
  final String name;
  final String email;

  factory UserEntity.fromJson(Map<String, dynamic> json) {
    return UserEntity(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? 'User',
      email: json['email']?.toString() ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {'id': id, 'name': name, 'email': email};
  }
}

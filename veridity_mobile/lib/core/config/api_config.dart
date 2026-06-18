class ApiConfig {
  const ApiConfig._();

  static const String baseUrl = String.fromEnvironment(
    'VERIDITY_API_BASE_URL',
    defaultValue: 'https://veridity-laravel.onrender.com/api',
  );

  static String get webBaseUrl => baseUrl.replaceFirst(RegExp(r'/api/?$'), '');

  static Uri get privacyPolicyUri => Uri.parse('$webBaseUrl/privacy-policy');

  static Uri get accountDeletionUri => Uri.parse('$webBaseUrl/account-deletion');

  static Uri get dataDeletionUri => Uri.parse('$webBaseUrl/data-deletion');
}

class ApiConfig {
  const ApiConfig._();

  static const String baseUrl = String.fromEnvironment(
    'VERIDITY_API_BASE_URL',
    defaultValue: 'http://54.169.169.253/api',
  );
}

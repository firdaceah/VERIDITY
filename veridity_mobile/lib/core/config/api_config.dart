class ApiConfig {
  const ApiConfig._();

  static const String baseUrl = String.fromEnvironment(
    'VERIDITY_API_BASE_URL',
    defaultValue: 'https://veridity-laravel.onrender.com/api',
  );
}

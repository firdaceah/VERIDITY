class ApiConfig {
  const ApiConfig._();

  static const String baseUrl = String.fromEnvironment(
    'VERIDITY_API_BASE_URL',
    defaultValue: 'http://10.57.201.184:8000/api',
  );
}

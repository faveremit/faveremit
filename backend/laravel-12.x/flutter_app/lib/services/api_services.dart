import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'https://your-backend-domain.com/api';
  static String? _token;

  static Future<void> setToken(String token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  static Future<void> loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
  }

  static Future<void> clearToken() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }

  static Map<String, String> get _headers {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    
    if (_token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }
    
    return headers;
  }

  static Future<Map<String, dynamic>> _handleResponse(http.Response response) async {
    final data = json.decode(response.body);
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    } else {
      throw ApiException(
        message: data['message'] ?? 'An error occurred',
        statusCode: response.statusCode,
        errors: data['errors'],
      );
    }
  }

  // Authentication
  static Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: _headers,
      body: json.encode({
        'email': email,
        'password': password,
      }),
    );
    
    return _handleResponse(response);
  }

  static Future<Map<String, dynamic>> register(String name, String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/register'),
      headers: _headers,
      body: json.encode({
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
      }),
    );
    
    return _handleResponse(response);
  }

  static Future<Map<String, dynamic>> logout() async {
    final response = await http.post(
      Uri.parse('$baseUrl/logout'),
      headers: _headers,
    );
    
    return _handleResponse(response);
  }

  static Future<Map<String, dynamic>> getProfile() async {
    final response = await http.get(
      Uri.parse('$baseUrl/profile'),
      headers: _headers,
    );
    
    return _handleResponse(response);
  }

  // Market Data
  static Future<Map<String, dynamic>> getMarketData() async {
    final response = await http.get(
      Uri.parse('$baseUrl/market/data'),
      headers: _headers,
    );
    
    return _handleResponse(response);
  }

  static Future<Map<String, dynamic>> getPairDetails(String symbol) async {
    final response = await http.get(
      Uri.parse('$baseUrl/market/pair/$symbol'),
      headers: _headers,
    );
    
    return _handleResponse(response);
  }

  // Trading
  static Future<Map<String, dynamic>> executeTrade({
    required String pair,
    required String side,
    required String type,
    required double amount,
    double? price,
  }) async {
    final body = {
      'pair': pair,
      'side': side,
      'type': type,
      'amount': amount,
    };
    
    if (price != null) {
      body['price'] = price;
    }

    final response = await http.post(
      Uri.parse('$baseUrl/trade/execute'),
      headers: _headers,
      body: json.encode(body),
    );
    
    return _handleResponse(response);
  }

  static Future<Map<String, dynamic>> placeOrder({
    required String pair,
    required String side,
    required String type,
    required double amount,
    required double price,
    double? stopPrice,
  }) async {
    final body = {
      'pair': pair,
      'side': side,
      'type': type,
      'amount': amount,
      'price': price,
    };
    
    if (stopPrice != null) {
      body['stop_price'] = stopPrice;
    }

    final response = await http.post(
      Uri.parse('$baseUrl/order/place'),
      headers: _headers,
      body: json.encode(body),
    );
    
    return _handleResponse(response);
  }

  // Portfolio
  static Future<Map<String, dynamic>> getPortfolio() async {
    final response = await http.get(
      Uri.parse('$baseUrl/portfolio'),
      headers: _headers,
    );
    
    return _handleResponse(response);
  }

  static Future<Map<String, dynamic>> getTrades() async {
    final response = await http.get(
      Uri.parse('$baseUrl/trades'),
      headers: _headers,
    );
    
    return _handleResponse(response);
  }

  static Future<Map<String, dynamic>> getOrders() async {
    final response = await http.get(
      Uri.parse('$baseUrl/orders'),
      headers: _headers,
    );
    
    return _handleResponse(response);
  }
}

class ApiException implements Exception {
  final String message;
  final int statusCode;
  final Map<String, dynamic>? errors;

  ApiException({
    required this.message,
    required this.statusCode,
    this.errors,
  });

  @override
  String toString() => message;
}

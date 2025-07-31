import 'package:flutter/material.dart';
import 'package:flutter_app/models/user.dart';
import 'package:flutter_app/services/api_service.dart';

class AuthProvider with ChangeNotifier {
  User? _user;
  bool _isLoading = false;
  String? _error;

  User? get user => _user;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isAuthenticated => _user != null;

  Future<void> initialize() async {
    await ApiService.loadToken();
    if (ApiService._token != null) {
      await fetchProfile();
    }
  }

  Future<void> login(String email, String password) async {
    try {
      _setLoading(true);
      _clearError();

      final response = await ApiService.login(email, password);
      
      if (response['success']) {
        final userData = response['data'];
        await ApiService.setToken(userData['token']);
        _user = User.fromJson(userData['user']);
        notifyListeners();
      } else {
        throw Exception(response['message'] ?? 'Login failed');
      }
    } catch (e) {
      _setError(e.toString());
      rethrow;
    } finally {
      _setLoading(false);
    }
  }

  Future<void> register(String name, String email, String password) async {
    try {
      _setLoading(true);
      _clearError();

      final response = await ApiService.register(name, email, password);
      
      if (response['success']) {
        final userData = response['data'];
        await ApiService.setToken(userData['token']);
        _user = User.fromJson(userData['user']);
        notifyListeners();
      } else {
        throw Exception(response['message'] ?? 'Registration failed');
      }
    } catch (e) {
      _setError(e.toString());
      rethrow;
    } finally {
      _setLoading(false);
    }
  }

  Future<void> logout() async {
    try {
      await ApiService.logout();
    } catch (e) {
      // Continue with logout even if API call fails
    } finally {
      await ApiService.clearToken();
      _user = null;
      notifyListeners();
    }
  }

  Future<void> fetchProfile() async {
    try {
      final response = await ApiService.getProfile();
      
      if (response['success']) {
        _user = User.fromJson(response['data']);
        notifyListeners();
      }
    } catch (e) {
      // If profile fetch fails, clear token
      await ApiService.clearToken();
      _user = null;
      notifyListeners();
    }
  }

  void _setLoading(bool loading) {
    _isLoading = loading;
    notifyListeners();
  }

  void _setError(String error) {
    _error = error;
    notifyListeners();
  }

  void _clearError() {
    _error = null;
    notifyListeners();
  }
}

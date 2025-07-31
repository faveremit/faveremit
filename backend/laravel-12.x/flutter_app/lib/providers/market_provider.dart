import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_app/models/crypto_pair.dart';
import 'package:flutter_app/services/api_service.dart';

class MarketProvider with ChangeNotifier {
  List<CryptoPair> _cryptoPairs = [];
  CryptoPair? _selectedPair;
  bool _isLoading = false;
  String? _error;
  Timer? _updateTimer;

  List<CryptoPair> get cryptoPairs => _cryptoPairs;
  CryptoPair? get selectedPair => _selectedPair;
  bool get isLoading => _isLoading;
  String? get error => _error;

  void initialize() {
    fetchMarketData();
    startRealTimeUpdates();
  }

  void dispose() {
    _updateTimer?.cancel();
    super.dispose();
  }

  Future<void> fetchMarketData() async {
    try {
      _setLoading(true);
      _clearError();

      final response = await ApiService.getMarketData();
      
      if (response['success']) {
        _cryptoPairs = (response['data'] as List)
            .map((pair) => CryptoPair.fromJson(pair))
            .toList();
        
        // Set default selected pair if none selected
        if (_selectedPair == null && _cryptoPairs.isNotEmpty) {
          _selectedPair = _cryptoPairs.firstWhere(
            (pair) => pair.symbol == 'BTC/USDT',
            orElse: () => _cryptoPairs.first,
          );
        }
        
        notifyListeners();
      } else {
        throw Exception(response['message'] ?? 'Failed to fetch market data');
      }
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }

  void selectPair(CryptoPair pair) {
    _selectedPair = pair;
    notifyListeners();
  }

  void startRealTimeUpdates() {
    _updateTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      fetchMarketData();
    });
  }

  List<CryptoPair> searchPairs(String query) {
    if (query.isEmpty) return _cryptoPairs;
    
    return _cryptoPairs.where((pair) =>
      pair.symbol.toLowerCase().contains(query.toLowerCase()) ||
      pair.baseCurrency.toLowerCase().contains(query.toLowerCase())
    ).toList();
  }

  List<CryptoPair> get topGainers {
    final sorted = List<CryptoPair>.from(_cryptoPairs);
    sorted.sort((a, b) => b.priceChangePercentage24h.compareTo(a.priceChangePercentage24h));
    return sorted.take(10).toList();
  }

  List<CryptoPair> get topLosers {
    final sorted = List<CryptoPair>.from(_cryptoPairs);
    sorted.sort((a, b) => a.priceChangePercentage24h.compareTo(b.priceChangePercentage24h));
    return sorted.take(10).toList();
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

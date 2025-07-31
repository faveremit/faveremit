import 'package:flutter/material.dart';
import 'package:flutter_app/models/portfolio.dart';
import 'package:flutter_app/models/trade.dart';
import 'package:flutter_app/services/api_service.dart';

class PortfolioProvider with ChangeNotifier {
  Portfolio? _portfolio;
  List<Trade> _trades = [];
  bool _isLoading = false;
  String? _error;

  Portfolio? get portfolio => _portfolio;
  List<Trade> get trades => _trades;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchPortfolio() async {
    try {
      _setLoading(true);
      _clearError();

      final response = await ApiService.getPortfolio();
      
      if (response['success']) {
        _portfolio = Portfolio.fromJson(response['data']['portfolio']);
        notifyListeners();
      } else {
        throw Exception(response['message'] ?? 'Failed to fetch portfolio');
      }
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }

  Future<void> fetchTrades() async {
    try {
      final response = await ApiService.getTrades();
      
      if (response['success']) {
        final tradesData = response['data']['data'] as List;
        _trades = tradesData.map((trade) => Trade.fromJson(trade)).toList();
        notifyListeners();
      }
    } catch (e) {
      _setError(e.toString());
    }
  }

  Future<void> executeTrade({
    required String pair,
    required String side,
    required String type,
    required double amount,
    double? price,
  }) async {
    try {
      _setLoading(true);
      _clearError();

      final response = await ApiService.executeTrade(
        pair: pair,
        side: side,
        type: type,
        amount: amount,
        price: price,
      );
      
      if (response['success']) {
        // Refresh portfolio and trades after successful trade
        await Future.wait([
          fetchPortfolio(),
          fetchTrades(),
        ]);
      } else {
        throw Exception(response['message'] ?? 'Trade execution failed');
      }
    } catch (e) {
      _setError(e.toString());
      rethrow;
    } finally {
      _setLoading(false);
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

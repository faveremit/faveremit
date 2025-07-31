class CryptoPair {
  final int id;
  final String symbol;
  final String baseCurrency;
  final String quoteCurrency;
  final double currentPrice;
  final double priceChange24h;
  final double priceChangePercentage24h;
  final double high24h;
  final double low24h;
  final double volume24h;
  final double? marketCap;
  final bool isActive;
  final DateTime lastUpdated;

  CryptoPair({
    required this.id,
    required this.symbol,
    required this.baseCurrency,
    required this.quoteCurrency,
    required this.currentPrice,
    required this.priceChange24h,
    required this.priceChangePercentage24h,
    required this.high24h,
    required this.low24h,
    required this.volume24h,
    this.marketCap,
    required this.isActive,
    required this.lastUpdated,
  });

  factory CryptoPair.fromJson(Map<String, dynamic> json) {
    return CryptoPair(
      id: json['id'],
      symbol: json['symbol'],
      baseCurrency: json['base_currency'],
      quoteCurrency: json['quote_currency'],
      currentPrice: double.parse(json['current_price'].toString()),
      priceChange24h: double.parse(json['price_change_24h'].toString()),
      priceChangePercentage24h: double.parse(json['price_change_percentage_24h'].toString()),
      high24h: double.parse(json['high_24h'].toString()),
      low24h: double.parse(json['low_24h'].toString()),
      volume24h: double.parse(json['volume_24h'].toString()),
      marketCap: json['market_cap'] != null 
          ? double.parse(json['market_cap'].toString()) 
          : null,
      isActive: json['is_active'] ?? true,
      lastUpdated: DateTime.parse(json['last_updated']),
    );
  }

  String get formattedPrice => '\$${currentPrice.toStringAsFixed(8)}';
  String get formattedVolume => '\$${(volume24h / 1000000).toStringAsFixed(1)}M';
  String get formattedChange => '${priceChangePercentage24h >= 0 ? '+' : ''}${priceChangePercentage24h.toStringAsFixed(2)}%';
  
  bool get isPositiveChange => priceChangePercentage24h >= 0;
}

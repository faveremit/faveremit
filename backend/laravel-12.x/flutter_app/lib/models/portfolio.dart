class Portfolio {
  final int id;
  final int userId;
  final double totalValueUsd;
  final double totalInvested;
  final double totalPnl;
  final double totalPnlPercentage;
  final double availableBalance;
  final List<PortfolioAsset> assets;

  Portfolio({
    required this.id,
    required this.userId,
    required this.totalValueUsd,
    required this.totalInvested,
    required this.totalPnl,
    required this.totalPnlPercentage,
    required this.availableBalance,
    required this.assets,
  });

  factory Portfolio.fromJson(Map<String, dynamic> json) {
    return Portfolio(
      id: json['id'],
      userId: json['user_id'],
      totalValueUsd: double.parse(json['total_value_usd'].toString()),
      totalInvested: double.parse(json['total_invested'].toString()),
      totalPnl: double.parse(json['total_pnl'].toString()),
      totalPnlPercentage: double.parse(json['total_pnl_percentage'].toString()),
      availableBalance: double.parse(json['available_balance'].toString()),
      assets: (json['assets'] as List<dynamic>?)
          ?.map((asset) => PortfolioAsset.fromJson(asset))
          .toList() ?? [],
    );
  }

  String get formattedTotalValue => '\$${totalValueUsd.toStringAsFixed(2)}';
  String get formattedPnl => '\$${totalPnl.toStringAsFixed(2)}';
  String get formattedAvailableBalance => '\$${availableBalance.toStringAsFixed(2)}';
  
  bool get isPositivePnl => totalPnl >= 0;
}

class PortfolioAsset {
  final int id;
  final int portfolioId;
  final String symbol;
  final double amount;
  final double averageBuyPrice;
  final double currentValueUsd;
  final double investedAmount;
  final double pnl;
  final double pnlPercentage;

  PortfolioAsset({
    required this.id,
    required this.portfolioId,
    required this.symbol,
    required this.amount,
    required this.averageBuyPrice,
    required this.currentValueUsd,
    required this.investedAmount,
    required this.pnl,
    required this.pnlPercentage,
  });

  factory PortfolioAsset.fromJson(Map<String, dynamic> json) {
    return PortfolioAsset(
      id: json['id'],
      portfolioId: json['portfolio_id'],
      symbol: json['symbol'],
      amount: double.parse(json['amount'].toString()),
      averageBuyPrice: double.parse(json['average_buy_price'].toString()),
      currentValueUsd: double.parse(json['current_value_usd'].toString()),
      investedAmount: double.parse(json['invested_amount'].toString()),
      pnl: double.parse(json['pnl'].toString()),
      pnlPercentage: double.parse(json['pnl_percentage'].toString()),
    );
  }

  String get formattedAmount => amount.toStringAsFixed(8);
  String get formattedValue => '\$${currentValueUsd.toStringAsFixed(2)}';
  String get formattedPnl => '\$${pnl.toStringAsFixed(2)}';
  
  bool get isPositivePnl => pnl >= 0;
}

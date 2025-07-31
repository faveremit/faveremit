class Trade {
  final int id;
  final String tradeId;
  final int userId;
  final String pair;
  final String side;
  final String type;
  final double amount;
  final double price;
  final double total;
  final double fee;
  final String status;
  final DateTime? executedAt;
  final DateTime createdAt;

  Trade({
    required this.id,
    required this.tradeId,
    required this.userId,
    required this.pair,
    required this.side,
    required this.type,
    required this.amount,
    required this.price,
    required this.total,
    required this.fee,
    required this.status,
    this.executedAt,
    required this.createdAt,
  });

  factory Trade.fromJson(Map<String, dynamic> json) {
    return Trade(
      id: json['id'],
      tradeId: json['trade_id'],
      userId: json['user_id'],
      pair: json['pair'],
      side: json['side'],
      type: json['type'],
      amount: double.parse(json['amount'].toString()),
      price: double.parse(json['price'].toString()),
      total: double.parse(json['total'].toString()),
      fee: double.parse(json['fee'].toString()),
      status: json['status'],
      executedAt: json['executed_at'] != null
          ? DateTime.parse(json['executed_at'])
          : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  String get formattedAmount => amount.toStringAsFixed(8);
  String get formattedPrice => '\$${price.toStringAsFixed(8)}';
  String get formattedTotal => '\$${total.toStringAsFixed(2)}';
  
  bool get isBuy => side.toLowerCase() == 'buy';
  bool get isCompleted => status.toLowerCase() == 'completed';
}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $sale->sale_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.4; }
        .receipt-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .header h1 { margin: 0 0 10px 0; font-size: 24px; color: #1e293b; }
        .header p { margin: 2px 0; color: #64748b; font-size: 14px; }
        .details-container { display: table; width: 100%; margin-bottom: 30px; }
        .details-left { display: table-cell; width: 50%; }
        .details-right { display: table-cell; width: 50%; text-align: right; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.items th { background-color: #f8fafc; padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; color: #475569; text-transform: uppercase; font-size: 12px; }
        table.items th.text-right { text-align: right; }
        table.items td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        table.items td.text-right { text-align: right; }
        .summary-container { width: 100%; display: table; }
        .summary-spacer { display: table-cell; width: 60%; }
        .summary-box { display: table-cell; width: 40%; }
        .summary-row { display: table; width: 100%; margin-bottom: 5px; }
        .summary-label { display: table-cell; text-align: left; color: #64748b; font-size: 14px; }
        .summary-value { display: table-cell; text-align: right; font-weight: bold; font-size: 14px; }
        .summary-row.total { border-top: 2px solid #e2e8f0; padding-top: 10px; margin-top: 5px; }
        .summary-row.total .summary-label { font-size: 16px; color: #1e293b; font-weight: bold; }
        .summary-row.total .summary-value { font-size: 18px; color: #1e293b; }
        .footer { text-align: center; margin-top: 50px; color: #94a3b8; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    @php
        $computedTax = $sale->items->sum('tax_amount') ?? 0;
        $subtotalExclTax = $sale->subtotal;
    @endphp
    <div class="receipt-container">
        <div class="header">
            <h1>{{ $business ? $business->name : 'S-FACTORY' }}</h1>
            @if($business && $business->address) <p>{{ $business->address }}</p> @endif
            @if($business && $business->phone) <p>Tel: {{ $business->phone }}</p> @endif
            @if($business && $business->email) <p>Email: {{ $business->email }}</p> @endif
        </div>

        <div class="details-container">
            <div class="details-left">
                <strong>Bill To:</strong><br>
                {{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}<br>
                @if($sale->customer && $sale->customer->phone) {{ $sale->customer->phone }}<br> @endif
                @if($sale->customer && $sale->customer->address) {{ $sale->customer->address }} @endif
            </div>
            <div class="details-right">
                <strong>Receipt No:</strong> {{ $sale->sale_number }}<br>
                <strong>Date:</strong> {{ \Carbon\Carbon::parse($sale->transaction_date)->format('d M Y') }}<br>
                <strong>Served By:</strong> {{ $sale->user ? $sale->user->name : 'System' }}<br>
                <strong>Status:</strong> <span style="text-transform: uppercase;">{{ $sale->payment_status }}</span>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>
                        {{ $item->product ? $item->product->name : 'Unknown Product' }}
                        @if($item->discount_amount > 0)
                            <br><small style="color: #e11d48;">Discount: {{ number_format($item->discount_amount, 2) }}</small>
                        @endif
                        @if($item->tax_amount > 0)
                            <br><small style="color: #6366f1;">+ {{ number_format($item->tax_amount, 2) }} VAT</small>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-container">
            <div class="summary-spacer"></div>
            <div class="summary-box">
                <div class="summary-row">
                    <div class="summary-label">Subtotal (Excl. VAT):</div>
                    <div class="summary-value">{{ number_format($subtotalExclTax, 2) }} TZS</div>
                </div>
                @if($sale->discount_amount > 0)
                <div class="summary-row">
                    <div class="summary-label">Discount:</div>
                    <div class="summary-value" style="color: #e11d48;">-{{ number_format($sale->discount_amount, 2) }} TZS</div>
                </div>
                @endif
                @if($computedTax > 0)
                <div class="summary-row">
                    <div class="summary-label">VAT (18%):</div>
                    <div class="summary-value">{{ number_format($computedTax, 2) }} TZS</div>
                </div>
                @endif
                <div class="summary-row total">
                    <div class="summary-label">Total Amount:</div>
                    <div class="summary-value">{{ number_format($sale->total_amount, 2) }} TZS</div>
                </div>
                <div class="summary-row" style="margin-top: 10px;">
                    <div class="summary-label">Amount Paid:</div>
                    <div class="summary-value" style="color: #059669;">{{ number_format($sale->paid_amount, 2) }} TZS</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Balance Due:</div>
                    <div class="summary-value" style="color: {{ $sale->balance_amount > 0 ? '#e11d48' : '#64748b' }};">{{ number_format($sale->balance_amount, 2) }} TZS</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Generated on {{ now()->format('d M Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

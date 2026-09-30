<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #1f2937;
        }
        .header p {
            margin: 0;
            color: #6b7280;
        }
        .summary {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .summary td {
            padding: 10px;
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            text-align: center;
            width: 25%;
        }
        .summary .label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            display: block;
            margin-bottom: 5px;
        }
        .summary .value {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }
        table.transactions {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.transactions th, table.transactions td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }
        table.transactions th {
            background-color: #f9fafb;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #374151;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            padding: 3px 6px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-danger { background-color: #ffe4e6; color: #9f1239; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-default { background-color: #f3f4f6; color: #374151; }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Sales Report</h1>
        <p>{{ $business?->name ?? 'Business Name' }}</p>
        <p>Period: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</p>
        @if(!empty($filters['status']))
            <p>Filtered by Status: <strong>{{ ucfirst($filters['status']) }}</strong></p>
        @endif
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="label">Total Revenue</span>
                <span class="value">{{ number_format($summary['total_revenue'], 2) }} TZS</span>
            </td>
            <td>
                <span class="label">Total Collected</span>
                <span class="value" style="color: #059669;">{{ number_format($summary['total_collected'], 2) }} TZS</span>
            </td>
            <td>
                <span class="label">Outstanding Debt</span>
                <span class="value" style="color: #e11d48;">{{ number_format($summary['total_outstanding'], 2) }} TZS</span>
            </td>
            <td>
                <span class="label">Transaction Vol.</span>
                <span class="value">{{ $summary['total_transactions'] }}</span>
            </td>
        </tr>
    </table>

    <table class="transactions">
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice No.</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Payment</th>
                <th class="text-right">Total Amount (TZS)</th>
                <th class="text-right">Balance (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($sale->transaction_date)->format('d M Y') }}</td>
                    <td><strong>{{ $sale->sale_number }}</strong></td>
                    <td>{{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</td>
                    <td>
                        @if($sale->status === 'cancelled')
                            <span class="badge badge-danger">Cancelled</span>
                        @else
                            <span class="badge badge-default">{{ $sale->status }}</span>
                        @endif
                    </td>
                    <td>
                        @if($sale->payment_status === 'paid')
                            <span class="badge badge-success">Paid</span>
                        @elseif($sale->payment_status === 'unpaid')
                            <span class="badge badge-danger">Unpaid</span>
                        @else
                            <span class="badge badge-warning">{{ $sale->payment_status }}</span>
                        @endif
                    </td>
                    <td class="text-right" style="{{ $sale->status === 'cancelled' ? 'text-decoration: line-through; color: #9ca3af;' : '' }}">
                        {{ number_format($sale->total_amount, 2) }}
                    </td>
                    <td class="text-right" style="{{ $sale->balance_amount > 0 ? 'color: #e11d48; font-weight: bold;' : 'color: #059669;' }}">
                        {{ number_format($sale->balance_amount, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">No sales found for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i:s') }} by {{ auth()->user()->name }}
    </div>

</body>
</html>

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
        <h1>Inventory Report</h1>
        <p>{{ $business?->name ?? 'Business Name' }}</p>
        <p>Period: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</p>
        @if(!empty($filters['status']))
            <p>Filtered by Status: <strong>{{ ucfirst($filters['status']) }}</strong></p>
        @endif
    </div>

    <table class="summary">
<tr>
<td><span class="label">Total Unique Items</span><span class="value">{{ $summary['total_items'] }}</span></td>
<td><span class="label">Total Stock Value</span><span class="value" style="color: #059669;">{{ number_format($summary['total_stock_value'], 2) . " TZS" }}</span></td>
<td><span class="label">Low Stock Items</span><span class="value" style="color: #e11d48;">{{ $summary['low_stock_items'] }}</span></td>
<td><span class="label">Status</span><span class="value">{{ "Current Stock" }}</span></td>
</tr>
</table>

    <table class="transactions">
<thead>
<tr>
<th>Item Name</th>
<th>SKU</th>
<th>Category</th>
<th>Brand</th>
<th class="text-right">In Stock</th>
<th class="text-right">Total Value (TZS)</th>
</tr>
</thead>
<tbody>
@forelse($products as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->category ? $item->category->name : 'N/A' }}</td>
                    <td>{{ $item->brand ? $item->brand->name : 'N/A' }}</td>
                    <td>
                        @if(($item->stock_sum_quantity ?? 0) <= $item->min_stock)
                            <span style="color: #e11d48; font-weight:bold;">{{ number_format($item->stock_sum_quantity ?? 0, 2) }}</span>
                        @else
                            {{ number_format($item->stock_sum_quantity ?? 0, 2) }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->stock_value, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No products found.</td></tr>
            @endforelse
</tbody>
</table>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i:s') }} by {{ auth()->user()->name }}
    </div>

</body>
</html>

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
        <h1>Profit & Loss Statement</h1>
        <p>{{ $business?->name ?? 'Business Name' }}</p>
        <p>Period: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</p>
        @if(!empty($filters['status']))
            <p>Filtered by Status: <strong>{{ ucfirst($filters['status']) }}</strong></p>
        @endif
    </div>

    
    <table class="summary">
        <tr>
            <td><span class="label">Total Revenue</span><span class="value">{{ number_format($summary['total_revenue'], 2) }} TZS</span></td>
            <td><span class="label">COGS</span><span class="value" style="color:#e11d48;">{{ number_format($summary['cogs'], 2) }} TZS</span></td>
            <td><span class="label">Gross Profit</span><span class="value">{{ number_format($summary['gross_profit'], 2) }} TZS</span></td>
            <td><span class="label">Net Profit</span><span class="value" style="color:#059669;">{{ number_format($summary['net_profit'], 2) }} TZS</span></td>
        </tr>
    </table>


    
    <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
        <h3 style="margin-top:0; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Income</h3>
        <p>Total Revenue: <strong>{{ number_format($summary['total_revenue'], 2) }} TZS</strong></p>
        <p>Cost of Goods Sold (COGS): <strong>{{ number_format($summary['cogs'], 2) }} TZS</strong></p>
        <p>Gross Profit: <strong>{{ number_format($summary['gross_profit'], 2) }} TZS</strong></p>
        
        <h3 style="margin-top:20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Expenses</h3>
        <p>Total Operating Expenses: <strong>{{ number_format($summary['total_expenses'], 2) }} TZS</strong></p>
        
        <h3 style="margin-top:20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Net Profit</h3>
        <h2 style="color: {{ $summary['net_profit'] >= 0 ? '#059669' : '#e11d48' }}; margin-bottom: 0;">{{ number_format($summary['net_profit'], 2) }} TZS</h2>
    </div>


    <div class="footer">
        Generated on {{ now()->format('d M Y H:i:s') }} by {{ auth()->user()->name }}
    </div>

</body>
</html>

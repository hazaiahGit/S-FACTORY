<?php
$baseTemplate = file_get_contents('resources/views/reports/sales-pdf.blade.php');

$reports = [
    'purchases' => [
        'title' => 'Purchases Report',
        'summary_blocks' => [
            ['label' => 'Total Purchases', 'value' => 'number_format($summary[\'total_purchases\'], 2) . " TZS"'],
            ['label' => 'Total Paid', 'value' => 'number_format($summary[\'total_paid\'], 2) . " TZS"', 'style' => 'color: #059669;'],
            ['label' => 'Outstanding Balance', 'value' => 'number_format($summary[\'total_outstanding\'], 2) . " TZS"', 'style' => 'color: #e11d48;'],
            ['label' => 'Transaction Vol.', 'value' => '$summary[\'total_transactions\']'],
        ],
        'table_headers' => ['Date', 'PO No.', 'Supplier', 'Status', 'Payment', 'Total Amount (TZS)', 'Balance (TZS)'],
        'table_rows' => '@forelse($purchases as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->transaction_date)->format(\'d M Y\') }}</td>
                    <td><strong>{{ $item->purchase_number }}</strong></td>
                    <td>{{ $item->supplier ? $item->supplier->name : \'N/A\' }}</td>
                    <td><span class="badge badge-default">{{ $item->status }}</span></td>
                    <td><span class="badge badge-default">{{ $item->payment_status }}</span></td>
                    <td class="text-right">{{ number_format($item->total_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($item->balance_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No purchases found.</td></tr>
            @endforelse'
    ],
    'inventory' => [
        'title' => 'Inventory Report',
        'summary_blocks' => [
            ['label' => 'Total Unique Items', 'value' => '$summary[\'total_items\']'],
            ['label' => 'Total Stock Value', 'value' => 'number_format($summary[\'total_stock_value\'], 2) . " TZS"', 'style' => 'color: #059669;'],
            ['label' => 'Low Stock Items', 'value' => '$summary[\'low_stock_items\']', 'style' => 'color: #e11d48;'],
            ['label' => 'Status', 'value' => '"Current Stock"'],
        ],
        'table_headers' => ['Item Name', 'SKU', 'Category', 'Brand', 'In Stock', 'Total Value (TZS)'],
        'table_rows' => '@forelse($products as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->category ? $item->category->name : \'N/A\' }}</td>
                    <td>{{ $item->brand ? $item->brand->name : \'N/A\' }}</td>
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
            @endforelse'
    ],
    'expenses' => [
        'title' => 'Expenses Report',
        'summary_blocks' => [
            ['label' => 'Total Expenses', 'value' => 'number_format($summary[\'total_expenses\'], 2) . " TZS"', 'style' => 'color: #e11d48;'],
            ['label' => 'Total Transactions', 'value' => '$summary[\'total_transactions\']'],
        ],
        'table_headers' => ['Date', 'Expense No.', 'Category', 'Title', 'Status', 'Amount (TZS)'],
        'table_rows' => '@forelse($expenses as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->expense_date)->format(\'d M Y\') }}</td>
                    <td><strong>{{ $item->expense_number }}</strong></td>
                    <td>{{ $item->category ? $item->category->name : \'N/A\' }}</td>
                    <td>{{ $item->title }}</td>
                    <td><span class="badge badge-default">{{ $item->status }}</span></td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No expenses found.</td></tr>
            @endforelse'
    ],
    'customers' => [
        'title' => 'Customers Report',
        'summary_blocks' => [
            ['label' => 'Total Customers', 'value' => '$summary[\'total_customers\']'],
            ['label' => 'Total Revenue', 'value' => 'number_format($summary[\'total_revenue\'], 2) . " TZS"', 'style' => 'color: #059669;'],
            ['label' => 'Total Debt Owed', 'value' => 'number_format($summary[\'total_debt\'], 2) . " TZS"', 'style' => 'color: #e11d48;'],
        ],
        'table_headers' => ['Customer Name', 'Phone', 'Total Revenue (TZS)', 'Total Paid (TZS)', 'Outstanding Debt (TZS)'],
        'table_rows' => '@forelse($customers as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->phone }}</td>
                    <td class="text-right">{{ number_format($item->total_revenue, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_paid, 2) }}</td>
                    <td class="text-right" style="{{ $item->debt > 0 ? \'color: #e11d48; font-weight: bold;\' : \'color: #059669;\' }}">
                        {{ number_format($item->debt, 2) }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">No customers found.</td></tr>
            @endforelse'
    ],
    'manufacturing' => [
        'title' => 'Manufacturing Report',
        'summary_blocks' => [
            ['label' => 'Total Orders', 'value' => '$summary[\'total_orders\']'],
            ['label' => 'Completed Orders', 'value' => '$summary[\'completed_orders\']', 'style' => 'color: #059669;'],
            ['label' => 'Total Produced Qty', 'value' => 'number_format($summary[\'total_produced\'], 2)'],
        ],
        'table_headers' => ['Start Date', 'Order No.', 'Product', 'Status', 'Planned Qty', 'Actual Qty'],
        'table_rows' => '@forelse($orders as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->start_date)->format(\'d M Y\') }}</td>
                    <td><strong>{{ $item->order_number }}</strong></td>
                    <td>{{ $item->product ? $item->product->name : \'N/A\' }}</td>
                    <td><span class="badge badge-default">{{ $item->status }}</span></td>
                    <td class="text-right">{{ number_format($item->planned_quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->actual_quantity ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No orders found.</td></tr>
            @endforelse'
    ],
];

// Special one for Profit Loss
$profitLossTemplate = str_replace('<h1>Sales Report</h1>', '<h1>Profit & Loss Statement</h1>', $baseTemplate);
$profitLossTemplate = preg_replace('/<table class="summary">.*?<\/table>/s', '
    <table class="summary">
        <tr>
            <td><span class="label">Total Revenue</span><span class="value">{{ number_format($summary[\'total_revenue\'], 2) }} TZS</span></td>
            <td><span class="label">COGS</span><span class="value" style="color:#e11d48;">{{ number_format($summary[\'cogs\'], 2) }} TZS</span></td>
            <td><span class="label">Gross Profit</span><span class="value">{{ number_format($summary[\'gross_profit\'], 2) }} TZS</span></td>
            <td><span class="label">Net Profit</span><span class="value" style="color:#059669;">{{ number_format($summary[\'net_profit\'], 2) }} TZS</span></td>
        </tr>
    </table>
', $profitLossTemplate);
$profitLossTemplate = preg_replace('/<table class="transactions">.*?<\/table>/s', '
    <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
        <h3 style="margin-top:0; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Income</h3>
        <p>Total Revenue: <strong>{{ number_format($summary[\'total_revenue\'], 2) }} TZS</strong></p>
        <p>Cost of Goods Sold (COGS): <strong>{{ number_format($summary[\'cogs\'], 2) }} TZS</strong></p>
        <p>Gross Profit: <strong>{{ number_format($summary[\'gross_profit\'], 2) }} TZS</strong></p>
        
        <h3 style="margin-top:20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Expenses</h3>
        <p>Total Operating Expenses: <strong>{{ number_format($summary[\'total_expenses\'], 2) }} TZS</strong></p>
        
        <h3 style="margin-top:20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Net Profit</h3>
        <h2 style="color: {{ $summary[\'net_profit\'] >= 0 ? \'#059669\' : \'#e11d48\' }}; margin-bottom: 0;">{{ number_format($summary[\'net_profit\'], 2) }} TZS</h2>
    </div>
', $profitLossTemplate);
file_put_contents('resources/views/reports/profit_loss-pdf.blade.php', $profitLossTemplate);

foreach ($reports as $key => $config) {
    $content = $baseTemplate;
    $content = str_replace('<h1>Sales Report</h1>', '<h1>' . $config['title'] . '</h1>', $content);
    
    // Replace summary block
    $summaryHtml = "<table class=\"summary\">\n<tr>\n";
    foreach ($config['summary_blocks'] as $block) {
        $style = isset($block['style']) ? " style=\"{$block['style']}\"" : "";
        $summaryHtml .= "<td><span class=\"label\">{$block['label']}</span><span class=\"value\"{$style}>{{ {$block['value']} }}</span></td>\n";
    }
    $summaryHtml .= "</tr>\n</table>";
    $content = preg_replace('/<table class="summary">.*?<\/table>/s', $summaryHtml, $content);
    
    // Replace transactions table
    $tableHtml = "<table class=\"transactions\">\n<thead>\n<tr>\n";
    foreach ($config['table_headers'] as $header) {
        $class = strpos($header, '(TZS)') !== false || $header == 'In Stock' || strpos($header, 'Qty') !== false ? ' class="text-right"' : '';
        $tableHtml .= "<th{$class}>{$header}</th>\n";
    }
    $tableHtml .= "</tr>\n</thead>\n<tbody>\n" . $config['table_rows'] . "\n</tbody>\n</table>";
    $content = preg_replace('/<table class="transactions">.*?<\/table>/s', $tableHtml, $content);
    
    file_put_contents("resources/views/reports/{$key}-pdf.blade.php", $content);
}
echo "Done";

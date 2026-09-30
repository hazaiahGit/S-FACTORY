<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePayment;

class NotificationService
{
    /**
     * Send an application notification.
     */
    public function send(
        int $businessId,
        ?int $branchId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?int $userId = null
    ): AppNotification {
        return AppNotification::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'user_id' => $userId,
            'type' => $type, // 'alert', 'warning', 'success', 'info'
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'read_by' => [],
        ]);
    }

    /**
     * Trigger a low stock notification.
     */
    public function notifyLowStock(int $branchId, Product $product, float $currentQty, float $minStock): ?AppNotification
    {
        // Avoid duplicate spam notifications within the last 4 hours for this product & branch
        $exists = AppNotification::where('business_id', $product->business_id)
            ->where('branch_id', $branchId)
            ->where('type', 'alert')
            ->where('title', 'Low Stock Alert')
            ->where('message', 'like', "%{$product->name}%")
            ->where('created_at', '>=', now()->subHours(4))
            ->exists();

        if ($exists) {
            return null;
        }

        $branchName = Branch::find($branchId)?->name ?? 'Branch';

        return $this->send(
            businessId: $product->business_id,
            branchId: $branchId,
            type: 'alert',
            title: 'Low Stock Alert',
            message: "Item '{$product->name}' is running low ({$currentQty} remaining vs min {$minStock}) at {$branchName}.",
            actionUrl: route('inventory.index')
        );
    }

    /**
     * Trigger a credit sale notification.
     */
    public function notifyCreditSale(Sale $sale): AppNotification
    {
        $branchName = $sale->branch?->name ?? 'Branch';
        $customerName = $sale->customer?->name ?? 'Walk-in Customer';
        $balanceFormatted = number_format($sale->balance_amount).' TZS';

        return $this->send(
            businessId: $sale->business_id,
            branchId: $sale->branch_id,
            type: 'warning',
            title: 'New Credit Sale Registered',
            message: "Sale #{$sale->sale_number} for {$customerName} placed on credit ({$balanceFormatted} due) at {$branchName}.",
            actionUrl: route('sales.show', $sale->id)
        );
    }

    /**
     * Trigger a payment received notification.
     */
    public function notifyPaymentReceived(SalePayment $payment): AppNotification
    {
        $sale = $payment->sale;
        $branchName = $sale?->branch?->name ?? 'Branch';
        $amountFormatted = number_format($payment->amount).' TZS';

        return $this->send(
            businessId: $payment->business_id,
            branchId: $sale?->branch_id,
            type: 'success',
            title: 'Payment Received',
            message: "Payment of {$amountFormatted} recorded for Sale #{$sale?->sale_number} via {$payment->payment_method} at {$branchName}.",
            actionUrl: $sale ? route('sales.show', $sale->id) : null
        );
    }
}

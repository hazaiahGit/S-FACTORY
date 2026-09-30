<?php
$file = "app/Services/SaleService.php";
$content = file_get_contents($file);

$old_logic = <<<'EOT'
            // Assume payments are unchanged, update balance
            $paidAmount = (float) $sale->paid_amount;
            $balanceAmount = $totalAmount - $paidAmount;
EOT;
$new_logic = <<<'EOT'
            // Update payments if provided
            $paidAmount = (float) $sale->paid_amount;
            if (isset($data['payments'])) {
                // Delete old payments
                $sale->payments()->delete();
                $paidAmount = 0;
                $businessId = $user->business_id;
                foreach ($data['payments'] as $paymentData) {
                    $amount = (float) $paymentData['amount'];
                    if ($amount <= 0) continue;
                    
                    \App\Models\SalePayment::create([
                        'sale_id' => $sale->id,
                        'business_id' => $businessId,
                        'customer_id' => $data['customer_id'] ?? null,
                        'user_id' => $user->id,
                        'payment_number' => $this->numberGenerator->generatePaymentNumber('sale', $businessId),
                        'payment_method' => $paymentData['payment_method'] ?? 'cash',
                        'amount' => $amount,
                        'payment_date' => $data['transaction_date'] ?? today(),
                        'reference' => $paymentData['reference'] ?? null,
                    ]);
                    $paidAmount += $amount;
                }
            }
            $balanceAmount = $totalAmount - $paidAmount;
EOT;
$content = str_replace($old_logic, $new_logic, $content);
file_put_contents($file, $content);
?>

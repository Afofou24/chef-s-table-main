<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['order.table', 'cashier']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->has('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($payments);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01|max:99999.99',
            'payment_method' => 'required|in:cash,card,mobile,voucher,mixed',
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ], [
            'order_id.required' => 'La commande est obligatoire.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.min' => 'Le montant doit être supérieur à 0.',
            'payment_method.required' => 'Le mode de paiement est obligatoire.',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        if ($order->status === 'cancelled') {
            return response()->json([
                'message' => 'Impossible de payer une commande annulée.',
            ], 422);
        }

        $totalPaid = $order->payments()->where('status', 'completed')->sum('amount');
        $remaining = $order->total_amount - $totalPaid;

        if ($validated['amount'] > $remaining + 0.01) {
            return response()->json([
                'message' => "Le montant dépasse le reste à payer ({$remaining} €).",
            ], 422);
        }

        $payment = Payment::create([
            'order_id' => $validated['order_id'],
            'cashier_id' => $request->user()->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'transaction_reference' => $validated['transaction_reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'completed',
        ]);

        $newTotalPaid = $totalPaid + $validated['amount'];
        if ($newTotalPaid >= $order->total_amount - 0.01) {
            $order->update(['status' => 'completed']);
        }

        $payment->load(['order', 'cashier']);

        return response()->json([
            'message' => 'Paiement enregistré avec succès.',
            'data' => $payment,
            'remaining' => max(0, $order->total_amount - $newTotalPaid),
        ], 201);
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['order.table', 'order.items.menuItem', 'cashier']);

        return response()->json($payment);
    }

    /**
     * Refund a payment.
     */
    public function refund(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status !== 'completed') {
            return response()->json([
                'message' => 'Seuls les paiements complétés peuvent être remboursés.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'La raison du remboursement est obligatoire.',
        ]);

        $payment->update([
            'status' => 'refunded',
            'notes' => ($payment->notes ? $payment->notes . "\n" : '') . 
                       "Remboursé: " . $validated['reason'],
        ]);

        $order = $payment->order;
        if ($order->status === 'completed') {
            $remainingPaid = $order->payments()->where('status', 'completed')->sum('amount');
            if ($remainingPaid < $order->total_amount) {
                $order->update(['status' => 'served']);
            }
        }

        return response()->json([
            'message' => 'Paiement remboursé avec succès.',
            'data' => $payment->fresh(['order', 'cashier']),
        ]);
    }

    /**
     * Get daily summary.
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        $summary = Payment::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->selectRaw('
                payment_method,
                COUNT(*) as count,
                SUM(amount) as total
            ')
            ->groupBy('payment_method')
            ->get();

        $totalAmount = $summary->sum('total');
        $totalCount = $summary->sum('count');

        return response()->json([
            'date' => $date,
            'by_method' => $summary,
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
        ]);
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        if ($payment->status === 'completed') {
            return response()->json([
                'message' => 'Impossible de supprimer un paiement complété. Utilisez le remboursement.',
            ], 422);
        }

        $payment->delete();

        return response()->json([
            'message' => 'Paiement supprimé avec succès.',
        ]);
    }
}

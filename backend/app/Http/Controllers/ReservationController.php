<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    /**
     * Display a listing of reservations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::with('table');

        if ($request->has('date')) {
            $query->whereDate('reservation_date', $request->input('date'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('reservation_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('reservation_date', '<=', $request->input('date_to'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->input('table_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $reservations = $query->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->paginate($request->input('per_page', 15));

        return response()->json($reservations);
    }

    /**
     * Store a newly created reservation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:restaurant_tables,id',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:20|regex:/^[+]?[0-9\s\-()]+$/',
            'customer_email' => 'nullable|email|max:150',
            'guests_count' => 'required|integer|min:1|max:20',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|date_format:H:i',
            'duration' => 'integer|min:30|max:480',
            'notes' => 'nullable|string|max:500',
        ], [
            'table_id.required' => 'La table est obligatoire.',
            'customer_name.required' => 'Le nom du client est obligatoire.',
            'guests_count.required' => 'Le nombre de personnes est obligatoire.',
            'reservation_date.required' => 'La date est obligatoire.',
            'reservation_date.after_or_equal' => 'La date doit être aujourd\'hui ou future.',
            'reservation_time.required' => 'L\'heure est obligatoire.',
        ]);

        $existingReservation = Reservation::where('table_id', $validated['table_id'])
            ->where('reservation_date', $validated['reservation_date'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($validated) {
                $duration = $validated['duration'] ?? 120;
                $startTime = $validated['reservation_time'];
                $endTime = date('H:i', strtotime($startTime) + ($duration * 60));

                $q->whereBetween('reservation_time', [$startTime, $endTime])
                  ->orWhereRaw("ADDTIME(reservation_time, SEC_TO_TIME(duration * 60)) > ?", [$startTime]);
            })
            ->exists();

        if ($existingReservation) {
            return response()->json([
                'message' => 'Cette table est déjà réservée pour ce créneau.',
            ], 422);
        }

        $reservation = Reservation::create($validated);
        $reservation->load('table');

        return response()->json([
            'message' => 'Réservation créée avec succès.',
            'data' => $reservation,
        ], 201);
    }

    /**
     * Display the specified reservation.
     */
    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->load('table');

        return response()->json($reservation);
    }

    /**
     * Update the specified reservation.
     */
    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        if (in_array($reservation->status, ['completed', 'cancelled', 'no_show'])) {
            return response()->json([
                'message' => 'Impossible de modifier cette réservation.',
            ], 422);
        }

        $validated = $request->validate([
            'table_id' => 'required|exists:restaurant_tables,id',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:20|regex:/^[+]?[0-9\s\-()]+$/',
            'customer_email' => 'nullable|email|max:150',
            'guests_count' => 'required|integer|min:1|max:20',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|date_format:H:i',
            'duration' => 'integer|min:30|max:480',
            'notes' => 'nullable|string|max:500',
        ]);

        $existingReservation = Reservation::where('table_id', $validated['table_id'])
            ->where('reservation_date', $validated['reservation_date'])
            ->where('id', '!=', $reservation->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($validated) {
                $duration = $validated['duration'] ?? 120;
                $startTime = $validated['reservation_time'];
                $endTime = date('H:i', strtotime($startTime) + ($duration * 60));

                $q->whereBetween('reservation_time', [$startTime, $endTime])
                  ->orWhereRaw("ADDTIME(reservation_time, SEC_TO_TIME(duration * 60)) > ?", [$startTime]);
            })
            ->exists();

        if ($existingReservation) {
            return response()->json([
                'message' => 'Cette table est déjà réservée pour ce créneau.',
            ], 422);
        }

        $reservation->update($validated);
        $reservation->load('table');

        return response()->json([
            'message' => 'Réservation mise à jour avec succès.',
            'data' => $reservation,
        ]);
    }

    /**
     * Update reservation status.
     */
    public function updateStatus(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed,no_show',
        ]);

        $reservation->update(['status' => $validated['status']]);

        if ($validated['status'] === 'confirmed') {
            $reservation->table->update(['status' => 'reserved']);
        } elseif (in_array($validated['status'], ['cancelled', 'no_show'])) {
            $hasOtherReservations = Reservation::where('table_id', $reservation->table_id)
                ->where('id', '!=', $reservation->id)
                ->where('reservation_date', $reservation->reservation_date)
                ->where('status', 'confirmed')
                ->exists();

            if (!$hasOtherReservations) {
                $reservation->table->update(['status' => 'available']);
            }
        }

        return response()->json([
            'message' => 'Statut de la réservation mis à jour.',
            'data' => $reservation->fresh('table'),
        ]);
    }

    /**
     * Get today's reservations.
     */
    public function today(): JsonResponse
    {
        $reservations = Reservation::with('table')
            ->whereDate('reservation_date', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('reservation_time')
            ->get();

        return response()->json($reservations);
    }

    /**
     * Remove the specified reservation.
     */
    public function destroy(Reservation $reservation): JsonResponse
    {
        $reservation->delete();

        return response()->json([
            'message' => 'Réservation supprimée avec succès.',
        ]);
    }
}

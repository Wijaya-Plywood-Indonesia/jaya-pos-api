<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ReadsDeviceData;
use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use ReadsDeviceData;

    // Menerima transaksi yang sudah dibayar dari tablet kasir.
    // Idempoten: ID transaksi dibuat di tablet, jadi kiriman ulang (misalnya setelah
    // sinyal putus) tidak membuat transaksi dobel.
    public function store(Request $request): JsonResponse
    {
        $cafeId = $this->cafeIdOrFail($request);

        $data = $request->validate([
            'id' => ['required', 'uuid'],
            'order_number' => ['required', 'string', 'max:40'],
            'outlet_id' => ['nullable', 'integer'],
            'shift_id' => ['nullable', 'uuid'],
            'cashier_id' => ['nullable', 'integer'],
            'type' => ['required', Rule::in(['dine_in', 'take_away'])],
            'dining_table_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['paid'])],

            'subtotal' => ['required', 'integer', 'min:0'],
            'discount_total' => ['required', 'integer', 'min:0'],
            'service_amount' => ['required', 'integer', 'min:0'],
            'tax_amount' => ['required', 'integer', 'min:0'],
            'rounding' => ['required', 'integer'],
            'total' => ['required', 'integer', 'min:0'],

            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.options_total' => ['nullable', 'integer', 'min:0'],
            'items.*.line_total' => ['required', 'integer', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            'items.*.options' => ['nullable', 'array', 'max:50'],
            'items.*.options.*.option_id' => ['nullable', 'integer'],
            'items.*.options.*.option_name' => ['required', 'string', 'max:255'],
            'items.*.options.*.extra_price' => ['nullable', 'integer', 'min:0'],

            'payment.method' => ['required', Rule::in(['cash', 'qris', 'card'])],
            'payment.amount' => ['required', 'integer', 'min:0'],
            'payment.cash_received' => ['nullable', 'integer', 'min:0'],
            'payment.change_amount' => ['nullable', 'integer', 'min:0'],

            'ordered_at' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
        ]);

        // Sudah pernah diterima?
        $existing = Order::withoutGlobalScopes()->find($data['id']);
        if ($existing) {
            return $this->alreadyReceived($existing, $cafeId);
        }

        // Shift harus sudah ada di server (tablet mengirim shift lebih dulu)
        $shiftId = $data['shift_id'] ?? null;
        if ($shiftId !== null && ! Shift::withoutGlobalScopes()->where('id', $shiftId)->where('cafe_id', $cafeId)->exists()) {
            return response()->json(['message' => 'Shift transaksi ini belum ada di server.', 'code' => 'shift_missing'], 422);
        }

        $items = $data['items'];

        // Tautan ke menu dan opsi hanya dipakai bila milik cafe ini.
        // Nama dan harga tetap tersimpan di baris transaksi apa pun yang terjadi.
        $productIds = collect($items)->pluck('product_id')->filter()->unique()->values();
        $validProducts = $productIds->isEmpty() ? [] : Product::withoutGlobalScopes()
            ->where('cafe_id', $cafeId)
            ->whereIn('id', $productIds)
            ->pluck('id')->all();

        $optionIds = collect($items)
            ->flatMap(fn ($i) => collect($i['options'] ?? [])->pluck('option_id'))
            ->filter()->unique()->values();
        $validOptions = $optionIds->isEmpty() ? [] : Option::whereIn('id', $optionIds)
            ->whereIn('option_group_id', OptionGroup::withoutGlobalScopes()->where('cafe_id', $cafeId)->select('id'))
            ->pluck('id')->all();

        // Nomor struk dibuat di tablet. Bila kebetulan sudah dipakai transaksi lain, beri akhiran.
        $number = $data['order_number'];
        if (Order::withoutGlobalScopes()->where('cafe_id', $cafeId)->where('order_number', $number)->exists()) {
            $number = substr($number, 0, 33) . '-' . strtoupper(substr($data['id'], 0, 6));
        }

        // Transaksi yang sudah terjadi tidak boleh ditolak karena selisih hitungan,
        // jadi hanya dicatat di log untuk diperiksa.
        $expectedTotal = $data['subtotal'] - $data['discount_total'] + $data['service_amount']
            + $data['tax_amount'] + $data['rounding'];
        if ($expectedTotal !== (int) $data['total'] || collect($items)->sum('line_total') !== (int) $data['subtotal']) {
            Log::warning('Total transaksi dari tablet tidak konsisten.', [
                'order_id' => $data['id'],
                'cafe_id' => $cafeId,
            ]);
        }

        $pay = $data['payment'];
        $orderedAt = $this->deviceTime($data['ordered_at']);
        $paidAt = $this->deviceTime($data['paid_at'] ?? null) ?? $orderedAt;

        try {
            $order = DB::transaction(function () use ($data, $items, $pay, $cafeId, $number, $shiftId, $orderedAt, $paidAt, $request, $validProducts, $validOptions) {
                $order = Order::create([
                    'id' => $data['id'],
                    'cafe_id' => $cafeId,
                    'outlet_id' => $this->ownedId(Outlet::class, $data['outlet_id'] ?? null, $cafeId),
                    'shift_id' => $shiftId,
                    'cashier_id' => $this->ownedId(User::class, $data['cashier_id'] ?? null, $cafeId) ?? $request->user()->id,
                    'dining_table_id' => $this->ownedId(DiningTable::class, $data['dining_table_id'] ?? null, $cafeId),
                    'order_number' => $number,
                    'type' => $data['type'],
                    'status' => 'paid',
                    'customer_name' => $data['customer_name'] ?? null,
                    'subtotal' => $data['subtotal'],
                    'discount_total' => $data['discount_total'],
                    'service_amount' => $data['service_amount'],
                    'tax_amount' => $data['tax_amount'],
                    'rounding' => $data['rounding'],
                    'total' => $data['total'],
                    'ordered_at' => $orderedAt,
                    'paid_at' => $paidAt,
                    'synced_at' => now(),
                ]);

                foreach ($items as $row) {
                    $item = $order->items()->create([
                        'product_id' => in_array($row['product_id'] ?? null, $validProducts, true) ? $row['product_id'] : null,
                        'product_name' => $row['product_name'],
                        'unit_price' => $row['unit_price'],
                        'quantity' => $row['quantity'],
                        'options_total' => $row['options_total'] ?? 0,
                        'line_total' => $row['line_total'],
                        'note' => $row['note'] ?? null,
                    ]);

                    foreach ($row['options'] ?? [] as $opt) {
                        $item->options()->create([
                            'option_id' => in_array($opt['option_id'] ?? null, $validOptions, true) ? $opt['option_id'] : null,
                            'option_name' => $opt['option_name'],
                            'extra_price' => $opt['extra_price'] ?? 0,
                        ]);
                    }
                }

                $order->payments()->create([
                    'method' => $pay['method'],
                    'amount' => $pay['amount'],
                    'cash_received' => $pay['cash_received'] ?? null,
                    'change_amount' => $pay['change_amount'] ?? 0,
                    'paid_at' => $paidAt,
                ]);

                return $order;
            });
        } catch (QueryException $e) {
            // Dua kiriman yang sama tiba bersamaan: yang kedua dianggap sudah diterima
            $dup = Order::withoutGlobalScopes()->find($data['id']);
            if ($dup) {
                return $this->alreadyReceived($dup, $cafeId);
            }

            throw $e;
        }

        return response()->json([
            'status' => 'created',
            'id' => $order->id,
            'order_number' => $order->order_number,
        ], 201);
    }

    private function alreadyReceived(Order $order, int $cafeId): JsonResponse
    {
        if ((int) $order->cafe_id !== $cafeId) {
            return response()->json(['message' => 'ID transaksi sudah dipakai.'], 409);
        }

        return response()->json([
            'status' => 'duplicate',
            'id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }
}
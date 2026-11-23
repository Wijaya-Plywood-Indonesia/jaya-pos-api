<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ReadsDeviceData;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ReadsDeviceData;

    // Ringkasan penjualan untuk layar Laporan di aplikasi. Hanya owner atau manajer.
    public function summary(Request $request, ReportService $reports): JsonResponse
    {
        $cafeId = $this->cafeIdOrFail($request);

        if (! $request->user()->hasAnyRole(['owner', 'manager'])) {
            return response()->json(['message' => 'Laporan hanya untuk owner atau manajer.'], 403);
        }

        $data = $request->validate([
            'period' => ['nullable', 'in:day,yesterday,week,month,lastmonth'],
        ]);

        return response()->json($reports->build($cafeId, $data['period'] ?? 'day'));
    }
}

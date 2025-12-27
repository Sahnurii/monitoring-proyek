<?php

namespace App\Http\Controllers\Procurement;

use Illuminate\Http\Request;
use App\Models\MaterialRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MaterialRequestApprovalController extends Controller
{
    public function approve(MaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'submitted') {
            return back()->with('error', 'Permintaan tidak dapat disetujui.');
        }

        $materialRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Permintaan material disetujui.');
    }

    public function reject(MaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'submitted') {
            return back()->with('error', 'Permintaan tidak dapat ditolak.');
        }

        $materialRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Permintaan material ditolak.');
    }
}

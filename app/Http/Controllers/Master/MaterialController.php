<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $materialsQuery = Material::query()->with('unit');

        if ($search = trim((string) $request->input('search'))) {
            $materialsQuery->where(function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('unit_id')) {
            $materialsQuery->where('unit_id', $request->input('unit_id'));
        }

        $perPage = $request->integer('per_page', 10) ?: 10;
        $perPage = max(min($perPage, 100), 1);

        $materials = $materialsQuery
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $units = Unit::orderBy('name')->get();

        return view('master.materials.index', [
            'materials' => $materials,
            'units' => $units,
            'title' => 'Master Material',
            'user' => Auth::user(),
        ]);
    }

    public function create()
    {
        $units = Unit::orderBy('name')->get();

        return view('master.materials.create', [
            'units' => $units,
            'title' => 'Tambah Material',
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateMaterial($request);

        $validated['min_stock'] = $validated['min_stock'] ?? 0;
        $validated['unit_price'] = $this->normalizeMoneyInput($validated['unit_price'] ?? 0);

        $material = Material::create($validated);

        return redirect()
            ->route('materials.show', $material)
            ->with('success', 'Material berhasil ditambahkan.');
    }

    public function show(Material $material)
    {
        $material->load('unit');

        return view('master.materials.show', [
            'material' => $material,
            'title' => 'Detail Material',
            'user' => Auth::user(),
        ]);
    }

    public function edit(Material $material)
    {
        $units = Unit::orderBy('name')->get();

        return view('master.materials.edit', [
            'material' => $material,
            'units' => $units,
            'title' => 'Ubah Material',
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request, Material $material)
    {
        $validated = $this->validateMaterial($request, $material);

        $validated['min_stock'] = $validated['min_stock'] ?? 0;
        $validated['unit_price'] = $this->normalizeMoneyInput($validated['unit_price'] ?? $material->unit_price);

        $material->update($validated);

        return redirect()
            ->route('materials.show', $material)
            ->with('success', 'Material berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        $material->delete();

        return redirect()
            ->route('materials.index')
            ->with('success', 'Material berhasil dihapus.');
    }

    protected function validateMaterial(Request $request, ?Material $material = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_id' => ['required', 'exists:units,id'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function normalizeMoneyInput(float|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}

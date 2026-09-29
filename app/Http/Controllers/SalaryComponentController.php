<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryComponentController extends Controller
{
    public const TYPES = [
        'late_deduction' => 'Potongan keterlambatan (per menit)',
        'absence_deduction' => 'Potongan absen (per hari)',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'karyawan_id' => ['nullable', 'integer', 'exists:karyawan,id'],
        ]);

        $employees = Employee::with('user')->where('aktif', true)->orderBy('kode_karyawan')->get();
        $selectedId = $filters['karyawan_id'] ?? $employees->first()?->id;
        $selected = $selectedId ? Employee::with('user')->find($selectedId) : null;

        $components = $selected
            ? SalaryComponent::where('karyawan_id', $selected->id)->orderBy('jenis')->get()
            : collect();

        return view('master-data.salary-components', [
            'employees' => $employees,
            'selected' => $selected,
            'components' => $components,
            'types' => self::TYPES,
            'activeTab' => 'salary-components',
            'title' => 'Master Data - Komponen Gaji',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'karyawan_id' => ['required', 'integer', 'exists:karyawan,id'],
            'jenis' => ['required', Rule::in(array_keys(self::TYPES)), Rule::unique('komponen_gaji', 'jenis')->where(fn ($query) => $query->where('karyawan_id', $request->input('karyawan_id')))],
            'nama' => ['required', 'string', 'max:100'],
            'nominal' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'aktif' => ['nullable', 'boolean'],
        ]);
        $data['aktif'] = $request->boolean('aktif');

        SalaryComponent::create($data);

        return redirect()->route('salary-components.index', ['karyawan_id' => $data['karyawan_id']])
            ->with('success', 'Tarif berhasil ditambahkan.');
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $data = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(self::TYPES)), Rule::unique('komponen_gaji', 'jenis')->where(fn ($query) => $query->where('karyawan_id', $salaryComponent->karyawan_id))->ignore($salaryComponent->id)],
            'nama' => ['required', 'string', 'max:100'],
            'nominal' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'aktif' => ['nullable', 'boolean'],
        ]);
        $data['aktif'] = $request->boolean('aktif');

        $salaryComponent->update($data);

        return redirect()->route('salary-components.index', ['karyawan_id' => $salaryComponent->karyawan_id])
            ->with('success', 'Tarif berhasil diperbarui.');
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        $employeeId = $salaryComponent->karyawan_id;
        $salaryComponent->delete();

        return redirect()->route('salary-components.index', ['karyawan_id' => $employeeId])
            ->with('success', 'Tarif berhasil dihapus. Perhitungan gaji kembali memakai tarif bawaan.');
    }
}

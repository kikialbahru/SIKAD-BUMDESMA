<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::withTrashed()->with('user', 'position', 'workSchedule')
            ->when($request->input('search'), fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('kode_karyawan', 'like', '%'.$search.'%')->orWhere('nama', 'like', '%'.$search.'%')->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
            }))
            ->when($request->filled('jabatan_id'), fn ($query) => $query->where('jabatan_id', $request->integer('jabatan_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('aktif', $request->boolean('status')))
            ->latest()
            ->paginate(15);

        return view('employees.index', ['employees' => $employees, 'positions' => Position::orderBy('nama')->get(), 'filters' => $request->only('search', 'status', 'jabatan_id')]);
    }

    public function create()
    {
        return view('employees.form', ['employee' => new Employee, 'positions' => Position::orderBy('nama')->get(), 'schedules' => WorkSchedule::where('aktif', true)->orderBy('nama')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode_karyawan' => ['nullable', 'string', 'max:30', Rule::unique('karyawan', 'kode_karyawan')],
            'jabatan_id' => ['required', 'exists:jabatan,id'],
            'jadwal_kerja_id' => ['nullable', 'exists:jadwal_kerja,id'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'tanggal_masuk' => ['required', 'date'],
            'gaji_pokok' => ['nullable', 'numeric', 'min:0'],
            'tunjangan_kehadiran' => ['nullable', 'numeric', 'min:0'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'aktif' => ['sometimes', 'boolean'],
            'create_account' => ['nullable', 'boolean'],
            'hapus_foto' => ['nullable', 'boolean'],
            'email' => [Rule::requiredIf($request->boolean('create_account')), 'nullable', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => [Rule::requiredIf($request->boolean('create_account')), 'nullable', 'string', 'min:8', 'confirmed'],
        ]);
        DB::transaction(function () use ($data, $request) {
            $user = null;
            if ($request->boolean('create_account')) {
                $user = User::create(['name' => $data['nama'], 'email' => $data['email'], 'role' => 'employee', 'password' => Hash::make($data['password'])]);
            }
            $employee = Employee::create($this->employeeData($data) + ['pengguna_id' => $user?->id, 'nama' => $data['nama'], 'email' => $user?->email]);
            $this->storePhoto($request, $employee);
        });

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', ['employee' => $employee->load('user'), 'positions' => Position::orderBy('nama')->get(), 'schedules' => WorkSchedule::where('aktif', true)->orderBy('nama')->get()]);
    }

    public function show(Employee $employee)
    {
        return view('employees.show', ['employee' => $employee->load('user', 'position', 'workSchedule')]);
    }

    public function myProfile(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Data karyawan belum terhubung ke akun ini.');

        return view('employees.show', ['employee' => $employee->load('user', 'position', 'workSchedule')]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validated($request, $employee);
        $data['aktif'] = $request->boolean('aktif');
        if (empty($data['kode_karyawan'])) {
            $data['kode_karyawan'] = $employee->kode_karyawan;
        }
        $accountData = null;
        if (! $employee->user && $request->boolean('create_account')) {
            $accountData = $request->validate([
                'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
            $data['password'] = null;
        }
        DB::transaction(function () use ($data, $employee, $accountData) {
            if ($accountData) {
                $user = User::create(['name' => $data['nama'], 'email' => $accountData['email'], 'role' => 'employee', 'password' => Hash::make($accountData['password'])]);
                $employee->update(['pengguna_id' => $user->id]);
                $data['email'] = $accountData['email'];
            }
            $employee->update($this->employeeData($data) + ['nama' => $data['nama'], 'email' => $data['email'] ?? null]);
            if ($employee->user) {
                $employee->user->update(['name' => $data['nama']]);
                if (! empty($data['email'])) {
                    $employee->user->update(['email' => $data['email']]);
                }
                if (! empty($data['password'])) {
                    $employee->user->update(['password' => Hash::make($data['password'])]);
                }
            }
        });
        if ($request->boolean('hapus_foto') && ! $request->hasFile('foto') && $employee->foto) {
            Storage::disk('public')->delete($employee->foto);
            $employee->update(['foto' => null]);
        }
        $this->storePhoto($request, $employee);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function storeAccount(Request $request, Employee $employee)
    {
        abort_if($employee->pengguna_id, 422, 'Karyawan sudah memiliki akun login.');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($data, $employee) {
            $user = User::create([
                'name' => $employee->nama ?: $employee->display_name,
                'email' => $data['email'],
                'role' => 'employee',
                'password' => Hash::make($data['password']),
            ]);
            $employee->update(['pengguna_id' => $user->id, 'email' => $data['email']]);
        });

        return back()->with('success', 'Akun login berhasil dibuat dan terhubung ke '.$employee->display_name.'.');
    }

    public function destroy(Employee $employee)
    {
        $employee->update(['aktif' => false]);
        $employee->delete();

        return back()->with('success', 'Karyawan berhasil dinonaktifkan.');
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($employee?->pengguna_id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'create_account' => ['nullable', 'boolean'],
            'hapus_foto' => ['nullable', 'boolean'],
            'kode_karyawan' => [$employee ? 'sometimes' : 'nullable', 'string', 'max:30', Rule::unique('karyawan', 'kode_karyawan')->ignore($employee?->id)],
            'jabatan_id' => ['required', 'exists:jabatan,id'],
            'jadwal_kerja_id' => ['nullable', 'exists:jadwal_kerja,id'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'tanggal_masuk' => ['required', 'date'],
            'gaji_pokok' => ['nullable', 'numeric', 'min:0'],
            'tunjangan_kehadiran' => ['nullable', 'numeric', 'min:0'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'aktif' => ['sometimes', 'boolean'],
        ]);
    }

    private function employeeData(array $data): array
    {
        $employeeCode = $data['kode_karyawan'] ?? null;
        if (! $employeeCode) {
            $nextId = (Employee::withTrashed()->max('id') ?? 0) + 1;
            do {
                $employeeCode = 'KRY-'.str_pad((string) $nextId++, 3, '0', STR_PAD_LEFT);
            } while (Employee::withTrashed()->where('kode_karyawan', $employeeCode)->exists());
        }

        return collect($data)->only(['jabatan_id', 'jadwal_kerja_id', 'telepon', 'tanggal_masuk', 'gaji_pokok', 'tunjangan_kehadiran', 'aktif'])->toArray() + ['kode_karyawan' => $employeeCode, 'aktif' => true];
    }

    private function storePhoto(Request $request, Employee $employee): void
    {
        if (! $request->hasFile('foto')) {
            return;
        }
        if ($employee->foto) {
            Storage::disk('public')->delete($employee->foto);
        }
        $employee->update(['foto' => $request->file('foto')->store('employee-photos', 'public')]);
    }
}

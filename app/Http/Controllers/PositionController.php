<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::withCount(['employees as employees_count' => fn ($query) => $query->withTrashed()])
            ->orderBy('nama')
            ->get();

        return view('master-data.positions', [
            'positions' => $positions,
            'activeTab' => 'positions',
            'title' => 'Master Data - Jabatan',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:jabatan,nama'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ]);

        Position::create($data);

        return back()->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Position $position)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:jabatan,nama,'.$position->id],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ]);

        $position->update($data);

        return back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Position $position)
    {
        if ($position->employees()->withTrashed()->exists()) {
            return back()->with('error', 'Jabatan "'.$position->nama.'" masih dipakai karyawan dan tidak dapat dihapus. Pindahkan dulu karyawannya ke jabatan lain.');
        }

        $position->delete();

        return back()->with('success', 'Jabatan berhasil dihapus.');
    }
}

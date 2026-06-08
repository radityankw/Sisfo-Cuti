<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DataKaryawanController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'HRD') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = User::with('manager');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $karyawan = $query->orderBy('nama')->paginate(10);
        
        $managers = User::whereIn('role', ['Manager', 'HRD'])
                        ->select('nik', 'nama')
                        ->orderBy('nama')
                        ->get();

        return response()->json([
            'karyawan' => $karyawan,
            'managers' => $managers
        ]);
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'HRD') return response()->json(['message' => 'Forbidden'], 403);

        $validated = $request->validate([
            'nik' => 'required|string|max:20|unique:users,nik',
            'nama' => 'required|string|max:255',
            'tglMasuk' => 'required|date',
            'departemen' => 'required|in:HRD,Keuangan,Produksi,Pemasaran',
            'role' => 'required|in:Staff,Manager,HRD',
            'manager_nik' => 'nullable|exists:users,nik',
            'password' => 'required|string|min:6',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return response()->json(['message' => 'Karyawan berhasil ditambahkan.']);
    }

    public function update(Request $request, $nik)
    {
        if (Auth::user()->role !== 'HRD') return response()->json(['message' => 'Forbidden'], 403);

        $user = User::findOrFail($nik);

        $validated = $request->validate([
            'nik' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($user->nik, 'nik')],
            'nama' => 'required|string|max:255',
            'tglMasuk' => 'required|date',
            'departemen' => 'required|in:HRD,Keuangan,Produksi,Pemasaran',
            'role' => 'required|in:Staff,Manager,HRD',
            'manager_nik' => 'nullable|exists:users,nik',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json(['message' => 'Data karyawan diperbarui.']);
    }

    public function destroy($nik)
    {
        if (Auth::user()->role !== 'HRD') return response()->json(['message' => 'Forbidden'], 403);
        
        if ($nik === Auth::user()->nik) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 400);
        }

        User::destroy($nik);
        return response()->json(['message' => 'Karyawan berhasil dihapus.']);
    }

    public function structure()
    {
        if (Auth::user()->role !== 'HRD') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $roots = User::whereNull('manager_nik')
            ->with([
                'bawahan.bawahan.bawahan.bawahan.bawahan.bawahan.bawahan' 
            ])
            ->orderBy('role', 'desc') 
            ->get();

        return response()->json([
            'roots' => $roots 
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function index()
    {
        $title = 'Backup & Restore Data';
        
        // Get database statistics
        $tables = $this->getTableStats();
        $totalRecords = array_sum(array_column($tables, 'count'));
        
        return view('backup.index', compact('title', 'tables', 'totalRecords'));
    }
    
    private function getTableStats()
    {
        $tables = [];
        $tableNames = [
            'users' => 'Pegawai',
            'mapping_shifts' => 'Data Absensi',
            'cutis' => 'Data Cuti',
            'kasbons' => 'Data Kasbon',
            'reimbursements' => 'Data Reimbursement',
            'payrolls' => 'Data Payroll',
            'lokasis' => 'Lokasi Kantor',
            'shifts' => 'Shift Kerja',
            'beritas' => 'Berita & Informasi',
            'laporan_kinerjas' => 'Data Kinerja',
        ];
        
        foreach ($tableNames as $table => $label) {
            if (Schema::hasTable($table)) {
                $tables[] = [
                    'name' => $table,
                    'label' => $label,
                    'count' => DB::table($table)->count()
                ];
            }
        }
        
        return $tables;
    }
    
    public function export()
    {
        try {
            $data = [];
            $tables = [
                'users', 'mapping_shifts', 'cutis', 'kasbons', 
                'reimbursements', 'payrolls', 'lokasis', 'shifts', 
                'beritas', 'laporan_kinerjas', 'jenis_kinerjas',
                'target_kinerjas', 'target_kinerja_teams'
            ];
            
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $data[$table] = DB::table($table)->get()->toArray();
                }
            }
            
            $data['export_date'] = now()->format('Y-m-d H:i:s');
            $data['export_by'] = auth()->user()->name ?? 'System';
            
            $json = json_encode($data, JSON_PRETTY_PRINT);
            $filename = 'backup_' . date('Y-m-d_His') . '.json';
            
            return response($json)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function import(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:json'
        ]);
        
        try {
            $file = $request->file('backup_file');
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);
            
            if (!$data) {
                return response()->json(['error' => 'File JSON tidak valid'], 400);
            }
            
            DB::beginTransaction();
            
            $imported = [];
            $tables = [
                'lokasis', 'shifts', 'jenis_kinerjas', 'users', 
                'mapping_shifts', 'cutis', 'kasbons', 'reimbursements', 
                'payrolls', 'beritas', 'laporan_kinerjas',
                'target_kinerjas', 'target_kinerja_teams'
            ];
            
            foreach ($tables as $table) {
                if (isset($data[$table]) && Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    
                    foreach ($data[$table] as $row) {
                        DB::table($table)->insert((array)$row);
                    }
                    
                    $imported[$table] = count($data[$table]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true, 
                'message' => 'Data berhasil diimport',
                'imported' => $imported
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function deleteAllData(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:HAPUS SEMUA DATA'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Tables to clear (in order to avoid foreign key issues)
            $tables = [
                'laporan_kinerjas',
                'target_kinerja_teams',
                'target_kinerjas',
                'reimbursements_items',
                'reimbursements',
                'kasbons',
                'payrolls',
                'mapping_shifts',
                'cutis',
                'beritas',
            ];
            
            $deleted = [];
            
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $deleted[$table] = $count;
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Semua data berhasil dihapus',
                'deleted' => $deleted
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function deleteTable(Request $request)
    {
        $request->validate([
            'table' => 'required|string'
        ]);
        
        $table = $request->table;
        
        // Allowed tables
        $allowedTables = [
            'mapping_shifts', 'cutis', 'kasbons', 'reimbursements',
            'payrolls', 'beritas', 'laporan_kinerjas'
        ];
        
        if (!in_array($table, $allowedTables)) {
            return response()->json(['error' => 'Tabel tidak diizinkan untuk dihapus'], 403);
        }
        
        try {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                
                return response()->json([
                    'success' => true,
                    'message' => "Data tabel berhasil dihapus ({$count} records)"
                ]);
            }
            
            return response()->json(['error' => 'Tabel tidak ditemukan'], 404);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

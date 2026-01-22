<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LaporanKinerja;
use App\Models\JenisKinerja;

class FixNilaiKinerja extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:nilai-kinerja {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix incorrect nilai values in laporan_kinerjas table based on jenis_kinerja bobot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info($dryRun ? '=== DRY RUN MODE ===' : '=== FIXING NILAI KINERJA ===');
        
        // Get all laporan with their jenis_kinerja
        $laporans = LaporanKinerja::with('jenis')->get();
        
        $fixedCount = 0;
        $affectedUsers = [];
        
        foreach ($laporans as $laporan) {
            if (!$laporan->jenis) {
                $this->warn("Laporan ID {$laporan->id} has no jenis_kinerja");
                continue;
            }
            
            $expectedNilai = $laporan->jenis->bobot;
            $actualNilai = $laporan->nilai;
            
            // Check if nilai doesn't match bobot
            if ($actualNilai != $expectedNilai) {
                $this->line("ID: {$laporan->id} | User: {$laporan->user_id} | Jenis: {$laporan->jenis->nama}");
                $this->line("  Current nilai: {$actualNilai} | Should be: {$expectedNilai}");
                
                if (!$dryRun) {
                    $laporan->update(['nilai' => $expectedNilai]);
                    $this->info("  -> Fixed!");
                }
                
                $fixedCount++;
                $affectedUsers[$laporan->user_id] = true;
            }
        }
        
        $this->newLine();
        $this->info("Found {$fixedCount} incorrect nilai values");
        $this->info("Affected users: " . count($affectedUsers));
        
        // Recalculate penilaian_berjalan for affected users
        if (!$dryRun && count($affectedUsers) > 0) {
            $this->info("Recalculating penilaian_berjalan for affected users...");
            
            foreach (array_keys($affectedUsers) as $userId) {
                $this->recalculateUserPoints($userId);
                $this->line("  User {$userId} recalculated");
            }
            
            $this->info("Done!");
        }
        
        return Command::SUCCESS;
    }
    
    private function recalculateUserPoints($userId)
    {
        $points = LaporanKinerja::where('user_id', $userId)
            ->orderBy('tanggal', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();
        
        $runningTotal = 0;
        foreach ($points as $point) {
            $runningTotal += $point->nilai;
            $point->update(['penilaian_berjalan' => $runningTotal]);
        }
    }
}

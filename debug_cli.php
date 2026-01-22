<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$start = date('Y-m-01');
$end = date('Y-m-t');

$output = "--- DATA START ---\n";
$data = App\Models\LaporanKinerja::with(['user', 'jenis'])
    ->whereBetween('tanggal', [$start, $end])
    ->get();

foreach($data as $d) {
    $output .= "User: " . ($d->user->name ?? 'Unknown') . " (" . $d->user_id . ") | ";
    $output .= "Jenis: " . ($d->jenis->nama ?? 'Unknown') . " | ";
    $output .= "Nilai: " . $d->nilai . "\n";
}
$output .= "--- DATA END ---\n";
file_put_contents('debug_output.txt', $output);

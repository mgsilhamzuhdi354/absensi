<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokumenPengembalianAset extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'inventory_return_documents';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_surat' => 'date',
        'employee_signed_at' => 'datetime',
        'it_receiver_signed_at' => 'datetime',
        'known_signed_at' => 'datetime',
    ];

    public function clearance()
    {
        return $this->belongsTo(PenyelesaianAsetPegawaiKeluar::class, 'pegawai_keluar_asset_clearance_id');
    }

    public function returnTransaction()
    {
        return $this->belongsTo(InventoryStockTransaction::class, 'return_inventory_stock_transaction_id')->withTrashed();
    }

    public function originalTransaction()
    {
        return $this->belongsTo(InventoryStockTransaction::class, 'original_inventory_stock_transaction_id')->withTrashed();
    }

    public function pegawaiKeluar()
    {
        return $this->belongsTo(PegawaiKeluar::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function itReceiver()
    {
        return $this->belongsTo(User::class, 'it_receiver_user_id');
    }

    public function knownBy()
    {
        return $this->belongsTo(User::class, 'known_by_user_id');
    }

    public function getIsFullySignedAttribute()
    {
        return $this->employee_signed_at !== null
            && ($this->it_receiver_user_id === null || $this->it_receiver_signed_at !== null)
            && ($this->known_by_user_id === null || $this->known_signed_at !== null);
    }

    public function canUserSignRole(User $user, $role)
    {
        if ($role === 'employee') {
            return (int) $this->employee_user_id === (int) $user->id;
        }

        if ($role === 'it_receiver') {
            return (int) $this->it_receiver_user_id === (int) $user->id;
        }

        if ($role === 'known') {
            return (int) $this->known_by_user_id === (int) $user->id;
        }

        return false;
    }

    public function signatureRoleConfig($role)
    {
        return self::signatureRoles()[$role] ?? null;
    }

    public static function signatureRoles()
    {
        return [
            'employee' => [
                'label' => 'Pihak Pertama / Pegawai',
                'short_label' => 'Pegawai',
                'signed_at' => 'employee_signed_at',
                'user_id' => 'employee_user_id',
                'name' => 'employee_signature_name',
                'image' => 'employee_signature_image',
                'ip' => 'employee_signature_ip',
                'user_agent' => 'employee_signature_user_agent',
            ],
            'it_receiver' => [
                'label' => 'Pihak Kedua / IT',
                'short_label' => 'IT',
                'signed_at' => 'it_receiver_signed_at',
                'user_id' => 'it_receiver_user_id',
                'name' => 'it_receiver_signature_name',
                'image' => 'it_receiver_signature_image',
                'ip' => 'it_receiver_signature_ip',
                'user_agent' => 'it_receiver_signature_user_agent',
            ],
            'known' => [
                'label' => 'Mengetahui / Crewing HRD',
                'short_label' => 'Mengetahui',
                'signed_at' => 'known_signed_at',
                'user_id' => 'known_by_user_id',
                'name' => 'known_signature_name',
                'image' => 'known_signature_image',
                'ip' => 'known_signature_ip',
                'user_agent' => 'known_signature_user_agent',
            ],
        ];
    }
}

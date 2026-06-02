<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBastDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_surat' => 'date',
        'signed_at' => 'datetime',
        'known_signed_at' => 'datetime',
        'first_party_signed_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(InventoryStockTransaction::class, 'inventory_stock_transaction_id')->withTrashed();
    }

    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }

    public function knownBy()
    {
        return $this->belongsTo(User::class, 'known_by_user_id');
    }

    public function firstParty()
    {
        return $this->belongsTo(User::class, 'first_party_user_id');
    }

    public function getIsSignedAttribute()
    {
        return $this->signed_at !== null;
    }

    public function getIsFullySignedAttribute()
    {
        return $this->signed_at !== null
            && ($this->known_by_user_id === null || $this->known_signed_at !== null)
            && ($this->first_party_user_id === null || $this->first_party_signed_at !== null);
    }

    public function isSignedForRole($role)
    {
        $config = $this->signatureRoleConfig($role);

        return $config ? $this->{$config['signed_at']} !== null : false;
    }

    public function canUserSignRole(User $user, $role)
    {
        if ($role === 'receiver') {
            return $this->transaction && (int) $this->transaction->penerima_user_id === (int) $user->id;
        }

        if ($role === 'known') {
            return (int) $this->known_by_user_id === (int) $user->id;
        }

        if ($role === 'first_party') {
            return (int) $this->first_party_user_id === (int) $user->id;
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
            'receiver' => [
                'label' => 'Pihak Kedua / Penerima',
                'short_label' => 'Penerima',
                'signed_at' => 'signed_at',
                'user_id' => 'signed_by_user_id',
                'name' => 'receiver_signature_name',
                'image' => 'receiver_signature_image',
                'ip' => 'signature_ip',
                'user_agent' => 'signature_user_agent',
            ],
            'known' => [
                'label' => 'Mengetahui / HRD',
                'short_label' => 'HRD',
                'signed_at' => 'known_signed_at',
                'user_id' => 'known_by_user_id',
                'name' => 'known_signature_name',
                'image' => 'known_signature_image',
                'ip' => 'known_signature_ip',
                'user_agent' => 'known_signature_user_agent',
            ],
            'first_party' => [
                'label' => 'Pihak Pertama / IT',
                'short_label' => 'IT',
                'signed_at' => 'first_party_signed_at',
                'user_id' => 'first_party_user_id',
                'name' => 'first_party_signature_name',
                'image' => 'first_party_signature_image',
                'ip' => 'first_party_signature_ip',
                'user_agent' => 'first_party_signature_user_agent',
            ],
        ];
    }
}

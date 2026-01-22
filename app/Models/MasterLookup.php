<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterLookup extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];

    // Lookup Types Constants
    const TYPE_EXIT = 'exit_type';           // Jenis Keberhentian
    const TYPE_MEETING = 'meeting_type';     // Jenis Pertemuan
    const TYPE_LOCATION = 'location_type';   // Keterangan Lokasi

    /**
     * Get all active lookups by type
     */
    public static function getByType($type)
    {
        return self::where('type', $type)
                   ->where('active', true)
                   ->orderBy('sort_order', 'ASC')
                   ->orderBy('name', 'ASC')
                   ->get();
    }

    /**
     * Get all lookup types with labels
     */
    public static function getTypes()
    {
        return [
            self::TYPE_EXIT => 'Jenis Keberhentian',
            self::TYPE_MEETING => 'Jenis Pertemuan',
            self::TYPE_LOCATION => 'Keterangan Lokasi',
        ];
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute()
    {
        $types = self::getTypes();
        return $types[$this->type] ?? $this->type;
    }
}

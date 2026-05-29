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

    private const DEFAULT_LOOKUPS = [
        self::TYPE_EXIT => [
            ['name' => 'PHK', 'value' => 'PHK', 'sort_order' => 1],
            ['name' => 'Mengundurkan Diri', 'value' => 'Mengundurkan Diri', 'sort_order' => 2],
            ['name' => 'Meninggal Dunia', 'value' => 'Meninggal Dunia', 'sort_order' => 3],
            ['name' => 'Pensiun', 'value' => 'Pensiun', 'sort_order' => 4],
        ],
        self::TYPE_MEETING => [
            ['name' => 'Pertemuan Offline', 'value' => 'Pertemuan Offline', 'sort_order' => 1],
            ['name' => 'Pertemuan Online', 'value' => 'Pertemuan Online', 'sort_order' => 2],
        ],
        self::TYPE_LOCATION => [
            ['name' => 'Office', 'value' => 'Office', 'sort_order' => 1],
            ['name' => 'Patroli', 'value' => 'Patroli', 'sort_order' => 2],
        ],
    ];

    /**
     * Get all active lookups by type. If the master table has not been seeded yet,
     * return built-in defaults so dependent forms remain usable.
     */
    public static function getByType($type)
    {
        $lookups = self::where('type', $type)
            ->where('active', true)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        if ($lookups->isNotEmpty() || self::where('type', $type)->exists()) {
            return $lookups;
        }

        return self::defaultCollection($type);
    }

    public static function valuesForType($type)
    {
        return self::getByType($type)
            ->pluck('value')
            ->filter()
            ->values()
            ->all();
    }

    public static function defaultItemsForType($type)
    {
        return self::DEFAULT_LOOKUPS[$type] ?? [];
    }

    private static function defaultCollection($type)
    {
        return collect(self::defaultItemsForType($type))->map(function ($item) use ($type) {
            return new self(array_merge($item, [
                'type' => $type,
                'active' => true,
            ]));
        });
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

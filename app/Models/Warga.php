<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Warga extends Model
{
    protected $table = 'wargas';
    protected $fillable = ['user_id','kategori_tarif_id', 'nomor_rumah', 'nama_warga', 'nomor_telepon', 'status_aktif'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

 
    public function kategoriTarif(): BelongsTo
    {
        return $this->belongsTo(KategoriTarif::class, 'kategori_tarif_id');
    }
 
    public function pencatatanMeter(): HasMany
    {
        return $this->hasMany(PencatatanMeter::class, 'warga_id');
    }
 
    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'warga_id');
    }

}

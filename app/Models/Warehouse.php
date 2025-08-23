<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table = 'warehouses';
    protected $primaryKey = 'warehouse_id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['user_id','nama','deskripsi'];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function dryers() { return $this->hasMany(BedDryer::class, 'warehouse_id', 'warehouse_id'); }
}
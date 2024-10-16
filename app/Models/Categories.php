<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;
    protected $table='categories';
    protected $fillable=['id','name','slug','images','position','status','created_at','updated_at'];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    } 
    public function products()
    {
        return $this->belongsToMany(Products::class, 'product_categories', 'id_categories', 'id_product');
    }  
}

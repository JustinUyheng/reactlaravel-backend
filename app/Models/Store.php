<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'description',
        'address',
        'contact_number',
        'operating_hours',
        'store_image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getStoreImageUrlAttribute()
    {
        return $this->store_image 
            ? Storage::url($this->store_image)
            : null;
    }

    protected $appends = ['store_image_url'];
}

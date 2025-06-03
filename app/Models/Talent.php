<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Talent extends Model {
    protected $table ="talents";
    protected $fillable = ['username', 'name', 'email', 'phone'];
    
    public function clients(): HasMany {
        return $this->hasMany(Client::class);
    }
    public function videos(): HasMany {
        return $this->hasMany(Video::class);
    }
}
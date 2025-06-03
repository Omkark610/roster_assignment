<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Client extends Model {
    protected $table ="clients";
    protected $fillable = ['name', 'talent_id'];
    
    public function talent() {
        return $this->belongsTo(Talent::class);
    }
}
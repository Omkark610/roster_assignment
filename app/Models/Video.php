<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Video extends Model {
    protected $table ="videos";
    protected $fillable = ['title', 'url', 'talent_id'];
    
    public function talent() {
        return $this->belongsTo(Talent::class);
    }
}
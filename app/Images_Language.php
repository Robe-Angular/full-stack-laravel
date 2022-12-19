<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Images_Language extends Model
{
    protected $table = 'images_language';
    
    public function image(){
        return $this->belongsTo('App\Image');
    }
}

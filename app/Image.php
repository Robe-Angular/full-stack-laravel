<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $table = 'images';
    
    //Relación de uno a muchos
    public function post(){
        return $this->belongsTo('App\Post');
    }
    
    public function images_language(){
        return $this->hasMany('App\Images_Language');
    }
    
    
}

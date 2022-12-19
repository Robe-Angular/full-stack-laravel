<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Posts_Language extends Model
{
    protected $table = 'posts_language';
    
    public function post(){
        return $this->belongsTo('App\Post');
    }
}

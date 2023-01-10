<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Posts_Language extends Model
{
    protected $table = 'posts_language';
    public $fillable = ['title_language','content_language','published'];
    
    public function post(){
        return $this->belongsTo('App\Post');
    }
}

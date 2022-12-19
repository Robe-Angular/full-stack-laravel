<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';
    public $fillable = ['title','content','image','category_id','published'];
    //Relación de uno a muchos inversa (muchos a uno)
    /*
    public function user(){
        return $this->belongsTo('App\User','user_id');
        
    }
     * */
     
    
    public function category(){
        return $this->belongsTo('App\Category','category_id');
        
    }
    
    public function images(){
        return $this->hasMany('App\Image');
        
    }
    public function image(){
        return $this->belongsTo('App\Image', 'image');
    }
    
    public function posts_language(){
        return $this->hasMany('App\Posts_Language');
    }
}

/*
 * 
 */

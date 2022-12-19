<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Categories_Language extends Model
{
    protected $table = 'categories_language';
    
    public function category(){
        return $this->BelongsTo('App\Category');
    }
            
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
use App\Category;

class PruebasController extends Controller
{
    public function index() {
        
        $animales = ['Perro', 'Gato', 'Tigre'];
        $titulo = 'Animales';
        
        return view('pruebas.index', array(
            'titulo' => $titulo,
            'animales' => $animales
            ));
    }
    
    public function testOrm(){
        /*
        $posts = Post::all();
        foreach ($posts as $post){
            echo "<h1>".$post->title."</h2>";
            echo "<span style='color:gray;'>{$post->user->name} - {$post->category->name}</span>";
            echo "<p>".$post->content."</p>";
        }
        */
        $categories = Category::all();
        foreach ($categories as $category){
            echo "<h2>{$category->name}</h2>";
            foreach ($category->posts as $post){
                echo "<h3>".$post->title."</h3>";
                echo "<span style='color:gray;'>{$post->user->name} - {$post->category->name}</span>";
                echo "<p>".$post->content."</p>";
            }    
            echo "<hr>";
        }
        
        die();
    }
}

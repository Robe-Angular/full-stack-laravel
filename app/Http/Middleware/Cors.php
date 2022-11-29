<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request)
                ->header("Access-Control-Allow-Origin", '*')
                //Métodos que a los que se da acceso
                ->header("Access-Control-Allow-Methods", "GET, POST, OPTIONS, PUT, DELETE")
                //Headers de la petición
                ->header("Access-Control-Allow-Headers", "X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, Authorization")
                ->header("Allow","GET, POST, OPTIONS, PUT, DELETE");                
        
    }
}

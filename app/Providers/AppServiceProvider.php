<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         RateLimiter::for('api', function (Request $request){
            App::setLocale($request->header('locale'));
            return Limit::perMinute(200)->by($request->ip())->response(function () use($request){
                $getBlockedIps=array(Cache::get('blocked_ips'));
                if(in_array($request->ip(), $getBlockedIps)){
                    return response()->json(["message"=>__("messages.error")],500);
                }
                Cache::put('blocked_ips', $request->ip(), now()->day(7));
                return response()->json(["message"=>__("messages.hack_attempt")],429);
            });
        });
    }
}

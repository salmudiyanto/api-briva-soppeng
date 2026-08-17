<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class CheckDatabaseConnection
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
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $path = $request->path();
            if (strpos($path, 'transfer-va/inquiry') !== false) {
                $responseCode = '5002400';
            } elseif (strpos($path, 'transfer-va/payment') !== false) {
                $responseCode = '5002500';
            } else {
                $responseCode = '500000';
            }

            return response()->json([
                'responseCode' => $responseCode,
                'responseMessage' => 'General Error'
            ], 500);
        }

        return $next($request);
    }
}

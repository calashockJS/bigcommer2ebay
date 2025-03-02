<?php

namespace App\Http\Middleware;

use App\Models\AccessToken;
use App\Services\EbaySyncService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class EbayAuthMiddleware
{
    protected $ebayService;

    public function __construct(EbaySyncService $ebaySyncService)
    {
        $this->ebayService = $ebaySyncService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::channel('stderr')->info('now in EbayAuthMiddleware CLASS here in handle()');
        $ebayAccessToken = $this->ebayService->accessToken;
        if($ebayAccessToken == ''){
            Log::channel('stderr')->info('now in EbayAuthMiddleware goingg to call getUpdateAccessToken() in EbayAuthMiddleware CLASS');
            $ebayAccessToken = $this->ebayService->getUpdateAccessTokenService();
            Log::channel('stderr')->info('now in EbayAuthMiddleware CLASS just after $this->ebayService->getUpdateAccessTokenService()');
            if (empty($ebayAccessToken)) {
                Log::channel('stderr')->info('now in EbayAuthMiddleware goingg to redirect /api/ebay/auth url to get token');
                return redirect('/api/ebay/auth'); // Redirect if no token
            }
        }
        return $next($request);
    }
}

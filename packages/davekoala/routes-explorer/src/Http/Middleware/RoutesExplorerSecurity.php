<?php

namespace DaveKoala\RoutesExplorer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Security middleware for Routes Explorer
 * 
 * Ensures the tool only runs in appropriate environments
 * and with proper debug settings enabled.
 */
class RoutesExplorerSecurity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get security configuration
        $allowedEnvironments = config('routes-explorer.security.allowed_environments', ['local', 'development', 'testing']);
        $requireDebug = config('routes-explorer.security.require_debug', true);
        
        // Check environment
        $currentEnv = app()->environment();
        if (!in_array($currentEnv, $allowedEnvironments)) {
            return $this->securityDeniedResponse(
                "Routes Explorer is disabled in '{$currentEnv}' environment. Only allowed in: " . implode(', ', $allowedEnvironments)
            );
        }
        
        // Check debug mode if required
        if ($requireDebug && !config('app.debug')) {
            return $this->securityDeniedResponse(
                'Routes Explorer requires debug mode to be enabled for security reasons.'
            );
        }
        
        // Additional security headers for development tools
        $response = $next($request);
        
        if ($response instanceof Response) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        }
        
        return $response;
    }
    
    /**
     * Return a security denied response
     */
    private function securityDeniedResponse(string $message): Response
    {
        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'Access Denied',
                'message' => $message,
                'security' => true
            ], 403);
        }
        
        return response($this->getSecurityDeniedHtml($message), 403)
            ->header('Content-Type', 'text/html');
    }
    
    /**
     * Get HTML for security denied page
     */
    private function getSecurityDeniedHtml(string $message): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>Access Denied - Routes Explorer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .error { color: #d32f2f; font-size: 18px; margin-bottom: 20px; }
        .message { color: #666; line-height: 1.6; }
        .title { color: #333; margin-bottom: 20px; }
        .security-icon { font-size: 48px; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="security-icon">🔒</div>
        <h1 class="title">Access Denied</h1>
        <div class="error">Routes Explorer Security Restriction</div>
        <div class="message">' . htmlspecialchars($message) . '</div>
        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
        <small style="color: #999;">This is a development tool and should only be accessible in appropriate environments.</small>
    </div>
</body>
</html>';
    }
}
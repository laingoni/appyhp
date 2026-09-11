<?php

namespace Alliswell\Appyhp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudioAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('appyhp.allowed_ips', ['127.0.0.1', '::1']);
        $allowed = is_array($allowed) ? array_values(array_filter($allowed, 'is_string')) : [];

        abort_unless($allowed !== [] && IpUtils::checkIp((string) $request->ip(), $allowed), 403, 'Appyhp Studio is not available from this address.');

        $token = (string) config('appyhp.access_token', '');
        if ($token !== '' && ($request->getUser() !== 'appyhp' || ! hash_equals($token, (string) $request->getPassword()))) {
            abort(response('Appyhp Studio authentication is required.', 401, [
                'WWW-Authenticate' => 'Basic realm="AppyHP Studio", charset="UTF-8"',
            ]));
        }

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->is('appyhp/api/*') || $request->is('appyhp/studio')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}

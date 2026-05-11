<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class NormalizeApiRequestHeaders
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->is('api/*')) {
            return $next($request);
        }

        $accept = $request->headers->get('Accept');
        if ($accept === null || $accept === '' || $accept === '*/*') {
            $request->headers->set('Accept', 'application/json');
        }

        if (in_array($request->getRealMethod(), [
            SymfonyRequest::METHOD_POST,
            SymfonyRequest::METHOD_PUT,
            SymfonyRequest::METHOD_PATCH,
        ], true) && ! $request->isJson()) {
            $content = ltrim($request->getContent() ?: '');
            if ($content !== '' && ($content[0] === '{' || $content[0] === '[')) {
                $request->headers->set('Content-Type', 'application/json');
            }
        }

        return $next($request);
    }
}

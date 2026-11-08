<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers in the language the interfaces ask for: French or Dutch, English by default for
 * other API clients. Signed-in people keep that language for the mails they receive.
 */
class SetLocale
{
    public const array SUPPORTED = ['en', 'fr', 'nl'];

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $this->requestedLocale($request);

        App::setLocale($requested ?? 'en');

        $response = $next($request);

        // The route's own authentication has run by now.
        $user = $request->user();

        if ($requested !== null && $requested !== 'en' && $user instanceof User && $user->locale !== $requested) {
            $user->forceFill(['locale' => $requested])->saveQuietly();
        }

        return $response;
    }

    private function requestedLocale(Request $request): ?string
    {
        if (! $request->headers->has('Accept-Language')) {
            return null;
        }

        $preferred = $request->getPreferredLanguage(self::SUPPORTED);

        return $preferred !== null && in_array($preferred, self::SUPPORTED, true) ? $preferred : null;
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Lets the login pages offer the demo accounts to people evaluating the application.
 */
class DemoAccountController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless((bool) config('demo.enabled'), Response::HTTP_NOT_FOUND);

        return response()->json([
            'password' => config('demo.password'),
            'accounts' => config('demo.accounts'),
        ]);
    }
}

<?php

namespace app\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler 
{
    public function register(): void
    {
        $this->renderable(function (Throwable $e, $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')){
                return null;
            }

            //404 model not found
            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'message' => 'Resource Not Found.',
                ], 404);
            }

            // 404 — route not found
            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => 'Endpoint not found.',
                ], 404);
            }

            //401 - unauthenticated
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Unauthenticated . Please provide a valid token.',
                ], 401);
            }

            //422 - validation errors
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }

            //HTTP Generic Exceptions
            if ($e instanceof HttpException) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'An error occured.',
                ], $e->getStatusCode());
            }

            // 500 - catch-all for production
            if (! config('app.debug')) {
                return response()->json([
                    'message' => 'server error. Please try again later.',
                ], 500);
            }

            return null;
        });

    }

}
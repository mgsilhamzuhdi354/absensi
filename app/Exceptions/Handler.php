<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Log additional context for production debugging
            if (app()->environment('production')) {
                Log::channel('daily')->error('Production Error', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => auth()->id() ?? 'guest',
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        });

        // Handle database connection errors gracefully
        $this->renderable(function (QueryException $e, $request) {
            // Check if it's a connection error
            if (
                str_contains($e->getMessage(), 'SQLSTATE[HY000] [2002]') ||
                str_contains($e->getMessage(), 'Connection refused')
            ) {

                Log::critical('Database Connection Error', [
                    'message' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Koneksi database terputus. Silakan coba lagi.',
                        'error_code' => 'DB_CONNECTION_ERROR'
                    ], 503);
                }

                return response()->view('errors.500', [], 503);
            }

            // Handle other query errors
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan pada database. Silakan coba lagi.',
                    'error_code' => 'DB_ERROR'
                ], 500);
            }
        });

        // Handle token mismatch (CSRF) errors
        $this->renderable(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi Anda telah berakhir. Silakan refresh halaman.',
                    'error_code' => 'SESSION_EXPIRED'
                ], 419);
            }

            return response()->view('errors.419', [], 419);
        });

        // Handle authentication errors
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda harus login untuk melanjutkan.',
                    'error_code' => 'AUTH_REQUIRED',
                    'redirect' => url('/login')
                ], 401);
            }
        });

        // Handle 404 errors for AJAX
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data atau halaman tidak ditemukan.',
                    'error_code' => 'NOT_FOUND'
                ], 404);
            }
        });

        // Handle validation errors (improve message format)
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dikirim tidak valid.',
                    'errors' => $e->errors(),
                    'error_code' => 'VALIDATION_ERROR'
                ], 422);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // For AJAX requests in production, always return JSON
        if (app()->environment('production') && ($request->expectsJson() || $request->ajax())) {
            $status = 500;

            if ($e instanceof HttpException) {
                $status = $e->getStatusCode();
            }

            // Don't expose detailed errors in production
            return response()->json([
                'success' => false,
                'message' => $this->getProductionMessage($e),
                'error_code' => 'SERVER_ERROR'
            ], $status);
        }

        return parent::render($request, $e);
    }

    /**
     * Get user-friendly error message for production.
     */
    private function getProductionMessage(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            return 'Terjadi kesalahan pada database. Silakan coba lagi.';
        }

        if ($e instanceof TokenMismatchException) {
            return 'Sesi Anda telah berakhir. Silakan refresh halaman.';
        }

        if ($e instanceof NotFoundHttpException) {
            return 'Data atau halaman tidak ditemukan.';
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return 'Metode request tidak diizinkan.';
        }

        return 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.';
    }
}

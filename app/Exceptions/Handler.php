<?php
declare(strict_types=1);

namespace App\Exceptions;

use App\Utils\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException as SmsSendFailedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        BusinessException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  Throwable  $e
     * @return void
     *
     * @throws Throwable
     */
    public function report(Throwable $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  $request
     * @param  Throwable  $e
     * @return JsonResponse|\Illuminate\Http\Response|Response
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof SmsSendFailedException) {
            return response()->json([
                'errno' => ResponseCode::AUTH_CAPTCHA_SEND_FAILED[0],
                'errmsg' => ResponseCode::AUTH_CAPTCHA_SEND_FAILED[1]
            ]);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'errno' => ResponseCode::MODEL_NOT_FOUND[0],
                'errmsg' => ResponseCode::MODEL_NOT_FOUND[1]
            ]);
        }

        if ($e instanceof BusinessException) {
            return response()->json([
                'errno' => $e->getCode(),
                'errmsg' => $e->getMessage()
            ]);
        }

        return parent::render($request, $e);
    }
}

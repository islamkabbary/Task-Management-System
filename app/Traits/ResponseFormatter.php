<?php

namespace App\Traits;

trait ResponseFormatter
{
    public function formatSuccess($data = null, $message = '', $code = 200)
    {
        return [
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'code'    => $code
        ];
    }

    public function formatError($message = '', $code = 400)
    {
        return [
            'success' => false,
            'message' => $message,
            'data'    => null,
            'code'    => $code
        ];
    }
}

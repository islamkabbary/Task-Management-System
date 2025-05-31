<?php

namespace App\Traits;

trait ResponseTrait
{
    protected $response = [
        'status' => false,
        'data' => [],
        'message' => '',
        'pagination' => null
    ];

    /**
     * Success response
     *
     * @param array $data
     * @param string|null $message
     * @param array|null $pagination
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function success(mixed $data = [], ?string $message = null, int $statusCode = 200)
    {
        $this->response['status'] = true;
        $this->response['data'] = $data;
        $this->response['message'] = $message ?? 'Success';

        if ($data instanceof \Illuminate\Http\Resources\Json\AnonymousResourceCollection) {
            $paginator = $data->resource;
    
            if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                $this->response['pagination'] = [
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                    'items_per_page' => $paginator->perPage(),
                    'total_items' => $paginator->total(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ];
    
                return response()->json($this->response, $statusCode)
                    ->header('X-Total-Count', $paginator->total())
                    ->header('X-Total-Pages', $paginator->lastPage())
                    ->header('X-Per-Page', $paginator->perPage())
                    ->header('X-Current-Page', $paginator->currentPage())
                    ->header('X-Next-Page', $paginator->nextPageUrl())
                    ->header('X-Previous-Page', $paginator->previousPageUrl());
            }
        }

        return response()->json($this->response, $statusCode);
    }

    /**
     * Error response
     *
     * @param array $data
     * @param string|null $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function error($data = [], $message = null, $statusCode = 500)
    {
        $this->response['status'] = false;
        $this->response['data'] = $data;
        $this->response['message'] = $message ?? 'Error';

        return response()->json($this->response, $statusCode);
    }
}

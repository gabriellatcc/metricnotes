<?php

namespace App\Http\Controllers;

use App\Services\WeeklyAnalyticsService;
use Exception;

class AnalyticsController extends Controller
{
    public function __construct(private readonly WeeklyAnalyticsService $weeklyAnalyticsService) {}

    public function weekly()
    {
        try {
            $data = $this->weeklyAnalyticsService->weekly();

            return $this->respondSuccess($data, 'Análise semanal carregada com sucesso.');
        } catch (Exception $e) {
            $code = ($e->getCode() >= 100 && $e->getCode() <= 599)
                ? (int) $e->getCode()
                : 500;

            return $this->respondError($e->getMessage(), null, $code);
        }
    }
}

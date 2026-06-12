<?php

namespace App\Http\Controllers;

use App\Services\ApiHealthRegistry;
use App\Services\BpjsChecker;
use Illuminate\Http\JsonResponse;

class BpjsDashboardController extends Controller
{
    public function __construct(private ApiHealthRegistry $registry) {}

    public function index()
    {
        $services    = $this->registry->getCachedAll();
        $cdnMode     = BpjsChecker::activeMode();
        $cdnHosts    = config('bpjs.hosts');

        return view('bpjs.dashboard', compact('services', 'cdnMode', 'cdnHosts'));
    }

    public function checkAll(): JsonResponse
    {
        $services = $this->registry->checkAll();
        return response()->json(['services' => $services]);
    }

    public function checkService(string $serviceKey): JsonResponse
    {
        $checker = $this->registry->get($serviceKey);

        if (!$checker) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $results = $checker->checkAll();
        return response()->json([
            'service' => $serviceKey,
            'label'   => $checker->getServiceLabel(),
            'results' => array_values($results),
        ]);
    }

    public function switchMode(string $mode): JsonResponse
    {
        BpjsChecker::setMode($mode);

        return response()->json([
            'mode'  => $mode,
            'host'  => config("bpjs.hosts.{$mode}"),
            'label' => $mode === 'cdn' ? 'CDN' : 'Non-CDN',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\OversightReport;
use Illuminate\View\View;

/** Executive oversight; protected by the `view-oversight` gate (see routes/web.php). */
class OversightController extends Controller
{
    public function __invoke(OversightReport $report): View
    {
        return view('oversight.index', ['report' => $report->build()]);
    }
}

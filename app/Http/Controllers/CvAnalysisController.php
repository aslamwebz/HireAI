<?php

namespace App\Http\Controllers;

use App\Models\CvAnalysis;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CvAnalysisController extends Controller
{
    public function show(CvAnalysis $cvAnalysis): View
    {
        $this->authorize('view', $cvAnalysis);

        return view('cv-analysis-show', ['analysis' => $cvAnalysis]);
    }

    public function destroy(CvAnalysis $cvAnalysis): RedirectResponse
    {
        $this->authorize('delete', $cvAnalysis);

        $cvAnalysis->delete();

        return redirect()->route('cv-analyzer')->with('success', 'Analysis deleted successfully.');
    }
}

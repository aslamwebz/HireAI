<?php

namespace App\Http\Controllers;

use App\Models\CvAnalysis;

class CvAnalysisController extends Controller
{
    public function show(CvAnalysis $cvAnalysis)
    {
        if ($cvAnalysis->user_id !== auth()->id()) {
            abort(403);
        }

        return view('cv-analysis-show', ['analysis' => $cvAnalysis]);
    }

    public function destroy(CvAnalysis $cvAnalysis)
    {
        if ($cvAnalysis->user_id !== auth()->id()) {
            abort(403);
        }

        $cvAnalysis->delete();

        return redirect()->route('cv-analyzer')->with('success', 'Analysis deleted successfully.');
    }
}

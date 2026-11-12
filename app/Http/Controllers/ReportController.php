<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plant;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $request)
    {
         $plants = $this->filteredPlants($request);
        return view('reports.index', compact('plants'));
    }

    public function filteredPlants(Request $request)
    {
        $query = Plant::query();

         if ($request->filled('category')) {
        $query->where('category', $request->category);
    }

    if ($request->filled('stock_status')) {
        $query->where('stock_status', $request->stock_status);
    }

    return $query->get();
    }

    public function pdf(Request $request)
    {
    $plants = $this->filteredPlants($request);

    return Pdf::LoadView('reports.pdf',compact('plants'))->download('plant-report.pdf');

    }

}

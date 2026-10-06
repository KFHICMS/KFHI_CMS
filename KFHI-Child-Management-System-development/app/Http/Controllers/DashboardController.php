<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
// Import your models here, e.g.:
// use App\Models\Child;
// use App\Models\Program;

class DashboardController extends Controller
{
    public function index()
    {
        // Example data (replace with actual Eloquent queries like Child::count())
        $stats = [
            'total_children' => 1245,
            'active_programs' => 18,
            'officers' => 35,
            'activities' => 86,
        ];

        // Data for charts (e.g., Program distribution or monthly activities)
        $chartData = [
            'pie_labels' => ['Education', 'Health', 'Shelter', 'Nutrition'],
            'pie_values' => [450, 300, 250, 245],
            'bar_labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'bar_values' => [12, 19, 15, 25, 22, 30],
        ];

        return view('dashboard', compact('stats', 'chartData'));
    }
}
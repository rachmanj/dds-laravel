<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Distribution;

class WelcomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user->loadMissing(['roles', 'department']);
        
        // Only load minimal, cached data for quick response
        $quickStats = [
            'pending_distributions' => Cache::remember(
                "welcome.pending.{$user->id}", 
                60, 
                function() use ($user) {
                    $isAdmin = $user->hasAnyRole(['admin', 'superadmin']);
                    $query = Distribution::where('status', 'sent');
                    
                    if (!$isAdmin && $user->department_id) {
                        $query->where('destination_department_id', $user->department_id);
                    }
                    
                    return $query->count();
                }
            ),
        ];
        
        return view('welcome', compact('quickStats'));
    }
}

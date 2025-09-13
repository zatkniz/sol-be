<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Employer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameter (default to 'this_month')
        $filter = $request->get('filter', 'this_month');
        
        // Calculate date ranges based on filter
        $dates = $this->getDateRangeFromFilter($filter);
        $currentStart = $dates['current_start'];
        $currentEnd = $dates['current_end'];
        $previousStart = $dates['previous_start'];
        $previousEnd = $dates['previous_end'];
        
        // Get the comparison label
        $comparisonLabel = $this->getComparisonLabel($filter);
        
        return $this->calculateStats($currentStart, $currentEnd, $previousStart, $previousEnd, $comparisonLabel, $filter);
    }
    
    private function getDateRangeFromFilter($filter) 
    {
        switch($filter) {
            case 'this_year':
                return [
                    'current_start' => Carbon::now()->startOfYear(),
                    'current_end' => Carbon::now()->endOfYear(),
                    'previous_start' => Carbon::now()->subYear()->startOfYear(),
                    'previous_end' => Carbon::now()->subYear()->endOfYear(),
                ];
            
            case 'last_month':
                return [
                    'current_start' => Carbon::now()->subMonth()->startOfMonth(),
                    'current_end' => Carbon::now()->subMonth()->endOfMonth(),
                    'previous_start' => Carbon::now()->subMonths(2)->startOfMonth(),
                    'previous_end' => Carbon::now()->subMonths(2)->endOfMonth(),
                ];
            
            case 'last_3_months':
                return [
                    'current_start' => Carbon::now()->subMonths(3)->startOfMonth(),
                    'current_end' => Carbon::now()->subMonth()->endOfMonth(),
                    'previous_start' => Carbon::now()->subMonths(6)->startOfMonth(),
                    'previous_end' => Carbon::now()->subMonths(4)->endOfMonth(),
                ];
            
            case 'this_month':
            default:
                return [
                    'current_start' => Carbon::now()->startOfMonth(),
                    'current_end' => Carbon::now()->endOfMonth(),
                    'previous_start' => Carbon::now()->subMonth()->startOfMonth(),
                    'previous_end' => Carbon::now()->subMonth()->endOfMonth(),
                ];
        }
    }
    
    private function getComparisonLabel($filter)
    {
        switch($filter) {
            case 'this_year': return 'σε σχέση με πέρυσι';
            case 'last_month': return 'σε σχέση με προηγούμενο μήνα';
            case 'last_3_months': return 'σε σχέση με προηγούμενα 3μήνα';
            case 'this_month': 
            default: return 'σε σχέση με προηγούμενο μήνα';
        }
    }
    
    private function calculateStats($currentStart, $currentEnd, $previousStart, $previousEnd, $comparisonLabel, $filter)
    {
        // Calculate statistics for dashboard
        
        // Total revenue (current period)
        $currentRevenue = Booking::whereBetween('date', [$currentStart, $currentEnd])->sum('cost');
        
        // Previous period revenue for comparison
        $previousRevenue = Booking::whereBetween('date', [$previousStart, $previousEnd])->sum('cost');
        
        // Revenue change percentage
        $revenueChange = 0;
        if ($previousRevenue > 0) {
            $revenueChange = (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
        }
        
        // Total bookings current period
        $currentBookings = Booking::whereBetween('date', [$currentStart, $currentEnd])->count();
        
        // Previous period bookings for comparison
        $previousBookings = Booking::whereBetween('date', [$previousStart, $previousEnd])->count();
        
        // Bookings change percentage
        $bookingsChange = 0;
        if ($previousBookings > 0) {
            $bookingsChange = (($currentBookings - $previousBookings) / $previousBookings) * 100;
        }
        
        // Total active clients (clients with bookings in current period)
        $activeClients = Client::whereHas('bookings', function ($query) use ($currentStart, $currentEnd) {
            $query->whereBetween('date', [$currentStart, $currentEnd]);
        })->count();
        
        // Previous period active clients
        $previousActiveClients = Client::whereHas('bookings', function ($query) use ($previousStart, $previousEnd) {
            $query->whereBetween('date', [$previousStart, $previousEnd]);
        })->count();
        
        // Active clients change
        $clientsChange = 0;
        if ($previousActiveClients > 0) {
            $clientsChange = (($activeClients - $previousActiveClients) / $previousActiveClients) * 100;
        }
        
        // Total employers
        $totalEmployers = Employer::count();
        
        // Average booking cost for current period
        $averageBookingCost = Booking::whereBetween('date', [$currentStart, $currentEnd])->avg('cost') ?? 0;
        
        // Previous period average cost
        $previousAverageBookingCost = Booking::whereBetween('date', [$previousStart, $previousEnd])->avg('cost') ?? 0;
        
        // Average cost change
        $averageCostChange = 0;
        if ($previousAverageBookingCost > 0) {
            $averageCostChange = (($averageBookingCost - $previousAverageBookingCost) / $previousAverageBookingCost) * 100;
        }
        
        // Total services offered
        $totalServices = Service::count();
        
        // Most popular service in current period
        $popularService = DB::table('booking_service')
            ->join('services', 'booking_service.service_id', '=', 'services.id')
            ->join('bookings', 'booking_service.booking_id', '=', 'bookings.id')
            ->whereBetween('bookings.date', [$currentStart, $currentEnd])
            ->select('services.name', DB::raw('count(*) as total'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->first();
        
        // Average booking duration in current period
        $averageDuration = DB::table('bookings')
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->whereNotNull('duration')
            ->avg(DB::raw('TIME_TO_SEC(duration)'));
        
        $averageDurationFormatted = $averageDuration ? gmdate('H:i', $averageDuration) : '0:00';
        
        return response()->json([
            'filter' => $filter,
            'period_label' => $this->getPeriodLabel($filter),
            'revenue' => [
                'value' => '€' . number_format($currentRevenue, 2),
                'change' => ($revenueChange >= 0 ? '+' : '') . number_format($revenueChange, 2) . '%',
                'changeType' => $revenueChange >= 0 ? 'positive' : 'negative',
                'name' => 'Έσοδα'
            ],
            'bookings' => [
                'value' => number_format($currentBookings),
                'change' => ($bookingsChange >= 0 ? '+' : '') . number_format($bookingsChange, 2) . '%',
                'changeType' => $bookingsChange >= 0 ? 'positive' : 'negative',
                'name' => 'Ραντεβού'
            ],
            'active_clients' => [
                'value' => number_format($activeClients),
                'change' => ($clientsChange >= 0 ? '+' : '') . number_format($clientsChange, 2) . '%',
                'changeType' => $clientsChange >= 0 ? 'positive' : 'negative',
                'name' => 'Ενεργοί Πελάτες'
            ],
            'employers' => [
                'value' => number_format($totalEmployers),
                'change' => 'Σύνολο υπαλλήλων',
                'changeType' => 'positive',
                'name' => 'Υπάλληλοι'
            ],
            'average_cost' => [
                'value' => '€' . number_format($averageBookingCost, 2),
                'change' => ($averageCostChange >= 0 ? '+' : '') . number_format($averageCostChange, 2) . '%',
                'changeType' => $averageCostChange >= 0 ? 'positive' : 'negative',
                'name' => 'Μέσος Όρος Κόστους'
            ],
            'services' => [
                'value' => number_format($totalServices),
                'change' => $popularService ? 'Δημοφιλής: ' . $popularService->name : 'Όλες οι υπηρεσίες',
                'changeType' => 'positive',
                'name' => 'Συνολικές Υπηρεσίες'
            ],
            'average_duration' => [
                'value' => $averageDurationFormatted,
                'change' => $comparisonLabel,
                'changeType' => 'positive',
                'name' => 'Μέση Διάρκεια'
            ]
        ]);
    }
    
    private function getPeriodLabel($filter)
    {
        switch($filter) {
            case 'this_year': return 'Φέτος';
            case 'last_month': return 'Προηγούμενος Μήνας';
            case 'last_3_months': return 'Τελευταίοι 3 Μήνες';
            case 'this_month': 
            default: return 'Αυτός ο Μήνας';
        }
    }
}

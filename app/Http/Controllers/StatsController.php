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
        
        // New Clients - Clients created in current period
        $newClients = Client::whereBetween('created_at', [$currentStart, $currentEnd])->count();
        
        // Previous period new clients
        $previousNewClients = Client::whereBetween('created_at', [$previousStart, $previousEnd])->count();
        
        // New clients change
        $newClientsChange = 0;
        if ($previousNewClients > 0) {
            $newClientsChange = (($newClients - $previousNewClients) / $previousNewClients) * 100;
        }
        
        // Completion Rate - Percentage of bookings completed (past dates are considered completed)
        $today = Carbon::now();
        $totalBookingsForCompletion = Booking::whereBetween('date', [$currentStart, $currentEnd])->count();
        
        // Consider bookings with past dates as completed
        $completedBookings = Booking::whereBetween('date', [$currentStart, $currentEnd])
            ->where('date', '<=', $today->format('Y-m-d'))->count();
        
        $completionRate = $totalBookingsForCompletion > 0 ? 
            ($completedBookings / $totalBookingsForCompletion) * 100 : 0;
        
        // Previous period completion rate
        $previousTotalBookings = Booking::whereBetween('date', [$previousStart, $previousEnd])->count();
        $previousCompletedBookings = Booking::whereBetween('date', [$previousStart, $previousEnd])
            ->where('date', '<=', $today->format('Y-m-d'))->count();
        
        $previousCompletionRate = $previousTotalBookings > 0 ? 
            ($previousCompletedBookings / $previousTotalBookings) * 100 : 0;
        
        $completionRateChange = $previousCompletionRate > 0 ? 
            (($completionRate - $previousCompletionRate) / $previousCompletionRate) * 100 : 0;
        
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
            ],
            'new_clients' => [
                'value' => number_format($newClients),
                'change' => ($newClientsChange >= 0 ? '+' : '') . number_format($newClientsChange, 2) . '%',
                'changeType' => $newClientsChange >= 0 ? 'positive' : 'negative',
                'name' => 'Νέοι Πελάτες'
            ],
            'completion_rate' => [
                'value' => number_format($completionRate, 1) . '%',
                'change' => ($completionRateChange >= 0 ? '+' : '') . number_format($completionRateChange, 1) . '%',
                'changeType' => $completionRateChange >= 0 ? 'positive' : 'negative',
                'name' => 'Ποσοστό Ολοκλήρωσης'
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
    
    /**
     * Get chart data for revenue trend
     */
    public function chartData(Request $request)
    {
        $filter = $request->get('filter', 'this_month');
        
        // Get the appropriate date range for charts
        switch($filter) {
            case 'this_year':
                $months = collect();
                for($i = 11; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $monthStart = $month->copy()->startOfMonth();
                    $monthEnd = $month->copy()->endOfMonth();
                    
                    $revenue = Booking::whereBetween('date', [$monthStart, $monthEnd])
                        ->sum('cost');
                    $bookings = Booking::whereBetween('date', [$monthStart, $monthEnd])
                        ->count();
                        
                    $months->push([
                        'label' => $month->locale('el')->format('M'),
                        'revenue' => (float) $revenue,
                        'bookings' => $bookings
                    ]);
                }
                break;
                
            case 'last_3_months':
                $months = collect();
                for($i = 2; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $monthStart = $month->copy()->startOfMonth();
                    $monthEnd = $month->copy()->endOfMonth();
                    
                    $revenue = Booking::whereBetween('date', [$monthStart, $monthEnd])
                        ->sum('cost');
                    $bookings = Booking::whereBetween('date', [$monthStart, $monthEnd])
                        ->count();
                        
                    $months->push([
                        'label' => $month->locale('el')->format('M'),
                        'revenue' => (float) $revenue,
                        'bookings' => $bookings
                    ]);
                }
                break;
                
            default: // this_month, last_month
                $startDate = $filter === 'last_month' ? 
                    Carbon::now()->subMonth()->startOfMonth() : 
                    Carbon::now()->startOfMonth();
                    
                $days = collect();
                $currentDate = $startDate->copy();
                $endDate = $startDate->copy()->endOfMonth();
                
                while($currentDate->lte($endDate)) {
                    $dayStart = $currentDate->copy()->startOfDay();
                    $dayEnd = $currentDate->copy()->endOfDay();
                    
                    $revenue = Booking::whereBetween('date', [$dayStart, $dayEnd])
                        ->sum('cost');
                    $bookings = Booking::whereBetween('date', [$dayStart, $dayEnd])
                        ->count();
                        
                    $days->push([
                        'label' => $currentDate->format('d'),
                        'revenue' => (float) $revenue,
                        'bookings' => $bookings
                    ]);
                    
                    $currentDate->addDay();
                }
                $months = $days;
        }
        
        // Get services distribution
        $servicesData = Service::leftJoin('booking_service', 'services.id', '=', 'booking_service.service_id')
            ->leftJoin('bookings', 'booking_service.booking_id', '=', 'bookings.id')
            ->select('services.name', DB::raw('COUNT(booking_service.id) as count'))
            ->groupBy('services.id', 'services.name')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();
        
        return response()->json([
            'monthly_data' => $months,
            'services_distribution' => $servicesData
        ]);
    }
    
    /**
     * Get table data for recent bookings and top services
     */
    public function tableData(Request $request)
    {
        $filter = $request->get('filter', 'this_month');
        
        // Get date range for filtering
        $dates = $this->getDateRangeFromFilter($filter);
        
        // Best clients by total spending in the selected period
        $bestClients = Client::withCount(['bookings as total_bookings' => function ($query) use ($dates) {
                $query->whereBetween('date', [$dates['current_start'], $dates['current_end']]);
            }])
            ->withSum(['bookings as total_spent' => function ($query) use ($dates) {
                $query->whereBetween('date', [$dates['current_start'], $dates['current_end']]);
            }], 'cost')
            ->with(['bookings' => function ($query) use ($dates) {
                $query->whereBetween('date', [$dates['current_start'], $dates['current_end']])
                      ->latest('date');
            }])
            ->whereHas('bookings', function ($query) use ($dates) {
                $query->whereBetween('date', [$dates['current_start'], $dates['current_end']]);
            })
            ->orderBy('total_spent', 'desc')
            ->take(5)
            ->get()
            ->map(function($client) {
                $lastBooking = $client->bookings->first();
                return [
                    'id' => $client->id,
                    'client_name' => $client->full_name,
                    'phone' => $client->telephone,
                    'total_bookings' => $client->total_bookings,
                    'total_spent' => number_format($client->total_spent ?? 0, 2),
                    'last_booking_date' => $lastBooking ? $lastBooking->date->toISOString() : null
                ];
            });
        
        // Top services performance
        $topServices = Service::leftJoin('booking_service', 'services.id', '=', 'booking_service.service_id')
            ->leftJoin('bookings', 'booking_service.booking_id', '=', 'bookings.id')
            ->whereBetween('bookings.date', [$dates['current_start'], $dates['current_end']])
            ->select(
                'services.id',
                'services.name',
                DB::raw('COUNT(booking_service.id) as bookings_count'),
                DB::raw('SUM(bookings.cost) as total_revenue')
            )
            ->groupBy('services.id', 'services.name')
            ->orderBy('total_revenue', 'desc')
            ->take(5)
            ->get()
            ->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'bookings_count' => $service->bookings_count,
                    'total_revenue' => number_format($service->total_revenue ?? 0, 2)
                ];
            });
        
        return response()->json([
            'best_clients' => $bestClients,
            'top_services' => $topServices
        ]);
    }
}

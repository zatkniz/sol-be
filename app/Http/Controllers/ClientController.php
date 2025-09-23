<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function index (Request $request) {
        $order = $request->input('sortOrder') === '1' ? 'asc' : 'desc';
        $perPage = $request->input('perPage') ?? 10;

        $orderBy = $request->input('sortField');

        $query = new Client();

        if ($orderBy) {
            $query = $query->orderBy($orderBy, $order);
        } else {
            $query = $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
        }

        if($request->input('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(first_name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                      ->orWhereRaw('LOWER(last_name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                      ->orWhereRaw('LOWER(telephone) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                      ->orWhereRaw('LOWER(email) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                      ->orWhereRaw('LOWER(address) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
            });
        }
        return $query->paginate($perPage);
    }

    public function getAllClients () {
        return Client::all();
    }

    public function save (Request $request) {
        $client = Client::updateOrCreate(
            ['id' => $request->input('id')],
            $request->all()
        );

        return $client;
    }

    public function delete (Client $client) {
        return $client->delete();
    }
    
    public function getStats (Request $request, Client $client) {
        $showAllBookings = $request->boolean('showAllBookings', false);
        
        $query = $client->bookings()->with(['client', 'user', 'services', 'employer', 'bookingServices.service']);
        
        // Apply booking visibility filtering if showAllBookings is false
        if (!$showAllBookings) {
            $currentTime = now();
            
            $query->where(function ($q) use ($currentTime) {
                // Always show bookings that came (came = true)
                $q->where('came', true)
                  // OR always show bookings that did not come (did_not_came = true)
                  ->orWhere('did_not_came', true)
                  // OR show future bookings (came = false/null AND did_not_came = false/null AND booking time > current time)
                  ->orWhere(function ($subQuery) use ($currentTime) {
                      $subQuery->where(function ($cameQuery) {
                          $cameQuery->where('came', false)->orWhereNull('came');
                      })
                      ->where(function ($didNotCameQuery) {
                          $didNotCameQuery->where('did_not_came', false)->orWhereNull('did_not_came');
                      })
                      ->whereRaw("STR_TO_DATE(CONCAT(date, ' ', time), '%Y-%m-%d %H:%i:%s') > ?", [$currentTime]);
                  });
            });
        }
        
        $history = $query->get()->sortByDesc('date');
        $bookingsCount = $history->count();

        return [
            'bookings_count' => $bookingsCount,
            'history' => $history,
            'total_amount' => $history->sum('cost'),
            'average_amount' => $bookingsCount > 0 ? $history->sum('cost') / $bookingsCount : '0',
            'last_booking' => $history->first()->date ?? 'N/A'
        ];
    }
}

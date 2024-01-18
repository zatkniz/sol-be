<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function index (Request $request) {
        $orderBy = $request->input('sortField') ?? 'id';
        $order = $request->input('sortOrder') === '1' ? 'asc' : 'desc';
        $perPage = $request->input('perPage') ?? 10;
        return Client::orderBy($orderBy, $order)->paginate($perPage);
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
    
    public function getStats (Client $client) {
        $history = $client->bookings()->with('services')->get()->sortByDesc('date');
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

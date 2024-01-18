<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index (Request $request) {
        $orderBy = $request->input('sortField') ?? 'id';
        $order = $request->input('sortOrder') === '1' ? 'asc' : 'desc';
        $perPage = $request->input('perPage') ?? 10;
        return Booking::with([ 'client', 'user', 'services' ])->orderBy($orderBy, $order)->paginate($perPage);
    }

    public function monthlyBookings (Request $request) {
        return Booking::with([ 'client', 'user', 'services' ])->get();
    }

    public function save (Request $request) {
        $client = $request->input('client');

        $date = $request->input('date');
        $newDate = Carbon::parse($date, 'UTC')->tz('Europe/Athens');
        $time = Carbon::parse($request->input('time'), 'UTC')->tz('Europe/Athens');

        $booking = Booking::updateOrCreate(
            ['id' => $request->input('id')],
            [
                'client_id' => $client['id'],
                'cost' => $request->input('cost'),
                'duration' => $request->input('duration'),
                'comments' => $request->input('comments'),
                'date' => $newDate,
                'time' => $time->format('H:i'),
                'user_id' => Auth()->user()->id,
            ]
        );

        $services = collect($request->input('services'))->pluck('id');

        $booking->services()->sync($services);

        return $booking;
    }

    public function delete (Booking $booking) {
        return $booking->delete();
    }
}

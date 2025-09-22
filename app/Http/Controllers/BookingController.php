<?php

namespace App\Http\Controllers;
use App\Models\Employer;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Schedule;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index (Request $request) {
        $orderBy = $request->input('sortField') ?? 'id';
        $order = $request->input('sortOrder') === '1' ? 'asc' : 'desc';
        $perPage = $request->input('perPage') ?? 10;
        return Booking::with([ 'client', 'user', 'services', 'employer', 'bookingServices'])->orderBy($orderBy, $order)->paginate($perPage);
    }

    public function monthlyBookings (Request $request) {
        $dateStart = Carbon::parse($request->input('start'));
        $dateEnd = Carbon::parse($request->input('end'));
        
        $bookings = Booking::with(['client', 'user', 'services', 'bookingServices.service', 'employer.schedule' => function ($query) use ($dateStart, $dateEnd) {
                            $query->whereBetween('date', [$dateStart, $dateEnd]);
                        },
                        'employerSecondary.schedule' => function ($query) use ($dateStart, $dateEnd) {
                            $query->whereBetween('date', [$dateStart, $dateEnd]);
                        } ])
                        ->whereBetween('date', [$dateStart, $dateEnd])
                        ->get();

        $schedules = Schedule::whereBetween('date', [$dateStart, $dateEnd])
                                ->with('employer')
                                ->get();

        return [
            'bookings' => $bookings,
            'schedules' => $schedules
        ];
    }

    public function getAvailableEmployers (Request $request) {
        $query = new Employer();

        return $query->get()->map(function($employer) use ($request) {
            $time = Carbon::parse($request->input('time'));
            $date = Carbon::parse($request->input('date'));

            // Assuming $duration is a string in the format HH:mm:ss (00:59:00 in this case)
            $duration = $request->input('duration');

            $minutes = explode(':', $duration)[1];
            $hours = explode(':', $duration)[0];
            
            // Calculate the end time by adding the duration to the start time
            $endTime = $time->copy()->addMinutes($minutes)->addHours($hours);
            $booking = Booking::where('date', $date)
                                ->with(['services', 'client', 'user', 'employer'])
                                ->where('employer_id', $employer->id)
                                ->get();

            // Check if the employer has another booking at the same time
            $employer->booking = $booking->filter(function($booking) use ($time, $endTime) {
                $bookingTime = Carbon::parse($booking->time);
                $minutes = explode(':', $booking->duration)[1];

                $bookingEndTime = $bookingTime->copy()->addMinutes($minutes);
                return $time <= $bookingEndTime && $endTime >= $bookingTime;
            })->values()->all();

            $employer->schedule = Schedule::where('employer_id', $employer->id)
                                        ->where('date', $date)
                                        ->get()
                                        ->filter(function($booking) use ($time, $endTime) {
                                            $bookingTime = Carbon::parse($booking->time_start);                            
                                            $bookingEndTime = Carbon::parse($booking->time_end);
                                            return $time <= $bookingEndTime && $endTime >= $bookingTime;
                                        });

            return $employer;
        });
    }

    public function save (Request $request) {
        $client = $request->input('client');

        $date = $request->input('date');
        $newDate = Carbon::parse($date);
        $time = Carbon::parse($request->input('time'));

        $booking = Booking::updateOrCreate(
            ['id' => $request->input('id')],
            [
                'client_id' => $client['id'],
                'cost' => $request->input('cost'),
                'duration' => $request->input('duration'),
                'secondary_duration' => $request->input('secondary_duration'),
                'came' => $request->input('came', false), // Default to false if not provided
                'comments' => $request->input('comments'),
                'comments_second' => $request->input('comments_second'),
                'employer_id' => $request->input('employer_id'),
                'secondary_employer_id' => $request->input('secondary_employer_id'),
                'requested_secondary' => $request->input('requested_secondary'),
                'date' => $newDate,
                'time' => $time->format('H:i'),
                'user_id' => Auth()->user()->id,
                'requested' => $request->input('requested')
            ]
        );

        $services = $request->input('services');
        $employer_id = $request->input('employer_id');
        $services_second = $request->input('services_second') ?? [];
        $secondary_employer_id = $request->input('secondary_employer_id');

        BookingService::where('booking_id', $booking->id)->delete();

        collect($services)->each(function ($service) use ($employer_id, $booking) {
            $booking->services()->attach($service, ['employer_id' => $employer_id]);
        });

        collect($services_second)->each(function ($service) use ($secondary_employer_id, $booking) {
            $booking->services()->attach($service, ['employer_id' => $secondary_employer_id]);
        });

        return $booking;
    }

    public function delete (Booking $booking) {
        return $booking->delete();
    }
}

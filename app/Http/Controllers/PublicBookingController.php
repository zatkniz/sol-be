<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Employer;
use App\Models\Schedule;
use App\Models\Booking;
use App\Models\Client;
use App\Models\BookingService;
use Carbon\Carbon;

class PublicBookingController extends Controller
{
    /**
     * Get all active services for public booking
     */
    public function getServices()
    {
        return Service::orderBy('name')->get();
    }

    /**
     * Get all active employers (staff) for public booking
     */
    public function getEmployers()
    {
        return Employer::whereNull('firing_date')
            ->orWhere('firing_date', '>', now())
            ->orderBy('order')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'color']);
    }

    /**
     * Get available time slots for a specific date
     */
    public function getAvailableSlots(Request $request)
    {
        $date = Carbon::parse($request->input('date'));
        $serviceIds = $request->input('services', []);
        
        // Ensure it's an array (handles both 'services[]' and 'services' formats)
        if (!is_array($serviceIds)) {
            $serviceIds = [$serviceIds];
        }
        
        // Filter out empty values and cast to integers
        $serviceIds = array_filter(array_map('intval', $serviceIds));
        
        // Calculate total duration needed
        $services = Service::whereIn('id', $serviceIds)->get();
        $totalMinutes = 0;
        
        foreach ($services as $service) {
            $duration = explode(':', $service->duration);
            $totalMinutes += ($duration[0] * 60) + $duration[1];
        }
        
        // Get all employers with their schedules for this date
        $employers = Employer::whereNull('firing_date')
            ->orWhere('firing_date', '>', now())
            ->with(['schedule' => function ($query) use ($date) {
                $query->where('date', $date->format('Y-m-d'));
            }])
            ->get();

        $availableSlots = [];

        foreach ($employers as $employer) {
            // Get employer's schedule for the date
            $schedule = $employer->schedule->first();
            
            // Skip if no schedule or if it's a repo/allowance day
            if (!$schedule || $schedule->repo || $schedule->allowance) {
                continue;
            }

            // Get existing bookings for this employer on this date
            $bookings = Booking::where('employer_id', $employer->id)
                ->where('date', $date->format('Y-m-d'))
                ->get();

            // Also check secondary bookings
            $secondaryBookings = Booking::where('secondary_employer_id', $employer->id)
                ->where('date', $date->format('Y-m-d'))
                ->get();

            // Generate time slots from schedule
            $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->time_start);
            $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->time_end);

            $slots = [];
            $currentTime = $startTime->copy();

            // Generate slots every 15 minutes
            while ($currentTime->copy()->addMinutes($totalMinutes) <= $endTime) {
                $slotEnd = $currentTime->copy()->addMinutes($totalMinutes);
                $isAvailable = true;

                // Check if this slot conflicts with any existing booking
                foreach ($bookings as $booking) {
                    $bookingDate = is_string($booking->date) ? $booking->date : Carbon::parse($booking->date)->format('Y-m-d');
                    $bookingStart = Carbon::parse($bookingDate . ' ' . $booking->time);
                    $duration = explode(':', $booking->duration);
                    $bookingEnd = $bookingStart->copy()->addHours($duration[0])->addMinutes($duration[1]);

                    // Check for overlap
                    if ($currentTime < $bookingEnd && $slotEnd > $bookingStart) {
                        $isAvailable = false;
                        break;
                    }
                }

                // Check secondary bookings
                if ($isAvailable) {
                    foreach ($secondaryBookings as $booking) {
                        $bookingDate = is_string($booking->date) ? $booking->date : Carbon::parse($booking->date)->format('Y-m-d');
                        $bookingStart = Carbon::parse($bookingDate . ' ' . $booking->time);
                        if ($booking->secondary_duration) {
                            $duration = explode(':', $booking->secondary_duration);
                            $bookingEnd = $bookingStart->copy()->addHours($duration[0])->addMinutes($duration[1]);

                            if ($currentTime < $bookingEnd && $slotEnd > $bookingStart) {
                                $isAvailable = false;
                                break;
                            }
                        }
                    }
                }

                if ($isAvailable) {
                    $slots[] = [
                        'time' => $currentTime->format('H:i'),
                        'display' => $currentTime->format('H:i'),
                    ];
                }

                $currentTime->addMinutes(15);
            }

            if (count($slots) > 0) {
                $availableSlots[] = [
                    'employer' => [
                        'id' => $employer->id,
                        'name' => $employer->first_name . ' ' . $employer->last_name,
                        'color' => $employer->color,
                    ],
                    'slots' => $slots,
                ];
            }
        }

        return [
            'date' => $date->format('Y-m-d'),
            'available_slots' => $availableSlots,
            'duration_minutes' => $totalMinutes,
        ];
    }

    /**
     * Create a new public booking
     */
    public function createBooking(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'telephone' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
            'employer_id' => 'required|exists:employers,id',
            'services' => 'required|array|min:1',
            'duration' => 'required',
            'cost' => 'required|numeric',
        ]);

        // Find or create client
        $client = Client::where('telephone', $request->telephone)
            ->first();

        if (!$client) {
            $client = Client::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'address' => $request->address,
                'comments' => $request->comments,
                'area' => $request->area,
                'gender' => $request->gender,
            ]);
        }

        // Create booking
        $date = Carbon::parse($request->date);
        $time = Carbon::parse($request->time);

        // For public bookings, get the first available user (system/admin user)
        // This represents bookings created through the public booking system
        $defaultUser = \App\Models\User::first();
        
        if (!$defaultUser) {
            return response()->json([
                'message' => 'System configuration error. Please contact support.',
            ], 500);
        }

        $booking = Booking::create([
            'client_id' => $client->id,
            'cost' => $request->cost,
            'duration' => $request->duration,
            'came' => false,
            'did_not_came' => false,
            'comments' => $request->booking_comments,
            'employer_id' => $request->employer_id,
            'date' => $date,
            'time' => $time->format('H:i'),
            'user_id' => $defaultUser->id, // Public booking - use first system user
            'requested' => true, // All public bookings are marked as requested
        ]);

        // Attach services
        $services = $request->services;
        $employer_id = $request->employer_id;

        foreach ($services as $serviceId) {
            $booking->services()->attach($serviceId, ['employer_id' => $employer_id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Το ραντεβού σας δημιουργήθηκε επιτυχώς!',
            'booking' => $booking->load(['client', 'services', 'employer']),
        ], 201);
    }
}

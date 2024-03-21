<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function save (Request $request) {
        $employer = $request->input('employer_id');
        $days = collect($request->input('days'));

        $days->each(function ($day) use ($employer) {
            if (!$day['time_start'] || !$day['time_end']) {
                return;
            }

            $id = $day['id'] ?? null;

            Schedule::updateOrCreate(
                [
                    'id' => $day['id'] ?? null,
                ],
                [
                    'employer_id' => $employer,
                    'date' => $day['date'],
                    'time_start' => $day['time_start'],
                    'time_end' => $day['time_end'],
                ]
            );

            if (!isset($day['time_start_second']) || !isset($day['time_end_second'])) {
                return;
            }

            // Schedule::updateOrCreate(
            //     [
            //         'id' => $day['id'] ?? null,
            //     ],
            //     [
            //         'employer_id' => $employer,
            //         'date' => $day['date'],
            //         'time_start' => $day['time_start_second'],
            //         'time_end' => $day['time_end_second'],
            //     ]
            // );
        });

        return $days;
    }

    public function getByEmployer (Request $request, $employer) {

        $date = $request->input('date');
        $carbonDate = Carbon::createFromFormat('m/Y', $date);
        $month = $carbonDate->month;
        $year = $carbonDate->year;

        $query = Schedule::with('employer')
                ->whereMonth('date', $month)
                ->whereYear('date', $year);

        if(isset($employer)) {
            $query->where('employer_id', $employer);
        }

        return $query->get();
    }
}

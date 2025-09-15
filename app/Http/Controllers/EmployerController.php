<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employer;

class EmployerController extends Controller
{
    public function index (Request $request) {
        $order = $request->input('sortOrder') === '1' ? 'asc' : 'desc';
        $perPage = 100;

        $orderBy = $request->input('sortField');

        $query = new Employer();

        // if ($orderBy) {
        //     $query = $query->orderBy($orderBy, $order);
        // } else {
        //     $query = $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
        // }

        $query->orderBy('order', 'asc');

        if($request->input('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(first_name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                ->orWhereRaw('LOWER(last_name) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
            });
        }
        
        return $query->orderBy('order', 'asc')->paginate(1000);
    }

    public function reorder (Request $request) {
        $services = collect($request->all())->map(function($service, $index){
            return Employer::where('id', $service['id'])->update(['order' => $index]);
        });

        return $services;
    }

    public function getAllEmployers () {
        return Employer::orderBy('order', 'asc')->get();
    }

    public function save (Request $request) {
        $employer = Employer::updateOrCreate(
            ['id' => $request->input('id')],
            $request->all()
        );

        return $employer;
    }

    public function delete (Employer $employer) {
        return $employer->delete();
    }
    
    public function getStats (Employer $employer) {
        $history = $employer->bookings()->with('services')->get()->sortByDesc('date');
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

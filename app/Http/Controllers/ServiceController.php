<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index (Request $request) {
        $orderBy = $request->input('sortField') ?? 'id';
        $order = $request->input('sortOrder') === '1' ? 'asc' : 'desc';
        $perPage = $request->input('perPage') ?? 10;
        // $query = Service::orderBy($orderBy, $order);
        $query = Service::orderBy('order', 'asc');

        if($request->input('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                    ->orWhereRaw('LOWER(sort_name) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
            });
        }
        return $query->paginate(-1);
    }

    public function save (Request $request) {
        $service = Service::updateOrCreate(
            ['id' => $request->input('id')],
            $request->all()
        );

        return $service;
    }

    public function reorder (Request $request) {
        $services = collect($request->all())->map(function($service, $index){
            return Service::where('id', $service['id'])->update(['order' => $index]);
        });

        return $services;
    }

    public function getAllServices () {
        return Service::orderBy('order')->get();
    }

    public function delete (Service $service) {
        return $service->delete();
    }
}

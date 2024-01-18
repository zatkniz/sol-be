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
        return Service::orderBy($orderBy, $order)->paginate($perPage);
    }

    public function save (Request $request) {
        $service = Service::updateOrCreate(
            ['id' => $request->input('id')],
            $request->all()
        );

        return $service;
    }

    public function getAllServices () {
        return Service::all();
    }

    public function delete (Service $service) {
        return $service->delete();
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index (Request $request) {
        $orderBy = $request->input('sortField') ?? 'id';
        $order = $request->input('sortOrder') === '1' ? 'asc' : 'desc';
        $perPage = $request->input('perPage') ?? 10;
        return User::orderBy($orderBy, $order)->paginate($perPage);
    }

    public function save (Request $request) {
        $user = User::updateOrCreate(
            ['id' => $request->input('id')],
            $request->all()
        );

        return $user;
    }

    public function getAllUsers () {
        return User::all();
    }

    public function delete($user) {
        return User::where('id', $user)->delete();
    }
}

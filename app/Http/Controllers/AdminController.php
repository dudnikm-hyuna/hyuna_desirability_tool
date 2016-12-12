<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\User;

use Yajra\Datatables\Facades\Datatables;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin.index');
    }

    /**
     * @return mixed
     */
    public function getUsersData()
    {
        return Datatables::eloquent(User::where('id', '<>', Auth::user()->id))->make(true);
    }

    public function changeUserRole($id, $is_manager)
    {
        $role = ($is_manager == 1) ? 'manager' : 'user';
        $user = User::find($id)->fill(['role' => $role]);

        return ($user->update()) ? $user : false;
    }
}

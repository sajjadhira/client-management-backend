<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Todos;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{


    function index(Request $request){


   
        // $data = DB::table('users')
        //     ->join('todos', 'todos.user', '=', 'users.id')
        //     ->select(
        //         'users.id',
        //         'users.name',
        //         DB::raw("count(todos.id) AS tasks"))
        // ->groupBy('users.name', 'users.id')
        // ->get()->count();             
        // return [$data];


        $skip = 0;
        $take = $item_per_page = 5;
        $page = 1;

        
        if($request->has('page')){
            $page = $request->page;
            if($page <1 || $page == ""){
                $page =1;
            }

            $take = $item_per_page;
            if($page>1){
                $skip = ($page-1)*$item_per_page;
            }
        }

        // $total = User::where('role','user')->get()->count();
        $total =  DB::table('users')
            ->join('todos', 'todos.user', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw("count(todos.id) AS tasks"))
        ->groupBy('users.name', 'users.id')
        ->get()->count();

        $pages_count = ceil($total/$item_per_page);
        
        if($skip>0){
            // $allCoins = User::where('role','user')->skip($skip)->take($take)->orderBy('id','DESC')->get();
            $data = DB::table('users')
            ->join('todos', 'todos.user', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw("count(todos.id) AS tasks"))
            ->groupBy('users.name', 'users.id')
            ->skip($skip)
            ->take($take)
            ->orderBy('id','DESC')
            ->get();
        }else{
            // $allCoins = User::where('role','user')->take($take)->orderBy('id','DESC')->get();
            $data = DB::table('users')
            ->join('todos', 'todos.user', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw("count(todos.id) AS tasks"))
            ->groupBy('users.name', 'users.id')
            ->take($take)
            ->orderBy('id','DESC')
            ->get();
        }

        return response()->json(['result'=>$data,'count'=>$total,'pages'=>$pages_count,'page'=>$page]);
        
    }



    // register user
    public function register(Request $request){
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|unique:users,email',
            'password' => 'required|min:8|max:16|confirmed'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
        ]
        );

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    // login user
    public function login(Request $request){
        $fields = $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        // check for email
        $user = User::where('email',$fields['email'])->first();

        if(!$user || !password_verify($fields['password'],$user->password)){
            return response(['message'=>'Bad Credentials']);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    function info(){
        $userdata = User::where('id',Auth::user()->id)->get();
        if($userdata->count() > 0 ){
            $user = $userdata[0];
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role
            ];

            $response = $data;
        }else{
            $response = ['message' => 'User not found'];
        }

        return response($response);
    }


    // logout user
    public function logout(Request $request){
        auth()->user()->tokens()->delete();
        return response(['message'=>'logged out'],200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Mail\createUser;
use Inertia\Inertia;

class UserController extends BaseCrudController
{
    protected $model;
    public function __construct()
    {
        $this->model =  User::class;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = $this->model::with('roles')->get();
        $roles = Roles::all();
        return Inertia::render('User/Index', ['roles' => $roles, 'users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }
    /**
     * Display the specified resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'idRole' => 'required|exists:roles,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $data = $request->all();
        $password = random_int(10000, 99999);
        $data['password'] = Hash::make($password);
        User::create($data);
        $data = [
            'email' => $request->email,
            'password' => $password,
        ];
        Mail::to($request->email)->send(new createUser($data));
        $users = $this->model::with('roles')->get();
        return response()->json(['check' => true, 'data' => $users]);
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'avatar' => 'nullable|image|max:2048', // Kích thước tối đa 2MB
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Xử lý upload avatar
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $avatarUrl = Storage::url($avatarPath);
            \Log::info("Avatar path: " . $avatarUrl);
            $user = User::create([
             'name' => $request->name,
             'email' => $request->email,
             'password' => Hash::make($request->password),
             'avatar' => $avatarUrl,
             'idRole' => 2,
            ]);
        }else{
            $user = User::create([
             'name' => $request->name,
             'email' => $request->email,
             'password' => Hash::make($request->password),
             'avatar' => "/storage/avatars/c4RwwjBs5BM9vLufRAvwurArjQDAwoIrSeT8cgkh.png",
             'idRole' => 2,
            ]);
        }
         Mail::to($user->email)->send(new createUser($user));
        $users = User::with('roles')->get();
        return response()->json(['check' => true, 'data' => $users]);
        // Tạo người dùng mới
        return response()->json(['message' => 'User registered successfully!', 'user' => $user], 201);
    }
    public function registerForm()
    {
        return Inertia::render('User/Register');
    }
    public function loginForm()
    {
        return Inertia::render('User/Login');
    }
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('YourAppName')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 200);
    }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
    public function info()
    {
       return Inertia::render('User/Info');
    }
    // public function logout(Request $request)
    // {
    //     $request->user()->currentAccessToken()->delete();
    //     return response()->json(['message' => 'Logout successfully!'], 200);
    // }
    public function forgotPassForm()
    {
        return Inertia::render('User/Forgot');
    }

    public function resetPassForm()
    {
        return Inertia::render('User/ResetPass');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['status' => 'Link reset mật khẩu đã được gửi đến email.'])
            : response()->json(['email' => 'Không tìm thấy email.'], 404);
    }

    // Đặt lại password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['status' => 'Mật khẩu đã được đặt lại thành công.'])
            : response()->json(['email' => 'Link reset không hợp lệ hoặc đã hết hạn.'], 404);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email|unique:users,email',
            'idRole' => 'exists:roles,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $data = $request->all();
        if ($request->has('status')) {
            $old = User::find($id)->value('status');
            if ($old == 0) {
                $new = 1;
            } else {
                $new = 0;
            }
            $data['status'] = $new;
        }
        User::findOrFail($id)->update($data);
        $users = User::with('roles')->get();
        return response()->json(['check' => true, 'data' => $users]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($identifier)
    {
        User::where('id', $identifier)->delete();
        $data = $this->model::with('roles')->get();
        return response()->json(['check' => true, 'data' => $data], 200);
    }
}

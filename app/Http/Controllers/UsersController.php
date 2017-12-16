<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Auth;
use Mail;


class UsersController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth', [
			'expect' => ['show', 'create', 'store', 'index', 'confirmEmail']
		]);

		$this->middleware('guest', [
			'only' => ['create']
		]);
	}

	public function index()
	{
		$users = User::paginate(10);
		return view('users.index', compact('users'));
	}


    public function create()
    {
    	return view('users.create');
    }

    public function show(User $user)
    {
        $statuses = $user->statuses()->orderBy('created_at', 'desc')->paginate(30);
    	return view('users.show', compact('user', 'statuses'));
    }

    public function store(Request $request)
    {
       $credentials = $this->validate($request, [
           'email' => 'required|email|max:255',
           'password' => 'required'
       ]);

       if (Auth::attempt($credentials, $request->has('remember'))) {
           if(Auth::user()->activated) {
               session()->flash('success', '欢迎回来！');
               return redirect()->intended(route('users.show', [Auth::user()]));
           } else {
               Auth::logout();
               session()->flash('warning', '你的账号未激活，请检查邮箱中的注册邮件进行激活。');
               return redirect('/');
           }
       } else {
           session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
           return redirect()->back();
       }
    }

    public function edit(User $user)
    {
    	$this->authorize('update', $user);
    	return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
    	$this->validate($request, [
    		'name' => 'required|max:50',
    		'password' => 'nullable|confirmed|min:6'
    	]);

    	$this->authorize('update', $user);

    	$data = [];
    	$data['name'] = $request->name;
    	if($request->password){
    		$data['password'] = bcrypt($request->password);
    	}
    	$user->update($data);

    	/*
    	$user->update([
    		'name' => $request->name;
    		'password' => $request->bcrypt($request->password),
    	]);
    	*/
    	session()->flash('success', '个人资料更新成功！');
    	return redirect()->route('users.show', $user->id);
    }

    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }

    protected function sendEmailConfrimationTo($user)
    {
        $view = "emails.confirm";
        $data = compact('user');
        $from = "aufree@yousails.com";
        $name = "Aufree";
        $to = $user->email;
        $subject = "感谢注册 Sample应用！请确认邮箱";

        Mail::send($view, $data, function($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });
    }

    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user = save();

        Auth::login($user);
        session()->flash('success', '恭喜，激活成功');
        return redirect()->route('users.show', [$user]);
    }



}

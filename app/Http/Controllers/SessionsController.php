<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use Illuminate\Foundation\Auth\Login;
use Auth;
class SessionsController extends Controller
{
	public function __construct()
	{
		$this->middleware('guest', [
			'only' => ['create']
		]);
	}

    public function create()
    {
    	return view('sessions.create');
    }

    public function store(Request $request)
    {
    	$credentials = $this->validate($request, [
    		'email' => 'required|email|max:255',
    		'password' => 'required'
    	]);

    	if(Auth::attempt($credentials, $request->has('remember'))){
            if(Auth::user()->activated){
            //登陆成功后操作
                session()->flash('success', '欢迎回来');
                return redirect()->intended(route('users.show', [Auth::user()]));   
            } else {
                Auth::logout();
                session()->flash('warning', '您的账号为激活，请去邮箱检查');
                return redirect('/');
            }
    	} else {
    		//登陆失败后操作
           session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
           return redirect()->back();
    		
    	}

    	return;
    }


    public function destroy()
    {
    	Auth::logout();
    	session()->flash('success', '您已成功退出!');
    	return redirect('login');
    }
}

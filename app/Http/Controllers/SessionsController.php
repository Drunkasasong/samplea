<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Auth;

class SessionsController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth', [
			'except' => ['show', 'create', 'store']
		]);
		
		$this->middleware('guest', [
			'only' => ['create']
		]);
	}
    public function create()
    {
        return view('sessions.create');
    }

    // 登陆验证
    public function store(Request $request)
    {
        $credentials = $this->validate($request, [
        	'email' => 'required|email|max:225',
        	'password' => 'required'
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            if (Auth::user()->activated) {
            	// 登陆成功后的相关操作
            	session()->flash('success', '欢迎回来');
            	return redirect()->intended(route('users.show', [Auth::user()]));
            } else {
                Auth::logout();
                session()->flash('warning', '你的账号未激活，请检查邮箱中的注册邮件进行激活。');
                return redirect('/');
            }
        } else {
        	// 登陆失败后台相关操作
        	session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
        	return redirect()->back();
        }
    }

    // 退出登录
    public function destroy()
    {
    	Auth::logout();
    	session()->flash('success', '你已成功退出');
    	return redirect('login');
    }
}

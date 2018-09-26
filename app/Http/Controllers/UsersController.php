<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Auth;
use Mail;

class UsersController extends Controller
{
	// 身份验证（Auth）中间件来过滤未登录用户的 edit, update 动作
	public function __construct()
	{
		$this->middleware('auth', [
			'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
		]);
	}
	public function index()
	{
		$users = User::paginate(10);
		return view('users.index', compact('users'));
	}
    // 注册页面
    public function create()
    {
        return view('users.create');
    }
    // 用户数据显示
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }
    // 注册处理
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $this->sendEmailConfirmationTo($user);
        
        session()->flash('success', '验证邮箱已经发送到你的注册邮箱上，请注意查收。');
        
        return redirect('/');
    }

    // 编辑
    public function edit(User $user)
    {
    	$this->authorize('update', $user);
    	return view('users.edit', compact('user'));
    }

    // 更新处理
    public function update(User $user, Request $request)
    {
    	$this->validate($request, [
    		'name' => 'required|max:50',
    		'password' => 'nullable|confirmed|min:6'
    	]);

    	$this->authorize('update', $user);

    	$data = [];
    	$data['name'] = $request->name;
    	if ($request->password) {
    		$data['password'] = bcrypt($request->password);
    	}

    	$user->update($data);

    	session()->flash('success', '个人资料更新成功！');

    	return redirect()->route('users.show', $user->id);
    }

    // 删除
    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }

    // 激活邮件方法
    public function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        // $from = 'aufree@yousails.com';
        // $name = 'Aufree';
        $to = $user->email;
        $subject = '感谢注册 Sample 应用！请确认你的邮箱。';

        Mail::send($view, $data, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }

    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::logout($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', [$user]);
    }
}

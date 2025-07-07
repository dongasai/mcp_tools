<?php

namespace App\Modules\UserAdmin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class UserAdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 检查用户是否已登录
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                    'message' => '请先登录',
                ], 401);
            }
            
            return redirect()->route('login')->with('error', '请先登录');
        }
        
        $user = Auth::user();
        
        // 检查用户状态
        if ($user->status !== 'active') {
            Auth::logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Account inactive',
                    'message' => '账户已被禁用',
                ], 403);
            }
            
            return redirect()->route('login')->with('error', '账户已被禁用');
        }
        
        // 检查邮箱验证状态（如果需要）
        if (config('user.registration.email_verification_required', true) && !$user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Email not verified',
                    'message' => '请先验证邮箱',
                ], 403);
            }
            
            return redirect()->route('verification.notice')->with('error', '请先验证邮箱');
        }
        
        return $next($request);
    }
}

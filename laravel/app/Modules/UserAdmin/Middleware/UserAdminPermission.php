<?php

namespace App\Modules\UserAdmin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class UserAdminPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permission = null)
    {
        $user = Auth::user();
        
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                    'message' => '请先登录',
                ], 401);
            }
            
            return redirect()->route('login');
        }
        
        // 如果没有指定权限，则只检查登录状态
        if (!$permission) {
            return $next($request);
        }
        
        // 检查用户权限
        if (!$this->hasPermission($user, $permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Forbidden',
                    'message' => '权限不足',
                ], 403);
            }
            
            return redirect()->back()->with('error', '权限不足');
        }
        
        return $next($request);
    }
    
    /**
     * 检查用户是否有指定权限
     */
    protected function hasPermission($user, string $permission): bool
    {
        // 超级管理员拥有所有权限
        if ($user->role === 'super_admin') {
            return true;
        }
        
        // 获取用户角色的权限配置
        $rolePermissions = config("user.permissions.{$user->role}", []);
        
        // 检查是否有该权限
        return in_array($permission, $rolePermissions);
    }
    
    /**
     * 检查用户是否拥有资源
     */
    protected function ownsResource($user, $resource): bool
    {
        if (!$resource) {
            return false;
        }
        
        // 检查资源是否属于用户
        if (method_exists($resource, 'belongsToUser')) {
            return $resource->belongsToUser($user);
        }
        
        // 检查user_id字段
        if (isset($resource->user_id)) {
            return $resource->user_id === $user->id;
        }
        
        // 检查owner_id字段
        if (isset($resource->owner_id)) {
            return $resource->owner_id === $user->id;
        }
        
        return false;
    }
}

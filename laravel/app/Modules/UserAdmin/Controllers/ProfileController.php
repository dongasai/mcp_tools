<?php

namespace App\Modules\UserAdmin\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * 显示个人资料页面
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        
        return view('user-admin.profile.index', compact('user'));
    }

    /**
     * 更新个人资料
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        // 处理头像上传
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $avatarPath;
        }

        $user->update($updateData);

        return redirect()->route('user-admin.profile.index')
            ->with('success', '个人资料更新成功！');
    }

    /**
     * 显示密码修改页面
     */
    public function password(Request $request): View
    {
        return view('user-admin.profile.password');
    }

    /**
     * 更新密码
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('user-admin.profile.password')
            ->with('success', '密码更新成功！');
    }

    /**
     * 显示设置页面
     */
    public function settings(Request $request): View
    {
        $user = auth()->user();
        
        return view('user-admin.profile.settings', compact('user'));
    }

    /**
     * 更新设置
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'timezone' => 'required|string|max:100',
            'language' => 'required|string|max:10',
            'notifications' => 'nullable|array',
            'preferences' => 'nullable|array',
        ]);

        $settings = [
            'timezone' => $validated['timezone'],
            'language' => $validated['language'],
            'notifications' => $validated['notifications'] ?? [],
            'preferences' => $validated['preferences'] ?? [],
        ];

        $user->update(['settings' => $settings]);

        return redirect()->route('user-admin.profile.settings')
            ->with('success', '设置更新成功！');
    }
}

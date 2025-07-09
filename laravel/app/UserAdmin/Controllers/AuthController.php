<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseAuthController
{
    /**
     * Handle a login request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function postLogin(Request $request)
    {
        $credentials = $request->only(['username', 'password']);
        $remember = $request->get('remember', false);

        // 将username字段映射到email字段进行认证
        $authCredentials = [
            'email' => $credentials['username'],
            'password' => $credentials['password'],
        ];

        $validator = Validator::make($authCredentials, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorsResponse($validator);
        }

        if (Auth::guard(config('admin.auth.guard'))->attempt($authCredentials, $remember)) {
            return $this->sendLoginResponse($request);
        }

        return back()->withInput()->withErrors([
            'username' => trans('admin.login_failed'),
        ]);
    }
}

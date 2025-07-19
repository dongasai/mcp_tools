<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseAuthController
{
    /**
     * 重写用户名字段，使用email进行认证
     *
     * @return string
     */
    protected function username()
    {
        return 'email';
    }

    /**
     * 重写登录处理方法，将username字段映射为email
     *
     * @param Request $request
     * @return mixed
     */
    public function postLogin(Request $request)
    {
        // 将username字段映射为email字段
        if ($request->has('username')) {
            $request->merge(['email' => $request->input('username')]);
        }

        return parent::postLogin($request);
    }
}

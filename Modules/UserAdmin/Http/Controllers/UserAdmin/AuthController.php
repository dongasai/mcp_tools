<?php

namespace Modules\UserAdmin\Http\Controllers\UserAdmin;

use Dcat\Admin\Http\Controllers\AuthController as BaseAuthController;
use Dcat\Admin\Form;
use Dcat\Admin\Http\Repositories\Administrator;
use Illuminate\Http\Request;

class AuthController extends BaseAuthController
{
    /**
     * User logout.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getLogout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        // 使用当前应用的配置生成登录URL
        $path = config('user-admin.route.prefix') . '/auth/login';
        $path = '/' . trim($path, '/');

        if ($request->pjax()) {
            return "<script>location.href = '$path';</script>";
        }

        return redirect($path);
    }
    
    /**
     * Get the post login redirect path.
     *
     * @return string
     */
    protected function getRedirectPath()
    {
        return $this->redirectTo ?: '/' . trim(config('user-admin.route.prefix'), '/') . '/';
    }
    
    /**
     * User setting form.
     *
     * @return Form
     */
    protected function settingForm()
    {
        return new Form(new Administrator(), function (Form $form) {
            // 使用当前应用的配置生成设置URL
            $path = '/' . trim(config('user-admin.route.prefix'), '/') . '/auth/setting';
            $form->action($path);

            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->disableViewCheck();

            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
                $tools->disableDelete();
            });

            $form->display('username', trans('admin.username'));
            $form->text('name', trans('admin.name'))->required();
            $form->image('avatar', trans('admin.avatar'))->autoUpload();

            $form->password('old_password', trans('admin.old_password'));
            $form->password('password', trans('admin.password'))->minLength(5);
            $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

            $form->ignore(['password_confirmation', 'old_password']);

            $form->saving(function (Form $form) {
                if ($form->password && $form->model()->password != $form->password) {
                    $form->password = bcrypt($form->password);
                }

                if (! $form->password) {
                    $form->deleteInput('password');
                }
            });
        });
    }
}
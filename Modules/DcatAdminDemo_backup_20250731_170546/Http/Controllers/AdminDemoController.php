<?php

namespace DcatAdminDemo\Http\Controllers;

class AdminDemoController extends BaseController
{
    /**
     * 显示模块主页面
     */
    public function index()
    {
        return view('madmindemo::index');
    }
    
    /**
     * 显示详情页面
     */
    public function show($id)
    {
        return view('madmindemo::show', compact('id'));
    }
}
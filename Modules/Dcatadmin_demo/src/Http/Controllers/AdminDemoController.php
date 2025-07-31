<?php

namespace DcatAdminDemo\Http\Controllers;

use DcatAdminDemo\Models\Demo;

class AdminDemoController extends BaseController
{
    /**
     * 显示模块主页面
     */
    public function index()
    {
        $demos = Demo::paginate(10);
        return view('madmindemo::index', compact('demos'));
    }
    
    /**
     * 显示详情页面
     */
    public function show($id)
    {
        $demo = Demo::findOrFail($id);
        return view('madmindemo::show', compact('demo'));
    }
}
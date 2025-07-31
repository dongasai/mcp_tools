<?php

namespace DcatAdminDemo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Demo extends Model
{
    use HasFactory;
    
    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'dcatadmin2demo_demos';
    
    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'status',
    ];
    
    /**
     * 属性类型转换
     *
     * @var array
     */
    protected $casts = [
        'status' => 'boolean',
    ];
    
    /**
     * 模型的默认属性值
     *
     * @var array
     */
    protected $attributes = [
        'status' => true,
    ];
}
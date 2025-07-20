<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * 可批量赋值的属性
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'avatar',
    ];

    /**
     * 序列化时应隐藏的属性
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 获取应该被转换的属性
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ===== DcatAdmin 兼容方法 =====

    /**
     * 获取认证标识符名称（dcat-admin需要）
     * 使用username字段进行认证
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * 获取用户名（dcat-admin需要）
     * 返回username字段
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * 获取用户头像（dcat-admin需要）
     */
    public function getAvatar(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // 使用Gravatar作为默认头像
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
    }

    /**
     * 获取用户名称（dcat-admin需要）
     */
    public function getName(): string
    {
        return $this->name ?: $this->email;
    }
}

<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class User extends Validate
{

    protected $rule =   [
        'code'  => 'require',
    ];

    protected $message  =   [
        'code.require' => 'code is required!',
    ];

    protected $scene = [
		"login" => ["code"]
	];
}


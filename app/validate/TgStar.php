<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class TgStar extends Validate
{

    protected $rule =   [
        'transaction_id'  => 'require',
    ];

    protected $message  =   [
        'transaction_id.require' => 'transaction_id is required!',
    ];

    protected $scene = [
		"checkPayment" => ["transaction_id"]
	];
}


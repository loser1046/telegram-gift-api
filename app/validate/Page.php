<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class Page extends Validate
{

    protected $rule =   [
        'page'  => 'number|min:1',
        'limit'  => 'number|between:1,120',
    ];

    protected $message  =   [
        'page.number' => 'validate_page.page_error',
        'page.min' => 'validate_page.page_error',
        'limit.number' => 'validate_page.limit_number',
        'limit.between' => 'validate_page.limit_between',
    ];
}

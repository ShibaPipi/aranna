<?php

namespace App\Http\Controllers\Wechat;

class HomeController extends BaseController
{
    protected $middlewareOnly = [];

    public function redirectShareUrl()
    {
        $type = $this->verifyString('type', 'groupon');
        $id = $this->verifyId('id');

        switch ($type) {
            case 'groupon':
                return redirect()->to(env('H5_URL').'/#/items/detail/'.$id);
            case 'goods':
                return redirect()->to(env('H5_URL').'/#/items/detail/'.$id);
        }
        return redirect()->to(env('H5_URL').'/#/items/detail/'.$id);
    }
}

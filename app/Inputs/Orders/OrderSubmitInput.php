<?php
/**
 *
 * Created By 皮神
 * Date: 2021/2/8
 */

namespace App\Inputs\Orders;

use App\Inputs\Input;

class OrderSubmitInput extends Input
{
    public $cartId;
    public $addressId;
    public $couponId;
    public $couponUserId;
    public $message;
    public $grouponRuleId;
    public $grouponLinkId;

    public function rules(): array
    {
        return [
            'cartId' => 'required|integer',
            'addressId' => 'required|integer',
            'couponId' => 'required|integer',
            'couponUserId' => 'integer',
            'message' => 'string',
            'grouponRuleId' => 'integer',
            'grouponLinkId' => 'integer',
        ];
    }
}

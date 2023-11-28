<?php

namespace App\Models;

class Coupon extends Model
{
    protected $connection = 'default';
    protected $table = 'coupon';

    public function expire(): string
    {
        return date('Y-m-d H:i:s', $this->attributes['expire']);
    }

    public function order($shop): bool
    {
        if ($this->attributes['shop'] == '') {
            return true;
        }

        $shop_array = explode(',', $this->attributes['shop']);

        return in_array($shop, $shop_array);
    }
}

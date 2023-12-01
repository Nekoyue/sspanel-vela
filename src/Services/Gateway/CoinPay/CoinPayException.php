<?php

namespace App\Services\Gateway\CoinPay;

class CoinPayException extends \Exception {
    public function errorMessage(): string
    {
        return $this->getMessage();
    }
}

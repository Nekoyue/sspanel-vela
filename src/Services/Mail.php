<?php

namespace App\Services;

/***
 * Mail Service
 */

use App\Models\Setting;
use App\Services\Mail\Mailgun;
use App\Services\Mail\Ses;
use App\Services\Mail\Smtp;
use App\Services\Mail\SendGrid;
use App\Services\Mail\NullMail;
use Smarty;

class Mail
{
    /**
     * @return Mailgun|NullMail|SendGrid|Ses|Smtp|null
     */
    public static function getClient(): Mailgun|Smtp|Ses|NullMail|SendGrid|null
    {
        $driver = Setting::obtain('mail_driver');
        switch ($driver) {
            case 'mailgun':
                return new Mailgun();
            case 'ses':
                return new Ses();
            case 'smtp':
                return new Smtp();
            case 'sendgrid':
                return new SendGrid();
            default:
                return new NullMail();
        }
    }

    /**
     * @param $template
     * @param $ary
     * @return mixed
     * @throws \SmartyException
     */
    public static function genHtml($template, $ary): mixed
    {
        $smarty = new smarty();
        $smarty->settemplatedir(BASE_PATH . '/resources/email/');
        $smarty->setcompiledir(BASE_PATH . '/storage/framework/smarty/compile/');
        $smarty->setcachedir(BASE_PATH . '/storage/framework/smarty/cache/');
        // add config
        $smarty->assign('config', Config::getPublicConfig());
        foreach ($ary as $key => $value) {
            $smarty->assign($key, $value);
        }
        return $smarty->fetch($template);
    }

    /**
     * @param $to
     * @param $subject
     * @param $template
     * @param array $ary
     * @param array $files
     * @return void
     * @throws \Exception
     */
    public static function send($to, $subject, $template, array $ary = [], array $files = []): void
    {
        $text = self::genHtml($template, $ary);
        self::getClient()->send($to, $subject, $text, $files);
    }
}

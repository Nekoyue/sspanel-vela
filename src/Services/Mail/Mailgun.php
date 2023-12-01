<?php

namespace App\Services\Mail;

use App\Models\Setting;
use App\Services\Config;
use Mailgun\Mailgun as MailgunService;
use Psr\Http\Client\ClientExceptionInterface;

class Mailgun extends Base
{
    private array $config;
    private MailgunService $mg;
    private mixed $domain;
    private mixed $sender;

    public function __construct()
    {
        $this->config = $this->getConfig();
        $this->mg = MailgunService::create($this->config['key']);
        $this->domain = $this->config['domain'];
        $this->sender = $this->config['sender'];
    }

    public function getConfig(): array
    {
        $configs = Setting::getClass('mailgun');

        return [
            'key' => $configs['mailgun_key'],
            'domain' => $configs['mailgun_domain'],
            'sender' => $configs['mailgun_sender']
        ];
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function send($to, $subject, $text, $files): void
    {
        $inline = array();
        foreach ($files as $file) {
            $inline[] = array('filePath' => $file, 'filename' => basename($file));
        }
        if (count($inline) == 0) {
            $this->mg->messages()->send($this->domain, [
                    'from' => $this->sender,
                    'to' => $to,
                    'subject' => $subject,
                    'html' => $text
                ]);
        } else {
            $this->mg->messages()->send($this->domain, [
                    'from' => $this->sender,
                    'to' => $to,
                    'subject' => $subject,
                    'html' => $text,
                    'inline' => $inline
                ]);
        }
    }
}

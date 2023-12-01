<?php

namespace App\Utils;

class AppURI
{
    public static function getSurgeURI(array $item, int $version): ?string
    {
        $return = null;
        switch ($item['type']) {
            case 'trojan':
                $return = ($item['remark'] . ' = trojan, ' . $item['address'] . ', ' . $item['port'] . ', password=' . $item['passwd']) . ", sni=" . $item['host'];
                if ($item['allow_insecure']) {
                    $return .= ', skip-cert-verify=true';
                }
                break;
        }
        return $return;
    }

    public static function getQuantumultXURI(array $item): ?string
    {
        $return = null;
        switch ($item['type']) {
            case 'trojan':
                // ;trojan=example.com:443, password=pwd, over-tls=true, tls13=true, tls-verification=true, fast-open=false, udp-relay=false, tag=trojan-tls-01
                $return  = ('trojan=' . $item['address'] . ':' . $item['port'] . ', password=' . $item['passwd'] . ', tls-host=' . $item['host']);
                $return .= ', over-tls=true, tls13=true';
                if ($item['allow_insecure']) {
                    $return .= ', tls-verification=false';
                } else {
                    $return .= ', tls-verification=true';
                }
                $return .= (', tag=' . $item['remark']);
                break;
        }
        return $return;
    }


    public static function getClashURI(array $item): array
    {
        $return = null;
        switch ($item['type']) {
            case 'trojan':
                $return = [
                    'name'        => $item['remark'],
                    'type'        => 'trojan',
                    'server'      => $item['address'],
                    'port'        => $item['port'],
                    'password'    => $item['passwd'],
                    'sni'         => $item['host'],
                    'udp'         => true
                ];
                if ($item['net'] == 'grpc') {
                    $return['network'] = 'grpc';
                    $return['grpc-opts']['grpc-service-name'] = ($item['servicename'] != '' ? $item['servicename'] : "");
                }
                if ($item['allow_insecure'] == 'true') {
                    $return['skip-cert-verify'] = true;
                }
                break;
        }
        return $return;
    }

    public static function getShadowrocketURI(array $item): ?string
    {
        $return = null;
        switch ($item['type']) {
            case 'trojan':
                $return  = ('trojan://' . $item['passwd'] . '@' . $item['address'] . ':' . $item['port'] . '?');
                if ($item['allow_insecure']) {
                    $return .= 'allowInsecure=1&';
                }
                $return .= ('peer=' . $item['host'] . '#' . rawurlencode($item['remark']));
                break;
        }
        return $return;
    }


    public static function getTrojanURI(array $item): ?string
    {
        $return = null;
        switch ($item['type']) {
            case 'trojan':
                $return  = ('trojan://' . $item['passwd'] . '@' . $item['address'] . ':' . $item['port']);
                $return .= ('?peer=' . $item['host'] . '&sni=' . $item['host']);
                if($item['tls'] == "xtls"){
                    $return .= ("&security=" . $item['tls'] . "&flow=" . $item['flow']);
                }
                $return .= ('#' . rawurlencode($item['remark']));
                break;
        }
        return $return;
    }

}

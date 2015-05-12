<?php

namespace Eva\EvaEngine\SDK;

// +----------------------------------------------------------------------
// | [wallstreetcn]
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 14-9-1 14:51
// +----------------------------------------------------------------------
// + SendCloudMailer.php
// +----------------------------------------------------------------------


class SendCloudMailer
{
    private $host;
    private $port;
    private $username;
    private $password;


    public static function newInstance()
    {
        return new SendCloudMailer();
    }

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @Deprecated
     * @param $port
     */
    public function setPort($port)
    {

    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function send(\Swift_Message $message)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, $this->host);
        // 不同于登录SendCloud站点的帐号，您需要登录后台创建发信子帐号，
        // 使用子帐号和密码才可以进行邮件的发送。
        $from = $message->getFrom();
        $to = '';
        foreach ($message->getTo() as $_mail => $_toName) {
            if ($to .= '') {
                $to .= ';';
            }
            $to .= $_mail;
        }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                array(
                    'api_user' => $this->username,
                    'api_key' => $this->password,
                    'from' => $this->username,
                    'fromname' => is_array($from) ? current($from) : $from,
                    'to' => $to,
                    'subject' => $message->getSubject(),
                    'html' => $message->getBody()
                )
            )
        );

        $result = curl_exec($ch);

        //请求失败
        if ($result === false) {
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);
        $ret = json_decode($result);
        //p($result);
        if ($ret->message != 'success') {
            throw new \Exception($result);
        }
        return $result;
    }
}

<?php

namespace PytoMVC\System\Helpers;

use PytoMVC\System\Http\Request;
use ReCaptcha\ReCaptcha as GoogleReCaptcha;

class ReCaptcha
{
    /**
     * @var \PytoMVC\System\Http\Request
     */
    protected $request;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     */
    private $siteKey;

    public function __construct(Request $request, $secret, $siteKey)
    {
        $this->request = $request;

        $this->secret = $secret;

        $this->siteKey = $siteKey;
    }

    /**
     * Check if the captcha is valid
     * 
     * @param  string      $value 
     * @param  string|null $clientIp 
     * @return bool
     */
    public function verify($value, $clientIp = null)
    {
        if (empty($value)) {
            return false;
        }

        if (is_null($clientIp)) {
            $clientIp = $this->request->clientIp();
        }

        $response = (new GoogleReCaptcha($this->secret))->verify($value, $clientIp);

        return $response->isSuccess();
    }
}

<?php

namespace App\Helpers;


use Illuminate\Support\Facades\Log;

class Recaptcha
{

    /**
     * @var string
     */
    private $remote_ip;

    /**
     * @var string
     */
    private $verify_url;

    /**
     * @var object
     */
    private $result;


    /**
     * Recaptcha constructor.
     */
    public function __construct()
    {
        $this->remote_ip  = $_SERVER['REMOTE_ADDR'] ?? request()->ip() ?? '127.0.0.1';
        $this->verify_url = config('services.recaptcha.verify_url');
    }


    /**
     * @param array $data
     *
     * @return bool|mixed
     */
    public function check(array $data)
    {
        if (app()->environment(['local', 'testing']) && config('services.recaptcha.bypass_local', true)) {
            $this->result = (object)['success' => true, 'score' => 0.9, 'bypassed' => true];
            return $this;
        }

        if (empty($data['recaptcha'])) {
            $this->result = (object)['success' => false, 'score' => 0.0, 'error-codes' => ['missing-token']];
            return $this;
        }

        $_data   = $this->setContentData($data['recaptcha']);
        $options = $this->setOptions($_data);

        $context = stream_context_create($options);
        $result  = @file_get_contents($this->verify_url, false, $context);

        if ($result === false) {
            Log::error('reCAPTCHA stream failure', ['error' => error_get_last()]);
            $this->result = (object)['success' => false, 'score' => 0.0, 'error-codes' => ['stream-failure']];
            return $this;
        }

        $this->result = json_decode($result) ?: (object)['success' => false, 'score' => 0.0, 'error-codes' => ['invalid-json']];
        return $this;
    }


    /**
     * @return bool
     */
    public function ok(?string $expectedAction = null, ?string $expectedHostname = null)
    {
        if (! isset($this->result)
            || ($this->result->success ?? false) !== true
            || (float) ($this->result->score ?? 0) < 0.3) {
            return false;
        }

        if (! empty($this->result->bypassed)) {
            return true;
        }

        if ($expectedAction !== null
            && (! isset($this->result->action) || ! hash_equals($expectedAction, (string) $this->result->action))) {
            return false;
        }

        if ($expectedHostname !== null) {
            $actualHostname = strtolower(rtrim((string) ($this->result->hostname ?? ''), '.'));
            $expectedHostname = strtolower(rtrim($expectedHostname, '.'));

            if ($actualHostname === '' || ! hash_equals($expectedHostname, $actualHostname)) {
                return false;
            }
        }

        return true;
    }


    /**
     * @param string $recaptcha
     *
     * @return array
     */
    private function setContentData(string $recaptcha)
    {
        return [
            'secret'   => config('services.recaptcha.secret'),
            'response' => $recaptcha,
            'remoteip' => $this->remote_ip
        ];
    }


    /**
     * @param array $data
     *
     * @return array[]
     */
    private function setOptions(array $data): array
    {
        return [
            'http' => [
                'method'           => 'POST',
                'header'           => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: AGMedia/RecaptchaVerifier\r\nConnection: close\r\n",
                'content'          => http_build_query($data, '', '&'),
                'timeout'          => 10,
                'protocol_version' => 1.1, // izbjegni neke HTTP/2/TLS edge-caseove
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
                'SNI_enabled'      => true,
                // Ako PHP/host ne nalazi CA bundle, ručno postavi:
                // 'cafile' => '/etc/ssl/certs/ca-certificates.crt',
            ],
        ];
    }

}

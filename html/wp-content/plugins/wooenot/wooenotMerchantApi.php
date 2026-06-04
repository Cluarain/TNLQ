<?php

/**
 * Class EnotMerchantAPI
 *
 */

class EnotMerchantAPI
{
    private $_api_url;
    private $_secretWord;
    private $_error = '';

    /**
     * Constructor
     *
     * @param string $secretWord
     */
    public function __construct(string $secretWord)
    {
        $this->_api_url = 'https://api.mivion.com/';
        $this->_secretWord = $secretWord;
    }

    /**
     * Builds a query string and call sendRequest method.
     * Could be used to custom API call method.
     *
     * @param string $path API method name
     * @param mixed $args query params
     *
     * @return string|bool
     * @throws HttpException
     */
    public function buildQuery(string $path, $args, $type = 'get')
    {
        $url = $this->_api_url;
        $url = $this->_combineUrl($url, $path);

        return $this->_sendRequest($url, $args, $type);
    }

    /**
     * Combines parts of URL. Simply gets all parameters and puts '/' between
     *
     * @return string
     */
    private function _combineUrl(): string
    {
        $args = func_get_args();
        $url = '';
        foreach ($args as $arg) {
            if (is_string($arg)) {
                if ($arg[strlen($arg) - 1] !== '/') $arg .= '/';
                $url .= $arg;
            }
        }
        return $url;
    }

    /**
     * Main method. Call API with params
     *
     * @param string $api_url API Url
     * @param mixed $args API params
     *
     * @return string|bool
     * @throws HttpException
     */
    private function _sendRequest(string $api_url, $args, $type)
    {

        $this->_error = '';

        $args = is_array($args) ? json_encode($args) : $args;

        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $api_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            if ($type == 'get') {
                curl_setopt($curl, CURLOPT_POST, 0);
            } else {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
            }
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'x-api-key:' . $this->_secretWord
            ));

            $out = curl_exec($curl);

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($httpCode === 200)
                return $out;

            file_put_contents(__DIR__ . '/wooenot_error.log', date('Y-m-d H:i:s') . ' - sendRequest # enot error: status -' . $httpCode . 'msg -' . $out . "\r\n", FILE_APPEND);
            return false;
        } else {
            file_put_contents(__DIR__ . '/wooenot_error.log', date('Y-m-d H:i:s') . ' - sendRequest # enot: Can not create connection to ' . $api_url . ' with args '
                . $args . "\r\n", FILE_APPEND);
            throw new HttpException(
                'Can not create connection to ' . $api_url . ' with args '
                    . $args,
                404
            );
        }
    }
}

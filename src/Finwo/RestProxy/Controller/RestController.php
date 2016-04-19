<?php

namespace Finwo\RestProxy\Controller;

use Finwo\Framework\RestController as BaseController;
use Finwo\Framework\Route;
use OAuth2\Client;

class RestController extends BaseController
{
    protected function get_furl($url)
    {
        $furl = false;

        // First check response headers
        $headers = get_headers($url);

        // Test for 301 or 302
        if(preg_match('/^HTTP\/\d\.\d\s+(301|302)/',$headers[0]))
        {
            foreach($headers as $value)
            {
                if(substr(strtolower($value), 0, 9) == "location:")
                {
                    $furl = trim(substr($value, 9, strlen($value)));
                }
            }
        }
        // Set final URL
        $furl = ($furl) ? $furl : $url;

        return $furl;
    }

    public function getAction($resource = '', $baseuri = '', $key = '', $secret = '', $query = '', $method = 'get', $header = array())
    {
        // Kickstart client
        $client = new Client($key, $secret);

        // Construct uri
        $uri = $this->get_furl($baseuri . '/' . $resource);

        // Unset a list of values, we won't send them directly
        $remove = array('resource','baseuri','key','secret','method','header');
        foreach($remove as $key) unset($query[$key]);

        // Set options for curl
        $client->setCurlOptions(array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => sprintf('PHP/%s (%s)', PHP_VERSION, PHP_OS),
            CURLOPT_REFERER        => 'http://finwo.nl/',
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        ));

        return $client->fetch($uri, $query, strtoupper($method), $header);
    }
}
<?php

namespace Fusio\Adapter\Http\Component;

use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\RequestInterface;

class ArgumentHelper
{
    /**
     * Specify the arguments in the url
     *
     * @param string $url
     * @param RequestInterface $request
     * @return string
     * @throws ConfigurationException
     */
    public static function specifyArguments(string $url, RequestInterface $request): string
    {
        $url = self::specifyHost($url, $request);
        $url = self::specifyPath($url, $request);
        return self::postProcess($url, $request);
    }

    /**
     * Replace url host with the service-info registered
     * e.g.
     *   http://{{BASE_URL}} -> http://api.example.com
     *   http://{{BASE_URL}}:8080 -> http://api.example.com:8080
     *
     * @param string $url
     * @param RequestInterface $request
     * @return string
     * @throws ConfigurationException
     */
    private static function specifyHost(string           $url,
                                        RequestInterface $request): string
    {
        // check arguments
        if (empty($url)) {
            return '';
        }
        // replace the host
        $pattern = '/:\/\/{{([a-zA-Z0-9_\-]*)}}/';
        if (!preg_match($pattern, $url, $match)) {
            return $url;
        }
        $key = $match[1];
        $value = ServiceRegister::getInstance()->queryServiceUri($key);
        return str_replace('{{' . $key . '}}', $value, $url);
    }

    /**
     * Replace url parameters with the arguments from the request
     * e.g.
     *   http://api.example.com/{{PARAM_1}}//{{PARAM_2}} -> http://api.example.com/foo/bar
     *
     * @param string $url
     * @param RequestInterface $request
     * @return string
     * @throws ConfigurationException
     */
    private static function specifyPath(string           $url,
                                        RequestInterface $request): string
    {
        // check arguments
        if (empty($url)) {
            return '';
        }
        // no arguments setting
        $argumentDict = $request->getArguments();
        if (empty($argumentDict)) {
            return $url;
        }
        // replace the arguments
        $pattern = '/{{([a-zA-Z0-9_\-]*)}}/';
        if (!preg_match_all($pattern, $url, $matches)) {
            return $url;
        }
        foreach ($matches[1] as $key) {
            if (!isset($argumentDict[$key])) {
                throw new ConfigurationException('No argument configured for ' . $key);
            }
            $value = $argumentDict[$key];
            $url = str_replace('{{' . $key . '}}', $value, $url);
        }
        return $url;
    }


    /**
     * Post process the url
     * should be called after specifyPath
     *
     * @param string $url
     * @param RequestInterface $request
     * @return string
     */
    private static function postProcess(string           $url,
                                        RequestInterface $request): string
    {
        // check arguments
        if (empty($url)) {
            return '';
        }
        // post process the url
        $originUrl = $request->getContext()->getRequest()->getUri()->getPath();
        if (!str_ends_with($originUrl, '/') && str_ends_with($url, '/')) {
            $url = substr($url, 0, -1);
        }
        return $url;
    }
}
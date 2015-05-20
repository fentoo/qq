<?php
/**
 * QQ strategy for Opauth
 * based on http://wiki.open.qq.com/wiki/website/OAuth2.0%E7%AE%80%E4%BB%8B
 *
 * More information on Opauth: http://opauth.org
 *
 * @link         http://opauth.org
 * @package      Opauth.QQStrategy
 * @license      MIT License
 */

class QQStrategy extends OpauthStrategy{

    /**
     * Compulsory config keys, listed as unassociative arrays
     */
    public $expects = array('key', 'secret');

    /**
     * Optional config keys with respective default values, listed as associative arrays
     */
    public $defaults = array(
        'redirect_uri' => '{complete_url_to_strategy}qq_callback'
    );

    /**
     * Auth request
     */
    public function request(){
        $url = 'https://graph.qq.com/oauth2.0/authorize';

        $params = array(
            'client_id' => $this->strategy['key'],
            'redirect_uri' => $this->strategy['redirect_uri'],
            'response_type' => 'code'
        );

        if (!empty($this->strategy['scope'])) $params['scope'] = $this->strategy['scope'];
        if (!empty($this->strategy['state'])) $params['state'] = $this->strategy['state'];
        if (!empty($this->strategy['display'])) $params['display'] = $this->strategy['display'];
        if (!empty($this->strategy['auth_type'])) $params['auth_type'] = $this->strategy['auth_type'];

        $this->clientGet($url, $params);
    }

    /**
     * Internal callback, after QQ's OAuth
     */
    public function qq_callback(){
        if (array_key_exists('code', $_GET) && !empty($_GET['code']) && empty($_GET['msg'])){
            $url = 'https://graph.qq.com/oauth2.0/token';

            $params = array(
                'client_id' =>$this->strategy['key'],
                'client_secret' => $this->strategy['secret'],
                'redirect_uri'=> $this->strategy['redirect_uri'],
                'code' => $_GET['code'],
                'grant_type' => 'authorization_code'
            );
            $response = $this->serverGet($url, $params);
            if (empty($response)){
                $error = array(
                    'code' => 'Get access token error',
                    'message' => 'Failed when attempting to get access token',
                    'raw' => array(
                        'headers' => $response
                    )
                );
                $this->errorCallback($error);
            }

            parse_str($response, $results);

            // Get user's openID
            $uid = $this->getOpenId($results['access_token']);

            $qqUser = $this->getUser($results['access_token'], $uid['client_id'], $uid['openid']);

            $this->auth = array(
                'uid' => $uid['openid'],
                    'info' => array(
                ),
                'credentials' => array(
                    'token' => $results['access_token'],
                    'expires' => date('c', time() + $results['expires_in'])
                ),
                'raw' => $qqUser
            );

            if (!empty($qqUser->nickname)) $this->auth['info']['name'] = $qqUser->nickname;
            if (!empty($qqUser->nickname)) $this->auth['info']['nickname'] = $qqUser->nickname;
            if (!empty($qqUser->figureurl_qq_2)) $this->auth['info']['image'] = $qqUser->figureurl_qq_2;
            if (!empty($qqUser->city)) $this->auth['info']['location'] = $qqUser->province .' '. $qqUser->city;

            $this->callback();
        }
        else
        {
            $error = array(
                'code' => $_GET['code'],
                'message' => $_GET['msg'],
                'raw' => $_GET
            );

            $this->errorCallback($error);
        }
    }

    private function getOpenId($access_token){
        $uid = $this->serverget('https://graph.qq.com/oauth2.0/me', array('access_token' => $access_token));

        if (!empty($uid)){
            $uid = str_replace("callback( ","",str_replace(" );","", $uid));
            return $this->simple_json_parser($uid);
        }
        else{
            $error = array(
                'code' => 'Get OpenID error',
                'message' => 'Failed when attempting to query for user OpenID',
                'raw' => array(
                    'access_token' => $access_token,
                    'headers' => $uid
                )
            );

            $this->errorCallback($error);
        }
    }

    private function getUser($access_token, $appId, $openId){
        $params = array(
            'oauth_consumer_key' => $appId,
            'access_token' => $access_token,
            'openid' => trim($openId)
        );

        $qqUser = $this->serverGet(
            'https://graph.qq.com/user/get_user_info',
            $params
        );

        if (!empty($qqUser)){
            $qqUser = json_decode($qqUser);
            if ($qqUser->ret == 0) {
                return $qqUser;
            } else {
                $error = array(
                    'code' => 'Get User error',
                    'message' => 'Failed when attempting to query for user information',
                    'raw' => array(
                        'access_token' => $access_token
                    )
                );
                $this->errorCallback($error);
            }
        }
        else{
            $error = array(
                'code' => 'Get User error',
                'message' => 'Failed when attempting to query for user information',
                'raw' => array(
                    'access_token' => $access_token
                )
            );

            $this->errorCallback($error);
        }
    }

    private function simple_json_parser($json){
        $json = str_replace("{","",str_replace("}","", $json));
        $jsonValue = explode(",", $json);
        $arr = array();
        foreach($jsonValue as $v){
            $jValue = explode(":", $v);
            $arr[str_replace('"',"", $jValue[0])] = (str_replace('"', "", $jValue[1]));
        }
        return $arr;
    }
}

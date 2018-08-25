<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

/*
 * This polyfill of hash_equals() is a modified edition of https://github.com/indigophp/hash-compat/tree/43a19f42093a0cd2d11874dff9d891027fc42214
 *
 * Copyright (c) 2015 Indigo Development Team
 * Released under the MIT license
 * https://github.com/indigophp/hash-compat/blob/43a19f42093a0cd2d11874dff9d891027fc42214/LICENSE
 */

/*
    Third Party Update by SuperSonic
   ====================================
        Copyright(c) 2018 Randy Chen.   http://randychen.tk/
    Version: 2.2
    More Information: 
        https://github.com/supersonictw/line-bot-sdk-php-tiny
*/

if (!function_exists('hash_equals')) {
    defined('USE_MB_STRING') or define('USE_MB_STRING', function_exists('mb_strlen'));

    function hash_equals($knownString, $userString) {
        $strlen = function ($string) {
            if (USE_MB_STRING) {
                return mb_strlen($string, '8bit');
            }

            return strlen($string);
        };

        // Compare string lengths
        if (($length = $strlen($knownString)) !== $strlen($userString)) {
            return false;
        }

        $diff = 0;

        // Calculate differences
        for ($i = 0; $i < $length; $i++) {
            $diff |= ord($knownString[$i]) ^ ord($userString[$i]);
        }
        return $diff === 0;
    }
}

class LINEAPI {
    public function __construct($channelAccessToken, $channelSecret) {
        $this->host = "https://api.line.me";
        $this->channelAccessToken = $channelAccessToken;
        $this->channelSecret = $channelSecret;
    }

    public function getProfile($userId) {
        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => implode("\r\n", $header),
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/profile/'.urlencode($userId), false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }else {
            return $response;
        }
    }

    public function getGroupMemberInfo($groupId, $userId) {
        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => implode("\r\n", $header),
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/group/'.urlencode($groupId).'/member/'.urlencode($userId), false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }else {
            return $response;
        }
    }

    public function getGroupMemberIds($groupId, $continuationToken = null) {
        if ($continuationToken != null) {
            $next = "?start=".$continuationToken;
        }else{
            $next = "";
        }

        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => implode("\r\n", $header),
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/group/'.urlencode($groupId).'/members/ids'.$next, false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }else {
            return $response;
        }
    }

    public function leaveGroup($groupId) {
        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => "[]",
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/group/'.urlencode($groupId).'/leave', false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }
    }

    public function getRoomMemberInfo($roomId, $userId) {
        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => implode("\r\n", $header),
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/room/'.urlencode($roomId).'/member/'.urlencode($userId), false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }else {
            return $response;
        }
    }

    public function getRoomMemberIds($roomId, $continuationToken = null) {
        if ($continuationToken != null) {
            $next = "?start=".$continuationToken;
        }else{
            $next = "";
        }

        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => implode("\r\n", $header),
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/room/'.urlencode($roomId).'/members/ids'.$next, false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }else {
            return $response;
        }
    }

    public function leaveRoom($roomId) {
        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => "[]",
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/room/'.urlencode($roomId).'/leave', false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }
    }

    public function parseEvents() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            error_log("Method not allowed");
            exit();
        }

        $entityBody = file_get_contents('php://input');

        if (strlen($entityBody) === 0) {
            http_response_code(400);
            error_log("Missing request body");
            exit();
        }

        if (!hash_equals($this->sign($entityBody), $_SERVER['HTTP_X_LINE_SIGNATURE'])) {
            http_response_code(400);
            error_log("Invalid signature value");
            exit();
        }

        $data = json_decode($entityBody, true);
        if (!isset($data['events'])) {
            http_response_code(400);
            error_log("Invalid request body: missing events property");
            exit();
        }
        return $data['events'];
    }

    public function replyMessage($replyToken, $message) {
        if (isset($message["type"])) {
            $messages = array($message); 
        }else{
            $messages = $message;
        }

        $header = array(
            "Content-Type: application/json",
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $content  = array(
            "replyToken" =>  $replyToken,
            "messages" => $messages
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => json_encode($content),
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/message/reply', false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }
    }

    public function pushMessage($to, $message) {
        if (isset($message["type"])){
            $messages = array($message); 
        }else{
            $messages = $message;
        }

        $header = array(
            "Content-Type: application/json",
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $content  = array(
            "to" =>  $to,
            "messages" => $messages
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => json_encode($content),
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/message/push', false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }
    }

    public function multicast($to, $message) {
        if (isset($message["type"])){
            $messages = array($message); 
        }else{
            $messages = $message;
        }

        $content  = array(
            "to" =>  $to,
            "messages" => $messages
        );

        $header = array(
            "Content-Type: application/json",
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => json_encode($content),
            ),
        ));

        $response = file_get_contents($this->host.'/v2/bot/message/multicast', false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }
    }

    public function getMessageObject($msgid) {
        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => implode("\r\n", $header),
            ),
        ));

        $response = file_get_contents($this->host."/v2/bot/message/".$msgid."/content", false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        }else {
            return $response;
        }
    }

    public function downloadMessageObject($msgid, $path = "./") {
        $response = $this->getMessageObject($msgid);
        if ($response != null) {
            $file = fopen($path.$msgid, "wb");
            fwrite($file, $response);
            fclose($file);
        }else{
            return false;
        }
    }

    private function sign($body) {
        $hash = hash_hmac('sha256', $body, $this->channelSecret, true);
        $signature = base64_encode($hash);
        return $signature;
    }
}

class LINEMSG {
   public  function Text($msgText) {
        $MsgObject = array(
               "type" => "text",
                "text" => $msgText
        );
        return $MsgObject;
    }

    public  function Image($url, $preview_url = null) {
        if ($preview_url == null) {
            $preview = $url;
        }else{
            $preview = $preview_url;
        }

        $MsgObject = array(
               "type" => "image",
                "originalContentUrl" => $url,
                "previewImageUrl" => $preview
        );
         return $MsgObject;
     }
    
    public  function Video($url, $preview_url) {
        $MsgObject = array(
               "type" => "video",
               "originalContentUrl" => $url,
               "previewImageUrl" => $preview_url
        );
         return $MsgObject;
     }

     public  function Audio($url, $second = null) {
        if ($second == null) {
            $seconds = 0;
        }else{
            $seconds = $second;
        }

        $MsgObject = array(
               "type" => "audio",
               "originalContentUrl" => $url,
               "duration" => $seconds
        );
         return $MsgObject;
     }

     public  function Location($title, $address, $latitude, $longitude) {
        $MsgObject = array(
               "type" => "location",
               "title" => $title,
               "address" => $address,
               "latitude" => $latitude,
               "longitude" => $longitude
        );
         return $MsgObject;
     }

     public  function Imagemap($baseUrl, $altText, $width, $height, $action) {
        if (isset($action["type"])){
            $actions = array($action); 
        }else{
            $actions = $action;
        }
        if ($width == 0 and $height == 0){
            list($width, $height) = getimagesize($baseUrl);
        }
        $baseSize = array(
            "width" => $width,
            "height" => $height,
        );
        $MsgObject = array(
               "type" => "imagemap",
               "baseUrl" => $baseUrl,
               "altText" => $altText,
               "baseSize" => $baseSize,
               "actions" => $actions
        );
         return $MsgObject;
     }
     
     public  function Template($altText, $template) {
        $MsgObject = array(
               "type" => "template",
               "altText" => $altText,
               "template" => $template
        );
         return $MsgObject;
     }

     public  function Flex($altText, $contents) {
        $MsgObject = array(
               "type" => "flex",
               "altText" => $altText,
               "contents" => $contents
        );
         return $MsgObject;
     }
}

class LINEMSG_Imagemap {
    public function action($type, $url_or_text, $area, $label = null) {
        if($type == "link"){
            $dataType = "linkUri";
        }elseif($type == "message"){
            $dataType = "text";
        }else{
            return null;
        }
        $object = array(
            "type" => $type,
            "label" => $label,
            $dataType => $url_or_text,
            "area" => $area
        );
        return $object;
    }

    public function actionArea($x, $y, $width, $height){
        $object = array(
            "x" => $x,
            "y" => $y,
            "width" => $width,
            "height" => $height
        );
        return $object;
    }
}

class LINEMSG_Template {
    public  function __construct($template){
        switch($template){
            case "buttons":
                $this->object = array(
                    "type" => "buttons",
                    "thumbnailImageUrl" => null,
                    "imageAspectRatio" => null,
                    "imageSize" => null,
                    "imageBackgroundColor" => null,
                    "title" => null,
                    "text" => null,
                    "defaultAction" => null,
                    "actions" => null
                );
                break;
            case "confirm":
                $this->object = array(
                    "type" => "confirm",
                    "text" => null,
                    "actions" => null
                );
                break;
            case "carousel":
                $this->object = array(
                    "type" => "carousel",
                    "columns" => null,
                    "imageAspectRatio" => null,
                    "imageSize" => null,
                );
                break;
            case "image_carousel":
                $this->object = array(
                    "type" => "image_carousel",
                    "columns" => null,
                );
                break;
            default:
                return null;
        }
    }

    public function set($var, $value = null){
        if(gettype($var) == "array"){
            $keys = array_keys($this->object);
            foreach($var as $num => $run){
                $this->set($keys[$num+1], $run);
            }
        }else{
            $this->object[$var] = $value;
        }
    }

    public function out(){
        return $this->object;
    }
}

class LINEMSG_FlexContainer {
    public  function __construct($container){
        switch($container){
            case "bubble":
                $this->object = array(
                    "type" => "bubble",
                    "direction" => null,
                    "header" => null,
                    "hero" => null,
                    "body" => null,
                    "footer" => null,
                    "styles" => null
                );
                break;
            case "carousel":
                $this->object = array(
                    "type" => "carousel",
                    "contents" => null,
                );
                break;
            default:
                return null;
        }
    }

    public function set($var, $value = null){
        if(gettype($var) == "array"){
            $keys = array_keys($this->object);
            foreach($var as $num => $run){
                $this->set($keys[$num+1], $run);
            }
        }else{
            $this->object[$var] = $value;
        }
    }

    public function out(){
        return $this->object;
    }
}
?>

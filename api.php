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
 * This polyfill of hash_equals() is a modified edition of https://git.io/Jfvrw
 *
 * Copyright (c) 2015 Indigo Development Team
 * Released under the MIT license
 * https://git.io/Jfvro
 */

/*
 * Third Party Update by SuperSonic v2.4
 * https://git.io/Jfvrg
 *
 * (c) 2020 SuperSonic (https://github.com/supersonictw)
 */

if (!function_exists("hash_equals")) {
    defined("USE_MB_STRING") or define("USE_MB_STRING", function_exists("mb_strlen"));

    function hash_equals($knownString, $userString)
    {
        $strlen = function ($string) {
            if (USE_MB_STRING) {
                return mb_strlen($string, "8bit");
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

class LINEAPI
{
    /**
     * If read the response as array while decoding JSON in `requestFactory`.
     *
     * @var boolean $responseDecodeAsArray The default is layout as Object, set value to `true` that it will use Array.
     */
    public $responseDecodeAsArray = false;

    /**
     * The constant is used for `requestFactory` to set the HTTP Method while transporting.
     *
     * @var integer HTTP_METHOD_GET
     * @var integer HTTP_METHOD_POST
     * @var integer HTTP_METHOD_DELETE
     */
    private const HTTP_METHOD_GET = 0;
    private const HTTP_METHOD_POST = 1;
    private const HTTP_METHOD_DELETE = 2;

    public function __construct($channelAccessToken, $channelSecret)
    {
        $this->host = "https://api.line.me";
        $this->data_host = "https://api-data.line.me";
        $this->channelAccessToken = $channelAccessToken;
        $this->channelSecret = $channelSecret;
    }

    /**
     * Issue channel access token.
     * https://developers.line.biz/en/reference/messaging-api/#issue-channel-access-token
     *
     * @param string $channelId
     * @param string $channelSecret
     *
     * @return object
     */
    public function issueChannelAccessToken($channelId, $channelSecret)
    {
        $header = array(
            "Content-Type: application/x-www-form-urlencoded",
        );

        $content = http_build_query(
            array(
                "grant_type" => "client_credentials",
                "client_id" => $channelId,
                "client_secret" => $channelSecret,
            )
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => $content,
            ),
        ));

        $response = file_get_contents("$this->host/v2/oauth/accessToken", false, $context);
        if (strpos($http_response_header[0], "200") === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        } else {
            $data = json_decode($response);
            $this->channelAccessToken = $data->access_token;
            return $data;
        }
    }

    /**
     * Revoke channel access token.
     * https://developers.line.biz/en/reference/messaging-api/#revoke-channel-access-token
     *
     * @return boolean If it success, will return `true`.
     */
    public function revokeChannelAccessToken()
    {
        $header = array(
            "Content-Type: application/x-www-form-urlencoded",
        );

        $content = http_build_query(
            array(
                "access_token" => $this->channelAccessToken,
            )
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => $content,
            ),
        ));

        $response = file_get_contents("$this->host/v2/oauth/revoke", false, $context);
        if (strpos($http_response_header[0], "200") === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
            return false;
        }
        return true;
    }

    /**
     * Issue link token.
     * https://developers.line.biz/en/reference/messaging-api/#issue-link-token
     *
     * @param string $userId
     *
     * @return object
     */
    public function issueUserLinkToken($userId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/user/${urlencode($userId)}/linkToken",
            self::HTTP_METHOD_POST
        );
    }

    /**
     * Get profile.
     * https://developers.line.biz/en/reference/messaging-api/#get-profile
     *
     * @param string $userId
     *
     * @return object
     */
    public function getProfile($userId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/profile/${urlencode($userId)}",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get the user ID of all users who added your LINE Official Accout as a friend.
     * https://developers.line.biz/en/reference/messaging-api/#get-follower-ids
     *
     * @param string $groupId
     * @param string $continuationToken (optional)
     *
     * @return object
     */
    public function getFollowersIds($groupId, $continuationToken = null)
    {
        $next = $continuationToken != null ? "?start=$continuationToken" : "";
        return $this->requestFactory(
            "$this->host/v2/bot/followers/ids${urlencode($next)}",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get group summary.
     * https://developers.line.biz/en/reference/messaging-api/#get-group-summary
     *
     * @param string $groupId
     *
     * @return object
     */
    public function getGroup($groupId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/group/${urlencode($groupId)}/summary",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get number of users in a group.
     * https://developers.line.biz/en/reference/messaging-api/#get-members-group-count
     *
     * @param string $groupId
     *
     * @return object
     */
    public function getGroupMemberCount($groupId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/group/${urlencode($groupId)}/members/count",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get group member profile.
     * https://developers.line.biz/en/reference/messaging-api/#get-group-member-profile
     *
     * @param string $groupId
     * @param string $userId
     *
     * @return object
     */
    public function getGroupMemberInfo($groupId, $userId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/group/${urlencode($groupId)}/member/${urlencode($userId)}",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get group member user IDs.
     * https://developers.line.biz/en/reference/messaging-api/#get-group-member-user-ids
     *
     * @param string $groupId
     * @param string $continuationToken (optional)
     *
     * @return object
     */
    public function getGroupMemberIds($groupId, $continuationToken = null)
    {
        $next = $continuationToken != null ? "?start=$continuationToken" : "";
        return $this->requestFactory(
            "$this->host/v2/bot/group/${urlencode($groupId)}/members/ids${urlencode($next)}",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Leave group.
     * https://developers.line.biz/en/reference/messaging-api/#leave-group
     *
     * @param string $groupId
     *
     * @return object
     */
    public function leaveGroup($groupId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/group/${urlencode($groupId)}/leave",
            self::HTTP_METHOD_POST
        );
    }

    /**
     * Get number of users in a room.
     * https://developers.line.biz/en/reference/messaging-api/#get-members-room-count
     *
     * @param string $roomId
     *
     * @return object
     */
    public function getRoomMemberCount($roomId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/room/${urlencode($roomId)}/members/count",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get room member user IDs.
     * https://developers.line.biz/en/reference/messaging-api/#get-room-member-profile
     *
     * @param string $roomId
     * @param string $userId
     *
     * @return object
     */
    public function getRoomMemberInfo($roomId, $userId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/room/${urlencode($roomId)}/member/${urlencode($userId)}",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get room member profile.
     * https://developers.line.biz/en/reference/messaging-api/#get-room-member-user-ids
     *
     * @param string $roomId
     * @param string $continuationToken (optional)
     *
     * @return object
     */
    public function getRoomMemberIds($roomId, $continuationToken = null)
    {
        $next = $continuationToken != null ? "?start=$continuationToken" : "";
        return $this->requestFactory(
            "$this->host/v2/bot/room/${urlencode($roomId)}/members/ids${urlencode($next)}",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Leave room.
     * https://developers.line.biz/en/reference/messaging-api/#leave-room
     *
     * @param string $roomId
     *
     * @return object
     */
    public function leaveRoom($roomId)
    {
        $next = $continuationToken != null ? "?start=$continuationToken" : "";
        return $this->requestFactory(
            "$this->host/v2/bot/room/${urlencode($roomId)}/leave",
            self::HTTP_METHOD_POST
        );
    }

    /**
     * Send reply message.
     * https://developers.line.biz/en/reference/messaging-api/#send-reply-message
     *
     * @param string $replyToken
     * @param string $message
     * @param boolean $notificationDisabled (optional)
     *
     * @return object
     */
    public function replyMessage($replyToken, $message, $notificationDisabled = false)
    {
        if (isset($message["type"])) {
            $messages = array($message);
        } else {
            $messages = $message;
        }

        $content = array(
            "replyToken" => $replyToken,
            "messages" => $messages,
            "notificationDisabled" => $notificationDisabled,
        );

        return $this->requestFactory(
            "$this->host/v2/bot/message/reply",
            self::HTTP_METHOD_POST,
            $content
        );
    }

    /**
     * Send push message.
     * https://developers.line.biz/en/reference/messaging-api/#send-push-message
     *
     * @param string $targetId
     * @param string $message
     * @param boolean $notificationDisabled (optional)
     *
     * @return object
     */
    public function pushMessage($targetId, $message, $notificationDisabled = false)
    {
        if (isset($message["type"])) {
            $messages = array($message);
        } else {
            $messages = $message;
        }

        $content = array(
            "to" => $targetId,
            "messages" => $messages,
            "notificationDisabled" => $notificationDisabled,
        );

        return $this->requestFactory(
            "$this->host/v2/bot/message/push",
            self::HTTP_METHOD_POST,
            $content
        );
    }

    /**
     * Send multicast message.
     * https://developers.line.biz/en/reference/messaging-api/#send-multicast-message
     *
     * @param array $targetIds
     * @param string $message
     * @param boolean $notificationDisabled
     *
     * @return object
     */
    public function multicast($targetIds, $message)
    {
        if (isset($message["type"])) {
            $messages = array($message);
        } else {
            $messages = $message;
        }

        $content = array(
            "to" => $targetIds,
            "messages" => $messages,
            "notificationDisabled" => $notificationDisabled,
        );

        return $this->requestFactory(
            "$this->host/v2/bot/message/multicast",
            self::HTTP_METHOD_POST,
            $content
        );
    }

    /**
     * Confirming that an audience is ready to accept messages.
     * https://developers.line.biz/en/docs/messaging-api/sending-messages/#get-audience-status
     *
     * @param string $audienceGroupId
     *
     * @return object
     */
    public function confirmingAudienceGroupStatus($audienceGroupId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/audienceGroup/${urlencode($audienceGroupId)}",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get content.
     * https://developers.line.biz/en/reference/messaging-api/#get-content
     *
     * @param string $messageId
     *
     * @return binary
     */
    public function getMessageObject($messageId)
    {
        return $this->requestFactory(
            "$this->data_host/v2/bot/message/${urlencode($messageId)}/content",
            self::HTTP_METHOD_GET,
            $decode = false
        );
    }

    /**
     * Save file of the message from the function `getMessageObject`.
     *
     * @param string $messageId
     * @param string $path (optional)
     *
     * @return boolean If it success, will return `true`.
     */
    public function downloadMessageObject($messageId, $path = "./")
    {
        $response = $this->getMessageObject($messageId);
        if ($response != null) {
            $file = fopen($path . $messageId, "wb");
            fwrite($file, $response);
            fclose($file);
            return true;
        }
        return false;
    }

    /**
     * Get rich menu list.
     * https://developers.line.biz/en/reference/messaging-api/#get-rich-menu-list
     *
     * @return object
     */
    public function getRichMenuList()
    {
        return $this->requestFactory(
            "$this->host/v2/bot/richmenu/list",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get rich menu.
     * https://developers.line.biz/en/reference/messaging-api/#get-rich-menu
     *
     * @param string $richMenuId
     *
     * @return object
     */
    public function getRichMenu($richMenuId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/richmenu/${urlencode($richMenuId)}",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Create rich menu.
     * https://developers.line.biz/en/reference/messaging-api/#create-rich-menu
     *
     * @param string $content
     *
     * @return object
     */
    public function createRichMenu($content)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/richmenu",
            self::HTTP_METHOD_POST,
            $content
        );
    }

    /**
     * Delete rich menu.
     * https://developers.line.biz/en/reference/messaging-api/#delete-rich-menu
     *
     * @param string $richMenuId
     *
     * @return object
     */
    public function deleteRichMenu($richMenuId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/richmenu/${urlencode($richMenuId)}",
            self::HTTP_METHOD_DELETE
        );
    }

    /**
     * Get rich menu ID of user.
     * https://developers.line.biz/en/reference/messaging-api/#get-rich-menu-id-of-user
     *
     * @param string $userId
     *
     * @return object
     */
    public function getRichMenuIdOfUser($userId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/user/${urlencode($userId)}/richmenu",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Link rich menu to user.
     * https://developers.line.biz/en/reference/messaging-api/#link-rich-menu-to-user
     *
     * @param string $userId
     * @param string $richMenuId
     *
     * @return object
     */
    public function linkRichMenuToUser($userId, $richMenuId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/user/${urlencode($userId)}/richmenu/${urlencode($richMenuId)}",
            self::HTTP_METHOD_POST
        );
    }

    /**
     * Unlink rich menu from user.
     * https://developers.line.biz/en/reference/messaging-api/#unlink-rich-menu-from-user
     *
     * @param string $userId
     * @param string $richMenuId
     *
     * @return object
     */
    public function unlinkRichMenuFromUser($userId, $richMenuId)
    {
        return $this->requestFactory(
            "$this->host/v2/bot/user/${urlencode($userId)}/richmenu/${urlencode($richMenuId)}",
            self::HTTP_METHOD_DELETE
        );
    }

    # I think it is not a good way to upload any file with "file_get_contents"
    /**
     * Upload rich menu image. (libcURL used)
     * https://developers.line.biz/en/reference/messaging-api/#upload-rich-menu-image.
     *
     * @param string $richMenuId
     * @param string $path
     *
     * @return void
     */
    public function uploadRichMenuImage($richMenuId, $path)
    {
        $ch = curl_init("$this->data_host/v2/bot/richmenu/${urlencode($richMenuId)}/content");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "file_input" => $path,
        ));
        curl_exec($ch);
    }

    /**
     * Download rich menu image. (Get the binary only)
     * https://developers.line.biz/en/reference/messaging-api/#download-rich-menu-image
     *
     * @param string $richMenuId
     *
     * @return binary
     */
    public function getRichMenuImage($richMenuId)
    {
        return $this->requestFactory(
            "$this->data_host/v2/bot/richmenu/${urlencode($richMenuId)}/content",
            self::HTTP_METHOD_GET,
            $decode = false
        );
    }

    /**
     * Save image of the rich menu from the function `getRichMenuImage`.
     *
     * @param string $richMenuId
     * @param string $path (optional)
     *
     * @return boolean If it success, will return `true`.
     */
    public function downloadRichMenuImage($richMenuId, $path = "./")
    {
        $response = $this->getRichMenuImage($richMenuId);
        if ($response != null) {
            $file = fopen($path . $richMenuId, "wb");
            fwrite($file, $response);
            fclose($file);
            return true;
        }
        return false;
    }

    /**
     * Verify the request which visits this API, if it's from LINE Webhook, parse the events.
     *
     * @return array
     */
    public function parseEvents()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            error_log("Method not allowed");
            exit();
        }

        $entityBody = file_get_contents("php://input");

        if (strlen($entityBody) === 0) {
            http_response_code(400);
            error_log("Missing request body");
            exit();
        }

        if (!hash_equals($this->sign($entityBody), $_SERVER["HTTP_X_LINE_SIGNATURE"])) {
            http_response_code(400);
            error_log("Invalid signature value");
            exit();
        }

        $data = json_decode($entityBody, true);
        if (!isset($data["events"])) {
            http_response_code(400);
            error_log("Invalid request body: missing events property");
            exit();
        }
        return $data["events"];
    }

    /**
     * Signing data via SHA-256
     *
     * @param mixed $body
     *
     * @return string
     */
    private function sign($body)
    {
        $hash = hash_hmac("sha256", $body, $this->channelSecret, true);
        $signature = base64_encode($hash);
        return $signature;
    }

    /**
     * Send request to LINE API Platform.
     *
     * @param string $targetUri An URL for sending request.
     * @param integer $method HTTP Method.
     * @param mixed $data Content for doing POST. (optional)
     * @param boolean $decode Decode the response from JSON. (optional)
     *
     * @return mixed
     */
    private function requestFactory($targetUri, $method, $data = array(), $decode = true)
    {
        $header = array(
            "Authorization: Bearer $this->channelAccessToken",
        );

        switch ($method) {
            case self::HTTP_METHOD_GET:
                $context = stream_context_create(array(
                    "http" => array(
                        "method" => "GET",
                        "header" => implode("\r\n", $header),
                    ),
                ));
                break;

            case self::HTTP_METHOD_POST:
                array_push($header, "Content-Type: application/json");
                $context = stream_context_create(array(
                    "http" => array(
                        "method" => "POST",
                        "header" => implode("\r\n", $header),
                        "content" => json_encode($data),
                    ),
                ));
                break;

            case self::HTTP_METHOD_DELETE:
                $context = stream_context_create(array(
                    "http" => array(
                        "method" => "DELETE",
                        "header" => implode("\r\n", $header),
                    ),
                ));
                break;

            default:
                error_log("Unknown request method: " . $method);
                return;
        }

        $response = file_get_contents($targetUri, false, $context);
        if (strpos($http_response_header[0], "200") === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
        } else {
            return !$json_decode ? $response : json_decode($response, $this->responseDecodeAsArray);
        }
    }
}

class LINEMSG
{
    public static function QuickReply($actions)
    {
        return array("quickReply" => array(
            "items" => $actions,
        ));
    }

    public static function Text($msgText)
    {
        return array(
            "type" => "text",
            "text" => $msgText,
        );
    }

    public static function Sticker($packageId, $stickerId)
    {
        return array(
            "type" => "sticker",
            "packageId" => $packageId,
            "stickerId" => $stickerId,
        );
    }

    public static function Image($url, $preview_url = null)
    {
        if ($preview_url == null) {
            $preview = $url;
        } else {
            $preview = $preview_url;
        }

        return array(
            "type" => "image",
            "originalContentUrl" => $url,
            "previewImageUrl" => $preview,
        );
    }

    public static function Video($url, $preview_url)
    {
        return array(
            "type" => "video",
            "originalContentUrl" => $url,
            "previewImageUrl" => $preview_url,
        );
    }

    public static function Audio($url, $second = null)
    {
        if ($second == null) {
            $seconds = 0;
        } else {
            $seconds = $second;
        }

        return array(
            "type" => "audio",
            "originalContentUrl" => $url,
            "duration" => $seconds,
        );
    }

    public static function Location($title, $address, $latitude, $longitude)
    {
        return array(
            "type" => "location",
            "title" => $title,
            "address" => $address,
            "latitude" => $latitude,
            "longitude" => $longitude,
        );
    }

    public static function Imagemap($baseUrl, $altText, $width, $height, $action)
    {
        if (isset($action["type"])) {
            $actions = array($action);
        } else {
            $actions = $action;
        }
        if ($width == 0 and $height == 0) {
            list($width, $height) = getimagesize($baseUrl);
        }
        $baseSize = array(
            "width" => $width,
            "height" => $height,
        );
        return array(
            "type" => "imagemap",
            "baseUrl" => $baseUrl,
            "altText" => $altText,
            "baseSize" => $baseSize,
            "actions" => $actions,
        );
    }

    public static function Template($altText, $template)
    {
        foreach ($template as $num => $var) {
            if ($var == null) {
                unset($template[$num]);
            }
        }
        return array(
            "type" => "template",
            "altText" => $altText,
            "template" => $template,
        );
    }

    public static function Flex($altText, $contents)
    {
        foreach ($contents as $num => $var) {
            if ($var == null) {
                unset($contents[$num]);
            }
        }
        return array(
            "type" => "flex",
            "altText" => $altText,
            "contents" => $contents,
        );
    }
}

class LINEMSG_QuickReply
{
    public function __construct()
    {
        $this->object = array(
            "type" => "action",
            "action" => array(),
        );
    }

    public function add($action)
    {
        if (gettype($action) == "array") {
            $this->object["action"] = $action;
        } else {
            push_array($this->object["action"], $action);
        }
    }

    public function out()
    {
        return $this->object;
    }

    public function actions($type)
    {
        switch ($type) {
            case "postback":
                $this->actions = array(
                    "type" => "postback",
                    "label" => null,
                    "data" => null,
                    "text" => null,
                );
                break;
            case "message":
                $this->actions = array(
                    "type" => "message",
                    "label" => null,
                    "text" => null,
                );
                break;
            case "uri":
                $this->actions = array(
                    "type" => "uri",
                    "label" => null,
                    "uri" => null,
                );
                break;
            case "datetimepicker":
                $this->actions = array(
                    "type" => "datetimepicker",
                    "label" => null,
                    "data" => null,
                    "mode" => null,
                    "initial" => null,
                    "max" => null,
                    "min" => null,
                );
                break;
            case "camera":
                $this->actions = array(
                    "type" => "camera",
                    "label" => null,
                );
                break;
            case "cameraRoll":
                $this->actions = array(
                    "type" => "cameraRoll",
                    "label" => null,
                );
                break;
            case "location":
                $this->actions = array(
                    "type" => "location",
                    "label" => null,
                );
                break;
        }
    }

    public function actions_set($var, $value = null)
    {
        if (gettype($var) == "array") {
            $keys = array_keys($this->actions);
            foreach ($var as $num => $run) {
                $this->actions_set($keys[$num + 1], $run);
            }
        } else {
            $this->actions[$var] = $value;
        }
    }

    public function actions_out()
    {
        return $this->actions;
    }
}

class LINEMSG_Imagemap
{
    public function action($type, $url_or_text, $area, $label = null)
    {
        if ($type == "link") {
            $dataType = "linkUri";
        } elseif ($type == "message") {
            $dataType = "text";
        } else {
            return null;
        }
        $object = array(
            "type" => $type,
            "label" => $label,
            $dataType => $url_or_text,
            "area" => $area,
        );
        return $object;
    }

    public function actionArea($x, $y, $width, $height)
    {
        $object = array(
            "x" => $x,
            "y" => $y,
            "width" => $width,
            "height" => $height,
        );
        return $object;
    }
}

class LINEMSG_Template
{
    public function __construct($template)
    {
        switch ($template) {
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
                    "actions" => null,
                );
                break;
            case "confirm":
                $this->object = array(
                    "type" => "confirm",
                    "text" => null,
                    "actions" => null,
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

    public function set($var, $value = null)
    {
        if (gettype($var) == "array") {
            $keys = array_keys($this->object);
            foreach ($var as $num => $run) {
                $this->set($keys[$num + 1], $run);
            }
        } else {
            $this->object[$var] = $value;
        }
    }

    public function out()
    {
        return $this->object;
    }

    public function actions($type)
    {
        switch ($type) {
            case "postback":
                $this->actions = array(
                    "type" => "postback",
                    "label" => null,
                    "data" => null,
                    "text" => null,
                );
                break;
            case "message":
                $this->actions = array(
                    "type" => "message",
                    "label" => null,
                    "text" => null,
                );
                break;
            case "uri":
                $this->actions = array(
                    "type" => "uri",
                    "label" => null,
                    "uri" => null,
                );
                break;
            case "datetimepicker":
                $this->actions = array(
                    "type" => "datetimepicker",
                    "label" => null,
                    "data" => null,
                    "mode" => null,
                    "initial" => null,
                    "max" => null,
                    "min" => null,
                );
                break;
            case "camera":
                $this->actions = array(
                    "type" => "camera",
                    "label" => null,
                );
                break;
            case "cameraRoll":
                $this->actions = array(
                    "type" => "cameraRoll",
                    "label" => null,
                );
                break;
            case "location":
                $this->actions = array(
                    "type" => "location",
                    "label" => null,
                );
                break;
        }
    }

    public function actions_set($var, $value = null)
    {
        if (gettype($var) == "array") {
            $keys = array_keys($this->actions);
            foreach ($var as $num => $run) {
                $this->actions_set($keys[$num + 1], $run);
            }
        } else {
            $this->actions[$var] = $value;
        }
    }

    public function actions_out()
    {
        return $this->actions;
    }
}

class LINEMSG_FlexContainer
{
    public function __construct($container)
    {
        switch ($container) {
            case "bubble":
                $this->object = array(
                    "type" => "bubble",
                    "direction" => null,
                    "header" => null,
                    "hero" => null,
                    "body" => null,
                    "footer" => null,
                    "styles" => null,
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

    public function set($var, $value = null)
    {
        if (gettype($var) == "array") {
            $keys = array_keys($this->object);
            foreach ($var as $num => $run) {
                $this->set($keys[$num + 1], $run);
            }
        } else {
            $this->object[$var] = $value;
        }
    }

    public function out()
    {
        return $this->object;
    }
}

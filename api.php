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

/**
 * LINEAPI (Messaging API)
 * https://developers.line.biz/en/reference/messaging-api/
 *
 * This is the Messaging API for creating LINE Chat BOTs.
 */
class LINEAPI
{
    /**
     * If read the response as array while decoding JSON in `requestFactory`.
     *
     * @var boolean $responseDecodeAsArray The default is exporting as Object, set value to `true` that it will use Array.
     */
    public $responseDecodeAsArray = false;

    /**
     * The value is setted for authorize while the API connecting LINE API Platform.
     * https://developers.line.biz/en/docs/messaging-api/getting-started/
     *
     * If you don't have the Authorized Tokens, following the guide of the URL to generate one pair.
     * https://developers.line.biz/en/docs/messaging-api/building-bot/#issue-a-channel-access-token
     *
     * @var boolean $channelAccessToken
     * @var boolean $channelSecret
     */
    private $channelAccessToken = null;
    private $channelSecret = null;

    /**
     * The URIs is the host of LINE API Platform.
     *
     * @var integer API_HOST
     * @var integer API_DATA_HOST
     */
    const API_HOST = "https://api.line.me";
    const API_DATA_HOST = "https://api-data.line.me";

    /**
     * The constants is used for `requestFactory` to set the HTTP Method while transporting.
     *
     * @var integer HTTP_METHOD_GET
     * @var integer HTTP_METHOD_POST
     * @var integer HTTP_METHOD_DELETE
     */
    const HTTP_METHOD_GET = 0;
    const HTTP_METHOD_POST = 1;
    const HTTP_METHOD_DELETE = 2;

    public function __construct($channelAccessToken, $channelSecret)
    {
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

        $response = file_get_contents(self::API_HOST . "/v2/oauth/accessToken", false, $context);
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
     * @param string $channelAccessToken Specify a channel access token you hope to revoke. (optional)
     *
     * @return boolean If it success, will return `true`.
     */
    public function revokeChannelAccessToken($channelAccessToken = "")
    {
        $header = array(
            "Content-Type: application/x-www-form-urlencoded",
        );

        $content = http_build_query(
            array(
                "access_token" => $channelAccessToken ?: $this->channelAccessToken,
            )
        );

        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => $content,
            ),
        ));

        $response = file_get_contents(self::API_HOST . "/v2/oauth/revoke", false, $context);
        if (strpos($http_response_header[0], "200") === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
            return false;
        }
        return true;
    }

    /**
     * Get number of message deliveries.
     * https://developers.line.biz/en/reference/messaging-api/#get-number-of-delivery-messages
     *
     * @param string $date
     *
     * @return object
     */
    public function getMessageDeliveriesCount($date)
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/insight/message/delivery?date=$date",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get number of followers.
     * https://developers.line.biz/en/reference/messaging-api/#get-number-of-followers
     *
     * @param string $date
     *
     * @return object
     */
    public function getFollowersCount($date)
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/insight/followers?date=$date",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get friend demographics.
     * https://developers.line.biz/en/reference/messaging-api/#get-follower-ids
     *
     * @return object
     */
    public function getFriendDemographics()
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/insight/demographic",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get user interaction statistics.
     * https://developers.line.biz/en/reference/messaging-api/#get-follower-ids
     *
     * @param string $requestId
     *
     * @return object
     */
    public function getUserInteractionStatistics($requestId)
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/insight/message/event?requestId=$requestId",
            self::HTTP_METHOD_GET
        );
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
            self::API_HOST . "/v2/bot/user/$userId/linkToken",
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
            self::API_HOST . "/v2/bot/profile/$userId",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get the user ID of all users who added your LINE Official Accout as a friend.
     * https://developers.line.biz/en/reference/messaging-api/#get-follower-ids
     *
     * @param string $continuationToken (optional)
     *
     * @return object
     */
    public function getFollowersIds($continuationToken = "")
    {
        $next = $continuationToken ? "?start=$continuationToken" : "";
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/followers/ids$next",
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
            self::API_HOST . "/v2/bot/group/$groupId/summary",
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
            self::API_HOST . "/v2/bot/group/$groupId/members/count",
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
            self::API_HOST . "/v2/bot/group/$groupId/member/$userId",
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
    public function getGroupMemberIds($groupId, $continuationToken = "")
    {
        $next = $continuationToken ? "?start=$continuationToken" : "";
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/group/$groupId/members/ids$next",
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
            self::API_HOST . "/v2/bot/group/$groupId/leave",
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
            self::API_HOST . "/v2/bot/room/$roomId/members/count",
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
            self::API_HOST . "/v2/bot/room/$roomId/member/$userId",
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
    public function getRoomMemberIds($roomId, $continuationToken = "")
    {
        $next = $continuationToken ? "?start=$continuationToken" : "";
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/room/$roomId/members/ids$next",
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
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/room/$roomId/leave",
            self::HTTP_METHOD_POST
        );
    }

    /**
     * Send reply message.
     * https://developers.line.biz/en/reference/messaging-api/#send-reply-message
     *
     * @param string $replyToken
     * @param array $message
     * @param boolean $notificationDisabled (optional)
     *
     * @return object
     */
    public function replyMessage($replyToken, $message, $notificationDisabled = false)
    {
        if (array_key_exists("type", $message)) {
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
            self::API_HOST . "/v2/bot/message/reply",
            self::HTTP_METHOD_POST,
            $content
        );
    }

    /**
     * Send push message.
     * https://developers.line.biz/en/reference/messaging-api/#send-push-message
     *
     * @param string $targetId
     * @param array $message
     * @param boolean $notificationDisabled (optional)
     *
     * @return object
     */
    public function pushMessage($targetId, $message, $notificationDisabled = false)
    {
        if (array_key_exists("type", $message)) {
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
            self::API_HOST . "/v2/bot/message/push",
            self::HTTP_METHOD_POST,
            $content
        );
    }

    /**
     * Send multicast message.
     * https://developers.line.biz/en/reference/messaging-api/#send-multicast-message
     *
     * @param array $targetIds
     * @param array $message
     * @param boolean $notificationDisabled (optional)
     *
     * @return object
     */
    public function multicast($targetIds, $message, $notificationDisabled = false)
    {
        if (array_key_exists("type", $message)) {
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
            self::API_HOST . "/v2/bot/message/multicast",
            self::HTTP_METHOD_POST,
            $content
        );
    }

    /**
     * Send broadcast message.
     * https://developers.line.biz/en/reference/messaging-api/#send-broadcast-message
     *
     * @param array $message
     * @param boolean $notificationDisabled (optional)
     *
     * @return object
     */
    public function broadcast($message, $notificationDisabled = false)
    {
        if (array_key_exists("type", $message)) {
            $messages = array($message);
        } else {
            $messages = $message;
        }

        $content = array(
            "messages" => $messages,
            "notificationDisabled" => $notificationDisabled,
        );

        return $this->requestFactory(
            self::API_HOST . "/v2/bot/message/broadcast",
            self::HTTP_METHOD_POST,
            $content
        );
    }

    /**
     * Get the target limit for additional messages.
     * https://developers.line.biz/en/reference/messaging-api/#get-quota
     *
     * @return object
     */
    public function getSendMessagesQuota()
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/message/quota",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get number of messages sent this month.
     * https://developers.line.biz/en/reference/messaging-api/#get-consumption
     *
     * @return object
     */
    public function getAllMessagesSentCount()
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/message/quota/consumption",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get number of sent reply messages.
     * https://developers.line.biz/en/reference/messaging-api/#get-number-of-reply-messages
     *
     * @return object
     */
    public function getReplyMessagesSentCount()
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/message/delivery/reply",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get number of sent push messages.
     * https://developers.line.biz/en/reference/messaging-api/#get-number-of-push-messages
     *
     * @return object
     */
    public function getPushMessagesSentCount()
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/message/delivery/push",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get number of sent multicast messages.
     * https://developers.line.biz/en/reference/messaging-api/#get-number-of-multicast-messages
     *
     * @return object
     */
    public function getMulticastMessagesSentCount()
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/message/delivery/multicast",
            self::HTTP_METHOD_GET
        );
    }

    /**
     * Get number of sent broadcast messages.
     * https://developers.line.biz/en/reference/messaging-api/#get-number-of-broadcast-messages
     *
     * @return object
     */
    public function getBroadcastMessagesSentCount()
    {
        return $this->requestFactory(
            self::API_HOST . "/v2/bot/message/delivery/broadcast",
            self::HTTP_METHOD_GET
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
            self::API_HOST . "/v2/bot/audienceGroup/$audienceGroupId",
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
            self::API_DATA_HOST . "/v2/bot/message/$messageId/content",
            self::HTTP_METHOD_GET,
            [],
            false
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
            self::API_HOST . "/v2/bot/richmenu/list",
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
            self::API_HOST . "/v2/bot/richmenu/$richMenuId",
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
            self::API_HOST . "/v2/bot/richmenu",
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
            self::API_HOST . "/v2/bot/richmenu/$richMenuId",
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
            self::API_HOST . "/v2/bot/user/$userId/richmenu",
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
            self::API_HOST . "/v2/bot/user/$userId/richmenu/$richMenuId",
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
            self::API_HOST . "/v2/bot/user/$userId/richmenu/$richMenuId",
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
        $ch = curl_init(self::API_DATA_HOST . "/v2/bot/richmenu/$richMenuId/content");
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
            self::API_DATA_HOST . "/v2/bot/richmenu/$richMenuId/content",
            self::HTTP_METHOD_GET,
            array(),
            false
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
     * Signing data via SHA-256.
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
                return null;
        }

        $response = file_get_contents($targetUri, false, $context);
        if (strpos($http_response_header[0], "200") === false) {
            http_response_code(500);
            error_log("Request failed: " . $response);
            return null;
        } else {
            return !$decode ? $response : json_decode($response, $this->responseDecodeAsArray);
        }
    }
}

/**
 * LINEMSG
 * https://developers.line.biz/en/reference/messaging-api/#message-objects
 *
 * This is the wrapper for creating a message object.
 */
class LINEMSG
{
    /**
     * Quick reply
     * https://developers.line.biz/en/reference/messaging-api/#quick-reply
     *
     * @param $actions
     * @return array
     */
    public static function QuickReply($actions)
    {
        return array("quickReply" => array(
            "items" => $actions,
        ));
    }

    /**
     * Text message
     * https://developers.line.biz/en/reference/messaging-api/#text-message
     *
     * @param string $msgText
     *
     * @return array
     */
    public static function Text($msgText)
    {
        return array(
            "type" => "text",
            "text" => $msgText,
        );
    }

    /**
     * Sticker message
     * https://developers.line.biz/en/reference/messaging-api/#sticker-message
     *
     * @param string $packageId
     * @param string $stickerId
     *
     * @return array
     */
    public static function Sticker($packageId, $stickerId)
    {
        return array(
            "type" => "sticker",
            "packageId" => $packageId,
            "stickerId" => $stickerId,
        );
    }

    /**
     * Image message
     * https://developers.line.biz/en/reference/messaging-api/#image-message
     *
     * @param string $url
     * @param string $previewUrl (optional)
     *
     * @return array
     */
    public static function Image($url, $previewUrl = null)
    {
        if ($previewUrl == null) {
            $preview = $url;
        } else {
            $preview = $previewUrl;
        }

        return array(
            "type" => "image",
            "originalContentUrl" => $url,
            "previewImageUrl" => $preview,
        );
    }

    /**
     * Video message
     * https://developers.line.biz/en/reference/messaging-api/#video-message
     *
     * @param string $url
     * @param string $previewUrl
     *
     * @return array
     */
    public static function Video($url, $previewUrl)
    {
        return array(
            "type" => "video",
            "originalContentUrl" => $url,
            "previewImageUrl" => $previewUrl,
        );
    }

    /**
     * Audio message
     * https://developers.line.biz/en/reference/messaging-api/#audio-message
     *
     * @param string $url
     * @param integer $second (optional)
     *
     * @return array
     */
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

    /**
     * Location message
     * https://developers.line.biz/en/reference/messaging-api/#location-message
     *
     * @param string $title
     * @param string $address
     * @param double $latitude
     * @param double $longitude
     *
     * @return array
     */
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

    /**
     * Imagemap message
     * https://developers.line.biz/en/reference/messaging-api/#imagemap-message
     *
     * @param string $baseUrl
     * @param string $altText
     * @param integer $width
     * @param integer $height
     * @param array $action
     *
     * @return array
     */
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

    /**
     * Template messages
     * https://developers.line.biz/en/reference/messaging-api/#template-messages
     *
     * @param string $altText
     * @param array $template
     *
     * @return array
     */
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

    /**
     * Flex Message
     * https://developers.line.biz/en/reference/messaging-api/#flex-message
     *
     * @param string $altText
     * @param array $contents
     *
     * @return array
     */
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

    /**
     * Change icon and display name
     * https://developers.line.biz/en/docs/messaging-api/icon-nickname-switch/
     *
     * The function will help you assign the message object with icon and display name changing.
     *
     * @param array $originalMessage
     * @param string $name
     * @param string $iconUrl
     *
     * @return array
     */
    public static function changeAppearance($originalMessage, $name, $iconUrl)
    {
        return array_merge($originalMessage, array(
            "sender" => array(
                "name" => $name,
                "iconUrl" => $iconUrl,
            ),
        ));
    }
}

// The classes below is used for creating the object of events with the class `LINEMSG`.

/**
 * LINEMSG_QuickReply
 * https://developers.line.biz/en/reference/messaging-api/#items-object
 */
class LINEMSG_QuickReply
{
    /**
     * @var array
     */
    private $actions;

    /**
     * @var array
     */
    private $object;

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
            array_push($this->object["action"], $action);
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

/**
 * LINEMSG_Imagemap
 * https://developers.line.biz/en/reference/messaging-api/#imagemap-action-objects
 */
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
        return array(
            "type" => $type,
            "label" => $label,
            $dataType => $url_or_text,
            "area" => $area,
        );
    }

    public function actionArea($x, $y, $width, $height)
    {
        return array(
            "x" => $x,
            "y" => $y,
            "width" => $width,
            "height" => $height,
        );
    }
}

/**
 * LINEMSG_Template
 * https://developers.line.biz/en/reference/messaging-api/#common-properties-of-template-message-objects
 */
class LINEMSG_Template
{
    /**
     * @var array
     */
    private $actions;

    /**
     * @var array
     */
    private $object;

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

/**
 * LINEMSG_FlexContainer
 * https://developers.line.biz/en/reference/messaging-api/#container
 */
class LINEMSG_FlexContainer
{
    /**
     * @var array
     */
    private $object;

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

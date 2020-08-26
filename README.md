# line-bot-sdk-php-tiny

[![License](https://img.shields.io/badge/license-Apache--2.0-FF3333.svg)](LICENSE)
[![Line](https://img.shields.io/badge/lineapi-v2-00DD77.svg)](https://developers.line.me)
[![Version](https://img.shields.io/badge/version-2.4.1-00BBFF.svg)](https://git.io/Jfvrg)
[![PHP](https://img.shields.io/badge/php->=5.4-B94FFF.svg)](https://php.net)

[![LINE](https://lineofficial.blogimg.jp/tw/imgs/2/2/22f62401.png)](https://line.me)

A simple SDK  for the LINE Messaging API with PHP.

## Description

This is a third party SDK for LINE Messaging API that extended more functions from [line-bot-sdk-tiny](https://git.io/JUUXz).

As <https://github.com/line/line-bot-sdk-php/issues/163> said, the original version which is created by [LINE Corporation](https://linecorp.com) has no plan to update, so that I created this one.

There is only an `api.php` file for you to include it into your LINE Messaging BOT as easy for someone who don't need the full API if he only wants to make a "Simple" BOT.

If you want the official LINE Messaging API for `PHP 7.x`, go to [line-bot-sdk-php](https://github.com/line/line-bot-sdk-php) for getting the full version.

## Note

It's using the function "file_get_contents()" as its HTTP Client for connecting to LINE API Platform.

As the result, it might be crashed by SELinux.

There are some solutions for resolving this problem:

+ Disable SELinux
+ Add SELinux Policy
+ To use [line-bot-sdk-php](https://github.com/line/line-bot-sdk-php)

## Example

Try to read and learn from [examples](./examples/) for understanding how to create a BOT with this API.

Before running the examples, please make sure that you have setted the Channel access token and Channel secret of your BOT.

## Requirement

    PHP >= 5.4

## License

    Copyright 2016 LINE Corporation

    LINE Corporation licenses this file to you under the Apache License,
    version 2.0 (the "License"); you may not use this file except in compliance
    with the License. You may obtain a copy of the License at:

      https://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
    WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
    License for the specific language governing permissions and limitations
    under the License.

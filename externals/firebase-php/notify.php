<?php

require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\AndroidConfig;

function notify($target, $title, $message, $data = array())
{
  $root = dirname(phutil_get_library_root('phabricator'));
  $path = $root . '/conf/local/sev-2-firebase.json';

  $factory = (new Factory)->withServiceAccount($path);

  $messaging = $factory->createMessaging();
  $message = CloudMessage::withTarget('topic', $target)
    ->withApnsConfig(ApnsConfig::fromArray([
      'headers' => [
        'apns-priority' => '10',
      ],
      'payload' => [
        'aps' => [
          'alert' => [
            'title' => $title,
            'body' => $message,
          ],
          'badge' => 0,
          'sound' => 'default',
        ],
      ],
    ]))
    ->withData($data);

  try {
    $messaging->send($message);
  } catch (Exception $e) {
  }
}

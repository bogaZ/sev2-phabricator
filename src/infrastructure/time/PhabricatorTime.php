<?php

final class PhabricatorTime extends Phobject {

  private static $stack = array();
  private static $originalZone;

  public static function pushTime($epoch, $timezone) {
    if (empty(self::$stack)) {
      self::$originalZone = date_default_timezone_get();
    }

    $ok = date_default_timezone_set($timezone);
    if (!$ok) {
      throw new Exception(pht("Invalid timezone '%s'!", $timezone));
    }

    self::$stack[] = array(
      'epoch'       => $epoch,
      'timezone'    => $timezone,
    );

    return new PhabricatorTimeGuard(last_key(self::$stack));
  }

  public static function popTime($key) {
    if ($key !== last_key(self::$stack)) {
      throw new Exception(
        pht(
          '%s with bad key.',
          __METHOD__));
    }
    array_pop(self::$stack);

    if (empty(self::$stack)) {
      date_default_timezone_set(self::$originalZone);
    } else {
      $frame = end(self::$stack);
      date_default_timezone_set($frame['timezone']);
    }
  }

  public static function getNow() {
    if (self::$stack) {
      $frame = end(self::$stack);
      return $frame['epoch'];
    }
    return time();
  }

  public static function parseLocalTime($time, PhabricatorUser $user) {
    $old_zone = date_default_timezone_get();

    date_default_timezone_set($user->getTimezoneIdentifier());
      $timestamp = (int)strtotime($time, self::getNow());
      if ($timestamp <= 0) {
        $timestamp = null;
      }
    date_default_timezone_set($old_zone);

    return $timestamp;
  }

  public static function getTodayMidnightDateTime($viewer) {
    $timezone = new DateTimeZone($viewer->getTimezoneIdentifier());
    $today = new DateTime('@'.time());
    $today->setTimezone($timezone);
    $year = $today->format('Y');
    $month = $today->format('m');
    $day = $today->format('d');
    $today = new DateTime("{$year}-{$month}-{$day}", $timezone);
    return $today;
  }

  public static function getDateTimeFromEpoch($epoch, PhabricatorUser $viewer) {
    $datetime = new DateTime('@'.$epoch);
    $datetime->setTimezone($viewer->getTimeZone());
    return $datetime;
  }

  public static function getTimezoneDisplayName($raw_identifier) {

    // Internal identifiers have names like "America/Los_Angeles", but this is
    // just an implementation detail and we can render them in a more human
    // readable format with spaces.
    $name = str_replace('_', ' ', $raw_identifier);

    return $name;
  }

  public static function getElapsedTimeAgo($epoch, $full = false) {
    $now = new DateTime;
    $ago = new DateTime('@'.$epoch);

    $just_now = array(
      '1 second',
      '2 seconds',
      '3 seconds',
      '4 seconds',
      '5 seconds',
      '6 seconds',
      '7 seconds',
      '8 seconds',
      '9 seconds',
      '10 seconds',
    );

    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string
      ? implode(', ', $string) . ' ago' : 'just now';
  }

}

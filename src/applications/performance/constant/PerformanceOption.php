<?php

class PerformanceOption {

  const PERIOD_7_DAYS = 'current_week';
  const PERIOD_14_DAYS = 'last_week';
  const PERIOD_30_DAYS = 'last_month';
  const PERIOD_90_DAYS = 'last_quartal';

  /**
   * Provide period options
   */
  public static function getPeriods() {
    return array(
      '' => 'All time',
      self::PERIOD_7_DAYS => 'Current week',
      self::PERIOD_14_DAYS => 'Last 2 weeks',
      self::PERIOD_30_DAYS => 'Last month',
      self::PERIOD_90_DAYS => 'Last 3 months',
    );
  }

  /**
   * Return epoch of the start of current week
   * @return int
   */
  public static function getStartOfTheWeekEpoch() {
      $day = date('w');
      $week_start = date('U', strtotime('-'.$day.' days'));

      return (int)$week_start;
  }

  /**
   * Return epoch of the end of current week
   * @return int
   */
  public static function getEndOfTheWeekEpoch() {
      $day = date('w');
      $week_end = date('U', strtotime('+'.(6 - $day).' days'));

      return (int)$week_end;
  }

  /**
   * Get epoc from any given diff
   * @param  int
   * @return int
   */
  public static function getEpochFrom($days = 0) {
    $now = time();
    $diff = $days * 24 * 3600;

    return $now - $diff;
  }
}

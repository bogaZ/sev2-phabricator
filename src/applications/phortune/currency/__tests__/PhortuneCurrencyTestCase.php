<?php

final class PhortuneCurrencyTestCase extends PhabricatorTestCase {

  public function testCurrencyFormatForDisplay() {
    $map = array(
      '0' => 'Rp0.00 IDR',
      '1.00' => 'Rp1.00 IDR',
      '-123' => 'Rp-123.00 IDR',
      '50000.00' => 'Rp50000.00 IDR',
    );

    foreach ($map as $input => $expect) {
      $this->assertEqual(
        $expect,
        PhortuneCurrency::newFromString($input, 'IDR')->formatForDisplay(),
        "newFromString({$input})->formatForDisplay()");
    }
  }

  public function testCurrencyFormatBareValue() {

    // NOTE: The PayPal API depends on the behavior of the bare value format!

    $map = array(
      '0' => '0.00',
      '1.00' => '1.00',
      '-123' => '-123.00',
      '50000.00' => '50000.00',
    );

    foreach ($map as $input => $expect) {
      $this->assertEqual(
        $expect,
        PhortuneCurrency::newFromString($input, 'IDR')->formatBareValue(),
        "newFromString({$input})->formatBareValue()");
    }
  }

  public function testCurrencyFromString() {

    $map = array(
      '1.00' => 1,
      '1.00 IDR' => 1,
      'Rp1.00' => 1,
      'Rp1.00 IDR' => 1,
      '-Rp1.00 IDR' => -1,
      'Rp-1.00 IDR' => -1,
      '1' => 1,
      '99' => 99,
      'Rp99' => 99,
      '-Rp99' => -99,
      'Rp-99' => -99,
      'Rp99 IDR' => 99,
    );

    foreach ($map as $input => $expect) {
      $this->assertEqual(
        $expect,
        PhortuneCurrency::newFromString($input, 'IDR')->getValue(),
        "newFromString({$input})->getValue()");
    }
  }

  public function testInvalidCurrencyFromString() {
    $map = array(
      '--1',
      'Rp$1',
      '1 JPY',
      'buck fiddy',
      '1.2.3',
      '1 dollar',
    );

    foreach ($map as $input) {
      $caught = null;
      try {
        PhortuneCurrency::newFromString($input, 'IDR');
      } catch (Exception $ex) {
        $caught = $ex;
      }
      $this->assertTrue($caught instanceof Exception, "{$input}");
    }
  }

  public function testCurrencyRanges() {
    $value = PhortuneCurrency::newFromString('3.00 IDR');

    $value->assertInRange('2.00 IDR', '4.00 IDR');
    $value->assertInRange('2.00 IDR', null);
    $value->assertInRange(null, '4.00 IDR');
    $value->assertInRange(null, null);

    $caught = null;
    try {
      $value->assertInRange('4.00 IDR', null);
    } catch (Exception $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught instanceof Exception);

    $caught = null;
    try {
      $value->assertInRange(null, '2.00 IDR');
    } catch (Exception $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught instanceof Exception);

    $caught = null;
    try {
      // Minimum and maximum are reversed here.
      $value->assertInRange('4.00 IDR', '2.00 IDR');
    } catch (Exception $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught instanceof Exception);

    $credit = PhortuneCurrency::newFromString('-3.00 IDR');
    $credit->assertInRange('-4.00 IDR', '-2.00 IDR');
    $credit->assertInRange('-4.00 IDR', null);
    $credit->assertInRange(null, '-2.00 IDR');
    $credit->assertInRange(null, null);

    $caught = null;
    try {
      $credit->assertInRange('-2.00 IDR', null);
    } catch (Exception $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught instanceof Exception);

    $caught = null;
    try {
      $credit->assertInRange(null, '-4.00 IDR');
    } catch (Exception $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught instanceof Exception);
  }

  public function testAddCurrency() {
    $cases = array(
      array('0.00 IDR', '0.00 IDR', 'Rp0.00 IDR'),
      array('1.00 IDR', '1.00 IDR', 'Rp2.00 IDR'),
      array('1.23 IDR', '9.77 IDR', 'Rp11.00 IDR'),
    );

    foreach ($cases as $case) {
      list($l, $r, $expect) = $case;

      $l = PhortuneCurrency::newFromString($l);
      $r = PhortuneCurrency::newFromString($r);
      $sum = $l->add($r);

      $this->assertEqual($expect, $sum->formatForDisplay());
    }
  }

}

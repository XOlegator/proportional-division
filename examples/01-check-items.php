<?php

declare(strict_types=1);

/**
 * Distribution of the advance payment by check items
 */

require __DIR__ . '/../vendor/autoload.php';

use \Xolegator\ProportionalDivision\ProportionalDivision;

/**
 * List of cash receipt items.
 * The amount and quantity are specified for each position.
 *
 * @var array
 */
$arCheckItems = [
    [
        'sum'   => 1000,
        'count' => 4,
    ],
    [
        'sum'   => 2100,
        'count' => 3,
    ],
];

/**
 * An advance payment made by the buyer.
 * The advance payment should be distributed among the positions of the check
 * in proportion to their amounts in the check.
 *
 * @var int
 */
$advancePayment = 1112;

for ($precision = 0; $precision <= 2; $precision++) {
    $arResult = null;
    try {
        $arResult = ProportionalDivision::getProportionalSums(
            $advancePayment,
            $arCheckItems,
            $precision
        );
    } catch (\RangeException $exception) {
        echo $exception->getMessage() . PHP_EOL;
    }

    if (null !== $arResult) {
        break;
    }
}

var_dump($arResult);

// Result:
//Could not distribute the remainder of the rounding
//Could not distribute the remainder of the rounding
//array(2) {
//  [0] => array(3) {
//    ["init"] => int(1000)
//    ["final"] => float(358.76)
//    ["count"] => int(4)
//  }
//  [1]=>
//  array(3) {
//    ["init"] => int(2100)
//    ["final"] => float(753.24)
//    ["count"] => int(3)
//  }
//}

// Final in result: 358.76 + 753.24 = advance payment (1112)
// In this example, the distribution cannot be performed with rounding to integers (precision = 0).
// It is also impossible to distribute with rounding to tenths (precision = 1).
// The distribution is possible only with rounding to hundredths (precision = 2).

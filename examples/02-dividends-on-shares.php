<?php

declare(strict_types=1);

/**
 * Distribution of the amount of dividends
 * in proportion to the ownership of shares
 */

require __DIR__ . '/../vendor/autoload.php';

use \Xolegator\ProportionalDivision\ProportionalDivision;

/**
 * List of shareholders.
 * When distributing dividends,
 * we take into account only the value of shares in ownership
 * and do not take into account the number of shares.
 *
 * @var array
 */
$arShareholders = [
    [
        'sum'   => 500,
        'count' => null,
    ],
    [
        'sum'   => 3750,
        'count' => null,
    ],
    [
        'sum'   => 1700,
        'count' => null,
    ],
];

/**
 * The company's profit, which goes to pay dividends to shareholders
 *
 * @var int
 */
$amountDividendPayment = 828;

$arResult = null;
try {
    $arResult = ProportionalDivision::getProportionalSums(
        $amountDividendPayment,
        $arShareholders,
        0
    );
} catch (\RangeException $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

var_dump($arResult);

// Result:
//array(3) {
//  [0] => array(3) {
//    ["init"] => int(500)
//    ["final"] => int(70)
//    ["count"] => NULL
//  }
//  [1] => array(3) {
//    ["init"] => int(3750)
//    ["final"] => int(521)
//    ["count"] => NULL
//  }
//  [2] => array(3) {
//    ["init"] => int(1700)
//    ["final"] => int(237)
//    ["count"] => NULL
//  }
//}

// Final in result: 70 + 521 + 237 = amount of dividend payment (828)

<?php

declare(strict_types=1);

/**
 * The distribution of the discount is proportional to the purchase items
 */

require __DIR__ . '/../vendor/autoload.php';

use \Xolegator\ProportionalDivision\ProportionalDivision;

/**
 * A shopping list with the amount and quantity for each item
 *
 * @var array
 */
$shoppingList = [
    [
        'sum'   => 1230,
        'count' => 2,
    ],
    [
        'sum'   => 3750,
        'count' => 5,
    ],
    [
        'sum'   => 1700,
        'count' => 1,
    ],
];

/**
 * The discount amount for the entire purchase
 *
 * @var int
 */
$discountAmount = 500;

$arResult = null;
try {
    $arResult = ProportionalDivision::getProportionalSums(
        $discountAmount,
        $shoppingList,
        0
    );
} catch (\RangeException $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

var_dump($arResult);

// Result:
//array(3) {
//  [0] => array(3) {
//    ["init"] => int(1230)
//    ["final"] => int(88)
//    ["count"] => int(2)
//  }
//  [1] => array(3) {
//    ["init"] => int(3750)
//    ["final"] => int(285)
//    ["count"] => int(5)
//  }
//  [2] => array(3) {
//    ["init"] => int(1700)
//    ["final"] => int(127)
//    ["count"] => int(1)
//  }
//}

// Final in result: 88 + 285 + 127 = discount amount (500)

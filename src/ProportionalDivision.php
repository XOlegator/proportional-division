<?php

declare(strict_types=1);

namespace Xolegator\ProportionalDivision;

class ProportionalDivision
{
    public const ERROR_DISTRIBUTE_AMOUNT_FOR_COEFFICIENT = 1001;

    public const ERROR_DISTRIBUTE_ROUNDING = 1002;

    /**
     * Метод выполняет пропорциональное распределение суммы
     * в соответствии с заданными коэффициентами распределения.
     * Также может выполняться проверка полного деления суммы коэффициента на его количество.
     * Например, при нулевой точности для чётного количества штук товара
     * было неправильно получить нечётную сумму после распределения, -
     * правильно немного увеличить сумму распределения (в ущерб пропорциональности),
     * чтобы добиться ровного распределения по количеству.
     * Используется, например, при распределении скидки равномерно по позициям корзины.
     *
     * @param float $sum            Распределяемая сумма
     * @param array $arCoefficients Массив коэффициентов распределения, где ключи - определённые значения,
     *                              которые также будут возвращены в виде ключей результирующего массива.
     *                              Значения - массив с ключами:
     *                              "sum"   - величина коэффициента (сумма, а не цена)
     *                              "count" - количество для коэффициента
     * @param int   $precision      Точность округления при распределении. Если передать 0,
     *                              то все суммы после распределения будут целыми числами
     *
     * @throws \RangeException Выбрасывается исключение в случае,
     *                         если невозможно ровно распределить по заданным параметрам
     *
     * @return array Массив, где сохранены ключи исходного массива $arCoefficients,
     *               а значения - массив с ключами:
     *               "init"  - начальная сумма, равная соответствующему входному коэффициенту
     *               "final" - сумма после распределения
     */
    public static function getProportionalSums(
        float $sum,
        array $arCoefficients,
        int $precision
    ): array {
        $arResult = [];

        /**
         * Сумма значений всех коэффициентов
         *
         * @var float
         */
        $sumCoefficients = 0.0;

        /**
         * Значение максимального коэффициента по модулю
         *
         * @var float
         */
        $maxCoefficient = 0.0;

        /**
         * Ключ массива для максимального коэффициента по модулю
         *
         * @var mixed
         */
        $maxCoefficientKey = null;

        /**
         * Распределённая сумма
         *
         * @var float
         */
        $allocatedAmount = 0;

        foreach ($arCoefficients as $keyCoefficient => $coefficient) {
            if (is_null($maxCoefficientKey)) {
                $maxCoefficientKey = $keyCoefficient;
            }

            $absCoefficient = abs($coefficient['sum']);
            if ($maxCoefficient < $absCoefficient) {
                $maxCoefficient = $absCoefficient;
                $maxCoefficientKey = $keyCoefficient;
            }
            $sumCoefficients += $coefficient['sum'];
        }
        if ($sumCoefficients) {
            /**
             * Шаг, который прибавляем в попытках распределить сумму с учётом количества
             *
             * @var float
             */
            $addStep = (0 === $precision) ? 1 : (1 / (10 ** $precision));

            foreach ($arCoefficients as $keyCoefficient => $coefficient) {
                /**
                 * Флаг, удалось ли подобрать сумму распределения для текущего коэффициента
                 *
                 * @var bool
                 */
                $isOk = false;

                /**
                 * Количество попыток подобрать сумму распределения
                 *
                 * @var int
                 */
                $i = 0;

                // Далее вычисляем сумму распределения с учётом заданного количества
                do {
                    $result = round(($sum * $coefficient['sum'] / $sumCoefficients), $precision) + $i * $addStep;

                    // Проверим распределённую сумму коэффициента относительно его количества
                    if (isset($coefficient['count']) && $coefficient['count'] > 0) {
                        // Проверяем, прошли ли проверку по количеству (распределяется ли ровно по заданному количеству)
                        if (round($result / $coefficient['count'], $precision) === ($result / $coefficient['count'])) {
                            $isOk = true;
                        }
                    } else {
                        // Количество не задано, значит не проверяем распределение по количеству
                        $isOk = true;
                    }

                    $i++;
                    if ($i > 100) {
                        // Мы старались долго. Пора признать, что ничего не выйдет
                        throw new \RangeException(
                            'Could not distribute the sum for the coefficient #' . $keyCoefficient,
                            self::ERROR_DISTRIBUTE_AMOUNT_FOR_COEFFICIENT
                        );
                    }
                } while (!$isOk);

                // Если сюда дошли, значит удалось вычислить сумму распределения
                $arResult[$keyCoefficient] = [
                    'init'  => $coefficient['sum'],
                    'final' => (0 === $precision) ? (int) $result : $result,
                    'count' => $coefficient['count']
                ];

                $allocatedAmount += $result;
            }

            if ($allocatedAmount !== $sum) {
                // Есть погрешности округления, которые надо куда-то впихнуть
                $tmpRes = $arResult[$maxCoefficientKey]['final'] + $sum - $allocatedAmount;
                if (!isset($arResult[$maxCoefficientKey]['count'])
                    || 1 === $arResult[$maxCoefficientKey]['count']
                    || ($arResult[$maxCoefficientKey]['count'] > 0
                        && (round($tmpRes / $arResult[$maxCoefficientKey]['count'], $precision) === ($tmpRes / $arResult[$maxCoefficientKey]['count']))
                    )
                ) {
                    // Погрешности округления отнесём на коэффициент с максимальным весом
                    $arResult[$maxCoefficientKey]['final'] = (0 === $precision) ? (int) $tmpRes : $tmpRes;
                } else {
                    // Погрешности округления нельзя отнести на коэффициент с максимальным весом
                    // Надо подыскать другой коэффициент
                    $isOk = false;
                    foreach ($arCoefficients as $keyCoefficient => $coefficient) {
                        if ($keyCoefficient !== $maxCoefficientKey) {
                            // Пробуем погрешность округления впихнуть в текущий коэффициент
                            $tmpRes = $arResult[$keyCoefficient]['final'] + $sum - $allocatedAmount;
                            if (!isset($arResult[$keyCoefficient]['count'])
                                || 1 === $arResult[$keyCoefficient]['count']
                                || ($arResult[$keyCoefficient]['count'] > 0
                                    && (round($tmpRes / $arResult[$keyCoefficient]['count'], $precision) === ($tmpRes / $arResult[$keyCoefficient]['count']))
                                )
                            ) {
                                // Погрешности округления отнесём на коэффициент с максимальным весом
                                $arResult[$keyCoefficient]['final'] = (0 === $precision) ? (int) $tmpRes : $tmpRes;
                                $isOk = true;
                                break;
                            }
                        }
                    }
                    if (!$isOk) {
                        throw new \RangeException(
                            'Could not distribute the remainder of the rounding',
                            self::ERROR_DISTRIBUTE_ROUNDING
                        );
                    }
                }
            }
        }

        return $arResult;
    }
}

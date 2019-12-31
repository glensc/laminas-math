<?php

/**
 * @see       https://github.com/laminas/laminas-math for the canonical source repository
 * @copyright https://github.com/laminas/laminas-math/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-math/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Math;

use Laminas\Math\Rand;

/**
 * @group      Laminas_Math
 */
class RandTest extends \PHPUnit_Framework_TestCase
{
    public static function provideRandInt()
    {
        return [
            [2, 1, 10000, 100, 0.9, 1.1],
            [2, 1, 10000, 100, 0.8, 1.2]
        ];
    }

    public function testRandBytes()
    {
        for ($length = 1; $length < 4096; $length++) {
            $rand = Rand::getBytes($length);
            $this->assertNotFalse($rand);
            $this->assertEquals($length, mb_strlen($rand, '8bit'));
        }
    }

    /**
     * @expectedException Laminas\Math\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid parameter provided to getBytes(length)
     */
    public function testWrongRandBytesParam()
    {
        $rand = Rand::getBytes('foo');
    }

    /**
     * @expectedException Laminas\Math\Exception\DomainException
     * @expectedExceptionMessage The length must be a positive number in getBytes(length)
     */
    public function testZeroRandBytesParam()
    {
        $rand = Rand::getBytes(0);
    }

    /**
     * @expectedException Laminas\Math\Exception\DomainException
     * @expectedExceptionMessage The length must be a positive number in getBytes(length)
     */
    public function testNegativeRandBytesParam()
    {
        $rand = Rand::getBytes(-1);
    }

    public function testRandBoolean()
    {
        for ($length = 1; $length < 512; $length++) {
            $rand = Rand::getBoolean();
            $this->assertInternalType('bool', $rand);
        }
    }

    /**
     * @dataProvider dataProviderForTestRandIntegerRangeTest
     */
    public function testRandIntegerRangeTest($min, $max, $cycles)
    {
        $counter = [];
        for ($i = $min; $i <= $max; $i++) {
            $counter[$i] = 0;
        }

        for ($j = 0; $j < $cycles; $j++) {
            $value = Rand::getInteger($min, $max);
            $this->assertInternalType('integer', $value);
            $this->assertGreaterThanOrEqual($min, $value);
            $this->assertLessThanOrEqual($max, $value);
            $counter[$value]++;
        }

        foreach ($counter as $value => $count) {
            $this->assertGreaterThan(0, $count, sprintf('The bucket for value %d is empty.', $value));
        }
    }

    /**
     * @return array
     */
    public function dataProviderForTestRandIntegerRangeTest()
    {
        return [
            [0, 100, 10000],
            [-100, 100, 10000],
            [-100, 50, 10000],
            [0, 63, 10000],
            [0, 64, 10000],
            [0, 65, 10000],
        ];
    }

    /**
     * A Monte Carlo test that generates $cycles numbers from 0 to $tot
     * and test if the numbers are above or below the line y=x with a
     * frequency range of [$min, $max]
     *
     * @dataProvider provideRandInt
     */
    public function testRandInteger($num, $valid, $cycles, $tot, $min, $max)
    {
        try {
            $test = Rand::getBytes(1);
        } catch (\Laminas\Math\Exception\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $i     = 0;
        $count = 0;
        do {
            $up   = 0;
            $down = 0;
            for ($i = 0; $i < $cycles; $i++) {
                $x = Rand::getInteger(0, $tot);
                $y = Rand::getInteger(0, $tot);
                if ($x > $y) {
                    $up++;
                } elseif ($x < $y) {
                    $down++;
                }
            }
            $this->assertGreaterThan(0, $up);
            $this->assertGreaterThan(0, $down);
            $ratio = $up / $down;
            if ($ratio > $min && $ratio < $max) {
                $count++;
            }
            $i++;
        } while ($i < $num && $count < $valid);

        if ($count < $valid) {
            $this->fail('The random number generator failed the Monte Carlo test');
        }
    }

    /**
     * @expectedException Laminas\Math\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid parameters provided to getInteger(min, max)
     */
    public function testWrongFirstParamGetInteger()
    {
        $rand = Rand::getInteger('foo', 0);
    }

    /**
     * @expectedException Laminas\Math\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid parameters provided to getInteger(min, max)
     */
    public function testWrongSecondParamGetInteger()
    {
        $rand = Rand::getInteger(0, 'foo');
    }

    /**
     * @expectedException Laminas\Math\Exception\DomainException
     * @expectedExceptionMessage The min parameter must be lower than max in getInteger(min, max)
     */
    public function testIntegerRangeFail()
    {
        $rand = Rand::getInteger(100, 0);
    }

    public function testIntegerRangeOverflow()
    {
        $values = 0;
        $cycles = 100;
        for ($i = 0; $i < $cycles; $i++) {
            $values += Rand::getInteger(0, PHP_INT_MAX);
        }

        // It's not possible to test $values > 0 because $values may suffer a integer overflow
        $this->assertNotEquals(0, $values);
    }

    public function testRandFloat()
    {
        for ($length = 1; $length < 512; $length++) {
            $rand = Rand::getFloat();
            $this->assertInternalType('float', $rand);
            $this->assertGreaterThanOrEqual(0, $rand);
            $this->assertLessThanOrEqual(1, $rand);
        }
    }

    public function testGetString()
    {
        for ($length = 1; $length < 512; $length++) {
            $rand = Rand::getString($length, '0123456789abcdef');
            $this->assertEquals(strlen($rand), $length);
            $this->assertEquals(1, preg_match('#^[0-9a-f]+$#', $rand));
        }
    }

    public function testGetStringBase64()
    {
        for ($length = 1; $length < 512; $length++) {
            $rand = Rand::getString($length);
            $this->assertEquals(strlen($rand), $length);
            $this->assertEquals(1, preg_match('#^[0-9a-zA-Z+/]+$#', $rand));
        }
    }
}

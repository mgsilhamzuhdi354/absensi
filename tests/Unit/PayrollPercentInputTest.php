<?php

namespace Tests\Unit;

use App\Http\Controllers\PayrollController;
use App\Http\Controllers\RekapDataController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PayrollPercentInputTest extends TestCase
{
    /** @test */
    public function rekap_payroll_percent_inputs_are_normalized_for_decimal_columns()
    {
        $controller = new RekapDataController();

        $this->assertSame('1.00', $this->normalize($controller, '1%', 0));
        $this->assertSame('1.50', $this->normalize($controller, '1,5%', 0));
        $this->assertSame('2.00', $this->normalize($controller, ' 2 % ', 0));
        $this->assertSame('1.00', $this->normalize($controller, null, 1));
        $this->assertSame('2.00', $this->normalize($controller, 'abc', 2));
    }

    /** @test */
    public function payroll_edit_percent_inputs_are_normalized_for_decimal_columns()
    {
        $controller = new PayrollController();

        $this->assertSame('1.00', $this->normalize($controller, '1%', 0));
        $this->assertSame('0.75', $this->normalize($controller, '0,75%', 0));
        $this->assertSame('2.00', $this->normalize($controller, '', 2));
    }

    private function normalize($controller, $value, $default)
    {
        $method = new ReflectionMethod($controller, 'normalizePercentInput');
        $method->setAccessible(true);

        return $method->invoke($controller, $value, $default);
    }
}

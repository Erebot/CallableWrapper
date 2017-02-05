<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!class_exists('PHPUnit_Framework_TestCase')) {
    class_alias('\\PHPUnit\\Framework\\TestCase', 'PHPUnit_Framework_TestCase');
}

function callableWrapperTestHelper(&$res)
{
    return $res = true;
}

class CallableWrapperTestHelper
{
    public static function helper(&$res)
    {
        return $res = true;
    }
}

/**
 * This test checks whether the various forms of callable objects
 * (closures, functions, methods, anonymouse functions, invokable objet)
 * are properly handled by the wrapper.
 *
 * It also makes sure that references are not lost when invoking the wrapped code.
 */
class CallableWrapperTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        \Erebot\CallableWrapper::initialize();
    }

    // @covers \Erebot\CallableWrapper::wrap
    public function testCallClosure()
    {
        $ok = false;
        $wrapped = \Erebot\CallableWrapper::wrap(function (&$res) { return $res = true; });
        $this->assertTrue($wrapped($ok));
        $this->assertTrue($ok);
    }

    // @covers \Erebot\CallableWrapper::wrap
    public function testCallFunction()
    {
        $ok = false;
        $wrapped = \Erebot\CallableWrapper::wrap('callableWrapperTestHelper');
        $this->assertTrue($wrapped($ok));
        $this->assertTrue($ok);
    }

    // @covers \Erebot\CallableWrapper::wrap
    public function testCallMethod()
    {
        $ok = false;
        $wrapped = \Erebot\CallableWrapper::wrap(array('CallableWrapperTestHelper', 'helper'));
        $this->assertTrue($wrapped($ok));
        $this->assertTrue($ok);
    }

    // @covers \Erebot\CallableWrapper::wrap
    public function testCallDynamicFunction()
    {
        $ok = false;
        $wrapped = \Erebot\CallableWrapper::wrap(create_function('&$res', 'return $res = true;'));
        $this->assertTrue($wrapped($ok));
        $this->assertTrue($ok);
    }
}

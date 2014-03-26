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

namespace Erebot\CallableWrapper\Tests {
    function CallableWrapperTypehintHelper(callable $f)
    {
    }
}

namespace {
    function CallableWrapperTypehintHelper(callable $f)
    {
    }

    class CallableTypehintTest extends PHPUnit_Framework_TestCase
    {
        public function setUp()
        {
            parent::setUp();
            \Erebot\CallableWrapper::initialize();
        }

        // @covers \Erebot\CallableWrapper::initialize
        public function testTypehintInNamespace()
        {
            // This test is mainly intended for PHP 5.3.x
            // where the callable typehint does not exist
            // natively.

            // The "class" must be defined first for this to work.
            class_exists('\\Erebot\\CallableWrapper\\Tests\\callable');
            $wrapped = \Erebot\CallableWrapper::wrap(function () {});
            \Erebot\CallableWrapper\Tests\CallableWrapperTypehintHelper($wrapped);
        }

        // @covers \Erebot\CallableWrapper::initialize
        public function testTypehintInGlobalScope()
        {
            // This test is mainly intended for PHP 5.3.x
            // where the callable typehint does not exist
            // natively.

            // The "class" must be defined first for this to work.
            class_exists('\\callable');
            $wrapped = \Erebot\CallableWrapper::wrap(function () {});
            \CallableWrapperTypehintHelper($wrapped);
        }
    }
}

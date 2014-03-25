<?php
/*
    This file is part of Erebot, a modular IRC bot written in PHP.

    Copyright © 2010 François Poirotte

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

namespace Erebot;

/**
 * \brief
 *      Class used to represent anything that is callable.
 *
 * This class can represent a wild range of callable items
 * supported by PHP (functions, lambdas, methods, closures, etc.).
 */
class CallableWrapper implements \Erebot\CallableInterface
{
    /// Inner callable object, as used by PHP.
    protected $callable;

    /// Human representation of the inner callable.
    protected $representation;

    /**
     * Constructs a new callable object, abstracting
     * differences between the different constructs
     * PHP supports.
     *
     * \param mixed $callable
     *      A callable item. It must be compatible
     *      with the PHP callback pseudo-type.
     *
     * \throw ::InvalidArgumentException
     *      The given item is not compatible
     *      with the PHP callback pseudo-type.
     *
     * \see
     *      More information on the callback pseudo-type can be found here:
     *      http://php.net/language.pseudo-types.php#language.types.callback
     */
    public function __construct($callable)
    {
        if (!is_callable($callable, false, $representation)) {
            throw new \InvalidArgumentException('Not a valid callable');
        }

        // This happens for anonymous functions
        // created with create_function().
        if (is_string($callable) && $representation == "") {
            $representation = $callable;
        }

        $this->callable        = $callable;
        $this->representation  = $representation;
    }

    /**
     * Invokes the callable object represented by this
     * instance, using the given array as a list of arguments.
     *
     * \param array $args
     *      An array whose values will become the arguments
     *      for the inner callable.
     *
     * \retval mixed
     *      Value returned by the inner callable.
     *
     * \note
     *      This method is smart enough to preserve
     *      references.
     */
    public function invokeArgs(&$args)
    {
        return call_user_func_array($this->callable, $args);
    }

    public function __toString()
    {
        return $this->representation;
    }

    public function __invoke()
    {
        // HACK:    we use debug_backtrace() to get (and pass along)
        //          references for call_user_func_array().

        if (version_compare(PHP_VERSION, '5.4', '>=')) {
            // Starting with PHP 5.4.0, it is possible to limit
            // the number of stack frames returned.
            $bt = debug_backtrace(0, 1);
        } elseif (version_compare(PHP_VERSION, '5.3.6', '>=')) {
            // Starting with PHP 5.3.6, the first argument
            // to debug_backtrace() is a bitmask of options.
            $bt = debug_backtrace(0);
        } else {
            $bt = debug_backtrace(false);
        }

        if (isset($bt[0]['args'])) {
            $args =& $bt[0]['args'];
        } else {
            $args = array();
        }

        return call_user_func(array($this, 'invokeArgs'), $args);
    }

    public static function initialize()
    {
        if (!defined('T_CALLABLE') && !class_exists('callable', false)) {
            if (class_alias('\\Erebot\\CallableWrapper', 'callable', true) !== true) {
                throw new \RuntimeException('Could not load wrapper');
            }
        }
    }
}

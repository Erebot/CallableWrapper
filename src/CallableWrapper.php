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
abstract class CallableWrapper implements \Erebot\CallableInterface
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
    protected function __construct($callable)
    {
        if (!is_callable($callable, FALSE, $representation)) {
            throw new Erebot_InvalidValueException('Not a valid callable');
        }

        // This happens for anonymous functions
        // created with create_function().
        if (is_string($callable) && $representation == "") {
            $representation = $callable;
        }

        $this->callable        = $callable;
        $this->representation  = $representation;
    }

    public function __toString()
    {
        return $this->representation;
    }

    public static function wrap($callable)
    {
        if (!is_callable($callable, false, $representation)) {
            throw new \InvalidArgumentException('Not a valid callable');
        }

        // This happens for anonymous functions
        // created with create_function().
        if (is_string($callable) && $representation == "") {
            $representation = $callable;
        }

        $parts = explode('::', $representation);
        if (count($parts) == 1) {
            // We wrapped a function.
            $reflector = new \ReflectionFunction($callable);
        } else {
            if (!is_array($callable)) {
                // We wrapped a Closure or some invokable object.
                $callable = array($callable, $parts[1]);
            }
            $reflector = new \ReflectionMethod($callable[0], $callable[1]);
        }

        $args = array();
        foreach ($reflector->getParameters() as $argReflect) {
            $arg = '';
            if ($argReflect->isPassedByReference()) {
                $arg .= '&';
            }
            $arg .= '$a' . count($args);
            if ($argReflect->isOptional()) {
                $arg .= '=' . var_export($argReflect->getDefaultValue(), true);
            } else {
                $arg .= '=null';
            }
            $args[] = $arg;
        }

        $args   = implode(',', $args);
        $class  = 'Wrapped_' . sha1($args);
        if (!class_exists("\\Erebot\\CallableWrapper\\$class", false)) {
            $tpl = "
                namespace Erebot\\CallableWrapper {
                    class $class extends \\Erebot\\CallableWrapper
                    {
                        public function __invoke($args)
                        {
                            // HACK: we use debug_backtrace() to get (and pass along)
                            // references for call_user_func_array().

                            // Starting with PHP 5.4.0, it is possible to limit
                            // the number of stack frames returned.
                            if (version_compare(PHP_VERSION, '5.4', '>='))
                                \$bt = debug_backtrace(0, 1);
                            // Starting with PHP 5.3.6, the first argument
                            // to debug_backtrace() is a bitmask of options.
                            else if (version_compare(PHP_VERSION, '5.3.6', '>='))
                                \$bt = debug_backtrace(0);
                            else
                                \$bt = debug_backtrace(FALSE);

                            if (isset(\$bt[0]['args']))
                                \$args =& \$bt[0]['args'];
                            else
                                \$args = array();
                            return call_user_func_array(\$this->callable, \$args);
                        }
                    }
                }
            ";
            eval($tpl);
        }
        $class = "\\Erebot\\CallableWrapper\\$class";
        return new $class($callable);
    }

    public static function initialize()
    {
        static $initialized = false;
        if (!defined('T_CALLABLE') && !$initialized) {
            spl_autoload_register(
                function ($class) {
                    if (ltrim(substr($class, strrpos($class, '\\')), '\\') === 'callable') {
                        if (class_alias('\\Erebot\\CallableInterface', "$class", true) !== true) {
                            throw new \RuntimeException('Could not load wrapper');
                        }
                        return true;
                    }
                    return false;
                },
                true
            );
            $initialized = true;
        }
    }
}

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
     * \param string $representation
     *      Human readable representation of the callable.
     *
     * \see
     *      More information on the callback pseudo-type can be found here:
     *      http://php.net/language.pseudo-types.php#language.types.callback
     */
    protected function __construct($callable, $representation)
    {
        $this->callable        = $callable;
        $this->representation  = $representation;
    }

    /**
     * Returns a human readable representation of this callable.
     *
     * For functions (including anonymous functions created with
     * create_function()), this is a string containing the name
     * of that function.
     * For methods and objects that implement the __invoke()
     * magic method (including Closures), this is a string
     * of the form "ClassName::methodName".
     *
     * \retval string
     *      Human readable representation of this callable.
     */
    public function __toString()
    {
        return $this->representation;
    }

    /**
     * Wraps an existing callable piece of code.
     *
     * \param mixed $callable
     *      Callable piece of code to wrap.
     *
     * \retval Erebot::CallableInterface
     *      An object of a class implementing the
     *      Erebot::CallableInterface is returned.
     *      The precise type of the object will usually vary
     *      between calls as new types are created on-the-fly
     *      if needed.
     */
    public static function wrap($callable)
    {
        $representation = static::represent($callable);
        $parts          = explode('::', $representation);
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
        return new $class($callable, $representation);
    }

    /**
     * Initialize the wrapper.
     *
     * \param bool $existing
     *      (optional) Whether to inject the wrapper inside existing
     *      namespaces. You don't need to pass this argument: the wrapper
     *      already does so internally when needed.
     *
     * \note
     *      This method must be called before using any of the wrapper's
     *      other methods. It registers an autoloader that automatically
     *      adds a "callable" typehint to namespaces on PHP 5.3.x when
     *      a new class/interface is autoloaded.
     *
     * \note
     *      This method needs to be called only once, unless you're using
     *      functions or requiring/including files manually, in which case
     *      it is advisable to call this method periodically (eg. using
     *      register_tick_function() and declare(ticks=N) with an adequate
     *      value for N depending on your code).
     */
    public static function initialize()
    {
        static $initialized = false;
        static $structures  = array();

        if (!defined('T_CALLABLE')) {
            if (!$initialized) {
                spl_autoload_register(
                    function ($class) {
                        $parts  = array_map('strrev', explode('\\', strrev($class), 2));
                        $short  = array_shift($parts);
                        $ns     = (string) array_shift($parts);

                        if ($short === 'callable') {
                            // The class to load is "callable", inject the alias.
                            if (class_alias('\\Erebot\\CallableInterface', "$class", true) !== true) {
                                throw new \RuntimeException('Could not load wrapper');
                            }
                            return true;
                        }

                        // Otherwise, inject the alias in the namespace
                        // of the class being loaded.
                        class_exists("$ns\\callable");
                        return false;
                    },
                    true
                );
                $initialized = true;
            }

            // Inject the alias in existing namespaces.
            $funcs  = get_defined_functions();
            $new    = array_merge(
                $funcs['user'],
                get_declared_classes(),
                get_declared_interfaces()
            );

            if ($new != $structures) {
                $newNS = array();
                foreach (array_diff($new, $structures) as $structure) {
                        $parts = explode('\\', strrev($structure), 2);
                        array_shift($parts); // Remove class/interface name.
                        $newNS[] = (string) array_shift($parts);
                }
                $structures = $new;
                $newNS      = array_unique($newNS);
                foreach ($newNS as $ns) {
                    class_exists(strrev($ns) . '\\callable');
                }
            }
        }
    }

    /**
     * Returns a human readable representation of a callable.
     *
     * For functions (including anonymous functions created with
     * create_function()), this is a string containing the name
     * of that function.
     * For methods and objects that implement the __invoke()
     * magic method (including Closures), this is a string
     * of the form "ClassName::methodName".
     *
     * \param mixed $callable
     *      Callable piece of code to describe.
     *
     * \retval string
     *      Human readable representation of the callable.
     *
     * \throw InvalidArgumentException
     *      The given argument does not represent a callable
     *      piece of code.
     */
    public static function represent($callable)
    {
        if (!is_callable($callable, false, $representation)) {
            throw new \InvalidArgumentException('Not a valid callable');
        }

        // This happens for anonymous functions
        // created with create_function().
        if (is_string($callable) && $representation == "") {
            $representation = $callable;
        }
        return $representation;
    }
}

A wrapper for the callable typehint in PHP < 5.4.0
==================================================

Installation
------------

Download the `composer.phar <https://getcomposer.org/composer.phar>`_
executable or use the installer.

..  sourcecode:: bash

    $ curl -sS https://getcomposer.org/installer | php

Create a ``composer.json`` that requires Erebot's Callable component.

..  sourcecode:: json

    {
        "require": {
            "erebot/callable-wrapper": "dev-master"
        }
    }

Run Composer.

..  sourcecode:: bash

    $ php composer.phar install


Usage
-----

To use the wrapper, first include composer's autoloader and call
``Erebot\\CallableWrapper::initialize()``, this will make sure everything
is set up properly.

Now, use ``Erebot\\CallableWrapper::wrap()`` every time you want to execute
some callable code while using the ``callable`` typehint.

..  sourcecode:: php

    <?php
        // Load composer's autoloader
        require_once PROJECT_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        // Initialize the wrapper
        Erebot\CallableWrapper::initialize();

        // Define a function/method that uses the "callable" typehint
        // as you normally would, even for PHP 5.3.x.
        function invokeCode(callable $code) {
            $result = null;
            $code($result);
            return $result;
        }

        // Wrap some code to make it compatible with the typehint.
        // In this case, we used a closure, but you may use anything
        // that is callable by PHP's standards (eg. a function, a method,
        // an anonymous function or an invokable object would be fine too)
        $wrapped = Erebot\CallableWrapper::wrap(
            function (&$retval) {
                $retval = 42;
            }
        );

        // Outputs "int(42)" because the $result from invokeCode()
        // was by reference to the wrapped closure and modified there.
        var_dump(invokeCode($wrapped));
    ?>


How it works
------------

This is really a two-part process.

Under the hood, the wrapper's ``initialize`` method first checks whether
it is running on PHP 5.3.x or not.
If it is, then ``Erebot\\CallableInterface`` gets aliased as ``callable``
in every currently-defined namespace. It also defines an autoloader
which is in charge of defining that class on the fly in the current namespace
when required. This is necessary for code such as the following where
``callable`` is used as a variable rather than directly as a typehint:

..  sourcecode:: php

    <?php
        $baseClass = "callable";
        if ($objClass instanceof $baseClass) {
            // ...
        }
    ?>

Since the ``callable`` typehint is only an alias to an interface, this is
why you need to wrap your code using the ``wrap`` method provided,
to convert your callable code into an object compatible with that interface.

This is where the magic of the wrapper's ``wrap`` method comes in.
It checks whether the given code is actually callable and then identifies
that code's signature (names of its arguments, which ones have default values,
which ones are passed by reference etc.) It then creates a new class
on the fly that implements the ``Erebot\\CallableInterface`` interface
by defining an ``__invoke`` magic method with that same signature.

To speed things up a little and to avoid using too much memory, the ``wrap``
method uses a cache, where only a single class is ever created for the same
code signature.

Hence, code which relies on this wrapper works the same way both on PHP 5.3.x
and on later versions.


License
-------

Erebot's Callable component is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Erebot's Callable component is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Erebot's Callable component.  If not, see <http://www.gnu.org/licenses/>.


.. vim: ts=4 et

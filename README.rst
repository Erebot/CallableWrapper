A wrapper for the callable typehint in PHP < 5.4.0
==================================================

Installation & Usage
--------------------

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

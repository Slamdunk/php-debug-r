<?php

namespace
{
    use Slam\Debug\R as DebugR;

    function r($var, $exit = true, $level = 0, $fullstack = false)
    {
        DebugR::$db = \debug_backtrace();
        DebugR::debug($var, $exit, $level, $fullstack);
    }

    function rq($query, array $params, $exit = true, $level = 0, $fullstack = false)
    {
        \uksort($params, function ($key1, $key2) {
            return strcmp(\strlen($key2), \strlen($key1));
        });

        foreach ($params as $key => $value) {
            $query = \str_replace(
                \sprintf(':%s', $key),
                \sprintf('"%s"', \str_replace('"', '\\"', $value)),
                $query
            );
        }

        DebugR::$db = \debug_backtrace();
        DebugR::debug($query, $exit, $level, $fullstack);
    }
}

namespace Slam\Debug
{
    final class R
    {
        public static $db = array();

        private function __construct()
        {
        }

        public static function debug($var, $exit = true, $level = 0, $fullstack = false)
        {
            if (null === $var || \is_scalar($var)) {
                \ob_start();
                \var_dump($var);
                $output = \trim(\ob_get_clean());
            } elseif ($level > 0) {
                $output = \print_r(Doctrine\Debug::export($var, $level), true);
            } else {
                $output = \print_r($var, true);
            }

            if (\PHP_SAPI === 'cli') {
                \fwrite(\STDERR, \PHP_EOL . self::formatDb($fullstack) . $output . \PHP_EOL);
            } else {
                echo '<pre><strong>' . self::formatDb($fullstack) . '</strong><br />' . \htmlspecialchars($output) . '</pre>';
            }

            if ($exit) {
                exit(253);
            }
        }

        private static function formatDb($fullstack)
        {
            $output = '';

            foreach (self::$db as $point) {
                if (isset($point['file'])) {
                    $output .= $point['file'] . '(' . $point['line'] . '): ';
                }

                $output .= (isset($point['class']) ? $point['class'] . '->' : '') . $point['function'];

                $args = array();
                foreach ($point['args'] as $argument) {
                    $args[] = (\is_object($argument)
                        ? \get_class($argument)
                        : \gettype($argument)
                    );
                }

                $output .= '(' . \implode(', ', $args) . ')' . \PHP_EOL;

                if (! $fullstack) {
                    break;
                }
            }

            if (\defined('ROOT_PATH')) {
                return \str_replace(ROOT_PATH, '.', $output);
            }

            return $output;
        }
    }
}

namespace Slam\Debug\Doctrine
{
    use Doctrine\Common\Collections\Collection;

    /**
     * Static class containing most used debug methods.
     *
     * @see   www.doctrine-project.org
     * @since  2.0
     *
     * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
     * @author Jonathan Wage <jonwage@gmail.com>
     * @author Roman Borschel <roman@code-factory.org>
     * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
     *
     * @deprecated the Debug class is deprecated, please use symfony/var-dumper instead
     */
    final class Debug
    {
        /**
         * Private constructor (prevents instantiation).
         */
        private function __construct()
        {
        }

        /**
         * Prints a dump of the public, protected and private properties of $var.
         *
         * @see https://xdebug.org/
         *
         * @param mixed $var       the variable to dump
         * @param int   $maxDepth  the maximum nesting level for object properties
         * @param bool  $stripTags whether output should strip HTML tags
         * @param bool  $echo      Send the dumped value to the output buffer
         *
         * @return string
         */
        public static function dump($var, $maxDepth = 2, $stripTags = true, $echo = true)
        {
            if (\extension_loaded('xdebug')) {
                \ini_set('xdebug.var_display_max_depth', (string) $maxDepth);
            }

            $var = self::export($var, $maxDepth);

            \ob_start();
            \var_dump($var);

            $dump = \ob_get_contents();

            \ob_end_clean();

            $dumpText = ($stripTags ? \strip_tags(\html_entity_decode($dump)) : $dump);

            if ($echo) {
                echo $dumpText;
            }

            return $dumpText;
        }

        /**
         * @param mixed $var
         * @param int   $maxDepth
         *
         * @return mixed
         */
        public static function export($var, $maxDepth)
        {
            $return = null;
            $isObj  = \is_object($var);

            if (! $maxDepth) {
                return \is_object($var) ? \get_class($var)
                    : (\is_array($var) ? 'Array(' . \count($var) . ')' : $var);
            }

            if (\is_array($var)) {
                $return = array();

                foreach ($var as $k => $v) {
                    $return[$k] = self::export($v, $maxDepth - 1);
                }

                return $return;
            }

            if (! $isObj) {
                return $var;
            }

            $return = new \stdclass();
            if ($var instanceof \DateTimeInterface) {
                $return->__CLASS__ = \get_class($var);
                $return->date      = $var->format('c');
                $return->timezone  = $var->getTimezone()->getName();

                return $return;
            }

            $return->__CLASS__ = self::getClass($var);

            if ($var instanceof \ArrayObject || $var instanceof \ArrayIterator) {
                $return->__STORAGE__ = self::export($var->getArrayCopy(), $maxDepth - 1);
            }

            return self::fillReturnWithClassAttributes($var, $return, $maxDepth);
        }

        /**
         * Fill the $return variable with class attributes
         * Based on obj2array function from {@see https://secure.php.net/manual/en/function.get-object-vars.php#47075}.
         *
         * @param object    $var
         * @param \stdClass $return
         * @param int       $maxDepth
         *
         * @return mixed
         */
        private static function fillReturnWithClassAttributes($var, \stdClass $return, $maxDepth)
        {
            $clone = (array) $var;

            foreach (\array_keys($clone) as $key) {
                $aux  = \explode("\0", (string) $key);
                $name = \end($aux);
                if ('' === $aux[0]) {
                    $name .= ':' . ('*' === $aux[1] ? 'protected' : $aux[1] . ':private');
                }
                $return->{$name} = self::export($clone[$key], $maxDepth - 1);
            }

            return $return;
        }

        /**
         * Gets the real class name of an object (even if its a proxy).
         *
         * @param object $object
         *
         * @return string
         */
        private static function getClass($object)
        {
            return \get_class($object);
        }
    }
}

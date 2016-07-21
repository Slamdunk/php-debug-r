<?php

namespace
{
    use Slam\Debug\R as DebugR;

    function r($var, $exit = true, $level = 0, $fullstack = false)
    {
        DebugR::$db = debug_backtrace();
        DebugR::debug($var, $exit, $level, $fullstack);
    }

    function rq($query, $params, $exit = true, $level = 0, $fullstack = false)
    {
        uksort($params, function ($key1, $key2) {
            $len1 = mb_strlen($key1);
            $len2 = mb_strlen($key2);

            if ($len1 === $len2) {
                return 0;
            }

            return ($len1 > $len2) ? -1 : 1;
        });

        foreach ($params as $key => $value) {
            $query = str_replace(
                sprintf(':%s', $key),
                sprintf('"%s"', str_replace('"', '\\"', $value)),
                $query
            );
        }

        DebugR::$db = debug_backtrace();
        DebugR::debug($query, $exit, $level, $fullstack);
    }
}

namespace Slam\Debug
{
    use Doctrine\Common\Util\Debug as DoctrineDebug;

    final class R
    {
        public static $db = array();

        private function __construct()
        {
        }

        public static function debug($var, $exit = true, $level = 0, $fullstack = false)
        {
            if ($var === null or is_scalar($var)) {
                ob_start();
                var_dump($var);
                $output = trim(ob_get_clean());
            } elseif ($level > 0 and class_exists(DoctrineDebug::class)) {
                $output = print_r(DoctrineDebug::export($var, $level), true);
            } else {
                $output = print_r($var, true);
            }

            if (PHP_SAPI === 'cli') {
                echo PHP_EOL . self::formatDb($fullstack) . $output . PHP_EOL;
            } else {
                echo '<pre><strong>' . self::formatDb($fullstack) . '</strong><br />' . htmlspecialchars($output) . '</pre>';
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
                    $args[] = (is_object($argument)
                        ? get_class($argument)
                        : gettype($argument)
                    );
                }

                $output .= '(' . implode(', ', $args) . ')' . PHP_EOL;

                if (! $fullstack) {
                    break;
                }
            }

            if (defined('ROOT_PATH')) {
                return str_replace(ROOT_PATH, '.', $output);
            }

            return $output;
        }
    }
}

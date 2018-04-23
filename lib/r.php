<?php

declare(strict_types=1);

namespace
{
    use Slam\Debug\R as DebugR;

    function r($var, bool $exit = true, int $level = 0, bool $fullstack = false): void
    {
        DebugR::$db = \debug_backtrace();
        DebugR::debug($var, $exit, $level, $fullstack);
    }

    function rq(string $query, array $params, bool $exit = true, int $level = 0, bool $fullstack = false): void
    {
        \uksort($params, function (string $key1, string $key2) {
            return \strlen($key2) <=> \strlen($key1);
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
    use Doctrine\Common\Util\Debug as DoctrineDebug;

    final class R
    {
        public static $db = [];

        private function __construct()
        {
        }

        public static function debug($var, bool $exit = true, int $level = 0, bool $fullstack = false): void
        {
            if (null === $var or \is_scalar($var)) {
                \ob_start();
                \var_dump($var);
                $output = \trim(\ob_get_clean());
            } elseif ($level > 0 and \class_exists(DoctrineDebug::class)) {
                $output = \print_r(DoctrineDebug::export($var, $level), true);
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

        private static function formatDb(bool $fullstack): string
        {
            $output = '';

            foreach (self::$db as $point) {
                if (isset($point['file'])) {
                    $output .= $point['file'] . '(' . $point['line'] . '): ';
                }

                $output .= (isset($point['class']) ? $point['class'] . '->' : '') . $point['function'];

                $args = [];
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

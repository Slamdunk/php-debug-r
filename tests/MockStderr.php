<?php

declare(strict_types=1);

namespace SlamTest\Debug;

use php_user_filter;

final class MockStderr extends php_user_filter
{
    public static $output = '';

    public function filter($in, $out, & $consumed, $closing)
    {
        while ($bucket = \stream_bucket_make_writeable($in)) {
            self::$output = $bucket->data;
            $consumed += $bucket->datalen;
        }

        return \PSFS_PASS_ON;
    }
}

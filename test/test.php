<?php
/**
 * Fixed preg_split.
 * See: http://bugs.php.net/bug.php?id=50605
 * @param $pattern
 * @param $subject
 * @param $limit
 * @param $flags
 * @return array of matches
 */
function preg_split_fixed($pattern, $subject, $limit = -1, $flags = 0)
{
    /**
     * (c) Serov Oleg, blog.serov.name
     */
    if (!($flags & PREG_SPLIT_DELIM_CAPTURE)) {
        return preg_split($pattern, $subject, $limit, $flags);
    }

    $clear_offset = false;

    if (!($flags & PREG_SPLIT_OFFSET_CAPTURE)) {
        $clear_offset = true;
        $flags |= PREG_SPLIT_OFFSET_CAPTURE;
    }

    $return = preg_split($pattern, $subject, $limit, $flags);
    $cleaned = array();
    $current = 0;

    foreach ($return as $match) {
        if ($match[1] != $current) {
            continue;
        }

        $current = $match[1] + strlen($match[0]);

        $cleaned[] = $clear_offset ? $match[0] : $match;
    }

    return $cleaned;
}

print_r(preg_split_fixed(
    '{((a|b)|c)}six',
    '--a--b--c--',
    0,
    PREG_SPLIT_DELIM_CAPTURE
));
exit;
$res1 = preg_split_fixed(
    '{(((a|b)|c))}six',
    '--a--b--c--',
    0,
    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE
);
var_export($res1);
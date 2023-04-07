<?php

declare(strict_types=1);

namespace Czim\MonologJsonContext\Formatters;

/**
 * Formats lines like:
 *  [<timestamp>] <env>.<SEVERITY> <json object>\n
 *
 * Typical keys that are expected to be present in the format,
 * to identify the context of the log message:
 *      application     the name/identifier of this application
 *      category        the type or category of message
 */
class JsonContextFormatter extends AbstractContextFormatter
{
    /**
     * Keys for properties that should not be allowed to bleed
     * to top-level from context array data. These will be
     * nested in 'context' if found (as a safeguard).
     *
     * @var string[]
     */
    protected array $reservedTopLevelKeys = [
        'application',
        'level',
        'message',
        'prospectors',
        'timestamp',
        'type',
    ];


    /**
     * Formats the context array in the desired log format.
     *
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $context
     * @return string
     */
    protected function formatContext(array $vars, array $context): string
    {
        // Set top-level data in the 'context', merging to get those keys in front
        $context = array_merge([
            'message' => $vars['message'],
            'level'   => $vars['level'],
        ], $context);

        return '[' . $this->stringify($vars['datetime']) . ']'
            . ' ' . $this->stringify($vars['channel'])
            . '.' . $this->stringify($vars['level_name'])
            . ': ' . $this->stringify($context)
            . "\n";
    }
}

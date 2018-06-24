<?php

namespace Czim\MonologJsonContext\Formatters;

/**
 * Class PureJsonContextFormatter
 *
 * Formats lines like:
 *  <json object>\n
 *
 * Special keys that are inserted into the json object:
 *      timestamp       the datetime in the default format
 *      channel         the monolog channel
 *      severity        the name of the severity level
 *
 * Typical keys that are expected to be present in the format,
 * to identify the context of the log message:
 *      application     the name/identifier of this application
 *      category        the type or category of message
 */
class PureJsonContextFormatter extends AbstractContextFormatter
{

    /**
     * Keys for properties that should not be allowed to bleed
     * to top-level from context array data. These will be
     * nested in 'context' if found (as a safeguard).
     *
     * @var string[]
     */
    protected $reservedTopLevelKeys = [
        'application',
        'channel',
        'level',
        'message',
        'prospectors',
        'severity',
        'timestamp',
        'type',
    ];


    /**
     * Formats the context array in the desired log format.
     *
     * @param array $vars
     * @param array $context
     * @return string
     */
    protected function formatContext(array $vars, array $context)
    {
        // Set top-level data in the 'context', merging to get those keys in front
        $context = array_merge([
            'timestamp' => $this->stringify($vars['datetime']),
            'channel'   => $this->stringify($vars['channel']),
            'severity'  => $this->stringify($vars['level_name']),
            'level'     => $vars['level'],
            'message'   => $vars['message'],
        ], $context);

        return $this->stringify($context) . "\n";
    }

}

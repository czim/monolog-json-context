<?php

declare(strict_types=1);

namespace Czim\MonologJsonContext\Formatters;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use UnexpectedValueException;

abstract class AbstractContextFormatter extends NormalizerFormatter
{
    protected bool $allowInlineLineBreaks = false;

    /**
     * Keys for properties that should not be allowed to bleed to top-level from context array data.
     * These will be nested in 'context' if found (as a safeguard).
     *
     * @var string[]
     */
    protected array $reservedTopLevelKeys = [];

    /**
     * @param null|string $dateFormat
     * @param null|string $application
     * @param null|string $defaultCategory
     */
    public function __construct(
        ?string $dateFormat = null,
        protected ?string $application = null,
        protected ?string $defaultCategory = null,
    ) {
        parent::__construct($dateFormat);
    }


    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        $vars = parent::format($record);

        if (! is_array($vars)) {
            throw new UnexpectedValueException('Expected array from format() call, got ' . gettype($vars));
        }

        if (! array_key_exists('context', $vars) || ! is_array($vars['context'])) {
            $context = [];
        } else {
            $context = $this->buildTopLevelArrayNestedForContext($vars['context']);
        }

        $this->setSpecialProperties($context);

        return $this->formatContext($vars, $context);
    }

    /**
     * Formats the context array in the desired log format.
     *
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $context
     * @return string
     */
    abstract protected function formatContext(array $vars, array $context): string;

    /**
     * Builds an array that can be used as a basis for top level JSON encoding.
     *
     * This nests reserved values under the 'context' key, where required.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function buildTopLevelArrayNestedForContext(array $context): array
    {
        // Context becomes the main level, so anything in context (with some caveats)
        // is moved up to the top level -- by making %context% the main 'message'
        // output in the log line.

        // Rename to prefix context variables with 'reserved' names
        $nested = [];

        foreach ($this->reservedTopLevelKeys as $key) {
            if (array_key_exists($key, $context)) {
                $nested[ $key ] = $context[ $key ];
                unset ($context[ $key ]);
            }
        }

        // The 'context' key is a special case: its value should be merged
        // into the nested context at that level.
        // (So context.context.key becomes context.key)

        if (array_key_exists('context', $context)) {
            if ( ! is_array($context['context'])) {
                $context['context'] = (array) $context['context'];
            }

            $context['context'] = array_merge($context['context'], $nested);
        } else {
            // if it does not exist yet, just make a nested context
            $context['context'] = $nested;
        }

        return $context;
    }

    /**
     * Updates the array with special properties if required.
     *
     * @param array<string, mixed> $context    by reference
     */
    protected function setSpecialProperties(array &$context): void
    {
        if ($this->application) {
            $context['application'] = $this->application;
        }

        if ( ! array_key_exists('category', $context) && $this->defaultCategory) {
            $context['category'] = $this->defaultCategory;
        }
    }

    protected function stringify(mixed $value): string
    {
        return $this->replaceNewlines($this->convertToString($value));
    }

    protected function convertToString(mixed $data): string
    {
        if ($data === null || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string) $data;
        }

        return $this->toJson($data, true);
    }

    protected function replaceNewlines(string $str): string
    {
        if ($this->allowInlineLineBreaks) {
            if (str_starts_with($str, '{')) {
                return str_replace(['\r', '\n'], ["\r", "\n"], $str);
            }

            return $str;
        }

        return str_replace(["\r\n", "\r", "\n"], ' ', $str);
    }
}

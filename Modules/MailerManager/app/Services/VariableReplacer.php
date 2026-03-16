<?php

namespace Modules\MailerManager\Services;

class VariableReplacer
{
    /**
     * Replace variables in a template string.
     *
     * Supports:
     * - Simple: {{variable_name}}
     * - Formatted: {{variable_name|currency}} {{variable_name|number}}
     * - Conditionals: {{#if variable_name}}...{{/if}}
     * - Loops: {{#each items}}...{{/each}}
     */
    public function replace(string $template, array $data): string
    {
        // 1. Process conditionals first (before loops, so conditional blocks can contain loops)
        $template = $this->processConditionals($template, $data);

        // 2. Process loops: {{#each key}}...{{/each}}
        //    Loop body gets its own variable replacement (including formatters)
        $template = $this->processLoops($template, $data);

        // 3. Process formatted variables: {{key|format}} (top-level)
        $template = $this->processFormattedVariables($template, $data);

        // 4. Process simple variables: {{key}} (top-level)
        $template = $this->processSimpleVariables($template, $data);

        return $template;
    }

    protected function processLoops(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s',
            function ($matches) use ($data) {
                $key = $matches[1];
                $body = $matches[2];

                $items = $data[$key] ?? [];
                if (!is_array($items)) {
                    return '';
                }

                $result = '';
                foreach ($items as $index => $item) {
                    $itemBody = $body;
                    if (is_array($item)) {
                        // First replace formatted variables: {{field|format}}
                        $itemBody = preg_replace_callback(
                            '/\{\{(\w+)\|(\w+)\}\}/',
                            function ($fmtMatches) use ($item) {
                                $fKey = $fmtMatches[1];
                                $format = $fmtMatches[2];
                                $val = $item[$fKey] ?? '';
                                return $this->formatValue($val, $format);
                            },
                            $itemBody
                        ) ?? $itemBody;

                        // Then replace simple variables: {{field}}
                        foreach ($item as $field => $value) {
                            $itemBody = str_replace('{{' . $field . '}}', $this->toString($value), $itemBody);
                        }
                        // Replace {{@index}} with current index (1-based)
                        $itemBody = str_replace('{{@index}}', (string) ($index + 1), $itemBody);
                    } else {
                        $itemBody = str_replace('{{this}}', $this->toString($item), $itemBody);
                    }
                    $result .= $itemBody;
                }

                return $result;
            },
            $template
        ) ?? $template;
    }

    protected function processConditionals(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s',
            function ($matches) use ($data) {
                $key = $matches[1];
                $body = $matches[2];

                $value = $data[$key] ?? null;
                if ($this->isTruthy($value)) {
                    return $body;
                }

                return '';
            },
            $template
        ) ?? $template;
    }

    protected function processFormattedVariables(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{\{(\w+)\|(\w+)\}\}/',
            function ($matches) use ($data) {
                $key = $matches[1];
                $format = $matches[2];
                $value = $data[$key] ?? '';

                return $this->formatValue($value, $format);
            },
            $template
        ) ?? $template;
    }

    protected function processSimpleVariables(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                $template = str_replace('{{' . $key . '}}', $this->toString($value), $template);
            }
        }

        // Remove any remaining unresolved variables
        $template = preg_replace('/\{\{\w+\}\}/', '', $template) ?? $template;

        return $template;
    }

    protected function formatValue(mixed $value, string $format): string
    {
        return match ($format) {
            'currency' => '$' . number_format((float) $value, 2),
            'number' => number_format((float) $value, 2),
            'integer' => number_format((int) $value, 0),
            'date' => $value ? date('d/m/Y', strtotime((string) $value)) : '',
            'datetime' => $value ? date('d/m/Y H:i', strtotime((string) $value)) : '',
            'uppercase' => mb_strtoupper($this->toString($value)),
            'lowercase' => mb_strtolower($this->toString($value)),
            default => $this->toString($value),
        };
    }

    protected function toString(mixed $value): string
    {
        if (is_null($value)) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'Si' : 'No';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) $value;
    }

    protected function isTruthy(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }
        if (is_string($value) && $value === '') {
            return false;
        }
        if (is_array($value) && empty($value)) {
            return false;
        }

        return (bool) $value;
    }
}

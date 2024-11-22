<?php

namespace Oro\Bundle\MakerBundle\Renderer;

/**
 * Render nested formatted attributes
 * @SuppressWarnings(PHPMD)
 */
class AttributeRenderer
{
    private const BLOCK_SYMBOL = '    ';
    private const DEFAULT_VALUES_KEY = 'defaultValues';

    public function render(string $attribute, array $options = null): string
    {
        return implode(
            PHP_EOL,
            array_map(static function (string $item) {
                return '' . $item;
            }, $this->getLines($attribute, $options))
        ) . PHP_EOL;
    }

    public function getLines(string $attribute, array $options = null, bool $singleLine = false): array
    {
        $rows = [
            '#[' . $attribute . '('
        ];

        if (1 === count($options) && !array_key_exists(self::DEFAULT_VALUES_KEY, $options)) {
            $rows = array_merge($rows, $this->renderValue(1, $options, true));
            $rows[] = ')]';

            return [implode($rows)];
        } else {
            $rows = array_merge($rows, $this->renderValue(1, $options, $singleLine));
            $rows[] = ')]';

            return $rows;
        }
    }

    protected function renderValue(int $nestingLevel, array $options = null, bool $singleLine = false): array
    {
        $rows = [];
        $i = 0;
        $count = count($options);
        foreach ($options as $option => $value) {
            if (is_array($value)) {
                if (is_int($option)) {
                    $rows[] = sprintf(
                        '%s[',
                        !$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : ''
                    );
                } else {
                    $row = sprintf(
                        '%s%s%s [',
                        !$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : '',
                        $nestingLevel > 1 ? '"' . $option . '"' : $option,
                        $nestingLevel > 1 ? ' =>' : ':'
                    );
                    $rows[] = $row;
                }
                $rows = array_merge($rows, $this->renderValue($nestingLevel + 1, $value, $singleLine));
                $str = (!$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : '') . ']';
            } else {
                $isSubAttribute = str_starts_with($value, '#[');
                if (is_string($value) && !$isSubAttribute) {
                    $value = '"' . $value . '"';
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }

                if ($isSubAttribute) {
                    $str = sprintf(
                        '%s%s',
                        !$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : '',
                        $value
                    );
                } else {
                    $str = sprintf(
                        '%s%s%s %s',
                        !$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : '',
                        $nestingLevel > 1 ? '"' . $option . '"' : $option,
                        $nestingLevel > 1 ? ' =>' : ':',
                        $value
                    );
                }
            }

            if (++$i < $count) {
                $str .= $singleLine ? ', ' : ',';
            }
            $rows[] = $str;
        }

        return $rows;
    }
}

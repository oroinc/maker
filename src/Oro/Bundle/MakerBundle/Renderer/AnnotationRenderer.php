<?php

namespace Oro\Bundle\MakerBundle\Renderer;

/**
 * Render nested formatted annotations.
 * @SuppressWarnings(PHPMD)
 */
class AnnotationRenderer
{
    private const BLOCK_SYMBOL = '    ';

    public function render(string $annotation, array $options = null): string
    {
        return implode(
                PHP_EOL,
                array_map(static function (string $item) {
                    return ' * ' . $item;
                }, $this->getLines($annotation, $options))
            ) . PHP_EOL;
    }

    public function getLines(string $annotation, array $options = null, bool $singleLine = false): array
    {
        $rows = [
            '@' . $annotation . '('
        ];
        $rows = array_merge($rows, $this->renderValue(1, $options, $singleLine));
        $rows[] = ')';

        return $rows;
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
                        '%s{',
                        !$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : ''
                    );
                } else {
                    $rows[] = sprintf(
                        '%s%s={',
                        !$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : '',
                        $nestingLevel > 1 ? '"' . $option . '"' : $option
                    );
                }
                $rows = array_merge($rows, $this->renderValue($nestingLevel + 1, $value, $singleLine));
                $str = (!$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : '') . '}';
            } else {
                $isSubAnnotation = str_starts_with($value, '@');
                if (is_string($value) && !$isSubAnnotation) {
                    $value = '"' . $value . '"';
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }

                if ($isSubAnnotation) {
                    $str = sprintf(
                        '%s%s',
                        !$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : '',
                        $value
                    );
                } else {
                    $str = sprintf(
                        '%s%s=%s',
                        !$singleLine ? str_repeat(self::BLOCK_SYMBOL, $nestingLevel) : '',
                        $nestingLevel > 1 ? '"' . $option . '"' : $option,
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

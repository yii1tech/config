<?php

namespace yii1tech\config;

use CApplicationComponent;

/**
 * Storage represents the storage for configuration items in format: `[id => value]`.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class Storage extends CApplicationComponent
{
    /**
     * Saves given values.
     * @param array $values in format: `['id' => 'value']`.
     * @return bool success.
     */
    abstract public function save(array $values): bool;

    /**
     * Returns previously saved values.
     * @return array values in format: `['id' => 'value']`.
     */
    abstract public function get(): array;

    /**
     * Clears all saved values.
     * @return bool success.
     */
    abstract public function clear(): bool;
}
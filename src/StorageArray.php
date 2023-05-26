<?php

namespace yii1tech\config;

/**
 * StorageArray uses internal array for the config storage.
 *
 * This class can be useful in unit tests.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class StorageArray extends Storage
{
    /**
     * @var array stored data.
     */
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function save(array $values): bool
    {
        $this->data = array_merge($this->data, $values);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): array
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->data = [];

        return true;
    }
}
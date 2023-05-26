<?php

namespace yii1tech\config;

use CApplicationComponent;
use InvalidArgumentException;
use LogicException;
use Yii;

/**
 * Manager allows management of the dynamic application configuration parameters.
 * Configuration parameters are set up via {@see items}.
 * Parameters can be saved inside the persistent storage determined by {@see storage}.
 *
 * Application configuration example:
 *
 * ```php
 * [
 *     'components' => [
 *         'configManager' => [
 *             'class' => yii1tech\config\Manager::class,
 *             'items' => [
 *                 'appName' => [
 *                     'path' => 'name',
 *                     'label' => 'Application Name',
 *                     'rules' => [
 *                         ['required'],
 *                     ],
 *                 ],
 *                 'validationKey' => [
 *                     'path' => 'components.securityManager.validationKey',
 *                     'label' => 'CSRF Validation Key',
 *                     'rules' => [
 *                         ['required'],
 *                     ],
 *                 ],
 *             ],
 *         ],
 *         ...
 *     ],
 * ];
 * ```
 *
 * Each configuration item is a model and so can be used to compose web form.
 *
 * Configuration apply example:
 * <code>
 * $configManager = Yii::app()->getComponent('configManager');
 * Yii::app()->configure($configManager->fetchConfig());
 * </code>
 *
 * @see \yii1tech\config\Item
 * @see \yii1tech\config\Storage
 *
 * @property array[]|\yii1tech\config\Item[]|string[] $items public alias of {@see _items}.
 * @property \yii1tech\config\Storage|array $storage public alias of {@see _storage}.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Manager extends CApplicationComponent
{
    /**
     * @var array[]|Item[]|string[] config items in format: id => configuration.
     * This filed can be setup as PHP file name, which returns the array of items.
     */
    protected $_items = [];
    /**
     * @var \yii1tech\config\Storage|array config storage.
     * It should be {@see \yii1tech\config\Storage} instance or its array configuration.
     */
    protected $_storage = [
        'class' => StorageDb::class,
    ];
    /**
     * @var string id of the cache application component.
     */
    public $cacheComponentId = 'cache';
    /**
     * @var string id, which will be used to stored composed application configuration
     * in the cache.
     */
    public $cacheId = self::class;
    /**
     * @var integer duration of cache for models in seconds.
     * '0' means never expire.
     * Set this parameter to a negative integer to aviod caching.
     */
    public $cacheDuration = 0;

    /**
     * @param array|\yii1tech\config\Storage $storage
     * @return static self reference.
     * @throws \InvalidArgumentException on invalid argument.
     */
    public function setStorage($storage): self
    {
        if (!is_array($storage) && !is_object($storage)) {
            throw new InvalidArgumentException('"' . get_class($this) . '::storage" should be instance of "' . Storage::class . '" or its array configuration. "' . gettype($storage) . '" given.');
        }
        $this->_storage = $storage;

        return $this;
    }

    /**
     * @return array|\yii1tech\config\Storage
     */
    public function getStorage()
    {
        if (!is_object($this->_storage)) {
            $this->_storage = Yii::createComponent($this->_storage);
        }

        return $this->_storage;
    }

    /**
     * Creates config storage by given configuration.
     * @param array $config storage configuration.
     * @return \yii1tech\config\Storage config storage instance
     */
    protected function createStorage(array $config)
    {
        $storage = Yii::createComponent($config);
        $storage->init();

        return $storage;
    }

    /**
     * @param array|string $items
     * @return static self reference.
     */
    public function setItems($items)
    {
        $this->_items = $items;

        return $this;
    }

    /**
     * @return \yii1tech\config\Item[] config items
     */
    public function getItems(): array
    {
        $this->normalizeItems();
        $items = [];
        foreach ($this->_items as $id => $item) {
            $items[] = $this->getItem($id);
        }

        return $items;
    }

    /**
     * @param string|int $id item id
     * @return \yii1tech\config\Item config item instance.
     * @throws \InvalidArgumentException on failure.
     */
    public function getItem($id)
    {
        $this->normalizeItems();

        if (!array_key_exists($id, $this->_items)) {
            throw new InvalidArgumentException("Unknown config item '{$id}'.");
        }

        if (!is_object($this->_items[$id])) {
            $this->_items[$id] = $this->createItem($id, $this->_items[$id]);
        }

        return $this->_items[$id];
    }

    /**
     * Creates config item by given configuration.
     * @param string|int $id item id.
     * @param array $config item configuration.
     * @return \yii1tech\config\Item config item instance
     */
    protected function createItem($id, array $config)
    {
        if (empty($config['class'])) {
            $config['class'] = Item::class;
        }
        $config['id'] = $id;

        return Yii::createComponent($config);
    }

    /**
     * Normalizes {@see $items} value, ensuring it is array.
     * @throws \LogicException on failure
     */
    protected function normalizeItems()
    {
        if (!is_array($this->_items)) {
            if (is_string($this->_items)) {
                $fileName = $this->_items;
                if (file_exists($fileName)) {
                    $this->_items = require($fileName);
                    if (!is_array($this->_items)) {
                        throw new LogicException('File "' . $fileName . '" should return an array.');
                    }
                } else {
                    throw new LogicException('File "' . $this->_items . '" does not exist.');
                }
            } else {
                throw new LogicException('"' . get_class($this) . '::items" should be array or file name containing it.');
            }
        }
    }

    /**
     * @param array $itemValues config item values.
     * @return static self reference.
     */
    public function setItemValues(array $itemValues)
    {
        foreach ($itemValues as $id => $value) {
            $item = $this->getItem($id);
            $item->value = $value;
        }

        return $this;
    }

    /**
     * @return array config item values
     */
    public function getItemValues(): array
    {
        $itemValues = [];
        foreach ($this->getItems() as $item) {
            $itemValues[$item->id] = $item->value;
        }

        return $itemValues;
    }

    /**
     * @return \CCache cache component instance.
     */
    public function getCacheComponent()
    {
        return Yii::app()->getComponent($this->cacheComponentId);
    }

    /**
     * Composes application configuration array from config items.
     * @return array application configuration.
     */
    public function composeConfig(): array
    {
        $itemConfigs = [];
        foreach ($this->getItems() as $item) {
            $itemConfigs[] = $item->composeConfig();
        }

        return call_user_func_array(['CMap', 'mergeArray'], $itemConfigs);
    }

    /**
     * Saves the current config item values into the persistent storage.
     * @return bool success.
     */
    public function saveValues(): bool
    {
        $result = $this->getStorage()->save($this->getItemValues());
        if ($result) {
            $this->getCacheComponent()->delete($this->cacheId);
        }

        return $result;
    }

    /**
     * Restores config item values from the persistent storage.
     * @return static self reference.
     */
    public function restoreValues()
    {
        return $this->setItemValues($this->getStorage()->get());
    }

    /**
     * Clears config item values saved in the persistent storage.
     * @return bool success.
     */
    public function clearValues(): bool
    {
        $result = $this->getStorage()->clear();
        if ($result) {
            $this->getCacheComponent()->delete($this->cacheId);
        }

        return $result;
    }

    /**
     * Composes the application configuration using config item values
     * from the persistent storage.
     * This method caches its result for the better performance.
     * @return array application configuration.
     */
    public function fetchConfig()
    {
        $cache = $this->getCacheComponent();
        $config = $cache->get($this->cacheId);
        if ($config === false) {
            $this->restoreValues();
            $config = $this->composeConfig();
            $cache->set($this->cacheId, $config, $this->cacheDuration);
        }

        return $config;
    }

    /**
     * Performs the validation for all config item models at once.
     * @return bool whether the validation is successful without any error.
     */
    public function validate(): bool
    {
        $result = true;
        foreach ($this->getItems() as $item) {
            $isItemValid = $item->validate();
            $result = $result && $isItemValid;
        }

        return $result;
    }
}
<?php

namespace yii1tech\config\test;

use Yii;
use yii1tech\config\Item;
use yii1tech\config\Manager;
use yii1tech\config\StorageArray;

class ManagerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dirName = dirname($this->getTestFileName());
        if (!file_exists($dirName)) {
            mkdir($dirName, 0777, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $fileName = $this->getTestFileName();
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        parent::tearDown();
    }

    /**
     * @return string test file name
     */
    protected function getTestFileName(): string
    {
        return Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . 'test_config_data_' . getmypid() . '.php';
    }

    /**
     * Creates test config manager.
     * @return \yii1tech\config\Manager config manager instance.
     */
    protected function createTestManager(): Manager
    {
        $config = [
            'class' => Manager::class,
            'storage' => [
                'class' => StorageArray::class,
            ],
        ];

        return Yii::createComponent($config);
    }

    // Tests :

    public function testSetupStorage(): void
    {
        $manager = new Manager();

        $storage = new StorageArray();
        $manager->setStorage($storage);
        $this->assertEquals($storage, $manager->getStorage(), 'Unable to setup storage!');
    }

    public function testSetupItems(): void
    {
        $manager = new Manager();

        $items = [
            'item1' => new Item(),
            'item2' => new Item(),
        ];
        $manager->setItems($items);
        $this->assertEquals($items, $manager->getItems(), 'Unable to setup items!');

        $manager->setItems([
            'test_id' => [
                'path' => 'test/path',
            ],
        ]);
        $normalizedItems = $manager->getItems();

        $this->assertFalse(empty($normalizedItems['test_id']));
        $this->assertSame('test_id', $normalizedItems['test_id']->id);
    }

    /**
     * @depends testSetupStorage
     */
    public function testGetDefaultStorage(): void
    {
        $manager = new Manager();
        $storage = $manager->getStorage();
        $this->assertTrue(is_object($storage), 'Unable to get default storage!');
    }

    /**
     * @depends testSetupItems
     */
    public function testGetItemById(): void
    {
        $manager = new Manager();

        $itemId = 'testItemId';
        $item = new Item();
        $manager->setItems([
            $itemId => $item
        ]);
        $this->assertEquals($item, $manager->getItem($itemId), 'Unable to get item by id!');
    }

    /**
     * @depends testGetItemById
     */
    public function testCreateItem(): void
    {
        $manager = new Manager();

        $itemId = 'testItemId';
        $itemConfig = [
            'label' => 'testLabel'
        ];
        $manager->setItems([
            $itemId => $itemConfig
        ]);
        $item = $manager->getItem($itemId);
        $this->assertTrue(is_object($item), 'Unable to create item from config!');
        $this->assertEquals($itemConfig['label'], $item->label, 'Unable to setup attributes!');
    }

    /**
     * @depends testCreateItem
     */
    public function testSetupItemsByFile(): void
    {
        $manager = new Manager();

        $items = [
            'item1' => [
                'label' => 'item1label'
            ],
            'item2' => [
                'label' => 'item2label'
            ],
        ];
        $fileName = $this->getTestFileName();
        $fileContent = '<?php return ' . var_export($items, true) . ';';
        file_put_contents($fileName, $fileContent);

        $manager->setItems($fileName);

        foreach ($items as $id => $itemConfig) {
            $item = $manager->getItem($id);
            $this->assertEquals($itemConfig['label'], $item->label, 'Wrong item label');
        }
    }

    /**
     * @depends testCreateItem
     */
    public function testSetupItemValues(): void
    {
        $manager = new Manager();
        $items = [
            'item1' => [],
            'item2' => [],
        ];
        $manager->setItems($items);

        $itemValues = [
            'item1' => 'item1value',
            'item2' => 'item2value',
        ];
        $manager->setItemValues($itemValues);
        $this->assertEquals($itemValues, $manager->getItemValues(), 'Unable to setup item values!');
    }

    /**
     * @depends testCreateItem
     */
    public function testComposeConfig(): void
    {
        $manager = new Manager();
        $items = [
            'item1' => [
                'path' => 'params.item1',
                'value' => 'item1value',
            ],
            'item2' => [
                'path' => 'params.item2',
                'value' => 'item2value',
            ],
        ];
        $manager->setItems($items);

        $config = $manager->composeConfig();
        $expectedConfig = [
            'params' => [
                'item1' => 'item1value',
                'item2' => 'item2value',
            ],
        ];
        $this->assertEquals($expectedConfig, $config, 'Wrong config composed!');
    }

    /**
     * @depends testSetupItemValues
     */
    public function testSave(): void
    {
        $manager = $this->createTestManager();
        $items = [
            'item1' => [
                'value' => 'item1value',
            ],
            'item2' => [
                'value' => 'item2value',
            ],
        ];
        $manager->setItems($items);

        $manager->save();
        $itemValues = $manager->getItemValues();

        $emptyItemValues = [
            'item1' => null,
            'item2' => null,
        ];

        $manager->setItemValues($emptyItemValues);
        $manager->restore();
        $this->assertEquals($itemValues, $manager->getItemValues(), 'Unable to restore values!');

        $manager->reset();

        $manager->setItemValues($emptyItemValues);
        $this->assertEquals($emptyItemValues, $manager->getItemValues(), 'Unable to clear values!');
    }

    /**
     * @depends testSave
     */
    public function testReset()
    {
        $manager = $this->createTestManager();
        $items = [
            'item1' => [
                'path' => 'params.param1',
            ],
            'item2' => [
                'path' => 'params.param2',
            ],
        ];
        $manager->setItems($items);

        $manager->setItemValues([
            'item1' => 'new-item1',
            'item2' => 'new-item2',
        ]);

        $manager->reset();

        $this->assertSame([], $manager->getStorage()->get());

        $this->assertSame('param1-value', $manager->getItem('item1')->getValue());
        $this->assertSame('param2-value', $manager->getItem('item2')->getValue());
    }

    /**
     * @depends testSave
     */
    public function testResetValue()
    {
        $manager = $this->createTestManager();
        $items = [
            'item1' => [
                'path' => 'params.param1',
            ],
            'item2' => [
                'path' => 'params.param2',
            ],
        ];
        $manager->setItems($items);

        $manager->setItemValues([
            'item1' => 'new-item1',
            'item2' => 'new-item2',
        ]);

        $manager->resetValue('item2');

        $this->assertSame([], $manager->getStorage()->get());

        $this->assertSame('new-item1', $manager->getItem('item1')->getValue());
        $this->assertSame('param2-value', $manager->getItem('item2')->getValue());
    }

    /**
     * @depends testComposeConfig
     * @depends testSave
     */
    public function testFetchConfig(): void
    {
        $manager = $this->createTestManager();
        $items = [
            'item1' => [
                'path' => 'params.item1',
                'value' => 'item1value',
            ],
            'item2' => [
                'path' => 'params.item2',
                'value' => 'item2value',
            ],
        ];
        $manager->setItems($items);
        $manager->save();

        $manager = $this->createTestManager();
        $manager->setItems($items);

        $config = $manager->fetchConfig();
        $expectedConfig = [
            'params' => [
                'item1' => 'item1value',
                'item2' => 'item2value',
            ],
        ];
        $this->assertEquals($expectedConfig, $config, 'Wrong config composed!');
    }

    /**
     * @depends testSetupItemValues
     */
    public function testValidate(): void
    {
        $manager = new Manager();

        $itemId = 'testItem';
        $items = [
            $itemId => [
                'rules' => [
                    ['required']
                ]
            ],
        ];
        $manager->setItems($items);

        $itemValues = [
            $itemId => ''
        ];
        $manager->setItemValues($itemValues);
        $this->assertFalse($manager->validate(), 'Invalid values considered as valid!');

        $itemValues = [
            $itemId => 'some value'
        ];
        $manager->setItemValues($itemValues);
        $this->assertTrue($manager->validate(), 'Valid values considered as invalid!');
    }
}
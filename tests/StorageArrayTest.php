<?php

namespace yii1tech\config\test;

use yii1tech\config\StorageArray;

class StorageArrayTest extends TestCase
{
    /**
     * @var \Illuminatech\Config\StorageDb test storage.
     */
    protected $storage;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->storage = new StorageArray();
    }

    public function testSave()
    {
        $values = [
            'test.name' => 'Test name',
            'test.title' => 'Test title',
        ];

        $this->storage->save($values);

        $returnedValues = $this->storage->get();

        $this->assertEquals($values, $returnedValues);
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $values = [
            'test.name' => 'Origin name',
            'test.title' => 'Origin title',
        ];

        $this->storage->save($values);

        $this->storage->save([
            'test.title' => 'Updated title'
        ]);

        $returnedValues = $this->storage->get();

        $this->assertSame('Updated title', $returnedValues['test.title']);
        $this->assertSame('Origin name', $returnedValues['test.name']);
    }

    /**
     * @depends testSave
     */
    public function testClear()
    {
        $values = [
            'test.name' => 'Test name',
            'test.title' => 'Test title',
        ];

        $this->storage->save($values);
        $this->storage->clear();

        $this->assertEmpty($this->storage->get());
    }
}
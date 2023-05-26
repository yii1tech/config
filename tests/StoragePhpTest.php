<?php

namespace yii1tech\config\test;

use Yii;
use yii1tech\config\StoragePhp;

class StoragePhpTest extends TestCase
{
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
     * @return \yii1tech\config\StoragePhp test storage instance.
     */
    protected function createTestStorage(): StoragePhp
    {
        $config = [
            'class' => StoragePhp::class,
            'fileName' => $this->getTestFileName(),
        ];

        return Yii::createComponent($config);
    }

    // Tests :

    public function testSetGet(): void
    {
        $storage = new StoragePhp();

        $fileName = '/test/file/name.php';
        $storage->setFileName($fileName);
        $this->assertEquals($fileName, $storage->getFileName(), 'Unable to setup file name!');
    }

    /**
     * @depends testSetGet
     */
    public function testGetDefaultFileName(): void
    {
        $storage = new StoragePhp();
        $fileName = $storage->getFileName();
        $this->assertNotEmpty($fileName, 'Unable to get default file name!');
    }

    /**
     * @depends testSetGet
     */
    public function testSave(): void
    {
        $storage = $this->createTestStorage();
        $values = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $this->assertTrue($storage->save($values), 'Unable to save values!');
        $this->assertFileExists($storage->getFileName(), 'Unable to create file!');

        $returnedValues = $storage->get();
        $this->assertEquals($values, $returnedValues);
    }

    /**
     * @depends testSave
     */
    public function testClear(): void
    {
        $storage = $this->createTestStorage();
        $values = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $storage->save($values);

        $this->assertTrue($storage->clear(), 'Unable to clear values!');
        $this->assertEmpty($storage->get(), 'Values are not cleared!');
    }

    /**
     * @depends testSave
     */
    public function testClearValue()
    {
        $storage = $this->createTestStorage();
        $values = [
            'test.name' => 'Test name',
            'test.title' => 'Test title',
        ];

        $storage->save($values);
        $storage->clearValue('test.name');

        $returnedValues = $storage->get();

        $this->assertFalse(array_key_exists('test.name', $returnedValues));
        $this->assertTrue(array_key_exists('test.title', $returnedValues));
    }
}
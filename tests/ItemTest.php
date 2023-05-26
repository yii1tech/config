<?php

namespace yii1tech\config\test;

use CRequiredValidator;
use yii1tech\config\Item;

class ItemTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->mockApplication([
            'name' => 'Test Application Name',
            'components' => [
                'securityManager' => [
                    'validationKey' => 'testValidationKey',
                    'encryptionKey' => 'testEncryptionKey'
                ],
            ],
            'params' => [
                'param1' => 'param1value',
                'param2' => 'param2value',
            ],
        ]);
    }

    public function testSetGet(): void
    {
        $model = new Item();

        $value = 'testValue';
        $model->setValue($value);
        $this->assertEquals($value, $model->getValue(), 'Unable to setup value!');

        $rules = [
            'required'
        ];
        $model->setRules($rules);
        $this->assertEquals($rules, $model->getRules(), 'Unable to setup rules!');
    }

    public function testLabel(): void
    {
        $model = new Item();

        $label = 'TestPlaceholderLabel';
        $model->label = $label;

        $this->assertEquals($label, $model->getAttributeLabel('value'), 'Wrong value label!');
    }

    /**
     * @depends testSetGet
     */
    public function testSetupRules(): void
    {
        $model = new Item();

        $validationRules = [
            ['required'],
        ];
        $model->setRules($validationRules);
        $validatorList = $model->getValidatorList();

        $this->assertEquals(count($validationRules) + 1, $validatorList->getCount(), 'Unable to set validation rules!');

        $validator = $validatorList->itemAt(1);
        $this->assertTrue($validator instanceof CRequiredValidator, 'Wrong validator created!');
    }

    /**
     * Data provider for {@see testExtractCurrentValue()}
     * @return array test data.
     */
    public static function dataProviderExtractCurrentValue(): array
    {
        return [
            [
                'name',
                'Test Application Name',
            ],
            [
                'params.param1',
                'param1value',
            ],
            [
                ['params', 'param1'],
                'param1value',
            ],
            [
                'components.securityManager.validationKey',
                'testValidationKey',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderExtractCurrentValue
     *
     * @param $path
     * @param $expectedValue
     */
    public function testExtractCurrentValue($path, $expectedValue): void
    {
        $model = new Item();
        $model->path = $path;
        $this->assertEquals($expectedValue, $model->extractCurrentValue());
    }

    /**
     * @depends testExtractCurrentValue
     */
    public function testGetDefaultValue(): void
    {
        $model = new Item();
        $model->path = 'params.param1';
        $defaultValue = $model->getValue();
        $this->assertEquals('param1value', $defaultValue, 'Wrong default value!');
    }

    /**
     * Data provider for {@see testComposeConfig()}.
     * @return array test data.
     */
    public static function dataProviderComposeConfig(): array
    {
        return [
            [
                'name',
                [
                    'name' => 'value'
                ],
            ],
            [
                'params.param1',
                [
                    'params' => [
                        'param1' => 'value'
                    ],
                ]
            ],
            [
                'components.securityManager.validationKey',
                [
                    'components' => [
                        'securityManager' => [
                            'validationKey' => 'value'
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderComposeConfig
     *
     * @param $path
     * @param array $expectedConfig
     */
    public function testComposeConfig($path, array $expectedConfig): void
    {
        $model = new Item();
        $model->path = $path;
        $model->value = 'value';
        $this->assertEquals($expectedConfig, $model->composeConfig());
    }
}
<?php

namespace yii1tech\config\test\support;

use CActiveRecord;

/**
 * @property string $id
 * @property string $value
 */
class ConfigItem extends CActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * {@inheritdoc}
     */
    public function tableName()
    {
        return 'test_config';
    }
}
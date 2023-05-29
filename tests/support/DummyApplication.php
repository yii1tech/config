<?php

namespace yii1tech\config\test\support;

class DummyApplication extends \CApplication
{
    /**
     * {@inheritdoc}
     */
    public function processRequest()
    {
        // do nothing
    }
}
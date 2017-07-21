<?php

namespace alexeevdv\image;

use kartik\file\FileInput;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\InputWidget;

/**
 * Class MultipleImageUploadWidget
 * @package alexeevdv\image
 */
class MultipleImageUploadWidget extends InputWidget
{
    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->getMultipleImageUploadBehavior()) {
            throw new InvalidConfigException('Model should have MultipleImageUploadBehavior behavior');
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $primaryKey = $this->model->primaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = reset($primaryKey);
        }

        $behavior = $this->getMultipleImageUploadBehavior();

        $images = $this->getImages();

        return Html::tag(
            'div',
            FileInput::widget([
                'value' => $this->value,
                'name' => $this->model ? Html::getInputName($this->model, $this->attribute) : $this->name,
                'options' => [
                    'multiple' => true
                ],
                'pluginLoading' => true,
                'pluginOptions' => [
                    'overwriteInitial' => false,
                    'initialPreview' => array_map(function ($image) use ($behavior) {
                        return Html::img(
                            str_replace('@frontend/web', Yii::$app->frontendUrlManager->baseUrl, $behavior->uploadPath)
                            . '/'
                            . $image->image
                        );
                    }, $images),
                    'initialPreviewConfig' => array_map(function ($image) use ($behavior) {
                        return [
                            'url' => is_array($behavior->deleteUrl) ? Url::to($behavior->deleteUrl) : $behavior->deleteUrl,
                            'key' => $image->primaryKey,
                        ];
                    }, $images),
                    'uploadUrl' => is_array($behavior->uploadUrl) ? Url::to($behavior->uploadUrl) : $behavior->uploadUrl,
                    'uploadExtraData' => [
                        $primaryKey => (int) $this->model->primaryKey,
                    ],
                ],
            ]),
            [
                'class' => 'multiple-image-upload-widget',
            ]
        );
    }

    /**
     * @return array
     */
    protected function getImages()
    {
        if (!$this->model->isNewRecord) {
            return $this->model->{$this->attribute};
        }

        return $this->getMultipleImageUploadBehavior()->getImagesWithoutModel();
    }

    /**
     * @return MultipleImageUploadBehavior|null
     */
    protected function getMultipleImageUploadBehavior()
    {
        foreach ($this->model->getBehaviors() as $behavior) {
            if ($behavior instanceof MultipleImageUploadBehavior) {
                return $behavior;
            }
        }
        return null;
    }
}

<?php

namespace alexeevdv\image;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Class ImageUploadAction
 * @package alexeevdv\image
 */
class MultipleImageUploadAction extends Action
{
    /**
     * @var string
     */
    public $modelClass;

    /**
     * @var string
     */
    public $relation;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->modelClass) {
            throw new InvalidConfigException('`modelClass` is required.');
        }
        if (!$this->relation) {
            throw new InvalidConfigException('`relation` is required.');
        }
        if (!$this->getMultipleImageUploadBehavior()) {
            throw new InvalidConfigException('Model should have MultipleImageUploadBehavior behavior.');
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $modelClass = $this->modelClass;
        /** @var ActiveRecord $model */
        $model = new $modelClass;
        $primaryKey = $model->primaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = reset($primaryKey);
        }

        if (Yii::$app->request->post($primaryKey)) {
            $model = $model::findOne(Yii::$app->request->post($primaryKey));
            if (!$model) {
                throw new NotFoundHttpException;
            }
        }

        $behavior = $this->getMultipleImageUploadBehavior();

        // TODO Interface for image class
        $imageClass = $model->getRelation($this->relation)->modelClass;
        /** @var ActiveRecord $image */
        $image = new $imageClass;
        $image->file = UploadedFile::getInstance($model, $this->relation);
        if ($image->validate()) {
            $fileName = $this->generateFilename($image->file);
            $image->file->saveAs(rtrim(Yii::getAlias($behavior->uploadPath), '\/') . '/' . $fileName);
            $image->image = $fileName;
            $image->file = null;
            $image->save();

            if ($model->primaryKey) {
                $model->link($this->relation, $image);
            }

            return $this->controller->asJson([
                'initialPreviewConfig' => [
                    [
                        'key' => $image->primaryKey,
                        'url' => is_array($behavior->deleteUrl) ? Url::to($behavior->deleteUrl) : $behavior->deleteUrl,
                    ],
                ],
                'initialPreview' => [
                    Html::img(
                        str_replace('@frontend/web', Yii::$app->frontendUrlManager->baseUrl, $behavior->uploadPath)
                        . '/'
                        // TODO do not hardcode this
                        . $image->image
                    ),
                ],
                'append' => true,
            ]);
        }

        $errors = $image->getFirstErrors();
        return $this->controller->asJson([
            'error' => reset($errors),
        ]);
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFilename(UploadedFile $file)
    {
        return $file->baseName . '.' . $file->extension;
    }

    /**
     * @return MultipleImageUploadBehavior|null
     */
    protected function getMultipleImageUploadBehavior()
    {
        $modelClass = $this->modelClass;
        $model = new $modelClass;
        foreach ($model->getBehaviors() as $behavior) {
            if ($behavior instanceof MultipleImageUploadBehavior) {
                if ($behavior->relation == $this->relation) {
                    return $behavior;
                }
            }
        }
        return null;
    }
}

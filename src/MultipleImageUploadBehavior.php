<?php

namespace alexeevdv\image;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * Class MultipleImageUploadBehavior
 * @package alexeevdv\image
 */
class MultipleImageUploadBehavior extends Behavior
{
    /**
     * @var string
     */
    public $relation;

    /**
     * @var string|array
     */
    public $uploadUrl;

    /**
     * @var string|array
     */
    public $deleteUrl;

    /**
     * @var string
     */
    public $uploadPath = '@frontend/web/uploads';

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->relation) {
            throw new InvalidConfigException('`relation` is required.');
        }
        if (!$this->uploadUrl) {
            throw new InvalidConfigException('`uploadUrl` is required.');
        }
        if (!$this->deleteUrl) {
            throw new InvalidConfigException('`deleteUrl` is required.');
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'onAfterInsert',
        ];
    }

    /**
     *
     */
    public function onAfterInsert()
    {
        foreach ($this->getImagesWithoutModel() as $image) {
            $this->owner->link($this->relation, $image);
        }
    }

    /**
     * @return ActiveRecord[]
     */
    public function getImagesWithoutModel()
    {
        $modelClass = $this->owner->getRelation($this->relation)->modelClass;
        return $modelClass::find()
            ->andWhere([
                $this->getRelationAttribute() => null,
            ])
            ->all();
    }

    /**
     * @return string
     */
    private function getRelationAttribute()
    {
        $relation = $this->owner->getRelation($this->relation);
        return key($relation->link);
    }
}

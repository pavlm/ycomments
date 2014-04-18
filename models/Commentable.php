<?php
/**
 * Для выборки подписок на материал
 *
 * @property integer $id
 * @property NotifySubscription[] $subscriptions
 */
class Commentable extends CActiveRecord
{
	public static $commentableType;
	
	public function defaultScope()
	{
		return ['select' => 'id'];
	}
	
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		$item = new self::$commentableType;
		return $item->tableName();
	}

	public static $_mdByType = [];
	
	/**
	 * respect dynamic relation depending on commentableType
	 * @see CActiveRecord::getMetaData()
	 */
	public function getMetaData()
	{
		if (isset(self::$_mdByType[self::$commentableType]))
			return self::$_mdByType[self::$commentableType];
		if (!$md = parent::getMetaData())
			return null;
		$md->relations = [];
		foreach($this->relations() as $name=>$config)
		{
			$md->addRelation($name,$config);
		}
		self::$_mdByType[self::$commentableType] = $md;
		return $md;
	}
	
	public function relations()
	{
		$rels = [];
		if (self::$commentableType)
		{
			$type = self::$commentableType;
			// dynamic relation with comment document
			$model = new $type();
			/* @var $behavior CommentableBehavior */
			$behavior = $model->asa('commentable');
			$rels['subscriptions'] = array(self::HAS_MANY, 'NotifySubscription', 'item_id', 
					'on' => "subscriptions.commentable_type='{$type}'", 'together' => false);
		}
		return $rels;
	}

	/**
	 * @param string $className active record class name.
	 * @return CommentLike the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
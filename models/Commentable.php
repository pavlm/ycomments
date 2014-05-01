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
	
	public function scopes()
	{
		$alias = $this->getDbCriteria()->alias;
		return array(
			'mini' => array('select' => "id"),
		);
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
	
	/**
	 * @return CommentableBehavior
	 */
	public function getCommentableBehavior()
	{
		$model = new self::$commentableType();
		// dynamic relation with comment document
		$behavior = $model->asa('commentable');
		return $behavior;
	}
	
	public function relations()
	{
		$rels = [];
		if (self::$commentableType)
		{
			$type = self::$commentableType;
			// dynamic relation with comment document
			$behavior = $this->getCommentableBehavior();
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
	
	/**
	 * url to comment data
	 * @return array
	 */
	public function getUrlData()
	{
		$b = $this->getCommentableBehavior();
		if ($urlFunc = $b->commentableUrl) {
			$data = array('/asdf'); // todo: change commentableUrl
		} else {
			$data = '';
		}
		return $data;
	}
}
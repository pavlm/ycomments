<?php

/**
 * Подписка пользователя на элемент определенного типа контента
 *
 * @property integer $id
 * @property string $user_id
 * @property string $commentable_type
 * @property integer $item_id
 *
 * @property CActiveRecord $item
 *
 */
class NotifySubscription extends CActiveRecord {

	/**
	 * @var bool - subscription flag for a form field
	 */
	public $active;
	
	public function __construct($scenario='insert')
	{
		parent::__construct($scenario);
		$this->active = false;
	}

	public function afterFind()
	{
		$this->active = true;
	}
	
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return NotifySubscription
	 */
	public static function findOrCreate($commentableType, $itemId, $userId)
	{
		if ($ns = NotifySubscription::model()->findByAttributes(['item_id' => $itemId, 'commentable_type' => $commentableType, 'user_id' => $userId]))
			return $ns;
		$ns = new self();
		$ns->commentable_type = $commentableType;
		$ns->item_id = $itemId;
		$ns->user_id = $userId;
		return $ns;
	}
	
	public function tableName() {
		return 'notify_subscription';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'NotifySubscription|NotifySubscriptions', $n);
	}

	public static function representingColumn() {
		return 'commentable_type';
	}

	public function rules() {
		return array(
			array('user_id, commentable_type, item_id', 'required'),
			array('item_id', 'numerical', 'integerOnly'=>true),
			array('user_id', 'length', 'max'=>10),
			array('commentable_type', 'commentableTypeValidator'),
			array('item_id', 'itemIdValidator'),
			array('commentable_type', 'length', 'max'=>128),
			array('id, user_id, commentable_type, item_id', 'safe', 'on'=>'search'),
		);
	}

	public function commentableTypeValidator($attribute, $params)
	{
		/* @var $cm YCommentsModule */
		$cm = Yii::app()->getModule('comment');
		$cts = array_keys($cm->commentableTypes);
		if (!in_array(strtolower($this->$attribute), $cts))
			$this->addError($attribute, 'Недопустимый тип');
	}
	
	public function itemIdValidator($attribute, $params)
	{
		if (!$this->commentable_type)
			return;
		$model = CActiveRecord::model($this->commentable_type);
		if (!$model->findByPk($this->item_id))
			$this->addError($attribute, 'ошибка данных');
	}
	
	public function getMetaData()
	{
		if (!$md = parent::getMetaData())
			return null;
		$md->relations = array();
		foreach($this->relations() as $name=>$config)
		{
			$md->addRelation($name,$config);
		}
		return $md;
	}
	
	public static $commentableType;
	
	public function relations() {
		if (self::$commentableType) {
			return array(
				'item' => array(self::BELONGS_TO, self::$commentableType, 'item_id'),
			);
		} else {
			return array();
		}
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'user_id' => 'User',
			'commentable_type' => 'Commentable Type',
			'item_id' => 'Item',
			'active' => $this->active ? 'Вы подписаны на комментарии' : 'Подписаться на комментарии',
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('user_id', $this->user_id, true);
		$criteria->compare('commentable_type', $this->commentable_type, true);
		$criteria->compare('item_id', $this->item_id);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}
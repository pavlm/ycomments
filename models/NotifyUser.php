<?php

/**
 * Настройки пользователя по типам контента
 * 
 * @property integer $id
 * @property string $commentable_type
 * @property string $user_id
 * @property integer $notify_all
 * @property integer $notify_reply
 *
 */
class NotifyUser extends CActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	public function __construct($scenario='insert')
	{
		parent::__construct($scenario);
		$this->notify_all = 1;
		$this->notify_reply = 1;
	}
	
	/**
	 * @return NotifyUser
	 */
	public static function loadDefault($commentableType=null)
	{
		if ($nu = NotifyUser::model()->findByAttributes(array('user_id' => Yii::app()->user->id, 'commentable_type' => $commentableType)))
			return $nu;
		$nu = new self();
		$nu->user_id = Yii::app()->user->id;
		$nu->commentable_type = $commentableType;
		return $nu;
	} 
	
	public function tableName() {
		return 'notify_user';
	}

	public static function label($n = 1) {
		return 'NotifyUser';
	}

	public static function representingColumn() {
		return 'commentable_type';
	}

	public function rules() {
		return array(
			array('commentable_type', 'required'),
			array('notify_all, notify_reply', 'numerical', 'integerOnly'=>true),
			array('commentable_type', 'length', 'min' => 3, 'max'=>128),
			array('user_id', 'unsafe'),
			array('commentable_type', 'commentableTypeValidator'),
			array('notify_all, notify_reply', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, commentable_type, user_id, notify_all, notify_reply', 'safe', 'on'=>'search'),
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
	
	public function relations() {
		return array(
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'commentable_type' => YCommentsModule::t('Commentable Type'),
			'user_id' => YCommentsModule::t('User'),
			'notify_all' => YCommentsModule::t('Подписка на все сообщения'),
			'notify_reply' => YCommentsModule::t('Подписка на ответы к моим сообщениям'),
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('commentable_type', $this->commentable_type, true);
		$criteria->compare('user_id', $this->user_id, true);
		$criteria->compare('notify_all', $this->notify_all);
		$criteria->compare('notify_reply', $this->notify_reply);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}
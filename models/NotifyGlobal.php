<?php

/**
 * Время последней проверки по типам
 *
 *
 * @property integer $id
 * @property string $commentable_type
 * @property string $last_check_at
 *
 */
class NotifyGlobal extends CActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'notify_global';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'NotifyGlobal|NotifyGlobals', $n);
	}

	public static function representingColumn() {
		return 'commentable_type';
	}

	public function rules() {
		return array(
			array('commentable_type', 'required'),
			array('commentable_type', 'length', 'max'=>128),
			array('last_check_at', 'safe'),
			array('last_check_at', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, commentable_type, last_check_at', 'safe', 'on'=>'search'),
		);
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
			'id' => Yii::t('app', 'ID'),
			'commentable_type' => Yii::t('app', 'Commentable Type'),
			'last_check_at' => Yii::t('app', 'Last Check At'),
		);
	}


	/**
	 * @return NotifyGlobal
	 */
	public static function findOrCreate($commentableType)
	{
		if ($ng = self::model()->findByAttributes(array('commentable_type' => $commentableType)))
			return $ng;
		$ng = new self();
		$ng->commentable_type = $commentableType;
		$ng->last_check_at = date('Y-m-d'); // start of day
		return $ng;
	}
	
	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('commentable_type', $this->commentable_type, true);
		$criteria->compare('last_check_at', $this->last_check_at, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}
<?php

/**
 * модель-отзыв об организации / событии
 * 
 * @property integer $rating
 *
 */
class Review extends Comment
{
	public static $commentLikeType = 'ReviewLike';

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array_merge(
				parent::rules(),
				array(
					array('rating', 'required'),
					array('rating', 'ratingFilter'),
					array('rating', 'numerical', 'integerOnly'=>true),
				)
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return parent::relations();
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array_merge(
				parent::attributeLabels(),
				array(
					'rating' => 'Оценка',
					'message' => 'Отзыв',
				)
		);
	}
	
	public function afterValidate()
	{
		parent::afterValidate();
	}
	
	public function ratingFilter()
	{
		if ($this->parent_id) // no rating for reply
		{
			$this->clearErrors('rating');
			$this->rating = null;
		}
	}
}

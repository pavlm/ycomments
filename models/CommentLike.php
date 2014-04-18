<?php
/**
 * This is the model class for table "comment_user_like".
 *
 * The followings are the available columns in table 'comment_user_like':
 * @property string $comment_id
 * @property integer $user_id
 */
class CommentLike extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'comment_user_like';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
				array('comment_id, user_id', 'required'),
				array('user_id', 'numerical', 'integerOnly'=>true),
				array('comment_id', 'length', 'max'=>10),
				array('comment_id, user_id', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
		);
	}

	public function attributeLabels()
	{
		return array(
				'comment_id' => 'Comment',
				'user_id' => 'User',
		);
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
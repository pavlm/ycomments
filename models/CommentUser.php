<?php
/*
 * wrapper for user AR
 */
class CommentUser extends CActiveRecord
{
	/**
	 * @var CActiveRecord
	 */
	public $wrappedUser;
	
	public function __construct($scenario='insert')
	{
		parent::__construct($scenario);
		$cm = Yii::app()->getModule('ycomments');
		$this->wrappedUser = new $cm->userModelClass;
	}

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	
	public function tableName()
	{
		return $this->wrappedUser->tableName();
	}
	
	public function relations()
	{
		$rels = array(
			'notifyUser' => array(self::HAS_MANY, 'NotifyUser', 'user_id'),
		);
		return array_merge($rels, $this->wrappedUser->relations());
	}

	public function getUName() {
		$cm = Yii::app()->getModule('ycomments');
		$attrib = $cm->userNameAttribute;
		return is_callable($attrib) ? $attrib($this) : $this->$attrib;
	}

	public function getUEmail() {
		$cm = Yii::app()->getModule('ycomments');
		$attrib = $cm->userEmailAttribute;
		return is_callable($attrib) ? $attrib($this) : $this->$attrib;
	}
	
	public function getGravatarImg($gravatarOpts=array('d' => 'identicon', 'r' => 'r', 's' => 40))
	{
		$query = http_build_query($gravatarOpts);
		$hash = md5($this->getUEmail());
		$src = "http://www.gravatar.com/avatar/{$hash}?{$query}";
		return CHtml::image($src, $this->getUName()); 
	}
	
	/**
	 * Или запись из БД, или новая запись со значениями по-умолчанию
	 * @param string $commentableType
	 * @return NotifyUser
	 */
	public function getNotifyUserOrDefault($commentableType)
	{
		$nus = array_filter($this->notifyUser, function($nu)use($commentableType){
			return $nu->commentable_type == $commentableType;
		});
		if (!empty($nus)) {
			return reset($nus);
		} else {
			$nu = new NotifyUser();
			$nu->user_id = $this->id;
			$nu->commentable_type = $commentableType;
			return $nu;
		}
	}

	public function isSubscribedAdmin($commentableType)
	{
		// todo: make universal
		if (!$this->superuser)
			return false;
		$nu = $this->getNotifyUserOrDefault($commentableType);
		return $nu->notify_all;
	}

	/**
	 * @param CommentHost $item
	 */
	public function isSubscribedOnItem($item)
	{
		/* @var $sub NotifySubscription */
		foreach ($item->subscriptions as $sub) {
			if ($sub->user_id == $this->id) 
				return true;
		}
		return false;
	}
	
	/**
	 * @param Comment $comment
	 */
	public function isSubscribedOnReplies($comment)
	{
		if (!$comment->parent) 
			return false;
		$nu = $this->getNotifyUserOrDefault(Comment::$commentableType); // todo: detect type from instance
		if (!$nu->notify_reply)
			return false;
		return $comment->parent->user_id == $this->id;
	}

	public function attributeLabels()
	{
		return array(
			'UName' => 'Имя',
			'UEmail' => 'Email',
		);
	}
}
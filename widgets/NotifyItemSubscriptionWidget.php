<?php

/**
 * 
 * Widget manages current user subscription for $commentableType item with id=$itemId
 * @author pavl
 *
 */
class NotifyItemSubscriptionWidget extends CWidget
{
	public $commentableType;
	
	public $itemId;
	
	/**
	 * @var NotifySubscription
	 */
	public $notifySubs;
	
	/**
	 * @return CommentableBehavior
	 */
	public function getCommentableBehavior() {
		$item = Yii::createComponent($this->commentableType);
		return $item->commentable;
	}
	
	public function run()
	{
		$uid = Yii::app()->user->id;
		
		if (!$uid || !$this->commentableType || !$this->itemId)
			return;

		$this->notifySubs = NotifySubscription::findOrCreate($this->commentableType, $this->itemId, $uid);
		
		$this->render('notifyItemSubscription');
	}
	
}
<?php

class NotifySubscriptionWidget extends CWidget
{
	public $commentableType;
	
	/**
	 * @var NotifyUser
	 */
	public $notifyUser;

	/**
	 * @var CActiveDataProvider
	 */
	public $subProvider;
	
	public function run()
	{
		$uid = Yii::app()->user->id;
		
		if (!$uid || !$this->commentableType)
			return;
		
		$this->notifyUser = $nu = NotifyUser::loadDefault($this->commentableType);
		
		$cr = new CDbCriteria();
		$cr->condition = "t.user_id = ? AND t.commentable_type = ?";
		$cr->params = [$uid, $this->commentableType];
		$cr->with = 'item';
		$this->subProvider = new CActiveDataProvider('NotifySubscription', ['criteria' => $cr, 'pagination' => ['pageSize' => 5]]);
		 
		$this->render('notifySubscription');
	}
	
}
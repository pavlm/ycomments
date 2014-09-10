<?php

class NotifyController extends CController
{
	public function init()
	{
	}

	public function filters()
	{
		return array(
				'accessControl'
		);
	}
	
	public function accessRules()
	{
		// load for bizRule
// 		$comment = in_array($this->action->id, array('update', 'delete')) ? $this->loadModel(@$_REQUEST['id']) : false;
	
		return array(
				array('allow',
						'actions'=>array('userSettings', 'itemSubscribe'),
						'roles'=>array('authenticated'),
				),
				array('allow',
						'actions' => array('performNotify'),
						'ips' => array('127.0.0.1'), // from cron only
						'users' => array('*'),
				),
				array('deny',
						'users' => array('*'),
				),
		);
	}
	
	/**
	 * изменение настроек подписки пользователя
	 */
	public function actionUserSettings()
	{
		$postNU = @$_POST['NotifyUser'];
		
		$nu = NotifyUser::loadDefault(@$postNU['commentable_type']);
		if (!empty($postNU))
		{
			// todo: filter notify_all option (admin-only)
			$nu->attributes = $_POST['NotifyUser'];
		}
		if ($nu->save()) {
			Yii::app()->user->setFlash('user-notify', YCommentsModule::t('Settings saved'));
		} else {
			Yii::app()->user->setFlash('user-notify', print_r($nu->getErrors(), true));
		}
			
		$this->widget('ycomments.widgets.NotifySubscriptionWidget', 
			array('commentableType' => $nu->commentable_type));
	}
	
	/**
	 * подписка/отписка на материал
	 */
	public function actionItemSubscribe()
	{
		$postNS = @$_POST['NotifySubscription'];
		$active = @$postNS['active'];
		
		$ns = NotifySubscription::findOrCreate(@$postNS['commentable_type'], @$postNS['item_id'], Yii::app()->user->id);
		if (!$ns->validate()) {
			print_r($ns->getErrors());
			return;
		}
		
		if ($active) {
			$ns->save();
			Yii::app()->user->setFlash('user-notify', YCommentsModule::t('Subscription performed'));
		} else {
			if (!$ns->isNewRecord) {
				$ns->delete();
				Yii::app()->user->setFlash('user-notify', YCommentsModule::t('Subscription canceled'));
			}
		}

		$this->widget('ycomments.widgets.NotifyItemSubscriptionWidget',
				array('commentableType' => $ns->commentable_type, 'itemId' => $ns->item_id));
	}
	
	
	public function actionPerformNotify()
	{
		$ns = new NotifySender();
		$ns->send();
	}
	
}

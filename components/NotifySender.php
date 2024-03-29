<?php
/**
 * 
 * Отправка коментариев
 *
 */
class NotifySender extends CComponent
{
	/**
	 * @var YCommentsModule
	 */
	public $cm;
	
	public $commentableType;
	
	/**
	 * @var CommentableBehavior
	 */
	public $behavior;
	
	public function __construct()
	{
		$this->cm = Yii::app()->getModule('ycomments');
	}

	public function send()
	{
		foreach ($this->cm->commentableTypes as $ct) {
			$this->sendForType($ct);
		}
	}
	
	public function sendForType($commentableType)
	{
		$this->commentableType = $commentableType;
		
		/* @var $model CActiveRecord */
		$model = new $commentableType;
		/* @var $behavior CommentableBehavior */
		$this->behavior = $behavior = $model->asa('commentable');
		if (!$behavior->notifyEnabled)
			return;
		$this->log('checking for type '.$commentableType);
		$commentType = $behavior->commentType;
		/* @var $comment Comment */
		$comment = Yii::createComponent($commentType);

		
		/*
		 * 1. load new comments 
		 */
		$ng = NotifyGlobal::findOrCreate($commentableType);
		$dateFilter = max(array($ng->last_check_at, date('Y-m-d'))); // максимум - с начала текущего дня
		Comment::$commentableType = $commentableType; 
		$cr = $behavior->getCommentsCriteria();
		$cr->addCondition('t.created_at > :dateFilter');
		$cr->params['dateFilter'] = $dateFilter;
		$cr->with = array_merge($cr->with, array('parent', 'commentableItems'));
		$cr->index = 'id';
		$cs = $comment->findAll($cr);
		if (empty($cs))
			return;
		
		/*
		 * 2. find subscribed users
		 */
		$us1 = $this->getSubscribedAdmins($commentableType);
		$us2 = $this->getSubscribedOnReplies($commentableType, $cs);
		$us3 = $this->getSubscribedUsers($commentableType, $cs);
		$us = $us1 + $us2 + $us3;

		/*
		 * 3. send notifications 
		 */
		/* @var $u CommentUser */
		$this->log('notified users: '.print_r(array_keys($us), true));
		foreach ($us as $u)
		{
			$this->filterAndSendForUser($u, $cs);
		}
		
		/*
		 * 4. set last check time
		 */
		$cf = reset($cs);
		$commentOldest = array_reduce($cs, function($res, $c){
			return ($c->created_at > $res->created_at) ? $c : $res;
		}, $cf);
		$ng->last_check_at = $commentOldest->created_at;
		if (!defined('YCOMMENTS_DEBUG'))
			$ng->save();
	}
	
	/**
	 * @param CommentUser $u
	 * @param Comment[] $cs
	 */
	public function filterAndSendForUser($u, &$cs)
	{
		$this->log('filtering comments for user:'.$u->id);
		
		$items = $this->getRelatedItems($cs);
		if (empty($items)) {
			return;
		}
		$itemIds = array_keys($items);
		Commentable::$commentableType = $this->commentableType;
		$itemsSubs = Commentable::model()->with('subscriptions')->findAllByPk($itemIds, array('index' => 'id'));
		
		if ($u->isSubscribedAdmin($this->commentableType)) {
			$this->log('user is admin');
			$this->sendForUser($u, $cs);
		} else {
			
			$this->log('user non-admin or not subscribed admin');
			$csf = array();
			/* @var $c Comment */
			foreach ($cs as $c)
			{
				if ($c->user_id == $u->id) {
					continue;
				}
				
				if (!$item = $c->getCommentableItem())
					continue; // unrelated
				$itemSub = $itemsSubs[$item->id]; // todo: check empty
				if ($u->isSubscribedOnItem($itemSub)) {
					$this->log('cfilter:subscribed: cid='.$c->id.', iid='.$item->id);
					$csf[$c->id] = $c;
					continue;
				}
				if ($u->isSubscribedOnReplies($c)) {
					$this->log('cfilter:replysub:'.$c->id);
					$csf[$c->id] = $c;
					continue;
				}
				
				$this->log('cfilter:skip:'.$c->id);
			}
			
			if (!empty($csf))
				$this->sendForUser($u, $csf);
			
		}
		
	}
	
	/**
	 * Все комментарии сгруппировать по материалам и отослать
	 * @param CommentUser $u
	 * @param Comment[] $cs
	 */
	public function sendForUser($u, &$cs)
	{
		$items = $this->getRelatedItems($cs);
		$groups = array();
		foreach ($cs as $c) {
			if (!$item = $c->getCommentableItem()) continue;
			$groups[$item->id][] = $c->id;
		}

		$from = $this->behavior->notifyMailFrom ?: $this->cm->notifyMailFrom;
		$subject = $this->behavior->notifyMailSubject;
		$to = $u->getUEmail();
		$body = Yii::app()->controller->widget('ycomments.widgets.CommentsWidget', array(
			'commentableType' => $this->commentableType, 'view' => 'notify',
			'options' => array('user' => $u, 'items' => $items, 'comments' => $cs, 'groups' => $groups),
		), true);
		
		$this->log($body);
		
		$emailParams = compact('from', 'to', 'subject', 'body');
		
		if (is_callable($this->cm->emailSenderCallable))
			$sent = $this->cm->emailSenderCallable($emailParams);
		else 
			$sent = $this->sendEmail($emailParams);
		
// 		$testMode = Yii::app()->params['notifyTestMode'];
// 		$testEmails = Yii::app()->params['notifyTestEmails'];

		$msg = $sent ? 'mail sent' : 'mail not sent';
		$this->log($msg." : ".json_encode(array('to' => $to, 'subject' => $subject)));
		
		return true;
	}
	
	/**
	 * @param Comment[] $cs
	 * @return CActiveRecord[]
	 */
	public function getRelatedItems(&$cs)
	{
		// todo: batch load
		$is = array();
		foreach ($cs as $c) {
			if (!$item = $c->getCommentableItem()) continue;
			$is[$item->id] = $item;
		}
		return $is;
	}
	
	/*
	 * администраторы подписанные на все сообщения
	 */
	public function getSubscribedAdmins($commentableType)
	{
		// todo: universal method
		$cr = new CDbCriteria($this->cm->criteriaAdminUsers);
		$cr->mergeWith(array('with' => 'notifyUser', 'index' => 'id'));
		$us = CommentUser::model()->findAll($cr);
		$us = array_filter($us, function($u)use($commentableType){
			/* @var $u CommentUser */
			$nu = $u->getNotifyUserOrDefault($commentableType);
			return $nu->notify_all; // подписан или нет
		});
		return $us;
	}
	
	/*
	 * пользователи с подпиской на ответы к их комментариям
	 */
	public function getSubscribedOnReplies($commentableType, &$cs)
	{
		// find comments that has been responded
		$csParents = array_filter(array_values(array_map(function($c){ return $c->parent; }, $cs)));
		if (empty($csParents))
			return array();
		$authorIds = array_unique(array_map(function($c){ return $c->user_id; }, $csParents));
		$us = CommentUser::model()->findAllByPk($authorIds, array('with' => 'notifyUser', 'index' => 'id'));
		$us = array_filter($us, function($u)use($commentableType){
			/* @var $u CommentUser */
			$nu = $u->getNotifyUserOrDefault($commentableType);
			return $nu->notify_reply; // подписан на комментарии к своим ответам?
		});
		return $us;
	}
	
	/*
	 * пользователи подписанные на новости
	 */
	public function getSubscribedUsers($commentableType, &$cs)
	{
		$items = $this->getRelatedItems($cs);
		$itemIds = array_keys($items);
		Commentable::$commentableType = $this->commentableType;
		$itemsSubs = Commentable::model()->with('subscriptions')->findAllByPk($itemIds, array('index' => 'id'));
		$subs = array_reduce($itemsSubs, function($res, $item){ 
			return array_merge($res, $item->subscriptions); 
		}, array());
		$uids = array_map(function($sub){ return $sub->user_id; }, $subs);
		if (empty($uids))
			return array();
		$us = CommentUser::model()->findAllByPk($uids, array('with' => 'notifyUser', 'index' => 'id'));
		$this->log('subscribed users: '.print_r(array_keys($us), true));
		return $us;
	}
	
	public function log($msg)
	{
		Yii::log($msg, $level=CLogger::LEVEL_INFO, 'notify');
	}
	
	public function sendEmail($emailParams)
	{
		extract($emailParams);
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "From: {$from}";
		$headers[] = "Content-Type: text/html; charset=UTF-8";
		$subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
		$body = '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8" /></head><body>'.$body.'</body></html>';
		if (defined('YCOMMENTS_DEBUG') && YCOMMENTS_DEBUG) {
			$this->log(var_export(array('to' => $to, 'subject' => $subject, 'body' => $body), true));
			return true;
		}
		if (!defined('YCOMMENTS_NOMAIL'))
			return mail($to, $subject, $body, implode("\r\n",$headers));
		else
			return true; 
	}
}
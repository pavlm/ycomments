<?php

/**
 *
 * @property YCommentsModule $module
 *
 */
class CommentableBehavior extends CActiveRecordBehavior
{
	
	/**
	 * @var string name of the table defining the relation with comment and model
	 */
	public $mapTable = 'news_comment';
	/**
	 * @var string name of the table column holding commentId in mapTable
	 */
	public $mapCommentColumn = 'comment_id';
	/**
	 * @var string name of the table column holding related Objects Id in mapTable
	 */
	public $mapRelatedColumn = 'news_id';
	/**
	 * @var bool - base views will be loaded (in base directory), if not - path to views will be based on comment model name (e.g. Review) 
	 */
	public $baseViews = true;
	
	public $allowReply = true;
	
	public $allowVotes = false;
	
	public $allowTags = false;
	
	public $showAnchors = true;
	
	public $sortTree = false;
	/**
	 * @var bool comments in tree or list
	 */
	public $treeNotList = true;
	/**
	 * @var int|bool максимальное уровень вложенности разрешенный для коментирования
	 */
	public $maxReplyLevel = false;
	
	public $commentsLoaded = 0;

	public $notifyEnabled = false;
	
	public $notifySubscriptionEnabled = false;
	
	public $notifyMailSubject = 'New comments';
	
	public $notifyMailFrom;
	
	/**
	 * link to commentable item
	 * function($commentable) {
	 * 		return array('news/view', 'id' => $commentable->id);
	 * } 
	 * @var Callable
	 */
	public $commentableUrl;
	
	/**
	 * @var array
	 */
	public $commentCriteria;
	
	/**
	 * @var array - labels override
	 */
	public $labels = array();
	
	public function attach($owner)
	{
		parent::attach($owner);
		// make sure comment module is loaded so views can be rendered properly
		Yii::app()->getModule('comment');
	}

	/**
	 * @return YCommentsModule
	 */
	public function getModule()
	{
		return Yii::app()->getModule('ycomments');
	}
	
	/**
	 * override for module field
	 * @var string
	 */
	protected $_commentType;
	
	public function getCommentType() {
		return (isset($this->_commentType)) ? $this->_commentType : $this->getModule()->commentType;
	}
	
	public function setCommentType($v) {
		$this->_commentType = $v;
	}
	
	/**
	 * returns a new comment instance that is related to the model this behavior is attached to
	 *
	 * @return Comment
	 * @throws CException
	 */
	public function getCommentInstance()
	{
		$comment = Yii::createComponent($this->commentType);
		$types = array_flip($this->module->commentableTypes);
		if (!isset($types[$c=get_class($this->owner)])) {
			throw new CException('No scope defined in YCommentsModule for commentable Model ' . $c);
		}
		$comment::$commentableType = get_class($this->owner);
		$comment->setKey($this->owner->primaryKey);
		$comment->user_id = Yii::app()->user->id;
		return $comment;
	}

	/**
	 * get all related comments for the model this behavior is attached to
	 *
	 * @return Comment[]
	 * @throws CException
	 */
	public function getComments()
	{
		$comments = Yii::createComponent($this->commentType)
					     ->findAll($this->getCommentCriteria());
		// get model type
		$type = get_class($this->owner);
		foreach($this->module->commentableTypes as $scope => $model) {
			if ($type == $model) {
				$type = $scope;
				break;
			}
		}
		foreach($comments as $comment) {
			/** @var Comment $comment */
			$comment->setKey($this->owner->primaryKey);
			$comment->items = array($this->owner); // prevent lazy load
		}
		$this->commentsLoaded = count($comments);

		if ($this->treeNotList)
		{
			return $this->createCommentsTree($comments);
		}
		
		if ($this->sortTree)
			$this->sortComments($comments);
		
		return $comments;
	}
	
	/**
	 * упорядочивание в список древовидных комментариев
	 * @param array $csin
	 */
	public function sortComments(&$csin)
	{
		$cs = array();
		foreach ($csin as $c) {
			$cs[$c->id] = $c;
		}
		
		$getLevel = function($c) use($cs, &$getLevel) {
			if ($c->level) return $c->level;
			if (!$c->parent_id) {
				$c->level = 1;
			} else {
				if ($parent = @$cs[$c->parent_id]) {
					$parent->childs[] = $c;
					$c->level = $getLevel($parent) + 1;
				} else {
					$c->level = 1;
				}
			}
			return $c->level;
		};
		
		foreach ($cs as $c) {
			$getLevel($c);
		}
		
		$csroots = array_filter($cs, function($c){ return $c->level == 1; });
		
		$csflat = array(); 
		
		$flattern = function($c) use(&$csflat, &$flattern) {
			$csflat[] = $c;
			foreach ($c->childs as $cc) {
				$flattern($cc);
			}
		};
		
		foreach ($csroots as $c) {
			$flattern($c);
		}
		
		$csin = $csflat;
	}
	
	/**
	 * Получение дерева из списка комментариев
	 * @param Comment[] $csin
	 */
	public function createCommentsTree($csin)
	{
		$csids = array();
		foreach ($csin as $c) {
			$csids[$c->id] = $c;
		}
		
		foreach ($csin as $c) {
			if (!$cp = @$csids[$c->parent_id])
				continue;
			$cp->childs[] = $c;
		}
		
		$cs = array_filter($csin, function($c) use($csids) {
			return !@$csids[$c->parent_id]; // root comments 
		} );
		return $cs;
	}

	/**
	 * count all related comments for the model this behavior is attached to
	 *
	 * @return int
	 * @throws CException
	 */
	public function getCommentCount()
	{
		return Yii::createComponent($this->commentType)
					->count($this->getCommentCriteria());
	}

	public function getCommentCriteria($commentTableAlias=null)
	{
		if (is_null($this->mapTable) || is_null($this->mapRelatedColumn)) {
			throw new CException('mapTable and mapRelatedColumn must not be null!');
		}

		$commentTableAlias = $commentTableAlias ?: 't';
		
		// @todo: add support for composite pks
		$cr = new CDbCriteria(array(
			'join' => "JOIN " . $this->mapTable . " cm ON {$commentTableAlias}.id = cm." . $this->mapCommentColumn,
		    'condition' => "cm." . $this->mapRelatedColumn . "=:pk",
			'params' => array(':pk'=>$this->owner->getPrimaryKey()),
			'order' => "{$commentTableAlias}.created_at asc",
			'with' => array('user'),
		));
		
		if ($this->commentCriteria)
			$cr->mergeWith($this->commentCriteria);

		if ($this->allowReply) {
			$cr->with = array_merge($cr->with, array('parent'));
		}

		if ($this->allowVotes) {
			$cr->with = array_merge($cr->with, array('userLike'));
		}
		
		return $cr;
	}
	
	/**
	 * all comments for this type
	 */
	public function getCommentsCriteria($commentTableAlias=null)
	{
		$commentTableAlias = $commentTableAlias ?: 't';
		
		$cr = new CDbCriteria(array(
				'join' => "JOIN " . $this->mapTable . " cm ON {$commentTableAlias}.id = cm." . $this->mapCommentColumn,
				'order' => "{$commentTableAlias}.created_at asc",
				'with' => array('user'),
		));
		return $cr;
	}

	/**
	 * @todo this should be moved to a controller or widget
	 *
	 * @return CArrayDataProvider
	 */
	public function getCommentDataProvider()
	{
		return new CArrayDataProvider($this->getComments());
	}

	/**
	 * @param CommentEvent $event
	 */
	public static function onNewCommentHandler($event) {
		self::callCommentEvent('onNewComment', $event);
	}

	public static function onUpdateCommentHandler($event) {
		self::callCommentEvent('onUpdateComment', $event);
	}

	public static function onDeleteCommentHandler($event) {
		self::callCommentEvent('onDeleteComment', $event);
	}
	
	/**
	 * @param string $eventName
	 * @param CommentEvent $event
	 */
	private static function callCommentEvent($eventName, $event) {
		$host = $event->commentedModel;
		if (method_exists($host, $eventName)) {
			call_user_func(array($host, $eventName), $event);
		}
	}
	
	private $delCmd;
	
	public function beforeDelete($event)
	{
		// remove linked comments
		/* @var $comment Comment */
		$comment = Yii::createComponent($this->commentType);
		$crit = $this->getCommentCriteria($comment->tableName());
		$crit->order = null;
// 		$comment->model()->deleteAll($crit);
		$this->delCmd = Yii::app()->db->commandBuilder->createDeleteCommand($comment->tableName(), $crit);
		$this->delCmd->execute();
		return true;
	}
	
	public function afterDelete($event)
	{
	}
	
	/**
	 * @return array
	 */
	public function getCommentableUrl() {
		$c = $this->commentableUrl;
		return $c($this->owner);
	}
	
}


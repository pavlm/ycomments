<?php

class YCommentsModule extends CWebModule
{
	/**
	 * @var array associative array of 'scopename' to commentable models 'modelclass'
	 *
	 * 'scopename' must be lower case and is an alias for the model
	 * class name that will be send with the create comment http request.
	 *
	 * 'modelclass' is a class name of the commentable AR
	 * this AR class must have the {@see CommentableBehavior} attached to it
	 */
	public $commentableTypes = array();

	/**
	 * @var string name of the user model class to use for comments
	 */
	public $userModelClass = 'User';
	/**
	 * @var string attribute which holds the name of the user in {@see $userModelClass}
	 */
	public $userNameAttribute = 'name';
	/**
	 * @var string attribute which holds the email of the user in {@see $userModelClass}
	 */
	public $userEmailAttribute = 'email';
	/**
	 * @var array you can set filters that will be added to the comment controller {@see CController::filters()}
	 */
	public $controllerFilters = array();
	/**
	 * @var array you can set accessRules that will be added to the comment controller {@see CController::accessRules()}
	 */
	public $controllerAccessRules = array();
	/**
	 * @var string allows you to extend comment class and use your extended one, set path alias here
	 */
	public $commentType = 'ycomments.models.Comment';
	
	public $notifyMailFrom;
	
	public $adminLayout;

    public function init()
    {
        // this method is called when the module is being created
        // you may place code here to customize the module or the application

        // import the module-level models and components
        $this->setImport(array(
	        'ycomments.models.*',
	        'ycomments.components.*',
        ));
        
		$this->attachEventHandler('onNewComment', array('CommentableBehavior', 'onNewCommentHandler'));
		$this->attachEventHandler('onUpdateComment', array('CommentableBehavior', 'onUpdateCommentHandler'));
		$this->attachEventHandler('onDeleteComment', array('CommentableBehavior', 'onDeleteCommentHandler'));
    }

    public function beforeControllerAction($controller, $action)
    {
	    // @todo: what to do if user is not loggend in and want to comment?
        if(parent::beforeControllerAction($controller, $action))
        {
            // this method is called before any module controller action is performed
            // you may place customized code here
            return true;
        }
        else
            return false;
    }
    
	/**
	 * This event is raised after a new comment has been added
	 *
	 * @param $comment
	 * @param $model
	 */
	public function onNewComment($comment, $model)
	{
		$event = new CommentEvent();
		$event->comment = $comment;
		$event->commentedModel = $model;
		$this->raiseEvent('onNewComment', $event);
	}

	/**
	 * This event is raised after a comment has been updated
	 *
	 * @param $comment
	 * @param $model 
	 */
	public function onUpdateComment($comment, $model=null)
	{
		$event = new CommentEvent();
		$event->comment = $comment;
		$event->commentedModel = $model;
		$this->raiseEvent('onUpdateComment', $event);
	}

	/**
	 * This event is raised after a comment got deleted
	 *
	 * @param $comment
	 * @param $model 
	 */
	public function onDeleteComment($comment, $model=null)
	{
		$event = new CommentEvent();
		$event->comment = $comment;
		$event->commentedModel = $model;
		$this->raiseEvent('onDeleteComment', $event);
	}
}
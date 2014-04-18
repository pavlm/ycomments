<?php

class CommentsWidget extends CWidget 
{
	/**
	 * commentable model
	 * @var CActiveRecord
	 */
	public $model;
	
	/**
	 * @var Comment
	 */
	public $comment;
	
	/**
	 * @var string class name of $model
	 */
	public $commentableType;
	
	/**
	 * @var string class name of $comment
	 */
	public $commentType;
	
	public $view = 'commentList';
	
	public $optionsDefault = array('appendComment' => false, 'charCounter' => true);
	
	public $options = array();
	
	public $readOnly = false;
	
	public $type;
	
	public $htmlOptions = array();
	
	public function init()
	{
		$this->getModule();
		
		if ($this->model)
			$this->commentableType = get_class($this->model);
		elseif ($this->commentableType)
			$this->model = Yii::createComponent($this->commentableType);
			
		if ($this->comment)
			$this->commentType = get_class($this->comment);
		
		if (!$this->comment && !$this->commentType) {
			$b = $this->getCommentableBehavior();
			$this->commentType = $b->commentType;
			$this->comment = $this->model->commentInstance;
		}
		
		$this->options = array_merge($this->optionsDefault, $this->options);
	}
	
	/**
	 * @return CommentableBehavior
	 */
	public function getCommentableBehavior() {
		return $this->model->commentable;
	}
	
	public function run()
	{
		$view = $this->type ? $this->type.'/'.$this->view : $this->view; // can load from views/type1
		$this->renderTyped($view);
	}
	
	public function renderTyped($view,$data=null,$return=false)
	{
		$behavior = $this->getCommentableBehavior();
		$baseViews = $behavior->baseViews;
		if ($baseViews) {
			return $this->render('base/'.$view, $data, $return);
		} else {
			$viewsType = strtolower(get_class($this->comment));
			return $this->render($viewsType.'/'.$view, $data, $return);
		}		
	}
	
	public function getJSCommentsSets()
	{
		$sets = array(
			'commentType' => $this->commentType,
			'commentableType' => $this->commentableType,
			'baseUrl' => '/ycomments/comment/',
			'readOnly' => $this->readOnly,
			'tree' => $this->getCommentableBehavior()->treeNotList,
		);
		if (Yii::app()->request->enableCsrfValidation) {
			$sets['csrfTokenName'] = Yii::app()->request->csrfTokenName;
			$sets['csrfToken'] = Yii::app()->request->csrfToken;
		}
		$sets = array_merge($sets, $this->options);
		if ($this->options['charCounter'] && $this->comment) {
			$sets['messageLength'] = $this->comment->getMessageRuleLength();
		}
		if ($this->model)
			$sets['key'] = $this->model->id;
		return $sets;
	}

	/**
	 * @return YCommentsModule
	 */
	public function getModule() {
		return Yii::app()->getModule('ycomments');
	}
	
	public function getModuleBasePath() {
		return Yii::app()->getModule('ycomments')->getBasePath();
	}
	
}
<?php

class AdminController extends CController
{
	
	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
				'accessControl'
		);
	}
	
	public function accessRules()
	{
		return array(
				array('allow',
						'actions'=>array('list'),
						'roles'=>array('comment-admin'),
				),
				array('deny',
						'users' => array('*'),
				),
		);
	}
	
	public function loadCommentableMeta()
	{
		$types = $this->getModule()->commentableTypes;
		$types = array_map(function($type){
			$meta = array(
				'type' => $type,
				'model' => CActiveRecord::model($type),
			);
			return $meta;
		}, $types);
		return $types;
	}
	
	/**
	 * Manages all models.
	 */
	public function actionList($type=null)
	{
/* 		$model=Yii::createComponent($this->module->commentType, 'search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Comment']))
			$model->attributes=$_GET['Comment'];
*/		
		$types = $this->loadCommentableMeta();
		
		$typeSelected = $type ?: reset(array_keys($types));
		$model = $types[$typeSelected]['model'];
		Comment::$commentableType = $typeSelected;
		/* @var $behavior CommentableBehavior */
		if (!$behavior = $model->commentable)
			throw new CException();
		$cr = $behavior->getCommentsCriteria();
		$cr->with = array('items', 'user');
		$cr->order = '';
		$provider = new CActiveDataProvider($behavior->getCommentInstance(), array(
				'criteria' => $cr,
				'pagination' => array('pageSize' => 10),
				'sort' => array('defaultOrder' => 'created_at DESC'),
		));
		
		$this->render('list',array(
				'typeSelected' => $typeSelected,
				'types' => $types,
				'provider' => $provider,
		));
	}
	
}

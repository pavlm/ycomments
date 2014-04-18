<?php

/**
 *
 * @property YCommentsModule $module
 *
 */
class CommentController extends CController
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

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		// load for bizRule
		$commentType = @$_REQUEST['commentType'];
		$comment = in_array($this->action->id, array('update', 'delete')) ? $this->loadModel(@$_REQUEST['id'], $commentType) : false;
		
		return array(
			array('allow',
				'actions'=>array('create', 'like'),
				'roles'=>array('commentator'),
			),
			array('allow',
				'actions'=>array('update', 'delete'),
				'roles'=>array('comment-admin', 'comment-author'=>array('comment' => $comment)),
			),
			array('deny',
				'users' => array('*'),
			),
		);
	}

	/**
	 * Creates a new comment.
	 *
	 * On Ajax request:
	 *   on successfull creation comment/_view is rendered
	 *   on error comment/_form is rendered
	 * On POST request:
	 *   If creation is successful, the browser will be redirected to the
	 *   url specified by POST value 'returnUrl'.
	 */
	public function actionCreate()
	{
		$commentType = Yii::app()->request->getParam('commentType') ?: $this->module->commentType;
		$commentableType = Yii::app()->request->getParam('commentableType');
		Comment::$commentableType = $commentableType;
		
		/** @var Comment $comment */
		$comment = Yii::createComponent($commentType);
		
		if (!($comment instanceof Comment))
			throw new Exception(null, 404);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if (isset($_POST[$cClass=get_class($comment)]))
		{
			$comment->attributes = $_POST[$cClass];
			$comment->key  = $_POST[$cClass]['key'];
			
			// determine current users id
			if (Yii::app()->user->isGuest) {
				$comment->user_id = null;
			} else {
				$comment->user_id = Yii::app()->user->id;
			}

			if(Yii::app()->request->isAjaxRequest) {
				$output = '';
				$commentSaved = $comment;
				if($saved = $comment->save())
				{
					// refresh model to replace CDbExpression for timestamp attribute
					$comment->refresh();

					// render new comment
					$output .= $this->widget('ycomments.widgets.CommentsWidget', array(
						'view' => '_view',
						'comment' => $comment,
						'commentableType' => $commentableType,
					), true); 
					// create new comment model for empty form
					$comment = Yii::createComponent($commentType);
					$comment->key  = $_POST[$cClass]['key'];
				}
				
				if (!$commentSaved->parent_id || !$saved) 
				{
					// render comment form, если только создан новый, а не ответ
					$output .= $this->widget('ycomments.widgets.CommentsWidget', array(
							'view' => '_form',
							'comment' => $comment,
							'commentableType' => $commentableType,
					), true);
				}
				// render javascript functions
				Yii::app()->clientScript->renderBodyEnd($output);
				echo $output;
				Yii::app()->end();
			} else {
				if($comment->save()) {
					$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('view','id'=>$comment->id));
				} else {
					// @todo: what if save fails?
				}
			}
		} else {
			
			//
			// return create reply comment form
			//
			
			$id = @$_REQUEST['id']; // reply to this comment
			$commentType = Yii::app()->request->getParam('commentType');
			$commentableType = Yii::app()->request->getParam('commentableType');
			$key = Yii::app()->request->getParam('key');
			$pcomment = $this->loadModel($id, $commentType);
			$pcomment->validateType();

			/** @var Comment $comment */
			$comment = Yii::createComponent($commentType);
			if (!($comment instanceof Comment))
				throw new Exception(null, 404);
			$comment->setKey($key);
			$comment->parent_id = $id;	
				
			$output = $this->widget('ycomments.widgets.CommentsWidget', array(
					'view' => '_form',
					'comment' => $comment,
					'commentableType' => $commentableType,
			), true);
			Yii::app()->clientScript->renderBodyEnd($output);
			echo $output;
			Yii::app()->end();
				
		}

	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate()
	{
		$id = @$_REQUEST['id'];
		$commentType = Yii::app()->request->getParam('commentType');
		$commentableType = Yii::app()->request->getParam('commentableType');
		$key = Yii::app()->request->getParam('key');
		Comment::$commentableType = $commentableType;
		$comment = $this->loadModel($id, $commentType);
		$comment->setKey($key);
		$comment->validateType();
		
		if(isset($_POST[$cClass=get_class($comment)]))
		{
			$comment->attributes = $_POST[$cClass];
				
			if ($comment->save())
			{
				if(Yii::app()->request->isAjaxRequest) {
					// refresh model to replace CDbExpression for timestamp attribute
					$comment->refresh();

					// render updated comment
					$this->widget('ycomments.widgets.CommentsWidget', array(
							'view' => '_view',
							'comment' => $comment,
							'commentableType' => $commentableType,
					));
// 					$this->renderPartial('_view',array(
// 						'data'=>$comment,
// 					));
					Yii::app()->end();
				} else {
					$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('view','id'=>$comment->id));
				}
			}
		}

		if(Yii::app()->request->isAjaxRequest)
		{
			$output = $this->widget('ycomments.widgets.CommentsWidget', array(
					'view' => '_form',
					'comment' => $comment,
					'commentableType' => $commentableType,
			), true);
				
// 			$output = $this->renderPartial('_form',array(
// 				'comment'=>$comment,
// 				'commentableType' => $commentableType,
// 				'ajaxId'=>time(),
// 			), true);
			// render javascript functions
			Yii::app()->clientScript->renderBodyEnd($output);
			echo $output;
			Yii::app()->end();
		}
		else
		{
			$this->render('update',array(
				'model'=>$comment,
			));
		}
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete()
	{
		$id = Yii::app()->request->getParam('id');
		$commentType = Yii::app()->request->getParam('commentType');
		$commentableType = Yii::app()->request->getParam('commentableType');
		Comment::$commentableType = $commentableType;
		$comment = $this->loadModel($id, $commentType);
		$comment->validateType();
		
		// we only allow deletion via POST request
		if(Yii::app()->request->isPostRequest)
		{
			$c = Yii::createComponent($commentType);
			$childsCount = $c->model()->count('t.parent_id = ?', array($id));
			if ($childsCount) {
				throw new CHttpException(400, 'there are child items');
			}
			
			$comment->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if (!Yii::app()->request->isAjaxRequest) {
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
			}
		}
		else {
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
		}
	}
	
	/**
	 * Like for comment
	 * @throws CHttpException
	 */
	public function actionLike()
	{
		$id = @$_REQUEST['id'];
		$commentableType = @$_REQUEST['commentableType'];
		
		$commentable = Yii::createComponent($commentableType);
		/* @var $behavior CommentableBehavior */
		$behavior = $commentable->commentable;
		$commentType = $behavior->commentType;
		$comment = $this->loadModel($id, $commentType);
		if (!$uid = Yii::app()->user->id)
			throw new CHttpException(403);
		$commentLikeType = $comment::$commentLikeType;
		$liked = CommentLike::model($commentLikeType)->findByPk(array('comment_id' => $id, 'user_id' => $uid));
		
		$trx = Comment::model()->getDbConnection()->beginTransaction();
		
		$dir = $liked ? -1 : 1;
		Comment::model($commentType)->updateCounters(
			array('votes_up' => $dir), "id=? ".($liked ? ' AND votes_up>0' : ''), array($id));
		if ($liked) {
			$liked->delete();
		} else {
			$like = new $commentLikeType;
			$like->comment_id = $id;
			$like->user_id = $uid;
			$like->save();
		} 
			
		$trx->commit();
		
		$res = array('likes' => $comment->votes_up + $dir, 'dir' => $dir);
		echo json_encode($res);
		Yii::app()->end();
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 * @return Comment
	 */
	public function loadModel($id, $type=null)
	{
		$model = Yii::createComponent(@$type ?: $this->module->commentType)->findByPk((int) $id);
		if ($model === null || !($model instanceof Comment)) {
			throw new CHttpException(404,'The requested page does not exist.');
		}
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='comment-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}

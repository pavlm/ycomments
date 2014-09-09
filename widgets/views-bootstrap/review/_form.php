<?php
/* @var $this CommentsWidget */
$comment = $this->comment;
$commentableType = $this->commentableType;
?>
<?php if (Yii::app()->user->isGuest):?>
<div class="comment__not-logged-in"></div>
<?php else: ?>
<?
$classes = sprintf("form comment %s %s", ($comment->id ? 'comment-exists' : ''), (!$comment->parent_id && !$comment->id ? 'static' : '')); 
?>
<div id="comment-<?=intval($comment->id);?>" class="<?=$classes?>">

	<div class="comment__avatar-cell">
		<? echo $comment->user ? $comment->user->getGravatarImg() : '' ?>
	</div>

	<div class="comment__content-cell">


<?php $form = $this->beginWidget('CActiveForm', array(
	'id'=>'comment-form', 'htmlOptions' => array('class' => 'comment__form'),
    'action'=>array('/comment/comment/create'),
	'enableAjaxValidation'=>false
)); ?>

	<?php /* @var $form SActiveForm */
	echo $form->errorSummary($comment);
	echo CHtml::hiddenField('commentType', get_class($comment));
	echo CHtml::hiddenField('commentableType', @$commentableType);
	if ($comment->isNewRecord) {
    	echo $form->hiddenField($comment, 'key');
    	echo $form->hiddenField($comment, 'parent_id');
    }
	?>
	
	<? if (!$comment->parent_id): // rating only for top reviews ?>
	<div class="comment__form__row">
		<label class="comment__form__label comment__form__label-rating">оценка</label>
		<div class="comment__form__rating">
    	<? $this->widget('CStarRating', array(
    			'model' => $comment, 'attribute'=>'rating', 'maxRating' => 5, 'allowEmpty' => false)); ?>
    	</div>
    	<div class="empty"></div>
	</div>
	<? endif; ?>
	<div class="comment__form__row comment-textedit">
	<?php echo $form->textArea($comment,'message',
			array('rows'=>5, 'cols'=>30, 'class' => 'comment__form__message comment-message boxsizing-border', 
				'placeholder' => $comment->getAttributeLabel('message'), 'maxlength' => 4096)); ?>
		<? if (Yii::app()->user->checkAccess('moderator')): ?>			
		<div class="comment-textedit__menu_knob">
			<span class="ui-icon ui-icon-gear"></span>
			<div class="comment-menu" style="">
				<div class="comment-menu-item"> <?=CHtml::openTag('a', array('href' => '#', 'class' => 'comment-cmd', 'data-comment' => json_encode(array('cmd' => 'addLink'))))?> <span class="ui-icon ui-icon-link"></span>вставить ссылку...</a> </div>
				<div class="comment-menu-item"> <?=CHtml::openTag('a', array('href' => '#', 'class' => 'comment-cmd', 'data-comment' => json_encode(array('cmd' => 'addImage'))))?> <span class="ui-icon ui-icon-image"></span>вставить изображение...</a> </div>
				<div class="comment-menu-item"> <?=CHtml::openTag('a', array('href' => '#', 'class' => 'comment-cmd', 'data-comment' => json_encode(array('cmd' => 'addVideo'))))?><span class="ui-icon ui-icon-video"></span>вставить видео...</a> </div>
			</div>
		</div>
		<? endif; ?>
	</div>
	
	<div class="comment__commands">
		<div class="btn-group">
	    <? if ($comment->isNewRecord): ?>
	    	<? 
	    	echo CHtml::link(YCommentsModule::t('Add review'), '#', array('class' => 'btn btn-small comment__button-post comment-cmd', 'title' => 'Ctrl+Enter',
				'data-comment' => json_encode(array('cmd' => 'post', 'cid' => 0, 'parent_id' => $comment->parent_id), JSON_NUMERIC_CHECK)) );
			if ($comment->parent_id)
				echo CHtml::link(YCommentsModule::t('Close'), '#', array('class' => 'btn btn-small comment__button-close comment-cmd', 
					'data-comment' => json_encode(array('cmd' => 'close', 'cid' => 0, 'parent' => $comment->parent_id), JSON_NUMERIC_CHECK)) );
			?>

		<? else: ?>
			<? 
			echo CHtml::link(YCommentsModule::t('Save review'), '#', array('class' => 'btn btn-small comment__button-post comment-cmd',
					'data-comment' => json_encode(array('cmd' => 'post', 'cid' => $comment->id), JSON_NUMERIC_CHECK)) );
			echo CHtml::link(YCommentsModule::t('Close'), '#', array('class' => 'btn btn-small comment__button-close comment-cmd',
					'data-comment' => json_encode(array('cmd' => 'close', 'cid' => $comment->id, 'parent' => $comment->parent_id), JSON_NUMERIC_CHECK)) );
			?>

		<? endif; ?>
		</div>
		<span class="comment__form__stat" title="Осталось символов"></span>

		<?
		if ($this->getCommentableBehavior()->notifyEnabled &&
			$this->getCommentableBehavior()->notifySubscriptionEnabled)
		{
			$this->widget('comment.widgets.NotifyItemSubscriptionWidget',
					array('commentableType' => $this->commentableType, 'itemId' => $this->model->id ?: $comment->key));
		}
		?>
		
	</div>

<?php $this->endWidget(); // form ?>

	</div>

</div>
<?php endif; ?>

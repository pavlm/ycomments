<?php
/* @var $this CommentsWidget */
/* @var $model CActiveRecord - commentable model */
$model = $this->model;
$this->registerAssets();
$widgetId = $this->id;
/** @var CArrayDataProvider $comments */
$comments = $model->getComments();

?>

<div class="comment-list-wrap">
	<a name="commentblock"></a>
	<h3 class="comment-list-head"><?=YCommentsModule::t('Comments')?></h3>

<?
echo CHtml::openTag('div', 
	array('id' => $widgetId, 'class' => 'comment-list '.@$this->htmlOptions['class'], 'data-comments-sets' => json_encode($this->getJSCommentsSets())));

if (!$this->readOnly && Yii::app()->user->checkAccess('commentator')) {

	$this->renderTyped('_form');
/* 	
	if ($this->getCommentableBehavior()->notifyEnabled &&
		$this->getCommentableBehavior()->notifySubscriptionEnabled) 
	{
		$this->widget('comment.widgets.NotifyItemSubscriptionWidget',
				array('commentableType' => $this->commentableType, 'itemId' => $this->model->id));
	}
 */	
}


$widget = $this;
/**
 * recursive comments print
 */
$printComments = function($cs, $level=1) use(&$printComments, $widget) {
	foreach ($cs as $c) {
		$widget->renderTyped('_view', array(
			'data' => $c,
			'level' => $level,
			'printComments' => $printComments,
		));
	}
};

if ($model->commentsLoaded) {

	$printComments($comments);
	
}

echo CHtml::closeTag('div');
?>

<script type="text/javascript">
<!--
$(function(){
	$('#<?=$widgetId?>').ycomments();
});
//-->
</script>

</div>

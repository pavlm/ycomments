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
	<a id="commentblock"></a>
	<h3 class="comment-list-head"><?=YCommentsModule::t('Reviews')?></h3>

<?
echo CHtml::openTag('div', 
	array('id' => $widgetId, 'class' => 'comment-list '.@$this->htmlOptions['class'], 'data-comments-sets' => json_encode($this->getJSCommentsSets())));

if (!$this->readOnly && Yii::app()->user->checkAccess('commentator')) {

	$this->renderTyped('_form');
	
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
</div>

<script type="text/javascript">
<!--
$(function(){
	$('#<?=$widgetId?>').ycomments()
		.on('comment.posted', function(e, cid){
			if (cid) {
				$('#user_rating_'+cid+' > input').rating({readOnly:true});				
			}
		});
});
//-->
</script>
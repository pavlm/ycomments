<?php
/* @var $this NotifyItemSubscriptionWidget */
$wid = $this->id.'-'.time();
$behavior = $this->getCommentableBehavior();
?>
<div id="<?=$wid?>" class="notify-item-subscription" data-action="/ycomments/notify/itemSubscribe">
<?
/* @var $form CActiveForm */
$form = $this->beginWidget('CActiveForm', array(
	'action'=>array('/ycomments/notify/itemSubscribe'),
));

if (Yii::app()->user->hasFlash('user-notify'))
	echo CHtml::tag('div', array('class' => '-alert -alert-info'), Yii::app()->user->getFlash('user-notify'));
?>
	<? echo $form->hiddenField($this->notifySubs, 'commentable_type'); ?>
	<? echo $form->hiddenField($this->notifySubs, 'item_id'); ?>
	<? //echo $form->checkBoxRow($this->notifySubs, 'active', array('onclick' => 'submitSubscription(event, this);')); ?>
	<label class="checkbox inline">
	<?
		echo $form->checkBox($this->notifySubs, 'active', array('onclick' => 'submitSubscription(event, this);', 'data-wid' => $wid)).PHP_EOL;
		echo ($label = @$behavior->labels['notify-item-subscribe-label']) ? $label : $this->notifySubs->getAttributeLabel('active');
	?>
	</label>
	
	
<? 
$this->endWidget();
?>
<script type="text/javascript">
function submitSubscription(e, flag) {
	flag.onclick = null;
	var wid = $(flag).attr('data-wid');
 	$form = $(flag).parents(".notify-item-subscription");
 	$.ajax({
 		url:$form.attr("data-action"),
 		data:$form.find('input').serialize(),
 		type:'post',
 		success:function(form){
 			$('#'+wid).replaceWith(form);
 		}
 	});
}
</script>
</div>
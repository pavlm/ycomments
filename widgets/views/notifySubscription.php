<?php
/* @var $this NotifySubscriptionWidget */
if (!Yii::app()->user->id) 
	return;
$wid = $this->id.'-'.time();
?>

<?
echo CHtml::openTag('div', array('id' => $wid));
/* @var $form CActiveForm */
$form = $this->beginWidget('CActiveForm', array(
	'id' => 'user-subs',
	'action'=>array('/ycomments/notify/userSettings'),
)); 
	echo $form->hiddenField($this->notifyUser, 'commentable_type');
	
	//echo $form->errorSummary($this->notifyUser);
	if (Yii::app()->user->hasFlash('user-notify'))
		echo CHtml::tag('div', array('class' => 'alert alert-info'), Yii::app()->user->getFlash('user-notify'));
	
?>
<div>
	
	<?
    $ajax = CHtml::ajax([
        'type' => 'post',
        'url' => new CJavaScriptExpression('jQuery(this).parents("form").attr("action")'),
        'update' => '#'.$wid
    ]);

    if (Yii::app()->user->checkAccess('admin')) {
		echo CHtml::openTag('label', array('for' => 'na-'.$wid));
    	echo $form->checkBox($this->notifyUser, 'notify_all', array(
            'id' => 'na-'.$wid,
            'onclick' => $ajax
        ));
		echo $this->notifyUser->getAttributeLabel('notify_all');
		echo CHtml::closeTag('label');
// 		echo $form->checkBoxRow($this->notifyUser, 'notify_all', array(
//             'id' => 'na-'.$wid,
//             'onclick' => $ajax
//         ));
	}
	?>
	<? 
	echo CHtml::openTag('label', array('for' => 'nr-'.$wid));
	echo $form->checkBox($this->notifyUser, 'notify_reply', array(
        'id' => 'nr-'.$wid,
        'onclick' => $ajax
    ));
	echo $this->notifyUser->getAttributeLabel('notify_reply');
	echo CHtml::closeTag('label');

// 	echo $form->checkBoxRow($this->notifyUser, 'notify_reply', array(
//         'id' => 'nr-'.$wid,
//         'onclick' => $ajax
//     )); 
	?>
	<br><br>
	
	<?
	NotifySubscription::$commentableType = $this->commentableType; // respect dynamic relation
	$itemCount = $this->subProvider->getTotalItemCount();
	?>
	<? if ($itemCount): ?>
		<h3><?=YCommentsModule::t('Item subscriptions')?></h3>
		<? 
		$this->widget('zii.widgets.CListView', 
			array(
				'id' => 'subs-items-'.$this->commentableType,
				'dataProvider' => $this->subProvider, 
				'itemView' => '_notifySubItem',
				'emptyText' => YCommentsModule::t('no subscriptions'),
				'template' => "<table> {items}\n </table> {pager}\n{summary}",
			));
		?>
	<? endif; ?> 
</div>

<?
$this->endWidget();
echo CHtml::closeTag('div'); 
?>
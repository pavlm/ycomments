<?php
/* @var $this CommentsWidget */
/* @var $data Comment */
/* @var $printComments Closure */
/* @var $level int */
$behavior = $this->getCommentableBehavior();
$comment = isset($data) ? $data : $this->comment; // can be called from CListView or from CommentsWidget
$user = $comment->user;
$level = isset($level) ? $level : $comment->getLevel();
$isGuest = Yii::app()->user->isGuest;
?>
<div class="comment" id="comment-<?php echo $comment->id; ?>">

	<div class="comment__avatar-cell">
		<? echo $user ? $user->getGravatarImg() : '' ?>
	</div>

	<div class="comment__content-cell">
		<div class="comment__info">
			<? if (!$comment->parent_id): // rating only for top reviews ?>
			<?
			$this->widget('CStarRating',
					array('value' => $comment->rating, 'name' => "user_rating_{$comment->id}", 'minRating' => 1, 'maxRating' => 5, 'readOnly' => true,
							'htmlOptions' => array('class' => 'user-rating') ));
			?>
			<? endif; ?>
		
			<span class="comment__author-name"><?php echo CHtml::encode($comment->userName); ?></span>
			<span class="comment__date">
				<?php
				$dt = date_create($comment->created_at);
				if ($dt && $dt->format('Y') < date('Y')) 
					echo Yii::app()->dateFormatter->format( "HH:mm dd MMMM yyyy", $comment->created_at );
				else 
					echo Yii::app()->dateFormatter->format( "HH:mm dd MMMM", $comment->created_at );
				?>
			</span>
			
			<?php if ($comment->checkAccess('comment-admin') || $comment->checkAccess('comment-author')): ?>
			<span class="comment__menu">
			<? 
			///////// edit & delete links
	
			echo CHtml::link('редактировать...', '#',
					array('class' => 'comment__menu__item comment-cmd', 'data-comment' => json_encode(array('cmd' => 'update', 'cid' => $comment->id), JSON_NUMERIC_CHECK) ) );

			echo CHtml::link('удалить...', '#',
					array('class' => 'comment__menu__item comment-cmd', 'data-comment' => json_encode(array('cmd' => 'delete', 'cid' => $comment->id), JSON_NUMERIC_CHECK) ) );
			?>
			</span>
			<? endif; ?>

			<? 
			if ($behavior->showAnchors) {
				echo CHtml::link('#', $comment->getUrlData(), array('id' => 'c'.$comment->id, 'class' => 'comment__anchor comment__info__link'));
			}
			?>
			
		</div>
	
		<div class="comment__message">
			<?php echo $comment->messageProcessed; ?>
		</div>
		
		<div class="comment__commands">

			<? if ($behavior->allowVotes): ?>
			
				<? if ($comment->checkAccess('commentator')): ?>
				<?=CHtml::link($comment->userLike ? 'Не нравится' : 'Нравится', "#",
						array('class' => "comment-like comment-cmd ", 'data-comment' => json_encode(array('cmd' => 'like', 'cid' => $comment->id), JSON_NUMERIC_CHECK), 'rel' => 'nofollow' ) ); ?>
				<? endif; ?>
				
			<? endif; ?>
		
			<? if ($behavior->allowReply && $comment->checkAccess('commentator')): ?>
			
				<? $levelAllowed = ($behavior->maxReplyLevel && ($level > $behavior->maxReplyLevel)) ? false : true; // ответы могут быть разрешены не для всех уровней ?>
				<? if (!$this->readOnly && $levelAllowed): ?>
				<? echo CHtml::link('Ответить', "#",
							array('class' => "comment__button comment__button-reply comment-cmd ", 'data-comment' => json_encode(array('cmd' => 'reply', 'cid' => $comment->id), JSON_NUMERIC_CHECK) ) ); ?>
				<? endif; ?>
			
			<? endif; ?>
			
			<? if ($behavior->allowVotes): ?>
				
				<span class="comment-likes" data-likes="<?=$comment->votes_up?>"><?=$comment->votes_up?></span>
			
			<? endif; ?>
			
		</div>
				
	</div>
	
</div>

<? $childsClass = ($behavior->maxReplyLevel && $level > $behavior->maxReplyLevel) ? 'comment__childs-overmax' : ''; ?>
<div class="comment__childs <?=$childsClass?>" id="comment__childs-<?php echo $comment->id; ?>">
	<? 
	if (isset($printComments)) 
		$printComments($comment->childs, $level+1); // recursive comments 
	?>
</div>
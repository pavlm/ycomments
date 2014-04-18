<?php

class ReviewLike extends CommentLike
{
	
	public function tableName()
	{
		return 'review_user_like';
	}
	
}
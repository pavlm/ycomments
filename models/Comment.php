<?php

/**
 * This is the model class for table "comments".
 *
 * @property-read YCommentsModule $module the comment module
 * @property string $type this is set to one of the commentableTypes scope from YCommentsModule
 * @property mixed  $key the primary key of the AR this comment belongs to
 *
 * The followings are the available columns in table 'comments':
 * @property integer $id
 * @property string  $message
 * @property integer $user_id
 * @property string $created_at
 * @property string $updated_at
 * @property integer $parent_id
 * @property integer $votes_up
 * @property integer $votes_dn
 * @property integer $rating
 *
 * The followings are the available model relations:
 * @property Comment $parent 
 * @property CommentUser $user
 *
 */
class Comment extends CActiveRecord
{
	private $_type;
	private $_key;
	private $_new = false;
	private $commentable; // cached host object
	public $level;
	public $childs = array();
	public static $commentableType;
	public static $commentLikeType = 'CommentLike';
	
	public function init()
	{
		$this->user_id = Yii::app()->user->id; // default user
		parent::init();
	}

	/**
	 * @var mixed set the primary key of the commentable AR this comment belongs to
	 */
	public function setKey($key)
	{
		$this->_key = $key;
	}

	/**
	 * @return mixed the primary key of the commentable AR this comment belongs to
	 */
	public function getKey()
	{
		return $this->_key;
	}

	/**
	 * Returns the static model of the specified AR class.
	 * @return Comment the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return YCommentsModule the comment module instance
	 */
	public function getModule()
	{
		return Yii::app()->getModule('ycomments');
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'comment';
	}

	public function behaviors()
	{
		return array(
			'CTimestampBehavior' => array(
				'class' => 'zii.behaviors.CTimestampBehavior',
				'createAttribute' => 'created_at',
				'updateAttribute' => 'updated_at',
			),
		);
	}
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('message', 'required'),
			array('message', 'length', 'max'=>2048),
			array('message','filter','filter'=>array($this,'htmlFilter')),
			array('parent_id', 'numerical', 'integerOnly'=>true),
			array('user_id', 'numerical', 'integerOnly'=>true, 'allowEmpty' => true),
			array('type', 'validateType', 'on'=>'create'),
			array('key',  'validateKey',  'on'=>'create'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, message, user_id, parent_id', 'safe', 'on'=>'search'),
			array('votes_up, votes_dn', 'unsafe'), // no mass assignment
		);
	}
	
	public function htmlFilter($value) {
		$p = new CHtmlPurifier();
		$p->setOptions(array('HTML.Allowed' => ''));
		return $p->purify($value);
	}

	public function validateType()
	{
		if (!(in_array(self::$commentableType, $this->module->commentableTypes))) {
			throw new CException('commentable type ' . self::$commentableType . ' not defined in YCommentsModule!');
		}
		if (!$commentable = $this->getCommentableBehavior())
			throw new CException('no commentable behavior attached');
		$comment = Yii::createComponent($commentable->commentType);
		if (get_class($comment) != get_class($this))
			throw new CException('wrong commentable type for this comment type');
	}
	
	public function validateKey()
	{
		// todo: refactor
		$commentableModel = CActiveRecord::model($this->module->commentableTypes[$this->type]);
		if ($commentableModel->asa('commentable') === null) {
			throw new CException('commentable Model must have behavior CommentableBehavior attached!');
		}
		if ($commentableModel->findByPk($this->key) === null) {
			throw new CException('comment related record does not exist!');
		}
	}
	
	/**
	 * @return CommentableBehavior
	 */
	public function getCommentableBehavior()
	{
		$model = new self::$commentableType();
		$behavior = $model->asa('commentable');
		return $behavior;
	}
	
	public static $_mdByType = array();
	
	/**
	 * respect dynamic relation depending on commentableType
	 * @see CActiveRecord::getMetaData()
	 */
	public function getMetaData()
	{
		if (!self::$commentableType)
			return parent::getMetaData();
		
		if (isset(self::$_mdByType[self::$commentableType]))
			return self::$_mdByType[self::$commentableType];
		if (!$md = parent::getMetaData())
			return null;
		$md->relations = array();
		foreach($this->relations() as $name=>$config)
		{
			$md->addRelation($name,$config);
		}
		self::$_mdByType[self::$commentableType] = $md;
		return $md;
	}
	
	public function relations()
	{
		$rels = array(
			'parent' => array(self::BELONGS_TO, 'Comment', 'parent_id'),
			'user' => array(self::BELONGS_TO, 'CommentUser', 'user_id'),
			'userLike' => array(self::HAS_ONE, self::$commentLikeType, 'comment_id', 'on' => 'userLike.user_id='.intval(Yii::app()->user->id)), // показывает наличие лайка для текущего пользователя
		);
		if (self::$commentableType)
		{
			// dynamic relation with commentable AR
			$behavior = $this->getCommentableBehavior();
			$rels['items'] = array(self::MANY_MANY, self::$commentableType, sprintf("%s(%s, %s)", $behavior->mapTable, $behavior->mapCommentColumn, $behavior->mapRelatedColumn), 'together' => false);
			Commentable::$commentableType = self::$commentableType;
			$rels['commentableItems'] = array(self::MANY_MANY, 'Commentable', sprintf("%s(%s, %s)", $behavior->mapTable, $behavior->mapCommentColumn, $behavior->mapRelatedColumn), 'together' => false);
		}
		return $rels;
	}

	/**
	 * gets linked item
	 * @return CActiveRecord
	 */
	public function getItem() 
	{
		if (!$this->hasRelated('items'))
			return false;
		$items = $this->items;
		return reset($items);
	}

	/**
	 * gets linked item
	 * @return Commentable
	 */
	public function getCommentableItem()
	{
		if (!$this->hasRelated('commentableItems'))
			return false;
		$items = $this->commentableItems;
		return reset($items);
	}
	
	protected function beforeSave()
	{
		$this->_new = $this->isNewRecord;
		return parent::beforeSave();
	}

	protected function afterSave()
	{
		// todo: review
		if ($this->_new) {
			if (!$this->key) {
				parent::afterSave();
				return;
			}
			$commentableModel = CActiveRecord::model(self::$commentableType);
			// if comment is new, connect it with commented model
			$this->getDbConnection()->createCommand(
				"INSERT INTO ".$commentableModel->mapTable."(".$commentableModel->mapCommentColumn.", ".$commentableModel->mapRelatedColumn.")
				 VALUES (:id, :key);"
			)->execute(array(':id' => $this->id, ':key' => $this->key));

			parent::afterSave();

			// raise new comment event
			$this->module->onNewComment($this, $commentableModel->findByPk($this->key));
		} else {
			parent::afterSave();
		}
		$commentable = $this->findCommentableModel();
		// raise update comment event
		$this->module->onUpdateComment($this, $commentable);
	}

	protected function beforeDelete()
	{
		$this->commentable = $this->findCommentableModel();
		return parent::beforeDelete();
	}
	
	protected function afterDelete()
	{
		parent::afterDelete();
		// raise update comment event
		$this->module->onDeleteComment($this, $this->commentable);
	}

	/**
	 * @return string get comment users name
	 */
	public function getUserName()
	{
		return is_null($this->user) ? 'Guest' : $this->user->getUName();
	}

	/**
	 * @return string get comment users email
	 */
	public function getUserEmail()
	{
		return is_null($this->user) ? 'nobody@example.com' : $this->user->getUEmail();
	}

	public function getUserAvatar()
	{
		// todo review
		$noAvatar = '/images/noavatar.png';
		return $noAvatar;
		if (!$this->user) return $noAvatar;
		if (!$img = $this->user->getImageUrl()) {
			return $noAvatar;
		} else {
			if (!file_exists(Yii::getPathOfAlias('webroot').$img))
				return $noAvatar;
		}
		return $img;
	}
	
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'message' => YCommentsModule::t('Comment'),
			'user_id' => YCommentsModule::t('User ID'),
			'userName' => YCommentsModule::t('Name'),
			'userEmail' => YCommentsModule::t('E-Mail'),
			'created_at' => YCommentsModule::t('Created'),
			'updated_at' => YCommentsModule::t('Updated'),
		);
	}

	/**
	 * ищет объект содержащий данный коммент через тип и связанную таблицу
	 * @return CActiveRecord
	 */
	public function findCommentableModel()
	{
		// todo: rewrite function
		if (!self::$commentableType)
			return null;
		if (!$commentable = Yii::createComponent(self::$commentableType))
			return null;
		/* @var $commentable CommentableBehavior */
		if (!$commentable->commentable)
			return null;
		$columnHost = $commentable->mapRelatedColumn;
		$columnThis = $commentable->mapCommentColumn;
		$q = $this->dbConnection->createCommand("select * from {$commentable->mapTable} where {$columnThis}={$this->id}");
		if (!$link = $q->queryRow())
			return null;
		if (!$commentableId = $link[$columnHost])
			return null;
		return $commentable->findByPk($commentableId);
	}
	
	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('message',$this->message,true);
		$criteria->compare('user_id',$this->user_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	
	public function getLevel() 
	{
		if (!$this->parent_id) return 1;
		if (!$pcomment = self::model()->findByPk($this->parent_id))
			return 1;
		return 1 + $pcomment->getLevel();
	}
	
	public function getMessageProcessed()
	{
		$root = Yii::getPathOfAlias('webroot');
		$msg = nl2br($this->message);
		return $msg;
	}

	public function isUserAuthor()
	{
		return Yii::app()->user->id == $this->user_id;
	}
	
	/**
	 * Checks access for this comment
	 * @param string $itemName
	 * @param array $params
	 * @return bool
	 */
	public function checkAccess($itemName, $params=array())
	{
		$params['comment'] = $this;
		return Yii::app()->user->checkAccess($itemName, $params);
	}

	/**
	 * длина поля message из правила
	 * @return number
	 */
	public function getMessageRuleLength()
	{
		$vs = $this->getValidators('message');
		if (empty($vs)) return 512;
		foreach ($vs as $v) {
			if (!($v instanceof CStringValidator)) continue;
			if ($v->max) return $v->max;
		}
		return 512;
	}
	
	/**
	 * url to comment data
	 * @return array
	 */
	public function getUrlData()
	{
		$b = $this->getCommentableBehavior();
		if ($urlFunc = $b->commentableUrl) {
			$commentable = @$this->items[0];
			$data = $urlFunc($commentable);
		} else {
			$route = Yii::app()->controller->id.'/'.Yii::app()->controller->action->id;
			$data = array_merge(array($route), $_GET);
		}
		$data['#'] = 'c'.$this->id;
		return $data;
	}
}

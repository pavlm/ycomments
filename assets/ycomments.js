;(function ( $, window, document, undefined ) {

    var defaultOpts = {
    }
    	
    // The actual plugin constructor
    function Ycomments( element, opts ) {
        this.element = element;
        var $el = $(element);
        this.$el = $el;
        this.$items = $el; //$el.find('div.items');
        var sets = JSON.parse($el.attr('data-comments-sets'));
        this.opts = $.extend( {}, defaultOpts, sets, opts) ;
        this._name = 'ycomments';
        this.init();
    }

    Ycomments.prototype = {

    	hidden : {},
    	opened : [],
    		
        init: function() {
        	this.opts.urlCreate = this.opts.baseUrl + 'create';
        	this.opts.urlUpdate = this.opts.baseUrl + 'update';
        	this.opts.urlDelete = this.opts.baseUrl + 'delete';
        	var self = this;
        	this.$el.on('click', 'a.comment-cmd', function(e){
        		var data = JSON.parse($(this).attr('data-comment'));
        		self[data.cmd](this, data);
        		e.preventDefault();
        	});
        	this.$el.find('.comment-send').attr('disabled', false).on('click', function(){
        	});
        	if (this.opts.charCounter) {
        		this.$el.on('focus', 'textarea', function(e){ 
        			$(this).closest('form').addClass('textarea-focus');
        			self.textareaStats(e, this);
        			$(this).attr('maxlength', self.opts.messageLength); });
        		this.$el.on('blur', 'textarea', function(e){ 
        			$(this).closest('form').removeClass('textarea-focus'); });
        		this.$el.on('keyup', 'textarea', function(e){ self.textareaStats(e, this); });
        	}
        	this.$el.on('keydown', function(e){ self.onkeydown(e, this); });
        },
        
        getBaseQueryData : function(data) {
        	var qd = { 
        			id:data.cid ? data.cid : 0, 
        			commentType:this.opts.commentType, 
        			commentableType:this.opts.commentableType,
        			key:this.opts.key,
        			};
        	if (this.opts.csrfToken) {
        		qd[this.opts.csrfTokenName] = this.opts.csrfToken;
        	}
        	return qd;
        },
        
        post : function(button, data) {
        	if (this.posted) return;
        	var commblock = $(button).closest('.comment');
        	var $form = $(button).attr('disabled', true).closest('form');
        	var fdata = {};
        	$form.serializeArray().forEach(function(pair){ return fdata[pair.name] = pair.value; })
        	var query = $.extend(this.getBaseQueryData(data), fdata);
        	this.posted = true;
        	$.ajax({ 
        		type:'post',
        		url: data.cid ? this.opts.urlUpdate : this.opts.urlCreate, 
        		data:query, 
        		success:function(result){
        			if (data.cid) { // update
        				this.replaceItem(data.cid, result);
        	        	delete this.hidden[data.cid];
        			} else { // new
        				if (this.opts.appendComment) {
        					if (data.parent_id || result.indexOf('errorSummary') != -1) {
            					this.replaceItem(commblock, result);
        					} else {
        						commblock.remove();
            					this.appendItem(result);
        					}
        				} else {
        					this.replaceItem(commblock, result);
        				}
        			}
        			var hasErrors = result.match('errorSummary') ? true : false;
    				this.$el.trigger('comment.posted', [data.cid, hasErrors]);
    				this.clearEmptyText();
        		}.bind(this),
        		complete:function(){
        			this.posted = false;
        		}.bind(this)
        	});
        },
        
        update : function(a, data) {
        	this.closeOpened();
        	var query = this.getBaseQueryData(data);
        	$.ajax({ 
        		url:this.opts.urlUpdate, 
        		data:query, 
        		success:function(result) {
        			var c = this.getItem(data.cid);
        			this.hidden[data.cid] = c;
        			this.replaceItem(data.cid, result);
    				this.$el.trigger('comment.updated', data.cid);
        		}.bind(this)
        	});
        },
        
        'delete' : function(a, data) {
        	if (!confirm('Удалить комментарий?')) return;
        	var query = this.getBaseQueryData(data);
        	$.ajax({ 
        		type:'post',
        		url:this.opts.urlDelete, 
        		data:query, 
        		success:function(result) {
        			this.replaceItem(data.cid, null);
    				this.$el.trigger('comment.deleted', data.cid);
        		}.bind(this),
        		error:function(xhr, status) {
        			alert('Ошибка удаления');
        		}
        	});
        },
        
        close : function(a, data) {
        	var commblock = this.getItem(a);
        	this.replaceItem(commblock, this.hidden[data.cid]);
        	delete this.hidden[data.cid];
        },
        
        closeOpened : function() {
        	$.each(this.hidden, function(cid, c){
        		cid = parseInt(cid, 10);
        		this.close(cid, {cid:cid, cmd:'close'});
        	}.bind(this));
        	$.each(this.opened, function(i, $e){ $e.remove(); });
        	this.hidden = {};
        	this.opened = [];
        },
        
        reply : function(button, data) {
        	this.closeOpened();
        	var commblock = $(button).closest('.comment');
        	var commChildsBlock = $('#comment__childs-'+data.cid);
        	var query = this.getBaseQueryData(data);
        	$.ajax({ 
        		url:this.opts.urlCreate, 
        		data:query, 
        		success:function(result) {
        			var $h = this.opts.tree ?
        					this.appendItem(result, commChildsBlock) :
        					this.insertAfter(commblock, result);
        			this.opened.push($h);
    				this.$el.trigger('comment.reply', data.cid);
        		}.bind(this)
        	});
        },

        like : function(button, data) {
        	
        	$.ajax({ 
        		url: this.opts.baseUrl + 'like', 
        		data: {id:data.cid, commentableType:this.opts.commentableType},
        		type:'post',
        		dataType:'json',
        		success:function(data) {
        			var $likes = $(button).closest('.comment-commands').find('.comment-likes');
        			$likes.text(data.likes).attr('data-likes', data.likes);
        			var $link = $(button).closest('.comment-commands').find('.comment-like');
        			$link.text(data.dir==1 ? 'Не нравится' : 'Нравится');
        		}.bind(this)
        	});
        	
        },

        voteup : function(button, data) { this.vote(button, data); },
        votedn : function(button, data) { this.vote(button, data); },
        vote : function(button, data) {
        	
        	$.ajax({ 
        		url:'/news/ratingcomments', 
        		data:{id_com:data.cid, rating:(data.cmd == 'voteup' ? 0 : 1)},
        		dataType:'json',
        		success:function(data) {
        			if (!data.res) return;
        			var $ud = $(button).closest('.up-down');
        			$ud.find('.comment_up').text(data.votes_up>0 ? '+'+data.votes_up : data.votes_up);
        			$ud.find('.comment_down').text(data.votes_dn>0 ? '-'+data.votes_dn : data.votes_dn);
        		}.bind(this)
        	});
        	
        },
        
        getItem : function(idOrElem) {
        	if (typeof idOrElem == 'object') 
        		return $(idOrElem).closest('.comment');
        	else
        		return $('#comment-'+idOrElem);
        },
        
        replaceItem : function(item, html) {
        	if (typeof item == 'number')
        		item = $('#comment-'+item);
        	if (html == null)
        		item.slideUp(function(){ $(this).remove(); });
        	else {
    			var $h = $(html).hide();
    			$h.insertAfter(item);
        		item.fadeOut('fast', function(){
        			item.remove();
        			$h.show();
        			$h.find('textarea').focus();
            		//$h.find('textarea.stretch-height').stretchTextarea();
        		});
        	}
        },
        
        insertAfter : function(item, html) {
    		var $h = $(html).hide(); 
			$($h).insertAfter(item);
			try {
	    		$h.slideDown(function(){
	    			$h.find('textarea').focus();
	    		});
			} catch(e) {}
    		//$h.find('textarea.stretch-height').stretchTextarea();
    		return $h;
        },
        
        appendItem : function(html, container) {
    		var $h = $(html);
    		if (!container) {
	    		var $l = this.$items.children(':last');
	    		if (!$l.hasClass('form'))
	    			this.$items.append($h); // последним элементом
	    		else
	    			$h.insertBefore($l); // перед формой коммента
    		} else {
    			$h.appendTo(container);
    		}
			$h.find('textarea').focus();
    		$("body,html").animate({scrollTop: $h.position()['top']});
    		this.$items.children('.comments-empty').remove();
    		//$h.find('textarea.stretch-height').stretchTextarea();
    		return $h;
        },
        
        setFormFocus : function() {
        	$form = this.getItem(0);
        	if (!$form.length) return;
    		$("body,html").animate({scrollTop: $form.position()['top']});
    		$form.find('textarea').focus();
        },
        
        textareaStats : function(e, ta) {
        	var $ta = $(ta);
        	var text = $ta.val();
        	var len = text.length + (text.match(/\n/g)||[]).length;
        	$ta.closest('form').find('.comment__form__stat').text( 
        			Math.max(this.opts.messageLength - len, 0) );
        },
        
        addLink : function(a, data) {
        	this.dialog(a, 'Добавление ссылки', 
        		'<div> <div class="comment-dialog-row"> <label> Адрес страницы: </label> <input name="url"> </div> </div>', 
        		function(data){
        			if (!data.url) return;
        			var $c = this.getItem(a);
        			var $t = $c.find('textarea');
        			$t.val($t.val() + "[url]"+data.url+"[/url]");
        		}.bind(this));
        },
        
        addImage : function(a, data) {
        	this.dialog(a, 'Добавление изображения', 
        		'<div> <div class="comment-dialog-row"> <label> Адрес изображения: </label> <input name="url"> </div> </div>', 
        		function(data){
	    			if (!data.url) return;
	    			var $c = this.getItem(a);
	    			var $t = $c.find('textarea');
	    			$t.val($t.val() + "[img]"+data.url+"[/img]");
    			}.bind(this));
        },
        
        addVideo : function(a, data) {
        	this.dialog(a, 'Добавление видео', 
        		'<div> <div class="comment-dialog-row"> <label> Адрес страницы с видео на youtube: </label> <input name="url"> </div> </div>', 
        		function(data){
	    			if (!data.url) return;
	    			var $c = this.getItem(a);
	    			var $t = $c.find('textarea');
	    			$t.val($t.val() + "[video]"+data.url+"[/video]");
        		}.bind(this));
        },
        
        dialog : function(anchor, title, content, callbackData) {
        	$content = $(content).appendTo('body').attr('title', title);
        	$content.dialog({
        		modal:true, dialogClass:'svs comment-dialog', 
        		buttons:{ 
        			'OK': function() {
        				var $dlg = $(this);
        				var dataString = $dlg.find('input, textarea').serialize();
        				var data = $.deparam(dataString);
        				$dlg.dialog("destroy").remove();
        				if (callbackData)
        					callbackData(data);
        			} 
        		}
        	});
        },
        
        onkeydown : function(e) {
        	if (e.ctrlKey && e.keyCode == 13 && e.target && e.target.tagName.toLowerCase() == 'textarea')
        	{
        		e.stopPropagation();
        		$(e.target).closest('form').find('.comment__button-post').click();
        	}
        },
        
        clearEmptyText : function() {
        	this.$el.find('.items .empty').remove();
        }

    };

    $.fn['ycomments'] = function ( options ) {
        return this.each(function () {
        	var cmnts = $.data(this, "ycomments"); 
            if (!cmnts) {
                $.data(this, "ycomments",
                new Ycomments( this, options ));
            } else {
            	if (typeof options == 'string') {
            		cmnts[options]();
            	}
            }
        });
    };

})( jQuery, window, document );

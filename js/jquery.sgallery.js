/*
 *	sGallery 1.0 - simple gallery with jQuery
 *	made by bujichong 2009-11-25
 *	作者：不羁虫  2009-11-25
 * http://hi.baidu.com/bujichong/
 *	欢迎交流转载，但请尊重作者劳动成果，标明插件来源及作者
 */

(function ($) {
	$.fn.sGallery = function (o) {
		return  new $sG(this, o);
    };

	var settings = {
		thumbObj:null,//预览对象
		titleObj:null,//标题
		thumbNowClass:'now',//预览对象当前的class,默认为now
		slideTime:500,//平滑过渡时间
		autoChange:true,//是否自动切换
		changeTime:3000//自动切换时间
	};

	 $.sGalleryLong = function(e, o) {
		this.options = $.extend({}, settings, o || {});
		var _self = $(e);
		var set = this.options;
		var thumb;
		var size = _self.size();
		var nowIndex = 0; //定义全局指针
		var index;//定义全局指针
		var startRun;//预定义自动运行参数
		var delayRun;//预定义延迟运行参数

		//初始化
		$(set.titleObj).hide().eq(0).show();
		//主切换函数
		function fadeAB () {
			if (nowIndex != index) {
				if (set.thumbObj!=null) {
					$(set.thumbObj).removeClass().eq(index).addClass(set.thumbNowClass);
				}
				_self.parent().stop(false,true).animate({left:'-=196px'},set.slideTime,function(){
					_self.eq(nowIndex).appendTo(_self.parent());
					_self.parent().css({left:0});
					nowIndex = index;
				});
				$(set.titleObj).eq(nowIndex).stop(true,true).fadeOut(set.slideTime);//新增加title
				$(set.titleObj).eq(index).stop(true,true).fadeIn(set.slideTime);//新增加title
				
				if (set.autoChange==true) {
					clearInterval(startRun);//重置自动切换函数
					startRun = setInterval(runNext,set.changeTime);
				}
			}
		}

		//切换到下一个
		function runNext() {
			index =  (nowIndex+1)%size;
			fadeAB();
		}


		//自动运行
		if (set.autoChange==true) {
		startRun = setInterval(runNext,set.changeTime);
		}

	}

	var $sG = $.sGalleryLong;
})(jQuery);

function slide(Name,Title,Class,Width){
	$(Name).append('<div class="change"></div>');
	var atr = $(Name+' div.changeDiv');
	var sum = atr.children().length;
	atr.width(sum*Width);
	for(i=1;i<=sum;i++){
		$(Name+' .change').append('<i></i>');
	}
	$(Name+' .change i').eq(0).addClass('cur');
	$(Name+' div.changeDiv').children().sGallery({//对象指向层，层内包含图片及标题
		titleObj:Title,
		thumbObj:Name+' .change i',
		thumbNowClass:Class
	});
}
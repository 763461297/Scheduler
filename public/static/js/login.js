/**
 * 
 */
function showErrorAlert(title, content) {
	$("#alert-area").html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><strong>' + title + '</strong>' + content + '</div>');
}
function setWaiting(btn, wat) {
	$(btn).addClass('disabled');
	$(btn).text(wat);
}
function setReset(btn, ret) {
	$(btn).removeClass('disabled');
	$(btn).text(ret);
}
$("#btn-login").click(function() {
	var nec = $(this).parents("form").find(".has-feedback");
	var isok = true;
	nec.each(function() {
		var ipt = $(this).find("input,select");
		if(ipt.val() == null || ipt.val() == '') {
			ipt.after('<span class="glyphicon glyphicon-remove form-control-feedback"></span>');
			$(this).addClass("has-error");
			if(isok) {
				ipt.focus();
			}
			isok = false;
		}
	});
	if(isok) {
		var fields = $(this).parents("form").find("[name]");
		var _url = $(this).parents("form").attr('action');
		var _data = "";
		var btn = $(this);
		fields.each(function() {
			if(_data != "") {
				_data = _data + "&"
			}
			_data = _data + $(this).attr("name").trim() + "=" + $(this).val().trim();
		});
		setWaiting($(this), '正在登录…');
		$.ajax({
			url: _url,
			type: "POST",
			data: _data,
			success: function(data) {
				if(data.success) {
					setReset(btn, '登录');
					// login success
					window.location.href = data.url;
				} else {
					setReset(btn, '登录');
					showErrorAlert('登录失败！', data.msg);
					$("#inputCaptcha").parent().next().find("img").click();
					$("input[name='captcha']").val('');
					if(data.code != -123) {						
						$("input[name='password']").val('').focus();
					}
				}
			},
			error: function() {
				setReset(btn, '登录');
				showErrorAlert('网络错误！', '请检查网络连接');
				$("#inputCaptcha").parent().next().find("img").click();
				$("input[name='captcha']").val('');
			}
		});
	}
});
$("input[name='user']").keypress(function(event) {
	var keycode = (event.keyCode ? event.keyCode : event.which);  
    if(keycode == '13'){  
    	$("input[name='password']").focus();  
    }
});

$("input[name='password']").keypress(function(event) {
	var keycode = (event.keyCode ? event.keyCode : event.which);  
    if(keycode == '13'){  
    	$("input[name='captcha']").focus();
    }
});

$("input[name='captcha']").keypress(function() {
	var keycode = (event.keyCode ? event.keyCode : event.which);  
    if(keycode == '13'){  
    	$("#btn-login").click();
    }
});

$("#inputCaptcha").parent().next().find("img").click(function() {
	var url = $(this).attr('src');
	$(this).attr('src', url);
});
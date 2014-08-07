function changeCondition(){
	var a = $("#field option:selected").attr('class');
	var b = $("#field option:selected").val();
	var c = $("#field option:selected").attr('rel');

	if(a == 'number') {
		$("#conditionContent").html('<select id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="gt">  大于  </option>'
							+'<option value="lt">  小于  </option>'
							+'<option value="eq">  等于  </option>'
							+'<option value="neq">  不等于  </option>'
							+'</select>&nbsp;&nbsp; ');
		$("#searchContent").html('<input id="search" type="text" class="input-medium search-query" name="search"/>&nbsp;&nbsp;');
	} else if ((a == 'word') || (a == 'text') || (a == 'textarea') || (a == 'editor')) {
		$("#conditionContent").html('<select id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="contains">包含</option>'
							+'<option value="not_contain">不包含</option>'
							+'<option value="is">是</option>'
							+'<option value="isnot">不是</option>'							
							+'<option value="start_with">开始字符</option>'
							+'<option value="end_with">结束字符</option>'
							+'<option value="is_empty">为空</option>'
							+'<option value="is_not_empty">不为空</option></select>&nbsp;&nbsp;');
		$("#searchContent").html('<input id="search" type="text" class="input-medium search-query" name="search"/>&nbsp;&nbsp;');
	} else if (a == 'date' || a== 'datetime') {
		$("#conditionContent").html('<select id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="tgt">  晚于  </option>'
							+'<option value="lt">  早于  </option>'
							+'<option value="between">  在  </option>'
							+'<option value="nbetween">  不在  </option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('<input id="search" type="text" class="input-medium search-query" name="search" onclick="WdatePicker()"/>&nbsp;&nbsp;');
	} else if (a == 'bool') {
		$("#conditionContent").html('<select id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="1">是</option>'
							+'<option value="0">否</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('<input id="search" type="text" class="input-medium search-query" name="search"/>&nbsp;&nbsp;');
	} else if (a == 'sex') {
		$("#searchContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="1">男</option>'
							+'<option value="0">女</option>'
							+'</select>&nbsp;&nbsp;');
		$("#conditionContent").html('');
	} else if (a == 'saltname') {
		$("#searchContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="女士">女士</option>'
							+'<option value="先生">先生</option>'
							+'<option value="老师">老师</option>'
							+'<option value="医生">医生</option>'
							+'<option value="博士">博士</option>'
							+'<option value="教授">教授</option>'
							+'</select>&nbsp;&nbsp;');
		$("#conditionContent").html('');
	} else if (a == 'source') {
		$.ajax({
			type:'get',
			url:'index.php?m=setting&a=getsourcelist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.source_id+'">'+v.name+'</option>';
				});

				$("#searchContent").html('<select id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});	
	} else if (a == 'leads_status') {
		$("#searchContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="试图联系">试图联系</option>'
							+'<option value="将来联系">将来联系</option>'
							+'<option value="已联系">已联系</option>'
							+'<option value="虚假线索">虚假线索</option>'
							+'<option value="丢失线索">丢失线索</option>'
							+'</select>&nbsp;&nbsp;');
		$("#conditionContent").html('');
	} else if (a == 'industry') {
		$.ajax({
			type:'get',
			url:'index.php?m=setting&a=getindustrylist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.industry_id+'">'+v.name+'</option>';
				});

				$("#searchContent").html('<select id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});	
	} else if (a == 'leads_rating') {
		$("#searchContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="五星">五星</option>'
							+'<option value="四星">四星</option>'
							+'<option value="三星">三星</option>'
							+'<option value="二星">二星</option>'
							+'<option value="一星">一星</option>'
							+'</select>&nbsp;&nbsp;');
		$("#conditionContent").html('');
	} else if (a == 'role') {
		$.ajax({
			type:'get',
			url:'index.php?m=user&a=getrolelist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.role_id+'">'+v.user_name+' ['+v.department_name+'-'+v.role_name+']</option>';
				});
				$("#searchContent").html('<select id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	} else if (a == 'business_status') {
		$.ajax({
			type:'get',
			url:'index.php?m=setting&a=getbusinessstatuslist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.status_id+'">'+v.name+'</option>';
				});

				$("#searchContent").html('<select id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	}  else if (a == 'leads_status') {
		$.ajax({
			type:'get',
			url:'index.php?m=setting&a=getleadsstatuslist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.status_id+'">'+v.name+'</option>';
				});

				$("#searchContent").html('<select id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	} else if (a == 'customer') {
		$.ajax({
			type:'get',
			url:'index.php?m=customer&a=getcustomerlist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.customer_id+'">'+v.name+'</option>';
				});
				$("#searchContent").html('<select id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	}else if (a == 'contacts') {
		$.ajax({
			type:'get',
			url:'index.php?m=contacts&a=getcontactslist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.contacts_id+'">'+v.name+'</option>';
				});
				$("#searchContent").html('<select id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	} else if (a == 'contract') {
		$.ajax({
			type:'get',
			url:'index.php?m=contract&a=getcontractlist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.contract_id+'">'+v.number+'--'+v.customer_name+'</option>';
				});
				$("#searchContent").html('<select id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	} else if(a=='all') {
		$("#conditionContent").html('<select id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="contains">包含</option>'
							+'<option value="is">是</option>'
							+'<option value="start_with">开始字符</option>'
							+'<option value="end_with">结束字符</option>'
							+'<option value="is_empty">为空</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('<input id="search" type="text" class="input-medium search-query" name="search"/>&nbsp;&nbsp;');
	} else if (a == 'task_status') {
		$("#conditionContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="未启动">未启动</option>'
							+'<option value="推迟">推迟</option>'
							+'<option value="进行中">进行中</option>'
							+'<option value="已完成">已完成</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	} else if (a == 'task_priority') {
		$("#conditionContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="高">高</option>'
							+'<option value="普通">普通</option>'
							+'<option value="低">低</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	}else if (a == 'payables_status') {
		$("#conditionContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="0">未付款</option>'
							+'<option value="1">部分已付</option>'
							+'<option value="2">已付款</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	}else if (a == 'order_status') {
		$("#conditionContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="0">未结账</option>'
							+'<option value="1">已结账</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	} else if (a == 'receivables_status') {
		$("#conditionContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="0">未收款</option>'
							+'<option value="1">部分已收</option>'
							+'<option value="2">已收款</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	} else if (a == 'customer_ownership') {	
		$("#conditionContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="高">高</option>'
							+'<option value="无">无</option>'
							+'<option value="国有企业">国有企业</option>'
							+'<option value="外资企业">外资企业</option>'
							+'<option value="民营企业">民营企业</option>'
							+'<option value="集体企业">集体企业</option>'
							+'<option value="股份制企业">股份制企业</option>'
							+'<option value="合资企业">合资企业</option>'
							+'<option value="独资企业">独资企业</option>'
							+'<option value="其他">其他</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	} else if (a == 'customer_type') {	
		$("#conditionContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="分析者">分析者</option>'
							+'<option value="竞争者">竞争者</option>'
							+'<option value="客户">客户</option>'
							+'<option value="集成商">集成商</option>'
							+'<option value="投资商">投资商</option>'
							+'<option value="合作伙伴">合作伙伴</option>'
							+'<option value="出版商">出版商</option>'
							+'<option value="目标">目标</option>'
							+'<option value="供应商">供应商</option>'
							+'<option value="其它">其它</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	}else if (a == 'box') {
		$.ajax({
			type:'get',
			url:'index.php?m=setting&a=boxfield&model='+c+'&field='+b,
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v+'">'+v+'</option>';
				});
				$("#searchContent").html('<select id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
                if(data.info == 'checkbox'){
                    $("#conditionContent").html('<input type="hidden" name="condition" value="contains">');
                }else{
                    $("#conditionContent").html('');
                }
			},
			dataType:'json'
		});		
	} else if (a == 'address') {
        $("#conditionContent").html('<select id="condition" style="width:auto" name="condition">'
							+'<option value="start_with">在</option>'
							+'<option value="not_start_with">不在</option></select>&nbsp;&nbsp;');
        $("#searchContent").html('<select name="state" id="state" style="width:auto"></select>'
							+'<select name="city" id="city" style="width:auto"></select>'
							+'<input type="text" id="search" name="search" placeholder="街道信息" class="input-large">&nbsp;&nbsp;');
        new PCAS("state","city","","");
	} 
}
function checkSearchForm() {
    search = $("#searchForm #search").val();
    field = $("#searchForm #field").val();
    if($("#searchForm #state").length>0){
        if($("#searchForm #state").val() == ''){
            alert("请选择地区");return false;
        }
    }else{
        if (search == "") {
            alert("请填写搜索内容");return false;
        }else if(field == ""){
			 alert("请选择筛选条件！");return false;
		}
    } 
    return true;
}

function changeSearch() {
	a = $("#field option:selected").attr('class');
	b = $("#condition option:selected").val();
	if(b == 'is_empty' || b == 'is_not_empty') {
		$("#searchContent").html('');
	} else {
		if(a == "date") {
			$("#searchContent").html('<input id="search" type="text" class="input-medium search-query" name="search" onclick="WdatePicker()"/>&nbsp;&nbsp;');	
		}  else if (a == "number" || a == "word" || a == "date") {
			$("#searchContent").html('<input id="search" type="text" class="input-medium search-query" name="search"/>&nbsp;&nbsp;');
		}
	}
}
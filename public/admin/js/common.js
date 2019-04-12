function selectAll(){
	//点击全选
	$("#selectAll").click(function(){
		if($(this).prop("checked")){
			//选中状态
			for(var i=0;i<$(".detailAddr").length;i++){
				$(".detailAddr").eq(i).prop("checked",true);
			}
			
		}else{
			for(var i=0;i<$(".detailAddr").length;i++){
				$(".detailAddr").eq(i).removeAttr("checked");
			}
		}
	})
}
/**
 ** 
 ** 说明：获取地址栏参数
 ** 调用：getUrlParam(this)
 ** 返回值：void
 **/
function getUrlParam(url,name) {  
     var pattern = new RegExp("[?&]"+name+"\=([^&]+)", "g");  
     var matcher = pattern.exec(url);  
     var items = null;  
     if(null != matcher){  
             try{  
                    items = decodeURIComponent(decodeURIComponent(matcher[1]));  
             }catch(e){  
                     try{  
                             items = decodeURIComponent(matcher[1]);  
                     }catch(e){  
                             items = matcher[1];  
                     }  
             }  
     }  
     return items;  
} 
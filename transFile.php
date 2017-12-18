<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="UTF-8">
	<title>文件转换</title>
	<style>
		html,body{
			margin: 0;
			padding: 0;
		}
		.wrap{
			width: 1024px;
			min-width: 1000px;
			background-color: #222;
			margin: 0 auto;
			position: relative;
		}
		.ipt_area,.btn_trans{
			width: 130px;
			height: 45px;
			line-height: 40px;
			float: left;
			color: white;
			border-radius: 10px;
			border: 2px solid white;
			box-sizing: border-box;
		}
		.btn_trans{
			margin-left: 20px;
		}
		label{
			float: left;
			width: 100%;
			height: 100%;
			font-size: 24px;
			letter-spacing: 3px;
			text-align: center;
			cursor: pointer;
		}
		.ipt_area:hover,
		.btn_trans:hover{
			color: #222;
			border: none;
			background-color: white;
		}
		input[type=file], button{
			display: none;
		}
		.inner{
			position: absolute;
			top: 50%;
			left: 50%;
			margin-left: -140px;
			margin-top: -22.5px;
			overflow: hidden;
		}
		span{
			display: block;
			margin-top: 55px;
			color: white;
		}
	</style>
</head>
<body>
	<div class="wrap">
		<div class="inner">
			<div class="ipt_area">
				<input type="file" id="myfile">
				<label for="myfile" title="请选择上传文件">选择文件</label>
			</div>
			<div class="btn_trans">
				<button id="btn_trans"></button>
				<label for="btn_trans" title="点击转换文件">转换</label>
			</div>
			<span></span>
		</div>
	</div>
	<script>
		var screenHeight = getClient().height;
        var wrap = document.querySelector(".wrap");
		wrap.style.height = screenHeight + "px";
        var my_file = document.querySelector("[type=file]");
        var tips = document.querySelector("span");
		var btn = document.querySelector(".btn_trans label");
		my_file.onchange = function(e){
			if(e.target.files[0]){
				fileName = e.target.files[0].name;
	        	tips.innerHTML = "已选择文件:" + fileName;
			}else{
	        	tips.innerHTML = "未选择文件";
			}
		}

		btn.onclick = function(){
			var xhr = getXmlHttp();
			xhr.open("get","trans_deal.php?fileName="+fileName+"&_="+Date.parse(new Date()));
			xhr.onreadystatechange = function(){
				if(xhr.readyState == 4){
					if(xhr.responseText == 1){
						tips.innerHTML = "转换成功！";
					}else if(xhr.responseText == 0){
						tips.innerHTML = "当前文件无需转换！";		
					}
				}
			}
			xhr.send(null);
		}

		function getXmlHttp(){
		    var xhr = null;
		    try{
		        xhr = new XMLHttpRequest();
		    }catch(e){
		        try{
		            xhr = new ActiveXObject("Msxml2.XMLHTTP");
		        }catch(e){
		            xhr = new ActiveXObject("Microsoft.XMLHTTP");
		        }
		    }
		    return xhr;
		}

        function getClient() {
          return {
            width: window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth || 0,
            height: window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || 0
          }
        }
	</script>
</body>
</html>
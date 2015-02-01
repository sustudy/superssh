<!doctype html>
    <!--<one line to give the program's name and a brief idea of what it does.>
    该程序可以是浏览器版的ssh。可以用手机，电脑，平板使用浏览器登陆ssh,来操作linux系统。
    Copyright (C) 2015 - 2016  苏少峰 支辉

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.-->
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<script type="text/javascript" src="./jquery-2.0.3.min.js"></script>
		<title>通用ssh</title>
		<style type="text/css">
			*{
				padding: 0px;
				margin: 0px;
			}
		</style>
		<script type="text/javascript">
			var url = "ws://115.28.59.153:9999";
			var ws = new WebSocket(url);
			ws.onopen = function(){
				console.log("success success !!");
				ws.send("i will send message to localhost");
			}
			ws.onmessage = function(e){
				var message = e.data;
				var len = 0;
				if(message == 'yes'){
					alert('连接成功');
					var remoteIp = $('#remoteIp').val();
					var remoteName = $('#remoteName').val();
					var changeRemoteIp = remoteIp.replace(/\./g, "-");
					var sshData = "------------------------------->\r\n------------------------------->\r\n------------------------------->\r\n------------------------------->\r\n------------------------------->";
					var ele = "<input id='"+changeRemoteIp+'_'+remoteName+"_data' type='hidden' value='"+sshData+"'/>";
					$("body").append(ele);
					var act = setAct() ? 'act' : '' ;
					var newSsh = '<li><a style="cursor:pointer" onclick="changeSsh(this)" class="'+act+'" id="'+changeRemoteIp+'_'+remoteName+'">'+remoteIp+'_'+remoteName+'</a></li>';
					$("#sshList").append(newSsh);
					closeLogin();
				}else if(message == 'no'){
					alert('链接失败');
				}else{
					if(message != "complete"){
						var content = $("#sshCommand").val();
						var addStr = "\n" + message;
						var newContent = content + addStr;
						$("#sshCommand").val(newContent);
					}else{
						var content = $("#sshCommand").val();
						addStr = "\n------------------------------->";
						var newContent = content + addStr;
						$("#sshCommand").val(newContent);
					}
				}
			}
			ws.onclose = function(){
				alert("服务器已经断开连接");
			}
			ws.onerror = function(e){
				alert('服务器已断开'+e.data);
			}
		</script>
	</head>
	<body>
			<div id="mainView" style="width:100%;height:auto">
				<div style="background-color:blue;height:100%;width:80%;float:left">
					<div id="sshIp" class="col-md-12 text-center" style="background-color:white;color:black;height:40px;line-height:40px;border-bottom:3px solid white;text-align:center">192.168.30.3--root</div>	
					<div id="sshContent" class="col-md-12" style="background-color:white;color:black;">
						<textarea id="sshCommand" style="background-color:white;color:black;height:100%;width:100%">
------------------------------->
------------------------------->
------------------------------->
------------------------------->
------------------------------->
------------------------------->
-------------------------------></textarea>
					</div>	
				</div>
				<div style="background-color:white;color:black;height:100%;width:20%;float:left">
					<a onclick="openLogin()" style="font-size:20px;color:blue;cursor:pointer">新建链接</a>
					<ul id="sshList" style="list-style:none;margin-top:50px;">
						<!--<li><a onclick="changeSsh(this)" id="192.168.20.21_root">192.168.20.21_root</a></li>
						<li><a onclick="changeSsh(this)" id="192.168.30.21_mary">192.168.30.21_mary</a></li>
						<li><a onclick="changeSsh(this)" id="192.168.20.22_jack">192.168.20.22_jack</a></li>
						<li><a onclick="changeSsh(this)" id="192.168.20.25_root">192.168.20.25_root</a></li>
					--></ul>
				</div>
				<div style="clear:both"></div>
			</div>
	</body>
	<script type="text/javascript">
		function openLogin(){
			var ele = "<div id='allLogin' style='left:0px;top:0px;width:100%;height:100%;position:fixed;background-color:black'>";
			ele += "<div style='width:300px;height:200px;position:fixed;top:40%;left:40%;background-color:white'>";
			ele += "远程ip：<input id='remoteIp' type='text' value='192.168.20.25' /><br/>";
			ele += "用户名：<input id='remoteName' type='text' value='root' /><br/>";
			ele += "密码：<input id='remotePwd' type='text' value='123456' /><br/>";
			ele += "<button onclick='checkLogin()'>链接</button>&nbsp;&nbsp;&nbsp;&nbsp;";
			ele += "<button onclick='closeLogin()'>关闭</button>";
			ele += "</div>";
			ele += "</div>";
			$("body").append(ele);	
		}
		function closeLogin(){
			$("#allLogin").remove();
		}
		function checkLogin(){
			if(true){
				addSsh();
			}
		}
		function setAct(){
			var num = $("#sshList").children();
			if(num.length == 0){
				return true;
			}else{
				return false;
			};
		}
		function addSsh(){
			var remoteIp = $('#remoteIp').val();
			var remoteName = $('#remoteName').val();
			var remotePwd = $('#remotePwd').val();
			var changeRemoteIp = remoteIp.replace(/\./g, "-");
			var msg = remoteIp + "#" + remoteName + "#" + remotePwd;
			ws.send(msg);
		}
		function changeSsh(obj){
			var content = $("#sshCommand").val();
			var oldSshDataId = $("#sshList a[class*='act']").attr("id");
			oldSshDataId = oldSshDataId + "_data";
			$("#"+oldSshDataId).val(content);

			$("#sshList a").removeClass('act');
			var sshDataId = $(obj).attr("id");
			sshDataId = sshDataId+"_data";
			$("#sshCommand").val($("#"+sshDataId).val());
			$(obj).addClass('act');
			var ipStr = $(obj).text();
			$("#sshIp").text(ipStr);
		}
	</script>
	<script type="text/javascript">
		var width = $(document).width();
		var height = $(document).height();
		$("#mainView").css({"width":width, "height":height});
		$("#sshContent").scrollTop(300);
		var sshIpHeight = $("#sshIp").height();
		var sshContentHeight = height - sshIpHeight;
		$("#sshContent").css({"height":sshContentHeight});
		$("#sshCommand").keydown(function(event){
			var kcode = event.keyCode;
			var sshNum = $("#sshList li").length;
			if(sshNum == 0){
				return false;
			}
			if(kcode == 37 || kcode == 38 || kcode == 39 || kcode == 40){
				return true;
			}
			if(kcode == 13){
				var content = $("#sshCommand").val();
				var oldSshDataId = $("#sshList a[class*='act']").attr("id");
				oldSshDataId = oldSshDataId + "_data";
				$("#"+oldSshDataId).val(content);
				var currentSshId = $("#sshList a[class*='act']").attr('id');
				currentSshId2 = currentSshId.replace(/-/g, ".");
				remoteSshInfo = currentSshId2.split('_');
				var remoteIp = remoteSshInfo[0];
				var remoteName = remoteSshInfo[1];
				//获取linux命令
				var sshDataId = "#" + currentSshId + "_data";
				var content = $(sshDataId).val();
				var command = content.split("\n");
				command = command[command.length-1];
				command = command.replace(/------------------------------->/, '');
				var message = remoteIp+'#'+remoteName+'#'+command;
				ws.send(message);
				return false;
			}
			var startFlag = this.selectionStart;
			if(event.keyCode == 8){
				var content = this.value;
				if(content[startFlag-1] == ">" && content[startFlag-2] == "-"){
					return false;
				}
			}
			var content = this.value;
			var str = content.split('\n');
			var endStr = str[str.length-1];
			var lowStr = content.length - endStr.length + 10;
			if(lowStr > startFlag){
				return false;
			}else{
				return true;
			}
		});
	</script>
</html>




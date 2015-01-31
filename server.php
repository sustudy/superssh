<?php
    /*<one line to give the program's name and a brief idea of what it does.>
    该程序可以是浏览器版的ssh。可以用手机，电脑，平板使用浏览器登陆ssh,来操作linux系统。
    Copyright (C) 2015 - 2016  苏少峰

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.*/
	header('content-type=text/html;charset=utf8');
	class WS{
		private $clients = array();
		private $sockets = array();
		private $clientsInfos = array();
		private $master;
		private $debug = true;

		function __construct($ip){
			$this->master=socket_create(AF_INET, SOCK_STREAM, SOL_TCP)     or die("socket_create() failed");
			socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1)  or die("socket_option() failed");
			socket_bind($this->master, $ip, 9999)                    or die("socket_bind() failed");
			socket_listen($this->master,20)                                or die("socket_listen() failed");
			$this->sockets[] = $this->master;
			while(true){
				$reads = $this->sockets;
				$a = null;
				$b = null;
				//socket_select($reads, $a=null, $b=null, 0);
				socket_select($reads, $a, $b, 0);
				if(in_array($this->master, $reads)){
					$client = socket_accept($this->master);
					$this->sockets[] = $client;
	                $bytes = @socket_recv($client, $buffer, 2048, 0);
	                $this->doHandShake($client, $buffer);
				}
				foreach ($reads as $client) {
					if($this->master == $client){
						continue;
					}
					$recvByte = socket_recv($client, $buffer, 2048, 0);
					socket_getpeername($client, $ip);
					if($recvByte == 8){
						$this->closeConnect($client, $ip);
						continue;
					}
					$message = $this->decode($buffer);
					echo "\n-------->".$message."<----------\n";
					$infos = explode("#", $message);
					$remoteIp = empty($infos[0]) ? "" : $infos[0];
					$remoteName = empty($infos[1]) ? "" : $infos[1];
					$remotePwdOrCommand = empty($infos[2]) ? "" : $infos[2];
					$this->clientsInfos[$ip][$remoteIp][$remoteName]['isLogin'] = empty($this->clientsInfos[$ip][$remoteIp][$remoteName]['isLogin']) ? false : true;
					if($this->clientsInfos[$ip][$remoteIp][$remoteName]['isLogin'] == true){
						$cmd = $remotePwdOrCommand;
						$connection = $this->clientsInfos[$ip][$remoteIp][$remoteName]['sshConn'];
						$shell = $this->clientsInfos[$ip][$remoteIp][$remoteName]['shell'];
						fwrite($shell, $cmd."\n");
						sleep(1);
						$contentArr = array();
					    while($line = fgets($shell)) {
					       	$contentArr[] = $line;
					    }
					    $this->sendAllMessage($client, $contentArr);
					    $this->send($client, "complete");
					}else if(($this->clientsInfos[$ip][$remoteIp][$remoteName]['isLogin'] == false || empty($this->clientsInfos[$ip][$remoteIp][$remoteName]['isLogin'])) && ($remoteName != "")){
						$connection = ssh2_connect($remoteIp, 22);
						$flag = ssh2_auth_password($connection, $remoteName, $remotePwdOrCommand);
						if($flag){
							$shell = ssh2_shell($connection, 'xterm');
							$this->clientsInfos[$ip][$remoteIp][$remoteName]['sshConn'] = $connection;
							$this->clientsInfos[$ip][$remoteIp][$remoteName]['isLogin'] = true;
							$this->clientsInfos[$ip][$remoteIp][$remoteName]['shell'] = $shell;
							$this->send($client, "yes");
						}else{
							$this->clientsInfos[$ip][$remoteIp][$remoteName]['isLogin'] == false;
							$this->send($client, "no");
						}
					}
				}
			}		
		}
		function sendAllMessage($client, $contentArr){
			foreach ($contentArr as $key => $content) {
			   	$content = str_replace("[00;34", "", $content);
			    $content = str_replace("[00m", "", $content);
			    $content = str_replace("[root@localhost ~]#", "", $content);
			    $content = str_replace(";root@localhost:~", "", $content);
			    $content = preg_replace("/\[(.*?)@(.*?)]#/", "", $content);
			   	$content = preg_replace("/;(.*?)@(.*?):~/", "", $content);
			   	$content = preg_replace("/\[\d{2};\d{2}m{0,1}/", "", $content);
			   	$content = str_replace("[0m", "", $content);
			    $content = str_replace("[m]0", "", $content);
			    $content = str_replace("]0", "", $content);
			    $content = str_replace("[00;31", "", $content);
			    $content = str_replace("ls", "", $content);
			    $content = str_replace("[m", "", $content);
			    $content = str_replace("", "", $content);
			    $content = str_replace("", "", $content);
			    $content = str_replace("\r\n", "", $content);
		       	$this->send($client, $content);
			}
		}
		function send($client, $msg){
			$this->log("> " . $msg);
			$msg = $this->frame($msg);
			socket_write($client, $msg, strlen($msg));
			$this->log("! " . strlen($msg));
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			echo "[$errorcode]".$errormsg."\n";
			echo "------------------------------\n";
		}
		function closeConnect($socket, $ip){
			$index = array_search($socket, $this->sockets);
			socket_close($socket);
			if ($index >= 0){
	            array_splice($this->sockets, $index, 1);
			}
			unset($this->clientsInfos[$ip]);
		}
		function doHandShake($socket, $buffer){
			$this->log("\nRequesting handshake...");
			$this->log($buffer);
			list($resource, $host, $origin, $key) = $this->getHeaders($buffer);
			$this->log("Handshaking...");
			$upgrade  = "HTTP/1.1 101 Switching Protocol\r\n" .
						"Upgrade: websocket\r\n" .
						"Connection: Upgrade\r\n" .
						"Sec-WebSocket-Accept: " . $this->calcKey($key) . "\r\n\r\n";  //必须以两个回车结尾
			$this->log($upgrade);
	        $sent = socket_write($socket, $upgrade, strlen($upgrade));
			$this->log("Done handshaking...");
			return true;
		}
		function getHeaders($req){
			$r = $h = $o = $key = null;
			if (preg_match("/GET (.*) HTTP/"              ,$req,$match)) { $r = $match[1]; }
			if (preg_match("/Host: (.*)\r\n/"             ,$req,$match)) { $h = $match[1]; }
			if (preg_match("/Origin: (.*)\r\n/"           ,$req,$match)) { $o = $match[1]; }
			if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$req,$match)) { $key = $match[1]; }
			return array($r, $h, $o, $key);
		}
		function calcKey($key){
			$accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
			return $accept;
		}
		function decode($buffer) {
			$len = $masks = $data = $decoded = null;
			$len = ord($buffer[1]) & 127;

			if ($len === 126) {
				$masks = substr($buffer, 4, 4);
				$data = substr($buffer, 8);
			} 
			else if ($len === 127) {
				$masks = substr($buffer, 10, 4);
				$data = substr($buffer, 14);
			} 
			else {
				$masks = substr($buffer, 2, 4);
				$data = substr($buffer, 6);
			}
			for ($index = 0; $index < strlen($data); $index++) {
				$decoded .= $data[$index] ^ $masks[$index % 4];
			}
			return $decoded;
		}
		function frame($s){
			$a = str_split($s, 125);
			if (count($a) == 1){
				return "\x81" . chr(strlen($a[0])) . $a[0];
			}
			$ns = "";
			foreach ($a as $o){
				$ns .= "\x81" . chr(strlen($o)) . $o;
			}
			return $ns;
		}
		function log($msg = ""){
			if ($this->debug){
				echo $msg . "\n";
			} 
		}
	}

	$ip = "115.28.59.153";
	$ws = new WS($ip);
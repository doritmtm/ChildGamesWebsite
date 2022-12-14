<!DOCTYPE html>
<html>
<?php
// PHP SOCKET SERVER
error_reporting(E_ERROR);
// Configuration variables
$host = "127.0.0.1";
$port = 4041;
$max = 200;
$client = array();

// No timeouts, flush content immediatly
set_time_limit(0);
ob_implicit_flush();

// Server functions
function rLog($msg){
             $msg = "[".date('Y-m-d H:i:s')."] ".$msg;
             print($msg."\n");

}
// Create socket
$sock = socket_create(AF_INET,SOCK_STREAM,0) or die("[".date('Y-m-d H:i:s')."] Could not create socket\n");
// Bind to socket
socket_bind($sock,$host,$port) or die("[".date('Y-m-d H:i:s')."] Could not bind to socket\n");
// Start listening
socket_listen($sock) or die("[".date('Y-m-d H:i:s')."] Could not set up socket listener\n");

rLog("Server started at ".$host.":".$port);
// Server loop
             socket_set_block($sock);
             // Setup clients listen socket for reading
             $read[0] = $sock;
             for($i = 0;$i<$max;$i++){
                          if($client[$i]['sock'] != null)
                                       $read[$i+1] = $client[$i]['sock'];
             }
             // Set up a blocking call to socket_select()
             $ready = socket_select($read,$write = NULL, $except = NULL, $tv_sec = NULL);
             // If a new connection is being made add it to the clients array
             if(in_array($sock,$read)){
                          for($i = 0;$i<$max;$i++){
                                       if($client[$i]['sock']==null){
                                                    if(($client[$i]['sock'] = socket_accept($sock))<0){
                                                                 rLog("socket_accept() failed: ".socket_strerror($client[$i]['sock']));
                                                    }else{
                                                                 rLog("Client #".$i." connected");
                                                    }
                                                    break;
                                       }elseif($i == $max - 1){
                                                    rLog("Too many clients");
                                       }
                          }
                          if(--$ready <= 0)
                          continue;
             }
             for($i=0;$i<$max;$i++){
                          if(in_array($client[$i]['sock'],$read)){
                                       $input = socket_read($client[$i]['sock'],1024);
                                       if($input==null){
                                                    unset($client[$i]);
                                       }
                                       $n = trim($input);
                                       $com = split(" ",$n);
                                       if($n=="EXIT"){
                                                    if($client[$i]['sock']!=null){
                                                                 // Disconnect requested
                                                                 socket_close($client[$i]['sock']);
                                                                 unset($client[$i]['sock']);
                                                                 rLog("Disconnected(2) client #".$i);
                                                                 for($p=0;$p<count($client);$p++){
                                                                              socket_write($client[$p]['sock'],"DISC ".$i.chr(0));
                                                                 }
                                                                 if($i == $adm){
                                                                              $adm = -1;
                                                                 }
                                                    }
                                       }elseif($n=="TERM"){
                                                    // Server termination requested
                                                    socket_close($sock);
                                                    rLog("Terminated server (requested by client #".$i.")");
                                                    exit();
                                       }elseif($input){
                                                    // Strip whitespaces and write back to user
                                                    // Respond to commands
                                                    /*$output = ereg_replace("[ \t\n\r]","",$input).chr(0);
                                                    socket_write($client[$i]['sock'],$output);*/
                                                    if($n=="PING"){
                                                                 socket_write($client[$i]['sock'],"PONG".chr(0));
                                                    }
                                                    if($n=="<policy-file-request/>"){
                                                                 rLog("Client #".$i." requested a policy file...");
                                                                 $cdmp="<?xml version=\"1.0\" encoding=\"UTF-8\"?><cross-domain-policy xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"http://www.adobe.com/xml/schemas/PolicyFileSocket.xsd\"><allow-access-from domain=\"*\" to-ports=\"*\" secure=\"false\" /><site-control permitted-cross-domain-policies=\"master-only\" /></cross-domain-policy>";
                                                                 socket_write($client[$i]['sock'],$cdmp.chr(0));
                                                                 socket_close($client[$i]['sock']);
                                                                 unset($client[$i]);
                                                                 $cdmp="";
                                                    }
                                       }
                          }else{
                                       //if($client[$i]['sock']!=null){
                                                    // Close the socket
                                                    //socket_close($client[$i]['sock']);
                                                    //unset($client[$i]);
                                                    //rLog("Disconnected(1) client #".$i);
                                       //}
                          }
             }
// Close the master sockets
socket_send($sock,"resp#is WORKING#dori#sse#0000FF");
socket_close($sock);
?>
<body>
<a id="sse"/>
<script type="text/javascript">
var user="dori";
  if ("WebSocket" in window)
  {
     var ws = new WebSocket("ws://echo.websocket.org/?encoding=text HTTP/1.1");
}
function wsc(str){
var scr=str.split("#");
var rez=new Array();
for(i=0;i<scr.length;i++){
rez[i]=scr[i].split(",");
}
if(rez[0]=="send"){
ws.onopen=function(){
ws.send("resp#"+rez[1]+"#"+rez[2].join(",")+"#"+rez[3]+"#"+rez[4]);
};
}
if(rez[0]=="resp"){
var tobj=document.getElementById(rez[3][0]);
var usr=rez[2].indexOf(user);
if(usr>-1){
tobj.innerHTML=rez[1];
tobj.style.color="#"+rez[4];
}
}
}
     ws.onmessage = function (evt) 
     { 
        wsc(evt.data);
};
</script>
</body>
</html>
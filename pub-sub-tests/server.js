var app = require('http').createServer(handler)
var io = require('socket.io')(app);
var fs = require('fs');
var http = require('http');
http.globalAgent.maxSockets = Infinity;


app.listen(19000);
var users=0;
function handler (req, res) {
  fs.readFile(__dirname + '/index.html',
      function (err, data) {
        if (err) {
          res.writeHead(500);
          return res.end('Error loading index.html');
        }

        res.writeHead(200);
        res.end(data);
      });
}

function str2ab(str) {
  let strLen=str.length;
  var buf = new ArrayBuffer(strLen);
  let bufView = new Uint8Array(buf);
  for (let i=0; i < strLen; i++) {
    bufView[i] = str.charCodeAt(i);
  }
  return buf;
}

var clients={};


setInterval( () => {
  for (let client in clients) {
    clients[client].sendMessage("PUBLIC_QNA_NOTIFICATIONS", "kaka");
  }
},10000);

var clientId=0;
class Client {
  constructor(socket) {
    this.socket=socket;
    this.id=clientId++;
    clients[this.id]=this;
    socket.emit('validated',{})
    socket.on('listen',  (data)=> {
      console.log("request for "+data);
      this.sendMessage(data,'hello world');
    });
    socket.on('disconnect',() => {
      delete clients[this.id];
      console.warn("got disconnection total users",--users);

    });
  }
  sendMessage(eventName,msg) {
    this.socket.emit('message',eventName,{data:str2ab(msg)});
  }
}


io.on('connection', function (socket) {
  var client=new Client(socket);
  console.warn("got connection total users",++users);
});
console.warn("running");
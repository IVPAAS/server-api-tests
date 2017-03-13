let  KalturaAPI = require('./KalturaService.js');
var ioClient = require('socket.io-client');
var _ = require('lodash');
var io = require('socket.io-client');
var logger=require('./logger.js');
var config = require('config');
const util = require('util');
class Client  {
    constructor(config, entryId,userName, id, callback) {

        this.logger=logger.getLogger("Client",userName);
        this.entryId=entryId;
        let myConf = _.clone(config);
        myConf.userId = userName;
        if (_.isObject(config)) {
            this.kalturaAPI = new KalturaAPI(myConf);
        } else {
            this.url = config;
        }
        this.counter = 0;
        this.id=id;
        this.userName=myConf.userId + "_" +id;
        this.socket=null;
        this.lastMsg={};
        this.recievedAnnotations=[];
        this.recievedMessageCallback = callback;
    }
    registerNotification(eventName,params) {

        if (this.url) {
            return Promise.resolve({url:this.url, key: eventName});
        }
        var request = {
            'service': 'eventNotification_eventNotificationTemplate',
            'action': 'register',
            'format': 1,
            'notificationTemplateSystemName': eventName,
            'pushNotificationParams:objectType': "KalturaPushNotificationParams",


        };
        var index = 0;
        _.each(params, function (value,key) {
            request['pushNotificationParams:userParams:item' + index + ":objectType"] = "KalturaPushEventNotificationParameter";
            request['pushNotificationParams:userParams:item' + index + ":key"] = key;
            request['pushNotificationParams:userParams:item' + index + ":value:objectType"] = "KalturaStringValue";
            request['pushNotificationParams:userParams:item' + index + ":value:value"] = value;
            index++;
        });
        this.logger.info("registering to ", request);

        return this.kalturaAPI.call(request);
    }
    connect(callback, port) {


        var events = [
           {
                "name": "USER_QNA_NOTIFICATIONS",
                args: {
                    "entryId": this.entryId,
                    "userId": this.userName
                }
            },
            {
                "name": "PUBLIC_QNA_NOTIFICATIONS",
                args: {
                    "entryId": this.entryId,
                }
            },
            {
                "name": "CODE_QNA_NOTIFICATIONS",
                args: {
                    "entryId": this.entryId
                }
            }
        ];


        var t1=new Date();
        var promises=[];
        var errors = [];
        var count = 0;
        this.kalturaAPI.startMultirequest();
        for (var i = 0 ; i < 3 ; i++ ){

            var event = events[i];
            this.registerNotification(event.name,event.args).then ((res)=> {
                if (this.registerEvents(event.name,res.url,res.queueName, res.queueKey, port)){
                count ++;
                if (count == 3)
                        callback(errors);

}
        }).catch( (err)=> {
                this.logger.warn("Error registering event " + event.name + ": "  + util.inspect(err));
                errors.push(err);
        });
        }
        this.kalturaAPI.execMultirequest();
    }


    addAnnotation(callback, text, offset, choice, freeText){
        var addCuePoint = {
            service: 'cuepoint_cuepoint',
            action: 'add',
            cuePoint: {
                objectType: 'KalturaCodeCuePoint',
                entryId: "0_9tz98xd9",
                isPublic: true,
                text: "Test-Annotation",
                startTime: 3000,
                tags: "WEBCASTSTATETAG",
                code: "TestCode"
            }
        };

        var multiRequest = {
            format: 1,
            0: addCuePoint,
        };

        this.kalturaAPI.call(addCuePoint).then((res)=> {
            callback(res);
    }).catch( (err)=> {
            console.log(err);
        callback(null);
    });
    }

    registerEvents(eventName,url,eventKey, key,port) {

        var t1=new Date();


        this.connectToPushServer(url);

        var This = this;

        return this.connectionPromise.then( () => {
                var t2=new Date();
        this.totalConnectionTime=t2-t1;
        this.logger.info("Connected to server "+url+" for key "+key + " and eventName : " + eventKey);

        this.socket.emit('listen', eventKey, key);
        return true;
    });

    }

    isConnected() {
        return _.size(this.lastMsg)===3;
    }

    connectToPushServer(url) {
      var This = this;
          if (this.connectionPromise) {
            return this.connectionPromise;
        }

        this.connectionPromise=new Promise( (resolve, reject)=> {
                let resolved=null;
        this.logger.info("Connecting to " +  url);
        process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
        this.socket = io.connect(url, {forceNew: true,reconnection: true, 'force new connection': true, transports: [ 'websocket' ], secure: true});
        var options = {'forceNew': true};

        this.socket.on('validated', () => {
            if (resolved===null) {
            this.logger.info("Connected to socket for "+url);
            resolved = true;
            resolve(true);
        } else {

            this.logger.warn("Connected to socket was after timeout "+url);
            this.socket.disconnect();
        }
    });

        this.socket.on('clientError', (err)=>{
        this.socket.on('disconnect',  (err)=> {this.logger.warn("Socket disconnect err=" ,err,'"');});
            this.logger.warn('push server was disconnected err="',err,'"');
    });
        this.socket.on('reconnect_error',  (e)=> {
            This.logger.warn('push server reconnection failed '+util.inspect(e));
    });
        this.socket.on('error', function (e) {
            this.logger.warn('socket errorר'+e);
    });
        this.socket.on('connect_error', function(error){
         This.logger.warn("conn error "+ util.inspect(error));
    });
         this.socket.on('errorMsg', function (e) {

            This.logger.warn('socket errorרMsg: '+ e);
    });

        this.socket.on('connected', (queueKey, key)=> {
            this.logger.info("listen to queue [" + queueKey + "] for eventName  key "+key);
        this.lastMsg[queueKey]=key;
    });

        this.socket.on('message', (queueKey, msgs)=>{
        var d = new Date();
        var seconds = d.getTime() / 1000;
        this.counter = this.counter + 1;
        this.logger.info(JSON.stringify(msgs));
        for (var i =0 ; i < msgs.length ; i++ ) {
                var msg = msgs[i];
                var annotation = msg;
                annotation.receivedAt = seconds;
                this.logger.info("Client number " + this.id + " recieved message number " + this.counter + ": [" + queueKey + "]: " +  JSON.stringify(annotation));
                this.recievedAnnotations.push(annotation);
                this.recievedMessageCallback();
        }
    });

        setTimeout(() =>{
            if (!resolved) {
            this.logger.warn("Timeout connecting to socket for "+url);
            resolved=false;
            reject("rejeceted - TIMEOUT");
        }
    },config.get('connectionTimeout'));
    });

        return this.connectionPromise;
    }
};

exports.Client=Client;

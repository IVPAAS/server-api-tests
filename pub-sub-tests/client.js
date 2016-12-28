let  KalturaAPI = require('./KalturaService.js');
var ioClient = require('socket.io-client');
var _ = require('lodash');
var io = require('socket.io-client');
var logger=require('./logger.js');
var config = require('config');

class Client  {
    constructor(config, entryId,userName, id) {

        this.logger=logger.getLogger("Client",userName);
        this.entryId=entryId;
        if (_.isObject(config)) {
            this.kalturaAPI = new KalturaAPI(config);
        } else {
            this.url = config;
        }
        this.id=id;
        this.userName=userName;
        this.socket=null;
        this.lastMsg={};
        this.recievedAnnotations=[];
    }
    registerNotification(eventName,params) {

        if (this.url) {
            return Promise.resolve({url:this.url, key: eventName});
        }
        var request = {
            'service': 'eventNotification_eventNotificationTemplate',
            'action': 'register',
            'format': 1,
            "notificationTemplateSystemName": eventName
        };
        var index = 0;
        _.each(params, function (value,key) {
            request["userParamsArray:" + index + ":objectType"] = "KalturaEventNotificationParameter";
            request["userParamsArray:" + index + ":key"] = key;
            request["userParamsArray:" + index + ":value:objectType"] = "KalturaStringValue";
            request["userParamsArray:" + index + ":value:value"] = value;
            index++;
        });
        this.logger.info("registering to ", request);

        return this.kalturaAPI.call(request);
    }
    connect() {

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
                    "entryId": this.entryId
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
        for (let event of events) {


            promises.push(this.registerNotification(event.name,event.args).then ((res)=> {
                    return this.registerEvents(event.name,res.url,res.key);
        }).catch( (err)=> {
                console.warn(err);
        }));
        }

        Promise.all(promises).then( ()=> {
            var t2=new Date();
        this.totalRegisterTime=t2-t1;

    });

    }

    addAnnotation(callback, text, offset, choice, freeText){
        var addCuePoint = {
            service: 'cuepoint_cuepoint',
            action: 'add',
            cuePoint: {
                objectType: 'KalturaCodeCuePoint',
                entryId: "0_47uvjg49",
                isPublic: true,
                text: "EREZ",
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

    registerEvents(eventName,url,key) {

        var t1=new Date();
        this.connectToPushServer(url);


        return this.connectionPromise.then( () => {
                var t2=new Date();
        this.totalConnectionTime=t2-t1;
        this.logger.info("Connected to server "+url+" for key "+key);

        this.socket.emit('listen', key);
        return Promise.resolve();
    });

    }

    isConnected() {
        return _.size(this.lastMsg)===3;
    }

    connectToPushServer(url) {
        if (this.connectionPromise) {
            return this.connectionPromise;
        }
        this.connectionPromise=new Promise( (resolve, reject)=> {

                let resolved=null;
        this.logger.debug("Connecting to " +  url);
        this.socket = io.connect(url, {forceNew: true,'force new connection': true});

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
        this.socket.on('disconnect',  (err)=> {
            this.logger.warn('push server was disconnected err="',err,'"');
    });
        this.socket.on('reconnect_error',  (e)=> {
            this.logger.warn('push server reconnection failed '+e);
    });

        this.socket.on('connected', (queueKey, key)=> {
            this.logger.info("listen to queue [" + queueKey + "] for eventName  key "+key);
        this.lastMsg[queueKey]=key;
    });

        this.socket.on('message', (queueKey, msg)=>{
            var message=String.fromCharCode.apply(null, new Uint8Array(msg.data));
        this.lastMsg[queueKey]=message;
        var annotation = JSON.parse(message);
        var d = new Date();
        var seconds = d.getTime() / 1000;
        annotation.receivedAt = seconds;
        this.logger.info("recieved message: [" + queueKey + "]: " +  JSON.stringify(annotation));
        this.recievedAnnotations.push(annotation);
    });

        setTimeout(() =>{
            if (!resolved) {
            this.logger.warn("Timeout connecting to socket for "+url);
            resolved=false;
            reject("TIMEOUT");
        }
    },config.get('connectionTimeout'));
    });

        return this.connectionPromise;
    }
};

exports.Client=Client;
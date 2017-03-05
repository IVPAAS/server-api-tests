const chai = require('chai');
const assert = require('assert');
var logger = require('./logger.js');
var Client = require('./client.js').Client;
var config = require('config');
var _ = require('lodash');
var http = require('http');
var os = require("os");

var id=1;
var annotations=[];
var annotationsToSend = 1;
var client;
var count;
var clientCount = parseInt(config.get('testClientCount'),10);
var doneMethod;
var logger1 = logger.getLogger("Client","pubSubTesting");
var receivedMsgs = 0;
var clients = [];
var finished = false;

var receivedMsgs = 0;
var registeredClients = 0;
var errorClientCount = 0;

function main()
{
         doneMethod = function(){};
        count = 0;
        setTimeout(function () {
            if (finished == false)
                {
                        logger1.info("Test Timed-out and didn't finish");
                        notifyEndTest(-1);
                }
            else{
                        logger1.info("Test Timed-out and finished");
                        notifyEndTest(receivedMsgs);
                }
                setTimeout(function () {process.exit(0)},2000);

        }, parseInt(config.get('test-runtime'),10));


        try{
                var x = 0;
var intervalID = setInterval(function () {

                multiMessagesTest(x);

       if (++x === clientCount ) {
           clearInterval(intervalID);
                 }
                 }, 50);
        }
        catch(err){
        logger1.info("ERROR CAUGHT: " + util.inspect(err));
        }

}

main();

function multiClientTest() {
for (let  i =0 ; i < clientCount; i++) {
    let client = new Client(config.get('server'), config.get('entryId'), "pubSubTesting", i, function () {

        receivedMsgs = receivedMsgs + 1;
        if (receivedMsgs == clientCount) {
            logger1.info("All Clients Received Msg!");
            if (validateAnnotations(client)) {
                logger1.info("TEST IS DONE!");
                doneMethod();
            }
            else {
                logger1.info("Test FAILED");
                doneMethod("Test Failed!");
            }

                   }
    });
    client.connect(function () {
            logger1.info("registered client number: " + count);
            count = count + 1;
            if (count == clientCount)
                client.addAnnotation(function (annotation) {
                    annotations.push(annotation);
                });
        }
    );

}
}

function multiMessagesTest(i)
{
        client = new Client(config.get('server'), config.get('entryId'), "pubSubTesting", i, function () {

        receivedMsgs = receivedMsgs + 1;
        if (receivedMsgs == (clientCount * annotationsToSend)) {
            logger1.info("All Clients Received all " + (receivedMsgs/clientCount) + " Messages!");
                logger1.info("TEST IS DONE!");
            finished = true;
        }
    });

        client.connect(function (errors) {
            if (errors.length > 0 )
            {
                errorClientCount = errorClientCount + 1;
                logger1.warn("Could not register client number " + i + " : "  + errors);
            }

            else
            {
                registeredClients = registeredClients + 1;
                clients.push(client);
            }

            if ((registeredClients + errorClientCount) == clientCount)
                {
                logger1.info("Finished trying to register all clients. Successful registered clients: " + registeredClients + " ,  unregistered clients: " + errorClientCount);
                if (registeredClients == clientCount)
                        {
                                 var options = {
                                           host: config.get('test-server'),
                                           path: '/',
                                           port: config.get('test-server-port'),
                                           headers: {'readyForMessages': 'true'}
                                   };
                                var req = http.request(options, function(res) {
                                  logger1.info('Notify Message Recieved Sent STATUS: ' + res.statusCode);
                                });

                                req.on('error', function(e) {
                                  logger1.info('Notify messages Recieved - problem with request: ' + e.message);
                                });
                                   req.end();


                        }
                }
        }, 8085);
}


function notifyEndTest(msgCount){
   var options = {
   host: config.get('test-server'),
   path: '/',
   port: config.get('test-server-port'),
   headers: {'registeredClients': registeredClients, 'unregisteredClients': errorClientCount, 'messagesRecieved': msgCount, 'hostdone': 'true', 'sender': os.hostname()}
   };

  var req = http.request(options, function(res) {
  logger1.info('Notify End Test. STATUS: ' + res.statusCode);
        });

        req.on('error', function(e) {
          logger1.info('Notify messages Recieved - problem with request: ' + e.message);
        });


   req.end();
}

function sendAnnotationsInvoker(){
        sleepFor(20000);
        logger1.info("Start Sending annotations for all clients " + clients.length);
        for (var i = 0; i < clients.length; i++)
        {
        sleepFor(10);
        sendAnnotations(clients[0], 1 , 0);
        }
}

function sendAnnotations(tempClient, annotationNumber, clientNumber ){
    MsgsSent = MsgsSent+1;
    if (MsgsSent >= annotationsToSend+1)
        return;
    logger1.info("Sending annotation " + annotationNumber + " from client number " + clientNumber);
    tempClient.addAnnotation(function (annotation) {
        annotations.push(annotation);
        if (MsgsSent != (annotationsToSend))
            sendAnnotations(tempClient, annotationNumber+1, clientNumber);

    });
}

function validateAnnotations(client)
{
    var recievedAnnotations = client.recievedAnnotations;
    var failedCount = 0;
    if (recievedAnnotations.length != annotationsToSend)
    {
        console.warn("Didn't receive all annotations. Received [" + recievedAnnotations.length +  "] annotations while expecting " + annotationsToSend);
        return false;
    }
    else
    {
        for (var key in annotations){
            var annotation = annotations[key];
            console.log("validating annotation:" + annotation.id);
            for (var i = 0; i < recievedAnnotations.length; i++) {
                var found = false;
                if (recievedAnnotations[i].id == annotation.id) {
                    found = true;
                    break;
                }
            }
            if ( found )
            {
                var duration = parseFloat(recievedAnnotations[i].receivedAt) - parseFloat(recievedAnnotations[i].createdAt);
                console.log("validating annotation duration:" + duration);
                if (duration > 4)
                {
                    console.log("Annotation duration is long! duration is " + duration);
                    failedCount++;
                }
                console.log("Annotation:" + annotation.id + " Validated!");
            }
            else
            {
                console.log("Annotation:" + annotation.id + " Wasn't received on queue!");
                failedCount++;
            }

        }
        if (failedCount != 0)
            return false;
        else
            return true;
    }
}

function sleepFor( sleepDuration ){
    var now = new Date().getTime();
    while(new Date().getTime() < now + sleepDuration){ /* do nothing */ }
}

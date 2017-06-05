const util = require('util');
const port = 8141;
my_http = require("http");
var fs = require('fs');
let  KalturaAPI = require('/opt/kaltura/pub-sub-server/testingServer/KalturaService.js');
let config = require('config');
let myConf = config.get('server');
let logFile = config.get('logFile');
let totalRegisteredClients = 0;
let totalUnRegisteredClients = 0;
let numberOfHosts = 1;
let responseCount = 0;
let numberOfClients = 0;
let numberOfExpectedMessages = 0;
let messagesRecieved = 0;
let entryToHandle = null;
let readyForMessages = 0;
let errors = [];
my_http.createServer(function(request,response){
        var body = '';
        request.on('data', function (data) {
            body += data;
        });
        request.on('end', function () {
        });


      response.writeHeader(200, {"Content-Type": "application/json"});
      if (request.headers.reset == 'true')
      {
        totalRegisteredClients = 0;
        totalUnRegisteredClients = 0;
        numberOfHosts = 1;
        responseCount = 0;
        numberOfClients = 0;
        numberOfExpectedMessages = 0;
        messagesRecieved = 0;
        readyForMessages = 0;
        errors = [];
        fs.writeFile(logFile, " " , function(err) {   });
        response.write('RESETING.\n' );
      }

      if (request.headers.hostcount)
        {
                numberOfHosts = parseInt(request.headers.hostcount,10);
                response.write('Host count is ' + JSON.stringify(numberOfHosts)+"\n" );
        }
         if (request.headers.clientcount)
        {
                numberOfClients = parseInt(request.headers.clientcount,10);
                response.write('Client count is ' + JSON.stringify(numberOfClients) +"\n");
        }
        if ( request.headers.entryid )
        {
                entryToHandle = request.headers.entryid;
                response.write('Entry id is ' +JSON.stringify(entryToHandle)+ "\n");
        }

        if (request.headers.messagescount)
        {
                numberOfExpectedMessages = parseInt(request.headers.messagescount,10);
                response.write('Messages count is ' + JSON.stringify(numberOfExpectedMessages)+"\n" );
        }

      if (request.headers.messagesrecieved)
        {
                if (parseInt(request.headers.messagesrecieved) == -1)
                 errors.push(request.headers.sender);

                else
                messagesRecieved = messagesRecieved + parseInt(request.headers.messagesrecieved);
        }

        if (request.headers.sender && request.headers.registeredclients && request.headers.unregisteredclients)
        {
                totalRegisteredClients = totalRegisteredClients + parseInt(request.headers.registeredclients,10);
                let failed = parseInt(request.headers.unregisteredclients,10);
                if (failed > 0 )
                {
                        totalUnRegisteredClients = totalUnRegisteredClients + failed;
                        errors.push(request.headers.sender);
                }
        }

        if (request.headers.readyformessages == 'true')
        {
                readyForMessages = readyForMessages + 1;
                if (numberOfHosts == readyForMessages)
                        addAnnotation();
        }
        if (request.headers.sendannouncement == 'true')
        {
                //                addAnnotation();
        }

        if (request.headers.endtest == 'true')
        {

        let result;
        let date = new Date();
        fs.appendFile(logFile, "[ " + date + " ] Test Finished Manually: \nNumber of registerd Clients: expected [" + numberOfClients  +  "] actual ["+ totalRegisteredClients
                        + "]. Number of unregistered Clients [" + totalUnRegisteredClients + "] \nNumber of messages - expected ["+ numberOfExpectedMessages +"] actual ["+ messagesRecieved  +"] \nMachines to check: " + errors.toString() + "\n" , function(err) {
                                if(err) {
                                        }
                              });

        if (request.headers.messagesstatus)
                        fs.appendFile(logFile, "messagesStatus: " + request.headers.messagesStatus + "\n" , function(err) {
                                if(err) {
                                }
                        });

                if (request.headers.pollvotesstatus)
                        fs.appendFile(logFile, "pollVotesStatus: " + request.headers.pollVotesStatus + "\n" , function(err) {
                                if(err) {
                                }
                        });
        }



        if( request.headers.hostdone == 'true')
        {
                responseCount = responseCount +1;
                console.log(" test done for : " +request.headers.sender + "response count is = " +responseCount);
        }

        if ( numberOfHosts == responseCount)
        {
                console.log(" TEST DONE");
                let result;
                let date = new Date();
                        fs.appendFile(logFile, "[ " + date + " ] Test Finished: \nNumber of registerd Clients: expected [" + numberOfClients  +  "] actual ["+ totalRegisteredClients
                        + "]. Number of unregistered Clients [" + totalUnRegisteredClients + "] \nNumber of messages - expected ["+ numberOfExpectedMessages +"] actual ["+ messagesRecieved  +"] \nMachines to check: " + errors.toString() + "\n" , function(err) {
                                if(err) {
                                }
                        });

                if (request.headers.messagesstatus)
                        fs.appendFile(logFile, "messagesStatus: " + request.headers.messagesstatus + "\n" , function(err) {
                                if(err) {
                                }
                        });

                if (request.headers.pollvotesstatus)
                        fs.appendFile(logFile, "pollVotesStatus: " + request.headers.pollvotesstatus + "\n" , function(err) {
                                if(err) {
                                }
                        });

                if ((numberOfClients == totalRegisteredClients ) && ( messagesRecieved == numberOfExpectedMessages) ){
                        result = 'SUCCESS';
                }
                else
                {
                        result = 'FAILED';
                }

                fs.appendFile(logFile, "TEST RESULT: " + result + "\n" , function(err) {
                                if(err) {
                                }
                        });
        }

        response.end();

},200000000000).listen(port);
console.log("Server Running on "+port);


function addAnnotation(){
     fs.appendFile(logFile, "\n Sending announcemnet\n", function(err){});
    var addCuePoint = {
            service: 'cuepoint_cuepoint',
            action: 'add',
            cuePoint: {
                objectType: 'KalturaCodeCuePoint',
                entryId: entryToHandle,
                isPublic: true,
                text: "Test-Annotation",
                startTime: 3000,
                partnerData: '{"qnaSettings":{"qnaEnabled":true,"announcementOnly":false}}',
                tags: "player-qna-settings-update",
                code: '{"message":"content inside partner data"}'
            }
        };

        var multiRequest = {
            format: 1,
            0: addCuePoint,
        };

        try{
                var x = 0;
        let kalturaAPI = new KalturaAPI(myConf);
                console.log("Adding announcements cuePoints...");

                for ( let x = 0 ; x <1 ; x++){
                kalturaAPI.call(addCuePoint).then((res)=> {
                 console.log("Announcemnet sent...");
                    }).catch( (err)=> {
                    console.log("Announcemnet failed " + util.inspect(err));
            });
                sleepFor(1000);
        }
        }
        catch(err){
                console.log("Annoucement failed: " + util.inspect(err));
        }
  }

function sleepFor( sleepDuration ){
    var now = new Date().getTime();
    while(new Date().getTime() < now + sleepDuration){ /* do nothing */ }
}

const chai = require('chai');
const assert = require('assert');
var logger = require('./logger.js');
let Client = require('./client.js').Client;
var config = require('config');
var _ = require('lodash');

var id=1;
var annotations=[];
var annotationsCount = 50;

describe('Pub sub annotation Test', function () {
    it('Annotation Test', function (done) {
        this.timeout(15000);
        var client = new Client(config.get('server'), config.get('entryId'),"pubSubTesting", id);
        client.connect();
        console.info("sleeping 5 seconds after registering...");
        sleepFor(5000);
        
        for ( var i = 0 ; i < annotationsCount ; i++)
        {
            client.addAnnotation(function (annotation) {
                annotations.push(annotation);
                if (annotations.length == annotationsCount)
                {
                    sleepFor(5000);
                    if (validateAnnotations(client))
                        done();
                    else
                        done("Test FAILED");
                }
            });
        }
    });
});

function validateAnnotations(client)
{
    var recievedAnnotations = client.recievedAnnotations;
    var failedCount = 0;
    if (recievedAnnotations.length != annotationsCount)
    {
        console.warn("Didn't receive all annotations. Received [" + recievedAnnotations.length +  "] annotations while expecting " + annotationsCount);
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
                if (duration > 2)
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

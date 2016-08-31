const os = require('os');
const fs = require('fs');
const url = require('url');
const dns = require('dns');
const util = require('util');
const http = require('http');
const chai = require('chai');
const assert = require('chai').assert;
const colors = require('colors/safe'); // does not alter string prototype
const rmdir = require('rmdir');
const kalturaClient = require('/opt/kaltura/web/content/clientlibs/node/KalturaClient');
const child_process = require('child_process');
const uuid = require('uuid');
require('../lib/utils/KalturaUtils');
const config = require('../lib/utils/KalturaConfig');

let failedTestsCount = 0;

const KalturaClientLogger = {
    log: function(str) {
        console.log(str);
    }
};

class TestingHelper {

    constructor() {
    }

    static printInfo(msg) {
        console.log(colors.blue(msg));
    }

    static printError(msg) {
        console.log(colors.red("ERROR: " + msg));
    }

    static printOk(msg) {
        console.log(colors.green(msg));
    }

    static printStatus(msg) {
        console.log(colors.yellow(msg));
    }

    static sleep(time) {
        var stop = new Date().getTime();
        while (new Date().getTime() < stop + time) {
            ;
        }
    }

    static initClient(serverHost, partnerId, adminSecret, callback) {
        console.log('Initializing client');
        var clientConfig = new kalturaClient.KalturaConfiguration(partnerId);

        clientConfig.serviceUrl = 'http://' + serverHost;
        clientConfig.setLogger(KalturaClientLogger);

        var type = kalturaClient.enums.KalturaSessionType.ADMIN;
        var client = new kalturaClient.KalturaClient(clientConfig);
        if (typeof callback === 'function') {
            client.session.start(function (ks) {
                client.setKs(ks);

                callback(client);
            }, adminSecret, 'test', type, partnerId, 86400, 'disableentitlement');
        }
        else {
            client.setKs(callback);
            return client;
        }
    }

    static parseCommandLineOptionsAndRunTest(callback) {
        var argv = process.argv.slice(2);

        var option;
        if (argv.length != 3)
            TestingHelper.printHelp();

        while (argv.length) {
            option = argv.shift();

            if (option[0] != '-' && argv.length == 2)
                this.serverHost = option;

            else if (option[0] != '-' && argv.length == 1) {
                this.partnerId = option;
                if (isNaN(this.partnerId)) {
                    console.error('Partner ID must be numeric [' + this.partnerId + ']');
                    TestingHelper.printHelp();
                }
                this.partnerId = parseInt(this.partnerId);
            }

            else if (option[0] != '-' && argv.length == 0)
                this.adminSecret = option;

            else if (option == '-h' || option == '--help')
                TestingHelper.printHelp();

        }

        console.log('Validating Kaltura API hostname [' + this.serverHost + ']');
        let This = this;
        dns.lookup(this.serverHost, function (err, address, family) {
            if (err) {
                console.error('Invalid Kaltura API hostname [' + This.serverHost + ']: ' + err);
                TestingHelper.printHelp();
            } else {
                console.log('Kaltura API hostname [' + This.serverHost + '] is valid');
                callback();
            }
        });

        if (!this.serverHost || !this.partnerId || !this.adminSecret) {
            TestingHelper.printHelp();
        }
    }

    static cleanFolder(folder) {
        let This = this;
        console.log("remove folder: " + folder);
        rmdir(folder, function (err, dirs, files) {
            if (err) {
                This.printError(err);
            }
        });
    }

    static printHelp() {
        console.log('Usage: ' + process.argv[0] + ' ' + process.argv[1] + ' serverHost partner-id admin-secret entry-id');
        console.log('Options:');
        console.log('\t -h / --help - This help');
        process.exit(1);
    }

    static createEntry(client, path) {
        let input = {client: client, path: path};
        return new Promise(function (resolve, reject) {
            TestingHelper.createEntryPromise(input)
                .then(TestingHelper.uploadTokenPromise)
                .then(TestingHelper.uploadFilePromise)
                .then(TestingHelper.addContentPromise)
                .then(function (result) {
                    resolve(result.entry);
                })
                .catch(reject);
        });
    }

    static deleteEntry(client, entry) {
        return new Promise(function (resolve, reject) {
            TestingHelper.printInfo("Start DeleteEntry");
            client.baseEntry.deleteAction(function (results) {
                    if (results && results.code && results.message) {
                        TestingHelper.printError('Kaltura Error', results);
                        reject(results);
                    } else {
                        TestingHelper.printOk('deleteEntry OK');
                        resolve(entry);
                    }
                },
                entry.id);
        });
    }


    static createEntryPromise(input) {
        return new Promise(function (resolve, reject) {
            TestingHelper.printInfo("Start CreateEntry");
            let entry = new kalturaClient.objects.KalturaMediaEntry();
            entry.mediaType = kalturaClient.enums.KalturaMediaType.VIDEO;
            entry.name = "testEntry";
            entry.description = "testEntry";
            input.client.baseEntry.add(function (results) {
                    if (results && results.code && results.message) {
                        TestingHelper.printError('Kaltura Error', results);
                        reject(results);
                    } else {
                        TestingHelper.printOk('createEntry OK');
                        input.entry = results;
                        resolve(input);
                    }
                },
                entry);
        });
    }

    static uploadTokenPromise(input) {
        return new Promise(function (resolve, reject) {
            TestingHelper.printInfo("Start uploadToken for entry:" + input.entry.id);
            let uploadToken = new kalturaClient.objects.KalturaUploadToken();
            if (!input.path)
                reject("No file path is defined for upload token file. please specify a path.");
            else {
                uploadToken.filename = input.path;
                input.client.uploadToken.add(function (results) {
                        if (results && results.code && results.message) {
                            TestingHelper.printError('Kaltura Error', results);
                            reject(results);
                        } else {
                            TestingHelper.printOk('uploadToken OK');
                            input.uploadToken = results;
                            resolve(input);
                        }
                    },
                    uploadToken);
            }
        });

    }

    static uploadFilePromise(input) {
        return new Promise(function (resolve, reject) {
            TestingHelper.printInfo("Start uploadFile for upload token: " + input.uploadToken.id);
            input.client.uploadToken.upload(function (results) {
                    if (results && results.code && results.message) {
                        TestingHelper.printError('Kaltura Error', results);
                        reject(results);
                    } else {
                        TestingHelper.printOk('uploadFile OK');
                        resolve(input);
                    }
                },
                input.uploadToken.id, input.path, null, null, null);
        });
    }

    static addContentPromise(input) {
        return new Promise(function (resolve, reject) {
            TestingHelper.printInfo("Start add content for entry: " + input.entry.id + " and uploadToken: " + input.uploadToken.id);
            var resource = new kalturaClient.objects.KalturaUploadedFileTokenResource();
            resource.token = input.uploadToken.id;

            input.client.media.addContent(function (results) {
                    if (results && results.code && results.message) {
                        TestingHelper.printError('Kaltura Error', results);
                        reject(results);
                    } else {
                        TestingHelper.printOk("entry was created and content was added");
                        TestingHelper.waitForEntryToBeReady(input, 15, resolve, reject);
                    }
                },
                input.entry.id,
                resource);
        });
    }

    static createCuePoint(client, entry, cuePointStartTime, cuePointDuration) {
        return new Promise(function (resolve, reject) {
            let cuePoint = new kalturaClient.objects.KalturaAdCuePoint();
            cuePoint.entryId = entry.id;
            cuePoint.startTime = cuePointStartTime;
            cuePoint.duration = cuePointDuration;
            cuePoint.sourceUrl = "http://dev-backend3.dev.kaltura.com/p/1/testing/getVast";

            client.cuePoint.add(function (results) {
                    if (!results) {
                        reject("No cue point was created");
                    }
                    if (results && results.code && results.message) {
                        TestingHelper.printError('Kaltura Error', results);
                        reject(results);
                    } else {
                        TestingHelper.printOk('Added CuePoint ' + results.id);
                        resolve(results);
                    }
                },
                cuePoint);
        });

    }

    static waitForEntryToBeReady(input, attempts, callback, errorCallback) {
        TestingHelper.printInfo("Waiting for entry: " + input.entry.id + " to be ready... (attempts left - " + attempts + ")");
        if (input.entry.id != null) {
            input.client.baseEntry.get(function (result) {
                    TestingHelper.printStatus("Entry Status is " + result.status);
                    if (result.status == 2) {
                        TestingHelper.printOk("Entry " + input.entry.id + " is ready!");
                        callback(input);
                    } else {
                        if (attempts == 0)
                            errorCallback("Entry is not ready");
                        else {
                            TestingHelper.sleep(15000);
                            TestingHelper.waitForEntryToBeReady(input, attempts - 1, callback, errorCallback);
                        }
                    }
                }
                , input.entry.id);
        } else
            errorCallback("Entry id is null");
    }

    static buildM3U8Url(client, entry) {
        return new Promise(function (resolve, reject) {
            client.flavorAsset.getByEntryId(function (results) {
                    if (results && results.code && results.message) {
                        TestingHelper.printError('Kaltura Error', results);
                        reject(results);
                    } else {
                        console.log('Got FlavorAssests for entry id');
                        let flavor = null;
                        for (let i = 0; i < results.length; i++) {
                            if (!(results[i].tags.indexOf('source') > -1)) {
                                flavor = results[i];
                            }
                        }

                        let m3u8Url = 'http://' + TestingHelper.serverHost + ':82/hls/p/' + TestingHelper.partnerId + '/usePlayServer/1/entryId/' + entry.id + '/flavorIds/' + flavor.id + '/uiConfId/23448255/sessionId/' + uuid.v1() + '/index.m3u8';
                        TestingHelper.printStatus("Build m3u8 Url is: " + m3u8Url);
                        resolve(m3u8Url);

                        //let playManifest = 'http://' + TestingHelper.serverHost + '/p/' + TestingHelper.partnerId + '/sp/10300/playManifest/usePlayServer/1/uiconf/23448262/entryId/' + entry.id + '/flavorIds/' + flavor.id + '/format/applehttp/protocol/http/a.m3u8';
                        //TestingHelper.printStatus("trying to get play manifest " + playManifest);
                        //
                        //new Promise( function (resolve, reject){
                        //    KalturaUtils.getHttpUrl(playManifest, null, function (manifestContent) {
                        //        TestingHelper.printStatus("manifestContent is: " + manifestContent);
                        //        if(resolve){
                        //            let m3u8Url;
                        //            var split = manifestContent.split('\n');
                        //            for (let i = 0 ; i < split.length ; i++)
                        //            if (split[i].trim().startsWith("http"))
                        //                m3u8Url = split[i];
                        //            TestingHelper.printStatus("Build m3u8 Url is: " + m3u8Url);
                        //            resolve(m3u8Url);
                        //        }
                        //    }, function (err) {
                        //        TestingHelper.printStatus("Error getting manifestContent:");
                        //        if(reject){
                        //            reject(err);
                        //        }
                        //    });
                        //}
                        //).then(resolve,reject );
                    }
                },
                entry.id);
        });
    }

    static getFlavorAssetToUse(client, entry) {
        return new Promise(function (resolve, reject) {
            client.flavorAsset.getByEntryId(function (results) {
                    if (results && results.code && results.message) {
                        TestingHelper.printError('Kaltura Error', results);
                        reject(results);
                    } else {
                        console.log('Got FlavorAssests for entry id');
                        let flavor = null;
                        for (let i = 0; i < results.length; i++) {
                            if (!(results[i].tags.indexOf('source') > -1)) {
                                flavor = results[i];
                                resolve(flavor);
                                return;
                            }
                        }
                        reject('No Suitable flavor asset was found for entry ' + entry.id );
                    }
                },
                entry.id);
        });
    }

    static generateThumbsFromM3U8Promise(m3u8Url, videoThumbDir) {
        return new Promise(function (resolve, reject) {
            TestingHelper.printStatus("Generating thumbs from M3U8 url ");
            child_process.exec('ffmpeg -i ' + m3u8Url + ' -vf fps=0.5 -f image2 -r 0.5 -y ' + videoThumbDir + '%d.jpg',
                function (error, stdout, stderr) {
                    if (error !== null) {
                        TestingHelper.printError('Error while generateThumbsFromM3U8Promise: ' + error);
                        reject(error);
                    } else {
                        TestingHelper.printOk('SUCCESS generateThumbsFromM3U8Promise');
                        resolve();
                    }
                });
        });
    }

    static testInvoker(testName, test, input) {
        TestingHelper.printInfo("Starting testing: " + testName);

        test.runTest(input, function () {
            TestingHelper.printInfo("Finished Test: " + testName);
            TestingHelper.printOk('TEST ' + test.constructor.name + ' - SUCCESS');
            process.exit();
        }, function () {
            TestingHelper.printInfo("Finished Test" + testName);
            TestingHelper.printError('TEST ' + test.constructor.name + ' - FAILED');
            process.exit(1);
        });
    }
}

module.exports.TestingHelper = TestingHelper;
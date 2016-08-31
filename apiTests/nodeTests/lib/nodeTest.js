const os = require('os');
const util = require('util');
const fs = require('fs');
const child_process = require('child_process');

const testingHelper = require('./../infra/testingHelper').TestingHelper;

const resourcesPath = KalturaConfig.config.testClient.resourcesPath;

let sessionClient = null;
let cuePointList = [];

class NodeTest {

	runTest(input, callback, errorCallback) {
		sessionClient = input.client;
		let entry;

		testingHelper.createEntry(sessionClient, resourcesPath + "/1MinVideo.mp4")
			.then(function (resultEntry) {
				entry = resultEntry;
				return testingHelper.createCuePoint(sessionClient, entry, 30000, 15000);
			})
			.then(function (cuePoint) {
				cuePointList.push(cuePoint);
				return testingHelper.buildM3U8Url(sessionClient, entry);
			})
			.then(function (m3u8Url) {
				return testingHelper.deleteEntry(sessionClient, entry);
			})
			.then(function (deletedEntry) {
				callback();
			})
			.catch(function(err){
				testingHelper.printError(err);
				errorCallback();
			});
	}

}
module.exports.NodeTest = NodeTest;
const testingHelper = require('./infra/testingHelper').TestingHelper;
const nodeTest = require('./lib/nodeTest');
testingHelper.parseCommandLineOptionsAndRunTest(main);

function main(){

	testingHelper.printInfo("Starting Test for: ");
	testingHelper.printInfo('serverHost: [' + testingHelper.serverHost + '] partnerId: [' +  testingHelper.partnerId + '] adminSecret: [' + testingHelper.adminSecret + ']');
	testingHelper.initClient(testingHelper.serverHost, testingHelper.partnerId, testingHelper.adminSecret, testInit);
}


function testInit(client) {

	let nodeTester = new nodeTest.NodeTest();
	let input = [];
	input.client = client;
	testingHelper.testInvoker("nodeTest", nodeTester , input);

}
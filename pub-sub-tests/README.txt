Pub-sub tests requirements:

1. kaltura server with the following:
    a. the following push notifications defined on the testing partner
       CODE_QNA_NOTIFICATIONS
       PUBLIC_QNA_NOTIFICATIONS
       USER_QNA_NOTIFICATIONS

2. rabbitMq
3. kaltura push-server

please view config/default.json for all specific configurations for testing.

**********************************************
to run tests:
npm-install
mocha test.js


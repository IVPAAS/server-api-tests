
var log4js = require('log4js');

log4js.configure("./config/logger.json")

function decorate(logger, id) {

    if (id) {
        id = "["+id+"] ";
        var loggerEx = {};
        function modify(func) {

            loggerEx[func] = function () {
                if (arguments && arguments.length > 0) {
                    arguments[0] = id + arguments[0];
                }
                return logger[func].apply(logger, arguments);
            };
        }

        modify("debug");
        modify("warn");
        modify("info");
        modify("trace");
        modify("error");
        modify("fatal");

        loggerEx.logger = logger;

        return loggerEx;
    } else {
        return logger;
    }
}

exports.getLogger=function(category,id) {
    var logger=log4js.getLogger("["+category+"] ");
    logger.setLevel('INFO');

    return decorate(logger,id);
};
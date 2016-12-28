var Promise = require("bluebird");
var request = require('request');
var url= require('url');
var log4js = require('log4js');
var njsCrypto = require('crypto');
var child_process = require('child_process');
const querystring = require('querystring');


function KalturaAPI(config) {
  //  this._ks = generateKs(config.secret, config.userId, config.ks_type, config.partnerId);
    this._config=config;
    this._loginPromise = null;
    this._multiRequestPromises=[];
    this._multiRequestParams=null;
    this._ks_expiry=null;
    this._logger = log4js.getLogger('KalturaAPI');
    if (this._ks) {
        this._loginPromise = Promise.resolve(this._ks);
    }
}


KalturaAPI.prototype.call=function(params) {
    var _this=this;

    var now=new Date();
    if (this._ks_expiry && now>this._ks_expiry) {
        this._loginPromise=null;
        this._ks=null;
    }

    if (this._multiRequestParams || this._ks) {
        return _this._kcall(params);
    } else {
        return this.login().then(function () {
            return _this._kcall(params);
        });
    }
}


KalturaAPI.prototype.login = function () {

    var _this=this;



    if (this._loginPromise) {
        return this._loginPromise;
    }

    if (!this._ks) {

        this._loginPromise  = this._kcall({
            service: "session",
            action: "start",
            type: this._config.ks_type,
            userId: this._config.userId,
            secret: this._config.secret,
            partnerId: this._config.partnerId
        },true).then(function (result) {
                _this._ks = result;
                var now=new Date();
                _this._ks_expiry=new Date(now.getTime()+1*60*60*1000);//1 hour
                _this._logger.info("loggedin with user '" + _this._config.userId + "' in with ks=" + result);
                return Promise.resolve(result);
            },
            function (res) {
                return Promise.reject(res);
            });
    }
    else {
        this._loginPromise = Promise.resolve(this.ks);
    }

    return this._loginPromise;
}


KalturaAPI.prototype._kcall=function(params,ingoreMR) {

    var _this=this;
    //we chain the requests in a multi-request calls
    if (this._multiRequestParams && !ingoreMR) {

        var  multiRequestCount=this._multiRequestPromises.length+1;
        for(var propertyName in params) {
            Object.defineProperty(this._multiRequestParams, multiRequestCount+":"+propertyName,
                Object.getOwnPropertyDescriptor(params, propertyName));
        }
        return  new Promise(function (resolve, reject) {
            _this._multiRequestPromises.push({ success: success, failure:reject});
        });
    }




    var startTime=new Date();

    params.format = 1; //return JSON
    if (this._ks)
        params.ks = this._ks;




    return new Promise( function(resolve,reject) {

        request.post({
            url: _this._config.serverAddress+ '/api_v3/index.php',
            json: true,
            body: params,
            timeout: 20*1000,
        }, function (error, response, result) {



            if (error || (result && result.objectType==="KalturaAPIException")) {
                return reject( error || result );
            }
            return resolve( result );
        });


    });


}

KalturaAPI.prototype.startMultirequest=function () {
    this._multiRequestPromises=[];
    this._multiRequestParams = { service: "multirequest", action: null };
}

KalturaAPI.prototype.execMultirequest=function() {

    var _this=this;

    var  doCall=function(params,oldMultiRequestPromises) {


        return _this._kcall(params).then(function (result) {

            for (var i = 0; i < result.length; i++) {

                if (result[i] && result[i].code) {
                    //klog.warn("Error from multirequest #{0} (params={1}) message={2}",i,JSON.stringify(params), JSON.stringify(result[i]));
                    return Promise.reject(result);
                }

                oldMultiRequestPromises[i].success(result[i]);
            }
            return Promise.resolve(result);
        });
    }


    var params=_this._multiRequestParams;
    var oldMultiRequestPromises=_this._multiRequestPromises;
    _this._multiRequestParams=null;
    _this._multiRequestPromises=[];

    if (_this._ks) {
        return doCall(params,oldMultiRequestPromises);
    } else {
        return this.login().then(function () {
            return doCall(params,oldMultiRequestPromises);
        });
    }

}



function generateKs(secret, userId, type, partnerId ,expiry,privileges="") {

    function hash(buf) {
        var sha1 = njsCrypto.createHash('sha1');
        sha1.update(buf);
        return sha1.digest();
    }

    // build fields array
    let fields = {};
    fields._e = expiry ? expiry : Math.round(new Date().getTime()/1000) +  86000;
    fields._t = type;
    fields._u = userId;
    privileges.split(',').forEach( privilege => {
        privilege = privilege.trim()
        if (privilege.length === 0) {
            return;
        }
        if (privilege === '*') {
            privilege = 'all:*';
        }
        let splittedPrivilege = privilege.split(':');
        if (splittedPrivilege.length > 1) {
            fields[splittedPrivilege[0]] = splittedPrivilege[1];
        }
        else {
            fields[splittedPrivilege[0]] = '';
        }
    });


    let fieldsStr = querystring.stringify(fields);

    let fieldsBuf = Buffer.from(fieldsStr);

    let rnd = Buffer.from(njsCrypto.randomBytes(16));

    fieldsBuf = Buffer.concat([rnd,fieldsBuf]);

    let sha1Buf = hash (fieldsBuf);

    let message = Buffer.concat([sha1Buf, fieldsBuf]);

    if(message.length % 16) {
        var padding =  Buffer.alloc(16 - message.length % 16,0,'binary');
        message = Buffer.concat( [message, padding]);
    }

    let iv =  Buffer.alloc(16,0,'binary');
    let key = hash(secret).slice(0, 16);
    let cipher = njsCrypto.createCipheriv("aes-128-cbc", key, iv);

    cipher.setAutoPadding(false);


    let ciphertext = cipher.update(message);

    let header = 'v2|'+ partnerId +'|';
    let $decodedKs = Buffer.concat([Buffer.from(header), Buffer.from(ciphertext)]).toString('base64');


    $decodedKs = $decodedKs.split('+').join('-').split('/').join('_');

    return $decodedKs;
}

module.exports=KalturaAPI;

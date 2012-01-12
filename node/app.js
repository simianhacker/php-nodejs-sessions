
/**
 * Module dependencies.
 */

var express    = require('express')
  , routes     = require('./routes')
  , httpProxy  = require('http-proxy')
  , proxy      = new httpProxy.RoutingProxy()
  , redisStore = require('connect-redis')(express)

var app = module.exports = express.createServer();

// Configuration

app.configure(function(){
  app.set('views', __dirname + '/views');
  app.set('view engine', 'jade');
});

app.configure('development', function(){
  app.use(express.errorHandler({ dumpExceptions: true, showStack: true })); 
});

app.configure('production', function(){
  app.use(express.errorHandler()); 
});

var middleware = [
    express.bodyParser()
  , express.methodOverride()
  , express.cookieParser()
  , express.session({ secret: 'your-secret-here', key:'session_id', store: new redisStore() })
  ];

// Routes

app.get('/node', middleware, function (req, res) {
  req.session.node = (req.session.node)? req.session.node+1 : 1;
  res.send({ session: req.session }, 200);
});

app.all('*', function (req, res) {
  proxy.proxyRequest(req, res, {
    host: 'localhost',
    port: 8888
  }); 
});

app.listen(3000);
console.log("Express server listening on port %d in %s mode", app.address().port, app.settings.env);

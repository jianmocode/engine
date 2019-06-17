var host = "coderstore.vpin.biz"

config = {
      mina: {
            target: false,
            priority: 99,
            server: "https://" + host,
            domain: host,
            project: "demo",
            appid: '152389239297374',
            secret: '500970dad3324330f8a1085939f20360',
            instance: "root",
      },
      wxapp: {
            "cli": "/Applications/wechatwebdevtools.app/Contents/Resources/app.nw/bin/cli",
            "appdid": "wx671a14fe272173d1",
            "secret": "e22fa0aae8304350fe4fa1e0c443b382"
      }
};
module.exports = config
var host = "dev.vpin.biz"

config = {
      mina: {
            target: false,
            priority: 99,
            server: "https://" + host,
            domain: host,
            project: "demo",
            appid: '156032579346487',
            secret: 'cec9b4f713a48373d9d037d9fdeb2d68',
            instance: "root",
      },
      wxapp: {
            "cli": "/Applications/wechatwebdevtools.app/Contents/Resources/app.nw/bin/cli",
            "appdid": "wx671a14fe272173d1",
            "secret": "e22fa0aae8304350fe4fa1e0c443b382"
      }
};
module.exports = config
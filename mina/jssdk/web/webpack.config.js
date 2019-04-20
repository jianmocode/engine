var path = require('path');
var dist = path.resolve(__dirname + '/..');

module.exports = {
  entry: {
    'Web': ['./web.js'],
    'Page': ['./page.js'],
    'getWeb': ['./getweb.js']
  },
  output: {
    path: dist,
    filename: "[name].dev.js",
    library: '[name]',
    libraryTarget: 'umd'
  },
  resolve: {},
  module: {
    rules: [{
      test: /\.js$/,
      use: [{
        loader: 'babel-loader',
        query: {
            presets: [ "babel-preset-es2015" ].map(require.resolve)
        }
        // options: {
        //   presets: ['es2015']
        // }
      }],
    }]
  }
}

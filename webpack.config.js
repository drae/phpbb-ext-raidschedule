/**
 * 
 * Webpack config for raid schedule extension
 * 
 */
const path = require('path')

module.exports = {
    entry: './src/js/signup.index.js',
    output: {
        path: path.resolve(__dirname, 'dist/styles/aquila/js/'),
        filename: 'signup.index.js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader"
                }
            }
        ]
    }
}
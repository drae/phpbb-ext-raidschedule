/**
 *
 * Webpack config for raid schedule extension
 *
 */
'use strict'

const path = require('path')
const webpack = require('webpack');
const MiniCssExtractPlugin = require("mini-css-extract-plugin")
const HtmlWebPackPlugin = require("html-webpack-plugin")
const CleanWebpackPlugin = require('clean-webpack-plugin')
const CrittersPlugin = require('critters-webpack-plugin')

const devMode = process.env.NODE_ENV !== 'production'

module.exports = {
    entry: './src/signup.index.js',
    output: {
        path: path.resolve(__dirname, 'dist/styles/aquila/'),
        filename: 'js/signup.index.js'
    },
	devtool: 'inline-source-map',
    module: {
        rules: [
			{
				test: /\.vue$/,
				loader: 'vue-loader'
			},
            {
                test: /\.js|\.jsx$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader"
                }
            },
			{
				test: /\.s?[ac]ss$/,
				use: [
					devMode ? 'style-loader' : MiniCssExtractPlugin.loader,
					'css-loader',
					'postcss-loader',
					'sass-loader',
				],
			},
        ]
    },
	plugins: [
		new CleanWebpackPlugin([ './dist/styles/aquila' ]),
		new MiniCssExtractPlugin({
			filename: "css/signup.css",
		}),
		new HtmlWebPackPlugin({
			template: "./src/list_players.html",
			filename: "template/list_players.html",
			inject: false,
		}),
		new CrittersPlugin({
			preload: 'swap',
			preloadFonts: true
		}),
		// enable scope hoisting
		new webpack.optimize.ModuleConcatenationPlugin(),
	]
}

if (process.env.NODE_ENV === 'production') {
	module.exports.devtool = '#source-map'
	// http://vue-loader.vuejs.org/en/workflow/production.html
	module.exports.plugins = (module.exports.plugins || []).concat([
		new webpack.DefinePlugin({
			'process.env': {
				NODE_ENV: '"production"'
			}
		}),
		new webpack.optimize.UglifyJsPlugin({
			sourceMap: true,
			compress: {
				warnings: false
			}
		}),
		new webpack.LoaderOptionsPlugin({
			minimize: true
		})
	])
}

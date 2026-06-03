const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

module.exports = {
	...defaultConfig,
	plugins: [
		...defaultConfig.plugins,
		new CopyWebpackPlugin({
			patterns: [
				{
					from: path.resolve(__dirname, 'blocks/pdf-viewer/block.json'),
					to:   path.resolve(__dirname, 'build/block.json'),
				},
				{
					from: path.resolve(__dirname, 'blocks/pdf-viewer/editor.css'),
					to:   path.resolve(__dirname, 'build/editor.css'),
				},
				{
					from: path.resolve(__dirname, 'blocks/pdf-viewer/style.css'),
					to:   path.resolve(__dirname, 'build/style.css'),
				},
			],
		}),
	],
};

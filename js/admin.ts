// Flarum 2 applies the extenders an extension exports as `extend`
// (Application.bootExtensions reads module.exports.extend); no app.extend() call.
export { default as extend } from './src/extend';

import { Admin } from 'flarum/common/extenders';

export default [
  new Admin()
    .setting(() => ({
      setting: 'ernestdefoe-og-image.default_image',
      type: 'text',
      label: 'Default OG Image URL',
      help: 'Fallback image used when a discussion has no images. Must be an absolute URL. Facebook recommends at least 1200 × 630 px.',
      placeholder: 'https://example.com/images/og-default.jpg',
    }))
    .setting(() => ({
      setting: 'ernestdefoe-og-image.fb_app_id',
      type: 'text',
      label: 'Facebook App ID (optional)',
      help: 'Your Facebook App ID. Adds fb:app_id to every page and removes the "missing fb:app_id" warning in the Facebook Sharing Debugger.',
      placeholder: '123456789012345',
    })),
];

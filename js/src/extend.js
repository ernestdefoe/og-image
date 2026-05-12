import app from 'flarum/admin/app';
import { Admin } from 'flarum/common/extenders';

export default [
  new Admin()
    .setting(() => ({
      setting: 'ernestdefoe-og-image.default_image',
      type: 'text',
      label: app.translator.trans('ernestdefoe-og-image.admin.settings.default_image_label'),
      help: app.translator.trans('ernestdefoe-og-image.admin.settings.default_image_help'),
      placeholder: app.translator.trans('ernestdefoe-og-image.admin.settings.default_image_placeholder'),
    }))
    .setting(() => ({
      setting: 'ernestdefoe-og-image.fb_app_id',
      type: 'text',
      label: app.translator.trans('ernestdefoe-og-image.admin.settings.fb_app_id_label'),
      help: app.translator.trans('ernestdefoe-og-image.admin.settings.fb_app_id_help'),
      placeholder: app.translator.trans('ernestdefoe-og-image.admin.settings.fb_app_id_placeholder'),
    })),
];

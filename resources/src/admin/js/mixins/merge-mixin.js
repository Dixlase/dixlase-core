/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * @api プラグイン/テーマから window.Dixlase.mixins.mergeMixins として使用可能
 *
 * Additional permission under GNU AGPL version 3 section 7:
 * Dixlase plugins and themes may use this file's exported functions via the
 * window.Dixlase.mixins runtime API without being subject to the copyleft
 * requirements of the AGPL. Direct import into plugin build bundles is NOT
 * covered by this exception.
 *
 * ミックスインマージユーティリティ
 * Object.assign / spread では getter（Alpine.js computed）がコピーされないため、
 * Object.getOwnPropertyDescriptors を使用して getter を正しく転送する。
 */

/**
 * 複数のオブジェクトを getter を含めてマージする
 * Alpine.js の get プロパティ（computed）を正しくコピーするために使用。
 *
 * @param {...object} sources - マージするオブジェクト群
 * @returns {object} マージされたオブジェクト
 *
 * @example
 * return mergeMixins(
 *     splitPaneMixin(),
 *     previewMixin(config),
 *     { myProp: 'value', get myComputed() { return this.myProp + '!'; } }
 * );
 */
/* eslint-disable no-new-func */
/**
 * Rollup/Vite のインライン最適化で spread に変換されるのを防ぐため、
 * 間接参照で Object.defineProperties を呼び出す
 */
const _defineProps = Object.defineProperties;
const _getDescriptors = Object.getOwnPropertyDescriptors;

export function mergeMixins(...sources) {
    const target = Object.create(null);
    for (let i = 0; i < sources.length; i++) {
        _defineProps(target, _getDescriptors(sources[i]));
    }
    // プロトタイプを復元（Alpine.js が必要とする）
    Object.setPrototypeOf(target, Object.prototype);
    return target;
}

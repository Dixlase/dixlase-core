# Alpine.js CSP互換コーディングルール

## 目次

1. [目的と背景](#目的と背景)
2. [早見表](#早見表)
3. [ルール詳細](#ルール詳細)
4. [JSファイル規約](#jsファイル規約)
5. [既存パターンとの対応表](#既存パターンとの対応表)
6. [コードレビューチェックリスト](#コードレビューチェックリスト)

---

## 目的と背景

### なぜこのルールが必要か

DixlaseはAlpine.jsを使用しているため、CSP（Content Security Policy）で`unsafe-eval`を許可する必要があります。これはAlpine.jsが内部的に`new Function()`でテンプレート式を評価するためです。

将来的にAlpine CSP Build（`@alpinejs/csp`）に移行し、`unsafe-eval`を排除して厳格モードを実現する計画があります。

**このルールの目的は、新規コードをCSP Build互換パターンで書くことで、将来の移行コストを最小化することです。** 通常版のAlpine.jsでもCSP互換パターンは問題なく動作するため、今すぐ適用できます。

### Alpine CSP Buildの制限事項

Alpine CSP Buildでは、テンプレート内のJavaScript式の評価に`new Function()`を使用しません。そのため、以下が使えません：

- インラインのオブジェクトリテラル（`x-data="{ open: false }"`）
- `x-model`ディレクティブ
- `x-html`ディレクティブ
- 引数付きメソッド呼び出し（`@click="doSomething('arg')"`）
- 複雑なJavaScript式（三項演算子、テンプレートリテラル、代入式等）
- グローバル変数・関数へのアクセス（`window`, `document`, `console`等）
- `$store`のテンプレート内直接アクセス

### 適用範囲

- **新規作成するすべてのBladeテンプレートとJavaScriptファイル**
- 既存コードの修正時は、**変更するファイル内で新しく書くコードのみ**が対象（既存部分の全面書き換えは不要）

---

## 早見表

### テンプレート（Blade）のパターン

| パターン | NG | OK |
|---------|----|----|
| **x-data** | `x-data="{ open: false }"` | `x-data="myComponent"` |
| **@click（メソッド）** | `@click="toggle()"` | `@click="toggle"` |
| **@click（引数）** | `@click="openModal('id')"` | `@click="openModal" data-modal-target="id"` |
| **@click（代入）** | `@click="open = !open"` | `@click="toggle"` |
| **x-model** | `x-model="name"` | `:value="name" @input="setName"` |
| **x-text（式）** | `x-text="count + ' items'"` | `x-text="itemCountText"` |
| **x-text（三項）** | `x-text="ok ? 'Yes' : 'No'"` | `x-text="statusText"` |
| **x-show（否定）** | `x-show="!open"` | `x-show="isClosed"` |
| **:class（オブジェクト）** | `:class="{ 'active': isActive }"` | `:class="activeClass"` |
| **$store** | `x-text="$store.notification.message"` | `x-text="message"`（getter経由） |
| **x-html** | `x-html="htmlContent"` | サーバーサイドレンダリング使用 |
| **グローバル変数** | `@click="window.location.reload()"` | `@click="reload"` |

### JavaScript側のパターン

| パターン | NG（将来の移行で問題） | OK |
|---------|----|----|
| **コンポーネント定義** | `window.func = function() {}` | `Alpine.data('name', () => ({}))` |
| **引数受け渡し** | `window.func = function(arg) {}` | `data-*`属性 + `init()` で読み取り |
| **算出プロパティ** | テンプレート内に式を書く | `get propName() { return ... }` |

---

## ルール詳細

### ルール1: `x-data` — `Alpine.data()`で登録する

#### NG: インラインオブジェクトリテラル

```html
<div x-data="{ open: false, count: 0 }">
    ...
</div>
```

#### OK: `Alpine.data()`への参照

```html
<div x-data="togglePanel">
    ...
</div>
```

```javascript
// resources/src/components/js/toggle-panel.js
document.addEventListener('alpine:init', () => {
    Alpine.data('togglePanel', () => ({
        open: false,
        count: 0,

        toggle() {
            this.open = !this.open;
        },
    }));
});
```

#### 初期値をサーバーから渡す場合

```html
<!-- data-*属性で初期値を渡す -->
<div x-data="emailInput"
     data-original-email="{{ $user->email }}"
     data-show-confirmation="{{ $showConfirmation ? 'true' : 'false' }}">
    ...
</div>
```

```javascript
Alpine.data('emailInput', () => ({
    email: '',
    showConfirmation: false,

    init() {
        this.email = this.$el.dataset.originalEmail || '';
        this.showConfirmation = this.$el.dataset.showConfirmation === 'true';
    },
}));
```

#### JSON形式で複雑な初期データを渡す場合

```html
<div x-data="chartWidget">
    <script type="application/json" data-config>
        {!! json_encode(['labels' => $labels, 'values' => $values]) !!}
    </script>
    ...
</div>
```

```javascript
Alpine.data('chartWidget', () => ({
    labels: [],
    values: [],

    init() {
        const configEl = this.$el.querySelector('script[data-config]');
        if (configEl) {
            const config = JSON.parse(configEl.textContent);
            this.labels = config.labels;
            this.values = config.values;
        }
    },
}));
```

---

### ルール2: イベントハンドラ — メソッド参照のみ使用する

#### NG: 引数付きメソッド呼び出し

```html
<button @click="openModal('deleteModal')">削除</button>
<button @click="selectTab('settings')">設定</button>
```

#### OK: `data-*`属性でデータを渡す

```html
<button @click="openModal" data-modal-target="deleteModal">削除</button>
<button @click="selectTab" data-tab="settings">設定</button>
```

```javascript
Alpine.data('myComponent', () => ({
    openModal(event) {
        const modalId = event.currentTarget.dataset.modalTarget;
        window.openModal(modalId);
    },

    selectTab(event) {
        this.activeTab = event.currentTarget.dataset.tab;
    },
}));
```

#### NG: テンプレート内での代入・式

```html
<button @click="open = !open">トグル</button>
<button @click="count++">カウント</button>
<button @click="if (!disabled) save()">保存</button>
```

#### OK: メソッド参照

```html
<button @click="toggle">トグル</button>
<button @click="increment">カウント</button>
<button @click="saveIfEnabled">保存</button>
```

```javascript
Alpine.data('myComponent', () => ({
    open: false,
    count: 0,
    disabled: false,

    toggle() {
        this.open = !this.open;
    },

    increment() {
        this.count++;
    },

    saveIfEnabled() {
        if (!this.disabled) {
            this.save();
        }
    },
}));
```

#### NG: グローバル関数の直接呼び出し

```html
<button @click="window.location.reload()">リロード</button>
<button @click="navigator.clipboard.writeText(url)">コピー</button>
```

#### OK: メソッド経由

```html
<button @click="reload">リロード</button>
<button @click="copyUrl" data-url="{{ $url }}">コピー</button>
```

```javascript
Alpine.data('myComponent', () => ({
    reload() {
        window.location.reload();
    },

    copyUrl(event) {
        const url = event.currentTarget.dataset.url;
        navigator.clipboard.writeText(url);
    },
}));
```

---

### ルール3: `x-model` — `:value` + `@input`で代替する

#### NG: `x-model`

```html
<input type="text" x-model="name">
<textarea x-model="content"></textarea>
<select x-model="country">...</select>
<input type="checkbox" x-model="agreed">
```

#### OK: `:value` + `@input`（`:checked` + `@change`）

```html
<input type="text" :value="name" @input="setName">
<textarea :value="content" @input="setContent"></textarea>
<select :value="country" @change="setCountry">...</select>
<input type="checkbox" :checked="agreed" @change="toggleAgreed">
```

```javascript
Alpine.data('myForm', () => ({
    name: '',
    content: '',
    country: '',
    agreed: false,

    setName(event) {
        this.name = event.target.value;
    },

    setContent(event) {
        this.content = event.target.value;
    },

    setCountry(event) {
        this.country = event.target.value;
    },

    toggleAgreed(event) {
        this.agreed = event.target.checked;
    },
}));
```

#### 汎用セッターパターン

フォームフィールドが多い場合、汎用的なセッターメソッドを使用できます：

```javascript
Alpine.data('myForm', () => ({
    name: '',
    email: '',
    phone: '',

    /**
     * data-field属性で指定されたプロパティにinput値をセット
     */
    setField(event) {
        const field = event.target.dataset.field || event.target.name;
        if (field && field in this) {
            this[field] = event.target.value;
        }
    },
}));
```

```html
<input :value="name" @input="setField" data-field="name">
<input :value="email" @input="setField" data-field="email">
<input :value="phone" @input="setField" data-field="phone">
```

---

### ルール4: `x-text` / `x-show` / `x-bind` — プロパティ参照またはgetterを使う

#### NG: テンプレート内の式

```html
<span x-text="count + ' items'"></span>
<span x-text="saving ? '保存中...' : '保存'"></span>
<div x-show="items.length > 0"></div>
<div x-show="!open"></div>
```

#### OK: プロパティ参照 or getter

```html
<span x-text="itemCountText"></span>
<span x-text="saveButtonText"></span>
<div x-show="hasItems"></div>
<div x-show="isClosed"></div>
```

```javascript
Alpine.data('myComponent', () => ({
    count: 0,
    saving: false,
    items: [],
    open: false,

    get itemCountText() {
        return this.count + ' items';
    },

    get saveButtonText() {
        return this.saving ? '保存中...' : '保存';
    },

    get hasItems() {
        return this.items.length > 0;
    },

    get isClosed() {
        return !this.open;
    },
}));
```

#### 単純なプロパティ参照はそのままOK

```html
<!-- これはCSP Buildでも問題なく動作する -->
<span x-text="message"></span>
<div x-show="open"></div>
<input :disabled="loading">
```

---

### ルール5: `:class` — getterで動的クラスを返す

#### NG: オブジェクト構文

```html
<div :class="{ 'bg-blue-500': isActive, 'bg-gray-300': !isActive, 'opacity-50': disabled }"></div>
```

#### OK: getterメソッドで文字列を返す

```html
<div :class="stateClass"></div>
```

```javascript
Alpine.data('myComponent', () => ({
    isActive: false,
    disabled: false,

    get stateClass() {
        const classes = [];
        classes.push(this.isActive ? 'bg-blue-500' : 'bg-gray-300');
        if (this.disabled) {
            classes.push('opacity-50');
        }
        return classes.join(' ');
    },
}));
```

---

### ルール6: `$store` — `Alpine.data()`ラッパー経由でアクセスする

#### NG: テンプレートから`$store`を直接参照

```html
<div x-data x-show="$store.notification.show">
    <span x-text="$store.notification.message"></span>
    <button @click="$store.notification.hide()">閉じる</button>
</div>
```

#### OK: `Alpine.data()`ラッパーを作成

```html
<div x-data="notificationDisplay" x-show="show">
    <span x-text="message"></span>
    <button @click="hide">閉じる</button>
</div>
```

```javascript
Alpine.data('notificationDisplay', () => ({
    get show() {
        return Alpine.store('notification').show;
    },

    get message() {
        return Alpine.store('notification').message;
    },

    hide() {
        Alpine.store('notification').hide();
    },
}));
```

---

### ルール7: `x-html` — 使用禁止

`x-html`はCSP Buildで完全に非対応です。代替手段を使用してください。

#### NG: `x-html`

```html
<div x-html="htmlContent"></div>
<div x-html="marked.parse(markdown)"></div>
```

#### OK: 代替手段

**方法A: サーバーサイドレンダリング（推奨）**

Bladeの`{!! !!}`やLivewireコンポーネントでHTMLを出力する。

```html
<!-- Blade -->
{!! $htmlContent !!}

<!-- Livewire -->
<livewire:markdown-preview :content="$content" />
```

**方法B: メソッド内でDOM操作**

```javascript
Alpine.data('markdownPreview', () => ({
    init() {
        this.$watch('content', (value) => {
            this.renderMarkdown(value);
        });
    },

    renderMarkdown(value) {
        const previewEl = this.$refs.preview;
        if (previewEl && typeof marked !== 'undefined') {
            previewEl.innerHTML = marked.parse(value || '');
        }
    },
}));
```

```html
<div x-data="markdownPreview">
    <div x-ref="preview"></div>
</div>
```

---

### ルール8: グローバル変数 — テンプレート式内で直接使用しない

#### NG: テンプレート内でグローバルオブジェクトにアクセス

```html
<span x-text="document.title"></span>
<span x-text="Math.round(percentage)"></span>
<span x-text="JSON.stringify(data)"></span>
<button @click="console.log('debug')">Debug</button>
```

#### OK: JavaScript側のメソッド/getterで処理する

```html
<span x-text="pageTitle"></span>
<span x-text="roundedPercentage"></span>
```

```javascript
Alpine.data('myComponent', () => ({
    percentage: 0,

    get pageTitle() {
        return document.title;
    },

    get roundedPercentage() {
        return Math.round(this.percentage);
    },
}));
```

---

### ルール9: データ受け渡し — `data-*`属性 or JSON script方式

サーバーからAlpineコンポーネントにデータを渡す方法。

#### 方法A: `data-*`属性（シンプルな値向け）

```html
<div x-data="userCard"
     data-user-id="{{ $user->id }}"
     data-user-name="{{ $user->name }}"
     data-is-admin="{{ $user->isAdmin() ? 'true' : 'false' }}">
</div>
```

```javascript
Alpine.data('userCard', () => ({
    userId: null,
    userName: '',
    isAdmin: false,

    init() {
        this.userId = this.$el.dataset.userId;
        this.userName = this.$el.dataset.userName;
        this.isAdmin = this.$el.dataset.isAdmin === 'true';
    },
}));
```

#### 方法B: JSON script方式（複雑なデータ向け）

```html
<div x-data="dataTable">
    <script type="application/json" data-initial>
        {!! json_encode([
            'columns' => $columns,
            'rows' => $rows,
            'options' => $options,
        ]) !!}
    </script>
    ...
</div>
```

```javascript
Alpine.data('dataTable', () => ({
    columns: [],
    rows: [],
    options: {},

    init() {
        const dataEl = this.$el.querySelector('script[data-initial]');
        if (dataEl) {
            const data = JSON.parse(dataEl.textContent);
            Object.assign(this, data);
        }
    },
}));
```

#### 翻訳文字列の受け渡し

```html
<div x-data="myComponent"
     data-translations='@json([
         "confirm" => __("common.confirm"),
         "cancel" => __("common.cancel"),
     ])'>
</div>
```

```javascript
Alpine.data('myComponent', () => ({
    translations: {},

    init() {
        this.translations = JSON.parse(this.$el.dataset.translations || '{}');
    },

    /**
     * 翻訳文字列を取得
     */
    t(key) {
        return this.translations[key] || key;
    },
}));
```

---

## JSファイル規約

### ファイル構成

新しいAlpineコンポーネントのJavaScriptファイルは、以下の構成に従います。

```
resources/src/
├── components/js/          # 共通コンポーネント
│   ├── ui-modal.js
│   ├── form-email.js
│   └── new-component.js    # ← 新規はここに
├── admin/js/               # 管理画面固有
├── install/js/             # インストーラー固有
└── common/js/
    └── app.js              # メインエントリ（ここでimport）
```

### 標準テンプレート

```javascript
/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * [ライセンスヘッダー省略]
 */

/**
 * ComponentName - コンポーネントの説明
 *
 * Usage in Blade:
 * <div x-data="componentName"
 *      data-initial-value="{{ $value }}">
 *     <button @click="doAction">Action</button>
 * </div>
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('componentName', () => ({
        // ===== データプロパティ =====
        value: '',
        loading: false,

        // ===== 算出プロパティ（getter） =====
        get isEmpty() {
            return this.value === '';
        },

        get displayValue() {
            return this.value || 'デフォルト値';
        },

        // ===== ライフサイクル =====
        init() {
            // data-*属性から初期値を読み取り
            this.value = this.$el.dataset.initialValue || '';
        },

        // ===== イベントハンドラ =====
        doAction() {
            // ボタンクリック時の処理
        },

        // ===== 内部メソッド =====
        _internalHelper() {
            // プレフィックス _ で内部メソッドを示す（任意）
        },
    }));
});
```

### `app.js`への登録

```javascript
// resources/src/common/js/app.js にimportを追加
import '../../components/js/new-component';
```

### 命名規約

| 対象 | 命名規約 | 例 |
|------|---------|-----|
| Alpine.data()の名前 | camelCase | `emailInput`, `togglePanel`, `menuEditor` |
| データプロパティ | camelCase | `showPassword`, `isLoading` |
| メソッド | camelCase、動詞始まり | `toggle`, `handleClick`, `saveForm` |
| getter | camelCase、名詞/形容詞 | `isEmpty`, `displayText`, `activeClass` |
| イベントハンドラ（data-*経由） | camelCase、動詞始まり | `openModal`, `selectTab` |
| JSファイル名 | kebab-case | `form-email.js`, `ui-modal.js` |
| data-*属性 | kebab-case | `data-modal-target`, `data-user-id` |

---

## 既存パターンとの対応表

現在のDixlaseコードベースでは2つのパターンが混在しています。新規コードでは`Alpine.data()`パターンを使用してください。

### コンポーネント登録

| 現行（window関数）| 新規推奨（Alpine.data） |
|---|---|
| `window.modal = function() { return {...} }` | `Alpine.data('modal', () => ({...}))` |
| `x-data="modal()"` | `x-data="modal"` |

### 引数付きコンストラクタ

| 現行 | 新規推奨 |
|---|---|
| `window.emailInput = function(config) { return {...} }` | `Alpine.data('emailInput', () => ({...}))` |
| `x-data="emailInput({ email: '...' })"` | `x-data="emailInput" data-email="..."` |
| `config.email` でアクセス | `this.$el.dataset.email` でアクセス |

### グローバルヘルパー関数

グローバル関数（`openModal()`, `showSuccess()`等）は後方互換性のために残しますが、Alpine.data()コンポーネント内からのみ呼び出します。

| 現行 | 新規推奨 |
|---|---|
| `@click="openModal('deleteModal')"` | `@click="openModal" data-modal-target="deleteModal"` |
| `@click="showSuccess('保存しました')"` | メソッド内で`window.showSuccess('保存しました')`を呼ぶ |

---

## コードレビューチェックリスト

新規コードや既存コードの変更をレビューする際の確認項目です。

### 必須（MUST）

- [ ] `x-data`にインラインオブジェクトリテラル（`x-data="{ ... }"`）を使用していない
- [ ] `x-model`を使用していない（`:value` + `@input`を使用）
- [ ] `x-html`を使用していない
- [ ] `@click`等のイベントハンドラに引数付きメソッド呼び出しがない
- [ ] `@click`等に代入式（`open = !open`、`count++`）がない
- [ ] テンプレート式内でグローバル変数（`window`, `document`, `console`等）にアクセスしていない
- [ ] `$store`をテンプレートから直接参照していない
- [ ] Alpineコンポーネントは`Alpine.data()`で登録されている
- [ ] サーバーデータの受け渡しは`data-*`属性 or JSON script方式を使用

### 推奨（SHOULD）

- [ ] 複雑な式はgetter（算出プロパティ）で定義している
- [ ] `:class`のオブジェクト構文ではなくgetterで文字列を返している
- [ ] JSファイルは標準テンプレートに従っている
- [ ] `app.js`に新しいコンポーネントのimportが追加されている
- [ ] JSDocコメントでUsageが記載されている

---

## 参考リンク

- [Alpine.js CSP Build 公式ドキュメント](https://alpinejs.dev/advanced/csp)
- [Dixlase CSP完全ガイド](../settings/security/csp-guide.md)
- [Hyva Alpine CSP ガイド](https://docs.hyva.io/hyva-themes/writing-code/csp/alpine-csp.html)

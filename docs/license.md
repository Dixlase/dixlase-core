# License

Dixlase is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)**.

## What this means

- You are free to use, modify, and distribute Dixlase
- If you modify Dixlase and provide it as a network service, you must make your modified source code available
- All derivative works must also be licensed under AGPL-3.0
- You must retain the original copyright notices

## Plugin and Theme Exception

As a special exception under AGPL v3 Section 7, plugins and themes that interact with Dixlase CMS **exclusively through the Plugin API** are **not** considered derivative works. This means you may distribute them under any license of your choice, including proprietary licenses.

This exception applies when **all** of the following conditions are met:

1. The plugin/theme communicates with Dixlase CMS only through the interfaces defined in `PLUGIN-API.md`
2. The plugin/theme does not modify, replace, or monkey-patch any core source file
3. The plugin/theme does not bypass or replicate internal core implementations
4. The plugin/theme is loaded through the standard loading mechanism (`PluginLoaderTrait` / `ThemeLoaderTrait`) and resides in the `plugins/` or `themes/` directory

If any of these conditions are not met, the plugin or theme is considered a derivative work and is subject to the full AGPL-3.0 terms.

For details, see the `LICENSE` file (Section "Dixlase Plugin and Theme Exception") and `PLUGIN-API.md` distributed with the source code.

## Third-Party Licenses

Each plugin and theme may specify its own license in its `plugin.json` or `theme.json` file. Check the individual license for each extension you install.

## Full License Text

The full text of the AGPL-3.0 license is available at:
[https://www.gnu.org/licenses/agpl-3.0.html](https://www.gnu.org/licenses/agpl-3.0.html)

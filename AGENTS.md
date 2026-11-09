# EVOLVE WordPress plugin rules (for AI coding tools and developers)

Every PR (GitHub) and MR (GitLab) runs the EVOLVE QA pipeline. **If one check fails, the code cannot be merged.**
Write code that passes it the first time, and prove it with `bash wp-qa-check.sh` before you push.

## 0. Workflow

1. `git checkout main && git pull origin main` (use `master` if that is the default branch).
2. `git checkout -b feature/<short-name>` and work there. Never commit to main/master.
3. Before every push: `bash wp-qa-check.sh` (add `--fix` to auto-fix formatting). It must end with `PASS`.
4. Push the branch and open a PR/MR into main/master. Do not merge red pipelines.

Do **not** edit to make checks pass: `.github/`, `.gitlab-ci.yml`, `phpcs.xml*`, `composer.json` tool versions, `vendor/`, `lib/` third-party code.
Do **not** add `phpcs:disable` or file-level ignores. A single-line `// phpcs:ignore <Sniff.Code> -- <real reason>` is allowed only when the code is correct and the rule cannot know it.

## 1. What the pipeline checks (exactly)

| Job | Rule | Blocks merge |
|---|---|---|
| PHP syntax | `php -l` on every PHP file, on every PHP version from the plugin's `Requires PHP` (default 7.4) up to 8.4 | yes |
| PHPCS | `WordPress` standard (WPCS 3.x) with the exclusions below, plus EvolveQA | errors yes, warnings no |

PHPCS checks every PHP file **changed** in the PR/MR, and the **whole** file, not only your lines. If you touch an old file, fix its existing errors too (without changing behaviour).

**Switched off (you may ignore):** all docblock/comment rules (`Squiz.Commenting`, `Generic.Commenting.DocComment`), Yoda conditions, translators comments, `WordPress` capital-P spelling, short ternary `?:`, `$i++`, lonely `if` inside `else`, functions+classes in one file, `return;` at the end of a function.

## 2. PHP syntax: write for the lowest supported PHP

Check `Requires PHP:` in the main plugin file. If it is 7.4, do **not** use PHP 8 syntax: no `match`, no `?->`, no named arguments, no union/`mixed`/`static` return types, no constructor property promotion, no `enum`, no `readonly`, no `never`, no `new` in initializers, no first-class callables `foo(...)`, no `#[Attributes]` that break 7.4 parsing.

## 3. Formatting (errors)

- Indent with **tabs**. Spaces inside parentheses: `foo( $a, $b )`, `if ( $x ) {`, `array( 'a' )`.
- **Long array syntax only:** `array( 1, 2 )`. Short `[ 1, 2 ]` is an error.
- An associative array with more than one item must be multi-line:
  ```php
  $args = array(
  	'post_type' => 'post',
  	'fields'    => 'ids',
  );
  ```
- `elseif`, not `else if`. Opening brace on the same line. One statement per line. No closing `?>` at file end.
- Names: functions, methods, variables in `snake_case`. Classes `Upper_Snake_Case`. Class files named `class-my-thing.php` for `class My_Thing`.
- **If the repo has its own `phpcs.xml` / `.phpcs.xml.dist`, that file wins** (the pipeline uses it too). Read it first, and follow the style of the existing code in the folder you edit. Example: Mint LMS `src/` uses PSR-4 classes (`src/Infrastructure/Ai/AiSettings.php`), `camelCase` methods and variables, text domain `mint-lms` and prefix `mintlms`. Do not rename existing code to another style.
- Methods need visibility (`public function`). No empty `if`/`foreach` bodies. No assignment inside `if ( $x = foo() )`.
- Run `bash wp-qa-check.sh --fix` to let phpcbf fix most formatting.

## 4. Security (errors)

**Input** (`$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, `$_SERVER`, `$_FILES`): check `isset()`, then `wp_unslash()`, then sanitize **for its type**:
```php
$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
$id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$key   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
$url   = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
$html  = isset( $_POST['body'] ) ? wp_kses_post( wp_unslash( $_POST['body'] ) ) : '';
```
Then **validate** against allowed values (`in_array( $key, array( 'a', 'b' ), true )`). Sanitizing is not validating. Never sanitize passwords (check them, do not alter them). Sanitize once, at the entry point.

**Nonce** before using form/AJAX input: `check_admin_referer()`, `check_ajax_referer()` or `wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'action' )`.
A nonce is not permission: also check `current_user_can( 'capability' )` for anything that reads private data or changes something.

**Output:** escape at the point of output, for its context: `esc_html()`, `esc_attr()`, `esc_url()`, `esc_textarea()`, `wp_kses_post()`, `wp_json_encode()`. Use `esc_html_e()` / `esc_html__()` instead of `_e()` / `__()` when echoing. Never `echo $var;` unescaped.

**SQL:** every query with a variable goes through `$wpdb->prepare()` with `%d` / `%s` / `%i` placeholders. Prefer WP APIs (`get_posts`, `WP_Query`, `get_option`, `update_post_meta`) over direct `$wpdb` queries (warning).

**Banned (errors):** `extract()`, `eval()`, `date()` (use `gmdate()` / `wp_date()`), backticks, `create_function`, `goto`.

## 5. EvolveQA custom rules (errors unless noted)

**Globals** (`EvolveQA.Globals.GlobalAccess`): `global $x` and `$GLOBALS['x']` are checked.
- Allowed (no API exists): `$wpdb`, `$wp_filesystem`, `$wp_rewrite`, `$wp`, `$pagenow`, `$typenow`, `$hook_suffix`, `$plugin_page`, `$menu`, `$submenu`, `$wp_meta_boxes`, `$wp_admin_bar`, `$wp_locale`, `$wp_embed`, `$wp_customize`, `$content_width`, `$wp_list_table`, and WooCommerce `$theorder`, `$hide_save_button`, `$current_tab`, `$current_section`, `$product_object`.
- Use the API instead:

  | Global | Use |
  |---|---|
  | `$post` | `get_post()` or the `$post` your hook passes |
  | `$current_user`, `$userdata` | `wp_get_current_user()` |
  | `$user_ID` | `get_current_user_id()` |
  | `$wp_version` | `get_bloginfo( 'version' )` |
  | `$wp_query`, `$wp_the_query` | `get_queried_object()`, `get_query_var()`, `is_*()` |
  | `$wp_roles` / `$wp_scripts` / `$wp_styles` | `wp_roles()` / `wp_scripts()` / `wp_styles()` |
  | `$current_screen` | `get_current_screen()` |
  | `$blog_id` | `get_current_blog_id()` |
  | `$table_prefix` | `$wpdb->prefix` |
  | `$woocommerce` / `$product` | `WC()` / `wc_get_product()` |
  | `$shortcode_tags`, `$wp_filter` | `shortcode_exists()`, `has_filter()` |
- Your plugin's own state must not be global: use a class property, a singleton/static property, a function parameter or a return value.
- `$GLOBALS[ $var ]` and looping over `$GLOBALS` are errors.

**REST** (`EvolveQA.Security.RestPermission`): every `register_rest_route()` needs `'permission_callback'`. Use a callback that checks `current_user_can()`. `'__return_true'` is a warning: only for truly public, read-only data.

**AJAX** (`EvolveQA.Security.AjaxHandler`): every `wp_ajax_*` / `wp_ajax_nopriv_*` handler must call `check_ajax_referer()` (or `wp_verify_nonce()`) **inside the handler function** (error). Logged-in `wp_ajax_*` handlers should also call `current_user_can()` (warning). Put the checks in the handler itself, not in a helper, or the check cannot see them.

## 6. Warnings (shown, do not block, still fix when cheap)

Loose `==` (use `===`), `in_array()` without `true`, `@` error suppression, `error_log()` / `var_dump()` / `print_r()`, `json_encode()` (use `wp_json_encode()`), `file_get_contents()` on URLs (use `wp_remote_get()`), `meta_query` / `tax_query` slow queries, direct DB queries without caching, misaligned `=` in consecutive assignments, commented-out code.

## 7. Not checked by tools, still required (reviewer checks these)

- One job per function. Clear names. Early returns / guard clauses instead of deep nesting. No duplicated logic: reuse a helper.
- Capability check matches the action (`manage_options` for settings, `edit_post` with the post ID for a post).
- Check the user owns the object they ask for (no IDOR: never trust an ID from the request alone).
- File uploads: `wp_handle_upload()`, check type with `wp_check_filetype_and_ext()`. No user-controlled file paths.
- Redirects: `wp_safe_redirect()` then `exit;`.
- No `unserialize()` on user data. No secrets/API keys in code: use options or constants from `wp-config.php`.
- Text domain matches the plugin slug in every `__()`, `esc_html__()` etc.
- Do not refactor or reformat code unrelated to the task.

## 8. Local check

`bash wp-qa-check.sh` uses the same rules as the pipeline, read live from `elearning-evolve/ci-workflows@v1`.
Exit 0 / `PASS` means the pipeline will pass PHPCS. The pipeline also runs `php -l` on the lowest PHP version, which your laptop may not have, so follow section 2.

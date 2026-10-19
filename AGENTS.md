# リポジトリガイドライン

## プロジェクト構成

Laravel 10 を使った投稿アプリです。認証には Fortify、データベースには MySQL、実行環境には Laravel Sail を使用します。

- `app/`: コントローラー、Eloquent モデル、認可ポリシー、Fortify のアクションを配置します。
- `routes/web.php`: ブラウザ向けのルートを定義します。Blade テンプレートは `resources/views/` に配置します。
- `resources/css/`・`resources/js/`: Vite で処理するアセットを配置します。`public/` は公開ディレクトリです。
- `database/`: マイグレーション、ファクトリー、シーダーを配置します。
- `tests/Feature/`・`tests/Unit/`: PHPUnit のテストを配置します。
- `docs/`: 学習者が作成する設計書を配置します。`answers/` は Tutorial 13 の解答例で、アプリのコードではありません。

## ビルド・テスト・開発コマンド

初回の依存パッケージのインストールは `README.md` に従ってください。Sail を使う前に Docker を起動します。

- `./vendor/bin/sail up -d`: アプリと関連サービスを起動します。
- `./vendor/bin/sail artisan key:generate`: `.env` の作成後にアプリケーションキーを生成します。
- `./vendor/bin/sail artisan migrate --seed`: テーブルを作成し、練習用のユーザー・カテゴリ・投稿を登録します。
- `./vendor/bin/sail npm install`: フロントエンドの依存パッケージをインストールします。
- `./vendor/bin/sail npm run dev`: 開発用の Vite サーバーを起動します。
- `./vendor/bin/sail npm run build`: 本番用のアセットをビルドします。
- `./vendor/bin/sail artisan test`: テストを実行します。
- `./vendor/bin/sail down`: サービスを停止します。

## コーディングスタイルと命名規則

`.editorconfig` に従い、UTF-8、LF 改行、スペース 4 個のインデント、ファイル末尾の改行を使用します。YAML のインデントはスペース 2 個です。既存の Laravel の慣例に合わせ、クラス名は `PostController` のような PascalCase、メソッド名は camelCase、データベースのカラム名は snake_case にします。マイグレーションのファイル名にはタイムスタンプを付けます。認可処理はポリシーにまとめ、コントローラーで適用してください。PHP の整形には `./vendor/bin/sail bin pint` を使用します。

## テスト方針

PHPUnit 10 を使用し、設定は `phpunit.xml` にあります。ファイル名は `*Test.php`、メソッド名は `test_descriptive_behavior` のように振る舞いが分かる名前にします。ルート、バリデーション、認証、投稿の所有者による操作制限は機能テストで、独立したロジックは単体テストで検証します。対象を絞る場合は `./vendor/bin/sail artisan test --filter=PostTest` を実行します。データベースを使うテストの前に、`testing` データベースが利用できることを確認してください。カバレッジの最低基準は設定されていません。

## コミットとプルリクエスト

コミット履歴には通常の説明文と、最近の `docs:` プレフィックスが混在しており、厳密な規則は確立されていません。変更内容が伝わる簡潔な件名を付け、変更の目的を絞ってください。プルリクエストには変更内容と実施した検証を記載し、関連する Issue があればリンクします。画面の見た目を変更した場合はスクリーンショットを添付してください。

## セキュリティと設定

ローカル設定は `.env.example` を `.env` にコピーして作成します。秘密情報や実際のユーザーデータはコミットしないでください。シーダーで作成する認証情報は練習専用です。投稿の編集・削除を変更する際は、サーバー側での所有者チェックを維持してください。

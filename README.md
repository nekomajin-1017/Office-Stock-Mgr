# オフィス在庫管理アプリ（OfficeStockMgr）

商品・仕入・販売・在庫を一元管理する、Laravel 製の業務アプリケーションです。

仕入・販売伝票の確定や取消に連動して在庫を更新し、商品ごとの入出庫履歴や各種レポートを確認できます。

## 主な機能

### 共通機能

- ユーザー登録・ログイン（Laravel Fortify）
- 一般ユーザー／管理者によるロール別アクセス制御
- 商品の登録・編集・検索・無効化
- 仕入先・顧客の登録・編集・検索・有効状態の切替
- 仕入伝票の下書き登録・編集・削除・確定
- 販売伝票の下書き登録・編集・削除・確定
- 伝票の確定に連動した在庫更新
- 在庫一覧、在庫不足商品の絞り込み、商品別の在庫履歴
- 確定済み販売伝票の納品書 PDF 出力
- 未販売商品、最新仕入単価、在庫不足商品、平均販売数超過商品、商品別販売ランキングの表示

### 管理者専用機能

- カテゴリ管理
- ユーザー管理
- 確定済みの仕入・販売伝票の訂正・取消

### アプリケーション仕様

- 仕入・販売伝票は「下書き」「確定済み」「取消済み」の 3 状態で管理します。
- 伝票を編集・削除できるのは下書き状態のみです。確定すると在庫へ反映されます。
- 販売数量が現在庫数を超える場合、販売伝票の登録・確定はできません。
- 確定済み伝票の訂正・取消は管理者のみ実行できます。訂正時は在庫を戻して下書き状態へ変更し、取消時は理由を記録して在庫を戻します。ただし、仕入後に在庫が払い出されているなど、仕入の取消数量を確保できない場合は訂正・取消できません。
- 在庫とレポートには確定済み伝票のみを反映します。無効商品、下書き伝票、取消済み伝票は集計対象外です。
- 在庫の平均原価は移動平均法で算出します。仕入確定時に「（仕入前の在庫数量 × 平均原価）＋ 今回の仕入金額」を仕入後の在庫数量で割り、小数第 2 位まで（第 3 位を四捨五入）保持します。
- 仕入単価と販売単価は税抜価格として扱います。現時点では消費税計算を実装していないため、税額は 0 円です。
- 自己登録したユーザーには一般ユーザーロールが付与されます。
- 一般ユーザーと管理者は同じログイン画面を使用し、ロールに応じて利用可能な機能が変わります。
- メール認証機能は実装していません。

## 使用技術

| 分類 | 技術・バージョン |
| --- | --- |
| バックエンド | PHP 8.5、Laravel 13.20.0 |
| データベース | MySQL 8.4、phpMyAdmin |
| 認証 | Laravel Fortify 1.37.2 |
| PDF 出力 | Laravel Dompdf 3.1.2（Dompdf 3.1.6） |
| フロントエンド | Vite 8、Tailwind CSS 4 |
| 開発環境 | Laravel Sail、Docker |

バージョンは `compose.yaml`、`composer.lock`、`package-lock.json` に記録された値を基準にしています。

## セットアップ

### 1. 必要なソフトウェア

- Docker Desktop
- Git

PHP や Composer をホストへインストールする必要はありません。Laravel Sail の Composer イメージを使用します。

### 2. リポジトリの取得と依存パッケージのインストール

```bash
git clone https://github.com/nekomajin-1017/Office-Stock-Mgr.git
cd Office-Stock-Mgr

docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs

cp .env.example .env
```

### 3. 環境変数の設定

`.env` のデータベース設定を次の値に変更してください。

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=office_stock_mgr
DB_USERNAME=sail
DB_PASSWORD=password
```

### 4. アプリケーションの初期化

コンテナを起動し、アプリケーションキー、データベース、フロントエンド資産を準備します。

```bash
./vendor/bin/sail up -d --wait
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

初期化が完了したら、<http://localhost> へアクセスしてください。

## よく使うコマンド

### コンテナの起動

```bash
./vendor/bin/sail up -d
```

### コンテナの停止

```bash
./vendor/bin/sail down
```

### データベースの再作成と初期データ投入

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### 初期データのみ再投入

```bash
./vendor/bin/sail artisan db:seed
```

### テスト実行

```bash
./vendor/bin/sail test
```

## 主要 URL

| 画面 | URL | 利用権限 |
| --- | --- | --- |
| アプリ入口 | <http://localhost> | 全ユーザー |
| ログイン | <http://localhost/login> | 未ログインユーザー |
| ユーザー登録 | <http://localhost/register> | 未ログインユーザー |
| 商品管理 | <http://localhost/products> | 全ユーザー |
| 仕入先管理 | <http://localhost/suppliers> | 全ユーザー |
| 仕入管理 | <http://localhost/purchases> | 全ユーザー |
| 販売管理 | <http://localhost/sales> | 全ユーザー |
| 顧客管理 | <http://localhost/customers> | 全ユーザー |
| 在庫管理 | <http://localhost/stocks> | 全ユーザー |
| レポート | <http://localhost/reports> | 全ユーザー |
| カテゴリ管理 | <http://localhost/categories> | 管理者のみ |
| ユーザー管理 | <http://localhost/users> | 管理者のみ |
| phpMyAdmin | <http://localhost:8080> | 開発用 |

`/` は `/products` へリダイレクトします。未ログインの場合は、ログイン画面へ遷移します。

## デモユーザー

`./vendor/bin/sail artisan migrate:fresh --seed` の実行後に利用できます。

| ロール | メールアドレス | パスワード |
| --- | --- | --- |
| 管理者 | `admin@example.com` | `Coachtech777` |
| 一般ユーザー | `staff1@example.com` | `Coachtech777` |
| 一般ユーザー | `staff2@example.com` | `Coachtech777` |
| 一般ユーザー | `staff3@example.com` | `Coachtech777` |

## 設計資料

- [ER 図（Draw.io）](./er.png)

## 本番環境の設定

本番環境では、例外の詳細や環境情報が画面へ表示されないよう、`.env` を次のように設定してください。

```dotenv
APP_ENV=production
APP_DEBUG=false
```

設定変更後は、本番環境のコンテナ内で設定キャッシュを再生成します。

```bash
php artisan config:cache
```

`.env` は Git へコミットせず、デプロイ先の環境変数またはシークレット管理機能で管理してください。

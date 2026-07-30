# オフィス在庫管理アプリ（OfficeStockMgr）

商品、仕入、販売、在庫を一元管理する Laravel 製の業務アプリケーションです。

仕入・販売伝票の確定と取消に連動して在庫を更新し、在庫履歴や各種レポートを確認できます。

## 機能

- ユーザー登録 / ログイン（Laravel Fortify）
- ロール別アクセス制御（一般ユーザー / 管理者）
- 商品の登録・編集・検索・無効化
- カテゴリ管理（管理者のみ）
- 仕入先・顧客の登録・編集・検索・有効状態の切替
- 仕入伝票の下書き登録・編集・削除・確定・取消
- 販売伝票の下書き登録・編集・削除・確定・取消
- 仕入・販売の確定および取消に連動した在庫更新
- 在庫一覧、在庫不足商品の絞り込み、商品別在庫履歴
- 確定済み販売伝票の納品書 PDF 出力
- 未販売商品、最新仕入単価、在庫不足商品、平均販売数超過商品、商品別販売ランキングの表示
- ユーザー管理（管理者のみ）

### 備考

- 仕入・販売伝票は「下書き」「確定済み」「取消済み」の状態で管理します。
- 在庫とレポートの集計対象は確定済み伝票です。無効商品、下書き伝票、取消済み伝票は除外します。
- 自己登録したユーザーには一般ユーザーロールが付与されます。
- 管理者専用のログイン画面はありません。一般ユーザーと管理者は同じログイン画面を使用し、ロールに応じて利用可能な機能が変わります。
- メール認証機能は実装していません。

## セットアップ

### 1. 前提

- Docker Desktop
- Git

PHP や Composer をホストへインストールせず、Laravel Sail の Composer イメージを利用する手順です。

### 2. 初期起動

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

`.env` のデータベース設定を次のように変更してください。

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=office_stock_mgr
DB_USERNAME=sail
DB_PASSWORD=password
```

コンテナを起動し、アプリケーションキー、データベース、フロントエンド資産を準備します。

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

通常起動:

```bash
./vendor/bin/sail up -d
```

停止:

```bash
./vendor/bin/sail down
```

DB 再作成 + Seed:

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

Seed のみ再投入:

```bash
./vendor/bin/sail artisan db:seed
```

### 本番環境の設定

本番環境では、例外の詳細や環境情報が画面へ表示されないように `.env` を次のように設定してください。

```dotenv
APP_ENV=production
APP_DEBUG=false
```

設定変更後は、本番環境のコンテナ内で設定キャッシュを再生成してください。

```bash
php artisan config:cache
```

`.env` はGitへコミットせず、デプロイ先の環境変数またはシークレット管理機能で管理してください。

## テスト実行

```bash
./vendor/bin/sail test
```

## 設計資料

- [ER 図（Draw.io）](./er.drawio)

## 使用技術（実行環境）

- PHP 8.5（Laravel Sail）
- Laravel 13.20.0
- MySQL 8.4
- phpMyAdmin
- Laravel Fortify 1.37.2
- Laravel Dompdf 3.1.2（Dompdf 3.1.6）
- Vite 8
- Tailwind CSS 4

バージョンは `compose.yaml`、`composer.lock`、`package-lock.json` に記録された値を基準にしています。

## 主要 URL

- アプリ入口: <http://localhost>
  - `/` は `/products` にリダイレクトします。
  - 未ログイン時はログイン画面へ遷移します。
- ログイン: <http://localhost/login>
- ユーザー登録: <http://localhost/register>
- 商品管理: <http://localhost/products>
- 仕入先管理: <http://localhost/suppliers>
- 仕入管理: <http://localhost/purchases>
- 販売管理: <http://localhost/sales>
- 顧客管理: <http://localhost/customers>
- 在庫管理: <http://localhost/stocks>
- レポート: <http://localhost/reports>
- カテゴリ管理（管理者のみ）: <http://localhost/categories>
- ユーザー管理（管理者のみ）: <http://localhost/users>
- phpMyAdmin: <http://localhost:8080>

## デモユーザー

`./vendor/bin/sail artisan migrate:fresh --seed` の実行後に利用できます。

| ロール | メールアドレス | パスワード |
|---|---|---|
| 管理者 | `admin@example.com` | `Coachtech777` |
| 一般ユーザー | `staff1@example.com` | `Coachtech777` |
| 一般ユーザー | `staff2@example.com` | `Coachtech777` |
| 一般ユーザー | `staff3@example.com` | `Coachtech777` |

## レポート仕様

### 未販売商品

商品ごとに確定済み販売明細が存在しないことを `NOT EXISTS` で判定します。結合結果の `NULL` 判定ではなく存在判定を使うため、販売明細が複数件あっても重複行を作りません。

```sql
SELECT *
FROM products
WHERE is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM sale_items
      JOIN sales ON sales.id = sale_items.sale_id
      WHERE sale_items.product_id = products.id
        AND sales.status = 'confirmed'
  );
```

### 最新仕入単価

商品に紐づく確定済み仕入明細を、仕入日と明細 ID の降順で 1 件に絞る相関サブクエリです。同日に複数の仕入がある場合は、明細 ID が大きいレコードを最新とします。仕入履歴がない商品は `NULL` となり、画面では「仕入履歴なし」と表示します。

### 在庫不足商品

商品と在庫を左結合し、在庫レコードがない商品は `COALESCE` により在庫数 0 として扱います。`現在庫数 <= 発注基準数` の商品を抽出し、`発注基準数 - 現在庫数` を不足数として計算します。

### 販売数量・ランキング

確定済み販売明細を商品ごとに集計するサブクエリで、販売数量と販売金額を算出します。平均販売数超過商品は集計結果の平均値と比較して抽出し、ランキングは販売数量の降順、同数の場合は商品コード順で並べます。対象期間と表示件数は集計時に適用します。

### 在庫一覧 PDF

在庫一覧には検索・絞り込み・ページネーションを備えた業務画面があり、現時点では PDF 出力を実装していません。固定時点の棚卸し帳票などが必要になった場合に追加する想定です。

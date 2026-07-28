<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# レポートSQL

すべてのレポートは無効商品、下書き伝票、取消済み伝票を集計対象から除外します。

## 未販売商品

商品ごとに確定済み販売明細が存在しないことを`NOT EXISTS`で検証します。結合結果の`NULL`判定ではなく存在判定を使うため、販売明細が複数件あっても重複行を作りません。

```sql
SELECT * FROM products
WHERE is_active = 1
AND NOT EXISTS (
    SELECT 1
    FROM sale_items
    JOIN sales ON sales.id = sale_items.sale_id
    WHERE sale_items.product_id = products.id
      AND sales.status = 'confirmed'
);
```

## 最新仕入単価

商品に紐づく確定済み仕入明細を仕入日、明細IDの降順で1件に絞る相関サブクエリです。同日複数仕入時は明細IDが大きい方を最新とします。仕入履歴がない商品は`NULL`となり、画面では「仕入履歴なし」と表示します。

## 在庫不足商品

商品と在庫を左結合し、在庫レコードがない商品は`COALESCE`で在庫数0として扱います。`現在庫数 <= 発注基準数`の商品を抽出し、`発注基準数 - 現在庫数`を不足数として計算します。

## 販売数量・ランキング

確定済み販売明細を商品ごとに集計するサブクエリで販売数量と販売金額を算出します。平均超過商品はこの集計結果の平均販売数と比較して抽出し、ランキングは販売数量の降順、同数時は商品コード順で並べます。対象期間と表示件数は集計時に適用します。

## 在庫一覧PDF

在庫一覧は検索・絞り込み・ページネーションを備えた業務画面で確認でき、帳票として配布する要件はありません。そのため現時点ではPDF出力を実装しません。外部提出や棚卸し用の固定時点帳票が必要になった場合に追加します。

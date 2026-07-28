# TIF 2026 推し巡りプランナー

TIF（TOKYO IDOL FESTIVAL 2026）向けの非公式 Web アプリです。  
ステージ間の移動時間を考慮して、推しのライブが**間に合うか**、**いつ出発すべきか**を自動判定します。

## 機能（Phase 1 MVP）

- 推しリスト作成（ゲストはセッション、ログインユーザーは DB 保存）
- ステージ間移動時間マトリクス
- 間に合うか判定エンジン（✅ / ⚠️ / ❌ / 🚨）
- プラン結果画面（矛盾検出・推奨退場時刻）
- タイムテーブル一覧（現在時刻ハイライト）
- 当日ビュー（NOW / NEXT / 出発アラート）
- 簡易会場マップ

## 技術スタック

- Laravel 12 / PHP 8.2+
- Blade + Alpine.js
- Tailwind CSS（CDN）
- SQLite（開発デフォルト）/ MySQL（Docker Compose 対応）

## セットアップ

```bash
cd C:\Users\aoi\develop\tif-planner
composer install
copy .env.example .env   # 初回のみ
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

ブラウザで http://127.0.0.1:8000 を開きます。

### MySQL を使う場合

```bash
docker compose up -d
```

`.env` を以下のように変更:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=tif_planner
DB_USERNAME=tif
DB_PASSWORD=secret
```

その後:

```bash
php artisan migrate:fresh --seed
```

## テスト

```bash
php artisan test
```

## データ追加

### CSV インポート（推奨）

1. `/import` ページから CSV をアップロード
2. または CLI: `php artisan tif:import-csv database/data/tif2026_day2_hot_stage.csv`

CSV 形式:

```csv
day,stage_slug,artist_name,starts_at,ends_at,notes
2026-08-01,hot-stage,=LOVE,20:25,20:55,
```

`database/data/` に DAY2 の HOT STAGE / SMILE GARDEN / DOLL FACTORY データ済み。

### タイムテーブル画像

1. `/import` から画像をアップロード（保管用）
2. 画像を見ながら CSV を作成
3. CSV をインポート

※ 自動 OCR には Tesseract のインストールが必要（未インストール環境では CSV 運用）

## 使い方

1. **推しリスト編集** で =LOVE, わーすた, ≠ME などを検索して追加
2. **プラン結果** で移動判定を確認
3. **当日ビュー** で NOW / NEXT / 出発アラートを確認

## サンプルシナリオ（DAY2）

| 推し | ステージ | 時間 |
|------|----------|------|
| ≠ME | HOT STAGE | 18:30-18:50 |
| =LOVE | HOT STAGE | 18:50-19:10 |
| わーすた | SMILE GARDEN | 20:00-20:20 |

HOT STAGE 内の ≠ME → =LOVE は同一ステージのため ✅。  
=LOVE 終了後に SMILE GARDEN の わーすた へは移動約 10 分 + バッファが必要です。

## Disclaimer

**非公式ツール**です。タイムテーブル・移動時間は目安値です。  
公式情報と異なる場合は [TIF 公式サイト](https://official.idolfes.com/s/tif2026/page/timetable) を正としてください。

## 今後（Phase 2/3）

- CSV インポート
- ブラウザ通知
- 管理画面 CRUD
- 入場規制の手動フラグ

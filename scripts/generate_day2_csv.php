<?php

/**
 * Generate DAY2 CSV files from official TIF2026 timetable HTML structure.
 * Data sourced from https://official.idolfes.com/s/tif2026/page/timetable (2026-07-26).
 */

$day = '2026-08-01';

$stages = json_decode(<<<'JSON'
[
  {"slug":"hot-stage","items":[{"time":"09:50-10:20","name":"UtaGe!"},{"time":"10:30-11:00","name":"INUWASI"},{"time":"11:10-11:40","name":"つばきファクトリー"},{"time":"11:50-12:20","name":"Devil ANTHEM."},{"time":"12:30-13:00","name":"夜光性アミューズ"},{"time":"13:10-13:25","name":"TIF2026×UNIDOLステージ 高嶺のなでしこ"},{"time":"13:35-14:05","name":"Appare!"},{"time":"14:15-14:45","name":"i☆Ris"},{"time":"14:55-15:25","name":"iLiFE! × NEO JAPONISM"},{"time":"15:35-16:05","name":"HKT48"},{"time":"16:25-16:55","name":"虹のコンキスタドール"},{"time":"17:05-17:35","name":"のんふぃく！"},{"time":"17:45-18:15","name":"SKE48"},{"time":"18:25-18:55","name":"僕が見たかった青空"},{"time":"19:05-19:35","name":"≒JOY"},{"time":"19:45-20:15","name":"≠ME"},{"time":"20:25-20:55","name":"=LOVE"}]},
  {"slug":"heat-garage","items":[{"time":"10:00-10:30","name":"Tシャツ着用者限定ステージ HEROINES DAY TENRIN / MEGAFON / 夜光性アミューズ"},{"time":"10:45-11:10","name":"ロージークロニクル"},{"time":"11:10-11:35","name":"AVAM"},{"time":"11:35-12:00","name":"AdamLilith"},{"time":"12:10-12:35","name":"手羽先センセーション"},{"time":"12:35-13:00","name":"PIGGS"},{"time":"13:00-13:25","name":"i-COL"},{"time":"13:25-13:50","name":"MyDearDarlin'"},{"time":"14:05-15:05","name":"TIF ASIA TOUR 2026 スペシャルライブステージ"},{"time":"15:15-15:40","name":"後藤真希"},{"time":"15:40-16:05","name":"@onefive"},{"time":"16:05-16:30","name":"CYNHN"},{"time":"16:40-17:05","name":"衛星とカラテア"},{"time":"17:05-17:30","name":"Palette Parade"},{"time":"17:30-17:55","name":"ジエメイ"},{"time":"18:10-18:35","name":"AsIs"},{"time":"18:35-19:00","name":"Mirror,Mirror"},{"time":"19:00-19:25","name":"MEGAFON"},{"time":"19:35-20:00","name":"Onephony"},{"time":"20:00-20:25","name":"FES☆TIVE"},{"time":"20:25-20:50","name":"fav me"}]},
  {"slug":"smile-garden","items":[{"time":"09:45-10:00","name":"ラジオ体操"},{"time":"10:00-10:20","name":"KAWAII LAB. MATES"},{"time":"10:20-10:40","name":"ラナキュラ"},{"time":"10:40-11:00","name":"THE ORCHESTRA TOKYO"},{"time":"11:10-11:30","name":"i-COL"},{"time":"11:30-11:50","name":"アンスリューム"},{"time":"11:50-12:10","name":"二丁目の魁カミングアウト"},{"time":"12:20-12:40","name":"Peel the Apple"},{"time":"12:45-13:05","name":"ラフ×ラフ"},{"time":"13:05-13:25","name":"なみだ色の消しごむ"},{"time":"13:35-13:55","name":"ばってん少女隊"},{"time":"13:55-14:15","name":"きのホ。"},{"time":"14:15-14:35","name":"fishbowl"},{"time":"14:45-15:05","name":"にしたんクリニック タンバリンダンス選手権 タイトル未定 / Merry BAD TUNE. / ラフ×ラフ"},{"time":"15:05-15:20","name":"AOSTARIA TIF de Debut 2026"},{"time":"15:20-15:40","name":"ハルニシオン"},{"time":"15:40-16:00","name":"CiON"},{"time":"16:10-16:30","name":"CYBERJAPAN DANCERS"},{"time":"16:30-16:55","name":"スペシャルコラボステージ Jams Collection × Rain Tree"},{"time":"16:55-17:15","name":"Ringwanderung"},{"time":"17:25-17:45","name":"タイトル未定"},{"time":"17:55-18:15","name":"高嶺のなでしこ"},{"time":"18:15-18:35","name":"諸橋沙夏（=LOVE）"},{"time":"18:55-19:30","name":"IDOL SUMMER JAMBOREE ACOUSTIC"},{"time":"19:40-20:00","name":"Task have Fun"},{"time":"20:00-20:20","name":"わーすた"},{"time":"20:20-20:40","name":"iLiFE!"}]},
  {"slug":"doll-factory","items":[{"time":"10:00-10:20","name":"愛乙女☆DOLL"},{"time":"10:20-10:40","name":"シャニムニ=パレード ONLY FIVEステージ"},{"time":"10:40-11:00","name":"ラストシーン"},{"time":"11:05-11:25","name":"W.ダブルヴィー"},{"time":"11:25-11:45","name":"RiNCENT♯"},{"time":"11:45-12:05","name":"葵乃まみ Road to TIF 2026 屋内ステージブロック2位"},{"time":"12:10-12:30","name":"WHITE SCORPION"},{"time":"12:30-12:50","name":"feelNEO"},{"time":"12:50-13:10","name":"Kolokol"},{"time":"13:20-13:40","name":"カフェオーレ（乃木坂46 五百城茉央＆奥田いろは）"},{"time":"13:40-14:00","name":"Rain Tree"},{"time":"14:00-14:20","name":"iON!"},{"time":"14:25-14:45","name":"himawari（船橋）"},{"time":"14:45-15:05","name":"SITUASION"},{"time":"15:05-15:25","name":"zanka"},{"time":"15:30-16:00","name":"ばってん少女隊"},{"time":"16:00-16:20","name":"ナナコロビヤオキ"},{"time":"16:20-16:40","name":"KLP48"},{"time":"16:45-17:05","name":"Merry BAD TUNE."},{"time":"17:05-17:25","name":"THE ORCHESTRA TOKYO"},{"time":"17:25-17:45","name":"buGG ONLY FIVEステージ"},{"time":"17:50-18:10","name":"Ill"},{"time":"18:10-18:30","name":"Axelight"},{"time":"18:30-18:50","name":"Tohkei"},{"time":"18:55-19:15","name":"アップアップガールズ（2）"},{"time":"19:15-19:35","name":"ネコプラpixx. ONLY FIVEステージ"},{"time":"19:35-19:55","name":"こみっきゅおん!"},{"time":"20:00-20:20","name":"JamsCollection"},{"time":"20:20-20:40","name":"まねきケチャ"},{"time":"20:40-21:00","name":"ドラマチックレコード"}]},
  {"slug":"sky-stage","items":[{"time":"10:15-10:30","name":"ChumToto"},{"time":"10:30-10:45","name":"Pastel Closet"},{"time":"10:45-11:00","name":"Primulav"},{"time":"11:00-11:15","name":"Luce Twinkle Wink☆"},{"time":"11:20-11:35","name":"木苺FRUCTOSE"},{"time":"11:35-11:50","name":"LinQ"},{"time":"11:50-12:05","name":"KLP48"},{"time":"12:05-12:20","name":"@onefive"},{"time":"12:25-12:40","name":"アイテムはてるてるのみ ONLY FIVEステージ"},{"time":"12:40-12:55","name":"KAWAII LAB. MATES"},{"time":"12:55-13:10","name":"すーぱーぷーばぁー!!"},{"time":"13:10-13:25","name":"NANIMONO"},{"time":"14:25-14:40","name":"ラストシーン"},{"time":"14:40-14:55","name":"Falench. ONLY FIVEステージ"},{"time":"14:55-15:10","name":"まねきケチャ"},{"time":"15:10-15:25","name":"さよならステイチューン"},{"time":"15:30-15:45","name":"THE ENCORE"},{"time":"15:45-16:00","name":"ジエメイ"},{"time":"16:00-16:15","name":"NEKIRU"},{"time":"16:15-16:30","name":"Task have Fun"},{"time":"16:35-16:50","name":"さとりモンスター"},{"time":"16:50-17:05","name":"きのホ。"},{"time":"17:05-17:20","name":"Kolokol"},{"time":"17:20-17:35","name":"INUWASI"},{"time":"17:45-18:00","name":"わーすた"},{"time":"18:00-18:15","name":"君に、胸キュン。"},{"time":"18:15-18:30","name":"カラフルスクリーム"},{"time":"18:30-18:45","name":"メイビーME"},{"time":"18:50-19:05","name":"Merry BAD TUNE."},{"time":"19:05-19:20","name":"Devil ANTHEM."},{"time":"19:20-19:35","name":"点染テンセイ少女。"},{"time":"19:35-19:50","name":"Ill"},{"time":"19:55-20:10","name":"SITUASION"},{"time":"20:10-20:25","name":"Ringwanderung"},{"time":"20:25-20:40","name":"衛星とカラテア"}]},
  {"slug":"torocco-park","items":[{"time":"10:00-10:20","name":"可憐なアイボリー"},{"time":"10:20-10:40","name":"すーぱーぷーばぁー!!"},{"time":"10:40-11:00","name":"アップアップガールズ（仮）"},{"time":"11:05-11:25","name":"KAWAII LAB. SOUTH"},{"time":"11:25-11:45","name":"限りなく白く"},{"time":"11:45-12:05","name":"Jewel☆Garden Road to TIF 2026 屋外ステージブロック2位"},{"time":"12:10-12:30","name":"びっくえんじぇる"},{"time":"12:30-12:50","name":"MAGICAL SPEC"},{"time":"12:50-13:10","name":"メイビーME"},{"time":"13:15-13:35","name":"最終未来少女"},{"time":"13:35-13:55","name":"TENRIN"},{"time":"13:55-14:15","name":"ROOKEY♡ROOKEYS"},{"time":"14:25-14:45","name":"フューチャーサイダー"},{"time":"14:45-15:05","name":"カラフルスクリーム"},{"time":"15:05-15:25","name":"fav me"},{"time":"15:30-15:50","name":"LinQ"},{"time":"15:50-16:10","name":"NANIMONO"},{"time":"16:10-16:30","name":"シンデレラ宣言！"},{"time":"16:35-16:55","name":"AdamLilith"},{"time":"16:55-17:15","name":"RAViDAVi"},{"time":"17:15-17:35","name":"NEO JAPONISM"},{"time":"17:40-18:00","name":"クマリデパート"},{"time":"18:00-18:20","name":"アンスリューム"},{"time":"18:20-18:40","name":"iON!"}]},
  {"slug":"ukishima-stage","items":[{"time":"10:00-10:15","name":"さとりモンスター"},{"time":"10:15-10:30","name":"君に、胸キュン。"},{"time":"10:30-10:45","name":"ラフ×ラフ"},{"time":"10:45-11:00","name":"NEKIRU"},{"time":"11:05-11:20","name":"さよならステイチューン"},{"time":"11:20-11:35","name":"THE ENCORE"},{"time":"11:35-11:50","name":"高嶺のなでしこ"},{"time":"11:50-12:05","name":"愛乙女☆DOLL"},{"time":"12:15-12:30","name":"ChumToto"},{"time":"12:30-12:45","name":"Primulav"},{"time":"12:45-13:00","name":"可憐なアイボリー"},{"time":"13:00-13:15","name":"AVAM"},{"time":"13:20-13:35","name":"zanka"},{"time":"13:35-13:50","name":"Axelight"},{"time":"13:50-14:05","name":"点染テンセイ少女。"},{"time":"14:05-14:20","name":"PIGGS"},{"time":"14:30-14:45","name":"ドラマチックレコード"},{"time":"14:45-15:00","name":"アップアップガールズ（仮）"},{"time":"15:00-15:15","name":"二丁目の魁カミングアウト"},{"time":"15:15-15:30","name":"UtaGe!"},{"time":"15:35-15:50","name":"Pastel Closet"},{"time":"15:50-16:05","name":"アップアップガールズ（2）"},{"time":"16:05-16:20","name":"木苺FRUCTOSE"},{"time":"16:20-16:35","name":"MEGAFON"},{"time":"16:45-17:00","name":"Luce Twinkle Wink☆"},{"time":"17:00-17:15","name":"FES☆TIVE"},{"time":"17:15-17:30","name":"feelNEO"},{"time":"17:30-17:45","name":"Appare!"},{"time":"17:45-18:00","name":"CYBERJAPAN DANCERS"}]},
  {"slug":"info-centre","items":[{"time":"10:00-10:45","name":"TIFで起きた朝は 〜今日ここ行きタイッテ！〜"},{"time":"10:55-11:25","name":"TOKYO GRAVURE IDOL FESTIVAL 2026トークの部 DAY2"},{"time":"11:35-12:05","name":"アイふた in TIF出張版"},{"time":"12:25-12:55","name":"ぐんまちゃんアイドルフェスティバル 特別企画"},{"time":"13:05-14:05","name":"ミスFLASH2027 × TIF2026 コラボステージ"},{"time":"14:15-14:45","name":"TIF学園祭スペシャルトークステージ"},{"time":"14:55-15:15","name":"Taskと乾杯！Yakultステージ Task have Fun"},{"time":"15:20-15:40","name":"Appare!と乾杯！Yakultステージ Appare!"},{"time":"15:50-16:50","name":"Jリーグ大好き部 〜開幕直前 みんなでJ〜"},{"time":"17:00-17:30","name":"ガラスガールpresents アイドルがみんなを救う！ 夏の人生相談2026"},{"time":"17:45-18:25","name":"TIF ASIA TOUR 2026 スペシャルトークステージ"},{"time":"18:35-19:35","name":"TIFの新企画を考える会〜部活プレゼン選手権〜"}]}
]
JSON, true);

$dataDir = dirname(__DIR__).'/database/data';

foreach ($stages as $stage) {
    $slug = $stage['slug'];
    $filename = "{$dataDir}/tif2026_day2_{$slug}.csv";
    $lines = ["day,stage_slug,artist_name,starts_at,ends_at,notes"];

    foreach ($stage['items'] as $item) {
        if (! preg_match('/^(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/', $item['time'], $m)) {
            fwrite(STDERR, "Invalid time: {$item['time']} ({$slug})\n");
            continue;
        }

        $name = str_replace('"', '""', $item['name']);
        $lines[] = sprintf(
            '%s,%s,"%s",%s,%s,',
            $day,
            $slug,
            $name,
            $m[1],
            $m[2],
        );
    }

    file_put_contents($filename, implode("\n", $lines)."\n");
    echo "Wrote {$filename} (".(count($lines) - 1)." rows)\n";
}

echo "Done.\n";
